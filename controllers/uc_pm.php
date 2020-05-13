<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_pm extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');

		$this->assign('title', $this->langs['u_mypm'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_mypm'] => 'uc_pm'))); //分配导航栏
	}

	//删除收到的短信 不验证权限
    public function delete(){
		$deletepmids = $_POST['deletepmids'];
		$myid = $this->user->data['userid'];

		for($i = 0; $i < count($deletepmids); $i++){

			$pmid = ForceInt($deletepmids[$i]);

			//删除当前短信及回复
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' AND pmid = '$pmid') OR (refer_id = '$pmid' AND (toid = '$myid' OR fromid = '$myid'))");
		}

		Success('', 1); //只是输出成功信息

		$this->index(); //进入收件箱
	}

	//收件箱
    public function index(){
		$this->assign('submenu', UCSub($this->langs['u_mypm'], array(array($this->langs['u_pmbox'], 'uc_pm', 1),array($this->langs['u_sendbox'], 'uc_pm/sendbox'))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$data = '';
		$myid = $this->user->data['userid'];

		$getpms = APP::$DB->query("SELECT pmid, toid, toname, fromid, fromname, readed, newreply, subject, created FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' OR (fromid = '$myid' AND newreply = 1)) AND refer_id = 0 ORDER BY readed, newreply DESC, created DESC LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(pmid) AS value FROM " . TABLE_PREFIX . "pm WHERE (toid = '$myid' OR (fromid = '$myid' AND newreply = 1)) AND refer_id = 0");

		if($maxrows['value'] < 1){
			$data .= '<tr><td colspan="5"><BR><font class=orangeb>' . $this->langs['u_nopms'] . '</font><BR><BR></td></tr>';
		}else{
			$line2 = false;
			while($pm = APP::$DB->fetch($getpms)){
				$subject = ShortTitle($pm['subject'], 36);

				if($line2) {
					$lineclass = " class=tr2";
					$line2 = false;
				}else{
					$lineclass = '';
					$line2 = true;
				}

				if($pm['fromid'] == $myid){ //自己发送的短信有新回复
					$subject = "<font class=red>" . $this->langs['u_reply'] . ": $subject</font>";
					$status = "<font class=red>" . $this->langs['u_newreply'] . "</font>";

					$userinfo = '<img src="' . GetAvatar($pm['toid']) . '" class="user_avatar wh30">' . $pm['toname'] . '&nbsp;&nbsp;<a href="#" toid="'.$pm['toid'].'" toname="'.$pm['toname'].'" class="pm">';
				}else{//自己收到的短信
					if($pm['readed']){
						if($pm['newreply'] == 0){
							$status = "<font class=orange>" . $this->langs['u_unreply'] . "</font>";
						}else{
							$subject = "<font class=light>$subject</font>";
							$status = "<font class=light>" . $this->langs['u_replied'] . "</font>";
						}
					}else{
						$subject = "<font class=red>$subject</font>";
						$status = "<font class=red>" . $this->langs['u_unread'] . "</font>";
					}

					$userinfo = '<img src="' . GetAvatar($pm['fromid']) . '" class="user_avatar wh30">' . $pm['fromname'] . '&nbsp;&nbsp;<a href="#" toid="'.$pm['fromid'].'" toname="'.$pm['fromname'].'" class="pm">';
				}

				$data .= '<tr' . $lineclass . '>
				<td class="al"><a href="' . URL('uc_pm/reply?pmid=' . $pm['pmid']) . '" title="' . $this->langs['u_replypm'] . '"><img src="' . T_URL . 'images/reply.gif">&nbsp;&nbsp;' . $subject . '</a></td>
				<td>' . $status . '</td>
				<td class="al">' . $userinfo . '<img src="' . T_URL . 'images/pm.png" title="' . $this->langs['u_sendpm'] . '"></a></td>
				<td>' . DisplayDate($pm['created'], '', 1) . '</td>
				<td>' . Iif($pm['fromid'] == $myid, '&nbsp;', '<input type="checkbox" name="deletepmids[]" value="' . $pm['pmid'] . '" class="chbox">') . '</td>
				</tr>';
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
		}

		$this->assign('tbtitle', str_replace('//2', $this->user->data['pms'], str_replace('//1', $maxrows['value'], $this->langs['u_pmboxinfo'])));
		$this->assign('data', $data); //数据
		$this->assign('page', $page); //当前页号
		$this->assign('pagelist', GetPageList(URL('uc_pm'), $totalpages, $page, 10)); //分页

		$this->display('uc_pm.html');
	}

	//收件箱
    public function sendbox(){
		$this->assign('submenu', UCSub($this->langs['u_mypm'], array(array($this->langs['u_pmbox'], 'uc_pm'),array($this->langs['u_sendbox'], 'uc_pm/sendbox', 1))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$data = '';
		$myid = $this->user->data['userid'];

		$getpms = APP::$DB->query("SELECT pmid, toid, toname, fromid, fromname, readed, newreply, subject, created FROM " . TABLE_PREFIX . "pm WHERE fromid = '$myid' AND refer_id = 0 ORDER BY newreply DESC, created DESC LIMIT $start, $NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(pmid) AS value FROM " . TABLE_PREFIX . "pm WHERE fromid = '$myid' AND refer_id = 0");

		if($maxrows['value'] < 1){
			$data .= '<tr><td colspan="4"><BR><font class=orangeb>' . $this->langs['u_nosend'] . '</font><BR><BR></td></tr>';
		}else{
			$line2 = false;
			while($pm = APP::$DB->fetch($getpms)){
				$subject = ShortTitle($pm['subject'], 36);

				if($pm['newreply'] == 1){
					$subject = "<font class=red>$subject</font>";
					$status = "<font class=red>" . $this->langs['u_newreply'] . "</font>";
				}elseif($pm['newreply'] == '-1'){
					$subject = "<font class=light>$subject</font>";
					$status = "<font class=light>" . $this->langs['u_replied2'] . "</font>";
				}else{
					$status = "<font class=orange>" . $this->langs['u_unreply2'] . "</font>";
				}

				if($line2) {
					$lineclass = " class=tr2";
					$line2 = false;
				}else{
					$lineclass = '';
					$line2 = true;
				}

				$data .= '<tr' . $lineclass . '>
				<td class="al"><a href="' . URL('uc_pm/reply?pmid=' . $pm['pmid']) . '" title="' . $this->langs['u_sendmore'] . '"><img src="' . T_URL . 'images/reply.gif">&nbsp;&nbsp;' . $subject . '</a></td>
				<td>' . $status . '</td>
				<td class="al"><img src="' . GetAvatar($pm['toid']) . '" class="user_avatar wh30">' . $pm['toname'] . '&nbsp;&nbsp;<a href="#" toid="'.$pm['toid'].'" toname="'.$pm['toname'].'" class="pm"><img src="' . T_URL . 'images/pm.png" title="' . $this->langs['u_sendpm'] . '"></a></td>
				<td>' . DisplayDate($pm['created'], '', 1) . '</td>
				</tr>';
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
		}

		$this->assign('tbtitle', str_replace('//1', $maxrows['value'], $this->langs['u_sendboxinfo']));
		$this->assign('data', $data); //数据
		$this->assign('pagelist', GetPageList(URL('uc_pm/sendbox'), $totalpages, $page, 10)); //分页

		$this->display('uc_pm_sendbox.html');
	} 


	//回复短信
    public function reply(){
		$pmid = ForceIntFrom('pmid');
		$myid = $this->user->data['userid'];

		//只能回复自己收到或发送的短信
		if($pmid) $pm = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pmid' AND refer_id = 0 AND (toid = '$myid' OR fromid = '$myid')");

		if(!$pmid OR !$pm) Error($this->langs['er_replyerrorinfo'], $this->langs['er_replyerror']);

		$data = '';

		if($pm['toid'] == $myid){ //当此条短信收件人是自己时, 更新此短信为已读
			$this->assign('submenu', UCSub($this->langs['u_mypm'], array(array($this->langs['u_pmbox'], 'uc_pm'), array($this->langs['u_replypm'], 'uc_pm/reply?pmid='.$pmid, 1), array($this->langs['u_sendbox'], 'uc_pm/sendbox'))));
			
			$rtitle = $this->langs['u_replypm'];

			if(!$pm['readed']) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET readed = 1 WHERE pmid = '$pmid'");
			$userid = $pm['fromid'];
			$username = $pm['fromname'];

		}else{ //当此条短信发送人是自己, 而对方有新回复时, 更新newreply为-1(表示对方有回复且已读)
			$this->assign('submenu', UCSub($this->langs['u_mypm'], array(array($this->langs['u_pmbox'], 'uc_pm'), array($this->langs['u_sendbox'], 'uc_pm/sendbox'), array($this->langs['u_sendmore'], 'uc_pm/reply?pmid='.$pmid, 1))));

			$rtitle = $this->langs['u_sendmore'];

			if($pm['newreply'] == 1) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pm SET newreply = '-1' WHERE pmid = '$pmid'");
			$userid = $pm['toid'];
			$username = $pm['toname'];
		}

		$data .="<tr><td colspan='4' class='tdt greyb'><div>$rtitle:<i>$pm[subject]</i></div></td></tr>
					<tr>
					<th>" . $this->langs['u_sendtime'] . "</th>
					<th><img src='" . GetAvatar($userid) . "' class='user_avatar wh30'>$username &nbsp;<a href='#' toid='$userid' toname='$username' class='pm'><img src='" . T_URL . "images/pm.png' title='" . $this->langs['u_sendpm'] . "'></a></th>
					<th><img src='" . GetAvatar($this->user->data['userid']) . "' class='user_avatar wh30'>" . $this->user->data['nickname'] . " <font class=greyb>( " . $this->langs['u_me'] . " )</font></th>
					<th>" . $this->langs['u_sendtime'] . "</th>
					</tr>";

		$message = Iif($pm['message'], nl2br($pm['message']), '<font class=orange>' . $this->langs['u_onlytitle'] . '</font>');

		if($pm['toid'] == $myid){
			$data .="<tr>
					<td>" . DisplayDate($pm['created'], '', 1) . "</td>
					<td><div class=pm1>$message</div></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>";

		}else{
			$data .="<tr>
					<td><div style='width:100px;'>&nbsp;</div></td>
					<td><div style='width:220px;'>&nbsp;</div></td>
					<td><div class=pm2>$message</div></td>
					<td>" . DisplayDate($pm['created'], '', 1) . "</td>
					</tr>";
		}

		$getreplies = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "pm WHERE refer_id = '$pmid' ORDER BY created");
		while($reply = APP::$DB->fetch($getreplies)){
			if($reply['toid'] == $myid){
				$data .='<tr>
						<td>' . DisplayDate($reply['created'], '', 1) . '</td>
						<td><div class=pm1>' . nl2br($reply['message']) . '</div></td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						</tr>';
			}else{
				$data .='<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td><div class=pm2>' . nl2br($reply['message']) . '</div></td>
						<td>' . DisplayDate($reply['created'], '', 1) . '</td>
						</tr>';
			}
		}

		$data .='<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><input type="hidden" name="pmid" value="' . $pmid . '"><textarea name="message" style="height:80px;width:220px;"></textarea></td>
				<td><input type="submit" value="' . $rtitle . '" class="save"></td>
				</tr>';

		$this->assign('data', $data); //数据
		$this->display('uc_pm_reply.html');
	}

}

?>