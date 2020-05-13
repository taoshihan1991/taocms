<?php if(!defined('ROOT')) die('Access denied.');

class user{

    var $data = null; //�����û���Ϣ

    public function __construct($ajax = 0){
        $this->auth($ajax); //user�๹��ʱ�ͽ�����Ȩ
    }

    /**
     * private ��Ȩ���� auth
     */
    private function auth($ajax){
        $sessionid = ForceCookieFrom(COOKIE_USER);
        $useragent = md5(substr($_SERVER['HTTP_USER_AGENT'], 0, 252) . WEBSITE_KEY);

        $check_agent = true; //�Ƿ���֤�û������

        if(IsPost('sessionid')) {//swfuploadʹ��
            $sessionid = ForceStringFrom('sessionid');
            $check_agent = false; //swf�ϴ�ʱ, $useragent��ֵΪ: Shockwave Flash, ��ʱ������֤�û������
        }

        if($sessionid AND IsAlnum($sessionid)){//��¼�ɹ���֤cookie��Ȩ
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
                setcookie(COOKIE_USER, '', 0, '/'); //�û����Ϸ�, ���cookie

                if(!$ajax AND $check_agent) {
                    Redirect('index?login'); //��ת����ҳ����ʾ��¼�Ի���

                }else{
                    $this->isguest(); //ajax��swfupload�ϴ�ʱ, �������¼����, ֻȷ��Ϊ�ο�
                }
            }else{
                unset($userinfo['password'], $userinfo['verifycode']); //ɾ�����ܲ鿴�����û�����
                $this->data = $userinfo; //��Ȩ�ɹ�, ִ�к���ĳ���
            }

        }else{ //�ο�
            $this->isguest(); //ȷ��Ϊ�ο�, �������ο͵���Ϣ
        }
    }


    //��ȡ���ο͵���Ϣ
    private function isguest(){
        $userinfo = APP::$DB->getOne("SELECT groupid, grouptype, groupname, groupname_en, actions FROM " . TABLE_PREFIX . "usergroup WHERE groupid = 3");
        $userinfo['userid'] = $userinfo['pms'] = 0; //�ο͵�id��������Ϊ0
        $userinfo['nickname'] = $userinfo['email'] = '';
        $this->data = $userinfo;
    }


    //�����û�session ����$remember: ��ʾ�Ƿ��ס��
    public function CreateSession($userid, $remember = 0){
        $userid = ForceInt($userid);
        If(!$userid) return false; //id�Ƿ�����

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
     * public ��֤�û���¼����  ����$password: δmd5; $remember:�Ƿ��ס��¼
     * ����false: ��֤ʧ��; ����string: ��֤ʧ����Ϣ;  ����true: ��֤�ɹ�
     */
    public function check($username = '', $password = '', $remember = 0){
        if(!$username OR !$password) return APP::$C->langs['er_nologinuser'];

        $password = md5($password);

        $user = APP::$DB->getOne("SELECT u.userid, ug.grouptype, ug.actions FROM " . TABLE_PREFIX . "user u LEFT JOIN  " . TABLE_PREFIX . "usergroup ug ON (u.groupid = ug.groupid) WHERE u.username = '$username' AND u.password = '$password' AND u.activated = 1");

        if(!$user OR !$user['userid']) return APP::$C->langs['er_nologinuser']; //�û������ڻ��������

        //�����ǰ̨�û�, ��֤�����û����Ƿ������¼, ��Ϊ��̨�п��ܻᴴ��һ���������¼���û���(�������)
        if($user['grouptype'] == 0 AND !getAccess($user['actions'], 'login')){
            return APP::$C->langs['er_deny'];
        }

        //���û���Ȩ���Զ�ɾ�����Ķ���
        $pmdays = getActionValue($user['actions'], 'pmdays');
        if($pmdays){

            //�����Ҫɾ�������Ķ���
            $getpms = APP::$DB->query("SELECT pmid FROM " . TABLE_PREFIX . "pm WHERE toid = '$user[userid]' AND readed = 1 AND refer_id = 0 AND created < " . (time() - 3600*24*$pmdays));

            while($pm = APP::$DB->fetch($getpms)){
                //ɾ�����ż���ظ�
                APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid = '$pm[pmid]' OR refer_id = '$pm[pmid]'");
            }
        }

        return $this->CreateSession($user['userid'], $remember); //����session��, ����true��false
    }


    /**
     * public �˳���¼����logout
     */
    public function logout(){
        $sessionid = ForceCookieFrom(COOKIE_USER);
        setcookie(COOKIE_USER, '', 0, '/'); //���cookie

        if($sessionid AND IsAlnum($sessionid)){
            //ǰ̨�û��˳�ʱɾ����ǰ��session
            APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "session WHERE sessionid = '$sessionid' AND admin = 0");
        }
    }


    /**
     * public ����Ȩ����֤���� CheckAccess �����
     */
    public function CheckAccess($action = '') {
        if($this->data['grouptype'] == 1){
            return true; //��̨�û�����ǰ̨ȫȨ��
        }elseif(!$this->data['userid'] AND $action == 'login'){
            return false; //������ο�����֤��¼Ȩ��ʱ, ֱ�ӷ���false; ����֤��ʵ����ȥ��, ��Ϊ��ֻ̨���������ο͵����ۺ�ѯ��Ȩ��
        }

        return Iif(strstr($this->data['actions'], "*$action*"), true, false);
    }

    /**
     * public ������Ȩ��֤��������������Ϣ CheckAction
     */
    public function CheckAction($action = '') {

        if(!$this->CheckAccess($action)){
            //�������֤��¼Ȩ��ʱ, ֱ����ת����ҳ����ʾ��¼�Ի���
            if($action == 'login') Redirect('index?login');

            //��֤����Ȩ��ʱ�������
            Error(APP::$C->langs['er_nopermission']);
        }
    }

    /**
     * public ��ȡ�û���Ȩ��ֵ ActionValue
     */
    public function ActionValue($action = '') {
        preg_match("/\\*$action:(\\w+)\\*/i", $this->data['actions'], $matchs);
        return ForceInt($matchs[1]); //����ֵ��Ϊ����
    }

}

?>