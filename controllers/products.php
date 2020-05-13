<?php if(!defined('ROOT')) die('Access denied.');

class c_products extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		$this->id = ForceIntFrom('id'); //当前产品ID
		$this->cat = ForceIntFrom('cat'); //当前分类ID
	}

    public function index(){
		$this->pagenav = GetNavLinks(array($this->langs['products'] => 'products')); //默认导航栏

		//如果有产品ID则显示产品, 其它情况显示分类
		if($this->id){
			$this->show_product();
		}else{
			$this->show_category();
		}
	}

	//按分类ID获得多级导航分类链接
	private function GetCategorylinks($cat){
		$sReturn = $this->langs['nav'].'<a href="'.URL('products?cat=' . $cat).'">'.ShortTitle($this->pcategories[$cat]['name'], 36).'</a>';
		
		if($this->pcat_ids[$cat]){//如果有父分类
			$sReturn = $this->GetCategorylinks($this->pcat_ids[$cat]) . $sReturn;
		}
		
		return $sReturn;
	}

	//显示产品
    private function show_product(){
		$id = $this->id; //当前产品ID

		$product_sql = "SELECT pro_id, cat_id, is_best, userid, username, path, filename, clicks, created, ";
		if(IS_CHINESE){
			$product_sql .= " price, title, keywords, content ";
		}else{
			$product_sql .= " price_en AS price, title_en AS title, keywords_en AS keywords, content_en AS content ";
		}

		$product = APP::$DB->getOne($product_sql . " FROM " . TABLE_PREFIX . "product WHERE is_show = 1 AND pro_id='$id'");

		if(!$product OR !in_array($product['cat_id'], $this->pcats_ok)) Error($this->langs['er_noproduct']); //错误信息, 不自动关闭

		$cat = $product['cat_id'];//当前产品的分类ID 

		//获得组图片, 使用图片延迟加载技术, src改成original或hide
		$getgimages = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "gimage  WHERE is_show = 1 AND pro_id = '$id' ORDER BY g_id ASC");
		
		// $step = 0;
		// $counts = 2; //前台加一个主图片, 从2开始, 每次显示8个图片
		$gimages = array(GetImageURL($product['path'], $product['filename'],3));

		while($gimage = APP::$DB->fetch($getgimages)){
//			if($counts == 1) $gimages .= '<ul class="lev_brandUL">';
			$gimages[]=GetImageURL($gimage['path'], $gimage['filename'],3);
//			$gimages .= '<div class="z"><div class="x" step="' . $step . '"><div class="i_thumb_1"><table><tr><td><img ' . Iif($step, 'hide', 'original') . '="' . GetImageURL($gimage['path'], $gimage['filename']) . '"></td></tr></table></div></div></div>';

			// if($counts == 8){
			// 	$gimages .= '</ul>';
			// 	$step += 1;
			// 	$counts = 1;
			// }else{
			// 	$counts += 1;
			// }
		}
		//var_dump($gimages);die;
		// if($gimages){
		// 	$gimages = '<ul class="lev_brandUL"><div class="z"><div class="x now" now="1" step="0"><div class="i_thumb_1"><table><tr><td><img original="' . GetImageURL($product['path'], $product['filename']) . '"></td></tr></table></div></div></div>' . $gimages;

		// 	if(substr($gimages, -5) != "</ul>") $gimages .= '</ul>'; //如果没有</ul>封闭加上
		// }

		$this->assign('description',  $product['keywords'] . ','. $this->description);
		$this->assign('keywords',  $product['keywords'] . ','. $this->keywords);
		$this->assign('title', $product['title'] . ' - ' . $this->pcategories[$cat]['name'] . ' - ' . $this->langs['products'] . ' - ' . $this->title); //标题
		$this->assign('product', $product); //分配产品
		$this->assign('gimages', $gimages); //分配组图片

		add_clicks($id); //增加点击次数

		//获取评论
		// $page = ForceIntFrom('p', 1); //当前页
		// $NumPerPage = 10;
		// $start = $NumPerPage * ($page-1);

		// $Where = "type = 1 AND actived = 1 AND lang = '" . IS_CHINESE . "' AND for_id = '$id'";
		// $comments = APP::$DB->getAll("SELECT * FROM " . TABLE_PREFIX . "comment WHERE $Where ORDER BY created ASC LIMIT $start, $NumPerPage");
		// $maxrows = APP::$DB->getOne("SELECT COUNT(c_id) AS value FROM " . TABLE_PREFIX . "comment WHERE $Where");
		// $totalpages = ceil($maxrows['value'] / $NumPerPage);

		// $this->assign('start', $start);
		// $this->assign('comments', $comments);
		// $this->assign('comms', $maxrows['value']); //评论数
		// $this->assign('pagelist', GetPageList(URL('products'), $totalpages, $page, 10, 'id', $id . '#c'));

		//获取当前产品及其分类的导航栏链接
		$this->pagenav .= $this->GetCategorylinks($cat) . $this->langs['nav'] . "<a href='" . URL("products?id=$id") . "'>$product[title]</a>";
		$this->assign('products', GetNewProducts(4, 2,$cat)); //分配推荐产品
		//$this->assign('articles', GetNewArticles(10)); //分配最新文章
		$this->assign('pagenav', $this->pagenav); //分配导航栏

		$this->display('product.html');
	}

	//显示产品分类
    private function show_category(){
		$cat = $this->cat; //当前分类ID
		$page = ForceIntFrom('p', 1); //当前页
		$NumPerPage = 20;   //每页显示的产品数量
		$start = $NumPerPage * ($page-1);  //分页的每页起始位置

		$re = Iif(IsGet("re"), 1); //推荐
		$sTitle = $this->langs['products'] . ' - ' . $this->title; //默认页面标题

		//根据产品分类生成附加的SQL
		$all_categories = implode(',', $this->pcats_ok); //获取所有未隐藏分类及下级分类的SQL
		if($all_categories){
			$special_sql = " AND cat_id IN ($all_categories) ";
		}else{
			Error($this->langs['er_noproducts']); //所有分类均无效时, 肯定没有任何产品直接输出错误信息
		}

		if($cat AND in_array($cat, $this->pcats_ok)){

			if($this->pcategories[$cat]['show_sub'] AND in_array($cat, $this->pcat_ids)){ //当分类设置成显示下级分类产品且有下级分类时
				$sub_categorysql = GetSubProcats($cat); //获取所以下级分类的SQL
			}

			if($sub_categorysql){
				$special_sql = " AND cat_id IN (". $cat . $sub_categorysql. ") ";
			}else{
				$special_sql = " AND cat_id = $cat ";
			}

			$sTitle = $this->pcategories[$cat]['name'] . ' - ' . $sTitle; //分类名是页面标题

			$this->pagenav .= $this->GetCategorylinks($cat); //获取当前分类的导航栏链接(包括父分类)

			//重新分配页面描述和关键字
			$this->assign('description',  empty($this->pcategories[$cat]['keywords']) ? $this->pcategories[$cat]['name'].','.$this->title:$this->pcategories[$cat]['keywords']  . ','. $this->description);
			$this->assign('keywords',  empty($this->pcategories[$cat]['keywords'])? $this->pcategories[$cat]['name'].','.$this->title:$this->pcategories[$cat]['keywords']. ','. $this->keywords);
		}

		$products_sql = "SELECT pro_id, cat_id, is_best, userid, username, path, filename, clicks, created, "; //分类产品
		if(IS_CHINESE){
			$products_sql .= " price, title ";
		}else{
			$products_sql .= " price_en AS price, title_en AS title ";
		}

		if($re) $special_sql .= " AND is_best = 1 "; //推荐产品

		$products = APP::$DB->getAll($products_sql . " FROM " . TABLE_PREFIX . "product WHERE is_show = 1 " . $special_sql . " ORDER BY sort DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(pro_id) AS value FROM " . TABLE_PREFIX . "product WHERE is_show = 1 " . $special_sql);
		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		//if(!$products) Error($this->langs['er_noproducts']); //错误信息

		$this->assign('products', $products); //分配分类产品
		$this->assign('pagelist', GetPageList(URL('products'), $totalpages, $page, 10, 'cat', $cat, 're', $re)); //类别分页

		if($re){
			$sTitle = $this->langs['reproducts'] . " - $sTitle";
			$this->pagenav = $this->pagenav . $this->langs['nav'] . '<a href="' . URL('products?re' . Iif($cat, "&cat=$cat")) . '">' . $this->langs['reproducts'] . '</a></span>';
		}
		$this->pagenav.="";
		$this->assign('title', $sTitle); //分类标题
		$this->assign('pagenav', $this->pagenav); //分配导航栏

		$this->display('products.html');
	} 

}

?>