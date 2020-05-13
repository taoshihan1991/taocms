<?php if(!defined('ROOT')) die('Access denied.');

class c_search extends SWeb{

	public function index(){
		$keyword = ForceStringFrom('s');
		if(!$keyword OR strlen($keyword) < 2) Error($this->langs['search_err']);

		$keyword = SafeSearchSql(Iif(IsGet('s'), urldecode($keyword), $keyword)); //安全过滤
		$keywords = explode (' ', str_replace(array('+', ',' , ';'), ' ', $keyword));
		if(IS_CHINESE){
			$s1 = "title";
			$s2 = "keywords";
			$s3 = "content";

			$p_sql = " price, title, 0 AS linkurl ";
			$a_sql = " 0 AS price, title, linkurl ";
		}else{
			$s1 = "title_en";
			$s2 = "keywords_en";
			$s3 = "content_en";

			$p_sql = " price_en AS price, title_en AS title, 0 AS linkurl ";
			$a_sql = " 0 AS price, title_en AS title, linkurl_en AS linkurl ";
		}

		foreach ($keywords AS $key) {
			if($key) $where .= " AND (username LIKE '%".$key."%' OR $s1 LIKE '%".$key."%' OR $s2 LIKE '%".$key."%' OR $s3 LIKE '%".$key."%') ";
		}

		$page = ForceIntFrom('p', 1); //当前页
		$NumPerPage = 10;   //每页显示的产品数量
		$start = $NumPerPage * ($page-1);  //分页的每页起始位置

		$results = APP::$DB->getAll("SELECT pro_id, cat_id, is_best, userid, username, path, filename, clicks, created, 1 AS type, " . $p_sql . " FROM " . TABLE_PREFIX . "product WHERE is_show = 1 " . $where);
		//. " UNION ALL 
		//SELECT a_id, cat_id, is_best, userid, username, 0 AS path, 0 AS filename, clicks, created, 0 AS type, " . $a_sql . " FROM " . TABLE_PREFIX . "article WHERE is_show = 1 " . $where . " ORDER BY created DESC LIMIT $start, $NumPerPage");

		// $alls = APP::$DB->query("SELECT pro_id FROM " . TABLE_PREFIX . "product WHERE is_show = 1 " . $where
		// . " UNION ALL 
		// SELECT a_id FROM " . TABLE_PREFIX . "article WHERE is_show = 1 " . $where);
		$total = APP::$DB->result_nums;
		$totalpages = ceil($total / $NumPerPage);

		$results_info = str_replace('//1', "<font class=orangeb>$total</font>", $this->langs['results']);
		
		$this->assign('results', $results); //搜索结果
		$this->assign('results_info', $results_info); //搜索结果总数
		$this->assign('keyword', $keyword); //分配搜索关键词给header.html
		$this->assign('pagelist', GetPageList(URL('search'), $totalpages, $page, 10, 's', urlencode($keyword))); //搜索分页
		
		$this->assign('title', $this->langs['search'] . ' - ' . $this->title); //标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['search'] => 'search?s=' . urlencode($keyword), $results_info))); //分配导航栏
		$this->assign('products', $results); //分配随机产品

		$this->display('products.html');
	}

}

?>