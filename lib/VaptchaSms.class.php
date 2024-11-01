<?php
if ( !defined('ABSPATH') ) {
   exit('No direct script access allowed');
}
//session_start();
class Vaptcha
{
    private $vid;
    private $key;
    // private $publicKey;
    private $lastCheckdownTime = 0;
    private $isDown = false;
    private $config;

    //宕机模式通过签证
    private static $passedSignatures = array();

    public function __construct($vid, $key)
    {
        $this->vid = $vid;
        $this->key = $key;
        $this->config = include(dirname(__FILE__).'/config.php');
    }

    /**
     * 获取流水号
     *
     * @param string $sceneId 场景id
     * @return void
     */
    public function getChallenge($sceneId = 0) 
    {
        $url = $this->config['API_URL'].$this->config['GET_knock_URL'];
        $now = $this->getCurrentTime();
        $query = "id=$this->vid&scene=$sceneId&time=$now&version=".$this->config['VERSION'].'&sdklang='.$this->config['SDK_LANG'];
        $signature = $this->HMACSHA1($this->key, $query);
        if (!$this->isDown)
        {
            $knock = self::readContentFormGet("$url?$query&signature=$signature");
            if ($knock === $this->config['REQUEST_UESD_UP']) {
                self::$passedSignatures = array();
                return $this->getDownTimeCaptcha();
            }
            if (empty($knock)) {
                if ($this->getIsDwon()) {
                    $this->lastCheckdownTime = $now;
                    $this->isDown = true;
                    self::$passedSignatures = array();
                }
                return $this->getDownTimeCaptcha();
            } 
            return json_encode(array(
                "vid" =>  $this->vid,
                "knock" => $knock
            ));
        } else {
        if ($now - $this->lastCheckdownTime > $this->config['DOWNTIME_CHECK_TIME']) {
                $this->lastCheckdownTime = $now;
                $knock = self::readContentFormGet("$url?$query&signature=$signature");
                if ($knock && $knock != $this->config['REQUEST_UESD_UP']){
                    $this->isDown = false;
                    self::$passedSignatures = array();
                    return json_encode(array(
                        "vid" =>  $this->vid,
                        "knock" => $knock
                    ));
                }
            }
            return $this->getDowniTimeCaptcha();
        }
    }

    /**
     * 二次验证
     *
     * @param [string] $knock 流水号
     * @param [sring] $token 验证信息
     * @param string $sceneId 场景ID 不填则为默认场景
     * @return void
     */
    public function validate($server,$knock, $token, $sceneId = 0)
    {
        $str = 'ffline-';
        if (strpos($token, $str, 0))
            return $this->downTimeValidate($token);
        else
            return $this->normalValidate($server,$knock, $token, $sceneId);
    }

    /**
     * @param $smsid
     * @param $smskey
     * @param $phone
     * @param $vcode
     * @return string
     * 验证短信验证码
     */
    public function validateSmsCode($smsid, $smskey, $phone, $vcode ){
        if( !self::isPhoneNumber($phone) || empty($vcode) ){
            return '请填写正确的手机号和验证码。';
        }
        $post_data = array(
            'smsid' => $smsid,
            'smskey' => $smskey,
            'phone' => $phone,
            'vcode' => $vcode,
        );

        $url = 'https://sms.vaptcha.com/verify';
        $args = array(
            'body'        => $post_data,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array("Content-type:application/json;charset='utf-8'",
                "Accept:application/json"),
        );

        $response = wp_remote_post($url,$args);
        $data =  json_decode($response['body']);
        return $data;
    }


    public function getChannelData()
    {
        $url = $this->config['Channel_DownTime'] . $this->vid;
        $response = wp_remote_get($url);
        $res = $response['body'];
        $data = str_replace('static(', '',  $res);
        $data = str_replace(')', '', $data);
        $data = json_decode($data, true);
        return $data;
    }

    private function getPublicKey()
    {
        return self::readContentFormGet($this->config['Channel_DownTime']);
    }

    private function getIsDwon()
    {
        // return !!self::readContentFormGet($this->config['DOWNTIME_URL']) == 'true';
        $channel = self::getChannelData();
        if($channel['state']== 1) {self::$isDown = false;return true;}
        if($channel['offline']== 1) {self::$isDown = true;return true;}
        return false;
    }

    public function downTime($data, $callback, $v=null, $knock=null)
    {
        // return json_encode($data);
        if (!$data)
            return json_encode(array("error" => "params error"));
        $datas = explode(',', $data);
        switch ($datas[0]) {
            case 'get':
                return $this->getDownTimeCaptcha($callback);
            case 'request':
                return $this->getDownTimeCaptcha();
            case 'getsignature':
                if (count($datas) < 2) {
                    return array("error" => "params error");
                } else {
                    $time = (int) $datas[1];
                    if ((bool) $time) {
                        return $this->getSignature($time);
                    } else {
                        return array("error" => "params error");
                    }

                }
            case 'verify':
                if ($v == null) {
                    return array("error" => "params error");
                } else {
                    return $this->downTimeCheck($callback ,$v, $knock);
                }
            default:
                return array("error" => "parms error");
        }
    }

    private function getCurrentTime() {
        return number_format(floor(microtime(true) * 1000), 0, '', '');
    }


    private function getSignature($time)
    {
        $now = $this->getCurrentTime();
        if (($now - $time) > $this->config['REQUEST_ABATE_TIME'])
            return null;
        $signature = md5($now.$this->key);
        return json_encode(array(
            'time' => $now,
            'signature' => $signature
        ));
    }

    public static function set($key, $value, $expire = 600)
    {
        $data = sanitize_text_field($_SESSION[$key]);
        return $_SESSION[$key] = array(
            'value' => $value,
            'create' => time(),
            'readcount' => 0,
            'expire' => $data['expire'] ? $data['expire'] : $expire,
        );
    }

    public static function get($key, $default = null)
    {
        $data = sanitize_text_field($_SESSION[$key]);
        $now = time();
        if (!$data) {
            return $default;
        } else if ($now - $data['create'] > $data['expire']) {
            return $default;
        } else {
            $_SESSION[$key]['readcount']++;
            return $data['value'];
        }
    }

    public function create_uuid($prefix = ""){
        $str = md5(uniqid(mt_rand(), true));   
        $uuid  = substr($str,0,8) . '-';   
        $uuid .= substr($str,8,4) . '-';   
        $uuid .= substr($str,12,4) . '-';   
        $uuid .= substr($str,16,4) . '-';   
        $uuid .= substr($str,20,12);   
        return $prefix . $uuid;
    }

    /**
     * 宕机模式验证
     *
     * @param [int] $time1
     * @param [int] $time2
     * @param [string] $signature
     * @param [string] $captcha
     * @return void
     */
    private function downTimeCheck($callback, $v, $knock)
    {
        $data = $this->getChannelData();
        $dtkey = $data['offline_key'];
        $imgs = $this->get($knock);
        unset($_SESSION[$knock]);
        $address = md5($v.$imgs);
        $url = DOWNTIME_URL.$dtkey.'/'.$address;
        $response = wp_remote_get($url);
        $res = $response['body'];
        $httpCode = $response['response']['code'];
        if($httpCode == 200) {
            $token = 'offline-'.$knock.'-'.$this->create_uuid().'-'.$this->getCurrentTime();;
            $this->set($token, $this->getCurrentTime());
            return $callback.'('.json_encode(array(
                "code" => '0103',
                "msg" => "",
                "token" => $token
            )).')';
        }
        else {
            return $callback.'('.json_encode(array(
                "code" => '0104',
                "msg" => "0104",
                "token" => "",
            )).')';
        }
        
    }

    private function normalValidate($server,$knock, $token, $sceneId)
    {
//        return false;

        if (!$token)
            return false;
        $ip = $this->getClientIp();
        $query = "id=$this->vid&scene=$sceneId&secretkey=$this->key&token=$token&ip=$ip";
        $url = $server.'?' . $query;
        $now = $this->getCurrentTime();
        $response = json_decode(self::postValidate($url, $query));
        return $response->success == 1 ? true : false;
    }

    public function getClientIp()
    {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } else if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } else if (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '127.0.0.1';
        }

        return $ip;
    }

    private function downTimeValidate($token)
    {
        $strs = explode('-', $token);
        if (count($strs) < 2) {
            return false;
        } else {
            $time = (int) $strs[count($strs) - 1];
            // $signature = $strs[1];
            $storageTIme = $this->get($token);
            $now = $this->getCurrentTime();
            // return $time.'  '.($strs[count($strs)]);
            if ($now - $time > $this->config['VALIDATE_PASS_TIME']) {
                return false;
            } else {
                if ($storageTIme && $storageTIme==$time) {
                    return true;
                } else {
                    return false;
                }

            }
        }
    }

    private function getDownTimeCaptcha($callback = null)
    {
        $time = $this->getCurrentTime();
        $md5 = md5($time . $this->key);
        $captcha = substr($md5, 0, 3);
        $data = $this->getChannelData();
        $knock = md5($captcha . $$time . $data['offline_key']);
        $ul = $this->getImgUrl();
        $url = md5($data['offline_key'] . $ul);
        $this->set($knock, $url);
        return $callback===null?array(
            "time" => $time,
            "url" => $url,
        ): $callback.'('.json_encode(array(
            "time" => $time,
            "imgid" => $url,
            "code" => '0103',
            "knock" => $knock,
            "msg" => "",
        )).')';
    }

    private function getImgUrl()
    {
        $str = '0123456789abcdef';
        $data = '';
        for ($i=0; $i < 4; $i++) { 
            # code...
            $data = $data.$str[rand(0, 15)];
        }
        return $data;
    }

    private static function postValidate($url, $data)
    {
         $response = wp_remote_post( $url, array(
            'body' => $data
        ));
        return $response['body'];
    }

    private static function readContentFormGet($url)
    {
        $response = wp_remote_get($url);
        return $response['body'];
    }

    private function HMACSHA1($key, $str)
    {
        $signature = "";  
        if (function_exists('hash_hmac')) {
            $signature = hash_hmac("sha1", $str, $key, true);
        } else {
            $blocksize = 64;  
            $hashfunc = 'sha1';  
            if (strlen($key) > $blocksize) {  
                $key = pack('H*', $hashfunc($key));  
            }  
            $key = str_pad($key, $blocksize, chr(0x00));  
            $ipad = str_repeat(chr(0x36), $blocksize);  
            $opad = str_repeat(chr(0x5c), $blocksize);  
            $signature = pack(  
                    'H*', $hashfunc(  
                            ($key ^ $opad) . pack(  
                                    'H*', $hashfunc(  
                                            ($key ^ $ipad) . $str  
                                    )  
                            )  
                    )  
            );  
        }  
        $signature = str_replace(array('/', '+', '='), '', base64_encode($signature));
        return $signature;  
    }
    /**
     * 验证是否为手机号
     * @param $phone
     *
     * @return bool
     */
    public static function isPhoneNumber($phone)
    {
        return preg_match("/^1[3-9]\d{9}$/", $phone) === 1;
    }
}