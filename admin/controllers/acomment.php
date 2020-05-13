<?php if(!defined('ROOT')) die('Access denied.');

class c_acomment extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	//ajax保存评论
    public function ajax(){
		//ajax权限验证
		if(!$this->CheckAccess('acomment')){
			$this->ajax['s'] = 0; //ajax操作失败
			$this->ajax['i'] = '您没有权限管理文章评论!';
			die($this->json->encode($this->ajax));
		}

		$c_id = ForceIntFrom('c_id');
		$userid = ForceIntFrom('userid');
		$actived = ForceIntFrom('actived');
		$content = ForceStringFrom('content');
		$deletethiscomm = ForceIntFrom('deletethiscomm');

		if($deletethiscomm){//删除评论
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE c_id = '$c_id'");

			//可能是游客
			if($userid) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET ac_num = (ac_num-1) WHERE userid = '$userid'");
		}else{//保存评论
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "comment SET actived = '$actived', content='$content' WHERE c_id = '$c_id'");

			if(APP::$DB->geterror()){//上一条语言中数据操作有错误
				$this->ajax['s'] = 0; //ajax操作失败
				$this->ajax['i'] = '保存评论时数据库写入发生错误!';
			}else{
				$this->ajax['i'] = ShortTitle($content, 72); //将评论内容截取后返回, 用于ajax操作成功后更新
			}
		}

		die($this->json->encode($this->ajax));
	}

	public function index(){
		$NumPerPage = 10;   //每页显示的评论列表的数量
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');   //搜索的内容
		$type = ForceStringFrom('type');
		$time = ForceStringFrom('t');

		$cat_id = ForceStringFrom('c');   //按状态

		if(IsGet('s')) $search = urldecode($search);

		$start = $NumPerPage * ($page-1);  //分页的每页起始位置
		$Where = $this->GetSearchSql($search, $type, $time, $cat_id);

		if($Where){
			SubMenu('文章评论管理', array(array('全部文章评论', 'acomment')));
		}else{
			SubMenu('文章评论管理', array(array('文章评论列表', 'acomment', 1)));
		}

		echo '<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<form method="post" action="'.BURL('acomment').'" name="searchform">';

		TableHeader('搜索评论');

		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="12" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>发表时间:</label>&nbsp;<select name="type"><option value="gr" ' . Iif($type == 'gr', 'SELECTED') . '>大于(>)</option><option value="eq" ' . Iif($type == 'eq', 'SELECTED') . '>等于(=)</option><option value="le" ' . Iif($type == 'le', 'SELECTED') . '>小于(<)</option></select> <input type="text" name="t" onClick="WdatePicker()" value="' . $time . '" size="12">&nbsp;&nbsp;&nbsp;<label>状态或语言:</label>&nbsp;<select name="c"><option value="0">全部评论</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>待审中的评论</option><option style="color:red;" value="-2" ' . Iif($cat_id == '-2', 'SELECTED') . '>已禁用的评论</option><option class="greyb" value="re" ' . Iif($cat_id == 're', 'SELECTED') . '>会员的评论</option><option class="greyb" value="gu" ' . Iif($cat_id == 'gu', 'SELECTED') . '>游客的评论</option><option class="blue" value="cn" ' . Iif($cat_id == 'cn', 'SELECTED') . '>中文 (语言)</option><option class="blue" value="en" ' . Iif($cat_id == 'en', 'SELECTED') . '>EN (语言)</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="searcharticle" value="搜索评论" class="cancel"></center>');

		TableFooter();
		echo '</form>';

		$getcomms = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "comment WHERE type = 0 " . $Where . " ORDER BY actived ASC, created DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(c_id) AS value FROM " . TABLE_PREFIX . "comment WHERE type = 0 " . $Where);

		echo '<form method="post" action="'.BURL('acomment/updatecomms').'" name="commsform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader(Iif($Where, '搜索到的文章评论管理', '全部文章评论管理') . '(' . $maxrows['value'] . '条)');
		TableRow(array('评论内容(编辑)', '状态', '作者(昵称)', '语言', '发表时间', '文章', '<input type="checkbox" id="checkAll" for="deletec_ids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何评论!</font><BR><BR></center>');
		}else{
			while($comment = APP::$DB->fetch($getcomms)){

				$content = ShortTitle($comment['content'], 72);

				if($comment['actived'] =='0'){
					$content = "<font class=red><s>$content</s></font>";
				}elseif($comment['actived'] =='-1'){
					$content = "<font class=red>$content</font>";
				}

				TableRow(array('<input type="hidden" name="c_ids[]" value="'.$comment['c_id'].'"><input type="hidden" name="userids[]" value="'.$comment['userid'].'"><a id="c_' . $comment['c_id'] . '" class="ajax" status="'.$comment['actived'].'" content="'.$comment['content'].'" userid="'.$comment['userid'].'" forid="'.$comment['for_id'].'">' . $content . '</a>',

					'<select name="activeds[]" id="s_' . $comment['c_id'] . '"><option value="1">发布</option><option style="color:red;" value="-1" ' . Iif($comment['actived'] == '-1', 'SELECTED') . '>待审</option><option style="color:red;" value="0" ' . Iif($comment['actived'] == '0', 'SELECTED') . '>禁用</option></select>',

					Iif($comment['userid'], '<a title="编辑" href="'.BURL('users/edit?userid='.$comment['userid']).'"><img src="' . GetAvatar($comment['userid']) . '" class="user_avatar wh30">'.$comment['username'] . '</a>&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$comment['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>', "<font class=grey>[ 游客 ]</font> $comment[username]"),

					Iif($comment['lang'], '中文', 'EN'),

					DisplayDate($comment['created'], '', 1),

					'<a href="' . URL('articles?id=' . $comment['for_id']) . '" target="_blank" title="浏览文章 (ID: ' . $comment['for_id'] . ')"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>',

					'<input type="checkbox" name="deletec_ids[]" value="' . $comment['c_id'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
			if($totalpages > 1){
				TableRow(GetPageList(BURL('acomment'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id, 't', $time, 'type', $type));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updatecomms" value="保存更新" class="save"><input type="submit" name="deletecomms" onclick="'.Confirm('<font class=red>确定删除所选文章评论吗?</font>', 'form').'" value="删除评论" class="cancel"></div></form>';

		//ajax评论编辑框等
		echo '<div class="tb" style="border:0;display:none;" id="ajax_div">
		<form id="ajax_editform">
		<input type="hidden" name="userid" value="">
		<input type="hidden" name="c_id" value="">
		<font class=lightb>是否删除?</font>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="deletethiscomm" value="1">&nbsp;&nbsp;<font class=redb>慎选!</font> <span class=light>如果选择删除, 此评论将被删除.</span>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" target="_blank" title="发送短信作者" id="ajax_send" style="display:none"><img src="' . SYSDIR . 'public/admin/images/pm.png" align="absmiddle"></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" target="_blank" title="浏览文章" id="ajax_view"><img src="' . SYSDIR . 'public/admin/images/view.gif" align="absmiddle"></a>
		<div style="margin:6px 0;width:100%;"><font class=lightb>发布状态:</font>&nbsp;&nbsp;&nbsp;&nbsp;
		<input id="radio1" type="radio" name="actived" value="1"><label for="radio1" class=blue>发布</label>&nbsp;&nbsp;&nbsp;&nbsp;
		<input id="radio2" type="radio" name="actived" value="-1"><label for="radio2" class=orange>待审</label>&nbsp;&nbsp;&nbsp;&nbsp;
		<input id="radio3" type="radio" name="actived" value="0"><label for="radio3" class=red>禁用</label></div>
		<div class=lightb style="margin:8px 0;width:100%;">评论内容:</div>
		<textarea name="content" style="height:200px;width:400px;"></textarea>
		</form>
		</div>
		<script type="text/javascript">
			$(function(){
				$("#main a.ajax").click(function(e){
					var _me=$(this);

					var c_id = _me.attr("id").substr(2);
					var c_status = _me.attr("status");
					var c_content = _me.attr("content");
					var c_userid = _me.attr("userid");
					var c_forid = _me.attr("forid");

					var ajax_editform = $("#ajax_editform");
					ajax_editform.find("a#ajax_view").attr("href", "' . URL('articles?id='). '" + c_forid);  //文章链接

					if(c_userid != "0"){
						ajax_editform.find("a#ajax_send").attr("href", "' . BURL('pm/send?userid='). '" + c_userid).show();  //发送短信链接
					}else{
						ajax_editform.find("a#ajax_send").hide(); //游客评论时
					}

					ajax_editform.find("textarea[name=content]").val(c_content);
					ajax_editform.find("input[name=c_id]").val(c_id);
					ajax_editform.find("input[name=userid]").val(c_userid);
					if(c_status == "1"){
						ajax_editform.find("#radio1").attr("checked",true);
					}else if(c_status == "0"){
						ajax_editform.find("#radio3").attr("checked",true);
					}else{
						ajax_editform.find("#radio2").attr("checked",true);
					}

					ajax_editform.find("input[name=deletethiscomm]").attr("checked", false); //去掉删除框的选择状态

					$.dialog({title:"编辑文章评论", lock:true, padding:8,
						content: document.getElementById("ajax_div"),
						okValue:"  保存  ",
						ok:function(){
							var form_data =  $("#ajax_editform").serialize();

							ajax("' . BURL('acomment/ajax') . '", form_data, function(data){
								var deletethiscomm = ajax_editform.find("input[name=deletethiscomm]:checked").val();
								if(deletethiscomm == "1"){
									_me.parent().parent().remove(); //删除评论
								}else{
									var status = ajax_editform.find("input[name=actived]:checked").val();
									var content = ajax_editform.find("textarea[name=content]").val();

									data.i = (data.i).replace(/\\\r\\\n|\\\n|\\\r/g, " "); //替换换行符

									if(status == "1"){
										_me.html(data.i).attr("status", "1").attr("content", content);
									}else if(status == "0"){
										_me.html("<font class=red><s>" + data.i + "</s></font>").attr("status", "0").attr("content", content);
									}else{
										_me.html("<font class=red>" + data.i + "</font>").attr("status", "-1").attr("content", content);
									}

									var select = $("#main form select#s_" + c_id).attr("value", status);
								}
							});
						},
						cancelValue:"取消",cancel:true
					});

					e.preventDefault();
				});
			});
		</script>';
	}

	public function updatecomms(){
		$this->CheckAction('acomment'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updatecomms')){
			$c_ids = $_POST['c_ids'];
			$activeds   = $_POST['activeds'];
			for($i = 0; $i < count($c_ids); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "comment SET actived = '". ForceInt($activeds[$i])."' WHERE c_id = '". ForceInt($c_ids[$i])."'");
			}
		}else{
			$c_ids = $_POST['deletec_ids'];
			$userids = $_POST['userids'];
			for($i=0; $i<count($c_ids); $i++){

				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE c_id = '". ForceInt($c_ids[$i])."'");

				//可能是游客
				$userid = ForceInt($userids[$i]);
				if($userid) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET ac_num = (ac_num-1) WHERE userid = '$userid'");
			}
		}
		Success('acomment?p=' . $page);
	}

	//$search 查询的关键字 $type时间条件 大于小于什么的  $time时间 $cat_id评论状态
	private function GetSearchSql($search, $type, $time, $cat_id){
		$Where = "";

		if($cat_id == '-1'){
			$Where .= " actived='-1' ";
		}elseif($cat_id == '-2'){
			$Where .= " actived=0 ";
		}elseif($cat_id == 'cn'){
			$Where .= " lang=1 "; //中文
		}elseif($cat_id == 'en'){
			$Where .= " lang=0 "; //英文
		}elseif($cat_id == 're'){
			$Where .= " userid<>0 "; //注册用户的
		}elseif($cat_id == 'gu'){
			$Where .= " userid=0 "; //游客的
		}

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " (for_id = '$search' OR userid='$search') "; //是数字时按文章或用户ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (username like '%$search%' OR content like '%$search%') ";
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

}

?>