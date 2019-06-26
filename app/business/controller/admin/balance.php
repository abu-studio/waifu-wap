<?php
class business_ctl_admin_balance extends desktop_controller{
    public function __construct($app)
	{
		parent::__construct($app);
        $this->router = app::get('desktop')->router();
		header("cache-control: no-store, no-cache, must-revalidate");
	}
	
    public function index(){
        $this->finder('ectools_mdl_refunds',array(

            'title'=>app::get('ectools')->_('结算单'),'allow_detail_popup'=>true,
            'base_filter'=>array('refund_type'=>2),
            'actions'=>array(

                        ),
            'use_buildin_export'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'force_view_tab'=>true,
            ));
    }

    public function _views(){

		$count_all = app::get('ectools')->model('refunds')->count(array('refund_type'=>2));
		$count_balance = app::get('ectools')->model('refunds')->count(array('is_balance'=>'1','refund_type'=>2));
		$count_no_balance = app::get('ectools')->model('refunds')->count(array('is_balance'=>'2','refund_type'=>2));

        /*return array(
                0=>array('label'=>app::get('ectools')->_('全部'),'optional'=>false,'filter'=>'','addon'=>$count_all,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>0))),
                1=>array('label'=>app::get('ectools')->_('待结算'),'optional'=>false,'filter'=>array('status'=>'ready'),'addon'=>$count_no_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>1))),
                2=>array('label'=>app::get('ectools')->_('结算成功'),'optional'=>false,'filter'=>array('status'=>'succ'),'addon'=>$count_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>2))),
            );*/

		return array(
                0=>array('label'=>app::get('ectools')->_('全部'),'optional'=>false,'filter'=>'','addon'=>$count_all,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>0))),
                1=>array('label'=>app::get('ectools')->_('未结算'),'optional'=>false,'filter'=>array('is_balance'=>'2'),'addon'=>$count_no_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>1))),
                2=>array('label'=>app::get('ectools')->_('已结算'),'optional'=>false,'filter'=>array('is_balance'=>'1'),'addon'=>$count_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_balance','act'=>'index','view'=>2))),
            );
    }

    public function balance(){
        $this->begin('index.php?app=business&ctl=admin_balance&act=index');
        $refund = app::get('ectools')->model('refunds');
        $refund_data = $refund->dump($_GET['refund_id'],'*');
        $bill = app::get('ectools')->model('order_bills');
        $rel_order_id = $bill->dump(array('bill_id'=>$_GET['refund_id']),'rel_id');
        if($refund_data['refund_type'] == '2'){
            if($refund_data['pay_app_id'] == 'ysepay'){
                if($refund_data['cur_money'] == 0){
                    $result['0'] = "true";
                }else{
                    foreach( kernel::servicelist('ysepay_tools') as $services ) {
                        if ( is_object($services)) {
                            if ( method_exists($services, 'amount_transfer') ) {
                                $obj_ys = app::get('business')->model('storemanger');

                                $sz_payer = unserialize(app::get('ectools')->getConf('ysepay_payment_plugin_ysepay'));
                                $payer['payerName'] = urlencode($sz_payer['setting']['src_name']);
                                $payer['payerUserCode'] = $sz_payer['setting']['member_id'];
                                $src = $sz_payer['setting']['member_id'];

                                $ys = $obj_ys->getRow('*',array('store_id'=>$refund_data['member_id']));
                                $payee['payeeName'] = urlencode($ys['company_name']);
                                $payee['payeeUserCode'] = $ys['ysusercode'];
                                //转账信息
                                $amount = $refund_data['cur_money'];//转账金额
                                $out_order_id=$rel_order_id['rel_id'];//代付单号，唯一

                                $result = $services->amount_transfer($src,$payer,$payee,$amount,$out_order_id,$rel_order_id['rel_id']);
                            }
                        }
                    }
                }
                if($result['0'] == "true"){
                    $obj_refunds = kernel::single("ectools_refund");
                    $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$_GET['refund_id'],'refund_type'=>'2'));
                    //添加操作员
                    $obj_user = kernel::single('desktop_user');
                    $refund->update(array('op_id'=>$obj_user->user_id),array('refund_id'=>$_GET['refund_id']));
                    if ($ref_rs)
                    {
                        $this->end(true, '结算成功！');

                    }else{
                        $this->end(false, '结算成功，更新结算单状态失败！');
                    }
                }else{
                    $refund->update(array('memo'=>$result['1']),array('refund_id'=>$_GET['refund_id']));
                    $this->end(false, '结算失败！'.$result['1']);
                }
            }else{
                $this->end(false, '结算失败！支付方式错误，请线下交易！');
            }
        }else{
            $this->end(false, '结算单类型错误！');
        }        
    }

    function balance_refund_finish(){
        $this->begin('index.php?app=ectools&ctl=admin_refund&act=index');
        $refund = app::get('ectools')->model('refunds');
        $refund_data = $refund->dump($_GET['refund_id'],'*');
        $obj_bills = app::get('ectools')->model('order_bills');
        $order_id = $obj_bills->getRow('rel_id',array('bill_id'=>$_GET['refund_id']));
        if($refund_data['refund_type'] == '1'){         
            $obj_refunds = kernel::single("ectools_refund");
            $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$_GET['refund_id'],'refund_type'=>'1'));
            //添加操作员
            $obj_user = kernel::single('desktop_user');
            $refund->update(array('op_id'=>$obj_user->user_id),array('refund_id'=>$_GET['refund_id']));
            if ($ref_rs)
            {
                $this->end(true, '退款成功！');
            }else{
                $this->end(false, '退款成功，更新退款单失败！');
            }
        }else{
            $this->end(false, '结算单类型错误！');
        }
    }

    public function balance_refund(){
        $this->begin('index.php?app=ectools&ctl=admin_refund&act=index');
        $refund = app::get('ectools')->model('refunds');
        $refund_data = $refund->dump($_GET['refund_id'],'*');
        $obj_bills = app::get('ectools')->model('order_bills');
        $order_id = $obj_bills->getRow('rel_id',array('bill_id'=>$_GET['refund_id']));
        if($refund_data['refund_type'] == '1'){

            $payment_id = $refund->get_payment($order_id['rel_id']);
            $obj_payment = app::get('ectools')->model('payments');
            $cur_money = $obj_payment->dump($payment_id['bill_id'],'*');

            //判断是否是合并付款
            if($cur_money['merge_payment_id'] != ''){
                $payment_id['bill_id'] = $cur_money['merge_payment_id'];
                $cur_money['cur_money'] = 0;
                $total = $obj_payment->getList('*',array('merge_payment_id'=>$payment_id['bill_id'],'status'=>'succ'));
                foreach($total as $key=>$val){
                    $cur_money['cur_money'] = $cur_money['cur_money'] + $val['cur_money'];
                }
            }
			$obj_refunds = kernel::single("ectools_refund");
            if($refund_data['pay_app_id'] != 'deposit'){ 
                if($refund_data['cur_money'] == 0){
					$result = "success";
                    $ref_rs = $obj_refunds->generate_after($refund_data);
                }else{
                    //$refund_data['payment_info'] = $cur_money;
					$refund_data = $obj_refunds->make_data($refund_data);
					if($refund_data){
						foreach($refund_data as $key=>$re_data){
							$result = $obj_refunds->dorefund($re_data,$this);
							$ref_rs = $obj_refunds->callback($re_data,$result);
						}
					}
                }

                if($result == "success"){
                    //添加操作员
                    $obj_user = kernel::single('desktop_user');
                    $refund->update(array('op_id'=>$obj_user->user_id),array('refund_id'=>$_GET['refund_id']));
                    if ($ref_rs)
                    {
                        $this->end(true, '退款成功！');

                    }else{
                        $this->end(false, '退款成功，更新退款单失败！');
                    }
                }else{
                    $this->end(false, '退款失败！'.$result);
                }
            }else{
				//预存款退款
				$res = $obj_refunds->refund_advance($refund_data,$msg);
				if($res){
					//添加操作员
                    $obj_user = kernel::single('desktop_user');
                    $refund->update(array('op_id'=>$obj_user->user_id),array('refund_id'=>$_GET['refund_id']));
					$this->end(true, '预存款退款成功！');
				}else{
					$this->end(false, '预存款退款失败！'.$msg);
				}
            }
        }else{
            $this->end(false, '结算单类型错误！');
        }        
    }

	public function balance_refund_confirm(){
		$refund_id = $_GET['refund_id'];
		$this->pagedata['refund_id'] = $refund_id;
		$this->display('admin/refund/refund_info.html');
	}

	public function refund_info(){
		$this->begin('index.php?app=ectools&ctl=admin_refund&act=index');
		$sdf = array();
		$sdf['account'] = $_POST['account'];
		$sdf['pay_account'] = $_POST['account'];
		$sdf['bank'] = $_POST['bank'];
		if(is_numeric($_POST['money']) && $_POST['money']>0){
			$sdf['money'] = $_POST['money'];
			$sdf['cur_money'] = $_POST['money'];
		}else{
			$this->end(false, '请填写正确的退款金额');
		}
		$sdf['memo'] = $_POST['memo'];

		$sdf['status'] = 'succ';
		$sdf['pay_type'] = 'offline';

		$refund = app::get('ectools')->model('refunds');
		$is_save = $refund->update($sdf,array('refund_id'=>$_POST['refund_id']));
		$this->end(true, '操作成功！');
	}
}