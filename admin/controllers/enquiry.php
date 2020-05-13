<?php if(!defined('ROOT')) die('Access denied.');

class c_enquiry extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	public function index(){
		SubMenu('询价管理', array(array('询价列表', 'enquiry', 1)));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$search = ForceStringFrom('s');   //搜索的内容
		$type = ForceStringFrom('type');   //搜索的内容
		$time = ForceStringFrom('t');
		$cat_id = ForceStringFrom('c');   //按状态

		if(IsGet('s')){
			$search = urldecode($search);
		}

		$Where = $this->GetSearchSql($search, $type, $time, $cat_id);

		$getenquiry = APP::$DB->query("SELECT e_id, refer_id, status, email, pro_id, userid, username, lang, title, created FROM " . TABLE_PREFIX . "enquiry WHERE refer_id =0 " . $Where . " ORDER BY status, e_id DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(e_id) AS value FROM " . TABLE_PREFIX . "enquiry WHERE refer_id =0 " . $Where);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<form method="post" action="'.BURL('enquiry').'" name="searchform">';

		TableHeader('搜索询价');
		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="12" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>提交时间:</label>&nbsp;<select name="type"><option value="gr" ' . Iif($type == 'gr', 'SELECTED') . '>大于(>)</option><option value="eq" ' . Iif($type == 'eq', 'SELECTED') . '>等于(=)</option><option value="le" ' . Iif($type == 'le', 'SELECTED') . '>小于(<)</option></select> <input type="text" name="t" onClick="WdatePicker()" value="' . $time . '" size="12">&nbsp;&nbsp;&nbsp;<label>状态或语言:</label>&nbsp;<select name="c"><option value="0">全部询价</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>未回复的询价</option><option style="color:red;" value="-2" ' . Iif($cat_id == '-2', 'SELECTED') . '>已回复的询价</option><option class="greyb" value="re" ' . Iif($cat_id == 're', 'SELECTED') . '>会员的询价</option><option class="greyb" value="gu" ' . Iif($cat_id == 'gu', 'SELECTED') . '>游客的询价</option><option class="blue" value="cn" ' . Iif($cat_id == 'cn', 'SELECTED') . '>中文 (语言)</option><option class="blue" value="en" ' . Iif($cat_id == 'en', 'SELECTED') . '>EN (语言)</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="search" value="搜索询价" class="cancel"></center>');
		TableFooter();
		echo '</form>
		<form method="post" action="'.BURL('enquiry/delete').'" name="listform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader(Iif($Where, '搜索到的询价列表', '全部询价列表') . '(' . $maxrows['value'] . '个)');

		TableRow(array('标题 (回复)', '状态',	'用户 (昵称)', '语言', 'Email', '提交时间', '产品', '<input type="checkbox" id="checkAll" for="deleteids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何询价或已删除!</font><BR><BR></center>');
		}else{
			while($eq = APP::$DB->fetch($getenquiry)){
				$title = ShortTitle($eq['title'], 24);

				if($eq['status'] == 0){
					$title = "<font class=red>$title</font>";
					$status = "<font class=red>未回复</font>";
				}elseif($eq['status'] == 1){
					$status = "<font class=orange>已回复, 对方未读</font>";
				}else{
					$title = "<font class=light>$title</font>";
					$status = "<font class=light>" . Iif($eq['userid'], '已回复, 对方已读', '已回复') . "</font>";
				}

				TableRow(array('<a href="' . BURL('enquiry/reply?e_id=' . $eq['e_id']) . '">' . $title . '</a>',
				$status,
				Iif($eq['userid'], '<a title="编辑" href="'.BURL('users/edit?userid='.$eq['userid']).'"><img src="' . GetAvatar($eq['userid']) . '" class="user_avatar wh30">'.$eq['username'] . '</a>&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$eq['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>', "<font class=grey>[ 游客 ]</font> $eq[username]"),
				Iif($eq['lang'], '中文', 'EN'),
				"<a href=\"mailto:$eq[email]\">$eq[email]</a>",
				DisplayDate($eq['created'], '', 1),
				'<a href="' . URL('products?id=' . $eq['pro_id']) . '" target="_blank" title="浏览产品 (ID: ' . $eq['pro_id'] . ')"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>',
				'<input type="hidden" name="userids[]" value="'.$eq['userid'].'"><input type="checkbox" name="deleteids[]" value="' . $eq['e_id'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('enquiry'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id, 't', $time, 'type', $type));
			}
		}

		TableFooter();
		echo '<div class="submit"><input type="submit" name="deletes" onclick="'.Confirm('<font class=red>确定删除所选已收询价吗?<BR><BR>注: 所选询价的回复也将同时被删除.</font>', 'form').'" value="删除询价" class="save"></div></form>';
	} 

	//$search 查询的关键字 $type时间条件 大于小于什么的  $time时间 $cat_id状态或分类id等
	private function GetSearchSql($search, $type, $time, $cat_id){
		$Where = "";

		if($cat_id == '-1'){
			$Where .= " status=0 "; //未回复
		}elseif($cat_id == '-2'){
			$Where .= " status !=0 "; //1表示已回复, 2表示已回复且对方已读
		}elseif($cat_id == 'cn'){
			$Where .= " lang=1 "; //中文
		}elseif($cat_id == 'en'){
			$Where .= " lang=0 "; //英文
		}elseif($cat_id == 're'){
			$Where .= " userid<>0 "; //注册用户的询价
		}elseif($cat_id == 'gu'){
			$Where .= " userid=0 "; //游客用户的询价
		}

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " (e_id = '$search' OR pro_id = '$search' OR userid = '$search') "; //是数字时按询价, 产品或用户ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (email like '%$search%' OR username like '%$search%' OR title like '%$search%' OR content like '%$search%') ";
		}

		if($time){
			$timearr=explode('-',$time);
			$year=$timearr[0];
			$month=$timearr[1];
			$day=$timearr[2];
			$bigtime=mktime(0,0,0,$month,$day+1,$year);
			$littletime=mktime(0,0,0,$month,$day,$year);
			if($type=='eq'){
				$twhere = " created >= $littletime AND created < $bigtime ";
			}elseif($type=='gr'){
				$twhere = " created >= $bigtime ";
			}elseif($type=='le'){
				$twhere = " created < $littletime ";
			}

			$Where .= Iif($Where, " AND ") . $twhere;
		}

		$Where = Iif($Where, " AND " . $Where);

		return $Where;
	}

	//批量删除询价
	public function delete(){
		$this->CheckAction('enquiry'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		$e_ids = $_POST['deleteids'];
		$userids = $_POST['userids'];
		for($i=0; $i<count($e_ids); $i++){
			$e_id = ForceInt($e_ids[$i]);

			//删除询价及所有回复
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."enquiry where e_id = '$e_id' OR refer_id = '$e_id'");

			//可能是游客
			$userid = ForceInt($userids[$i]);
			if($userid) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET q_num = (q_num-1) WHERE userid = '$userid'");
		}

		Success('enquiry?p=' . $page);
	}

	//回复询价
    public function reply(){
		$this->CheckAction('enquiry'); //权限验证

		$e_id = ForceIntFrom('e_id');
		SubMenu('询价管理', array(array('询价列表', 'enquiry'), array('回复询价', 'enquiry/reply?e_id=' . $e_id, 1)));
		$eq = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "enquiry WHERE e_id = '$e_id' AND refer_id = 0");

		if(!$eq) Error('您尝试回复的询价不存在!', '回复询价错误');

		//查询产品
		$product = APP::$DB->getOne("SELECT pro_id, is_show, userid, username, path, filename, price, price_en, title, title_en, clicks, created FROM " . TABLE_PREFIX . "product WHERE pro_id = '$eq[pro_id]'");

		TableHeader('询价产品');

		if(!$product){ //产品不存在时显示
			TableRow('<div class="redb center">询价的产品不存在或已删除! 您仍可以对此询价进行回复.</div>');
		}else{
			TableRow(array('ID', '缩图', '产品标题(编辑)', '状态', '点击', '价格(中)', '价格(英)', '作者(昵称)', '日期', '浏览'), 'tr0');

			$title = ShortTitle($product['title'], 28);
			$status = '发布';

			if($product['is_show'] =='0'){
				$title = "<font class=red><s>$title</s></font>";
				$status = "<font class=red>禁用</font>";
			}elseif($product['is_show'] =='-1'){
				$title = "<font class=red>$title</font>";
				$status = "<font class=red>待审</font>";
			}

			TableRow(array($product['pro_id'],
				'<a href="'.BURL('products/edit?pro_id='.$product['pro_id']).'"><img src="'. GetImageURL($product['path'], $product['filename']).'" width="40" class="ZoomImg"></a>',
				'<a href="'.BURL('products/edit?pro_id='.$product['pro_id']).'" title="英文: '.$product['title_en'].'">' . $title . '</a>',
				$status,
				$product['clicks'],
				$product['price'],
				$product['price_en'],
				'<a title="编辑" href="'.BURL('users/edit?userid='.$product['userid']).'"><img src="' . GetAvatar($product['userid']) . '" class="user_avatar wh30">' . $product['username'] . '</a>&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$product['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>',
				DisplayDate($product['created']),
				Iif($product['is_show'] == '1', '<a href="'.URL('products?id=' . $product['pro_id']).'" target="_blank" title="浏览此产品"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>', '<img src="' . SYSDIR . 'public/admin/images/disview.gif">')));
		}

		TableFooter();

		echo '<form method="post" action="'.BURL('enquiry/save').'">';
		TableHeader('回复询价: <span class=note>' . $eq['title'] . '</span>');

		TableRow(array('提交时间', Iif($eq['userid'], '<a href="'. BURL("users/edit?userid=$eq[userid]") .'" title="编辑用户"><img src="' . GetAvatar($eq['userid']) . '" class="user_avatar wh30">' .$eq['username']. '</a>&nbsp;&nbsp;<a href="'.BURL("pm/send?userid=$eq[userid]").'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送新短信"></a>', "<font class=grey>[ 游客 ]</font> $eq[username]") . "&nbsp;&nbsp;&nbsp;<font class='light normal'>(Email: $eq[email])</font>", '回复人', '回复内容', '回复时间'), 'tr0');

		$message = Iif($eq['content'], nl2br($eq['content']), '<font class=orange>注: 这是一条仅有标题的询价!</font>');
		TableRow(array(DisplayDate($eq['created'], '', 1), "<div class=message1>$message</div>", '&nbsp;', '&nbsp;', '&nbsp;'));

		$getreplies = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "enquiry WHERE refer_id = '$e_id' ORDER BY e_id");
		while($reply = APP::$DB->fetch($getreplies)){
			if($reply['status']){ //对于回复的status: 0表示询价发起者; 1表示管理员的回复
				TableRow(array('&nbsp;', '&nbsp;', Iif($reply['userid'] == $this->admin->data['userid'], "<font class=grey><img src='" . GetAvatar($reply['userid']) . "' class='user_avatar wh30'>$reply[username]</font>", '<a href="'. BURL("users/edit?userid=$reply[userid]") . '" title="编辑用户"><img src="' . GetAvatar($reply['userid']) . '" class="user_avatar wh30">' .$reply['username'].  '</a>&nbsp;&nbsp;<a href="'.BURL("pm/send?userid=$reply[userid]").'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送新短信"></a>'), '<div class=message2>' . nl2br($reply['content']) . '</div>', DisplayDate($reply['created'], '', 1)));
			}else{
				TableRow(array(DisplayDate($reply['created'], '', 1), '<div class=message1>' . nl2br($reply['content']) . '</div>', '&nbsp;', '&nbsp;', '&nbsp;'));
			}
		}

		TableRow(array('&nbsp;', '&nbsp;', '<font class=greyb><img src="' . GetAvatar($this->admin->data['userid']) . '" class="user_avatar wh30">' .$this->admin->data['nickname'] . '</font>', '<input type="hidden" name="e_id" value="' . $e_id . '"><input type="checkbox" name="sendmail" value="1" checked' . Iif(!$eq['userid'], ' Disabled') . '> <b>邮件通知:</b> <span class=light>是否将同时回复内容发送Email通知对方?<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;注: Email内容中将自动附加以上产品信息.</span><BR><textarea name="content" style="height:80px;width:300px;margin-top:6px;"></textarea>', '<input type="submit" value="回复询价" class="save">'));

		TableFooter();
		echo '</form><a name="b"></a>'; //<a name="b"></a>锚点

		//输出小图片放大镜效果的JS
		PrintZoomJS();
	}

	//保存回复
    public function save(){
		$this->CheckAction('enquiry'); //权限验证

		@set_time_limit(0); //发送邮件时可能运行时间较长, 解除运行时间的限制

		$e_id = ForceIntFrom('e_id');
		$sendmail = ForceIntFrom('sendmail');
		$content = ForceStringFrom('content');

		$userid  = $this->admin->data['userid'];
		$username  = $this->admin->data['nickname'];

		if(!$content) Error('回复内容不能为空!', '回复询价错误');

		$eq = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "enquiry WHERE e_id = '$e_id' AND refer_id = 0");

		if(!$eq) Error('您正在尝试回复的询价不存在或已删除', '回复询价错误');

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "enquiry (refer_id, status, userid, username, content, created) VALUES ('$e_id', 1, '$userid', '$username', '$content', '".time()."')");

		//询价设置成有新回复
		$status = Iif($eq['userid'], 1, 2); //如果是游客的询价, 设置成已回复已读
		if($eq['status'] != 1) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "enquiry SET status = '$status' WHERE e_id = '$e_id'");

		//回复游客的询价或要求发送Email时, 发送Email通知, 需要区分中英文
		if(!$eq['userid'] OR $sendmail){

			$email = $eq['email']; //询价人的Email地址

			$product = APP::$DB->getOne("SELECT pro_id, is_show, path, filename, title, title_en FROM " . TABLE_PREFIX . "product WHERE pro_id = '$eq[pro_id]'");

			//附加上产品信息等
			if($eq['lang']){ //中文用户
				$subject = '您的询价有新回复 -- ' . APP::$_CFG['siteCopyright'];
				$content = "$eq[username]:<br>您好! 感谢您对我们的产品进行询价!<br><br>";

				if(!$product OR $product['is_show'] != 1){
					$content .= "<font color=red><b>您询价的产品不存在或已下架!</b></font><br><br>";
				}else{
					$content .= '您询价的产品是: <a href="' . BASEURL . Iif(!SITEREWRITE, 'index.php/') .  'products?id=' . $product['pro_id'] . '" title="' . $product['title'] . '" target="_blank"><img src="' . BASEURL . "uploads/$product[path]/$product[filename]" . '_s.jpg" border="0" align="absmiddle"></a><br><br>';
				}

				$fromtitle = '您询价的内容(';
			}else{ //English用户
				$subject = 'New Reply for Your Enquiry -- ' . APP::$_CFG['siteCopyrightEn'];
				$content = "Dear $eq[username]:<br>Thanks for your enquiry for our products.<br><br>";

				if(!$product OR $product['is_show'] != 1){
					$content .= "<font color=red><b>The product you inquired not exist or not on sale!</b></font><br><br>";
				}else{
					$content .= 'The product you inquired is: <a href="' . BASEURL . Iif(!SITEREWRITE, 'index.php/') .  'products?id=' . $product['pro_id'] . '" title="' . $product['title_en'] . '"><img src="' . BASEURL . "uploads/$product[path]/$product[filename]" . '_s.jpg" border="0" align="absmiddle"></a><br><br>';
				}

				$fromtitle = 'Your Enquiry (';
			}

			//附加上询价内容
			$content .= $fromtitle . DisplayDate($eq['created'], '', 1) . '):<br><div style="background-color:#f7ffff;border:1px solid #969696;border-radius: 4px 4px 4px 4px;line-height:20px;padding:6px;width:520px;">' . $eq['title'] . '<br>' . nl2br($eq['content']) . '</div><br>';

			//附加上回复记录
			$getreplies = APP::$DB->query("SELECT status, content, username, created FROM " . TABLE_PREFIX . "enquiry WHERE refer_id = '$e_id' ORDER BY e_id");
			while($reply = APP::$DB->fetch($getreplies)){
				if($reply['status']){ //对于回复的status: 1表示管理员的回复

					$content .= 'Reply from ' . $reply['username'] . ' (' . DisplayDate($reply['created'], '', 1) . '):<br><div style="background-color:#feeacf;border:1px solid #cc9900;border-radius: 4px 4px 4px 4px;line-height:20px;padding:6px;width:520px;">' . nl2br($reply['content']) . '</div>';
				}else{ //status: 0表示询价发起者的追加询价或回复

					$content .= $fromtitle . DisplayDate($reply['created'], '', 1) . '):<br><div style="background-color:#f7ffff;border:1px solid #969696;border-radius: 4px 4px 4px 4px;line-height:20px;padding:6px;width:520px;">' . nl2br($reply['content']) . '</div>';
				}
			}

			//邮件发送不成功时显示一条特殊信息
			if(SendMail($email, $subject, $content, $eq['lang']) !== true){
				echo '<script>$.dialog({lock:true,title:"回复询价错误",content:"<span class=blue>您回复的内容已保存.</span><br><br><span class=redb>但是通知对方的邮件发送失败!</span>",okValue:"  确定  ",ok:true,beforeunload:function(){document.location="' . BURL('enquiry') . '";}});</script>';

				exit(); //不运行下面的跳转代码
			}

		}

		Success("enquiry/reply?e_id=$e_id#b"); //返回到回复表单, b是页面底部的锚点
	}

} 

?>