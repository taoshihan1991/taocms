<?php if(!defined('ROOT')) die('Access denied.');

class c_getpass extends SWeb{

	public function verify(){
		$key = ForceStringFrom('key');
		$sid = ForceStringFrom('sid');

		if(!IsGet('key') OR !IsGet('sid') OR !$key OR !$sid) Error($this->langs['er_link']);

		$userid = ForceInt(base64_decode($key));
		if(!$userid) Error($this->langs['er_link']);

		if(!$user = APP::$DB->getOne("SELECT u.userid, u.username, u.password, u.nickname, u.verifycode FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.userid = '$userid' AND u.activated = 1")){

			Error($this->langs['er_nouser']); //用户不存在
		}else{
			$code = md5($user['nickname'] . WEBSITE_KEY. $user['password'] . $user['verifycode']);

			if($sid != $code) Error($this->langs['er_link']); //再次验证URL, 确保安全

			$newpass = PassGen(8);
			$password = md5($newpass);

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET password = '$password', verifycode = '' WHERE userid = '$userid'");

			//激活成功创建session, 6秒后自动跳转到用户首页; 创建session不成功时, 跳转到首页并显示登录框
			$url = Iif($this->user->CreateSession($userid), 'uc', 'index?login');

			$info = str_replace('//2', $newpass, str_replace('//1', $user['username'], $this->langs['i_getpassok']));
			Success($url, 0, $info);
		}
	}

	public function index(){
		die('Access denied.'); //默认动作显示不允许进入
	}
} 

?>