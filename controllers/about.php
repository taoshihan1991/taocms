<?php if(!defined('ROOT')) die('Access denied.');

class c_about extends SWeb{

	public function index(){
		$id = ForceIntFrom('id', 1); //当前常态内容调用ID

		$content = GetContent($id);
		if(!$content) Error($this->langs['er_info'], '', 8); //错误信息, 8秒后自动关闭

		$this->assign('description',  $content['keywords'] . ','. $this->description);
		$this->assign('keywords',  $content['keywords'] . ','. $this->keywords);
		$this->assign('title', $content['title'] . ' - ' . $this->title); //标题
		$this->assign('content', $content); //常态内容

		$this->assign('id', $id);

		//$this->assign('products', GetNewProducts(10, 2)); //分配随机产品
		$this->assign('pagenav', GetNavLinks(array($content['title'] => Iif($id==1, 'about', 'about?id=' . $id)))); //分配导航栏

		$this->display('about.html');
	}

}

?>