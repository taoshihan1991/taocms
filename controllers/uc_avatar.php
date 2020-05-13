<?php if(!defined('ROOT')) die('Access denied.');

class c_uc_avatar extends SWeb{
	public function __construct($path){
		parent::__construct($path);

		//所有用户中心的控制器都需要在构造函数中先验证登录权限
		$this->CheckAction('login');
	}

    public function index(){
		$this->assign('title', $this->langs['u_uploadav'] . ' - ' . $this->title); //页面标题
		$this->assign('pagenav', GetNavLinks(array($this->langs['uc'] => 'uc', $this->langs['u_uploadav'] => 'uc_avatar'))); //分配导航栏

		//为当前页面中的上传头像设置语言数组, 不加到系统语言里是因为这些语言很少用
		if(IS_CHINESE){
			$langsforme = array(
				'warning' => '本组件需要安装Flash Player后才可使用，请从<a href="http://www.adobe.com/go/getflashplayer" target="_blank"> www.adobe.com </a>下载安装。',
				'over_limit' => '文件大小（%7b0%7d）超出限制（2MB）\n请重新选择!',
				'browse_button' => '请点击按钮选择图片',
				'browse_tip' => '仅支持JPG、JPEG、GIF、PNG格式的图片文件\n文件不能大于2MB',
				'avatar_sizes_desc' => '120*120像素|48*48像素',
				'avatar_intro' => '保存后将生成以下两个规格的头像:',
				'tooltip_zoomIn' => '放大',
				'tooltip_zoomOut' => '缩小',
				'tooltip_zoomNone' => '按图片窗口大小显示',
				'tooltip_rotateCW' => '顺时针旋转',
				'tooltip_rotateCCW' => '逆时针旋转',
				'tooltip_reset' => '重置',
				'saved' => '头像保存成功!'
			);
		}else{
			$langsforme = array(
				'warning' => 'This plugin needs Flash Player, please download from: <a href="http://www.adobe.com/go/getflashplayer" target="_blank">www.adobe.com</a>',
				'over_limit' => 'Filesize（%7b0%7d）exceeds limit（2MB）\nPlease select another one!',
				'browse_button' => 'Click the button up to select file',
				'browse_tip' => 'File type of JPG、JPEG、GIF、PNG allowed\nFilesize limit: 2MB',
				'avatar_sizes_desc' => '120*120px|48*48px',
				'avatar_intro' => 'Two avatars will be saved, the dimensions as below:',
				'tooltip_zoomIn' => 'Zoom In',
				'tooltip_zoomOut' => 'Zoom Out',
				'tooltip_zoomNone' => 'Show As This Image Frame',
				'tooltip_rotateCW' => 'Rotate Clockwise',
				'tooltip_rotateCCW' => 'Rotate Anticlockwise',
				'tooltip_reset' => 'Reset',
				'saved' => 'Your avatars save successfully!'
			);
		}

		$this->assign('langsforme', $langsforme);
		$this->assign('submenu', UCSub($this->langs['u_uploadav'])); //标题及二级菜单
		$this->display('uc_avatar.html');
	}
}

?>