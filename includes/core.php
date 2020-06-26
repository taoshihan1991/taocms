<?php if(!defined('ROOT')) die('Access denied.');

error_reporting(E_ALL & ~E_NOTICE);

$mtime = explode(' ', microtime());
$sys_starttime = $mtime[1] + $mtime[0];

@include(ROOT . 'config/config.php');

//自动加载函数
function autoloader($class){
	if($class[0] === "S"){
		$file = ROOT . "system/plugins/$class.class.php"; //自动加载系统扩展类
	}else{
		//自动加载模型, 模型类名: name, 文件名必须小写, 文件路径如: ./models/name.php
		$file ="./models/$class.php";
	}
	
	require_once($file);
}
spl_autoload_register('autoloader');

require(ROOT . 'config/settings.php');
require(ROOT . 'system/APP.php');

APP::$_CFG = &$_CFG; //设置APP静态成员$_CFG引用全局的系统配置数组$_CFG

define('APP_NAME', "TaoCMS");
define('APP_VERSION', "4.0.0");

define('BASEURL', $_CFG['siteBaseUrl']);  //网站的完整URL
define('BACKURL', $_SERVER['HTTP_REFERER']); //前一个页面的URL

define('SITEREWRITE', $_CFG['siteRewrite']);  //定义是否开启了伪静态常量, 用于URL(), PURL(), BURL()函数及发送邮件等
define('RW_FRONTEND', SYSDIR . (SITEREWRITE? '' : 'index.php/')); //前台切换伪静态时相对于服务器根目录的URL, 用于URL(), PURL()函数, 提高运行速度
if(defined('ADMINDIR')) {
    define('RW_BACKEND', SYSDIR . ADMINDIR . '/' . (SITEREWRITE ? '' : 'index.php/')); //后台切换伪静态时相对系统根目录的URL, 用于BURL()函数, 提高运行速度
}
define('T_PATH', ROOT . 'public/templates/' . $_CFG['siteDefaultTemplate'].'/'); //前台当前模板绝对路径
define('T_URL', SYSDIR . 'public/templates/' . $_CFG['siteDefaultTemplate'].'/'); //前台当前模板相对URL

define('T_CACHEPATH', ROOT . 'cache/' . $_CFG['siteDefaultTemplate'].'/'); //当前模板的缓存路径

define('COOKIE_USER', COOKIE_KEY.'user');  //前台用户的COOKIE名称
define('COOKIE_ADMIN', COOKIE_KEY.'admin');  //后台用户的COOKIE名称

//定义前台语言
$lang = 'Chinese';
if(isset($_COOKIE['hongcmslang168'])){
	$lang = $_COOKIE['hongcmslang168'];
}else{
	if($_CFG['siteDefaultLang'] == 'Auto'){
		if (strstr(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 'zh-cn') OR strstr(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 'zh-tw'))
		{
			$lang = 'Chinese';
		}else{
			$lang = 'English';
		}
	}else{
		$lang = $_CFG['siteDefaultLang'];
	}
}
//百度蜘蛛展示中文
if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'baiduspider')){
    $lang = 'Chinese';
}

//获取或生成安全cookie名称, 随PHP进程消失. 这个cookie名称为后面的程序设置cookie使用
if(isset($_COOKIE[COOKIE_KEY . 'safe'])){
	define('COOKIE_SAFE', $_COOKIE[COOKIE_KEY . 'safe']);
}else{
	$value = md5(COOKIE_KEY . time());
	setcookie(COOKIE_KEY . 'safe', $value, 0, '/');
	define('COOKIE_SAFE', $value);
}
//新目录进行设置英文版
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
$enPrefix=strtolower(substr($path,0,4));
if($enPrefix=="/en/"){
    $_SERVER['PATH_INFO']=substr($path,4);
    $lang = 'English';
}

define('IS_CHINESE', ($lang == 'Chinese') ? 1 : 0);

if($dbmysql == "mysqli"){
	APP::$DB = new SMysqli($dbusername, $dbpassword, $dbname,  $servername); //MSQLI
}else{
	APP::$DB = new SMysql($dbusername, $dbpassword, $dbname,  $servername);
}

$dbpassword   = ''; //将config.php文件中的密码赋值为空, 增加安全性

?>