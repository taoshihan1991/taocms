<?php if(!defined('ROOT')) die('Access denied.');

//独立运行的控制器, 不从SAdmin扩展而来
class c_getpass {

	protected $ajax = array(); //用于ajax数据收集与输出
	protected $json; //ajax时的JSON对象

	public function __construct($path){

		@set_time_limit(0); //发送邮件时可能运行时间较长, 解除运行时间的限制

		include(ROOT . 'includes/functions.admin.php'); //加载函数库(包括前后台公共函数库)

		$this->ajax['s'] = 0; //初始化ajax返回数据, s表示状态
		$this->ajax['i'] = ''; //i指ajax提示信息
		$this->ajax['d'] = ''; //d指ajax返回的数据
	}

	//check()为ajax动作
	public function check(){
		$this->json = new SJSON;

		$key = ForceStringFrom('key');
		$code = ForceStringFrom('code');
		$decode = authcode($code, 'DECODE', $key);
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		$email = ForceStringFrom('email');

		if(!$email){
			$this->ajax['i'] = '请输入Email地址!';
		}elseif(!IsEmail($email)){
			$this->ajax['i'] = 'Email地址非法!';
		}elseif($decode != md5(WEBSITE_KEY)){
			$this->ajax['i'] = '验证码超时! 请刷新页面后重新提交.';
		}elseif($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = '安全验证Cookie错误!';
		}elseif(!$user = APP::$DB->getOne("SELECT u.userid, u.password, u.nickname FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.email = '$email' AND u.activated = 1 AND ug.grouptype = 1")){
			$this->ajax['i'] = 'Email地址不存在!';
		}else{
			$subject = '管理员找回密码 -- ' . APP::$_CFG['siteCopyright'];

			$verifycode = PassGen(8);

			$verify_url = BASEURL . ADMINDIR . '/' . Iif(!SITEREWRITE, 'index.php/') . 'getpass/verify?key=' . base64_encode($email) . '&sid=' . md5($user['nickname'] . WEBSITE_KEY. $user['password'] . $verifycode);

			$content = "$user[nickname]:<br><br>您好! 请点击以下链接重设密码:<br><br>";
			$content .= "<a href=\"$verify_url\" target=\"_blank\">$verify_url</a><br><br>";

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET verifycode = '$verifycode' WHERE userid = '$user[userid]'");

			if(SendMail($email, $subject, $content) === true){
				$this->ajax['s'] = 1;
				$this->ajax['i'] = '重设密码的邮件已发送到您的信箱, 请查收!';
			}else{
				$this->ajax['i'] = '发送邮件失败!';
			}
		}

		die($this->json->encode($this->ajax));
	}

	public function verify(){
		if(!IsGet('key') OR !IsGet('sid')) $this->out('邮件验证参数非法!');

		$key = ForceStringFrom('key');
		$sid = ForceStringFrom('sid');

		if(!$key OR !$sid ) $this->out('邮件验证参数非法!');

		$email = base64_decode($key);
		if(!IsEmail($email)) $this->out('Email地址非法!');

		if(!$user = APP::$DB->getOne("SELECT u.userid, u.username, u.password, u.nickname, u.verifycode FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.email = '$email' AND u.activated = 1 AND ug.grouptype = 1")){
			$this->out('Email地址不存在!');
		}else{
			$code = md5($user['nickname'] . WEBSITE_KEY. $user['password'] . $user['verifycode']);

			if($sid != $code) $this->out('链接请求的验证码错误!');

			$newpass = PassGen(8);

			$backen_url = BASEURL . ADMINDIR . '/';

			$subject = '您的新密码 -- ' . APP::$_CFG['siteCopyright'];

			$content = "$user[nickname]:<br><br>您好! <br><br>您的登录名是: $user[username]<br>您的新密码是:$newpass<br><br>";
			$content .= "请点击以下链接登录后台管理:<br><br><a href=\"$backen_url\" target=\"_blank\">$backen_url</a><br><br>";

			if(SendMail($email, $subject, $content) === true){
				//邮件发送成功后才更新用户密码, 清空验证码防止重复点击邮件中更新密码的链接
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET password    = '".md5($newpass)."', verifycode = '' WHERE userid = '$user[userid]'");
				$this->out('新密码已发送到您的邮箱, 请查收!', 0);
			}else{
				$this->out('发送邮件失败! 请尝试刷新当前页面.');
			}
		}
	}


	//输出信息
	private function out($info, $err = 1){
		//信息样式
		$info = Iif($err, "<font color=#ff3300>$info</font>", "<font color=blue>$info</font>");

		echo '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>'.APP_NAME.' - 找回密码</title>
<link rel="stylesheet" type="text/css" href="'. SYSDIR .'public/admin/login.css">
</head>
<body>
<div id="logo">
	<img src="'. SYSDIR .'public/admin/images/logo-login.png" alt="'.APP_NAME.'"> 
</div>
<div id="login">
		<p id="info2">' . $info . '</p><BR>
		<div class="login-btn forget-btn">
			<input id="forget-btn" value="返回登录" type="submit">
		</div>
</div>
<div id="login-copyright">
	'.date("Y").' &copy; HongCMS <a href="http://www.weensoft.cn" target="_blank">weensoft.cn</a>
</div>
<script src="'. SYSDIR .'public/js/jquery-1.8.3.min.js" type="text/javascript"></script>   
<script>
$(function(){     
	$("#forget-btn").click(function (e) {
		document.location = "' .BURL(). '"
		e.preventDefault();
	});
});
</script>
</body>
</html>';

		exit();
	}

	public function index(){
		die('Access denied.'); //默认动作显示不允许进入
	}
} 

?>