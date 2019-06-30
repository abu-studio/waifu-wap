<?php
class qiyecenter_ctl_site_enter extends b2c_frontpage{
	function __construct(&$app, $verify=true){
        parent::__construct(&$app);
        $this->app = $app;
        $this->app_b2c = app::get('b2c');
        $this->_response->set_header('Cache-Control', 'no-store');
        kernel::single('base_session')->start();
    	$this->auth->type='member';
    }
    /**
     * 用户登录处理
     */
    public function loginDeal(){
        /*
		if($this->app->member_id ==$_SESSION['account']['member']){
            $this->redirect(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index'));
        }
        */
    	//初始化返回数据
    	$data = array();
    	//customerUserLogin
        $uname = !empty($_POST['uname'])?htmlspecialchars($_POST['uname']):'';
        $password = !empty($_POST['password'])?htmlspecialchars($_POST['password']):'';
        $vcode = !empty($_POST['CODE'])?htmlspecialchars($_POST['CODE']):'';
        $params['METHOD'] = 'customerManagerLogin';
        $params['LOGINNAME'] = $uname;
        $params['PASSWORD'] = $password;
        //$params['CODE'] =  '1111';

        //先去本地验证
        if($uname == ''){
            $this->splash('failed',"javascript:changeimg('membervocde')",app::get('qiyecenter')->_('用户名为空'),'','',true);
            die;
        }
        if($password == ''){
            $this->splash('failed',"javascript:changeimg('membervocde')",app::get('qiyecenter')->_('密码为空'),'','',true);
            die;
        }
        // 禁用掉 使用人才号从悠福网数据库直接登录功能
        $memberIsexist = false;
        if(constant('DEBUG_LOGIN') === true){
            //验证是否有该用户名
            $account_type = pam_account::get_account_type('b2c');
            $obj_pam_account = new pam_account($account_type);
            $memberIsexist = $obj_pam_account->is_exists($params['LOGINNAME']);
        }
        if($memberIsexist)
        {
            $password_string = pam_encrypt::get_encrypted_password($params['PASSWORD'], 'member', array('login_name' => $params['LOGINNAME']));
            $rows = app::get('pam')->model('account')->getList('*', array(
                'login_name' => $_POST['uname'],
                'login_password' => $password_string,
                'account_type' => 'member',
                'disabled' => 'false',
            ), 0, 1);

            if(!empty($rows[0]['account_id']))
            {
                $seller = app::get('b2c')->model('members')->getlist('seller,setup_manager_type',array('member_id'=>$rows[0]['account_id']));
                if($seller[0]['seller'] == 'qiye'){

                    $_SESSION['account']['member'] = $rows[0]['account_id'];
                    $_SESSION['account']['SETUP_MANAGER_TYPE'] =  $seller[0]['setup_manager_type'];
                    $_SESSION['account']['HUMBAS_NO'] = substr($rows[0]['login_name'],2);
                    //模拟正常用户登录
                    $this->bind_member($rows[0]['account_id']);
                    $this->splash('success',$this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'index')),app::get('qiyecenter')->_('登录成功'),'','',true);
                    die();
                }
            }else{
                $this->splash('failed',"javascript:changeimg('membervocde')",app::get('qiyecenter')->_('用户名或密码错误！'),'','',true);
                die;
            }
        }
        $resSF = SFSC_HttpClient::doLifCostMain($params, 'CustomerManagerUserLoginService');
        if($resSF['RESULT_CODE'] == "10001"){
            //$ppbObj->loginQ($uname,$resSF['RESULT_DATA']['USER_LOGIN_NO']);
            //企业中心的登陆
            //$_SESSION['account'][pam_account::get_account_type($this->app->app_id)] = $member_id;
            //关联用户中心同步登陆
            //$_SESSION['account']['member'] = $member_id;


            //$this->splash('failed',"javascript:changeimg('membervocde')",app::get('b2c')->_('用户名或密码错误'),'','',true);
            //$data['info'] = $resSF['RESULT_DATA'];
			//如果本地数据库不存在数据就注册
			$pam = app::get('pam')->model('account');
			$conut = $pam->count(array('login_name'=>$resSF['RESULT_DATA']['QS_HUMBAS_NO'],'account_type'=>'member'));
			if($conut==0){
				$member_model=app::get('b2c')->model('members');
				//预留接口
				$use_pass_data['login_name'] = $resSF['RESULT_DATA']['QS_HUMBAS_NO'];
				$use_pass_data['createtime'] = time();
				$login_password = pam_encrypt::get_encrypted_password(trim('123456'), pam_account::get_account_type(app::get('b2c')), $use_pass_data);
                
				$sdf = array(
					'license' => 'agree',
					'member_lv' =>
					array (
						'member_group_id' => '1',
					),
					'currency' => 'CNY',
					'regtime' => $use_pass_data['createtime'],
					'reg_type' => 'username',
                    'seller' => 'qiye',
                    // 'nickname' => substr($params['LOGINNAME'],2),
                    'setup_manager_type' => $resSF['RESULT_DATA']['SETUP_MANAGER_TYPE'],
					'pam_account' =>
						array (
							'login_password' => $login_password,
                            'psw_confirm' => '123456',
                            'login_name' =>  $resSF['RESULT_DATA']['QS_HUMBAS_NO'],
                            'account_type' => 'member',
                            'createtime' => $use_pass_data['createtime'],
						),
				);
				$flag = $member_model->save($sdf);
				if(!flag){
                    $this->splash('failed',"javascript:changeimg('membervocde')",app::get('qiyecenter')->_('用户名或密码错误'),'','',true);
                    die;
                }
				$_SESSION['account']['member'] = $sdf['member_id'];
				$_SESSION['account']['SETUP_MANAGER_TYPE'] =  $resSF['RESULT_DATA']['SETUP_MANAGER_TYPE'];
                $_SESSION['account']['HUMBAS_NO'] = $resSF['RESULT_DATA']['HUMBAS_NO'];
				//模拟正常用户登录
				$this->bind_member($sdf['member_id']);
			}else{
               
				$info = $pam->getRow('*',array('login_name'=>$resSF['RESULT_DATA']['QS_HUMBAS_NO'],'account_type'=>'member'));
                // //start//更改昵称
                // $member_model=app::get('b2c')->model('members');
                // $mem_info = $member_model->getRow('*',array('member_id'=>$info['account_id']));
                // $LOGINNAME=substr($params['LOGINNAME'],2);
                // if($mem_info['nickname']!=$params['LOGINNAME']){
                    
                //     $flag = $member_model->update(array('nickname'=>$LOGINNAME),array('member_id'=>$info['account_id']));
                // }
                // //end
				$_SESSION['account']['member'] =$info['account_id'];
                $_SESSION['account']['SETUP_MANAGER_TYPE'] = $resSF['RESULT_DATA']['SETUP_MANAGER_TYPE'];
                $_SESSION['account']['HUMBAS_NO'] = $resSF['RESULT_DATA']['HUMBAS_NO'];
				//模拟正常用户登录
				$this->bind_member($info['account_id']);
			}
			$this->splash('success',$this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'index')),app::get('qiyecenter')->_('登录成功'),'','',true);
            die();
        }
	
	    $msg = empty($resSF['RESULT_MSG']) ? app::get('qiyecenter')->_('用户名或密码错误') : $resSF['RESULT_MSG'];
        $this->splash('failed',"javascript:changeimg('membervocde')",$msg,'','',true);
		die;
    }
	/**
	 * 获取短息验证码
     *

    public function getMobileCode(){
		if($this->app->member_id =$_SESSION['account']['qmember']){
              $this->redirect(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index'));
        }
    	//初始化返回数据
    	$data = array();
    	$data['code'] = 0;
    	$data['msg'] = '';
    	$data['info'] = '';
    	
    	$uname = !empty($_POST['LOGINNAME'])?htmlspecialchars($_POST['LOGINNAME']):'';
    	$password = !empty($_POST['PASSWORD'])?htmlspecialchars($_POST['PASSWORD']):'';
    	//获取接口数据
    	$params['METHOD'] = 'customerUserLoginSendMsg';
        $params['LOGINNAME'] = $uname;
        $params['PASSWORD'] = $password;
		$resSF = SFSC_HttpClient::doLifCostMain($params,'CustomerUserLoginService');
		if($resSF['RESULT_CODE'] == 10001){
            $data['code'] = '1';
            $data['msg'] = '操作成功';
            $data['info'] = $resSF['RESULT_DATA'];
		}else{
            $data['code'] = 0;
            $data['msg'] = '没有数据';
            $data['info'] = '';
		}
		echo json_encode($data);
		die;
    }
    
    */
    function index(){
    	$this->login();
    }
    /**
     * 登陆操作
     */
    public function login(){
		if($this->app->member_id =$_SESSION['account']['member']){
              $this->redirect(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index'));
        }
    	$this->set_tmpl('login');
        $this->page('site/enter/login.html');
    }
    
    function bind_member($member_id){
        $obj_member = app::get('b2c')->model('members');
        $data = $obj_member->dump($member_id,'*',array(':account@pam'=>array('*')));
        $service = kernel::service('pam_account_login_name');
        if(is_object($service)){
            if(method_exists($service,'get_login_name')){
                $data['pam_account']['login_name'] = $service->get_login_name($data['pam_account']);
            }
        }

        //获取ucenter中的 注册类型reg_type
        if($uc = kernel::service("uc_user_register")){
            $user_rs = $uc->uc_get_user($data['pam_account']['login_name']);
            if($user_rs['reg_type']){
                $data['reg_type'] = $user_rs['reg_type'];
            }
        }

        //用什么注册，就显示什么
        if($data['reg_type'] == 'email' && $data['contact']['email']){
            $tmp = $data['contact']['email'];
        }elseif($data['reg_type'] == 'mobile' && $data['contact']['phone']['mobile']){
            $tmp = $data['contact']['phone']['mobile'];
        }elseif($data['reg_type'] == 'username'&& $data['setup_manager_type']=='I03701'){
            $tmp = substr($data['pam_account']['login_name'],2);
        }else{
            $tmp = $data['pam_account']['login_name'];
        }

        $secstr = $obj_member->gen_secret_str($data['pam_account']['account_id']);
        $this->cookie_path = kernel::base_url().'/';
        //将java接口用户名注入cookie  start

        $MemberInfoArr = SFSC_HttpClient::doMemberMain($tmp);
        $MemberInfoArr = SFSC_HttpClient::objectToArray($MemberInfoArr);
        //将java接口用户名注入cookie  end
        
        $this->set_cookie('JAVA[UNAME]',$MemberInfoArr['RESULT_DATA']['NAME'],0);
        $this->set_cookie('JAVA[UNAME_EN]',$MemberInfoArr['RESULT_DATA']['NAME_EN'],0);
        $this->set_cookie('MEMBER',$secstr,0);
        $this->set_cookie('loginName',$data['pam_account']['login_name'],0);
        $this->set_cookie('UNAME',$tmp,0);
        $this->set_cookie('MLV',$data['member_lv']['member_group_id'],0);
        $this->set_cookie('CUR',$data['currency'],0);
        $this->set_cookie('LANG',$data['lang'],0);
        $this->set_cookie('S[MEMBER]',$member_id,0);
        $this->set_cookie('SELLER',$data['seller'],0);
        $this->set_cookie('EMAIL',$data['contact']['email'],0);
        $this->set_cookie('MOBILE',$data['contact']['phone']['mobile'],0);
    }

	 function logout(){
		$this->app->member_id = 0;
        $this->cookie_path = kernel::base_url().'/';
        $this->set_cookie('MEMBER',null,time()-3600);
        $this->set_cookie('UNAME','',time()-3600);
        $this->set_cookie('MLV','',time()-3600);
        $this->set_cookie('JAVA[UNAME]','',time()-3600);
		$this->set_cookie('JAVA[UNAME_EN]','',time()-3600);
        $this->set_cookie('CUR','',time()-3600);
        $this->set_cookie('LANG','',time()-3600);
        $this->set_cookie('S[MEMBER]','',time()-3600);
        $this->set_cookie('SELLER','',time()-3600);
        $this->set_cookie('login_member_type','',time()-3600);
		unset($_SESSION['account']['member']);
		unset($_SESSION['account']['USER_ID']);
		unset($_SESSION['account']['USER_ROLE']);
		unset($_SESSION['roleListData']);
		unset($_SESSION['account']['SETUP_MANAGER_TYPE']);
        $this->redirect(array('app'=>'b2c','ctl'=>'site_passport','act'=>'login'));
    }
	
}
