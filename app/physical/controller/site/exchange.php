<?php
class physical_ctl_site_exchange extends b2c_frontpage{

	function __construct($app){
        parent::__construct($app);
    }

	function verify_member(){
        kernel::single('base_session')->start();
        $this->member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        if(app::get('b2c')->member_id = $_SESSION['account'][pam_account::get_account_type(app::get('b2c')->app_id)]){
            $obj_member = app::get('b2c')->model('members');
            $data = $obj_member->select()->columns('member_id')->where('member_id = ?',app::get('b2c')->member_id)->instance()->fetch_one();
            if($data){
                //登陆受限检测
                $res = $this->loginlimit(app::get('b2c')->member_id,$redirect);
                if($res){
                    $this->redirect($redirect);
                }else{
                    return true;
                }
            }else{
                $this->redirect(array('app'=>'b2c', 'ctl'=>'site_passport', 'act'=>'error'));
            }
        }else{
            $this->redirect(array('app'=>'b2c', 'ctl'=>'site_passport', 'act'=>'error'));
        }

    }

	public function get_current_member()
    {
        if($this->member) return $this->member;
        $obj_members = app::get('b2c')->model('members');
		$this->member = $obj_members->get_current_member();
        //登陆受限检测
        if(is_array($this->member)){
            $minfo = $this->member;
            $mid = $minfo['member_id'];
            $res = $this->loginlimit($mid,$redirect);
            if($res){
                $this->redirect($redirect);
            }
        }
        return $this->member;
    }

	/*
	$type  0  用户登录  无卡号卡密  在线预约
		   1  用户登录  有卡号卡密  在线预约
		   2  匿名登录  有卡号卡密  在线预约
	$step  预约流程步骤
	*/
    function index($type=0,$step=1,$goods_id=0,$package_id=0,$card_no="",$card_pass=""){

        $obj_filter = kernel::single('b2c_site_filter');
        $type = $obj_filter->check_input($type);
        $step = $obj_filter->check_input($step);
        $goods_id = $obj_filter->check_input($goods_id);
        $package_id = $obj_filter->check_input($package_id);
        $card_no = $obj_filter->check_input($card_no);
        $card_pass = $obj_filter->check_input($card_pass);

		//判断登录
		switch($type){
			case 0:
				$this->verify_member();
				break;
			case 1:
				$this->verify_member();
				break;
			case 2:
				kernel::single('base_session')->start();
				if(!$_SESSION['account']['card']){
					$url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards', 'act'=>'index') );
					$this->splash('failed', $url, app::get('physical')->_('卡券用户登录超时！'));
				}
				break;
		}


		$obj_package = $this->app->model('package');
		if($type == 1 || $type == 2){
			if($card_no && $card_pass){
				//卡券信息
				$sql = "SELECT a.card_pass_id,a.order_id,b.goods_id FROM sdb_cardcoupons_cards_pass as a JOIN sdb_cardcoupons_cards as b ON b.card_id = a.card_id where a.card_no = '{$card_no}' AND a.card_pass = '".base64_decode($card_pass)."' AND a.status in('1','2')";
                $cards_pass_info = $obj_package -> db -> selectrow($sql);
				if(!$cards_pass_info || !$cards_pass_info['goods_id']){
					$this->splash('failed', 'back', app::get('physical')->_('无效卡券！'));
				}else{
					$card_pass_id = $cards_pass_info['card_pass_id'];
					$order_id = $cards_pass_info['order_id'];
					$goods_id = $cards_pass_info['goods_id'];
				}
			}else{
				$this->splash('failed', 'back', app::get('physical')->_('无卡号或卡密！'));
			}
		}

		if(!$goods_id){
            $this->splash('failed', 'back', app::get('physical')->_('无效体检产品！'));
        }

		//体检预约卡券 关联 预约套餐
		$sql1 = "SELECT a.package_id,a.package_name,a.project_ids FROM sdb_physical_package as a JOIN sdb_cardcoupons_cards_solution as b ON b.services_id = a.package_id LEFT JOIN sdb_cardcoupons_cards as c ON c.card_id = b.card_id WHERE c.goods_id = {$goods_id} ";
		if($package_id){
			$sql1 .= " AND a.package_id = {$package_id} ";
		}
		$packages = $obj_package -> db -> select($sql1);
		if(!$packages){
			$this->splash('failed', 'back', app::get('physical')->_('无预约套餐！'));
		}

		//选中套餐
		$package_info = $packages[0];
		$this->pagedata['package_info'] = $package_info;

		//体检项目
		$project_ids=$package_info['project_ids'];
		if(!$project_ids){
			$this->splash('failed', 'back', app::get('physical')->_('预约套餐无关联项目！'));
		}else{
			$sql2 = "SELECT a.project_id,a.project_name,a.introduction FROM sdb_physical_project as a WHERE a.project_id in ({$project_ids}) ";
			$projects = $obj_package -> db -> select($sql2);
			if(!$projects){
				$this->splash('failed', 'back', app::get('physical')->_('预约套餐无关联项目！'));
			}
		}
		


		$this->pagedata['type'] = $type;
		$this->pagedata['step'] = $step;
		$this->pagedata['goods_id'] = $goods_id;
		$this->pagedata['package_id'] = $package_id;
		$this->pagedata['card_no'] = $card_no;
		$this->pagedata['card_pass'] = $card_pass;

		//体检兑换流程临时信息
		$obj_exchange = &$this->app->model('exchange');

		$min_time = time() - 24*60*60;

		//条件
		$filter=array();
		switch($type){
			case 0:
				$filter = array("goods_id"=>$goods_id,"package_id"=>$package_info['package_id'],"member_id"=>$this->member_id,"order_id"=>"0",'update_time|than'=>$min_time);
				break;
			case 1:
				$filter = array("goods_id"=>$goods_id,"package_id"=>$package_info['package_id'],"member_id"=>$this->member_id,"card_pass_id"=>$card_pass_id,'update_time|than'=>$min_time);
				break;
			case 2:
				$filter = array("goods_id"=>$goods_id,"package_id"=>$package_info['package_id'],"card_pass_id"=>$card_pass_id,'update_time|than'=>$min_time);
				break;
		}

		$info = $obj_exchange->getRow("*",$filter);
		$this->pagedata['info'] = $info;

		if($step > 1 && empty($info['id']) && !$obj_filter->check_input($_POST['st'])){
			$url = $this->gen_url( array('app'=>'physical', 'ctl'=>'site_exchange', 'act'=>'index','args'=>array($type,1,$goods_id,$package_id,$card_no,$card_pass)) );
			$this->splash('failed', $url, app::get('physical')->_('无体检预约信息！'));
		}

		//保存或者修改的体检兑换流程临时信息
		if(empty($info['id'])){
			$data['goods_id'] = $goods_id;
			$data['package_id'] = $package_info['package_id'];
			$data['type'] = 1;
			$data['create_time'] = time();

			switch($type){
				case 0:
					$data['member_id'] = $this->member_id;
					break;
				case 1:
					$data['member_id'] = $this->member_id;
					$data['card_pass_id'] = $card_pass_id;
					$data['order_id'] = $order_id;
					break;
				case 2:
					$data['card_pass_id'] = $card_pass_id;
					$data['order_id'] = $order_id;
					break;
			}
		}else{
			$data['id']=intval($info['id']);
		}
		switch($step){
			case 1:
				//机构列表
				$obj_organization = &$this->app->model('organization');
				$list = $obj_organization->getList("organization_id,organization_name");
				foreach($list as $key=>$val){
					$organization_list[$val['organization_id']]=$val['organization_name'];
				}
				$this->pagedata['organization_list'] = $organization_list;

				$pageLimit = 10;
				$page = $obj_filter->check_input($_POST['page'])?$obj_filter->check_input($_POST['page']):1;
				$this->pagedata['page'] = $page;

				$where=" AND b.package_id = '".$package_info['package_id']."'";
				//地区
                $area_tmp = $obj_filter->check_input($_POST['area']);
				$area_arr = explode(":",$area_tmp);
				$area = $area_arr[1];
				if($area){
					$where .= " AND a.area like '%{$area}%' ";
				}

				//机构
				$organization_id = $obj_filter->check_input($_POST['organization_id']);
				if($organization_id){
					$where .= " AND a.organization_id = {$organization_id} ";
				}

				//体检门店列表
				$sql = "SELECT a.store_id,a.store_name,a.image,a.area,a.address,a.phone,a.open,a.close,a.weekday FROM sdb_physical_store as a LEFT JOIN sdb_physical_store_package_attach as b ON b.store_id = a.store_id where a.type = 1";
				if($where){
					$sql .= " {$where}";
				}
				$sql .= " GROUP BY a.store_id";
				$sql .= " LIMIT ".$pageLimit*($page-1).",{$pageLimit}";
				$store_list = $obj_package -> db -> select($sql);

				if(!$store_list){
					$this->splash('failed', 'back', app::get('physical')->_('套餐无关联体检门店！'));
				}

				$week=array(
					"1"=>"周一",
					"2"=>"周二",
					"3"=>"周三",
					"4"=>"周四",
					"5"=>"周五",
					"6"=>"周六",
					"0"=>"周日",
				);
				$week_arr=array("1","2","3","4","5","6","0");
				foreach($store_list as $key => $val){
					$weekday_html="";
					$weekday_arr = explode(",",$val['weekday']);
					foreach($weekday_arr as $v){
						$weekday_html .= ",".$week[$v];
					}
					$store_list[$key]["weekday_html"] = trim($weekday_html,",");
				}

				$this->pagedata['store_list'] = $store_list;
				
				//体检门店总数
				$sql = "SELECT a.store_id FROM sdb_physical_store as a LEFT JOIN sdb_physical_store_package_attach as b ON b.store_id = a.store_id where a.type = 1";
				if($where){
					$sql .= " {$where}";
				}
				$sql .= " GROUP BY a.store_id";
				$row = $obj_package -> db -> select($sql);
				$count = count($row);

				$this->pagedata['count'] = $count;

				$tmp =time();

				$this->pagedata['pager'] = array(
					'current'=>$page,
					'total'=>ceil($count/$pageLimit),
					'link'=>  "javascript:void(0);",
					'token'=>$tmp,
					);
				break;
			case 2:
				if($obj_filter->check_input($_POST['st'])){
					$data['store_id'] = $obj_filter->check_input($_POST['store_id']);
					$data['store_name'] = $obj_filter->check_input($_POST['store_name']);
					$data['update_time'] = time();

					$rs= $obj_exchange->save($data);
					if(!$rs){
						$this->splash('failed', 'back',app::get('physical')->_('选择门店信息保存失败'));
					}
				}else{
					if(!$info['store_id'] || !$info['store_name']){
						$url = $this->gen_url( array('app'=>'physical', 'ctl'=>'site_exchange', 'act'=>'index','args'=>array($type,1,$goods_id,$package_id,$card_no,$card_pass)) );
						$this->splash('failed', $url,app::get('physical')->_('请选择门店信息'));
					}
				}


				
				$this->pagedata['projects'] = $projects;
				break;
			case 3:
				//预约时间信息
				$order_times = $info['order_times'];
				if($order_times){
					$order_time_arr = explode(",",$order_times);
				}else{
					$order_time_arr =array();
				}
				$this->pagedata['order_time_arr'] = $order_time_arr;
				break;
			case 4:
				if($obj_filter->check_input($_POST['st'])){
					$order_times = "";
					if($obj_filter->check_input($_POST['best_time'])){
						$order_times = $obj_filter->check_input($_POST['best_time']);
					}
                    $order_time_tmp = $obj_filter->check_input($_POST['order_time']);
					foreach($order_time_tmp as $val){
						if($val != $obj_filter->check_input($_POST['best_time'])){
							$order_times .= ",".$val;
						}
					}
					
					$data['order_times'] = trim($order_times,",");
					$data['update_time'] = time();

					$rs= $obj_exchange->save($data);
					if(!$rs){
						$this->splash('failed', 'back',app::get('physical')->_('选择时间信息保存失败'));
					}else{
						$info['order_times'] = trim($order_times,",");
					}
				}else{
					if(!$info['order_times']){
						$url = $this->gen_url( array('app'=>'physical', 'ctl'=>'site_exchange', 'act'=>'index','args'=>array($type,3,$goods_id,$package_id,$card_no,$card_pass)) );
						$this->splash('failed', $url,app::get('physical')->_('请选择时间信息'));
					}
				}

				//门店信息
				$obj_store = $this->app->model('store');
				$store_info = $obj_store -> getInfobyid($info['store_id']);
				$this->pagedata['store_info'] = $store_info;

				//预约时间信息
				$order_times = $info['order_times'];
				if($order_times){
					$order_time_arr = explode(",",$order_times);
				}else{
					$order_time_arr =array();
				}
				$this->pagedata['order_time_arr'] = $order_time_arr;
				break;
			case 5:
				if($obj_filter->check_input($_POST['st'])){
					$data['person_name'] = trim($obj_filter->check_input($_POST['person_name']));
					$data['sex'] = $obj_filter->check_input($_POST['sex']);
					$data['marry'] = $obj_filter->check_input($_POST['marry']);
					$data['age'] = $obj_filter->check_input($_POST['age']);
					$data['c_type'] = $obj_filter->check_input($_POST['c_type']);
					$data['c_no'] = trim($obj_filter->check_input($_POST['c_no']));
					$data['mobile'] = $obj_filter->check_input($_POST['mobile']);
					$data['address'] = trim($obj_filter->check_input($_POST['address']));
					$data['update_time'] = time();

					$rs= $obj_exchange->save($data);
					if(!$rs){
						$this->splash('failed', 'back',app::get('physical')->_('体检预约信息保存失败'));
					}else{
						$this->pagedata['info']['person_name'] = $data['person_name'];
						$this->pagedata['info']['sex'] = $data['sex'];
						$this->pagedata['info']['marry'] = $data['marry'];
						$this->pagedata['info']['age'] = $data['age'];
						$this->pagedata['info']['c_type'] = $data['c_type'];
						$this->pagedata['info']['c_no'] = $data['c_no'];
						$this->pagedata['info']['mobile'] = $data['mobile'];
						$this->pagedata['info']['address'] = $data['address'];
					}
				}else{
					if(!$this->pagedata['info']['person_name'] || !$this->pagedata['info']['sex'] || !$this->pagedata['info']['marry'] || !$this->pagedata['info']['age'] || !$this->pagedata['info']['c_type'] || !$this->pagedata['info']['c_no'] || !$this->pagedata['info']['mobile']){
						$url = $this->gen_url( array('app'=>'physical', 'ctl'=>'site_exchange', 'act'=>'index','args'=>array($type,4,$goods_id,$package_id,$card_no,$card_pass)) );
						$this->splash('failed', $url,app::get('physical')->_('请填写体检预约信息'));
					}
				}

				
				
				

				//门店信息
				$obj_store = $this->app->model('store');
				$store_info = $obj_store -> getInfobyid($info['store_id']);
				$this->pagedata['store_info'] = $store_info;

				//预约时间信息
				$order_times = $info['order_times'];
				if($order_times){
					$order_time_arr = explode(",",$order_times);
				}else{
					$order_time_arr =array();
				}
				$this->pagedata['order_time_arr'] = $order_time_arr;

				if($type == 0){
					//产品信息
					$obj_products = app::get('b2c')->model('products');
					$products_info = $obj_products->dump(array('goods_id' =>$goods_id),'product_id','default');

					$this->pagedata['member_id'] = $this->member_id;

					$this->_common($goods_id,$products_info['product_id']);
				}
				break;
			case 6:
				if(!$obj_filter->check_input($_POST['st'])){
					$this->splash('failed', 'back',app::get('physical')->_('体检预约单提交失败'));
					exit;
				}
				if($type == 0){
					//产品信息
					$obj_products = app::get('b2c')->model('products');
					$products_info = $obj_products->dump(array('goods_id' =>$goods_id),'product_id','default');
					//生成订单ID
					$order_id = $this->creat_order($goods_id,$products_info['product_id'],$info['id']);

					$this->pagedata['order_id'] = $order_id;
				}else{
					//预约单ID
					$id = $this->create_physical_order($info,$card_pass_id,$type,$card_no);
					if(!$id){
						$this->splash('failed', 'back',app::get('physical')->_('体检预约单生成失败！'));
						exit;
					}
				}

				break;
		}
		$this->pagedata['Parea'] = $obj_filter->check_input($_POST['area']);
		$this->pagedata['res_url']=$this->app->res_url;
		if($type == 2){
			$this->set_tmpl("exchange");
		}else{
			$this->set_tmpl("exchange1");
		}
		$this->page('site/exchange/step'.$step.'.html');
    }
	
	//生成预约单
	function create_physical_order($exchange_info,$card_pass_id,$type,$card_no){
        $obj_filter = kernel::single('b2c_site_filter');

        $exchange_info = $obj_filter->check_input($exchange_info);
        $card_pass_id = $obj_filter->check_input($card_pass_id);
        $type = $obj_filter->check_input($type);
        $card_no = $obj_filter->check_input($card_no);

		$obj_orders = $this->app->model('orders');
		$time = time();
		//预约单生成
		$order_data = $exchange_info;
		unset($order_data['id']);
		$order_data['status'] = 2;
		$order_data['create_time'] = $time;
		$order_data['update_time'] = $time;

		//事物处理开始
		$db = kernel::database();
		$transaction_status = $db->beginTransaction();

		$id = $obj_orders->insert($order_data);
		if($id){
			//更新卡密信息
			$sql1 = "UPDATE sdb_cardcoupons_cards_pass SET exchange_no = '{$id}',exchange_prefix = 'physical',use_time ='{$time}',status = '3' where card_pass_id = '{$card_pass_id}' ";
			if( $obj_orders->db->exec($sql1) ){
				//删除体检兑换流程临时信息
				$obj_exchange = $this->app->model('exchange');
				if( $obj_exchange->delete( array('id'=>$exchange_info['id']) ) ){
					if($type == 1){
						$_sjson = array(
							'METHOD'=>'updateDocItemStatus',
							'CARD_NUMBER'=>$card_no,
							'REC_ORDER_ID'=>$id,
							'REC_STATUS'=>'I01102'
						);
						$post_data = array('serviceNo'=>'DocumentItemService',"inputParam"=>json_encode($_sjson));
						$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);

						$db->commit($transaction_status);
					}else{
						$db->commit($transaction_status);
					}
					return $id;
				}else{
					$db->rollback();
					return false;
				}
			}else{
				$db->rollback();
				return false;
			}
		}else{
			$db->rollback();
			return false;
		}
	}


	function creat_order($goods_id,$product_id,$exchange_id){
        $obj_filter = kernel::single('b2c_site_filter');
		$arrMember = $this->get_current_member();

        $post_cardinfo = Array (
            'goods' => Array (
				'cards_pass_type' => 'virtual',
                'num' => '1',
                'goods_id' => $goods_id,
                'pmt_id' =>'',
                'product_id' =>$product_id
            ),
            '0' => 'goods'
        );
        // 购物车数据信息
        kernel::single('fastbuy_cart_fastbuy_goods')->get_fastbuy_arr(
            $post_cardinfo,
            array(),
            $allCart
        );

        //事物处理开始
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();


        //取得分单信息。
        $split_order=unserialize($obj_filter->check_input($_POST['split_order']));
        //
        $temp_split_order=kernel::single('b2c_cart_object_split')->split_order(kernel::single("b2c_ctl_site_cart"),$this->pagedata['area_id'],$allCart);

        foreach($temp_split_order as $store_id=>$sgoods){
            foreach($sgoods['slips'] as $order_sp=>$sorder){
                //post数据
                $order = &kernel::single("b2c_mdl_orders");
                //分单后重新构建购物车数据.
                $aCart=$this->get_split_cart($allCart,$sorder,$obj_filter->check_input($_POST),$obj_filter->check_input($_POST['shipping'][$store_id][$order_sp]),$store_id);

                $postData=$this->get_post_cart($aCart,$sorder,$obj_filter->check_input($_POST),$obj_filter->check_input($_POST['shipping'][$store_id][$order_sp]));

                //取得购物车数据对应的订单数据。
                $order_data=$this->get_order_data($aCart,$postData,$store_id,$msg,$arrMember);

                $obj_order_create = kernel::single("b2c_order_create");
                $order_id=$order_data['order_id'];
                $result = $obj_order_create->save($order_data, $msg);

                if ($result)
                {
                    // 发票高级配置埋点
                    foreach( kernel::servicelist('invoice_setting') as $services ) {
                        if ( is_object($services) ) {
                            if ( method_exists($services, 'saveInvoiceData') ) {
                                $services->saveInvoiceData($postData['order_id'],$postData['payment']);
                            }
                        }
                    }
                }

                // 取到日志模块
                if ($arrMember['member_id'])
                {
                    $obj_members = kernel::single("b2c_mdl_members");
                    $arrPams = $obj_members->dump($arrMember['member_id'], '*', array(':account@pam' => array('*')));
                }


                // remark create
                $obj_order_create = kernel::single("b2c_order_remark");
                $arr_remark = array(
                    'order_bn' => $order_id,
                    'mark_text' => $postData['memo'],
                    'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                    'mark_type' => 'b0',
                );


                $log_text = "";
                if ($result)
                {
                    $log_text[] = array(
                        'txt_key'=>'订单创建成功！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }
                else
                {
                    $log_text[] = array(
                        'txt_key'=>'订单创建失败！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }
                $orderLog = kernel::single("b2c_mdl_order_log");
                $sdf_order_log = array(
                    'rel_id' => $order_id,
                    'op_id' => $arrMember['member_id'],
                    'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'creates',
                    'result' => 'SUCCESS',
                    'log_text' => $log_text,
                );

                $log_id = $orderLog->save($sdf_order_log);

                if ($result)
                {
                    foreach(kernel::servicelist('b2c_save_post_om') as $object)
                    {
                        $object->set_arr($order_id, 'order');
                    }

                    $cart_model = &kernel::single("b2c_mdl_cart_objects");

                    // 订单成功后清除购物车的的信息
                    if($obj_filter->check_input($_POST['fastbuy'])){
                        unset($_SESSION['S[Cart_Fastbuy]']);//立即购买后清空session,普通购买清空购物车
                    }else{
                        //由于删除购物车限制参数是obj_ident所以此处循环删除
                        foreach($sorder['object']['goods']['obj_ident'] as $obj_ident_key=>$obj_ident_val){
                            $cart_model->remove_object('goods',$obj_ident_val);
                        }
                    }

                    // 得到物流公司名称
                    if ($order_data['order_objects'])
                    {
                        $itemNum = 0;
                        $good_id = "";
                        $goods_name = "";
                        foreach ($order_data['order_objects'] as $arr_objects)
                        {
                            if ($arr_objects['order_items'])
                            {
                                if ($arr_objects['obj_type'] == 'goods')
                                {
                                    $obj_goods = kernel::single("b2c_mdl_goods");
                                    $good_id = $arr_objects['order_items'][0]['goods_id'];
                                    $obj_goods->updateRank($good_id, 'buy_count',$arr_objects['order_items'][0]['quantity']);
                                    $arr_goods = $obj_goods->dump($good_id);
                                }

                                foreach ($arr_objects['order_items'] as $arr_items)
                                {
                                    $itemNum = kernel::single("ectools_math")->number_plus(array($itemNum, $arr_items['quantity']));
                                    if ($arr_objects['obj_type'] == 'goods')
                                    {
                                        if ($arr_items['item_type'] == 'product')
                                            $goods_name .= $arr_items['name'] . ($arr_items['products']['spec_info'] ? '(' . $arr_items['products']['spec_info'] . ')' : '') . '(' . $arr_items['quantity'] . ')';
                                    }
                                }
                            }
                        }
                        $obj_dlytype = kernel::single("b2c_mdl_dlytype");
                        $arr_dlytype = $obj_dlytype->dump($order_data['shipping']['shipping_id'], 'dt_name');
                        $arr_updates = array(
                            'order_id' => $order_id,
                            'total_amount' => $order_data['total_amount'],
                            'shipping_id' => $arr_dlytype['dt_name'],
                            'ship_mobile' => $order_data['consignee']['mobile'],
                            'ship_tel' => $order_data['consignee']['telephone'],
                            'ship_addr' => $order_data['consignee']['addr'],
                            'ship_email' => $order_data['consignee']['email'] ? $order_data['consignee']['email'] : '',
                            'ship_zip' => $order_data['consignee']['zip'],
                            'ship_name' => $order_data['consignee']['name'],
                            'member_id' => $order_data['member_id'] ? $order_data['member_id'] : 0,
                            'uname' => (!$order_data['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                            'itemnum' => count($order_data['order_objects']),
                            'goods_id' => $good_id,
                            'goods_url' => kernel::base_url(1).kernel::url_prefix().$this->gen_url(array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$good_id)),
                            'thumbnail_pic' => base_storager::image_path($arr_goods['image_default_id']),
                            'goods_name' => $goods_name,
                            'ship_status' => '',
                            'pay_status' => 'Nopay',
                            'is_frontend' => true,
                        );
                        $order->fireEvent('create', $arr_updates, $order_data['member_id']);
                    }



					/** 订单  关联  体检兑换流程临时信息 start**/
					$obj_exchange = kernel::single("physical_mdl_exchange");
					$exchange_data=array(
						'id' => $exchange_id,
						'order_id' => $order_id,
						'update_time' => time(),
					);
					$rs= $obj_exchange->save($exchange_data);
					if($rs){
						$db->commit($transaction_status);
					}else{
						$db->rollback();
					}
					/** 订单  关联  体检兑换流程临时信息 end**/



                    //$db->commit($transaction_status);


                    /** 订单创建结束后执行的方法 **/
                    $odr_create_service = kernel::servicelist('b2c_order.create');
                    $arr_order_create_after = array();
                    if ($odr_create_service)
                    {
                        foreach ($odr_create_service as $odr_ser)
                        {
                            if(!is_object($odr_ser)) continue;

                            if( method_exists($odr_ser,'get_order') )
                                $index = $odr_ser->get_order();
                            else $index = 10;

                            while(true) {
                                if( !isset($arr_order_create_after[$index]) )break;
                                $index++;
                            }
                            $arr_order_create_after[$index] = $odr_ser;
                        }
                    }
                    ksort($arr_order_create_after);
                    if ($arr_order_create_after)
                    {
                        foreach ($arr_order_create_after as $obj)
                        {
                            $obj->generate($order_data);
                        }
                    }
                    /** end **/
                }
                else
                {
                    $db->rollback();
                }

                if ($result)
                {
                    $order_num = $order->count(array('member_id' => $order_data['member_id']));
                    $obj_mem = kernel::single("b2c_mdl_members");
                    $obj_mem->update(array('order_num'=>$order_num), array('member_id'=>$order_data['member_id']));

                    // 与中心交互
                    $is_need_rpc = false;
                    $obj_rpc_obj_rpc_request_service = kernel::servicelist('b2c.rpc_notify_request');
                    foreach ($obj_rpc_obj_rpc_request_service as $obj)
                    {
                        if ($obj && method_exists($obj, 'rpc_judge_send'))
                        {
                            if ($obj instanceof b2c_api_rpc_notify_interface)
                                $is_need_rpc = $obj->rpc_judge_send($order_data);
                        }

                        if ($is_need_rpc) break;
                    }

                    if ($is_need_rpc)
                    {
                        //新的版本控制api
                        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                        $obj_apiv->rpc_caller_request($order_data, 'ordercreate');
                    }

                    $flag = true;
                    $aOrders[] = $order_id;

                }else{
                    $flag = false;
                }
            }
        }


        if($flag){
            $orderStr = base64_encode(implode('|', $aOrders));
            return $aOrders[0];
        }else{
            $this->end(false, $msg, $this->gen_url(array('app'=>'b2c','ctl'=>'site_cart','act'=>'checkout')),true,true);
        }

    }




    //购物车
    function _common($goods_id,$product_id){

        $post_cardinfo = Array (
            'goods' => Array (
				'cards_pass_type' => 'virtual',
				'num' => '1',
				'goods_id' => $goods_id,
				'pmt_id' =>'',
				'product_id' =>$product_id
            ),
            '0' => 'goods'
        );
        // 购物车数据信息
        kernel::single('fastbuy_cart_fastbuy_goods')->get_fastbuy_arr(
            $post_cardinfo,
            array(),
            $aCart
        );

        //判断购物车有没有自己的商品
        foreach($aCart['object']['goods'] as $k=>$v){
            $check_objects = kernel::servicelist('business_check_goods_isMy');
            $sign = true;
            if($check_objects){
                foreach($check_objects as $check_object){
                    $check_object->check_goods_isMy($v['params']['goods_id'],$msg,$sign);
                }
                if(!$sign){
                    //$this->end(false,'商品数据异常');
                    $this->splash('failed', $this->gen_url( array('app'=>'b2c','act'=>'index','ctl'=>'site_product','arg1'=>$aCart['object']['goods'][0]['params']['goods_id']) ) , app::get('b2c')->_('不能购买自己的商品'));
                }
            }
        }

        $this->pagedata['aCart'] = $aCart;

        if( $this->show_gotocart_button ) $this->pagedata['show_gotocart_button'] = 'true';

        if( $this->ajax_update === true ) {
            foreach(kernel::servicelist('b2c_cart_object_apps') as $object) {
                if( !is_object($object) ) continue;
                //应该判断是否实现了接口
                if( !method_exists( $object,'get_update_num' ) ) continue;
                if( !method_exists( $object,'get_type' ) ) continue;

                $this->pagedata['edit_ajax_data'] = $object->get_update_num( $aCart['object'][$object->get_type()],$this->update_obj_ident );
                if( $this->pagedata['edit_ajax_data'] ) {
                    $this->pagedata['edit_ajax_data'] = json_encode( $this->pagedata['edit_ajax_data'] );
                    if( $object->get_type()=='goods' ) {
                        $this->pagedata['update_cart_type_godos'] = true;
                        if( !method_exists( $object,'get_error_html' ) ) continue;
                        $this->pagedata['error_msg'] = $object->get_error_html( $aCart['object']['goods'],$this->update_obj_ident );
                    }
                    break;
                }
            }
        }





        $obj_member_addrs = kernel::single("b2c_mdl_member_addrs");

        $obj_dltype = kernel::single("b2c_mdl_dlytype");
        $addr = array();
        $member_point = 0;
        $shipping_method = '';
        $shipping_id = 0;
        $arr_shipping_method = array();
        $payment_method = 0;
        $def_addr = 0;
        $arr_def_addr = array();
        $str_def_currency = $arrMember['member_cur'] ? $arrMember['member_cur'] : "";


        // 会员没有默认的地址 默认的货币
        if ((!$def_addr || !$str_def_currency) && !$arrMember['member_id'])
        {
            if ($_COOKIE['purchase']['addon'])
            {
                $arr_addon = unserialize(stripslashes($_COOKIE['purchase']['addon']));
                if (!$def_addr)
                {
                    if (isset($arr_addon['member']['ship_area']) && $arr_addon['member']['ship_area'])
                    {
                        $def_addr = 0;
                        $arr_area = explode(':', $arr_addon['member']['ship_area']);
                        $def_area = $arr_area[2];
                        $arr_def_addr = $arr_addon['member'];
                        $arr_def_addr['addr_region'] = $arr_addon['member']['ship_area'];
                        $arr_def_addr['addr'] = $arr_addon['member']['ship_addr'];
                        $arr_def_addr['zip'] = $arr_addon['member']['ship_zip'] ? $arr_addon['member']['ship_zip'] : '';
                        $arr_def_addr['name'] = $arr_addon['member']['ship_name'];
                        $arr_def_addr['email'] = $arr_addon['member']['ship_email'];
                        $arr_def_addr['mobile'] = $arr_addon['member']['ship_mobile'];
                        $arr_def_addr['tel'] = $arr_addon['member']['ship_tel'] ? $arr_addon['member']['ship_tel'] : '';
                        $arr_def_addr['day'] = $arr_addon['member']['day'] ? $arr_addon['member']['day'] : '';
                        $arr_def_addr['specal_day'] = $arr_addon['member']['specal_day'] ? $arr_addon['member']['specal_day'] : '';
                        $arr_def_addr['time'] = $arr_addon['member']['time'] ? $arr_addon['member']['time'] : '';
                        if ($arr_def_addr['day'] == app::get('b2c')->_('任意日期') && $arr_def_addr['time'] == app::get('b2c')->_('任意时间段'))
                        {
                            unset($arr_def_addr['day']);
                            unset($arr_def_addr['time']);
                        }
                        $this->pagedata['addr'] = array(
                            'area'=> $arr_addon['member']['ship_area'],
                            'addr'=> $arr_addon['member']['ship_addr'],
                            'zipcode' => $arr_addon['member']['ship_zip'] ? $arr_addon['member']['ship_zip'] : '',
                            'name' => $arr_addon['member']['ship_name'],
                            'email' => $arr_addon['member']['ship_email'],
                            'phone' => array(
                                'mobile'=>$arr_addon['member']['ship_mobile'],
                                'telephone' => $arr_addon['member']['ship_tel'] ? $arr_addon['member']['ship_tel'] : ''
                            ),
                        );
                    }
                }
            }
        }

        $obj_dlytype = kernel::single("b2c_mdl_dlytype");
        $arr_shipping_info = $obj_dlytype->get_shiping_info($shipping_id, $this->pagedata['aCart']["subtotal"]);
        $this->pagedata['def_addr'] = $def_addr ? $def_addr : 0;
        $this->pagedata['def_area'] = $def_area ? $def_area : 0;

        foreach($addrlist as $k=>$v){
            $area = array();
            $area = explode(':',$v['area']);
            $addrlist[$k]['_area'] = $area[2];
            $area = explode('/',$area[1]);

            if(in_array($area[0],array('北京','天津','上海','重庆'))){
                $area[0] = '';
            }

            $addrlist[$k]['area_arr'] = $area;
            if($v['def_addr']==1){
                $addr_default_addr = $addrlist[$k];
            }
        }
        if($addrlist){
            if(!$addr_default_addr){
                $addrlist[0]['def_addr'] = 1;
                $addr_default_addr = $addrlist[0];
            }
        }

        $this->pagedata['addrlist'] = $addrlist;
        $this->pagedata['default_addr'] = $addr_default_addr;
        $defaule_area_id = explode(':',$addr_default_addr['area']);
        $this->pagedata['area_id'] = $defaule_area_id[2];
        $this->pagedata['address']['member_id'] = $arrMember['member_id'];
        $this->pagedata['def_arr_addr'] = $arr_def_addr ? $arr_def_addr : $arr_def_addr_member;
        $this->pagedata['def_arr_addr_member'] = $arr_def_addr_member;
        $this->pagedata['def_arr_addr_other'] = $arr_def_addr;
        $this->pagedata['site_checkout_zipcode_required_open'] = $this->app->getConf('site.checkout.zipcode.required.open');

        $currency = app::get('ectools')->model('currency');
        $this->objMath = kernel::single("ectools_math");
        //订单总和
        $total_amount = $this->objMath->number_minus(array($this->pagedata['aCart']["subtotal"], $this->pagedata['aCart']['discount_amount']));


        $split_order=kernel::single('b2c_cart_object_split')->split_order(kernel::single("b2c_ctl_site_cart"),$this->pagedata['area_id'],$this->pagedata['aCart']);

    }
    //取得订单数据。
    function get_order_data($aCart,$postData,$store_id,&$msg,$arrMember){
        $obj_filter = kernel::single('b2c_site_filter');
        $postData = $obj_filter->check_input($postData);

        $order = &kernel::single("b2c_mdl_orders");
        $postData['order_id'] = $order_id = $order->gen_id();
        $postData['member_id'] = $arrMember['member_id'] ? $arrMember['member_id'] : 0;
        $order_data = array();

        $obj_order_create = kernel::single("b2c_order_create");
        // 加入订单能否生成的判断
		/*
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if ($obj_checkorder)
        {
            if (!$obj_checkorder->check_create($aCart, $postData['delivery']['ship_area'], $message))
                $this->end(false, $message); 
        }
		*/
		$postData['card_info'] =  "card";
        $order_data = $obj_order_create->generate($postData,'',$msg,$aCart);
        $order_data['store_id'] = $store_id;
        $obj_checkproducts = kernel::servicelist('b2c_order_check_products');
        if ($obj_checkproducts)
        {
            foreach($obj_checkproducts as $obj_check){
                if (!$obj_check->check_products($order_data, $messages)){

                    $this->end(false, $messages,'',true,true);
                }
            }
        }
        if (!$order_data || !$order_data['order_objects'])
        {
            $msg = app::get('b2c')->_("订单生成失败！");
            $this->end(false, $msg, '',true,true);
        }
        /*
        if($order_data['shipping']['shipping_id'] == null || $order_data['shipping']['shipping_id'] == ''){
            $msg = app::get('b2c')->_("请选择店铺配送方式！");
            $this->end(false, $msg, '',true,true);
        }
		*/
        return $order_data;
    }
    //为分单准备post数据。
    function get_post_cart($aCart,$sOrder,$postData,$oShipping){
        $post=array();
        if(isset($postData['purchase'])){
            $post['purchase']=$postData['purchase'];
        }
        if(isset($postData['split_order'])){
            $post['split_order']=$postData['purchase'];
        }
        if(isset($postData['extends_args'])){
            $post['extends_args']=$postData['extends_args'];
        }
        if(isset($postData['delivery'])){
            $post['delivery']=$postData['delivery'];
        }
        if(isset($postData['payment'])){
            $post['payment']=$postData['payment'];
        }
        if(isset($postData['fromCart'])){
            $post['fromCart']=$postData['fromCart'];
        }
        if(isset($postData['split_order'])){
            $post['split_order']=$postData['split_order'];
        }
        if(isset($postData['minfo'])){
            $post['minfo']=$postData['minfo'];
        }
        $shipping_id = $oShipping['shipping_id'];
        $post['delivery']['shipping_id'] = $shipping_id;
        $post['delivery']['is_protect'][$shipping_id] = $oShipping['is_protect'];
        $post['is_protect'][]= $oShipping['is_protect'];
        $post['shipping_id'][]= $oShipping['shipping_id'];
        $post['memo'] = $oShipping['memo'];
        $post['payment']['dis_point']=0;

        return $post;
    }
    //根据分单数据重新构建购物车结构，以便直接生成订单结构。
    function get_split_cart(&$allCart,$sOrder,$postData,$oShipping,$store_id){
        $sCart=array();
        foreach($sOrder['object'] as $obj_type=>$obj){
            foreach($obj['index'] as $index){
                $sCart['object'][$obj_type][]=$allCart['object'][$obj_type][$index];
            }
        }
        $mCart = kernel::single("b2c_mdl_cart");
        $mCart->count_objects($sCart);
        if($allCart['promotion']){
            foreach($allCart['promotion'] as $pkey=> $promotion){
                foreach($promotion as $ptypekey=> $pcoupon){
                    if($allCart['is_free_shipping'][$store_id]){//免运费
                        if($pcoupon['store_id']==$store_id){
                            $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                        }
                    }elseif ($pkey == 'coupon'){
                        $sCart['promotion'][$pkey][$ptypekey] = $pcoupon;
                    }else{//折扣或者送优惠券
                        if($pcoupon['store_id']==$store_id){
                            if($pcoupon['discount_amount']>0){
                                if($sCart['subtotal']>$pcoupon['discount_amount']){

                                    $sCart['discount_amount']=$pcoupon['discount_amount'] + $sCart['discount_amount_prefilter'];
                                    $sCart['subtotal_discount']=$pcoupon['discount_amount'] + $sCart['discount_amount_prefilter'];
                                    $sCart['discount_amount_order']=$pcoupon['discount_amount'];
                                    $allCart['promotion'][$pkey][$ptypekey]=$pcoupon['discount_amount']-$sCart['subtotal'];
                                }
                                $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                            }else if($pcoupon['discount_amount']==0){//送优惠券。
                                $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                                unset($allCart['promotion'][$pkey][$ptypekey]);
                            }
                        }
                    }
                }
            }
        }
        //if($sCart['promotion']){
        if($allCart['object']['coupon']){
            foreach($allCart['object']['coupon'] as $coupon){
                if($coupon['store_id']==$store_id){
                    $sCart['object']['coupon'][]=$coupon;
                }
            }
        }
        //}
        if($allCart['is_free_shipping']){
            $sCart['is_free_shipping']=$allCart['is_free_shipping'][$store_id];
        }
        if($allCart['free_shipping_rule_type']){
            $sCart['free_shipping_rule_type']=$allCart['free_shipping_rule_type'][$store_id];
        }
        if($allCart['free_shipping_rule_id']){
            $sCart['free_shipping_rule_id']=$allCart['free_shipping_rule_id'][$store_id];
        }
        if(isset($allCart['inAct'])){
            $sCart['inAct']=$allCart['inAct'];
        }

        if(isset($allCart['promotion_order_create'])){
            foreach($allCart['promotion_order_create'] as $key=>$promotion_order_create){
                foreach($promotion_order_create as $k=>$val){
                    $objCoupon = app::get('b2c')->model('coupons');
                    $cList = $objCoupon->dump($val['cpns_id'][0]);
                    $cList['store_id'] = explode(',',$cList['store_id']);
                    if($cList['store_id'][1]==$store_id){
                        $sCart['promotion_order_create'][$key][$k] = $val;
                    }
                }
            }
        }
        return $sCart;
    }
}
