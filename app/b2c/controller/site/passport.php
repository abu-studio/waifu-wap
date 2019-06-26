<?php

class b2c_ctl_site_passport extends b2c_frontpage
{
    public function __construct(&$app)
    {
        parent::__construct($app);
        $this->_response->set_header('Cache-Control', 'no-store');
        kernel::single('base_session')->start();
    }

    public function index()
    {
        $this->login();
    }

    public function gen_vcode()
    {
        $vcode = kernel::single('base_vcode');
        $vcode->length(4);
        $vcode->verify_key($this->app->app_id);
        $vcode->display();

    }

    public function getuname()
    {
        $member = $this->get_current_member();
        $uname  = $member['uname'] ? $member['uname'] : '';
        echo $uname;
        exit;
    }

    public function login($mini = 0, $company = "")
    {
        $this->title                = $this->app->_('会员登录');
        $this->path[]               = array('title' => app::get('b2c')->_('会员登录'), 'link' => 'a');
        $GLOBALS['runtime']['path'] = $this->path;
        /*
        if(!isset($_SESSION['next_page']))
        {
        $_SESSION['next_page'] = $_SERVER['HTTP_REFERER'];
        }
         */
        if (strpos($_SESSION['next_page'], 'passport')) {
            unset($_SESSION['next_page']);
        }

        if ($member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)]) {
            $this->bind_member($_SESSION['account'][pam_account::get_account_type($this->app->app_id)]);
            $obj_mem = $this->app->model('members');
            $sdf     = $obj_mem->dump($member_id);
            if ($sdf['seller'] == 'seller') {
                $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_member', 'act' => 'index'));
            } elseif ($sdf['seller'] == 'qicenter') {
                $url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'index'));
            } else {
                $url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
            }

            $_SESSION['next_page'] = $url;

            if ($_GET['mini_passport'] == 1 || $mini) {
                // $this->_response->set_http_response_code(404);return;
                echo "<script>parent.location.reload();</script>";exit;
            } else {
                $this->splash('success', $url, app::get('b2c')->_('您已经是登录状态，不需要重新登录'));
            }
        }
        $falg = false;
        if ($_GET['mini_passport'] == 1 || $mini) {
            $falg                            = true;
            $this->pagedata['mini_passport'] = 1;
            $this->pagedata['no_right']      = 1;
            $this->pagedata['shopDefine']    = $this->setShop();
        }
        $this->gen_login_form($company);

        //企业登录参数及模版调用
        if ($company) {
            $this->pagedata['company'] = $company;
            $this->set_tmpl('login1');
        } else {
            $this->set_tmpl('login');
        }
        if (!$mini) {
            $this->page('site/passport/login.html', $falg);
        }

    }

    private function mini_login()
    {
        if ($_GET['mini_passport'] == 1) {
            $this->gen_login_form_mini();
            $this->pagedata["mini_passport"] = 1;
            return true;
        }
        return false;
    }

    public function gen_login_form_mini()
    {
        $auth = pam_auth::instance(pam_account::get_account_type($this->app->app_id));
        #设置回调函数地址
        $auth->set_redirect_url(base64_encode($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'post_login'))));
        foreach (kernel::servicelist('passport') as $k => $passport) {
            if ($auth->is_module_valid($k)) {
                $this->pagedata['passports'][] = array(
                    'name' => $auth->get_name($k) ? $auth->get_name($k) : $passport->get_name(),
                    'html' => $passport->get_login_form($auth, $singup_url),
                );
            }
        }
    }

    public function gen_login_form($company)
    {
        $pagedata['company'] = $company;
        $pagedata['_THEME_'] = kernel::base_url() . "/themes/" . kernel::single('site_theme_base')->get_default(); //模版地址

        if ($_SESSION['next_page']) {
            $url = $_SESSION['next_page'];
        } else {
            $url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
        }
        unset($_SESSION['next_page']);

        $auth = pam_auth::instance(pam_account::get_account_type($this->app->app_id));
        $auth->set_appid($this->app->app_id);
        $this->pagedata['qiyecenter_callback'] = $auth->get_callback_url('qiyecenter');
        if ($_GET['mini_passport'] == 1) {
            $pagedata['mini_passport'] = 1;
            $pagedata['singup_url']    = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'signup')) . "?mini_passport=1";
        } else {
            $pagedata['singup_url'] = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'signup'));
        }
        $pagedata['lost_url']  = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost'));
        $pagedata['loginName'] = $_COOKIE['loginName'];
        #设置回调函数地址
        if ($_GET['mini_passport'] == 1) {
            $redirect_url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'post_login', 'arg' => base64_encode($url))) . "?mini=1";
            $auth->set_redirect_url(base64_encode($redirect_url));
        } else {
            $auth->set_redirect_url(base64_encode($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'post_login', 'arg' => base64_encode($url)))));
        }

        foreach (kernel::servicelist('passport') as $k => $passport) {
            if ($auth->is_module_valid($k)) {
                $this->pagedata['passports'][] = array(
                    'name' => $auth->get_name($k) ? $auth->get_name($k) : $passport->get_name(),
                    'html' => $passport->get_login_form($auth, 'b2c', 'site/passport/member-login.html', $pagedata),
                );
            }
        }
    }

    public function post_login_mini()
    {
        $member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)];
        if ($member_id) {
            $this->bind_member($member_id);
            $this->splash('success', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index')), app::get('b2c')->_('登录成功1'));
        } else {
            $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')), app::get('b2c')->_('登录失败'));
        }
    }

    public function post_login($url = null)
    {
        $url       = base64_decode($url);
        $mini      = $_GET['mini'];
        $member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)];
        unset($_SESSION['next_page']);
        if ($member_id) {
            $obj_mem      = $this->app->model('members');
            $member_point = $this->app->model('member_point');
            $member_data  = $obj_mem->dump($member_id);
            if (!$member_data) {
                //如果pam表存在记录而member表不存在记录
                $this->unset_member();
                if ($mini == 1) {
                    echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => app::get('b2c')->_("登录失败")));return;
                } else {
                    $_SESSION['next_page'] = $url;
                    $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')), app::get('b2c')->_('登录失败'), '', '', true);
                }
            }
            $sdf = $obj_mem->dump($member_id);

            //   卖家登录
            $seller = $sdf['seller'];

            if ($seller == 'seller') {
                $this->set_cookie('login_member_type', 'seller', 0); //判断是否为买家
                $sto  = kernel::single("business_memberstore", $member_id);
                $data = $sto->storeinfo;

                if ($sto->isshoper == 'false' && $sto->isshopmember == 'false') {
                    //未提交入驻申请
                    $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1', 'full' => 1));
                } else {
                    $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_member', 'act' => 'index', 'full' => 1));
                }
            } else {
                $this->set_cookie('login_member_type', 'buyers', 0); //设置不是买家的字段
                //如果是雇员则要接口检验
                //$url=$this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'index','full' => 1));
                /**
                 * 调用外服登录接口
                 *
                $member = $this->get_current_member();
                $uname = $member['uname'] ? $member['uname'] : '';
                $_empinfo = SFSC_HttpClient::doLogin($uname,$_SESSION["account"]["__pwd"]);
                if($_empinfo["RESULT_CODE"] != '10001'){
                //$_SESSION['next_page'] = $url;
                $this->unset_member();
                //$this->splash('failed',"javascript:changeimg('membervocde')",app::get('b2c')->_('用户名或密码错误'),'','',true);
                $this->splash('failed',"javascript:changeimg('membervocde')",print_r($member_data),'','',true);
                return;
                }*/
            }

            $obj_order        = $this->app->model('orders');
            $msg              = kernel::single('b2c_message_msg');
            $sdf['order_num'] = count($obj_order->getList('order_id', array('member_id' => $member_id)));
            $sdf['unreadmsg'] = count($msg->getList('*', array('to_id' => $member_id, 'has_sent' => 'true', 'for_comment_id' => 'all', 'mem_read_status' => 'false')));
            unset($msg);
            if ($this->app->getConf('site.level_switch') == 1) {
                $sdf['member_lv']['member_group_id'] = $obj_mem->member_lv_chk($sdf['member_lv']['member_group_id'], $sdf['experience']);
            }
            if ($this->app->getConf('site.level_switch') == 0) {
                $sdf['member_lv']['member_group_id'] = $member_point->member_lv_chk($member_id, $sdf['member_lv']['member_group_id'], $sdf['score']['total']);
            }
            $obj_mem->save($sdf);
            $this->bind_member($member_id);

            if ($mini == 1) {
                echo json_encode(array('status' => 'plugin_passport', 'url' => $url));return;
            } else {
                $this->app->model('cart_objects')->setCartNum($arr);
                $this->splash('success', $url, app::get('b2c')->_('登录成功'), '', '', true);
                exit;
            }
        } else {
            $msg = $_SESSION['error'] ? $_SESSION['error'] : app::get('b2c')->_('页面已过期,操作失败!');
            unset($_SESSION['error']);
            if ($mini == 1) {
                echo '<script>changeimg("membervocde");</script>'; //刷新验证码
                echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => $msg));return;
            } else {

                $_SESSION['next_page'] = $url;

                $this->splash('failed', "javascript:changeimg('membervocde');", app::get('b2c')->_($msg), '', '', true);

            }
        }
    }

    public function postSfscLogin($url = null)
    {
        $url       = base64_decode($url);
        $mini      = $_GET['mini'];
        $member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)];
        unset($_SESSION['next_page']);
        if ($member_id) {
            $obj_mem      = $this->app->model('members');
            $member_point = $this->app->model('member_point');
            $member_data  = $obj_mem->dump($member_id);
            if (!$member_data) {
                //如果pam表存在记录而member表不存在记录
                $this->unset_member();
                if ($mini == 1) {
                    header('Location:' . $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
                    exit;
                } else {
                    $_SESSION['next_page'] = $url;
                    header('Location:' . $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
                    exit;
                }
            }
            $sdf = $obj_mem->dump($member_id);

            //   卖家登录
            $seller = $sdf['seller'];

            if ($seller == 'seller') {

                $sto  = kernel::single("business_memberstore", $member_id);
                $data = $sto->storeinfo;

                if ($sto->isshoper == 'false' && $sto->isshopmember == 'false') {
                    //未提交入驻申请
                    $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1', 'full' => 1));
                } else {
                    $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_member', 'act' => 'index', 'full' => 1));
                }
            } else {

            }

            $obj_order        = $this->app->model('orders');
            $msg              = kernel::single('b2c_message_msg');
            $sdf['order_num'] = count($obj_order->getList('order_id', array('member_id' => $member_id)));
            $sdf['unreadmsg'] = count($msg->getList('*', array('to_id' => $member_id, 'has_sent' => 'true', 'for_comment_id' => 'all', 'mem_read_status' => 'false')));
            unset($msg);
            if ($this->app->getConf('site.level_switch') == 1) {
                $sdf['member_lv']['member_group_id'] = $obj_mem->member_lv_chk($sdf['member_lv']['member_group_id'], $sdf['experience']);
            }
            if ($this->app->getConf('site.level_switch') == 0) {
                $sdf['member_lv']['member_group_id'] = $member_point->member_lv_chk($member_id, $sdf['member_lv']['member_group_id'], $sdf['score']['total']);
            }
            $obj_mem->save($sdf);
            $this->bind_member($member_id);

            if ($mini == 1) {
                header('Location:' . $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
                exit;
            } else {
                $this->app->model('cart_objects')->setCartNum($arr);
                header('Location:' . $url);
                exit;
            }
        } else {
            header('Location:' . $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
            exit;
        }
    }

    public function namecheck()
    {
        $obj_member = &$this->app->model('members');
        $name       = trim($_POST['name']);

        if (strlen($name) > 0) {
            if (!preg_match('/^[a-zA-Z0-9_]*$/u', $name)) {
                //后台取消纯数字不可进行会员添加
                echo '<span class="font-red">' . app::get('b2c')->_('英文字母开头,3-20位数字、字母和下划线组合') . '</span>';
                exit;
            } else {
                /*if(preg_match('/^[0-9]+$/', $name)){
                echo '<span class="font-red">' . app::get('b2c')->_('不能全为数字') . '</span>';//后台取消纯数字不可进行会员添加
                exit;
                }*/

                if (strlen($name) < 3 || strlen($name) > 20) {
                    echo '<span class="font-red">' . app::get('b2c')->_('长度只能在3-20位字符之间') . '</span>';
                    exit;
                } else {
                    if (!$obj_member->is_exists($name)) {

                        //加入 调用外服接口start

                        $_sjson = array(
                            'USER_NAME' => $name,
                        );
                        //true存在，用户名不可用；false不存在，用户名可用
                        $post_data = array('serviceNo' => 'CheckUname', "inputParam" => json_encode($_sjson));
                        $tmpdata   = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
                        if ($tmpdata != null && gettype($tmpdata) == "object") {
                            $tmp = SFSC_HttpClient::objectToArray($tmpdata);
                        }
                        if ($tmp['RESULT_CODE'] == "10001") {
                            echo '<span class="font-green">' . app::get('b2c')->_('可以使用') . '</span>';
                            exit;
                        }

                        if ($tmp['RESULT_CODE'] == "10000") {
                            echo '<span class="font-red">' . $tmp['RESULT_MSG'] . '</span>';
                            exit;
                        }

                    }

                    echo '<span class="font-red">' . app::get('b2c')->_('已被占用') . '</span>';
                    exit;
                }
            }
        }

    }

    public function emailcheck()
    {
        $obj_member = $this->app->model('members');

        if ($_POST['email'] != '') {

            if ($obj_member->is_exists($_POST['email'])) {
                echo '<span class="font-red">' . app::get('b2c')->_('已被占用') . '</span>';
                exit;
            }

            if ($obj_member->is_exists_email($_POST['email'])) {
                echo '<span class="font-red">' . app::get('b2c')->_('已被占用') . '</span>';
                exit;
            }

            //加入外服邮箱唯一性校验start

            //加入外服邮箱唯一性校验end

        }

    }

    //用于商家入驻
    public function applyemailcheck()
    {
        $obj_member = $this->app->model('members');

        if ($_POST['email'] != '') {

            if ($obj_member->is_exists($_POST['email'])) {
                echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app::get('b2c')->_('已被占用') . '</span>'));
                exit;
            }

            if ($obj_member->is_exists_email($_POST['email'])) {
                echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app::get('b2c')->_('已被占用') . '</span>'));
                exit;
            }
            echo json_encode(array('status' => 'success', 'message' => '<span class="font-green">&nbsp;' . app::get('b2c')->_('可以使用') . '</span>'));
            exit;
        }
    }

    public function applymobilecheck()
    {
        $obj_member = &$this->app->model('members');
        $mobile     = trim($_POST['mobile']);
        if (trim($_POST['member_id'])) {
            $member_id = trim($_POST['member_id']);
        } else {
            $member_id = null;
        }

        if ($mobile != '') {
            if (!preg_match('/^(1[3458])-?\d{9}$/', $mobile)) {
                //echo '<span class="font-red">'.app::get('b2c')->_('手机号码格式有误，请输入以13/14/15/18开头的11位数字').'</span>';
                echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app::get('b2c')->_('请填写有效的手机号码，以13/14/15/18开头的11位数字') . '</span>'));
                exit;
            } else {
//                if(!$obj_member->is_exists($mobile)){
                if ($obj_member->is_exists_mobile($mobile, $member_id)) {
                    echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app::get('b2c')->_('该手机号已被使用') . '</span>'));
                    exit;
                }
                echo json_encode(array('status' => 'success', 'message' => '<span class="font-green">&nbsp;' . app::get('b2c')->_('可以使用') . '</span>'));
                exit;

//                }

                //echo '<span class="font-red">'.app::get('b2c')->_('该手机号已被使用，请更换号码').'</span>';
            }
        }
    }

    public function verifyCode()
    {
        $vcode = kernel::single('base_vcode');
        $vcode->length(4);
        $vcode->verify_key('LOGINVCODE');
        $vcode->display();
    }

    public function verify()
    {
        $this->begin($this->gen_url('passport', 'login'));
        $member_model = &$this->app->model('members');
        $verifyCode   = app::get('b2c')->getConf('site.register_valide');
        if ($verifyCode == "true") {
            if (!base_vcode::verify('LOGINVCODE', intval($_POST['loginverifycode']))) {
                $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')), app::get('b2c')->_('验证码错误'));

            }
        }
        $rows = app::get('pam')->model('account')->getList('account_id', array('account_type' => 'member', 'disabled' => 'false', 'login_name' => $_POST['login'], 'login_password' => pam_encrypt::get_encrypted_password($_POST['passwd'], pam_account::get_account_type($this->app->app_id), array('login_name' => $_POST['login']))));
        if ($rows) {
            $_SESSION['account'][pam_account::get_account_type($this->app->app_id)] = $rows[0]['account_id'];
            $this->bind_member($rows[0]['account_id']);
            $this->end(true, app::get('b2c')->_('登录成功3，进入会员中心'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index')));
        } else {
            $_SESSION['login_msg'] = app::get('b2c')->_('用户名或密码错误');
            $this->end(false, $_SESSION['login_msg'], $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'login')));
        }
    }

    public function __restore()
    {
        if ($_SESSION['login_info']['post']) {
            call_user_func_array(array(&$this, 'redirect'), $_SESSION['login_info']['action']);
        }
    }

    public function signup($url = null)
    {

        //2013-6-18  判断是否卖家注册
        $_getParams = $this->_request->get_params();
        if ($_getParams[0] == 'seller') {
            $this->pagedata['seller'] = $_getParams[0];
            $url                      = null;
            $this->title              = $this->app->_('企业注册');
        } else {
            $this->title = $this->app->_('会员注册');
        }

        $this->path[]               = array('title' => app::get('b2c')->_('会员注册'), 'link' => 'a');
        $GLOBALS['runtime']['path'] = $this->path;
        $login_url                  = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'login'));

        foreach (kernel::servicelist('api_signup') as $signup) {
            if (is_object($signup)) {
                if ($signup->get_status()) {
                    $signup_url = $signup->get_url();
                    echo "<script>location.href='{$signup_url}';</script>";
                }
            }
        }

        if (!strpos($_SERVER['HTTP_REFERER'], 'passport')) {
            $_SESSION['signup_next'] = $_SERVER['HTTP_REFERER'];
        }

        $falg = false;
        if ($_GET['mini_passport'] == 1) {
            $falg                            = true;
            $this->pagedata['mini_passport'] = 1;
            $login_url                       = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'login')) . "?mini_passport=1"; //登录链接
            $this->pagedata['shopDefine']    = $this->setShop();
        }

        $member_model = $this->app->model('members');
        $mem_schema   = $member_model->_columns();
        $attr         = array();

        foreach ($this->app->model('member_attr')->getList() as $item) {
            if ($item['reg_show'] == "true") {
                $attr[] = $item;
            }
            //筛选显示项
        }

        foreach ((array) $attr as $key => $item) {
            $sdfpath = $mem_schema[$item['attr_column']]['sdfpath'];
            if ($sdfpath) {
                $a_temp = explode("/", $sdfpath);
                if (count($a_temp) > 1) {
                    $name = array_shift($a_temp);
                    if (count($a_temp)) {
                        foreach ($a_temp as $value) {
                            $name .= '[' . $value . ']';
                        }
                    }

                }
            } else {
                $name = $item['attr_column'];
            }

            if ($attr[$key]['attr_type'] == 'select' || $attr[$key]['attr_type'] == 'checkbox') {
                $attr[$key]['attr_option'] = unserialize($attr[$key]['attr_option']);
            }

            $attr[$key]['attr_column'] = $name;
            if ($attr[$key]['attr_column'] == "birthday") {
                $attr[$key]['attr_column'] = "profile[birthday]";
            }
        }

        $this->pagedata['attr']      = $attr;
        $this->pagedata['next_url']  = $url;
        $this->pagedata['login_url'] = $login_url; //登录链接
        $this->set_tmpl('passport');
        $this->pagedata['valideCode'] = app::get('b2c')->getConf('site.register_valide');
        $this->page("site/passport/signup.html", $falg);

    }

    /**
     * save_attr
     * 保存会员注册信息
     *
     * @access private
     * @return bool
     */
    private function save_attr($member_id = null, $aData, &$msg)
    {
        if (!$member_id) {
            $msg = app::get('b2c')->_('注册失败');
            return false;
        }
        $member_model                       = &$this->app->model('members');
        $aData['pam_account']['account_id'] = $member_id;
        if (!$_POST['profile']['birthday']) {
            unset($aData['profile']['birthday']);
        }

        if ($aData['profile']['gender'] == 1) {
            $aData['profile']['gender'] = 'male';
        } elseif ($aData['profile']['gender'] === 0) {
            $aData['profile']['gender'] = 'female';
        } else {
            $aData['profile']['gender'] = 'no';
        }
        foreach ($aData as $key => $val) {
            if (strpos($key, "box:") !== false) {
                $aTmp            = explode("box:", $key);
                $aData[$aTmp[1]] = serialize($val);
            }
        }

        if ($aData['contact']['name'] && !preg_match('/^([@\.]|[^\x00-\x2f^\x3a-\x40]){2,20}$/i', $aData['contact']['name'])) {
            $msg = app::get('b2c')->_('姓名包含非法字符');
            return false;
        }
        $obj_filter = kernel::single('b2c_site_filter');

        $aData = $obj_filter->check_input($aData);

        if ($member_model->save($aData)) {

            $msg = app::get('b2c')->_('注册成功');
            return true;
        }
        $msg = app::get('b2c')->_('注册失败');
        return false;

    }

    /**
     * create
     * 创建会员
     * 采用事务处理,function save_attr 返回false 立即回滚
     * @access public
     * @return void
     */
    public function create($next_url = null)
    {
        //点击了按钮就是同意
        $_POST['license'] = 'agree';
        $mini             = $_GET['mini'];
        //$back_url = $this->gen_url(array('app'=>'b2c','ctl'=>'site_passport','act'=>'signup'));
        $back_url     = null;
        $next_url     = base64_decode($next_url);
        $member_model = &$this->app->model('members');

        if ($_POST['reg_type'] == 'email') {
            $_POST['pam_account']['login_name'] = strtolower($_POST['regName']);
            $_POST['contact']['email']          = htmlspecialchars($_POST['regName']);
            unset($_POST['mobileverifycode']);

        }

        if ($_POST['reg_type'] == 'mobile') {
            $_POST['pam_account']['login_name']  = strtolower($_POST['regName']);
            $_POST['contact']['phone']['mobile'] = strtolower($_POST['regName']);
        }

        if ($_POST['reg_type'] == 'username') {
            $_POST['pam_account']['login_name'] = strtolower($_POST['regName']);
            $_POST['contact']['email']          = htmlspecialchars($_POST['contact']['commonlyemail']);
            unset($_POST['mobileverifycode']);
        }

        //验证注册项
        if (!$member_model->validate($_POST, $msg)) {
            if ($mini != 1) {
                $this->splash('failed', $back_url, $msg, '', '', true);
            } else {
                echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => $msg));return;
            }
        }

        //验证普通图片验证码
        if ($_POST['reg_type'] == 'email' || $_POST['reg_type'] == 'username') {
            $valideCode = app::get('b2c')->getConf('site.register_valide');
            if ($valideCode == 'true') {
                if (!base_vcode::verify('LOGINVCODE', intval($_POST['signupverifycode']))) {
                    if ($mini != 1) {
                        $this->splash('failed', 'javascript:changeimg("membercode");', app::get('b2c')->_('验证码填写错误'), '', '', true);
                    } else {
                        echo '<script>changeimg("membercode");</script>'; //刷新验证码
                        echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => app::get('b2c')->_('验证码填写错误')));return;
                    }
                }
            }
        }

        //验证手机短信验证码
        if ($_POST['reg_type'] == 'mobile') {
            if (!$member_model->check_mobilecode($_POST['mobileverifycode'], $msg)) {
                if ($mini != 1) {
                    $this->splash('failed', $back_url, $msg, '', '', true);
                } else {
                    echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => $msg));return;
                }
            }

            $_POST['verify_mobile'] = 'Y'; //如果是手机注册，那么将验证手机状态置为已验证
        }

        //验证是否同意条款
        if ($_POST['license'] != 'agree') {
            if ($mini != 1) {
                $this->splash('failed', $back_url, app::get('b2c')->_('同意注册条款后才能注册'), '', '', true);
            } else {
                echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => app::get('b2c')->_('同意注册条款后才能注册')));return;
            }
        }

        $lv_model                              = &$this->app->model('member_lv');
        $_POST['member_lv']['member_group_id'] = $lv_model->get_default_lv();
        $arrDefCurrency                        = app::get('ectools')->model('currency')->getDefault();
        $_POST['currency']                     = $arrDefCurrency['cur_code'];

        if ($_POST['reg_type'] == 'email') {
            $_POST['contact']['email'] = htmlspecialchars($_POST['contact']['email']);
            $_POST['verify_email']     = 'Y'; //如果是邮箱注册，那么将验证邮箱状态置为已验证
        }

        //邮箱和手机注册时，用户名随机生成
        if ($_POST['reg_type'] == 'mobile' || $_POST['reg_type'] == 'email') {
            $login_name_prefix = $member_model->getLoginNamePrefix();
            $random_login_name = strtolower($this->randomName($login_name_prefix, 12));

            while (true) {
                if (!$member_model->is_exists($random_login_name)) {
                    break;
                }

                $random_login_name = strtolower($this->randomName($login_name_prefix, 12));
            }

            $_POST['pam_account']['login_name'] = $random_login_name;
        }

        //邮箱和手机注册时，用户名为邮箱或手机
        /*
        if($_POST['reg_type'] == 'mobile'){
        $_POST['pam_account']['login_name'] = $_POST['contact']['phone']['mobile'];
        }elseif($_POST['reg_type'] == 'email'){
        $_POST['pam_account']['login_name'] = $_POST['contact']['email'];
        }
         */

        $_POST['uc_pwd'] = $_POST['pam_account']['login_password'];
        //姓名
        $_name = $_POST['contact']['name'];
        //身份证号
        $_idcard = $_POST['idcard'];

        $_POST['pam_account']['account_type'] = pam_account::get_account_type($this->app->app_id);
        $_POST['pam_account']['createtime']   = time();

        $use_pass_data['login_name'] = $_POST['pam_account']['login_name'];
        $use_pass_data['createtime'] = $_POST['pam_account']['createtime'];

        $_POST['pam_account']['login_password'] = pam_encrypt::get_encrypted_password(trim($_POST['pam_account']['login_password']), pam_account::get_account_type($this->app->app_id), $use_pass_data);

        $_POST['reg_ip']  = base_request::get_remote_addr();
        $_POST['regtime'] = time();

        /**
         * 调用外服注册验证注册项
         */

        //验证姓名+身份证号
        $_sjson = array(
            'NAME'   => $_name,
            'CARDID' => $_idcard,
        );
        ////返回true可以注册，返回false不可以注册
        $post_data = array('serviceNo' => 'CheckNameAndId', "inputParam" => json_encode($_sjson));
        $tmpdata   = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);

        if ($tmpdata != null && gettype($tmpdata) == "object") {
            $tmp = SFSC_HttpClient::objectToArray($tmpdata);
        }

        if ($tmp['RESULT_CODE'] != '10001') {
            $this->splash('failed', $back_url, $tmp['RESULT_MSG'], '', '', true);
            exit;
        }

        //注册时验证用户名
        $_sjson = array(
            'USER_NAME' => $use_pass_data['login_name'],
        );

        $post_data = array('serviceNo' => 'CheckUname', "inputParam" => json_encode($_sjson));
        $tmpdata1  = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
        if ($tmpdata1 != null && gettype($tmpdata1) == "object") {
            $tmp1 = SFSC_HttpClient::objectToArray($tmpdata1);
        }

        if ($tmp1['RESULT_CODE'] == "10001") {
            $this->splash('failed', $back_url, $tmp1['RESULT_MSG'], '', '', true);
            exit;
        }

        /**
         * 调用注册接口，如果成功则走下面 不成功 则 页面输出 没有相关用户，请联系管理员
         */

        $_rs = SFSC_HttpClient::doRegister($_name, $_idcard, $use_pass_data['login_name'], $_POST['uc_pwd'], $_POST['contact']['email']);
        if ($_rs["RESULT_CODE"] != '10001') {
            $sfsc_message = $_rs["RESULT_MSG"] ? $_rs["RESULT_MSG"] : "注册失败！";
            $this->splash('failed', $back_url, $sfsc_message, '', '', true);
        }
        $_arr = SFSC_HttpClient::doLogin($use_pass_data['login_name'], $_POST['uc_pwd']);
        // echo '333';
        // var_dump($_arr);
        // exit();

        $use_pass_data['login_name']        = $_arr["RESULT_DATA"]["HUMBAS_NO"];
        $_POST['pam_account']['login_name'] = $_arr["RESULT_DATA"]["HUMBAS_NO"];

        $db = kernel::database();
        $db->beginTransaction();

        //--防止恶意修改
        foreach ($_POST as $key => $val) {
            if (strpos($key, "box:") !== false) {
                $aTmp            = explode("box:", $key);
                $_POST[$aTmp[1]] = serialize($val);
            }
        }

        /*添加字段reg_type：注册类型*/
        $arr_colunm = array('regtime', 'member_id', 'license', 'reg_ip', 'currency',
            'contact', 'profile', 'pam_account', 'forward', 'member_lv',
            'reg_type', 'verify_email', 'verify_mobile');

        //注册企业用户（卖家）
        if ($_POST['seller'] != 'seller') {
            unset($_POST['seller']);
        } else {
            $arr_colunm = array_merge($arr_colunm, array('seller'));
        }

        $attr = $this->app->model('member_attr')->getList('attr_column');

        foreach ($attr as $attr_colunm) {
            $colunm       = $attr_colunm['attr_column'];
            $arr_colunm[] = $colunm;
        }

        foreach ($_POST as $post_key => $post_value) {
            if (!in_array($post_key, $arr_colunm)) {
                unset($_POST[$post_key]);
            }
        }

        if ($uc = kernel::service("uc_user_register")) {
            $uid = $uc->uc_user_register($_POST['pam_account']['login_name'], $_POST['pam_account']['psw_confirm'], $_POST['contact']['email'], '', '', '', $_POST['contact']['phone']['mobile'], null, $_POST['reg_type']);
            if ($uid > 0) {
                $_POST['foreign_id'] = $uid;
            } else {
                if ($mini != 1) {
                    if ($_POST['reg_type'] == 'mobile') {
                        $this->splash('failed', $back_url, 'UCenter注册失败,请检查用户名或手机号', '', '', true);
                    } else if ($_POST['reg_type'] == 'email') {
                        $this->splash('failed', $back_url, 'UCenter注册失败,请检查用户名或邮箱', '', '', true);
                    } else {
                        $this->splash('failed', $back_url, 'UCenter注册失败,请检查用户名', '', '', true);
                    }
                } else {

                    if ($_POST['reg_type'] == 'mobile') {
                        echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => 'UCenter注册失败,请检查用户名或手机号'));return;
                    } else if ($_POST['reg_type'] == 'email') {
                        echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => 'UCenter注册失败,请检查用户名或邮箱'));return;
                    } else {
                        echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => 'UCenter注册失败,请检查用户名'));return;
                    }
                }
            }
        }

        if ($member_model->save($_POST)) {

            $member_id = $_POST['member_id'];
            if (!($this->save_attr($member_id, $_POST, $msg))) {
                $db->rollBack();
                if ($mini != 1) {
                    $this->splash('failed', $back_url, $msg, '', '', true);
                } else {
                    echo json_encode(array('status' => 'failed', 'url' => 'back', 'msg' => $msg));return;
                }
            }

            $db->commit();

            $_SESSION['account'][pam_account::get_account_type($this->app->app_id)] = $member_id;
            $this->bind_member($member_id);

            if ($uc = kernel::service("uc_user_synlogin")) {
                $uc->uc_user_synlogin($_POST['foreign_id']);
            }

            foreach (kernel::servicelist('b2c_save_post_om') as $object) {
                $object->set_arr($member_id, 'member');
                $refer_url = $object->get_arr($member_id, 'member');
            }

            /*注册完成后做某些操作! begin*/
            foreach (kernel::servicelist('b2c_register_after') as $object) {
                $object->registerActive($member_id);
            }

            //增加会员同步 2012-5-15
            if ($member_rpc_object = kernel::service("b2c_member_rpc_sync")) {
                $member_rpc_object->createActive($member_id);
            }
            /*end*/

            $data['member_id']   = $member_id;
            $data['uname']       = $_POST['pam_account']['login_name'];
            $data['passwd']      = $_POST['pam_account']['psw_confirm'];
            $data['email']       = $_POST['contact']['email'];
            $data['refer_url']   = $refer_url ? $refer_url : '';
            $data['is_frontend'] = true;

            $obj_account = &$this->app->model('member_account');
            $obj_account->fireEvent('register', $data, $member_id);

            if ($next_url) {
                header("Location: " . $next_url);
            } else {

                if ($mini != 1) {
                    //   $this->splash('success', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index')), app::get('b2c')->_('注册成功'), '', '', true);
                    if (isset($_SESSION['signup_next']) && $_SESSION['signup_next']) {
                        //企业注册后直接跳转至入驻。
                        if ($_POST['seller'] == 'seller') {
                            $signup_next = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1'));
                        } else {
                            $signup_next = $_SESSION['signup_next'];
                        }
                        unset($_SESSION['signup_next']);

                        if ($errmsg) {
                            $this->splash('success', $signup_next, app::get('b2c')->_('会员注册成功,注册银盛账号失败：') . $errmsg, '', '', true);
                        } else {
                            //echo json_encode(array('status' => 'succ', 'url' => $signup_next, 'msg' => app::get('b2c')->_('注册成功')));
                            $this->splash('success', $signup_next, app::get('b2c')->_('注册成功'), '', '', true);
                        }
                        return;
                    } else {
                        //企业注册后直接跳转至入驻。
                        if ($_POST['seller'] == 'seller') {
                            $re_next = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1'));
                        } else {
                            $re_next = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
                        }

                        if ($errmsg) {
                            //echo json_encode(array('status' => 'succ', 'url' => $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index')), 'msg' => app::get('b2c')->_('注册成功').$errmsg));
                            $this->splash('success', $re_next, app::get('b2c')->_('注册成功') . $errmsg, '', '', true);

                        } else {

                            //echo json_encode(array('status' => 'succ', 'url' => $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index')), 'msg' => app::get('b2c')->_('注册成功')));
                            $this->splash('success', $re_next, app::get('b2c')->_('注册成功'), '', '', true);

                        }
                        return;
                    }

                } else {
                    if (isset($_SESSION['signup_next']) && $_SESSION['signup_next']) {
                        //企业注册后直接跳转至入驻。
                        if ($_POST['seller'] == 'seller') {
                            $signup_next = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1'));
                        } else {
                            $signup_next = $_SESSION['signup_next'];
                        }

                        unset($_SESSION['signup_next']);

                        if ($errmsg) {
                            echo json_encode(array('status' => 'succ', 'url' => $signup_next, 'msg' => app::get('b2c')->_('会员注册成功,注册银盛账号失败：') . $errmsg));
                        } else {
                            echo json_encode(array('status' => 'succ', 'url' => $signup_next, 'msg' => app::get('b2c')->_('注册成功')));
                        }
                        return;
                    } else {
                        //企业注册后直接跳转至入驻。
                        if ($_POST['seller'] == 'seller') {
                            $re_next = $this->gen_url(array('app' => 'business', 'ctl' => 'site_store', 'act' => 'storeapplystep1'));
                        } else {
                            $re_next = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
                        }
                        if ($errmsg) {
                            echo json_encode(array('status' => 'succ', 'url' => $re_next, 'msg' => app::get('b2c')->_('注册成功') . $errmsg));
                        } else {
                            echo json_encode(array('status' => 'succ', 'url' => $re_next, 'msg' => app::get('b2c')->_('注册成功')));
                        }
                        return;
                    }
                }
            }
        }

        $this->splash('failed', $back_url, app::get('b2c')->_('注册失败'), '', '', false);
    }

    /*----------- 次要流程 ---------------*/

    public function recover()
    {
        $this->path[]               = array('title' => app::get('b2c')->_('忘记密码'), 'link' => 'a');
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_member                 = &$this->app->model('members');

        //找回密码时，验证验证码是否正确
        if (!base_vcode::verify('b2c', intval($_POST['verifycode']))) {
            $this->splash('failed', "javascript:changeimg('membervocde');", app::get('b2c')->_('验证码不正确！'), '', '', true);
        }
        //end

        /* 用户名可以是注册的用户名账号，也可以是已验证邮箱和手机号 */
        $obj_account = &app::get('pam')->model('account');
        $rows        = $obj_account->getList('*', array('account_type' => 'member', 'login_name' => $_POST['login']));

        if (empty($rows)) {
            unset($rows);
            $mem_filter = array(
                'filter_sql' => '`email`="' . $_POST['login'] . '" OR `mobile`="' . $_POST['login'] . '"',
            );
            $mem_rows = $obj_member->getList('*', $mem_filter);

            if (empty($mem_rows)) {
                $this->splash('failed', '', app::get('b2c')->_('该用户不存在！'), '', '', true);
            }

            $rows = $obj_account->getList('*', array('account_type' => 'member', 'account_id' => $mem_rows[0]['member_id']));
        }

        $member_id                            = $rows[0]['account_id'];
        $this->pagedata['data']               = $obj_member->dump($member_id);
        $this->pagedata['data']['login_name'] = $rows[0]['login_name'];

        /* 用户名部分用“*”代替 */
        $name_len = strlen($this->pagedata['data']['login_name']);

        if ($name_len >= 3) {
            $rep = '';
            for ($i = 0; $i < $name_len - 2; $i++) {
                $rep .= '*';
            }
            $hidden_name = substr_replace($this->pagedata['data']['login_name'], $rep, 1, $name_len - 2);
        } else {
            $hidden_name = $this->pagedata['data']['login_name'];
        }
        $this->pagedata['data']['hidden_name'] = $hidden_name;

        if ($this->pagedata['data']['disabled'] == "true") {
            $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')), app::get('b2c')->_('该用户已经放入回收站！'), '', '', true);
        }

        /* 邮箱和手机号码部分用“*”代替  */
        $verifyWay = array();
        if ($this->pagedata['data']['contact']['email'] != '') {
            $verifyWay[] = 'email';

            $part_len = strpos($this->pagedata['data']['contact']['email'], '@');
            $rep      = '';
            if ($part_len >= 3) {
                for ($i = 0; $i < $part_len - 2; $i++) {
                    $rep .= '*';
                }
                $hidden_email = substr_replace($this->pagedata['data']['contact']['email'], $rep, 1, $part_len - 2);
            } else {
                $hidden_email = $this->pagedata['data']['contact']['email'];
            }

            $this->pagedata['data']['contact']['hidden_email'] = $hidden_email;
        }

        if ($this->pagedata['data']['contact']['phone']['mobile'] != '') {
            $verifyWay[]                                                 = 'mobile';
            $rep                                                         = '*****';
            $hidden_mobile                                               = substr_replace($this->pagedata['data']['contact']['phone']['mobile'], $rep, 3, 5);
            $this->pagedata['data']['contact']['phone']['hidden_mobile'] = $hidden_mobile;
        }

        $this->pagedata['verifyWay'] = $verifyWay;

        $this->set_tmpl('lost');
        $this->display("site/passport/recover.html");
    }

    public function sendPSW()
    {
        $this->begin($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
        $rows       = app::get('pam')->model('account')->getList('*', array('account_type' => 'member', 'login_name' => $_POST['uname']));
        $member_id  = $rows[0]['account_id'];
        $obj_member = &$this->app->model('members');
        $data       = $obj_member->dump($member_id);
//        if(($data['account']['pw_answer']!=$_POST['pw_answer']) || ($data['contact']['email']!=$_POST['email']))
        //        {
        //            $this->end(false,app::get('b2c')->_('问题回答错误或当前账户的邮箱填写错误'),$this->gen_url(array('app'=>'b2c','ctl'=>'site_passport','act'=>'index')),'',true);
        //        }

        if ($data['pam_account']['account_id'] < 1) {
            $this->end(false, app::get('b2c')->_('会员信息错误'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
        }

        $objRepass = $this->app->model('member_pwdlog');
        $secret    = $objRepass->generate($data['pam_account']['account_id']);
        $url       = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index'));

        if ($_POST['verify_way'] == 'mobile') {
            if (!$obj_member->check_mobilecode($_POST['mobileverifycode'], $msg)) {
                $this->end(false, $msg, $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
            }

            if ($objRepass->isValiad($secret)) {
                $this->pagedata['secret'] = $secret;
                $this->set_tmpl('lost');
                $this->page("site/passport/repass.html");
            } else {
                $this->end(false, app::get('b2c')->_('参数不正确，请重新申请密码取回'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
            }

        } else {
            $sdf                         = app::get('pam')->model('account')->dump($member_id);
            $new_password                = $this->randomkeys(6);
            $use_pass_data['login_name'] = $rows[0]['login_name'];
            $use_pass_data['createtime'] = $rows[0]['createtime'];

            $sdf['login_password'] = pam_encrypt::get_encrypted_password(trim($new_password), pam_account::get_account_type($this->app->app_id), $use_pass_data);

            if ($member_object = kernel::service("uc_user_edit")) {
                $aData['member_id'] = $member_id;
                $aData['passwd_re'] = $new_password;
                if (!$member_object->uc_user_edit_pwd($aData)) {
                    $this->end(false, app::get('b2c')->_('第三方修改密码失败,请重试'), $url);
                    return false;
                }
            }

            if (app::get('pam')->model('account')->save($sdf)) {
                if ($this->send_email($_POST['uname'], $data['contact']['email'], $new_password, $member_id)) {
                    $this->end(true, app::get('b2c')->_('密码变更邮件已经发送到') . $data['contact']['email'] . app::get('b2c')->_('，请注意查收'), $url);
                } else {
                    $this->end(false, app::get('b2c')->_('发送失败，请与商家联系'), $url);
                }
            } else {
                $this->end(false, app::get('b2c')->_('发送失败，请与商家联系'), $url);
            }
        }
    }

    ####随机取6位字符数
    public function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyz'; //字符池
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern{mt_rand(0, 35)}; //生成php随机数
        }
        return $key;
    }

    public function send_email($login_name, $user_email, $new_password, $member_id)
    {
        $ret = $this->app->getConf('messenger.actions.account-lostPw');
        $ret = explode(',', $ret);

        if (!in_array('b2c_messenger_email', $ret)) {
            return false;
        }

        $data['uname']  = $login_name;
        $data['passwd'] = $new_password;
        $data['email']  = $user_email;
        $obj_account    = &$this->app->model('member_account');
        return $obj_account->fireEvent('lostPw', $data, $member_id);
    }

    public function lost()
    {
        $this->path[]               = array('title' => app::get('b2c')->_('忘记密码'), 'link' => 'a');
        $GLOBALS['runtime']['path'] = $this->path;
        $url                        = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
        if ($_SESSION['account'][pam_account::get_account_type($this->app->app_id)]) {
            $url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
            $this->splash('failed', $url, app::get('b2c')->_('请先退出'));
        }
        $this->set_tmpl('lost');
        $this->page("site/passport/lost.html");
    }

    public function repass($secret)
    {
        $this->begin(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'repass'));
        $objRepass = $this->app->model('member_pwdlog');

        if ($objRepass->isValiad($secret)) {
            $this->pagedata['secret'] = $secret;
            $this->set_tmpl('lost');
            $this->page("site/passport/repass.html");
        } else {
            $this->end(true, app::get('b2c')->_('参数不正确，请重新申请密码取回'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        }
    }

    /* 设置新密码 */
    public function dorepass()
    {
        $this->begin($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
        $objRepass = $this->app->model('member_pwdlog');

        if ($_POST['password'] == '') {
            $this->end(false, app::get('b2c')->_('请输入新密码！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        } elseif (strlen($_POST['password']) < 6) {
            $this->end(false, app::get('b2c')->_('密码长度不能小于6！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        } elseif (strlen($_POST['password']) > 20) {
            $this->end(false, app::get('b2c')->_('密码长度不能大于20！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        } elseif ($_POST['repassword'] == '') {
            $this->end(false, app::get('b2c')->_('请输入确认新密码！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        } elseif ($_POST['repassword'] != $_POST['password']) {
            $this->end(false, app::get('b2c')->_('确认密码与新密码不一致！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        }

        if ($objRepass->rePass($_POST)) {
            $this->end(true, app::get('b2c')->_('新密码设置成功，请牢记您新设置的密码！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index')));
        } else {
            $this->end(false, app::get('b2c')->_('新密码设置失败，请重新设置您的新密码！'), $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'lost')));
        }
    }

    public function error()
    {
        $this->unset_member();
        $back_url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'index'));
        $this->splash('failed', $back_url, app::get('b2c')->_('本页需要会员才能进入，您未登录或者已经超时'));
    }

    //退出按钮执行操作
    public function logout()
    {
        $this->unset_member();
        $this->app->model('cart_objects')->setCartNum($arr);
        if (!$this->app->app_id == 'b2c') {
            $this->redirect(array('app' => 'b2c', 'ctl' => 'passport', 'act' => 'index', 'full' => 1));
        } else {
            $redirectUrl = trim($_SERVER['HTTP_REFERER']);
            if (empty($redirectUrl)) {
                $redirectUrl = '/index.html';
            }

            $this->_response->set_http_response_code(302);
            header("location:" . $redirectUrl);
        }

    }

    public function unset_member()
    {
        $auth = pam_auth::instance(pam_account::get_account_type($this->app->app_id));
        foreach (kernel::servicelist('passport') as $k => $passport) {
            $passport->loginout($auth);
        }
        $this->app->member_id = 0;
        $this->cookie_path    = kernel::base_url() . '/';
        $this->set_cookie('MEMBER', null, time() - 3600);
        $this->set_cookie('UNAME', '', time() - 3600);
        $this->set_cookie('MLV', '', time() - 3600);
        $this->set_cookie('JAVA[UNAME]', '', time() - 3600);
        $this->set_cookie('JAVA[UNAME_EN]', '', time() - 3600);
        $this->set_cookie('CUR', '', time() - 3600);
        $this->set_cookie('LANG', '', time() - 3600);
        $this->set_cookie('S[MEMBER]', '', time() - 3600);
        $this->set_cookie('SELLER', '', time() - 3600);
        $this->set_cookie('login_member_type', '', time() - 3600);
        unset($_SESSION['roleListData']);
        foreach (kernel::servicelist('member_logout') as $service) {
            $service->logout();
        }

        if ($uc = kernel::service("uc_user_synlogout")) {
            $uc->uc_user_synlogout();
        }

    }

    /* 会员注册 */
    public function signupByRegType($url = null)
    {
        $this->path[]               = array('title' => app::get('b2c')->_('会员注册'), 'link' => 'a');
        $GLOBALS['runtime']['path'] = $this->path;
        $login_url                  = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'login'));

        foreach (kernel::servicelist('api_signup') as $signup) {
            if (is_object($signup)) {
                if ($signup->get_status()) {
                    $signup_url = $signup->get_url();
                    echo "<script>location.href='{$signup_url}';</script>";
                }
            }
        }

        if (!strpos($_SERVER['HTTP_REFERER'], 'passport')) {
            $_SESSION['signup_next'] = $_SERVER['HTTP_REFERER'];
        }

        $falg = false;
        if ($_GET['mini_passport'] == 1) {
            $falg                            = true;
            $this->pagedata['mini_passport'] = 1;
        }

        $member_model = $this->app->model('members');
        $mem_schema   = $member_model->_columns();
        $attr         = array();

        foreach ($this->app->model('member_attr')->getList() as $item) {
            if ($item['attr_show'] == "true") {
                $attr[] = $item;
            }
            //筛选显示项
        }

        foreach ((array) $attr as $key => $item) {
            $sdfpath = $mem_schema[$item['attr_column']]['sdfpath'];
            if ($sdfpath) {
                $a_temp = explode("/", $sdfpath);
                if (count($a_temp) > 1) {
                    $name = array_shift($a_temp);
                    if (count($a_temp)) {
                        foreach ($a_temp as $value) {
                            $name .= '[' . $value . ']';
                        }
                    }

                }
            } else {
                $name = $item['attr_column'];
            }

            if ($attr[$key]['attr_type'] == 'select' || $attr[$key]['attr_type'] == 'checkbox') {
                $attr[$key]['attr_option'] = unserialize($attr[$key]['attr_option']);
            }

            $attr[$key]['attr_column'] = $name;
            if ($attr[$key]['attr_column'] == "birthday") {
                $attr[$key]['attr_column'] = "profile[birthday]";
            }
        }

        $this->pagedata['attr']     = $attr;
        $this->pagedata['next_url'] = $url;
        $this->set_tmpl('passport');
        $this->pagedata['valideCode'] = app::get('b2c')->getConf('site.register_valide');
    }

    /* 切换注册方式  */
    public function echoRegPart($url = null)
    {

        //2013-6-18  判断是否卖家注册
        $_getParams = $this->_request->get_params();

        if (count($_getParams) == 1) {
            if ($_getParams[0] == 'seller') {
                $this->pagedata['seller'] = $_getParams[0];
                $url                      = null;
            }
        } elseif (count($_getParams) == 2) {
            if ($_getParams[1] == 'seller') {
                $this->pagedata['seller'] = $_getParams[1];
            }
        }

        $this->signupByRegType($url);

        if ($_POST['regType'] == 'email') {
            $this->__tmpl = 'site/passport/index/signup_email.html';
        } elseif ($_POST['regType'] == 'mobile') {
            $this->__tmpl = 'site/passport/index/signup_mobile.html';
        } elseif ($_POST['regType'] == 'username') {
            $this->__tmpl = 'site/passport/index/signup_username.html';
        }
        $this->page($this->__tmpl, true);
    }

    /* 手机验证  */
    public function mobilecheck()
    {
        $obj_member = &$this->app->model('members');
        $mobile     = trim($_POST['mobile']);
        if (trim($_POST['member_id'])) {
            $member_id = trim($_POST['member_id']);
        } else {
            $member_id = null;
        }

        if ($mobile != '') {
            //if(!preg_match('/^(13\d|144|15[012356789]|18[056789])-?\d{8}$/', $mobile)){
            if (!preg_match('/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}|14[0-9]{1}[0-9]{8}$/', $mobile)) {
                echo '<span class="font-red">' . app::get('b2c')->_('手机号码格式有误，请输入以13/14/15/18开头的11位数字') . '</span>';
                exit;
            } else {
//                if(!$obj_member->is_exists($mobile)){
                if (!$obj_member->is_exists_mobile($mobile, $member_id)) {
                    echo '<span class="font-green">' . app::get('b2c')->_('可以使用') . '</span>';
                    exit;
                }
//                }

                echo '<span class="font-red">' . app::get('b2c')->_('该手机号已被使用，请更换号码') . '</span>';
                exit;
            }
        }
    }

    /*密码框失去焦点时提示  */
    public function pwdblur()
    {
        $pwd = trim($_POST['pwd']);

        if (strlen($pwd) > 0) {
            if (strlen($pwd) > 20 || strlen($pwd) < 6) {
                echo '<span class="font-red">' . app::get('b2c')->_('密码长度只能在6-20位字符之间。') . '</span>';
                exit;
            }
//            else{
            //                echo '<span>&nbsp;' . app::get('b2c')->_('') . '</span>';
            //                exit;
            //            }
        }
//        else{
        //            echo '<span>&nbsp;' . app::get('b2c')->_('') . '</span>';
        //            exit;
        //        }
    }

    /* 获取手机短信验证码  */
    public function getMobileCode()
    {
        $objmember  = &$this->app->model('members');
        $objaccount = &$this->app->model('member_account');
        $mobile     = trim($_POST['contact']['phone']['mobile']);

        if ($objmember->check_mobile($mobile, $message)) {
            //验证是否是恶意请求验证码
            $request_time = time();
            $isSpite      = $this->app->model('message_log')->isSpiteRequest($request_time, $mobile, $msg);
            if ($isSpite != 'ok') {
                if ($isSpite == 'spite') {
                    $message_log = $this->app->model('message_log')->saveMessageLog(__FUNCTION__, $request_time, $mobile, base_request::get_remote_addr(), 'spite', 'sms'); //恶意请求 记录日志
                }
                echo '<span class="error">' . $msg . '</span>';exit;
            }
            //end

            $random = $this->randCode();
//            setcookie('MOBILE_CODE', $random);
            $_SESSION['MOBILE_CODE'] = $random;

            //发送会员注册时的手机验证码
            $data['contact']['phone']['mobile'] = $mobile;
            $data['mobile_code']                = $random;
            $data['sendmobilecodetype']         = 'userRegister';
            $data['disabled_time']              = 2;

            $message_log = $this->app->model('message_log')->saveMessageLog(__FUNCTION__, $request_time, $mobile, base_request::get_remote_addr(), 'ok', 'sms'); //正常请求 记录日志

            $tmp_rs = $objaccount->fireEvent('sendmobilecode', $data);

            if ($tmp_rs) {
//                setcookie('MOBILE_CODE_TIMER', time());
                echo 1;exit;
            } else {
                echo '<span class="font-red">短信发送失败，请重新获取验证码</span>';exit;
            }
        } else {
            echo '<span class="font-red">' . $message . '</span>';exit;
        }
    }

    /* 随机生成码  */
    public function randCode($len = 6)
    {
        $chars = '0123456789';
        mt_srand((double) microtime() * 1000000 * getmypid());
        $code = '';
        while (strlen($code) < $len) {
            $code .= substr($chars, (mt_rand() % strlen($chars)), 1);
        }

        return $code;
    }

    /* 登录时，验证用户名(包括已验证邮箱和已验证手机)是否存在 */
    public function checkuname()
    {
        $login_name = trim($_POST['login_name']);
        if ($login_name != '') {
            $account_type    = pam_account::get_account_type($this->app->app_id);
            $obj_pam_account = new pam_account($account_type);
            $isExists        = $obj_pam_account->is_exists($login_name);
        }

        if (!$isExists) {
            //登陆时验证 外服用户名
            $db = kernel::single('base_db_oracle_connections');

            $bindPara  = array(":user_name" => $login_name);
            $sql       = "select * from uup.v_yoofuu_login where USER_NAME=:user_name or PHONE=:user_name or EMAIL=:user_name";
            $rows      = $db->bindSelect($sql, $bindPara);
            $loginInfo = $rows[0];
            if (empty($loginInfo)) {
                echo '<span class="font-red">' . app::get('b2c')->_('该用户名不存在') . '</span>';
                exit;
            }
        }
        echo 1;exit;
    }

    /* 登录时，如果是手机号码登录，则获取手机短信验证码 */
    public function getLoginMobileCode()
    {
        $objmember  = &$this->app->model('members');
        $objaccount = &$this->app->model('member_account');
        $mobile     = trim($_POST['contact']['phone']['mobile']);

        if ($objmember->check_login_mobile($mobile, $message)) {
            $random = $this->randCode();
//            setcookie('MOBILE_CODE', $random);
            $_SESSION['MOBILE_CODE'] = $random;

            //发送会员登录时的手机验证码
            $data['contact']['phone']['mobile'] = $mobile;
            $data['mobile_code']                = $random;
            $data['sendmobilecodetype']         = 'userRegister';
            $data['disabled_time']              = 2;

            $tmp_rs = $objaccount->fireEvent('sendmobilecode', $data);

            if ($tmp_rs) {
//                setcookie('MOBILE_CODE_TIMER', time());
                echo 1;exit;
            } else {
                echo '<span class="valierror">短信发送失败，请重新获取验证码</span>';exit;
            }
        } else {
            echo '<span class="valierror">' . $message . '</span>';exit;
        }
    }

    /* 获取找回密码时的手机验证码  */
    public function getRecoverMobileCode()
    {
        $objmember  = &$this->app->model('members');
        $objaccount = &$this->app->model('member_account');
        $mobile     = trim($_POST['contact']['phone']['mobile']);

        if ($objmember->check_login_mobile($mobile, $message)) {
            //验证是否是恶意请求验证码
            $request_time = time();
            $isSpite      = $this->app->model('message_log')->isSpiteRequest($request_time, $mobile, $msg);
            if ($isSpite != 'ok') {
                if ($isSpite == 'spite') {
                    $message_log = $this->app->model('message_log')->saveMessageLog(__FUNCTION__, $request_time, $mobile, base_request::get_remote_addr(), 'spite', 'sms'); //恶意请求 记录日志
                }
                echo '<span class="error">' . $msg . '</span>';exit;
            }
            //end

            $random = $this->randCode();
//            setcookie('MOBILE_CODE', $random);
            $_SESSION['MOBILE_CODE'] = $random;

            //发送会员登录时的手机验证码
            $data['contact']['phone']['mobile'] = $mobile;
            $data['mobile_code']                = $random;
            $data['sendmobilecodetype']         = 'userRegister';
            $data['disabled_time']              = 2;

            $message_log = $this->app->model('message_log')->saveMessageLog(__FUNCTION__, $request_time, $mobile, base_request::get_remote_addr(), 'ok', 'sms'); //正常请求 记录日志

            $tmp_rs = $objaccount->fireEvent('sendmobilecode', $data);

            if ($tmp_rs) {
//                setcookie('MOBILE_CODE_TIMER', time());
                echo 1;exit;
            } else {
                echo '<span class="valierror">短信发送失败，请重新获取验证码</span>';exit;
            }
        } else {
            echo '<span class="valierror">' . $message . '</span>';exit;
        }
    }

    /*
     * @method : randomName
     * @description : 用时间戳生成字符串
     * @params :
     *       $prefix : 生成的字符串前缀
     *       $length : 生成的字符串长度(不包含前缀长度)
     * @return : string(生成的随机字符串)
     * @author : zlj
     * @date : 2013-5-11 10:39:18
     */
    public function randomName($prefix = '', $length)
    {
        $time     = microtime();
        $arr_time = explode(' ', $time);
        $micro    = (double) $arr_time[0] * 1000000; //不重复

        if (strlen($arr_time[1]) < $length && $length <= 16) {
            $random_len = $length - strlen($arr_time[1]);
            $micro_str  = substr($micro, -$random_len);

            return $prefix . $arr_time[1] . $micro_str;
        } elseif (strlen($arr_time[1]) == $length) {
            return $prefix . $arr_time[1];
        } else {
            $chars = '0123456789';
            $code  = '';

            while (strlen($code) < $length) {
                $code .= substr($chars, (mt_rand() % strlen($chars)), 1);
            }

            return $prefix . $code;
        }
    }

    public function setShop()
    {

        $shop['url']['shipping']          = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'shipping'));
        $shop['url']['total']             = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'total'));
        $shop['url']['region']            = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_tools', 'act' => 'selRegion'));
        $shop['url']['payment']           = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'payment'));
        $shop['url']['purchase_shipping'] = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'purchase_shipping'));
        $shop['url']['purchase_def_addr'] = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'purchase_def_addr'));
        $shop['url']['purchase_payment']  = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'purchase_payment'));
        $shop['url']['get_default_info']  = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_cart', 'act' => 'get_default_info'));
        $shop['url']['diff']              = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_product', 'act' => 'diff'));
        $shop['base_url']                 = $url;
        $shop['url']['fav_url']           = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'ajax_fav'));
        return json_encode($shop);
    }

    public function sfsccheck()
    {
        $idcard       = trim($_POST['idcard']);
        $contact_name = trim($_POST['contact_name']);

        if (!empty($idcard) && !empty($contact_name)) {
            $_sjson = array(
                'NAME'   => $contact_name,
                'CARDID' => $idcard,
            );

            $post_data = array('serviceNo' => 'CheckNameAndId', "inputParam" => json_encode($_sjson));
            $tmpdata   = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);

            if ($tmpdata != null && gettype($tmpdata) == "object") {
                $tmp = SFSC_HttpClient::objectToArray($tmpdata);
            }
            if ($tmp['RESULT_CODE'] == '10001') {
                echo '<span class="font-green">' . app::get('b2c')->_('可以使用') . '</span>';
                exit;
            } else {
                if ($tmp['RESULT_CODE'] == "10000") {
                    echo '<span style="color:red;">' . $tmp['RESULT_MSG'] . '</span>';
                    exit;
                }
                echo '<span style="color:red;">' . app::get('b2c')->_('不可以使用') . '</span>';
                exit;
            }
        }

    }

    public function ebayhelp()
    {
        $this->pagedata['angel']         = kernel::base_url() . "/themes/simple/images/ebayimages/angel.png";
        $this->pagedata['range']         = kernel::base_url() . "/themes/simple/images/ebayimages/range.jpg";
        $this->pagedata['process']         = kernel::base_url() . "/themes/simple/images/ebayimages/process.jpg";
        $this->pagedata['round']         = kernel::base_url() . "/themes/simple/images/ebayimages/round.png";
        $this->display('site/passport/ebayhelp.html');
    }

    //登录界面
    public function elogin()
    {

        // $jsonData = $_POST['param'];
        $jsonData = $_REQUEST['param'];
        $jsonData =  $this->decrypt(str_replace(" ","+",$jsonData),"KkEeYvVMJ4x7CNtO");
        
        $res_json = json_decode($jsonData,true);
        $signres = $res_json['sign']['value'];

        $appId = $res_json['service']['appId'];
        $partnerId = $res_json['data']['partnerId'];
        $parterName = $res_json['data']['partnerName'];
        $backURL = $res_json['data']['backUrl'];

        $key = "KkEeYvVMJ4x7CNtO";
        $signst = "key=".$key."&partnerId=".$partnerId."&partnerName=".$parterName."&backURL=".$backURL;
        $sign = sha1($signst);

        // if ($sign != $signres) {
        //     // kernel::single('site_controller')->splash('success', "", app::get('b2c')->_('参数验证失败！'));
        //     echo "<script>alert('参数验证失败！')</script>";exit;
        // }

        // $appId = $_REQUEST['appId'];
        // $partnerId = $_REQUEST['partnerId'];
        // $parterName = $_REQUEST['parterName'];
        // $backURL = $_REQUEST['backURL'];

        if ($_SESSION['account']['member']) {
            $url = &app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
            kernel::single('site_controller')->splash('success', $url, app::get('b2c')->_('您已经是登录状态，不需要重新登录'));

        }
        $this->pagedata['logo_01']         = kernel::base_url() . "/themes/simple/images/ebayimages/logo_01.png";
        $this->pagedata['login_right_img'] = kernel::base_url() . "/themes/simple/images/ebayimages/login_right_img.png";
        $this->pagedata['url']             = kernel::base_url() . "/passport-ebayLogin.html?appId={$appId}&partnerId={$partnerId}&parterName={$parterName}&backURL={$backURL}";
        $this->pagedata['helpurl']         = kernel::base_url() . "/passport-ebayhelp.html";

        $loginurl = $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?appId={$appId}&partnerId={$partnerId}&parterName={$parterName}&backURL={$backURL}";
        $encodeurl = urlencode($loginurl);
        $this->pagedata['regurl']             = "https://cloudtest.fsgplus.com/wf_efescoCn/UserAction.do?method=fillRegisterFirstStep&backurl=http://".$encodeurl."&languageType=zh_CN";
        $this->pagedata['forgeturl'] = "https://cloudtest.fsgplus.com/wf_efescoCn/UserAction.do?method=forgetPwd&backurl=http://".$encodeurl."&languageType=zh_CN";
        
        $this->display('site/passport/ebaylogin.html');
    }

    public function tiao($en_data,$backURL)
    {
        $protocolName = substr($backURL,0,4);
        if('http' != strtolower($protocolName)){
            $backURL = 'https://' . $backURL;
        }
         setcookie("param", "", time() - 3600);
         echo "<!DOCTYPE html>
            <html>
                <head>
                    <meta http-equiv=\"content-type\" content=\"text/html;charset=utf-8\">
                    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\">
                    <title>跳转页面</title>
                </head>
                <body>
                    <form id='form1' name='form1' method='post' action='{$backURL}'>
                      <input name='param' type='hidden' value='{$en_data}' />
                    </form>
                    <script type='text/javascript'>function load_submit(){document.form1.submit()}load_submit();</script>
                </body>
            </html>";
    }

    //绑定操作// param={"data":{"appid":"yf16400c549e405","partnerId":"3960","parterName":"ebayseller","backURL": "http: //www.ebay.com.hk/","fkHumbasNo":"123456789","userName":"123456789@qq.com"}}
    public function bingact()
    {
        $param = $this->decrypt($_COOKIE['param'],'KkEeYvVMJ4x7CNtO');
        $data = json_decode($param,1);

        $fk_humbas_no   = $data['fk_humbas_no'];
        $ebay_humbas_no = $data['ebay_humbas_no'];
        $partnerid      = $data['partnerid'];
        $appid          = $data['appid'];
        $parterName     = $data['parterName'];
        $backURL        = str_replace("\\/", "/", $data['backURL']);
        $username       = $data['username'];

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            
            //绑定ebay跟外服
            $db = kernel::single('base_db_oracle_connections');
            $db->getYFDb();
            $sql = "select * from bindinguser where PARTNERID='{$partnerid}' and HUMBAS_NO !='{$ebay_humbas_no}'";
            $bindedList     = $db->select($sql);
            if(is_array($bindedList) && count($bindedList)){
                $this->splash('failed', '', app::get('b2c')->_('ebay账号"'.$parterName.'"已经被绑定过,不能多次绑定！'), '', '', true);
                return false;
            }

            $sql = "select * from bindinguser where HUMBAS_NO='".$ebay_humbas_no."'";
            $bindedList     = $db->select($sql);
            if($bindedList){
                $bindedItem = array_pop($bindedList);
            }
            // echo $password."<pre>";print_r($bindedItem);exit;
            $time = date("Y-m-d h:i",time());

            $key = 'KkEeYvVMJ4x7CNtO';
            $operationType = "1";
            $type = "00";
            if(empty($bindedItem)){
                //对账号进行绑定
                $sql      = "insert into bindinguser (HUMBAS_NO,PARTNERID,APPID,FK_HUMBAS_NO,BINDFLAG,ADD_TIME,UPDATE_TIME,BACKURL,PARTERNAME) values ('".$ebay_humbas_no."','".$partnerid."','".$appid."','".$fk_humbas_no."','1','".$time."','".$time."','{$backURL}','{$parterName}')";
                $rs = $db->exec($sql);
                //$this->binDing($key ,$appid , $partnerid, $ebay_humbas_no,$operationType,$backURL,$type);
            }else if ('1' != $bindedItem['BINDFLAG']) {
                $bindPara = array(":humbas_no"=>$ebay_humbas_no,":partnerid"=>$partnerid,":appid"=>$appid,":fk_humbas_no"=>$fk_humbas_no);
                $sql = "update bindinguser set HUMBAS_NO=:humbas_no,PARTNERID=:partnerid,APPID=:appid,FK_HUMBAS_NO=:fk_humbas_no,BINDFLAG=1,ADD_TIME='".$time."',UPDATE_TIME='".$time."',BACKURL='{$backURL}',PARTERNAME='{$parterName}' where HUMBAS_NO=".$ebay_humbas_no;
                $db->bindExec($sql,$bindPara);

                //$this->binDing($key ,$appid , $partnerid, $ebay_humbas_no,$operationType,$backURL,$type);
            }

            // $url            = kernel::base_url() . "/passport-elogin.html?appId={$appid}&partnerId={$partnerid}&parterName={$parterName}&backURL={$backURL}";
            // $this->splash('success', $url, app::get('b2c')->_('绑定成功，正在跳转登录页！'), '', '', true);

            //绑定成功跳转ebay给定backURL
            if(is_numeric($username)){
                $maskUsername = substr($username , 0 , 3) . str_repeat("*" , 5) . substr($username , -3);
            }else{
                $maskUsername = substr($username , 0 , 5) . str_repeat("*" , strlen($username)-8) . substr($username , -3);
            }

            $backData = array(
                'appid'=>$appid,
                'partnerId'=>$partnerid,
                'partnerName'=>$parterName,
                'backURL'=>$backURL,
                'fkHumbasNo'=>$ebay_humbas_no,
                'userName'=>$maskUsername,
            );
            $json_param = str_replace("\\/", "/", json_encode(array("data"=>$backData)));
            $en_data = $this->encrypt($json_param,"KkEeYvVMJ4x7CNtO");
            $this->splash('success', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'tiao','arg0'=>urlencode($en_data),'arg1'=>urlencode($backURL))), app::get('b2c')->_('绑定成功，正在跳转页面！'), '', '', true);

        }else{
            $this->pagedata['logo_01']         = kernel::base_url() . "/themes/simple/images/ebayimages/logo_01.png";
            $this->pagedata['i']         = kernel::base_url() . "/themes/simple/images/ebayimages/i.png";
            $this->pagedata['url'] = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'bingact'));
            $this->pagedata['fk_humbas_no'] = $fk_humbas_no;
            $this->pagedata['ebay_humbas_no'] = $ebay_humbas_no;
            $this->pagedata['parterName'] = $parterName;
            $this->pagedata['username'] = $username;
            $this->display('site/passport/bingact.html');
        }
        
    }

    //lpc ebay登录
    public function ebayLogin()
    {
        if ($_SESSION['account']['member']) {
            $url = &app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
            kernel::single('site_controller')->splash('success', $url, app::get('b2c')->_('您已经是登录状态，不需要重新登录'));
        }
        if(!base_vcode::verify('EBVERIFYCODE',intval($_POST['verifyCode']))){
             $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'elogin')), app::get('b2c')->_('验证码错误！'), '', '', true);
        }

        $name      = trim($_POST['name']); 
        $pass      = trim($_POST['password']);
        $password  = pam_encrypt::get_encrypted_password($pass, pam_account::get_account_type($this->app->app_id));

        //获取该库的人才号
        $db = kernel::single('base_db_oracle_connections');
        $bindPara          = array(":user_name" => $name);
        $sql = "select * from uup.v_yoofuu_login where (USER_NAME=:user_name or (PHONE=:user_name and MB_LOGIN_STATUS=1) or (EMAIL=:user_name and MAIL_LOGIN_STATUS=1)) and DELETED = 'N'";
        $yoofuu_login_data = $db->bindSelect($sql, $bindPara);//echo $password."<pre>";print_r($yoofuu_login_data);exit;
        if ($yoofuu_login_data[0]['PWD'] != $password) {
            $_SESSION['login_msg'] = app::get('b2c')->_('云平台:用户名或密码错误');
            $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'elogin')), app::get('b2c')->_('用户名或密码错误'), '', '', true);
        }

        $fk_humbas_no = $yoofuu_login_data[0]['HUMBAS'];//外服人才号
        $ebay_humbas_no = 'ebay_' . $fk_humbas_no;
        $partnerid = $_REQUEST['partnerId'];
        $appid = $_REQUEST['appId']; 
        $parterName = $_REQUEST['parterName'];
        $backURL = $_REQUEST['backURL'];
        $company_no = 'CH28101';//ebay商社号
        //切换数据库，登录分离库
        $db->getYFDb();
        //检查雇员是否存在，查分离库
        $time = date("Y-m-d h:i",time());
        $bindPara = array(":humbas_no" => $ebay_humbas_no);
        //如果有则查询 RDP oracle库是否有雇员信息
        $sql = "SELECT HUMBAS_NO FROM S_IMS_EMPLOYEE WHERE HUMBAS_NO = '{$ebay_humbas_no}'";
        $employee     = $db->bindSelect($sql, $bindPara);
        // $sql = "SELECT * FROM INTERFACELOG";
        // $employee     = $db->bindSelect($sql, $bindPara);
        // echo $ebay_humbas_no."<pre>";print_r($employee);exit;
        if (!$employee) {
            //如果有则不做操作，没有则插入雇员信息
            reset($bindPara);
            $sql = "INSERT INTO  S_IMS_EMPLOYEE (HUMBAS_NO,STATUS,COMPANY_NO,IS_DELETE,ADD_USER,ADD_TIME,UPDATE_USER,UPDATE_TIME) VALUES('{$ebay_humbas_no}','I01401','{$company_no}','0','SYSTEM','{$time}','SYSTEM','{$time}')";
            $employee = $db->bindExec($sql, $bindPara);
        }

        //绑定ebay跟外服
        $sql = "select * from bindinguser where HUMBAS_NO='".$ebay_humbas_no."'";
        $bindedList     = $db->select($sql);
        if($bindedList){
            $bindedItem = array_pop($bindedList);
        }
// echo $ebay_humbas_no."<pre>";print_r($bindedList);exit;

        if(empty($bindedItem) || '1' != $bindedItem['BINDFLAG']){

            //云平台同步api
            $keyStr = 'fsg_yoofu;nyz-#&';  
            $post_arr = array(
                'humbasNo'  => $fk_humbas_no,
                'appId'     => $appid, 
                'outUserId' => $partnerid,
            );
            $plainText = json_encode($post_arr);
            $encText = $this->encrypt($plainText,$keyStr);
            // $result['status'] = '1';
            $result = $this->doPost($encText,$plainText);
            if('1' != $result['status']){
                $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'elogin')), app::get('b2c')->_('用户数据丢失，请从指定链接登录！'), '', '', true);
            }


            $param = array(
                    'partnerid'=>$partnerid,
                    'appid'=>$appid,
                    'parterName'=>$parterName,
                    'backURL'=>$backURL,
                    'fk_humbas_no'=>$fk_humbas_no,
                    'ebay_humbas_no'=>$ebay_humbas_no,
                    'username'=>$name,
            );
            setcookie('param',$this->encrypt(json_encode($param) ,"KkEeYvVMJ4x7CNtO"),0,'/');

            $this->splash('success', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'bingact')), app::get('b2c')->_('正在跳转绑定页面！'), '', '', true);

        }

        
        //登录
        //模拟post 数据
        
        $result = kernel::single("b2c_sfsc_loginbyoracle")->sfsc_register($ebay_humbas_no,$pass,$fk_humbas_no."@youfu.com");
        if(!$result){
            $this->splash('failed', $url, app::get('b2c')->_('注册ebay数据失败！'), '', '', true);
        }

        $sql="select `account_id` from sdb_pam_account where `login_name` = '".$ebay_humbas_no."'";
        $data_pam_account=kernel::database()->select($sql);
        $member_id = trim($data_pam_account[0]['account_id']);

        setcookie('login_member_type','buyers',0,'/');
        setcookie('S[MEMBER]',$member_id,0,'/');
        setcookie('UNAME',$name,0 , '/');

        $_SESSION['account']['member'] = $member_id;
        $_SESSION['login_time'] = time();
        $_SESSION['type'] = "member";
        $url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member', 'act' => 'index'));
        $this->splash('success', $url, app::get('b2c')->_('登录成功'), '', '', true);

    }

    public function doPost($methodParams,$plainText)
    {
        $wsdlUrl = 'https://cloudtest.fsgplus.com/wf_efescoCn/services/YoufuWebService?WSDL';
        $client  = new SoapClient($wsdlUrl);
        $res_json = $client->syncYoufuData($methodParams);
        $res      = json_decode($res_json, true);
        // echo "<pre>";print_r(json_encode($res,1));exit;
        // 插日志
        $this->ebaylog("php_syncYoufuData",$plainText,$res_json);
        return $res;

    } 


    public  function encrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }
   
    private  function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
   
    public  function decrypt($sStr, $sKey) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
        );
  
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    } 

    //lpc 绑定通知java
    function binDing($key ,$appid , $partnerid, $ebay_humbas_no,$operationType,$backURL,$type)
    {

        $signst = "key=".$key."&partnerId=".$partnerid."&humbas_no=".$ebay_humbas_no."&operationType=".$operationType."&backURL=".$backURL;
        $sign = sha1($signst);//return $signst.'<br>'.$sign;
        $param = array(
            'service' => array(
                'appId'=>$appid,
            ),
            'data'=>array(
                'partnerId'=>$partnerid,
                'humbas_no'=>$ebay_humbas_no,
                'operationType'=>$operationType,
                'backURL'=>$backURL,
            ),
            'sign'=>array(
                'type'=>$type,
                'value'=>$sign,
            ),
        );
        $logdata  = json_encode($param);
        $param = 'param='.json_encode($param);

        $url = "http://172.16.197.67:8080/wfqzj/binding.do";
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        // curl_setopt($ch, CURLOPT_VERBOSE , 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($ch);//echo curl_getinfo($ch,CURLINFO_HTTP_CODE);//运行curl
        curl_close($ch);

        //插日志
        $this->ebaylog("php_binding",$logdata,$data);

        return $data;
    }

    //lpc ebay调用接口日志  //方法名，请求参数，返回值
    public function ebaylog($interface_name,$request,$response)
    {
        $db = kernel::single('base_db_oracle_connections');
        $db->getYFDb();
        // $addtime = date("j-n月 -y",time());//22-3月 -18
//        $bindPara = array(":interface_name" => $interface_name,":request" => $request,":response" => $response,":add_time" => $addtime);
//        reset($bindPara);
        $sql = "INSERT INTO  INTERFACELOG (ID,INTERFACE_NAME,REQUEST,RESPONSE,ADD_TIME) VALUES (seq_INTERFACELOG.Nextval,'{$interface_name}','{$request}','{$response}',sysdate)";
        $result = $db->exec($sql);
        return $result;
    }

    //LPC ebay登录验证码
    public function eBayverifyCode()
    {
        $vcode = kernel::single('base_vcode');
        $vcode->length(4);
        $vcode->verify_key('EBVERIFYCODE');
        $vcode->display();
    } 

}


