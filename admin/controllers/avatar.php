<?php if(!defined('ROOT')) die('Access denied.');

class c_avatar extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

	}

	//上传头像(flash文件上传)模拟ajax操作
	public function ajax(){
		$result = array();
		$result['ok'] = false;

		$avatarpath = ROOT . 'uploads/avatars/';
		MakeDir($avatarpath); //avatars文件夹可能不存在, 先创建之

		$userid = sprintf("%09d", $this->admin->data['userid']); // $userid并不真正的用户id
		$dir1 = substr($userid, 0, 3) . '/';
		$dir2 = substr($userid, 3, 2) . '/';
		$dir3 = substr($userid, 5, 2) . '/';
		$avatarname = $dir1. $dir2. $dir3. substr($userid, -2); //头像文件名不含后缀

		MakeDir($avatarpath.$dir1);
		MakeDir($avatarpath.$dir1.$dir2);
		MakeDir($avatarpath.$dir1.$dir2.$dir3);

		foreach($_FILES AS $key => $file){
			if(!IsUploadedFile($file['tmp_name'])) $error = '无效的图片文件!';
			$image_size = @getimagesize($file['tmp_name']); //看看它是不是图片
			if(!$image_size) $error = '无效的图片文件!';

			if(isset($error)){
				$result['msg'] = $error;
				die($this->json->encode($result));
			}

			$ext = Iif($key == '__avatar1', '1.jpg', '.jpg');
			$avatar = $avatarpath. $avatarname . $ext; //头像绝对路径及文件名
			$ok = @move_uploaded_file($file['tmp_name'], $avatar);
			if(!$ok){
				$result['msg'] = '保存头像文件失败, 请重试!';
				die($this->json->encode($result));
			}
		}

		//返回大头像的URL
		$result['msg'] = SYSDIR . "uploads/avatars/$avatarname" . '1.jpg?' . time(); //加一个参数方便更新原头像
		$result['ok'] = true;
		die($this->json->encode($result));
	}

	public function index(){
		$userid = $this->admin->data['userid'];

		SubMenu('上传头像');

		echo '<script type="text/javascript" src="'. SYSDIR .'public/js/fullavatar/swfobject.js"></script>
		<script type="text/javascript" src="'. SYSDIR .'public/js/fullavatar/fullAvatarEditor.js"></script>';

		TableHeader('我的头像');

		echo '<tr><td class="td" style="padding:18px;vertical-align:top;width:1px;"><a href="' . BURL("users/edit?userid=$userid") . '"><img src="' . GetAvatar($userid, 1) . '" class="user_avatar1" title="当前头像"></a></td>
		<td class="td last" style="padding:18px 0;">
		<div style="">
			<div id="avatar_upload">
				本组件需要安装Flash Player后才可使用，请从<a href="http://www.adobe.com/go/getflashplayer" target="_blank"> www.adobe.com </a>下载安装。
			</div>
			<div style="text-align:center;display:none;" id="avatar_tools">
				<input type="submit" value="保存头像" class="save" id="avatar_save">
				<input type="submit" value="取消" class="cancel" id="avatar_cancel">
			</div>
		</div>
		</td></tr>';

		TableFooter();

		echo '<script type="text/javascript">
		$(function(){
			swfobject.addDomLoadEvent(function () {
				var swf = new fullAvatarEditor("avatar_upload", 420, 760, {
						id: "swf",
						upload_url: "' . BURL('avatar/ajax') . '",
						src_upload:0,
						quality: 88,
						src_size_over_limit: "文件大小（%7b0%7d）超出限制（2MB）\n请重新选择!",
						src_size_over_limit_font: "Microsoft Yahei",
						src_size_over_limit_font_size:12,
						src_box_width: 360,
						src_box_height: 360,
						src_box_border_width: 3,
						tab_visible: false,
						browse_button: "请点击按钮选择图片",
						browse_button_font: "Microsoft Yahei",
						browse_button_color: "#FF9900",
						browse_tip: "仅支持JPG、JPEG、GIF、PNG格式的图片文件\n文件不能大于2MB",
						browse_tip_font_size: 12,
						browse_tip_font: "Microsoft Yahei",
						browse_box_align: "left",
						button_visible: false,
						avatar_sizes: "120*120|48*48",
						avatar_sizes_desc: "120*120像素|48*48像素",
						avatar_intro: "保存后将生成以下两个规格的头像:",
						avatar_intro_font: "Microsoft Yahei",
						avatar_intro_font_size: 12,
						avatar_tools_font: "Microsoft Yahei",
						avatar_tools_font_size:12,
						tooltip_zoomIn: "放大",
						tooltip_zoomOut: "缩小",
						tooltip_zoomNone: "按图片窗口大小显示",
						tooltip_rotateCW: "顺时针旋转",
						tooltip_rotateCCW: "逆时针旋转",
						tooltip_reset: "重置",
						tooltip_font: "Microsoft Yahei",
						tooltip_font_size:12,
						tooltip_color:"#C50006"
					}, function (data) {
						switch(data.code){
							case 2:
								if (data.type == 0) {
									$("#avatar_tools").show();
								} else {
									$("#avatar_tools").hide();
								}
							break;
							case 5 : 
								if(data.type == 0){
									if(data.content.ok){
										$(".user_avatar1").attr("src", data.content.msg);
											$.dialog({title:"操作成功",lock:true,content:"<span class=blue>呵呵, 您的头像已保存!</span>",okValue:"  确定  ",ok:true,time:2000, beforeunload:function(){$("#avatar_cancel").click();}});
									}else{
										$.dialog({title:"操作失败",lock:true,content:"<span class=red>" + data.content.msg + "</span>", okValue:"  确定  ",ok:true});
									}
								}else{
									$.dialog({title:"操作失败",lock:true,content:"<span class=red>保存头像文件失败, 请重试!</span>", okValue:"  确定  ",ok:true});
								}
							break;
						}
					}
				);

				$("#avatar_save").click(function(e){
					swf.call("upload");
					e.preventDefault();
				});
				$("#avatar_cancel").click(function(e){
					$("#avatar_tools").hide();
					swf.call("changepanel", "upload");
					e.preventDefault();
				});
			});
		});
		</script>';
	}
} 

?>