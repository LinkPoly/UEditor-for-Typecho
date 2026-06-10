<?php
/**
 * 为Typecho启用UEditor编辑器(支持云存储)
 *
 * @package UEditor for Typecho
 * @author LinkPoly
 * @version 2022
 * @link http://linkpoly.com
 * Date: 2021-12-29
 * Time: 08:20:22
 */
class UEditor_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('UEditor_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('UEditor_Plugin', 'render');
        
        Helper::addPanel(0, 'UEditor/ueditor/ueditor.config.js.php','', '', 'contributor');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removePanel(0, 'UEditor/ueditor/ueditor.config.js.php');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        /** 使用UPYUN */
        $c1 = new Typecho_Widget_Helper_Form_Element_Radio('cloud',
            array(
                '0' => '不使用',
                'upyun' => '又拍云(upyun)',
                'qcloud_cos' => '腾讯云COS',
            ),
            '0', '是否使用云服务器存储?', '开启后会把图片和文件上传到云服务器上');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Checkbox('cloud_only', array('cloud_only' => '图片上传到云服务器后删除本服务器上对应的文件'), array(), '图片仅上传到云服务器', '如果勾选，则把图片文件上传到云服务器并删除本地服务器上对应的文件');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Text('cloud_url', NULL, NULL, 'Bucket空间域名', '大概是这样的:http://bucket.b0.upaiyun.com, 或使用你绑定的域名,这是访问你上传文件的域名<br/>前面要带http或者https,后面不要带斜杆等符号');
        $form->addInput($c1);
        
        $c1 = new Typecho_Widget_Helper_Form_Element_Text('cloud_bucket', NULL, NULL, 'Bucket空间名称', '例如bucket');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Text('cloud_user', NULL, NULL, '操作员', '对应的bucket写入权限的账号(操作员/secretId/AccessKeyId)');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Password('cloud_password', NULL, NULL, '密码', '对应的正确的密码(操作员密码/secretKey/AccessKeySecret)');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Password('cloud_qcloud_appid', NULL, NULL, 'appid', '腾讯云COS的appid');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Text('cloud_qcloud_region', NULL, NULL, '腾讯云COS地域简称代码', '腾讯云COS的地域简称代码,其值可以为下列之一:cn-east, cn-sorth, cn-north, cn-south-2, cn-southwest, sg, tj, bj, sh, gz, cd, sgp, ap-guangzhou等');
        $form->addInput($c1);

        $c1 = new Typecho_Widget_Helper_Form_Element_Text('cloud_suffix', NULL, NULL, '缩略图版本', '在文件URL后添加的内容,upyun用户常用功能,例如 !default');
        $form->addInput($c1);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $options = Helper::options();
        $configJs = Typecho_Common::url('extending.php?panel=UEditor/ueditor/ueditor.config.js.php', $options->adminUrl);
        $js = Typecho_Common::url('UEditor/ueditor/ueditor.all.min.js', $options->pluginUrl);

        echo '<script type="text/javascript" src="'. $configJs. '"></script><script type="text/javascript" src="'. $js. '"></script>';
        echo '<script type="text/javascript">
            var ue1;
        	window.onload = function() {
				// 渲染
                ue1 = UE.getEditor("text");
        	}
    
    // 保存草稿时同步
	document.getElementById("btn-save").onclick = function() {
        ue1.sync("text");
    }

    // 提交时同步
	document.getElementById("btn-submit").onclick = function() {
		ue1.sync("text");
	}
	</script>';
    }
}
