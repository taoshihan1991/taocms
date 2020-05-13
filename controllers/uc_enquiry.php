<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_enquiry extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');

		$this->assign('title', $this->langs['u_myeq'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_myeq'] => 'uc_enquiry'))); //分配导航栏
	}

	//删除询价
    public function delete(){
		$this->CheckAction('del_e'); //验证删除权限

		$deleteids = $_POST['deleteids'];
		$myid = $this->user->data['userid'];
		$nums = count($deleteids);
		for($i = 0; $i < $nums; $i++){
			$e_id = ForceInt($deleteids[$i]);

			//验证这个询价是否属于自己, 防止非法操作删除别人的询价, 运行稍慢但更安全
			if(APP::$DB->getOne("SELECT e_id FROM " . TABLE_PREFIX . "enquiry WHERE e_id = '$e_id' AND refer_id = 0 AND userid = '$myid'")){
				//删除询价及所有回复
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."enquiry where e_id = '$e_id' OR refer_id = '$e_id'");
			}
		}

		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET q_num = (q_num - $nums) WHERE userid = '$myid'");

		//sys_user系统用户的统计数据在SWeb基类中已经分配, 更新之, 以便当前页面显示实际的数量
		$this->_tpl_vars['sys_user']['q_num'] -= $nums;

		Success('', 1); //只是输出成功信息
		$this->index(); //进入我的询价
	}

    public function index(){
		$this->assign('submenu', UCSub($this->langs['u_myeq'], array(array($this->langs['u_eqlist'], 'uc_enquiry', 1))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$data = '';
		$myid = $this->user->data['userid'];
		$del_e = $this->CheckAccess('del_e'); //是否允许删除询价

		$geteqs = APP::$DB->query("SELECT e_id, refer_id, status, email, pro_id, userid, username, lang, title, created FROM " . TABLE_PREFIX . "enquiry WHERE refer_id =0 AND userid = '$myid' ORDER BY status, created DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(e_id) AS value FROM " . TABLE_PREFIX . "enquiry WHERE refer_id =0  AND userid = '$myid'");

		if($maxrows['value'] < 1){
			$del_e = 0;
			$data .= '<tr><td colspan="6"><BR><font class=orangeb>' . $this->langs['u_noenqs'] . '</font><BR><BR></td></tr>';
		}else{
			$line2 = 0;
			while($eq = APP::$DB->fetch($geteqs)){
				if($line2) {
					$lineclass = " class=tr2";
					$line2 = 0;
				}else{
					$lineclass = '';
					$line2 = 1;
				}

				$title = ShortTitle($eq['title'], 36);
				if($eq['status'] == 1){
					$title = "<font class=red>$title</font>";
					$status = "<font class=red>" . $this->langs['u_newreply'] . "</font>";
				}elseif($eq['status'] == 0){
					$status = "<font class=orange>" . $this->langs['u_unreply'] . "</font>";
				}else{
					$title = "<font class=grey>$title</font>";
					$status = "<font class=grey>" . $this->langs['u_replied'] . "</font>";
				}

				$data .= '<tr' . $lineclass . '>
				<td class="al"><a href="' . URL('uc_enquiry/reply?e_id=' . $eq['e_id']) . '" title="' . $this->langs['u_enqmore'] . '">' . $title . '</a></td>
				<td>' . $status . '</td>
				<td>' . $eq['email'] . '</td>
				<td>' . DisplayDate($eq['created'], '', 1) . '</td>
				<td><a href="' . URL('products?id=' . $eq['pro_id']) . '" target="_blank" title="'. $this->langs['product'] . ' (ID: ' . $eq['pro_id'] . ')"><img src="' . T_URL . 'images/product.gif"></a></td>
				<td><input type="checkbox" name="deleteids[]" value="' . $eq['e_id'] . '" class="chbox"' . Iif(!$del_e, ' disabled') . '></td>
				</tr>';
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
		}

		$this->assign('tbtitle', str_replace('//1', $maxrows['value'], $this->langs['u_enqinfo']));
		$this->assign('data', $data); //数据
		$this->assign('page', $page); //当前页号
		$this->assign('del_e', $del_e); //删除记录的权限
		$this->assign('pagelist', GetPageList(URL('uc_enquiry'), $totalpages, $page, 10)); //分页

		$this->display('uc_enquiry.html');
	}


	//回复询价
    public function reply(){
		$myid = $this->user->data['userid'];
		$e_id = ForceIntFrom('e_id');

		$this->assign('submenu', UCSub($this->langs['u_myeq'], array(array($this->langs['u_eqlist'], 'uc_enquiry'), array($this->langs['u_enqmore'], 'uc_enquiry/reply?e_id=' . $e_id, 1))));

		if($e_id) $eq = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "enquiry WHERE e_id = '$e_id' AND refer_id = 0 AND userid = '$myid'");
		if(!$e_id OR !$eq) Error($this->langs['er_replyeqerror'], $this->langs['u_enqmore'] . ' ' . $this->langs['error']);

		//如果有新回复设置为已读
		if($eq['status'] == 1) APP::$DB->exe("UPDATE " . TABLE_PREFIX . "enquiry SET status = 2 WHERE e_id = '$e_id'");

		//查询产品
		$product = APP::$DB->getOne("SELECT pro_id, is_show, userid, username, path, filename, " . Iif(IS_CHINESE, "price, title", "price_en AS price, title_en AS title"). ", clicks, created FROM " . TABLE_PREFIX . "product WHERE pro_id = '$eq[pro_id]'");

		$replies = APP::$DB->getAll("SELECT * FROM " . TABLE_PREFIX . "enquiry WHERE refer_id = '$e_id' ORDER BY e_id");

		$this->assign('product', $product); //产品
		$this->assign('eq', $eq); //询价
		$this->assign('replies', $replies); //回复
		$this->display('uc_enquiry_reply.html');
	}

}

?>