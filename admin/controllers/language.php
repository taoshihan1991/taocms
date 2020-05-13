<?php if(!defined('ROOT')) die('Access denied.');

class c_language extends SAdmin{

	public function __construct($path){
		parent::__construct($path);

		$this->lang_path = ROOT.'public/languages/';
	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){
		//ajax权限验证
		if(!$this->CheckAccess('language')){
			$this->ajax['s'] = 0; //ajax操作失败
			$this->ajax['i'] = '您没有权限设置或管理网站语言!';
			die($this->json->encode($this->ajax));
		}
		
		$action = ForceStringFrom('action');
		if($action == 'setlang'){
			$this->select();
		}elseif($action == 'delete'){
			$file = ForceStringFrom('file');

			//不允许删除系统默认的语言文件
			if($file == 'English.php' OR $file == 'Chinese.php'){
				$this->ajax['s'] = 0; //ajax操作失败
				$this->ajax['i'] = '系统默认的语言文件无法删除!';
			}else{

				if(@unlink($this->lang_path.$file))	{
					//无动作
				}else{
					$this->ajax['s'] = 0; //ajax操作失败
					$this->ajax['i'] = '无法删除语言文件! 文件夹不可写或文件不存在.';
				}
			}
		}elseif($action == 'savelang'){
			$result = $this->save(); //保存当前语言文件
			if($result !== true){
				$this->ajax['s'] = 0; //ajax操作失败
				$this->ajax['i'] = $result;
			}

		}elseif($action == 'refreshcache'){
			$Langs = GetLangs(1); //获取所有PHP语言文件名含后缀
			foreach($Langs as $file){
				$result = $this->refreshcache($file); //更新JS缓存
			}

			if($result !== true){
				$this->ajax['s'] = 0; //ajax操作失败
				$this->ajax['i'] = $result;
			}
		}

		die($this->json->encode($this->ajax));
	}

	//更新JS缓存, 将语言生成JS缓存是为了方便各种JS调用
    private function refreshcache($file){
		if(!is_writable($this->lang_path)) return "语言文件夹(/public/languages/)不可写! 请将其属性设置为: 777";

		$filename = explode(".", $file);
		$filename = $filename[0];
		if(!$filename) return "更新缓存失败, PHP语言文件不存在!";

		$js_langpath = ROOT . "public/languages/$filename.js";
		$langs = require(ROOT . "public/languages/$filename.php"); //读取

		$json = new SJSON;
		$contents = "var langs = " . $json->encode($langs); //将语言生成js对象
		file_put_contents($js_langpath, $contents, LOCK_EX);

		return true;
	}

	//选择并设置语言
    private function select(){
		$siteDefaultLang    = ForceStringFrom('siteDefaultLang');

		if(APP::$_CFG['siteDefaultLang'] != $siteDefaultLang){
			$filename = ROOT . "config/settings.php";
			$fp = @fopen($filename, 'rb');
			$contents = @fread($fp, @filesize($filename));
			@fclose($fp);
			$contents =  trim($contents);

			$contents = preg_replace("/[$]_CFG\['siteDefaultLang'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['siteDefaultLang'] = \"$siteDefaultLang\";", $contents);

			$fp = @fopen($filename, 'w');
			@fwrite($fp, $contents);
			@fclose($fp);
		}
	}

	//保存语言文件
    public function save(){
		$filename = ForceStringFrom('filename');
		$file = $this->lang_path . $filename;

		if (is_writable($file)) {
			$filecontent = trim($_POST['filecontent']);
			if (get_magic_quotes_gpc()) {
				$filecontent = stripslashes($filecontent);
			}

			$fd = fopen($file, 'wb');
			fputs($fd,$filecontent);

			return $this->refreshcache($filename); //更新JS缓存
		}else{
			return "语言文件($filename)不可写! 请将其属性设置为: 777";
		}
	}

	//编辑语言文件
    public function edit(){
		SubMenu('语言管理', array(array('语言列表及操作', 'language')));

		$filename = ForceStringFrom('filename');
		$filepath = $this->lang_path . $filename;

		if(!is_file($filepath)) Error('正在打开的文件不存在!', '打开文件错误');

		$filecontent = htmlspecialchars(implode("",file($filepath)));

		echo '<form method="post" name="editform" onsubmit="return false;">
		<input type="hidden" name="filename" value="' . $filename . '">
		<input type="hidden" name="action" value="savelang">';

		TableHeader('编辑语言文件: &nbsp;' . BASEURL . "public/languages/$filename");

		TableRow('<b>注意:</b> <span class=note>语言文件为PHP程序文件, 请使用正确的标点符号, 不正确的编辑可能导致前台运行错误!</span><BR><textarea rows="26" style="width:90%;margin-top:8px" name="filecontent" >' . $filecontent . '</textarea>');

		TableFooter();

		echo '<div class="submit"><input type="submit" id="updatelang" value="保存更新" class="save"><input class="cancel" type="submit" name="cancel" value="返回" onclick="history.back();return false;"></div></form>
		<script type="text/javascript">
			$(function(){
				$("#updatelang").click(function(e){
					var form = $(this).closest("form");

					$.dialog({title:"操作确认",lock:true,content:"<font class=red>确定保存更新语言文件: ' . $filename . ' 吗?</font>",okValue:"  确定  ",
					ok:function(){
						ajax("' . BURL('language/ajax') . '", form.serialize(), function(data){
							$.dialog({title:"操作成功",lock:true,content:"<span class=blue>操作成功! 当前语言文件已更新.</span>",okValue:"  确定  ",ok:true,time:1000});
						});
					},
					cancelValue:"取消",cancel:true});
					e.preventDefault();
				});
			});
		</script>';
	}

    public function index(){
		SubMenu('语言管理', array(array('语言列表及操作', 'language', 1)));

		$Langs = GetLangs();
		foreach($Langs as $val){
			$langoptions .='<option value="'.$val.'"' . Iif(APP::$_CFG['siteDefaultLang'] == $val, ' SELECTED') . '>'.$val.'</option>';
		}

		TableHeader('设置前台语言 | 更新缓存');
		TableRow('<form>
			<b>设置前台语言:</b>&nbsp;&nbsp;<select name="siteDefaultLang"><option value="Auto"' . Iif(APP::$_CFG['siteDefaultLang'] == 'Auto', ' SELECTED') . '>自动</option>'.$langoptions.'</select>&nbsp;&nbsp;
			<input type="submit" value="保存设置" class="cancel" id="setlang">&nbsp;&nbsp;&nbsp;<font class=grey>注: 当选择 <span class=note>自动</span> 时, 前台将根据用户浏览器自动选择语言, 中文浏览器进入中文, 其它语言浏览器进入英文.</font>
			</form>');

		TableRow('<form>
			<b>更新JS缓存:</b> &nbsp;&nbsp;
			<input type="submit" value="更新缓存" class="cancel" id="refreshcache">&nbsp;&nbsp;&nbsp;<font class=grey>注: 如果不是在线编辑PHP语言文件, 需要手动更新语言的JS缓存, 否则JS中无法调用修改后的语言变量.</font>
			</form>');
		TableFooter();

		BR(2);

		TableHeader('语言文件列表');

		$files   = GetLangs(1);
		$columncount = 0;

		echo '<td class="td last"><table width="100%" border="0" cellpadding="5" cellspacing="0">';

		for($i = 0; $i < count($files); $i++) {
			$columncount++;

			if($columncount == 1)	{
				echo '<tr>';
			}

			echo '<td width="33%">';
			$this->DisplayFileDetails($files[$i]);
			echo '</td>';

			if($columncount == 3)	{
				echo '</tr>';
				$columncount = 0;
			}
		}
		@closedir($handle);

		if($columncount != 0 && $columncount != 3){
			while($columncount < 3){
				$columncount++;
				echo '<td>&nbsp;</td>';
			}
			echo '</tr>';
		}

		echo '</table></td>';

		TableFooter();

		echo '<script type="text/javascript">
				$(function(){
					$("#setlang").click(function(e){
						var data = $(this).parent().serialize();
						ajax("' . BURL('language/ajax?action=setlang') . '", data, function(data){
							$.dialog({title:"操作成功",lock:true,content:"<span class=blue>Ajax操作, 网站前台默认语言设置成功.</span>",okValue:"  确定  ",ok:true,time:1000});
						});

						e.preventDefault();
					});

					$("#refreshcache").click(function(e){
						ajax("' . BURL('language/ajax?action=refreshcache') . '", {}, function(data){
							$.dialog({title:"操作成功",lock:true,content:"<span class=blue>Ajax操作, 语言的JS缓存已更新.</span>",okValue:"  确定  ",ok:true,time:1000});
						});

						e.preventDefault();
					});

					$("#main a.ajax").click(function(e){
						var _me=$(this);
						$.dialog({title:"操作确认",lock:true,content:"<font class=red>确定删除语言文件: " + _me.attr("file") + " 吗?</font>",okValue:"  确定  ",
						ok:function(){
							ajax("' . BURL('language/ajax?action=delete') . '", {file: _me.attr("file")}, function(data){
								_me.parent().parent().hide();
							});
						},
						cancelValue:"取消",cancel:true});
						e.preventDefault();
					});
				});

				</script>';

	} 

	private function DisplayFileDetails($file){
		echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td width="10" valign="top" style="padding-right: 15px;">
		<a href="'.BURL('language/edit?filename=' . $file).'"><img style="border:1px solid #e8e8e8; padding:3px;" src="'.SYSDIR .'public/admin/images/editablefile.gif" /></a>
		</td>
		<td valign="top">
		<b>' . $file . '</b> (' .DisplayFilesize(@filesize($this->lang_path . $file)). ')<br /><br />
		<a href="'.BURL('language/edit?filename=' . $file).'" class="link-btn">编辑文件</a>
		<a file="' . $file . '" class="link-btn ajax">删除文件</a>
		</td>
		</tr>
		</table>';
	}
} 

?>