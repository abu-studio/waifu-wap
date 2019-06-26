<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class business_ctl_admin_settlement extends desktop_controller
{

    var $pagelimit = 10;

    function __construct($app)
    {
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
    }

    function index($settlement_no='',$type='')
    {
		$this->finder('business_mdl_settlement', 
		   array('title' => app::get('b2c')->_('结算报表'),
		   'use_buildin_export'=>true,
		   'base_filter'=>array(),
		   'actions' => array(
				array(
					'label' => "生成结算报表",
					'href' => 'index.php?app=business&ctl=admin_settlement&act=auto_create_page&step=1',
					'target' => 'dialog::{title:\'' . app::get('business')->_('生成结算报表') . '\',width:450,height:450}'
				),
			),
		   'use_view_tab'=>true,
		   'force_view_tab'=>true,
		   'use_buildin_filter'=>true,
		   
		));
    }

	public function _views(){
		$count_all = app::get('business')->model('settlement')->count(array());
		$count_no_balance = app::get('business')->model('settlement')->count(array('is_balance|in'=>array('2','3','4','5')));
		$count_balance = app::get('business')->model('settlement')->count(array('is_balance|in'=>array('1')));

		return array(
			0=>array('label'=>'全部','optional'=>false,'filter'=>'','addon'=>$count_all,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_settlement','act'=>'index','view'=>0))),
			1=>array('label'=>'未结算','optional'=>false,'filter'=>array('is_balance|in'=>array('2','3','4','5')),'addon'=>$count_no_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_settlement','act'=>'index','view'=>1))),
			2=>array('label'=>'已结算','optional'=>false,'filter'=>array('is_balance|in'=>array('1')),'addon'=>$count_balance,'href'=>$this->router->gen_url(array('app'=>'business','ctl'=>'admin_settlement','act'=>'index','view'=>2))),
        );
	}

	public function nobalance(){
		$this->finder('business_mdl_settlement', 
		   array('title' => app::get('b2c')->_('结算报表'),
		   'use_buildin_export'=>true,
		   'base_filter'=>array('is_balance|in'=>array('2','4')),
		   'actions'=>array(
                array(
                    'label' => "批量结算",
					'confirm'=>app::get('settlement')->_('确定结算所有选中项吗？'),
					'submit'=>'index.php?app=business&ctl=admin_settlement&act=settlement_balance&callback='.$_GET['act'],
                ),
            ),
            'use_buildin_export'=>true,
            'use_view_tab'=>true,
            'force_view_tab'=>true,
		   
		));
	}

    public function pagination($current, $count, $get)
    {
        $app = app::get('business');
        $render = $app->render();

        $ui = new base_component_ui($this->app);
        //unset($get['singlepage']);
        $link = 'index.php?app=business&ctl=admin_settlement&act=ajax_html&id=' . $get['id'] .
            '&finder_act=' . $get['page'] . '&' . $get['page'] . '=%d';
        $this->pagedata['pager'] = $ui->pager(array(
            'current' => $current,
            'total' => ceil($count / $this->pagelimit),
            'link' => $link,
            ));
    }

    public function ajax_html()
    {
        $finder_act = $_GET['finder_act'];
        $html = kernel::service('desktop_finder.business_mdl_settlement')->$finder_act($_GET['id']);
        echo $html;
    }


    //提示自动创建结算单页面
    function auto_create_page(){
        $step = $_GET['step'];
        switch($step)
        {
            case 2:
				$obj_refunds = app::get('ectools')->model('refunds');
				//旧的结算逻辑
				//$obj_store = app::get('business')->model('storemanger');
				//$stores = $obj_store->store_getList();
				$stores = $_POST['store']['value'];
				$order_kind = $_POST['order_kind'];
				$card_id = implode(',',$_POST['card_id']);
				if($card_id){
					foreach($order_kind as $ke=>$val){
						if($val == 'card'){
							unset($order_kind[$ke]);	
						}	
					}	
				}
				
				
				$store_ids = implode(',',$stores);
				//时间字符串拼接-hy
				//$from_time = $_POST['from_time'].' '.$_POST['_DTIME_']['H']['from_time'].':'.$_POST['_DTIME_']['M']['from_time'];
				//$to_time = $_POST['to_time'].' '.$_POST['_DTIME_']['H']['to_time'].':'.$_POST['_DTIME_']['M']['to_time'];
				//时间转换为时间戳-hy
				$from_time = strtotime($_POST['from_time']);
				$to_time = strtotime($_POST['to_time']);
				if(count($order_kind)>0){
					$order_kind = "'".implode("','",$order_kind)."'";
				}else{
					$order_kind = '';
				}
				$filter = array(
					'start' => $from_time, 
					'end' => $to_time,
					'order_kind' => $order_kind,
					'card_id'=>$card_id
				);
				if(!empty($stores)){
					$balances = $obj_refunds->count_bills_by_filter($store_ids,'balance',$filter);
				}
                $order_count_fyw =0;
                //如果选择福员外订单，需要单独统计需要结算的订单数量
                if (strpos ($order_kind,'fyw')){
                    //获取商家信息
                    $obj_store = app::get('business')->model('storemanger');
                    //福员外店铺id
                    $fyw_store_id  = $obj_store->getRow('store_id',array('shop_name'=>'fuyuanwai','account_id'=>1));
                    if (!in_array($fyw_store_id['store_id'],$stores)){
                        echo app::get('business')->_('包含福员外订单类别，请选择:福员外店铺');die();
                    }
                    $fuyuanwai_orders = kernel::single('b2c_fuyuanwai_orders');
                    $fyw_ordrs =  $fuyuanwai_orders->get_bills($filter);

                    $order_count_fyw= sizeof($fyw_ordrs);
                }

				$this->pagedata['store_ids'] = $store_ids;
				$this->pagedata['start'] = $from_time;
				$this->pagedata['end'] = $to_time;
				$this->pagedata['order_kind'] = $order_kind;
				$this->pagedata['card_id'] = $card_id;
				$this->pagedata['store_count'] = sizeof($stores);
                $order_count = $balances?$balances:0;
				$this->pagedata['order_count'] = $order_count + $order_count_fyw;
				

				$this->display('admin/settlement/create.html');
				break;
            default:
				//获取订单类型下拉框
				$dbschema_orders = app::get('b2c')->model('orders')->get_schema();
				$order_kind = $dbschema_orders['columns']['order_kind']['type'];
				

				//获取卡劵类型
				$dbschema_card = app::get('cardcoupons')->model('cards_pass')->get_schema();
				$source = $dbschema_card['columns']['source']['type'];
						
                $order_kind['fyw']=app::get('business')->_('福员外订单');

				$this->pagedata['order_kind'] = $order_kind;
				$this->pagedata['source'] = $source;
                $this->display('admin/settlement/select.html');
		}
    }
    
    //创建结算单
    function create(){
        //创建结算单时，几万的数据量，需要的内存与时间与平常程序不同，临时增大内存配额
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('memory_limit','2048M');

        $this->begin('index.php?app=business&ctl=admin_settlement&act=index');

        //获取退款单信息
		$obj_refunds = app::get('ectools')->model('refunds');
        //获取结算单信息
        $obj = app::get('business')->model('settlement');
        //获取商家信息
        $obj_store = app::get('business')->model('storemanger');
		//旧的结算逻辑
		//$stores = $obj_store->store_getList();
		
		$stores_ids = $_POST['store_ids'];
		$stores = explode(',',$stores_ids);
		$filter = array(
			'start' => $_POST['start'],
			'end' => $_POST['end'],
			'order_kind' => $_POST['order_kind'],
			'card_id'=>$_POST['card_id']
		);

        //处理福员外订单
        if ($filter){
            $str_order_kind = $filter['order_kind'];

            //如果选择福员外订单，先进行福员外订单的结算
            if (strpos ($str_order_kind,'fyw')){

                //如果只选择了福员外订单
                if ($str_order_kind ==="'fyw'"){
                    $only_fyw = true;

                }else{
                    $only_fyw = false;
                }
                $this->create_fyw($filter);
                if ($only_fyw){
                    $this->end(true, '结算单创建成功');
                }
            }

        }

        //商家信息不为空
        if(!empty($stores)){
            //获取可结算的结算信息
            $balances = $obj_refunds->count_bills_by_filter($stores_ids,'balance',$filter);
        }

        //没有商家信息,没有结算单信息，也没有退款单信息
        if (empty($stores) || empty($balances)){
            $this->end(false, '没有可以结算的订单');
        }

        //根据店铺结算
        $data = array();
        foreach ($stores as $key => $value){
            //保存报表并打款
            $tp = $this->create_item($value,$data,$filter);
            if (empty($tp)){
                $this->end(false, '结算报表创建失败');
            }
        }

        //上期负数结算报表结算以及店铺未结算的售后退款单
        $this->set_settlement($data);

        //生成报表
		if(!empty($data['data_B'])){
			foreach($data['data_B'] as $key_b=>$val_b){
				$result_b = $this->create_settlement($val_b);
				if(!$result_b){
					$this->end(false, 'B店结算报表创建失败');
				}
				$res = $obj_store->update(array('last_settlement_time'=>time()),array('store_id'=>$key_b));
				if (!$res){
					$this->end(false, '修改店铺结算时间失败');
				}
			}
			//店铺结算明细报表
			if(!empty($data['order_ids'])){
				$result = $this->create_settlement_detail($data['order_ids']);
				if(!$result){
					$this->end(false, '店铺结算明细创建失败');
				}
			}
		}
        
        $this->end(true, '结算单创建成功');
    }

    //福员外订单结算
    private function create_fyw($filter){
        //获取可结算的结算单
        $obj_fyw_orders = kernel::single('b2c_fuyuanwai_orders');
        $fyw_orders =  $obj_fyw_orders->get_bills($filter);


        if (empty($fyw_orders)){
            $this->end(false, '没有可以结算的订单');
        }
        $obj_settlement = app::get('business')->model('settlement');
        //获取商家信息
        $obj_store = app::get('business')->model('storemanger');
        //订单主表object
        $obj_orders = app::get('b2c')->model('orders_fyw');
        //订单物品表
        $obj_order_items = app::get('b2c')->model('order_fyw_items');
        $objMath = kernel::single("ectools_math");

        //福员外店铺id//vip
        $fyw_store_id  = $obj_store->getRow('store_id',array('shop_name'=>'fuyuanwai','account_id'=>1));
        //$this->log_rsa('fyw_store_id:'.$fyw_store_id['store_id']);


        //根据店铺结算
        $data = array();
        //保存报表并打款
        $tp = $this->create_item_fyw($fyw_store_id['store_id'],$data,$filter,$fyw_orders);
        if (empty($tp)){
            $this->end(false, '结算报表创建失败');
        }

        //$this->log_rsa('before set_settlement_fyw',$data);

        //上期负数结算报表结算以及店铺未结算的售后退款单
        $this->set_settlement_fyw($data,$filter);

        //$this->log_rsa('after set_settlement_fyw',$data);

        //生成报表
        if(!empty($data['data_B'])){
            foreach($data['data_B'] as $key_b=>$val_b){
                $result_b = $this->create_settlement($val_b);
                if(!$result_b){
                    $this->end(false, 'B店结算报表创建失败');
                }
                $res = $obj_store->update(array('last_settlement_time'=>time()),array('store_id'=>$key_b));
                if (!$res){
                    $this->end(false, '修改店铺结算时间失败');
                }
            }

            //店铺结算明细报表
            if(!empty($data['order_ids'])){
                $result = $this->create_settlement_detail_fyw($data['order_ids']);
                if(!$result){
                    $this->end(false, '店铺结算明细创建失败');
                }
            }
        }

        $this->end(true, '结算单创建成功');

    }

    private function create_item_fyw($store_id,&$data,$filter,$fyw_orders){
        //$this->log_rsa('create_item_fyw');
        //订单主表object
        $obj_orders = app::get('b2c')->model('orders_fyw');
        //订单物品表
        $obj_order_items = app::get('b2c')->model('order_fyw_items');
        //计算工具
        $objMath = kernel::single("ectools_math");

        //根据结算单生报表
        $data_B = array();
        $order_ids = array();
        if(!empty($fyw_orders)){
            foreach($fyw_orders as $order_info){

                $data_B = $this->create_data_fyw($data_B,$order_info,$store_id);
                $order_ids[] = $order_info['order_id'];
                //修改结算单状态
                $obj_orders->update(array('is_balance'=>'1'),array('order_id'=>$order_info['order_id']));
            }
        }
        //统计所有订单商品
        $products = $obj_order_items->getList('*',array('order_id|in'=>$order_ids));
        $products_sum = array();
        foreach($products as $k => $v){
            // 结算逻辑调整--20170612
            //$cost_index=$v['goodsPointPrice']*1000;
            if($v['purchasePrice']){
                $cost_index=$v['purchasePrice']*1000;
            }else{
                $cost_index=$v['goodsPointPrice']*1000;
            }

            $price_index=$v['price']*1000;
            if(isset($products_sum[$v['goodsId']][$cost_index][$price_index])){
                $products_sum[$v['goodsId']][$cost_index][$price_index]['count'] =
                    $objMath->number_plus(array($products_sum[$v['goodsId']][$cost_index][$price_index]['count'],$v['goodsNum']));
            }else{
                $products_sum[$v['goodsId']][$cost_index][$price_index] = array(
                    'goods_name' => $v['goodsName'],
                    'count' => $v['goodsNum'],
                    // 结算逻辑调整--20170612
                    //'cost' => $v['goodsPointPrice'],
                    'cost' => $v['purchasePrice'],
                    // 结算逻辑调整--20170620
                    //'price' => $v['price'],
                    'price' => $v['goodsPointPrice'],
                    'start' => $filter['start'],
                    'end' => $filter['end'],
                );
            }
        }
        $data_B[$store_id]['products'] = $products_sum;
        //组装报表数组
        $data_B[$store_id]['info'] = $this->create_data_info($data_B,$store_id);
        $data_B[$store_id]['info']['start'] = $filter['start']?date('Y-m-d',$filter['start']):'';
        $data_B[$store_id]['info']['end'] = $filter['end']?date('Y-m-d',$filter['end']):'';
        $data['order_ids'][$data_B[$store_id]['info']['settlement_no']] = implode(',',$order_ids);
        if($data['data_B']){
            $data['data_B'] = $data['data_B']+$data_B;
        }else{
            $data['data_B'] = $data_B;
        }

        return $data;

    }

    //生成报表明细数组
    private function create_data_fyw(&$data,$order_info,$store_id){

        $obj_order_items = app::get('b2c')->model('order_fyw_items');
        $order_items_array = $obj_order_items->getList('*',array('order_id'=>$order_info['order_id']));
        $total_amount = 0;

        foreach($order_items_array as $k=>$v){
            // 结算逻辑调整--20170612
            //$total_amount =  $total_amount + $v['goodsPointPrice']*$v['goodsNum'];
            if($v['purchasePrice']){
                $total_amount =  $total_amount + $v['purchasePrice']*$v['goodsNum'];
            }else{
                $total_amount =  $total_amount + $v['goodsPointPrice']*$v['goodsNum'];
            }


        }
        //是否有用？
        //$order_info['balances_info'] = $balances_info;

        //订单退款明细
        $order_info['refunds_info'] = $this->get_refunds_fyw($order_info['order_id']);
        //售前退款金额统计
        //$order_info['refunds_info_before'] = $this->get_refunds($order_info['order_id'],'B');
        //组装报表明细数据
        $info = array();
        //获取每笔订单的 退款单数据
        if($order_info['refunds_info']){
            $info['is_refund'] = '1';
            $re_cut = 0;
            //成本价结算(暂时不考虑退款因素)
            //20179628调整 需考虑退款 $new_re_cut = $total_amount;
            $new_re_cut = 0;

            //订单的平台抽成金额
            $order_cut = 0;
            $re_cut = 0;
            $refund_id = array();

            $obj_fyw_orders = kernel::single('b2c_fuyuanwai_orders');
            foreach($order_info['refunds_info'] as $k=>$v){
                $re_cut = $re_cut + $v['totalPointAmt'];
                $order_cut = $order_cut+$v['final_amount'];
                $refund_id[]=$v['order_id'];

                //20179628调整
                $refundAccount = $obj_fyw_orders->calRefundAccount($v['order_id']);
                if ($refundAccount['refund_cost']){
                    $new_re_cut =  $new_re_cut + $refundAccount['refund_cost'];
                }
            }

            $refund_ids = implode(',',$refund_id);
            $info['re_cut'] = $order_cut;
            $info['refund_id'] = $refund_ids;
        }else{
            $info['is_refund'] = '0';
            $info['re_cut'] = 0;
            $info['refund_id'] = '无';
        }
        //order_money
        $info['order_id'] = $order_info['order_id'];
        //店铺结算价格(依照订单价格走的)
        //依照成本价格走的结算账单
        //20179628调整--需考虑退款 $info['account'] = $total_amount;
        $info['account'] = $total_amount - $new_re_cut;
        //订单总金额
        // 结算逻辑调整--20170620 $info['order_money'] = intval($order_info['final_amount']);
        $info['order_money'] = intval($order_info['totalPointAmt']);
        //平台抽成金额
        $info['order_cut'] = $order_info['final_amount']-$order_info['totalPointAmt'];
        //售后退款金额
        $info['order_recut'] = $order_cut - $re_cut;
        //店铺承担售后金额
        $info['total_recut'] = $order_cut;
        //平台承担售后金额
        $info['score_use'] = 0;
        $info['score_give'] = 0;
        //订单运费 wang_bin
        $info['cost_freight'] = 0;
        //订单类型
        $info['order_kind'] = $order_info['order_type'];
        //无售前款
        $info['refund'] = 0;
        $data[$store_id]['orders'][$order_info['order_id']] = $info;
        return $data;
    }

    private function create_settlement_detail_fyw($order_ids){
        $db = kernel::database();
        $obj_settlement_detail = app::get('business')->model('settlement_detail');

        foreach($order_ids as $settlement_no=>$value){
            // 结算逻辑调整--20170620
            $sql_settlement_detail = "SELECT '".$settlement_no."' as settlement_no, o.order_id, "
                ." a.company_no,a.company_name,i.goodsId as goods_id,i.goodsName as goods_name, "
                ." o.totalPointAmt  as order_money,i.purchasePrice*i.goodsNum as account, "
                ." o.final_amount  as sfsc_money, "
                ." i.goodsPointPrice as price,i.purchasePrice as cost,i.goodsNum as count "
                ." FROM sdb_b2c_orders_fyw o LEFT JOIN sdb_pam_account a ON o.member_id=a.account_id "
                ." LEFT JOIN sdb_b2c_order_fyw_items i ON o.order_id=i.order_id "
                ." WHERE o.order_id IN (".$value.")";

            $settlement_detail = $db->select($sql_settlement_detail);
            foreach($settlement_detail as $key=>$val){

                $settlement_detail[$key]['type']='福点支付';
                $settlement_detail[$key]['sfsc_money']=$val['sfsc_money'];
                $settlement_detail[$key]['third_money'] = 0;

            }
            foreach($settlement_detail as $key=>$val){
                if(!$obj_settlement_detail->save($val)){
                    return false;
                }
            }
        }
        return true;
    }

    //按店铺生成报表
    private function create_item($store_id,&$data,$filter){
        //退款单表
        $obj_refunds = app::get('ectools')->model('refunds');
        //订单钱款单据主表object
        $obj_bills = app::get('ectools')->model('order_bills');
        //订单主表object
        $obj_orders = app::get('b2c')->model('orders');
        //旧的结算逻辑
        //$stores = array($store_id);
        //获取可结算的结算单
        $balances = $obj_refunds->get_bills_by_filter($store_id,'balance',$filter);
        //获取可结算的退款单（未被用到）
        //$refunds = $obj_refunds->get_bills_by_filter($store_id,'refunds',$filter);
		//订单物品表
        $obj_order_items = app::get('b2c')->model('order_items');
		//商品表
        $obj_goods = app::get('b2c')->model('goods');
		//计算工具
		$objMath = kernel::single("ectools_math");
        
        //根据结算单生报表
        $data_B = array();
		$order_ids = array();
		if(!empty($balances)){
			foreach($balances as $balances_info){
				$rel_id = $obj_bills->dump(array('bill_id'=>$balances_info['refund_id']),'rel_id');
				//订单信息
                $order_info = $obj_orders->dump($rel_id['rel_id'],'*');
				$data_B = $this->create_data($data_B,$balances_info,$order_info);
				$order_ids[] = $order_info['order_id'];
				//修改结算单状态
				$obj_refunds->update(array('is_balance'=>'1','status'=>'succ'),array('refund_id'=>$balances_info['refund_id']));
			}
        }
		//统计所有订单商品
		$products = $obj_order_items->getList('*',array('order_id|in'=>$order_ids));
		$products_sum = array();
		foreach($products as $k => $v){
			$cost_index=$v['cost']*1000;
			$price_index=$v['price']*1000;
			if(isset($products_sum[$v['goods_id']][$cost_index][$price_index])){
				$products_sum[$v['goods_id']][$cost_index][$price_index]['count'] = $objMath->number_plus(array($products_sum[$v['goods_id']][$cost_index][$price_index]['count'],$v['sendnum']));
			}else{
				$products_sum[$v['goods_id']][$cost_index][$price_index] = array(
					'goods_name' => $v['name'],
					'bn' => $v['bn'],
					'count' => $v['sendnum'],
					'cost' => $v['cost'],
					'price' => $v['price'],
					'start' => $filter['start'],
					'end' => $filter['end'],
				);
			}
		}
		$data_B[$store_id]['products'] = $products_sum;
        //组装报表数组
        $data_B[$store_id]['info'] = $this->create_data_info($data_B,$store_id);
        $data_B[$store_id]['info']['start'] = $filter['start']?date('Y-m-d',$filter['start']):'';
        $data_B[$store_id]['info']['end'] = $filter['end']?date('Y-m-d',$filter['end']):'';
		$data['order_ids'][$data_B[$store_id]['info']['settlement_no']] = implode(',',$order_ids);
		if($data['data_B']){
			$data['data_B'] = $data['data_B']+$data_B;
		}else{
			$data['data_B'] = $data_B;
		}

        return $data;

    }

    //生成报表明细数组
    private function create_data(&$data,$balances_info,$order_info){
        //退款表
        $obj_refunds = app::get('ectools')->model('refunds');
        $point_money_value = app::get('b2c')->getConf('site.point_deductible_value');

        //获取订单商品的成本价总和
        $obj_order_items = app::get("b2c")->model("order_items");
        $order_items_array = $obj_order_items->getlist('cost,nums',array('order_id'=>$order_info['order_id']));
        $total_amount = 0;
        foreach($order_items_array as $k=>$v){
            $total_amount =  $total_amount + $v['cost']*$v['nums'];
        }

        $order_info['balances_info'] = $balances_info;
        //订单退款明细
        $order_info['refunds_info'] = $this->get_refunds($order_info['order_id']);
        //售前退款金额统计
        $order_info['refunds_info_before'] = $this->get_refunds($order_info['order_id'],'B');
        //组装报表明细数据
        $info = array();
        //获取每笔订单的 退款单数据
        if($order_info['refunds_info']){
            $info['is_refund'] = '1';
            $re_cut = 0;
            //成本价结算(暂时不考虑退款因素)
            $new_re_cut = $total_amount;
            $order_cut = 0;
            $refund_id = array();
            foreach($order_info['refunds_info'] as $k=>$v){
                //cur_money退款单金额  seller_amount 商家承担的金额
                $re_cut = $re_cut + $v['seller_amount'];
                //每笔订单的售后退款金额
                $order_cut = $order_cut+($v['cur_money']-$v['seller_amount']);

                $refund_id[] = $v['refund_id'];
                $obj_refunds->update(array('is_balance'=>'1'),array('refund_id'=>$v['refund_id']));
            }
            $refund_ids = implode(',',$refund_id);
            $info['re_cut'] = $re_cut;
            $info['refund_id'] = $refund_ids;
        }else{
            $info['is_refund'] = '0';
            $info['re_cut'] = 0;
            $info['refund_id'] = '无';
        }
        //order_money
        $info['order_id'] = $order_info['order_id'];
        //店铺结算价格(依照订单价格走的)
        //$info['account'] = $balances_info['cur_money']-$re_cut;

        //依照成本价格走的结算账单
        $info['account'] = $total_amount - $new_re_cut;
        //订单总金额
        $info['order_money'] = $order_info['total_amount'];
        $info['order_cut'] = $balances_info['profit']-$order_cut;
        //售后退款金额
        $info['order_recut'] = $order_cut;
        //店铺承担售后金额
        $info['total_recut'] = $order_cut+$re_cut;
        //平台承担售后金额
        $info['score_use'] = $order_info['score_u']*$point_money_value;
        $info['score_give'] = $balances_info['score_cost'];
		//订单运费 wang_bin
		$info['cost_freight'] = $order_info['shipping']['cost_shipping']; 
		//订单类型
		$info['order_kind'] = $order_info['order_kind'];
		
		
        //获取售前退款总金额
        if($order_info['refunds_info_before']){
            foreach($order_info['refunds_info_before'] as $k=>$v){
                $refund = $refund + $v['cur_money'];
            }
            $info['refund'] = $refund;
        }else{
            $info['refund'] = 0;
        }
        $data[$balances_info['store_id']]['orders'][$order_info['order_id']] = $info;
        return $data;
    }

    private function create_data_info($data,$store_id,$parent_settlement=''){
        $obj_settlement = app::get('business')->model('settlement');

        $info = array();

        $settlement_no = $obj_settlement->GetBillNo();

        $order_money = 0;
        $account = 0;
        $order_cut = 0;
        $score_use = 0;
        $score_give = 0;
        $re_cut = 0;
        $refund = 0;
        $order_recut = 0;
        $total_recut = 0;
		//订单运费  
		$order_freight = 0;
		
        if($parent_settlement){
            $data_info = $data['orders'];
        }else{
            $data_info = $data[$store_id]['orders'];
        }
		if($data_info){
			foreach($data_info as $k=>$v){
				$order_money = $order_money+$v['order_money'];
				$account = $account+$v['account'];
				$order_cut = $order_cut+$v['order_cut'];
				$score_use = $score_use+$v['score_use'];
				$score_give = $score_give+$v['score_give'];
				$re_cut = $re_cut+$v['re_cut'];
				$refund = $refund+$v['refund'];
				$order_recut = $order_recut+$v['order_recut'];
				$total_recut = $total_recut+$v['total_recut'];
				$order_freight = $order_freight +$v['cost_freight'];
			}
		}
        $info['settlement_no'] = $settlement_no;
        $info['store_id'] = $store_id;
        $info['create_time'] = time();
        $info['account'] = $account;
        $info['order_money'] = $order_money;
        $info['order_cut'] = $order_cut;
        $info['score_use'] = $score_use;
        $info['score_give'] = $score_give;
        $info['re_cut'] = $re_cut;
        $info['refund'] = $refund;
        $info['order_recut'] = $order_recut;
        $info['total_recut'] = $total_recut;
		$info['order_freight'] = $order_freight;
		
        return $info;

    }
    
    //保存报表
    private function create_settlement($data){
        $obj_settlement = app::get('business')->model('settlement');
        $obj_item = app::get('business')->model('settlement_item');
        $obj_product = app::get('business')->model('settlement_product');
        $obj_result = app::get('business')->model('settlement_result');
		$objMath = kernel::single("ectools_math");

        $result = $obj_settlement->save($data['info']);
        if($result){
            $flag = true;
			if($data['orders']){
				foreach($data['orders'] as $k=>$v){
					$v['order_id'] = $k;
					$v['settlement_no'] = $data['info']['settlement_no'];
					$v['item_type'] = 'order';
					$res = $obj_item->save($v);
					if(!$res){
						$flag = false;
					}
					$r = array(
						'settlement_no'=>$data['info']['settlement_no'],
						'order_id'=>$k,
						'account'=>$v['account']+$v['cost_freight'],
						'is_balance'=>'2',
					);
					$res = $obj_result->save($r);
					if(!$res){
						$flag = false;
					}
				}
			}
			if(is_array($data['refunds'])){
				foreach($data['refunds'] as $k1=>$v1){
					$v1['settlement_no'] = $data['info']['settlement_no'];
					$v1['item_type'] = 'refund';

					$res = $obj_item->save($v1);
					if(!$res){
						$flag = false;
					}
				}
			}
			if($data['products']){
				foreach($data['products'] as $id=>$cost){
					foreach($cost as $cost_key=>$price){
						foreach($price as $price_key=>$v){
							$v['goods_id'] = $id;
							$v['settlement_no'] = $data['info']['settlement_no'];
							$v['total_cost'] = $objMath->number_multiple(array($v['cost'],$v['count']));
							$res = $obj_product->save($v);
							if(!$res){
								$flag = false;
							}
						}
					}
				}
			}
            return $flag;
        }else{
            return false;
        }

    }

    //获取fyw订单的售后退款
    private function get_refunds_fyw($order_id){
        //获取可结算的结算单
        $ojb_fyw_orders = kernel::single('b2c_fuyuanwai_orders');
        $refund_orders =  $ojb_fyw_orders->get_refunds($order_id);
        return $refund_orders;
    }

    //获取订单的售后退款
    private function get_refunds($order_id,$type='A'){
        $obj = app::get('ectools')->model('order_bills');
        $sql .= "SELECT a.* FROM " . kernel::database()->prefix .
            "ectools_refunds as a LEFT JOIN " . kernel::database()->prefix .
            "ectools_order_bills as b on a.refund_id = b.bill_id ";
        if($type == 'A'){
            //售后单查找
            $sql .= " WHERE b.bill_type = 'refunds' AND b.rel_id = '" . $order_id .
            "' AND a.is_safeguard='2' ";
        }elseif($type == 'B'){
            //售前单查找
            $sql .= " WHERE b.bill_type = 'refunds' AND b.rel_id = '" . $order_id .
            "' AND a.is_safeguard='1' ";
        }else{
            //传入条件出错
            $sql .= " WHERE 0=1";
        }
        $bill = $obj->db->select($sql);
        
        return $bill;
    }

    //获取B店往期未结算的售后单
    private function get_b_refunds($store_id){
        $obj = app::get('ectools')->model('refunds');

        $sql .= "SELECT a.*,b.* FROM " . kernel::database()->prefix .
            "ectools_refunds as a LEFT JOIN " . kernel::database()->prefix .
            "ectools_order_bills as b on a.refund_id = b.bill_id  WHERE a.is_balance = '2' AND a.is_safeguard = '2' AND a.store_id=".$store_id;

        $bill = $obj->db->select($sql);
        
        return $bill;
    }

    function set_settlement_fyw(&$data,$time_filter){

        $obj_settlement = app::get('business')->model('settlement');

        //处理B店结算报表
        foreach($data['data_B'] as $key_b=>$val_b){
            //获取B店往期负结算报表
            $filter['filter_sql'] = ' store_id ='.$key_b.' and account < 0 and is_balance = 2';
            $pre_settlement = $obj_settlement->getList('settlement_no,account',$filter);
            //处理B店负结算报表
            $data['data_B'][$key_b]['info']['pre_settlement_cut'] = 0;
            if(!empty($pre_settlement)){
                $pre_settlements = array();
                foreach($pre_settlement as $pre_settlement_no){
                    $data['data_B'][$key_b]['info']['account'] += $pre_settlement_no['account'];
                    $data['data_B'][$key_b]['info']['pre_settlement_cut'] -= $pre_settlement_no['account'];
                    $pre_settlements[] = $pre_settlement_no['settlement_no'];
                    $obj_settlement->update(array('is_balance'=>'1'),array('settlement_no'=>$pre_settlement_no['settlement_no']));
                }
                $data['data_B'][$key_b]['info']['pre_settlement'] = implode(',',$pre_settlements);
            }

            $obj_fyw_orders = kernel::single('b2c_fuyuanwai_orders');
            $refunds_info =  $obj_fyw_orders->get_all_refunds($time_filter);

            $data['data_B'][$key_b]['info']['pre_refund_recut'] = 0;
            $data['data_B'][$key_b]['info']['b_pre_refund_recut'] = 0;
            $data['data_B'][$key_b]['info']['p_pre_refund_recut'] = 0;
            if(!empty($refunds_info)){
                foreach($refunds_info as $refund){
                    //根据售后退款并做明细记录
                    //处理B店数据
                    // 结算逻辑调整--20170628 // 结算逻辑调整--20170612
                    //$data['data_B'][$key_b]['info']['account'] -= $refund['totalPointAmt'];
                    //$refundAccount = $obj_fyw_orders->calRefundAccount($refund['order_id']);
                    //if (empty($refundAccount) || $refundAccount['refund_cost'] === 0){
                    //    $data['data_B'][$key_b]['info']['account'] -= $refund['totalPointAmt'];
                    //}else{
                    //    $data['data_B'][$key_b]['info']['account'] -= $refundAccount['refund_cost'];
                    //}

                    $data['data_B'][$key_b]['info']['order_cut'] -= ($refund['final_amount']-($refund['totalPointAmt']));
                    $data['data_B'][$key_b]['info']['pre_refund_recut'] += $refund['final_amount'];
                    $data['data_B'][$key_b]['info']['b_pre_refund_recut'] += $refund['totalPointAmt'];
                    $data['data_B'][$key_b]['info']['p_pre_refund_recut'] += ($refund['final_amount']-($refund['totalPointAmt']));

                    //组装往期售后item数组
                    //$obj_refund = app::get('ectools')->model('refunds');
                    $refundInfo = array();

                    $refundInfo['is_refund'] = 1;
                    $refundInfo['total_recut'] = $refund['final_amount'];
                    $refundInfo['re_cut'] = $refund['totalPointAmt'];
                    $refundInfo['order_recut'] = $refund['final_amount']-$refund['totalPointAmt'];
                    $refundInfo['refund_id'] = $refund['order_id'];
                    $refundInfo['order_id'] = $refund['oriTradeNo'];

                    $obj_fyw_orders->update_balance($refund['order_id']);

                    $data['data_B'][$key_b]['refunds'][$refund['oriTradeNo']] = $refundInfo;

                }
            }

        }
    }

    function set_settlement(&$data){
        $obj_settlement = app::get('business')->model('settlement');
        $obj_storemanger = app::get('business')->model('storemanger');
        $obj_orders = app::get('b2c')->model('orders');

        //处理B店结算报表
        foreach($data['data_B'] as $key_b=>$val_b){
            //获取B店往期负结算报表
            $filter['filter_sql'] = ' store_id ='.$key_b.' and account < 0 and is_balance = 2';
            $pre_settlement = $obj_settlement->getList('settlement_no,account',$filter);
            //处理B店负结算报表
            $data['data_B'][$key_b]['info']['pre_settlement_cut'] = 0;
            if(!empty($pre_settlement)){
                $pre_settlements = array();
                foreach($pre_settlement as $pre_settlement_no){
                    $data['data_B'][$key_b]['info']['account'] += $pre_settlement_no['account'];
                    $data['data_B'][$key_b]['info']['pre_settlement_cut'] -= $pre_settlement_no['account'];
                    $pre_settlements[] = $pre_settlement_no['settlement_no'];
                    $obj_settlement->update(array('is_balance'=>'1'),array('settlement_no'=>$pre_settlement_no['settlement_no']));
                }
                $data['data_B'][$key_b]['info']['pre_settlement'] = implode(',',$pre_settlements);
            }

            //处理B店铺往期售后申请订单
            $refunds_info = $this->get_b_refunds($key_b);
            $data['data_B'][$key_b]['info']['pre_refund_recut'] = 0;
            $data['data_B'][$key_b]['info']['b_pre_refund_recut'] = 0;
            $data['data_B'][$key_b]['info']['p_pre_refund_recut'] = 0;
            if(!empty($refunds_info)){
                foreach($refunds_info as $refund){
                    //根据售后退款并做明细记录
                    //处理B店数据
                    $this->make_data_B($refund,$data,$key_b);
                    $data['data_B'][$key_b]['refunds'][$refund['bill_id']] = $this->getRefundInfo($refund);
                }
            }
            
        }
    }

    //组装往期售后item数组
    private function getRefundInfo($refund){
        $obj_refund = app::get('ectools')->model('refunds');
        $refundInfo = array();

        $refundInfo['is_refund'] = 1;
        $refundInfo['total_recut'] = $refund['cur_money'];
        $refundInfo['re_cut'] = $refund['seller_amount'];
        $refundInfo['order_recut'] = $refund['cur_money']-$refund['seller_amount'];
        $refundInfo['refund_id'] = $refund['refund_id'];
        $refundInfo['order_id'] = $refund['rel_id'];

        $obj_refund->update(array('is_balance'=>'1'),array('refund_id'=>$refund['refund_id']));
        
        return $refundInfo;
    }

    private function make_data_B($refund,&$data,$key_b){
        $data['data_B'][$key_b]['info']['account'] -= $refund['seller_amount'];
        $data['data_B'][$key_b]['info']['order_cut'] -= ($refund['cur_money']-($refund['seller_amount']));
        $data['data_B'][$key_b]['info']['pre_refund_recut'] += $refund['cur_money'];
        $data['data_B'][$key_b]['info']['b_pre_refund_recut'] += $refund['seller_amount'];
        $data['data_B'][$key_b]['info']['p_pre_refund_recut'] += ($refund['cur_money']-($refund['seller_amount']));
    }

	public function get_ys_info($datainfo){
		if(empty($datainfo)) return false;

		$obj = app::get('business')->model('settlement');
        $obj_store = app::get('business')->model('storemanger');
		
		//生成唯一单据号
		$batch_id = $obj->get_batch_id();

		//构造数组
		$total_amount = 0;
		$detail = array();
		foreach($datainfo as $i_store_id=>$i_val){
			//去掉金额判断
			//if($i_val['account'] > 0){
				$detail_info = array();
				//$ysinfo = $obj_store->dump(array('store_id'=>$i_store_id),'store_idcardname,company_name,ysusercode,store_type');
				$ysinfo = $obj_store->dump(array('store_id'=>$i_store_id),'store_idcardname,company_name');
				$detail_info['settlement_no'] = $i_val['settlement_no'];

				$detail_info['amount'] = $i_val['account']*100;
				$detail_info['custName'] = $ysinfo['company_name'];

				//$detail_info['userCode'] = $ysinfo['ysusercode'];
				$total_amount += $detail_info['amount'];

				$detail[$i_store_id] = $detail_info;
				
				//更新结算报表字段
				$obj->update(array('batch_id'=>$batch_id,'is_balance'=>'1'),array('settlement_no'=>$i_val['settlement_no']));
			//}
		}

		$transferData = array();
		$transferData['total']['batch_id'] = $batch_id;
		$transferData['total']['total_num'] = count($datainfo);
		$transferData['total']['total_amount'] = $total_amount;
		$transferData['detail'] = $detail;

		return $transferData;

    }

	public function transfer($ys_info){
		$obj = app::get('business')->model('settlement');
		//调用银盛批量代付接口
		if($ys_info){
			foreach( kernel::servicelist('ysepay_tools') as $services ) {
				if ( is_object($services)) {
					if ( method_exists($services, 'transfer') ) {
						$result = $services->transfer($ys_info);
					}
				}
			}
		}
        //调用银盛批量代付接口
		if($result){
			if($result['Code'] == '0010'){
				if($result['BatchId'] != ''){
					$res = $obj->update(array('is_balance'=>'3'),array('batch_id'=>$result['BatchId']));
				}
			}
		}

		if($res){
			return true;
		}else{
			return false;
		}
	}

	public function settlement_balance(){
        $this->begin('index.php?app=business&ctl=admin_settlement&act='.$_GET['callback']);
		$settlement_no = array();
		if(isset($_GET['settlement_no'])){
			$settlement_no[] = $_GET['settlement_no'];
		}else{
			$settlement_no = $_POST['settlement_no'];
		}

        $obj_settlement = app::get('business')->model('settlement');

		$datainfo = array();
		foreach($settlement_no as $s_no){
			$settlement_info = $obj_settlement->dump($s_no,'settlement_no,store_id,account');
			$datainfo[$settlement_info['store_id']] = $settlement_info;
		}

        //调转账接口
		$ys_info = $this->get_ys_info($datainfo);

		//$res = $this->transfer($ys_info);//屏蔽银盛代付接口

        $this->end(true,'结算成功！(未接入支付方式)');
    }


	public function create_settlement_detail($order_ids){
		$db = kernel::database();
		$obj_settlement_detail = app::get('business')->model('settlement_detail');
		$ectools_pay = kernel::single('ectools_pay');

		foreach($order_ids as $settlement_no=>$value){
			if($value==''){$value='0';}
			$sql_settlement_detail = "SELECT '".$settlement_no."' as settlement_no,o.order_id,a.company_no,a.company_name,i.goods_id,i.`name` as goods_name,i.bn,(case o.order_kind when 'card' then o.order_kind else o.payment end) as type,i.cost,i.price,i.sendnum as count,o.cost_freight,o.total_amount as order_money,i.cost*i.sendnum as account,o.shipping,o.ship_name,o.ship_addr,o.ship_zip,(case o.ship_mobile when '' then o.ship_tel else o.ship_mobile end) as ship_mobile FROM sdb_b2c_orders o LEFT JOIN sdb_pam_account a ON o.member_id=a.account_id LEFT JOIN sdb_b2c_order_items i ON o.order_id=i.order_id WHERE o.order_id IN (".$value.")";
			$settlement_detail = $db->select($sql_settlement_detail);
			//获取卡券兑换订单的卡券名称
			$sql_cards = "SELECT cp.exchange_order_id,cp.card_name FROM sdb_b2c_orders o LEFT JOIN sdb_cardcoupons_cards_pass cp ON cp.exchange_order_id REGEXP o.order_id WHERE o.order_id IN (".$value.") AND o.order_kind='card' AND cp.card_name!=''";
			$cards = $db->select($sql_cards);
			$order_to_card = array();
			foreach($cards as $k=>$v){
				$exchange_order_id = unserialize($v['exchange_order_id']);
				foreach($exchange_order_id as $key=>$order_id){
					$order_to_card[$order_id] = $v['card_name'];
				}
			}
			foreach($settlement_detail as $key=>$val){
				$settlement_detail[$key]['sfsc_money'] = 0;
				$settlement_detail[$key]['third_money'] = 0;
				switch($val['type']){
					case 'sfscpay':
						$settlement_detail[$key]['type']='福点支付';
						$settlement_detail[$key]['sfsc_money']=$val['order_money'];
						break;
					case 'card':
						$settlement_detail[$key]['type']=$order_to_card[$val['order_id']]?'卡券（'.$order_to_card[$val['order_id']].'）':'卡券（未获取到卡券名称）';
						break;
					default:
						if(strpos($val['type'],',')!==false){
							$settlement_detail[$key]['type']='组合支付';
							$combine_payment = $ectools_pay->get_combine_payment($val['order_id']);
							foreach($combine_payment['sfsc'] as $k=>$v){
								$settlement_detail[$key]['sfsc_money'] += $v['cur_money'];
							}
							foreach($combine_payment['third'] as $k=>$v){
								$settlement_detail[$key]['third_money'] += $v['cur_money'];
							}
						}else{
							$settlement_detail[$key]['type']='第三方支付';
							$settlement_detail[$key]['third_money']=$val['order_money'];
						}
						break;
				}
			}
			foreach($settlement_detail as $key=>$val){
				if(!$obj_settlement_detail->save($val)){
					return false;
				}
			}
		}
		return true;
	}

	public function export_detail(){
        $charset = kernel::single('base_charset');
		$obj_store = app::get('business')->model('storemanger');
		$obj_settlement = app::get('business')->model('settlement');
		$obj_detail = app::get('business')->model('settlement_detail');
		$settlement_no = $_GET['settlement_no'];
		//获取店铺名称
		$settlement = $obj_settlement->dump($settlement_no,'*');
		$store = $obj_store->dump($settlement['store_id'],'*');
		$store_name = $store['store_name'];
		//导出内容
		$cols = 'order_id,goods_name,cost,price,count,cost_freight,order_money,account,0 as order_account,type,shipping,ship_name,ship_addr,ship_zip,ship_mobile';
		$title = '"店铺名称","订单号","商品名称","成本价","销售价","发货数量","订单运费","订单总额","结算金额","订单结算总额","订单类型","配送方式","收货人","收货地址","邮编","收货电话"';

		$list = $obj_detail->getList($cols,array('settlement_no'=>$settlement_no));
		if(empty($list))return false;
		//计算各订单的结算总金额
		$order_account = array();
		
		foreach($list as $k=>$v){
			if($current_order_id==$v['order_id']){
				$order_account[$current_order_id] += $v['account'];
			}else{
				$current_order_id = $v['order_id'];
				$order_account[$current_order_id] = $v['account'] + $v['cost_freight'];
			}
		}
		$order_id_array = array();
		foreach($list as $k=>$v){
			 if(!in_array($v['order_id'],$order_id_array)){
				$list[$k]['order_account'] = $order_account[$v['order_id']];
			 }else{
			    $list[$k]['order_account'] = 0;
				$list[$k]['order_money'] = 0; 
			 }
			 $order_id_array[] = $v['order_id'];
		}

		$content = array();
		foreach($list as $k=>$v){
			array_unshift($v,$store_name);
			foreach($v as $col_k=>$col_v){
				//长于8位的数字串加换行符
                if(strlen($col_v) > 8 && eregi("^[0-9]+$",$col_v)){
                    $v[$col_k] .= "\r";
                }
			}
			$content[] = '"'.implode('","',$v).'"';
		}
		
		$out = $title."\n".implode("\n",$content)."\n";

        header("Content-type: text/csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');  
        header('Expires:0');
        header('Pragma:public');
        header("Content-Disposition: attachment; filename=settlement_detail_".$settlement_no.".csv");
		//echo mb_convert_encoding($out, 'GBK', 'UTF-8');exit;
        echo $charset->utf2local($out,'zh');exit;
	}

	public function export_list(){
        $charset = kernel::single('base_charset');
		$obj_store = app::get('business')->model('storemanger');
		$obj_settlement = app::get('business')->model('settlement');
		$obj_result = app::get('business')->model('settlement_result');
		$settlement_no = $_GET['settlement_no'];
		//获取店铺名称
		$settlement = $obj_settlement->dump($settlement_no,'*');
		$store = $obj_store->dump($settlement['store_id'],'*');
		$store_name = $store['store_name'];
		//导出内容
		$cols = 'order_id,account';
		$title = '"店铺名称(store_name)","订单号(order_id)","实际结算金额(real_account)"';

		$list = $obj_result->getList($cols,array('settlement_no'=>$settlement_no));
		if(empty($list))return false;
		$content = array();
		foreach($list as $k=>$v){
			array_unshift($v,$store_name);
			foreach($v as $col_k=>$col_v){
				//长于8位的数字串加换行符
                if(strlen($col_v) > 8 && eregi("^[0-9]+$",$col_v)){
                    $v[$col_k] .= "\r";
                }
			}
			$content[] = '"'.implode('","',$v).'"';
		}
		
		$out = $title."\n".implode("\n",$content)."\n";

        header("Content-type: text/csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');  
        header('Expires:0');
        header('Pragma:public');
        header("Content-Disposition: attachment; filename=settlement_".$settlement_no.".csv");
		//echo mb_convert_encoding($out, 'GBK', 'UTF-8');exit;
        echo $charset->utf2local($out,'zh');exit;
	}

    function import_settlement(){
        $settlement_no = $_POST['settlement_no'];
		$obj_settlement = app::get('business')->model('settlement');
		$obj_result = app::get('business')->model('settlement_result');
		
		/*$check = $obj_settlement->dump($settlement_no,'*');
		if($check['is_balance']=='1'){
			echo '本批次已全部结算！';exit;
		}*/
		$settlement = $this->read_csv($_FILES['settlement_file'],$msg);
		if(!$settlement){
			echo $msg;exit;
		}
		//数据校验
		$order_id_arr=array();
		foreach($settlement as $key=>$row){
			if(!trim($row['order_id'])){
				echo '第'.$key.'条数据订单号为空！';exit;
			}
			if(!trim($row['real_account'])){
				echo '第'.$key.'条数据结算金额为空！';exit;
			}
			$order_id_arr[]=$row['order_id'];
			
		}
		if($order_id_arr){
			$old = $obj_result->getList('order_id',array('settlement_no'=>$settlement_no));
			$all_order = array();
			foreach($old as $k=>$v){
				$all_order[] = $v['order_id'];
			}
			//获取去掉重复数据的数组
			$unique_arr = array_unique($order_id_arr);
			//订单号重复性校验
			$repeat_arr = array_diff_assoc($order_id_arr, $unique_arr);
			if(is_array($repeat_arr) && !empty($repeat_arr)){
				$repeat_arr = array_unique($repeat_arr);
				echo '存在重复订单号：'.implode(',',$repeat_arr);exit;
			}
			//订单号存在性校验
			$not_exists = array();
			foreach($unique_arr as $key=>$val){
				if(!in_array($val,$all_order)){
					$not_exists[] = $val;
				}
			}
			if(count($not_exists)>=1){
				echo '订单号不存在：'.implode(',',$not_exists);exit;
			}
		}
		//数据修改
		$db = kernel::database();
		$transaction_status = $db->beginTransaction();
		if(!$obj_result->update(array('real_account'=>0,'is_balance'=>'2'),array('settlement_no'=>$settlement_no))){
			$db->rollback();
			echo '数据修改失败！';exit;
		}
		if(!$obj_settlement->update(array('is_balance'=>'2'),array('settlement_no'=>$settlement_no))){
			$db->rollback();
			echo '数据修改失败！';exit;
		}
		foreach($settlement as $key=>$row){
			$v = array(
				'real_account'=>$row['real_account'],
				'is_balance'=>'3',
			);
			if(!$obj_result->update($v,array('settlement_no'=>$settlement_no,'order_id'=>$row['order_id']))){
				$db->rollback();
				echo '数据修改失败！';exit;
			}
		}
		$db->commit($transaction_status);
		exit;
    }
	//读取csv文件
	function read_csv($file,&$pass_error){
		$file_type = substr(strrchr($file['name'],'.'),1);
		
		// 检查文件格式
	   if ($file_type != 'csv'){
			$pass_error= '文件格式不对,请重新上传!';
			return false;
		}
		$handle = fopen($file['tmp_name'],"r");
		/*if ($file_encoding != 'UTF-8'){
			echo '文件编码错误,请重新上传!';
			exit;
		}*/
		
		$row = 0;
		$post=array();
		$key=0;
		$m_key=array();
		setlocale(LC_CTYPE, "zh_CN.GBK");//防止以中文开头时读取的内容为空
		while ($data = fgetcsv($handle,1000,',')){
			$row++;
			if ($row == 1){
				foreach($data as $k_key=>$k_value){
					if($k_value){
						$result = array(); 
						preg_match_all("/(?:\()(.*)(?:\))/i",$k_value, $result);
						$k_value=explode(':',$k_value);
						$m_key[$k_key]=$result[1][0];
					}	
				}
			}else{
				$num = count($data);
				// 这里会依次输出每行当中每个单元格的数据
				foreach($data as $v_key=>$v_value){
					$v_value1 = iconv('GBK','UTF-8',$v_value);
					// 去除换行符
					$v_value1 = str_replace("\r","",$v_value1);
					$post[$key][$m_key[$v_key]]=$v_value1;
				}
			}
		   $key++;
		}
		
		fclose($handle);
		//$post = iconv('GB2312','UTF-8',$post);
		return $post;
	
	}

	//新结算功能（旧结算功能已撤去）-hy
    function do_settlement(){
		$settlement_no = $_POST['settlement_no'];
		$obj_settlement = app::get('business')->model('settlement');
		$obj_result = app::get('business')->model('settlement_result');

		//统计结算数据
		$result = $obj_result->getList('*',array('settlement_no'=>$settlement_no));
		$total = count($result);
		$count = 0;
		foreach($result as $k=>$v){
			if($v['is_balance']=='3'){
				$count = $count + 1;
			}
		}
		if($count==0){
			echo '无待结算数据！';exit;
		}
		$total_balance = $count<$total?'5':'1';

		$db = kernel::database();
		$transaction_status = $db->beginTransaction();
		if(!$obj_result->update(array('is_balance'=>'1'),array('settlement_no'=>$settlement_no,'is_balance'=>'3'))){
			$db->rollback();
			echo '数据修改失败！';exit;
		}
		if(!$obj_settlement->update(array('is_balance'=>$total_balance),array('settlement_no'=>$settlement_no))){
			$db->rollback();
			echo '数据修改失败！';exit;
		}
		$db->commit($transaction_status);
		exit;
	}

    function log_rsa($message,$arrInfo=null) {
        file_put_contents(DATA_DIR . '/api_rsa.log', date('Y-m-d H:i:s',time())."\n\r", FILE_APPEND);
        file_put_contents(DATA_DIR . '/api_rsa.log', $message."\n\r", FILE_APPEND);
        if ($arrInfo){
            file_put_contents(DATA_DIR . '/api_rsa.log', var_export($arrInfo,1)."\n\r", FILE_APPEND);
        }
    }
}
