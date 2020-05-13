<?php if(!defined('ROOT')) die('Access denied.');

class c_advertise extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	private function showinfo(){
			ShowTips('<ul>
			<li>请先添加广告位置, 然后再添加广告, 同一广告位置下如有多个广告, 每次打开页面时仅随机显示其中之一.</li>
			<li>调用广告需要编辑前台模板文件(tpl), 在需要插入广告的地方添加代码: <font class=red>{ShowAdvert(ID)}</font>&nbsp;&nbsp;<font class=note>注: ID指广告位置的ID号</font>.</li>
			<li>编辑前台模板文件(tpl)后, 需要更新模板缓存才能显示广告效果. <font class=note>注: 当网站运行于模板编辑模式时, 无需更新.</font></li>
			</ul>', '广告使用说明');
	}

	public function index(){
		$NumPerPage = 10;   //每页显示的广告管理的数量
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');   //搜索的内容
		$type = ForceStringFrom('type');   //搜索的内容
		$time = ForceStringFrom('t');
		$cat_id = ForceIntFrom('c');   //按分类搜索时所选的分类的id

		if(IsGet('s')){
			$search = urldecode($search);
		}

		$start = $NumPerPage * ($page-1);  //分页的每页起始位置
		$Where = $this->GetSearchSql($search, $type, $time, $cat_id);

		SubMenu('广告管理', array(array('添加广告', 'advertise/add'), array('广告列表', 'advertise', 1), array('添加位置', 'advertise/addpo'), array('位置列表', 'advertise/adpo')));

		$adpositions = array();
		$adposelect = '';
		$redstyle = "";

		$getadpositions = APP::$DB->query("SELECT adpoid, title, actived FROM " . TABLE_PREFIX . "adposition ORDER BY adpoid ASC");
		while($adpo = APP::$DB->fetch($getadpositions)) {
			$adpositions[$adpo['adpoid']] = array('title' => $adpo['title'], 'actived' => $adpo['actived']);

			if(!$adpo['actived']) $redstyle = " style='color:red;'";
			$adposelect .= "<option " . Iif($adpo['adpoid'] == $cat_id, "selected") . " value=\"$adpo[adpoid]\"  $redstyle>$adpo[title] (ID:$adpo[adpoid])</option>";
		}

		$this->showinfo(); //显示说明

		echo '<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<form method="post" action="'.BURL('advertise').'" name="searchform">';

		TableHeader('搜索广告');
		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="12" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>发布时间:</label>&nbsp;<select name="type"><option value="gr" ' . Iif($type == 'gr', 'SELECTED') . '>大于(>)</option><option value="eq" ' . Iif($type == 'eq', 'SELECTED') . '>等于(=)</option><option value="le" ' . Iif($type == 'le', 'SELECTED') . '>小于(<)</option></select> <input type="text" name="t" onClick="WdatePicker()" value="' . $time . '" size="12">&nbsp;&nbsp;&nbsp;<label>状态或位置:</label>&nbsp;<select name="c"><option value="0">全部广告</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>已禁用的广告</option><option style="color:red;" value="-2" ' . Iif($cat_id == '-2', 'SELECTED') . '>已过期的广告</option>' . $adposelect . '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索广告" class="cancel"></center>');
		TableFooter();
		echo '</form>';

		$getadvertises = APP::$DB->query("SELECT adid, adpoid, actived, overdate, title, created FROM " . TABLE_PREFIX . "advertise " . $Where . " ORDER BY actived ASC, adid DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(adid) AS value FROM " . TABLE_PREFIX . "advertise " . $Where);

		echo '<form method="post" action="'.BURL('advertise/updateads').'" name="advertiseform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader(Iif($Where, '搜索到的广告列表', '全部广告列表') . '(' . $maxrows['value'] . '个)');
		TableRow(array('广告名称(编辑)', '状态', '广告位置(编辑)', '发布日期', '过期日期', '<input type="checkbox" id="checkAll" for="deleteadids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何广告!</font><BR><BR></center>');
		}else{
			while($ad = APP::$DB->fetch($getadvertises)){

				$title = ShortTitle($ad['title'], 28);

				if($ad['actived'] =='0'){
					$title = "<font class=red><s>$title</s></font>";
				}elseif($ad['overdate'] AND (time() + 3600 * APP::$_CFG['siteTimezone']) > $ad['overdate']){
					$title = "<font class=red>$title</font>";
				}

				TableRow(array('<input type="hidden" name="adids[]" value="'.$ad['adid'].'"><a href="'.BURL('advertise/edit?adid='.$ad['adid']).'">' . $title . '</a>',
					'<select name="activeds[]"><option value="1">发布</option><option style="color:red;" value="0"' . Iif(!$ad['actived'], ' SELECTED') . '>禁用</option></select>',

					'<a href="'.BURL('advertise/editpo?adpoid=' . $ad['adpoid']) . '">' .$adpositions[$ad['adpoid']]['title'] . ' (ID:'.$ad['adpoid'].')</a>',

					DisplayDate($ad['created']),

					Iif($ad['overdate'], Iif((time() + 3600 * APP::$_CFG['siteTimezone']) > $ad['overdate'], '<font class=redb>已过期</font> ('.DisplayDate($ad['overdate']).')', '<font class=orange>'.DisplayDate($ad['overdate']).'</font>'), '<font class=green>从不过期</font>'),
					'<input type="checkbox" name="deleteadids[]" value="' . $ad['adid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
			if($totalpages > 1){
				TableRow(GetPageList(BURL('advertise'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id, 't', $time, 'type', $type));
			}
		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updateads" value="保存更新" class="save"><input type="submit" name="deleteads" onclick="'.Confirm('<span class=red>注: 确定删除所选广告吗!</span>', 'form').'" value="删除广告" class="cancel"></div></form>';
	}

	public function updateads(){
		$this->CheckAction('advertise'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updateads')){
			$adids = $_POST['adids'];
			$activeds   = $_POST['activeds'];
			for($i = 0; $i < count($adids); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "advertise SET 	actived = '". ForceInt($activeds[$i])."'
				WHERE adid = '". ForceInt($adids[$i])."'");
			}
		}else{
			$adids = $_POST['deleteadids'];
			for($i=0; $i<count($adids); $i++){
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "advertise WHERE adid=".ForceInt($adids[$i]));
			}
		}

		Success('advertise?p=' . $page);
	}

	//$search 查询的关键字 $type时间条件 大于小于什么的  $time时间 $cat_id广告位置id或状态
	private function GetSearchSql($search, $type, $time, $cat_id){
		$Where = "";

		if($cat_id > 0){
			$Where .= " adpoid = $cat_id ";
		}elseif($cat_id == '-1'){
			$Where .= " actived = 0 ";
		}elseif($cat_id == '-2'){
			$Where .= " overdate <> 0 AND overdate < " . (time() + 3600 * APP::$_CFG['siteTimezone']);
		}

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " (adid = '$search' OR adpoid = '$search') "; //是数字时按广告或位置ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (title like '%$search%' OR content like '%$search%' OR content_en like '%$search%') ";
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

		$Where = Iif($Where, " WHERE " . $Where);

		return $Where;
	}

	//编辑调用add
	public function edit(){
		$this->add();
	}

	public function add(){
		$adid = ForceIntFrom('adid');

		if($adid){  //编辑时的
			SubMenu('广告管理', array(array('添加广告', 'advertise/add'), array('编辑广告', 'advertise/edit?adid='.$adid, 1), array('广告列表', 'advertise'), array('添加位置', 'advertise/addpo'), array('位置列表', 'advertise/adpo')));
	
			$ad = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "advertise WHERE adid = '$adid'");
		}else{
			SubMenu('广告管理', array(array('添加广告', 'advertise/add', 1), array('广告列表', 'advertise'), array('添加位置', 'advertise/addpo'), array('位置列表', 'advertise/adpo')));

			$ad = array('actived' => 1, 'adpoid' => 0);
		}

		$adposelect = '';
		$redstyle = "";
		$getadpos = APP::$DB->query("SELECT adpoid, actived, title FROM " . TABLE_PREFIX . "adposition ORDER BY adpoid");
		while($adpo = APP::$DB->fetch($getadpos)) {
			if(!$adpo['actived']) $redstyle = " style='color:red;'";
			$adposelect .= "<option " . Iif($adpo['adpoid'] == $ad['adpoid'], "selected") . " value=\"$adpo[adpoid]\"  $redstyle>$adpo[title] (ID:$adpo[adpoid])</option>";
		}

		echo '<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/kindeditor.js"></script>
		<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/lang/zh_CN.js"></script>
		<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<script>
			KindEditor.ready(function(K) {
				var editor88 = K.create(\'textarea[name="content"]\', {
					uploadJson : \''.BURL('editor_upload/ajax').'\',
					fileManagerJson : \''.BURL('editor_file_manager/ajax').'\',
					allowFileManager : true,
					afterCreate : function() {
						var self = this;
						K.ctrl(document, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
						K.ctrl(self.edit.doc, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
					}
				});

				var editor66 = K.create(\'textarea[name="content_en"]\', {
					uploadJson : \''.BURL('editor_upload/ajax').'\',
					fileManagerJson : \''.BURL('editor_file_manager/ajax').'\',
					allowFileManager : true,
					afterCreate : function() {
						var self = this;
						K.ctrl(document, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
						K.ctrl(self.edit.doc, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
					}
				});

			});
		</script>
		<form id="editorform88" name="editorform88" method="post" action="'.BURL('advertise/save').'">
		<input type="hidden" name="adid" value="' . $adid . '">';

		if($adid){
			TableHeader('编辑广告: <span class=note>' . $ad['title'] . '</span>');

		}else{
			TableHeader('添加广告');
		}

		TableRow(array('<B>是否发布?</B>', '<input type="checkbox" name="actived" value="1" '.Iif($ad['actived'] == 1, 'CHECKED').'> <b>是:</b> <span class=light>当不发布时, 此广告在前台不显示.</span>'));

		TableRow(array('<B>广告名称:</B>', '<input type="text" name="title" value="' . $ad['title'] . '"  size="40"> <font class=red>* 必填项</font>'));
		TableRow(array('<B>所属位置:</B>', '<select name="adpoid"><option value="0">-- 请选择 --</option>' . $adposelect . '</select> <font class=red>* 必填项</font>'));
		TableRow(array('<B>过期日期:</B>', '<input type="text" name="overdate" value="' . Iif($ad['overdate'], DisplayDate($ad['overdate'], 'Y-m-d')).'" onClick="WdatePicker()" size="20"> <span class=light>注: 留空表示从不过期, 要求日期格式如: 2018-01-02</span>'));

		if($adid){
			TableRow(array('<B>是否删除?</B>', '<input type="checkbox" name="deletethisad" value="1"> <b>是:</b> <font class=redb>慎选!</font> <span class=light>如果选择删除, 此广告相关的所有信息将被删除.</span'));
		}

		TableRow(array('<B>广告内容:</B><BR><span class=light>注: 中英文页面独立<BR>显示中英文内容.</span>', '
			<div class="ok_tab">
				<div class="ok_tabheader">
					<ul id="tabContent-li-ok_tabOn-">
						<li class="ok_tabOn"><a href="javascript:void(0)" title="中文内容" rel="1" hidefocus="true">中文内容</a></li>
						<li><a href="javascript:void(0)" title="英文内容" rel="2" hidefocus="true">英文内容</a></li>
					</ul>
				</div>
				<div id="tabContent_1" class="tabContent">
				 <textarea name="content" style="width:100%;height:400px;visibility:hidden;" id="content">'.$ad['content'].'</textarea>
				</div>

				<div id="tabContent_2" class="tabContent" style="display: none;">
				<textarea name="content_en" style="width:100%;height:400px;visibility:hidden;" id="content_en">'.$ad['content_en'].'</textarea>
				</div>

				<div class="ok_tabbottom">
					<span class="tabbottomL"></span>
					<span class="tabbottomR"></span>
				</div>
			</div>
			<script type="text/javascript">new tab(\'tabContent-li-ok_tabOn-\', \'-\');</script>
		'));


		TableFooter();

		PrintSubmit(Iif($adid, '保存更新', '添加广告'));
	}

	public function save(){
		$this->CheckAction('advertise'); //权限验证

		$adid = ForceIntFrom('adid');
		$actived = ForceIntFrom('actived');
		$title = ForceStringFrom('title');
		$content = ForceStringFrom('content');
		$content_en = ForceStringFrom('content_en');
		$adpoid     = ForceIntFrom('adpoid');
		$overdate        = ForceStringFrom('overdate');
		
		$deletethisad     = ForceIntFrom('deletethisad');

		if($deletethisad AND $adid){//删除广告
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."advertise where adid='$adid'");

			Success('advertise');
		}

		if(!$title) $errors[] = '广告名称不能为空！';
		if(!$adpoid) $errors[] = "请选择广告所属位置!";

		if(preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/i", $overdate, $pregdata)){
			$year  = $pregdata[1];
			$month  = $pregdata[2];
			$day    = $pregdata[3];
			if($month > 12 || $day > 31 || $month < 1 || $day < 1){
				$errors[] = "日期格式不正确!";
			}else{
				$overdate = mktime(0, 0, 0, $month, $day, $year);
			}
		}else{
			$overdate = 0;
		}

		if(isset($errors)) Error($errors, Iif($adid, '编辑广告错误', '添加广告错误'));

		if($adid){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "advertise SET 
			adpoid     = '$adpoid',
			actived     = '$actived',
			overdate     = '$overdate',
			title     = '$title',
			content     = '$content',
			content_en     = '$content_en'
			WHERE adid   = '$adid'");
		}else{
			$created = time();

			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "advertise (adpoid, actived, overdate, title, content, content_en, created) VALUES ('$adpoid', '$actived', '$overdate', '$title', '$content', '$content_en', '$created')");
		}

		Success('advertise');
	}


	//以下广告位置相关代码
	public function adpo(){
		$NumPerPage = 10;   //每页显示的数量
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');   //搜索的内容
		$cat_id = ForceIntFrom('c');   //位置状态

		if(IsGet('s')){
			$search = urldecode($search);
		}

		$Where = "";
		if($cat_id == '-1') $Where .= " p.actived=0 ";

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " p.adpoid = '$search' "; //是数字时按位置ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (p.title like '%$search%' OR p.description like '%$search%' OR p.width like '%$search%') ";
		}

		$Where = Iif($Where, " WHERE " . $Where);

		$start = $NumPerPage * ($page-1);  //分页的每页起始位置

		SubMenu('广告管理', array(array('添加广告', 'advertise/add'), array('广告列表', 'advertise'), array('添加位置', 'advertise/addpo'), array('位置列表', 'advertise/adpo', 1)));

		$this->showinfo(); //显示说明

		echo '<form method="post" action="'.BURL('advertise/adpo').'" name="searchform">';

		TableHeader('搜索广告位置');
		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="18" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="c"><option value="0">全部位置</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>已禁用的位置</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索广告位置" class="cancel"></center>');
		TableFooter();
		echo '</form>';

		$getadpos = APP::$DB->query("SELECT p.*, COUNT(a.adid) AS ads FROM " . TABLE_PREFIX . "adposition p LEFT JOIN " . TABLE_PREFIX . "advertise a ON (a.adpoid = p.adpoid) " . $Where . " GROUP BY p.adpoid ORDER BY p.actived, p.adpoid LIMIT $start, $NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(p.adpoid) AS value FROM " . TABLE_PREFIX . "adposition p " . $Where);

		echo '<form method="post" action="'.BURL('advertise/updateadpos').'" name="adpoform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader(Iif($Where, '搜索到的广告位置', '全部广告位置') . '(' . $maxrows['value'] . '个)');
		TableRow(array('ID', '位置名称(编辑)', '状态', '广告数', '宽 | 高', '简要说明', '<input type="checkbox" id="checkAll" for="deleteadpoids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何广告位置!</font><BR><BR></center>');
		}else{
			while($adpo = APP::$DB->fetch($getadpos)){

				$title = ShortTitle($adpo['title'], 36);

				if($adpo['actived'] =='0'){
					$title = "<font class=red><s>$title</s></font>";
				}

				TableRow(array('<input type="hidden" name="adpoids[]" value="'.$adpo['adpoid'].'">' . $adpo['adpoid'],
					'<a href="'.BURL('advertise/editpo?adpoid='.$adpo['adpoid']).'">' . $title . '</a>',
					'<select name="activeds[]"><option value="1">发布</option><option style="color:red;" value="0"' . Iif(!$adpo['actived'], ' SELECTED') . '>禁用</option></select>',
					$adpo['ads'],
					$adpo['width'] . " | " . $adpo['height'],
					 ShortTitle($adpo['description'], 72),
					'<input type="checkbox" name="deleteadpoids[]" value="' . $adpo['adpoid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
			if($totalpages > 1){
				TableRow(GetPageList(BURL('advertise/adpo'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id));
			}
		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updateadpos" value="保存更新" class="save"><input type="submit" name="deleteadpos" onclick="'.Confirm('确定删除所选广告位置吗?<BR><BR><span class=red>注: 所选位置下的所有广告将同时被删除！</span>', 'form').'" value="删除位置" class="cancel"></div></form>';
	}

	//批量修改广告位置
	public function updateadpos(){
		$this->CheckAction('advertise'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updateadpos')){
			$adpoids = $_POST['adpoids'];
			$activeds   = $_POST['activeds'];
			for($i = 0; $i < count($adpoids); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "adposition SET actived = '". ForceInt($activeds[$i])."'
				WHERE adpoid =". ForceInt($adpoids[$i]));
			}
		}else{
			$adpoids = $_POST['deleteadpoids'];
			for($i=0; $i<count($adpoids); $i++){
				$adpoid = ForceInt($adpoids[$i]);
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "adposition WHERE adpoid=$adpoid");
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "advertise WHERE adpoid=$adpoid");
			}
		}

		Success('advertise/adpo?p=' . $page);
	}

	//编辑位置调用addpo
	public function editpo(){
		$this->addpo();
	}

	//添加广告位置
	public function addpo(){
		$adpoid = ForceIntFrom('adpoid');

		if($adpoid){  //编辑
			SubMenu('广告管理', array(array('添加广告', 'advertise/add'), array('广告列表', 'advertise'), array('添加位置', 'advertise/addpo'), array('编辑位置', 'advertise/editpo?adpoid=' . $adpoid, 1), array('位置列表', 'advertise/adpo')));

			$adpo = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "adposition WHERE adpoid = '$adpoid'");
		}else{
			SubMenu('广告管理', array(array('添加广告', 'advertise/add'), array('广告列表', 'advertise'), array('添加位置', 'advertise/addpo', 1), array('位置列表', 'advertise/adpo')));

			$adpo = array('actived' => 1);
		}

		echo '<form id="editorform88" name="editorform88" method="post" action="'.BURL('advertise/savepo').'">
		<input type="hidden" name="adpoid" value="' . $adpoid . '">';

		if($adpoid){
			TableHeader('编辑位置: <span class=note>' . $adpo['title'] . '</span>');
		}else{
			TableHeader('添加位置');
		}

		TableRow(array('<B>是否发布?</B>', '<input type="checkbox" name="actived" value="1" '.Iif($adpo['actived'] == 1, 'CHECKED').'> <b>是:</b> <span class=light>是否发布此位置下的所有广告?</span>'));
		TableRow(array('<B>位置名称:</B>', '<input type="text" name="title" value="'.$adpo['title'].'" size="40"> <font class=red>* 必填项</font>'));
		TableRow(array('<B>位置宽度:</B>', '<input type="text" name="width" value="'.$adpo['width'].'" size="18"><br><span class=light>注: 广告位置在前台以DIV显示, 这里设置的宽度即DIV的宽度, 如设置为: 200px, 100%, auto等. 如留空, 则取值为: auto</span>'));
		TableRow(array('<B>位置高度:</B>', '<input type="text" name="height" value="'.$adpo['height'].'" size="18"><br><span class=light>注: 同上</span>'));
		TableRow(array('<B>简要说明:</B>', '<textarea name="description" style="height:100px;width:400px;">'.$adpo['description'].'</textarea>'));
	
		if($adpoid){
			TableRow(array('<B>是否删除?</B>', '<input type="checkbox" name="deletethispo" value="1"> <b>是:</b> <font class=redb>慎选!</font> <span class=light>如果选择删除, 此广告位置及属于此位置的所有广告将同时被删除.</span>'));
		}

		TableFooter();

		PrintSubmit(Iif($adpoid, '保存更新', '添加位置'));
	}

	//保存位置
	public function savepo(){
		$this->CheckAction('advertise'); //权限验证

		$adpoid = ForceIntFrom('adpoid');
		$title = ForceStringFrom('title');
		$description = ForceStringFrom('description');
		$actived = ForceIntFrom('actived');
		$width = ForceStringFrom('width');
		$height = ForceStringFrom('height');
		$deletethispo     = ForceIntFrom('deletethispo');

		if($deletethispo){
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "adposition WHERE adpoid = '$adpoid'");
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "advertise WHERE adpoid = '$adpoid'");

			Success('advertise/adpo');
		}

		if(!$title) $errors = "广告位置的名称不能为空!";
		if(isset($errors))	Error($errors, Iif($adpoid, '编辑位置错误', '添加位置错误'));

		if(strlen($width) == 0) $width = 'auto';
		if(strlen($height) == 0) $height = 'auto';

		if($adpoid){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "adposition SET 
			actived     = '$actived',
			title     = '$title',
			description = '$description',
			width     = '$width',
			height     = '$height'
			WHERE adpoid = '$adpoid'");
		}else{
			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "adposition (actived, title, description, width, height) VALUES ('$actived', '$title', '$description', '$width', '$height')");
		}

		Success('advertise/adpo');
	}

}

?>