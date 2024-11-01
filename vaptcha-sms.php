<?php
/*
Plugin Name: vaptcha-sms 手机登录注册
Plugin URI: https://www.vaptcha.com
Description: vaptcha-sms由智能人机验证服务商VAPTCHA官方提供，国内/国际短信极速发送。操作简单，兼容性好。
Version: 1.0.1
Author: vaptcha
Text Domain: vaptcha
Domain Path: /languages
Author URI: https://github.com/vaptcha
*/

/*  Copyright 2017  vaptcha  (email : vaptcha@wlinno.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('VAPTCHA_BASENAME')||define( 'VAPTCHA_BASENAME', plugin_basename(__FILE__));
defined('VAPTCHA_SMS_URL')||define( 'VAPTCHA_SMS_URL', plugins_url( 'vaptcha' ) );//update -----------------------------------------------------------------
defined('VAPTCHA_JS_DIR')||define( 'VAPTCHA_JS_DIR', VAPTCHA_BASENAME .'/js/' );//update -----------------------------------------------------------------

if ( !defined('ABSPATH') ) {
   exit('No direct script access allowed');
}

require_once plugin_dir_path( __FILE__ ) . 'VaptchaSmsPlugin.php';

if(class_exists("VaptchaSmsPlugin")){
	$vaptcha = new VaptchaSmsPlugin();
    $vaptcha->init();
}
?>