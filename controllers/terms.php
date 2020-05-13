<?php if(!defined('ROOT')) die('Access denied.');

class c_terms extends SAjax{

	public function index(){

	}

}

$termsbody = '<h1>服务条款</h1>
<h2>请务必详细阅读以下服务条款, 及免责声明.</h2>

<p>1. 基于本网站的实时性本质, 我们不可能审阅所有的信息或者确认张贴出来的内容是否真实有效. 我们不会主动地监视任何讨论的内容, 也无法为这些内容负责. 我们不担保或保证任何信息的精确性, 完整性及实用性, 也不为会员所张贴的任何数据内容所负责.</p>

<p>2. 信息仅表达该信息作者自己的观点, 并不见得代表本网站或任何与本网站所关联的实体的观点. 任何使用者如若对本网站的内容有所异议, 请即刻联络我们. 如果我们发现有任何具有争议的信息, 那么我们将有权删除该信息, 而且我们将尽一切可能, 在合理的时间内执行. 然而这是个手动的, 特定的操作过程, 所以请理解我们可能无法在第一时间就删除或修改该信息.</p>

<p>3. 当您使用本系统时即已同意, 您不得利用本网站发表任何虚假, 毁谤, 模糊, 辱骂, 粗俗, 骚扰, 淫秽, 亵渎, 性暗示, 侵犯他人隐私或任何违反现行法规的言论. 除非您即为著作权持有人, 或者已经著作权持有人授权, 否则您亦同意不得张贴任何受到著作权保护的内容.</p>

<p>4. 虽然本网站不会也无法审阅每一条张贴出来的信息, 也不为这些信息负责, 但是对于这些信息中的任何内容, 我们均保留删除的权利. <b>HongCMS</b>系统程序开发商或代理商与本网站内容的合法性无任何关联.</p>

<p>5. 在因您发表的信息所引起的诉讼事件中, 我们亦保留向法定机构提供您身份(或任何我们所知跟您有关的信息)的权利. 我们会记录所有使用此网站的IP地址.</p>

<p><strong>6. 我们保留无条件终止任何用户会员资格的权利.</strong></p>';

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>服务条款</title>
<style type="text/css">
body {
    font-family: "Microsoft Yahei","\5b8b\4f53",Helvetica,Arial,sans-serif;
	background: #ffffff;
	margin: 10px;
	padding-bottom: 10px;
}
body, div, h1, h2, p {
	color: #000;
	line-height: 160%;
}
h1 {
	font-size: 22px;
}
h2 {
	color: #c00;
	font-size: 14px;
	margin-bottom: 20px;

}
strong {
	color: #c00;
	font-weight: normal;
}
b {
	color: #51B400;
}
p {
	font-size: 12px;
	padding: 0px;
	margin-left: 0px;
	margin-right: 0px;
	margin-top: 0px;
	margin-bottom: 10px;
	font-size: 12px;
	color: #333;
}
a, a:link, a:visited {
	color: #36f;
	background: #ffc;
	text-decoration: none;
}
a:hover {
	color: #3300FF;
	background: #ffa;
	text-decoration: none;
}
</style>
</head>
<body>
<?php echo $termsbody; ?>
</body>
</html>