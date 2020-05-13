<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_ajax extends SAjax{
	public function __construct($path){
		parent::__construct($path);

		$this->action = ForceStringFrom('action'); //用于区别同一个动作下的多个ajax操作
	}


	//编辑个人信息
    public function edit(){
		$this->ajax['s'] = 0;

		//验证登录权限
		if(!$this->CheckAccess('login')){
			$this->ajax['i'] = $this->langs['er_nopermission'];
			die($this->json->encode($this->ajax));
		}

		$password = ForceStringFrom('password');
		$repassword = ForceStringFrom('repassword');

		if($password AND strlen($password) < 4){
			$this->ajax['i'] = $this->langs['enter_ps'] . "(4 - 20" . $this->langs['characters'] . ") !";
		}elseif($password AND $password != $repassword){
			$this->ajax['i'] = $this->langs['er_pnotsame'];
		}else{
			$this->ajax['s'] = 1;

			$userid = $this->user->data['userid'];
			$nickname        = ForceStringFrom('nickname');
			if(!$nickname) $nickname = $this->user->data['username'];
			$profile        = ForceStringFrom('profile');
			$company        = ForceStringFrom('company');
			$address        = ForceStringFrom('address');
			$postcode        = ForceStringFrom('postcode');
			$tel        = ForceStringFrom('tel');
			$fax        = ForceStringFrom('fax');
			$online        = ForceStringFrom('online');
			$website        = ForceStringFrom('website');

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET 
			". Iif($password, "password = '" . md5($password) . "',") . "
			nickname       = '$nickname',
			profile       = '$profile',
			company       = '$company',
			address       = '$address',
			postcode       = '$postcode',
			tel       = '$tel',
			fax       = '$fax',
			online       = '$online',
			website       = '$website'												 
			WHERE userid      = '$userid'");
		}

		die($this->json->encode($this->ajax));
	} 

	//上传头像(flash文件上传)模拟ajax操作
    public function avatar(){
		$result = array();
		$result['ok'] = false;

		$key = ForceStringFrom('key');
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		//验证登录权限及防机器人
		if(!$this->CheckAccess('login')){
			$error = $this->langs['er_nopermission'];
		}elseif($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$error = $this->langs['er_cookie'];
		}

		if(isset($error)){
			$result['msg'] = $error;
			die($this->json->encode($result));
		}

		$avatarpath = ROOT . 'uploads/avatars/';
		MakeDir($avatarpath); //avatars文件夹可能不存在, 先创建之

		$userid = sprintf("%09d", $this->user->data['userid']); // $userid并不真正的用户id
		$dir1 = substr($userid, 0, 3) . '/';
		$dir2 = substr($userid, 3, 2) . '/';
		$dir3 = substr($userid, 5, 2) . '/';
		$avatarname = $dir1. $dir2. $dir3. substr($userid, -2); //头像文件名不含后缀

		MakeDir($avatarpath.$dir1);
		MakeDir($avatarpath.$dir1.$dir2);
		MakeDir($avatarpath.$dir1.$dir2.$dir3);

		foreach($_FILES AS $key => $file){
			if(!IsUploadedFile($file['tmp_name'])) $error = $this->langs['er_file'];
			$image_size = @getimagesize($file['tmp_name']); //看看它是不是图片
			if(!$image_size) $error = $this->langs['er_file'];

			if(isset($error)){
				$result['msg'] = $error;
				die($this->json->encode($result));
			}

			$ext = Iif($key == '__avatar1', '1.jpg', '.jpg');
			$avatar = $avatarpath. $avatarname . $ext; //头像绝对路径及文件名
			$ok = @move_uploaded_file($file['tmp_name'], $avatar);
			if(!$ok){
				$result['msg'] = $this->langs['er_avatar'];
				die($this->json->encode($result));
			}
		}

		//返回大头像的URL
		$result['msg'] = SYSDIR . "uploads/avatars/$avatarname" . '1.jpg?' . time(); //加一个参数方便更新原头像
		$result['ok'] = true;
		die($this->json->encode($result));
	}

	//发送新短信或回复短信
    public function savepm(){
		$this->ajax['s'] = 0; //先将ajax设置为失败状态

		$key = ForceStringFrom('key');
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		$pmid = ForceIntFrom('pmid');

		//验证登录权限及防机器人
		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$this->CheckAccess('login')){ //发短信及回复时均验证登录权限
			$this->ajax['i'] = $this->langs['er_nopermission'];
		}

		if($this->ajax['i']) die($this->json->encode($this->ajax));

		$fromid  = $this->user->data['userid'];
		$fromname  = $this->user->data['nickname'];
		$message = ForceString(ShortTitle($_POST['message'], 1800));

		if($pmid){ //回复或追加短信
			if(strlen($message) == 0){
				$this->ajax['i'] = $this->langs['er_nomsg'];
				die($this->json->encode($this->ajax));
			}

			if($pm = APP::$DB->getOne("SELECT toid, toname, fromid, fromname, readed FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pmid' AND refer_id = 0 AND (toid = '$fromid' OR fromid = '$fromid')")){

				$time = time();
				if($pm['toid'] == $fromid){ //回复我收到的短信
					APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "pm (refer_id, toid, toname, fromid, fromname, message, created) VALUES ('$pmid', '$pm[fromid]', '$pm[fromname]', '$fromid', '$fromname', '$message', '$time')");

					APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET newreply = 1 WHERE pmid = '$pmid'"); //设置主短信有新回复
				}else{ //追加或回复我发送的短信
					APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "pm (refer_id, toid, toname, fromid, fromname, message, created) VALUES ('$pmid', '$pm[toid]', '$pm[toname]', '$fromid', '$fromname', '$message', '$time')");

					APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET readed = 0 WHERE pmid = '$pmid'"); //设置为对方未读
				}

				$this->ajax['s'] = 1; //设置ajax操作成功
				$this->ajax['i'] = DisplayDate($time, '', 1); //回复成功后将发送时间返回, 供页面实时更新
			}else{
				$this->ajax['i'] = $this->langs['er_replyerrorinfo'];
			}
		}else{ //保存新短信
			$toid     = ForceIntFrom('toid');
			$subject  = ForceString(ShortTitle($_POST['subject'], 180));

			if(!$this->CheckAccess('pm')){ //发短信验证验证PM权限
				$this->ajax['i'] = $this->langs['er_nopm_right'];
			}elseif($toid == $fromid){
				$this->ajax['i'] = $this->langs['er_notself'];
			}elseif(strlen($subject) == 0){
				$this->ajax['i'] = $this->langs['er_nopmtitle'];
				$this->ajax['d'] = 1; //供JS确认为标题错误, 特别显示
			}

			if($this->ajax['i']) die($this->json->encode($this->ajax));

			if($toid AND $touser = APP::$DB->getOne("SELECT userid, nickname FROM " . TABLE_PREFIX . "user WHERE userid = '$toid' AND activated = 1")){

				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "pm (toid, toname, fromid, fromname, subject, message, created) VALUES ('$toid', '$touser[nickname]', '$fromid', '$fromname', '$subject', '$message', '".time()."')");

				$this->ajax['s'] = 1; //设置ajax操作成功
			}else{
				$this->ajax['i'] = $this->langs['er_nopmuser'];
			}
		}

		die($this->json->encode($this->ajax));
	}


	//登录用户追加询价
    public function savequiry(){
		$this->ajax['s'] = 0; //先将ajax设置为失败状态

		$key = ForceStringFrom('key');
		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		//验证登录权限及防机器人
		if($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['siteKillRobotCode'])){
			$this->ajax['i'] = $this->langs['er_cookie'];
		}elseif(!$this->CheckAccess('login')){ //追加询价时均验证登录权限
			$this->ajax['i'] = $this->langs['er_nopermission'];
		}

		if($this->ajax['i']) die($this->json->encode($this->ajax));

		$myid  = $this->user->data['userid'];
		$e_id = ForceIntFrom('e_id');
		$content = ForceString(ShortTitle($_POST['content'], 3600));

		if(!$content){
			$this->ajax['i'] = 'Please enter enquiry content!';
		}else{
			$eq = APP::$DB->getOne("SELECT status FROM " . TABLE_PREFIX . "enquiry WHERE e_id = '$e_id' AND refer_id = 0 AND userid = '$myid'");
			if(!$eq) $this->ajax['i'] = $this->langs['er_replyeqerror'];
		}

		if(!$this->ajax['i']){
			$time = time();
			//追加询价中status为0, 管理员的回复为1
			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "enquiry (refer_id, status, userid, content, created) VALUES ('$e_id', 0, '$myid', '$content', '$time')");

			//询价的状态重新设置成未回复, 重设时间(便于前台用户的询价排序)
			if($eq['status']) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "enquiry SET status = 0, created = '" . time() . "' WHERE e_id = '$e_id'");

			$this->ajax['s'] = 1; //设置ajax状态成功
			$this->ajax['i'] = DisplayDate($time, '', 1); //将发送时间返回, 供页面动态更新
		}

		die($this->json->encode($this->ajax));
	}


	//删除已上传的组图片
    public function deluploaded(){
		if(!$this->CheckAccess('login')) die($this->json->encode($this->ajax));

		$upload_path = ROOT . 'uploads/';

		if($this->action == 'deleteone'){
			$file = ForceStringFrom('file');
			@unlink($upload_path . $file);
		}else{
			$files = get_upload_files($this->user->data['userid']);
			foreach($files AS $Item){
				@unlink($upload_path . $Item);
			}
		}
		die($this->json->encode($this->ajax));
	}
}

?>