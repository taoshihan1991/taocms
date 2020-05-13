<?php if(!defined('ROOT')) die('Access denied.');

class c_products extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

		$this->upload_path = ROOT . 'uploads/';

		@set_time_limit(0);  //解除时间限制
	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){

		$action = ForceStringFrom('action');

		if($action == 'deletelast'){
			$files = get_upload_files($this->admin->data['userid']);

			foreach($files AS $Item){
				@unlink($this->upload_path . $Item);
			}
		}elseif($action == 'deleteone'){
			$file = ForceStringFrom('file');
			@unlink($this->upload_path . $file);
		}

		die($this->json->encode($this->ajax));
	}

	private function GetSelect($selectedid =0, $selectname = 'cat_id'){
		$sReturn = '<select name="' . $selectname . '"><option value="0">-- 请选择 --</option>';
		$categories = APP::$DB->getAll("SELECT cat_id, p_id, name, name_en, counts  FROM " . TABLE_PREFIX . "pcat ORDER BY sort");
		$sReturn .= $this->GetOptions($categories, $selectedid);
		$sReturn .= '</select>';

		return $sReturn;
	}

	private function GetOptions($categories, $selectedid = 0, $p_id = 0, $sublevelmarker = ''){
		if($p_id) $sublevelmarker .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		foreach($categories as $value){
			if($p_id == $value['p_id']){
				$sReturn .= '<option '. Iif(!$p_id, 'style="color:#cc4911;font-weight:bold;"') . ' value="' . $value['cat_id'] . '" ' . Iif($selectedid == $value['cat_id'], 'SELECTED', '') . '>' . $sublevelmarker . $value['name'] . '-' . $value['name_en'] . ' (' . $value['counts'] . ')</option>';

				$sReturn .= $this->GetOptions($categories, $selectedid, $value['cat_id'], $sublevelmarker);
			}
		}

		return $sReturn;
	}

	private function UploadImage($imagefile, $file_path_str, $imagename) {
		$ext = getFileExt($imagefile['name']);
		if(!$ext OR !in_array($ext, array('jpeg', 'jpg', 'gif', 'png'))) return false;

		$uploaddir = $this->upload_path;
		$file_path = '';

		$temp = explode('/', $file_path_str);
		foreach($temp AS $path){
			$file_path .= $path . '/';
			MakeDir($uploaddir . $file_path);
		}

		$newpath = $uploaddir.$file_path.$imagename; //不含后缀
		$thisfile = $newpath . ".$ext";

		if((function_exists('move_uploaded_file') AND @move_uploaded_file($imagefile['tmp_name'], $thisfile )) OR @rename($imagefile['tmp_name'], $thisfile))	{
			$s = CreateImageFile($thisfile,     $newpath."_l.jpg",        APP::$_CFG['siteLarge'],     APP::$_CFG['siteLargeH']);
			$s = CreateImageFile($thisfile,     $newpath . "_m.jpg",    APP::$_CFG['siteMiddle'],    APP::$_CFG['siteMiddleH']);
			$s = CreateImageFile($thisfile,     $newpath . "_s.jpg",     APP::$_CFG['siteSmall'],     APP::$_CFG['siteSmallH']);
			@unlink($thisfile);
			return $s;
		}else{
			return false;
		}
	}

	//处理并保存已上传的组图片
	private function SaveGroupImage($pro_id) {
		$uploaded_images = get_upload_files($this->admin->data['userid']);
		if(empty($uploaded_images)) return;

		$uploadpath = $this->upload_path;
		$file_path_str = DisplayDate(time(), 'Y/md');
		$file_path = '';
		$arr = explode('/', $file_path_str);
		foreach($arr AS $path){
			$file_path .= $path . '/';
			MakeDir($uploadpath . $file_path);
		}

		foreach($uploaded_images AS $file){
			$ext = getFileExt($file);
			if(!$ext OR !in_array($ext, array('jpeg', 'jpg', 'gif', 'png'))) continue;

			$thisfile = $uploadpath . $file;
			$imagename = md5(uniqid(COOKIE_KEY.microtime()));
			$newpath = $uploadpath .$file_path . $imagename; //不含后缀及图片大小标志

			$s = CreateImageFile($thisfile,    $newpath . "_l.jpg", APP::$_CFG['siteLarge'], APP::$_CFG['siteLargeH']);
			$s = CreateImageFile($thisfile,    $newpath . '_m.jpg', APP::$_CFG['siteMiddle'], APP::$_CFG['siteMiddleH']);
			$s = CreateImageFile($thisfile,    $newpath . '_s.jpg', APP::$_CFG['siteSmall'], APP::$_CFG['siteSmallH']);
			@unlink($thisfile);

			if($s) APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "gimage (pro_id, path, filename) VALUES ('$pro_id', '$file_path_str', '$imagename')");
		}
	}

	//删除图片文件
	private function UnlinkImage($path, $filename) {
		@unlink($this->upload_path . $path . '/' . $filename . '_s.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_m.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_l.jpg');
	}

	//按pro_id删除产品
	private function DeleteProductById($pro_id) {
		$product = APP::$DB->getOne("SELECT cat_id, userid, path, filename FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id'");
		if(!$product) return;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id'");
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts-1) WHERE cat_id = '$product[cat_id]'");

		$this->UnlinkImage($product['path'], $product['filename']); //删除主图片

		$this->DeleteGroupImage($pro_id); //删除组图片

		//更新用户的产品数
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET p_num = (p_num-1) WHERE userid = '$product[userid]'");

		//删除评论及更新用户的评论数
		$getcomments = APP::$DB->query("SELECT userid FROM " . TABLE_PREFIX . "comment WHERE for_id = '$pro_id' AND type = 1");
		while($comment = APP::$DB->fetch($getcomments)){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET pc_num = (pc_num-1) WHERE userid = '$comment[userid]'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE for_id = '$pro_id' AND type = 1");
	}

	//按产品pro_id 或组图片g_id 删除其图片
	private function DeleteGroupImage($pro_id, $g_id = 0) {
		$more = '';
		
		if($g_id) $more = " AND g_id ='$g_id'";

		$getimages = APP::$DB->query("SELECT g_id, path, filename FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' " . $more);

		while($image = APP::$DB->fetch($getimages)){
			$this->UnlinkImage($image['path'], $image['filename']);
		}
		
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' " . $more);
	}

	//更新保存多个产品
	public function updateproducts(){
		$this->CheckAction('products'); //权限验证
		$page = ForceIntFrom('p', 1);   //页码

		if(IsPost('updateproducts')){
			$pro_ids = $_POST['pro_ids'];
			$sorts   = $_POST['sorts'];
			$is_shows   = $_POST['is_shows'];
			$is_bests   = $_POST['is_bests'];
			for($i = 0; $i < count($pro_ids); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET sort = '". ForceInt($sorts[$i])."',
				is_show = '". ForceInt($is_shows[$i])."',
				is_best = '". ForceInt($is_bests[$i])."'
				WHERE pro_id = '". ForceInt($pro_ids[$i])."'");
			}
		}else{
			$pro_ids = $_POST['deletepro_ids'];
			for($i=0; $i<count($pro_ids); $i++){
				$this->DeleteProductById(ForceInt($pro_ids[$i]));
			}
		}
		Success('products?p=' . $page);
	}

	//$search 查询的关键字 $type时间条件 大于小于什么的  $time时间 $cat_id产品分类的id
	public function GetSearchSql($search, $type, $time, $cat_id){
		$Where = "";

		if($cat_id > 0){
			$Where .= ' cat_id=' . $cat_id;
		}elseif($cat_id == '-1'){
			$Where .= " is_show='-1' ";
		}elseif($cat_id == '-2'){
			$Where .= " is_show=0 ";
		}

		if($search AND preg_match("/^[1-9][0-9]*$/", $search)){
			$search = ForceInt($search);
			$Where .= Iif($Where, " AND ") . " (pro_id = '$search' OR userid = '$search') "; //是数字时按产品或用户ID搜索
		}elseif($search){
			$Where .= Iif($Where, " AND ") . " (title like '%$search%' OR username like '%$search%' OR title_en like '%$search%' OR content like '%$search%' OR content_en like '%$search%' OR keywords like '%$search%' OR keywords_en like '%$search%') ";
		}

		if($time){
			$timearr=explode('-',$time);
			$year=$timearr[0];
			$month=$timearr[1];
			$day=$timearr[2];
			$bigtime=mktime(0,0,0,$month,$day+1,$year);
			$littletime=mktime(0,0,0,$month,$day,$year);
			if($type=='eq'){
				$twhere = " created >= $littletime AND created < $bigtime ";
			}elseif($type=='gr'){
				$twhere = " created >= $bigtime ";
			}elseif($type=='le'){
				$twhere = " created < $littletime ";
			}

			$Where .= Iif($Where, " AND ") . $twhere;
		}

		$Where = Iif($Where, " WHERE " . $Where);

		return $Where;
	}



	public function save(){
		$this->CheckAction('products'); //权限验证

		$pro_id = ForceIntFrom('pro_id');

		$is_show = ForceIntFrom('is_show');
		$is_best = ForceIntFrom('is_best');
		$sort = ForceIntFrom('sort');
		$cat_id = ForceIntFrom('cat_id');
		$oldcat_id = ForceIntFrom('oldcat_id');

		$price = ForceStringFrom('price');
		$price_en = ForceStringFrom('price_en');
		$title = ForceStringFrom('title');
		$title_en = ForceStringFrom('title_en');
		if(!$title_en) $title_en = 'No English Title!';

		$keywords = ForceStringFrom('keywords');
		$keywords_en = ForceStringFrom('keywords_en');
		$content = ForceStringFrom('content');
		$content_en = ForceStringFrom('content_en');

		$pro_path = ForceStringFrom('pro_path');
		$pro_filename = ForceStringFrom('pro_filename');
		
		$deletethisproduct     = ForceIntFrom('deletethisproduct');

		if($deletethisproduct AND $pro_id){//删除产品
			$this->DeleteProductById($pro_id);

			Success('products');
		}

		$imagefile         = $_FILES['imagefile'];
		$valid_image_types = array('image/pjpeg',	'image/jpeg', 'image/jpg', 'image/gif', 'image/png', 'image/x-png');

		if(!$title) $errors[] = '产品的中文标题不能为空！';
		if(!$cat_id) $errors[] = '您没有选择产品分类！';

		if (!function_exists('imagecreatetruecolor')) $errors[] ='服务器PHP环境不支持GD2库, 无法上传图片文件!';

		if (!is_dir($this->upload_path)){
			$errors[] ='保存图片的文件夹: uploads/ 不存在!';
		}else if (!is_writable($this->upload_path)){
			@chmod($this->upload_path, 0777);
			if(!is_writable($this->upload_path)) {
				$errors[] = '保存图片的文件夹: uploads/ 不可写! - 文件夹属性需改为: 0777';
			}
		}

		if(isset($errors))	Error($errors, Iif($pro_id, '编辑产品错误', '添加产品错误'));

		if($pro_id){//编辑产品
			$filesize = $imagefile['size'];

			if($filesize > 0){//有主图片文件上传时
				if(!in_array($imagefile['type'], $valid_image_types)){
					$errors[] = '无效的图片文件类型!';
				}elseif (!IsUploadedFile($imagefile['tmp_name']) || !($imagefile['tmp_name'] != 'none' && $imagefile['tmp_name'] && $imagefile['name'])){
					$errors[] ='上传的文件无效!';
				}

				if(isset($errors)) Error($errors, '编辑产品错误');

				$file_path = DisplayDate(time(), 'Y/md');
				$imagename = md5(uniqid(COOKIE_KEY.time()));

				if(!$this->UploadImage($imagefile, $file_path, $imagename)){
					Error('处理产品图片发生错误!', '编辑产品错误');
				}
			}

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET 
			sort= '$sort',
			cat_id= '$cat_id',
			is_show= '$is_show',
			is_best= '$is_best',
			" . Iif($filesize AND $file_path AND $imagename, "path = '$file_path', filename = '$imagename',") . "
			price = '$price',
			price_en = '$price_en',
			title = '$title',
			title_en = '$title_en',
			content = '$content',
			content_en = '$content_en',
			keywords = '$keywords',
			keywords_en = '$keywords_en'
			WHERE pro_id = ".$pro_id);

			if($oldcat_id != $cat_id){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts+1) WHERE cat_id = '$cat_id'");
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts-1) WHERE cat_id = '$oldcat_id'");
			}

			//重新上传了主图片时删除原有图片文件
			if($filesize AND $file_path AND $imagename){
				$this->UnlinkImage($pro_path, $pro_filename);
			}

			//设置或删除已有组图片
			$gis_shows   = $_POST['gis_shows'];
			$deletegimages   = $_POST['deletegimages'];

			for($i = 0; $i < count($deletegimages); $i++){
				$this->DeleteGroupImage($pro_id, ForceInt($deletegimages[$i]));
			}

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "gimage SET is_show = 0 WHERE pro_id   = '$pro_id'");

			for($i = 0; $i < count($gis_shows); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "gimage SET 
				is_show     = 1
				WHERE pro_id = '$pro_id' AND g_id   = ". ForceInt($gis_shows[$i]));
			}

			//处理并保存上传组图片
			$this->SaveGroupImage($pro_id);

			Success('products/edit?pro_id=' . $pro_id);
		}else{//添加产品
			$time = time();
			$username = $this->admin->data['nickname'];
			$userid = $this->admin->data['userid'];

			$filesize = $imagefile['size'];
			if($filesize == 0)	{
				$errors[] = '未选择图片文件, 或文件大小超过了服务器PHP环境允许上传的文件大小: '.ini_get('upload_max_filesize');
			}elseif(!in_array($imagefile['type'], $valid_image_types)){
				$errors[] = '无效的图片文件类型!';
			}elseif (!IsUploadedFile($imagefile['tmp_name']) || !($imagefile['tmp_name'] != 'none' && $imagefile['tmp_name'] && $imagefile['name'])){
				$errors[] ='上传的文件无效!';
			}

			if(isset($errors)) Error($errors, '添加产品错误');

			$file_path = DisplayDate(time(), 'Y/md');
			$imagename = md5(uniqid(COOKIE_KEY.time()));

			if($this->UploadImage($imagefile, $file_path, $imagename)){
			
				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "product (cat_id, is_show, is_best, userid, username, path, filename, price, price_en, title, title_en, content, content_en, keywords, keywords_en, clicks, created) VALUES ('$cat_id', '$is_show', '$is_best', '$userid', '$username', '$file_path', '$imagename', '$price', '$price_en', '$title', '$title_en', '$content', '$content_en', '$keywords', '$keywords_en', '0', '$time') ");

				$lastid = APP::$DB->insert_id;
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET sort = '$lastid' WHERE pro_id = '$lastid'");
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts+1) WHERE cat_id = '$cat_id'");

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET p_num = (p_num+1) WHERE userid = '$userid'");

				//处理并保存组图片
				$this->SaveGroupImage($lastid);

				Success('products/edit?pro_id=' . $lastid);
			}else{
				Error('处理产品图片发生错误!', '添加产品错误');
			}

		}
	}

	public function index(){
		$NumPerPage = 10;   //每页显示的产品列表的数量
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');   //搜索的内容
		$type = ForceStringFrom('type');   //搜索的内容
		$time = ForceStringFrom('t');
		$cat_id = ForceIntFrom('c');   //按分类搜索时所选的分类的id
		if(IsGet('s')){
			$search = urldecode($search);
		}

		$start = $NumPerPage * ($page-1);  //分页的每页起始位置
		$Where = $this->GetSearchSql($search, $type, $time, $cat_id);

		if($Where){
			SubMenu('产品列表', array(array('添加产品', 'products/add'), array('全部产品', 'products')));
		}else{
			SubMenu('产品列表', array(array('添加产品', 'products/add')));
		}

		$newcategories = array();
		$getcategories = APP::$DB->query("SELECT cat_id, p_id, name, name_en, counts FROM " . TABLE_PREFIX . "pcat ORDER BY sort");
		while($category = APP::$DB->fetch($getcategories)){
			$newcategories[$category['cat_id']] = $category;
		}

		echo '<script type="text/javascript" src="'.SYSDIR.'public/js/DatePicker/WdatePicker.js"></script>
		<form method="post" action="'.BURL('products').'" name="searchform">';

		TableHeader('搜索产品');
		TableRow('<center><label>搜索:</label>&nbsp;<input type="text" name="s" size="12" value="' . $search . '">&nbsp;&nbsp;&nbsp;<label>发表时间:</label>&nbsp;<select name="type"><option value="gr" ' . Iif($type == 'gr', 'SELECTED') . '>大于(>)</option><option value="eq" ' . Iif($type == 'eq', 'SELECTED') . '>等于(=)</option><option value="le" ' . Iif($type == 'le', 'SELECTED') . '>小于(<)</option></select> <input type="text" name="t" onClick="WdatePicker()" value="' . $time . '" size="12">&nbsp;&nbsp;&nbsp;<label>分类:</label>&nbsp;<select name="c"><option value="0">全部分类</option><option style="color:red;" value="-1" ' . Iif($cat_id == '-1', 'SELECTED') . '>待审中的产品</option><option style="color:red;" value="-2" ' . Iif($cat_id == '-2', 'SELECTED') . '>已禁用的产品</option>' . $this->GetOptions($newcategories,$cat_id) . '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="searchproduct" value="搜索产品" class="cancel"></center>');
		TableFooter();
		echo '</form>';

		$getproducts = APP::$DB->query("SELECT pro_id, sort, cat_id, is_show, is_best, userid, username, path, filename, title, title_en, clicks, created FROM " . TABLE_PREFIX . "product " . $Where . " ORDER BY is_show ASC, sort DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(pro_id) AS value FROM " . TABLE_PREFIX . "product " . $Where);

		echo '<form method="post" action="'.BURL('products/updateproducts').'" name="productsform">
		<input type="hidden" name="p" value="'.$page.'">';
		TableHeader(Iif($Where, '搜索到的产品列表', '全部产品列表') . '(' . $maxrows['value'] . '个)');
		TableRow(array('排序', '缩图', '产品标题(编辑)', '所属分类', '作者(昵称)', '状态', '推荐', '点击', '日期', '浏览', '<input type="checkbox" id="checkAll" for="deletepro_ids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何产品!</font><BR><BR></center>');
		}else{
			while($product = APP::$DB->fetch($getproducts)){

				$title = ShortTitle($product['title'], 28);

				if($product['is_show'] =='0'){
					$title = "<font class=red><s>$title</s></font>";
				}elseif($product['is_show'] =='-1'){
					$title = "<font class=red>$title</font>";
				}

				TableRow(array('<input type="hidden" name="pro_ids[]" value="'.$product['pro_id'].'"><input type="text" name="sorts[]" value="' . $product['sort'] . '" size="4">',
					'<a href="'.BURL('products/edit?pro_id='.$product['pro_id']).'"><img src="'. GetImageURL($product['path'], $product['filename']).'" width="40" class="ZoomImg"></a>',
					'<a href="'.BURL('products/edit?pro_id='.$product['pro_id']).'" title="英文: '.$product['title_en'].'">' . $title . '</a>',
					$newcategories[$product['cat_id']]['name'],
					'<a title="编辑" href="'.BURL('users/edit?userid='.$product['userid']).'"><img src="' . GetAvatar($product['userid']) . '" class="user_avatar wh30">' . $product['username'] . '</a>&nbsp;&nbsp;<a href="'.BURL('pm/send?userid='.$product['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信"></a>',
					'<select name="is_shows[]"><option value="1">发布</option><option style="color:red;" value="-1" ' . Iif($product['is_show'] == '-1', 'SELECTED') . '>待审</option><option style="color:red;" value="0" ' . Iif($product['is_show'] == '0', 'SELECTED') . '>禁用</option></select>',
					'<select name="is_bests[]"><option value="0">否</option><option style="color:orange;" value="1" ' . Iif($product['is_best'], 'SELECTED', '') . '>是</option></select>',
					$product['clicks'],
					DisplayDate($product['created']),
					Iif($product['is_show'] == '1', '<a href="'.URL('products?id=' . $product['pro_id']).'" target="_blank"><img src="' . SYSDIR . 'public/admin/images/view.gif"></a>', '<img src="' . SYSDIR . 'public/admin/images/disview.gif">'),
					'<input type="checkbox" name="deletepro_ids[]" value="' . $product['pro_id'] . '" checkme="group">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);
			if($totalpages > 1){
				TableRow(GetPageList(BURL('products'), $totalpages, $page, 10, 's', urlencode($search), 'c', $cat_id, 't', $time, 'type', $type));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updateproducts" value="保存更新" class="save"><input type="submit" name="deleteproducts" onclick="'.Confirm('确定删除所选产品吗?<br><br><span class=red>注: 所选产品的全部信息将被永久删除!</span>', 'form').'" value="删除产品" class="cancel"></div></form>';

		//输出小图片放大镜效果的JS
		PrintZoomJS();
	}

	//编辑调用add
	public function edit(){
		$this->add();
	}

	public function add(){
		$pro_id = ForceIntFrom('pro_id');

		if($pro_id){  //编辑时的
			SubMenu('产品管理', array(array('添加产品', 'products/add'),array('编辑产品', 'products/edit?pro_id='.$pro_id, 1),array('产品列表', 'products')));
			$product = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id'");

			//组图片
			$getgimages = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' ORDER BY g_id ASC");

			$gimagetable = '';
			if(APP::$DB->result_nums > 0){
				while($gimage = APP::$DB->fetch($getgimages)){
					$gimagetable .= '<div class="th_gr">
					<div class="i_thumb_1"><table><tr><td><img src="'.GetImageURL($gimage['path'], $gimage['filename']).'"></td></tr></table></div>
					<div class="status"><input type="checkbox" name="gis_shows[]" value="'.$gimage['g_id'].'" '.Iif($gimage['is_show'] == 1, 'CHECKED').'>&nbsp;发布</div>
					<div class="del"><input type="checkbox" name="deletegimages[]" value="'.$gimage['g_id'].'">&nbsp;删除</div>
					</div>';
				}
			}
		}else{
			SubMenu('产品管理', array(array('添加产品', 'products/add', 1),array('产品列表', 'products')));

			$product = array('is_best' => 0, 'is_show' => 1);
		}

		$uploaded_images = get_upload_files($this->admin->data['userid'], 1);
		$uploaded_counts = count($uploaded_images);

		echo '<link href="'. SYSDIR .'public/js/swfupload/css/swfupload.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="'. SYSDIR .'public/js/swfupload/swfupload.js"></script>
		<script type="text/javascript" src="'. SYSDIR .'public/js/swfupload/swfupload.queue.js"></script>
		<script type="text/javascript" src="'. SYSDIR .'public/js/swfupload/swfupload.fileprogress.js"></script>
		<script type="text/javascript" src="'. SYSDIR .'public/js/swfupload/swfupload.handlers.js"></script>
		<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/kindeditor.js"></script>
		<script charset="utf-8" src="'. SYSDIR .'public/js/kindeditor/lang/zh_CN.js"></script>
		<script type="text/javascript">
			KindEditor.ready(function(K) {
				var editor88 = K.create(\'textarea[name="content"]\', {
					uploadJson : \''.BURL('editor_upload/ajax').'\',
					fileManagerJson : \''.BURL('editor_file_manager/ajax').'\',
					allowFileManager : true,
					afterCreate : function() {
						var self = this;
						K.ctrl(document, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
						K.ctrl(self.edit.doc, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
					}
				});

				var editor66 = K.create(\'textarea[name="content_en"]\', {
					uploadJson : \''.BURL('editor_upload/ajax').'\',
					fileManagerJson : \''.BURL('editor_file_manager/ajax').'\',
					allowFileManager : true,
					afterCreate : function() {
						var self = this;
						K.ctrl(document, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
						K.ctrl(self.edit.doc, 13, function() {
							self.sync();
							K(\'form[name=editorform88]\')[0].submit();
						});
					}
				});

			});

			var swfu;
			window.onload = function() {
				var settings = {
					flash_url : "'. SYSDIR .'public/js/swfupload/swfupload.swf",
					upload_url: "'.BURL('swfupload/ajax').'",
					post_params: {"sessionid": "'.$this->admin->data['sessionid'].'"},
					file_size_limit : "10 MB",
					file_types : "*.jpg;*.png;*.gif;*.jpeg",
					file_types_description : "Image Files",
					file_upload_limit : 60,
					file_queue_limit : 60,
					custom_settings : {
						progressTarget : "fsUploadProgress",
						cancelButtonId : "btnCancel",
						uploadButtonId : "btnUpload",
						filesStatusId: "filesStatus"
					},

					button_image_url: "'. SYSDIR .'public/js/swfupload/images/swfupload_btn_flash.png",
					button_width: "78",
					button_height: "28",
					button_cursor: SWFUpload.CURSOR.HAND,
					button_placeholder_id: "spanButtonPlaceHolder",
					
					file_queued_handler : fileQueued,
					file_queue_error_handler : fileQueueError,
					file_dialog_complete_handler : fileDialogComplete,
					upload_start_handler : uploadStart,
					upload_progress_handler : uploadProgress,
					upload_error_handler : uploadError,
					upload_success_handler : uploadSuccess,
					upload_complete_handler : uploadComplete,
					upload_completeinfo_handler : uploadCompleteInfo
				};

				swfu = new SWFUpload(settings);
			 };
		</script>
		<form id="editorform88" name="editorform88" method="post" enctype="multipart/form-data" action="'.BURL('products/save').'">
		<input type="hidden" name="pro_id" value="' . $pro_id . '">';

		if($pro_id){
			TableHeader('编辑产品: <span class=note>' . $product['title'] . '</span>');
		}else{
			TableHeader('添加产品');
		}

		if($uploaded_counts > 0){
			$uploaded_file_str = '';

			foreach($uploaded_images as $value){
				$uploaded_file_str .= '<div>' . (++$key) . ') ' .$value . '</div>';
			}

			TableRow(array('<B>未处理的组图片:</B>', '<font class=redb>重要提示:</font> 您共有<font class=redb> ' .$uploaded_counts .'</font> 个已经上传, 但未正常处理的组图片文件, 如果您不删除它们, 本次保存将自动添加这些组图片. <BR>' . $uploaded_file_str . '<BR><a class="link-btn ajax">全部删除</a>
			<script type="text/javascript">
				$(function(){
					$("#main a.open").click(function(e){
						var _me=$(this);
						var w = parseInt(_me.attr("w"));
						var h = parseInt(_me.attr("h"));
						var filename=_me.html();

						if(w >= h){
							h = parseInt(h * 400 / w);
							w = 400;
						}else{
							w = parseInt(w * 400 / h);
							h = 400;
						}

						$.dialog({title: "查看及确认删除", lock:true, padding: 8, content: "<img  width="+w+"  height="+h+"  src=\'' . SYSDIR . 'uploads/" + filename + "\'>", okValue:"  删除  ", ok:function(){
								ajax("' . BURL('products/ajax?action=deleteone') . '", {file: filename}, function(data){
									_me.parent().hide();
								});
						},cancelValue:"取消",cancel:true});

						e.preventDefault();
					});

					$("#main a.ajax").click(function(e){
						var _me=$(this);
						$.dialog({title:"操作确认",lock:true,content:"确定删除已上传的图片文件吗?",okValue:"  确定  ",
						ok:function(){
							ajax("' . BURL('products/ajax?action=deletelast') . '", {}, function(data){
								_me.parent().parent().hide();
							});
						},
						cancelValue:"取消",cancel:true});
						e.preventDefault();
					});

				});
			</script>'));
		}

		if($pro_id){
			TableRow(array('<b>' . Iif($product['is_show'] == '1', '浏览产品', '<font class=red>' . Iif($product['is_show'] == '-1', '待审产品', '禁用产品') . '</font>') . ':</b>', Iif($product['is_show'] == '1', '<a href="'.URL('products?id=' . $pro_id).'" target="_blank">') . '<input type="hidden" name="pro_path" value="' . $product['path'] . '"><input type="hidden" name="pro_filename" value="' . $product['filename'] . '"><img src="'. GetImageURL($product['path'], $product['filename']).'" align="absmiddle" class="ZoomImg">'. Iif($product['is_show'] == '1', '</a>') . '<a title="编辑 '.$product['username'].'" href="'.BURL('users/edit?userid='.$product['userid']).'"><img src="' . GetAvatar($product['userid']) . '" class="user_avatar wh30" style="margin-left:60px"></a><a href="'.BURL('pm/send?userid='.$product['userid']).'"><img src="' . SYSDIR . 'public/admin/images/pm.png" title="发送短信给作者: ' . $product['username'] . '"></a>'));
		}

		TableRow(array('<B>'. Iif($pro_id, '重新上传主图片', '上传主图片') . ':</B>',
		'<div class="fileupload" id="fileupload">
		<input type="text" class="file_text" id="file_text" disabled>
		<input type="button" class="cancel" value="选择文件">
		<input type="file" name="imagefile" class="file_input" size="46" onchange="$(\'#file_text\').val(this.value);">
		</div>&nbsp;&nbsp;&nbsp;&nbsp;<span class=light>注: <span class=note>仅允许上传JPG, PNG, GIF类型的图片文件</span>.</span>'));

		if($pro_id){
			TableRow(array('<B>是否删除?</B>', '<input type="checkbox" name="deletethisproduct" value="1"> <b>是:</b> <font class=redb>慎选!</font> <span class=light>如果选择删除, 此产品相关的所有信息将被删除.</span>'));
		}

		TableRow(array('<B>产品名称(<span class=blue>中文</span>):</B>', '<input type="text" name="title" value="' . $product['title'] . '"  size="50"> <font class=red>* 必填项</font>'));
		TableRow(array('<B>产品名称(<span class=red>英文</span>):</B>', '<input type="text" name="title_en" value="' . $product['title_en'] . '"  size="50">'));

		TableRow(array('<B>产品分类:</B>', '<input type="hidden" name="oldcat_id" value="' . $product['cat_id'] . '">'.$this->GetSelect($product['cat_id']) . ' <font class=red>* 必填项</font>'));

		if($pro_id){
			TableRow(array('<B>排序编号:</B>', '<input type="text" name="sort" value="' . $product['sort'] . '"  size="10"> <span class=light>注: 当前分类中产品将按此编号排序.</span>'));
		}

		$Radio = new SRadio;
		$Radio->Name = 'is_show';
		$Radio->SelectedID = $product['is_show'];
		$Radio->AddOption(1, '发布', '&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(-1, '待审', '&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(0, '禁用', '&nbsp;&nbsp;');

		TableRow(array('<b>发布状态?</b>', $Radio->Get()));

		TableRow(array('<B>是否推荐?</B>', '<input type="checkbox" name="is_best" value="1" '.Iif($product['is_best'] == 1, 'CHECKED').'> <b>是:</b> <span class=light>推荐的产品显示在重要的位置.</span>'));

		TableRow(array('<B>产品价格(<span class=blue>中文</span>):</B>', '<input type="text" name="price" value="' . $product['price'] . '" size="30"> <span class=light>注: 可填写价格及单位.</span>'));
		TableRow(array('<B>产品价格(<span class=red>英文</span>):</B>', '<input type="text" name="price_en" value="' . $product['price_en'] . '" size="30"> <span class=light>注: 同上</span>'));

		TableRow(array('<B>Meta关键字(<span class=blue>中文</span>):</B>', '<input type="text" name="keywords" value="' . $product['keywords'] . '" size="50"> <span class=light>注: 产品的Meta关键字, <span class=note>便于搜索引擎收录, 请用英文逗号隔开</span>.</span>'));
		TableRow(array('<B>Meta关键字(<span class=red>英文</span>):</B>', '<input type="text" name="keywords_en" value="' . $product['keywords_en'] . '" size="50"> <span class=light>注: 同上</span>'));

		TableRow(array('<B>产品正文:</B><BR><span class=light>产品的详细内容.</span>', '
			<div class="ok_tab">
				<div class="ok_tabheader">
					<ul id="tabContent-li-ok_tabOn-">
						<li class="ok_tabOn"><a href="javascript:void(0)" title="中文内容" rel="1" hidefocus="true">中文内容</a></li>
						<li><a href="javascript:void(0)" title="英文内容" rel="2" hidefocus="true">英文内容</a></li>
					</ul>
				</div>
				<div id="tabContent_1" class="tabContent">
				 <textarea name="content" style="width:100%;height:400px;visibility:hidden;" id="content">'.$product['content'].'</textarea>
				</div>

				<div id="tabContent_2" class="tabContent" style="display: none;">
				<textarea name="content_en" style="width:100%;height:400px;visibility:hidden;" id="content_en">'.$product['content_en'].'</textarea>
				</div>

				<div class="ok_tabbottom">
					<span class="tabbottomL"></span>
					<span class="tabbottomR"></span>
				</div>
			</div>
			<script type="text/javascript">new tab(\'tabContent-li-ok_tabOn-\', \'-\');</script>
		'));

		if($pro_id AND $gimagetable){
			TableRow(array('<B>组图片列表:</B>', '<div style="width:800px;">' . $gimagetable . '</div>'));
		}

		TableRow(array('<B>上传组图片:</B><BR><span class=light>组图片是辅助展示图片.</span>', '<div id="swfupload">
			<div class="fieldset">
				<span class="legend">上传文件列表</span>
				<table id="file_table" cellpadding="0" cellspacing="0" class="file_table">
					<thead>
						<tr>
							<th width="30">#</th>
							<th width="180">文件</th>
							<th width="60">大小</th>
							<th width="180">状态</th>
							<th width="40">操作</th>
						</tr>
					</thead>
					<tbody id="fsUploadProgress"></tbody>
				</table>
				<div class="filesStatus" id="filesStatus">请选择(可多选)需要上传的文件! <span class=light>注: 仅允许上传 <span class=note>JPG, PNG, GIF</span> 类型的图片文件</span></div>
			</div>
			<div class="buttons">
				<span id="spanButtonPlaceHolder"></span>
				<input id="btnUpload" type="button" onfocus="this.blur();" onclick="swfu.startUpload();" disabled="disabled" class="btnUpload_disabled">
				<input id="btnCancel" type="button" onfocus="this.blur();" onclick="swfu.cancelQueue();" disabled="disabled" class="btnCancel_disabled">
			</div>
		</div>'));

		TableFooter();

		PrintSubmit(Iif($pro_id, '保存更新', '添加产品'));

		//输出小图片放大镜效果的JS
		PrintZoomJS();
	}

}

?>