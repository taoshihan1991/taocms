<?php if(!defined('ROOT')) die('Access denied.');

class c_news extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		$this->id = ForceIntFrom('id'); //当前新闻ID
	}

    public function index(){
		//如果有新闻ID则显示新闻, 其它情况所有新闻
		if($this->id){
			$this->show_new();
		}else{
			$this->show_list();
		}
	}

	//显示新闻
    private function show_new(){
		$id = $this->id; //当前新闻ID

		if(IS_CHINESE){
			$news_sql = "SELECT n_id, title, linkurl, keywords, content, clicks, created ";
			$prev_next_sql = " title, linkurl ";
		}else{
			$news_sql = "SELECT n_id, title_en AS title, linkurl_en AS linkurl, keywords_en AS keywords, content_en AS content, clicks, created ";
			$prev_next_sql = " title_en AS title, linkurl_en AS linkurl ";
		}

		$news = APP::$DB->getOne($news_sql . " FROM " . TABLE_PREFIX . "news WHERE is_show = 1 AND n_id='$id'");

		if(!$news) Error($this->langs['er_nonew']); //错误信息, 不自动关闭

		if($news['linkurl']){//如果新闻有链接则跳转
			header("Location: $news[linkurl]");
			exit();
		}

		//获取上一个和下一个新闻
		$prev_news = APP::$DB->getOne("SELECT n_id, " . $prev_next_sql . " FROM " . TABLE_PREFIX . "news WHERE is_show = 1 AND n_id > '$id' ORDER BY n_id ASC");
		$next_news = APP::$DB->getOne("SELECT n_id, " . $prev_next_sql . " FROM " . TABLE_PREFIX . "news WHERE is_show = 1 AND n_id < '$id' ORDER BY n_id DESC");

		$this->assign('description',  $news['keywords'] . ','. $this->description);
		$this->assign('keywords',  $news['keywords'] . ','. $this->keywords);
		$this->assign('title', $news['title'] . ' - ' . $this->langs['news'] . ' - ' . $this->title); //标题

		$this->assign('news', $news); //分配新闻
		$this->assign('prev_news', $prev_news); //上一个新闻
		$this->assign('next_news', $next_news); //下一个新闻

		add_clicks($id, 'news'); //增加点击次数

		$this->assign('products', GetNewProducts(10, 2)); //分配随机产品
		$this->assign('pagenav', GetNavLinks(array($this->langs['news'] => 'news', $news['title']))); //分配导航栏

		$this->display('new.html');
	}

	//显示全部新闻
    private function show_list(){
		$page = ForceIntFrom('p', 1); //当前页
		$NumPerPage = 10;   //每页显示的新闻数量
		$start = $NumPerPage * ($page-1);  //分页的每页起始位置

		$this->assign('title', $this->langs['news'] . ' - ' . $this->title); //分配标题

		if(IS_CHINESE){
			$news_sql = "SELECT n_id, title, linkurl, clicks, created ";
		}else{
			$news_sql = "SELECT n_id, title_en AS title, linkurl_en AS linkurl, clicks, created ";
		}

		$news = APP::$DB->getAll($news_sql . " FROM " . TABLE_PREFIX . "news WHERE is_show = 1 ORDER BY sort DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(n_id) AS value FROM " . TABLE_PREFIX . "news WHERE is_show = 1");
		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		if(!$news) Error($this->langs['er_nonews']); //错误信息, 不自动关闭

		$this->assign('news', $news); //分配新闻
		$this->assign('start', $start);
		$this->assign('pagelist', GetPageList(URL('news'), $totalpages, $page, 10)); //类别分页

		$this->assign('newproducts', GetNewProducts()); //分配最新产品
		$this->assign('pagenav', GetNavLinks(array($this->langs['news'] => 'news'))); //分配导航栏

		$this->display('news.html');
	} 

}

?>