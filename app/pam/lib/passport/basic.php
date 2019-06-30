<?php

/**
* 该类是系统基本的验证类，必须实现 pam_interface_passport 这个接口
*/
class pam_passport_basic implements pam_interface_passport{

	/**
	* 构造方法,初始化配置信息
	*/
    function __construct(){
        kernel::single('base_session')->start();
        $this->init();
    }
    /**
	* 获取配置信息
	* @return array 返回配置信息数组
	*/
    function init(){
        if($ret = app::get('pam')->getConf('passport.'.__CLASS__)){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'true';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;
        }
    }
	/**
	* 获取认证方式名称
	* @return string 返回名称
	*/
    function get_name(){
        return app::get('pam')->_('用户登录');
    }
	/**
	* 生成认证表单,包括用户名,密码,验证码等input
	* @param object $auth pam_auth 对象
	* @param string $appid app_id
	* @return string 返回HTML页面
	*/
    function get_login_form($auth, $appid, $view, $ext_pagedata=array()){
        $render = app::get('pam')->render();
        $render->pagedata['callback'] = $auth->get_callback_url(__CLASS__);
        if($auth->is_enable_vcode()){
            $render->pagedata['show_varycode'] = 'true';
            $render->pagedata['type'] = $auth->type;
        }
        if(isset($_SESSION['last_error']) && ($auth->type == $_SESSION['type'])){
            $render->pagedata['error_info'] = $_SESSION['last_error'];
            unset($_SESSION['last_error']);
            unset($_SESSION['type']);
        }
        if($ext_pagedata){
            foreach($ext_pagedata as $key => $v){
                $render->pagedata[$key] = $v;
            }
        }

        foreach(kernel::servicelist('openid_imageurl') as $object)
        {
            if(is_object($object))
            {
                if(method_exists($object,'get_image_url'))
                {
                    $render->pagedata['login_image_url'][] = $object->get_image_url();
                }
            }
        }
        return $render->fetch($view,$appid);
    }

	/**
	* 认证用户名密码以及验证码等
	* @param object $auth pam_auth对象
	* @param array $usrdata 认证提示信息
	* @return bool|int返回认证成功与否
	*/
    function login($auth,&$usrdata){
		//验证普通图片验证码
        if ($_POST['isebay']) {
            if($auth->is_enable_vcode()){
                $key = $auth->appid;
                if(!base_vcode::verify($key,intval($_POST['verifycode']))){
                    $usrdata['log_data'] = app::get('pam')->_('验证码不正确！');
                    $_SESSION['error'] = app::get('pam')->_('验证码不正确');
                    return false;
                }
            }
        }

        //调用外服接口同步用户信息 //member 区分前台用户登陆
        $sfsc_flag = false;
        $sfsc_uname = trim($_POST['uname']);
        $sfsc_password = trim($_POST['password']);
        if($auth->type=='member'){
            if(constant('DEBUG_LOGIN') !== true && $sfsc = kernel::service("sfsc_login_by_oracle")){
                //如果本系统中没有此用户名，去外服获取 （把这里注释掉，就是不检查本地数据 ，直接去外服网查信息）
                if(!$sfsc->checkuname($sfsc_uname)){
                    $msg = '帐号错误';
                    if(!$sfsc->sfsc_user_login($sfsc_uname,$sfsc_password,&$msg)){
                        $usrdata['log_data'] = app::get('pam')->_('验证失败！');
                        $_SESSION['error'] = $msg;
                        $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
                        return false;
                    }else{
                        $sfsc_flag = true;
                    }
                }
            }
        }

		if($auth->type=='member' && $uc = kernel::service("uc_user_login")){
            if($userInfo = $uc->uc_user_login($_POST['uname'],$_POST['password'])){
                $rows = $userInfo;
            }else{
				$usrdata['log_data'] = app::get('pam')->_('验证失败！');
				$_SESSION['error'] = app::get('pam')->_('uc用户名或密码错误');
				$_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
				return false;
			}
        }else{
			$password_string = pam_encrypt::get_encrypted_password($_POST['password'], $auth->type, array('login_name' => $_POST['uname']));
            if(!$_POST['uname'] || ( !$_POST['password'])){
				$usrdata['log_data'] = app::get('pam')->_('验证失败！');

                $_SESSION['error'] = app::get('pam')->_('用户名为空');
				if(! $_POST['password']){
                    $_SESSION['error'] = app::get('pam')->_('密码为空');
                }

				$_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
				return false;
			}
            /**
             * 调用外服登录接口
             *
             */
            /*$_rMap = SFSC_HttpClient::doLogin($_POST['uname'],$_POST['password']);
            if($_rMap["recode"] != '10001'){
                $usrdata['log_data'] = app::get('pam')->_('验证失败！');
                $_SESSION['error'] = app::get('pam')->_('用户名或密码错误');
                $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
                return false;
            }*/

            if($sfsc_flag){
                $rows = app::get('pam')->model('account')->getList('*', array(
                    'login_name' => $_POST['uname'],
                    'account_type' => $auth->type,
                    'disabled' => 'false',
                ), 0, 1);
            }else{
                $rows = app::get('pam')->model('account')->getList('*', array(
                    'login_name' => $_POST['uname'],
                    'login_password' => $password_string,
                    'account_type' => $auth->type,
                    'disabled' => 'false',
                ), 0, 1);
            }
		}
        //登录时判断是否存在用户名或已验证邮箱或已验证手机
        if(empty($rows)){
            unset($rows);
            $mem_email_rows = app::get('b2c')->model('members')->getList('*', array(
                'email' => $_POST['uname'],
                'disabled' => 'false',
            ));

            if(empty($mem_email_rows)){
                /*
                $mem_mobile_rows = app::get('b2c')->model('members')->getList('*', array(
                    'mobile' => $_POST['uname'],
                    'disabled' => 'false',
                ));

                if(empty($mem_mobile_rows)){
                    $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证失败！');
                    $_SESSION['error'] = app::get('pam')->_('用户名或密码错误');
                    $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
                    return false;
                }else{
                    $rows = app::get('pam')->model('account')->getList('*', array('account_id' => $mem_mobile_rows[0]['member_id'], 'login_password' => $password_string, 'account_type' => $auth->type, 'disabled' => 'false'), 0, 1);
                }
                */


                /*
                 *
		        if(!SFSC_HttpClient::doCheckUname($_POST['uname'])){
                    $usrdata['log_data'] = app::get('pam')->_('验证失败！');
                    $_SESSION['error'] = app::get('pam')->_('用户名或密码错误！');
                    return false;
                }

                $_empinfo = SFSC_HttpClient::doLogin($_POST['uname'],$_POST['password']);
                if($_empinfo["RESULT_CODE"] != '10001'){
                    $usrdata['log_data'] = app::get('pam')->_('验证失败！');
                    $_SESSION['error'] = app::get('pam')->_('用户名或密码错误');
                    $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
                    return false;
                }
		        $rows = app::get('pam')->model('account')->getList('*', array('login_name' => $_empinfo["RESULT_DATA"]['HUMBAS_NO'], 'account_type' => $auth->type, 'disabled' => 'false'), 0, 1);
                */
            }else{
                $rows = app::get('pam')->model('account')->getList('*', array('account_id' => $mem_email_rows[0]['member_id'], 'login_password' => $password_string, 'account_type' => $auth->type, 'disabled' => 'false'), 0, 1);
            }
        }
        
        if($rows[0]){
            if($rows[0]['account_id'] != ''){
                $member_rows = app::get('b2c')->model('members')->getList('seller,reg_ip',array('member_id'=>$rows[0]['account_id']));
                $member_rows[0]['seller'] = trim($member_rows[0]['seller']);
                if($member_rows[0]['seller'] == 'qiye'){
                    $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证失败,请从企业管理员登录！');
                    $_SESSION['error'] = app::get('pam')->_('请从企业管理员登录');
                    $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
                    return false;
                }else if(!$sfsc_flag && $rows[0]['account_type'] == 'member' && $member_rows[0]['seller'] == '' && substr($_POST['uname'],0,5) != 'fedex' && substr($_POST['uname'],0,3) != 'bcf'){
                    if(!(constant('DEBUG_LOGIN') === true)){
                        $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证失败,不能使用人才号直接登录！');
                        $_SESSION['error'] = app::get('pam')->_('抱歉，不能使用人才号直接登录！');
                        return false;
                    }
                }
            }

        if($_POST['remember'] === "true")
        {
		     if(IS_DOMAIN)
		     {
				setcookie('pam_passport_basic_uname', $rows[0]['login_name'], time()+365*24*3600, '/',COOKIE_DOMAIN,1);
		     }else{
				setcookie('pam_passport_basic_uname', $rows[0]['login_name'], time()+365*24*3600, '/');
		     }
	    }
        else
	    {
	    	if(IS_DOMAIN){
		        setcookie('pam_passport_basic_uname', '', 0, '/',COOKIE_DOMAIN,1);
		    }else{
		        setcookie('pam_passport_basic_uname', '', 0, '/');
		    }
	    }

            $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证成功！');
            unset($_SESSION['error_count'][$auth->appid]);
            if(substr($rows[0]['login_password'], 0, 1) !== 's'){
                $pam_filter = array(
                    'account_id' => $rows[0]['account_id']
                );
                $string_pass = md5($rows[0]['login_password'] . $rows[0]['login_name'] . $rows[0]['createtime']);
                $update_data['login_password'] = 's' . substr($string_pass, 0, 31);
                app::get('pam')->model('account')->update($update_data, $pam_filter);
            }

			//同步到ucenter yindingsheng
			if($rows[0]['account_type'] == 'member' && $uc = kernel::service("uc_user_synlogin")){
				$uid = kernel::single('b2c_mdl_members')->getList('foreign_id',array('member_id'=>$rows[0]['account_id']));
				if($uid[0]['foreign_id']){
					$uc->uc_user_synlogin($uid[0]['foreign_id']);
				}
			}
			//同步到ucenter yindingsheng
            return $rows[0]['account_id'];
        }else{
            $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证失败！');
            $_SESSION['error'] = app::get('pam')->_('yoofuu:密码错误');
            $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
            return false;
        }
    }



    function ssoSfscLogin($auth,&$usrdata){
        //调用外服接口同步用户信息
        $sfsc_uname = trim($_POST['uname']);
        $sfsc_password = trim($_POST['password']);
        if($auth->type=='member' && $sfsc = kernel::service("sfsc_httpclient_login")){
            //如果本系统中没有此用户名，去外服获取
            if(!$sfsc->checkuname($sfsc_uname)){
                if(!$sfsc->sfsc_user_login_nopassword($sfsc_uname,$sfsc_password)){
                    $usrdata['log_data'] = app::get('pam')->_('用户验证失败！');
                    return false;
                }
            }
        }

        $password_string = pam_encrypt::get_encrypted_password($_POST['password'], $auth->type, array('login_name' => $_POST['uname']));
        if(!$_POST['uname'] || ( !$_POST['password'])){
            $usrdata['log_data'] = app::get('pam')->_('验证失败！');
            return false;
        }
        //不需要密码验证,此处删除密码
        $rows = app::get('pam')->model('account')->getList('*', array(
            'login_name' => $_POST['uname'],
            'account_type' => $auth->type,
            'disabled' => 'false',
        ), 0, 1);


        //登录时判断是否存在用户名或已验证邮箱或已验证手机
        if(empty($rows)){
            unset($rows);
            $mem_email_rows = app::get('b2c')->model('members')->getList('*', array(
                'email' => $_POST['uname'],
                'disabled' => 'false',
            ));

            $rows = app::get('pam')->model('account')->getList('*', array('account_id' => $mem_email_rows[0]['member_id'], 'login_password' => $password_string, 'account_type' => $auth->type, 'disabled' => 'false'), 0, 1);
        }

        if($rows[0]){
            if($_POST['remember'] === "true"){
					if(IS_DOMAIN){
							setcookie('pam_passport_basic_uname', $rows[0]['login_name'], time()+365*24*3600, '/',COOKIE_DOMAIN,1);
					}else{
							setcookie('pam_passport_basic_uname', $rows[0]['login_name'], time()+365*24*3600, '/');
					}
			}else{ 
					if(IS_DOMAIN){
						setcookie('pam_passport_basic_uname', '', 0, '/',COOKIE_DOMAIN,1);
					}else{
						setcookie('pam_passport_basic_uname', '', 0, '/');
					}
			}

            $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证成功！');
            unset($_SESSION['error_count'][$auth->appid]);
            if(substr($rows[0]['login_password'], 0, 1) !== 's'){
                $pam_filter = array(
                    'account_id' => $rows[0]['account_id']
                );

                $string_pass = md5($rows[0]['login_password'] . $rows[0]['login_name'] . $rows[0]['createtime']);
                $update_data['login_password'] = 's' . substr($string_pass, 0, 31);
                app::get('pam')->model('account')->update($update_data, $pam_filter);
            }
            return $rows[0]['account_id'];
        }else{
            $usrdata['log_data'] = app::get('pam')->_('用户') . $_POST['uname'] . app::get('pam')->_('验证失败！');
            return false;
        }
    }



    
    /**
     * 企业登录
     */
    public function loginQ($name,$USER_ID){
        setcookie('pam_passport_basic_uname',$name,time()+365*24*3600, '/');
        //$_COOKIE['S']['MEMBER']
        kernel::single('base_session')->start();
        $_SESSION['account']['qmember'] = "q".rand(1,1000);
        $_SESSION['account']['USER_ID'] = $USER_ID;
        return true;
    }
    
    
    /**
	* 退出相关操作
	* @param object $autn pam_auth对象
	* @param string $backurl 跳转地址
	*/
    function loginout($auth,$backurl="index.php"){
        unset($_SESSION['account'][$auth->type]);
        unset($_SESSION['last_error']);
        #Header('Location: '.$backurl);
    }

    function get_data(){
    }

    function get_id(){
    }

    function get_expired(){
    }

    /**
	* 得到配置信息
	* @return  array 配置信息数组
	*/
    function get_config(){
        $ret = app::get('pam')->getConf('passport.'.__CLASS__);
        if($ret && isset($ret['shopadmin_passport_status']['value']) && isset($ret['site_passport_status']['value'])){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'true';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;
        }
    }
    /**
	* 设置配置信息
	* @param array $config 配置信息数组
	* @return  bool 配置信息设置成功与否
	*/
    function set_config(&$config){
        $save = app::get('pam')->getConf('passport.'.__CLASS__);
        if(count($config))
            foreach($config as $key=>$value){
                if(!in_array($key,array_keys($save))) continue;
                $save[$key]['value'] = $value;
            }
            $save['shopadmin_passport_status']['value'] = 'true';

        return app::get('pam')->setConf('passport.'.__CLASS__,$save);

    }
   /**
	* 获取finder上编辑时显示的表单信息
	* @return array 配置信息需要填入的项
	*/
    function get_setting(){
        return array(
            'passport_id'=>array('label'=>app::get('pam')->_('通行证id'),'type'=>'text','editable'=>false),
            'passport_name'=>array('label'=>app::get('pam')->_('通行证'),'type'=>'text','editable'=>false),
            'shopadmin_passport_status'=>array('label'=>app::get('pam')->_('后台开启'),'type'=>'bool','editable'=>false),
            'site_passport_status'=>array('label'=>app::get('pam')->_('前台开启'),'type'=>'bool'),
            'passport_version'=>array('label'=>app::get('pam')->_('版本'),'type'=>'text','editable'=>false),
        );
    }
}
