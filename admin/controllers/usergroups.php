<?php if(!defined('ROOT')) die('Access denied.');

class c_usergroups extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

		$this->arrProtectedIds = array(1, 2, 3);

		$this->actions = ''; //保存用户组的权限, 方便动作中调用
	}

	//操作权限Checkbox选择验证函数
	private function AccessSelected($action) {
		if($this->actions == 'all') return ' CHECKED ';

		if ($action AND strstr($this->actions, "*$action*")) {
			return ' CHECKED ';
		}else{
			return '';
		}
	}

	//获取用户组设置值
	private function getActionValue($item) {
		preg_match("/\\*$item:(\\w+)\\*/i", $this->actions, $matchs);
		return $matchs[1];
	}

	//删除
	public function delete(){
		$this->CheckAction('usergroups'); //权限验证

		$groupid = ForceIntFrom('groupid');

		if(in_array($groupid, $this->arrProtectedIds)) Error('默认用户组不允许删除!', '删除用户组错误');

		if(IsPost('confirmdelete'))	{
			$togroupid = ForceIntFrom('togroupid');
			if(!in_array($groupid, $this->arrProtectedIds)){//默认用户组不允许删除
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET groupid = '$togroupid' WHERE groupid = '" . $groupid . "'");
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "usergroup WHERE groupid = '" . $groupid . "'");
			}

			Success('usergroups');
		}else{
			SubMenu('用户组与权限', array(array('创建用户组', 'usergroups/add'),array('删除用户组', 'usergroups/delete?groupid=' . $groupid, 1),array('用户组列表', 'usergroups')));

			$thisgroup = APP::$DB->getOne("SELECT groupname, grouptype FROM " . TABLE_PREFIX . "usergroup WHERE groupid='$groupid' ");

			if(!$thisgroup) Error('您正在尝试删除的用户组不存在!', '删除用户组错误');

			$getusergroups = APP::$DB->query("SELECT groupid, groupname FROM " . TABLE_PREFIX . "usergroup WHERE grouptype ='$thisgroup[grouptype]' AND groupid <>'$groupid' AND groupid <>3 ORDER BY groupid");

			while($usergroup = APP::$DB->fetch($getusergroups)) {
				$usergroupselect .= '<option value="' . $usergroup['groupid'] . '">' . $usergroup['groupname'] . '</option>';
			}

			echo '<form method="post" action="'.BURL('usergroups/delete').'">
			<input type="hidden" name="groupid" value="' . $groupid . '" />';

			TableHeader('删除用户群组: <span class=note>' . $thisgroup['groupname'] . '</span>');
			TableRow(array('<BR><b>确定删除用户群组: "<font class=redb>' . $thisgroup['groupname'] . '</font>" 吗?</b><BR><BR>
			<span class=note>属于此群组的所有用户将自动转入群组</span>: <select name="togroupid">'.$usergroupselect.'</select><BR><BR>', '<input type="submit" name="confirmdelete" value="确定删除" class="save">
			<input type="submit" value="取 消" class="cancel" onclick="history.back();return false;">'));

			TableFooter();
			echo '</form>';
		}
	}

	//保存
	public function save(){
		$this->CheckAction('usergroups'); //权限验证

		$groupid = ForceIntFrom('groupid');

		if($groupid == 1) Success('usergroups'); //不允许编辑超级管理员

		$groupname  = ForceStringFrom('groupname');
		$groupname_en  = ForceStringFrom('groupname_en');
		$grouptype  = ForceIntFrom('grouptype');
		$description  = ForceStringFrom('description');
		$actions = Iif($grouptype == 1, $_POST['adminactions'], $_POST['useractions']);

		if($groupname == ''){
			$errors[] = "用户组中文名称不能为空!";
		}

		if($groupname_en == ''){
			$errors[] = "用户组英文名称不能为空!";
		}

		if(isset($errors)){
			Error($errors, Iif($groupid, '编辑用户组错误', '添加用户组错误'));
		}else{
			if($groupid == 3){ //如果是游客用户组, 仅能设置评论, 评论立即发布及询价权限
				$actionstr = '*';
				if(!empty($actions)){
					if(in_array('comment', $actions)) $actionstr .= 'comment*';
					if(in_array('enquiry', $actions)) $actionstr .= 'enquiry*';
					if(in_array('cRightnow', $actions)) $actionstr .= 'cRightnow*';
				}
				$actions = $actionstr;

			}else{ //其它用户组
				if(!empty($actions)){
					$actions = implode("*", $actions);
					$actions = '*' . ForceString($actions) . '*';
				}else{
					$actions = '*';
				}

				if($grouptype == 0){ //前台组设置值
					$anum = ForceIntFrom('anum');
					if($anum) $actions .= "anum:$anum*"; //前台用户组发表文章数量限制

					$pnum = ForceIntFrom('pnum');
					if($pnum) $actions .= "pnum:$pnum*"; //产品数量限制

					$pmdays = ForceIntFrom('userpmdays');
				}else{ //管理组设置值
					$pmdays = ForceIntFrom('pmdays');
				}

				if($pmdays) $actions .= "pmdays:$pmdays*"; //已阅短信保留天数
			}

			if($actions == '*') $actions = '';

			if($groupid){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "usergroup SET 
				groupname= '$groupname',
				groupname_en= '$groupname_en',
				description= '$description',
				actions= '$actions'
				WHERE groupid = '$groupid' ");
			}else{
				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "usergroup (grouptype, groupname, groupname_en, description, actions) VALUES ('$grouptype', '$groupname', '$groupname_en', '$description', '$actions') ");
			}

			Success('usergroups');
		}
	}

	//编辑调用add
	public function edit(){
		$this->add();
	}

	//添加用户组
	public function add(){
		$groupid = ForceIntFrom('groupid');

		if($groupid){
			SubMenu('用户组与权限', array(array('创建用户组', 'usergroups/add'),array('编辑用户组', 'usergroups/edit?groupid=' . $groupid, 1),array('用户组列表', 'usergroups')));

			$usergroup = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "usergroup WHERE groupid = '$groupid'");

			if(!$usergroup) Error('您想要编辑的用户组不存在!', '编辑用户组错误');

			$this->actions = $usergroup['actions'];
		}else{
			SubMenu('用户组与权限', array(array('创建用户组', 'usergroups/add', 1),array('用户组列表', 'usergroups')));

			$usergroup = array('grouptype' => 0);
		}

		echo '<form method="post" action="'.BURL('usergroups/save').'" name="usergroups">
		<input type="hidden" name="groupid" value="' . $groupid . '" />';


		if($groupid){
			TableHeader('编辑用户组: <span class=note>' . $usergroup['groupname'] . '</span>');
		}else{
			TableHeader('创建用户组');
		}

		TableRow(array('<b>用户组名称(<span class=blue>中文</span>):</b>', '<input type="text" name="groupname" value="' . $usergroup['groupname'] . '"  size="30" />&nbsp;&nbsp;<font class=red>* 必填项</font>'));
		TableRow(array('<b>用户组名称(<span class=red>英文</span>):</b>', '<input type="text" name="groupname_en" value="' . $usergroup['groupname_en'] . '"  size="30" />&nbsp;&nbsp;<font class=red>* 必填项</font>'));

		if(!$groupid){
			TableRow(array('<b>用户组类别:</b>', '<select name="grouptype" id="gtselecter"><option value="0">前台组</option><option value="1">管理组</option></select>
			<script>
			$(function(){     
				$("#gtselecter").change(function(){
					if($(this).val() == 0){
						$("#admingroup").hide();
						$("#usergroup").show(200);
					}else{
						$("#usergroup").hide();
						$("#admingroup").show(200);
					}
				});
			});
			</script>'));

		}else{
			TableRow(array('<b>用户组类别:</b>', '<input type="hidden" name="grouptype" value="' . $usergroup['grouptype'] . '"><select disabled><option value="0">前台组</option><option value="1" '.Iif($usergroup['grouptype'], 'SELECTED').'>管理组</option></select>'));
		}

		TableRow(array('<B>简要描述:</B>', '<textarea name="description" rows="4" style="width:320px;">' . $usergroup['description'] . '</textarea>'));

		TableFooter();

		echo '<div style="display:'.Iif($usergroup['grouptype'], 'none;', '').'" id="usergroup">';
		TableHeader('前台用户组权限设置');

		TableRow('<input type="checkbox" name="useractions[]" value="login" ' . $this->AccessSelected('login') .' />&nbsp;&nbsp;<b>允许登录</b>: <span class=light>是否允许登录网站前台?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="pm" ' . $this->AccessSelected('pm') .' />&nbsp;&nbsp;<b>允许发送短信</b>: <span class=light>是否允许发送站内短信?</span> <span class=note>注: 即使无此权限, 用户仍可以回复收到的短信.</span>');
		TableRow('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>已阅短信保留天数</b>: <input type="text" name="userpmdays" value="'.$this->getActionValue('pmdays').'" size="6"> 天 &nbsp;&nbsp;<span class=light>所收短信已阅后可保留的天数.</span> <span class=note>注: 留空或0表示不限制, 此设置有利于系统自动维护短信数据库表.</span>');

		TableRow('', 'tr2');

		TableRow('<input type="checkbox" name="useractions[]" value="comment" ' . $this->AccessSelected('comment') .' />&nbsp;&nbsp;<b>允许发表评论</b>: <span class=light>是否允许对文章或产品发表评论?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="cRightnow" ' . $this->AccessSelected('cRightnow') .' />&nbsp;&nbsp;<b>评论立即发布</b>: <span class=light>用户发表评论立即发布(即不需要审核)?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="del_c" ' . $this->AccessSelected('del_c') .' />&nbsp;&nbsp;<b>允许删除评论</b>: <span class=light>是否允许删除自己发表的评论?</span>');

		TableRow('', 'tr2');

		TableRow('<input type="checkbox" name="useractions[]" value="articles" ' . $this->AccessSelected('articles') .' />&nbsp;&nbsp;<b>允许发表文章</b>: <span class=light>是否允许发表文章?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="aRightnow" ' . $this->AccessSelected('aRightnow') .' />&nbsp;&nbsp;<b>文章立即发布</b>: <span class=light>用户发表文章立即发布(即不需要审核)?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="del_a" ' . $this->AccessSelected('del_a') .' />&nbsp;&nbsp;<b>允许删除文章</b>: <span class=light>是否允许删除自己发表的文章?</span>');
		TableRow('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>文章数量限制</b>: <input type="text" name="anum" value="'.$this->getActionValue('anum').'" size="6"> 篇 &nbsp;&nbsp;<span class=light>可以发表文章的最大数量.</span> <span class=note>注: 留空或0表示不限制.</span>');

		TableRow('', 'tr2');

		TableRow('<input type="checkbox" name="useractions[]" value="products" ' . $this->AccessSelected('products') .' />&nbsp;&nbsp;<b>允许发表产品</b>: <span class=light>是否允许发表产品?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="pRightnow" ' . $this->AccessSelected('pRightnow') .' />&nbsp;&nbsp;<b>产品立即发布</b>: <span class=light>用户发表产品立即发布(即不需要审核)?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="del_p" ' . $this->AccessSelected('del_p') .' />&nbsp;&nbsp;<b>允许删除产品</b>: <span class=light>是否允许删除自己发表的产品?</span>');
		TableRow('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>产品数量限制</b>: <input type="text" name="pnum" value="'.$this->getActionValue('pnum').'" size="6"> 个 &nbsp;&nbsp;<span class=light>可以发表产品的最大数量.</span> <span class=note>注: 留空或0表示不限制.</span>');

		TableRow('', 'tr2');

		TableRow('<input type="checkbox" name="useractions[]" value="enquiry" ' . $this->AccessSelected('enquiry') .' />&nbsp;&nbsp;<b>允许产品询价</b>: <span class=light>是否允许对产品提交询价?</span>');
		TableRow('<input type="checkbox" name="useractions[]" value="del_e" ' . $this->AccessSelected('del_e') .' />&nbsp;&nbsp;<b>允许删除询价</b>: <span class=light>是否允许删除自己提交的询价?</span>');

		TableFooter();
		echo '</div>';

		echo '<div style="display:'.Iif($usergroup['grouptype'], '', 'none;').'" id="admingroup">';

		TableHeader('后台管理组权限设置(<font class="orange">管理组用户具有前台的所有权限</font>)');
		TableRow('<input type="checkbox" name="adminactions[]" value="settings" ' . $this->AccessSelected('settings') .' />&nbsp;&nbsp;<font class=blueb>允许系统设置</font>: 是否允许操作网站的系统(基本)设置?');
		TableRow('<input type="checkbox" name="adminactions[]" value="phpinfo" ' . $this->AccessSelected('phpinfo') .' />&nbsp;&nbsp;<font class=blueb>允许查看环境信息</font>: 是否允许查看服务器的PHP环境信息?');
		TableRow('<input type="checkbox" name="adminactions[]" value="upgrade" ' . $this->AccessSelected('upgrade') .' />&nbsp;&nbsp;<font class=blueb>允许操作升级</font>: 是否允许操作系统升级?');
		TableRow('<input type="checkbox" name="adminactions[]" value="database" ' . $this->AccessSelected('database') .' />&nbsp;&nbsp;<font class=blueb>允许数据维护</font>: 是否允许查错, 优化, 备份, 恢复网站数据库等?');
		TableRow('<input type="checkbox" name="adminactions[]" value="template" ' . $this->AccessSelected('template') .' />&nbsp;&nbsp;<font class=blueb>允许管理模板</font>: 是否允许设置或在线编辑网站前台模板及模板文件管理等?');
		TableRow('<input type="checkbox" name="adminactions[]" value="language" ' . $this->AccessSelected('language') .' />&nbsp;&nbsp;<font class=blueb>允许管理语言</font>: 是否允许设置或在线编辑网站前台语言等?');

		TableRow('', 'tr2');
		TableRow('<input type="checkbox" name="adminactions[]" value="users" ' . $this->AccessSelected('users') .' />&nbsp;&nbsp;<font class=blueb>允许管理用户</font>: 是否允许管理用户?');
		TableRow('<input type="checkbox" name="adminactions[]" value="usergroups" ' . $this->AccessSelected('usergroups') .' />&nbsp;&nbsp;<font class=blueb>允许管理用户组</font>: 是否允许管理用户组?');
		TableRow('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font class=blueb>已阅短信保留天数</font>: <input type="text" name="pmdays" value="'.$this->getActionValue('pmdays').'" size="6"> 天 &nbsp;&nbsp;所收短信已阅后可保留的天数. <span class=note>注: 留空或0表示不限制, 此设置有利于系统自动维护短信数据库表.</span>');

		TableRow('', 'tr2');
		TableRow('<input type="checkbox" name="adminactions[]" value="articles" ' . $this->AccessSelected('articles') .' />&nbsp;&nbsp;<font class=blueb>允许管理文章</font>: 是否允许管理网站的文章?');
		TableRow('<input type="checkbox" name="adminactions[]" value="acategory" ' . $this->AccessSelected('acategory') .' />&nbsp;&nbsp;<font class=blueb>允许管理文章分类</font>: 是否允许管理文章的分类目录?');
		TableRow('<input type="checkbox" name="adminactions[]" value="acomment" ' . $this->AccessSelected('acomment') .' />&nbsp;&nbsp;<font class=blueb>允许管理文章评论</font>: 是否允许管理用户发表的文章评论?');

		TableRow('', 'tr2');
		TableRow('<input type="checkbox" name="adminactions[]" value="products" ' . $this->AccessSelected('products') .' />&nbsp;&nbsp;<font class=blueb>允许管理产品</font>: 是否允许管理网站的产品?');
		TableRow('<input type="checkbox" name="adminactions[]" value="pcategory" ' . $this->AccessSelected('pcategory') .' />&nbsp;&nbsp;<font class=blueb>允许管理产品分类</font>: 是否允许管理产品的分类目录?');
		TableRow('<input type="checkbox" name="adminactions[]" value="enquiry" ' . $this->AccessSelected('enquiry') .' />&nbsp;&nbsp;<font class=blueb>允许管理产品询价</font>: 是否允许管理(回复, 删除等)前台用户提交的产品询价?');
		TableRow('<input type="checkbox" name="adminactions[]" value="pcomment" ' . $this->AccessSelected('pcomment') .' />&nbsp;&nbsp;<font class=blueb>允许管理产品评论</font>: 是否允许管理用户发表的产品评论?');

		TableRow('', 'tr2');
		TableRow('<input type="checkbox" name="adminactions[]" value="news" ' . $this->AccessSelected('news') .' />&nbsp;&nbsp;<font class=blueb>允许管理网站新闻</font>: 是否允许管理站点新闻?');
		TableRow('<input type="checkbox" name="adminactions[]" value="contents" ' . $this->AccessSelected('contents') .' />&nbsp;&nbsp;<font class=blueb>允许管理常态内容</font>: 是否允许管理常态内容?');
		TableRow('<input type="checkbox" name="adminactions[]" value="advertise" ' . $this->AccessSelected('advertise') .' />&nbsp;&nbsp;<font class=blueb>允许管理网站广告</font>: 是否允许管理站内广告及广告位置?');

		TableFooter();

		echo '</div>';

		PrintSubmit(Iif($groupid, '保存更新', '创建用户组'));
	}

	public function index(){
		SubMenu('用户组与权限', array(array('创建用户组', 'usergroups/add')));

		$getusergroups = APP::$DB->query("SELECT ug.*, COUNT(u.userid) AS users FROM " . TABLE_PREFIX . "usergroup ug LEFT JOIN " . TABLE_PREFIX . "user u ON (u.groupid = ug.groupid) GROUP BY ug.groupid ORDER BY ug.grouptype, ug.groupid");


		ShowTips('<ul>
		<li><b>游客</b>: 指网站前台未注册的浏览者, 且仅能设置该用户组的<span class=note>评论、评论立即发布、询价</span>权限.</li>
		<li><b>注册会员</b>: 前台用户注册后的默认用户组.</li>
		<li><b>超级管理员</b>: 具有网站前后台所有权限, 且权限无法更改.</li>
		</ul>', '默认用户组权限说明');
		
		TableHeader('用户组列表');
		TableRow(array('用户组名称(英文)', '类别', '描述', '用户数量', '编辑', '删除'), 'tr0');

		while($usergroup = APP::$DB->fetch($getusergroups)){
			TableRow(array('<a href="'.BURL('usergroups/edit?groupid=' . $usergroup['groupid']) . '">' . $usergroup['groupname'] . " ($usergroup[groupname_en])</a>",
				Iif($usergroup['grouptype'], '<span class=orange>管理组</span>', '前台组'),
				$usergroup['description'],
				$usergroup['users'],
				'<a href="' . BURL('usergroups/edit?groupid=' . $usergroup['groupid']) . '"><img src="' . SYSDIR . 'public/admin/images/edit.png" /></a>',
				Iif(in_array($usergroup['groupid'], $this->arrProtectedIds), '', '<a href="'.BURL('usergroups/delete?groupid=' . $usergroup['groupid']) . '"><img src="' . SYSDIR . 'public/admin/images/trash.png" /></a>')));
		}

		TableFooter();
	}

} 

?>