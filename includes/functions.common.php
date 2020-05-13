<?php if(!defined('ROOT')) die('Access denied.');

include(ROOT . 'includes/functions.global.php');

//前台模板中输出伪静态PURL
function PURL($url = ''){
	echo RW_FRONTEND . $url;
}

//立即跳转函数 redirect
function Redirect($url = ''){
	echo '<script type="text/javascript">document.location="' . URL($url) . '";</script>';
	exit();
}

//输出用户头像 参数：$size为空时小头像, 为1是大头像
function PrintAvatar($userid, $size = '') {
	echo GetAvatar($userid, $size);
}

//返回需要在JS中初步验证权限对象字符串, 仅包含用户ID, 及短信,评论,询价3项权限
function getUserRights($user) {
	$str = "{userid:$user[userid],nickname:'$user[nickname]',email:'$user[email]'";
	if($user['grouptype'] == 1){ //管理员
		return $str . ",pm:1,comment:1,enquiry:1}";
	}else{ //前台用户
		$rights = getAccess($user['actions'], array('pm', 'comment', 'enquiry'));
		return $str . ",pm:$rights[pm],comment:$rights[comment],enquiry:$rights[enquiry]}";
	}
}

// 输出 错误信息对话框  参数$errors:字符串或数据;  $time: 自动关闭的时间(秒)
function Error($errors, $title = '', $time = 0) {

	if(empty($errors)) return; //无信息时仅返回;
	if(!$title) $title = APP::$C->langs['systeminfo'];

	if(is_array($errors)){
		for($i = 0; $i < count($errors); $i++)
			$str .= "<font color=#AEABAA>" . ($i + 1) . "). </font><font color=#FF9900>$errors[$i]</font><br>";
	}else {
		$str =  "<font color=#FF9900>$errors</font><br>";
	}

	$extra = ",callback: function(){history.back();}"; //关闭后返回上一页
	if($time) {
		$time = $time * 1000;
		$extra .= ",autoClose:$time"; //自动关闭, 默认为不关闭
	}

	//设置类成员变量
	APP::$C->display_allowed = false; //错误发生时返回到前一个页面, 因此不允许加载其它的模板文件(只需要页头和页尾)
	APP::$C->system_info = "<script>$(function(){easyDialog.open({container:{header:'<font color=red>$title</font>',content:'$str',yesFn:function(){},yesText:'" . APP::$C->langs['d_no'] . "'}$extra});$('#easyDialogYesBtn').focus();});</script>";

	APP::$C->assign('system_info',  APP::$C->system_info);
	exit(); //后面的代码不必再运行(注: __destruct()函数仍会运行)
}


// 输出 成功信息对话框  参数$url: 为空显示信息不跳转; $info:字符串;  $time自动关闭的时间(秒)
//参数$realurl表示是否是真实的URL, 如果是则不需要进行伪静态处理
function Success($url = '', $time = 1, $info = '', $title = '', $realurl = false) {
	if(!$title) $title = APP::$C->langs['systeminfo'];

	//给信息搞个不同的颜色
	$info =Iif($info, "<font color=blue>$info</font>", '<font color=#AEABAA>' . APP::$C->langs['okinfo'] . '</font>');

	if($url){
		$extra = ",callback: function(){document.location='". Iif($realurl, $url, URL($url)) ."';}"; //关闭后跳转, $url如果为空则不跳转
		APP::$C->display_allowed = false; //如果操作成功跳转, 不允许加载其它的模板文件(只需要页头和页尾)
	}
	if($time) {
		$time = $time * 1000;
		$extra .= ",autoClose:$time"; //自动关闭, 默认1秒后关闭
	}

	//设置类成员变量
	APP::$C->system_info = "<script>$(function(){easyDialog.open({container:{header:'<font color=#33CC00>$title</font>',content:'$info',noFn:true,noText:'" . APP::$C->langs['d_yes'] . "'}$extra});$('#easyDialogNoBtn').focus();});</script>";

	APP::$C->assign('system_info',  APP::$C->system_info);

	if($url) exit(); //如果跳转, 后面的代码不必再运行(注: __destruct()函数仍会运行)
}

//用户中心标题及二级菜单函数
function UCSub($title, $ms = array()) {
	$s = '<h3>'.$title.'</h3>';
	if(!empty($ms)) {
		$s .= '<ul>';
		foreach($ms AS $m) {
			$s .= '<a class="link-btn' . Iif($m[2], ' link-on') . '" href="' . URL($m[1]) . '">' . $m[0] . '</a>';
		}
		$s .= '</ul>';
	}
	return $s;
}


//输出debug信息
function Debug() {
	global $sys_starttime;

	$mtime = explode(' ', microtime());
	$sys_runtimie = number_format(($mtime[1] + $mtime[0] - $sys_starttime), 3);

	echo 'Done in '. $sys_runtimie.' seconds, ' . APP::$DB->query_nums . ' queries';
}

//创建验证码, 返回id
function CreateVVC() {
	APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "vvc (created) VALUES ('".time()."')");

	return APP::$DB->insert_id;
}

//验证码校验  参数delete:  ajax验证时, 如果表单还要提交, 不能删除
function CheckVVC($vvcid, $code, $delete = true) {
	$vvcid = ForceInt($vvcid);
	if(!$vvcid OR !$code){return false;}

	$vvc = APP::$DB->getOne("SELECT code FROM " . TABLE_PREFIX . "vvc WHERE vvcid = '$vvcid'");

	if($vvc['code'] == strtoupper($code)){
		if($delete) APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "vvc WHERE vvcid = '$vvcid'");
		return true;
	}

	return false;
}

//前台模板中输出图片的URL
function PrintImageURL($path, $filename, $size = 1){
	switch($size){
		case 1:
			$size = '_s.jpg';
			break;
		case 2:
			$size = '_m.jpg';
			break;
		case 3:
			$size = '_l.jpg';
			break;
	}

	echo SYSDIR  . "uploads/$path/$filename$size";
}

//根据调用ID号获取常态内容
function GetContent($id) {
	$content = array();

	$id = ForceInt($id);
	if($id){

		if(IS_CHINESE){
			$sql = "SELECT r_id, title, content, keywords, created ";
		}else{
			$sql = "SELECT r_id, title_en AS title, content_en AS content, keywords_en AS keywords, created ";
		}

		$content = APP::$DB->getOne($sql ." FROM " . TABLE_PREFIX . "content WHERE r_id = '$id'");

		if($content){
			$content['content'] = html($content['content']);//内容正文部分转换成html
			$content['created'] = DisplayDate($content['created']);//时间截转换日期时间
		}
	}

	return $content;
}

//分页函数
function GetPageList($FileName, $PageCount, $CurrentPage = 1, $PagesToDisplay = 10, $PN01 = '', $PNV01 = '', $PN02 = '', $PNV02 = '', $PN03 = '', $PNV03 = '', $PN04 = '', $PNV04 = '', $PN05 = '', $PNV05 = '') {

	if($PageCount < 2) return '';

	if(IS_CHINESE){
		$PreviousText =  '上一页';
		$NextText = '下一页';
		$SumPage ='总数';
		$Current ='当前页';
	}else{
		$PreviousText =  'Prev';
		$NextText = 'Next';
		$SumPage ='Total';
		$Current ='Page';
	}

	$Params = '';
	$Params .= Iif($PN01 AND $PNV01, '&'.$PN01.'='.$PNV01);
	$Params .= Iif($PN02 AND $PNV02, '&'.$PN02.'='.$PNV02);
	$Params .= Iif($PN03 AND $PNV03, '&'.$PN03.'='.$PNV03);
	$Params .= Iif($PN04 AND $PNV04, '&'.$PN04.'='.$PNV04);
	$Params .= Iif($PN05 AND $PNV05, '&'.$PN05.'='.$PNV05);

	$iPagesToDisplay = $PagesToDisplay - 2;      
	if ($iPagesToDisplay <= 8) $iPagesToDisplay = 8;

	$MidPoint = ($iPagesToDisplay / 2);

	$FirstPage = $CurrentPage - $MidPoint;
	if ($FirstPage < 1) $FirstPage = 1;

	$LastPage = $FirstPage + ($iPagesToDisplay - 1);

	if ($LastPage > $PageCount) {
		$LastPage = $PageCount;
		$FirstPage = $PageCount - $iPagesToDisplay;
		if ($FirstPage < 1) $FirstPage = 1;
	}

	$Loop = 0;
	$iTmpPage = 0;

	$sReturn = '<div id="pagelist"><div class="PageListDiv"><ol class="PageList"><li><span class="NoPagePrev">'."{$SumPage}:{$PageCount}".'</span></li>';
	$sReturn .="<li><span class='NoPagePrev'>{$Current}:{$CurrentPage}</span></li>";
	if ($CurrentPage > 1) {
		$iTmpPage = $CurrentPage - 1;
		$sReturn .= '<li><a href="' . $FileName . '?p=' . $iTmpPage . $Params . '" class="PagePrev"  onfocus="this.blur()">'.$PreviousText.'</a></li>';
	} else {
		$sReturn .= '<li><span class="NoPagePrev">'.$PreviousText.'</span></li>';
	}

	if ($FirstPage > 2) {
		$sReturn .= '<li><a href="' . $FileName . '?p=1' . $Params . '" onfocus="this.blur()">1</a></li><li>...</li>';
	} elseif ($FirstPage == 2) {
		$sReturn .= '<li><a href="' . $FileName . '?p=1' . $Params . '" onfocus="this.blur()">1</a></li>';
	}

	$Loop = 0;

	for ($Loop = 1; $Loop <= $PageCount; $Loop++) {
		if (($Loop >= $FirstPage) && ($Loop <= $LastPage)) {
			if ($Loop == $CurrentPage) {
				$sReturn .= '<li><span class="CurrentPage">'.$Loop.'</span></li>';
			} else {
				$sReturn .= '<li><a href="' .  $FileName . '?p=' . $Loop . $Params . '" onfocus="this.blur()">'.$Loop.'</a></li>';
			}
		}
	}

	if ($CurrentPage < ($PageCount - $MidPoint) && $PageCount > $PagesToDisplay - 1) {
		$sReturn .= '<li>...</li><li><a href="' . $FileName . '?p=' . $PageCount . $Params . '" onfocus="this.blur()">'.$PageCount.'</a></li>';
	} else if ($CurrentPage == ($PageCount - $MidPoint) && ($PageCount > $PagesToDisplay)) {
		$sReturn .= '<li><a href="' . $FileName . '?p=' . $PageCount . $Params . '" onfocus="this.blur()">'.$PageCount.'</a></li>';
	}

	if ($CurrentPage != $PageCount) {
		$iTmpPage = $CurrentPage + 1;
		$sReturn .= '<li><a href="' . $FileName . '?p=' . $iTmpPage . $Params . '" class="PageNext" onfocus="this.blur()">'.$NextText.'</a></li>';
	} else {
		$sReturn .= '<li><span class="NoPageNext">'.$NextText.'</span></li>';
	}

	$sReturn .= '</ol></div></div>';

	return $sReturn;
}

//增加点击次数
function add_clicks($id, $type = 'product'){
	if(!ForceInt($id)) return; //非法id返回

	$cookiename = COOKIE_KEY . $type; //根据类型确定cookie名称

	$ids = ForceCookieFrom($cookiename);
	$arrIds = explode(',', $ids);

	if(!in_array($id, $arrIds)){
		switch($type){
			case 'product': //产品
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET clicks = (clicks + 1)  WHERE pro_id='$id'");
				break;
			case 'news': //新闻
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "news SET clicks = (clicks + 1)  WHERE n_id='$id'");
				break;
			case 'article': //文章
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "article SET clicks = (clicks + 1)  WHERE a_id='$id'");
				break;
		}

		//将新id保存cookie, 24小时过期
		$ids .= Iif($ids, ',') . $id; 
		setcookie($cookiename, $ids, time() + 24*3600, "/");
	}
}


//输出广告
function ShowAdvert($id) {
	$adpoid = ForceInt($id);

	if($adpoid){
		$ad = APP::$DB->getOne("SELECT a.content, a.content_en, p.width, p.height FROM " . TABLE_PREFIX . "advertise a LEFT JOIN  " . TABLE_PREFIX . "adposition p ON (a.adpoid = p.adpoid) WHERE a.actived = 1 AND p.actived = 1 AND a.adpoid = '$adpoid' AND (a.overdate > ".(time() + 3600 * APP::$_CFG['siteTimezone'])." OR a.overdate = 0) ORDER BY rand() LIMIT 1");

		$content = Iif(IS_CHINESE, $ad['content'], $ad['content_en']);
		if($content){
			echo '<div style="margin:0 auto;padding-top:8px;width:'.$ad['width'].';height:'.$ad['height'].';overflow:hidden;">' . html($content) . '</div>';
		}
	}

}


//获得导航栏 
//参数$arr是一维数据, 键名是链接名称, 值是链接;  $nav表示分隔符
function GetNavLinks($arr = array(), $nav = '&nbsp;&rarr;&nbsp;') {
	$str = '<a href="' . URL() . '">' . Iif(IS_CHINESE, '首页', 'Home') . '</a>';

	if(is_array($arr)){
		foreach($arr as $title => $url){ //$url可能是链接的URL或链接名称
			$str .= Iif(is_int($title), Iif($url, $nav . "<span class=title>$url</span>"), $nav . Iif($url, "<a href=\"" . URL($url) . "\">$title</a>", "<span class=title>$title</span>"));
		}
	}elseif($arr){
		$str .= $nav . "<span class=title>$arr</span>";
	}

	return $str;
}


//递归  按分类ID获取当前产品分类的所有未隐藏(有效的)下级分类, 返回逗号分隔的字符串
function GetSubProcats($cat_id){
	$cats = APP::$C->pcat_ids;
	$sReturn = '';

	foreach($cats as $id => $pid){
		if($cat_id == $pid){
			$sReturn .= "," . $id . GetSubProcats($id);
		}
	}
	
	return $sReturn;
}


//获得最新或随机产品: $num表示个数  $cat_id指分类id
//参数$type:  默认0表示最新产品  1表示推荐产品  2表示随机产品
function GetNewProducts($num = 10, $type = 0, $cat_id = 0) {
	$num = ForceInt($num, 10);
	$cat_id = ForceInt($cat_id);

	//如果有分类且此分类未发布, 返回空数组(省得循环时出错)
	if($cat_id AND !in_array($cat_id, APP::$C->pcats_ok)) return array();

	if($cat_id){
		if(APP::$C->pcategories[$cat_id]['show_sub'] AND in_array($cat_id, APP::$C->pcat_ids)){ //当分类设置成显示下级分类产品且有下级分类时
			$sub_categorysql = GetSubProcats($cat_id); //获取所以下级分类的SQL
		}

		if($sub_categorysql){
			$cat_sql = " AND cat_id IN (". $cat_id . $sub_categorysql. ") ";
		}else{
			$cat_sql = " AND cat_id = $cat_id ";
		}

	}else{//全部产品中所属分类已发布的最新产品
		$all_categories = implode(',', APP::$C->pcats_ok); //获取所有未隐藏分类及下级分类的SQL
		if($all_categories){
			$cat_sql = " AND cat_id IN ($all_categories) ";
		}
	}

	$sql = "SELECT pro_id, cat_id, is_best, userid, username, path, filename, clicks, created, ";
	if(IS_CHINESE){
		$sql .= " price, title ,keywords";
	}else{
		$sql .= " price_en AS price, title_en AS title ,keywords_en AS keywords";
	}

	$products = APP::$DB->getAll($sql . " FROM " . TABLE_PREFIX . "product  WHERE is_show = 1 " . Iif($type ===1, " AND is_best = 1 ") . $cat_sql . " ORDER BY " . Iif($type === 2, "rand()", "sort DESC") . " LIMIT $num");

	return $products;
}


//获得最新站内新闻: $num表示个数
function GetNews($num = 10) {
	$num = ForceInt($num, 10);

	if(IS_CHINESE){
		$news_sql = "SELECT n_id, title, linkurl, clicks, created ";
	}else{
		$news_sql = "SELECT n_id, title_en AS title, linkurl_en AS linkurl, clicks, created ";
	}

	return APP::$DB->getAll($news_sql . " FROM " . TABLE_PREFIX . "news  WHERE is_show = 1 ORDER BY sort DESC LIMIT $num");
}


//递归  按分类ID获取当前文章分类的所有未隐藏(有效的)下级分类, 返回逗号分隔的字符串
function GetSubArtcats($cat_id){
	$cats = APP::$C->acat_ids;
	$sReturn = '';

	foreach($cats as $id => $pid){
		if($cat_id == $pid){
			$sReturn .= "," . $id . GetSubArtcats($id);
		}
	}
	
	return $sReturn;
}


//获得最新或随机文章: $num表示个数  $cat_id指分类id
//参数$type:  默认0表示最新文章  1表示推荐文章  2表示随机文章
function GetNewArticles($num = 10, $type = 0, $cat_id = 0) {
	$num = ForceInt($num, 10);
	$cat_id = ForceInt($cat_id);

	//如果有分类且此分类未发布, 返回空数组(省得循环时出错)
	if($cat_id AND !in_array($cat_id, APP::$C->acats_ok)) return array();

	if($cat_id){
		if(APP::$C->acategories[$cat_id]['show_sub'] AND in_array($cat_id, APP::$C->acat_ids)){ //当分类设置成显示下级分类文章且有下级分类时
			$sub_categorysql = GetSubArtcats($cat_id); //获取所以下级分类的SQL
		}

		if($sub_categorysql){
			$cat_sql = " AND cat_id IN (". $cat_id . $sub_categorysql. ") ";
		}else{
			$cat_sql = " AND cat_id = $cat_id ";
		}

	}else{//全部产品中所属分类已发布的文章
		$all_categories = implode(',', APP::$C->acats_ok); //获取所有未隐藏分类及下级分类的SQL
		if($all_categories){
			$cat_sql = " AND cat_id IN ($all_categories) ";
		}
	}

	$sql = "SELECT a_id, cat_id, is_best, userid, username, clicks, created, ";
	if(IS_CHINESE){
		$sql .= " title, linkurl ";
	}else{
		$sql .= " title_en AS title, linkurl_en AS linkurl ";
	}

	$articles = APP::$DB->getAll($sql . " FROM " . TABLE_PREFIX . "article  WHERE is_show = 1 " . Iif($type ===1, " AND is_best = 1 ") . $cat_sql . " ORDER BY " . Iif($type === 2, "rand()", "sort DESC") . " LIMIT $num");

	return $articles;
}


?>