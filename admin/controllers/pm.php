<?php if(!defined('ROOT')) die('Access denied.');

//短信的状态说明:
//readed主要用于收到的短信:   0未读          1已读
//newreply用于自己发的短信:   0对方未回复  1对方新回复   -1对方有回复我已读

class c_pm extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){

		$action = ForceStringFrom('action');

		if($action == 'search'){ //搜索用户
			$search = SafeSearchSql(ForceStringFrom('s'));

			if(!$search){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = '未输入搜索关键词或其中存在非法字符!';
			}else{
				if(preg_match("/^[1-9][0-9]*$/", $search)){
					$searchsql = " userid = '".ForceInt($search)."' "; //按ID搜索
				}else{
					$searchsql = " (username LIKE '%$search%' OR nickname LIKE '%$search%' OR email LIKE '%$search%') "; //按ID搜索
				}

				$users = APP::$DB->getAll("SELECT userid, lang, nickname FROM " . TABLE_PREFIX . "user WHERE activated = 1 AND userid <> '" . $this->admin->data['userid'] . "' AND $searchsql ORDER BY userid DESC LIMIT 20"); //限20个结果

				if(!empty($users)){
					$this->ajax['i'] = urlencode($search);

					//获得搜索到的每个用户的小头像
					foreach($users AS $key => $user){
						$filename =  GetUserImage($user['userid']);
						if(!file_exists(ROOT . $filename)) 	{
							$users[$key]['avatar'] = T_URL . "images/noavatar.gif";
						}else{
							$users[$key]['avatar'] = SYSDIR . $filename;
						}
					}

					$this->ajax['d'] = $users;
				}
			}
		}elseif($action == 'savepm'){ //回复短信
			$this->ajax['s'] = 0; //先将ajax设置为失败状态
			$pmid = ForceIntFrom('pmid');
			$message = ForceStringFrom('message');

			$fromid  = $this->admin->data['userid'];
			$fromname  = $this->admin->data['nickname'];

			if(strlen($message) == 0){
				$this->ajax['i'] = "回复或追加短信的内容不能为空!";
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
				$this->ajax['i'] = "您尝试回复或追加的短信不存在或已删除!";
			}

		}

		die($this->json->encode($this->ajax));
	}

	//删除收到的短信
    public function delete(){
		$deletepmids = $_POST['deletepmids'];
		$page = ForceIntFrom('p', 1);   //页码
		$myid = $this->admin->data['userid'];

		for($i = 0; $i < count($deletepmids); $i++){

			$pmid = ForceInt($deletepmids[$i]);

			//删除当前短信及回复
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' AND pmid = '$pmid') OR (refer_id = '$pmid' AND (toid = '$myid' OR fromid = '$myid'))");
		}

		Success('pm?p=' . $page); //返回到收件箱
	}

	//保存新短信(不是回复, 回复使用ajax完成)
    public function save(){
		$toid     = ForceIntFrom('toid');
		$subject  = ForceStringFrom('subject');
		$message = ForceStringFrom('message');

		$fromid  = $this->admin->data['userid'];
		$fromname  = $this->admin->data['nickname'];

		$errortitle = '发送短信错误';

		if($toid == $fromid){
			Error('请勿自己给自己发短信!', $errortitle);
		}elseif(strlen($subject) == 0){
			Error("短信标题不能为空!", $errortitle);
		}

		if($toid AND $touser = APP::$DB->getOne("SELECT userid, nickname FROM " . TABLE_PREFIX . "user WHERE userid = '$toid' AND activated = 1")){
			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "pm (toid, toname, fromid, fromname, subject, message, created) VALUES ('$toid', '$touser[nickname]', '$fromid', '$fromname', '$subject', '$message', '".time()."')");

			Success('pm/sendbox'); //发送新短信返回到收件箱
		}else{
			Error('用户不存在或已删除!', $errortitle);
		}
	}


	//发送短信表单
    public function send(){
		$userid = ForceIntFrom('userid');

		$lang_note = '请先搜索用户!';
 
		if($userid){
			SubMenu('我的短信', array(array('&nbsp;收件箱&nbsp;', 'pm'), array('&nbsp;发件箱&nbsp;', 'pm/sendbox'), array('发送短信', 'pm/send?userid='.$userid, 1)));
			$touser = APP::$DB->getOne("SELECT userid, lang, nickname FROM " . TABLE_PREFIX . "user WHERE userid = '$userid' AND activated = 1");

			if(!$touser) Error('用户不存在, 已禁止或未激活!', '发送短信错误');
			if($touser['userid'] == $this->admin->data['userid']) Error('请勿自己给自己发短信!', '发送短信错误');

			$lang_note = '<img src="' . GetAvatar($userid) . '" class="user_avatar wh30">' . Iif($touser['lang'], '中文用户', '<font class=redb>英文用户</font>');
		}else{
			$touser = array('userid' => 0, 'nickname' => '');
		}


		echo '<form method="post" action="'.BURL('pm/save').'">
		<input type="hidden" name="toid" id="to_id" value="' . $touser['userid'] . '">';

		TableHeader('发送短信');

		TableRow(array('<B>发送给(昵称):</B>', '<input type="text" id="to_username" Disabled style="width:120px;" value="' . $touser['nickname'] . '"><label style="padding-left:6px;width:176px;display:inline-block;" class="greyb" id="to_username_note">' . $lang_note . '</label><label>ID, 用户名, 昵称或Email: </label><input type="text" id="search_key" style="width:120px;" autocomplete="off">&nbsp;&nbsp;<input type="button" value="搜索用户" class="cancel" id="search_button"><div id="users_div" class="users_div" style="display:none;"></div>'));
		TableRow(array('<B>短信标题:</B>', '<input type="text" name="subject" style="width:292px;" value="">&nbsp;&nbsp;<font class=red>* 必填项</font>&nbsp;&nbsp;<span class=light>注: 请根据用户的语言发送恰当的短信内容?</span>'));
		TableRow(array('<B>短信内容:</B>', '<textarea name="message" style="height:80px;width:400px;"></textarea>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="submit_pm" value="发送短信" ' . Iif($userid, ' class="save"', ' class="cancel" disabled') . '>'));
		TableFooter();

		echo '</form>
		<script type="text/javascript">
			$(function(){
				//搜索框直接回车
				$("#search_key").keydown(function(e){
					$("#users_div").hide();

					if(e.which==13){
						$("#search_button").click();
						e.preventDefault();
					}
				});

				$("#search_button").click(function(e){
					var search =$.trim($("#search_key").val());
					if(search.length == 0){
						$.dialog({title:"搜索用户",lock:true,content:"<font color=red>请输入用户ID, 用户名, 昵称或Email搜索!</font>",okValue:"  确定  ",
						ok:true, time:5000});
					}else{
						ajax("' . BURL('pm/ajax?action=search') . '", {s: search}, function(data){
							var users_div = $("#users_div");
							users_div.hide();

							var users = data.d;

							if(users.length == 0){
								users_div.html("<font class=red>未找到用户!</font>");
							}else{
								var user_html = "";
								for(var i=0; i<users.length; i++){
									user_html += "<li><a href=\"#\" username=\"" + users[i].nickname + "\" userid=\"" + users[i].userid + "\" lang=\"" + users[i].lang + "\"><img src=\""+users[i].avatar+"\" class=\"user_avatar wh30\">" + users[i].nickname + "</a></li>";
								}

								if(users.length > 19){
									user_html += "<dl><a href=\"' . BURL('users?s=') . '" + data.i + "\" target=\"_blank\"><font class=red>更多用户 ...</font></a></dl>";
								}else{
									user_html += "<li class=last></li>";
								}

								users_div.html(user_html);

								users_div.find("li a").click(function(e){
									var _me=$(this);
									$("#to_id").val(_me.attr("userid"));
									$("#to_username").val(_me.attr("username"));
									$("#to_username_note").html("<img src=\""+ _me.children("img").attr("src") + "\" class=\"user_avatar wh30\">" + (_me.attr("lang") == 1 ? "中文用户" : "<font class=redb>英文用户</font>"));
									users_div.hide();
									$("#submit_pm").removeAttr("disabled").addClass("save").removeClass("cancel");;
									e.preventDefault();
								});
							}
							users_div.slideDown(200);
						});
					}
					e.preventDefault();
				});
			});
		</script>';
	}

	//回复短信
    public function reply(){
		$pmid = ForceIntFrom('pmid');
		$myid = $this->admin->data['userid'];

		//只能回复自己收到或发送的短信
		$pm = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pmid' AND refer_id = 0 AND (toid = '$myid' OR fromid = '$myid')");

		if(!$pm) Error('您尝试回复或追加的短信不存在!', '回复或追加短信错误');

		if($pm['toid'] == $myid){ //当此条短信收件人是自己时, 更新此短信为已读
			SubMenu('我的短信', array(array('&nbsp;收件箱&nbsp;', 'pm'), array('回复短信', 'pm/reply?pmid='.$pmid, 1), array('&nbsp;发件箱&nbsp;', 'pm/sendbox')));
			
			$rtitle = '回复';

			if(!$pm['readed']) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET readed = 1 WHERE pmid = '$pmid'");
			$userid = $pm['fromid'];
			$username = $pm['fromname'];

		}else{ //当此条短信发送人是自己, 而对方有新回复时, 更新newreply为-1(表示对方有回复且已读)
			SubMenu('我的短信', array(array('&nbsp;收件箱&nbsp;', 'pm'), array('&nbsp;发件箱&nbsp;', 'pm/sendbox'), array('追加短信', 'pm/reply?pmid='.$pmid, 1)));

			$rtitle = '追加';

			if($pm['newreply'] == 1) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET newreply = '-1' WHERE pmid = '$pmid'");
			$userid = $pm['toid'];
			$username = $pm['toname'];
		}

		echo '<form id="u_reply_form" onsubmit="return false;">';
		TableHeader($rtitle ."短信: <span class=note>$pm[subject]</span>");

		TableRow(array('发送时间', '<a href="'. BURL('users/edit?userid='.$userid) .'" title="编辑用户"><img src="' . GetAvatar($userid) . '" class="user_avatar wh30">' . $username. '</a>&nbsp;&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$userid).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送新短信"></a>',  '<img src="' . GetAvatar($this->admin->data['userid']) . '" class="user_avatar wh30">' . $this->admin->data['nickname'] . ' (我)', '发送时间'), 'tr0');

		$message = Iif($pm['message'], nl2br($pm['message']), '<font class=orange>注: 这是一条仅有标题的短信!</font>');

		if($pm['toid'] == $myid){
			TableRow(array(DisplayDate($pm['created'], '', 1), "<div class=message1>$message</div>", '&nbsp;', '&nbsp;'));
		}else{
			TableRow(array('<div style="width:100px;">&nbsp;</div>', '<div style="width:300px;">&nbsp;</div>', "<div class=message2>$message</div>", DisplayDate($pm['created'], '', 1)));
		}

		$getreplies = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "pm WHERE refer_id = '$pmid' ORDER BY created");
		while($reply = APP::$DB->fetch($getreplies)){
			if($reply['toid'] == $myid){
				TableRow(array(DisplayDate($reply['created'], '', 1), '<div class=message1>' . nl2br($reply['message']) . '</div>', '&nbsp;', '&nbsp;'));
			}else{
				TableRow(array('&nbsp;', '&nbsp;', '<div class=message2>' . nl2br($reply['message']) . '</div>', DisplayDate($reply['created'], '', 1)));
			}
		}

		TableRow(array('&nbsp;', '&nbsp;', '<input type="hidden" name="pmid" value="' . $pmid . '"><textarea name="message" style="height:80px;width:300px;"></textarea>', '<input type="submit" value="' . $rtitle . '短信" class="save">'));

		TableFooter();

		echo '</form><script>
		$(function(){
			var zzz = 1;

			$("#u_reply_form input.save").click(function(e){
				var form = $("#u_reply_form");
				var msg = $("#u_reply_form textarea[name=\"message\"]");
				if(!$.trim(msg.val())){
					shake(msg, "shake", 3);
					msg.val("").focus();
					return;
				}

				var me = $(this);
				me.attr("disabled", true);
				ajax("' . BURL('pm/ajax?action=savepm') . '", form.serialize(), function(data){
					var pm = msg.val().replace(/\r\n|\r|\n/g, "<br>");
					msg.val("");
					zzz += 1;
					var id = "new888" + zzz;

					$.dialog({lock:true,title:"' . $rtitle .'短信",content:"<font color=blue>' . $rtitle .'短信发送成功!</font>",okValue:"  确定  ",ok:true,time:1000,beforeunload:function(){
						me.parent().parent().before("<tr id=\"" + id + "\"><td class=\"td\">&nbsp;</td><td class=\"td\">&nbsp;</td><td class=\"td\"><div class=message2>" + pm + "</div></td><td class=\"td last\">" + data.i + "</td></tr><tr>");

						shake($("#" + id), "shake2", 6);
						me.removeAttr("disabled");
					}});
				});

				e.preventDefault();
			});
		});
		</script>';
	}

	//收件箱
    public function sendbox(){
		SubMenu('我的短信', array(array('&nbsp;收件箱&nbsp;', 'pm'),array('&nbsp;发件箱&nbsp;', 'pm/sendbox', 1)));

		$this->send(); //调用发送框

		BR(1);

		$NumPerPage =10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$myid = $this->admin->data['userid'];

		$getpms = APP::$DB->query("SELECT pmid, toid, toname, fromid, fromname, readed, newreply, subject, created FROM " . TABLE_PREFIX . "pm WHERE fromid = '$myid' AND refer_id = 0 ORDER BY newreply DESC, created DESC LIMIT $start, $NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(pmid) AS value FROM " . TABLE_PREFIX . "pm WHERE fromid = '$myid' AND refer_id = 0");

		TableHeader('已发送的短信列表('.$maxrows['value'].'条)');

		TableRow(array('标题 (追加)', '状态', '收信人', '发送时间'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>暂未发送任何短信或接收方已删除!</font><BR><BR></center>');
		}else{
			while($pm = APP::$DB->fetch($getpms)){
				$subject = ShortTitle($pm['subject'], 48);

				if($pm['newreply'] == 1){
					$subject = "<font class=red>$subject</font>";
					$status = "<font class=red>有新回复</font>";
				}elseif($pm['newreply'] == '-1'){
					$subject = "<font class=light>$subject</font>";
					$status = "<font class=light>对方已回复</font>";
				}else{
					$status = "<font class=orange>对方未回复</font>";
				}

				TableRow(array('<a href="' . BURL('pm/reply?pmid=' . $pm['pmid']) . '" title="追加短信"><img src="' . SYSDIR . 'public/admin/images/reply.gif">&nbsp;&nbsp;&nbsp;' . $subject . '</a>',
				$status,
				'<a href="'. BURL('users/edit?userid='.$pm['toid']) .'" title="编辑用户"><img src="' . GetAvatar($pm['toid']) . '" class="user_avatar wh30">' . $pm['toname'] . '</a>&nbsp;&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$pm['toid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送新短信"></a>',
				DisplayDate($pm['created'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('pm/sendbox'), $totalpages, $page, 10));
			}
		}

		TableFooter();
	} 

	//发件箱
	public function index(){
		SubMenu('我的短信', array(array('&nbsp;收件箱&nbsp;', 'pm', 1),array('&nbsp;发件箱&nbsp;', 'pm/sendbox')));

		$this->send(); //调用发送框

		BR(1);

		$NumPerPage =10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$myid = $this->admin->data['userid'];

		$getpms = APP::$DB->query("SELECT pmid, toid, toname, fromid, fromname, readed, newreply, subject, created FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' OR (fromid = '$myid' AND newreply = 1)) AND refer_id = 0 ORDER BY readed, newreply DESC, created DESC LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(pmid) AS value FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' OR (fromid = '$myid' AND newreply = 1)) AND refer_id = 0");

		echo '<form method="post" action="'.BURL('pm/delete').'" name="pmsform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader('已收短信列表('.$maxrows['value'].'条)');

		TableRow(array('标题 (回复)', '状态',	'来自', '发送时间', '<input type="checkbox" id="checkAll" for="deletepmids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>暂未收到任何短信或已删除!</font><BR><BR></center>');
		}else{
			while($pm = APP::$DB->fetch($getpms)){
				$subject = ShortTitle($pm['subject'], 48);

				if($pm['fromid'] == $myid){ //自己发送的短信有新回复
					$subject = "<font class=red>回复: $subject</font>";
					$status = "<font class=red>有新回复</font>";

					$userinfo = '<a href="'. BURL('users/edit?userid='.$pm['toid']) .'" title="编辑用户"><img src="' . GetAvatar($pm['toid']) . '" class="user_avatar wh30">' . $pm['toname'] . '</a>&nbsp;&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$pm['toid']).'">';
				}else{//自己收到的短信
					if($pm['readed']){
						if($pm['newreply'] == 0){
							$status = "<font class=orange>未回复</font>";
						}else{
							$subject = "<font class=light>$subject</font>";
							$status = "<font class=light>已回复</font>";
						}
					}else{
						$subject = "<font class=red>$subject</font>";
						$status = "<font class=red>未读</font>";
					}

					$userinfo = '<a href="'. BURL('users/edit?userid='.$pm['fromid']) .'" title="编辑用户"><img src="' . GetAvatar($pm['fromid']) . '" class="user_avatar wh30">' . $pm['fromname'] . '</a>&nbsp;&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$pm['fromid']).'">';
				}

				TableRow(array('<a href="' . BURL('pm/reply?pmid=' . $pm['pmid']) . '" title="回复短信"><img src="' . SYSDIR . 'public/admin/images/reply.gif">&nbsp;&nbsp;&nbsp;' . $subject . '</a>',
				$status,
				$userinfo . '<img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送新短信"></a>',
				DisplayDate($pm['created'], '', 1),
				Iif($pm['fromid'] == $myid, '&nbsp;', '<input type="checkbox" name="deletepmids[]" value="' . $pm['pmid'] . '">')));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('pm'), $totalpages, $page, 10));
			}
		}

		TableFooter();
		echo '<div class="submit"><input type="submit" name="deletepms" onclick="'.Confirm('<font class=red>确定删除所选已收短信吗?</font>', 'form').'" value="删除短信" class="save"></div></form>';
	} 

} 

?>