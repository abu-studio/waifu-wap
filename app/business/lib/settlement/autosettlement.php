<?php

class business_settlement_autosettlement
{
	/**
     * 构造方法
     * @param object app
     * @return null
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
	
	/**
     * 自动结算
     * @return null
     */
	public function autosettlement(){
		$db = kernel::database();
        $transaction = $db->beginTransaction();

        $obj_refunds = app::get('ectools')->model('refunds');
        $obj = app::get('business')->model('settlement');
        $obj_store = app::get('business')->model('storemanger');

        $stores = $obj_store->store_getList();
        if(!empty($stores)){
            $balances = $obj_refunds->get_bills($stores,'balance');
        }

        $obj = app::get('business')->model('settlement');
        $obj_store = app::get('business')->model('storemanger');

 $data = array();
		if(!empty($stores) && !empty($balances)){
			foreach ($stores as $key => $value){
				//保存报表并打款
				$tp = $this->create_item($value,$data);
				if (empty($tp)){
					$db->rollback();
					exit;
				}
			}

			//上期负数结算报表结算以及店铺未结算的售后退款单
			$this->set_settlement($data);

			//生成报表
			foreach($data['data_B'] as $key_b=>$val_b){
				$result_b = $this->create_settlement($val_b);
				if(!$result_b){
					$db->rollback();
					exit;
				}
				$res = $obj_store->update(array('last_settlement_time'=>time()),array('store_id'=>$key_b));
				if (!$res){
					$db->rollback();
					exit;
				}
			}
			
			//自动结算程序
			/*$datainfo = array();
			foreach($data['data_B'] as $b_info_id=>$b_info_val){
				$datainfo[$b_info_id] = $b_info_val['info'];
			}
			foreach($data['data_C'] as $c_info_id=>$c_info_val){
				$datainfo[$c_info_id] = $c_info_val['info'];
			}

			$ys_info = $this->get_ys_info($datainfo);

			if($ys_info['total']['total_num'] > 0){
				$this->transfer($ys_info);
			}*/

			$db->commit($transaction);
		}
		
	}


	//按店铺生成报表
    private function create_item($store_id,&$data){        
        $obj_refunds = app::get('ectools')->model('refunds');
        $obj_bills = app::get('ectools')->model('order_bills');
        $obj_orders = app::get('b2c')->model('orders');
        
        $stores = array($store_id);
        $balances = $obj_refunds->get_bills($stores,'balance');
        $refunds = $obj_refunds->get_bills($stores,'refunds');
        
        //根据结算单生报表
        $data_B = array();
		if(!empty($balances)){
			foreach($balances as $balances_info){
				$rel_id = $obj_bills->dump(array('bill_id'=>$balances_info['refund_id']),'rel_id');
				$order_info = $obj_orders->dump($rel_id['rel_id'],'*');

				$data_B = $this->create_data($data_B,$balances_info,$order_info);
							
				//修改结算单状态
				$obj_refunds->update(array('is_balance'=>'1','status'=>'succ'),array('refund_id'=>$balances_info['refund_id']));
			}
		}
        
        //组装报表数组
        $data_B[$store_id]['info'] = $this->create_data_info($data_B,$store_id);

		if($data['data_B']){
			$data['data_B'] = $data['data_B']+$data_B;
		}else{
			$data['data_B'] = $data_B;
		}

        return $data;

    }

    //生成报表明细数组
    private function create_data(&$data,$balances_info,$order_info){
        $obj_refunds = app::get('ectools')->model('refunds');
        $point_money_value = app::get('b2c')->getConf('site.point_deductible_value');

        $order_info['balances_info'] = $balances_info;
        $order_info['refunds_info'] = $this->get_refunds($order_info['order_id']);
        //售前退款金额统计
        $order_info['refunds_info_before'] = $this->get_refunds($order_info['order_id'],'B');

        //组装报表明细数据
        $info = array();
        if($order_info['refunds_info']){
            $info['is_refund'] = '1';
            $re_cut = 0;
            $order_cut = 0;
            $refund_id = array();
            foreach($order_info['refunds_info'] as $k=>$v){

                $re_cut = $re_cut + $v['seller_amount'];
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
        $info['order_id'] = $order_info['order_id'];
        $info['account'] = $balances_info['cur_money']-$re_cut;
        $info['order_money'] = $order_info['total_amount'];
        $info['order_cut'] = $balances_info['profit']-$order_cut;
        $info['order_recut'] = $order_cut;
        $info['total_recut'] = $order_cut+$re_cut;
        $info['score_use'] = $order_info['score_u']*$point_money_value;
        $info['score_give'] = $balances_info['score_cost'];
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

        return $info;

    }
    
    //保存报表
    private function create_settlement($data){
        $obj_settlement = app::get('business')->model('settlement');
        $obj_item = app::get('business')->model('settlement_item');

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
            return $flag;
        }else{
            return false;
        }

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
			if($i_val['account'] > 0){
				$detail_info = array();
				$ysinfo = $obj_store->dump(array('store_id'=>$i_store_id),'store_idcardname,company_name,ysusercode,store_type');
				$detail_info['settlement_no'] = $i_val['settlement_no'];

				$detail_info['amount'] = $i_val['account']*100;
				$detail_info['custName'] = $ysinfo['company_name'];

				$detail_info['userCode'] = $ysinfo['ysusercode'];
				$total_amount += $detail_info['amount'];

				$detail[$i_store_id] = $detail_info;
				
				//更新结算报表字段
				$obj->update(array('batch_id'=>$batch_id),array('settlement_no'=>$i_val['settlement_no']));
			}
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

}
