<?php


/**
 * 预存款支付具体实现
 *
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
final class ectools_payment_plugin_sfscpay extends ectools_payment_app implements ectools_interface_payment_app{
    /**
     * @var string 支付方式名称
     */
    public $name = '福点支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '福点支付接口';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'sfscpay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'sfscpay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '福点支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'ispc';

    public $supportCurrency = array("CNY"=>"01");

    /**
     * 构造方法
     * @param object 传递应用的app
     * @return null
     */
    public function __construct($app){
        parent::__construct($app);

        $this->callback_url = "";
        $this->submit_url = '';
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 显示支付接口表单基本信息
     * @params null
     * @return string - description include account.
     */
    public function admin_intro(){
        return app::get('ectools')->_('福点支付后台自定义描述');
    }

    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return app::get('ectools')->_('福点支付自定义描述');
    }

    /**
     * 显示支付接口表单选项设置
     * @params null
     * @return array - 字段参数
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('ectools')->_('支付方式名称'),
                'type'=>'string'
            ),
            'support_cur'=>array(
                'title'=>app::get('ectools')->_('支持币种'),
                'type'=>'text hidden cur',
                'options'=>$this->arrayCurrencyOptions,
            ),
            'pay_desc'=>array(
                'title'=>app::get('ectools')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
                'type'=>'select',
                'name' => 'pay_type',
                'options' => array('true' => '在线支付')
            ),
            'status'=>array(
                'title'=>app::get('ectools')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
                'name' => 'status',
            ),
        );
    }

    /**
     * 提交支付信息的接口
     * 支付接口表单提交方式
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {

    }

    /**
     * 提交支付信息的接口
     * 无需表单正常支付
     * @param array 提交信息的数组
     * @return mixed false or null
     */

    public function do_payment($payment, &$msg)
    {
        if($payment['action']=='freeze'){
            $pay_sub_tmp = app::get('ectools')->model('order_bills')->dump(array('bill_id'=>$payment['payment_id']),'*');
            $pay_sub_data[] = app::get('b2c')->model('orders')->dump(array('order_id'=>$pay_sub_tmp['rel_id']),"*");
            //组合支付福点冻结特化处理
            $pay_sub_data[0]['total_amount'] = $payment['cur_money'];
            $payment_tmp = $payment;
            $payment_tmp['sub_orders'] = $pay_sub_data;
            return $this->sfscpaydeduct($msg, $payment_tmp);
        }
        $obj_pay_lists = kernel::servicelist("order.pay_finish");
        $is_payed = 'succ';

        foreach ($obj_pay_lists as $order_pay_service_object)
        {
            $class_name = get_class($order_pay_service_object);

            if (!$payment['member_id'])
            {
                $is_payed = 'failed';
                return false;
            }

            $obj_payment_update = kernel::single('ectools_payment_update');
            $obj_sub_payments = app::get('ectools')->model('order_bills');
            $obj_objects = app::get('b2c')->model('orders');
            //判断是否合并支付
            if($payment['merge_payment_id'] != ''){
                $obj_payments = app::get('ectools')->model('payments');
                $pay_data = $obj_payments->getList('*',array('merge_payment_id'=>$payment['merge_payment_id']));
                foreach($pay_data as $key=>$val){
                    //获取子订单信息
                    $pay_sub_tmp = $obj_sub_payments->dump(array('bill_id'=>$val['payment_id']),'*');
                    $pay_sub_data[] = $obj_objects->dump(array('order_id'=>$pay_sub_tmp['rel_id']),"*");
                    $val['status'] = 'succ';
                    //重新生成订单
                    $is_payed = $obj_payment_update->generate($val, $msg);
                }
            }else{
                $payment['status'] = 'succ';
                //获取子订单信息
                $pay_sub_tmp = $obj_sub_payments->dump(array('bill_id'=>$payment['payment_id']),'*');
                $pay_sub_data[] = $obj_objects->dump(array('order_id'=>$pay_sub_tmp['rel_id']),"*");
                //重新生成订单
                $is_payed = $obj_payment_update->generate($payment, $msg);
            }

            if (!$is_payed)
            {
                return false;
            }
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();
            //增加合并接口调用的数据
            $payment_tmp = $payment;
            $payment_tmp['sub_orders'] = $pay_sub_data;
            //增加调用扣款接口
            $is_payed_tmp = $this->sfscpaydeduct($msg,$payment_tmp);
            if(! $is_payed_tmp){
                $db->rollback();
                $this->sfscpaycallback($msg,$payment_tmp);
                $this->sub_orders_failed_sfscpaycallback($payment_tmp['sub_orders']);
                return false;
            }

            //付款后做状态的变更和处理
            $is_payed = $order_pay_service_object->order_pay_finish($payment, 'succ', 'font',$msg);

            if (!$is_payed)
            {
                $db->rollback();
                return false;
            }

            $db->commit($transaction_status);
            // 支付扩展事宜 - 如果上面与中心没有发生交互，那么此处会发出和中心交互事宜.
            $order_pay_service_object->order_pay_finish_extends($payment);
            return $is_payed;
            //}
        }

        return false;
    }

    public function sub_orders_failed_sfscpaycallback($pay_sub_data)
    {
        // {"order_id":"2018032214527761","store_id":"153","payment":"sfscpay","cur_money":"536","pay_status":"1","RELATION_ID":"0004975874","METHOD":"singlePayCallBack"}
        //福点or组合支付完成回调接口-hy
        $objMath = kernel::single('ectools_math');
        foreach($pay_sub_data as $sub_order){
            $sub_order['cur_money'] = $objMath->number_minus(array($sub_order['cur_amount'], $sub_order['payed']));
            $arr_callback = array(
                'order_id' => $sub_order['order_id'],
                'store_id' => $sub_order['store_id'],
                'payment' => $sub_order['payinfo']['pay_app_id'],
                'cur_money' => $sub_order['cur_money'],
                'pay_status' => '0',
            );
            $this->sfscpaycallback($msg,$arr_callback);
        }

        return true;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    /**
     * 支付回调的方法
     * @param array 回调参数数组
     * @return array 处理后的结果
     */
    public function callback(&$recv)
    {

    }

    /**
     * 生成form的方法
     * @param null
     * @return string html
     */
    public function gen_form()
    {
        return '';
    }

    function sfscpaydeduct(&$errMsg,$sdf){
        $obj_mem = app::get('b2c')->model('members');
        $pay_sub_tmp = app::get('ectools')->model('order_bills')->dump(array('bill_id'=>$sdf['payment_id']),'*');
        //由于读取出来的数据可能是多条数据 ，那在这一块做一个三目运算，防止这种情况发生

        $order = app::get('b2c')->model('orders')->dump($pay_sub_tmp['rel_id']);


        if($order == "" || $order === null){
            $order = app::get('b2c')->model('orders')->dump($sdf['order_id']);
        }


        $tmp = app::get('b2c')->model('members')->get_member_info($order['member_id']);
        $sdf['RELATION_ID'] = $tmp['uname'];
        $sdf['METHOD'] = "singlePay";
        if(!$tmp['uname']){
            $errMsg .= app::get('b2c')->_('帐号不存在，请重新登陆');
            return false;
        }

        if($sdf['QS_payment_id'] != ""){
            $sdf['RELATION_ID'] = $sdf['QS_payment_id'];
            unset($sdf['QS_payment_id']);
        }


        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);
        // 10000  失败    10003   余额不足    10001  成功
        if(empty($arr) && !is_numeric($arr['RESULT_CODE'])){
            $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10003){
            $errMsg .= app::get('b2c')->_('福点账户余额不足');
            return false;
        }elseif($arr['RESULT_CODE'] == 10000){
            $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10008){
            $errMsg .= app::get('b2c')->_('重复支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10001){
            return true;
        }
        $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
        return false;
    }

    function sfscpaycallback(&$errMsg,$sdf){
        $order = app::get('b2c')->model('orders')->dump($sdf['order_id']);
        $tmp = app::get('b2c')->model('members')->get_member_info($order['member_id']);
        if($order['java_payment_company'] != ''){
            $sdf['RELATION_ID'] = $order['java_payment_company'];
        }else{
            $sdf['RELATION_ID'] = $tmp['uname'];
        }

        $sdf['METHOD'] = "singlePayCallBack";

        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);

        // 10000  失败   10001  成功
        if(empty($arr) && !is_numeric($arr['RESULT_CODE'])){
            $errMsg = app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10000){
            if(!$errMsg) {$errMsg= app::get('b2c')->_('支付失败，请重新支付');}
            return false;
        }elseif($arr['RESULT_CODE'] == 10001){
            return true;
        }
        $errMsg = app::get('b2c')->_('未知错误，请重新支付');
        return false;
    }

    function failureDebitQueue($sdf){
        $data = array();
        $data['queue_title'] = app::get('b2c')->_('发送订单到外部');
        $data['start_time'] = time();
        $data['params'] = $sdf;
        $data['worker'] = 'b2c_queue.debit_queue';
        $queue = app::get('base')->model('queue');
        $queue->insert($data);

        return true;
    }
}
