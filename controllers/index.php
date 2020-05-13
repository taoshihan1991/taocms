<?php if(!defined('ROOT')) die('Access denied.');

class c_index extends SWeb{

    public function index(){

		$this->assign('title',$this->title); //首页标题
		$id = ForceIntFrom('id', 1); //当前常态内容调用ID
		$about = GetContent($id);
		$this->assign('about', $about);



		// $this->assign('news', GetNews(9)); //分配9条最新站内新闻
		$this->assign('newproducts', GetNewProducts(8)); //分配8个最新产品
		$this->assign('recommends', GetNewProducts(8, 1)); //分配个推荐产品

		$this->display('index.html');
	} 


}

?>