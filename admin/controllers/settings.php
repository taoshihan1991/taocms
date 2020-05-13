<?php if(!defined('ROOT')) die('Access denied.');

class c_settings extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

		SubMenu('网站设置', array(
			array('基本设置', 'settings', Iif($path[1] == 'index',1,0)),
			array('邮件设置', 'settings/mail', Iif($path[1] == 'mail',1,0)),
			array('注册设置', 'settings/register', Iif($path[1] == 'register',1,0))
		));
	}

    public function save(){
		$this->CheckAction('settings'); //权限验证

		$action = ForceStringFrom('action');
		$filename = ROOT . "config/settings.php";

		@chmod($filename, 0777);

		if(!is_writeable($filename)) {
			$errors = '请将系统配置文件config/settings.php设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)){
			Error($errors, '系统设置错误');
		}else{
			$settings    = $_POST['settings'];
			$fp = @fopen($filename, 'rb');
			$contents = @fread($fp, filesize($filename));
			@fclose($fp);
			$contents =  trim($contents);
			$oldcontents = $contents;

			foreach($settings as $key => $value){
				if(APP::$_CFG[$key] != $settings[$key]){
					$value = ForceString($value);
					
					if($key == 'siteKillRobotCode' AND trim($value) == "") $value =  APP::$_CFG[$key];
					if($key == 'siteBaseUrl' AND substr($value, -1) != '/') $value .= '/';

					switch($key){
						case 'siteSmall':
							$value = ForceInt($value);
							if($value < 40) $value = 40;
							break;
						case 'siteMiddle':
							$value = ForceInt($value);
							if($value < 160) $value = 160;
							break;
						case 'siteLarge':
							$value = ForceInt($value);
							if($value < 600) $value = 600;
							break;
						case 'siteSmallH':
							$value = ForceInt($value, '');
							if($value && $value < 40) $value = 40;
							break;
						case 'siteMiddleH':
							$value = ForceInt($value, '');
							if($value && $value < 160) $value = 160;
							break;
						case 'siteLargeH':
							$value = ForceInt($value, '');
							if($value && $value < 600) $value = 600;
							break;
					}

					$code = ForceString($key);
					$contents = preg_replace("/[$]_CFG\['$code'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['$code'] = \"$value\";", $contents);
				}
			}

			if($contents != $oldcontents){
				$fp = @fopen($filename, 'w');
				@fwrite($fp, $contents);
				@fclose($fp);
			}

			Success('settings'. Iif($action, '/'.$action));
		}
	}

    public function index(){

		echo '<form method="post" action="'.BURL('settings/save').'">';

		TableHeader('基本设置');

		TableRow(array('<B>网站URL</B><BR><font class=grey>网站完整的URL, 用于正确显示编辑器中上传的图片、邮件发送等. 请以 <span class=note>/</span> 结束.</font>', '<input type="text" style="width:292px;" name="settings[siteBaseUrl]" value="' . BASEURL . '">'));

		$Radio = new SRadio;
		$Radio->Name = 'settings[siteRewrite]';
		$Radio->SelectedID = APP::$_CFG['siteRewrite'];
		$Radio->AddOption(1, '开启', '<i class="w20"></i>');
		$Radio->AddOption(0, '关闭', '&nbsp;&nbsp;');
		TableRow(array('<B>URL友好访问模式(伪静态)</B><BR><font class=grey>如果服务器是Apache环境, 且Rewrite重写模式有效, 可设置为 <span class=note>开启</span>, 有利于搜索引擎收录您的网页. <BR>如果网站前台链接无效或访问不正常, 说明服务器不支持此功能, 需要重新设置为 <span class=note>关闭</span>.</font>', $Radio->Get()));

		$Select = new SSelect;
		$Select->Name = 'settings[siteTimezone]';
		$Select->SelectedValue = APP::$_CFG['siteTimezone'];
		$Select->AddOption('-12', '(GMT -12) Eniwetok,Kwajalein');
		$Select->AddOption('-11', '(GMT -11) Midway Island,Samoa');
		$Select->AddOption('-10', '(GMT -10) Hawaii');
		$Select->AddOption('-9', '(GMT -9) Alaska');
		$Select->AddOption('-8', '(GMT -8) Pacific Time(US & Canada)');
		$Select->AddOption('-7', '(GMT -7) Mountain Time(US & Canada)');
		$Select->AddOption('-6', '(GMT -6) Mexico City');
		$Select->AddOption('-5', '(GMT -5) Bogota,Lima');
		$Select->AddOption('-4', '(GMT -4) Caracas,La Paz');
		$Select->AddOption('-3', '(GMT -3) Brazil,Buenos Aires,Georgetown');
		$Select->AddOption('-2', '(GMT -2) Mid-Atlantic');
		$Select->AddOption('-1', '(GMT -1) Azores,CapeVerde Islands');
		$Select->AddOption('', '(GMT) London,Lisbon,Casablanca');
		$Select->AddOption('+1', '(GMT +1) Paris,Brussels,Copenhagen');
		$Select->AddOption('+2', '(GMT +2) Kaliningrad,South Africa');
		$Select->AddOption('+3', '(GMT +3) Moscow,Baghdad,Petersburg');
		$Select->AddOption('+4', '(GMT +4) Abu Dhabi,Muscat,Baku,Tbilisi');
		$Select->AddOption('+5', '(GMT +5) Karachi,Islamabad,Tashkent');
		$Select->AddOption('+6', '(GMT +6) Almaty,Dhaka,Colombo');
		$Select->AddOption('+7', '(GMT +7) Bangkok,Hanoi,Jakarta');
		$Select->AddOption('+8', '(GMT +8) 北京, 香港, 新加坡');
		$Select->AddOption('+9', '(GMT +9) Tokyo,Osaka,Yakutsk');
		$Select->AddOption('+10', '(GMT +10) Australia,Guam,Vladivostok');
		$Select->AddOption('+11', '(GMT +11) Magadan,Solomon Islands');
		$Select->AddOption('+12', '(GMT +12) Auckland,Wellington,Fiji');
		TableRow(array('<B>网站默认时区</B><BR><font class=grey>'.APP_NAME.'中英文网站系统将按默认时区显示日期和时间.</font>', $Select->Get()));

		$Select->Clear();
		$Select->Name = 'settings[siteDateFormat]';
		$Select->SelectedValue = APP::$_CFG['siteDateFormat'];
		$Select->AddOption('Y-m-d', "2010-08-12");
		$Select->AddOption('Y-n-j', "2010-8-12");
		$Select->AddOption('Y/m/d', "2010/08/12");
		$Select->AddOption('Y/n/j', "2010/8/12");
		$Select->AddOption('Y年n月j日', "2010年8月12日");
		$Select->AddOption('m-d-Y', "08-12-2010");
		$Select->AddOption('m/d/Y', "08/12/2010");
		$Select->AddOption('M j, Y', "Aug 12, 2010");
		TableRow(array('<B>日期格式</B><BR><font class=grey>系统显示日期的格式.</font>', $Select->Get()));

		TableRow(array('<B>大号图尺寸(像素)</B><BR><font class=grey>上传的产品图片, 将按三种规格保存, 其中大号图的宽和高为:<br><font class=redb>特别注意:</font> <span class=note>如果仅设置大图宽度, 那么在生成图片时, 图片只会被缩放成设定的宽度而不会被裁剪.<br>但是, 如果设置了高度, 那么将按“原图片裁剪位置”中设置的方式裁剪, 而生成统一规格的图片.</span></font>', '<input type="text" style="width:80px;" name="settings[siteLarge]" value="' . APP::$_CFG['siteLarge'] . '"> 宽 * 高 <input type="text" style="width:80px;" name="settings[siteLargeH]" value="' . APP::$_CFG['siteLargeH'] . '">'));
		TableRow(array('<B>中号图尺寸(像素)</B><BR><font class=grey>生成中号图的宽和高为 (<font class=note>缩放或裁剪规则同上</font>):</font>', '<input type="text" style="width:80px;" name="settings[siteMiddle]" value="' . APP::$_CFG['siteMiddle'] . '"> 宽 * 高 <input type="text" style="width:80px;" name="settings[siteMiddleH]" value="' . APP::$_CFG['siteMiddleH'] . '">'));
		TableRow(array('<B>小号图尺寸(像素)</B><BR><font class=grey>生成小号图的宽和高为 (<font class=note>缩放或裁剪规则同上</font>):</font>', '<input type="text" style="width:80px;" name="settings[siteSmall]" value="' . APP::$_CFG['siteSmall'] . '"> 宽 * 高 <input type="text" style="width:80px;" name="settings[siteSmallH]" value="' . APP::$_CFG['siteSmallH'] . '">'));

		$Radio ->Clear();
		$Radio->Name = 'settings[siteScalePosition]';
		$Radio->SelectedID = APP::$_CFG['siteScalePosition'];
		$Radio->AddOption(1, '水平或垂直中间部分', '<i class="w20"></i>');
		$Radio->AddOption(0, '从左侧或顶部开始', '&nbsp;&nbsp;');
		TableRow(array('<B>原图片裁剪位置</B><BR><font class=grey>当以上三项设置了图片高度时, 生成图片时会被裁剪, 裁剪位置为:</font>', $Radio->Get()));

		TableRow(array('<B>网站名称(<span class=blue>中文</span>)</B><BR><font class=grey>在网站页面底部等处的版权信息, 邮件中显示的中文网站名称.</font>', '<input type="text" style="width:292px;" name="settings[siteCopyright]" value="' . APP::$_CFG['siteCopyright'] . '">'));

		TableRow(array('<B>网站名称(<span class=red>English</span>)</B><BR><font class=grey>在网站页面底部等处的版权信息, 邮件中显示的英文网站名称.</font>', '<input type="text" style="width:292px;" name="settings[siteCopyrightEn]" value="' . APP::$_CFG['siteCopyrightEn'] . '">'));

		TableRow(array('<B>网站标题(<span class=blue>中文</span>)</B><BR><font class=grey>显示在浏览器上方的中文网站Title标题.</font>', '<input type="text" style="width:292px;" name="settings[siteTitle]" value="' . APP::$_CFG['siteTitle'] . '">'));

		TableRow(array('<B>网站标题(<span class=red>English</span>)</B><BR><font class=grey>显示在浏览器上方的中文网站Title标题.</font>', '<input type="text" style="width:292px;" name="settings[siteTitleEn]" value="' . APP::$_CFG['siteTitleEn'] . '">'));

		TableRow(array('<B>Meta关键字(<span class=blue>中文</span>)</B><BR><font class=grey>便于搜索引擎收录和搜索您的网站, 多个Meta关键字需用英文逗号隔开.</font>', '<input type="text" style="width:292px;" name="settings[siteKeywords]" value="' . APP::$_CFG['siteKeywords'] . '">'));

		TableRow(array('<B>Meta关键字(<span class=red>English</span>)</B><BR><font class=grey>便于搜索引擎收录和搜索您的网站, 多个Meta关键字需用英文逗号隔开.</font>', '<input type="text" style="width:292px;" name="settings[siteKeywordsEn]" value="' . APP::$_CFG['siteKeywordsEn'] . '">'));

		TableRow(array('<B>Description描述(<span class=blue>中文</span>)</B><BR><font class=grey>一段话介绍便于搜索引擎收录和搜索您的网站</font>', '<input type="text" style="width:292px;" name="settings[siteDescription]" value="' . APP::$_CFG['siteDescription'] . '">'));

		TableRow(array('<B>Description描述(<span class=red>English</span>)</B><BR><font class=grey>一段话介绍便于搜索引擎收录和搜索您的网站</font>', '<input type="text" style="width:292px;" name="settings[siteDescriptionEn]" value="' . APP::$_CFG['siteDescriptionEn'] . '">'));

		TableRow(array('<B>网站备案信息</B><BR><font class=grey>在网站页面底部添加备案信息链接, <span class=note>不显示可留空</span>.</font>', '<input type="text" style="width:292px;" name="settings[siteBeian]" value="' . APP::$_CFG['siteBeian'] . '">'));

		$Radio ->Clear();
		$Radio->Name = 'settings[siteActived]';
		$Radio->SelectedID = APP::$_CFG['siteActived'];
		$Radio->AddOption(1, '开启', '<i class="w20"></i>');
		$Radio->AddOption(0, '关闭', '&nbsp;&nbsp;');
		TableRow(array('<B>开启或关闭网站</B><BR><font class=grey>当系统进行升级, 数据库备份或恢复等维护操作时, 推荐先关闭网站.</font>', $Radio->Get()));

		TableRow(array('<B>关闭时显示(<span class=blue>中文</span>)</B><BR><font class=grey>网站关闭后显示的中文提示信息(允许HTML).</font>', '<textarea name="settings[siteOffTitle]" rows="4" style="width:292px;">' . APP::$_CFG['siteOffTitle'] . '</textarea>'));

		TableRow(array('<B>关闭时显示(<span class=red>English</span>)</B><BR><font class=grey>网站关闭后显示的英文提示信息(允许HTML).</font>', '<textarea name="settings[siteOffTitleEn]" rows="4" style="width:292px;">' . APP::$_CFG['siteOffTitleEn'] . '</textarea>'));

		TableFooter();

		PrintSubmit('保存设置', '取消');
	} 

    public function mail(){

		echo '<form method="post" action="'.BURL('settings/save').'">
		<input type="hidden" name="action" value="mail">';

		TableHeader('邮件设置');

		TableRow(array('<B>网站Email地址</B><BR><font class=grey>接收用户邮件, 及发送邮件时显示在邮件的回复地址中.</font>', '<input type="text" style="width:292px;" name="settings[siteEmail]" value="' . APP::$_CFG['siteEmail'] . '">'));

		TableRow(array('<B>邮件发送方式</B><BR><font class=grey>如果网站服务器是Windows系统, 则必须选择SMTP方式才能发送邮件(<span class=note>要求服务器php环境支持Sockets</span>).<BR>UNIX或linux服务器则推荐使用PHP Mail函数发送邮件.</font>', '<input type="radio" id="m1" name="settings[siteUseSmtp]" value="0" '.Iif(!APP::$_CFG['siteUseSmtp'], ' checked="checked"').'><label for="m1">PHP Mail</label><i class="w20"></i><input type="radio" id="m2" name="settings[siteUseSmtp]" value="1" '.Iif(APP::$_CFG['siteUseSmtp'], ' checked="checked"').'><label for="m2">SMTP</label>'));

		TableRow(array('<B>-- SMTP服务器地址</B><BR><font class=grey>如: mailer.weensoft.cn 或SMTP邮件服务器IP地址.</font>', '<input type="text" style="width:292px;" name="settings[siteSmtpHost]" value="' . APP::$_CFG['siteSmtpHost'] . '">'));
		TableRow(array('<B>-- SMTP服务器端口</B><BR><font class=grey>SMTP邮件服务器的端口号, 一般为25.</font>', '<input type="text" style="width:292px;" name="settings[siteSmtpPort]" value="' . APP::$_CFG['siteSmtpPort'] . '">'));
		TableRow(array('<B>-- SMTP服务器邮箱</B><BR><font class=grey>使用当前SMTP邮件服务器时您的Email地址, 此Email地址仅用于发送邮件, 不用于接收Email.</font>', '<input type="text" style="width:292px;" name="settings[siteSmtpEmail]" value="' . APP::$_CFG['siteSmtpEmail'] . '">'));
		TableRow(array('<B>-- SMTP服务器邮箱用户名</B><BR><font class=grey>登录SMTP服务器邮箱的用户名. 注: 有的SMTP服务器需求填写为用户名对应的邮箱地址.</font>', '<input type="text" style="width:292px;" name="settings[siteSmtpUser]" value="' . APP::$_CFG['siteSmtpUser'] . '">'));
		TableRow(array('<B>-- SMTP服务器用户密码</B><BR><font class=grey>登录SMTP服务器邮箱的用户密码.</font>', '<input type="password" style="width:292px;" name="settings[siteSmtpPassword]" value="' . APP::$_CFG['siteSmtpPassword'] . '">'));

		TableFooter();

		PrintSubmit('保存设置');
	} 

    public function register(){

		echo '<form method="post" action="'.BURL('settings/save').'">
		<input type="hidden" name="action" value="register">';

		TableHeader('注册设置');

		$Radio = new SRadio;
		$Radio->Name = 'settings[siteAllowRegister]';
		$Radio->SelectedID = APP::$_CFG['siteAllowRegister'];
		$Radio->AddOption(1, '开放', '<i class="w20"></i>');
		$Radio->AddOption(0, '关闭', '&nbsp;&nbsp;');
		TableRow(array('<B>开放用户注册</B><BR><font class=grey>是否开放或关闭网站前台用户注册?</font>', $Radio->Get()));

		$Select = new SSelect;
		$Select->Name = 'settings[siteRegisterCheck]';
		$Select->SelectedValue = APP::$_CFG['siteRegisterCheck'];
		$Select->AddOption('Auto', '不验证');
		$Select->AddOption('EmailVerify', '邮件验证');
		$Select->AddOption('AdminVerify', '人工审核');
		TableRow(array('<B>注册验证</B><BR><font class=grey>验证用户注册的方式. 当设置为 <span class=note>不验证</span> 时, 注册后立即拥有注册会员资格.</font>', $Select->Get()));

		TableRow(array('<B>防恶意注册码</B><BR><font class=grey>此码有效防止机器人恶意注册, 可时常更换, 但<span class=note>不能设置为空</span>.</font>', '<input type="text" style="width:292px;" name="settings[siteKillRobotCode]" value="' . APP::$_CFG['siteKillRobotCode'] . '">'));

		TableFooter();

		PrintSubmit('保存设置');
	} 

} 

?>