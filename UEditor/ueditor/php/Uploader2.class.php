<?php
// load typecho config file
require_once '../../../../../config.inc.php';

switch( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
{
    case 'upyun':
        define('CLOUD_UPYUN', 'upyun');
        require 'upyun.class.php';
        break;
    case 'qcloud_cos':
        define('CLOUD_QCLOUD_COS', 'qcloud_cos');
        require './qcloud-cos/cos-autoloader.php';
        break;
}


/**
 * Class Uploader
 * Date: 2016-04-19
 * Time: 00:19:16
 */
class Uploader
{
    private $fileField; //文件域名
    private $file; //文件上传对象
    private $base64; //文件上传对象
    private $config; //配置信息
    private $oriName; //原始文件名
    private $fileName; //新文件名
    private $fullName; //完整文件名,即从当前配置目录开始的URL
    private $filePath; //完整文件名,即从当前配置目录开始的URL
    private $fileSize; //文件大小
    private $fileType; //文件类型
    private $stateInfo; //上传状态信息,
    private $stateMap = array( //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_TMP_FILE" => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED" => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED" => "文件类型不允许",
        "ERROR_CREATE_DIR" => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE" => "目录没有写权限",
        "ERROR_FILE_MOVE" => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND" => "找不到上传文件",
        "ERROR_WRITE_CONTENT" => "写入文件内容错误",
        "ERROR_UNKNOWN" => "未知错误",
        "ERROR_DEAD_LINK" => "链接不可用",
        "ERROR_HTTP_LINK" => "链接不是http链接",
        "ERROR_HTTP_CONTENTTYPE" => "链接contentType不正确",
        "ERROR_UPYUN" => "上传到UPYUN时发生错误",
        "ERROR_QCLOUD_COS" => "上传到腾讯云COS时发生错误"
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config 配置项
     * @param bool $base64 是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($fileField, $config, $type = "upload")
    {
        $this->fileField = $fileField;
        $this->config = $config;
        $this->type = $type;
        if ($type == "remote") {
            $this->saveRemote();
        } else if($type == "base64") {
            $this->upBase64();
        } else {
            $this->upFile();
        }

        $this->stateMap['ERROR_TYPE_NOT_ALLOWED'] = iconv('unicode', 'utf-8', $this->stateMap['ERROR_TYPE_NOT_ALLOWED']);

    }

    /**
     * 根据配置上传文件到特定的云服务器
     *
     * @param      $file     要上传的文件
     * @param      $fileName 云服务器上的file path
     * @param bool $this_is_file 是文件或者内容?true为文件,false为内容,默认true
     * @throws Typecho_Widget_Exception
     */
    private function upload_to_cloud($file, $fileName, $this_is_file = true)
    {
        switch( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
        {
            case CLOUD_UPYUN:
                $this->_upload_to_upyun($file, $fileName, $this_is_file);
                break;
            case CLOUD_QCLOUD_COS:
                $this->_upload_to_qcloud_cos($file, $fileName, $this_is_file);
                break;
        }
        // 是否删除本地冗余图片文件
        if( $this_is_file && Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_only )
        {
            unlink($file);
        }
    }

    /**
     * 上传文件到腾讯云COS
     *
     * @param      $file     要上传的文件
     * @param      $fileName 云服务器上的file path
     * @param bool $this_is_file 是文件或者内容?true为文件,false为内容,默认true
     * @return bool
     * @throws Typecho_Widget_Exception
     */
    private function _upload_to_qcloud_cos($file, $fileName,$this_is_file = true)
    {
        if($this_is_file && !file_exists($file) )
        {
            $this->stateInfo = $this->getStateInfo('ERROR_FILE_NOT_FOUND');
            return false;
        }

        $cosClient = new Qcloud\Cos\Client(array('region' => Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_qcloud_region,
                                                 'credentials'=> array(
                                                     'appId' => Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_qcloud_appid,
                                                     'secretId'    => Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_user,
                                                     'secretKey' => Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_password)));
        try
        {
            if( $this_is_file )
            {
                ob_start();
                $_fp = fopen($file, 'rb');
                $result = $cosClient->upload(Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_bucket, $fileName, $_fp);
                fclose($_fp);
                ob_end_clean();
            }
            else
            {
                ob_start();
                $result = $cosClient->upload(Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_bucket, $fileName, $file);
                ob_end_clean();
            }

            if( $result )
            {
                // $this->url = str_replace('%2F', '/', $result['ObjectURL']);
                $this->stateInfo = $this->stateMap[0];
            }
            else
            {
                $this->stateInfo = $this->getStateInfo("ERROR_QCLOUD_COS");
                return false;
            }
        }
        catch (\Exception $e1)
        {
            $this->stateInfo = $e1->getCode();
            return false;
        }
        catch (Exception $e)
        {
            $this->stateInfo = $e->getCode();
            return false;
        }
    }

    /**
     * 上传内容或文件到upyun
     *
     * @param      $file     要上传的文件
     * @param      $fileName 云服务器上的file path
     * @param bool $this_is_file $file是文件或者内容?true为文件,false为内容,默认true
     * @return bool
     * @throws Typecho_Widget_Exception
     */
    private function _upload_to_upyun($file, $fileName, $this_is_file = true)
    {
        if( $this_is_file && !file_exists($file) )
        {
            $this->stateInfo = $this->getStateInfo('ERROR_FILE_NOT_FOUND');
            return false;
        }

        $upyun = new UpYun(Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_bucket,
                        Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_user,
                        Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_password);

        try
        {
            if( $this_is_file )
            {
                $_fp = fopen($file, 'rb');
                $rsp = $upyun->writeFile($fileName, $_fp, true);
                fclose($_fp);
            }
            else
            {
                $rsp = $upyun->writeFile($fileName, $file, true);
            }

            if( $rsp )
            {
                $this->stateInfo = $this->stateMap[0];
            }
            else
            {
                $this->stateInfo = $this->getStateInfo("ERROR_UPYUN");
                return false;
            }
        }
        catch (Exception $e)
        {
            $this->stateInfo = $e->getCode();
            return false;
        }
    }

    /**
     * 上传文件的主处理方法
     * @return mixed
     */
    private function upFile()
    {
        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo($file['error']);
            return;
        } else if (!file_exists($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMPFILE");
            return;
        }

        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        // 创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                return false;
            }
        } else if (!is_writeable($dirname)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                return false;
            }
        }

        // 移动文件
        if (!(move_uploaded_file($file["tmp_name"], $this->filePath) && file_exists($this->filePath))) { //移动失败
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->upload_to_cloud($file['tmp_name'], $this->fullName);
            }
            else
            {
                $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
                return false;
            }
        } else { //移动成功
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud)
            {
                $this->upload_to_cloud($this->filePath, $this->fullName);
            }
            else
            {
                $this->stateInfo = $this->stateMap[0];
            }
        }
    }

    /**
     * 处理base64编码的图片上传
     * @return mixed
     */
    private function upBase64()
    {
        $base64Data = $_POST[$this->fileField];
        $img = base64_decode($base64Data);

        $this->oriName = $this->config['oriName'];
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                return false;
            }
        } else if (!is_writeable($dirname)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                return false;
            }
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->upload_to_cloud($img, $this->fullName, false);
            }
            else
            {

                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }
        } else { //移动成功
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->upload_to_cloud($this->filePath, $this->fullName);
            }
            else
            {
                $this->stateInfo = $this->stateMap[0];
            }
        }
    }

    /**
     * 拉取远程图片
     * @return mixed
     */
    private function saveRemote()
    {
        $imgUrl = htmlspecialchars($this->fileField);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);

        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return;
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
            return;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) || stristr($heads['Content-Type'], "image")) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
            return;
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $this->oriName = $m ? $m[1]:"";
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                return false;
            }
        } else if (!is_writeable($dirname)) {
            if( !Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                return false;
            }
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->upload_to_cloud($img, $this->fullName, false);
            }
            else
            {

                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }
        } else { //移动成功
            if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
            {
                $this->upload_to_cloud($this->filePath, $this->fullName);
            }
            else
            {
                $this->stateInfo = $this->stateMap[0];
            }
        }
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getFullName()
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 获取文件名
     * @return string
     */
    private function getFileName () {
        return substr($this->filePath, strrpos($this->filePath, '/') + 1);
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath()
    {
        $fullname = $this->fullName;
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        if (substr($fullname, 0, 1) != '/') {
            $fullname = '/' . $fullname;
        }

        return $rootPath . $fullname;
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        $a = array(
            "state" => $this->stateInfo,
            "url" => $this->fullName,
            "title" => $this->fileName,
            "original" => $this->oriName,
            "type" => $this->fileType,
            "size" => $this->fileSize
        );

        if( Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud )
        {
            if( defined('CLOUD_QCLOUD_COS') )
            {
//                $a['url'] = $this->url. Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_suffix;
                $a['url'] = Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_url. '/'.  Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_bucket. '/'. $this->fullName. Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_suffix;
            }
            else
            {
                $a['url'] = Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_url. $this->fullName. Typecho_Widget::widget('Widget_Options')->plugin('UEditor')->cloud_suffix;
            }
        }
        return $a;
    }

}