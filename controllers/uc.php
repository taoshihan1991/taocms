<?php if(!defined('ROOT')) die('Access denied.');

class c_uc extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');
	}

    public function index(){
		$this->assign('title', $this->langs['uc'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc'))); //分配导航栏

		$this->assign('s_pm', Iif($this->CheckAccess('pm'), 'yes', 'no'));
		$this->assign('s_pmdays', Iif($pmdays = $this->ActionValue('pmdays'), $pmdays, $this->langs['unlimited']));
		$this->assign('s_enquiry', Iif($this->CheckAccess('enquiry'), 'yes', 'no'));
		$this->assign('s_del_e', Iif($this->CheckAccess('del_e'), 'yes', 'no'));
		$this->assign('s_comment', Iif($this->CheckAccess('comment'), 'yes', 'no'));
		$this->assign('s_cRightnow', Iif($this->CheckAccess('cRightnow'), 'yes', 'no'));
		$this->assign('s_del_c', Iif($this->CheckAccess('del_c'), 'yes', 'no'));
		$this->assign('s_articles', Iif($this->CheckAccess('articles'), 'yes', 'no'));
		$this->assign('s_aRightnow', Iif($this->CheckAccess('aRightnow'), 'yes', 'no'));
		$this->assign('s_del_a', Iif($this->CheckAccess('del_a'), 'yes', 'no'));
		$this->assign('s_anum', Iif($anum = $this->ActionValue('anum'), $anum, $this->langs['unlimited']));
		$this->assign('s_products', Iif($this->CheckAccess('products'), 'yes', 'no'));
		$this->assign('s_pRightnow', Iif($this->CheckAccess('pRightnow'), 'yes', 'no'));
		$this->assign('s_del_p', Iif($this->CheckAccess('del_p'), 'yes', 'no'));
		$this->assign('s_pnum', Iif($pnum = $this->ActionValue('pnum'), $pnum, $this->langs['unlimited']));

		$this->assign('submenu', UCSub($this->langs['uc'])); //标题及二级菜单
		$this->display('uc.html');
	}
}

?>