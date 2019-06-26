<?php
class b2c_sfsc_httpclient
{
    function __construct(&$app){
        $this->app=$app;
    }
	/**
	 * 企业用户登录校验
	 * @param $user_name
	 * @param $user_pw
	 * @param $code
	 */
    function sfsc_user_login_qiye($user_name,$user_pw, $code){
    	if($user_name != '' && $user_pw != ''){
    		//获取接口数据
	    	$params['METHOD'] = 'customerUserLogin';
	        $params['LOGINNAME'] = $user_name;
	        $params['PASSWORD'] = $user_pw;
	        $params['CODE'] = $code;
			$user_data = SFSC_HttpClient::doLifCostMain($params, 'CustomerUserLoginService');
    		//将HUMBAS_NO 存入session中
    		$_SESSION["HUMBAS_NO"] = $user_data["RESULT_DATA"]['HUMBAS_NO'];
        	$_SESSION["USER_ID"] = $user_data["RESULT_DATA"]['USER_ID'];
        	$_SESSION["USER_NAME"] = $user_data["RESULT_DATA"]['USER_LOGIN_NO'];
            //$user_data = SFSC_HttpClient::doLogin($user_name,$user_pw);
            
            if($user_data['RESULT_CODE'] !== "10001"){
                $user_email = ( ' ' != $user_data['RESULT_DATA']['EMAIL']) ? $user_data['RESULT_DATA']['EMAIL'] : $user_data['RESULT_DATA']['HUMBAS_NO'].'@efesco.com';
                return $this->sfsc_register($user_data['RESULT_DATA']['HUMBAS_NO'],$user_pw,$user_email);
            }
            return true;
        }
        return false;
    }
    
    function sfsc_user_login($user_name,$user_pw,&$msg){
        if($user_name != '' && $user_pw != ''){
            $user_data = SFSC_HttpClient::doLogin($user_name,$user_pw);
            if($user_data['RESULT_CODE'] == "10001"){
                $user_email = ( ' ' != $user_data['RESULT_DATA']['EMAIL']) ? $user_data['RESULT_DATA']['EMAIL'] : $user_data['RESULT_DATA']['HUMBAS_NO'].'@efesco.com';
                return $this->sfsc_register($user_data['RESULT_DATA']['HUMBAS_NO'],$user_pw,$user_email);
            }
            if(empty($user_data)){
                $msg = '登录超时';
            }
            return false;
        }
        $msg = "用户名或密码为空";
        return false;
    }


    function sfsc_user_login_nopassword($user_name,$user_pw){
        /*
        $sfsc_data = SFSC_HttpClient::doCheckUname($user_name);
        //调用java端数据不成功
        if(!$sfsc_data){
            return false;
        }
        //验证用户名是否存在
        if($sfsc_data && $sfsc_data['RESULT_CODE'] == "10001"){
            return false;
        }
        */

        if($user_name != '' && $user_pw != ''){
            $user_email = $user_name.'@efesco.com';
            return $this->sfsc_register($user_name,$user_pw,$user_email);
        }
        return false;
    }


    function checkuname($login_name){

        if($login_name != ''){
            $account_type = pam_account::get_account_type($this->app->app_id);
            $obj_pam_account = new pam_account($account_type);
            return $obj_pam_account->is_exists($login_name);
        }

        return false;

    }

    function sfsc_register($user_name,$password,$email) {

        $sql="select `account_id` from sdb_pam_account where `login_name` = '".$user_name."'";
        $data_pam_account=kernel::database()->select($sql);
        //判断本地是否存在数据
        if(!$data_pam_account){
            //模拟注册数据
            $regtime = time();
            $register_data=array(
                'reg_type' =>'username',
                'pam_account' => array(
                    'login_name' =>$user_name,
                    'login_password' => $password,
                    'psw_confirm' => $password,
                    'createtime' =>$regtime,
                    'account_type' => 'member'
                ),
                'contact' => array(
                    'commonlyemail' => $email,
                    'email' => $email,
                ),
                'member_lv' => array(
                    'member_group_id' => 1,
                ),
                'currency'=>'CNY',
                'reg_ip'=>'127.0.0.1',
                'regtime'=>$regtime,
                'is_subscibe'=>'true',
                'license'=>'agree',
            );
            //调用保存方法
            $member_id = $this->add($register_data);
            if($member_id){
                $_POST['uname'] = $user_name;
                return true;
            }
        }else{
            $_POST['uname'] = $user_name;
            return true;
        }
        return false;
    }

    function add($register_data){
        $sfsc_post=$register_data;
        foreach($sfsc_post as $key=>$val){
            if(strpos($key,"box:") !== false){
                $aTmp = explode("box:",$key);
                $sfsc_post[$aTmp[1]] = serialize($val);
            }
        }
        $mem_model = kernel::single('b2c_mdl_members');
        $message = "";
        //校验用户注册项 是否正确
        if($mem_model->validate($sfsc_post,$message)){

            //用户登陆密码加密
            $use_pass_data['login_name'] = $sfsc_post['pam_account']['login_name'];
            $use_pass_data['createtime'] = $sfsc_post['pam_account']['createtime'];
            $sfsc_post['pam_account']['login_password'] = pam_encrypt::get_encrypted_password(trim($sfsc_post['pam_account']['login_password']), pam_account::get_account_type('b2c'), $use_pass_data);

            //事物模拟开始
            $db = kernel::database();
            $db->beginTransaction();
            $msg = "";
            //模拟注册创建
            if($mem_model->save($sfsc_post)){
                $member_id = $sfsc_post['member_id'];
                if(!($this->save_attr($member_id, $sfsc_post, $msg))){
                    $db->rollBack();
                    return false;
                }
                $db->commit();

                $data['member_id'] = $member_id;
                $data['uname'] = $_POST['pam_account']['login_name'];
                $data['passwd'] = $_POST['pam_account']['psw_confirm'];
                $data['email'] = $_POST['contact']['email'];
                $data['is_frontend'] = false;

                //增加会员同步
                if( $member_rpc_object = kernel::service("b2c_member_rpc_sync") ) {
                    $member_rpc_object->createActive($member_id);
                }

                $obj_account=kernel::single('b2c_mdl_member_account');
                $obj_account->fireEvent('register',$data,$member_id);

                #↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓记录管理员操作日志↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
                if($obj_operatorlogs = kernel::service('operatorlog')){
                    if(method_exists($obj_operatorlogs,'inlogs')) {
                        $memo = '添加新会员，会员名为  "'.$data['uname'].'"';
                        $obj_operatorlogs->inlogs($memo, '添加会员', 'members');
                    }
                }
                #↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑记录管理员操作日志↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
                return $member_id;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //保存相关的会员信息
    private function save_attr($member_id=null,$aData,&$msg){
        if(!$member_id)
        {
            $msg = app::get('b2c')->_('注册失败');
            return false;
        }
        $member_model = &$this->app->model('members');
        $aData['pam_account']['account_id'] = $member_id;
        if(!$_POST['profile']['birthday']) unset($aData['profile']['birthday']);
        if($aData['profile']['gender'] == 1){
            $aData['profile']['gender'] = 'male';
        }
        elseif($aData['profile']['gender'] ===0){
            $aData['profile']['gender'] = 'female';
        }
        else{
            $aData['profile']['gender'] = 'no';
        }
        foreach($aData as $key=>$val)
        {
            if(strpos($key,"box:") !== false)
            {
                $aTmp = explode("box:",$key);
                $aData[$aTmp[1]] = serialize($val);
            }
        }
         /*
        if($aData['contact']['name']&&!preg_match('/^([@\.]|[^\x00-\x2f^\x3a-\x40]){2,20}$/i', $aData['contact']['name']))
        {
            $msg = app::get('b2c')->_('姓名包含非法字符');
            return false;
        }
         */
        $obj_filter = kernel::single('b2c_site_filter');


        $aData = $obj_filter->check_input($aData);


        if($member_model->save($aData))
        {

            $msg = app::get('b2c')->_('注册成功');
            return true;
        }
        $msg  = app::get('b2c')->_('注册失败');
        return false;

    }

}
