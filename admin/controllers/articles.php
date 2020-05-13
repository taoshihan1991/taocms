<?php if(!defined('ROOT')) die('Access denied.');

class c_articles extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	public function index(){
		$NumPerPage = 10;   //每页显示的文章列表的数量
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

		if($Where){
			SubMenu('文章列表', array(array('添加文章', 'articles/add'), array('全部文章', 'articles')));
		}else{
			SubMenu('文章列表', array(array('添加文章', 'articles/add')));
		}

		$newcategories = array();
		$getcategories = APP::$DB->query("SELECT cat_id, p_id, name, name_en, counts FROM " . TABLE_PREFIX . "acat ORDER BY sort");
		while($category = APP::$DB->fetch($getcategories)){
			$newcategories[$category['cat_id']] = $category;
		}

		echo '<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<form method="post" action="'.BURL('articles').'" name="searchform">';

		TableHeader('搜索文章');
		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="12" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>发表时间:</label>&nbsp;<select name="type"><option value="gr" ' . Iif($type == 'gr', 'SELECTED') . '>大于(>)</option><option value="eq" ' . Iif($type == 'eq', 'SELECTED') . '>等于(=)</option><option value="le" ' . Iif($type == 'le', 'SELECTED') . '>小于(<)</option></select> <input type="text" name="t" onClick="WdatePicker()" value="' . $time . '" size="12">&nbsp;&nbsp;&nbsp;<label>分类:</label>&nbsp;<select name="c"><option value="0">全部分类</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>待审中的文章</option><option style="color:red;" value="-2" ' . Iif($cat_id == '-2', 'SELECTED') . '>已禁用的文章</option>' . $this->GetOptions($newcategories,$cat_id) . '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="searcharticle" value="搜索文章" class="cancel"></center>');
		TableFooter();
		echo '</form>';

		$getarticles = APP::$DB->query("SELECT a_id, sort, cat_id, is_show, is_best, userid, username, title, title_en, clicks, created FROM " . TABLE_PREFIX . "article " . $Where . " ORDER BY is_show ASC, sort DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(a_id) AS value FROM " . TABLE_PREFIX . "article " . $Where);

		echo '<form method="post" action="'.BURL('articles/updatearticles').'" name="articlesform">
		<input type="hidden" name="p" value="'.$page.'">';
		TableHeader(Iif($Where, '搜索到的文章列表', '全部文章列表') . '(' . $maxrows['value'] . '篇)');
		TableRow(array('排序', '文章标题(编辑)', '所属分类', '作者(昵称)', '状态', '推荐', '点击', '日期', '浏览', '<input type="checkbox" id="checkAll" for="deletea_ids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何文章!</font><BR><BR></center>');
		}else{
			while($article = APP::$DB->fetch($getarticles)){

				$title = ShortTitle($article['title'], 28);

				if($article['is_show'] =='0'){
					$title = "<font class=red><s>$title</s></font>";
				}elseif($article['is_show'] =='-1'){
					$title = "<font class=red>$title</font>";
				}

				TableRow(array('<input type="hidden" name="a_ids[]" value="'.$article['a_id'].'"><input type="text" name="sorts[]" value="' . $article['sort'] . '" size="4">',
					'<a href="'.BURL('articles/edit?a_id='.$article['a_id']).'" title="英文: '.$article['title_en'].'">' . $title . '</a>',
					$newcategories[$article['cat_id']]['name'],
					'<a title="编辑" href="'.BURL('users/edit?userid='.$article['userid']).'"><img src="' . GetAvatar($article['userid']) . '" class="user_avatar wh30">' . $article['username'] . '</a>&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$article['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>',
					'<select name="is_shows[]"><option value="1">发布</option><option style="color:red;" value="-1" ' . Iif($article['is_show'] == '-1', 'SELECTED') . '>待审</option><option style="color:red;" value="0" ' . Iif($article['is_show'] == '0', 'SELECTED') . '>禁用</option></select>',
					'<select name="is_bests[]"><option value="0">否</option><option style="color:orange;" value="1" ' . Iif($article['is_best'], 'SELECTED', '') . '>是</option></select>',
					$article['clicks'],
					DisplayDate($article['created']),
					Iif($article['is_show'] == '1', '<a href="' . URL('articles?id=' . $article['a_id']) . '" target="_blank"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>', '<img src="' . SYSDIR . 'public/admin/images/disview.gif">'),
					'<input type="checkbox" name="deletea_ids[]" value="' . $article['a_id'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
			if($totalpages > 1){
				TableRow(GetPageList(BURL('articles'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id, 't', $time, 'type', $type));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updatearticles" value="保存更新" class="save"><input type="submit" name="deletearticles" onclick="'.Confirm('确定删除所选文章吗?<br><br><span class=red>注: 所选文章及其评论将被永久删除!</span>', 'form').'" value="删除文章" class="cancel"></div></form>';
	}

	public function updatearticles(){
		$this->CheckAction('articles'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updatearticles')){
			$a_ids = $_POST['a_ids'];
			$sorts   = $_POST['sorts'];
			$is_shows   = $_POST['is_shows'];
			$is_bests   = $_POST['is_bests'];
			for($i = 0; $i < count($a_ids); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET sort = '". ForceInt($sorts[$i])."',
				is_show = '". ForceInt($is_shows[$i])."',
				is_best = '". ForceInt($is_bests[$i])."'
				WHERE a_id = '". ForceInt($a_ids[$i])."'");
			}
		}else{
			$a_ids = $_POST['deletea_ids'];
			for($i=0; $i<count($a_ids); $i++){
				$this->DeleteArticleByID(ForceInt($a_ids[$i]));
			}
		}
		Success('articles?p=' . $page);
	}

	//$search 查询的关键字 $type时间条件 大于小于什么的  $time时间 $cat_id文章分类的id
	private function GetSearchSql($search, $type, $time, $cat_id){
		$Where = "";

		if($cat_id > 0){
			$Where .= ' cat_id=' . $cat_id;
		}elseif($cat_id == '-1'){
			$Where .= " is_show='-1' ";
		}elseif($cat_id == '-2'){
			$Where .= " is_show=0 ";
		}

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " (a_id = '$search' OR userid = '$search') "; //是数字时按文章或用户ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (title like '%$search%' OR username like '%$search%' OR title_en like '%$search%' OR content like '%$search%' OR content_en like '%$search%' OR keywords like '%$search%' OR keywords_en like '%$search%') ";
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
		$a_id = ForceIntFrom('a_id');

		if($a_id){  //编辑时的
			SubMenu('文章管理', array(array('添加文章', 'articles/add'),array('编辑文章', 'articles/edit?a_id='.$a_id, 1),array('文章列表', 'articles')));
			$articles = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "article WHERE a_id = '$a_id'");
		}else{
			SubMenu('文章管理', array(array('添加文章', 'articles/add', 1),array('文章列表', 'articles')));

			$articles = array('is_best' => 0, 'is_show' => 1);
		}

		echo '<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/kindeditor.js"></script>
		<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/lang/zh_CN.js"></script>
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
		<form id="editorform88" name="editorform88" method="post" action="'.BURL('articles/save').'">
		<input type="hidden" name="a_id" value="' . $a_id . '">';

		if($a_id){
			TableHeader('编辑文章: <span class=note>' . $articles['title'] . '</span>');

			TableRow(array('<B>是否删除?</B>', '<input type="checkbox" name="deletethisarticle" value="1"> <b>是:</b> <font class=redb>慎选!</font> <span class=light>如果选择删除, 此文章相关的所有信息将被删除.</span><a title="编辑 '.$articles['username'].'" href="'.BURL('users/edit?userid='.$articles['userid']).'"><img src="' . GetAvatar($articles['userid']) . '" class="user_avatar wh30" style="margin-left:60px"></a><a href="'.BURL('pm/send?userid='.$articles['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信给作者: ' . $articles['username'] . '"></a>&nbsp;&nbsp;&nbsp;&nbsp;' . Iif($articles['is_show'] == '1', '<a href="' . URL('articles?id=' . $a_id) . '" target="_blank" title="浏览文章"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>', '<img src="' . SYSDIR . 'public/admin/images/disview.gif" title="待审或已禁用中">')));
		}else{
			TableHeader('添加文章');
		}

		TableRow(array('<B>文章标题(<span class=blue>中文</span>):</B>', '<input type="text" name="title" value="' . $articles['title'] . '"  size="50"> <font class=red>* 必填项</font>'));
		TableRow(array('<B>文章标题(<span class=red>英文</span>):</B>', '<input type="text" name="title_en" value="' . $articles['title_en'] . '"  size="50">'));

		TableRow(array('<B>文章分类:</B>', '<input type="hidden" name="oldcat_id" value="' . $articles['cat_id'] . '">'.$this->GetSelect($articles['cat_id']) . ' <font class=red>* 必填项</font>'));

		if($a_id){
			TableRow(array('<B>排序编号:</B>', '<input type="text" name="sort" value="' . $articles['sort'] . '"  size="10"> <span class=light>注: 当前分类中文章将按此编号排序.</span>'));
		}

		$Radio = new SRadio;
		$Radio->Name = 'is_show';
		$Radio->SelectedID = $articles['is_show'];
		$Radio->AddOption(1, '发布', '&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(-1, '待审', '&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(0, '禁用', '&nbsp;&nbsp;');

		TableRow(array('<b>发布状态?</b>', $Radio->Get()));

		TableRow(array('<B>是否推荐?</B>', '<input type="checkbox" name="is_best" value="1" '.Iif($articles['is_best'] == 1, 'CHECKED').'> <b>是:</b> <span class=light>推荐的文章显示在重要的位置.</span>'));

		TableRow(array('<B>Meta关键字(<span class=blue>中文</span>):</B>', '<input type="text" name="keywords" value="' . $articles['keywords'] . '" size="50"> <span class=light>注: 文章的Meta关键字, <span class=note>便于搜索引擎收录, 请用英文逗号隔开</span>.</span>'));
		TableRow(array('<B>Meta关键字(<span class=red>英文</span>):</B>', '<input type="text" name="keywords_en" value="' . $articles['keywords_en'] . '" size="50"> <span class=light>注: 同上</span>'));

		TableRow(array('<B>文章正文:</B><BR><span class=light>文章的详细内容.</span>', '
			<div class="ok_tab">
				<div class="ok_tabheader">
					<ul id="tabContent-li-ok_tabOn-">
						<li class="ok_tabOn"><a href="javascript:void(0)" title="中文内容" rel="1" hidefocus="true">中文内容</a></li>
						<li><a href="javascript:void(0)" title="英文内容" rel="2" hidefocus="true">英文内容</a></li>
					</ul>
				</div>
				<div id="tabContent_1" class="tabContent">
				 <textarea name="content" style="width:100%;height:400px;visibility:hidden;" id="content">'.$articles['content'].'</textarea>
				</div>

				<div id="tabContent_2" class="tabContent" style="display: none;">
				<textarea name="content_en" style="width:100%;height:400px;visibility:hidden;" id="content_en">'.$articles['content_en'].'</textarea>
				</div>

				<div class="ok_tabbottom">
					<span class="tabbottomL"></span>
					<span class="tabbottomR"></span>
				</div>
			</div>
			<script type="text/javascript">new tab(\'tabContent-li-ok_tabOn-\', \'-\');</script>
		'));

		TableRow(array('<B>链接URL(<span class=blue>中文</span>):</B>', '<input type="text" name="linkurl" value="' . $articles['linkurl'] . '"  size="50" /> <span class=light>注: 如填写, 点击文章标题时将打开此链接而不显示以上详细内容.</span>'));
		TableRow(array('<B>链接URL(<span class=red>英文</span>):</B>', '<input type="text" name="linkurl_en" value="' . $articles['linkurl_en'] . '"  size="50" /> <span class=light>注: 同上.</span>'));

		TableFooter();

		PrintSubmit(Iif($a_id, '保存更新', '添加文章'));
	}

	public function save(){
		$this->CheckAction('articles'); //权限验证

		$a_id = ForceIntFrom('a_id');

		$is_show = ForceIntFrom('is_show');
		$is_best = ForceIntFrom('is_best');
		$sort = ForceIntFrom('sort');
		$cat_id = ForceIntFrom('cat_id');
		$oldcat_id = ForceIntFrom('oldcat_id');

		$title = ForceStringFrom('title');
		$title_en = ForceStringFrom('title_en');
		if(!$title_en) $title_en = 'No English Title!';

		$linkurl = ForceStringFrom('linkurl');
		$linkurl_en = ForceStringFrom('linkurl_en');
		$keywords = ForceStringFrom('keywords');
		$keywords_en = ForceStringFrom('keywords_en');
		$content = ForceStringFrom('content');
		$content_en = ForceStringFrom('content_en');
		
		$deletethisarticle     = ForceIntFrom('deletethisarticle');

		if($deletethisarticle AND $a_id){//删除文章
			$this->DeleteArticleByID($a_id);

			Success('articles');
		}

		if(!$title) $errors[] = '文章的中文标题不能为空！';
		if(!$cat_id)	$errors[] = '您没有选择文章分类！';

		if(isset($errors))	Error($errors, Iif($a_id, '编辑文章错误', '添加文章错误'));

		if($a_id){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET 
			sort= '$sort',
			cat_id= '$cat_id',
			is_show= '$is_show',
			is_best= '$is_best',
			title = '$title',
			title_en = '$title_en',
			linkurl = '$linkurl',
			linkurl_en = '$linkurl_en',
			content = '$content',
			content_en = '$content_en',
			keywords = '$keywords',
			keywords_en = '$keywords_en'
			WHERE a_id = ".$a_id);
			if($oldcat_id != $cat_id){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts+1) WHERE cat_id = '$cat_id'");
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts-1) WHERE cat_id = '$oldcat_id'");
			}

			Success('articles/edit?a_id=' . $a_id);
		}else{
			$time = time();
			$username = $this->admin->data['nickname'];
			$userid = $this->admin->data['userid'];

			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "article (cat_id, is_show, is_best, userid, username, title, title_en, linkurl, linkurl_en, content, content_en, keywords, keywords_en, clicks, created) VALUES ('$cat_id', '$is_show', '$is_best', '$userid', '$username', '$title', '$title_en', '$linkurl', '$linkurl_en', '$content', '$content_en', '$keywords', '$keywords_en', '0', '$time') ");

			$lastid = APP::$DB->insert_id;
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET sort = '$lastid' WHERE a_id = '$lastid'");
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts+1) WHERE cat_id = '$cat_id'");

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET a_num = (a_num+1) WHERE userid = '$userid'");

			Success('articles/edit?a_id=' . $lastid);
		}
	}

	private function GetSelect($selectedid =0, $selectname = 'cat_id'){
		$sReturn = '<select name="' . $selectname . '"><option value="0">-- 请选择 --</option>';
		$categories = APP::$DB->getAll("SELECT cat_id, p_id, name, name_en, counts  FROM " . TABLE_PREFIX . "acat ORDER BY sort");
		$sReturn .= $this->GetOptions($categories, $selectedid);
		$sReturn .= '</select>';

		return $sReturn;
	}

	private function GetOptions($categories, $selectedid = 0, $p_id = 0, $sublevelmarker = ''){
		if($p_id) $sublevelmarker .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		foreach($categories as $value){
			if($p_id == $value['p_id']){
				$sReturn .= '<option '. Iif(!$p_id, 'style="color:#cc4911;font-weight:bold;"') . ' value="' . $value['cat_id'] . '" ' . Iif($selectedid == $value['cat_id'], 'SELECTED', '') . '>' . $sublevelmarker . $value['name'] . '-' . $value['name_en'] . ' (' . $value['counts'] . ')</option>';

				$sReturn .= $this->GetOptions($categories, $selectedid, $value['cat_id'], $sublevelmarker);
			}
		}

		return $sReturn;
	}

	//按文章ID删除文章
	private function DeleteArticleByID($a_id){
		$article = APP::$DB->getOne("SELECT cat_id, userid FROM " . TABLE_PREFIX . "article WHERE a_id = '$a_id'");
		if(!$article) return;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."article where a_id = '$a_id'");
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts-1) WHERE cat_id = '$article[cat_id]'");

		//更新用户的文章数
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET a_num = (a_num-1) WHERE userid = '$article[userid]'");

		//删除评论及更新用户的评论数
		$getcomments = APP::$DB->query("SELECT userid FROM " . TABLE_PREFIX . "comment WHERE for_id = '$a_id' AND type = 0");
		while($comment = APP::$DB->fetch($getcomments)){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET ac_num = (ac_num-1) WHERE userid = '$comment[userid]'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE for_id = '$a_id' AND type = 0");
	}

}

?>