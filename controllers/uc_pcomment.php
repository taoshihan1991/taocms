<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_pcomment extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');

		$this->assign('title', $this->langs['u_pcomm'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_pcomm'] => 'uc_pcomment'))); //分配导航栏
	}

    public function index(){
		$this->assign('submenu', UCSub($this->langs['u_pcomm'], array(array($this->langs['u_pcomms'] . $this->langs['list'], 'uc_pcomment', 1))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$myid = $this->user->data['userid'];
		$del_c = $this->CheckAccess('del_c'); //是否允许删除

		$comments = APP::$DB->getAll("SELECT * FROM " . TABLE_PREFIX . "comment WHERE userid = '$myid' AND actived != 0 AND type = 1 ORDER BY actived ASC, created DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(c_id) AS value FROM " . TABLE_PREFIX . "comment WHERE userid = '$myid' AND actived != 0 AND type = 1");

		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		$this->assign('comments', $comments); //评论
		$this->assign('page', $page); //当前页号
		$this->assign('del_c', $del_c); //删除记录的权限
		$this->assign('pagelist', GetPageList(URL('uc_pcomment'), $totalpages, $page, 10)); //分页

		$this->display('uc_pcomment.html');
	}

	//删除
    public function delete(){
		$this->CheckAction('del_c'); //验证删除权限

		$deleteids = $_POST['deleteids'];
		$myid = $this->user->data['userid'];
		$nums = count($deleteids);
		for($i = 0; $i < $nums; $i++){
			$c_id = ForceInt($deleteids[$i]);

			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "comment WHERE c_id = '$c_id' AND userid = '$myid' AND type = 1");
		}

		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET pc_num = (pc_num - $nums) WHERE userid = '$myid'");

		//sys_user系统用户的统计数据在SWeb基类中已经分配, 更新之, 以便当前页面显示实际的数量
		$this->_tpl_vars['sys_user']['pc_num'] -= $nums;

		Success('', 1); //只是输出成功信息
		$this->index();
	}

}

?>