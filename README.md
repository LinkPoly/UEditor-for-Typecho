UEditor-for-Typecho
===================

2021-12-29 updated

适用于Typecho 1.0/0.9/0.8等版本, UEditor内核已升级到1.4.3.3版本.

- 支持**又拍云存储**(upyun), 支持UPYUN的缩略图版本
- 支持**腾讯云对象存储**(COS)
- 支持图片上传到云服务器后删除本地服务器的对应冗余图片从而减少本地服务器磁盘空间用量
- 即使Typecho安装在SAE(**Sina** **APP** **Engine**)上也能正常使用


## 需要注意
1. Typecho 1.0/0.9用户请在**控制台/个人设置**中关闭Markdown解析再启用插件
2. **请知晓**: 对于使用云存储的用户, 暂时无法通过此插件管理云服务器上的文件. 如果你的Post会经常更改或删除图片,可能会导致多余的图片占用你的云服务器空间容量. 建议使用Word等软件编写带图片的文章，确认无误后粘贴内容到编辑器内, 根据提示上传图片.

## 安装方式1: 在线安装
使用ueditor.install.php在服务器上下载和安装

1. 访问[https://github.com/LinkPoly/UEditor-for-Typecho/releases](https://github.com/LinkPoly/UEditor-for-Typecho/releases)
2. 下载最新版本的安装脚本(ueditor.install.php)
3. 上传ueditor.install.php文件到/usr/plugins文件夹下
4. 访问YourDomain.com/usr/plugins/ueditor.install.php进行安装
5. 安装成功后进入Typecho插件管理界面启用即可.

## 安装方式2: 下载安装

服务器位于某些特定的区域时可能无法使用在线安装,无法使用在线安装时请下载插件压缩包上传到服务器进行安装.

访问[https://github.com/LinkPoly/UEditor-for-Typecho/releases](https://github.com/LinkPoly/UEditor-for-Typecho/releases),下载所需版本的压缩包,解压后上传其中的**UEditor**文件夹到/usr/plugins文件夹下, 进入Typecho插件管理界面启用即可

## 联系作者

Github: [github.com/LinkPoly/](https://github.com/LinkPoly/)
WebSite: [LinkPoly.com](http://linkpoly.com)

UEditor-for-Typecho QQ群: 558310881
