<?php if(!defined('ROOT')) die('Access denied.');

//前台Ajax类, 无模板
class SAjax{

	protected $user; //前台用户对象
	public $title;
	public $sitename;
	public $langs = array(); //语言数组成员, 在子类中调用

	protected $ajax = array(); //用于ajax数据收集与输出
	protected $json; //ajax时的JSON对象

	public function __construct($path = ''){

		if(!APP::$_CFG['siteActived']){
			die(); //如果系统关闭, 什么也不做了
		}

		include(ROOT . 'includes/functions.common.php'); //加载函数库(包括公共函数库)

		APP::$DB->printerror = false; //数据库访问不打印错误信息

		if(IS_CHINESE){
			$this->langs = require(ROOT . 'public/languages/Chinese.php'); //将语言数组赋值给类成员
			$this->title = APP::$_CFG['siteTitle'];
			$this->sitename = APP::$_CFG['siteCopyright'];
		}else{
			$this->langs = require(ROOT . 'public/languages/English.php'); //将语言数组赋值给类成员
			$this->title = APP::$_CFG['siteTitleEn'];
			$this->sitename = APP::$_CFG['siteCopyrightEn'];
		}

		//初始化ajax返回数据
		$this->ajax['s'] = 1; // s表示状态, 默认为1(正常),  0(错误)
		$this->ajax['i'] = ''; // i指ajax提示信息
		$this->ajax['d'] = ''; // d指ajax返回的数据
		$this->json = new SJSON;

		$this->user = new user(1); //实例化user模型类, 有些ajax需要验证用户的权限, 1表示是ajax援权, 不显示登录页面
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
}

?>