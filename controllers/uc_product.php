<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_product extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');

		@set_time_limit(0);  //解除时间限制
		$this->upload_path = ROOT . 'uploads/';

		$this->assign('title', $this->langs['u_mypro'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_mypro'] => 'uc_product'))); //分配导航栏
	}

	private function GetOptions($selectedid = 0, $p_id = 0, $sublevelmarker = ''){
		if($p_id) $sublevelmarker .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		$cats = $this->pcat_ids;
		foreach($cats AS $cat_id => $pid){
			if($p_id == $pid){
				$sReturn .= '<option '. Iif(!$p_id, 'style="color:#cc4911;font-weight:bold;"') . ' value="' . $cat_id . '" ' . Iif($selectedid == $cat_id, 'SELECTED', '') . '>' . $sublevelmarker . $this->pcategories[$cat_id]['name'] . '</option>';

				$sReturn .= $this->GetOptions($selectedid, $cat_id, $sublevelmarker);
			}
		}

		return $sReturn;
	}

	//删除
    public function delete(){
		$this->CheckAction('del_p'); //验证删除权限
		$page = ForceIntFrom('p');   //页码

		$pro_ids = $_POST['deleteids'];
		for($i=0; $i<count($pro_ids); $i++){
			$this->DeleteProductById(ForceInt($pro_ids[$i]));
		}

		Success('uc_product?p=' . $page);
	}

    public function index(){
		$this->assign('submenu', UCSub($this->langs['u_mypro'], array(array($this->langs['u_products'] . $this->langs['list'], 'uc_product', 1), array($this->langs['u_addp'], 'uc_product/add'))));

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$start = $NumPerPage * ($page-1);

		$myid = $this->user->data['userid'];
		$del_p = $this->CheckAccess('del_p'); //是否允许删除
		$add_p = Iif($this->CheckAccess('products'), 1, 0); //是否允许添加, JS验证
		$pnum = ForceInt($this->ActionValue('pnum'));
		$morepros = Iif($add_p && $pnum, $pnum - $this->user->data['p_num']); //还可以添加多少产品

		$sql = "SELECT pro_id, cat_id, is_show, is_best, userid, username, path, filename, clicks, created, ";
		if(IS_CHINESE){
			$sql .= " price, title ";
		}else{
			$sql .= " price_en AS price, title_en AS title ";
		}

		$products = APP::$DB->getAll($sql . " FROM " . TABLE_PREFIX . "product WHERE userid = '$myid' ORDER BY is_show ASC, created DESC LIMIT $start, $NumPerPage");
		$maxrows = APP::$DB->getOne("SELECT COUNT(pro_id) AS value FROM " . TABLE_PREFIX . "product WHERE userid = '$myid'");

		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		$this->assign('products', $products); //产品
		$this->assign('page', $page); //当前页号
		$this->assign('del_p', $del_p); //删除记录的权限
		$this->assign('add_p', $add_p); //添加产品权限
		$this->assign('morepros', $morepros);
		$this->assign('pagelist', GetPageList(URL('uc_product'), $totalpages, $page, 10)); //分页

		$this->display('uc_product.html');
	}


	//编辑调用add
	public function edit(){
		$this->add();
	}

	public function add(){
		$this->CheckAction('products'); //验证添加权限

		$pro_id = ForceIntFrom('pro_id');
		$myid = $this->user->data['userid'];

		if($pro_id){  //编辑时的
			$this->assign('submenu', UCSub($this->langs['u_mypro'], array(array($this->langs['u_products'] . $this->langs['list'], 'uc_product'), array($this->langs['u_editp'], 'uc_product/edit?pro_id='.$pro_id, 1), array($this->langs['u_addp'], 'uc_product/add'))));

			$product = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id' AND userid='$myid'");
			if(!$product) Error('er_noeditp');

			//组图片
			$groupimages = APP::$DB->getAll("SELECT * FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' ORDER BY g_id ASC");
		}else{
			$pnum = ForceInt($this->ActionValue('pnum'));
			if($pnum AND $pnum - $this->user->data['p_num'] <= 0) Error($this->langs['er_pnum']);

			$this->assign('submenu', UCSub($this->langs['u_mypro'], array(array($this->langs['u_products'] . $this->langs['list'], 'uc_product'), array($this->langs['u_addp'], 'uc_product/add', 1))));

			$product = array('pro_id' => 0);
		}

		$uploaded_images = get_upload_files($myid, 1);
		$uploaded_counts = count($uploaded_images);

		$uploaded_file_str = '';
		if($uploaded_counts > 0){
			foreach($uploaded_images as $value){
				$uploaded_file_str .= '<div>' . (++$key) . ') ' .$value . '</div>';
			}
		}

		$this->assign('del_p', $this->CheckAccess('del_p')); //是否允许删除
		$this->assign('pro_id', $pro_id);
		$this->assign('product', $product);
		$this->assign('groupimages', $groupimages);
		$this->assign('procat_options', $this->GetOptions($product['cat_id']));
		$this->assign('uploaded_counts', $uploaded_counts);
		$this->assign('uploaded_file_str', $uploaded_file_str);

		$this->display('uc_product_edit.html');
	}


	public function save(){
		$this->CheckAction('products'); //权限验证

		$pro_id = ForceIntFrom('pro_id');
		if($pro_id) {
			$deletethisproduct = ForceIntFrom('deletethisproduct');

			if($deletethisproduct){//删除产品
				$this->CheckAction('del_p'); //验证删除权限
				$this->DeleteProductById($pro_id);
				Success('uc_product');
			}

			$is_show = ForceIntFrom('is_show');
			if(($is_show ==1 AND !$this->CheckAccess('pRightnow')) OR $is_show == 0){
				$is_show = '-1'; //状态改为待审
			}
		}else{
			$pnum = ForceInt($this->ActionValue('pnum'));
			if($pnum AND $pnum - $this->user->data['p_num'] <= 0) Error($this->langs['er_pnum']);

			$is_show = Iif($this->CheckAccess('pRightnow'), 1, '-1');
		}

		$cat_id = ForceIntFrom('cat_id');
		$oldcat_id = ForceIntFrom('oldcat_id');

		$price = ForceStringFrom('price');
		$price_en = ForceStringFrom('price_en');
		$title = ForceStringFrom('title');
		$title_en = ForceStringFrom('title_en');
		$keywords = ForceStringFrom('keywords');
		$keywords_en = ForceStringFrom('keywords_en');
		$content = ForceStringFrom('content');
		$content_en = ForceStringFrom('content_en');
		$pro_path = ForceStringFrom('pro_path');
		$pro_filename = ForceStringFrom('pro_filename');

		$imagefile         = $_FILES['imagefile'];
		$valid_image_types = array('image/pjpeg',	'image/jpeg', 'image/jpg', 'image/gif', 'image/png', 'image/x-png');

		if(IS_CHINESE){
			if(!$title) $errors[] = '产品的中文名称不能为空！';
			if(!$title_en) $title_en = 'No English Title!';
		}else{
			if(!$title_en) $errors[] = 'The name of product is required!';
			if(!$title) $title = '暂无中文产品名称!';
		}

		if(!$cat_id) $errors[] = $this->langs['er_nocat'];

		if (!function_exists('imagecreatetruecolor')) $errors[] ='服务器PHP环境不支持GD2库, 无法上传图片文件!';

		if (!is_dir($this->upload_path)){
			$errors[] ='保存图片的文件夹: uploads/ 不存在!';
		}else if (!is_writable($this->upload_path)){
			@chmod($this->upload_path, 0777);
			if(!is_writable($this->upload_path)) {
				$errors[] = '保存图片的文件夹: uploads/ 不可写! - 文件夹属性需改为: 0777';
			}
		}

		if(isset($errors))	Error($errors);

		$myid = $this->user->data['userid'];
		$time = time();
		if($pro_id){//编辑产品
			$filesize = $imagefile['size'];

			if($filesize > 0){//有主图片文件上传时
				if($filesize > 2048000)	{
					$errors = '文件大小超过2M限制!';
				}elseif(!in_array($imagefile['type'], $valid_image_types)){
					$errors = '无效的图片文件类型!';
				}elseif (!IsUploadedFile($imagefile['tmp_name']) || !($imagefile['tmp_name'] != 'none' && $imagefile['tmp_name'] && $imagefile['name'])){
					$errors ='上传的文件无效!';
				}

				if(isset($errors)) Error($errors);

				$file_path = DisplayDate($time, 'Y/md');
				$imagename = md5(uniqid(COOKIE_KEY.$time));

				if(!$this->UploadImage($imagefile, $file_path, $imagename)){
					Error('处理产品图片发生错误!');
				}
			}

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET 
			cat_id= '$cat_id',
			is_show= '$is_show',
			" . Iif($filesize AND $file_path AND $imagename, "path = '$file_path', filename = '$imagename',") . "
			price = '$price',
			price_en = '$price_en',
			title = '$title',
			title_en = '$title_en',
			content = '$content',
			content_en = '$content_en',
			keywords = '$keywords',
			keywords_en = '$keywords_en'
			WHERE pro_id = '$pro_id' AND userid= '$myid'");

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

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "gimage SET is_show = 0 WHERE pro_id = '$pro_id'");

			for($i = 0; $i < count($gis_shows); $i++){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "gimage SET 
				is_show = 1
				WHERE pro_id = '$pro_id' AND g_id = ". ForceInt($gis_shows[$i]));
			}

			//处理并保存上传组图片
			$this->SaveGroupImage($pro_id);

			Success('uc_product/edit?pro_id=' . $pro_id);
		}else{//添加产品
			$username = $this->user->data['nickname'];

			$filesize = $imagefile['size'];
			if($filesize == 0 OR $filesize > 2048000)	{
				$errors = '未选择图片文件, 或文件大小超过2M限制!';
			}elseif(!in_array($imagefile['type'], $valid_image_types)){
				$errors = '无效的图片文件类型!';
			}elseif (!IsUploadedFile($imagefile['tmp_name']) || !($imagefile['tmp_name'] != 'none' && $imagefile['tmp_name'] && $imagefile['name'])){
				$errors ='上传的文件无效!';
			}

			if(isset($errors)) Error($errors);

			$file_path = DisplayDate($time, 'Y/md');
			$imagename = md5(uniqid(COOKIE_KEY.$time));

			if($this->UploadImage($imagefile, $file_path, $imagename)){
			
				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "product (cat_id, is_show, userid, username, path, filename, price, price_en, title, title_en, content, content_en, keywords, keywords_en, clicks, created) VALUES ('$cat_id', '$is_show', '$myid', '$username', '$file_path', '$imagename', '$price', '$price_en', '$title', '$title_en', '$content', '$content_en', '$keywords', '$keywords_en', '0', '$time') ");

				$lastid = APP::$DB->insert_id;
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "product SET sort = '$lastid' WHERE pro_id = '$lastid'");
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts+1) WHERE cat_id = '$cat_id'");

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET p_num = (p_num+1) WHERE userid = '$myid'");

				//处理并保存组图片
				$this->SaveGroupImage($lastid);

				Success('uc_product/edit?pro_id=' . $lastid);
			}else{
				Error('处理产品图片发生错误!');
			}

		}
	}

	//删除图片文件
	private function UnlinkImage($path, $filename) {
		@unlink($this->upload_path . $path . '/' . $filename . '_s.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_m.jpg');
		@unlink($this->upload_path . $path . '/' . $filename . '_l.jpg');
	}

	//按产品pro_id 或组图片g_id 删除其图片
	private function DeleteGroupImage($pro_id, $g_id = 0) {
		$more = Iif($g_id, " AND g_id ='$g_id'", "");
		$getimages = APP::$DB->query("SELECT g_id, path, filename FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' " . $more);

		while($image = APP::$DB->fetch($getimages)){
			$this->UnlinkImage($image['path'], $image['filename']);
		}
		
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "gimage WHERE pro_id = '$pro_id' " . $more);
	}

	//按pro_id删除产品
	private function DeleteProductById($pro_id) {
		$myid = $this->user->data['userid'];

		$product = APP::$DB->getOne("SELECT cat_id, userid, path, filename FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id' AND userid='$myid'");
		if(!$product) return false;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "product WHERE pro_id = '$pro_id'");
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "pcat SET counts = (counts-1) WHERE cat_id = '$product[cat_id]'");

		$this->UnlinkImage($product['path'], $product['filename']); //删除主图片

		$this->DeleteGroupImage($pro_id); //删除组图片

		//更新用户的产品数
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET p_num = (p_num-1) WHERE userid = '$myid'");

		//删除评论及更新用户的评论数
		$getcomments = APP::$DB->query("SELECT userid FROM " . TABLE_PREFIX . "comment WHERE for_id = '$pro_id' AND type = 1");
		while($comment = APP::$DB->fetch($getcomments)){
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET pc_num = (pc_num-1) WHERE userid = '$comment[userid]'");
		}
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX ."comment WHERE for_id = '$pro_id' AND type = 1");
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
		$uploaded_images = get_upload_files($this->user->data['userid']);
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

}

?>