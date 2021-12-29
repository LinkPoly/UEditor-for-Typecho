<?php
/**
 * 在线安装UEditor for Typecho
 * (写于18岁后的第一个春节)
 * 
 * @author Shengzhi Chen
 * @link http://chensz.com
 */


/** 项目地址 */
$_online_url = 'https://codeload.github.com/chanshengzhi/UEditor-for-Typecho/zip/master';

/** 下载到本地的文件名称 */
$_local_file = 'ueditor.zip';


header('Content-type:text/html;chatset="utf-8"');

// 如果接收到?delete参数 则删除自身
if( isset($_GET['delete']) ) {
	unlink('./README.md');
	unlink('./'. $_local_file);
	unlink($_SERVER['SCRIPT_FILENAME']);
	exit('已经删除安装文件, 请进入插件管理界面启用.<br><a href="/">返回主页</a>');
}

/**
 * 删除文件夹
 * @param string $dir 要删除的文件夹路径
 * @return bool 成功删除返回true,失败则false
 */
function unlink_dir($dir) {
	$dh = opendir($dir);
	while($file = readdir($dh)) {
		if( $file!= '.' && $file != '..') {
			$fullpath = $dir. '/'. $file;
			if( is_dir($fullpath) ) {
				unlink_dir($fullpath);
			} else {
				unlink($fullpath);
			}
		}
	}
	closedir($dh);
	return rmdir($dir);
}


echo '请稍候...(如果长时间无响应请刷新页面)<br /></br />';

try {
	// 下载安装包
	$file_content = file_get_contents($_online_url);
	if( ! $file_content ) {
		throw new Exception('无法下载');
	}
	
	if( ! file_put_contents($_local_file, $file_content) ) {
		throw new Exception('无法保存为本地文件');
	}
	
	// 如果已经存在UEditor文件夹,则删除
	if( is_dir('./UEditor') ) {
		if( !unlink_dir('./UEditor')) {
			throw new Exception('旧的UEditor文件夹无法删除,请手动删除后重新安装');
		}
	}
	
	// 解压
	$zip = new ZipArchive();
	if( TRUE === $zip->open($_local_file) ) {
		$zip->extractTo('./');
		$zip->close();
	} else {
		throw new Exception('无法解压');
	}
	
	// 移除子文件夹
	$dir = "./UEditor-for-Typecho-master/";
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($currfile = readdir($dh)) !== false) {
				if ($currfile!="." && $currfile!="..") {
					if (!file_exists("./".$currfile)) {
						rename($dir.$currfile, "./".$currfile);
					}
				}
			}
			closedir($dh);
		}
		if( ! unlink_dir($dir) ) {
			$dir = trim($dir, './');
			throw new Exception('安装成功,但'. $dir. '文件夹未被删除,请进入/usr/plugins/目录手动删除'. $dir. '文件夹<br>');
		}
	} else {
		throw new Exception('压缩包数据有误');
	}
	
	// 收尾工作
	echo '已完成安装, 请<a href="?delete">删除安装文件</a>';
	
} catch (Exception $e) {
	echo $e->getMessage();
}
