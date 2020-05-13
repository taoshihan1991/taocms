<?php if(!defined('ROOT')) die('Access denied.');

//前台基础类继承模板类
class SWeb extends STpl{

	protected $user; //前台用户对象
	public $title;
	public $sitename;
	public $description;
	public $keywords;
	public $pcategories = array(); //产品分类数组
	public $pcat_ids = array(); //产品分类cat_id - 父p_id数组, 用于数组循环提高效率
	public $pcats_ok = array(); //所有的有效(未隐藏)的产品分类cat_id数组(一维), 如果某分类无效其所有下级分类同时无效
	public $acategories = array(); //文章分类
	public $acat_ids = array();
	public $acats_ok = array();
	public $langs = array(); //语言数组成员, 在子类中调用

	public $system_info = ''; //保存系统信息的输出内容(对话框), 在footer.html模板文件中显示出来
	public $display_allowed = true; //是否允许加载模板. 用于在析构函数中判断, 以便加载页头和页尾. 这样才能显示信息对话框

	public function __construct($path = ''){

		include(ROOT . 'includes/functions.common.php'); //加载函数库(包括公共函数库)

		if(IS_CHINESE){
			$this->langs = require(ROOT . 'public/languages/Chinese.php'); //将语言数组赋值给类成员
			$this->title = APP::$_CFG['siteTitle'];
			$this->keywords = APP::$_CFG['siteKeywords'];
			$this->description = APP::$_CFG['siteDescription'];
			$this->sitename = APP::$_CFG['siteCopyright'];

			$js_lang = "Chinese"; //中文JS语言缓存文件名
			$cats_cachename = "cats_cn.php";  //中文分类缓存文件名
		}else{
			$this->langs = require(ROOT . 'public/languages/English.php'); //将语言数组赋值给类成员
			$this->title = APP::$_CFG['siteTitleEn'];
			$this->keywords = APP::$_CFG['siteKeywordsEn'];
			$this->description = APP::$_CFG['siteDescriptionEn'];
			$this->sitename = APP::$_CFG['siteCopyrightEn'];

			$js_lang = "English"; //英文JS语言缓存文件名
			$cats_cachename = "cats_en.php";  //英文分类缓存文件名
		}

		$this->tpl_compile_dir = T_CACHEPATH;  //定义STpl模板缓存路径
		$this->tpl_template_dir = T_PATH;  //定义STpl模板路径
		$this->tpl_check = APP::$_CFG['siteTemplateCheck'];  //定义STpl模板是否检测文件更新

		//常用变量模板赋值
		$this->assign('baseurl',  BASEURL); //网址URL
		$this->assign('public',  SYSDIR . 'public/'); //公共文件URL
		$this->assign('t_url',  T_URL); //当前模板URL
		$this->assign('title',  $this->title); //默认网站标题名称
		$this->assign('description',  $this->description);
		$this->assign('keywords',  $this->keywords);
		$this->assign('sitename',  $this->sitename); //版权名称
		$this->assign('sitebeian',  APP::$_CFG['siteBeian']); //备案信息
		$this->assign('this_uri',  $_SERVER['REQUEST_URI']); //当前页面的URI

		$this->assign('js_lang', $js_lang); //分配JS语言缓存文件名
		$this->assign('langs', $this->langs); //将语言数组分配给模板

		//判断网站是否关闭
		if(!APP::$_CFG['siteActived']){
			$this->assign('errtitle', $this->langs['systeminfo']); //错误信息
			$this->assign('errorinfo', APP::$_CFG['siteOffTitle'] . '<br>' . APP::$_CFG['siteOffTitleEn']); //错误信息
			$this->display('offline.html');
			exit();
		}

		//根据语言从产品分类缓存中获取产品分类数组
		$temp = require(ROOT . "cache/p$cats_cachename");
		$this->pcat_ids = $temp[1];
		$this->pcategories = $this->tree($temp[2]);
		$this->map_pcats($this->pcat_ids);

		$this->assign('pcategories', $this->pcategories); //分配多级产品分类的输出字符串

		//文章分类
		$temp = require(ROOT . "cache/a$cats_cachename");
		$this->acat_ids = $temp[1];
		$this->acategories = $temp[2];
		$this->assign('acategories', $this->map_acats($this->acat_ids));

		//实例化用户等
		$this->user = new user;
		$this->user->data['rights'] = getUserRights($this->user->data); //需要在JS中初步验证权限字符串, 包含用户ID, email, nickname, 及短信,评论,询价3项权限等
		$this->assign('sys_user', $this->user->data); //将用户数据数组分配给模板

		//分配以下两个变量, 用于游客或已登录用户有关操作的安全验证
		$sys_key = PassGen(8);
		$sys_cookievalue = md5(WEBSITE_KEY . $sys_key . APP::$_CFG['siteKillRobotCode']);
		$this->assign('sys_key', $sys_key); 
		$this->assign('sys_cookievalue', $sys_cookievalue);

		//如果没有id, 说明是游客, 分配以下变量给模板, 用于游客登录, 找加密码, 游客发表评论, 提交询价等操作的更多一次安全验证
		if(!$this->user->data['userid']){
			$this->assign('sys_code', authcode(md5(WEBSITE_KEY), 'ENCODE', $sys_key, 1800));

			//通过判断url中是否有login参数显示登录框, 用户如果已经登录则无效
			if(IsGet('login')) $this->assign('system_info', '<script>$(function(){login();});</script>');
		}
		//联系我们
		$contact = GetContent(2);
		$this->assign('contact', $contact);

		//底部信息
		$copyright = GetContent(11);
		$this->assign('copyright', $copyright);
	}

	//递归函数 -- 产品菜单多级分类字符串, 前台分类下拉菜单仅显示3级
	protected function map_pcats($pcat_ids, $pid = 0, $level = 1){
		$sReturn = '';

		foreach($pcat_ids as $cat_id => $p_id){
			if($pid == $p_id){
				$this->pcats_ok[] = $cat_id; //记录有效(未隐藏)的产品分类cat_id数组
				$hasSub = in_array($cat_id, $this->pcat_ids); //当前分类是否存在下级分类

				if($level < 4) $catname = $this->pcategories[$cat_id]['name'];

				switch($level){
				case 1:
					$sReturn .= '<li'. Iif($hasSub, ' class="sub"') . '><span class="c"><a href="'.URL('products?cat=' . $cat_id).'">' . $catname . '</a></span>';

					//存在子分类时递归获取, 级数加1
					if($hasSub){
						$sReturn .= '<div class="item"><table><tr><td class="td1"><div class="subcats">';
						$sReturn .= $this->map_pcats($pcat_ids, $cat_id, $level+1);
						$sReturn .= '</div></td>';

						//如果第一级分类有描述
						$description = $this->pcategories[$cat_id]['description'];
						if($description){
							$sReturn .= '<td class="td2"><div class="desc">' . html($description). '</div></td>';
						}
						$sReturn .= '</tr></table></div>';
					}
					$sReturn .= '</li>';
					break;

				case 2:
					$sReturn .= '<div class="line"><div class="second"><a href="'.URL('products?cat=' . $cat_id).'">' . $catname . '</a></div>';
					if($hasSub){
						$sReturn .= '<div class="third">';
						$sReturn .= $this->map_pcats($pcat_ids, $cat_id, $level+1); //存在子分类时递归获取, 级数加1
						$sReturn .= '</div>';
					}
					$sReturn .= '</div>';
					break;

				case 3:
					$sReturn .= '<a href="'.URL('products?cat=' . $cat_id).'">' . $catname . '</a> '; //注意后台加一个空格

					if($hasSub){
						//第4级开始没有输出内容, 只是完成递归循环, 以便记录有效(未隐藏)的产品分类
						$this->map_pcats($pcat_ids, $cat_id, $level+1);
					}
					break;

				default:
					//第4级开始没有输出内容, 只是完成递归循环, 以便记录有效(未隐藏)的产品分类, 级数无须再增加(总是=4)
					if($hasSub) $this->map_pcats($pcat_ids, $cat_id, $level);
					break;
				}
			}
		}

		return $sReturn . Iif($level== 2, '<div class="last2"></div>'); //加这个Iif是为了让最后一行没有border-bottom
	}


	//递归函数 -- 文章分类下拉菜单仅显示3级. 此函数没有同上面的map_pcats函数整合成一个函数是为了减少较多的判断, 以提高运行速度.
	protected function map_acats($acat_ids, $pid = 0, $level = 1){
		$sReturn = '';

		foreach($acat_ids as $cat_id => $p_id){
			if($pid == $p_id){
				$this->acats_ok[] = $cat_id; //记录有效(未隐藏)的产品分类cat_id数组
				$hasSub = in_array($cat_id, $this->acat_ids); //当前分类是否存在下级分类

				if($level < 4) $catname = $this->acategories[$cat_id]['name'];

				switch($level){
				case 1:
					$sReturn .= '<li'. Iif($hasSub, ' class="sub"') . '><span class="c"><a href="'.URL('articles?cat=' . $cat_id).'">' . $catname . '</a></span>';

					if($hasSub){
						$sReturn .= '<div class="item"><table><tr><td class="td1"><div class="subcats">';
						$sReturn .= $this->map_acats($acat_ids, $cat_id, $level+1);
						$sReturn .= '</div></td>';

						$description = $this->acategories[$cat_id]['description'];
						if($description){
							$sReturn .= '<td class="td2"><div class="desc">' . html($description). '</div></td>';
						}
						$sReturn .= '</tr></table></div>';
					}
					$sReturn .= '</li>';
					break;

				case 2:
					$sReturn .= '<div class="line"><div class="second"><a href="'.URL('articles?cat=' . $cat_id).'">' . $catname . '</a></div>';
					if($hasSub){
						$sReturn .= '<div class="third">';
						$sReturn .= $this->map_acats($acat_ids, $cat_id, $level+1);
						$sReturn .= '</div>';
					}
					$sReturn .= '</div>';
					break;

				case 3:
					$sReturn .= '<a href="'.URL('articles?cat=' . $cat_id).'">' . $catname . '</a> '; //注意后台加一个空格

					if($hasSub){
						$this->map_acats($acat_ids, $cat_id, $level+1);
					}
					break;

				default:
					if($hasSub) $this->map_acats($acat_ids, $cat_id, $level);
					break;
				}
			}
		}

		return $sReturn . Iif($level== 2, '<div class="last2"></div>');
	}
	/**
	 * 生成树形产品目录
	 */
	public function tree($arr,$pid=0){
		$refer=array();//存储主键与数组单元的引用关系
		//遍历
		foreach($arr as $k=>$v){
			$refer[$v['cat_id']]=&$arr[$k];//为每个数组成员建立对应关系
		}
		//遍历2
		foreach($arr as $k=>$v){
				$parent=&$refer[$v['p_id']];//获取父分类的引用
				$parent['child'][]=&$arr[$k];//在父分类的children中再添加一个引用成员
		}
		$tree=array();
		//遍历3
		foreach($arr as $k=>$v){
			if($v['p_id']==$pid){
				$tree[$v['cat_id']]=$v;
			}
		}
		return $tree;
	}
	/**
	 * public 操作权限验证函数 CheckAccess 无输出
	 */
	public function CheckAccess($action = '') {
		return $this->user->CheckAccess($action);
	}

	/**
	 * public 操作授权验证输出并输出错误信息 CheckAction
	 */
	public function CheckAction($action = '') {
		$this->user->CheckAction($action);
	}

	/**
	 * public 获取用户组权限值 ActionValue
	 */
	public function ActionValue($action = '') {
		return $this->user->ActionValue($action);
	}

	//以上CheckAccess, CheckAction和ActionValue两个函数调用user模型中对应的函数, 只是为了方便书写代码, 完全可通用


	/**
	 * 析构函数   在析构函数中判断是否允许加载模板, 及输出系统信息对话框
	 */
	public function __destruct(){

		//如果状态为不允许加载模板文件, 直接使用STpl父类display函数页头和页尾, 因为如果不输出JS, 对话框无法显示
		if(!$this->display_allowed) {
			$this->display('header.html'); //页头
			$this->display('footer.html'); //页尾
		}
	}
}

?>