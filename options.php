<?php
if ( !defined('ABSPATH') ) {
    exit('No direct script access allowed');
}


function vaptcha_options_page_html()
{
    $ajaxUrl = admin_url('admin-ajax.php');
    $dropdown = plugins_url('images/dropdown.png', __FILE__);//下拉图标地址
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <!-- Create a header in the default WordPress 'wrap' container -->
    <div class='wrap vaptcha-wrap'>
        <h2></h2>
        <?php
        $options = get_option("vaptcha_options");
        ?>
        <div class="vaptcha-header">
            <div class='vaptcha-badge'></div>
            <div>
                <h2><?php _e('VAPTCHA-SMS', 'vaptcha') ?></h2>
                <p class="vaptcha-about-text"><?php _e( '为了避免冲突，VAPTCHA及VAPTCHA-SMS仅能同时启用其中一个插件；短信单价经过补贴后远低于市场价，低至2.8分/条，插件安装即赠送30条测试短信，超出请自行充值后使用。', 'vaptcha' ); ?>
            </div>
        </div>
        <form name="form" action="options.php" method="post">
            <?php
            settings_fields('vaptcha_options_group');
            ?>
            <p class="get-vaptcha-key"><?php echo __( '请登录VAPTCHA官网获取下方参数，填写保存后即可生效。官网地址：  ', 'uncr_translate' ) . '<a href="https://www.vaptcha.com" target="_blank" title="vaptcha">' . __( 'https://www.vaptcha.com', 'uncr_translate' ) . '</a>'; ?></p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th class="form-title"><?php _e( '人机验证设置', 'vaptcha' ); ?></th>
                    <td>
                        <?php _e( '（登录VAPTCHA - 创建验证单元  -  免费获取）', 'vaptcha' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="public_key_text">VID</label></th>
                    <td>
                        <fieldset>
                            <input placeholder="" type="text" id="vaptcha_unit_vid" name="vaptcha_options[vaptcha_vid]" value="<?php echo $options['vaptcha_vid'] ?>">
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><label for="public_key_text" id="unit_key">KEY</label></th>
                    <td>
                        <fieldset id="vaptcha-hidden-url" data-ajax-url="<?php echo $ajaxUrl ?>">
                            <input placeholder="" type="text" id="vaptcha_unit_key" name="vaptcha_options[vaptcha_key]" value="<?php echo $options['vaptcha_key'] ?>">
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th class="form-title"><b style="font-size: 16px;"><?php _e('短信接口设置', 'vaptcha') ?></b></th>
                    <td style="width: 1000px">
                        <?php _e( '（登录VAPTCHA - 短信接口  -  获取参数。默认模板签名被公用，到达率较低，建议使用自定义短信模板，自定义模板内容只能包含验证码一个变量）', 'vaptcha' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="public_key_text"><?php _e('SMSID', 'vaptcha') ?></label></th>
                    <td>
                        <fieldset>
                            <input type="text" id="vaptcha_smsid" name="vaptcha_options[vaptcha_smsid]" value="<?php echo $options['vaptcha_smsid'] ?>">
                            <label class='automatic' style="color: #999;display: none" for="public_key_text" onclick=""><?php _e('自动获取中...', 'vaptcha') ?></label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><label for="public_key_text"><?php _e('SMSKEY', 'vaptcha') ?></label></th>
                    <td>
                        <fieldset>
                            <input type="text" id="vaptcha_smskey" name="vaptcha_options[vaptcha_smskey]" value="<?php echo $options['vaptcha_smskey'] ?>">
                            <label class='automatic'  style="color: #999;display: none" for="public_key_text" onclick=""><?php _e('自动获取中...', 'vaptcha') ?></label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><label for="public_key_text"><?php _e('模板ID', 'vaptcha') ?></label></th>
                    <td>
                        <fieldset>
                            <input type="text" id="public_key_text" name="vaptcha_options[vaptcha_modelId]" value="<?php echo $options['vaptcha_modelId'] ?>">
                        </fieldset>
                    </td>
                    <td class='descript' style="width: 550px"><?php _e('建议使用自定义模板，示例：【XX网站】您的验证码是{变量}，请在十分钟内输入', 'vaptcha') ?></td>
                </tr>
                <tr>
                    <th class="form-title"><b style="font-size: 16px;"><?php _e('高级设置', 'vaptcha') ?></b></th>
                </tr>
                <tr>
                    <th><label for="public_key_text"><?php _e('管理员手机', 'vaptcha') ?></label></th>
                    <td id="">
                        <div style="display: flex;align-items: center">
                            <div id="phonePrefix" class="area-code">
                                <div class="country-code active flex">
                                    <span class="add">+</span>
                                    <input type="text" id="get_code"
                                           name="vaptcha_options[vaptcha_code]"
                                           class="form-control"
                                           style="border: none;outline: none!important;"
                                           value="<?php echo $options['vaptcha_code'] ?>">
                                    <button id="btn-down" type="button" style="background: none;border: none;outline: none;padding: 0 3px">
                                        <img id="more_icon" src="<?php echo $dropdown ?>" style="width: 10px;cursor: pointer;"/>
                                    </button>
                                </div>
                                <ul class="dropdown-menu" id="code_menu" style="display: none;margin-top: 0">
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
                            <input type="text" name="vaptcha_options[vaptcha_phone]" id="public_key_text admin_phone" value="<?php echo $options['vaptcha_phone'] ?>" style="width: 206px;border-radius: 0 5px 5px 0;margin-left: 0" class="input" size="20" autocapitalize="off" />
                        </div>
                    </td>
<!--                    <td>-->
<!--                        <fieldset>-->
<!--                            <input type="text" id="public_key_text" name="vaptcha_options[vaptcha_modelId]" value="--><?php //echo $options['vaptcha_modelId'] ?><!--">-->
<!--                        </fieldset>-->
<!--                    </td>-->
                    <td class='descript'><?php _e(' 填写即绑定，用于管理员账号登录', 'vaptcha') ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="button_no_secret"><?php _e('默认登录方式', 'vaptcha') ?></label></th>
                    <td>
                        <fieldset class="vaptcha-radio-field-wrapper">
                            <input id="invisible" type="radio" <?php if( 'on' == $options['button_no_secret'] ) echo 'checked="checked"'; ?> name="vaptcha_options[button_no_secret]" value="on">
                            <label for="invisible"><?php _e('免密登录', 'vaptcha') ?></label>
                            <input id="normal" type="radio" <?php if( 'off' == $options['button_no_secret'] ) echo 'checked="checked"'; ?> name="vaptcha_options[button_no_secret]" value="off">
                            <label for="normal"><?php _e('账号密码登录', 'vaptcha') ?></label>
                        </fieldset>
                    </td>
                    <td class='descript'><?php _e(' 开启后用户将使用手机进行登录，校验为本机设备时即可免密登录', 'vaptcha') ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="button_international"><?php _e('国际短信', 'vaptcha') ?></label></th>
                    <td>
                        <fieldset class="vaptcha-radio-field-wrapper">
                            <input id="on" type="radio" <?php if( 'on' == $options['button_international'] ) echo 'checked="checked"'; ?> name="vaptcha_options[button_international]" value="on">
                            <label for="on"><?php _e('开启', 'vaptcha') ?></label>
                            <input id="off" type="radio" <?php if( 'off' == $options['button_international'] ) echo 'checked="checked"'; ?> name="vaptcha_options[button_international]" value="off">
                            <label for="off"><?php _e('关闭', 'vaptcha') ?></label>
                        </fieldset>
                    </td>
                    <td class='descript'><?php _e('国际短信将使用默认短信模板进行发送', 'vaptcha') ?></td>
                </tr>
                </tbody>
            </table>
            <p class="deadline"></p>
            <?php submit_button(__('保存设置', 'vaptcha')); ?>
        </form>
    </div>
    <?php
}