<?php
if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

require_once plugin_dir_path(__FILE__) . 'lib/VaptchaSms.class.php';
require_once plugin_dir_path(__FILE__) . 'options.php';

class VaptchaSmsPlugin
{
    private $vaptcha;

    private $options;

//    private $phone;

    public function init()
    {
        register_activation_hook(plugin_dir_path(__FILE__) . 'vaptcha-sms.php', array($this, 'init_default_options'));
        register_activation_hook(plugin_dir_path(__FILE__) . 'vaptcha-sms.php', array($this, 'uninstall'));
        $this->init_add_actions();

        $options = get_option('vaptcha_options');
        $this->vaptcha = new Vaptcha($options['vaptcha_vid'], $options['vaptcha_key']);
        $this->options = $options;
    }

    public function offline()
    {
        $offline_action = sanitize_text_field($_GET['offline_action']);
        $callback = sanitize_text_field($_GET['callback']);
        $v = sanitize_text_field($_GET['v']);
        $knock = sanitize_text_field($_GET['knock']);
        return $this->vaptcha->downTime($offline_action, $callback, $v, $knock);
    }

    /**
     * 加载vaptcha
     */
    private function get_captcha($form, $btn, $type)
    {
        $loading = plugins_url('images/vaptcha-loading.gif', __FILE__);
        $vid = get_option('vaptcha_options')['vaptcha_vid'];
        $lang = get_option('vaptcha_options')['vaptcha_lang'];
        $height = get_option('vaptcha_options')['vaptcha_height'];
        $width = get_option('vaptcha_options')['vaptcha_width'];
        $smsid = get_option('vaptcha_options')['vaptcha_smsid'];
        $smskey = get_option('vaptcha_options')['vaptcha_smskey'];
        $modelid = get_option('vaptcha_options')['vaptcha_modelId'];
        $color = get_option('vaptcha_options')['bg_color'];
        $style = get_option('vaptcha_options')['button_style'];
        $height = $height ? $height : '36px';
        $ajaxUrl = admin_url('admin-ajax.php');

        $options = json_encode(array(
            "vid" => $vid,
            'type' => $type,
            "lang" => $lang,
            "style" => $style,
            "https" => true,
            "color" => $color,
            "offline_server" => site_url() . '/wp-json/vaptcha/offline',
            // 'mode' => 'offline',
        ));
        return <<<HTML
        <style>
            .vaptcha-container{
                height: $height;
                width: $width;
                margin-bottom: 10px;
            }
            .vaptcha-init-main{
                display: table;
                width: 100%;
                height: 100%;
                background-color: #EEEEEE;
            }
            .vaptcha-init-loading {
                display: table-cell;
                vertical-align: middle;
                text-align: center
            }

            .vaptcha-init-loading>a {
                display: inline-block;
                width: 18px;
                height: 18px;
            }
            .vaptcha-init-loading>a img{
                vertical-align: middle
            }
            .vaptcha-init-loading .vaptcha-text {
                font-family: sans-serif;
                font-size: 12px;
                color: #CCCCCC;
                vertical-align: middle
            }
            .vaptcha-init-loading .vaptcha-text a {
                font-family: sans-serif;
                font-size: 12px;
                color: #CCCCCC;
                text-decoration: none;
            }
        </style>
        
        <span id="hidden-vaptcha-vid" data-vaptcha-vid="$vid"></span>
        <span id="hidden-vaptcha-smsid" data-vaptcha-smsid="$smsid"></span>
        <span id="hidden-vaptcha-modelid" data-vaptcha-modelid="$modelid"></span>
        <span id="vaptcha-hidden-url" data-ajax-url="$ajaxUrl"></span>
        <span id="vaptcha-hidden-type" data-vaptcha-type="$type"></span>
HTML;
    }

    //    选择国别码

    function select_code_box()
    {
        $dropdown = plugins_url('images/dropdown.png', __FILE__);

        echo '
            <style>
            .flex{
                display: flex;
                align-items: center;
            }
            .area-code {
                width: 90px;
                height: 38px;
                border: 1px solid #dadada;
                border-right: none;
                margin-bottom: 14px;
            }
            .add{
                color: #cccccc;
                font-size: 18px;
                padding: 0 3px;
            }
            .form-control{
                border: none!important;
                font-size: 15px!important;
                padding: 0!important;
                position: relative!important;
                top: 0!important;
                flex: 1;
                min-height: 26px!important;
                margin: 6px 0!important;
                position: relative;
            }
            .form-control:focus{
                outline:medium!important;
            }
            .dropdown-menu{
            position: relative;
            border: 1px #dadada solid;
            background: white;
            z-index: 1000;
            text-align: center;
            padding: 5px 0;
            }
            .dropdown-menu li {
            list-style:none;
            cursor: pointer;
            line-height: 25px;
            font-size: 15px;
            }
            .dropdown-menu li:hover{
            background: #f1efef;
            }
            
            </style>
			<div id="select_phone">
        		<p><label for="user_phone">手机号</label></p>
        		<div style="display: flex;align-items: center">
                    <div id="phonePrefix" class="area-code">
                        <div class="country-code active flex">
                            <span class="add">+</span>
                            <input type="text" id="country_code" class="form-control" style="border: none;outline: none!important;" value="86" name="country_code">
                            <button id="btn-down" type="button" style="background: none;border: none;outline: none;padding: 0 3px">
                                <img id="dropdown-icon" src="' . $dropdown . '" style="width: 10px;"/>
                            </button>
                        </div> 
                        <ul class="dropdown-menu" id="dropdown-menu" style="display: none;">
                            <li data-value="86">
                                <a class="dropdown-item">
                                    CN +86
                                </a>
                            </li>
                            <li data-value="886">
                                <a class="dropdown-item">
                                    TW +886
                                </a>
                            </li>
                            <li data-value="852">
                                <a class="dropdown-item">
                                    HK +852
                                </a>
                            </li>
                            <li data-value="1">
                                <a class="dropdown-item">
                                    USA +1
                                </a>
                            </li>
                            <li data-value="81">
                                <a class="dropdown-item">
                                    JP +81
                                </a>
                            </li>
                            <li data-value="850">
                                <a class="dropdown-item">
                                    KP +850
                                </a>
                            </li>
                        </ul>    
                    </div>
                    <input type="text" name="user_phone" id="user_phone" style="width: 190px;border-radius: 0 3px 3px 0" class="input" size="20" autocapitalize="off" />
                </div>		
        	</div>';
    }

    function captcha_validate_comment($comment_data)
    {
//        if ($this->options['vaptcha_comment'] == 0) return $comment_data;
//        if (!isset($_POST['vaptcha_challenge'])) {
//            $challenge = '';
//        } else {
//            $challenge = sanitize_text_field($_POST['vaptcha_challenge']);
//        }
//
//        $token = sanitize_text_field($_POST['vaptcha_token']);
//        echo $token;
//        if (!$token || !$this->vaptcha->validate($challenge, $token)) {
//            wp_die(__('人机验证未通过-评论' . $token, 'vaptcha'));
//        }
        return $comment_data;
    }

    /**
     * 修改登录表单
     */
    function captcha_in_login_form()
    {
        //判断是否在登陆时出现vaptcha验证，为off时不验证
        if ($this->options['button_no_secret'] == 'off') return;
        else {
            $ajaxUrl = admin_url('admin-ajax.php');
            if ($this->options['button_international'] == 'off') {
                echo '<p id="phone-verify-fields">
        		<label for="user_phone">手机号</label>
        		<input type="text" name="user_phone" id="user_phone" class="input" size="20" autocapitalize="off" />
        	</p>
			';
            } else {
                echo $this->select_code_box();
            };
            echo <<<HTML
            <style>
            #loginform{
            padding-bottom: 120px;
            }
            #wp-submit{
            display: none;
            }
            </style>
            <div id="sms-code" style="display: none">
			<span id="vaptcha-hidden-url" data-ajax-url="$ajaxUrl"></span>
				<p><label for="">短信验证码</label></p>
				<input type="text" name="sms_code" id="sms_code" style="width: 150px;" autocapitalize="off" />
				<button type="button" id="get_login_sms_code" class="button" style="height: 40px;width: 110px">发送验证码</button>
			</div>
            <script>
             jQuery('#user_login').parent().hide();
             jQuery('#user_pass').parent().hide();
             jQuery('.user-pass-wrap').hide();
            </script>
HTML;
            echo '<p id="smsForm" style="margin-top: 80px;position: absolute;text-align: center;width: 270px">
                    <span style="cursor:pointer;color: #2271b1" id="name-or-email-login" >账号密码登录</span>
                    <span id="phone-login" style=" cursor:pointer;display: none;color: #2271b1">免密登录</span>
                    <input type="hidden" name="reset_way" id="reset_way" value="phone">
            </p>';
            echo $this->get_captcha('loginform', 'submit', 'click');
            echo <<<HTML
			<button type="button" id="submit_form_button" class="button" style="height: 32px;float: right;background: #2271b1;color: white;">免密登录</button>
            <button type="button" id="sms_login_button" class="button" style="display: none;padding: 0 15px;height: 32px;float: right;background: #2271b1;color: white;">登录</button>
HTML;
        }
        //在登陆界面输出人机验证


    }

    /**
     * @param $error
     * @param $user
     * @return mixed|WP_Error|WP_User
     * 验证手机免密登录
     */
    function captcha_validate_login($error, $user)
    {
        $phone = sanitize_text_field($_POST['user_phone']);
        //非手机号和验证码登录直接返回
        if (empty($phone)) {
            //验证账号密码是否输入的是手机号
            return $error;
        } else {
            //通过手机号查找用户
            $args = array(
                'meta_key' => 'phone',
                'meta_value' => $phone,
            );
            $phoneFindUser = get_users($args);
            if (!empty($phoneFindUser)) {
                $phoneFindUser = $phoneFindUser[0];
            }
            //通过用户名和手机号都未找到用户
            if (!($phoneFindUser instanceof WP_User)) {
                return new WP_Error('captcha_wrong', __('该手机号未绑定用户。', 'vaptcha'));
            }

            $currentUser = ($phoneFindUser instanceof WP_User) ? $phoneFindUser : $error;
            return $currentUser;
        }
    }

    /**
     * 手机号和密码登录
     * @param $username
     * @param $raw_username
     * @param $strict
     * @return string
     */
    function yang_allow_phone_login($username, $raw_username, $strict)
    {
        if (!empty($raw_username) && preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $raw_username)) {
            global $wpdb;
            $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta jw WHERE jw.meta_key='%s' AND jw.meta_value='%s'", 'phone', $raw_username));
            if (empty($user_id)) {
                wp_die(__('ERROR: There is no user registered with that phone number.'), '用户名不正确');
            } else {
                if (function_exists('get_user_by')) {
                    include_once(ABSPATH . 'wp-includes/pluggable.php');
                }
                $user = get_user_by('id', $user_id);
                return $user->user_login;
            }
        } else {
            return $username;
        }
    }


    /**
     * 修改注册表单
     */
    function captcha_in_register_form()
    {
        if ($this->options['button_international'] == 'off') {
            echo '<p>
        		<label for="user_phone">手机号</label>
        		<input type="text" name="user_phone" id="user_phone" class="input" size="20" autocapitalize="off" />
        	</p>';
        } else {
            echo $this->select_code_box();
        }

        echo '
            <style>
            #wp-submit{
            display: none;
            }
            </style>
            <p>
				<p><label for="">短信验证码</label></p>
				<input type="text" name="register_sms_code" id="register_sms_code" style="width: 150px;" autocapitalize="off" />
				<button type="button" id="get_sms_code" class="button" style="height: 40px;width: 110px">发送验证码</button>
			</p>
			<p><button type="button" id="submit-btn" class="button" style="height: 32px;padding: 0 15px;float: right;background: #2271b1;color: white;margin-top: 30px">注册</button></p>
';
        echo $this->get_captcha('registerform', 'submit', 'invisible');
    }

    /**
     * @param $errors
     * @return mixed
     * 注册验证
     */
    function captcha_validate_register($errors)
    {
        return $errors;
    }

    /**
     * @param $user_id
     * 保存用户手机号码
     */
    function user_register_prestige($user_id)
    {
        $user_phone = sanitize_text_field($_POST['user_phone']);
        update_user_meta($user_id, 'phone', $user_phone);
    }

    function vaptcha_settings_init()
    {
        register_setting('vaptcha_options_group', 'vaptcha_options', array($this, 'validate_options'));
    }

    /**
     * @param $input
     * @return array
     * 保存插件设置
     */
    function validate_options($input)
    {
        $validated['vaptcha_vid'] = sanitize_text_field($input['vaptcha_vid']);
        $validated['vaptcha_key'] = sanitize_text_field($input['vaptcha_key']);
        $validated['vaptcha_comment'] = (sanitize_text_field($input['vaptcha_comment']) == "1" ? "1" : "0");
        $validated['vaptcha_register'] = (sanitize_text_field($input['vaptcha_register']) == "1" ? "1" : "0");
        $validated['vaptcha_login'] = (sanitize_text_field($input['vaptcha_login']) == "1" ? "1" : "0");
        $validated['vaptcha_lang'] = sanitize_text_field($input['vaptcha_lang']);
        $validated['vaptcha_phone'] = sanitize_text_field($input['vaptcha_phone']);
        $validated['vaptcha_code'] = sanitize_text_field($input['vaptcha_code']);
        $validated['bg_color'] = sanitize_text_field($input['bg_color']);
        $validated['vaptcha_width'] = sanitize_text_field($input['vaptcha_width']);
        $validated['vaptcha_smsid'] = sanitize_text_field($input['vaptcha_smsid']);//短信id
        $validated['vaptcha_smskey'] = sanitize_text_field($input['vaptcha_smskey']);//短信key
        $validated['vaptcha_modelId'] = sanitize_text_field($input['vaptcha_modelId']);//模板id
        $validated['vaptcha_height'] = sanitize_text_field($input['vaptcha_height']);
        $validated['button_style'] = (sanitize_text_field($input['button_style']) == "light" ? "light" : "dark");
        $validated['button_no_secret'] = (sanitize_text_field($input['button_no_secret']) == "on" ? "on" : "off");//免密登录
        $validated['button_international'] = (sanitize_text_field($input['button_international']) == "on" ? "on" : "off");//国际短信
        $user = wp_get_current_user();
        $userid = $user->ID;
        $user_phone = $validated['vaptcha_phone'];
        update_user_meta($userid, 'phone', $user_phone);
        return $validated;
    }

    /**
     * vaptcha设置菜单
     */
    function vaptcha_options_page()
    {
        add_menu_page('VAPTCHA设置',
            'VAPTCHA设置',
            'manage_options',
            'vaptcha',
            'vaptcha_options_page_html',
            '');
    }


    /**
     * 退出设备菜单
     */

    function exit_device()
    {
        $this->removeDevice();
    }


    /**
     * 插件设置默认值
     */
    function init_default_options()
    {
        if (!get_option('vaptcha_options')) {
            $options = array(
                'vaptcha_vid' => '',
                'vaptcha_key' => '',
                'vaptcha_comment' => '1',
                'vaptcha_register' => '1',
                'vaptcha_login' => '1',
                'vaptcha_lang' => 'auto',
                'bg_color' => '#57ABFF',
                'vaptcha_width' => '200',
                'vaptcha_height' => '36',
                'vaptcha_smsid' => '',//短信id
                'vaptcha_smskey' => '',//短信key
                'vaptcha_phone' => '',//绑定手机号
                'vaptcha_code' => 86,//绑定手机号
                'vaptcha_modelId' => '0',//模板id
                'https' => true,
                'button_style' => 'dark',
                'button_no_secret' => 'off',//免密登录
                'button_international' => 'off',//国际短信
                'type' => 'click',
                "offline_server" => site_url() . '/wp-json/vaptcha/offline',
                // 'mode' => 'offline',
            );
            add_option('vaptcha_options', $options);
        }
    }

    function uninstall()
    {
        unregister_setting("vaptcha_options_group", 'vaptcha_options');
    }

    function load_textdomain()
    {
        load_plugin_textdomain('vaptcha', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    function get_vaptcha_api()
    {
        header('Content-Type: application/javascript');
        return json_decode($this->vaptcha->getChallenge());
    }

    function get_downtime_api()
    {
        header('Content-Type: application/javascript');
        $offline_action = sanitize_text_field($_GET['offline_action']);
        $callback = sanitize_text_field($_GET['callback']);
        return $this->vaptcha->downTime($offline_action, $callback);
    }

    function captcha_in_woocommerce()
    {
        echo $this->get_captcha('woocommerce-form-register', 'submit');
    }

    function captcha_validate_woocommerce($errors)
    {
        $challenge = '';
        $token = sanitize_text_field($_POST['vaptcha_token']);
        $server = sanitize_text_field($_POST['vaptcha_server']);
        if (!$token || !$this->vaptcha->validate($server,$challenge, $token)) {
            $errors->add('captcha_wrong', __('人机验证未通过', 'vaptcha'));
        }
        return $errors;
    }

    function captcha_validate_woocommerce_allow($data)
    {
        $challenge = '';
        $token = sanitize_text_field($_POST['vaptcha_token']);
        $server = sanitize_text_field($_POST['vaptcha_server']);
        if (!$token || !$this->vaptcha->validate($server,$challenge, $token)) {
            return new WP_Error('captcha_wrong', __('人机验证未通过', 'vaptcha'));
        }
        return $data;
    }

    /**
     * 加载css
     */
    function back_end_styles()
    {
        // load styles
        wp_register_style('vaptcha-setting-style', plugin_dir_url(__FILE__) . '/css/back-end-styles.css', false, '1.0');
        wp_enqueue_style('vaptcha-setting-style');

    }


    /**
     * 加载js脚本
     */
    public function load_sms_js()
    {
        wp_register_script('vaptcha_sms_js', plugins_url('js/sms.js', __FILE__), array('jquery'), '3.3.2', true);
        wp_enqueue_script('vaptcha_sms_js');

        wp_register_script('vaptcha_v3_js', 'https://v-na.vaptcha.com/v3.js', array('jquery'), '3.3.2', true);
        wp_enqueue_script('vaptcha_v3_js');
    }

    /**
     * 验证注册表单
     */

    public function validateRegister()
    {
        $smsid = get_option('vaptcha_options')['vaptcha_smsid'];
        $smskey = get_option('vaptcha_options')['vaptcha_smskey'];
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_text_field($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $smscode = sanitize_text_field($_POST['smscode']);

        $args = array(
            'meta_key' => 'phone',
            'meta_value' => $phone,
        );
        $phoneFindUser = get_users($args);
        $exists = email_exists($email);//检查邮箱是否存在
        $user = username_exists($username);//检查用户名是否存在
        if ($user) {
            $name = '该用户名已被注册。';
        }
        if ($exists) {
            $mail = '该邮箱地址已被注册。';
        }
        if (!!$phoneFindUser) {
            $phoneNumber = '该手机号已绑定其他用户。';
        } else {
            $res = $this->vaptcha->validateSmsCode($smsid, $smskey, $phone, $smscode);
            if ($res == 601) {
                $code = '短信验证码错误';
            }
        }
        if ($name === null && $mail === null && $phoneNumber=== null && $code === null){
            wp_send_json_success();
        }else{
            wp_send_json_error(array(
                'name' => $name,
                'email' => $mail,
                'phone' => $phoneNumber,
                'smscode' => $code
            ));
        }

    }

    /**
     * 后台设置页自动获取smsid
     */
    public function getSMSID()
    {
        $vid = sanitize_text_field($_GET['id']);
        $key = sanitize_text_field($_GET['key']);
        $url = 'https://sms.vaptcha.com/smsid?id=' . $vid . '&key=' . $key;
        $response = wp_remote_get($url);
        $data = json_decode($response['body']);
        wp_send_json_success($data);
    }

    /**
     * 发送手机短信验证码
     */
    public function sendSmsCode()
    {
        $countrycode = sanitize_text_field($_POST['countrycode']);
        $post_data = array(
            'smsid' => sanitize_text_field($_POST['smsid']),
            'smskey' => get_option('vaptcha_options')['vaptcha_smskey'],
            'templateid' => sanitize_text_field($_POST['templateid']),
            'phone' => sanitize_text_field($_POST['phone']),
            'token' => sanitize_text_field($_POST['token']),
            'countrycode' => $countrycode === null ? '86' : $countrycode,
            'data' => "_vcode"
        );
        $url = 'https://sms.vaptcha.com/send';

        $args = array(
            'body' => $post_data,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array("Content-type:application/json;charset='utf-8'",
                "Accept:application/json"),
        );

        $response = wp_remote_post($url, $args);
        $data = json_decode($response['body']);
        wp_send_json_success($data);
    }

    /**
     * 设备登录
     *
     */
    public function getDeviceLogin()
    {
        $phone = sanitize_text_field($_GET['phone']);
        $token = sanitize_text_field($_GET['token']);
        $server = sanitize_text_field($_GET['server']);
        //通过手机号查找用户
        $args = array(
            'meta_key' => 'phone',
            'meta_value' => $phone,
        );

        $phoneFindUser = get_users($args);
        if (!empty($phoneFindUser)) {
            $phoneFindUser = $phoneFindUser[0];
        }
        //通过用户名和手机号都未找到用户
        if (!($phoneFindUser instanceof WP_User)) {
            wp_send_json_error(array('code' => 444, 'msg' => '该手机未绑定用户'));
        }
        if (!$token || !$this->vaptcha->validate($server,'', $token)) {
            wp_send_json_error(array('code' => 444, 'msg' => '人机验证未通过'));
        }
        $url = $server.'/device?id=' . $phone . '&token=' . $token . '&renewal=1';
        $response = wp_remote_get($url);

        $data = json_decode($response['body']);
        wp_send_json_success($data);
    }

    /**
     * 添加登录设备
     */
    public function addDevice()
    {
        $smsid = get_option('vaptcha_options')['vaptcha_smsid'];
        $smskey = get_option('vaptcha_options')['vaptcha_smskey'];
        $phone = sanitize_text_field($_POST['phone']);
        $vcode = sanitize_text_field($_POST['code']);
        $token = sanitize_text_field($_POST['token']);
        $server = sanitize_text_field($_POST['server']);
//        $server = sanitize_text_field($_POST['server']);
        //通过手机号查找用户
        $args = array(
            'meta_key' => 'phone',
            'meta_value' => $phone,
        );
        $phoneFindUser = get_users($args);
        if (!empty($phoneFindUser)) {
            $phoneFindUser = $phoneFindUser[0];
        }
        //通过用户名和手机号都未找到用户
        if (!($phoneFindUser instanceof WP_User)) {
            wp_send_json_error(array('code' => 400, 'msg' => '该手机未绑定用户'));
        }

        $res = $this->vaptcha->validateSmsCode($smsid, $smskey, $phone, $vcode);
        if (!empty($res) && $res != 600) {
            wp_send_json_error(array('code' => $res, 'msg' => '短信验证未通过'));
        }
//        if (!$token || !$this->vaptcha->validate($server, '', $token)) {
//            wp_send_json_error(array('code' => 400, 'msg' => '人机验证未通过'));
//        }
        $data = array(
            'id' => $phone,
            'token' => $token,
            'regedit' => 0
        );
        $url =  $server.'/device';
        $args = array(
            'method' => 'PUT',
            'body' => json_encode($data),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array("Content-type:application/json;charset='utf-8'",
                "Accept:application/json"),
        );
        $response = wp_remote_request($url, $args);
        $data = json_decode($response['body']);
        wp_send_json_success($data);

    }

    /**
     * 删除设备
     * @param
     * @return string|string[]
     */
    function removeDevice()
    {
        $user = get_currentuserinfo();
        $userid = $user->ID;
        $phone = get_user_option($userid);
        $data = array(
            'id' => '13696463913',
        );

        $url = 'http://192.168.100.11:8080/api/v1/device';
        $args = array(
            'method' => 'DELETE',
            'body' => json_encode($data),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array("Content-type:application/json;charset='utf-8'",
                "Accept:application/json"),
        );
        $response = wp_remote_request($url, $args);
//        $data =  json_decode($response['body']);
//        wp_send_json_success($user);
    }

    /**
     * 添加钩子
     */
    function init_add_actions()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('login_form', array($this, 'captcha_in_login_form'));
        add_action('user_register', array($this, 'user_register_prestige'));
        add_action('register_form', array($this, 'captcha_in_register_form'));
        add_action('admin_init', array($this, 'vaptcha_settings_init'));
        //添加菜单
        add_action('admin_menu', array($this, 'vaptcha_options_page'));
//        add_action('admin_menu',  array($this, 'pluginSettingPage'));
        // load styles
        add_action('admin_enqueue_scripts', array($this, 'back_end_styles'));
        // load js
        add_action('admin_enqueue_scripts', array($this, 'load_sms_js'));
        add_action('login_enqueue_scripts', array($this, 'load_sms_js'));
        add_action('woocommerce_login_form', array($this, 'captcha_in_woocommerce'));
        add_action('woocommerce_register_form', array($this, 'captcha_in_woocommerce'));
        add_action('woocommerce_lostpassword_form', array($this, 'captcha_in_woocommerce'));
        //获取短信id接口
        add_action('wp_ajax_get_smsid', array($this, 'getSMSID'));
        //发送短信验证码
        add_action('wp_ajax_nopriv_send_smscode', array($this, 'sendSmsCode'));
        add_action('wp_ajax_nopriv_submit_register', array($this, 'validateRegister'));
        //设备登录
        add_action('wp_ajax_nopriv_device_login', array($this, 'getDeviceLogin'));
        //添加登录设备
        add_action('wp_ajax_nopriv_add_device', array($this, 'addDevice'));
        //api
        add_action('rest_api_init', function () {
            register_rest_route('vaptcha', '/getchallenge', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_vaptcha_api'),
            ));
            register_rest_route('vaptcha', '/offline', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_downtime_api'),
            ));
        });
        add_filter('preprocess_comment', array($this, 'captcha_validate_comment'), 100, 1);
        add_filter('wp_logout', array($this, 'exit_device'), 101, 2);//注销钩子
        add_filter('authenticate', array($this, 'captcha_validate_login'), 101, 3);//authenticate
        add_filter('sanitize_user', array($this, 'yang_allow_phone_login'), 10, 3);
        add_filter('registration_errors', array($this, 'captcha_validate_register'), 100, 1);
        add_filter('woocommerce_process_registration_errors', array($this, 'captcha_validate_woocommerce'), 100, 1);
        // 插件列表加入设置按钮
        add_filter('plugin_action_links', array($this, 'pluginSettingPageLinkButton'), 10, 2);
    }

    /**
     * 插件列表添加设置按钮
     * @param $links
     * @param $file
     * @return mixed
     */
    public function pluginSettingPageLinkButton($links, $file)
    {
        if ($file === VAPTCHA_BASENAME) {
            $links[] = '<a href="admin.php?page=vaptcha">设置</a>';
        }
        return $links;
    }


}