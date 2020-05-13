<?php if(!defined('ROOT')) die('Access denied.');

class c_ajax extends SAjax{
	public function __construct($path){
		parent::__construct($path);

		$this->action = ForceStringFrom('action'); //用于区别同一个动作下的多个ajax操作
	}

	//Ajax创建图片验证码key值
    public function vvckey(){
		$this->ajax['i'] = CreateVVC();
		die($this->json->encode($this->ajax));
	}

	//Ajax注册验证
    public function register(){
		if('checkusername' == $this->action){
			$username = ForceStringFrom('username'); //用户名

			if(!$username){
				$error = $this->langs['enter_un'] . '!';
			}elseif(!isName($username)){
				$error = $this->langs['er_badname'];
			}

			if(isset($error)){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = $error;
			}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '$username'")){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = $this->langs['er_noname'];
			}
		}elseif('checkemail' == $this->action){
			$email = ForceStringFrom('email');

			if(!$email){
				$error = $this->langs['enter_em'] . '!';
			}elseif(!IsEmail($email)){
				$error = $this->langs['er_bademail'];
			}

			if(isset($error)){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = $error;
			}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE email = '$email'")){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = $this->langs['er_noemail'];
			}
		}elseif('checkvvc' == $this->action){
			$vvckey = ForceIntFrom('vvckey');
			$vvc = ForceStringFrom('vvc');

			if(!CheckVVC($vvckey, $vvc, false)){ //false表示不要删除验证码, 表单提交时还需要再次验证
				$this->ajax['s'] = 0;
				$this->ajax['i'] = $this->langs['er_badcode'];
			}
		}

		die($this->json->encode($this->ajax));
	}


	//Ajax用户登录
    public function login(){
		$key = ForceStringFrom('key');
		$code = ForceStringFrom('code');
		$decode = authcode($code, 'DECODE', $key); //解码
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		$username = ForceStringFrom('username');
		$password = ForceStringFrom('password');
		$remember = ForceIntFrom('remember');

		$this->ajax['s'] = 0; //ajax返回默认为错误状态

		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$username){
			$this->ajax['i'] = $this->langs['enter_un'] . '!';
			$this->ajax['d'] = 1;  //错误类型: 1 用户名错误
		}elseif(!isName($username)){
			$this->ajax['i'] = $this->langs['er_badname'];
		}elseif(!$password){
			$this->ajax['i'] = $this->langs['enter_ps'] . '!';
			$this->ajax['d'] = 2;  //错误类型: 2 密码错误
		}elseif($decode != md5(WEBSITE_KEY)){
			$this->ajax['i'] = $this->langs['er_vvctimeout'];
		}else{
			$info = $this->user->check($username, $password, $remember); //验证用户, 如果通过返回true, 失败返回错误信息或false

			if($info !== true){ //严格类型比较
				$this->ajax['i'] = Iif($info, $info, 'Login failed!');
			}else{
				//获取昵称及未读短信
				$sql = "SELECT u.userid, u.nickname, u.email, ug.grouptype, ug.actions,
							(select COUNT(*)  FROM " . TABLE_PREFIX . "pm WHERE ((toid = u.userid AND readed = 0) OR (fromid = u.userid AND newreply = 1)) AND refer_id = 0) AS pms
							FROM " . TABLE_PREFIX . "user u
							LEFT JOIN " . TABLE_PREFIX . "usergroup ug ON ug.groupid = u.groupid
							WHERE username = '$username'";
				$user = APP::$DB->getOne($sql);

				$this->ajax['s'] = 1; //登录成功, 返回ajax状态为ok
				$this->ajax['d']['nickname'] = $user['nickname'];
				$this->ajax['d']['pms'] = $user['pms'];

				//JS中初步验证权限字符串, 仅包含用户ID, 及短信,评论,询价3项权限
				$this->ajax['d']['rights'] = getUserRights($user);
			}
		}

		die($this->json->encode($this->ajax));
	}

	//Ajax找回密码
    public function getpass(){
		$key = ForceStringFrom('key');
		$code = ForceStringFrom('code');
		$decode = authcode($code, 'DECODE', $key); //解码
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		$email = ForceStringFrom('email');
		$this->ajax['s'] = 0; //ajax返回默认为错误状态

		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$email){
			$this->ajax['i'] = $this->langs['enter_em'] . '!';
		}elseif(!IsEmail($email)){
			$this->ajax['i'] = $this->langs['er_bademail'];
		}elseif($decode != md5(WEBSITE_KEY)){
			$this->ajax['i'] = $this->langs['er_vvctimeout'];

		}else{
			$user = APP::$DB->getOne("SELECT u.userid, u.password, u.nickname, ug.grouptype, ug.actions FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.email = '$email' AND u.activated = 1");

			//不允许登录的用户也无法找回密码
			if(!$user OR !$user['userid'] OR ($user['grouptype'] == 0 AND !getAccess($user['actions'], 'login'))){

				$this->ajax['i'] = $this->langs['er_useremail'];

			}else{ //发送确认邮件
				@set_time_limit(0); //发送邮件时可能运行时间较长, 解除运行时间的限制

				$subject = $this->langs['getbackpass'] . ' -- ' . $this->sitename;

				$verifycode = PassGen(8);

				$verify_url = BASEURL . Iif(!SITEREWRITE, 'index.php/') . 'getpass/verify?key=' . base64_encode($user['userid']) . '&sid=' . md5($user['nickname'] . WEBSITE_KEY. $user['password'] . $verifycode);

				$content = str_replace('//1', $user['nickname'], $this->langs['i_mailcontent']);
				$content .= "<br><br><a href=\"$verify_url\" target=\"_blank\">$verify_url</a><br><br>";

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET verifycode = '$verifycode' WHERE userid = '$user[userid]'");

				if(SendMail($email, $subject, $content, IS_CHINESE) === true){
					$this->ajax['s'] = 1; //找回密码发送邮件成功
				}else{
					$this->ajax['i'] = $this->langs['i_getpassmailerr'];
				}
			}
		}

		die($this->json->encode($this->ajax));
	}

	//Ajax退出登录
    public function logout(){
		$this->user->logout(); //直接调用user模型的logout动作

		die($this->json->encode($this->ajax));
	}


	//发表评论
    public function savecomm(){
		$this->ajax['s'] = 0; //先将ajax设置为失败状态

		$userid  = $this->user->data['userid'];
		$key = ForceStringFrom('key');
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);
		if(!$userid){ //游客多两个验证
			$code = ForceStringFrom('code');
			$decode = authcode($code, 'DECODE', $key); //解码
			$vvckey = ForceIntFrom('vvckey');
			$vvc = ForceStringFrom('vvc');
		}

		//验证权限及防机器人
		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$userid AND $decode != md5(WEBSITE_KEY)){
			$this->ajax['i'] = $this->langs['er_vvctimeout'];
		}elseif(!$this->CheckAccess('comment')){
			$this->ajax['i'] = $this->langs['er_nocomm_right'];
		}

		if($this->ajax['i']) die($this->json->encode($this->ajax));

		$for_id = ForceIntFrom('for_id');
		$type = ForceIntFrom('type'); //评论类型: 0文章; 1产品
		$username  = ForceString(ShortTitle($_POST['nickname'], 32));
		$content = ForceString(ShortTitle($_POST['content'], 1800));
		$lang = IS_CHINESE;
		$actived =  Iif($this->CheckAccess('cRightnow'), 1, -1); //评论状态, -1表示待审

		if(!$username){
			$this->ajax['d'] = 1; //供JS确认为昵称错误, 特别显示
		}elseif(!$content){
			$this->ajax['d'] = 2;
		}elseif(!$for_id){
			$this->ajax['i'] = Iif($type, $this->langs['er_nop'], $this->langs['er_noa']);
		}elseif(!$userid AND !CheckVVC($vvckey, $vvc)){ //验证码最后核对, 因为需要读数据库
			$this->ajax['d'] = 3;
			$this->ajax['i'] = $this->langs['er_badcode'];
		}

		if($this->ajax['i'] OR $this->ajax['d']) die($this->json->encode($this->ajax));

		$time = time();
		if($type){
			if($product = APP::$DB->getOne("SELECT pro_id FROM " . TABLE_PREFIX . "product WHERE pro_id = '$for_id' AND is_show = 1")){
				$this->ajax['s'] = 1; //设置ajax为操作成功状态
			}else{
				$this->ajax['i'] = $this->langs['er_nop'];
			}
		}else{
			if($article = APP::$DB->getOne("SELECT a_id FROM " . TABLE_PREFIX . "article WHERE a_id = '$for_id' AND is_show = 1")){
				$this->ajax['s'] = 1;
			}else{
				$this->ajax['i'] = $this->langs['er_noa'];
			}
		}

		if($this->ajax['s']){ //验证成功保存评论等
			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "comment (for_id, userid, type, lang, username, actived, content, created) VALUES ('$for_id', '$userid', '$type', '$lang', '$username', '$actived', '$content', '$time')");

			//增加用户的产品评论数
			if($userid) {
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET " . Iif($type, "pc_num = (pc_num+1)", "ac_num = (ac_num+1)") . " WHERE userid = '$userid'");

				$filename =  GetUserImage($userid);
				if(!file_exists(ROOT . $filename)) 	{
					$this->ajax['avatar'] = T_URL . "images/noavatar.gif";
				}else{
					$this->ajax['avatar'] = SYSDIR . $filename;
				}
			}else{
				$this->ajax['avatar'] = T_URL . "images/noavatar.gif";
			}

			$this->ajax['d'] = $actived; //返回评论发布状态
			$this->ajax['i'] = DisplayDate($time, '', 1);
		}

		die($this->json->encode($this->ajax));
	}


	//提交询价
    public function savequiry(){
		$this->ajax['s'] = 0; //先将ajax设置为失败状态

		$userid  = $this->user->data['userid'];
		$key = ForceStringFrom('key');
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);
		if(!$userid){ //游客多两个验证
			$code = ForceStringFrom('code');
			$decode = authcode($code, 'DECODE', $key); //解码
			$vvckey = ForceIntFrom('vvckey');
			$vvc = ForceStringFrom('vvc');
		}

		//验证权限及防机器人
		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$userid AND $decode != md5(WEBSITE_KEY)){
			$this->ajax['i'] = $this->langs['er_vvctimeout'];
		}elseif(!$this->CheckAccess('enquiry')){
			$this->ajax['i'] = $this->langs['er_noquiry_right'];
		}

		if($this->ajax['i']) die($this->json->encode($this->ajax));

		$username  = ForceString(ShortTitle($_POST['username'], 32));
		$pro_id = ForceIntFrom('pro_id');
		$title  = ForceString(ShortTitle($_POST['title'], 180));
		$content = ForceString(ShortTitle($_POST['content'], 3600));
		$email  = ForceStringFrom('email');

		if(!$username){
			$this->ajax['d'] = 5;
		}elseif(!$title){
			$this->ajax['d'] = 1;
		}elseif(!$content){
			$this->ajax['d'] = 2;
		}elseif(!$email){
			$this->ajax['d'] = 3;
		}elseif(!IsEmail($email)){
			$this->ajax['d'] = 3;
			$this->ajax['i'] = $this->langs['er_bademail'];
		}elseif(!$pro_id){
			$this->ajax['i'] = $this->langs['er_noep'];
		}elseif(!$userid AND !CheckVVC($vvckey, $vvc)){ //验证码最后核对, 因为需要读数据库
			$this->ajax['d'] = 4;
			$this->ajax['i'] = $this->langs['er_badcode'];
		}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid != '$userid' AND email = '$email'")){
			$this->ajax['d'] = 3;
			$this->ajax['i'] = $this->langs['er_noemail'];
		}

		if($this->ajax['i'] OR $this->ajax['d']) die($this->json->encode($this->ajax));

		if(APP::$DB->getOne("SELECT pro_id FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id' AND is_show = 1")){
			$lang = IS_CHINESE;
			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "enquiry (email, pro_id, userid, username, lang, title, content, created) VALUES ('$email', '$pro_id', '$userid', '$username', '$lang', '$title', '$content', '" .  time() . "')");

			if($userid)  APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET q_num = (q_num+1) WHERE userid = '$userid'");
			$this->ajax['s'] = 1; //设置ajax为操作成功状态
		}else{
			$this->ajax['i'] = $this->langs['er_noep'];
		}

		die($this->json->encode($this->ajax));
	}
}

?>