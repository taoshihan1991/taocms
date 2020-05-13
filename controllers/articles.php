<?php if(!defined('ROOT')) die('Access denied.');

class c_articles extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		$this->id = ForceIntFrom('id'); //当前文章ID
		$this->cat = ForceIntFrom('cat'); //当前分类ID
	}

    public function index(){
		$this->pagenav = GetNavLinks(array($this->langs['articles'] => 'articles')); //默认导航栏

		//如果有文章ID则显示文章, 其它情况显示分类
		if($this->id){
			$this->show_article();
		}else{
			$this->show_category();
		}
	}

	//按分类ID获得多级导航分类链接
	private function GetCategorylinks($cat){
		$sReturn = $this->langs['nav'].'<a href="'.URL('articles?cat=' . $cat).'">'.ShortTitle($this->acategories[$cat]['name'], 36).'</a>';

		if($this->acat_ids[$cat]){//如果有父分类
			$sReturn = $this->GetCategorylinks($this->acat_ids[$cat]) . $sReturn;
		}

		return $sReturn;
	}

	//显示文章
    private function show_article(){
		$id = $this->id; //当前文章ID

		$article_sql = "SELECT a_id, cat_id, is_best, userid, username, clicks, created, ";
		if(IS_CHINESE){
			$article_sql .= " title, keywords, linkurl, content ";
		}else{
			$article_sql .= " title_en AS title, keywords_en AS keywords, linkurl_en AS linkurl, content_en AS content ";
		}

		$article = APP::$DB->getOne($article_sql . " FROM " . TABLE_PREFIX . "article WHERE is_show = 1 AND a_id='$id'");

		if(!$article OR !in_array($article['cat_id'], $this->acats_ok)) Error($this->langs['er_noarticle']); //错误信息, 不自动关闭

		if($article['linkurl']){//如果有链接则跳转
			header("Location: $article[linkurl]");
			exit();
		}

		$cat = $article['cat_id'];//当前文章的分类ID 

		$this->assign('description',  $article['keywords'] . ','. $this->description);
		$this->assign('keywords',  $article['keywords'] . ','. $this->keywords);
		$this->assign('title', $article['title'] . ' - ' . $this->acategories[$cat]['name'] . ' - ' . $this->langs['articles'] . ' - ' . $this->title); //标题

		$this->assign('article', $article); //分配文章

		add_clicks($id, 'article'); //增加点击次数

		//获取评论
		// $page = ForceIntFrom('p', 1); //当前页
		// $NumPerPage = 10;
		// $start = $NumPerPage * ($page-1);

		// $Where = "type = 0 AND actived = 1 AND lang = '" . IS_CHINESE . "' AND for_id = '$id'";
		// $comments = APP::$DB->getAll("SELECT * FROM " . TABLE_PREFIX . "comment WHERE $Where ORDER BY created ASC LIMIT $start, $NumPerPage");
		// $maxrows = APP::$DB->getOne("SELECT COUNT(c_id) AS value FROM " . TABLE_PREFIX . "comment WHERE $Where");
		// $totalpages = ceil($maxrows['value'] / $NumPerPage);

		// $this->assign('start', $start);
		// $this->assign('comments', $comments);
		// $this->assign('comms', $maxrows['value']); //评论数
		// $this->assign('pagelist', GetPageList(URL('articles'), $totalpages, $page, 10, 'id', $id . '#c'));

		//获取当前文章及其分类的导航栏链接
		$this->pagenav .= $this->GetCategorylinks($cat) . $this->langs['nav'] . "<a href='" . URL("articles?id=$id") . "'>$article[title]</a>";

		// $this->assign('products', GetNewProducts(10)); //分配最新产品
		// $this->assign('articles', GetNewArticles(10, 1)); //分配推荐文章
		$this->assign('pagenav', $this->pagenav); //分配导航栏

		$this->display('new.html');
	}

	//显示文章分类
    private function show_category(){
		$cat = $this->cat; //当前分类ID

		$page = ForceIntFrom('p', 1); //当前页
		$NumPerPage = 10;   //每页显示的文章数量
		$start = $NumPerPage * ($page-1);  //分页的每页起始位置

		$re = Iif(IsGet("re"), 1); //推荐
		$sTitle = $this->langs['articles'] . ' - ' . $this->title; //默认页面标题

		//根据搜索或文章分类生成附加的SQL
		$all_categories = implode(',', $this->acats_ok); //获取所有未隐藏分类及下级分类的SQL
		if($all_categories){
			$special_sql = " AND cat_id IN ($all_categories) ";
		}else{
			Error($this->langs['er_noarticles']); //所有分类均无效时, 肯定没有任何文章直接输出错误信息
		}

		if($cat AND in_array($cat, $this->acats_ok)){

			if($this->acategories[$cat]['show_sub'] AND in_array($cat, $this->acat_ids)){ //当分类设置成显示下级分类文章且有下级分类时
				$sub_categorysql = GetSubArtcats($cat); //获取所以下级分类的SQL
			}

			if($sub_categorysql){
				$special_sql = " AND cat_id IN (". $cat . $sub_categorysql. ") ";
			}else{
				$special_sql = " AND cat_id = $cat ";
			}

			$sTitle = $this->acategories[$cat]['name'] . ' - ' . $sTitle; //分类名是页面标题

			$this->pagenav .= $this->GetCategorylinks($cat); //获取当前分类的导航栏链接(包括父分类)

			//重新分配页面描述和关键字
			$this->assign('catname', $this->acategories[$cat]['name']);
			$this->assign('description',  $this->acategories[$cat]['keywords'] . ','. $this->description);
			$this->assign('keywords',  $this->acategories[$cat]['keywords'] . ','. $this->keywords);
		}

		$articles_sql = "SELECT a_id, cat_id, is_best, userid, username, clicks, created, "; //分类文章
		if(IS_CHINESE){
			$articles_sql .= " title, linkurl ";
		}else{
			$articles_sql .= " title_en AS title, linkurl_en AS linkurl ";
		}

		if($re) $special_sql .= " AND is_best = 1 "; //推荐文章

		$articles = APP::$DB->getAll($articles_sql . " FROM " . TABLE_PREFIX . "article WHERE is_show = 1 " . $special_sql . " ORDER BY sort DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(a_id) AS value FROM " . TABLE_PREFIX . "article WHERE is_show = 1 " . $special_sql);
		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		//if(!$articles) Error($this->langs['er_noarticles']); //错误信息

		$this->assign('articles', $articles); //分配分类文章
		$this->assign('start', $start);
		$this->assign('pagelist', GetPageList(URL('articles'), $totalpages, $page, 10, 'cat', $cat, 're', $re)); //类别分页

		if($re){
			$sTitle = $this->langs['rearticles'] . " - $sTitle";
			$this->pagenav = $this->pagenav . $this->langs['nav'] . '<a href="' . URL('articles?re' . Iif($cat, "&cat=$cat")) . '">' . $this->langs['rearticles'] . '</a></span>';
		}

		$this->assign('title', $sTitle); //分类标题
		$this->assign('pagenav', $this->pagenav); //分配导航栏
		//$this->assign('re_articles', GetNewArticles(10, 1, $cat)); //分配当前分类下的推荐文章

		$this->display('news.html');
	} 

}

?>