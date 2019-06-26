<?php

 
class ectools_ctl_admin_refund extends desktop_controller{
    
	public function __construct($app)
	{
		parent::__construct($app);
        $this->router = app::get('desktop')->router();
		header("cache-control: no-store, no-cache, must-revalidate");
	}
	
    public function index(){
		$actions = array();
		if($_GET['view']=='6'){
			$actions[] = array(
				 'label'=>'批量退款',
				 'submit'=>'index.php?app=ectools&ctl=admin_refund&act=alipay_refund',
				 'target'=>'_blank',
			);
		}
        $this->finder('ectools_mdl_refunds',array(

            'title'=>app::get('ectools')->_('退款单'),'allow_detail_popup'=>true,
            'base_filter'=>array('refund_type'=>1),
            'actions'=>$actions,
           	'use_buildin_export'=>true,
            ));
    }

    public function _views(){

		$count_all = app::get('ectools')->model('refunds')->count(array('refund_type'=>'1'));
		$count_balance = app::get('ectools')->model('refunds')->count(array('status'=>'succ','refund_type'=>'1','is_safeguard'=>'1'));
		$count_no_balance = app::get('ectools')->model('refunds')->count(array('status'=>'ready','refund_type'=>'1','is_safeguard'=>'1'));
        $safeguard_balance = app::get('ectools')->model('refunds')->count(array('status'=>'succ','refund_type'=>'1','is_safeguard'=>'2'));
		$safeguard_no_balance = app::get('ectools')->model('refunds')->count(array('status'=>'ready','refund_type'=>'1','is_safeguard'=>'2'));
		$alipay_no_balance = app::get('ectools')->model('refunds')->count(array('status'=>'ready','refund_type'=>'1','pay_app_id'=>'alipay'));
		$alipay_refunds = app::get('ectools')->model('refunds')->count(array('status'=>'ready','refund_type'=>'1','pay_app_id'=>'alipay'));

        return array(
                0=>array('label'=>app::get('ectools')->_('全部'),'optional'=>false,'filter'=>'','addon'=>$count_all,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>0))),
                1=>array('label'=>app::get('ectools')->_('确认收货前待处理退款'),'optional'=>false,'filter'=>array('status'=>'ready','is_safeguard'=>'1'),'addon'=>$count_no_balance,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>1))),
                2=>array('label'=>app::get('ectools')->_('确认收货前已处理退款'),'optional'=>false,'filter'=>array('status'=>'succ','is_safeguard'=>'1'),'addon'=>$count_balance,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>2))),
                3=>array('label'=>app::get('ectools')->_('确认收货后待处理退款'),'optional'=>false,'filter'=>array('status'=>'ready','is_safeguard'=>'2'),'addon'=>$safeguard_no_balance,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>3))),
                4=>array('label'=>app::get('ectools')->_('确认收货后已处理退款'),'optional'=>false,'filter'=>array('status'=>'succ','is_safeguard'=>'2'),'addon'=>$safeguard_balance,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>4))),
                5=>array('label'=>'支付宝待处理退款','optional'=>false,'filter'=>array('status'=>'ready','pay_app_id'=>'alipay'),'addon'=>$alipay_no_balance,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>5))),
                6=>array('label'=>'支付宝批量处理退款','optional'=>false,'filter'=>array('status'=>'ready','pay_app_id'=>'alipay','paytype'=>'alipay'),'addon'=>$alipay_refunds,'href'=>$this->router->gen_url(array('app'=>'ectools','ctl'=>'admin_refund','act'=>'index','view'=>6))),
        );
    }

	/* 支付宝批量退款功能 */
	public function alipay_refund(){
		$post = $_POST;
		$db = kernel::database();
		$obj_refunds = app::get('ectools')->model('refunds');
		$obj_order_bills = app::get('ectools')->model('order_bills');
		$obj_return_product = app::get('aftersales')->model('return_product');

		$refund_detail = array();
		//获取所有选中的退款单数据
		$sel_refunds = $obj_refunds->getList('*',array('refund_id|in'=>$post['refund_id']));
		foreach($sel_refunds as $key=>$refund){
			//查询对应订单号
			$order_bill = $obj_order_bills->getRow('*',array('bill_id'=>$refund['refund_id'],'bill_type'=>'refunds'));
			$order_id = $order_bill['rel_id'];
			//查询订单对应的收款单据
			$sql_payments = 'SELECT * FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_payments p ON ob.bill_id=p.payment_id WHERE ob.rel_id="'.$order_id.'" AND ob.bill_type="payments" AND p.status="succ" AND p.pay_app_id="alipay"';
			$payments = $db->select($sql_payments);
			//确保收款单据唯一且不是合并支付
			if(count($payments)>1||$payments[0]['merge_payment_id']){continue;}
			//查询订单对应的退款单据
			$sql_refunds = 'SELECT * FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_refunds p ON ob.bill_id=p.refund_id WHERE ob.rel_id="'.$order_id.'" AND ob.bill_type="refunds" AND p.status="ready" AND p.pay_app_id="alipay"';
			$refunds = $db->select($sql_refunds);
			//确保退款单据唯一
			if(count($refunds)>1){continue;}
			//查询退款申请
			$return_product = $obj_return_product->getRow('*',array('order_id'=>$order_id),'add_time DESC');
			//支付宝交易单号
			$trade_no = $payments[0]['trade_no'];
			//退款金额
			$money = $refunds[0]['cur_money'];

			if(!$trade_no||!$money){continue;}
			//退款原因
			$reason = $return_product['comment'];
			if(!$reason){$reason = '其他原因';}

			$refund_detail[] = array(
				'order_id'=>$order_id,
				'refund_id'=>$refunds[0]['refund_id'],
				'trade_no'=>$trade_no,
				'money'=>$money,
				'reason'=>$reason,
			);
		}

		$classname = 'ectools_payment_plugin_alipay';
		if(class_exists($classname)){
			$class=new $classname($this->app);
			if(method_exists($class,'refund_fastpay_by_platform_pwd')){
				return $class->refund_fastpay_by_platform_pwd($refund_detail);
			}
		}
		echo 'method not found';
		exit;
	}
}
