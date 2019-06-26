<?php

/**
 * 福员外业务接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/18
 * Time: 11:44
 */
class b2c_fuyuanwai_api_biz extends b2c_fuyuanwai_api_base{
    public $app;
    //private $verify_array=array('direct','method','date','HUMBAS_NO');
    function __construct($app){
        parent::__construct();
        $this->mdl_log_api = app::get('b2c')->model('member_fyw_log_api');
        $this->fuyuanwai_orders = kernel::single('b2c_fuyuanwai_orders');

        set_error_handler(array('b2c_fuyuanwai_api_biz', 'error_handler'));
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
     * 提供api入口
     *
     */
    function api(){

        //$this->log_rsa($GLOBALS['HTTP_RAW_POST_DATA']);
        $log_api = array('post_data'=>$GLOBALS['HTTP_RAW_POST_DATA'],
                        'createtime'=>time());
        $log_id = $this->mdl_log_api->insert($log_api);
        $log_api_filter = array('log_id'=>$log_id);

        $inMerId = substr($GLOBALS['HTTP_RAW_POST_DATA'],8,15);

        $serverCode = substr($GLOBALS['HTTP_RAW_POST_DATA'],23,8);

        if($inMerId !== $this->merId){
            $errMsg = app::get('b2c')->_('商户号错误');
            $this->mdl_log_api->update(array('failure_log'=>$errMsg),$log_api_filter);
            $outMessage = $this->send_user_error('0100',$errMsg);
            $this->log_result($log_id,$outMessage,false,'0100',$errMsg,'商户号错误');
            $result = $this->getResponse($outMessage,$serverCode);
            //$this->log_rsa($outMessage);
            echo $result;
            exit;
        }

        if(empty($serverCode)){
            $errMsg = app::get('b2c')->_('交易码错误');
            $this->mdl_log_api->update(array('failure_log'=>$errMsg),$log_api_filter);
            $outMessage = $this->send_user_error('0101',$errMsg);
            $this->log_result($log_id,$outMessage,false,'0101',$errMsg,'无交易码');
            $result = $this->getResponse($outMessage,$serverCode);
            //$this->log_rsa($outMessage);
            echo $result;
            exit;
        }else{
            $this->mdl_log_api->update(array('api_method'=>$serverCode),$log_api_filter);
        }

        $signature = substr($GLOBALS['HTTP_RAW_POST_DATA'],35,344);

        $inMessage = substr($GLOBALS['HTTP_RAW_POST_DATA'],379);
        //$this->log_rsa($inMessage);

        $reqText = $this->decryptInMessage($inMessage);

        //if (empty($signature) || !$this->verifySign($reqText,$signature)){
        //    $errMsg = app::get('b2c')->_('验签失败');
        //    $this->mdl_log_api->update(array('failure_log'=>$errMsg),$log_api_filter);
        //    $outMessage = $this->send_user_error('0102',$errMsg);
        //    $this->log_result($log_id,$outMessage,false,'0102',$errMsg,'错误签名');
        //    $result = $this->getResponse($outMessage,$serverCode);
        //    echo $result;
        //    exit;
        //}

        if(empty($reqText)){
            $errMsg = app::get('b2c')->_('参数错误');
            $this->mdl_log_api->update(array('failure_log'=>$errMsg),$log_api_filter);
            $outMessage = $this->send_user_error('1001',$errMsg);
            $this->log_result($log_id,$outMessage,false,'1001',$errMsg,'无法解析数据');
            $result = $this->getResponse($outMessage,$serverCode);
            //$this->log_rsa($outMessage);
        }else{
            $params = str_replace(","," ",$reqText);
            $this->mdl_log_api->update(array('params'=>$params),$log_api_filter);

            //return $this->send_user_succ($GLOBALS['HTTP_RAW_POST_DATA']);
            if( $serverCode === 'TIRD0002'){
                $result = $this->TIRD0002($reqText,$log_id);
            }else if ($serverCode === 'TIRD0003'){
                $result = $this->TIRD0003($reqText,$log_id);
            }else if ($serverCode === 'TIRD0004'){
                $result = $this->TIRD0004($reqText,$log_id);
            }else if ($serverCode === 'TIRD0005'){
                $result = $this->TIRD0005($reqText,$log_id);
            }else{
                $msg  = app::get('b2c')->_('交易码错误');
                $outMessage = $this->send_user_error('0101',$msg);
                $this->log_result($log_id,$outMessage,false,'0101',$msg,'交易码错误');
                $result = $this->getResponse($outMessage,$serverCode);
                //$this->log_rsa($outMessage);

            }
            $this->mdl_log_api->update(array('return_data'=>$result),$log_api_filter);
        }

        echo $result;
        exit;
    }

    /**
     * 获取个人积分余额（福点余额）
     */
    private function TIRD0002($reqText,$log_id){
        //$this->log_rsa('TIRD0002 '.$reqText);
        //$this->verify("");

        $inMessage = json_decode($reqText,true);
        $member_no =$inMessage['memberNo'];

        //业务处理
        if(!empty($member_no) && $this->checkMember($inMessage['memberNo'])){
            $balance = $this->getBalance($member_no);
            $outMessage= $this->send_user_succ(array('memberNo'=>$member_no,
                                                     'pointbalance'=>$balance));
            $this->log_result($log_id,$outMessage,true);
        }else{
            $msg = app::get('b2c')->_('参数错误');
            $outMessage = $this->send_user_error('1001',$msg);
            $this->log_result($log_id,$outMessage,false,'1001',$msg,'业务参数错误');
        }


        $result = $this->getResponse($outMessage,'TIRD0002');
        return $result;
    }

    /**
     * 个人积分扣减
     * @param $reqText
     */
    private function TIRD0003($reqText,$log_id){
        //$this->log_rsa('TIRD0003 '.$reqText);

        $inMessage = json_decode($reqText,true);
        //$this->log_rsa(var_export($inMessage,1));

        if (empty($inMessage['memberNo']) || !($this->checkMember($inMessage['memberNo']))){
            $msg = app::get('b2c')->_('参数错误');
            $outMessage=$this->send_user_error('1001',$msg);
            $this->log_result($log_id,$outMessage,false,'1001',$msg,'业务参数错误');
        }else{
            $order_result = $this->fuyuanwai_orders->create_order($inMessage);
            if ($order_result['success'] == '1'){
                $outMessage=$this->send_user_succ($order_result['result']);
                $this->log_result($log_id,$outMessage,true);
            }else{
                $msg = app::get('b2c')->_('参数错误');
                $outMessage=$this->send_user_error($order_result['code'],$msg);
                $this->log_result($log_id,$outMessage,false,$order_result['code'],$msg,$order_result['msg']);
            }
        }

        $result = $this->getResponse($outMessage,'TIRD0003');
        return $result;
    }

    /**
     * 单笔交易查询
     * @param $reqText
     */
    private function TIRD0004($reqText,$log_id){
        //$this->log_rsa('TIRD0004 '.$reqText);

        $inMessage = json_decode($reqText,true);
        $this->log_rsa(var_export($inMessage,1));
        $mdl_orders = app::get('b2c')->model('orders_fyw');
        if (empty($inMessage['tradeNo']) && empty($inMessage['outTradeNo'])){
            $msg = app::get('b2c')->_('参数错误');
            $outMessage = $this->send_user_error('1001',$msg);
            $this->log_result($log_id,$outMessage,false,'1001',$msg,'业务参数错误');
        }else{
            if (!empty($inMessage['tradeNo'])){
                $filter =array('order_id'=> $inMessage['tradeNo']);
            }else{
                $filter =array('outTradeNo'=>$inMessage['outTradeNo']);
            }
            //$this->log_rsa(var_export($filter,1));
            $order_data = $mdl_orders->getRow('tradeStatus,order_id',$filter);
            //$this->log_rsa(var_export($order_data,1));
            if ($order_data){
                if($order_data['tradeStatus'] == 'FAIL'){
                    $order_result['tradeStatus']='FAIL';
                }else{
                    $order_result['tradeStatus']=$order_data['tradeStatus'];
                    $order_result['tradeNo']=$order_data['order_id'];
                }
                $outMessage=$this->send_user_succ($order_result);
                $this->log_result($log_id,$outMessage,true);
            }else{
                $msg = app::get('b2c')->_('参数错误');
                $outMessage = $this->send_user_error('1001',$msg);
                $this->log_result($log_id,$outMessage,false,'1001',$msg,'业务参数错误');
            }

        }

        $result = $this->getResponse($outMessage,'TIRD0004');
        return $result;
    }


    /**
     * 订单冲正/退款
     * @param $reqText
     */
    private function TIRD0005($reqText,$log_id){

        $inMessage = json_decode($reqText,true);
        //$this->log_rsa('TIRD0005',($inMessage));

        if (empty($inMessage['oriTradeNo']) && empty($inMessage['outTradeNo'])){
            $msg        = app::get('b2c')->_('参数错误');
            $outMessage = $this->send_user_error('1001', $msg);
            $this->log_result($log_id, $outMessage, false, '1001', $msg, '业务参数错误');
        }else{
            $order_result = $this->fuyuanwai_orders->create_refund($inMessage);
            //$this->log_rsa(var_export($order_result,1));
            if ($order_result['success'] == '1'){
                $outMessage=$this->send_user_succ($order_result['result']);
                $this->log_result($log_id,$outMessage,true);
            }else{
                $msg = app::get('b2c')->_('参数错误');
                $outMessage=$this->send_user_succ($order_result['result'],$order_result['code'],$msg);
                $this->log_result($log_id,$outMessage,false,$order_result['code'],$msg,$order_result['msg']);
            }
        }

        $result = $this->getResponse($outMessage,'TIRD0003');
        return $result;
    }

    /**
     *
     * 接口日志
     * @param        $log_id
     * @param        $outMessage
     * @param        $success
     * @param string $code
     * @param string $msg
     * @param string $failure_log
     */
    private function log_result($log_id,$outMessage,$success,$code='0000',$msg='操作成功',$failure_log=''){
        $result = str_replace(","," ",$outMessage);

        $log_api_filter = array('log_id'=>$log_id);
        $this->mdl_log_api->update(array('result'=>$result,
                                         'success'=>$success,
                                         'result_code'=>$code,
                                         'result_message'=>$msg,
                                         'failure_log'=>$failure_log),$log_api_filter);
    }

    private function checkOrderNo($order_no){
        $retVal = false;
        $mdl_member_fyw = app::get('b2c')->model('orders_fyw');
        $order_id = $mdl_member_fyw->getRow('order_id',array('member_no'=>$order_no));
        if ($order_id){
            $retVal = true;
        }
        return $retVal;
    }

    private function checkMember($member_no){

        // 2017-07-10 需求调整
        //$retVal = false;
        //$mdl_member_fyw = app::get('b2c')->model('member_fyw');
        //$member_id = $mdl_member_fyw->getRow('member_id',array('member_no'=>$member_no));
        //if (!empty($member_id)){
        //    $retVal = true;
        //}
        //return $retVal;
        return true;
    }

    private function getBalance($member_no){
        $_sjson = array(
            'METHOD'=>'getBalanceInfoByRelationId',
            'RELATION_ID'=>$member_no,
            'QUERY_TIME'=>""
        );
        //$this->log_rsa(json_encode($_sjson),var_export($_sjson,1));
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        //$this->log_rsa(var_export($tmpdata,1));
        if($tmpdata != null && gettype($tmpdata) == "object"){
            $tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
        }else{
            $tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
        }
        return $tmp22['RESULT_DATA']['SUM'];
    }

    /**
     * send_user_succ-返回成功信息
     *
     * @param      $result
     * @return     json数组
     */
    private function send_user_succ($result,$code='0000',$msg='操作成功'){
        $result_json = array(
            'body'=>$result,
            'head'=>array('code'=>$code,
                          'msg'=>$msg),
        );
        return json_encode($result_json);
    }

    /**
     * send_user_error-返回失败信息
     *
     * @param      $code,$data
     * @return     json数组
     */
    private function send_user_error($code='9999', $msg='系统错误'){
        $result_json = array(
            'body'=>'',
            'head'=>array('code'=>$code,
                          'msg'=>$msg),
        );
        return json_encode($result_json);
    }

    /**
     * 验证是否合法
     * @param $reqText
     */
    private function verify($reqText) {
        //验证是否合法
    }

    private function log($message) {
        error_log($message, 0);
    }

    private function get_sign($params,$token){
        return strtoupper(md5(strtoupper(md5($params)).$token));
    }

    private function assemble($params){
        if(!is_array($params))  return null;
        ksort($params,SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            $sign .= $key . (is_array($val) ? assemble($val) : $val);
        }
        return $sign;
    }

}