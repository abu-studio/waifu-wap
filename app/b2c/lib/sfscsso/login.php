<?php
	class b2c_sfscsso_login{

        public $app;
        private $verify_array=array('direct','method','date','HUMBAS_NO');
		function __construct($app){
            set_error_handler(array('cardcoupons_sfscorder_create', 'error_handler'));
            $this->app = $app;
            header("cache-control: no-store, no-cache, must-revalidate");
            header("Content-type:text/html;charset=utf-8");
        }

        static function error_handler($errno, $errstr, $errfile, $errline ){
            switch ($errno) {
                case E_ERROR:
                case E_USER_ERROR:
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                    break;

                case E_STRICT:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                default:
                    break;
            }
        }

        /**
         * send_user_succ-返回成功信息
         *
         * @param      $result
         * @return     json数组
         */
        private function send_user_succ($result){
            $result_json = array(
                'rsp'=>'succ',
                'data'=>$result,
            );
            echo $_GET['callback']."(".json_encode($result_json).")";
            exit;
        }

        /**
         * send_user_error-返回失败信息
         *
         * @param      $code,$data
         * @return     json数组
         */
        private function send_user_error($code, $data){
            $res = array(
                'rsp'   =>  'fail',
                'res'   =>  $code,
                'data'  =>  $data,
            );
            echo $_GET['callback']."(".json_encode($res).")";
            exit;
        }

        private function get_sign($params){
            return strtoupper(md5(strtoupper(md5($params))));
        }

        private function assemble($params){
            if(!is_array($params))  return null;
            ksort($params,SORT_STRING);
            $sign = '';
            foreach($params AS $key=>$val){
                if(in_array($key,$this->verify_array)){
                    if(is_null($val))   continue;
                    if(is_bool($val))   $val = ($val) ? 1 : 0;
                    $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
                }
            }
            return $sign;
        }

        private function verify($params) {
            $sign = "";
            $last_sign = "";
            $sign = $this->assemble($params);
            if($sign){
                $last_sign = self::get_sign($sign);
                if($last_sign == $params['sign']){
                    return true;
                }
            }
            return false;
        }


		public function login(){
            if(!$this->verify($_POST)){
                $this->send_user_error("4301","签名认证失败！");
            }
            $HUMBAS_NO = trim($_POST['HUMBAS_NO']);
            if(!$HUMBAS_NO){
                $this->send_user_error("4302",app::get('b2c')->_('参数无效！'));
            }

            $auth = pam_auth::instance('b2c');
            $auth->type='member';
            $auth->appid='b2c';
            $msg = array();
            //模拟post 数据
            $_POST['uname'] = $HUMBAS_NO;
            $_POST['password'] = "123456";

            $pam_passport_object = kernel::single('pam_passport_basic');
            $login_flag = $pam_passport_object->ssoSfscLogin($auth,$msg);
            if($login_flag){

                $url1 = base64_encode("/index.php/member.html");
                $url2 = base64_encode("/index.php/passport-postSfscLogin-".$url1.".html");
                //$url = "http://127.0.0.1/ecstore/index.php/openapi/pam_callback/ssoSfscLogin/module/pam_passport_basic/type/member/appid/b2c/redirect/".$url2;
                $params = array(
                    "uname"=>$_POST['uname'],
                    "password"=>$_POST['password'],
                    "appid"=>"b2c",
                    "type"=>"member",
                    "module"=>"pam_passport_basic",
                    "redirect"=>$url2
                );
                kernel::single("pam_callback")->ssoSfscLogin($params);
                //$this->send_user_succ(array('member_id'=>$login_flag,'url'=>$url,'uname'=>$_GET['HUMBAS_NO'],'password'=>rand(100000,999999)));

            }else{
                header('Location:' . app::get('site')->router()->gen_url(array('app'=>'b2c','ctl'=>'site_passport','act'=>'index')));
                exit;
                //$this->send_user_error("4303",$msg['log_data']);
            }

		}
    
    }
?>