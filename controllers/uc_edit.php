<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_edit extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');
	}

    public function index(){
		$this->assign('title', $this->langs['u_edit'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_edit'] => 'uc_edit'))); //分配导航栏


		$this->assign('submenu', UCSub($this->langs['u_edit'])); //标题及二级菜单
		$this->display('uc_edit.html');
	}
}

?>