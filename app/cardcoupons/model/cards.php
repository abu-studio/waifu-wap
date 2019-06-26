<?php
class cardcoupons_mdl_cards extends dbeav_model{
	var $has_many=array(
		'card_solution'=>'cards_solution:replace:card_id^card_id',
	);
	var $has_one=array(
		'card_service'=>'cards_service:append:service_id^card_service_id',
	);
	var $subSdf = array(
        'default' => array(
            'card_solution' => array('*'),
			'card_service'=>array('*'),
        ),

         'delete'=>array( 
			'card_solution' => array('*'),
		),
    );
	function __construct($app){
        parent::__construct($app);
        //使用meta系统进行存储
        $this->use_meta();
    }

    /**
     * 获取礼品卡详细信息 涵盖商品
     * @param $card_no
     * @return bool
     */
    function getcardinfo($card_no){

        if(empty($card_no)){
            return false;
        }
        //获取卡原型列表
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        //获取卡密列表
        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
        //获取卡原型和goods关联关系表
        $cards_solution = kernel::single("cardcoupons_mdl_cards_solution");
        $cards_pass_data = $cards_pass_object->getList("*",array('card_no' => $card_no,'status'=>array(0,1,2,3,4,9),'disabled'=>'false'));

        if($cards_pass_data[0]['to_time'] < time()){
            return false;
        }

        $cards_data = $cards_object->getList("*",array("card_id"=>$cards_pass_data[0]['card_id']));

        //获取卡原型style 信息
        $kv_obj=kernel::single('base_mdl_kvstore');
        $card_style=$kv_obj->getList('*',array('key'=>'cardcoupons.card.set'));
        if($cards_data[0]['style']['top_image']){
            $data['card_style']['top']['image'] = $cards_data[0]['style']['top_image'];
        }else{
            $data['card_style']['top']['image'] = $card_style[0]['value']['top']['image'];
        }
        if($cards_data[0]['style']['left_image']){
            $data['card_style']['left']['image'] = $cards_data[0]['style']['left_image'];
        }else{
            $data['card_style']['left']['image'] = $card_style[0]['value']['left']['image'];
        }
        if($cards_data[0]['style']['right_image']){
            $data['card_style']['right']['image'] = $cards_data[0]['style']['right_image'];
        }else{
            $data['card_style']['right']['image'] = $card_style[0]['value']['right']['image'];
        }

        $data['card_style']['top']['width'] = $card_style[0]['value']['top']['width'] ? $card_style[0]['value']['top']['width'] : "1200";
        $data['card_style']['top']['height'] = $card_style[0]['value']['top']['height'] ? $card_style[0]['value']['top']['height'] : "600";
        $data['card_style']['left']['width'] = $card_style[0]['value']['left']['width'] ? $card_style[0]['value']['left']['width'] : "1200";
        $data['card_style']['left']['height'] = $card_style[0]['value']['left']['height'] ? $card_style[0]['value']['left']['height'] : "600";
        $data['card_style']['right']['width'] = $card_style[0]['value']['right']['width'] ? $card_style[0]['value']['right']['width'] : "1200";
        $data['card_style']['right']['height'] = $card_style[0]['value']['right']['height'] ? $card_style[0]['value']['right']['height'] : "600";

        $data['card_style']['left']['bgcolor1'] = $cards_data[0]['style']['bgcolor1'] ? $cards_data[0]['style']['bgcolor1'] : kernel::base_url()."/themes/simple/images/kq_04.jpg";
        //$data['card_style']['right']['bgcolor2'] = $cards_data[0]['style']['bgcolor2'] ? $cards_data[0]['style']['bgcolor2'] : kernel::base_url()."/themes/simple/images/kq_04.jpg";

        $data['card_style']['message'] = $cards_data[0]['message'] ? htmlspecialchars_decode($cards_data[0]['message']) : htmlspecialchars_decode($card_style[0]['value']['message']);

        $goods_object = kernel::single("b2c_mdl_goods");

        if($cards_data[0]['type_id'] == "01"){
            //实物卡
            //获取卡关联信息
            $cards_solution_data = $cards_solution->getList("*",array("card_id"=>$cards_pass_data[0]['card_id']));
            if(!empty($cards_solution_data)){
                foreach($cards_solution_data as $k=>$v){
                    $services_id[] = $v['goods_id'];
                }

                $goods_data = $goods_object->getList("*",array("goods_id"=>$services_id));
                $cards_info = $cards_object->getList("goods_id,type_id",array("goods_id"=>$services_id,"type_id"=>array("01")));

                if(!empty($cards_info)){
                    foreach($cards_info as $k=>$v){
                        foreach($goods_data as $key=>$val){
                            if($v['goods_id'] == $val['goods_id']){
                                if($v['type_id'] == "01"){
                                    $goods_data[$key]['card_type'] = "01";
                                }
                            }
                        }
                    }
                }

                $new_goods_data = $this->quickSort($goods_data);
                $data['item'] = $new_goods_data;
                //正卡id
                $data["card_id"] = $cards_pass_data[0]['card_id'];
                $data["card_no"] = $card_no;
                $data['card_type'] = $cards_data[0]['type_id'];
                $data['name'] = $cards_data[0]['name'];
                //可领取数量 为卡原型规则 - 已领取数量
                $data['rules'] = $cards_data[0]['rules'] - $cards_pass_data[0]['exchange_num'];
				$data['change_way'] = $cards_data[0]['change_way'];
                $data['change_mode'] = $cards_data[0]['change_mode'];
                return $data;

            }
        }elseif($cards_data[0]['type_id'] == "02"){
        //体检卡  体检卡获取卡内的关联关系信息
            $cards_solution_data =  $cards_solution->getList("services_id",array('card_id'=>$cards_data[0]['card_id']));
            $physical_package_object = kernel::single("physical_mdl_package");
            foreach($cards_solution_data as $k=>$v){
                if($v['services_id'] != 0){
                    $physical_package_data = $physical_package_object->dump(array('package_id'=>$v['services_id']),"*");
                    $goods_data[] = $physical_package_data;
                }
            }

            //$goods_data = $goods_object->getList('*',array('goods_id'=>$cards_data[0]['goods_id']));
            //$goods_data =
            $data["card_no"] = $card_no;
            $data["card_id"] = $cards_pass_data[0]['card_id'];
            $new_goods_data = $this->quickSort($goods_data);
            $data['item'] = $new_goods_data;
            $data['card_type'] = $cards_data[0]['type_id'];
            $data['name'] = $cards_data[0]['name'];
            $data['rules'] = $cards_data[0]['rules'];
            return $data;


        }elseif($cards_data[0]['type_id'] == "03"){
        //复合卡
            $cards_solution_data = $cards_solution->getList("*",array("card_id"=>$cards_pass_data[0]['card_id']));
            if(!empty($cards_solution_data)){
                foreach($cards_solution_data as $k=>$v){
                    $services_id[] = $v['goods_id'];
                }
                //获取goods表中信息
                $goods_data = $goods_object->getList("*",array("goods_id"=>$services_id));
                //获取card原型信息
                $cards_info = $cards_object->getList("goods_id,type_id",array("goods_id"=>$services_id,"type_id"=>array("01","02")));

                if(!empty($cards_info)){
                    foreach($cards_info as $k=>$v){
                        foreach($goods_data as $key=>$val){
                            if($v['goods_id'] == $val['goods_id']){
                                /*
                                if($v['type_id'] == "02"){
                                    $goods_data[$key]['card_type'] = "02";
                                    $data['itemtype'][] = $goods_data[$key];
                                    unset($goods_data[$key]);
                                }
                                */
                                //if($v['type_id'] == "01"){
                                    $goods_data[$key]['card_type'] = "01";
                                //}
                            }
                        }
                    }
                }

                //获取体检卡信息
                $cards_solution_data =  $cards_solution->getList("services_id",array('card_id'=>$cards_data[0]['card_id']));
                $physical_package_object = kernel::single("physical_mdl_package");
                if(!empty($cards_solution_data)){
                    foreach($cards_solution_data as $k=>$v){
                        if($v['services_id'] != 0){
                            $physical_package_data = $physical_package_object->dump(array('package_id'=>$v['services_id']),"*");
                            $physical_package_data['card_type'] =  '02';
                            $data['itemtype'][] = $physical_package_data;
                        }

                    }
                }
            }
            $data["card_no"] = $card_no;
            $data["card_id"] = $cards_pass_data[0]['card_id'];
            $new_goods_data = $this->quickSort($goods_data);
            $data['item'] = $new_goods_data;
            $data['card_type'] = $cards_data[0]['type_id'];
            $data['name'] = $cards_data[0]['name'];
            $data['rules'] = $cards_data[0]['rules'];
            return $data;

        }
    }


    //排序算法
    private function quickSort($array){
        $len=count($array);
        for($i=1;$i<$len;$i++)
        {
            for($k=0;$k<$len-$i;$k++)
            {
                if($array[$k]['goods_order_down']<$array[$k+1]['goods_order_down'])
                {
                    $tmp=$array[$k+1];
                    $array[$k+1]=$array[$k];
                    $array[$k]=$tmp;
                }
            }
        }
        return $array;
    }


    /**
     * 根据会员ID 获取
     * @param $member_id
     * @return mixed
     */
    function get_card_member($member_id){
        $member_sdf = $this->db->selectrow("select p.login_name,m.member_id,m.name,m.sex,m.point,m.experience,m.email,m.member_lv_id,cur,advance,m.seller
        from sdb_b2c_members as m left join sdb_pam_account as p on m.member_id = p.account_id where m.member_id=".intval($member_id));
        $service = kernel::service('pam_account_login_name');
        if(is_object($service)){
            if(method_exists($service,'get_login_name')){
                $member_sdf['pam_account']['login_name'] = $service->get_login_name($member_sdf['pam_account']);
            }
        }
        if( !empty($member_sdf) ) {
            $member_info['member_id'] = $member_sdf['member_id'];
            $member_info['uname'] =  $member_sdf['login_name'];
            $member_info['name'] = $member_sdf['name'];
            $member_info['sex'] =  $member_sdf['sex'] == 1 ?'男':'女';
            $member_info['point'] = $member_sdf['point'];
            $member_info['usage_point'] = $member_info['point'];
            $obj_extend_point = kernel::service('b2c.member_extend_point_info');
            if ($obj_extend_point)
            {
                // 当前会员拥有的积分
                $obj_extend_point->get_real_point($this->member_info['member_id'], $this->member_info['point']);
                // 当前会员实际可以使用的积分
                $obj_extend_point->get_usage_point($this->member_info['member_id'], $this->member_info['usage_point']);
            }
            $member_info['experience'] = $member_sdf['experience'];
            $member_info['email'] = $member_sdf['email'];
            $member_info['member_lv'] = $member_sdf['member_lv_id'];
            $member_info['member_cur'] = $member_sdf['cur'];
            $member_info['advance'] = $member_sdf['advance'];

            //添加企业用户。
            if($member_sdf['seller']=='seller'){
                $member_info['seller'] ='seller';
            }
        }
        return $member_info;
    }


	function delete($so_filter,$type='internal'){
		$return=array();
		
		$obj_cards=kernel::single('cardcoupons_mdl_cards');
		$cards=$obj_cards->getList('card_id,goods_id',$so_filter);
		$goods_id=array();
		$objProduct=kernel::single('b2c_mdl_products');
		$obj_check_order = kernel::single('b2c_order_checkorder');
		foreach($cards as $key=>$value){
			$product_id = $objProduct->getList('product_id',array('goods_id'=>$value['goods_id']));
			foreach($product_id as $pkey =>$pval){
			   $orderStatus = $obj_check_order->check_order_product(array('goods_id'=>$value['goods_id'],'product_id'=>$pval['product_id']));
				if(!$orderStatus){
					$return['result']=false;
					$return['error']='该卡券有订单未处理';
					return $return;
				}
			}
			foreach( kernel::servicelist("b2c_allow_delete_goods") as $object ) {
				if( !method_exists($object,'is_delete') ) continue;
				if( !$object->is_delete($val['goods_id']) ) {
					$return['result']=false;
					$return['error']=$object->error_msg;
					return $return;
					return false;
				}
			}
			$data['goods_id'][$key]=$value['goods_id'];
			$data['card_id'][$key]=$value['card_id'];
		}
		$solutions=kernel::single('cardcoupons_mdl_cards_solution')->getList('goods_id',array('goods_id'=>$data['goods_id']));
		if($solutions){
			$return['result']=false;
			$return['error']='有卡券被设置为其他卡券的套餐';
			return $return;
		}
		$card_result=parent::delete(array('card_id'=>$data['card_id']),'delete');
		$goods_result=kernel::single('b2c_mdl_goods')->delete(array('goods_id'=>$data['goods_id']));
		$return['result']=true;
		$return['error']='';
		return $return;
		
	}




}