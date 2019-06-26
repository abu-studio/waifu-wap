<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class cellphone_base_payment_mpay extends cellphone_cellphone{

    public function __construct( $app ) {
        parent::__construct() ;
        $this->app = $app ;

    }

    /**
     * 支持合并付款
     * @params order_id Array(
                              0=>2014062611518355
                              1=>2014062611518356
                              2=>2014062611518358
                              )
       @params pay_app_id string (malipay、upmp)
       @params session string 
     * @return null
     */
    public function pay(){
        $params = $this->params ;

        //检查应用级必填参数
        $must_params = array( 
            'session' => '会员ID', 
            'order_id' => '订单编号' ,//数组
            'pay_app_id'=>'支付方式'  //字符串
            ) ;
        $this->check_params( $must_params ) ;
        $params['order_id'] = trim($params['order_id']);

        $aryorder_id = json_decode($params['order_id'],true);
        if(!is_array($aryorder_id)){
           $this->send(false,null, app::get( 'b2c' )->_('order_id 参数格式错误'));
        }
        if(!in_array($params['pay_app_id'],array('malipay','upmp','deposite'))){
           $this->send(false,null, app::get( 'b2c' )->_('pay_app_id 参数错误'));
        }
        $member = $this->get_current_member() ;
        if(empty($member)){
            $this->send( false, null, app::get( 'b2c' )->_('session失效或您还未登录')) ;
        }
        $sdf['member_id'] = $member['member_id'] ;

        //支付对象order
        $pay_object = 'order' ;
        $sdf['pay_object'] = $pay_object;

        //接收支付方式
        $sdf['pay_app_id'] = $params['pay_app_id'] ;

        //支付类型在线支付
        $sdf['pay_type'] = 'online';

        // 得到商店名称
        $shopName = app::get('b2c')->getConf('system.shopname');
        $sdf['shopName'] = $shopName;
        
        $objOrders = &app::get( 'b2c' )->model( 'orders' ) ;
        $objPay = kernel::single( 'ectools_pay' ) ;
        $objMath = kernel::single( 'ectools_math' ) ;
             
        // 合并支付单号  
        if(count($aryorder_id) > 1){
           $merge_payment_id = $objPay->get_payment_id();
        }


        $money = 0;
        //循环订单号数组
        foreach($aryorder_id as $item){

            $sdf['order_id']= $item;
            $sdf['merge_payment_id'] = $merge_payment_id;//合并支付单号
            $orders = $objOrders->dump($sdf['order_id']);
            $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
            $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'],$orders['cur_rate']));
            $sdf['money'] = $objMath->number_minus(array($orders['total_amount'],$orders['payed']));
           // floatval($orders['total_amount'] - $orders['payed']);
            $sdf['currency'] = $orders['currency'];
            $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
            $sdf['cur_rate'] = $orders['cur_rate'];

            $payment_id = $sdf['payment_id'] = $objPay->get_payment_id();//生成支付单号

            if (!$sdf['pay_app_id']){
               $this->send( false, null, app::get( 'b2c' )->_( '支付方式不能为空！' ) ) ;
            }
           
           // $arrOrders = $objOrders->dump($sdf['order_id'], '*');
           
            //订单支付时更换订单支付方式
            if ($orders['payinfo']['pay_app_id'] != $sdf['pay_app_id']){
                $class_name = "";
                $obj_app_plugins = kernel::servicelist("ectools_payment.ectools_mdl_payment_cfgs");
                foreach ($obj_app_plugins as $obj_app){
                    $app_class_name = get_class($obj_app);
                    $arr_class_name = explode('_', $app_class_name);
                    if (isset($arr_class_name[count($arr_class_name)-1]) && $arr_class_name[count($arr_class_name)-1]){
                        if($arr_class_name[count($arr_class_name)-1] == $sdf['pay_app_id']){
                            $pay_app_ins = $obj_app;
                            $class_name = $app_class_name;
                          }
                    }else{
                        if($app_class_name == $sdf['pay_app_id']){
                            $pay_app_ins = $obj_app;
                            $class_name = $app_class_name;
                        }
                    }
                }
                $strPaymnet = app::get('ectools')->getConf($class_name);
                $arrPayment = unserialize($strPaymnet);

                $cost_payment = $objMath->number_multiple(array($objMath->number_minus(array($orders['total_amount'], $orders['payinfo']['cost_payment'])), $arrPayment['setting']['pay_fee']));
                $total_amount = $objMath->number_plus(array($objMath->number_minus(array($orders['total_amount'], $orders['payinfo']['cost_payment'])), $cost_payment));
                $cur_money = $objMath->number_multiple(array($total_amount, $orders['cur_rate']));

                // 更新订单支付信息
                $arr_updates = array(
                    'order_id' => $sdf['order_id'],
                    'payinfo' => array(
                                    'pay_app_id' => $sdf['pay_app_id'],
                                    'cost_payment' => $objMath->number_multiple(array($cost_payment, $orders['cur_rate'])),
                                ),
                    'total_amount' => $total_amount,
                    'cur_amount' => $cur_money,
                );

                $changepayment_services = kernel::servicelist('b2c_order.changepayment');
                foreach($changepayment_services as $changepayment_service)
                {
                    $changepayment_service->generate($arr_updates);
                }

                $objOrders->save($arr_updates);

                $arrOrders = $objOrders->dump($sdf['order_id'], '*');
                /** 需要想中心发送支付方式修改的动作 **/
                $obj_b2c_pay = kernel::single('b2c_order_pay');
                $obj_b2c_pay->order_payment_change($arrOrders);
            }

            // 检查是否能够支付
            $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
            $sdf_post = $sdf;
            $sdf_post['money'] = $sdf['cur_money'];
            //判断是否已经支付
            if($sdf['cur_money'] > 0){
                if (!$obj_checkorder->check_order_pay($sdf['order_id'],$sdf_post,$message))
                {      
                   $this->send(false,null, $message);
                }
            }

            if (!$sdf['pay_app_id'])
                $sdf['pay_app_id'] = $arrOrders['payinfo']['pay_app_id'];

            $sdf['currency'] = $arrOrders['currency'];
            $sdf['total_amount'] = $arrOrders['total_amount'];
            $sdf['payed'] = $arrOrders['payed'] ? $arrOrders['payed'] : '0.000';
            //$sdf['money'] = $objMath->number_div(array($sdf['cur_money'], $arrOrders['cur_rate']));

            $sdf['payinfo']['cost_payment'] = $arrOrders['payinfo']['cost_payment'];

            // 相关联的id.
            $sdf['rel_id'] = $sdf['order_id'];
            
            $sdf['status'] = 'ready';
            // 需要加入service给其他实体和虚拟卡
            $obj_prepaid = kernel::service('b2c.prepaidcards.add');
             
            $is_save_prepaid = false;
            if ($obj_prepaid)
            {
                $is_save_prepaid = $obj_prepaid->gen_charge_log($sdf);
            }
            if($sdf['cur_money'] > 0){
                $is_payed = $objPay->all_generate($sdf,app::get('b2c')->controller('site_paycenter'), $msg);
            }

            $money = $money + $sdf['cur_money'];

        }  //循环生成支付单结束

        //统计订单累计总金额money
        $sdf['cur_money'] = $money;
        $sdf['money'] = $money;
        $sdf['cur_amount'] = $money;
        $sdf['total_amount'] = $money;
        $sdf['cur_money'] = $money;
        $sdf['merge_payment_id'] = $merge_payment_id;

        //如果是合并支付时 合并支付单号作为payment_id
        if($merge_payment_id){
          $sdf['payment_id'] = $merge_payment_id;
        }

        $rel_order = array('rel_id'=>$merge_payment_id,'bill_type'=>'payments','pay_object'=>'order','bill_id'=>$sdf['merge_payment_id'],'money'=>$money);
        $sdf['orders']['0'] = $rel_order;
        $sdf['pay_type'] = 'online';
        
       
        //支付方式为预存款时
        if ($sdf['pay_app_id'] == 'deposit'){

          $is_payed = $objPay->all_dopay($sdf, app::get('b2c')->controller('site_paycenter'), $msg);
          if ($is_save_prepaid && $is_payed){

            $obj_prepaid->update_charge_log($sdf);
            $this->send(true,null,$msg);

           }
        //支付方式为非预存款时(malipay upmp)
        }else if($sdf['pay_app_id'] == 'malipay'|| $sdf['pay_app_id'] == 'upmp'){
            $response = $objPay->all_dopay($sdf, app::get('b2c')->controller('site_paycenter'), $msg);

            if ($is_save_prepaid && $response['params']) {
             $obj_prepaid->update_charge_log($sdf);
            }
           $this->send(true,$response,$msg);
        } else {
            $this->send(false,null,'参数错误');
        }
    }

}