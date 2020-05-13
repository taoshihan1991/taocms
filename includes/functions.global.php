<?php if(!defined('ROOT')) die('Access denied.');


//前台伪静态处理函数
function URL($url = ''){
	return RW_FRONTEND . $url;
}


//获取图片的URL
function GetImageURL($path, $filename, $size = 1){
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

	return SYSDIR  . "uploads/$path/$filename$size";
}

// ##
function DisplayDate($timestamp = 0, $dateformat = '', $time = 0){
	if(!$dateformat){
		$dateformat = APP::$_CFG['siteDateFormat'] . Iif($time, ' H:i:s');
	}

	$timezoneoffset = ForceInt(APP::$_CFG['siteTimezone']);

	return @gmdate($dateformat, Iif($timestamp, $timestamp, time()) + (3600 * $timezoneoffset));
}

// ##
function DisplayFilesize($filesize){

	$kb = 1024;         // Kilobyte
	$mb = 1048576;      // Megabyte

	if($filesize < $kb){
		$size = $filesize . ' B';
	}else if($filesize < $mb){
		$size = round($filesize/$kb,2) . ' K';
	}else{
		$size = round($filesize/$mb,2) . ' M';
	}

	return (isset($size) AND $size != '0 B' AND  $size != ' B') ? $size : 0;
}

// ##
function Iif($expression, $returntrue, $returnfalse = ''){
	if($expression){
		return $returntrue;
	}else{
		return $returnfalse;
	}
}

// ##
function SafeSql($source){
	$entities_match = array(',',';','$','!','@','#','%','^','&','*','_','(',')','{','}','|',':','"','<','>','?','[',']','\\',"'",'.','/','*','+','~','`','=');
	return str_replace($entities_match, '', trim($source));
}

// ##
function SafeSearchSql($source){
	$entities_match = array('$','!','@','#','%','^','&','*','_','(',')','{','}','|',':','"','<','>','?','[',']','\\',"'",'.','/','*','~','`','=');
	return str_replace($entities_match, '', trim($source));
}


// ##
function IsEmail($email){
	return preg_match("/^[a-z0-9]+[.a-z0-9_-]*@[a-z0-9]+[.a-z0-9_-]*\.[a-z0-9]+$/i", $email);
}

// ##
function IsName($name){
	$entities_match = array(',',';','$','!','@','#','%','^','&','*','(',')','{','}','|',':','"','<','>','?','[',']','\\',"'",'/','*','+','~','`','=');
	for ($i = 0; $i<count($entities_match); $i++) {
	     if(strpos($name, $entities_match[$i])){
               return false;
		 }
	}
   return true;
}

// ##
function IsAlnum($str){
   return preg_match("/^[[:alnum:]]+$/i", $str);
}

// ##
function PassGen($length = 8){
	$str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for ($i = 0, $passwd = ''; $i < $length; $i++)
		$passwd .= substr($str, mt_rand(0, strlen($str) - 1), 1);
	return $passwd;
}

// ##
function IsGet($VariableName) {
	if (isset($_GET[$VariableName])) {
		return true;
	} else {
		return false;
	}

}

// ##
function IsPost($VariableName) {
	if (isset($_POST[$VariableName])) {
		return true;
	} else {
		return false;
	}

}

// ##
function ForceInt($InValue, $DefaultValue = 0) {
	$iReturn = intval($InValue);
	return ($iReturn == 0) ? $DefaultValue : $iReturn;
}

// ##
function ForceString($InValue, $DefaultValue = '') {
	if (is_string($InValue)) {
		$sReturn = EscapeSql(trim($InValue));
		if (empty($sReturn) && strlen($sReturn) == 0) $sReturn = $DefaultValue;
	} else {
		$sReturn = EscapeSql($DefaultValue);
	}
	return $sReturn;
}

// ##
function ForceStringFrom($VariableName, $DefaultValue = '') {
	if (isset($_GET[$VariableName])) {
		return ForceString($_GET[$VariableName], $DefaultValue);
	} elseif (isset($_POST[$VariableName])) {
		return ForceString($_POST[$VariableName], $DefaultValue);
	} else {
		return $DefaultValue;
	}
}

// ##
function ForceIntFrom($VariableName, $DefaultValue = 0) {
	if (isset($_GET[$VariableName])) {
		return ForceInt($_GET[$VariableName], $DefaultValue);
	} elseif (isset($_POST[$VariableName])) {
		return ForceInt($_POST[$VariableName], $DefaultValue);
	} else {
		return $DefaultValue;
	}

}

// ##
function ForceCookieFrom($VariableName, $DefaultValue = '') {
	if (isset($_COOKIE[$VariableName])) {
		return ForceString($_COOKIE[$VariableName], $DefaultValue);
	} else {
		return $DefaultValue;
	}
}

// ##
function EscapeSql($query_string) {

	if(get_magic_quotes_gpc()) {
		$query_string = stripslashes($query_string);
	}

	//XSS过滤
	$xss = new SXss;
	$query_string = $xss->clean($query_string);

	$query_string = htmlspecialchars(str_replace(array('\0', '　'), '', $query_string), ENT_QUOTES);
	
	if(APP::$DB->type == "mysqli"){
		if(function_exists('mysqli_real_escape_string')) {
			$query_string = mysqli_real_escape_string(APP::$DB->conn, $query_string);
		}else{
			$query_string = addslashes($query_string);
		}
	}else{
		if(function_exists('mysql_real_escape_string')) {
			$query_string = mysql_real_escape_string($query_string);
		}else if(function_exists('mysql_escape_string')){
			$query_string = mysql_escape_string($query_string);
		}else{
			$query_string = addslashes($query_string);
		}
	}

	return $query_string;
}

// ##
function html($String) {
	 return str_replace(array('&amp;','&#039;','&quot;','&lt;','&gt;'), array('&','\'','"','<','>'), $String);
}

// ##
function ShortTitle($string, $length=81){
	if(strlen($string) == 0) 	return '';
	if(strlen($string) <= $length) return $string;

	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut = '';

	$n = $tn = $noc = 0;
	while($n < strlen($string)) {
		$t = ord($string[$n]);
		if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
			$tn = 1; $n++; $noc++;
		} elseif(194 <= $t && $t <= 223) {
			$tn = 2; $n += 2; $noc += 2;
		} elseif(224 <= $t && $t < 239) {
			$tn = 3; $n += 3; $noc += 2;
		} elseif(240 <= $t && $t <= 247) {
			$tn = 4; $n += 4; $noc += 2;
		} elseif(248 <= $t && $t <= 251) {
			$tn = 5; $n += 5; $noc += 2;
		} elseif($t == 252 || $t == 253) {
			$tn = 6; $n += 6; $noc += 2;
		} else {
			$n++;
		}

		if($noc >= $length) break;
	}

	if($noc > $length) $n -= $tn;

	$strcut = substr($string, 0, $n);
	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	return $strcut.'...';
}


// ###
function GetIP() {
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$thisip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$thisip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$thisip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$thisip = $_SERVER['REMOTE_ADDR'];
	}

	preg_match("/[\d\.]{7,15}/", $thisip, $thisips);
	$thisip = $thisips[0] ? $thisips[0] : gethostbyname($_SERVER['HTTP_HOST']);
	return $thisip;
}

// ###
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 600) {
	$ckey_length = 4;
	$key = md5($key ? $key : 'default_key');
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

//返回用户头像URL 参数：$size为空时小头像, 为1是大头像
function GetAvatar($userid, $size = '') {
	if(!$userid) return T_URL . "images/noavatar$size.gif"; //游客

	$filename =  GetUserImage($userid, $size);
	return SYSDIR . $filename; //当用户的头像不存在时, 系统使用JS加载默认图片
}

//获得用户头像文件相对路径
function GetUserImage($userid, $size = '') {
	$userid = sprintf("%09d", $userid);
	$dir1 = substr($userid, 0, 3);
	$dir2 = substr($userid, 3, 2);
	$dir3 = substr($userid, 5, 2);
	return "uploads/avatars/$dir1/$dir2/$dir3/" . substr($userid, -2) . "$size.jpg";
}

//按用户ID获取用户已上传临时图片文件名数组(不含路径)
//$more指后台需要显示的一些图片信息等
function get_upload_files($userid, $more = 0){
	$files = array();
	$uploadpath = ROOT . 'uploads/';
	$FolderHandle = @opendir($uploadpath);

	while (false !== ($Item = readdir($FolderHandle))) {
		if ($Item != '.' AND $Item != '..' AND preg_match("/^image" . "_$userid" . "_/i", $Item) AND $imagesize = @getimagesize($uploadpath . $Item)) {
			if($more){
				$files[] = "<a class=\"open\" w=\"$imagesize[0]\" h=\"$imagesize[1]\">$Item</a>&nbsp;&nbsp;&nbsp;&nbsp;$imagesize[0] * $imagesize[1]";
			}else{
				$files[] = $Item;
			}
		}
	}

	@closedir($uploadpath);
	return $files;
}


//获取用户组权限值 未登录时使用
function getActionValue($actions, $item) {
	preg_match("/\\*$item:(\\w+)\\*/i", $actions, $matchs);
	return ForceInt($matchs[1]); //返回值都为整数
}

//判断用户组权限状态 未登录时使用; 参数$action是数组时可以验证多个权限, 并返回array
function getAccess($actions = '', $action = '') {
	if(is_array($action)){
		$sReturn = array();
		foreach($action AS $a){
			$sReturn["$a"] = Iif(strstr($actions, "*$a*"), 1, 0);
		}
	}else{
		$sReturn = Iif(strstr($actions, "*$action*"), 1, 0);
	}
	return $sReturn;
}


/**
 * 邮件发送函数SendMail
 *
 * @param string $email 接受邮件的email地址
 * @param string $subject 邮件主题(标题)
 * @param string $content 邮件内容(正文)
 * @param boolean $html 邮件内容是否以html格式发送, 默认为true. fasle时以文本格式发送
 * @param boolean $lang 邮件内容中文或英文, 默认为1: 中文; 0: 英文
 * @return boolean OR string 发送成功返回true, 失败返回错误信息
 */
function SendMail($email, $subject, $content, $lang = 1, $html = true) {
	if(!$email OR !$subject OR !$content) return false;

	$mail = new SPHPMailer();
	$mail->IsHTML($html); //邮件内容格式

	if(APP::$_CFG['siteUseSmtp']){ //以SMTP方式发送邮件
		$mail->IsSMTP();
		$mail->Host = APP::$_CFG['siteSmtpHost'];
		$mail->Port = APP::$_CFG['siteSmtpPort'];

		$mail->SMTPAuth = true;
		$mail->Username = APP::$_CFG['siteSmtpUser'];
		$mail->Password = APP::$_CFG['siteSmtpPassword'];
		$mail->Sender = APP::$_CFG['siteSmtpEmail'];

	}else{ //使用php mail()函数发送邮件
		$mail->IsMail();
		$mail->Sender = APP::$_CFG['siteEmail'];
	}

	$sitename = Iif($lang, APP::$_CFG['siteCopyright'], APP::$_CFG['siteCopyrightEn']); //中英文名称
	$mail->From = APP::$_CFG['siteEmail'];
	$mail->FromName = $sitename;
	$mail->AddReplyTo(APP::$_CFG['siteEmail'], $sitename); //回复地址及姓名

	$mail->AddAddress($email);
	$mail->Subject  = $subject;

	//在邮件内容最后加上网站版权名称及链接
	$mail->Body = $content . '<br><a href="' . BASEURL . '" target="_blank">' . $sitename . '</a><br>' . DisplayDate() . '<br><br>';

	if($mail->Send()){
		return true;
	}else{
		return $mail->ErrorInfo; //发送失败时返回错误信息
	}
}

//创建文件夹
function MakeDir($path) {
	if (!file_exists($path)) {
		mkdir($path, 0777);
		@chmod($path, 0777);
	}
}

//检测是否为合法的上传文件
function IsUploadedFile($file) {
	return function_exists('is_uploaded_file') && (is_uploaded_file($file) || is_uploaded_file(str_replace('\\\\', '\\', $file)));
}

// 获得文件后缀函数  *参数$filename: 文件名或路径
function getFileExt($filename) {
	$temp_arr = explode(".", $filename);
	$file_ext = strtolower(trim(array_pop($temp_arr)));

	if($filename == $file_ext) return ''; //没有后缀返回空字符串

	return $file_ext;
}

//生成图片函数, 按$nWidth缩小或放大, 如有$nHeight将智能裁剪, 限JPG, GIF, PNG文件
function CreateImageFile($src_path, $des_path, $nWidth, $nHeight = '') {
	$ext = getFileExt($src_path);
	$nWidth = ForceInt($nWidth);
	if(!$ext OR !$nWidth) return false; //如果没有后缀或新宽度返回false

	switch($ext){
		case 'gif':
			$source = @imagecreatefromgif($src_path);
			break;
		case 'jpg':
		case 'jpeg':
			$source = @imagecreatefromjpeg($src_path);
			break;
		case 'png':
			$source = @imagecreatefrompng($src_path);
			break;
		default:
			$source = false;
			break;
	}

	if ($source) {
		$nHeight = ForceInt($nHeight);
		$sourceW = @imagesx($source);
		$sourceH = @imagesy($source);

		$sX = 0; //原图的X坐标
		$sY = 0; //原图的Y坐标
		if($nHeight){ //如果设置了新高度, 图片将缩放且会被裁剪
			$wh1 = $sourceW/$sourceH; //原图长宽比
			$wh2 = $nWidth/$nHeight; //新图长宽比

			if($wh1 >= $wh2){
				//以新图高度为基准缩放裁剪
				$tempW = floor($nWidth * $sourceH / $nHeight);
				if(APP::$_CFG['siteScalePosition']) $sX = ceil(($sourceW - $tempW)/2); //改变原图的X坐标
				$sourceW = $tempW;
			}else{
				//以新图宽度为基准缩放裁剪
				$tempH = floor($nHeight * $sourceW / $nWidth);
				if(APP::$_CFG['siteScalePosition']) $sY = ceil(($sourceH - $tempH)/2); //改变原图的Y坐标
				$sourceH = $tempH;
			}

			$newW = $nWidth; //新图的宽和高等于设定值
			$newH = $nHeight;
		}else{//没有新高度, 则按宽度缩放
			$newW = $nWidth; //新图的宽和高等于设定值
			$newH = ceil($nWidth*$sourceH/$sourceW);
		}

		$dest_thum  = @imagecreatetruecolor($newW, $newH);
		@imagecopyresampled ($dest_thum, $source, 0, 0, $sX, $sY, $newW, $newH, $sourceW, $sourceH);
		@imageinterlace($dest_thum);
		@imagejpeg($dest_thum, $des_path, 88);
		@ImageDestroy($dest_thum);
		@ImageDestroy($source);

		return true;
	}

	return false;
}

?>