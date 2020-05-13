<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_article extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');

		$this->assign('title', $this->langs['u_myart'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_myart'] => 'uc_article'))); //分配导航栏
	}

	private function GetOptions($selectedid = 0, $p_id = 0, $sublevelmarker = ''){
		if($p_id) $sublevelmarker .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		$cats = $this->acat_ids;
		foreach($cats AS $cat_id => $pid){
			if($p_id == $pid){
				$sReturn .= '<option '. Iif(!$p_id, 'style="color:#cc4911;font-weight:bold;"') . ' value="' . $cat_id . '" ' . Iif($selectedid == $cat_id, 'SELECTED', '') . '>' . $sublevelmarker . $this->acategories[$cat_id]['name'] . '</option>';

				$sReturn .= $this->GetOptions($selectedid, $cat_id, $sublevelmarker);
			}
		}

		return $sReturn;
	}

	//删除
    public function delete(){
		$this->CheckAction('del_a'); //验证删除权限
		$page = ForceIntFrom('p');   //页码

		$a_ids = $_POST['deleteids'];
		for($i=0; $i<count($a_ids); $i++){
			$this->DeleteArticleById(ForceInt($a_ids[$i]));
		}

		Success('uc_article?p=' . $page);
	}

    public function index(){
		$this->assign('submenu', UCSub($this->langs['u_myart'], array(array($this->langs['u_articles'] . $this->langs['list'], 'uc_article', 1), array($this->langs['u_adda'], 'uc_article/add'))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$myid = $this->user->data['userid'];
		$del_a = $this->CheckAccess('del_a'); //是否允许删除
		$add_a = Iif($this->CheckAccess('articles'), 1, 0); //是否允许添加, JS验证
		$anum = ForceInt($this->ActionValue('anum'));
		$morearts = Iif($add_a && $anum, $anum - $this->user->data['a_num']); //还可以添加多少文章

		$sql = "SELECT a_id, cat_id, is_show, is_best, userid, username, clicks, created, ";
		if(IS_CHINESE){
			$sql .= " title ";
		}else{
			$sql .= " title_en AS title ";
		}

		$articles = APP::$DB->getAll($sql . " FROM " . TABLE_PREFIX . "article WHERE userid = '$myid' ORDER BY is_show ASC, created DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(a_id) AS value FROM " . TABLE_PREFIX . "article WHERE userid = '$myid'");

		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		$this->assign('articles', $articles); //文章
		$this->assign('page', $page); //当前页号
		$this->assign('del_a', $del_a); //删除记录的权限
		$this->assign('add_a', $add_a); //添加文章权限
		$this->assign('morearts', $morearts);
		$this->assign('pagelist', GetPageList(URL('uc_article'), $totalpages, $page, 10)); //分页

		$this->display('uc_article.html');
	}


	//编辑调用add
	public function edit(){
		$this->add();
	}

	public function add(){
		$this->CheckAction('articles'); //验证添加权限

		$a_id = ForceIntFrom('a_id');
		$myid = $this->user->data['userid'];

		if($a_id){  //编辑时的
			$this->assign('submenu', UCSub($this->langs['u_myart'], array(array($this->langs['u_articles'] . $this->langs['list'], 'uc_article'), array($this->langs['u_edita'], 'uc_article/edit?a_id='.$a_id, 1), array($this->langs['u_adda'], 'uc_article/add'))));

			$article = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "article WHERE a_id = '$a_id' AND userid='$myid'");
			if(!$article) Error('er_noedita');
		}else{
			$anum = ForceInt($this->ActionValue('anum'));
			if($anum AND $anum - $this->user->data['a_num'] <= 0) Error($this->langs['er_anum']);	

			$this->assign('submenu', UCSub($this->langs['u_myart'], array(array($this->langs['u_articles'] . $this->langs['list'], 'uc_article'), array($this->langs['u_adda'], 'uc_article/add', 1))));

			$article = array('a_id' => 0);
		}

		$this->assign('del_a', $this->CheckAccess('del_a')); //是否允许删除
		$this->assign('a_id', $a_id);
		$this->assign('article', $article);
		$this->assign('cat_options', $this->GetOptions($article['cat_id']));

		$this->display('uc_article_edit.html');
	}


	public function save(){
		$this->CheckAction('articles'); //权限验证

		$a_id = ForceIntFrom('a_id');
		if($a_id) {
			$deletethisarticle = ForceIntFrom('deletethisarticle');

			if($deletethisarticle){//删除文章
				$this->CheckAction('del_a'); //验证删除权限
				$this->DeleteArticleById($a_id);
				Success('uc_article');
			}

			$is_show = ForceIntFrom('is_show');
			if(($is_show ==1 AND !$this->CheckAccess('aRightnow')) OR $is_show == 0){
				$is_show = '-1'; //状态改为待审
			}
		}else{
			$anum = ForceInt($this->ActionValue('anum'));
			if($anum AND $anum - $this->user->data['a_num'] <= 0) Error($this->langs['er_anum']);

			$is_show = Iif($this->CheckAccess('aRightnow'), 1, '-1');
		}

		$cat_id = ForceIntFrom('cat_id');
		$oldcat_id = ForceIntFrom('oldcat_id');

		$title = ForceStringFrom('title');
		$title_en = ForceStringFrom('title_en');
		$keywords = ForceStringFrom('keywords');
		$keywords_en = ForceStringFrom('keywords_en');
		$content = ForceStringFrom('content');
		$content_en = ForceStringFrom('content_en');
		$linkurl = ForceStringFrom('linkurl');
		$linkurl_en = ForceStringFrom('linkurl_en');

		if(IS_CHINESE){
			if(!$title) $errors[] = '文章的中文标题不能为空！';
			if(!$title_en) $title_en = 'No English Title!';
		}else{
			if(!$title_en) $errors[] = 'The title of article is required!';
			if(!$title) $title = '暂无中文标题!';
		}

		if(!$cat_id) $errors[] = $this->langs['er_noacat'];

		if(isset($errors))	Error($errors);

		$myid = $this->user->data['userid'];
		if($a_id){//编辑文章

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET 
			cat_id= '$cat_id',
			is_show= '$is_show',
			title = '$title',
			title_en = '$title_en',
			linkurl = '$linkurl',
			linkurl_en = '$linkurl_en',
			content = '$content',
			content_en = '$content_en',
			keywords = '$keywords',
			keywords_en = '$keywords_en'
			WHERE a_id = '$a_id' AND userid= '$myid'");

			if($oldcat_id != $cat_id){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts+1) WHERE cat_id = '$cat_id'");
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts-1) WHERE cat_id = '$oldcat_id'");
			}

			Success('uc_article/edit?a_id=' . $a_id);
		}else{//添加文章
			$time = time();
			$username = $this->user->data['nickname'];

			APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "article (cat_id, is_show, userid, username, title, title_en, linkurl, linkurl_en, content, content_en, keywords, keywords_en, created) VALUES ('$cat_id', '$is_show', '$myid', '$username', '$title', '$title_en', '$linkurl', '$linkurl_en', '$content', '$content_en', '$keywords', '$keywords_en', '$time') ");

			$lastid = APP::$DB->insert_id;
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET sort = '$lastid' WHERE a_id = '$lastid'");
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts+1) WHERE cat_id = '$cat_id'");

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET a_num = (a_num+1) WHERE userid = '$myid'");

			Success('uc_article/edit?a_id=' . $lastid);
		}
	}

	//按a_id删除文章
	private function DeleteArticleById($a_id) {
		$myid = $this->user->data['userid'];

		$article = APP::$DB->getOne("SELECT cat_id, userid FROM " . TABLE_PREFIX . "article WHERE a_id = '$a_id' AND userid='$myid'");
		if(!$article) return false;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."article where a_id = '$a_id'");
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "acat SET counts = (counts-1) WHERE cat_id = '$article[cat_id]'");

		//更新用户的文章数
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET a_num = (a_num-1) WHERE userid = '$myid'");

		//删除评论及更新用户的评论数
		$getcomments = APP::$DB->query("SELECT userid FROM " . TABLE_PREFIX . "comment WHERE for_id = '$a_id' AND type = 0");
		while($comment = APP::$DB->fetch($getcomments)){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET ac_num = (ac_num-1) WHERE userid = '$comment[userid]'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE for_id = '$a_id' AND type = 0");
	}

}

?>