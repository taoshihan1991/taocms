<?php if(!defined('ROOT')) die('Access denied.');

class c_register extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//判断用户是否已经登录(游客无id)
		if($this->user->data['userid']) Redirect('uc'); //直接跳转到用户首页
	}

	//在默认动作中导航, 这样可以统一URL而比较短
    public function index(){
		if(IsPost('submit')){
			$this->save(); //验证及保存
		}else{
			$this->register(); //注册页面
		}
	}


	//注册页面
    public function register(){
		//是否关闭新会员注册
		if(!APP::$_CFG['siteAllowRegister']) Error($this->langs['er_registeroff']);

		$this->assign('title', $this->langs['regmember'] . ' - ' . $this->title); //注册页面标题

		//图片验证码
		$vvckey = CreateVVC();
		$this->assign('vvckey', $vvckey);

		$this->assign('products', GetNewProducts()); //分配10个最新产品
		$this->assign('pagenav', GetNavLinks(array($this->langs['regmember'] => 'register'))); //分配导航栏

		$this->assign('backurl', BACKURL); //记录注册页面前一个页面

		$this->display('register.html');
	}


	//验证注册信息及保存
    public function save(){
		//是否关闭新会员注册
		if(!APP::$_CFG['siteAllowRegister']) Error($this->langs['er_registeroff']);

		$key = ForceStringFrom('key');
		$code = ForceStringFrom('code');
		$decode = authcode($code, 'DECODE', $key); //解码
		$backurl = ForceStringFrom('backurl'); //注册页面前一个页面的URL

		$username = ForceStringFrom('username');
		$email = ForceStringFrom('email');
		$password = ForceStringFrom('password');
		$repassword = ForceStringFrom('repassword');
		$vvckey = ForceIntFrom('vvckey');
		$vvc = ForceStringFrom('vvc');
		$agreeterms = ForceIntFrom('agreeterms');

		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			Error($this->langs['er_cookie']);  //非法提交的注册信息, 如机器人. 直接输出错误并停止运行
		}

		if(!$username OR strlen($username) < 3){
			$errors[] = $this->langs['enter_un'] . '!';
		}elseif(!isName($username)){
			$errors[] = $this->langs['er_badname'];
		}

		if(!$password OR strlen($password) < 4){
			$errors[] = $this->langs['enter_ps'] . '!';
		}elseif($password != $repassword){
			$errors[] = $this->langs['er_pnotsame'];
		}

		if(!$email){
			$errors[] = $this->langs['enter_em'] . '!';
		}elseif(!IsEmail($email)){
			$errors[] = $this->langs['er_bademail'];
		}

		if(!$vvc) $errors[] = $this->langs['enter_ca'];
		if(!$vvckey) $errors[] = $this->langs['er_badcode'];
		if(!$agreeterms) $errors[] = $this->langs['enter_te'] . '!';

		if($decode != md5(WEBSITE_KEY)) $errors[] = $this->langs['er_vvctimeout'];

		//上面的验证如果有错误时输出并停止运行, 减少数据库查询
		if(isset($errors)) Error($errors);

		//进一步验证, 需要查询数据库
		if(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '$username'")){
			$errors = $this->langs['er_noname'];

		}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE email = '$email'")){
			$errors = $this->langs['er_noemail'];

		}elseif(!CheckVVC($vvckey, $vvc)){ //如果正确的话, 验证码记录将删除
			$errors = $this->langs['er_badcode'];
		}

		if(isset($errors)) Error($errors);

		//无错误写入数据
		$lang = Iif(IS_CHINESE, 1, 0); //用户语言(1中文, 0英文)
		$groupid = 2; //注册用户的默认用户组
		$nickname = $username; //默认昵称为用户名
		$activated = Iif(APP::$_CFG['siteRegisterCheck'] == 'Auto', 1, -1); //如果注册需要验证, 用户状态为未激活(-1)
		$verifycode = Iif(APP::$_CFG['siteRegisterCheck'] == 'EmailVerify', PassGen(8)); //如果注册需要邮件验证, 生成一个验证码

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "user (groupid, activated, username, password, verifycode, joindate, joinip, lang, nickname, email) VALUES ($groupid, $activated, '$username', '".md5($password)."', '$verifycode', '".time()."', '".GetIP()."', $lang, '$nickname', '$email')");

		$userid = APP::$DB->insert_id; //用户ID号

		//如果注册需要邮件验证, 发送邮件. 跳转到用户首页, 如果邮件失败, 可重新发送验证邮件
		if($verifycode){
			@set_time_limit(0); //发送邮件时可能运行时间较长, 解除运行时间的限制

			$subject = $this->langs['activate_acc'] . ' -- ' . $this->sitename;

			$verify_url = BASEURL . Iif(!SITEREWRITE, 'index.php/') . 'register/verify?key=' . base64_encode($userid) . '&sid=' . md5($username . WEBSITE_KEY. md5($password) . $verifycode);

			$content = str_replace('//1', $username, $this->langs['activate_acc_info']);
			$content .= "<br><br><a href=\"$verify_url\" target=\"_blank\">$verify_url</a><br><br>";

			if(SendMail($email, $subject, $content, $lang) === true){
				Success('', 0, $this->langs['activate_info']); //邮件发送成功, 对话框不自动动关闭, 不跳转

				$this->register(); //重新加载注册页面
			}else{
				Error($this->langs['activate_acc_err']);
			}

		}elseif($activated != 1){ //等待管理员人工验证

			$url = Iif($backurl, $backurl, 'index'); //如果没有前一个页面的URL, 对话框关闭后跳转到首页
			Success($url, 0, '<font color=red>' . $this->langs['register_waiting'] . '</font>', '', $backurl); //参数$backurl表示不需要对URL进行伪静态处理

		}else{ //更新用户状态, 跳转到用户首页
			if($this->user->CreateSession($userid)){
				$url = 'uc'; //激活成功创建session, 6秒后自动跳转到用户首页
			}else{
				$url = Iif($backurl, $backurl . Iif(strstr($backurl, '?'), '&login', '?login'), 'index?login'); //跳转到首页并显示登录框
			}

			Success($url, 6, $this->langs['register_ok'], '', $backurl);
		}
	}


	//激活帐号
    public function verify(){
		$key = ForceStringFrom('key');
		$sid = ForceStringFrom('sid');

		if(!IsGet('key') OR !IsGet('sid') OR !$key OR !$sid) Error($this->langs['er_link']);

		$userid = ForceInt(base64_decode($key));
		if(!$userid) Error($this->langs['er_link']);

		//ug.grouptype = 0 只允许前台用户可以激活, 管理员用户不可以激活
		if(!$user = APP::$DB->getOne("SELECT u.userid, u.username, u.password, u.nickname, u.verifycode FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.userid = '$userid' AND ug.grouptype = 0")){

			Error($this->langs['er_nouser']); //用户不存在
		}else{
			$code = md5($user['username'] . WEBSITE_KEY. $user['password'] . $user['verifycode']);

			if($sid != $code) Error($this->langs['er_link']); //再次验证URL, 确保安全

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET activated = 1, verifycode = '' WHERE userid = '$userid'");

			//激活成功创建session, 6秒后自动跳转到用户首页; 创建session不成功时, 跳转到首页并显示登录框
			$url = Iif($this->user->CreateSession($userid), 'uc', 'index?login');
			Success($url, 6, $this->langs['activate_ok']);
		}
	}

}

?>