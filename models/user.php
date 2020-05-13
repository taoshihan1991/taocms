<?php if(!defined('ROOT')) die('Access denied.');

class user{

    var $data = null; //保存用户信息

    public function __construct($ajax = 0){
        $this->auth($ajax); //user类构造时就进行授权
    }

    /**
     * private 授权函数 auth
     */
    private function auth($ajax){
        $sessionid = ForceCookieFrom(COOKIE_USER);
        $useragent = md5(substr($_SERVER['HTTP_USER_AGENT'], 0, 252) . WEBSITE_KEY);

        $check_agent = true; //是否验证用户浏览器

        if(IsPost('sessionid')) {//swfupload使用
            $sessionid = ForceStringFrom('sessionid');
            $check_agent = false; //swf上传时, $useragent的值为: Shockwave Flash, 此时不能验证用户浏览器
        }

        if($sessionid AND IsAlnum($sessionid)){//登录成功验证cookie授权
            $sql = "SELECT s.sessionid, u.*, ug.grouptype" . Iif(IS_CHINESE, ", ug.groupname", ", ug.groupname_en AS groupname") . ", ug.actions, 
						(select COUNT(*)  FROM " . TABLE_PREFIX . "pm WHERE ((toid = s.userid AND readed = 0) OR (fromid = s.userid AND newreply = 1)) AND refer_id = 0) AS pms 
						FROM " . TABLE_PREFIX . "session s
						LEFT JOIN " . TABLE_PREFIX . "user u ON u.userid = s.userid
						LEFT JOIN " . TABLE_PREFIX . "usergroup ug ON ug.groupid = u.groupid
						WHERE s.sessionid    = '$sessionid'
						" . Iif($check_agent, " AND s.useragent = '$useragent' ") . "
						AND   s.admin = 0
						AND   u.activated = 1";

            $userinfo = APP::$DB->getOne($sql);

            if(!$userinfo OR !$userinfo['userid'] OR ($userinfo['grouptype'] == 0 AND !getAccess($userinfo['actions'], 'login'))){
                setcookie(COOKIE_USER, '', 0, '/'); //用户不合法, 清除cookie

                if(!$ajax AND $check_agent) {
                    Redirect('index?login'); //跳转到首页并显示登录对话框

                }else{
                    $this->isguest(); //ajax或swfupload上传时, 不输出登录窗口, 只确认为游客
                }
            }else{
                unset($userinfo['password'], $userinfo['verifycode']); //删除不能查看到的用户数据
                $this->data = $userinfo; //授权成功, 执行后面的程序
            }

        }else{ //游客
            $this->isguest(); //确认为游客, 并保存游客的信息
        }
    }


    //获取并游客的信息
    private function isguest(){
        $userinfo = APP::$DB->getOne("SELECT groupid, grouptype, groupname, groupname_en, actions FROM " . TABLE_PREFIX . "usergroup WHERE groupid = 3");
        $userinfo['userid'] = $userinfo['pms'] = 0; //游客的id及短信数为0
        $userinfo['nickname'] = $userinfo['email'] = '';
        $this->data = $userinfo;
    }


    //创建用户session 参数$remember: 表示是否记住我
    public function CreateSession($userid, $remember = 0){
        $userid = ForceInt($userid);
        If(!$userid) return false; //id非法返回

        $userip = GetIP();
        $timenow = time();
        $sessionid = md5(uniqid($userid . COOKIE_KEY));
        $useragent = md5(substr($_SERVER['HTTP_USER_AGENT'], 0, 252) . WEBSITE_KEY);

        APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "session (sessionid, userid, ipaddress, useragent, created, admin)
				  VALUES ('$sessionid', '$userid', '$userip', '$useragent', '$timenow', 0)");
        APP::$DB->exe("UPDATE " . TABLE_PREFIX . "user SET lastdate = '$timenow', lastip = '$userip', loginnum = (loginnum + 1)  WHERE userid = '$userid'");

        $time = Iif($remember, time()+3600*24*30, 0);
        setcookie(COOKIE_USER, $sessionid, $time, '/');

        return true;
    }


    /**
     * public 验证用户登录函数  参数$password: 未md5; $remember:是否记住登录
     * 返回false: 验证失败; 返回string: 验证失败信息;  返回true: 验证成功
     */
    public function check($username = '', $password = '', $remember = 0){
        if(!$username OR !$password) return APP::$C->langs['er_nologinuser'];

        $password = md5($password);

        $user = APP::$DB->getOne("SELECT u.userid, ug.grouptype, ug.actions FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.username = '$username' AND u.password = '$password' AND u.activated = 1");

        if(!$user OR !$user['userid']) return APP::$C->langs['er_nologinuser']; //用户不存在或密码错误

        //如果是前台用户, 验证所属用户组是否允许登录, 因为后台中可能会创建一个不允许登录的用户组(如黑名单)
        if($user['grouptype'] == 0 AND !getAccess($user['actions'], 'login')){
            return APP::$C->langs['er_deny'];
        }

        //按用户组权限自动删除已阅短信
        $pmdays = getActionValue($user['actions'], 'pmdays');
        if($pmdays){

            //获得需要删除的已阅短信
            $getpms = APP::$DB->query("SELECT pmid FROM " . TABLE_PREFIX . "pm WHERE toid = '$user[userid]' AND readed = 1 AND refer_id = 0 AND created < " . (time() - 3600*24*$pmdays));

            while($pm = APP::$DB->fetch($getpms)){
                //删除短信及其回复
                APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pm[pmid]' OR refer_id = '$pm[pmid]'");
            }
        }

        return $this->CreateSession($user['userid'], $remember); //创建session等, 返回true或false
    }


    /**
     * public 退出登录函数logout
     */
    public function logout(){
        $sessionid = ForceCookieFrom(COOKIE_USER);
        setcookie(COOKIE_USER, '', 0, '/'); //清除cookie

        if($sessionid AND IsAlnum($sessionid)){
            //前台用户退出时删除当前的session
            APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "session WHERE sessionid = '$sessionid' AND admin = 0");
        }
    }


    /**
     * public 操作权限验证函数 CheckAccess 无输出
     */
    public function CheckAccess($action = '') {
        if($this->data['grouptype'] == 1){
            return true; //后台用户具有前台全权限
        }elseif(!$this->data['userid'] AND $action == 'login'){
            return false; //如果是游客且验证登录权限时, 直接返回false; 此验证其实可以去掉, 因为后台只允许设置游客的评论和询价权限
        }

        return Iif(strstr($this->data['actions'], "*$action*"), true, false);
    }

    /**
     * public 操作授权验证输出并输出错误信息 CheckAction
     */
    public function CheckAction($action = '') {

        if(!$this->CheckAccess($action)){
            //如果是验证登录权限时, 直接跳转到首页并显示登录对话框
            if($action == 'login') Redirect('index?login');

            //验证其它权限时错误输出
            Error(APP::$C->langs['er_nopermission']);
        }
    }

    /**
     * public 获取用户组权限值 ActionValue
     */
    public function ActionValue($action = '') {
        preg_match("/\\*$action:(\\w+)\\*/i", $this->data['actions'], $matchs);
        return ForceInt($matchs[1]); //返回值都为整数
    }

}

?>