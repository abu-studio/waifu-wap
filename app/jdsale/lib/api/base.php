<?php

/**
 * 实现京东API调用的基础类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/9
 * Time: 17:38
 */
class jdsale_api_base {

    //京东业务数据api接口url
    public  $url = "https://bizapi.jd.com/api/";
    //京东开普勒获取token的url
    public  $token_url = "https://bizapi.jd.com/oauth2/access_token";
    public  $token_refresh_url = "https://bizapi.jd.com/oauth2/refreshToken";
    //京东开普勒获取的token
    public  $access_token = '';

    //测试临时使用
    public $log_file= 'd:/aaa.log';

    //部分api不保存返回的result数据
    public $filter_api = array (
                        'product/getDetail',
                        'area/getProvince',
                        'area/getCity',
                        'area/getCounty',
                        'area/getTown',
                      );
    /**
     * app object
     */
    public $app;

    public function __construct($app){
        $this->log_file = ROOT_DIR . '/router_jd_com.txt';
        $this->app = $app;
        # normal : 京东优选频道
        # book   : 京东图书频道
        $this->kindwhitelist = array('normal' , 'book');
    }

    private function getAppkey($jdgoodsKind){
        if(! in_array($jdgoodsKind , $this->kindwhitelist)){
            trigger_error('jdgoods kind error' , E_USER_ERROR);
        }
        //临时返回
        $className = 'jdsale_base_'.$jdgoodsKind;
        $app_key = kernel::single($className)->getAppkey();

        return $app_key;
    }

    private function getAppSecret($jdgoodsKind){
        if(! in_array($jdgoodsKind , $this->kindwhitelist)){
            trigger_error('jdgoods kind error' , E_USER_ERROR);
        }
        //临时返回
        $className = 'jdsale_base_'.$jdgoodsKind;
        $app_secret = kernel::single($className)->getAppSecret();

        return $app_secret;
    }

    private function getUserName($jdgoodsKind){
        if(! in_array($jdgoodsKind , $this->kindwhitelist)){
            trigger_error('jdgoods kind error' , E_USER_ERROR);
        }
        //临时返回
        $className = 'jdsale_base_'.$jdgoodsKind;
        $username = kernel::single($className)->getUserName();

        return $username;
    }

    private function getPassword($jdgoodsKind){
        if(! in_array($jdgoodsKind , $this->kindwhitelist)){
            trigger_error('jdgoods kind error' , E_USER_ERROR);
        }
        //临时返回
        $className = 'jdsale_base_'.$jdgoodsKind;
        $password = kernel::single($className)->getPassword();

        return $password;
    }

    private function getSign($client_id,$client_secret,$timestamp,$username,$password,$grant_type,$scope){
        // client_secret + timestamp + client_id + username + password + grant_type + scope + client_secret

        $param = '';
        $param .= $client_secret;
        $param .= $timestamp;
        $param .= $client_id;
        $param .= $username;
        $param .= $password;
        $param .= $grant_type;
        $param .= $scope;
        $param .= $client_secret;

        $sign =  strtoupper(md5($param));
        return $sign;
    }

    private function doPost($url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,9);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        $output = curl_exec($ch);
        curl_close($ch);
        if($curl_errno >0){
            return false;
        }

        $rs = json_decode($output,true);

        return $rs;
    }

    /**
     * @param $method 京东api的调用方法名
     * @param $api_function 京东api的功能名称
     * @param $params 京东api需传入的业务参数
     * @param $msg 返回响应信息和出错信息
     * @return array 返回业务数据对应
     */
     public function getBizData($suffixUri,$api_function,$params,$jdgoodsKind='normal'){
         $paramsArr = array();
         if ($params != null || $params != ''){
             foreach ($params as $key => $val){
                 if(is_array($val)){
                     $paramsArr[$key] = json_encode($val);
                 }else{
                     $paramsArr[$key] = $val;
                 }
             }
         }

         $api_params = array(
//             'app_key' => $this->getAppkey($jdgoodsKind),
             'token' => $this->getToken($jdgoodsKind),
             'timestamp'=>date('Y-m-d H:i:s'),
         );
         $api_params = array_merge($api_params , $paramsArr);
         $result = array();
         $url = $this->url . $suffixUri;
         $data = $this->doPost($url,$api_params);

         if (!$data){
             $msg['code'] = app::get('b2c')->_('未知错误');
             $msg['resultCode'] = app::get('b2c')->_('未知错误');
             $msg['resultMessage']= app::get('b2c')->_('调用api失败');
             $msg['success'] = false;
             $this->saveCallResult($suffixUri,$api_function,$params,$msg,array(),$jdgoodsKind);
             $this->logResult($suffixUri,$api_function,$params,$msg);
             return $result;
         }

         //在调用api时，如果api返回的code码是1004 ，1003，2007等说明token过期,需刷新token
         if ($data['resultCode'] == '1004' ||$data['resultCode'] == '1003' ||
             $data['resultCode'] == '2007'){

             $api_params['token'] = $this->refreshToken($jdgoodsKind);
             if(empty($api_params['token'])){
                 return $result;
             }
             //再次调用一次api请求
             $data = $this->doPost($this->url,$api_params);
         }
         $result = $data;

         if ($data['success']){
              $msg['code'] =  $data['resultCode'];
              $msg['resultCode'] = $data['resultCode'];
              $msg['resultMessage'] = $data['resultMessage'];
              $msg['success'] = $data['success'];
         }

         if(in_array($suffixUri,$this->filter_api)){
             $this->saveCallResult($suffixUri,$api_function,$params,$msg,array(),$jdgoodsKind);
         }else{
             $this->saveCallResult($suffixUri,$api_function,$params,$msg,$result,$jdgoodsKind);
         }
         $this->logResult($suffixUri,$api_function,$params,$msg,$data);

         return $result ;
    }

    private function getToken($jdgoodsKind){
        $tokenKey = 'jdapi.token.time.'.$jdgoodsKind;
        $tokenConf = app::get('jdsale')->getConf($tokenKey);
        if ($tokenConf){
            $timeDiff = abs(time() - $tokenConf['time']);
            $dayDiff = $timeDiff / 3600 / 24;
            //    每28-29天自动获取token，其有效期将恢复30天
            if ($dayDiff>28 || empty($tokenConf['token'])){
                if(empty($tokenConf['refresh_token'])){
                    $token = $this->_getJDToken('access_token',$jdgoodsKind);
                }else{
                    $token = $this->_getJDToken('refresh_token',$jdgoodsKind);
                }

                $this->access_token = $token[0];
                if(empty($this->access_token)){
                    return false;
                }

                app::get('jdsale')->setConf($tokenKey , array(
                    'token' => $this->access_token,
                    'refresh_token' => $token[1],
                    'time'  => time()
                ),false);
            }else{
                $this->access_token =$tokenConf['token'];
            }
        }else{
            //第一次获取token，并保存至本地数据库
            $token = $this->_getJDToken('access_token',$jdgoodsKind);
            $this->access_token = $token[0];
            if(empty($this->access_token)){
                return false;
            }
            app::get('jdsale')->setConf($tokenKey , array(
                'token' => $this->access_token,
                'refresh_token' => $token[1],
                'time'  => time()
            ),false);
        }

        return $this->access_token;
    }

    private function refreshToken($jdgoodsKind){
        $tokenKey = 'jdapi.token.time.'.$jdgoodsKind;
        $token = $this->_getJDToken('refresh_token',$jdgoodsKind);
        $refresh_token = $token[0];
        if(empty($refresh_token)){
            return false;
        }

        app::get('jdsale')->setConf($tokenKey , array(
            'token' => $refresh_token,
            'refresh_token' => $token[1],
            'time'  => time()
        ),false);
        $this->access_token = $refresh_token;
        return $refresh_token;
    }

    private function _getJDToken($grant_type,$jdgoodsKind){
        $lockKey = 'access_token_lock';
        $lockTTL = 50;
        $kvObj = base_kvstore::instance('jdapikvstore');
        if($kvObj->increment($lockKey) > 1){
            return false;
        }
        $kvObj->expire($lockKey, $lockTTL);

        //开发测试时候固定值
//        return '099d109f8f1f49cf98e9c482ca3400c38';
        $client_id = $this->getAppkey($jdgoodsKind);
        $client_secret = $this->getAppSecret($jdgoodsKind);
        $timestamp = date('Y-m-d H:i:s');
        $username = $this->getUserName($jdgoodsKind);
        $password = $this->getPassword($jdgoodsKind);
        $scope = '';

        $tokenKey = 'jdapi.token.time.'.$jdgoodsKind;
        $tokenConf = app::get('jdsale')->getConf($tokenKey);
        if('access_token' == $grant_type || empty($tokenConf['refresh_token'])){
            $queryURL = $this->token_url ;
            $grant_type = 'access_token';
            $params = array(
                'grant_type' => $grant_type ,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'timestamp' => $timestamp,
                'scope' => $scope,
                'username' => $username,
                'password' => $password,
                'sign' => $this->getSign($client_id,$client_secret,$timestamp,$username,$password,$grant_type,$scope)
            );
        }else{
            $params = array(
                'refresh_token' => $tokenConf['refresh_token'],
                'client_id' => $client_id,
                'client_secret' => $client_secret
            );
            $queryURL = $this->token_refresh_url;
        }

        $data = $this->doPost($queryURL , $params);
        if($data && $data['success']){
            return array($data['result']['access_token'] ,$data['result']['refresh_token']);
        }

        return false;
    }

    public function logResult($method,$api_function,$params,$msg,$result=null){
        error_log($method." ".$api_function." ".date('Y-m-d H:i:s')."\r\n",3,$this->log_file);
        error_log("调用入口参数：".var_export($params,true),3,$this->log_file);
        error_log("\r\n",3,$this->log_file);
        error_log("返回消息：".var_export($msg,true),3,$this->log_file);
        error_log("\r\n",3,$this->log_file);
        error_log("返回业务数据：".var_export($result,true),3,$this->log_file);
        error_log("\r\n",3,$this->log_file);
    }

    //保存调用api记录和返回结果
    private function saveCallResult($method,$api_function,$params,$msg,$result=array(),$jdgoodsKind="normal"){
        if('normal' == $jdgoodsKind){
            $jdgoodsKind='jdgoods';
        }else{
            $jdgoodsKind='jdbook';
        }
        $log = array(
            'api_method' => $method,
            'api_function' => $api_function,
            'createtime' => time(),
            'code' => $msg['code'],
            'result_code' => $msg['resultCode'],
            'result_message' => $msg['resultMessage'],
            'api_kind'=>$jdgoodsKind,
        );
        if ($msg['success']){
            $log['success'] = true;
        }
        if (!empty($params)){
            $log['params'] = $params;
        }
        if (!empty($result)){
            $log['result'] = $result['result'];
        }

        $mdl_api_log = app::get('jdsale')->model('api_log');
        $ret = $mdl_api_log->insert($log);
        return;
    }

}
