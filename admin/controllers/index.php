<?php if(!defined('ROOT')) die('Access denied.');

class c_index extends SAdmin{

    function index($path){
		
		//获取统计数据
		$basedata = APP::$DB->getOne("SELECT (select COUNT(userid)  FROM " . TABLE_PREFIX . "user WHERE activated = '-1') AS users, 
		(select COUNT(e_id) FROM " . TABLE_PREFIX . "enquiry WHERE status =0) AS enquiries,
		(select COUNT(a_id) FROM " . TABLE_PREFIX . "article WHERE is_show = '-1') AS articles, 
		(select COUNT(c_id) FROM " . TABLE_PREFIX . "comment WHERE type = 0 AND actived = '-1') AS acomments, 
		(select COUNT(pro_id)  FROM " . TABLE_PREFIX . "product WHERE is_show = '-1') AS products, 
		(select COUNT(c_id) FROM " . TABLE_PREFIX . "comment WHERE type = 1 AND actived = '-1') AS pcomments, 
		(select COUNT(adid) FROM " . TABLE_PREFIX . "advertise WHERE overdate <> 0 AND overdate < " . (time() + 3600 * APP::$_CFG['siteTimezone'])  . ") AS adverts");

		SubMenu('欢迎进入 '.APP_NAME.' 管理中心', array(
			array('添加文章', 'articles/add'),
			array('添加产品', 'products/add'),
			array('添加新闻', 'news/add'),
			array('切换模板', 'template')
		));

		$welcome = '<ul><li>欢迎 <font class=orange>'.$this->admin->data['nickname'].'</font> 进入后台管理面板! 为了确保系统安全, 请在关闭前点击 <a href="" onclick="'.Confirm('确定退出'.APP_NAME.'系统吗?', 'index/logout').'">退出</a> 安全离开!</li>
		<li>隐私保护: <span class="note2">'.APP_NAME.'郑重承诺, 您在使用本系统时, '.APP_NAME.'开发商不会收集您的任何信息</span>.</li>
		<li>您在使用'.APP_NAME.'时有任何问题, 请访问: qq630892807</a>!</li></ul>';

		ShowTips($welcome, '系统信息');

		BR(2);

		TableHeader('重点数据统计');

		TableRow(array('1)', '未激活用户: <font class='.Iif($basedata['users'], 'redb', 'grey').'>'.$basedata['users'].'</font>', '<a class="link-btn" href="'.BURL('users').'">激活用户</a>', '未回复询价数: <font class='.Iif($basedata['enquiries'], 'redb', 'grey').'>'.$basedata['enquiries'].'</font>', '<a class="link-btn" href="'.BURL('enquiry').'">回复用户询价</a>'));

		TableRow(array('2)', '待审文章数: <font class='.Iif($basedata['articles'], 'redb', 'grey').'>'.$basedata['articles'].'</font>', '<a class="link-btn" href="'.BURL('articles').'">审核文章</a>', '待审文章评论: <font class='.Iif($basedata['acomments'], 'redb', 'grey').'>'.$basedata['acomments'].'</font>', '<a class="link-btn" href="'.BURL('acomment').'">审核文章评论</a>'));

		TableRow(array('3)', '待审产品数: <font class='.Iif($basedata['products'], 'redb', 'grey').'>'.$basedata['products'].'</font>', '<a class="link-btn" href="'.BURL('products').'">审核产品</a>', '待审产品评论: <font class='.Iif($basedata['pcomments'], 'redb', 'grey').'>'.$basedata['pcomments'].'</font>', '<a class="link-btn" href="'.BURL('pcomment').'">审核产品评论</a>'));

		TableRow(array('4)', '已过期广告: <font class='.Iif($basedata['adverts'], 'redb', 'grey').'>'.$basedata['adverts'].'</font>', '<a class="link-btn" href="'.BURL('advertise').'">管理广告</a>', '&nbsp;', '&nbsp;'));

		TableFooter();

		$info_total = array_sum($basedata);

		//将统计数据保存为cookie, 供其它页面调用并保持统计数据
		$cookiedata = implode("*", $basedata);

		//更新顶部提示信息
		echo '<script type="text/javascript">
			$(function(){
				var info_total = ' . $info_total . ';
				var info_us = ' . $basedata['users'] . ';
				var info_as = ' . $basedata['articles'] . ';
				var info_acs = ' . $basedata['acomments'] . ';
				var info_ps = ' . $basedata['products'] . ';
				var info_pcs = ' . $basedata['pcomments'] . ';
				var info_es = ' . $basedata['enquiries'] . ';
				var info_ads = ' . $basedata['adverts'] . ';

				if(info_total > 0){
					$("#topuser dl#info_all").removeClass("none");
					$("#topuser #info_total").html(info_total);
					if(info_us > 0) $("#topuser #info_us").html(info_us).attr("class", "orangeb");
					if(info_es > 0) $("#topuser #info_es").html(info_es).attr("class", "orangeb");
					if(info_as > 0) $("#topuser #info_as").html(info_as).attr("class", "orangeb");
					if(info_acs > 0) $("#topuser #info_acs").html(info_acs).attr("class", "orangeb");
					if(info_ps > 0) $("#topuser #info_ps").html(info_ps).attr("class", "orangeb");
					if(info_pcs > 0) $("#topuser #info_pcs").html(info_pcs).attr("class", "orangeb");
					if(info_ads > 0) $("#topuser #info_ads").html(info_ads).attr("class", "orangeb");
				}

				//将统计数据保存为cookie. 注: header已发送, 此页面不能使用php保存cookie
				$.cookie("' . COOKIE_KEY . 'backinfos", "' . $cookiedata . '", {expires: 365, path: "/"});
			});
		</script>';
    }

}

?>