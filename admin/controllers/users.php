<?php if(!defined('ROOT')) die('Access denied.');

class c_users extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

		$this->upload_path = ROOT . 'uploads/';

		@set_time_limit(0);  //可以运行时间较长, 解除时间限制
	}

	//删除产品图片文件
	private function UnlinkImage($path, $filename) {
		@unlink($this->upload_path . $path . '/' . $filename . '_s.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_m.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_l.jpg');
	}

	//按用户ID删除用户及收到的短信, 但不删除用户发表信息
	private function DeleteUser($userid){
		if(!$userid) return;
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "user WHERE userid = '$userid'");

		//删除收到的短信及其回复
		$getpms = APP::$DB->query("SELECT pmid FROM " . TABLE_PREFIX . "pm WHERE toid = '$userid' AND refer_id = 0");
		while($pm = APP::$DB->fetch($getpms)){
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pm[pmid]' OR refer_id = '$pm[pmid]'");
		}

		//删除头像
		@unlink(ROOT . GetUserImage($userid)); //小头像
		@unlink(ROOT . GetUserImage($userid, 1)); //大头像

		//删除组图片临时文件
		$files = get_upload_files($userid);
		foreach($files AS $Item){
			@unlink($this->upload_path . $Item);
		}
	}

	//按用户ID删除用户发表的信息: 文章, 产品, 评论, 询价等
	private function DeleteUserPost($userid){
		if(!$userid) return;
		
		//删除用户文章
		$user_acats = array(); //用户于记录用户发表在各个文章分类的文章数量
		//获得用户发表的所有文章
		$getarticles = APP::$DB->query("SELECT a_id, cat_id FROM " . TABLE_PREFIX . "article WHERE userid = '$userid'");
		while($article = APP::$DB->fetch($getarticles)){
			$user_acats[$article['cat_id']] += 1;

			//删除当前文章的所有评论
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "comment WHERE for_id = '$article[a_id]' AND type = 0");
		}
		//更新文章分类的文章数量
		foreach($user_acats as $cat_id => $num){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts - $num) WHERE cat_id = '$cat_id'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "article WHERE userid = '$userid'"); //删除用户文章记录


		//删除用户产品
		$user_pcats = array(); //用户于记录用户发表在各个产品分类的产品数量
		//获得用户发表的所有产品
		$getproducts = APP::$DB->query("SELECT pro_id, cat_id, path, filename FROM " . TABLE_PREFIX . "product WHERE userid = '$userid'");
		while($product = APP::$DB->fetch($getproducts)){
			$user_pcats[$product['cat_id']] += 1;

			//删除当前产品的所有评论
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "comment WHERE for_id = '$product[pro_id]' AND type = 1");

			$this->UnlinkImage($product['path'], $product['filename']); //删除产品主图片

			//删除组图片
			$getimages = APP::$DB->query("SELECT path, filename FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$product[pro_id]'");
			while($image = APP::$DB->fetch($getimages)){
				$this->UnlinkImage($image['path'], $image['filename']);
			}
		}
		//更新产品分类的产品数量
		foreach($user_pcats as $cat_id => $num){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts - $num) WHERE cat_id = '$cat_id'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "product WHERE userid = '$userid'"); //删除用户产品记录


		//删除用户评论
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "comment WHERE userid = '$userid'"); //删除用户评论记录


		//删除用户询价
		$getenquiries = APP::$DB->query("SELECT e_id FROM " . TABLE_PREFIX . "enquiry WHERE userid = '$userid' AND reply_id = 0");
		while($enquiry = APP::$DB->fetch($getenquiries)){
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "enquiry WHERE userid = '$userid' OR reply_id = '$enquiry[e_id]'"); //删除用户询价记录
		}


		//将用户的文章,产品,评论, 询价记录数设置为0
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET p_num = 0, a_num = 0, q_num, pc_num = 0, ac_num = 0 WHERE userid = '$userid'");
	}

	//保存
	public function save(){
		$userid          = ForceIntFrom('userid');

		if($userid != $this->admin->data['userid']) $this->CheckAction('users'); //编辑自己时不验证权限

		$groupid     = ForceIntFrom('groupid');
		$username        = ForceStringFrom('username');
		$password        = ForceStringFrom('password');
		$passwordconfirm = ForceStringFrom('passwordconfirm');
		$activated       = ForceIntFrom('activated');
		$lang       = ForceIntFrom('lang');

		$nickname        = ForceStringFrom('nickname');
		if(!$nickname) $nickname = $username;

		$email           = ForceStringFrom('email');
		$profile        = ForceStringFrom('profile');
		$company        = ForceStringFrom('company');
		$address        = ForceStringFrom('address');
		$postcode        = ForceStringFrom('postcode');
		$tel        = ForceStringFrom('tel');
		$fax        = ForceStringFrom('fax');
		$online        = ForceStringFrom('online');
		$website        = ForceStringFrom('website');

		$deleteuser       = ForceIntFrom('deleteuser');
		$deleteuserpublish       = ForceIntFrom('deleteuserpublish');

		if($deleteuserpublish){
			$this->DeleteUserPost($userid); //删除用户发表的信息
		}

		if($deleteuser AND $userid != $this->admin->data['userid']){
			$this->DeleteUser($userid);
			Success('users'); //如果删除用户, 直接跳转
		}

		if(strlen($username) == 0){
			$errors[] = '请输入用户名!';
		}elseif(!IsName($username)){
			$errors[] = '用户名存在非法字符!';
		}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '$username' AND userid != '$userid'")){
			$errors[] = '用户名已存在!';
		}

		if($userid){
			if(strlen($password) OR strlen($passwordconfirm)){
				if(strcmp($password, $passwordconfirm)){
					$errors[] = '两次输入的密码不相同!';
				}
			}
		}else{
			if(strlen($password) == 0){
				$errors[] = '请输入密码!';
			}elseif($password != $passwordconfirm){
				$errors[] = '两次输入的密码不相同!';
			}
		}

		if(strlen($email) == 0){
			$errors[] = '请输入Email地址!';
		}elseif(!IsEmail($email)){
			$errors[] = 'Email地址不规范!';
		}elseif(APP::$DB->getOne("SELECT userid FROM " . TABLE_PREFIX . "user WHERE email = '$email' AND userid != '$userid'")){
			$errors[] = 'Email地址已占用!';
		}

		if(isset($errors)){
			Error($errors, Iif($userid, '编辑用户错误', '添加用户错误'));
		}else{
			if($userid){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET username    = '$username',
				".Iif($userid != $this->admin->data['userid'], "groupid = '$groupid', activated = '$activated',")."
				".Iif($password, "password = '" . md5($password) . "',")."
				lang       = '$lang',
				nickname       = '$nickname',
				email       = '$email',
				profile       = '$profile',
				company       = '$company',
				address       = '$address',
				postcode       = '$postcode',
				tel       = '$tel',
				fax       = '$fax',
				online       = '$online',
				website       = '$website'												 
				WHERE userid      = '$userid'");

			}else{
				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "user (groupid, activated, username, password, joindate, joinip, lang, nickname, email, profile, company, address, postcode, tel, fax, online, website) VALUES ('$groupid', 1, '$username', '".md5($password)."', '".time()."', '".GetIP()."', '$lang', '$nickname', '$email', '$profile', '$company', '$address', '$postcode', '$tel', '$fax', '$online', '$website')");
			}

			Success('users');
		}
	}

	//批量更新用户
	public function updateusers(){
		$this->CheckAction('users'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updateusers')){
			$userids   = $_POST['updateuserids'];
			$activateds   = $_POST['activateds'];

			for($i = 0; $i < count($userids); $i++){
				if($userids[$i] != $this->admin->data['userid']){
					APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET activated = '".ForceInt($activateds[$i])."' WHERE userid = '".ForceInt($userids[$i])."'"); //批量更改用户状态
				}
			}

		}else{
			$deleteuserids = $_POST['deleteuserids'];

			for($i = 0; $i < count($deleteuserids); $i++){
				$userid = ForceInt($deleteuserids[$i]);
				if($userid != $this->admin->data['userid']){
					$this->DeleteUser($userid); //批量删除用户, 但不删除用户发表的信息
				}
			}
		}

		Success('users?p=' . $page);
	}

	//编辑调用add
	public function edit(){
		$this->add();
	}

	//添加页面
	public function add(){
		$userid = ForceIntFrom('userid');

		if($userid){
			SubMenu('管理用户', array(array('添加用户', 'users/add'),array('编辑用户', 'users/edit?userid='.$userid, 1),array('用户列表', 'users')));
			
			$user = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "user WHERE userid = '$userid'");

			if(!$user) Error('您正在尝试编辑的用户不存在!', '编辑用户组错误');
		}else{
			SubMenu('添加用户', array(array('添加用户', 'users/add', 1),array('用户列表', 'users')));

			$user = array('userid' => 0, 'groupid' => 2, 'activated' => 1, 'lang' => 1);
		}

		$need_info = '&nbsp;&nbsp;<font class=red>* 必填项</font>';
		$pass_info = Iif($userid, '&nbsp;&nbsp;<font class=grey>不修改请留空</font>', $need_info);

		$getusergroups = APP::$DB->query("SELECT groupid, grouptype, groupname, groupname_en FROM " . TABLE_PREFIX . "usergroup WHERE groupid<>3 ORDER BY grouptype, groupid");
		$usergroupselect = '<select name="groupid" ' . Iif($userid == $this->admin->data['userid'], 'disabled') .'>';
		while($usergroup = APP::$DB->fetch($getusergroups)) {
			$usergroupselect .= '<option '. Iif($usergroup['grouptype'] ,'class="orange"') .' value="' . $usergroup['groupid'] . '" ' . Iif($user['groupid'] == $usergroup['groupid'], ' SELECTED') . '>' .   "$usergroup[groupname] ($usergroup[groupname_en])</option>";
		}
		$usergroupselect .= '</select>';


		echo '<form method="post" action="'.BURL('users/save').'">
		<input type="hidden" name="userid" value="' . $user['userid'] . '">';

		if($userid){
			TableHeader('编辑用户信息: <span class=note>' . $user['username'] . '</span>');
		}else{
			TableHeader('填写用户信息');
		}

		TableRow(array('<b>用户名:</b>', '<input type="text" name="username" value="'.$user['username'].'" size="20">' .$need_info . Iif($userid, "<font class=light><img src='" . GetAvatar($user['userid']) . "' class='user_avatar wh30' style='margin-left:60px'>登录: $user[loginnum]&nbsp;&nbsp;询价: $user[q_num]&nbsp;&nbsp文章: $user[a_num]&nbsp;&nbsp;产品: $user[p_num]&nbsp;&nbsp;评论: " . ($user[pc_num] + $user[ac_num]) . "</font>" . '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$user['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>')));

		TableRow(array('<b>所属用户组:</b>', $usergroupselect));

		$Radio = new SRadio;
		$Radio->Name = 'lang';
		$Radio->SelectedID = $user['lang'];
		$Radio->AddOption(1, '中文', '&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(0, '英文', '&nbsp;&nbsp;');

		TableRow(array('<b>前台语言:</b>', $Radio->Get()));

		if($userid){
			$Radio ->Clear();
			$Radio->Name = 'activated';
			$Radio->SelectedID = $user['activated'];
			$Radio->AddOption(1, '正常', '&nbsp;&nbsp;&nbsp;&nbsp;');
			$Radio->AddOption(0, '已禁止', '&nbsp;&nbsp;&nbsp;&nbsp;');
			$Radio->AddOption(-1, '未激活', '&nbsp;&nbsp;');
			$Radio->Attributes = Iif($userid == $this->admin->data['userid'], ' disabled');

			TableRow(array('<b>状态?</b>', $Radio->Get()));
		}

		TableRow(array('<b>密码:</b>', '<input type="text" name="password" size="20">'.$pass_info));
		TableRow(array('<b>确认密码:</b>', '<input type="text" name="passwordconfirm" size="20">'.$pass_info));
		TableRow(array('<b>Email地址:</b>', '<input type="text" name="email" value="'.$user['email'].'" size="20">'.$need_info));

		if($userid){
			TableRow(array('<b>删除此用户?</b>', '<input type="checkbox" ' . Iif($userid == $this->admin->data['userid'], 'disabled') .' name="deleteuser" value="1">&nbsp;<font class=redb>慎选!</font> <span class=light>如果删除此用户, 而不选择删除用户发表的信息, 那么用户发表的信息将保留.</span>'));

			TableRow(array('<b>删除此用户发表的信息?</b>', '<input type="checkbox" name="deleteuserpublish" value="1">&nbsp;<font class=redb>慎选!</font> <span class=light>删除此用户发表的所有信息, 包括: 询价、文章、产品及评论等.</span>'));
		}

		TableRow(array('<b>昵称:</b>', '<input type="text" name="nickname" value="'.$user['nickname'].'" size="20">'));
		TableRow(array('<b>单位名称:</b>', '<input type="text" name="company" value="'.$user['company'].'" size="40">'));
		TableRow(array('<b>通讯地址:</b>', '<input type="text" name="address" value="'.$user['address'].'" size="40">'));
		TableRow(array('<b>邮编:</b>', '<input type="text" name="postcode" value="'.$user['postcode'].'" size="20">'));
		TableRow(array('<b>电话:</b>', '<input type="text" name="tel" value="'.$user['tel'].'" size="20">'));
		TableRow(array('<b>传真:</b>', '<input type="text" name="fax" value="'.$user['fax'].'" size="20">'));
		TableRow(array('<b>在线联系:</b>', '<input type="text" name="online" value="'.$user['online'].'" size="40">'));
		TableRow(array('<b>网址:</b>', '<input type="text" name="website" value="'.$user['website'].'" size="40">'));
		TableRow(array('<b>个人简介:</b>', '<textarea name="profile" style="height:80px;width:400px;" id="profile">'.$user['profile'].'</textarea>'));

		TableFooter();

		PrintSubmit(Iif($userid, '保存更新', '添加用户'));
	}

	public function index(){
		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$letter = ForceStringFrom('key');
		$search = ForceStringFrom('s');
		$groupid = ForceStringFrom('g');

		if(IsGet('s')){
			$search = urldecode($search);
		}

		$getusergroups = APP::$DB->query("SELECT groupid, grouptype, groupname, groupname_en FROM " . TABLE_PREFIX . "usergroup WHERE groupid <> 3 ORDER BY grouptype, groupid");
		while($usergroup = APP::$DB->fetch($getusergroups)) {
			$usergroups[$usergroup['groupid']] = Iif($usergroup['grouptype'], "<font class=orange>$usergroup[groupname]</font>", $usergroup['groupname']);
			$usergroup_options .= "<option ". Iif($usergroup['grouptype'] ,'class="orange"') ." value=\"$usergroup[groupid]\" " . Iif($usergroup['groupid'] == $groupid, 'SELECTED') . ">$usergroup[groupname] ($usergroup[groupname_en])</option>";
		}

		$start = $NumPerPage * ($page-1);

		if($search OR $letter OR $groupid){
			SubMenu('用户列表', array(array('添加用户', 'users/add'), array('全部用户', 'users')));
		}else{
			SubMenu('用户列表', array(array('添加用户', 'users/add')));
		}

		TableHeader('快速查找用户');
		for($alphabet = 'a'; $alphabet != 'aa'; $alphabet++){
			$alphabetlinks .= '<a href="'.BURL('users?key=' . $alphabet) . '" title="' . strtoupper($alphabet) . '开头的用户" class="link_alphabet">' . strtoupper($alphabet) . '</a> &nbsp;';
		}

		TableRow('<center><b><a href="'.BURL('users').'" class="link_alphabet">全部用户</a>&nbsp;&nbsp;&nbsp;' . $alphabetlinks . '&nbsp;<a href="'.BURL('users?key=Validating').'" class="link_alphabet">未激活</a>&nbsp;&nbsp;<a href="'.BURL('users?key=Neverlogin').'" class="link_alphabet">未登陆</a>&nbsp;&nbsp;<a href="'.BURL('users?key=Other').'" class="link_alphabet">中文名</a></b></center>');
		TableFooter();

		echo '<form method="post" action="'.BURL('users').'" name="searchusers">';

		TableHeader('搜索用户');
		TableRow('<center><label>ID, 用户名, 昵称或Email:</label>&nbsp;<input type="text" name="s" size="18">&nbsp;&nbsp;&nbsp;<label>用户组或语言:</label>&nbsp;<select name="g"><option value="0">全部</option>' . $usergroup_options . '<option value="cn" ' . Iif($groupid == 'cn', 'SELECTED') . ' class=blue>中文 (语言)</option><option value="en" ' . Iif($groupid == 'en', 'SELECTED') . ' class=blue>EN (语言)</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索用户" class="cancel"></center>');
		TableFooter();

		echo '</form>';

		if($letter){
			if($letter == 'Other'){
				$searchsql = " WHERE username NOT REGEXP(\"^[a-zA-Z]\") ";
				$title = '<span class=note>中文用户名</span> 的用户列表';
			}else if($letter == 'Validating'){
				$searchsql = " WHERE activated < 1 ";
				$title = '<span class=note>未激活</span> 的用户列表';
			}else if($letter == 'Neverlogin')	{
				$searchsql = " WHERE lastdate = 0 ";
				$title = '<span class=note>未登陆</span> 的用户列表';
			}else{
				$searchsql = " WHERE username LIKE '$letter%' ";
				$title = '<span class=note>'.strtoupper($letter) . '</span> 字母开头的用户列表';
			}
		}else if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$searchsql = " WHERE userid = '".ForceInt($search)."' "; //按ID搜索
				$title = "搜索ID号为: <span class=note>$search</span> 的用户";
			}else{
				$searchsql = " WHERE (username LIKE '%$search%' OR nickname LIKE '%$search%' OR email LIKE '%$search%') "; //按ID搜索
				$title = "搜索: <span class=note>$search</span> 的用户列表";
			}

			if($groupid) {
				if($groupid == 'cn' OR $groupid == 'en'){
					$searchsql .= " AND lang = " . Iif($groupid == 'cn', 1, 0)." ";
					$title = "在 <span class=note>" .Iif($groupid == 'cn', '中文用户', '英文用户'). "</span> 中, " . $title;
				}else{
					$searchsql .= " AND groupid = '$groupid' ";
					$title = "在 <span class=note>$usergroups[$groupid]</span> 中, " . $title;
				}
			}
		}else if($groupid){
			if($groupid == 'cn' OR $groupid == 'en'){
				$searchsql .= " WHERE lang = " . Iif($groupid == 'cn', 1, 0)." ";
				$title = "<span class=note>" .Iif($groupid == 'cn', '中文用户', '英文用户'). "</span> 的全部列表";
			}else{
				$searchsql .= " WHERE groupid = '$groupid' ";
				$title = "<span class=note>$usergroups[$groupid]</span> 的全部用户列表";
			}
		}else{
			$searchsql = '';
			$title = '全部用户列表';
		}

		$getusers = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "user ".$searchsql." ORDER BY activated ASC, userid DESC LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(userid) AS value FROM " . TABLE_PREFIX . "user ".$searchsql);

		echo '<form method="post" action="'.BURL('users/updateusers').'" name="usersform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');
		TableRow(array('ID', '用户名(昵称)', '语言', '用户组', '状态', 'Email', '登录', '询价', '文章', '产品', '评论', '注册日期(IP)', '最后登陆(IP)', '<input type="checkbox" id="checkAll" for="deleteuserids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何用户!</font><BR><BR></center>');
		}else{
			while($user = APP::$DB->fetch($getusers)){
				TableRow(array($user['userid'],
				'<input type="hidden" name="updateuserids[]" value="'.$user['userid'].'"><a title="编辑" href="'.BURL('users/edit?userid='.$user['userid']).'"><img src="' . GetAvatar($user['userid']) . '" class="user_avatar wh30">'.Iif($user['activated'] == 1, $user['username'] . " ($user[nickname])", "<font class=red>" . Iif($user['activated'] == 0, "<s>$user[username] ($user[nickname])</s>", "$user[username] ($user[nickname])") . "</font>") . '</a>&nbsp;<a href="'.BURL('pm/send?userid='.$user['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>',
				Iif($user['lang'], '中文', 'EN'),
				$usergroups[$user['groupid']],
				'<select name="activateds[]"><option value="1">正常</option><option style="color:red;" value="0" ' . Iif($user['activated']==0, 'SELECTED', '') . '>已禁止</option><option style="color:red;" value="-1" ' . Iif($user['activated'] == -1, 'SELECTED', '') . '>未激活</option></select>',
				Iif($user['userid'] == $this->admin->data['userid'], $user['email'], '<a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a>'),
				$user['loginnum'],
				$user['q_num'],
				$user['a_num'],
				$user['p_num'],
				$user[pc_num] + $user[ac_num],
				DisplayDate($user['joindate']) . " ($user[joinip])",
				Iif($user['lastdate'] == 0, '<span class="red">从未登陆</span>', DisplayDate($user['lastdate'], '', 1)  . " ($user[lastip])"),
				Iif($user['userid'] != $this->admin->data['userid'], '<input type="checkbox" name="deleteuserids[]" value="' . $user['userid'] . '">')));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('users'), $totalpages, $page, 10, 'key', $letter, 's', urlencode($search), 'g', $groupid));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updateusers" value="保存更新" class="save"><input type="submit" name="deleteusers" onclick="'.Confirm('确定删除所选用户吗?<br><br><span class=red>注: 这里删除用户, 用户发表的信息不会被删除!</span>', 'form').'" value="删除用户" class="cancel"></div></form>';
	}

} 

?>