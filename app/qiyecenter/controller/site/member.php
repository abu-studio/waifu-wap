<?php
class qiyecenter_ctl_site_member extends b2c_ctl_site_member{
	public function __construct(&$app){
        $this->app_current = $app;
        $this->app_b2c = app::get('b2c');
        parent::__construct($this->app_b2c);
        $shopname = $this->app_b2c->getConf('system.shopname');
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('企业中心').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('企业中心').'_'.$shopname;
            $this->description = app::get('b2c')->_('企业中心').'_'.$shopname;
        }

        $this->qiyemember_id = $_SESSION['account']['member'];
        //I03702   I03703
        $this->qiye_role_id = $_SESSION['account']['SETUP_MANAGER_TYPE'];

        $this->humbas_no = $_SESSION['account']['HUMBAS_NO'];
        $this->cur_view = 'member';
    }
    /**
     * 确认提交
     */
    public function confirmSub(){
    	//改变状态
    	//清除session
    	unset($_SESSION['fuliInfo']);
    	die;
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'getBizOrderItemUserByOrderId';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
		$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
		echo json_encode($this->pagedata['employeeList']);
    }
    /**
     * 发布福利详情
     */
    public function fabufuliDetail(){
    	$orderId = $this->_request->get_param(0);
    	$params['METHOD'] = 'getBizOrderById';
        $params['ORDER_ID'] = $orderId;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['fuliInfo'] = $resSF['RESULT_DATA'];
        $this->output();
    }
    /**
     * 创建人员详情
     */
    public function showEmployeeList(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'getBizOrderItemUserByOrderId';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
		$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
		$render = new base_render(app::get('qiyecenter'));
        echo  $render->fetch('site/member/showEmployeeList.html');
    }
    /**
     * 根据条件查询
     */
    public function getSearchEmployeesByConds(){
    	$type = $_POST['IS_NULL'];
    	if($type){
    		$this->getEmployeesList();
    	}else{
    		$this->getUnEmployeesList();
    	}
    }
	/**
	 * 批量修改
	 */
    public function updateEmployees(){
   		//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'updateItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
        echo json_encode($this->pagedata['employeeList']);
    }
    /**
     * 发给指定员工
     */
    public function showEmployees(){
    	//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
    	$render = new base_render(app::get('qiyecenter'));
        //指定员工菜单调用
        $this->pagedata['qiyemenu'] = $this->getMenus(true);
        unset($params);
        $orderId = !empty($_POST['ORDER_ID'])?htmlspecialchars($_POST['ORDER_ID']):'';
        $nodeId = !empty($_POST['NODE_ID'])?htmlspecialchars($_POST['NODE_ID']):'';
        if($_SESSION['treeParams'] && !$_POST['NODE_TYPE']){
        	$params['ORDER_ID'] = $orderId;
        	$params['IS_NULL'] = 0;
        	$nodeInfo = $this->getNodeInfo($_SESSION['treeParams'], $nodeId);
        	$params['NODE_TYPE'] = $nodeInfo['nodeType'];
        	$params['NODE_ID'] = $nodeId;
        	$params['METHOD'] = 'getItemUserByCondition';
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
			if(!$params){
				//错误处理
			}
	    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
        }
        echo $render->fetch('site/member/employee_main.html');
    }
	/**
     * 发给指定部门
     */
    public function showDepartments(){
    	//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
    	$render = new base_render(app::get('qiyecenter'));
        //指定员工菜单调用
        $this->pagedata['qiyemenu'] = $this->getMenus(true);
        unset($params);
        $orderId = !empty($_POST['ORDER_ID'])?htmlspecialchars($_POST['ORDER_ID']):'';
        $nodeId = !empty($_POST['NODE_ID'])?htmlspecialchars($_POST['NODE_ID']):'';
        if($_SESSION['treeParams'] && !$_POST['NODE_TYPE']){
        	$params['ORDER_ID'] = $orderId;
        	$params['IS_NULL'] = 0;
        	$nodeInfo = $this->getNodeInfo($_SESSION['treeParams'], $nodeId);
        	$params['NODE_TYPE'] = $nodeInfo['nodeType'];
        	$params['NODE_ID'] = $nodeId;
        	$params['METHOD'] = 'getItemUserByCondition';
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
			if(!$params){
				//错误处理
			}
	    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
        }
        echo $render->fetch('site/member/department_main.html');
    }
	/**
     * 发给指定群组
     */
    public function showGroups(){
    	//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
    	$render = new base_render(app::get('qiyecenter'));
        //指定员工菜单调用
        $this->pagedata['qiyemenu'] = $this->getMenus(true);
        unset($params);
        $orderId = !empty($_POST['ORDER_ID'])?htmlspecialchars($_POST['ORDER_ID']):'';
        $nodeId = !empty($_POST['NODE_ID'])?htmlspecialchars($_POST['NODE_ID']):'';
        if($_SESSION['treeParams'] && !$_POST['NODE_TYPE']){
        	$params['ORDER_ID'] = $orderId;
        	$params['IS_NULL'] = 0;
        	$nodeInfo = $this->getNodeInfo($_SESSION['treeParams'], $nodeId);
        	$params['NODE_TYPE'] = $nodeInfo['nodeType'];
        	$params['NODE_ID'] = $nodeId;
        	$params['METHOD'] = 'getItemUserByCondition';
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
			if(!$params){
				//错误处理
			}
	    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
        }
        echo $render->fetch('site/member/group_main.html');
    }
    
    
    /**
     * 根据节点Id获取节点信息
     */
    public function getNodeInfo($tree, $nodeId){
    	global $res;
    	foreach($tree as $k=>$t){
    		if($t['id'] == $nodeId){
    			$res = $t;
    		}else{
    			self::getNodeInfo($t, $nodeId);
    		}
    	}
    	return $res;
    }
    
    /**
     * 生成发给指定员工的菜单
     */
    public function getMenus($default=false){
    	if($_POST['PAY_ACT_TYPE']){
         	if($_POST['PAY_ACT_TYPE'] == 'I00105'){
         		$menu_name = "群组";
         		$menu_type = "I01304";
         	}else{
         		$menu_name = "部门";
         		$menu_type = "I01303";
         	}
        }else{
            $menu_name = "部门";
            $menu_type = "I01303";
        }
        $orderId = !empty($_POST['ORDER_ID'])?htmlspecialchars($_POST['ORDER_ID']):'';
        $nodeId = !empty($_POST['NODE_ID'])?htmlspecialchars($_POST['NODE_ID']):'';
        $menu_array[] = $nodeId;
        $PAY_ACT_TYPE = !empty($_POST['PAY_ACT_TYPE'])?htmlspecialchars($_POST['PAY_ACT_TYPE']):'';
        $REC_ACT_TYPE = !empty($_POST['REC_ACT_TYPE'])?htmlspecialchars($_POST['REC_ACT_TYPE']):'';
        $serviceNo = "TreeManagerService";
        $_sjson = array(
            'METHOD'=>'getTreeByOrderId',
            'USER_ID'=>$_SESSION['account']['USER_ID'],
            'NODE_TYPE'=>$menu_type,
        	'ORDER_ID'=>$orderId,
        	'PAY_ACT_TYPE' => $PAY_ACT_TYPE,
        	'REC_ACT_TYPE' => $REC_ACT_TYPE,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $qi_menu_data_tmp = $sfscdata['RESULT_DATA'];
        if(!empty($qi_menu_data_tmp['nodes'])){
        	$_SESSION['treeParams'] = $qi_menu_data_tmp['nodes'];
            $sfsc_menu = $this->sfscTreeData($qi_menu_data_tmp['nodes'],$menu_array,$flag = true);
        }
        $tmp = substr_replace($sfsc_menu,"<ul class='tree_ul' id='both'>",0,4);
        $this->pagedata['sfsc_menu'] = $tmp;
        $this->pagedata['sfsc_menu_name'] = $menu_name;
        if($_POST['menu_type']){
            echo $tmp;
            die();
        }
        $render = new base_render(app::get('qiyecenter'));
        return $render->fetch('site/member/employeeMenu.html');
    }
    /**
     * 删除指定员工
     */
    public function deleteBatchItemUser(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'deleteItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);die;
    }
    /**
     * 保存成指定员工
     */
    public function createItemUser(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'createBatchItemUser';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);die;
    }
    /**
     * 获取指定员工
     */
    public function getDepartEmployeeList(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);die;
    }
    /**
     * 福利列表页面
     */
    public function welfareList(){
    	//获取接口数据
    	$params['METHOD'] = 'getBizOrderByUserId';
        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
        if(!$params){
			//错误处理
		}
		$this->pagedata['welfareList'] = $resSF['RESULT_DATA'];
		$this->output();
    }
    /**
     * 保存并下一步
     */
    public function saveNext(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'updateBizOrderByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
		if(!$params){
			//错误处理
		}
    }
    /**
     * 清除
     */
 	public function cleanData(){
 		//获取接口数据
 		$params = $_POST;
    	$params['METHOD'] = 'resetBizOrderByOrderId';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
		if(!$resSF){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);
 	}
    /**
     * 发放详情
     */
    public function provideDetail(){
    	//获取接口数据
    	$params = $_POST;
    	$params['METHOD'] = 'employeeGrantProduct';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemService');
		if(!$resSF){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);
		die;
    }
    /**
     * 获取公司部门群组列表
     */
    public function delOrder(){
    	$params = $_POST;
    	//获取接口数据
    	$params['METHOD'] = 'deleteOrderItemByItemId';
    	//$params
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemService');
		if(!$resSF){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);die;
    }

    /**
     * 获取公司部门群组列表
     */
    public function resetOrder(){
    	//获取参数1公司，2部门，3群组
    	//$params = $_POST;
    	$tmpData = $_POST['ITEM_LIST']['passParam'];
    	unset($_POST['ITEM_LIST']);
    	$_POST['ITEM_LIST'] = $tmpData;
    	$params = $_POST;
    	//获取接口数据
    	$params['METHOD'] = 'createOrderItemByOrderId';
    	//$params
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemService');
		if(!$resSF){
			//错误处理
		}
		echo json_encode($resSF['RESULT_DATA']);die;
    }
    /**
     * 获取收入账户列表按公司
     */
    public function getIncomeAccountListByCompany(){
    	//获取接口数据
    	$params['METHOD'] = 'getCustomerListByUserId';
        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'CustomerService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['data'] = json_encode($resSF['RESULT_DATA']);
		$this->pagedata['list'] = $resSF['RESULT_DATA'];
		$render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/getIncomeAccountListByCompany.html');
    }
	/**
     * 获取收入账户列表按部门
     * 
     */
    public function getIncomeAccountListByDepartment(){
    	//获取接口数据
    	$params['METHOD'] = 'getDepartmentListByUserId';
        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'DepartmentService');
		if(!$resSF){
			//错误处理
		}
		//传递页面数据
		$this->pagedata['data'] = json_encode($resSF['RESULT_DATA']);
		//按照公司分组
		$companys = array();
		foreach ($resSF['RESULT_DATA'] as $k=>$v){
			if(!in_array($v['CUSTOMER_NAME'], $companys)){
				$companys[$v['CUSTOMER_NAME']][] = $v;
			}else{
				$companys[$v['CUSTOMER_NAME']][] = $v;
			}
		}
		$this->pagedata['list'] = $companys;
		$render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/getIncomeAccountListByDepartment.html');
    }
    
	/**
     * 获取收入账户列表按群组
     */
    public function getIncomeAccountListByGroup(){
    	//获取接口数据
    	$params['METHOD'] = 'getGroupListByUserId';
        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'GroupService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['data'] = json_encode($resSF['RESULT_DATA']);
		//按照公司分组
		$companys = array();
		foreach ($resSF['RESULT_DATA'] as $k=>$v){
			if(!in_array($v['CUSTOMER_NAME'], $companys)){
				$companys[$v['CUSTOMER_NAME']][] = $v;
			}else{
				$companys[$v['CUSTOMER_NAME']][] = $v;
			}
		}
		$this->pagedata['list'] = $companys;
		$render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/getIncomeAccountListByGroup.html');
    }
    
    
    
	function index(){
        //初始化返回数据
    	$data = array();
    	$data['code'] = 0;
    	$data['msg'] = '测试';
    	$data['info'] = '';
        //显示是什么列表
        $name_type=$this->qiye_role_id;
        $this->pagedata['name_type'] =$name_type;
        //显示管理员等级
        $this->pagedata['SETUP_MANAGER_TYPE'] = $this->qiye_role_id;
		//我的悠福
		//获取接口数据
        /*
    	$params['METHOD'] = 'getManagementDataByUserId';
        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'CustomerUserService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['head_data'] = $resSF['RESULT_DATA'];
		*/
		//获取本周过生日的员工
		/*
        $params['METHOD'] = 'getBirthdayEmployeeListByUserId';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'EmployeeService');
		if(!$resSF){
			//错误处理
		}
		*/

		//根据条件获取本地登陆用户
        /*
		$obj_pam_account = app::get('pam')->model('account');
		$member_object = kernel::single("b2c_mdl_members");
		if($resSF['RESULT_DATA']){
			foreach ($resSF['RESULT_DATA'] as $k=>$v){
				$pam_account = $obj_pam_account->getList('*',array('login_name'=>$v['HUMBAS_NO']));
				$member_data = $member_object->dump(array('member_id'=>$pam_account[0]['account_id']),"*");
        		$resSF['RESULT_DATA'][$k]['member_avatar'] = $member_data['member_avatar'] ? kernel::base_url().'/public/memberavatar/'.$member_data['member_avatar'] : kernel::base_url().'/themes/simple/images/grzx0601_01.png';
			}
		}
        */
		//$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];

        //商社列表
        $serviceNo="SubAccountService";
        $_sjson = array(
            'METHOD'=>'getAttributeCompanyAllToPayByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['COMPANY_LIST'] = $sfscdata['RESULT_DATA'];
        //获取账户余额
        $BALANCE = 0 ;
        if($sfscdata['RESULT_DATA']){
            foreach($sfscdata['RESULT_DATA'] as $item){
                $BALANCE += $item['BALANCE'];
            }
        }
        $this->pagedata['BALANCE'] = $BALANCE;

            //获取我的订单
        $count_tmp = $this->get_search_order_main_ids();
        $this->pagedata['headerdata']['my_dd'] = $count_tmp ?$count_tmp :0;

        //获取频道信息 CHANNEL_LIST
        //1 商城  2 团购 3 便生活 4卡劵 5 体检 6 理财  8.京东频道

        $serviceNo="CustomerChannelService";
        $_sjson = array(
            'METHOD'=>'getCompanyChannelByHumbasNo',
            'HUMBAS_NO' => $this->humbas_no,
            'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
            
        );
       
       $MemberInfoArr = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        $channel_list_array = array(
            array(
                'channel_id'=>'1',
                'app'=>'site',
                'ctl'=>'default',
                'act'=>'index',
                'pic'=>'grzx0601_14.png',
                'describe'=>'购物配送一站式'
            ),
            array(
                'channel_id'=>'2',
                'app'=>'groupbuy',
                'ctl'=>'site_grouplist',
                'act'=>'index',
                'pic'=>'grzx0601_26.png',
                'describe'=>'惊爆单品 诚意推荐'
            ),
            array(
                'channel_id'=>'3',
                'app'=>'b2c',
                'ctl'=>'site_lifecost',
                'act'=>'index',
                'pic'=>'grzx0601_16.png',
                'describe'=>'生活缴费一站式'
            ),
            array(
                'channel_id'=>'4',
                'app'=>'cardcoupons',
                'ctl'=>'site_card_channel',
                'act'=>'index',
                'pic'=>'grzx0601_24.png',
                'describe'=>'节日礼包 商务馈赠'
            ),
            array(
                'channel_id'=>'5',
                'app'=>'physical',
                'ctl'=>'site_index',
                'act'=>'index',
                'pic'=>'grzx0601_27.png',
                'describe'=>'关爱您和家人健康'
            ),
            array(
                'channel_id'=>'6',
                'app'=>'b2c',
                'ctl'=>'site_member',
                'act'=>'index',
                'pic'=>'grzx0601_25.png',
                'describe'=>'一站式购物'
            ),
            array(
                'channel_id'=>'7',
                'app'=>'b2c',
                'ctl'=>'site_product',
                'act'=>'Japan',
                'pic'=>'grzx0601_28.png',
                'describe'=>'日本馆'
            ),
            array(
                'channel_id'=>'8',
                'app'=>'jdsale',
                'ctl'=>'site_gallery',
                'act'=>'index',
                'pic'=>'grzx0601_28.png',
                'describe'=>'京东特卖'
            )
        );
        if(!empty($MemberInfoArr['RESULT_DATA']['CHANNEL_LIST'])){
            foreach($MemberInfoArr['RESULT_DATA']['CHANNEL_LIST'] as  $ckey=>$cval){
                foreach($channel_list_array as $channelkey=>$channelvalue){
                    if($cval['CHANNEL_ID'] == $channelvalue['channel_id']){
                        $MemberInfoArr['RESULT_DATA']['CHANNEL_LIST'][$ckey]['channel_info'] = $channel_list_array[$channelkey];
                    }
                }
            }
        }
        $this->pagedata['channellist'] = $MemberInfoArr['RESULT_DATA']['CHANNEL_LIST'];
        //获取我的消息
        $oMsg = kernel::single('b2c_message_msg');
        $no_read = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true','mem_read_status' => 'false'));
        $no_read = count($no_read);
		$this->pagedata['headerdata']['message'] = $no_read;

        $this->indexoutput();
	}

	private function get_cpmenu(){
	    $roleList = $this->getRoleOfMember();
		if(in_array('I03701' , $roleList)){
            $arr_bases =
                array(
                    array('label'=>app::get('qiyecenter')->_('组织管理'),
                        'mid'=>0,
                        'items'=>array(
                            array('mid'=>'1','label'=>app::get('qiyecenter')->_('默认支付帐号'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'paymentdefault'),
                            array('mid'=>'5','label'=>app::get('qiyecenter')->_('员工管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'yuangongguanli'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('订单管理'),
                        'mid'=>1,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('企业订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'orders'),
                            array('label'=>app::get('qiyecenter')->_('购物车'),'app'=>'b2c','ctl'=>'site_cart','link'=>'1','href'=>'true'),
                            array('label'=>app::get('qiyecenter')->_('退款退货管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'return_list'),
                            array('label'=>app::get('qiyecenter')->_('收货地址'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'receiver'),
                            array('label'=>app::get('qiyecenter')->_('我的发票'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'my_apple_invoice'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('积分发放'),
                        'mid'=>2,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_orders'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'fupoint'
                    ),
                    array('label'=>app::get('qiyecenter')->_('统计报表'),
                        'mid'=>3,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_list'),
                            array('label'=>app::get('qiyecenter')->_('礼品领用统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_receive'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                );
		}elseif(in_array('I03702' , $roleList)){
            $arr_bases =
                array(
                    array('label'=>app::get('qiyecenter')->_('组织管理'),
                        'mid'=>0,
                        'items'=>array(
                            array('mid'=>'1','label'=>app::get('qiyecenter')->_('默认支付帐号'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'paymentdefault'),
                            array('label'=>app::get('qiyecenter')->_('员工管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'yuangongguanli'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('订单管理'),
                        'mid'=>1,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('企业订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'orders'),
                            array('label'=>app::get('qiyecenter')->_('购物车'),'app'=>'b2c','ctl'=>'site_cart','link'=>'1','href'=>'true'),
                            array('label'=>app::get('qiyecenter')->_('退款退货管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'return_list'),
                            array('label'=>app::get('qiyecenter')->_('收货地址'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'receiver'),
                            array('label'=>app::get('qiyecenter')->_('我的发票'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'my_apple_invoice'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('积分发放'),
                        'mid'=>2,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_orders'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'fupoint'
                    ),
                    array('label'=>app::get('qiyecenter')->_('统计报表'),
                        'mid'=>3,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_list'),
                            //array('label'=>app::get('qiyecenter')->_('礼品领用统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_receive'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                );
        }else{
            $arr_bases =
                array(
                    array('label'=>app::get('qiyecenter')->_('组织管理'),
                        'mid'=>0,
                        'items'=>array(
                            array('mid'=>'1','label'=>app::get('qiyecenter')->_('默认支付帐号'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'paymentdefault'),
                            array('label'=>app::get('qiyecenter')->_('员工管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'yuangongguanli'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('订单管理'),
                        'mid'=>1,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('企业订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'orders'),
                            array('label'=>app::get('qiyecenter')->_('购物车'),'app'=>'b2c','ctl'=>'site_cart','link'=>'1','href'=>'true'),
                            array('label'=>app::get('qiyecenter')->_('退款退货管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'return_list'),
                            array('label'=>app::get('qiyecenter')->_('收货地址'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'receiver'),
                            array('label'=>app::get('qiyecenter')->_('我的发票'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'my_apple_invoice'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                    array('label'=>app::get('qiyecenter')->_('积分发放'),
                        'mid'=>2,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放订单'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_orders'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'fupoint'
                    ),
                    array('label'=>app::get('qiyecenter')->_('统计报表'),
                        'mid'=>3,
                        'items'=>array(
                            array('label'=>app::get('qiyecenter')->_('积分发放统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_list'),
                            //array('label'=>app::get('qiyecenter')->_('礼品领用统计'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'jifen_receive'),
                        ),
                        'app'=>'qiyecenter',
                        'ctl'=>'site_member',
                        'link'=>'index'
                    ),
                );
        }
        //$oMsg = kernel::single('b2c_message_msg');
        //$no_read = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true','mem_read_status' => 'false'));
        //$no_read = count($no_read);
        //人事经理权限最高
        if(in_array('I03701' , $roleList)){
            array_unshift($arr_bases[0]['items'], array('mid'=>'4','label'=>app::get('qiyecenter')->_('群组管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'qunzuguanli'));
            array_unshift($arr_bases[0]['items'], array('mid'=>'3','label'=>app::get('qiyecenter')->_('部门管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'bumenguanli'));
            array_unshift($arr_bases[0]['items'], array('mid'=>'2','label'=>app::get('qiyecenter')->_('企业管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'qiyeguanli'));
        }else{
            if(in_array('I03703' , $roleList)){
                array_unshift($arr_bases[0]['items'] , array('mid'=>'2','label'=>app::get('qiyecenter')->_('群组管理'),'app'=>'qiyecenter','ctl'=>'site_member','link'=>'qunzuguanli'));
            }

            if(in_array('I03702' , $roleList)) {
                array_unshift($arr_bases[0]['items'], array('mid' => '2', 'label' => app::get('qiyecenter')->_('部门管理'), 'app' => 'qiyecenter', 'ctl' => 'site_member', 'link' => 'bumenguanli'));
            }
        }

        return $arr_bases;
    }

    function getRoleOfMember(){
	    $roleListData = $_SESSION['roleListData'];

        if(empty($roleListData)){
            $serviceNo="CustomerManagerUserLoginService";
            $_sjson = array(
                'METHOD'=>'getAdminRolesByHumbasNo',
                //'HUMBAS_NO'=>'0001978894',
                'HUMBAS_NO'=>$this->humbas_no,
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            if(is_array($sfscdata['RESULT_DATA'])){
                $roleListData =  $sfscdata['RESULT_DATA'];
                $_SESSION['roleListData'] = $roleListData;
            }
        }

        return $roleListData;
    }

    function setupmanage(){
        //$this->singlepage('site/member/setupmanage.html');
    }


    protected function indexoutput($app_id = 'b2c'){
        $this->pagedata['cpmenu'] = $this->get_cpmenu();
        $this->pagedata['current'] = $this->action;
        foreach(kernel::servicelist('member_index') as $service) {
            if (is_object($service)) {
                if (method_exists($service, 'get_member_html')) {
                    $aData[] = $service->get_member_html();
                }
            }
        }
        $this->pagedata['app_id'] = $app_id;
        $this->pagedata['base_url'] = kernel::base_url();
        $this->pagedata['_MAIN_'] = 'site/member/main.html';
        $this->pagedata['get_member_html'] = $aData;
        $this->set_tmpl("qiyecenter_main");
        $this->page('');
    }
	
    //账户信息
    function fupoint($tag=""){
    	$query_time = $_POST['query_time'] ?$_POST['query_time']:"";
    	if($query_time == ''){
    		$_sjson = array(
    				'METHOD'=>'getBalanceInfoByRelationId',
    				'RELATION_ID'=>$this->member['uname'],
    				'QUERY_TIME'=>""
    		);
    		$post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
    		$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
    		if($tmpdata != null && gettype($tmpdata) == "object"){
    			$tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
    		}else{
    			$tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
    		}
    		if($tag != ""){
    			$this->pagedata['shifttab'] = true;
    		}
    		$this->pagedata['sfsc_member_info'] = $tmp22;
    		$this->output();
    	}else{
    		if($query_time == "qb004"){
    			$query_time = '';
    		}
    		$_sjson = array(
    				'METHOD'=>'getBalanceInfoByRelationId',
    				'RELATION_ID'=>$this->member['uname'],
    				'QUERY_TIME'=>$query_time
    		);
    		$post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
    		$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
    		if($tmpdata != null && gettype($tmpdata) == "object"){
    			$tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
    		}else{
    			$tmp22= Array('RESULT_CODE'=>0,'RESULT_DATA'=>Array('INCOME'=>'0','SUM'=>'0','EXPENSES'=>'0'));
    		}
    		echo json_encode($tmp22);
    		die();
    	}
    
    }


    /**
     *获取 待付款  待发货   待收货
     * @return string
     */
    private function get_search_order_main_ids(){
        $sdb = kernel::database()->prefix;
        $order = app::get('b2c')->model('orders');
        $type_sql = " ((pay_status='0' and status='active') or (pay_status='1' and ship_status='0') or (pay_status='1' and ship_status='1' and status='active')) ";
        $str_sql = "SELECT order_id FROM ".$sdb."b2c_orders WHERE member_id=".$this->app->member_id;
        $str_sql.=" AND ".$type_sql;
        $arrayorser = $order->db->select($str_sql);
        return count($arrayorser);

    }

	protected function output($app_id='qiyecenter'){
        $this->pagedata['cpmenu'] = $this->get_cpmenu();
        $this->pagedata['current'] = $this->action;

        if ($this->pagedata['_PAGE_']) {
            if($this->qiye_role_id == 'I03701'){
                $this->pagedata['_PAGE_'] = 'site/'.$this->cur_view.'/' . $this->pagedata['_PAGE_'];
            }else{
                $this->pagedata['_PAGE_'] = 'site/'.$this->cur_view.'/role/' . $this->pagedata['_PAGE_'];
            }
        } else {
            if($this->qiye_role_id == 'I03701'){
                $this->pagedata['_PAGE_'] = 'site/'.$this->cur_view.'/' . $this->action_view;
            }else{
                $this->pagedata['_PAGE_'] = 'site/'.$this->cur_view.'/role/' . $this->action_view;
            }
        }

        $this->pagedata['base_url'] = kernel::base_url();
        $this->pagedata['app_id'] = $app_id;
        $this->pagedata['_MAIN_'] = 'site/member/main.html';
        $this->set_tmpl("qiyecenter");
        $this->page('site/member/main.html', false, $app_id);
    }


    /**
        企业管理主页面
    **/
    function qiyeguanli(){
        $serviceNo="ManagerByHumbasNoService";
        $_sjson = array(
            'METHOD'=>'getCustomerByListHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            'NAME_CH' =>$_POST['name_ch'] ? trim($_POST['name_ch']) : "",
            'COMPANY_NO'=>$_POST['company_no'] ? trim($_POST['company_no']) : "",
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
         //显示商社等级的选择情况
         $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
         $member_sel = $obj_com->getList('target_id',array('m_id'=>$this->qiyemember_id , 'target_type' => 'I00102'));
         $this->pagedata['business_dj_id'] = $member_sel['0']['target_id'];
         //end
        $this->output();
    }

    /**
        设置默认支付帐号
    **/
    function paymentdefault(){
        $serviceNo="SubAccountService";
        $_sjson = array(
            'METHOD'=>'getAttributeCompanyAllToPayByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            'NAME_CH' =>$_POST['name_ch'] ? trim($_POST['name_ch']) : "",
            'COMPANY_NO'=>$_POST['company_no'] ? trim($_POST['company_no']) : "",
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
         //显示商社等级的选择情况
         $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
         $member_sel = $obj_com->getList('target_id,target_type',array('m_id'=>$this->qiyemember_id));
         $this->pagedata['business_target_id'] = $member_sel['0']['target_id'];
         $this->pagedata['business_target_type'] = $member_sel['0']['target_type'];
         //end
        $this->output();
    }

    function set_default_payment(){
        $BUY_CUSTOMER_TYPE = $_POST['BUY_CUSTOMER_TYPE'];
        $BUY_CUSTOMER_ID = $_POST['BUY_CUSTOMER_ID'];
        //var_dump($BUY_CUSTOMER_ID);exit;
        if('I00102' == $BUY_CUSTOMER_TYPE){
            $_POST['BUY_CUSTOMER_ID'] = $BUY_CUSTOMER_ID;
            $this->get_business_add();
        }else if('I00103' == $BUY_CUSTOMER_TYPE){//部门
            $_POST['BUY_BUMEN_ID'] = $BUY_CUSTOMER_ID;
            $this->get_bumenbusiness_add();
        }else if('I00104' == $BUY_CUSTOMER_TYPE){//群组
            $_POST['BUY_GROUP_ID'] = $BUY_CUSTOMER_ID;
            $this->get_qunzubusiness_add();
        }

    }

    function jifen_orders(){
        if($this->qiye_role_id == 'I03701') {
            $serviceNo = "BizOrderService";
            $_sjson = array(
                'METHOD' => 'getPointOutByHumbasNo',
                'HUMBAS_NO' => $this->humbas_no,
                'GRANT_NAME' => $_POST['GRANT_NAME'] ? trim($_POST['GRANT_NAME']) : "",
            );
        }else{
            $serviceNo = "DepartmentService";
            $_sjson = array(
                'METHOD' => 'getPointOutByHumbasNo',
                'HUMBAS_NO' => $this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'GRANT_NAME' => $_POST['GRANT_NAME'] ? trim($_POST['GRANT_NAME']) : "",

            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->output();
    }
    //订单积分创建
    function jifen_orders_edit(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD'=>'selectOrderMessageByOederId',
            'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID'=>''
        );


        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['qiye_role_id'] = $this->qiye_role_id;
        $this->output();
    }

    //福利发放
    function fabufuli(){
    	//获取接口数据
    	unset($_SESSION['fuliInfo']);
    	if(!$_SESSION['fuliInfo']){
    		$params['METHOD'] = 'createBizOrder';
	        $params['USER_ID'] = $_SESSION['account']['USER_ID'];
	        $params['USER_NAME'] = $_SESSION["USER_NAME"];
	        $params['CUSTOMER_ID'] = '';
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
			if(!$params){
				//错误处理
			}
			$this->pagedata['fuliInfo'] = $resSF['RESULT_DATA'];
			$_SESSION['fuliInfo'] = $resSF['RESULT_DATA'];
    	}else{
    		$this->pagedata['fuliInfo'] = $_SESSION['fuliInfo'];
    	}
    	
    	unset($params);
    	//获取接口数据
    	$params['METHOD'] = 'getOptionList';
        $params['BIZ_ID'] = 'biz901';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'SelectService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['incomeAccountList'] = $resSF['RESULT_DATA'];
        $this->output();
    }
	//福利发放
    function fabufuliEdit(){
    	$orderId = $this->_request->get_param(0);
    	$params['METHOD'] = 'getBizOrderById';
        $params['ORDER_ID'] = $orderId;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['fuliInfo'] = $resSF['RESULT_DATA'];
    	//获取收入账户类型
    	unset($params);
    	//获取接口数据
    	$params['METHOD'] = 'getOptionList';
        $params['BIZ_ID'] = 'biz901';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'SelectService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['incomeAccountList'] = $resSF['RESULT_DATA'];
        $this->output();
    }


    /*
        群组管理主页面
    */
    function qunzuguanli(){
        if($this->qiye_role_id == 'I03701'){
            $serviceNo="GroupService";
            $_sjson = array(
                'METHOD'=>'getGroupListByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'CUSTOMER_NAME'=>$_POST['CUSTOMER_NAME'] ? trim($_POST['CUSTOMER_NAME']) : ""
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getListDeptOrGroupByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE'=>'I03703'
            );

        }

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['SETUP_MANAGER_TYPE'] = $this->qiye_role_id;
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        //显示商社等级的选择情况
         $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
         $member_sel = $obj_com->getList('target_id',array('m_id'=>$this->qiyemember_id , 'target_type' => 'I00104'));
         $this->pagedata['business_qz_id'] = $member_sel['0']['target_id'];
         //end
        $this->output();
    }


    function add_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'getGroupByGroupAndCompany',
            'HUMBAS_NO'=>$this->humbas_no,
            'COMPANY_NO'=>$_GET['COMPANY_NO'] ? trim($_GET['COMPANY_NO']):'',
            'GROUP_ID' =>$_GET['GROUP_ID'] ? trim($_GET['GROUP_ID']):''
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($_GET['qunzu_edit'] == "true"){
            $this->pagedata['qunzu_edit'] = 'true';
        }

        $this->pagedata['GROUP_ID'] = $_GET['GROUP_ID'];
        $this->pagedata['company']=$sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_qunzu1.html');
    }


    function add_qunzu1(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getCompanyNameByManagerId',
            'HUMBAS_NO'=>$this->humbas_no,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['company']=$sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_qunzu1.html');
    }

    //更新群组
    function updatequnzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'updateGroupByGroupAndCompany',
            'COMPANY_NO' => $_POST['COMPANY_NO'],  //CUSTOMER_ID
            'GROUP_ID' =>$_POST['GROUP_ID'],
            'GROUP_NAME' => $_POST['GROUP_NAME'],  //部门名称
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == '10001'){
            echo '<script type="text/javascript">top.location.reload();</script>';
        }else{
            echo '<script type="text/javascript">alert(\'保存失败！\')</script>';
        }
    }

    //创建群组

   function createqunzu(){
       $serviceNo="GroupService";
       $_sjson = array(
           'METHOD'=>'createGroupByHumbasNo',
           'COMPANY_NO' => $_POST['COMPANY_NO'],
           'GROUP_NAME' => $_POST['GROUP_NAME'],

       );
       $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
       if($sfscdata['RESULT_CODE'] == '10001'){
           echo '<script type="text/javascript">top.location.reload();</script>';
       }else{
           echo '<script type="text/javascript">alert(\'保存失败！\')</script>';
       }
   }


   //删除群组
    function del_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'deleteGroupByGroupAndCompany',
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            //'HUMBAS_NO'=>$this->humbas_no,
            'GROUP_ID' =>$_POST['GROUP_ID'],
        );

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }








    /**
        部门管理主页面
    **/
    function bumenguanli(){
        //I03702	部门管理员
        //I03701	商社管理员
        //I03703	群组管理员
        if($this->qiye_role_id == 'I03701'){
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getDepartmentListByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'CUSTOMER_NAME'=>$_POST['CUSTOMER_NAME'] ? trim($_POST['CUSTOMER_NAME']) : ""
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getListDeptOrGroupByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE'=>'I03702'
            );
        }
        $this->pagedata['SETUP_MANAGER_TYPE'] = $this->qiye_role_id;
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->output();
	}

	/*
	 部门管理主页面---编辑页面
	*/
	function editbumen(){
        $menu_array = array(trim($_POST['customer_no']),trim($_POST['dept_id']),trim($_POST['group_id']));
        $dept_id = $_POST['dept_id'];
        $company_no = $_POST['company_no'];
        $render = new base_render(app::get('qiyecenter'));
        //sfsc 管理菜单
        //$this->pagedata['qiyemenu'] = $this->getqiyemenu($flag,$menu_array);
        echo $render->fetch('site/member/editbumen.html');
    }

    /**
        员工管理主页面
    **/
    function yuangongguanli(){
        if($this->qiye_role_id == 'I03701'){
            $serviceNo="CustomerService";
            $_sjson = array(
                'METHOD'=>'getCompanyEmpByCustmoerId',
                'HUMBAS_NO'=>$this->humbas_no,
                'EMP_NAME' => $_GET['EMP_NAME'] ? $_GET['EMP_NAME'] : '',
                'CUSTOMER_NAME'=>$_GET['CUSTOMER_NAME'] ? $_GET['CUSTOMER_NAME'] :'',
                'DEPT_ID' => $_GET['department_id'] ? $_GET['department_id'] : '',
                'GROUP_ID' => $_GET['sfscgroup_id'] ? $_GET['sfscgroup_id'] : '',
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getFindDeptOrGroupEmployeeByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'EMP_NAME' => $_GET['EMP_NAME'] ? $_GET['EMP_NAME'] : '',
                'DEPT_ID' => $_GET['department_id'] ? $_GET['department_id'] : '',
                'GROUP_ID' => $_GET['sfscgroup_id'] ? $_GET['sfscgroup_id'] : '',
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        //去除重复处理
        if($sfscdata['RESULT_DATA']){
            $newResultData = array();
            foreach($sfscdata['RESULT_DATA'] as $index => $item){
                $HUMBAS_NO = $item['HUMBAS_NO'];
                $newResultData[$HUMBAS_NO] = $item;
                unset($sfscdata['RESULT_DATA'][$index]);
            }
            $sfscdata['RESULT_DATA'] = $newResultData;
        }
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['member_total'] = count($sfscdata['RESULT_DATA']);

        //查询 部门，群组 列表数据
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getAllCompanyAndDeptGroupByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $sfscDepartList = array();
        $sfscGroupList = array();
        if($sfscdata['RESULT_DATA']){
            foreach($sfscdata['RESULT_DATA'] as $index => $item){
                $type = $item['TYPE_NAME'];
                if('部门' == $type){
                    array_push($sfscDepartList , $item);
                }else if('群组' == $type){
                    array_push($sfscGroupList , $item);
                }
            }
        }
        $this->pagedata['sfscDepartList'] = $sfscDepartList;
        $this->pagedata['sfscGroupList'] = $sfscGroupList;

        $this->output();
    }
     //员工导出
     function yuangong_export(){
        //start
         if($this->qiye_role_id == 'I03701'){
             $serviceNo="CustomerService";
             $_sjson = array(
                 'METHOD'=>'getCompanyEmpByCustmoerId',
                 'HUMBAS_NO'=>$this->humbas_no,
                 'EMP_NAME' => $_GET['EMP_NAME'] ? $_GET['EMP_NAME'] : '',
                 'CUSTOMER_NAME'=>$_GET['CUSTOMER_NAME'] ? $_GET['CUSTOMER_NAME'] :'',
                 'DEPT_ID' => $_GET['department_id'] ? $_GET['department_id'] : '',
                 'GROUP_ID' => $_GET['sfscgroup_id'] ? $_GET['sfscgroup_id'] : '',
             );
         }else{
             $serviceNo="DepartmentService";
             $_sjson = array(
                 'METHOD'=>'getFindDeptOrGroupEmployeeByHumbasNo',
                 'HUMBAS_NO'=>$this->humbas_no,
                 'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                 'EMP_NAME' => $_GET['EMP_NAME'] ? $_GET['EMP_NAME'] : '',
                 'DEPT_ID' => $_GET['department_id'] ? $_GET['department_id'] : '',
                 'GROUP_ID' => $_GET['sfscgroup_id'] ? $_GET['sfscgroup_id'] : '',
             );
         }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);     

        $aData['vcode']='0001';
        $aData['vcode']=str_pad((intval($aData['vcode'])+1), 4, "0", STR_PAD_LEFT);

        // header("Content-Type: text/csv");
        header("Content-type:application/vnd.ms-excel");
        $filename = "Employee_".$aData['vcode'].".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');

       $csv_data[0]=array('雇员号(HUMBAS_NO)','雇员姓名(NAME)','商社名称(CUSTOMER_NAME)','身份证号(ID)');
        foreach($sfscdata['RESULT_DATA'] as $key=>$item){
            $arr = array();
            $arr = array(
                'HUMBAS_NO'=> "\t".$item['HUMBAS_NO']."\t", 
                'NAME'=>$item['NAME'],    
                'CUSTOMER_NAME'=>$item['CUSTOMER_NAME'],    
                'ID'=>$item['ID'],
            );
            $csv_data[] = $arr;
        }
        $csv_row = array();
        foreach($csv_data as $key=>$csv_item)
        {
            $current = array();
            foreach($csv_item AS $item)
            {
                /****************************************************************************************************************************
                 *很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
                 ****************************************************************************************************************************/
                $current[] = is_numeric($item)?$item:'"'.str_replace('"','""',$item ).'"';

            }
            $csv_row[] = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        //end
        if(function_exists('iconv')){
            echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        }else{
            echo kernel::single('base_charset')->utf2local( $csv_string );
        }
    }

    
 /**
    礼包兑换结果 
 **/
    function libaoduihuan(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getGiftsResults',
            'HUMBAS_NO'=>$this->humbas_no,
            'USER_ID'=>$_SESSION['account']['USER_ID']
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
    	$this->output();
    }
    
    /**
     积分使用结果
     **/
    function jifenshiyong(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getEnterpriseResults',
            'HUMBAS_NO'=>$this->humbas_no,
            'USER_ID'=>$_SESSION['account']['USER_ID']
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
    	$this->output();
    }
    /**
        创建福利订单调用
    **/
    function create_order(){
		$render = new base_render(app::get('qiyecenter'));
		echo $render->fetch('site/member/fabufuliEdit.html');
	}
   
    /**
        企业管理编辑调用
    **/
    function editinput(){
        $render = new base_render(app::get('qiyecenter'));
        //企业内页调用
        if($_POST['qiyeguanli_edit']){
            $this->qiyeguanli_edit();
			$flag="qiyeguanli_edit";
            $edit_html = "qiye_main.html";
        }elseif($_POST['bumenguanli_edit']){
            $this->bumenguanli_edit();
			$flag="bumenguanli_edit";
            $edit_html = "bumen/bumen_main.html";
        }elseif($_POST['qunzuguanli_edit']){
            $this->qunzuguanli_edit();
			$flag="qunzuguanli_edit";
			$_POST['menu_type']="I01304";
            $edit_html = "qunzu/qunzu_main.html";
        }
        $menu_array = array(trim($_POST['customer_no']),trim($_POST['dept_id']),trim($_POST['group_id']));
        //sfsc 管理菜单
        $this->pagedata['qiyemenu'] = $this->getqiyemenu($flag,$menu_array);
        echo $render->fetch('site/member/'.$edit_html);
    }


    /**
    餐单栏权限调用页面
     **/
    function meuneditinput(){
        $render = new base_render(app::get('qiyecenter'));
        //企业内页调用
        if($_POST['qiyeguanli_edit']){
            $this->qiyeguanli_edit();
            $edit_html = "qiye_main.html";
        }elseif($_POST['bumenguanli_edit']){
            $this->bumenguanli_edit();
            $edit_html = "bumen_main.html";
        }elseif($_POST['qunzuguanli_edit']){
            $this->qunzuguanli_edit();
            $edit_html = "qunzu_main.html";
        }elseif($_POST['getSpecifyEmployees']){
            $this->getSpecifyEmployees_edit();
            $edit_html = "getEmployeesList.html";
        }
        $this->pagedata['customer_no'] = $_POST['id'];
        echo $render->fetch('site/member/menu/'.$edit_html);
    }
	
    /**
     * 编辑指定与为指定员工
     */
	function getSpecifyEmployees_edit(){
		//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
	}

    /**
        editinput  函数调用   企业管理 内页
    **/
    function qiyeguanli_edit(){
        $serviceNo="EmployeeService";
        $_sjson = array(
            'METHOD'=>'getEmployeeListByCustomerIds',
            'CUSTOMER_LIST'=>array(
                array(
                    'CUSTOMERIDS'=>$_POST['customer_no']
                )
            )
        );
		$params['METHOD'] = 'getSingleCustomerExt';
        $params['CUSTOMER_ID'] = $_POST['customer_no'];
		$resSF = SFSC_HttpClient::doLifCostMain($params,'CustomerService');
		 foreach($resSF['RESULT_DATA'] as $k=>$v){
                if($v['BIZ_ID'] == "customer.name"){
                    $this->pagedata['company_name'] = $v['VALUE'] ? $v['VALUE'] : "";
                }
                if($v['BIZ_ID'] == "banner.url"){
                   $this->pagedata['banner_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/grzx0601.png";
                }
                if($v['BIZ_ID'] == "log.url"){
                    $this->pagedata['log_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/yoofuu_default_logo.png";
                }
				$this->pagedata['customer_name'] = $v['CUSTOMER_NAME'];
				$this->pagedata['customer_id'] = $v['CUSTOMER_ID'];
            }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['customer_no'] =  $_POST['customer_no'];
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
    }
    /**
    editinput  函数调用   部门管理 内页
     **/
    function bumenguanli_edit(){
        $serviceNo="EmployeeService";
        $_sjson = array(
            'METHOD'=>'getEmployeeListByDeptIds',
            'DEPT_LIST'=>array(
                array(
                    'DEPTID'=>$_POST['id']
                )
            )
        );

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['customer_no'] =  $_POST['customer_no'];
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
    }


    /**
    editinput  函数调用   群组管理 内页
     **/
    function qunzuguanli_edit(){
        $serviceNo="EmployeeService";
        $_sjson = array(
            'METHOD'=>'getEmployeeListByGroupIds',
            'GROUP_LIST'=>array(
                array(
                    'GROUPID'=>$_POST['id']
                )
            )
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['customer_no'] =  $_POST['id'];
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
    }



    /**
     *  组织菜单树结构
    **/
    private function sfscTreeData($tree,$menu_array,$flag){
        global $sfsc_menu;
        $checked = "";
        $sfsc_menu .= "<ul>";
        foreach($tree as $k=>$t){
            if(in_array($t['id'],$menu_array)){
                $checked = "checked='checked'";
            }else{
                $checked = "";
            }
            $sfsc_menu .="<li><a class='expand href='#'></a><span onclick=\"switch_sfscmenu('".$t['id']."',this,'".$t['nodeType']."')\" style='cursor:pointer;'><input id='".$t['id']."' lang='".$t['nodeType']."' type='radio' ".$checked." name='bumen_id'/><span>".$t['name']."</span></span>";
            if($flag!="qiyeguanli_edit"||$t['level']=='1'){
				if(!empty($t['nodes'])){
					self::sfscTreeData($t['nodes'],$menu_array,$flag);
				}
			}
            $sfsc_menu .="</li>";
        }
        $sfsc_menu .= "</ul>";
        return $sfsc_menu;
    }

    /**
        根据id 获取对应菜单
     **/
    function getqiyemenu($flag,$menu_array=array()){
        $render = new base_render(app::get('qiyecenter'));


        if($_POST['menu_type']){
            $menu_type = trim($_POST['menu_type']);
        }else{
            $menu_type = "I01303";
        }

        $serviceNo = "TreeManagerService";

        $_sjson = array(
            'METHOD'=>'getTreeByUserId',
            'USER_ID'=>$_SESSION['account']['USER_ID'],
            'NODE_TYPE'=>$menu_type
        );

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $qi_menu_data_tmp = $sfscdata['RESULT_DATA'];

        if(!empty($qi_menu_data_tmp['nodes'])){
            $sfsc_menu = $this->sfscTreeData($qi_menu_data_tmp['nodes'],$menu_array,$flag);
        }

        $tmp = substr_replace($sfsc_menu,"<ul class='tree_ul' id='both'>",0,4);
        $this->pagedata['sfsc_menu'] = $tmp;
        $this->pagedata['sfsc_menu_type'] = $menu_type;

		$this->pagedata['customer_no'] =  $_POST['customer_no'];
        return $render->fetch('site/member/qiyemenu.html');
    }



    /**
     * 本控制器 分页公共函数
    **/
    function pagination($current,$totalPage,$act,$arg='',$app_id='qiyecenter',$ctl='site_member'){
        if (!$arg)
            $this->pagedata['pager'] = array(
                'current'=>$current,
                'total'=>$totalPage,
                'link' =>$this->gen_url(array('app'=>$app_id, 'ctl'=>$ctl,'act'=>$act,'args'=>array(($tmp = time())))),
                'token'=>$tmp,
            );
        else
        {
            $arg = array_merge($arg, array(($tmp = time())));
            $this->pagedata['pager'] = array(
                'current'=>$current,
                'total'=>$totalPage,
                'link' =>$this->gen_url(array('app'=>$app_id, 'ctl'=>$ctl,'act'=>$act,'args'=>$arg)),
                'token'=>$tmp,
            );
        }
    }
  
    /**
     * 企业中心 员工页面快速搜索 内容展示
     **/
    function  getyuangongsearch(){
        $render = new base_render(app::get('qiyecenter'));
        $serviceNo="EmployeeService";

        $_sjson = array(
            'METHOD'=>'getEmployeeListByCustomerIds',
            'CUSTOMER_LIST'=>array(
                array(
                    'CUSTOMER_ID'=>$_POST['customer_no'],
                )
            ),
            'NAME'=>$_POST['name'] ? trim($_POST['name']): "",
            'SEX'=>is_numeric($_POST['sex']) ? $_POST['sex'] : "",
            'ID'=>$_POST['id'] ? trim($_POST['id']) : "",
            'POSITION'=>$_POST['position'] ? trim($_POST['position']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        echo  $render->fetch('site/member/yuangongsearch.html');
    }

    /**
     * 企业中心 福点账户快速搜索 内容展示
     **/
    function  getzhanghusearch(){
        $render = new base_render(app::get('qiyecenter'));
        $serviceNo="SubAccountService";

        //获取福点账余额
        $_sjson1 = array(
            'METHOD'=>'getBalanceByRelationId',
            'RELATION_ID'=>$_POST['customer_no']
        );
        $sfscdata1 = SFSC_HttpClient::doLifCostMain($_sjson1,$serviceNo);

        $this->pagedata['sum_balance'] = $sfscdata1['RESULT_DATA']['SUM_BALANCE'];
        //获取获取福点账户信息
        $_sjson = array(
            'METHOD'=>'getsubAccountListByRelationId',
            'RELATION_ID'=>$_POST['customer_no'],
            'NAME'=>$_POST['name'] ? trim($_POST['name']): "",
            'SEX'=>is_numeric($_POST['sex']) ? $_POST['sex'] : "",
            'ID'=>$_POST['id'] ? trim($_POST['id']) : "",
            'POSITION'=>$_POST['position'] ? trim($_POST['position']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['zhanghuxinxi'] = $sfscdata['RESULT_DATA'];
        echo $render->fetch('site/member/zhanghusearch.html');
    }

    /**
     * 企业中心 福利订单快速搜索 内容展示
     **/

    function  getordersearch(){
        $render = new base_render(app::get('qiyecenter'));

        $_sjson = array(
            'METHOD'=>'getBizOrderByNodeId',
            'NODE_ID'=>$_POST['customer_no'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,'BizOrderService');
        $this->pagedata['sfsc_order'] = $sfscdata['RESULT_DATA'];
        echo $render->fetch('site/member/ordersearch.html');

    }
    /**
     * 企业中心 企业设置快速搜索 内容展示
     **/

    function getsettingsearch(){
        $render = new base_render(app::get('qiyecenter'));
		$params['METHOD'] = 'getSingleCustomerExt';
        $params['CUSTOMER_ID'] = $_POST['customer_no'];
		$resSF = SFSC_HttpClient::doLifCostMain($params,'CustomerService');
		 foreach($resSF['RESULT_DATA'] as $k=>$v){
                if($v['BIZ_ID'] == "customer.name"){
                    $this->pagedata['company_name'] = $v['VALUE'] ? $v['VALUE'] : "";
                }
                if($v['BIZ_ID'] == "banner.url"){
                   $this->pagedata['banner_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/grzx0601.png";
                }
                if($v['BIZ_ID'] == "log.url"){
                    $this->pagedata['log_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/yoofuu_default_logo.png";
                }
				$this->pagedata['customer_name'] = $v['CUSTOMER_NAME'];
				$this->pagedata['customer_id'] = $v['CUSTOMER_ID'];
            }
        echo $render->fetch('site/member/settingsearch.html');

    }
    /**
        企业中心 table 管理
    **/
    function getqiyetable(){
        switch ($_POST['type'])
        {
            case "totab_yuangong":
                $this->getyuangongsearch();
                break;
            case "totab_zhanghu":
                $this->getzhanghusearch();
                break;
            case "totab_order":
                $this->getordersearch();
                break;
           case "totab_setting":
                $this->getsettingsearch();
                break;
           case 'totab_getSpecifyEmployees':
            	$this->getEmployeesList();
            	break;
          case 'totab_getUnspecifyEmployees':
            	$this->getUnEmployeesList();
            	break;
            default:
                $this->getyuangongsearch();
        }
    }

    /**
         企业中心  账户详情table
    **/
    function get_qiyezhanghutable(){
        $render = new base_render(app::get('qiyecenter'));
        switch ($_POST['type'])
        {
            case "qiye_zhanghuxinxi":
                $_sjson = array(
                    'METHOD'=>'getsubAccountListByRelationId',
                    'RELATION_ID'=>$_POST['customer_no'],
                    'NAME'=>$_POST['name'] ? trim($_POST['name']): "",
                    'SEX'=>is_numeric($_POST['sex']) ? $_POST['sex'] : "",
                    'ID'=>$_POST['id'] ? trim($_POST['id']) : "",
                    'POSITION'=>$_POST['position'] ? trim($_POST['position']) : ""
                );
                $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,'SubAccountService');
                $this->pagedata['zhanghuxinxi'] = $sfscdata['RESULT_DATA'];
                break;
            case "qiye_liushuixinxi":
                $_sjson = array(
                    'METHOD'=>'GetTransactionflowByRelationId',
                    'RELATION_ID'=>$_POST['customer_no'],
                );
                $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,'TransactionflowService');
                $this->pagedata['zhanghuxinxi'] = $sfscdata['RESULT_DATA'];
                break;
            default:
        }
        $this->pagedata['type'] = $_POST['type'];
        echo $render->fetch('site/member/getqiye_zhanghu.html');
    }

    /**
        部门管理 部门设置 页面
     **/
    function getsettingsearch_bumen(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/bumen/settingsearch.html');
    }

    /**
    部门管理 员工 页面
     **/
    function getyuangongsearch_bumen(){
        $render = new base_render(app::get('qiyecenter'));
        $serviceNo="EmployeeService";
        $_sjson = array(
            'METHOD'=>'getEmployeeListByDeptIds',
            'CUSTOMER_LIST'=>array(
                array(
                    'DEPT_LIST'=>$_POST['customer_no'],
                )
            ),
            'NAME'=>$_POST['name'] ? trim($_POST['name']): "",
            'SEX'=>is_numeric($_POST['sex']) ? $_POST['sex'] : "",
            'ID'=>$_POST['id'] ? trim($_POST['id']) : "",
            'POSITION'=>$_POST['position'] ? trim($_POST['position']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        echo  $render->fetch('site/member/bumen/yuangongsearch.html');
    }

    /**
    部门管理 账户福点 页面
     **/
    function getzhanghusearch_bumen(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/bumen/zhanghusearch.html');
    }

    /**
    部门管理 福利订单 页面
     **/
    function getordersearch_bumen(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/bumen/ordersearch.html');
    }

    /**
        企业设置里面 table
     **/

    function get_qiyesettingtable(){
        $render = new base_render(app::get('qiyecenter'));
        switch ($_POST['type'])
        {
            case "qiye_setting":

                break;
            case "qiye_stylesetting":
                $this->pagedata['file_dir'] = &app::get('image')->res_url;
                $this->pagedata['IMAGE_MAX_SIZE'] = "1024*1024*5";
                break;
            case "qiye_guyuansetting":

                break;
            default:
                echo "";
        }
		$params['METHOD'] = 'getSingleCustomerExt';
        $params['CUSTOMER_ID'] = $_POST['customer_no'];
		$resSF = SFSC_HttpClient::doLifCostMain($params,'CustomerService');
		 foreach($resSF['RESULT_DATA'] as $k=>$v){
                if($v['BIZ_ID'] == "customer.name"){
                    $this->pagedata['company_name'] = $v['VALUE'] ? $v['VALUE'] : "";
                }
                if($v['BIZ_ID'] == "banner.url"){
                   $this->pagedata['banner_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/grzx0601.png";
                }
                if($v['BIZ_ID'] == "log.url"){
                    $this->pagedata['log_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/yoofuu_default_logo.png";
                }
				$this->pagedata['customer_name'] = $v['CUSTOMER_NAME'];
				$this->pagedata['customer_id'] = $v['CUSTOMER_ID'];
            }
        $this->pagedata['type'] = $_POST['type'];
        echo $render->fetch('site/member/getqiye_setting.html');
    }

     /**
      *   部门管理  table 管理
      **/
    function get_bumenzhanghutable(){
        $render = new base_render(app::get('qiyecenter'));
        switch ($_POST['type'])
        {
            case "bumen_zhanghuxinxi":
                break;
            case "bumen_liushuixinxi":
                break;
            default:
        }
        $this->pagedata['type'] = $_POST['type'];

        echo $render->fetch('site/member/bumen/getbumen_zhanghu.html');
    }

    /**
    部门管理 table切换
     **/
    function getbumentable(){
        switch ($_POST['type'])
        {
            case "totab_yuangong":
                $this->getyuangongsearch_bumen();
                break;
            case "totab_zhanghu":
                $this->getzhanghusearch_bumen();
                break;
            case "totab_order":
                $this->getordersearch_bumen();
                break;
            case "totab_setting":
                $this->getsettingsearch_bumen();
                break;
            default:
                $this->getyuangongsearch_bumen();
        }
    }
	
    function getEmployeesList(){
    	//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
    	$render = new base_render(app::get('qiyecenter'));
        echo  $render->fetch('site/member/menu/getEmployeesList.html');
    }

	function getUnEmployeesList(){
    	//获取初始化数据
    	$params = $_POST;
    	$params['METHOD'] = 'getItemUserByCondition';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'BizOrderItemUserService');
		if(!$params){
			//错误处理
		}
    	$this->pagedata['employeeList'] = $resSF['RESULT_DATA'];
    	$render = new base_render(app::get('qiyecenter'));
        echo  $render->fetch('site/member/menu/getEmployeesList.html');
    }

    function save_qiye_stylesetting(){
        $params = $this->_request->get_params(true);
        $render = new base_render(app::get('qiyecenter'));
		$type=strtolower(end(explode(".",$_FILES['Filedata']['name'])));
		$newname=date('YmdHis').rand(1,10000).".".$type;
		$dir =ROOT_DIR.'/themes/simple/images/'.$newname;
		move_uploaded_file($_FILES['Filedata']['tmp_name'],iconv('utf-8','gb2312',$dir));
		$image_src =kernel::base_url(1).'/themes/simple/images/'.$newname;
        $this->pagedata['gimage']['image_id'] = $newname;
		$this->pagedata['gimage']['image_src'] = $image_src;
        $this->pagedata['upload_type'] = $params[0];
        header('Content-Type:text/html; charset=utf-8');
        echo $render->fetch('site/member/upload/gimage.html');
    }

    /**
    群组管理 部门设置 页面
     **/
    function getsettingsearch_qunzu(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/qunzu/settingsearch.html');
    }

    /**
    群组管理 员工 页面
     **/
    function getyuangongsearch_qunzu(){
        $render = new base_render(app::get('qiyecenter'));
        $serviceNo="EmployeeService";
        $_sjson = array(
            'METHOD'=>'getEmployeeListByGroupIds',
            'CUSTOMER_LIST'=>array(
                array(
                    'GROUP_LIST'=>$_POST['customer_no'],
                )
            ),
            'NAME'=>$_POST['name'] ? trim($_POST['name']): "",
            'SEX'=>is_numeric($_POST['sex']) ? $_POST['sex'] : "",
            'ID'=>$_POST['id'] ? trim($_POST['id']) : "",
            'POSITION'=>$_POST['position'] ? trim($_POST['position']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfsc_qiyemember'] = $sfscdata['RESULT_DATA'];
        echo  $render->fetch('site/member/qunzu/yuangongsearch.html');
    }

    /**
    群组管理 账户福点 页面
     **/
    function getzhanghusearch_qunzu(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/qunzu/zhanghusearch.html');
    }

    /**
    群组管理 福利订单 页面
     **/
    function getordersearch_qunzu(){
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/qunzu/ordersearch.html');
    }

    /**
     *   群组管理  table 管理
     **/
    function get_qunzuzhanghutable(){
        $render = new base_render(app::get('qiyecenter'));
        switch ($_POST['type'])
        {
            case "bumen_zhanghuxinxi":
                break;
            case "bumen_liushuixinxi":
                break;
            default:
        }
        $this->pagedata['type'] = $_POST['type'];
        echo $render->fetch('site/member/qunzu/getqunzu_zhanghu.html');
    }

    /**
    群组管理 table切换
     **/
    function getqunzutable(){
        switch ($_POST['type'])
        {
            case "totab_yuangong":
                $this->getyuangongsearch_qunzu();
                break;
            case "totab_zhanghu":
                $this->getzhanghusearch_qunzu();
                break;
            case "totab_order":
                $this->getordersearch_qunzu();
                break;
            case "totab_setting":
                $this->getsettingsearch_qunzu();
                break;
            default:
                $this->getyuangongsearch_qunzu();
        }
    }
		function updateSingleCustomerExt(){
			$post=$_POST;
			$sdf=array();
			$i=0;
			if($post['company_name']){
				$sdf[$i]['BIZ_ID']='customer.name';
				$sdf[$i]['VALUE']=$post['company_name'];
				$sdf[$i]['CUSTOMER_ID']=$post['customer_id'];
				$i=$i+1;
			}
			if($post['logo']){
				$sdf[$i]['BIZ_ID']='log.url';
				$sdf[$i]['VALUE']=$post['logo'];
				$sdf[$i]['CUSTOMER_ID']=$post['customer_id'];
				$i=$i+1;
			}
			if($post['center']){
				$sdf[$i]['BIZ_ID']='banner.url';
				$sdf[$i]['VALUE']=$post['center'];
				$sdf[$i]['CUSTOMER_ID']=$post['customer_id'];
				$i=$i+1;
			}
			$params['METHOD'] = 'updateSingleCustomerExt';
			$params['CUSTOMER_ID'] = $post['customer_id'];
			$params['VALUE_LIST'] = $sdf;
			$resSF = SFSC_HttpClient::doLifCostMain($params,'CustomerService');
			echo '<script type="text/javascript">top.location.reload();</script>';
		}

    /*
        创建/编辑群组或者部门
    */
    function add_department(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'getDepartmentByDeptAndCompany',
            'HUMBAS_NO'=>$this->humbas_no,
            'COMPANY_NO'=>$_GET['COMPANY_NO'] ? trim($_GET['COMPANY_NO']):'',
            'DEPT_ID' =>$_GET['DEPT_ID'] ? trim($_GET['DEPT_ID']):''
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($_GET['bumenguanli_edit'] == "true"){
            $this->pagedata['bumenguanli_edit'] = 'true';
        }
        $this->pagedata['DEPT_ID'] = $_GET['DEPT_ID'];
        $this->pagedata['company']=$sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_department.html');
    }

    function add_department1(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getCompanyNameByManagerId',
            'HUMBAS_NO'=>$this->humbas_no,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['company']=$sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_department.html');
    }

    //删除群主
    function del_department(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'deleteDepartmentByDeptAndCompany',
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'HUMBAS_NO'=>$this->humbas_no,
            'DEPT_ID' =>$_POST['DEPT_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }

    function createDepartment(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'createDepartmentByHumbasNo',
            'COMPANY_NO' => $_POST['COMPANY_NO'],
            'DEPT_NAME' => $_POST['DEPT_NAME'],
            //'USER_ID'=>$_SESSION['account']['USER_ID'],
            //'COMPANY_NO'=>$_POST['customer_id'],
            //'DEPT_NAME'=>$_POST['department_name'],
            //'DEPT_NO'=>$_POST['department_no'],
            //'PARENT_ID'=>$_POST['parent_id']
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == '10001'){
            echo '<script type="text/javascript">top.location.reload();</script>';
        }else{
            echo '<script type="text/javascript">alert(\'保存失败！\')</script>';
        }

    }

    function updateDepartment(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'updateDepartmentByDeptAndCompany',
            'COMPANY_NO' => $_POST['COMPANY_NO'],  //CUSTOMER_ID
            'DEPT_ID' =>$_POST['DEPT_ID'],
            'DEPT_NAME' => $_POST['DEPT_NAME'],  //部门名称
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == '10001'){
            echo '<script type="text/javascript">top.location.reload();</script>';
        }else{
            echo '<script type="text/javascript">alert(\'保存失败！\')</script>';
        }
    }

    function add_group(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getCustomerListByUserId',
            'USER_ID'=>$_SESSION['account']['USER_ID'],
            'NODE_TYPE'=>'I01302'
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['company']=$sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_group.html');
    }

    function createGroup(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'createGroup',
            'USER_ID'=>$_SESSION['account']['USER_ID'],
            'COMPANY_NO'=>$_POST['customer_id'],
            'GROUP_NAME'=>$_POST['group_name'],
            'GROUP_NO'=>$_POST['group_no']
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        echo '<script type="text/javascript">top.location.reload();</script>';
    }


    public function orders($type='',$order_id='',$goods_name='',$goods_bn='',$time='',$pay_status='',$nPage=1)
    {
        //进入页面是需要调用订单操作脚本
        $obj_filter = kernel::single('b2c_site_filter');
        $type = mysql_real_escape_string($obj_filter->check_input($type));
        $order_id = mysql_real_escape_string($obj_filter->check_input($order_id));
        $goods_name = mysql_real_escape_string($obj_filter->check_input($goods_name));
        $goods_bn = mysql_real_escape_string($obj_filter->check_input($goods_bn));
        $time = mysql_real_escape_string($obj_filter->check_input($time));
        $pay_status = mysql_real_escape_string($obj_filter->check_input($pay_status));
        $nPage = mysql_real_escape_string($obj_filter->check_input($nPage));

        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('我的订单'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $order = app::get('b2c')->model('orders');
        $order_id = trim($order_id);
        $goods_name = trim($goods_name);
        $goods_bn = trim($goods_bn);
        /**
         *以下方法只需与数据库交互1次，但是需要循环所有该会员下的订单
         **/
        $member_orders_all=$order->db->select("select pay_status,status,ship_status,confirm,comments_count from ".kernel::database()->prefix ."b2c_orders where member_id=".$this->app->member_id." and order_type <> 'sand'");
        $type_orders_count=array ('all' =>0,'shiped' =>0,'dead' =>0,'ship' =>0,'comment' =>0,'finish' =>0,'nopayed'=>0,'confirm'=>0);
        $type_orders_count['all']=count($member_orders_all);
        foreach($member_orders_all as $moa_key=>$moa_value){
            if($moa_value['pay_status']==0 &&$moa_value['status']=='active'){
                $type_orders_count['nopayed']=$type_orders_count['nopayed']+1;//待付款
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==0&&$moa_value['status']=='active'){
                $type_orders_count['ship']=$type_orders_count['ship']+1;//待发货
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==1&&$moa_value['status']=='active'){
                $type_orders_count['shiped']=$type_orders_count['shiped']+1;//待收货
            }else if($moa_value['status']=='finish'){
                if($moa_value['comments_count']==0){
                    $type_orders_count['comment']=$type_orders_count['comment']+1;//未评论
                }
                $type_orders_count['finish']=$type_orders_count['finish']+1;//已完成
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==1&&$moa_value['status']=='active' &&$moa_value['confirm']=='N' ){
                $type_orders_count['confirm']=$type_orders_count['confirm']+1;//待确认
            }else if($moa_value['status']=='dead'){
                $type_orders_count['dead']=$type_orders_count['dead']+1;//作废
            }

        }
        $sql = $this->get_search_order_ids($type,$time);
        $arrayorser = $order->db->select($sql);
        if($type==''){
            $type_orders_count['all']=count($arrayorser);
        }else{
            $type_orders_count[$type]=count($arrayorser);
        }
        //给会员中心订单列表标签增加数量显示end
        $search_order=$this->search_order($order_id,$goods_name,$goods_bn);
        foreach($arrayorser as $key=>$value){
            foreach($search_order as $k=>$v){
                if($value['order_id']==$v['order_id']){
                    $arr[]=$value;
                }
            }
        }
        $arrayorser=$arr;
        if(empty($arrayorser)){
            $msg='没有找到相应的订单！';
        }else{
            $aData = $order->fetchByMember($this->app->member_id,$nPage-1,'','',$arrayorser);
        }
        $this->get_order_details($aData,'member_orders');
        $oImage = app::get('image')->model('image');
        $imageDefault = app::get('image')->getConf('image.set');
        $applySObj = app::get('spike')->model('spikeapply');
        $applyGObj = app::get('groupbuy')->model('groupapply');
        $applyScoreObj = app::get('scorebuy')->model('scoreapply');
        foreach($aData['data'] as $k=>$v) {
            //获取订单支付时间
            $obj_payment = app::get('ectools')->model('refunds');
            $payment_id = $obj_payment->get_payment($v['order_id']);
            $pay_time = app::get('ectools')->model('payments')->getRow('t_payed',array('payment_id'=>$payment_id['bill_id']));
            $aData['data'][$k]['pay_time'] = $pay_time['t_payed'];
            $obj_aftersales = app::get('aftersales')->model('return_product');
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'3','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_send'] = 1;
            }else{
                $aData['data'][$k]['need_send'] = 0;
            }
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'11','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_edit'] = 1;
            }else{
                $aData['data'][$k]['need_edit'] = 0;
            }
            //end
            foreach($v['goods_items'] as $k2=>$v2) {
                if( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$v['image_default_id']))){
                    $aData['data'][$k]['goods_items'][$k2]['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                }
                $act_id = '';
                //秒杀详细页参数
                switch($v['order_type']){
                    case 'spike':
                        $act_id = $applySObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'group':
                        $act_id = $applyGObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'score':
                        $act_id = $applyScoreObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'normal':
                        break;
                }
                if($act_id){
                    $aData['data'][$k]['goods_items'][$k2]['product']['args'] = array($v2['product']['goods_id'],'','',$act_id);
                }
            }
            //获取买家/卖家
            $obj_members = app::get('pam')->model('account');
            $buy_name = $obj_members->getRow('login_name',array('account'=>$v['member_id']));
            $aData['data'][$k]['buy_name'] = $buy_name['login_name'];
            $obj_strman = app::get('business')->model('storemanger');
            $seller_id = $obj_strman->getRow('account_id,store_idcardname',array('store_id'=>$v['store_id']));
            $seller_name = $obj_members->getRow('login_name',array('account_id'=>$seller_id['account_id']));
            $aData['data'][$k]['seller_name'] = $seller_name['login_name'];
            $aData['data'][$k]['seller_real_name'] = $seller_id['store_idcardname'];
        }

        //添加订单html埋点
        foreach($aData['data'] as $k=>$v){
            foreach(kernel::servicelist('business.member_orders') as $service){
                if(is_object($service)){
                    if(method_exists($service,'get_orders_html')){
                        $aData['data'][$k]['html'] .= $service->get_orders_html($v);
                    }
                }
            }

            if($aData['data'][$k]['order_kind']=='b2c_card'&&$aData['data'][$k]['pay_status']=='1'&&$aData['data'][$k]['ship_status']=='1'){
                //短信重发功能
                $url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'send_message', 'arg0' => $aData['data'][$k]['order_id']));
                $aData['data'][$k]['html'] = $aData['data'][$k]['html']."
                <a href=".$url." class='font-blue operate-btn'>重发</a>";
            }
        }
        $this->pagedata['type_orders_count']=$type_orders_count;
        $this->pagedata['msg']=$msg;
        $this->pagedata['orders'] = $aData['data'];
        $this->pagedata['orders_nums'] = count($aData['data']);
        //下拉框数据 --start
        $this->pagedata['select']['time']['options'] = $this->get_select_date();
        $this->pagedata['select']['time']['value'] = $time;
        //下拉框数据 --end
        //获取传过来的参数
        $this->pagedata['type'] =$type;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['goods_name'] = $goods_name;
        $this->pagedata['goods_bn'] = $goods_bn;
        $this->pagedata['time'] = $time;
        //修改分页链接参数 --start
        $arr_args = array($type,$order_id,$goods_name,$goods_bn,$time,$pay_status);
        //--end
        $this->pagination($nPage,$aData['pager']['total'],'orders',$arr_args);
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->output();
    }
    //重发电子券
    //发送电子码短信（以订单为单位）
    public function send_message($order_id){
        $this->begin();
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'orders'));
        $obj_pass_log=kernel::single('cardcoupons_mdl_cards_pass_log');
        $obj_pass= kernel::single('cardcoupons_mdl_cards_pass');
        $obj_orders=kernel::single('b2c_mdl_orders');
        $obj_cards = app::get('cardcoupons')->model('cards');
        $flag = false;
        $orders=$obj_orders->getList('*',array('order_id'=>$order_id));
        $order=$orders[0];
        if($order['pay_status']!='1'){
            $this->end(false,app::get('b2c')->_('订单尚未完成付款！'),$url);
        }
        $arrMemberInfo = kernel::single("pam_mdl_account")->getList("*",array("account_id"=>$order['member_id']));
        $card_pass=$obj_pass->getList('*',array('order_id'=>$order_id,'type'=>'virtual'));

        if(empty($card_pass)){
            $this->end(false,app::get('qiyecenter')->_('没有可重发短信的电子码！'),$url);
        }
        foreach($card_pass as $key => $value){
            //默认短信内容
            $content=$arrMemberInfo[0]['login_name'].'你好，你的'.$value['card_name'].'卡号：'.$value['card_no'].'.密码：'.$value['card_pass'];

            $arrCards = $obj_cards->getList("*",array("card_id"=>$value['card_id']));
            //短信模板
            $msg_templet = $arrCards[0]['msg_templet'];
            $msg_templet = str_replace('<{$user_name}>',$arrMemberInfo[0]['login_name'],$msg_templet);
            $msg_templet = str_replace('<{$card_name}>',$value['card_name'],$msg_templet);
            $msg_templet = str_replace('<{$card_no}>',$value['card_no'],$msg_templet);
            $value['from_time'] = date('Y年m月d日',$value['from_time']);
            $value['to_time'] = date('Y年m月d日',$value['to_time']);
            $msg_templet = str_replace('<{$date_start}>',$value['from_time'],$msg_templet);
            $msg_templet = str_replace('<{$date_end}>',$value['to_time'],$msg_templet);

            $msg_templet = str_replace('<{$card_password}>',$value['card_pass'],$msg_templet);

            if($msg_templet){
                $content = $msg_templet;
            }
            //订单日志上记录全部信息
            $content_log=$arrMemberInfo[0]['login_name'].'你好，你的'.$value['card_name'].'卡号：'.$value['card_no'].'.密码：'.'<span class="show_card_pass">'.$value['card_pass_ori'].'</span> 卡券有效期截止:'.$value['to_time'];
            
            //在订单详情页显示卡密等信息-hy
            $objorder_log =kernel::single('b2c_mdl_order_log');
            $sdf_order_log = array(
                'rel_id' => $order_id,
                'op_id' => '0',
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'SUCCESS',
                'log_text' => $content_log,
                'addon' => $log_addon,
            );
            $objorder_log->save($sdf_order_log);
            
            $_sjson = array(
                'METHOD'=>'sendMessage',
                'PHONENO'=>$order['ship_mobile'],
                'MESSAGE'=>$content,
                'SENDUSER_TYPE'=>'HUMBAS_NO',
                //'RELATION_ID'=>$arrMemberInfo[0]['login_name']
                'RELATION_ID'=>substr($arrMemberInfo[0]['login_name'],2)
            );
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' =>$_SESSION['account']['user_data']['account']['account_id'],
                'op_name' =>$_SESSION['account']['user_data']['account']['login_name'],
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'FAILURE',
                'log_text' => serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$value['card_name'].'</span>短信发送'.$value['card_no'].'失败','data'=>array()))),
            );
            $post_data = array('serviceNo'=>'SendMessageService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            if($tmpdata != null && gettype($tmpdata) == "object"){
                $getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
                if($getSubActList['RESULT_CODE'] == "10001"){
                    $order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$value['card_name'].'</span>短信发送'.$value['card_no'].'成功','data'=>array())));
                    $pass_log_data['status']='1';
                    $flag = true;
                }else{
                    //记录发送失败
                    $order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$value['card_name'].'</span>短信发送'.$value['card_no'].'失败','data'=>array())));
                    $pass_log_data['status']='2';
                }
            }else{
                //记录调用短信接口失败
                $order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$value['card_name'].'</span>短信发送'.$value['card_no'].'异常','data'=>array())));
                $pass_log_data['status']='3';
            }
            $order_log_re=$objorder_log->save($order_log);
            $pass_log_data['card_no']   =$value['card_no']?$value['card_no']:' ';
            $pass_log_data['mobile']    =$order['ship_mobile'];
            $pass_log_data['card_pass'] =$value['card_pass_ori'];
            $pass_log_data['time']      =time();
            $save=$obj_pass_log->save($pass_log_data);
            
        }
        if($flag){
            $this->end(true,app::get('qiyecenter')->_('重新发送短信成功！'),$url);
        }else{
            $this->end(false,app::get('qiyecenter')->_('重新发送短信失败！'),$url);
        }

    }
    //确认收货页面
    public function dofinish($order_id){
        // echo 'aaa2';
        // varr_dump($orders_id);
        // exit();
        $obj_filter = kernel::single('b2c_site_filter');
        $order_id = $obj_filter->check_input($order_id);

        $this->pagedata['order_id'] = $order_id;
        $obj_order = app::get('business')->model('orders');
        $time = $obj_order->dump($order_id,'*');

        $this->pagedata['time'] = ($time['confirm_time'])*1000;
        $this->pagedata['now_time'] = time()*1000;
        $this->pagedata['base_url'] = kernel::base_url();
        $this->pagedata['path'] = app::get('site')->res_full_url;

        $objOrder = &$this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))), 'order_pmt'=>array('*'));
        $sdf_order = $objOrder->dump($order_id, '*', $subsdf);
        $objMath = kernel::single("ectools_math");

        if($sdf_order['member_id']){
            $member = &$this->app->model('members');
            $aMember = $member->dump($sdf_order['member_id'], 'email');
            $sdf_order['receiver']['email'] = $aMember['contact']['email'];
        }

        // 处理收货人地区
        $arr_consignee_area = array();
        $arr_consignee_regions = array();
        if (strpos($sdf_order['consignee']['area'], ':') !== false)
        {
            $arr_consignee_area = explode(':', $sdf_order['consignee']['area']);
            if ($arr_consignee_area[1])
            {
                if (strpos($arr_consignee_area[1], '/') !== false)
                {
                    $arr_consignee_regions = explode('/', $arr_consignee_area[1]);
                }
            }

            $sdf_order['consignee']['area'] = (is_array($arr_consignee_regions) && $arr_consignee_regions) ? $arr_consignee_regions[0] . $arr_consignee_regions[1] . $arr_consignee_regions[2] : $sdf_order['consignee']['area'];
        }

        // 订单的相关信息的修改
        $obj_other_info = kernel::servicelist('b2c.order_other_infomation');
        if ($obj_other_info)
        {
            foreach ($obj_other_info as $obj)
            {
                $this->pagedata['discount_html'] = $obj->gen_point_discount($sdf_order);
            }
        }
        $this->pagedata['order'] = $sdf_order;

        $order_items = array();
        $gift_items = array();
        $this->get_order_detail_item($sdf_order,'member_order_detail');
        $this->pagedata['order'] = $sdf_order;

        /** 去掉商品优惠 **/
        if ($this->pagedata['order']['order_pmt'])
        {
            foreach ($this->pagedata['order']['order_pmt'] as $key=>$arr_pmt)
            {
                if ($arr_pmt['pmt_type'] == 'goods')
                {
                    unset($this->pagedata['order']['order_pmt'][$key]);
                }
            }
        }
        /** end **/

        // 得到订单留言.
        $oMsg = &kernel::single("b2c_message_order");
        $arrOrderMsg = $oMsg->getList('*', array('order_id' => $order_id, 'object_type' => 'order'), $offset=0, $limit=-1, 'time DESC');

        $this->pagedata['ordermsg'] = $arrOrderMsg;
        $this->pagedata['res_url'] = $this->app->res_url;

        // 生成订单日志明细
        //$oLogs =&$this->app->model('order_log');
        //$arr_order_logs = $oLogs->getList('*', array('rel_id' => $order_id));
        $arr_order_logs = $objOrder->getOrderLogList($order_id);

        // 取到支付单信息
        $obj_payments = app::get('ectools')->model('payments');
        $this->pagedata['paymentlists'] = $obj_payments->get_payments_by_order_id($order_id);

        // 支付方式的解析变化
        $obj_payments_cfgs = app::get('ectools')->model('payment_cfgs');
        $arr_payments_cfg = $obj_payments_cfgs->getPaymentInfo($this->pagedata['order']['payinfo']['pay_app_id']);

        //物流跟踪安装并且开启
        $logisticst = app::get('b2c')->getConf('system.order.tracking');
        $logisticst_service = kernel::service('b2c_change_orderloglist');
        if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
            $this->pagedata['services']['logisticstack'] = $logisticst_service;
        }

        $this->pagedata['orderlogs'] = $arr_order_logs['data'];


        $this->output('qiyecenter');
    }
    /**
     * 订单完成
     * @params string oder id
     * @return boolean 成功与否
     */
    public function gofinish()
    {
        $this->begin($this->gen_url(array('app' =>'qiyecenter','ctl'=>'site_member','act' =>'orders')));
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();

        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if (!$obj_checkorder->check_order_finish($_POST['order_id'],'',$message))
        {
            $db->rollback();
            $this->end(false, $message);
        }

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');

        $sdf['order_id'] = $_POST['order_id'];
        $arrMember = $this->get_current_member();
        $sdf['op_id'] = $arrMember['member_id'];
        $sdf['opname'] = $arrMember['uname'];
        $sdf['confirm_time'] = time();

        $b2c_order_finish = kernel::single("b2c_order_finish");

        $system_money_decimals = $this->app->getConf('system.money.decimals');
        $system_money_operation_carryset = $this->app->getConf('system.money.operation.carryset');

        if ($b2c_order_finish->generate($sdf, $this, $message))
        {
            //买家确认收货成功，更新收货状态为买家确认收货
            $objOrders = app::get('b2c')->model('orders');
            $arr_data['receiving_state'] = '1';
            $arr_data['order_id'] = $sdf['order_id'];
            $objOrders->save($arr_data);
            //生成结算单
            $obj_order = &$this->app->model('orders');
            $money = $obj_order->getRow('payed,pmt_order,cost_freight,is_protect,cost_protect,cost_payment,member_id,ship_status,score_u,score_g,discount_value',array('order_id'=>$_POST['order_id']));
            $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));

            $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
            $sdf_order = $obj_order->dump($sdf['order_id'],'*',$subsdf);

            $refunds = app::get('ectools')->model('refunds');
            unset($sdf['inContent']);

            $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');
            $sdf['payment'] = ($sdf['payment']) ? $sdf['payment'] : $sdf_order['payinfo']['pay_app_id'];

            $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

            $time = time();
            $sdf['pay_app_id'] = $sdf['payment'];
            $sdf['member_id'] = $sdf_order['member_id'] ? $sdf_order['member_id'] : 0;
            $sdf['currency'] = $sdf_order['currency'];
            $sdf['paycost'] = 0;
            //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
            $sdf['t_begin'] = $time;
            $sdf['t_payed'] = $time;
            $sdf['t_confirm'] = $time;
            $sdf['pay_object'] = 'order';

            $return_product_obj = app::get('aftersales')->model('return_product');
            $returns = $return_product_obj->getList('amount',array('order_id'=>$sdf['order_id'],'refund_type|in'=>array('3','4'),'status'=>'3'));
            if($returns[0]['amount']){
                //部分退款的确认收货
                if($money['is_protect']){
                    $cost_freight = $money['cost_freight']+$money['cost_payment']+$money['cost_protect']-$returns[0]['amount'];
                }else{
                    $cost_freight = $money['cost_freight']+$money['cost_payment']-$returns[0]['amount'];
                }
                if($money['discount_value'] > 0){
                    $total_money = ($money['payed'])+$money['pmt_order']-$cost_freight+$money['discount_value'];
                }else{
                    $total_money = ($money['payed'])+$money['pmt_order']-$cost_freight;
                }
                $obj_items = $this->app->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));
                //退款金额小于运费
                if($cost_freight >= 0){
                    $profit = 0;
                    foreach($items as $k=>$v){
                        $obj_cat = $this->app->model('goods_cat');
                        $obj_goods = $this->app->model('goods');
                        $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                        if(app::get('b2c')->getConf('member.isprofit') == 'true'){
                            $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                            if(is_null($profit_point['profit_point'])){
                                $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                                $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                            }
                        }else{
                            $profit_point['profit_point'] = 0;
                        }
                        if($total_money>0){
                            $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                        }else{
                            $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                        }
                    }
                    $freight_pro = app::get('b2c')->getConf('member.profit');
                    $profit = $profit + $cost_freight*($freight_pro/100);
                }else{
                    $freight_pro = app::get('b2c')->getConf('member.profit');
                    if($money['discount_value'] > 0){
                        $total_money = ($money['payed']+($money['discount_value']))*($freight_pro/100);
                    }else{
                        $total_money = ($money['payed'])*($freight_pro/100);
                    }
                }
                //计算系统价格
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['discount_value'] > 0){
                    $sdf['money'] = ($money['payed']+($money['discount_value']))-$profit;
                }else{
                    $sdf['money'] = ($money['payed'])-$profit;
                }

                if($money['score_g'] > 0){
                    $sdf['money'] = $sdf['money']-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }

                //end
                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];

                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';
                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;

                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $db->rollback();
                     $this->end(false, $message);
                }
                $obj_refunds = kernel::single("ectools_refund");
                $rs_seller = $obj_refunds->generate($sdf, $this, $msg);

                // 增加经验值
                $obj_member = $this->app->model('members');
                $obj_member->change_exp($money['member_id'], floor($total_money));
            }elseif($money['ship_status'] == '3'){
                //部分退货的确认收货
                $obj_items = $this->app->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));

                $payed = 0;
                foreach($items as $k=>$v){
                    $payed = $payed+$v['price']*$v['sendnum'];
                }
                $payed = $payed - $money['pmt_order'];
                //剩余可打金额
                $return_product_obj = app::get('aftersales')->model('return_product');
                $amount = $return_product_obj->getRow('amount',array('order_id'=>$sdf['order_id'],'status'=>'6'));
                if($money['discount_value'] > 0){
                    $money_useful = ($money['payed'])+($money['discount_value']);
                }else{
                    $money_useful = ($money['payed']);
                }
                //剩余杂费
                $cost_freight = $money_useful - $payed;

                $total_money = $payed+$money['pmt_order'];

                $profit = 0;
                foreach($items as $k=>$v){
                    $obj_cat = $this->app->model('goods_cat');
                    $obj_goods = $this->app->model('goods');
                    $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                    if(app::get('b2c')->getConf('member.isprofit') == 'true'){
                        $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                        if(is_null($profit_point['profit_point'])){
                            $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                            $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                        }
                    }else{
                        $profit_point['profit_point'] = 0;
                    }
                    if($total_money>0){
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                    }else{
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                    }
                }
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $profit = $profit + $cost_freight*($freight_pro/100);

                //计算系统价格
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['score_g'] > 0){
                    $sdf['money'] = $money_useful-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }else{
                    $sdf['money'] = $money_useful-$profit;
                }
                //end

                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];

                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';

                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;

                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $db->rollback();
                     $this->end(false, $message);
                }

                $obj_refunds = kernel::single("ectools_refund");
                $rs_seller = $obj_refunds->generate($sdf, $this, $msg);

                // 增加经验值
                $obj_member = $this->app->model('members');
                $obj_member->change_exp($money['member_id'], floor($total_money));
            }else{
                //进行提成计算（正常流程）
                if($money['is_protect']){
                    $cost_freight = $money['cost_freight']+$money['cost_payment']+$money['cost_protect'];
                }else{
                    $cost_freight = $money['cost_freight']+$money['cost_payment'];
                }
                if($money['discount_value'] > 0){
                    $total_money = $money['payed']+$money['pmt_order']-$cost_freight+($money['discount_value']);
                }else{
                    $total_money = $money['payed']+$money['pmt_order']-$cost_freight;
                }
                $obj_items = $this->app->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));

                $profit = 0;
                foreach($items as $k=>$v){
                    $obj_cat = $this->app->model('goods_cat');
                    $obj_goods = $this->app->model('goods');
                    $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                    if(app::get('b2c')->getConf('member.isprofit') == 'true'){
                        $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                        if(is_null($profit_point['profit_point'])){
                            $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                            $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                        }
                    }else{
                        $profit_point['profit_point'] = 0;
                    }
                    if($total_money>0){
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                    }else{
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                    }
                }
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $profit = $profit + $cost_freight*($freight_pro/100);

                //计算系统价格
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['discount_value'] > 0 && $money['score_g'] > 0){
                    $sdf['money'] = $money['payed']+($money['discount_value'])-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }elseif($money['discount_value'] > 0 && $money['score_g'] == 0){
                    $sdf['money'] = $money['payed']+($money['discount_value'])-$profit;
                }elseif($money['discount_value'] == 0 && $money['score_g'] > 0){
                    $sdf['money'] = $money['payed']-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }else{
                    $sdf['money'] = $money['payed']-$profit;
                }
                //end

                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];

                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';
                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;

                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $db->rollback();
                     $this->end(false, $message);
                }

                $obj_refunds = kernel::single("ectools_refund");
                $rs_seller = $obj_refunds->generate($sdf, $this, $msg);

                // 增加经验值
                $obj_member = $this->app->model('members');
                $obj_member->change_exp($money['member_id'], floor($total_money));
            }

            //$this->updateRank($sdf['order_id']);
            if($rs_seller){
                $refund = app::get('ectools')->model('refunds');

                $obj_refunds = kernel::single("ectools_refund");
                $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$refund_id,'refund_type'=>'2'));

                $db->commit($transaction_status);
                $this->end(true, '确认收货成功！');

            }else{
                $db->rollback();
                $this->end(false,'生成结算单失败');
            }

        }
        else
        {
            $db->rollback();
            $this->end(false, app::get('b2c')->_('确认收货失败！'));
        }
    }
           /**
     * 生成退款单页面
     * @params string order id
     * @return string html
     */
    public function gorefund_mai($order_id,$type=0,$page=1)
    {
        if($type==3){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'gorefund_mai_3','arg0'=>$order_id));
        }elseif($type==4){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'gorefund_mai_4','arg0'=>$order_id));
        }
        $this->begin($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member')));

        $objOrder = &$this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.   
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        //$this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);
        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }
        
        $point_money_value = app::get('b2c')->getConf('site.point_money_value');

        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }
        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';

                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        
        $count = count($order_items);
        //$arrMaxPage = $this->get_start($page, $count);
        //$this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'aftersales', 'site_member');
        $this->pagedata['url'] = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));
        
        //echo '<pre>';print_r($this->pagedata);exit;
        $this->output('qiyecenter');
    }
    public function return_save_mai()
    {
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        
        if(! $_POST){
            $this->end(false, app::get('qiyecenter')->_("缺少必要的数据！"));
        }

        
        if($_POST['edit'] == 'edit'){
            $rp = app::get('aftersales')->model('return_product');
            $obj_order = app::get('b2c')->model('orders');
            $rp->update(array('status'=>'13'),array('return_id'=>$_POST['return_id']));
            $obj_order->update(array('refund_status'=>'2'),array('order_id'=>$_POST['order_id']));
        }

        //echo '<pre>';print_r($_POST);exit;

        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('aftersales')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('aftersales')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product'])
        {
            $this->end(false, app::get('aftersales')->_("售后服务信息没有开启！"));
        }

        $upload_file = "";
        if ( $_FILES['file']['size'] > 5242880 )
        {
            if($_POST['type'] == '3'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
            }elseif($_POST['type'] == '4'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('aftersales')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '3'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
                }elseif($_POST['type'] == '4'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id = $mdl_img->store($_FILES['file']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
            $aData['image_file'] = $image_id;
        }

        //添加两张维权图片
        if ( $_FILES['file1']['size'] > 5242880 )
        {
            if($_POST['type'] == '3'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
            }elseif($_POST['type'] == '4'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('aftersales')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file1']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file1']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '3'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
                }elseif($_POST['type'] == '4'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file1']['name'];
            $image_id = $mdl_img->store($_FILES['file1']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
            $aData['image_file1'] = $image_id;
        }


        if ( $_FILES['file2']['size'] > 5242880 )
        {
            if($_POST['type'] == '3'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
            }elseif($_POST['type'] == '4'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('aftersales')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file2']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file2']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '3'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
                }elseif($_POST['type'] == '4'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file2']['name'];
            $image_id = $mdl_img->store($_FILES['file2']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
            $aData['image_file2'] = $image_id;
        }

       


        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $product_data = array();
        foreach ((array)$_POST['product_bn'] as $key => $val)
        {
            if ($_POST['product_item_nums'][$key] < intval($_POST['product_nums'][$key]))
            {
                if($_POST['type'] == '3'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_3', 'arg0' => $_POST['order_id']));
                }elseif($_POST['type'] == '4'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai_4', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('aftersales')->_("申请售后商品的数量不能大于订购数量: "), $com_url);
            }

            $item = array();
            $item['bn'] = $val;
            $item['name'] = $_POST['product_name'][$key];
            $item['num'] = intval($_POST['product_nums'][$key]);
            $product_data[] = $item;
        }
        $objOrder = &$this->app->model('orders');
        $store_id = $objOrder->getRow('store_id,score_u,payed',array('order_id'=>$_POST['order_id']));

        $sto= kernel::single("business_memberstore",$_POST['member_id']);
        $aData['store_id'] = $store_id['store_id'];
        $aData['order_id'] = $_POST['order_id'];
        $aData['add_time'] = time();
        //$aData['member_id'] = $this->member['member_id'];
        $aData['member_id'] = $_POST['member_id'];
        $aData['product_data'] = serialize($product_data);
        $aData['content'] = $_POST['content'];
        $aData['status'] = 1;

        $point_money_value = app::get('b2c')->getConf('site.point_deductible_value');
        if($_POST['gorefund_price'] > $store_id['payed']){
            $amount = $store_id['payed'];
            if($point_money_value != 0){
                $return_score = floor(($_POST['gorefund_price']-$store_id['payed'])/$point_money_value);
            }
            $score_u = $store_id['score_u'] - $return_score;
        }else{
            $amount = $_POST['gorefund_price'];
        }

        $aData['amount'] = $amount;
        $aData['return_score'] = $return_score;
        $aData['close_time'] = time()+86400*(app::get('b2c')->getConf('member.to_agree'));
        $aData['comment'] = $_POST['comment'];
        if($_POST['type']){
            $aData['refund_type'] = $_POST['type'];
        }
        if($_POST['edit'] == 'edit'){
            $aData['old_return_id'] = $_POST['return_id'];
        }
        $msg = "";
        $obj_aftersales = kernel::service("api.aftersales.request");
        //echo '<pre>';print_r($aData);exit;
        if ($obj_aftersales->generate($aData, $msg))
        {
            $obj_rpc_request_service = kernel::service('b2c.rpc.send.request');
            if ($obj_rpc_request_service && method_exists($obj_rpc_request_service, 'rpc_caller_request'))
            {
                if ($obj_rpc_request_service instanceof b2c_api_rpc_request_interface)
                    $obj_rpc_request_service->rpc_caller_request($aData,'aftersales');
            }
            else
            {
                $obj_aftersales->rpc_caller_request($aData);
            }
            //停止确认收货时间
            $confirm_time = $objOrder->getRow('confirm_time,status',array('order_id'=>$_POST['order_id']));
            if($confirm_time['confirm_time']){
                $time = $confirm_time['confirm_time'] - time();
            }else{
                $time = $confirm_time['confirm_time'];
            }
            //修改订单状态
            if($_POST['edit'] == 'edit' || $confirm_time['status'] == 'finish'){
                $refund_status = array('refund_status'=>'1');
            }else{
                $refund_status = array('refund_status'=>'1','confirm_time'=>$time);
            }
            $rs = $objOrder->update($refund_status,array('order_id'=>$_POST['order_id']));

            if($rs){
                $this->end(true, app::get('b2c')->_('提交成功！'), $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
            }else{
                $this->end(false,app::get('b2c')->_('提交成功！更新订单状态失败！'), $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
            }
        }
        else
        {
            $this->end(false, $msg, $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
        }
    }
    public function gorefund_mai_3($order_id){
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $this->pagedata['type'] = 3;
        $objOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        //$this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);
        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }
        
        $point_money_value = app::get('b2c')->getConf('site.point_money_value');

        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }
        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';

                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        
        $count = count($order_items);
        //$arrMaxPage = $this->get_start($page, $count);
        //$this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'aftersales', 'site_member');
        // $this->pagedata['url'] = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));
        
        //echo '<pre>';print_r($this->pagedata);exit;
        $this->output();
    }
    function edit_rp(){
        $rp = app::get('aftersales')->model('return_product');
        $obj_order = &$this->app->model('orders');

        $url = $this->gen_url(array('app' =>'aftersales','ctl'=>'site_member','act' =>'return_list'));
        //处理申请单
        $order_id = $rp->getRow('*',array('return_id'=>$_POST['return_id']));
        if($order_id['shop_cost'] || $order_id['amount_seller']){
            $total = $order_id['shop_cost']+$order_id['amount_seller']+$order_id['amount'];
            $status['amount'] = $_POST['amount'];
            $status['shop_cost'] = $order_id['shop_cost'];
            $amount_seller = $total - $status['shop_cost'] - $status['amount'];
            $status['amount_seller'] = $amount_seller;
        }else{
            $status['amount'] = $_POST['amount'];
        }

        //如果是售后，修改卖家需要承担的金额(暂时处理方式：卖家承担的金额减去修改差额)
        if($order_id['is_safeguard'] == '2'){
            $status['seller_amount'] = $order_id['seller_amount'] - ($order_id['amount'] - $_POST['amount']);
        }
        $status['status'] = '12';
        $status['close_time'] = time()+86400*(app::get('b2c')->getConf('member.to_agree'));
        
        $retutn = $rp->update($status,array('return_id'=>$_POST['return_id']));

        
        //修改订单状态
        $refund_status = array('refund_status'=>'5');
        $rs = $obj_order->update($refund_status,array('order_id'=>$order_id['order_id']));

        //添加退款日志
        if ($this->member['member_id'])
        {
            $obj_members = app::get('b2c')->model('members');
            $arrPams = $obj_members->dump($this->member['member_id'], '*', array(':account@pam' => array('*')));
        }

        $log_text = "买家修改退款申请,修改金额为：".$_POST['amount']."元";
        $result = "SUCCESS";
        $returnLog = app::get('aftersales')->model("return_log");
        $sdf_return_log = array(
            'order_id' => $order_id['order_id'],
            'return_id' => $_POST['return_id'],
            'op_id' => $this->member['member_id'],
            'op_name' => (!$this->member['member_id']) ? app::get('b2c')->_('卖家') : $arrPams['pam_account']['login_name'],
            'alttime' => time(),
            'behavior' => 'updates',
            'result' => $result,
            'role' => 'member',
            'log_text' => $log_text,
        );

        $log_id = $returnLog->save($sdf_return_log);

        $objOrderLog = app::get('b2c')->model("order_log");

        $sdf_order_log = array(
            'rel_id' => $order_id['order_id'],
            'op_id' => $this->member['member_id'],
            'op_name' => (!$this->member['member_id']) ? app::get('b2c')->_('卖家') : $arrPams['pam_account']['login_name'],
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'refunds',
            'result' => 'SUCCESS',
            'log_text' => $log_text,
        );
        $log_id = $objOrderLog->save($sdf_order_log);

        $this->splash('success',$url,app::get('qiyecenter')->_('修改成功'));
    }
   /**
     * 下载售后附件
     * @param string return id
     * @return null
     */
    public function file_download($return_id,$image_file)
    {
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $obj_return_policy->file_download($return_id,$image_file);
    }
    public function gorefund_mai_4($order_id){
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $this->pagedata['type'] = 4;
        $objOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        //$this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);
        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }
        
        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        
        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }
        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';

                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;

        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        
        $count = count($order_items);

        //$arrMaxPage = $this->get_start($page, $count);
        //$this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'aftersales', 'site_member');
        $this->pagedata['url'] = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));
        
        //echo '<pre>';print_r($this->pagedata);exit;
        $this->output();
    }
    /**
     *订单评论
     * @params string order id
     * @return string html
     */
     function discuss($order_id=0){
        $this -> path[] = array('title' => app :: get('qiyecenter') -> _('评论宝贝'), 'link' => '#');
        $GLOBALS['runtime']['path'] = $this -> path;
        $this->pagedata['comment_goods_type'][] = array('type_id'=>0,'name'=>'商品评分');
        $objCommentType = $this->app_b2c->model('comment_goods_type');
        $comment_type = $objCommentType->getList('*');
        if(!$comment_type){
            $sdf['type_id'] = 1;
            $sdf['name'] = app::get('b2c')->_('宝贝与描述相符');
            $addon['is_total_point'] = 'on';
            $addon['description'] = array(5 => '质量非常好，与卖家描述的完全一致，非常满意',
                            4 => '质量不错，与卖家描述的基本一致，还是挺满意的',
                            3 => '质量一般，没有卖家描述的那么好',
                            2 => '部分有破损，与卖家描述的不符，不满意',
                            1 => '差的太离谱，与卖家描述的严重不符，非常不满');
            $sdf['addon'] = serialize($addon);
            $objCommentType->insert($sdf);
            $comment_type = $objCommentType->getList('*');
        }
        $comment_des = array();
        foreach($comment_type as $rows){
            $sdf['addon'] = unserialize($rows['addon']);
            $comment_des[$rows['type_id']] = $sdf['addon']['description'];
        }
        $this->pagedata['comment_store_des'] = json_encode($comment_des);
        $this->pagedata['comment_store_type'] = $comment_type;
        $goods_point_status = app::get('b2c')->getConf('goods.point.status');
        $this->pagedata['point_status'] = $goods_point_status ? $goods_point_status: 'on';
        
        $objOrder = $this->app_b2c->model('orders');
        $objOrderItems = $this->app_b2c->model('order_items');
        $objGoods = $this->app_b2c->model('goods');
        $objComment = $this->app_b2c->model('member_comments');
        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $this->pagedata['discussshow'] = $this->app_b2c->getConf('comment.verifyCode.discuss');
    
        $day_1 = app::get('b2c')->getConf('site.comment_original_time');
        $day_1 = intval($day_1)?intval($day_1):30;
        
        //$order_info = $objOrder->getList('order_id,createtime,comments_count', array('order_id'=>$order_id,'member_id'=>$this->app_b2c->member_id,'status'=>'finish'), 0, -1, 'createtime desc');]
        $sql  = " select o.order_id,o.createtime,o.comments_count from sdb_b2c_orders as o 
                  left join sdb_business_comment_orders_point as p on p.order_id = o.order_id 
                  where o.order_id='{$order_id}' and o.member_id='".$this->app_b2c->member_id."' and o.status='finish' 
                  and ifnull(o.comments_count,0)=0 and DATE_SUB(CURDATE(),INTERVAL {$day_1} DAY)<=from_unixtime(o.createtime) 
                  and p.order_id is null 
                  order by createtime desc ";
        $order_info = $objOrder->db->select($sql);
        foreach($order_info as $rows){
            //if(intval($rows['comments_count']) > 0 || intval($rows['createtime']) < strtotime("-1 month")) continue;
            $order_item = $objOrderItems->getList('order_id,goods_id,product_id',array('order_id' => $rows['order_id']));
            $data = array();
            foreach($order_item as $items){
                $data[] = $items['goods_id'];
            }
            $goods_info[$rows['order_id']] = $objGoods->getList('goods_id,name,thumbnail_pic,udfimg,image_default_id',array('goods_id' => $data),0,-1);
        }
        $this->pagedata['order_info'] = $goods_info;
        $this->pagedata['border_id'] = $order_id;
        $this->page('site/member/discuss.html',false,'qiyecenter');
    }
    //订单评论
    function toComment($item='ask', $order_id, $type=0){
        if($type==0){
            $act = 'discuss';
        }elseif($type==3){
            $act = 'addition';
        }
        if($act && $order_id){
            $url = app::get('site')->router()->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>$act,'arg'=>$order_id));
        }else{
            $url = app::get('site')->router()->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'orders'));
        }
        if(empty($_POST['order_id'])){
            $this->splash('failed',$url,app::get('b2c')->_('参数错误'),'','',false);
        }
        if ($this->app_b2c->getConf('comment.verifyCode.'.$item)=="on"){
            if($item =="ask"){
                if(!base_vcode::verify('ASKVCODE',intval($_POST['askverifyCode']))){
                    $this->splash('failed',$url,app::get('b2c')->_('验证码填写错误'),'','',false);
                }
            }
            if($item =="discuss"){
                if(!base_vcode::verify('DISSVCODE',intval($_POST['discussverifyCode']))){
                    $this->splash('failed',$url,app::get('b2c')->_('验证码填写错误'),'','',false);
                }
            }
        }

        $member_data = $this->get_current_member();
        
        $objBGoods = app::get('business')->model('goods');

        
        $order_info = $objBGoods->get_order_info($_POST['order_id'], $member_data['member_id']);
        if(!$order_info){
            $this->splash('failed',$url,app::get('b2c')->_('参数错误'),'','',false);
        }
        $objComment = kernel::single('business_message_disask');
        $objGoods = app::get('business')->model('goods');
        $aData = array();
        $aData['comments_type'] = $type?$type:'1';
        $aData['gask_type'] = $_POST['gask_type'];
        $aData['title'] = $_POST['title'];
        $aData['object_type'] = $item;
        $aData['author_id'] = $member_data['member_id'] ? $member_data['member_id']:0;
        $aData['author'] = ($member_data['uname'] ? $member_data['uname'] : app::get('b2c')->_('非会员顾客'));
        $aData['contact'] = ($_POST['contact']=='' ? $member_data['email'] : $_POST['contact']);
        $aData['time'] = time();
        $aData['lastreply'] = 0;
        $aData['ip'] = $_SERVER["REMOTE_ADDR"];
        $aData['display'] = ($this->app_b2c->getConf('comment.display.'.$item)=='soon' ? 'true' : 'false');
        $order_ids = array();
        foreach($order_info as $rows){
            $order_ids[$rows['order_id']] = $rows['store_id'];
            if(is_array($_POST['goods_id']) && isset($_POST['goods_id'][$rows['order_id']])){
                foreach($_POST['goods_id'][$rows['order_id']] as $gid){
                    if($gid != $rows['goods_id']) continue;
                    $temp = $aData;
                    $temp['order_id'] = $rows['order_id'];
                    $temp['goods_id'] = $gid;
                    if($temp['comments_type'] == '3'){
                        $temp['for_comment_id'] = $_POST['comment_id'][$rows['order_id']][$gid];
                    }
                    $temp['hidden_name'] = $_POST['hidden_name'][$rows['order_id']][$gid];
                    foreach($_POST['goods_point'][$rows['order_id']]['goods'][$gid] as $ck => $cv){
                        $temp['goods_point'][$ck]['point'] = $cv?$cv:5;
                    }
                    $temp['comment'] = $_POST['comment'][$rows['order_id']][$gid];
                    if($comment_id = $objComment->send($temp, $item)){
                        $single_order[$rows['order_id']] = $rows['order_id'];
                        //$objGoods->updateRank($gid, $item,1);
                        $objGoods->db->exec('update sdb_b2c_goods as g inner join (select avg(goods_point) as point ,goods_id,count(point_id) as comments_count from sdb_b2c_comment_goods_point where goods_id='.intval($gid).' group by goods_id) as p on g.goods_id=p.goods_id set g.avg_point = p.point,g.comments_count=p.comments_count where g.goods_id='.intval($gid));
                        if($this->app_b2c->getConf('comment.display.'.$item) == 'soon' && $item == 'discuss' && $aData['author_id']){
                            $_is_add_point = app::get('b2c')->getConf('member_point');
                            if($_is_add_point){
                                $obj_member_point = $this->app_b2c->model('member_point');
                                $obj_member_point->change_point($aData['author_id'],$_is_add_point,$_msg,'comment_discuss',2,$aData['goods_id'],$aData['author_id'],'comment');
                            }
                        }
                    }else{
                        $error_info[] = array('订单号：'.$rows['order_id'].'|商品号：'.$rows['bn']);
                    }
                }
            }
        }

        $objCommentType = $this->app_b2c->model('comment_goods_type');
        $comment_type = $objCommentType->getList('*');
        $exp_type = '';
        foreach($comment_type as $rows){
            $sdf['addon'] = unserialize($rows['addon']);
            if($sdf['addon']['is_total_point'] == 'on'){
                $exp_type = $rows['type_id'];
                break;
            }
        }
        $obj_store = app::get('business')->model('storemanger');
        if(count($order_info) > 0 && isset($single_order)){
            $objBOrderPoint = app::get('business')->model('comment_orders_point');
            foreach($_POST['order_id'] as $rows){
                $aData = array();
                $aData['store_id'] = $order_ids[$rows];
                $aData['member_id'] = $member_data['member_id'] ? $member_data['member_id']:0;
                $aData['order_id'] = $rows;
                foreach($_POST['point_type'][$rows]['store'] as $ck => $cv){
                    $temp = $aData;
                    $temp['point'] = $cv?$cv:5;
                    $temp['type_id'] = $ck;
                    $objBOrderPoint->save($temp);
                    if($exp_type && $exp_type == $ck){
                        $exper = 0;
                        switch(intval($temp['point'])){
                            case 1:
                            case 2:
                            $exper = -1;
                            break;
                            case 4:
                            case 5:
                            $exper = 1;
                            break;
                            default:
                            $exper = 0;
                            break;
                        }
                        $obj_store->change_experience($aData['store_id'],$exper,$msg,$aData['member_id'],'1',$rows,'discuss');
                    }
                }
            }
        }
        if(isset($error_info) && count($error_info)>0){
            $this->splash('failed',$url,implode(',',$error_info),'','',false);
        }else{
            foreach($single_order as $rows){
                $objGoods->updateOrderRank($rows, $item,1);
            }
            $url = app::get('site')->router()->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'orders'));
            $this->splash('success',$url,app::get('b2c')->_('评论成功'),'','',false);
        }
    }
    //追加评论
     function addition($order_id=0){
        $this -> path[] = array('title' => app :: get('qiyecenter') -> _('追加评论'), 'link' => '#');
        $GLOBALS['runtime']['path'] = $this -> path;
        $objOrder = $this->app_b2c->model('orders');
        $objOrderItems = $this->app_b2c->model('order_items');
        $objGoods = app::get('business')->model('goods');
        $objComment = $this->app_b2c->model('member_comments');
        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];



        $this->pagedata['discussshow'] = $this->app_b2c->getConf('comment.verifyCode.discuss');
        $day_2 = app::get('b2c')->getConf('site.comment_additional_time');
        $day_2 = intval($day_2)?intval($day_2):90;
        
        //$order_info = $objOrder->getList('order_id,createtime,comments_count', array('order_id'=>$order_id,'member_id'=>$this->app_b2c->member_id,'status'=>'finish'), 0, -1, 'createtime desc');
        $sql  = " select o.order_id,o.createtime,o.comments_count from sdb_b2c_orders as o 
                  left join sdb_business_comment_orders_point as p on p.order_id = o.order_id 
                  where o.order_id='{$order_id}' and o.member_id='".$this->app_b2c->member_id."' and o.status='finish' 
                  and ifnull(o.comments_count,0)=1 and DATE_SUB(CURDATE(),INTERVAL {$day_2} DAY)<=from_unixtime(o.createtime) 
                  order by createtime desc ";
        $order_info = $objOrder->db->select($sql);
        foreach($order_info as $rows){
            //if(intval($rows['comments_count']) > 1 || intval($rows['createtime']) < strtotime("-3 month")) continue;
            $order_item = $objOrderItems->getList('order_id,goods_id,product_id',array('order_id' => $rows['order_id']));
            $data = array();
            foreach($order_item as $items){
                $data[] = $items['goods_id'];
            }
            $goods_info[$rows['order_id']] = $objGoods->get_comment_goods($data, $rows['order_id']);
        }
        $this->pagedata['order_info'] = $goods_info;
        $this->pagedata['border_id'] = $order_id;
        
        $this->page('site/member/discuss_addition.html',false,'qiyecenter');
    }
    //填写退货信息
    
     public function refund_add_buyer($order_id){
        
        //获取所有配送方式
        $shippings = $this->app->model('dlytype');
        $this->pagedata['shippings'] = $shippings->getList('*');

        $dlycorp = $this->app->model('dlycorp');
        $this->pagedata['corplist'] = $dlycorp->getList('*');
        $arrDlytype = $shippings->dump($this->pagedata['order']['shipping']['shipping_id']);
        $this->pagedata['order']['shipping']['corp_id'] = $arrDlytype['corp_id'];
        $objDelivery = $this->app->model('delivery');
        $arrDeliverys = $objDelivery->getList('*', array('order_id' => $order_id));

        //获取收货地址
        $returns = app::get('aftersales')->model('return_product');

        $refund_address = $returns->getRow('refund_address,return_id,close_time,seller_comment',array('order_id'=>$order_id,'status'=>'3','refund_type'=>'2'));

        $this->pagedata['return_id'] = $refund_address['return_id'];

        //$obj_order = $this->app->model('orders');
        //$store_id = $obj_order->getRow('store_id',array('order_id'=>$order_id));
        $obj_address = app::get('business')->model('dlyaddress');
        $address = $obj_address->getList('*',array('da_id'=>$refund_address['refund_address']));
        $regions = explode(':',$address[0]['region']);
        $region = $regions[1];
        $this->pagedata['refund_address'] = $region.'/'.$address[0]['address'].'，'.$address[0]['uname'].'，'.$address[0]['phone'];
        $this->pagedata['refunds'] = $refund_address['refund_address'];

        $this->pagedata['seller_comment'] = $refund_address['seller_comment'];

        $this->pagedata['time'] = ($refund_address['close_time'])*1000;
        $this->pagedata['now_time'] = time()*1000;
        $this->pagedata['order_id'] = $order_id;
        $this->output();
    }
     function edit_refund_app($order_id){
        $rp = app::get('aftersales')->model('return_product');

        $return_id = $rp->dump(array('order_id'=>$order_id,'status'=>14));
        $obj_return_policy = kernel::single('aftersales_data_return_policy');
        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($return_id['return_id']);

        //添加退货地址显示
        $obj_address = app::get('business')->model('dlyaddress');
        $address = $obj_address->getList('*',array('da_id'=>$this->pagedata['return_item']['refund_address']));
        $ads = explode(':',$address['0']['region']);
        $address['0']['region'] = $ads[1];
        $this->pagedata['address'] = $address['0'];

        $this->output();

        //echo "<pre>";print_r($return_id);exit;
    }
    function edit_refund($order_id){
        $rp = app::get('aftersales')->model('return_product');
        $objOrder = app::get('b2c')->model('orders');

        $return_id = $rp->dump(array('order_id'=>$order_id,'status'=>11));
        $obj_return_policy = kernel::single('aftersales_data_return_policy');
        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($return_id['return_id']);

        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);
        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }
        $this->pagedata['now_time'] = time()*1000;
        $this->pagedata['time'] = ($this->pagedata['return_item']['close_time'] + 86400*(app::get('b2c')->getConf('member.to_buyer_edit')))*1000;

        $this->output();
    }
    function edit_refund_2($order_id){
        $rp = app::get('aftersales')->model('return_product');
        $objOrder = app::get('b2c')->model('orders');

        $return_id = $rp->dump(array('order_id'=>$order_id,'status'=>11));
        $obj_return_policy = kernel::single('aftersales_data_return_policy');
        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($return_id['return_id']);

        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('aftersales')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('aftersales')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('aftersales')->_("售后服务信息没有开启！"));
        }

        $limit = 10;
        $objOrder = &$this->app_b2c->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        
        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
            $this->pagedata['order']['payed'] = $this->pagedata['gorefund_price'];
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';
                    //zxc_jdgoods
                    // if ($item['item_type'] == 'jdgoods')
                    //     $item['item_type'] = 'goods';

                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }

                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }

        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        $count = count($order_items);
        $arrMaxPage = $this->get_start($page, $count);
        $this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'qiyecenter', 'site_member');
        $this->pagedata['url'] = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));
        if($this->pagedata['order']['status'] == 'finish'){
            $retutn_info = $rp->dump($return_id['return_id']);
            $this->pagedata['retutn_info'] = $retutn_info;
            $obj_products = app::get('b2c')->model('products');
            //需要退货的情况
            if($this->pagedata['return_item']['product_data']){
                $gorefund_price = 0;
                foreach($this->pagedata['return_item']['product_data'] as $k=>$v){
                    $price = $obj_products->dump(array('bn'=>$v['bn']),'price');
                    $gorefund_price = $gorefund_price + $price['price']['price']['price']*$v['num'];
                }
                $this->pagedata['amount_price'] = $gorefund_price;
                $this->pagedata['shipping_price'] = $this->pagedata['order']['shipping']['cost_shipping'];
                $gorefund_price = $gorefund_price+$this->pagedata['order']['shipping']['cost_shipping'];
                $this->pagedata['gorefund_price'] = $gorefund_price;
            }else{
                //不需要退货的情况
                $biggest_payed = $objOrder->dump($retutn_info['order_id'],'payed');
                $this->pagedata['biggest_payed'] = $biggest_payed['payed'];
            }
            //售后添加 售后服务类型
            $this->pagedata['type'] = array(array('id'=>'1','name'=>'商品问题'),array('id'=>'2','name'=>'七天无理由退换货'),array('id'=>'3','name'=>'发票无效'),array('id'=>'4','name'=>'退回多付的运费'),array('id'=>'5','name'=>'未收到退货'));

            //售后添加 售后要求
            $this->pagedata['require'] = array(array('id'=>'1','name'=>'不退货部分退款'),array('id'=>'2','name'=>'需要退货退款'),array('id'=>'3','name'=>'要求换货'),array('id'=>'4','name'=>'要求维修'),array('id'=>'5','name'=>'已经退货，要求退款'),array('id'=>'6','name'=>'要求退款'));

            
            $this->pagedata['_PAGE_'] = 'safeguard_edit.html';

        }
        $this->pagedata['now_time'] = time()*1000;
        $this->pagedata['time'] = ($this->pagedata['return_item']['close_time'] + 86400*(app::get('b2c')->getConf('member.to_buyer_edit')))*1000;
       
        $this->output();
    }
    function edit_safeguard(){
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $rp = app::get('aftersales')->model('return_product');
        $money = $rp->dump(array('return_id'=>$_POST['return_id']),'amount,member_id,product_data,safeguard_type');
        $obj_order = app::get('b2c')->model('orders');
        //echo "<pre>";print_r($_POST);exit;
        

        $aData['content'] = $_POST['content'];
        if(isset($_POST['product_item'])){
            $aData['amount'] = $_POST['goods_amount'] + $_POST['shipping_amount'];
            $aData['shipping_amount'] = $_POST['shipping_amount'];
            $product_data = unserialize($money['product_data']);
            foreach($_POST['product_item'] as $key=>$val){
                foreach($product_data as $k=>$v){
                    if($v['bn'] == $key){
                        $product_data[$k]['refund'] = $val;
                    }
                }
            }
        }else{
            if($money['safeguard_type'] == '4'){
                //退邮费的情况
                $aData['amount'] = $_POST['noship_amount'];
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $seller_amount = ($_POST['noship_amount'])*(1-$freight_pro/100);
            }else{
                $aData['amount'] = $_POST['noship_amount'];
                $seller_amount = $_POST['noship_amount'];
            }
        }

        $obj_cat = app::get('b2c')->model('goods_cat');
        $obj_goods = app::get('b2c')->model('goods');
        $obj_product = app::get('b2c')->model('products');

        //根据商品金额以及抽成比例算出商家出多少钱
        if(isset($_POST['product_item'])){
            $seller_amount = 0;
            foreach($product_data as $key=>$val){
                $good_id = $obj_product->dump(array('bn'=>$val['bn']),'goods_id');
                $cat_id = $obj_goods->dump($good_id['goods_id'],'cat_id');
                if(app::get('b2c')->getConf('member.isprofit') == 'true'){
                    $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                    if(is_null($profit_point['profit_point'])){
                        $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                        $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                    }
                }else{
                    $profit_point['profit_point'] = 0;
                }
                $seller_amount = $seller_amount + $val['refund']*(1-$profit_point['profit_point']/100);
            }
        }
        if($aData['shipping_amount'] > 0){
            $freight_pro = app::get('b2c')->getConf('member.profit');
            $seller_amount = $seller_amount + ($aData['shipping_amount'])*(1-$freight_pro/100);
        }
        $aData['seller_amount'] = $seller_amount;

        $aData['product_data'] = serialize($product_data);
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $order_info = $obj_order->dump($_POST['order_id'], '*', $subsdf);

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        if($order_info['discount_value'] > 0){
            $gorefund_price = $order_info['payed']+($order_info['discount_value']);
        }else{
            $gorefund_price = $order_info['payed'];
        }

        if($_POST['gorefund_price']>$gorefund_price){
            $this->end(false, app::get('qiyecenter')->_("金额非法"));
        }

        $upload_file = "";
        if ( $_FILES['file']['size'] > 314572800 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('aftersales')->_("上传文件不能超过300M"), $com_url);
        }

        if ( $_FILES['file']['name'] != "" )
        {
            $type=array("png","jpg","gif","jpeg","rar","zip");

            if(!in_array(strtolower($this->fileext($_FILES['file']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id = $mdl_img->store($_FILES['file']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
        }
        if($image_id != ''){
            $aData['image_id'] = $image_id;
        }
        //添加两张维权图片
        if ( $_FILES['file1']['size'] > 5242880 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('aftersales')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file1']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file1']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file1']['name'];
            $image_id1 = $mdl_img->store($_FILES['file1']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id1,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id1, $type);
        }
        if($image_id1 != ''){
            $aData['image_file1'] = $image_id1;
        }

        if ( $_FILES['file2']['size'] > 5242880 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file2']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file2']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file2']['name'];
            $image_id2 = $mdl_img->store($_FILES['file2']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id2,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id2, $type);
        }
        if($image_id2 != ''){
            $aData['image_file2'] = $image_id2;
        }
     
        $aData['status'] = '1';
        $aData['add_time'] = time();

        $res = $rp->update($aData,array('return_id'=>$_POST['return_id']));
        $obj_order->update(array('refund_status'=>'1'),array('order_id'=>$_POST['order_id']));
        if($res){
            if ($money['member_id'])
            {
                $obj_members = app::get('b2c')->model('members');
                $arrPams = $obj_members->dump($money['member_id'], '*', array(':account@pam' => array('*')));
            }
            $behavior = "updates";
            $log_text = "买家修改退款申请,修改金额从".$money['amount']."改为：".$aData['amount'].'元！';
            $result = "SUCCESS";
            $image_file = $aData['image_file'].','.$aData['image_file1'].','.$aData['image_file2'];

            $returnLog = app::get('aftersales')->model("return_log");
            $sdf_return_log = array(
                'order_id' => $_POST['order_id'],
                'return_id' => $_POST['return_id'],
                'op_id' => $money['member_id'],
                'op_name' => (!$money['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                'alttime' => time(),
                'behavior' => $behavior,
                'result' => $result,
                'role' => 'member',
                'log_text' => $log_text,
                'image_file' => $image_file,
            );

            $log_id = $returnLog->save($sdf_return_log);

            $objOrderLog = app::get('b2c')->model("order_log");

            $sdf_order_log = array(
                'rel_id' => $_POST['order_id'],
                'op_id' => $money['member_id'],
                'op_name' => (!$money['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'refunds',
                'result' => 'SUCCESS',
                'log_text' => $log_text,
            );
            $log_id = $objOrderLog->save($sdf_order_log);

            $this->end(true, app::get('qiyecenter')->_('修改成功！'));
        }else{
            $this->end(false, app::get('qiyecenter')->_('修改失败！'));
        }
    }
     function s_mall_intervene(){
        $rp = app::get('aftersales')->model('return_product');
        $objOrder = app::get('b2c')->model('orders');
        $obj_return_policy = kernel::single('aftersales_data_return_policy');
        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($_POST['return_id']);      

        $this->pagedata['comment'] = array(array('id'=>'1','name'=>'空包裹，少货'),array('id'=>'2','name'=>'快递问题'),array('id'=>'3','name'=>'卖家发错货'),array('id'=>'4','name'=>'虚假发货'),array('id'=>'5','name'=>'多拍，搓牌，不想要'),array('id'=>'6','name'=>'其他'));
        $this->output();
    }
    //自动关闭的
    public function js_function_order_do_refund_atuo_cancel(){
        //进入页面是需要调用订单操作脚本
        kernel::single('b2c_orderautojob')->do_refund_atuo_cancel(array(array('order_id'=>$_POST['order_id'],'return_id'=>$_POST['return_id'])));

    }
     public function js_function_do_refund_cancel(){
        //进入页面是需要调用订单操作脚本
        $obj_return = app::get('aftersales')->model('return_product');
        $order_id = $obj_return->getRow('order_id',array('return_id'=>$_POST['return_id']));
        kernel::single('b2c_orderautojob')->do_refund_cancel(array(array('order_id'=>$order_id['order_id'],'return_id'=>$_POST['return_id'])));

    }
    
      /**
     * 生成退款单页面
     * @params string order id
     * @return string html
     */
    public function gorefund($order_id,$type=0)
    {
        $obj_product = app::get('aftersales')->model('return_product');
        $return_products = $obj_product->getList('*',array('order_id'=>$order_id,'refund_type|in'=>array('3','4')));
        $tag = false;
        foreach($return_products as $k=>$v){
            if($v['status'] == '1'){
                $tag = true;
                $return_id = $v['return_id'];
            }
        }
        if($tag){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'return_products','arg0'=>$return_id));
        }else{
            if($type){
                $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'gorefund_mai','arg0'=>$order_id,'arg1'=>$type));
            }else{
                $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'gorefund_mai','arg0'=>$order_id));
            }
        }
    }
     public function return_products($return_id){
        $obj_product = app::get('aftersales')->model('return_product');
        $return_products = $obj_product->getRow('*',array('return_id'=>$return_id));
        //echo '<pre>';print_r($return_products);exit;
        $this->pagedata['return_products'] = $return_products;

        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('aftersales')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('aftersales')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product'])
        {
            $this->end(false, app::get('aftersales')->_("售后服务信息没有开启！"));
        }

        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($return_products['return_id']);
        //添加退货地址显示
        $obj_address = app::get('business')->model('dlyaddress');
        $address = $obj_address->getList('*',array('da_id'=>$this->pagedata['return_item']['refund_address']));
        $ads = explode(':',$address['0']['region']);
        $address['0']['region'] = $ads[1];
        $this->pagedata['address'] = $address['0'];
        //添加确认收到退货按钮
        $sto= kernel::single("business_memberstore",$this->member['member_id']);        
        $store_id = $sto->storeinfo['store_id'];
        if($store_id == $this->pagedata['return_item']['store_id'] && $this->pagedata['return_item']['status'] == '已退货'){
            $this->pagedata['is_shop'] = true;
        }else{
            $this->pagedata['is_shop'] = false;
        }

        $this->pagedata['return_id'] = $return_products['return_id'];
        if( !($this->pagedata['return_item']) )
        {
           $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
           $this->end(false, $this->app->_("售后服务申请单不存在！"));
        }
        $this->output('');
    }

    //延长收货申请
     public function extend_finish_apl($order_id){
        $obj_orders=app::get('b2c')->model('orders');
        $obj_members=app::get('b2c')->model('members');
        $obj_storemanger=app::get('business')->model('storemanger');
        $order_info = $obj_orders->getRow('store_id,member_id',array('order_id'=>$order_id));
        $menber_id = $obj_storemanger->getRow('account_id',array('store_id'=>$order_info['store_id']));
        $uname = $obj_members->getRow('name',array('member_id'=>$order_info['member_id']));
        //echo "<pre>";print_r($order_info);exit;
        if($uname['name']){
                $data['uname'] = $uname['name'];
        }else{
            $obj_pam = &app::get('pam')->model('account');
            $pam_account=$obj_pam->dump( array('account_id'=>$order_info['account_id']),'login_name');
            $data['uname'] =$pam_account['login_name'];
        }
        $data['order_id'] = $order_id;
        $id = $menber_id['account_id'];
        $obj_orders->fireEvent('extend',$data,$id);
        $rs = $obj_orders->update(array('is_extend'=>'1'),array('order_id'=>$order_id));

        if($this->app->getConf('webcall.ordernotice.enabled') == 'true'){
            $webcall_service = kernel::service('api.b2c.webcall');
            if($webcall_service && method_exists($webcall_service, 'orderNotice')){
                $result = $webcall_service->orderNotice($order_id,2);
            }
        }

        $this->splash('success', $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'orders')),'申请成功，请等待卖家操作！');
    }
    //退货退款
    public function gorefund_select($order_id){
        $obj_return = app::get('aftersales')->model('return_product');
        $returns = $obj_return->getRow('*',array('order_id'=>$order_id,'refund_type'=>'2','status'=>'1'));
        $return = $obj_return->getRow('*',array('order_id'=>$order_id,'refund_type|in'=>array('3','4'),'status'=>'1'));
        if($returns){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'return_details','arg0'=>$returns['return_id']));
        }elseif($return){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'return_details','arg0'=>$return['return_id']));
        }else{
            $this->pagedata['order_id'] = $order_id;
            $this->output();
        }
        
    }
   //退货退款提交之后
    public function swith_refund(){
        if($_POST['is_required'] == '1'){
            if($_POST['is_need_refund'] == '1'){
                $this->return_add_before($_POST['order_id']);
            }else{
                $this->gorefund($_POST['order_id'],3);
            }
        }else{
            $this->gorefund($_POST['order_id'],4);
        }
    }
    private function get_search_order_ids($type='',$time=''){
        //解析时间
        $year = date('Y',time());
        $sdb = kernel::database()->prefix;
        //三个月内
        if($time == '3th'){
            $time_sql = " createtime<".time()." AND createtime>".strtotime("-3 month");
            //半年内
        }else if($time == '6th'){
            $time_sql = " createtime<".time()." AND createtime>".strtotime("-6 month");
            //今年
        }else if($time == $year){
            $time_sql = " createtime<".time()." AND createtime>".mktime(0,0,0,1,1,$year);
            //一年前
        }else if($time == '1'){
            $time_sql = " createtime<".mktime(0,0,0,12,31,$year-1);
        }else {
            $time_sql = " 1=1 ";
        }

        //type
        $type_sql='';
        if($type == 'nopayed'){
            $type_sql=" pay_status='0' and status='active' ";//待付款
        }else if($type == 'ship'){
            $type_sql=" pay_status='1' and ship_status='0' and status='active' ";//待发货
        }else if($type == 'shiped'){
            $type_sql=" pay_status='1' and ship_status='1' and status='active'";//待收货
        }else if($type == 'comment'){
            $type_sql="status='finish' and comments_count=0 ";//未评论
        }else if($type == 'finish'){
            $type_sql=" status='finish' ";//已完成
        }else if($type == 'confirm'){
            $type_sql=" pay_status='1' and ship_status='1' and status='active' and confirm='N' ";//待确认
        }else if($type == 'dead'){
            $type_sql=" status='dead' ";//作废
        }else{
            $type_sql=' 1=1 ';
        }

        $type_sql .= " and order_type <> 'sand' ";

        $str_sql = "SELECT order_id FROM ".$sdb."b2c_orders WHERE member_id=".$this->app->member_id;

        $str_sql.=" AND ". $time_sql.' AND '.$type_sql;

        return $str_sql;

    }

    /**
     * 订单的搜素
     * @params order_id：订单号,goods_name：商品名称,goods_bn：商品编号
     * @return array
     */
    private function search_order($order_id,$goods_name,$goods_bn){
        //防止SQL注入
        $order_id = mysql_real_escape_string($order_id);
        $goods_name = mysql_real_escape_string($goods_name);
        $goods_bn = mysql_real_escape_string($goods_bn);

        $sdb = kernel::database()->prefix;
        $strsql="select distinct order_id from ".$sdb."b2c_orders where member_id='".$this->app->member_id."' and order_id in ";

        $strsql.="(select item.order_id from ".$sdb."b2c_order_items as item inner join ".$sdb."b2c_goods goods on item.goods_id=goods.goods_id where 1=1 ";

        if($order_id != ''){
            $strsql.="and item.order_id like '%".$order_id."%'";
        }

        if($goods_bn != ''){
            $strsql.="and  goods.bn like '%".$goods_bn."%'";
        }

        if($goods_name != ''){
            $strsql.="and goods.name like '%".$goods_name."%' ";
        }

        $strsql.=")";

        $arr_order_id= $order = app::get('b2c')->model('orders')->db->select($strsql);

        return $arr_order_id;
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string tpl
     * @return null
     */
    protected function get_order_details(&$aData,$tml='member_orders')
    {
        if (isset($aData['data']) && $aData['data'])
        {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }

            foreach ($aData['data'] as &$arr_data_item)
            {
                $this->get_order_detail_item($arr_data_item,$tml);
            }
        }
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string 模版名称
     * @return null
     */
    protected function get_order_detail_item(&$arr_data_item,$tpl='member_order_detail')
    {
        if (isset($arr_data_item) && $arr_data_item)
        {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }

            $arr_data_item['goods_items'] = array();
            $obj_specification = app::get('b2c')->model('specification');
            $obj_spec_values = app::get('b2c')->model('spec_values');
            $obj_goods = app::get('b2c')->model('goods');
            if (isset($arr_data_item['order_objects']) && $arr_data_item['order_objects'])
            {
                foreach ($arr_data_item['order_objects'] as $k=>$arr_objects)
                {
                    $index = 0;
                    $index_adj = 0;
                    $index_gift = 0;
                    $image_set = app::get('image')->getConf('image.set');
                    if ($arr_objects['obj_type'] == 'goods')
                    {
                        foreach ($arr_objects['order_items'] as $arr_items)
                        {
                            if (!$arr_items['products'])
                            {
                                $o = app::get('b2c')->model('order_items');
                                $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                            }

                            if ($arr_items['item_type'] == 'product')
                            {
                                if ($arr_data_item['goods_items'][$k]['product'])
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k]['product']['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][$k]['product']['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k]['product']['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k]['product']['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                $arr_data_item['goods_items'][$k]['product']['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k]['product']['quantity']);
                                $arr_data_item['goods_items'][$k]['product']['amount'] = $arr_items['amount'];
                                $arr_goods_list = $obj_goods->getList('image_default_id', array('goods_id' => $arr_items['goods_id']));
                                $arr_goods = $arr_goods_list[0];
                                if (!$arr_goods['image_default_id'])
                                {
                                    $arr_goods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k]['product']['thumbnail_pic'] = $arr_goods['image_default_id'];
                                //团购秒杀链接
                                if($arr_data_item['order_type']=='group' || $arr_data_item['order_type']=='spike' || $arr_data_item['order_type']=='score'){
                                    switch($arr_data_item['order_type']){
                                        case 'group':
                                            $appName = 'groupbuy';
                                            break;
                                        case 'spike':
                                            $appName = 'spike';
                                            break;
                                        case 'score':
                                            $appName = 'scorebuy';
                                            break;
                                        default:
                                            $appName = 'b2c';
                                    }
                                    $args = array($arr_items['goods_id'],'','',$arr_data_item['act_id']);

                                    $arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>$appName,'ctl'=>'site_product','act'=>'index','args'=>$args));
                                }else{
                                    $arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$arr_items['goods_id']));
                                }
                                if ($arr_items['addon'])
                                {
                                    $arrAddon = $arr_addon = unserialize($arr_items['addon']);
                                    if ($arr_addon['product_attr'])
                                        unset($arr_addon['product_attr']);
                                    $arr_data_item['goods_items'][$k]['product']['minfo'] = $arr_addon;
                                }else{
                                    unset($arrAddon,$arr_addon);
                                }
                                if ($arrAddon['product_attr'])
                                {
                                    foreach ($arrAddon['product_attr'] as $arr_product_attr)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k]['product']['attr']) && $arr_data_item['goods_items'][$k]['product']['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] = substr($arr_data_item['goods_items'][$k]['product']['attr'], 0, strrpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")));
                                    }
                                }
                            }
                            elseif ($arr_items['item_type'] == 'adjunct')
                            {
                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);


                                if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj])
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $arr_items['quantity'];

                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']);
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['link_url'] = $arrGoods['link_url'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['amount'] = $arr_items['amount'];

                                if ($arr_items['addon'])
                                {
                                    $arr_addon = unserialize($arr_items['addon']);

                                    if ($arr_addon['product_attr'])
                                    {
                                        foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                        }
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")));
                                    }
                                }

                                $index_adj++;
                            }else if($arr_items['item_type'] == 'entity' ||$arr_items['item_type']=='virtual'){
                                //实物券,电子券
                                if($arr_items['goods_id']){
                                    $cards_objects = kernel::single("cardcoupons_mdl_cards");
                                    $cards_data = $cards_objects->dump(array("goods_id"=>$arr_items['goods_id']),"type_id");
                                    if($cards_data['type_id'] == "02"){
                                        $card_url = $this->gen_url(array('app'=>'physical','ctl'=>'site_index','act'=>'goodsdetail','arg0'=>$arr_items['goods_id']));;
                                    }else{
                                        $card_url = $this->gen_url(array('app'=>'cardcoupons','ctl'=>'site_product','act'=>'index','arg0'=>$arr_items['goods_id']));;
                                    }
                                }

                                if ($arr_data_item['goods_items'][$k]['product'])
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k]['product']['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][$k]['product']['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k]['product']['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k]['product']['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                $arr_data_item['goods_items'][$k]['product']['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k]['product']['quantity']);
                                $arr_data_item['goods_items'][$k]['product']['amount'] = $arr_items['amount'];
                                $arr_goods_list = $obj_goods->getList('image_default_id', array('goods_id' => $arr_items['goods_id']));
                                $arr_goods = $arr_goods_list[0];
                                if (!$arr_goods['image_default_id'])
                                {
                                    $arr_goods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k]['product']['thumbnail_pic'] = $arr_goods['image_default_id'];
                                $arr_data_item['goods_items'][$k]['product']['link_url'] = $card_url;
                                //$arr_data_item['goods_items'][$k]['product']['type'] = 'card';

                            }else if($arr_items['item_type'] == 'jdgoods' || $arr_items['item_type'] == 'jdbook'){
                                if ($arr_data_item['goods_items'][$k]['product'])
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k]['product']['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][$k]['product']['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k]['product']['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k]['product']['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                $arr_data_item['goods_items'][$k]['product']['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k]['product']['quantity']);
                                $arr_data_item['goods_items'][$k]['product']['amount'] = $arr_items['amount'];
                                $arr_goods_list = $obj_goods->getList('image_default_id', array('goods_id' => $arr_items['goods_id']));
                                $arr_goods = $arr_goods_list[0];
                                $arr_data_item['goods_items'][$k]['product']['thumbnail_pic'] =jdsale_goods_import::$image_url_n3.$arr_goods['image_default_id'];
                                $arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>'jdsale','ctl'=>'site_product','act'=>'index','arg0'=>$arr_items['goods_id']));

                            }else
                            {
                                // product gift.
                                if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                                {


                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift])
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['name'] = $arr_items['name'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['price'] = $arr_items['price'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity']);
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['link_url'] = $arrGoods['link_url'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'])
                                    {
                                        if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                                $index_gift++;
                            }
                        }
                    }
                    else
                    {
                        if ($arr_objects['obj_type'] == 'gift')
                        {
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {
                                foreach ($arr_objects['order_items'] as $arr_items)
                                {
                                    if (!$arr_items['products'])
                                    {
                                        $o = app::get('b2c')->model('order_items');
                                        $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                        $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                                    }

                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if (!isset($arr_items['products']['product_id']) || !$arr_items['products']['product_id'])
                                        $arr_items['products']['product_id'] = $arr_items['goods_id'];

                                    if ($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']])
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }

                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['name'] = $arr_items['name'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['price'] = $arr_items['price'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['mktprice'] = $arr_items['products']['price']['mktprice']['price'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['point'] = intval($arr_items['score']*$arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']);
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['nums'] = $arr_items['quantity'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['link_url'] = $arrGoods['link_url'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr']) && $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'])
                                    {
                                        if (strpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] = substr($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], 0, strrpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {

                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                $arr_data_item['extends_items'][] = $str_service_goods_type_obj->get_order_object($arr_objects, $arr_Goods,$tpl);
                            }
                        }
                    }
                }
            }

        }
    }

    /**
     * 动态获取选择的时间
     * @return array
     */
    private function get_select_date(){
        $year = date('Y',time());
        $options = array();
        $options['all'] = "全部时间";
        $options['3th'] = "三个月内";
        $options['6th'] = "半年内";
        $options[$year] = "今年内";
        $options['1'] = "1年以前";
        return $options;
    }

    function receiver(){
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('收货地址'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $objMem = app::get('b2c')->model('members');
        $this->pagedata['receiver'] = $objMem->getMemberAddr($this->app->member_id);
        $this->pagedata['is_allow'] = (count($this->pagedata['receiver'])<6 ? 1 : 0);
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->output();
    }
    //企业管理页面-查看管理员
    function getenterprise_price_selectmanger(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'getManagersByTypeId',
            'TYPE_ID'=>$_GET['COMPANY_NO'],
            'SETUP_MANAGER_TYPE'=> 'I03701',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->singlepage('site/member/getenterprise_price_selectmanger.html','qiyecenter');
    }
    //企业管理页面-删除管理员
    function getenterprise_price_deletemanger(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'deleteManager',
            'TYPE_ID'=>$_POST['CUSTOMER_ID'],
            'HUMBAS_NO'=>$_POST['HUMBAS_NO'],
            'SETUP_MANAGER_TYPE'=> 'I03701',
        );
        

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        // var_dump($sfscdata);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }
    //企业管理页面-雇员列表页面
    function getenterprise_price(){
        $serviceNo="ManagerByHumbasNoService";
        $_sjson = array(
            'METHOD'=>'getEmployeeByHumbasNoAndCustomerid',
            'HUMBAS_NO'=> $this->humbas_no,
            'COMPANY_NO'=>$_GET['COMPANY_NO'] ? $_GET['COMPANY_NO'] : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['COMPANY_NO'] = $_GET['COMPANY_NO'];
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->singlepage('site/member/getenterprise_price.html','qiyecenter');
    }

    // 企业管理页面-雇员模糊查询
    function getenterprise_price_humbas(){
        $serviceNo="ManagerByHumbasNoService";
        $_sjson = array(
            'METHOD'=>'getEmployeeByHumbasNoAndCustomerid',
            'HUMBAS_NO'=>$this->humbas_no,
            'NAME'=>$_POST['NAME'],
            'COMPANY_NO'=>$_POST['COMPANY_NO']
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];

        $render = new base_render(app::get('qiyecenter'));
        $this->pagedata['sid'] = time();
        echo $render->fetch('site/member/getenterprise_price_humbas.html');
        //$this->singlepage('site/member/getenterprise_price_humbas.html','qiyecenter');
    }
    
    // 企业管理页面-积分发放明细查询
    function getenter_jifenselect(){
         if($this->qiye_role_id == "I03701"){
            $serviceNo="StatisticsService";
            //CUSTOMER_ID、FILTER_TIME
            $_sjson = array(
                'METHOD'=>'getPointStatisticsOfCompany',
                'HUMBAS_NO'=>$this->humbas_no,
                'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : "",
                'FILTER_TIME' => $_GET['FILTER_TIME'] ?date('Y-m',strtotime(trim($_GET['FILTER_TIME']))) : '',
                'STRAT_TIME' => $_GET['STRAT_TIME'] ?date('Y-m-d',strtotime(trim($_GET['STRAT_TIME']))) : '',
                'END_TIME' => $_GET['END_TIME'] ? date('Y-m-d',strtotime(trim($_GET['END_TIME']))) : '',

            );
        }else{
            $serviceNo="StatisticsService";
            $_sjson = array(
                //TYPE_ID、FILTER_TIME、SETUP_MANAGER_TYPE
                'METHOD'=>'getPointStatisticsOfDeptOrGroup',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'TYPE_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : "",
                'FILTER_TIME' => $_GET['FILTER_TIME'] ?date('Y-m',strtotime(trim($_GET['FILTER_TIME']))) : '',
                'STRAT_TIME' => $_GET['STRAT_TIME'] ?date('Y-m-d',strtotime(trim($_GET['STRAT_TIME']))) : '',
                'END_TIME' => $_GET['END_TIME'] ? date('Y-m-d',strtotime(trim($_GET['END_TIME']))) : '',
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];

        $render = new base_render(app::get('qiyecenter'));
        $this->pagedata['sid'] = time();

        echo $render->fetch('site/member/getenter_jifenselect.html');
    }
     // 企业管理页面-礼包发放明细查询
    function getenter_giftselect(){
         $serviceNo="StatisticsService";
        //CUSTOMER_ID、FILTER_TIME、GIFT_NAME
        //STRAT_TIME、END_TIME
        $_sjson = array(
            'METHOD'=>'getgiftStatisticsOfCompany',
            'HUMBAS_NO'=>$this->humbas_no,
            'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : '',
            'GIFT_NAME' => $_GET['GRANT_NAME'] ? $_GET['GRANT_NAME'] : '',
            'FILTER_TIME' => $_GET['FILTER_TIME'] ?date('Y-m',strtotime(trim($_GET['FILTER_TIME']))) : '',
            'STRAT_TIME' => $_GET['STRAT_TIME'] ?date('Y-m-d',strtotime(trim($_GET['STRAT_TIME']))) : '',
            'END_TIME' => $_GET['END_TIME'] ? date('Y-m-d',strtotime(trim($_GET['END_TIME']))) : '',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];

        $render = new base_render(app::get('qiyecenter'));
        $this->pagedata['sid'] = time();
        
        echo $render->fetch('site/member/getenter_giftselect.html');
    }
    /*设置公司管理员*/
    function setcompanymanager(){
        $serviceNo="CustomerUserRegisterService";
        $_sjson = array(
            'METHOD'=>'setCompanyManager',
            'HUMBAS_NO'=> substr($_POST['HUMBAS_NO'],0,strlen($_POST['HUMBAS_NO'])-1),
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
       /*Array ( [RESULT_MSG] => 操作成功 [RESULT_CODE] => 10001 )*/
       if($sfscdata['RESULT_CODE'] == '10001'){
           $tmp22= Array('RESULT_CODE'=>'10001');
       }else{
           $tmp22= Array('RESULT_CODE'=>'10000','RESULT_MSG'=>$sfscdata['RESULT_MSG']);
       }
       echo json_encode($tmp22);
    }

    //部门管理-员工管理
    function employeemanagement(){

        if($this->qiye_role_id == "I03701"){
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'deprtmentStaffManagement',
                'COMPANY_NO'=>$_GET['COMPANY_NO'],
                'DEPT_ID'=>$_GET['DEPT_ID'],
                'HUMBAS_NO' => $this->humbas_no,
                'EMPLOYEE_NAME'=> $_POST['EMPLOYEE_NAME'] ? trim($_POST['EMPLOYEE_NAME']) : "",
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getAllEmpByTypeId',
                'COMPANY_NO'=>$_GET['COMPANY_NO'],
                'SETUP_MANAGER_TYPE'=>$this->qiye_role_id,
                'TYPE_ID'=>$_GET['DEPT_ID'],
                'HUMBAS_NO' => $this->humbas_no,
                'EMPLOYEE_NAME'=> $_POST['EMPLOYEE_NAME'] ? trim($_POST['EMPLOYEE_NAME']) : "",
            );
        }

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['COMPANY_NO'] = $_GET['COMPANY_NO'];
        $this->pagedata['DEPT_ID'] = $_GET['DEPT_ID'];
        $this->pagedata['sid'] = time();
        if($this->qiye_role_id == "I03701"){
            $this->singlepage('site/member/employeemanagement.html','qiyecenter');
        }else{
            $this->singlepage('site/member/role/employeemanagement.html','qiyecenter');
        }
    }

    /*
        群组管理中-员工管理
    */
    function employeemanagement_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'groupStaffManagement',
            'COMPANY_NO'=>$_GET['COMPANY_NO'],
            'GROUP_ID'=>$_GET['GROUP_ID'],
            'HUMBAS_NO' => $this->humbas_no,
            'EMPLOYEE_NAME'=> $_POST['EMPLOYEE_NAME'] ? trim($_POST['EMPLOYEE_NAME']) : "",
        );

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['COMPANY_NO'] = $_GET['COMPANY_NO'];
        $this->pagedata['GROUP_ID'] = $_GET['GROUP_ID'];
        $this->pagedata['sid'] = time();
        if($this->qiye_role_id == "I03701"){
            $this->singlepage('site/member/employeemanagement_qunzu.html','qiyecenter');
        }else{
            $this->singlepage('site/member/role/employeemanagement_qunzu.html','qiyecenter');
        }
    }


    function employeemanagement_qunzu_search(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'groupStaffManagement',
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'GROUP_ID'=>$_POST['GROUP_ID'],
            'HUMBAS_NO' => $this->humbas_no,
            'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? trim($_POST['EMPLOYEE_NAME']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/employeemanagement_search.html');
    }


    function employeemanagement_qunzu_del(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'deleteEMPInGroupByHumbasNos',
            'HUMBAS_NO'=>$_POST['HUMBAS_NO'],
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'GROUP_ID'=>$_POST['GROUP_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] != "10001"){
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array());
        }
    }


    function employeemanagement_search(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'deprtmentStaffManagement',
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'DEPT_ID'=>$_POST['DEPT_ID'],
            'HUMBAS_NO' => $this->humbas_no,
            'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? trim($_POST['EMPLOYEE_NAME']) : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/employeemanagement_search.html');
    }

    function employeemanagement_del(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'deleteEMPInDeprtmentByHumbasNos',
            'HUMBAS_NO'=>$_POST['HUMBAS_NO'],
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] != "10001"){
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array());
        }
    }

    function add_organization(){
        if($this->qiye_role_id == "I03701"){
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'insertEmployeeManagerToDept',
                'COMPANY_NO'=>$_POST['COMPANY_NO'],
                'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? $_POST['EMPLOYEE_NAME'] : ""
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'insertEmployeeManagerToDept',
                'COMPANY_NO'=>$_POST['COMPANY_NO'],
                'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? $_POST['EMPLOYEE_NAME']  : "",
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        if($this->qiye_role_id == "I03701"){
            echo $render->fetch('site/member/add_organization.html');
        }else{
            echo $render->fetch('site/member/role/add_organization.html');
        }

    }
    //群组管理
    function add_organization_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'insertEmployeeManagerToGroup',
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            //群组
            'GROUP_ID'=>$_POST['GROUP_ID'],
            'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? $_POST['EMPLOYEE_NAME'] : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['GROUP_ID'] = $_POST['GROUP_ID'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/add_organization_qunzu.html');
    }

    function add_organization_save_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'addHumbas2Group',
            'HUMBAS_NO'=>substr($_POST['HUMBAS_NO'],0,strlen($_POST['HUMBAS_NO'])-1),
            'GROUP_ID'=>$_POST['GROUP_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] != '10001'){
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array());
        }
    }


    function add_organization_save(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'addHumbas2Dept',
            'HUMBAS_NO'=>substr($_POST['HUMBAS_NO'],0,strlen($_POST['HUMBAS_NO'])-1),
            'DEPT_ID'=>$_POST['DEPT_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] != '10001'){
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array());
        }

    }

    //群组员工管理查询筛选
    function get_qunzu_guyuan_gyselect(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'insertEmployeeManagerToGroup',
            'COMPANY_NO'=>$_GET['COMPANY_NO'],
            //群组
            'GROUP_ID'=>$_GET['GROUP_ID'],
             //姓名
            'EMPLOYEE_NAME'=>$_GET['EMPLOYEE_NAME'] ? $_GET['EMPLOYEE_NAME'] : "",
             //雇员编号ID
            'HUMBAS_NO'=>$_GET['HUMBAS_NO'],
            //证件号
            'ID'=>$_GET['ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['GROUP_ID'] = $_POST['GROUP_ID'];
        $render = new base_render(app::get('qiyecenter'));
        echo $render->fetch('site/member/get_qunzu_manual_gyselect.html');

    }

     //部门员工管理查询筛选
    function get_bumen_guyuan_gyselect(){

        if($this->qiye_role_id == "I03701"){
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'insertEmployeeManagerToDept',
                'COMPANY_NO'=>$_GET['COMPANY_NO'],
                 //姓名
                'EMPLOYEE_NAME'=>$_GET['EMPLOYEE_NAME'] ? $_GET['EMPLOYEE_NAME'] : "",
                 //雇员编号ID
                'HUMBAS_NO'=>$_GET['HUMBAS_NO'],
                //证件号
                'ID'=>$_GET['ID'],
            );
        }else{
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'insertEmployeeManagerToDept',
                'COMPANY_NO'=>$_GET['COMPANY_NO'],
                 //姓名
                'EMPLOYEE_NAME'=>$_GET['EMPLOYEE_NAME'] ? $_GET['EMPLOYEE_NAME'] : "",
                 //雇员编号ID
                'HUMBAS_NO'=>$_GET['HUMBAS_NO'],
                //证件号
                'ID'=>$_GET['ID'],
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        // if($this->qiye_role_id == "I03701"){
        //     echo $render->fetch('site/member/add_organization.html');
        // }else{
        //     echo $render->fetch('site/member/role/add_organization.html');
        // }
        echo $render->fetch('site/member/get_bumen_manual_gyselect.html');
          

    }
    //部门管理-查看管理员
    function department_selectmanger(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'getManagersByTypeId',
            'TYPE_ID'=>$_GET['DEPT_ID'],
            'SETUP_MANAGER_TYPE'=> 'I03702',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['DEPT_ID'] = $_GET['DEPT_ID'];
        $this->singlepage('site/member/department_selectmanger.html','qiyecenter');
    }
     //部门管理-删除管理员
    function department_deletemanger(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'deleteManager',
            'TYPE_ID'=>$_POST['DEPT_ID'],
            'HUMBAS_NO'=>$_POST['HUMBAS_NO'],
            'SETUP_MANAGER_TYPE'=> 'I03702',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }
    //部门管理-设置管理员
    function department_setmanger(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'selectEMPInDeprtmentEmployee',
            'HUMBAS_NO' => $this->humbas_no,
            'DEPT_ID'=>$_GET['DEPT_ID'],
            'COMPANY_NO'=>$_GET['COMPANY_NO'],
            'EMPLOYEE_NAME'=>''
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->pagedata['DEPT_ID'] = $_GET['DEPT_ID'];
        $this->pagedata['COMPANY_NO'] = $_GET['COMPANY_NO'];
        $this->singlepage('site/member/department_setmanger.html','qiyecenter');
    }
     //群组管理-查看管理员
    function department_selectmanger_qunzu(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'getManagersByTypeId',
            'TYPE_ID'=>$_GET['GROUP_ID'],
            'SETUP_MANAGER_TYPE'=> 'I03703',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['GROUP_ID'] = $_GET['GROUP_ID'];
        $this->singlepage('site/member/department_selectmanger_qunzu.html','qiyecenter');
    }
     //群组管理-删除管理员
    function department_qunzu_deletemanger(){
        $serviceNo="CustomerManagerUserLoginService";
        $_sjson = array(
            'METHOD'=>'deleteManager',
            'TYPE_ID'=>$_POST['GROUP_ID'],
            'HUMBAS_NO'=>$_POST['HUMBAS_NO'],
            'SETUP_MANAGER_TYPE'=> 'I03703',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }
   //群组管理-设置管理员
    function department_setmanger_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'selectEMPInGroupEmployee',
            'HUMBAS_NO' => $this->humbas_no,
            'GROUP_ID'=>$_GET['GROUP_ID'],
            'COMPANY_NO'=>$_GET['COMPANY_NO'],
            'EMPLOYEE_NAME'=>''
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->pagedata['GROUP_ID'] = $_GET['GROUP_ID'];
        $this->pagedata['COMPANY_NO'] = $_GET['COMPANY_NO'];
        $this->singlepage('site/member/department_setmanger_qunzu.html','qiyecenter');
    }


    function department_setmanger_humbas_qunzu(){
        $serviceNo="GroupService";
        $_sjson = array(
            'METHOD'=>'selectEMPInGroupEmployee',
            'GROUP_ID'=>$_POST['GROUP_ID'],
            'HUMBAS_NO' => $this->humbas_no,
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? $_POST['EMPLOYEE_NAME'] : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        $this->pagedata['sid'] = time();
        echo $render->fetch('site/member/department_setmanger_humbas_qunzu.html');
    }



    function department_setmanger_humbas(){
        $serviceNo="DepartmentService";
        $_sjson = array(
            'METHOD'=>'selectEMPInDeprtmentEmployee',
            'DEPT_ID'=>$_POST['DEPT_ID'],
            'HUMBAS_NO' => $this->humbas_no,
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'EMPLOYEE_NAME'=>$_POST['EMPLOYEE_NAME'] ? $_POST['EMPLOYEE_NAME'] : ""
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $render = new base_render(app::get('qiyecenter'));
        $this->pagedata['sid'] = time();
        echo $render->fetch('site/member/department_setmanger_humbas.html');
    }

    function set_bumen_companymanager(){
            $serviceNo="CustomerUserRegisterService";
            $_sjson = array(
                'METHOD'=>'setupManager',
                'HUMBAS_NO'=> substr($_POST['HUMBAS_NO'],0,strlen($_POST['HUMBAS_NO'])-1),
                'COMPANY_NO'=>$_POST['COMPANY_NO'],
                'DEPT_ID'=>$_POST['DEPT_ID'],
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            /*Array ( [RESULT_MSG] => 操作成功 [RESULT_CODE] => 10001 )*/
            if($sfscdata['RESULT_CODE'] == '10001'){
                $tmp22= Array('RESULT_CODE'=>'10001');
            }else{
                $tmp22= Array('RESULT_CODE'=>'10000','RESULT_MSG'=>$sfscdata['RESULT_MSG']);
            }
            echo json_encode($tmp22);
    }

    function set_qunzu_companymanager(){
        $serviceNo="CustomerUserRegisterService";
        $_sjson = array(
            'METHOD'=>'setGroupManager',
            'HUMBAS_NO'=> substr($_POST['HUMBAS_NO'],0,strlen($_POST['HUMBAS_NO'])-1),
            'COMPANY_NO'=>$_POST['COMPANY_NO'],
            'GROUP_ID'=>$_POST['GROUP_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == '10001'){
            $tmp22= Array('RESULT_CODE'=>'10001');
        }else{
            $tmp22= Array('RESULT_CODE'=>'10000','RESULT_MSG'=>$sfscdata['RESULT_MSG']);
        }
        echo json_encode($tmp22);
    }




    function getCompanyNameByManagerId(){
        $serviceNo="CustomerService";
        $_sjson = array(
            'METHOD'=>'getCompanyNameByManagerId',
            'HUMBAS_NO' => $this->humbas_no,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if(empty($sfscdata['RESULT_DATA'])){
            echo '<option value="">请选择</option>';
        }else{
            $tmp = "<option value=\"\">请选择</option>";
            foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                $tmp .= '<option value="'.$v['CUSTOMER_NAME'].'"  CUSTOMER_ID = "'.$v['CUSTOMER_ID'].'"  >'.$v['CUSTOMER_NAME'].'</option>';
            }
        }
        echo $tmp;
    }

    function getGroupOrDeptNameByManagerId(){

        $serviceNo="StatisticsService";
        $_sjson = array(
            'METHOD'=>'getGroupOrDeptNameByManagerId',
            'HUMBAS_NO' => $this->humbas_no,
            'SETUP_MANAGER_TYPE' =>$this->qiye_role_id
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        /* 部门
            [DEPT_ID] => 8e385ffe604240ff832c7bd6dedc952f
            [DEPT_NAME] => 未分配部门
        */
        if(empty($sfscdata['RESULT_DATA'])){
            echo '<option value="">请选择</option>';
        }else{
            $tmp = "<option value=\"\">请选择</option>";
            if($this->qiye_role_id == 'I03701'){
                //商社管理员
                foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                    $tmp .= '<option value="'.$v['CUSTOMER_NAME'].'"  CUSTOMER_ID = "'.$v['CUSTOMER_ID'].'"  >'.$v['CUSTOMER_NAME'].'</option>';
                }
            }elseif($this->qiye_role_id == 'I03702'){
                //部门管理员
                foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                    $tmp .= '<option value="'.$v['DEPT_ID'].'"  CUSTOMER_ID = "'.$v['DEPT_ID'].'"  >'.$v['DEPT_NAME'].'</option>';
                }
            }elseif($this->qiye_role_id == 'I03703'){
                //群组管理员
                foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                    $tmp .= '<option value="'.$v['GROUP_ID'].'"  CUSTOMER_ID = "'.$v['GROUP_ID'].'"  >'.$v['GROUP_NAME'].'</option>';
                }
            }

        }
        echo $tmp;
    }



    /* 积分发放订单页面，获取企业账户信息
     */
    function getaccount_balance(){
        if($this->qiye_role_id == "I03701"){
            $serviceNo="BizOrderService";
            $_sjson = array(
                'METHOD'=>'getCompanyUsedPayByOederId',
                'HUMBAS_NO' => $this->humbas_no,
                'ORDER_ID'=>$_GET['ORDER_ID'],
                'CUSTOMER_NAME'=> $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : ""
            );
        }else{
            $serviceNo="DepartmentService";
            //{"METHOD":"getAllEmpByTypeId","HUMBAS_NO":"344hjkl0987"，“SETUP_MANAGER_TYPE”：“I03702”,"ORDER_ID":"订单编号"}
            $_sjson = array(
                'METHOD'=>'getDeptOrGroupUsedPayByOederId',
                'HUMBAS_NO' => $this->humbas_no,
                'ORDER_ID'=>$_GET['ORDER_ID'],
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'CUSTOMER_NAME'=> $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : ""
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        if($this->qiye_role_id == "I03701"){
            $this->singlepage('site/member/getaccount_balance.html','qiyecenter');
        }else{
            $this->singlepage('site/member/role/getaccount_balance.html','qiyecenter');
        }

    }
    //选择商社进行修改会员等级
    function get_business_add(){


        $BUY_CUSTOMER_ID= $_POST['BUY_CUSTOMER_ID'];
         //商社列表
        $params['METHOD'] = 'getCompanyBuyTrdeGradeByCompanyNo';
        $params['COMPANY_NO'] = $BUY_CUSTOMER_ID;
        $resSF = SFSC_HttpClient::doLifCostMain($params, 'CustomerService');
        $business_lv = app::get('b2c')->model('member_lv');
        $busin_id = $business_lv->getRow('member_lv_id',array('name'=>$resSF['RESULT_DATA']['BUY_TRADE_GRADE']));
        $member_obj = app::get('b2c')->model('members');
        header('Content-Type:text/html;charset=utf-8');
        $member_id = $this->qiyemember_id;
        $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
        $qiye_com = $obj_com->getList('m_id',array('m_id'=>$this->qiyemember_id));
        $o_mid=$this->qiyemember_id;
        if($qiye_com[0]['m_id'] < 1){
             $sql = 'INSERT INTO `sdb_qiyecenter_abcommercial_sfscpayment_setting` (`m_id` ,`target_type`,`target_id` ) VALUES ('.$this->qiyemember_id.',"I00102","'.$BUY_CUSTOMER_ID.'")';
        }else{
             $sql="UPDATE `sdb_qiyecenter_abcommercial_sfscpayment_setting` SET target_id='".$BUY_CUSTOMER_ID."', target_type='I00102' where m_id=".$this->qiyemember_id;
        }
        $myall=mysql_query($sql);
        if ($myall){
             $sql_2="UPDATE `sdb_b2c_members` SET member_lv_id=".$busin_id['member_lv_id']." where member_id=".$this->qiyemember_id;
             $myall_2=mysql_query($sql_2);
             echo json_encode(array('success'=>'true'));
        }else{
             echo json_encode(array('error'=>'false'));
        }
   


    }

    //选择部门进行部门支付账号
    function get_bumenbusiness_add(){
        $BUY_BUMEN_ID = $_POST['BUY_BUMEN_ID'];
        $member_id = $this->qiyemember_id;
        $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
        $qiye_com = $obj_com->getList('m_id',array('m_id'=>$this->qiyemember_id));
        if(isset($_POST['BUY_BUMEN_ID'])){
            if($qiye_com[0]['m_id'] < 1){
                $sql = 'INSERT INTO `sdb_qiyecenter_abcommercial_sfscpayment_setting` ( `m_id` , `target_type`, `target_id`) VALUES ('.$this->qiyemember_id.',"I00103","'.$BUY_BUMEN_ID.'")';
            }else{
                $sql="UPDATE `sdb_qiyecenter_abcommercial_sfscpayment_setting` SET target_id='".$BUY_BUMEN_ID."',target_type = 'I00103' where m_id=".$this->qiyemember_id;
            }
        }
        $myall=mysql_query($sql);
        if ($myall){
            echo json_encode(array('success'=>'true'));
        }else{
            echo json_encode(array('error'=>'false','message'=>'请选择默认支付账号'));
        }
    }

    //选择群组商社进行群组支付账号
    function get_qunzubusiness_add(){
        $BUY_GROUP_ID= $_POST['BUY_GROUP_ID'];
        $member_id = $this->qiyemember_id;
        $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
        $qiye_com = $obj_com->getList('m_id',array('m_id'=>$this->qiyemember_id));
        if(isset($_POST['BUY_GROUP_ID'])){
            if($qiye_com[0]['m_id'] < 1){
                 $sql = 'INSERT INTO `sdb_qiyecenter_abcommercial_sfscpayment_setting` ( `m_id` , `target_type`, `target_id`) VALUES ('.$this->qiyemember_id.',"I00104","'.$BUY_GROUP_ID.'")';
            }else{
                 $sql="UPDATE `sdb_qiyecenter_abcommercial_sfscpayment_setting` SET target_id='".$BUY_GROUP_ID."' , target_type = 'I00104' where m_id=".$this->qiyemember_id;
            }
        }
        $myall=mysql_query($sql);
        if ($myall){
             echo json_encode(array('success'=>'true'));
        }else{
             echo json_encode(array('error'=>'false','message'=>'请选择默认支付账号'));
        }
    }

    //判断是否选择了企业商社
     function get_is_business(){
         $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
         $member_sel = $obj_com->getList('*',array('m_id'=>$this->qiyemember_id , 'target_type' => 'I00102'));
        if(!$member_sel[0]){
            echo json_encode(array('success'=>'true'));
        }else{
            echo json_encode(array('error'=>'false'));
        }

    }
     //判断是否选择了群组支付名称
     function get_is_qunzubusiness(){
         $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
         $member_sel = $obj_com->getList('*',array('m_id'=>$this->qiyemember_id , 'target_type' => 'I00104'));
        if(!$member_sel[0]){
            echo json_encode(array('success'=>'true'));
        }else{
            echo json_encode(array('error'=>'false'));
        }

    }
    function singlepage($view, $app_id=''){
        $service = kernel::service(sprintf('desktop_controller_display.%s.%s.%s', $_GET['app'],$_GET['ctl'],$_GET['act']));
        if($service){
            if(method_exists($service, 'get_file'))  $view = $service->get_file();
            if(method_exists($service, 'get_app_id'))   $app_id = $service->get_app_id();
        }
        $page = $this->fetch($view, $app_id);
        $this->pagedata['_PAGE_PAGEDATA_'] = $this->_vars;
        $re = '/<script([^>]*)>(.*?)<\/script>/is';
        $this->__scripts = '';
        $page = preg_replace_callback($re,array(&$this,'_singlepage_prepare'),$page)
            .'<script type="text/plain" id="__eval_scripts__" >'.$this->__scripts.'</script>';

        //后台singlepage页面增加自定义css引入到head标签内的操作--@lujy-start
        $recss = '/<link([^>]*)>/is';
        $this->__link_css = '';
        $page = preg_replace_callback($recss,array(&$this,'_singlepage_link_prepare'),$page);
        $this->pagedata['singleappcss'] = $this->__link_css;
        //--end
        $this->pagedata['statusId'] = $this->app->getConf('b2c.wss.enable');
        $this->pagedata['session_id'] = kernel::single('base_session')->sess_id();
        $this->pagedata['desktop_path'] = app::get('desktop')->res_url;
        $this->pagedata['shopadmin_dir'] = dirname($_SERVER['PHP_SELF']).'/';
        $this->pagedata['shop_base'] = $this->app->base_url();
        $this->pagedata['desktopresurl'] = app::get('desktop')->res_url;
        $this->pagedata['desktopresfullurl'] = app::get('desktop')->res_full_url;
        $this->pagedata['_PAGE_'] = &$page;
        $this->display('singlepage.html','desktop');
    }

    function del_jifen_order(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD'=>'deleteOrderItemByOrderId',
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID'=>$_POST['ORDER_ID'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true','message'=>'删除成功！'));
        }else{
            echo json_encode(array('fail'=>'true','message'=>$sfscdata['RESULT_MSG']));
        }
    }

    function bumenselect(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD'=>'getToPayDeptByOederId',
            //企业编号
            'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'],
            //搜索用企业名称
            'CUSTOMER_NAME'=>''
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->pagedata['PRICE'] = $_GET['PRICE'];
        $this->singlepage('site/member/bumenselect.html','qiyecenter');
    }

    function qunzhuselect(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD'=>'getToPayGroupByOederId',
            //企业编号
            'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'],
            //搜索用企业名称
            'CUSTOMER_NAME'=>''
        );

        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->pagedata['PRICE'] = $_GET['PRICE'];
        $this->singlepage('site/member/qunzhuselect.html','qiyecenter');
    }

    function guyuanselect(){
        header("Cache-Control:no-store, no-cache, must-revalidate");
        if($this->qiye_role_id == "I03701"){
            $serviceNo="BizOrderService";
            $_sjson = array(
                'METHOD'=>'getToPayEmpByOederId',
                //企业编号
                'CUSTOMER_ID'=>$_REQUEST['CUSTOMER_ID'],
                //搜索用企业名称
                'CUSTOMER_NAME'=>''
            );
        }else{
            //{"METHOD":"getEmpListByTypeId","TYPE_ID":"344hjkl0987_523829hef_72y8238"，“SETUP_MANAGER_TYPE”：“I03702”,"TYPE_NAME":"代表部门或群组名称"}
            $serviceNo="DepartmentService";
            $_sjson = array(
                'METHOD'=>'getEmpListByTypeId',
                'TYPE_ID'=>$_REQUEST['CUSTOMER_ID'],
                //群组或者部门_用于搜索
                'TYPE_NAME'=> $_POST['TYPE_NAME'] ? $_POST['TYPE_NAME'] : '',
                //操作雇员id
                'HUMBAS_NO' => $this->humbas_no,
                //群主或者部门
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['CUSTOMER_ID'] = $_GET['CUSTOMER_ID'];
        $this->pagedata['sid'] = time();
        $this->pagedata['PRICE'] = $_GET['PRICE'];
        if($this->qiye_role_id == "I03701"){
            $this->singlepage('site/member/guyuanselect.html','qiyecenter');
        }else{
            $this->singlepage('site/member/role/guyuanselect.html','qiyecenter');
        }
    }


    function get_guyuan_select(){
        header("Cache-Control:no-store, no-cache, must-revalidate");
        $EMP_NEW_PRICE=$_POST['EMP_NEW_PRICE'];
        if($_POST['type'] != 'manual_selection'){
        //上传文件
            $render = new base_render(app::get('qiyecenter'));
            $this->pagedata['PRICE'] = $EMP_NEW_PRICE;
            echo $render->fetch('site/member/get_file_import.html');
        }else{
        //手工选择
            if($this->qiye_role_id == "I03701"){
                $serviceNo="BizOrderService";
                $_sjson = array(
                    'METHOD'=>'getToPayEmpByOederId',
                    //企业编号
                    'CUSTOMER_ID'=>$_POST['CUSTOMER_ID'],
                    //搜索用企业名称
                    'CUSTOMER_NAME'=>''
                );
            }else{
                $serviceNo="DepartmentService";
                $_sjson = array(
                    'METHOD'=>'getEmpListByTypeId',
                    'TYPE_ID'=>$_REQUEST['CUSTOMER_ID'],
                    //群组或者部门_用于搜索
                    'TYPE_NAME'=> $_POST['TYPE_NAME'] ? $_POST['TYPE_NAME'] : '',
                    //操作雇员id
                    'HUMBAS_NO' => $this->humbas_no,
                    //群主或者部门
                    'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                );
            }
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
            $render = new base_render(app::get('qiyecenter'));
            $this->pagedata['PRICE'] = $EMP_NEW_PRICE;
            echo $render->fetch('site/member/get_manual_selection.html');
        }

    }
    //雇员查询筛选
    function get_guyuan_gyselect(){
        header("Cache-Control:no-store, no-cache, must-revalidate");
       //手工选择
            $serviceNo="BizOrderService";
            $_sjson = array(
                'METHOD'=>'getToPayEmpByOederId',
                //企业编号
                'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'],
                //雇员编号ID
                'HUMBAS_NO'=>$_GET['EMP_HUMBAS_NO'],
                //搜索用雇员名称
                'EMP_NAME'=>$_GET['EMP_NAME'],
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
            $render = new base_render(app::get('qiyecenter'));
            $this->pagedata['PRICE'] = $_GET['EMP_NEW_PRICE'];
            echo $render->fetch('site/member/get_manual_gyselect.html');

    }

    function downRecharge(){
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        $csv_data[0]=array('雇员id(HUMBAS_NO)','雇员名称(NAME)','商社名称(CUSTOMER_NAME)','身份证号(ID)','积分数(NEW_PRICE)');
        $csv_data[1]=array('','');
        $csv_string = null;
        $csv_row = array();
        foreach($csv_data as $key=>$csv_item)
        {
            /*
            if($key === 0){$csv_row[]=implode(",",$csv_item);continue;}
            */
            $current = array();
            foreach($csv_item AS $item)
            {
                /****************************************************************************************************************************
                 *很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
                 ****************************************************************************************************************************/
                $current[] = is_numeric($item)?$item:'"'.str_replace('"','""',$item ).'"';
                //$current[]='"'.str_replace('"','""',$item).'"';
            }
            $csv_row[] = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=pass".$_GET['card_id'].".csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string,'GBK','UTF-8');
    }


    function do_importRecharge(){
        $csv_array=$this->read_csv($_FILES['sandRecharge']);
        if(!is_array($csv_array) || empty($csv_array)){
            $this->end(false,app::get('b2c')->_('无数据录入'));
        }else{
            $csv_array = array_merge($csv_array,array());
            $serviceNo="BizOrderService";
            $_sjson = array(
                'METHOD'=>'ImportFileByJsonArray',
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'HumbasNoArray'=>$csv_array,
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            if($sfscdata['RESULT_CODE'] == "10001"){
                $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
                $render = new base_render(app::get('qiyecenter'));
                echo $render->fetch('site/member/do_importRecharge.html');
            }
        }
    }

    function read_csv($file){
        $file_type = substr(strrchr($file['name'],'.'),1);
        if(empty($file['tmp_name'])){
            $this->end(false,app::get('b2c')->_('文件不能为空'));
        }
        // 检查文件格式
        if ($file_type != 'csv'){
            $this->end(false,app::get('b2c')->_('文件格式不对,请重新上传'));
        }
        $handle = fopen($file['tmp_name'],"r");
        
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
                    $post[$key][$m_key[$v_key]]=$v_value1;
                }
            }
            $key++;
        }
        fclose($handle);
        
        return $post;
    }



    function get_order_source(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD'=>'getOrderSourceName',
            'HUMBAS_NO'=>$this->humbas_no,
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if(empty($sfscdata['RESULT_DATA'])){
            echo '<option value="">请选择</option>';
        }else{
            $tmp = "<option value=\"\">请选择</option>";
            foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                $tmp .= '<option value="'.$v['ITEM_CODE'].'">'.$v['ITEM_NAME'].'</option>';
            }
        }
        echo $tmp;
    }

    function save_jifen_order(){
        /*
            {
                "SYSTEM_ID": "第三方系统ID",
                "ORDER_NO ": "订单编号",
                "ORDER_ID ": "订单ID",
                "GRANT_NAME": "发放名目",
                "REC_ACT_TYPE": "接受积分类型",I00103->部门账户 I00104->群组账户 I00105->雇员账户
                "PRICE": "福点数量",
                "ORDER_TYPE":"I02101：发放到企业  I02102：发放到雇员 个人和群组",
                "CONTRACT_NO":"合同号",
                "BUSINESS_MAN":"业务员",
                "BUSINESS_DEPT":"业务部",
                "ORDER_SOURCE":"订单的来源",:I00703->平台, I00702->浩东 ,I00701->E-HR ,I00704-->企业,I00705-->其他
                "BIZ_TYPE": "接受积分类型", I00103->部门账户 I00104->群组账户 I00105->雇员账户
                "CUSTOMERS": [
                    {
                        "CUSTOMER_ID": "商社ID",
                        "RECS": [
                            {
                            "REC_ACT_TYPE": "发放类型：个人(I00105)群组(I00104)部门(I00103)",
                            "REC_ID": "个人部门群组ID"
                            }
                        ]
                    },
                    {
                        "CUSTOMER_ID": "商社ID",
                        "RECS": [
                            {
                            "REC_ACT_TYPE": "发放类型：个人(I00105)群组(I00104)部门(I00103)",
                            "REC_ID": "个人部门群组ID"
                            },
                            {
                            "REC_ACT_TYPE": "发放类型：个人(I00105)群组(I00104)部门(I00103)",
                            "REC_ID": "个人部门群组ID"
                            },
                            {
                            "REC_ACT_TYPE": "发放类型：个人(I00105)群组(I00104)部门(I00103)",
                            "REC_ID": "个人部门群组ID"
                            }

                        ]
                    },
                    {}
                ]
            }
        */

        //BizOrderExecService(deptOrGroupgrantWeal)
        $serviceNo="BizOrderExecService";
        $CUSTOMERS = array();
        $CUSTOMERS_TMP = array();
        $PRICE_ARR = array();
        $ARR = array();
        $newarray = array();
        $narr = array();
        if(!empty($_POST['ORDER_ITEM'])){
           //数组合并
            $a = array(ORDER_ITEM=>$_POST['ORDER_ITEM']);
            $b = array (NEW_PRICE=>$_POST['NEW_PRICE']);
            $test = array("a"=>ORDER_ITEM,"b"=>NEW_PRICE);
            $result = array();
            for($i=0;$i<count($a[ORDER_ITEM]);$i++){
                foreach($test as $key=>$value){
                    $result[$i][$value] = ${$key}[$value][$i];
                }
            }
            //合并end
            foreach ($result as $k=>$v){

                $TMP =  explode("_",$v['ORDER_ITEM']);
                
                $CUSTOMERS_TMP[$TMP[0]][] = array('REC_ACT_TYPE' => $_POST['REC_ACT_TYPE'],'REC_ID' => $TMP[1],'PRICE' => $v['NEW_PRICE'],);
              
                    
             }


             // var_dump($CUSTOMERS_TMP);
           
            
        }

        if($this->qiye_role_id == "I03701"){
  
           
          foreach ($CUSTOMERS_TMP as $key=>$val){
                $CUSTOMERS[] = array(
                    'CUSTOMER_ID' => $key,
                    'RECS' => $val
                );
            }
            $_sjson_tmp = array(
                'SYSTEM_ID' => '',
                'ORDER_NO' => $_POST['ORDER_NO'],
                'ORDER_ID' => $_POST['uuid'],
                'GRANT_NAME' => $_POST['GRANT_NAME'],
                'REC_ACT_TYPE' => $_POST['REC_ACT_TYPE'],
                // 'PRICE' => $_POST['PRICE'],
                'ORDER_TYPE' => 'I02102',
                'CONTRACT_NO' => $_POST['CONTRACT_NO'],
                'BUSINESS_MAN' => $_POST['BUSINESS_MAN'],
                'BUSINESS_DEPT' => $_POST['BUSINESS_DEPT'],
                'ORDER_SOURCE' => $_POST['ORDER_SOURCE'],
                'BIZ_TYPE' => $_POST['BIZ_TYPE'],
                'CUSTOMERS' => $CUSTOMERS,
            );

            $_sjson = array(
                'ifPhp'=>'yoofuu_shop',
                'METHOD' => 'phpMarginGrantWeal',
                'wealJson'=>$_sjson_tmp,
            );
        }else{

            foreach ($CUSTOMERS_TMP as $key=>$val){
                $CUSTOMERS[] = array(
                    'CUSTOMER_ID' => $_POST['CUSTOMER_ID'],
                    'TYPE_ID' => $key,
                    'RECS' => $val
                );
            }
            // ORDER_TYPE = I02104 部门/  I02105群组
            if($this->qiye_role_id  == 'I03703'){
                $ORDER_TYPE = 'I02105';
            }else{
                $ORDER_TYPE = 'I02104';
            }

            $_sjson_tmp = array(
                'SYSTEM_ID' => '',
                'SETUP_MANAGER_TYPE'=> $this->qiye_role_id,
                'ORDER_NO' => $_POST['ORDER_NO'],
                'ORDER_ID' => $_POST['uuid'],
                'GRANT_NAME' => $_POST['GRANT_NAME'],
                'REC_ACT_TYPE' => $_POST['REC_ACT_TYPE'],
                'PRICE' => $_POST['PRICE'],
                'ORDER_TYPE' => $ORDER_TYPE,
                'CONTRACT_NO' => $_POST['CONTRACT_NO'],
                'BUSINESS_MAN' => $_POST['BUSINESS_MAN'],
                'BUSINESS_DEPT' => $_POST['BUSINESS_DEPT'],
                'ORDER_SOURCE' => $_POST['ORDER_SOURCE'],
                'BIZ_TYPE' => $_POST['BIZ_TYPE'],
                'DEPT_OR_GROUP' => $CUSTOMERS,
            );
            $_sjson = array(
                'ifPhp'=>'yoofuu_shop',
                'METHOD' => 'deptOrGroupgrantWeal',
                'wealJson'=>$_sjson_tmp,
            );
        }
         // $json = json_encode($_sjson);
         // echo($json);
         // exit();
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        // var_dump($sfscdata);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('success'=>'true'));
        }else{
            echo json_encode(array('error'=>$sfscdata['RESULT_MESSAGE']));
        }
    }

    //积分发放查询列表
    function jifen_list(){
        //StatisticsService（getDeptOrGroupPointByHumbasNo）
        if($this->qiye_role_id == "I03701"){
          $serviceNo="StatisticsService";
          $_sjson = array(
            'METHOD'=>'getPointStatisticsByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
            'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))) : "",
            'CUSTOMER_NAME'=>$_POST['CUSTOMER_NAME'] ? trim($_POST['CUSTOMER_NAME']) : "",
        ); 
        }else{
          $serviceNo="StatisticsService";
          $_sjson = array(
            'METHOD'=>'getDeptOrGroupPointByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            'SETUP_MANAGER_TYPE'=>$this->qiye_role_id,
            'TYPE_ID' =>$_POST['TYPE_ID'] ? trim($_POST['TYPE_ID']) : "",
            'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
            'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))):"",
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if(!empty($sfscdata['RESULT_DATA'])){
            foreach($sfscdata['RESULT_DATA'] as $k=>$v){
                if($v['DEPT_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['DEPT_ID'];
                }
                if($v['GROUP_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['GROUP_ID'];
                }

                $sfscdata['RESULT_DATA'][$k]['mouth_strtotime'] = strtotime($v['MOUTH'])*1000;
                if($v['POINT_PUT_OUT'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_PUT_OUT'] = '0';
                }
                if($v['POINT_CONSUME'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_CONSUME'] = '0';
                }
            }
        }
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['SETUP_MANAGER_TYPE'] = $this->qiye_role_id;
        $this->output();
    }
    //积分发放统计列表导出
     function jifen_list_export(){
        //start
         if($this->qiye_role_id == "I03701"){
          $serviceNo="StatisticsService";
          $_sjson = array(
            'METHOD'=>'getPointStatisticsByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            // 'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
            // 'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))) : "",
            'CUSTOMER_NAME'=>$_POST['CUSTOMER_NAME'] ? trim($_POST['CUSTOMER_NAME']) : "",
        ); 
        }else{
          $serviceNo="StatisticsService";
          $_sjson = array(
            'METHOD'=>'getDeptOrGroupPointByHumbasNo',
            'HUMBAS_NO'=>$this->humbas_no,
            'SETUP_MANAGER_TYPE'=>$this->qiye_role_id,
            'TYPE_ID' =>$_POST['TYPE_ID'] ? trim($_POST['TYPE_ID']) : "",
            // 'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
            // 'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))):"",
            );
        }
        header('Content-Type:text/html; charset=utf-8');
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if(!empty($sfscdata['RESULT_DATA'])){
            foreach($sfscdata['RESULT_DATA'] as $k=>$v){
                if($v['DEPT_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['DEPT_ID'];
                }
                if($v['GROUP_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['GROUP_ID'];
                }

                $sfscdata['RESULT_DATA'][$k]['mouth_strtotime'] = strtotime($v['MOUTH'])*1000;
                if($v['POINT_PUT_OUT'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_PUT_OUT'] = '0';
                }
                if($v['POINT_CONSUME'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_CONSUME'] = '0';
                }
            }
        }

        $aData['vcode']='0001';
        $aData['vcode']=str_pad((intval($aData['vcode'])+1), 4, "0", STR_PAD_LEFT);

        // header("Content-Type: text/csv");
        header("Content-type:application/vnd.ms-excel");
        $filename = "jifen_list_".$aData['vcode'].".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        if($this->qiye_role_id== 'I0370'){
           $csv_data[0]=array('商社名称(CUSTOMER_NAME)','月份(MOUTH)','企业充值(POINT_PUT_OUT)','积分发放(POINT_CONSUME)');
        }else{
           $csv_data[0]=array('商社名称(CUSTOMER_NAME)','月份(MOUTH)','积分发放(POINT_PUT_OUT)','消费积分(POINT_CONSUME)');
        }
        foreach($sfscdata['RESULT_DATA'] as $key=>$item){
            $arr = array();
            $arr = array(
                'CUSTOMER_NAME'=> "\t".$item['CUSTOMER_NAME']."\t", 
                'MOUTH'=>$item['MOUTH'],    
                'POINT_PUT_OUT'=>$item['POINT_PUT_OUT'],    
                'POINT_CONSUME'=>$item['POINT_CONSUME'],
            );
            $csv_data[] = $arr;
        }
        $csv_row = array();
        foreach($csv_data as $key=>$csv_item)
        {
            $current = array();
            foreach($csv_item AS $item)
            {
                /****************************************************************************************************************************
                 *很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
                 ****************************************************************************************************************************/
                $current[] = is_numeric($item)?$item:'"'.str_replace('"','""',$item ).'"';

            }
            $csv_row[] = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        //end
        if(function_exists('iconv')){
            echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        }else{
            echo kernel::single('base_charset')->utf2local( $csv_string );
        }
    }
    //end
    function jifen_receive(){

        if($this->qiye_role_id == "I03701"){
            $serviceNo="StatisticsService";
            $_sjson = array(
                'METHOD' => 'getgiftStatisticsByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'GRANT_NAME' => $_POST['GRANT_NAME'] ? $_POST['GRANT_NAME'] : "",
                // 'CUSTOMER_NAME' => $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : "",
                // 'END_TIME' => $_POST['END_TIME'] ? trim($_POST['END_TIME']) : "",
                // 'STRAT_TIME' => $_POST['STRAT_TIME'] ? trim($_POST['STRAT_TIME']) : "",
                'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
                'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))):"",
                'CUSTOMER_NAME' => $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : "",
            );
        }else{
            $serviceNo="StatisticsService";
            $_sjson = array(
                'METHOD' => 'outDeptOrGroupStatisticsByHumbasNo',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'GRANT_NAME' => $_POST['GRANT_NAME'] ? $_POST['GRANT_NAME'] : "",
                // 'CUSTOMER_NAME' => $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : "",
                // 'END_TIME' => $_POST['END_TIME'] ? trim($_POST['END_TIME']) : "",
                // 'STRAT_TIME' => $_POST['STRAT_TIME'] ? trim($_POST['STRAT_TIME']) : "",
                'END_TIME' =>$_POST['END_TIME']?date('Y-m',strtotime(trim($_POST['END_TIME']))):"",
                'STRAT_TIME' =>$_POST['STRAT_TIME']?date('Y-m',strtotime(trim($_POST['STRAT_TIME']))):"",
                'TYPE_ID' => $_POST['CUSTOMER_NAME'] ? $_POST['CUSTOMER_NAME'] : "",
            );
        }


        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if(!empty($sfscdata['RESULT_DATA'])){
            foreach($sfscdata['RESULT_DATA'] as $k=>$v){
                if($v['DEPT_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['DEPT_ID'];
                }
                if($v['GROUP_ID']){
                    $sfscdata['RESULT_DATA'][$k]['TYPE_ID'] = $v['GROUP_ID'];
                }

                $sfscdata['RESULT_DATA'][$k]['mouth_strtotime'] = strtotime($v['MOUTH'])*1000;
                if($v['POINT_PUT_OUT'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_PUT_OUT'] = '0';
                }
                if($v['POINT_CONSUME'] == ''){
                    $sfscdata['RESULT_DATA'][$k]['POINT_CONSUME'] = '0';
                }
            }
        }
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->output();
    }

    function sendmessages(){
        $serviceNo="BizOrderService";
        $_sjson = array(
            'METHOD' => 'getOrderPhoneMessageByOederId',
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID' => $_GET['ORDER_ID'] ? $_GET['ORDER_ID'] : "",
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['ORDER_ID'] = $_GET['ORDER_ID'];
        $this->pagedata['sid'] = time();
        $this->singlepage('site/member/sendmessages.html','qiyecenter');
    }

    function save_message(){
        $serviceNo="BizOrderService";
        //ORDER_ID,SEND_MEG_ID,SEND_TYPE,CONTENTS
        $_sjson = array(
            'METHOD' => 'updateOrderPhoneMessageByOederId',
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID' => $_POST['ORDER_ID'] ? $_POST['ORDER_ID'] : '',
            'SEND_MEG_ID' => $_POST['SEND_MEG_ID'] ? $_POST['SEND_MEG_ID'] : '',
            'SEND_TYPE' => '0',
            'CONTENTS' => $_POST['CONTENTS'] ? $_POST['CONTENTS'] : '',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('sussce'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }

    }

    function sendmessageaction(){
        //ORDER_ID、SEND_METHOD、REC_ACT_TYPE
        $serviceNo="BizOrderExecService";
        $_sjson = array(
            'METHOD' => 'sendmsgByOrderId',
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID' => $_POST['ORDER_ID'] ? $_POST['ORDER_ID'] : '',
            'SEND_METHOD' => $_POST['SEND_METHOD'] ? $_POST['SEND_METHOD'] : '',
            'REC_ACT_TYPE' => $_POST['REC_ACT_TYPE'] ? $_POST['REC_ACT_TYPE'] : '',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('sussce'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }
    }



    function get_detailed(){
        if($this->qiye_role_id == "I03701"){
            $serviceNo="StatisticsService";
            //CUSTOMER_ID、FILTER_TIME
            $_sjson = array(
                'METHOD'=>'getPointStatisticsOfCompany',
                'HUMBAS_NO'=>$this->humbas_no,
                'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : "",
                'FILTER_TIME' => $_GET['FILTER_TIME'] ? $_GET['FILTER_TIME'] : '',
                'STRAT_TIME' => $_GET['STRAT_TIME'] ? $_GET['STRAT_TIME'] : '',
                'END_TIME' => $_GET['END_TIME'] ? $_GET['END_TIME'] : '',

            );
        }else{
            $serviceNo="StatisticsService";
            $_sjson = array(
                //TYPE_ID、FILTER_TIME、SETUP_MANAGER_TYPE
                'METHOD'=>'getPointStatisticsOfDeptOrGroup',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'TYPE_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : "",
                'FILTER_TIME' => $_GET['FILTER_TIME'] ? $_GET['FILTER_TIME'] : '',
                'STRAT_TIME' => $_GET['STRAT_TIME'] ? $_GET['STRAT_TIME'] : '',
                'END_TIME' => $_GET['END_TIME'] ? $_GET['END_TIME'] : '',
            );
        }
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['SETUP_MANAGER_TYPE'] = $this->qiye_role_id;
        $this->pagedata['sid'] = time();
        if($this->qiye_role_id == 'I03701'){
           $this->singlepage('site/member/get_detailed.html','qiyecenter');
        }else{
            $this->singlepage('site/member/role/get_detailed.html','qiyecenter');
        }
    }

    //积分发放明细导出
     function get_detailed_export(){ 
        
        if($this->qiye_role_id == "I03701"){
            $serviceNo="StatisticsService";
            //CUSTOMER_ID、FILTER_TIME
            $_sjson = array(
                'METHOD'=>'getPointStatisticsOfCompany',
                'HUMBAS_NO'=>$this->humbas_no,
                'CUSTOMER_ID'=>$_POST['CUSTOMER_ID'] ? $_POST['CUSTOMER_ID'] : "",
                'FILTER_TIME' =>$_POST['FILTER_TIME'] ?date('Y-m',strtotime(trim($_POST['FILTER_TIME']))) : '',

            );
        }else{
            $serviceNo="StatisticsService";
            $_sjson = array(
                //TYPE_ID、FILTER_TIME、SETUP_MANAGER_TYPE
                'METHOD'=>'getPointStatisticsOfDeptOrGroup',
                'HUMBAS_NO'=>$this->humbas_no,
                'SETUP_MANAGER_TYPE' => $this->qiye_role_id,
                'TYPE_ID'=>$_POST['CUSTOMER_ID'] ? $_POST['CUSTOMER_ID'] : "",
                'FILTER_TIME' =>$_POST['FILTER_TIME'] ?date('Y-m',strtotime(trim($_POST['FILTER_TIME']))) : '',
            );
        }
        header('Content-Type:text/html; charset=utf-8');
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $aData['vcode']='0001';
        $aData['vcode']=str_pad((intval($aData['vcode'])+1), 4, "0", STR_PAD_LEFT);

        // header("Content-Type: text/csv");
        header("Content-type:application/vnd.ms-excel");
        $filename = "get_detailed_".$aData['vcode'].".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');

       $csv_data[0]=array('操作日期(REC_TIME)','发放（消费）名目(NAME)','操作类型(CUSTOMER_NAME)','积分(ID)','编号(REC_ID)','姓名(NAME)');
        foreach($sfscdata['RESULT_DATA'] as $key=>$item){
            $arr = array();
            $arr = array(
                'REC_TIME'=> "\t".$item['REC_TIME']."\t", 
                'PUT_OUT_NAME'=>$item['PUT_OUT_NAME'],    
                'POINT_TYPE'=>$item['POINT_TYPE'],    
                'POINT'=>$item['POINT'],
                'REC_ID'=>$item['REC_ID'],
                'NAME'=>$item['NAME'],
            );
            $csv_data[] = $arr;
        }
        $csv_row = array();
        foreach($csv_data as $key=>$csv_item)
        {
            $current = array();
            foreach($csv_item AS $item)
            {
                /****************************************************************************************************************************
                 *很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
                 ****************************************************************************************************************************/
                $current[] = is_numeric($item)?$item:'"'.str_replace('"','""',$item ).'"';

            }
            $csv_row[] = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        //end
        if(function_exists('iconv')){
            echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        }else{
            echo kernel::single('base_charset')->utf2local( $csv_string );
        }
    }
    function get_lihaodetailed(){
        $serviceNo="StatisticsService";
        //CUSTOMER_ID、FILTER_TIME、GIFT_NAME
        //STRAT_TIME、END_TIME
        $_sjson = array(
            'METHOD'=>'getgiftStatisticsOfCompany',
            'HUMBAS_NO'=>$this->humbas_no,
            'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : '',
            'FILTER_TIME' => $_GET['FILTER_TIME'] ? $_GET['FILTER_TIME'] : '',
            'GIFT_NAME' => $_GET['GIFT_NAME'] ? $_GET['GIFT_NAME'] : '',
            'STRAT_TIME' => $_GET['STRAT_TIME'] ? $_GET['STRAT_TIME'] : '',
            'END_TIME' => $_GET['END_TIME'] ? $_GET['END_TIME'] : '',
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $this->pagedata['sfscdata'] = $sfscdata['RESULT_DATA'];
        $this->pagedata['sid'] = time();
        $this->singlepage('site/member/get_lihaodetailed.html','qiyecenter');
    }



    function getpackage(){
        $serviceNo="StatisticsService";
        $_sjson = array(
            'METHOD'=>'getGiftNameByCompany',
            'HUMBAS_NO'=>$this->humbas_no,
            'CUSTOMER_ID'=>$_GET['CUSTOMER_ID'] ? $_GET['CUSTOMER_ID'] : "",
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        $tmp = "";
        if(!empty($sfscdata['RESULT_DATA'])){
            foreach ($sfscdata['RESULT_DATA'] as $k=>$v){
                $tmp .= '<option value="'.$v['GRANT_NAME'].'">'.$v['GRANT_NAME_SHOW'].'</option>';
            }
            echo $tmp;
        }else{
            echo '<option>请选择</option>';
        }
    }

    function backpoint(){
        $serviceNo="BizOrderExecService";
        //$this->member['name']
        $_sjson = array(
            'METHOD'=>'backPoint',
            'HUMBAS_NO'=>$this->humbas_no,
            'ORDER_ID'=>$_POST['ORDER_ID'] ? $_POST['ORDER_ID'] : "",
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
        if($sfscdata['RESULT_CODE'] == "10001"){
            echo json_encode(array('sussce'=>$sfscdata['RESULT_MSG']));
        }else{
            echo json_encode(array('error'=>$sfscdata['RESULT_MSG']));
        }
    }


    /**
     * 企业中心订单提交页面
     * @params string order id
     * @params boolean 支付方式的选择
     */
    public function orderPayments($order_id,$selecttype=false,$from=false)
    {
        $objOrder = &$this->app->model('orders');
        $sdf = $objOrder->dump($order_id);
        $objMath = kernel::single("ectools_math");
        if(!$sdf){
            exit;
        }
        $sdf['total'] = $sdf['cur_amount'];
        $sdf['cur_amount'] = $objMath->number_minus(array($sdf['cur_amount'], $sdf['payed']));
        $sdf['total_amount'] = $objMath->number_div(array($sdf['cur_amount'], $sdf['cur_rate']));
        $this->pagedata['order'] = $sdf;
         //zxc显示具体支付账号
        $serviceNo="SubAccountService";
        $_sjson = array(
            'METHOD' => 'getCompanyDepGroupToPayByCompanyNo',
            'RELATION_ID' => $this->pagedata['order']['java_payment_company'],
        );
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

        //I03701    商社管理员
        //I03702    部门管理员
        //I03703    群组管理员
        if(!empty($sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'])){
            $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] .= '-';
        }
        if('10001' == $sfscdata['RESULT_CODE']){
            $sfscdata['RESULT_DATA']['zhifu_name']= $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] . $sfscdata['RESULT_DATA'][0]['RELATION_NAME'];
        }else{
            $serviceNo="AccountService";
            $_sjson = array(
                'METHOD' => 'getDeptOrGroupAccountByHumbasNo',
                'SETUP_MANAGER_TYPE' =>$_SESSION['account']['SETUP_MANAGER_TYPE'],
                'HUMBAS_NO' => $_SESSION['account']['HUMBAS_NO'],
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);

            if(!empty($sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'])){
                $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] .= '-';
            }
            $sfscdata['RESULT_DATA']['zhifu_name']= $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] . $sfscdata['RESULT_DATA'][0]['DEPT_NAME'];
        }

       $this->pagedata['zhifu_name']=$sfscdata['RESULT_DATA']['zhifu_name'];
        // 货到付款不能进入此页面
        if ($sdf['payinfo']['pay_app_id'] == '-1')
        {
            $this->begin(array('app' => 'b2c','ctl' => 'site_member', 'act'=>'orderdetail', 'arg0'=>$order_id));
            $this->end(false, app::get('b2c')->_('配送方式只支持货到付款'));
        }

        if($selecttype){
            $selecttype = 1;
        }else{
            $selecttype = 0;
        }
        $this->pagedata['order']['selecttype'] = $selecttype;
        $opayment = app::get('ectools')->model('payment_cfgs');
        $this->pagedata['payments'] = $opayment->getListByCode($sdf['currency']);

        $system_money_decimals = $this->app->getConf('system.money.decimals');
        $system_money_operation_carryset = $this->app->getConf('system.money.operation.carryset');
        foreach ($this->pagedata['payments'] as $key=>&$arrPayments)
        {
            if (!$sdf['member_id'])
            {
                if (trim($arrPayments['app_id']) == 'deposit')
                {
                    unset($this->pagedata['payments'][$key]);
                    continue;
                }
            }

            if ($arrPayments['app_id'] == $this->pagedata['order']['payinfo']['pay_app_id'])
            {
                $arrPayments['cur_money'] = $objMath->formatNumber($this->pagedata['order']['cur_amount'], $system_money_decimals, $system_money_operation_carryset);
                $arrPayments['total_amount'] = $objMath->formatNumber($this->pagedata['order']['total_amount'], $system_money_decimals, $system_money_operation_carryset);
            }
            else
            {
                $arrPayments['cur_money'] = $this->pagedata['order']['cur_amount'];
                $cur_discount = $objMath->number_multiple(array($sdf['discount'], $this->pagedata['order']['cur_rate']));
                if ($this->pagedata['order']['payinfo']['cost_payment'] > 0)
                {
                    if ($this->pagedata['order']['cur_amount'] > 0)
                        $cost_payments_rate = $objMath->number_div(array($arrPayments['cur_money'], $objMath->number_plus(array($this->pagedata['order']['cur_amount'], $this->pagedata['order']['payed']))));
                    else
                        $cost_payments_rate = 0;
                    $cost_payment = $objMath->number_multiple(array($objMath->number_multiple(array($this->pagedata['order']['payinfo']['cost_payment'], $this->pagedata['order']['cur_rate'])), $cost_payments_rate));
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cur_discount));
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cost_payment));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $objMath->number_multiple(array($arrPayments['cur_money'], $arrPayments['pay_fee']))));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cur_discount));
                }
                else
                {
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cur_discount));
                    $cost_payment = $objMath->number_multiple(array($arrPayments['cur_money'], $arrPayments['pay_fee']));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cost_payment));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cur_discount));
                }

                $arrPayments['total_amount'] = $objMath->formatNumber($objMath->number_div(array($arrPayments['cur_money'], $this->pagedata['order']['cur_rate'])), $system_money_decimals, $system_money_operation_carryset);
                $arrPayments['cur_money'] = $objMath->formatNumber($arrPayments['cur_money'], $system_money_decimals, $system_money_operation_carryset);
            }
        }

        $objCur = app::get('ectools')->model('currency');
        $aCur = $objCur->getFormat($this->pagedata['order']['currency']);
        $this->pagedata['order']['cur_def'] = $aCur['sign'];

        $this->pagedata['return_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result'));
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->pagedata['form_action'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'dopayment','arg0'=>'order'));
        $obj_order_payment_html = kernel::servicelist('b2c.order.pay_html');
        $app_id = 'b2c';
        if ($obj_order_payment_html)
        {
            foreach ($obj_order_payment_html as $obj)
            {
                $obj->gen_data($this, $app_id);
            }
        }

        if ($sdf['cur_amount'] == '0' && $sdf['pay_status'] == '0')
        {
            // 模拟支付流程
            $objPay = kernel::single("ectools_pay");
            $sdffds = array(
                'payment_id' => $objPay->get_payment_id(),
                'order_id' => $sdf['order_id'],
                'rel_id' => $sdf['order_id'],
                'op_id' => $sdf['member_id'],
                'pay_app_id' => $sdf['payinfo']['pay_app_id'],
                'currency' => $sdf['currency'],
                'payinfo' => array(
                    'cost_payment' => $sdf['payinfo']['cost_payment'],
                ),
                'pay_object' => 'order',
                'member_id' => $sdf['member_id'],
                'op_name' => $this->user->user_data['account']['login_name'],
                'status' => 'ready',
                'cur_money' => $sdf['cur_amount'],
                'money' => $sdf['total_amount'],
            );
            $is_payed = $objPay->gopay($sdffds, $msg);
            if (!$is_payed){
                $msg = app::get('b2c')->_('订单自动支付失败！');
                $this->end(false, $msg, $this->gen_url(array('app'=>'b2c','ctl'=>'site_cart','act'=>'checkout')));
            }

            $obj_pay_lists = kernel::servicelist("order.pay_finish");
            $is_payed = false;
            foreach ($obj_pay_lists as $order_pay_service_object)
            {
                $is_payed = $order_pay_service_object->order_pay_finish($sdffds, 'succ', 'font',$msg);
            }
        }
        //平台客服
        $qq=app::get('b2c')->getConf('member.ServiceQQ');
        $this->pagedata['qq']=$qq;
        $tel=app::get('b2c')->getConf('member.ServiceTel');
        $this->pagedata['tel']=$tel;
        //begin 获取银行信息
        $bankInfo = kernel::single('b2c_banks_info')->getBank();
        $this->pagedata['bankinfo'] = $bankInfo;
        //	echo '<pre>';print_r($bankInfo);exit;

        //获取我的福点余额 start
        //if($this->pagedata['order']['payinfo']['pay_app_id'] == "sfscpay"){
        $_sjson = array(
            'METHOD'=>'getBalanceInfoByRelationId',
            'RELATION_ID'=>$this->pagedata['order']['java_payment_company'],
            'QUERY_TIME'=>""
        );
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        if($tmpdata != null && gettype($tmpdata) == "object"){
            $tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
        }else{
            $tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
        }

        $this->pagedata['sfsc_balance']=$tmp22['RESULT_DATA'];
        //}
        //获取我的福点余额 end


        //获取组合支付开关状态
        $is_open_combine_pay = app::get('ectools')->getConf('site.is_open_combine_pay');
        //判断组合支付
        if(strpos($sdf['payinfo']['pay_app_id'],'sfscpay')!==false&&strpos($sdf['payinfo']['pay_app_id'],',')!==false){
            $this->pagedata['order']['payinfo']['combine_pay'] = true;
            $arr_payments=explode(',',$sdf['payinfo']['pay_app_id']);
            foreach($arr_payments as $k=>$v){
                if($v=='sfscpay')unset($arr_payments[$k]);
            }
            $this->pagedata['order']['payinfo']['pay_app_id'] = implode(',',$arr_payments);
        }elseif($sdf['payinfo']['pay_app_id']=='sfscpay'){
            //若为福点支付，则检查福点余额是否足够支付
            $points = $tmp22['RESULT_DATA']['SUM'];
            $lack = $points>=$sdf['cur_amount']?0:$sdf['cur_amount']-$points;
            $lack = round($lack,2);
            if(!$is_open_combine_pay){$lack=0;}
            $this->pagedata['order']['lack'] = $lack;
            if($lack > 0){
                //获取支付方式
                $ctl_cart = kernel::single('b2c_ctl_site_cart');
                $obj_payments = kernel::single('ectools_payment_select');
                $currency = app::get('ectools')->model('currency');
                $arrMember = $this->get_current_member();
                $str_def_currency = $arrMember['member_cur'] ? $arrMember['member_cur'] : "";
                if (!$str_def_currency){
                    $arrDefCurrency = $currency->getDefault();
                    $str_def_currency = $arrDefCurrency['cur_code'];
                }else{
                    $arrDefCurrency = $currency->getcur($str_def_currency);
                }
                $this->pagedata['app_id'] = 'b2c';
                $order = $this->pagedata['order'];
                $obj_payments->select_pay_method($this, $arrDefCurrency, $arrMember['member_id']);
                //订单数组被select_pay_method修改，需重新赋值
                $this->pagedata['order'] = $order;
                //从选出的支付方式中去除福点支付
                foreach($this->pagedata['payments'] as $k=>$v){
                    if($v['app_id']=='sfscpay')unset($this->pagedata['payments'][$k]);
                }
                $this->pagedata['payment_html'] = $this->fetch("site/common/paymethod.html",$this->pagedata['app_id']);
            }
        }

        if(!empty($_SESSION['sfsc']['vcat'])){
            $this->set_tmpl('hfcart');
        }
        if($from){
            //$this->set_tmpl('order_index');
            $this->page('site/member/orderPayments.html',false,'qiyecenter');
        }else{
            $this->page('site/member/orderPayments.html',false,'qiyecenter');
        }

    }

    /*
        申请开发票
    */
    function applyinvoice(){
        $order_id = trim($_GET['order_id']);
        if($order_id == ''){
            echo '无法得到订单号，请刷新页面重新获取';
            die();
        }
        if($this->qiyemember_id == ''){
            echo '会员登录超时，请重新登录';
            die();
        }
        //获取收货地址
        $objMem = app::get('b2c')->model('members');
        $this->pagedata['receiver'] = $objMem->getMemberAddr($this->qiyemember_id);
        if(!empty($this->pagedata['receiver'])){
            foreach ($this->pagedata['receiver'] as $k=>$v){
                if(!empty($v['area'])){
                    $tmp = explode(':',$v['area']);
                    $this->pagedata['receiver'][$k]['new_area'] = $tmp[1];
                }
            }
        }
        $invoice_model = app::get('b2c')->model('member_invoice');

        $data = $invoice_model->getlist('*',array('member_id'=>$this->qiyemember_id,'invoice_type'=>'com'));
        $this->pagedata['invoice_data'] = $data;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['sid'] = time();
        //start，后台显示发票的商社和姓名
        $objOrder = &$this->app->model('orders');
        $sdf_order = $objOrder->dump($order_id, 'java_payment_company');
         if($this->qiye_role_id == 'I03701'){
            $serviceNo="SubAccountService";
            //显示企业商社等级的选择情况
            $_sjson = array(
                'METHOD' => 'getCompanyToPayByCompanyNo',
                'COMPANY_NO' => $sdf_order['java_payment_company'],
            );
        }else if($this->qiye_role_id == 'I03703'){
            $serviceNo="AccountService";
            //显示群组商社等级的选择情况
             $_sjson = array(
                'METHOD' => 'getGroupAccountByGroupId',
                'GROUP_ID' => $sdf_order['java_payment_company'],
               
            );
        }else{
            $serviceNo="AccountService";
            $_sjson = array(
                'METHOD' => 'getDeptOrGroupAccountByHumbasNo',
                'SETUP_MANAGER_TYPE' =>$_SESSION['account']['SETUP_MANAGER_TYPE'],
                'HUMBAS_NO' => $_SESSION['account']['HUMBAS_NO'],
            );
        }
        header('Content-Type:text/html; charset=utf-8');
        $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
       if($this->qiye_role_id == 'I03701'){
         $sfscdata['RESULT_DATA']['zhifu_name']=$sfscdata['RESULT_DATA'][0][CUSTOMER_NAME];
       }else if($this->qiye_role_id == 'I03703'){
          $sfscdata['RESULT_DATA']['zhifu_name']=$sfscdata['RESULT_DATA'][0][GROUP_NAME];
       }else{
          $sfscdata['RESULT_DATA']['zhifu_name']=$sfscdata['RESULT_DATA'][0][DEPT_NAME];
       }
       $this->pagedata['zhifu_name']=$sfscdata['RESULT_DATA']['zhifu_name'];
       $this->pagedata['java_payment_company']=$sdf_order['java_payment_company'];

        $this->singlepage('site/member/applyinvoicelist.html','qiyecenter');
    }


    /*
        增值税发票和普票之间的切换tab
    */
    function tab_applyinvoice(){
        $type_id = trim($_POST['type']);
        $render = new base_render(app::get('qiyecenter'));

        $invoice_model = app::get('b2c')->model('member_invoice');
        if($type_id == 'manual_selection'){
            //普通发票 -- 数据读取操作
            $data = $invoice_model->getlist('*',array('member_id'=>$this->qiyemember_id,'invoice_type'=>'com'));
            $this->pagedata['invoice_data'] = $data;
            echo $render->fetch('site/member/tab_aicom.html');
        }else{
            //增值税发票 -- 数据读取操作
            $data = $invoice_model->getlist('*',array('member_id'=>$this->qiyemember_id,'invoice_type'=>'vat'));
            $this->pagedata['invoice_data'] = $data;
            echo $render->fetch('site/member/tab_aivat.html');
        }
    }

    /*
        发票增值税编辑前
    */
    function pre_applyinvoice(){
        $type = trim($_POST['type']);
        $invoice_id = trim($_POST['invoice_id']);
        $render = new base_render(app::get('qiyecenter'));
        $invoice_model = app::get('b2c')->model('member_invoice');
        $data = $invoice_model->getlist('*',array('invoice_id'=>$invoice_id));
        $this->pagedata['invoice_data'] = $data[0];
        if($type == 'com'){
            //普票
            echo $render->fetch('site/member/add_invoicecom.html');
        }else{
            //增票
            echo $render->fetch('site/member/add_invoicevat.html');
        }
    }

    /*
        修改后添加发票信息
      */
    function insert_invoice(){
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'myinvoice'));
        //$obj_member = app::get('b2c')->model('members');

        $aData = $this->check_input($_POST);
        $invoice_type = $aData['invoice_type'];
        $member_id = $this->qiyemember_id;

        if($invoice_type == 'com'){
            //普通发票新增
            $invoice_title = $aData['invoice_title'];
            if($invoice_title == ''){
                $this->splash('failed','','发票抬头不能为空！','','',true);
            }
            $data = array(
                'invoice_title' => $invoice_title,
                'member_id' => $member_id,
                'invoice_type' => 'com',
                'last_modify' => time(),
            );
            $invoice_model = app::get('b2c')->model('member_invoice');
            $result = $invoice_model->insert($data);
            if($result){
                $this->splash('success',$url,'新增成功','','',true);
            }else{
                $this->splash('failed','','新增失败','','',true);
            }

        }elseif($invoice_type == 'vat'){
            //增值税发票新增
            $company_name = $aData['company_name'];
            $vat_number = $aData['vat_number'];
            $vat_addr = $aData['vat_addr'];
            $vat_tel = $aData['vat_tel'];
            $vat_bank = $aData['vat_bank'];
            $bank_number = $aData['bank_number'];
            if($company_name == ''){
                $this->splash('failed','','公司名称不能为空','','',true);
            }
            if($vat_number == ''){
                $this->splash('failed','','纳税人识别号不能为空','','',true);
            }
            if($vat_addr == ''){
                $this->splash('failed','','注册地址不能为空','','',true);
            }
            if($vat_tel == ''){
                $this->splash('failed','','注册电话不能为空','','',true);
            }
            if($vat_bank == ''){
                $this->splash('failed','','开户银行不能为空','','',true);
            }
            if($bank_number == ''){
                $this->splash('failed','','银行账号不能为空','','',true);
            }
            $data = array(
                'company_name' => $company_name,
                'member_id' => $member_id,
                'invoice_type' => 'vat',
                'vat_number' => $vat_number,
                'vat_addr' => $vat_addr,
                'vat_tel' => $vat_tel,
                'vat_bank' => $vat_bank,
                'bank_number' => $bank_number,
                'last_modify' => time(),
            );

            $invoice_model = app::get('b2c')->model('member_invoice');
            $result = $invoice_model->insert($data);
            if($result){
                $this->splash('success',$url,'新增成功','','',true);
            }else{
                $this->splash('failed','','新增失败','','',true);
            }
        }
    }




    //设置和取消默认发票信息，$disabled 1为设置默认0为取消默认
    function set_default_invoice($invoice_id=null,$disabled='0',$invoice_type = 'com'){

        //lpc
        $invoice_id = $_POST['invoice_id'];
        $disabled  =$_POST['disabled']?$_POST['disabled']:0;
        $invoice_type = $_POST['invoice_type']?$_POST['invoice_type']:'com';

        if(!$invoice_id) $this->splash('failed', 'back', app::get('qiyecenter')->_('参数错误'));
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'myinvoice'));

        $invoice_obj = app::get('b2c')->model('member_invoice');
        $member_id = $this->qiyemember_id;
        $update_flag = $invoice_obj->update(array('invoice_default'=>'0'),array('member_id'=>$member_id,'invoice_type'=>$invoice_type));
        if(!$update_flag){
            $this->splash('failed','','设置默认失败','','',true);
        }

        $update_flag_tmp = $invoice_obj->update(array('invoice_default'=>'1'),array('invoice_id'=>$invoice_id));


        if($update_flag_tmp){
            $this->splash('success',$url,'设置默认成功','','',true);
        }else{
            $this->splash('failed','','设置默认失败','','',true);
        }

    }

    function del_invoice($invoice_id){
        //lpc
        $invoice_id = $_POST['invoice_id'];
        
        if(!$invoice_id) $this->splash('failed', 'back', app::get('qiyecenter')->_('参数错误'));
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'myinvoice'));
        $invoice_obj = app::get('b2c')->model('member_invoice');
        $del_flag = $invoice_obj->delete(array('invoice_id'=>$invoice_id));
        if($del_flag){
            $this->splash('success',$url,'删除成功','','',true);
        }else{
            $this->splash('failed','','删除失败','','',true);
        }

    }


    /*
       编辑发票环境
    */
    function edit_invoice(){
        $invoice_id = $_POST['invoice_id'];
        if(!$invoice_id)
        {
            echo  app::get('b2c')->_("参数错误");exit;
        }

        $invoice_obj = app::get('b2c')->model('member_invoice');
        $invoice_array = $invoice_obj->dump(array('invoice_id'=>$invoice_id),'*');

        if(!empty($invoice_array))
        {
            $render = new base_render(app::get('qiyecenter'));
            $this->pagedata['data'] = $invoice_array;
            echo $render->fetch('site/member/edit_invoice.html');
        }
        else
        {
            echo  app::get('b2c')->_("参数错误");exit;
        }
    }

    /*
        保存发票信息
    */
    function save_invoice(){
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'myinvoice'));

        $aData = $this->check_input($_POST);
        $invoice_type = $aData['invoice_type'];
        $member_id = $this->qiyemember_id;

        if($invoice_type == 'com'){
            //普通发票新增
            $invoice_title = $aData['invoice_title'];
            if($invoice_title == ''){
                $this->splash('failed','','发票抬头不能为空！','','',true);
            }
            $data = array(
                'invoice_title' => $invoice_title,
                'member_id' => $member_id,
                'invoice_type' => 'com',
                'last_modify' => time(),
            );
            $invoice_model = app::get('b2c')->model('member_invoice');
            $result = $invoice_model->update($data,array('invoice_id'=>$aData['invoice_id']));
            if($result){
                $this->splash('success',$url,'保存成功','','',true);
            }else{
                $this->splash('failed','','保存失败','','',true);
            }

        }elseif($invoice_type == 'vat'){
            //增值税发票新增
            $company_name = $aData['company_name'];
            $vat_number = $aData['vat_number'];
            $vat_addr = $aData['vat_addr'];
            $vat_tel = $aData['vat_tel'];
            $vat_bank = $aData['vat_bank'];
            $bank_number = $aData['bank_number'];
            if($company_name == ''){
                $this->splash('failed','','公司名称不能为空','','',true);
            }
            if($vat_number == ''){
                $this->splash('failed','','纳税人识别号不能为空','','',true);
            }
            if($vat_addr == ''){
                $this->splash('failed','','注册地址不能为空','','',true);
            }
            if($vat_tel == ''){
                $this->splash('failed','','注册电话不能为空','','',true);
            }
            if($vat_bank == ''){
                $this->splash('failed','','开户银行不能为空','','',true);
            }
            if($bank_number == ''){
                $this->splash('failed','','银行账号不能为空','','',true);
            }
            $data = array(
                'company_name' => $company_name,
                'member_id' => $member_id,
                'invoice_type' => 'vat',
                'vat_number' => $vat_number,
                'vat_addr' => $vat_addr,
                'vat_tel' => $vat_tel,
                'vat_bank' => $vat_bank,
                'bank_number' => $bank_number,
                'last_modify' => time(),
            );

            $invoice_model = app::get('b2c')->model('member_invoice');
            $result = $invoice_model->update($data,array('invoice_id' => $aData['invoice_id']));
            if($result){
                $this->splash('success',$url,'保存成功','','',true);
            }else{
                $this->splash('failed','','保存失败','','',true);
            }
        }
    }


    /*
        保存发票信息
    */
    function save_applyinvoice(){
        $type = $_POST['type'];
        $invoice_id = $_POST['invoice_id'];
        $member_id = $this->qiyemember_id;
        if($type == 'com'){
            $invoice_title = trim($_POST['invoice_title']);
            $invoice_default = $_POST['invoice_default'];
            if($invoice_title == ""){
                echo json_encode(array('error'=>'发票抬头不能为空！'));
            }
            $data = array(
                'invoice_title' => $invoice_title,
                'member_id' => $member_id,
                'invoice_type' => 'com',
                'invoice_default' => $invoice_default,
                'last_modify' => time(),
            );
            $invoice_model = app::get('b2c')->model('member_invoice');
            //普通发票保存
            if($invoice_id){
                //修改
                if($invoice_default){
                    $invoice_id_array = $invoice_model->getlist('invoice_id',array('member_id'=>$member_id,'invoice_default'=>'1','invoice_type'=>'com'));
                    if(!empty($invoice_id_array)){
                        //$invoice_id_array[0]['invoice_id']
                        $invoice_model->update(array('invoice_default'=>'0'),array('invoice_id'=>$invoice_id_array[0]['invoice_id']));
                    }
                    $result = $invoice_model->update($data,array('invoice_id'=>$invoice_id));
                }else{
                    $result = $invoice_model->update($data,array('invoice_id'=>$invoice_id));
                }


            }else{
                //新增
                if($invoice_default){
                    $invoice_id_array = $invoice_model->getlist('invoice_id',array('member_id'=>$member_id,'invoice_default'=>'1','invoice_type'=>'com'));
                    if(!empty($invoice_id_array)){
                        //$invoice_id_array[0]['invoice_id']
                        $invoice_model->update(array('invoice_default'=>'0'),array('invoice_id'=>$invoice_id_array[0]['invoice_id']));
                    }
                    $result = $invoice_model->insert($data);
                }else{
                    $result = $invoice_model->insert($data);
                }


            }

            echo json_encode(array('success'=>'保存成功'));

        }else{
            $company_name = trim($_POST['company_name']);
            $vat_number = trim($_POST['vat_number']);
            $vat_addr = trim($_POST['vat_addr']);
            $vat_tel = trim($_POST['vat_tel']);
            $vat_bank = trim($_POST['vat_bank']);
            $bank_number = trim($_POST['bank_number']);
            $invoice_default = $_POST['invoice_default'];
            if($company_name == ""){
                echo json_encode(array('error'=>'单位名称不能为空！'));
            }
            if($vat_number == ""){
                echo json_encode(array('error'=>'纳税人识别号不能为空！'));
            }
            if($vat_addr == ""){
                echo json_encode(array('error'=>'注册地址不能为空！'));
            }
            if($vat_tel == ""){
                echo json_encode(array('error'=>'注册电话不能为空！'));
            }
            if($vat_bank == ""){
                echo json_encode(array('error'=>'注册银行不能为空！'));
            }
            if($bank_number == ""){
                echo json_encode(array('error'=>'银行号码不能为空！'));
            }

            $invoice_model = app::get('b2c')->model('member_invoice');

            $data = array(
                'company_name' => $company_name,
                'member_id' => $member_id,
                'invoice_type' => 'vat',
                'vat_number' => $vat_number,
                'vat_addr' => $vat_addr,
                'vat_tel' => $vat_tel,
                'vat_bank' => $vat_bank,
                'bank_number' => $bank_number,
                'invoice_default' => $invoice_default,
                'last_modify' => time(),
            );

            //增值税发票保存
            if($invoice_id){
                //修改
                if($invoice_default){
                    $invoice_id_array = $invoice_model->getlist('invoice_id',array('member_id'=>$member_id,'invoice_default'=>'1','invoice_type'=>'vat'));
                    if(!empty($invoice_id_array)){
                        //$invoice_id_array[0]['invoice_id']
                        $invoice_model->update(array('invoice_default'=>'0'),array('invoice_id'=>$invoice_id_array[0]['invoice_id']));
                    }
                    $result = $invoice_model->update($data,array('invoice_id'=>$invoice_id));
                }else{
                    $result = $invoice_model->update($data,array('invoice_id'=>$invoice_id));
                }
            }else{
                //新增
                if($invoice_default){
                    $invoice_id_array = $invoice_model->getlist('invoice_id',array('member_id'=>$member_id,'invoice_default'=>'1','invoice_type'=>'vat'));
                    if(!empty($invoice_id_array)){
                        //$invoice_id_array[0]['invoice_id']
                        $invoice_model->update(array('invoice_default'=>'0'),array('invoice_id'=>$invoice_id_array[0]['invoice_id']));
                    }
                    $result = $invoice_model->insert($data);
                }else{
                    $result = $invoice_model->insert($data);
                }
            }
            echo json_encode(array('success'=>'保存成功'));
        }


    }

    //订单 保存发票信息
    function save_order_invoice(){
        $invoice_id = trim($_POST['invoice_id']);
        $addr_id = trim($_POST['addr_id']);
        $order_id = trim($_POST['order_id']);
        $java_payment_company = trim($_POST['java_payment_company']);
        $zhifu_name = trim($_POST['zhifu_name']);



        if($invoice_id == ""){
            echo json_encode(array('error'=>'请选择相应的发票信息！'));
            return false;
        }

        if($addr_id == ""){
            echo json_encode(array('error'=>'发票收货地址不能为空！'));
            return false;
        }

        if($order_id == ""){
            echo json_encode(array('error'=>'订单号为空！'));
            return false;
        }


        $invoice_model = app::get('b2c')->model('member_invoice');
        $addrs_model = app::get('b2c')->model('member_addrs');
        $order_invoice_model = app::get('b2c')->model('order_invoice');

        $invoice_data = $invoice_model->getlist('*',array('invoice_id'=>$invoice_id));
        $addrs_data = $addrs_model->getlist('*',array('addr_id'=>$addr_id));

        if(empty($invoice_data)){
            echo json_encode(array('error'=>'发票信息为空！'));
            return false;
        }

        if(empty($addrs_data)){
            echo json_encode(array('error'=>'地址信息为空！'));
            return false;
        }

       
        $order_id_tmp = $order_invoice_model->getlist('order_id',array('order_id'=>$order_id));
        if(!empty($order_id_tmp[0])){
            echo json_encode(array('error'=>'您已经申请过发票信息了！'));
            return false;
        }

        //处理地址
        $area_tmp = explode(":",$addrs_data[0]['area']);
        $data = array(
            'member_id'=>$this->qiyemember_id,
            'invoice_id'=>$invoice_id,
            'order_id' => $order_id,
            'invoice_type' => $invoice_data[0]['invoice_type'],
            'invoice_title' => $invoice_data[0]['invoice_title'],
            'company_name' => $invoice_data[0]['company_name'],
            'vat_number' => $invoice_data[0]['vat_number'],
            'vat_addr' => $invoice_data[0]['vat_addr'],
            'vat_tel' => $invoice_data[0]['vat_tel'],
            'vat_bank' => $invoice_data[0]['vat_bank'],
            'bank_number' => $invoice_data[0]['bank_number'],
            'name' => $addrs_data[0]['name'],
            'area' => $area_tmp[1],
            'addr' => $addrs_data[0]['addr'],
            'zip' => $addrs_data[0]['zip'],
            'tel' => $addrs_data[0]['tel'],
            'mobile' => $addrs_data[0]['mobile'],
            'last_modify' => time(),
            'application_date' => time(),
            'commercial_id' => $java_payment_company,
            'commercial_name' =>$zhifu_name,
        );

        $flag = $order_invoice_model->insert($data);
        if($flag) {
            echo json_encode(array('Success'=>''));
            return false;
        }else{
            echo json_encode(array('error'=>'发票申请失败，请稍后再试！'));
            return false;
        }

    }

    /*
        我的发票
        @params 无
        @return views
    */
    function myinvoice(){
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('我的发票'),'link'=>'#');
        //sdb_b2c_member_invoice
        $invoice_obj = app::get("b2c")->model("member_invoice");
        $data = $invoice_obj->getlist('*',array('member_id'=>$this->qiyemember_id));
        $this->pagedata['data'] = $data;
        
        $this->output();
    }


    /*
        我申请过的发票记录
    */
    function my_apple_invoice($type=''){
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('我申请的发票'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'myinvoice')));
        $order_invoice_model = app::get('b2c')->model('order_invoice');
        $type_orders_count=array ('all' =>0,'no' =>0,'yes' =>0);
        //所有
        $data_all = $order_invoice_model->getlist('id',array('member_id'=>$this->app->member_id));
        $type_orders_count['all']=count($data_all);
         //未处理
        $data_no = $order_invoice_model->getlist('id',array('member_id'=>$this->app->member_id,
            'is_send'=>'no'));
        $type_orders_count['no']=count($data_no);
         //已处理
        $data_no = $order_invoice_model->getlist('id',array('member_id'=>$this->app->member_id,
            'is_send'=>'yes'));
        $type_orders_count['yes']=count($data_no);
         //type
        $type_sql='';
        if($type == 'no'){
            $type_sql="is_send='no' ";
        }else if($type == 'yes'){
            $type_sql=" is_send='yes' ";
        }else{
            $type_sql=' 1=1 ';
        }
        $sdb = kernel::database()->prefix;
        $str_sql = "SELECT * FROM ".$sdb."b2c_order_invoice WHERE member_id=".$this->app->member_id;

        $str_sql.=" AND ".$type_sql;
        $arrayorser = $order_invoice_model->db->select($str_sql);
        //end
        if($type==''){
            $type_orders_count['all']=count($arrayorser);
        }else{
            $type_orders_count[$type]=count($arrayorser);
   
        }
        header('Content-Type:text/html; charset=utf-8');
        //获取传过来的参数
        $this->pagedata['type'] =$type;
        $this->pagedata['data'] = $arrayorser;
        $this->pagedata['type_orders_count']=$type_orders_count;
        
        $this->output();
    }



    /**
     * Generate the order detail
     * @params string order_id
     * @return null
     */
    public function orderdetail($order_id=0)
    {
        //过滤xss 和 sql注入
        $obj_filter = kernel::single('b2c_site_filter');
        $order_id = $obj_filter->check_input($order_id);

        if (!isset($order_id) || !$order_id)
        {
            $this->begin(array('app' => 'b2c','ctl' => 'site_member', 'act'=>'index'));
            $this->end(false, app::get('b2c')->_('订单编号不能为空！'));
        }

        $objOrder = &$this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))), 'order_pmt'=>array('*'));
        $sdf_order = $objOrder->dump($order_id, '*', $subsdf);
        $objMath = kernel::single("ectools_math");

        if(!$sdf_order||$this->app->member_id!=$sdf_order['member_id']){
            $this->_response->set_http_response_code(404);
            $this->_response->set_body(app::get('b2c')->_('订单号：') . $order_id . app::get('b2c')->_('不存在！'));
            return;
        }
        if($sdf_order['member_id']){
            $member = &$this->app->model('members');
            $aMember = $member->dump($sdf_order['member_id'], 'email');
            $sdf_order['receiver']['email'] = $aMember['contact']['email'];
        }

        // 处理收货人地区
        $arr_consignee_area = array();
        $arr_consignee_regions = array();
        if (strpos($sdf_order['consignee']['area'], ':') !== false)
        {
            $arr_consignee_area = explode(':', $sdf_order['consignee']['area']);
            if ($arr_consignee_area[1])
            {
                if (strpos($arr_consignee_area[1], '/') !== false)
                {
                    $arr_consignee_regions = explode('/', $arr_consignee_area[1]);
                }
            }

            $sdf_order['consignee']['area'] = (is_array($arr_consignee_regions) && $arr_consignee_regions) ? $arr_consignee_regions[0] . $arr_consignee_regions[1] . $arr_consignee_regions[2] : $sdf_order['consignee']['area'];
        }

        // 订单的相关信息的修改
        $obj_other_info = kernel::servicelist('b2c.order_other_infomation');
        if ($obj_other_info)
        {
            foreach ($obj_other_info as $obj)
            {
                $this->pagedata['discount_html'] = $obj->gen_point_discount($sdf_order);
            }
        }



        $this->pagedata['order'] = $sdf_order;

        $order_items = array();
        $gift_items = array();
        $this->get_order_detail_item($sdf_order,'member_order_detail');
        $this->pagedata['order'] = $sdf_order;
         //zxc显示具体支付账号7
        $this->pagedata['zhifu_name'] = $sdf_order['payinfo']['pay_app_id'];
        if('sfscpay' == $sdf_order['payinfo']['pay_app_id']){
            //TYPE: "I00102" 商社账户     I00103  部门账户    I00104 群组账户   I00105 个人账户
            $serviceNo = 'AccountService';
            $_sjson = array(
                'METHOD' => 'getAccountTypeByRelationId',
                'RELATION_ID' => $this->pagedata['order']['java_payment_company'],
            );
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            $isGroup = false;
            if('10001' == $sfscdata['RESULT_CODE']){
                if('I00102' == $sfscdata['RESULT_DATA']['TYPE']){
                    $serviceNo="SubAccountService";
                    //显示企业商社等级的选择情况
                    $_sjson = array(
                        'METHOD' => 'getCompanyToPayByCompanyNo',
                        'COMPANY_NO' => $this->pagedata['order']['java_payment_company'],
                    );
                    $isGroup = true;
                }else if('I00104' == $sfscdata['RESULT_DATA']['TYPE']){
                    $serviceNo="AccountService";
                    //显示群组商社等级的选择情况
                    $_sjson = array(
                        'METHOD' => 'getGroupAccountByGroupId',
                        'GROUP_ID' =>  $this->pagedata['order']['java_payment_company'],
                    );
                    $isGroup = true;
                }
            }
            if(! $isGroup){
                $serviceNo="SubAccountService";
                $_sjson = array(
                    'METHOD' => 'getCompanyDepGroupToPayByCompanyNo',
                    'RELATION_ID' => $this->pagedata['order']['java_payment_company'],
                );
            }
            $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
            if('10001' == $sfscdata['RESULT_CODE']){
                if(!empty($sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'])){
                    $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] .= '-';
                }
                if(!empty($sfscdata['RESULT_DATA'][0]['RELATION_NAME'])){
                    $sfscdata['RESULT_DATA']['zhifu_name'] = $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] . $sfscdata['RESULT_DATA'][0]['RELATION_NAME'];
                }else if(!empty($sfscdata['RESULT_DATA'][0]['GROUP_NAME'])){
                    $sfscdata['RESULT_DATA']['zhifu_name'] = $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] . $sfscdata['RESULT_DATA'][0]['GROUP_NAME'];
                }else if(!empty($sfscdata['RESULT_DATA'][0]['DEPT_NAME'])){
                    $sfscdata['RESULT_DATA']['zhifu_name'] = $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'] . $sfscdata['RESULT_DATA'][0]['DEPT_NAME'];
                }else{
                    $sfscdata['RESULT_DATA']['zhifu_name'] = $sfscdata['RESULT_DATA'][0]['CUSTOMER_NAME'];
                }
            }

            if(empty($sfscdata['RESULT_DATA']['zhifu_name'])){
                $sfscdata['RESULT_DATA']['zhifu_name'] = '个人帐户福点支付';
            }

            $this->pagedata['zhifu_name'] = $sfscdata['RESULT_DATA']['zhifu_name'];
        }

        /** 去掉商品优惠 **/
        if ($this->pagedata['order']['order_pmt'])
        {
            foreach ($this->pagedata['order']['order_pmt'] as $key=>$arr_pmt)
            {
                if ($arr_pmt['pmt_type'] == 'goods')
                {
                    unset($this->pagedata['order']['order_pmt'][$key]);
                }
            }
        }
        /** end **/

        // 得到订单留言.
        $oMsg = &kernel::single("b2c_message_order");
        $arrOrderMsg = $oMsg->getList('*', array('order_id' => $order_id, 'object_type' => 'order'), $offset=0, $limit=-1, 'time DESC');

        $this->pagedata['ordermsg'] = $arrOrderMsg;
        $this->pagedata['res_url'] = $this->app->res_url;

        // 生成订单日志明细
        //$oLogs =&$this->app->model('order_log');
        //$arr_order_logs = $oLogs->getList('*', array('rel_id' => $order_id));
        $arr_order_logs = $objOrder->getOrderLogList($order_id);

        // 取到支付单信息
        $obj_payments = app::get('ectools')->model('payments');
        $this->pagedata['paymentlists'] = $obj_payments->get_payments_by_order_id($order_id);

        // 支付方式的解析变化
        $obj_payments_cfgs = app::get('ectools')->model('payment_cfgs');
        $arr_payments_cfg = $obj_payments_cfgs->getPaymentInfo($this->pagedata['order']['payinfo']['pay_app_id']);

        //物流跟踪安装并且开启
        $logisticst = app::get('b2c')->getConf('system.order.tracking');
        $logisticst_service = kernel::service('b2c_change_orderloglist');
        if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
            $this->pagedata['services']['logisticstack'] = $logisticst_service;
        }

        $this->pagedata['orderlogs'] = $arr_order_logs['data'];
        // 添加html埋点
        foreach( kernel::servicelist('b2c.order_add_html') as $services ) {
            if ( is_object($services) ) {
                if ( method_exists($services, 'fetchHtml') ) {
                    $services->fetchHtml($this,$order_id,'site/invoice_detail.html');
                }
            }
        }

        //添加体检频道html嵌入页面
        $physical_flag = false;
        if($this->pagedata['order']['order_kind'] == 'card' || $this->pagedata['order']['order_kind'] == 'b2c_card'){
            $physical_orders_object = kernel::single("physical_mdl_orders");
            $physical_orders_data = $physical_orders_object->dump(array("order_id"=>$this->pagedata['order']['order_id']),"*");
            if(!empty($physical_orders_data)){
                $physical_flag = true;
                $physical_store_object = kernel::single("physical_mdl_store");
                $physical_store_data = $physical_store_object->dump(array('store_id'=>$physical_orders_data['store_id']),"*");
                $physical_package_object = kernel::single("physical_mdl_package");
                $physical_package_data = $physical_package_object->dump(array('package_id'=>$physical_orders_data['package_id']),"*");
                if($physical_orders_data['c_type'] == '1'){
                    $physical_orders_data['c_type'] = "身份证";
                }elseif($physical_orders_data['c_type'] == '2'){
                    $physical_orders_data['c_type'] = "军官证";
                }elseif($physical_orders_data['c_type'] == '3'){
                    $physical_orders_data['c_type'] = "团员证";
                }else{
                    $physical_orders_data['c_type'] = "身份证";
                }
                if($physical_orders_data['marry'] == "1"){
                    $physical_orders_data['marry'] = '是';
                }elseif($physical_orders_data['marry'] == "2"){
                    $physical_orders_data['marry'] = '否';
                }else{
                    $physical_orders_data['marry'] = '否';
                }
                if($physical_orders_data['sex'] == "1"){
                    $physical_orders_data['sex'] = '男';
                }elseif($physical_orders_data['sex'] == "2"){
                    $physical_orders_data['sex'] = '女';
                }else{
                    $physical_orders_data['sex'] = '男';
                }
                $physical_orders_data['package_info'] = $physical_package_data;
                $physical_orders_data['store_info'] = $physical_store_data;
                $this->pagedata['physical_data'] = $physical_orders_data;
                $this->pagedata['physical_flag'] = $physical_flag;
            }
        }
        
        $this->output();
    }



    public function return_policy($app='',$ctl='',$act='')
    {
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('申请售后服务'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $this->begin($this->gen_url(array('app' => $app, 'ctl' => $ctl, 'act' => $act)));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $this->pagedata['is_open_return_product'] = $arr_settings['is_open_return_product'];
        $this->pagedata['comment'] = $arr_settings['return_product_comment'];
        $this->pagedata['args'] = array($app, $ctl, $act);
        $this->output();
    }


    public function return_list($nPage=1)
    {
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'qiyecenter', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('售后服务列表'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $clos = "return_id,order_id,add_time,status,member_id,refund_type,is_intervene";
        $filter = array();

        $filter["member_id"] = $this->member['member_id'];
        if( $_POST["title"] != "" ){
            $filter["title"] = $_POST["title"];
        }

        if( $_POST["status"] != "" ){
            $filter["status"] = $_POST["status"];
        }

        if( $_POST["order_id"] != "" ){
            $filter["order_id"] = $_POST["order_id"];
        }

        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $aData = $obj_return_policy->get_return_product_list($clos, $filter, $nPage);

        $obj_account = app::get('pam')->model('account');
        //添加用户名
        foreach($aData['data'] as $key=>$val){
            $uname = $obj_account->getRow('login_name',array('account_id'=>$val['member_id']));
            $aData['data'][$key]['uname'] = $uname['login_name'];
        }
        if (isset($aData['data']) && $aData['data'])
            $this->pagedata['return_list'] = $aData['data'];

        $arrPager = $this->get_start($nPage, $aData['total']);
        $this->pagination($nPage, $arrPager['maxPage'], 'return_list', '', 'qiyecenter', 'site_member');

        $this->output();
    }


    public function return_order_list($nPage=1)
    {
        $this->path[] = array('title'=>app::get('b2c')->_('企业中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('新增退货申请'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $this->begin($this->gen_url(array('app' => 'aftersales', 'ctl' => 'site_member')));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $obj_orders = $this->app->model('orders');
        $clos = "order_id,createtime,final_amount,currency";
        $filter = array();
        if( $_POST['order_id'] )
        {
            $filter['order_id|has'] = $_POST['order_id'];
        }
        $filter['member_id'] = $this->member['member_id'];
        $filter['pay_status'] = 1;
        $filter['ship_status'] = 1;

        $aData = $obj_orders->getList($clos, $filter, ($nPage-1)*10, 10);
        if (isset($aData) && $aData)
            $this->pagedata['orders'] = $aData;
        $total = $obj_orders->count($filter);

        $arrPager = $this->get_start($nPage, $total);
        $this->pagination($nPage, $arrPager['maxPage'], 'return_order_list', '', 'qiyecenter', 'site_member');

        $this->output();
    }


    public function return_add_before($order_id){
        $obj_return = app::get('aftersales')->model('return_product');
        $returns = $obj_return->getRow('*',array('order_id'=>$order_id,'refund_type'=>'2','status'=>'1'));
        if($returns){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'return_details','arg0'=>$returns['return_id']));
        }else{
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'return_add','arg0'=>$order_id));
        }
    }



    public function return_add($order_id,$page=1)
    {
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $limit = 10;
        $objOrder = &$this->app_b2c->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');

        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
            $this->pagedata['order']['payed'] = $this->pagedata['gorefund_price'];
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';

                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;

                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        $count = count($order_items);
        $arrMaxPage = $this->get_start($page, $count);
        $this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'qiyecenter', 'site_member');
        $this->pagedata['url'] = $this->gen_url(array('app' => 'aftersales', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));
        $this->output();
    }
    public function reshipped(){

        //处理退货单
        $this->begin(array('app' =>'qiyecenter','ctl'=>'site_member','act' =>'orders'));
        $rs_sdf['order_id'] = $_POST['order_id'];
        //配送方式
        $rs_sdf['delivery'] = "1";
        $rs_sdf['reason'] = "质量原因";
        //物流公司
        $rs_sdf['logi_id'] = $_POST['logi_id'];
        //$rs_sdf['other_name'] = "";
        //运单号
        $rs_sdf['logi_no'] = $_POST['logi_no'];
        //配送费用
        $rs_sdf['money'] = $_POST['money'];
        //是否保价
        $rs_sdf['is_protect'] = 'false';
        //获取退货信息
        $obj_address = app::get('business')->model('dlyaddress');
        $address = $obj_address->getList('*',array('da_id'=>$_POST['refund_address']));
        $rs_sdf['ship_name'] = $address[0]['uname'];
        $rs_sdf['ship_tel'] = $address[0]['phone'];
        $rs_sdf['ship_mobile'] = $address[0]['mobile'];
        $rs_sdf['ship_zip'] = $address[0]['zip'];
        $rs_sdf['ship_area'] = $address[0]['region'];
        $rs_sdf['ship_addr'] = $address[0]['address'];
        $rs_sdf['memo'] = $_POST['content'];
        //获取处理人
        $o_account = app::get('pam')->model('account');
        $uname = $o_account->dump($this->member['member_id']);
        $rs_sdf['op_name'] = $uname['login_name'];
        $rs_sdf['op_id'] = $this->member['member_id'];
        $rs_sdf['opname'] = $uname['login_name'];
        //处理退货物品
        $obj_order_items = app::get('b2c')->model('order_items');
        $items = $obj_order_items->getList('item_id,bn',array('order_id'=>$_POST['order_id']));
        $rp = app::get('aftersales')->model('return_product');
        $returns = $rp->getRow('*',array('return_id'=>$_POST['return_id']));
        $item = unserialize($returns['product_data']);
        $send = array();
        foreach($item as $k1=>$v1){
            foreach($items as $k2=>$v2){
                if($v1['bn'] == $v2['bn']){
                    $send[$v2['item_id']] = $v1['num'];
                }
            }
        }
        $rs_sdf['send'] = $send;
        //处理上传图片
        if ( $_FILES['file']['size'] > 314572800 )
        {
            $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过300M"), $com_url);
        }
      
        if ( $_FILES['file']['name'] != "" )
        {
            $type=array("png","jpg","gif","jpeg","rar","zip");

            if(!in_array(strtolower($this->fileext($_FILES['file']['name'])), $type))
            {
                $text = implode(",", $type);
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id = $mdl_img->store($_FILES['file']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
        }
        $rs_sdf['image_file'] = $image_id;

        //添加两张维权图片
        if ( $_FILES['file1']['size'] > 5242880 )
        {
            $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过5M"), $com_url);
        }
       
        if ( $_FILES['file1']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file1']['name'])), $type))
            {
                $text = implode(",", $type);
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file1']['name'];
            $image_id1 = $mdl_img->store($_FILES['file1']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id1,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id1, $type);
        }
        $rs_sdf['image_file1'] = $image_id1;
          
        if ( $_FILES['file2']['size'] > 5242880 )
        {
            $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            $this->end(false, app::get('aftersales')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file2']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file2']['name'])), $type))
            {
                $text = implode(",", $type);
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                $this->end(false, app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file2']['name'];
            $image_id2 = $mdl_img->store($_FILES['file2']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id2,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id2, $type);
        }
        $rs_sdf['image_file2'] = $image_id2;
         
        //echo '<pre>';print_r($rs_sdf);exit;
        $reship = app::get('b2c')->model('reship');

        $rs_sdf['reship_id'] = $reship->gen_id();

        $b2c_order_reship = b2c_order_reship::getInstance(app::get('b2c'), $reship);
       
        if ($b2c_order_reship->generate($rs_sdf, $this, $message))
        {

            //ajx crm 
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $req_arr['order_id']=$rs_sdf['order_id'];
            $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
            //修改退款结束时间
            $close_time = time()+86400*(app::get('b2c')->getConf('member.to_agree'));
            $ref_data = array('close_time'=>$close_time,'status'=>12);
            $res = $rp->update($ref_data,array('return_id'=>$_POST['return_id']));
            
            //修改订单状态
            $obj_order = $this->app->model('orders');
            $refund_status = array('refund_status'=>'5');
            $rs = $obj_order->update($refund_status,array('order_id'=>$returns['order_id']));

            //添加退款日志
            if ($this->member['member_id'])
            {
                $obj_members = app::get('b2c')->model('members');
                $arrPams = $obj_members->dump($this->member['member_id'], '*', array(':account@pam' => array('*')));
            }

            $log_text = "买家退货";
            $result = "SUCCESS";
            $image_file = $rs_sdf['image_file'].','.$rs_sdf['image_file1'].','.$rs_sdf['image_file2'];
            $returnLog = app::get('aftersales')->model("return_log");
            $sdf_return_log = array(
                'order_id' => $returns['order_id'],
                'return_id' => $returns['return_id'],
                'op_id' => $this->member['member_id'],
                'op_name' => (!$this->member['member_id']) ? app::get('qiyecenter')->_('卖家') : $arrPams['pam_account']['login_name'],
                'alttime' => time(),
                'behavior' => 'reship',
                'result' => $result,
                'role' => 'member',
                'log_text' => $log_text,
                'image_file' => $image_file,
            );

            $log_id = $returnLog->save($sdf_return_log);

            $this->end(true, app::get('qiyecenter')->_('操作成功'));
        }

        else
        {
            $this->end(false,$message);
        }
    }
    private function fileext($filename)
    {
        return substr(strrchr($filename, '.'), 1);
    }

    public function return_save()
    {
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));

        if(! $_POST){
            $this->end(false, app::get('qiyecenter')->_("缺少必要的数据！"));
        }


        if($_POST['edit'] == 'edit'){
            $rp = app::get('aftersales')->model('return_product');
            $obj_order = app::get('b2c')->model('orders');
            $rp->update(array('status'=>'13'),array('return_id'=>$_POST['return_id']));
            $obj_order->update(array('refund_status'=>'2'),array('order_id'=>$_POST['order_id']));
        }


        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product'])
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $objOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $order_info = $objOrder->dump($_POST['order_id'], '*', $subsdf);

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        if($order_info['discount_value'] > 0){
            $gorefund_price = $order_info['payed']+($order_info['discount_value']);
        }else{
            $gorefund_price = $order_info['payed'];
        }

        if($_POST['amount']>$gorefund_price){
            $this->end(false, app::get('qiyecenter')->_("金额非法"));
        }
        //判断时候是售后
        if($order_info['status'] == 'finish'){
            if($_POST['gorefund_price']>$gorefund_price){
                $this->end(false, app::get('qiyecenter')->_("金额非法"));
            }
            if($_POST['amount']>$gorefund_price){
                $this->end(false, app::get('qiyecenter')->_("金额非法"));
            }
        }else{
            if (!$_POST['product_bn'])
            {
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您没有选择商品，请先选择商品！"), $com_url);
            }
        }
        $upload_file = "";
        if ( $_FILES['file']['size'] > 314572800 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过300M"), $com_url);
        }

        if ( $_FILES['file']['name'] != "" )
        {
            $type=array("png","jpg","gif","jpeg","rar","zip");

            if(!in_array(strtolower($this->fileext($_FILES['file']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id = $mdl_img->store($_FILES['file']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
        }
        //添加两张维权图片
        if ( $_FILES['file1']['size'] > 5242880 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file1']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file1']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file1']['name'];
            $image_id1 = $mdl_img->store($_FILES['file1']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id1,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id1, $type);
        }
        $aData['image_file1'] = $image_id1;

        if ( $_FILES['file2']['size'] > 5242880 )
        {
            if($_POST['type'] == '1'){
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
            }else{
                $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
            }
            $this->end(false, app::get('qiyecenter')->_("上传文件不能超过5M"), $com_url);
        }

        if ( $_FILES['file2']['name'] != "" )
        {
            $type=array("png","jpg","gif");

            if(!in_array(strtolower($this->fileext($_FILES['file2']['name'])), $type))
            {
                $text = implode(",", $type);
                if($_POST['type'] == '1'){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                }else{
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                }
                $this->end(false, app::get('qiyecenter')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url);
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file2']['name'];
            $image_id2 = $mdl_img->store($_FILES['file2']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id2,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id2, $type);
        }
        $aData['image_file2'] = $image_id2;


        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $product_data = array();
        //原系统判断金额
        $amount = 0;
        $obj_items = app::get('b2c')->model('order_items');
        if($order_info['status'] == 'active'){
            foreach ((array)$_POST['product_bn'] as $key => $val)
            {
                $price = $obj_items->getRow('price',array('order_id'=>$_POST['order_id'],'bn'=>$val));
                $amount = $amount + $price['price']*intval($_POST['product_nums'][$key]);
                if ($_POST['product_item_nums'][$key] < intval($_POST['product_nums'][$key]))
                {
                    if($_POST['type'] == '1'){
                        $com_url = $this->gen_url(array('app' => 'aftersales', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                    }else{
                        $com_url = $this->gen_url(array('app' => 'aftersales', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                    }
                    $this->end(false, app::get('qiyecenter')->_("申请售后商品的数量不能大于订购数量: "), $com_url);
                }

                $item = array();
                $item['bn'] = $val;
                $item['name'] = $_POST['product_name'][$key];
                $item['num'] = intval($_POST['product_nums'][$key]);
                $product_data[] = $item;
            }

            $re_num = 0;
            foreach($product_data as $key=>$val){
                $re_num = $re_num + $val['num'];
            }
        }
        //去除订单分摊优惠
        $obj_order = app::get('b2c')->model('orders');
        $pmt_order = $obj_order->getRow('pmt_order,itemnum,cost_freight,is_protect,cost_protect,payed,score_u,discount_value,status',array('order_id'=>$_POST['order_id']));
        //$amount = $amount - $pmt_order['pmt_order']*($re_num/$pmt_order['itemnum']);
        //end

        $point_money_value = app::get('b2c')->getConf('site.point_deductible_value');
        if($_POST['amount'] > $pmt_order['payed']){
            $amount = $pmt_order['payed'];
            if($point_money_value != 0){
                $return_score = floor(($_POST['amount']-$pmt_order['payed'])/$point_money_value);
            }
            $score_u = $pmt_order['score_u'] - $return_score;
        }else{
            $amount = $_POST['amount'];
        }

        $aData['image_file'] = $image_id;
        $store_id = $obj_order->getRow('store_id',array('order_id'=>$_POST['order_id']));
        $sto= kernel::single("business_memberstore",$this->member['member_id']);
        $aData['store_id'] = $store_id['store_id'];
        $aData['order_id'] = $_POST['order_id'];
        $aData['add_time'] = time();
        $aData['image_file'] = $image_id;
        //$aData['member_id'] = $this->member['member_id'];
        $aData['member_id'] = $_POST['member_id'];
        $aData['product_data'] = serialize($product_data);
        $aData['content'] = $_POST['content'];
        $aData['account_info'] = $_POST['account_info'];
        $aData['status'] = 1;
        $aData['amount'] = $amount;
        $aData['close_time'] = time()+86400*(app::get('b2c')->getConf('member.to_agree'));
        $aData['refund_type'] = '2';
        $aData['return_score'] = $return_score;
        $aData['comment'] = $_POST['comment'];
        //判断是否是售后申请
        if($pmt_order['status'] == 'finish'){
            $status_array = array('1' => '商品问题','2' => '七天无理由退换货','3' => '发票无效','4' => '退回多付的运费','5' => '未收到货');
            $aData['shipping_amount'] = $_POST['amount'];
            $aData['ship_cost'] = 0;
            $aData['amount_seller'] = 0;
            $aData['is_safeguard'] = '2';
            $aData['safeguard_type'] = $_POST['type'];
            $aData['comment'] = $status_array[$_POST['type']];
            if($_POST['type'] == '1' || $_POST['type'] == '2'){
                $aData['safeguard_require'] = $_POST['required_1'];
            }elseif($_POST['type'] == '3' || $_POST['type'] == '4'){
                $aData['safeguard_require'] = $_POST['required_2'];
            }else{
                $aData['safeguard_require'] = $_POST['required'];
            }
            if($aData['safeguard_require'] == '1' || $aData['safeguard_require'] == '5'){
                $aData['amount'] = $_POST['gorefund_price'];
                $aData['refund_type'] = '3';
            }elseif($aData['safeguard_require'] == '2' || $aData['safeguard_require'] == '3' || $aData['safeguard_require'] == '4'){
                $real_amount = 0;
                if(empty($_POST['product_bn'])){
                    $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'safeguard_add', 'arg0' => $_POST['order_id']));
                    $this->end(false, app::get('aftersales')->_("请选择退货商品"), $com_url);
                }
                foreach ((array)$_POST['product_bn'] as $key => $val)
                {
                    $price = $obj_items->getRow('price',array('order_id'=>$_POST['order_id'],'bn'=>$val));
                    $amount = $amount + $price['price']*intval($_POST['product_nums'][$key]);
                    if ($_POST['product_item_nums'][$key] < intval($_POST['product_nums'][$key]))
                    {
                        if($_POST['type'] == '1'){
                            $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'gorefund_mai', 'arg0' => $_POST['order_id']));
                        }else{
                            $com_url = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_add', 'arg0' => $_POST['order_id']));
                        }
                        $this->end(false, app::get('aftersales')->_("申请售后商品的数量不能大于订购数量: "), $com_url);
                    }

                    $item = array();
                    $item['bn'] = $val;
                    $item['name'] = $_POST['product_name'][$key];
                    $item['num'] = intval($_POST['product_nums'][$key]);
                    $item['refund'] = $_POST['products_refund'][$key];
                    $item['price'] = $price['price'];
                    $product_data_safeguard[] = $item;
                    $real_amount = $real_amount + $item['refund'];
                }
                $aData['product_data'] = serialize($product_data_safeguard);
                if($_POST['amount']>0){
                    $aData['amount'] = $real_amount+$_POST['amount'];
                }else{
                    $aData['amount'] = $real_amount;
                }
                $aData['refund_type'] = '2';

                if($aData['safeguard_require'] == '3' || $aData['safeguard_require'] == '4'){
                    $aData['amount'] = 0;
                }
            }elseif($aData['safeguard_require'] == '6'){
                $aData['amount'] = $_POST['gorefund_price'];
                $aData['refund_type'] = '3';
            }else{
                $aData['amount'] = 0;
            }

            if($aData['amount'] > $pmt_order['payed']){
                if($point_money_value != 0){
                    $return_score = floor(($aData['amount']-$pmt_order['payed'])/$point_money_value);
                }
                $aData['amount'] = $pmt_order['payed'];
                $score_u = $pmt_order['score_u'] - $return_score;
            }
            $aData['return_score'] = $return_score;

            //计算商家承担的金额
            if($aData['safeguard_type'] == '1' || $aData['safeguard_type'] == '2'){
                if($aData['safeguard_require'] == '1' || $aData['safeguard_require'] == '5' || $aData['safeguard_require'] == '6'){
                    $aData['seller_amount'] = $aData['amount'];
                }
                if($aData['safeguard_require'] == '2'){
                    $obj_cat = app::get('b2c')->model('goods_cat');
                    $obj_goods = app::get('b2c')->model('goods');
                    $obj_product = app::get('b2c')->model('products');
                    $seller_amount = 0;
                    //根据商品金额以及抽成比例算出商家出多少钱
                    foreach($product_data_safeguard as $key=>$val){
                        $good_id = $obj_product->dump(array('bn'=>$val['bn']),'goods_id');
                        $cat_id = $obj_goods->dump($good_id['goods_id'],'cat_id');
                        if(app::get('b2c')->getConf('member.isprofit') == 'true'){
                            $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                            if(is_null($profit_point['profit_point'])){
                                $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                                $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                            }
                        }else{
                            $profit_point['profit_point'] = 0;
                        }
                        $seller_amount = $seller_amount + $val['refund']*(1-$profit_point['profit_point']/100);
                    }
                    if($_POST['amount'] > 0){
                        $freight_pro = app::get('b2c')->getConf('member.profit');
                        $seller_amount = $seller_amount + ($_POST['amount'])*(1-$freight_pro/100);
                    }
                    $aData['seller_amount'] = $seller_amount;
                }
                if($aData['safeguard_require'] == '3' || $aData['safeguard_require'] == '4'){
                    $aData['seller_amount'] = 0;
                    $aData['shipping_amount'] = 0;
                }
            }elseif($aData['safeguard_type'] == '4'){
                //退邮费的情况
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $aData['seller_amount'] = ($aData['amount'])*(1-$freight_pro/100);
            }else{
                //其余情况 卖家承担全部
                $aData['seller_amount'] = $aData['amount'];
            }

            //判断是否超过卖家结算所获得的金额
            $mdl_order_bill = app::get('ectools')->model('order_bills');
            $model_refunds = app::get('ectools')->model('refunds');
            $blances = $mdl_order_bill->dump(array('rel_id'=>$_POST['order_id'],'bill_type'=>'blances'),'bill_id');
            $cur_money = $model_refunds->dump(array('refund_id'=>$blances['bill_id']),'cur_money');
            if($aData['seller_amount'] > $cur_money['cur_money']){
                $aData['seller_amount'] = $cur_money['cur_money'];
            }

            //判断平台承担是否超过平台抽成
            if(($aData['amount']-$aData['seller_amount']) > $cur_money['profit']){
                $aData['seller_amount'] = $aData['amount'] - $cur_money['profit'];
            }

            if($aData['amount']>$gorefund_price){
                $this->end(false, app::get('qiyecenter')->_("金额非法"));
            }

        }else{
            if($re_num == $pmt_order['itemnum']){
                //全部退款时退还卖家运费
                $return_money = ($pmt_order['payed']+($pmt_order['discount_value'])) - $_POST['amount'];
                //判断剩余金额是否大于邮费
                if($return_money > $pmt_order['cost_freight']){
                    $aData['ship_cost'] = $pmt_order['cost_freight'];
                }else{
                    $aData['ship_cost'] = $return_money;
                }
                //退款以后的多余款项记录
                $amount_seller = ($pmt_order['payed']+($pmt_order['discount_value'])) - $_POST['amount'] - $pmt_order['cost_freight'];
                $freight_pro = app::get('b2c')->getConf('member.profit');
                //退款金额判断
                if($amount_seller>0){
                    $aData['amount_seller'] = $amount_seller;
                    //是否保价
                    if($pmt_order['is_protect']){
                        $aData['ship_cost'] = $aData['ship_cost'] + $pmt_order['cost_protect'];
                        //邮费抽成
                        $aData['ship_cost'] = $aData['ship_cost'];
                        $aData['amount'] = $aData['amount'] - $pmt_order['cost_protect'];
                    }else{
                        $aData['ship_cost'] = $aData['ship_cost'];
                    }
                }else{
                    $aData['amount_seller'] = 0;
                    //是否保价
                    if($pmt_order['is_protect']){
                        $aData['ship_cost'] = $aData['ship_cost'] + $pmt_order['cost_protect'];
                        //邮费抽成
                        $aData['ship_cost'] = $aData['ship_cost'];
                        $aData['amount'] = $aData['amount'] - $pmt_order['cost_protect'];
                    }else{
                        $aData['ship_cost'] = $aData['ship_cost'];
                    }
                }

            }else{
                $aData['ship_cost'] = 0;
                $aData['amount_seller'] = 0;
            }
        }

        if($_POST['edit'] == 'edit'){
            $aData['old_return_id'] = $_POST['return_id'];
        }

        $msg = "";
        $obj_aftersales = kernel::service("api.aftersales.request");
        if ($obj_aftersales->generate($aData, $msg))
        {
            $obj_rpc_request_service = kernel::service('b2c.rpc.send.request');
            if ($obj_rpc_request_service && method_exists($obj_rpc_request_service, 'rpc_caller_request'))
            {
                if ($obj_rpc_request_service instanceof b2c_api_rpc_request_interface)
                    $obj_rpc_request_service->rpc_caller_request($aData,'aftersales');
            }
            else
            {
                $obj_aftersales->rpc_caller_request($aData);
            }
            //停止确认收货时间
            $confirm_time = $obj_order->getRow('confirm_time,status',array('order_id'=>$_POST['order_id']));
            if($confirm_time['confirm_time']){
                $time = $confirm_time['confirm_time'] - time();
            }else{
                $time = $confirm_time['confirm_time'];
            }
            //修改订单状态
            if($_POST['edit'] == 'edit' || $confirm_time['status'] == 'finish'){
                $refund_status = array('refund_status'=>'1');
            }else{
                $refund_status = array('refund_status'=>'1','confirm_time'=>$time);
            }
            $rs = $obj_order->update($refund_status,array('order_id'=>$_POST['order_id']));

            $this->end(true, app::get('b2c')->_('提交成功！'), $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));

        }
        else
        {
            $this->end(false, $msg, $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
        }
    }


    public function return_details($return_id)
    {
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product'])
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $this->pagedata['return_item'] =  $obj_return_policy->get_return_product_by_return_id($return_id);

        //添加退货地址显示
        $obj_address = app::get('business')->model('dlyaddress');
        $address = $obj_address->getList('*',array('da_id'=>$this->pagedata['return_item']['refund_address']));
        $ads = explode(':',$address['0']['region']);
        $address['0']['region'] = $ads[1];
        $this->pagedata['address'] = $address['0'];
        //添加确认收到退货按钮
        $sto= kernel::single("business_memberstore",$this->member['member_id']);
        $store_id = $sto->storeinfo['store_id'];

        //获取订单状态
        $obj_orders = app::get('b2c')->model('orders');
        $obj_return_p = app::get('aftersales')->model('return_product');
        $order_id = $obj_return_p->dump(array('return_id'=>$this->pagedata['return_item']['return_id']));
        $order_info = $obj_orders->dump(array('order_id'=>$order_id['order_id']));
        if($this->pagedata['return_item']['status'] == '已退货' && $this->pagedata['return_item']['refund_type'] == '2' && $order_info['refund_status'] == '5'){
            $this->pagedata['is_shop'] = true;

        }else{
            $this->pagedata['is_shop'] = false;
        }

        $this->pagedata['return_id'] = $return_id;
        if( !($this->pagedata['return_item']) )
        {
            $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_list')));
            $this->end(false, $this->app->_("售后服务申请单不存在！"));
        }

        $this->pagedata['now_time'] = time()*1000;
        $this->pagedata['time'] = $this->pagedata['return_item']['close_time']*1000;

        $this->pagedata['now_time_do_return'] = time()*1000;
        $this->pagedata['time_do_return'] = ($this->pagedata['return_item']['add_time']+(app::get('b2c')->getConf('member.to_agree'))*86400)*1000;

        //添加退款日志
        $obj_return_log = app::get('aftersales')->model('return_log');
        $log_info = $obj_return_log->getList('*',array('order_id'=>$order_id['order_id']),-1,-1,'alttime DESC');
        $this->pagedata['log_info'] = $log_info;

        $this->output();
    }
    //京东售后
    public function order_skus($order_id){
        $this->pagedata['order_id'] = $order_id;
        $skus = $this->getSkus($order_id);

        $result = $this->getJdOrderID($order_id);
        if ($result['success']){
            $jdOrderId = $result['result'];
            $this->pagedata['jdOrderId'] = $jdOrderId;
        }else{
            $this->pagedata['availableMsg'] = $result['resultMessage'];
            $this->output('qiyecenter');
            return ;
        }
        //需判断是否有拆单
        $mdl_jd_suborders = $this->app_current->model('jd_suborders');
        $sub_filter = array('jd_order_id'=>$jdOrderId);
        $c = $mdl_jd_suborders->count($sub_filter);
        if ($c>0){
            $has_sub_order = true;
            $this->pagedata['hasSubOrder']=1;
        }else{
            $has_sub_order = false;
            $this->pagedata['hasSubOrder']=0;
        }

        if($has_sub_order){
            $this->pagedata['hasService'] = 0;
            foreach ($skus as $k => $v){

                $jd_suborder_info = $mdl_jd_suborders->getRow('*',array_merge($sub_filter,array('sku_id'=>$v['bn'])));
                $jd_suborder_id = $jd_suborder_info['jd_suborder_id'];

                $available = $this->afterSaleAvailable($jd_suborder_id,$v['bn']);
                $skus[$k]['jd_suborder_id'] = $jd_suborder_id;
                if ($available === true){
                    $skus[$k]['afterSales'] = 1;

                }else{
                    $skus[$k]['afterSales'] = 0;
                    $this->pagedata['availableMsg'] = $available;
                }
                if($this->hasafsService($jd_suborder_id)){
                    $this->pagedata['hasService'] = 1;
                }
            }

        }else{
            $this->pagedata['hasService'] = $this->hasafsService($jdOrderId);
            foreach ($skus as $k => $v){
                $available = $this->afterSaleAvailable($jdOrderId,$v['bn']);
                if ($available === true){
                    $skus[$k]['afterSales'] = 1;

                }else{
                    $skus[$k]['afterSales'] = 0;
                    $this->pagedata['availableMsg'] = $available;
                }
            }
        }

        $this->pagedata['skus'] = $skus;
        $this->output('qiyecenter');

    }
    private function getSkus($order_id){
        $mdl_order_items = app::get('b2c')->model('order_items');
        $filter = array('order_id'=>$order_id);
        $skus = $mdl_order_items->getList('goods_id,bn,name,nums',$filter);
        return $skus;
    }

    private function getOrderInfo($order_id){
        $mdl_orders = app::get('b2c')->model('orders');
        $filter = array('order_id'=>$order_id);
        $order = $mdl_orders->getRow('*',$filter);
        return $order;
    }
    private function getJdOrderID($order_id){

        //lpc 获取售后类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('order_id'=>$order_id),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_orders = kernel::single('jdsale_api_orders');
        $result = $jdsale_api_orders -> getOrderJdOrderIDByThridOrderID(array('thirdOrder'=>$order_id),$jdgoodsKind);
        return $result;
    }
    //京东售后列表
     public function service_list($jdOrderId,$order_id,$hasSubOrder,$nPage=1){
        $mdl_jd_suborders = $this->app_current->model('jd_suborders');
        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $totalNum = 0;
        // lpc 增加jd_suborders表查询字段order_kind来判断是什么（book？）
        $jdgoodsKind = "normal";
        if($hasSubOrder === '1'){
            $jd_sub_order = $mdl_jd_suborders->db->select(
                "SELECT DISTINCT jd_suborder_id,order_kind FROM sdb_jdsale_jd_suborders WHERE jd_order_id ='".$jdOrderId."'");
            $sub_afsServiceList = array();
            foreach($jd_sub_order as $k=>$v){
                //lpc 
                if ($v['order_kind'] == "jdbook")
                    $jdgoodsKind = "book";

                $afsServiceList = $jdsale_api_aftersales -> afterSaleServiceList(
                    array('param'=> array('jdOrderId'=>$v['jd_suborder_id'],'pageIndex'=>$nPage,'pageSize' =>$this->pagesize)),$jdgoodsKind);
                if ($afsServiceList['result']){
                    $totalNum += $afsServiceList['result']['totalNum'];
                    $sub_afsServiceList = array_merge($afsServiceList['result']['serviceInfoList'],$sub_afsServiceList);
                }
            }
            $this->pagedata['afsServiceList'] = $sub_afsServiceList;

        }else{

            //lpc 获取类型（goods、book）
            $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderId),'order_kind');
            if ($jdGoods['order_kind'] == "jdbook")
                $jdgoodsKind = "book";

            $afsServiceList = $jdsale_api_aftersales -> afterSaleServiceList(
                array('param'=> array('jdOrderId'=>$jdOrderId,'pageIndex'=>$nPage,'pageSize' =>$this->pagesize)),$jdgoodsKind);
            $totalNum = $afsServiceList['result']['totalNum'];

            $this->pagedata['afsServiceList'] = $afsServiceList['result']['serviceInfoList'];
        }
        $this->pagedata['hasSubOrder'] = $hasSubOrder;
        $this->pagedata['jdOrderId'] = $jdOrderId;
        $this->pagedata['order_id'] = $order_id;

        $arr_args = array($jdOrderId,$order_id,$hasSubOrder);
        $arrMaxPage = $this->get_start($nPage, $totalNum);
        $this->pagination($nPage, $arrMaxPage['maxPage'], 'service_list', $arr_args, 'jdsale', 'site_aftersales');
        $this->output('qiyecenter');
    }
    //京东售后列表的操作
     public function cancel_apply($afsServiceId,$jdOrderID,$order_id){
        $this->begin(array('app' => 'qiyecenter','ctl' => 'site_member', 'act'=>'service_list' ,
                           'arg0'=>$jdOrderID ,'arg1'=>$order_id ));

        //lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderID),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result  = $jdsale_api_aftersales -> afterSaleAuditCancel(
            array('param'=> array('serviceIdList'=>array($afsServiceId),'approveNotes'=> 'cancel')),$jdgoodsKind);
        if ($result['result']){
            $this->end(true, app::get('qiyecenter')->_('该服务单取消操作成功！'));

        }else{
            $this->end(false, app::get('qiyecenter')->_('该服务单取消操作失败！'));
        }
    }
    //京东售后的详情页面
    public function service_detail($afsServiceId,$order_id,$jd_order_id,$hasSubOrder){

        //lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jd_order_id),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $afsServiceDetail = $jdsale_api_aftersales -> afterSaleServiceDetail(
            array('param'=> array('afsServiceId'=>$afsServiceId,
                                  'appendInfoSteps'=> array())),$jdgoodsKind);
        $afsServiceDetail['result']['customerExpect']=$this->getCustomerExpectName($afsServiceDetail['result']['customerExpect']);
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['jdOrderId'] = $jd_order_id;
        $this->pagedata['afsServiceDetail']= $afsServiceDetail['result'];
        $this->pagedata['hasSubOrder'] = $hasSubOrder;
        $this->output('qiyecenter');
    }
     //申请维权方法
    function safeguard($order_id){
        $this->pagedata['order_id'] = $order_id;
        $this->output('qiyecenter');
    }
   public function swith_safeguard(){
        if($_POST['is_required'] == '1'){
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'safeguard_add','arg0'=>$_POST['order_id']));
        }else{
            $this->redirect(array('app'=>'qiyecenter', ctl=>'site_member','act'=>'add_safeguard','arg0'=>$_POST['order_id']));
        }
    }
      public function safeguard_add($order_id,$page=1)
    {
        $this->begin($this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member')));
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务应用不存在！"));
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有取到！"));
        }

        if(!$arr_settings['is_open_return_product']){
            $this->end(false, app::get('qiyecenter')->_("售后服务信息没有开启！"));
        }

        $limit = 10;
        $objOrder = &$this->app_b2c->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        
        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
            $this->pagedata['order']['payed'] = $this->pagedata['gorefund_price'];
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id, $page, $limit);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';
                    //zxc_jdgoods
                    // if ($item['item_type'] == 'jdgoods')
                    //     $item['item_type'] = 'goods';
                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);
        $count = count($order_items);
        $arrMaxPage = $this->get_start($page, $count);
        $this->pagination($page, $arrMaxPage['maxPage'], 'return_add', array($order_id), 'aftersales', 'site_member');
        $this->pagedata['url'] = $this->gen_url(array('app' => 'qiyecenter', 'ctl' => 'site_member', 'act' => 'return_order_items', 'arg' => array($order_id)));

        //售后添加 售后服务类型
        $this->pagedata['type'] = array(array('id'=>'1','name'=>'商品问题')/*,array('id'=>'2','name'=>'七天无理由退换货'),array('id'=>'3','name'=>'发票无效')*/,array('id'=>'4','name'=>'退回多付的运费'));

        //售后添加 售后要求
        $this->pagedata['require_1'] = array(array('id'=>'1','name'=>'不退货部分退款'),array('id'=>'2','name'=>'需要退货退款'),array('id'=>'3','name'=>'要求换货'),array('id'=>'4','name'=>'要求维修'),array('id'=>'5','name'=>'已经退货，要求退款'));

        $this->pagedata['require_2'] = array(array('id'=>'1','name'=>'不退货部分退款'));

        $this->output('qiyecenter');
    }
     function add_safeguard($order_id){
        $this->begin($this->gen_url(array('app' => 'b2c', 'ctl' => 'site_member')));
        $objOrder = &$this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if(!$this->pagedata['order'])
        {
            $this->end(false,  app::get('b2c')->_('订单无效！'), array('app'=>'site','ctl'=>'default','act'=>'index'));
        }
        
        $point_money_value = app::get('b2c')->getConf('site.point_money_value');

        if($this->pagedata['order']['discount_value'] > 0){
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed']+($this->pagedata['order']['discount_value']);
        }else{
            $this->pagedata['gorefund_price'] = $this->pagedata['order']['payed'];
        }
        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';
                    
                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        
                        $product_id = $tmp_array['products']['product_id'];
                        if (!$order_items[$product_id]){
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity'])));
                        }
                        //$order_items[$item['products']['product_id']] = $tmp_array;
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{
                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }
        //金额格式化-hy-2016年1月22日
        $mdl_currency = kernel::single('ectools_mdl_currency');
        $decimals = app::get('b2c')->getConf('system.money.decimals');
        $carryset = app::get('b2c')->getConf('system.money.operation.carryset');
        foreach($order_items as $k=>$v){
            $price = $mdl_currency->changer_odr($v['price'], $_COOKIE["S"]["CUR"], true, false, $decimals,$carryset);
            $order_items[$k]['price'] = $price;
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = array_slice($order_items,($page-1)*$limit,$limit);

        //售后添加 售后服务类型
        $this->pagedata['type'] = array(array('id'=>'5','name'=>'未收到货'));

        //售后添加 售后要求
        $this->pagedata['require'] = array(array('id'=>'6','name'=>'要求退款'));
        
        $this->output('qiyecenter');
    }
 
    //添加收货地址
    function insert_rec(){
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'receiver'));
        $obj_member = app::get('b2c')->model('members');
        if(!$obj_member->isAllowAddr($this->app->member_id)){
            $this->splash('failed','',app::get('b2c')->_('不能新增收货地址'),'','',true);
        }
        $aData = $this->check_input($_POST);
        if($obj_member->insertRec($aData,$this->app->member_id,$message)){
            $this->splash('success',$url,$message,'','',true);
        }
        else{
            $this->splash('failed','',$message,'','',true);
        }

    }


    //编辑收货地址
    function add_receiver(){
        $obj_member = app::get('b2c')->model('members');
        if($obj_member->isAllowAddr($this->app->member_id)){
            $this->output();
        }else{
            echo app::get('b2c')->_('不能新增收货地址');
        }
    }



    //设置和取消默认地址，$disabled 2为设置默认1为取消默认
    function set_default($addrId=null,$disabled=1){
        $addrId = $_POST['addrId'];
        $disabled = $_POST['disabled'];
        if(!$addrId) $this->splash('failed', 'back', app::get('qiyecenter')->_('参数错误'));
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'receiver'));
        $obj_member = app::get('b2c')->model('members');
        $member_id = $this->app->member_id;
        if($obj_member->check_addr($addrId,$this->member['member_id']))
        {
            if($obj_member->set_to_def($addrId,$member_id,$message,$disabled))
            {
                $this->splash('success',$url,$message,'','',true);
            }
            else
            {
                $this->splash('failed',$url,$message,'','',true);
            }
        }
        else
        {
            $this->splash('failed', 'back', app::get('b2c')->_('参数错误'),'','',true);
        }
    }


    //修改收货地址
    function modify_receiver($addrId=null){
        if(!$addrId)
        {
            echo  app::get('qiyecenter')->_("参数错误");exit;
        }
        $obj_member = app::get('b2c')->model('members');
        if($obj_member->check_addr($addrId,$this->member['member_id']))
        {
            if($aRet = $obj_member->getAddrById($addrId))
            {
                $aRet['defOpt'] = array('0'=>app::get('qiyecenter')->_('否'), '1'=>app::get('qiyecenter')->_('是'));
                $this->pagedata = $aRet;
            }else
            {
                $this->_response->set_http_response_code(404);
                $this->_response->set_body(app::get('qiyecenter')->_('修改的收货地址不存在！'));
                exit;
            }
            $this->output();
        }
        else
        {
            echo  app::get('qiyecenter')->_("参数错误");exit;
        }
    }


    //保存地址
    function save_rec(){
        $back_url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'receiver'));
        $obj_member = app::get('b2c')->model('members');
        if($obj_member->check_addr($_POST['addr_id'],$this->member['member_id']))
        {
            $aData = $this->check_input($_POST);
            if($obj_member->save_rec($aData,$this->app->member_id,$message)){
                $this->splash('success',$back_url,app::get('qiyecenter')->_('修改成功'),'','',true);
            }
            else{
                $this->splash('failed',$back_url,$message,'','',true);
            }
        }
        else
        {
            $this->splash('failed',$back_url,app::get('qiyecenter')->_('操作失败'),'','',true);
        }
    }


    //删除收货地址
    function del_rec($addrId=null){
        $addrId = $_POST['addrId'];
        if(!$addrId) $this->splash('failed', 'back', app::get('qiyecenter')->_('参数错误'));
        $url = $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'receiver'));
        $obj_member = app::get('b2c')->model('members');
        if($obj_member->check_addr($addrId,$this->member['member_id']))
        {
            if($obj_member->del_rec($addrId,$message,$this->member['member_id']))
            {
                $this->splash('success',$url,$message,'','',true);
            }
            else
            {
                $this->splash('failed',$url,$message,'','',true);
            }
        }
        else
        {
            $this->splash('failed', 'back', app::get('qiyecenter')->_('操作失败'),'','',true);
        }
    }


    //企业管理
    function qiyeguanli_list($tag=""){
        $query_time = $_POST['query_time'] ?$_POST['query_time']:"";
        $company_no = $_REQUEST['company_no'];
        if($query_time == ''){
            $_sjson = array(
                //getCompanyBalanceInfoByRelationId
                'METHOD'=>'getCompanyBalanceInfoByRelationId',
                'RELATION_ID'=>$company_no,
                'QUERY_TIME'=>""
            );
            $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);

            if($tmpdata != null && gettype($tmpdata) == "object"){
                $tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
            }else{
                $tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
            }
            if($tag != ""){
                $this->pagedata['shifttab'] = true;
            }
            $this->pagedata['company_no'] = $company_no;
            $this->pagedata['sfsc_member_info'] = $tmp22;
            $this->output();
        }else{
            if($query_time == "qb004"){
                $query_time = '';
            }
            $_sjson = array(
                'METHOD'=>'getCompanyBalanceInfoByRelationId',
                'RELATION_ID'=>$company_no,
                'QUERY_TIME'=>$query_time
            );
            $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            if($tmpdata != null && gettype($tmpdata) == "object"){
                $tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
            }else{
                $tmp22= Array('RESULT_CODE'=>0,'RESULT_DATA'=>Array('INCOME'=>'0','SUM'=>'0','EXPENSES'=>'0'));
            }
            echo json_encode($tmp22);
            die();
        }
    }


    //企业流水信息start
    public function get_liushuizhanginfo(){
        $render = new base_render(app::get('qiyecenter'));
        $type=trim($_POST['type']);
        $company_no = $_REQUEST['company_no'];
        if($type == 'all'){
            $_sjson = array(
                'METHOD'=>'getsubAccountListByRelationId',
                'RELATION_ID'=>$company_no
            );
            $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            if($tmpdata != null && gettype($tmpdata) == "object"){
                $getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
            }else{
                $msg = "没有相关信息！";
            }
        }else{
            $page = intval(trim($_POST['page'])) ? intval(trim($_POST['page'])) : 1;
            $num = 8;
            $_sjson = array(
                'METHOD'=>'GetTransactionflowByRelationId',
                'RELATION_ID'=>$company_no,
                'PAGE_NO'=>$page,
                'PAGE_VOL'=>$num
            );
            $post_data = array('serviceNo'=>'TransactionflowService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            if($tmpdata != null && gettype($tmpdata) == "object"){
                $getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
            }else{
                $msg = "没有相关信息！";
            }

            foreach($getSubActList['RESULT_DATA'] as $k=>$v){
                if($v['TRANSACTION_TYPE'] == "I00602"){
                    $obj_return_policy = kernel::service("aftersales.return_policy");
                    $aData = $obj_return_policy->get_return_product_list('return_id',array('order_id'=>$v['ORDER_ID']));
                    if($aData){
                        $getSubActList['RESULT_DATA'][$k]['return_id'] = $aData['data'][0]['return_id'];
                    }
                }
            }

            //分页start

            $page_nums = ceil($getSubActList['RESULT_DATA'][count($getSubActList['RESULT_DATA'])-1]['count']/$num);
            array_pop($getSubActList['RESULT_DATA']);
            //$offset = ($page - 1) * $num;

            /*

            foreach($getSubActList_tmp['RESULT_DATA'] as $k=>$v){
                 if(!($offset > $k || ($offset+$num-1) < $k)){
                     $getSubActList['RESULT_DATA'][] = $v;
                 }
            }
              */
            if($page_nums > 1){
                $page_array[] = "<a href='javascript:void(0);' class='prev' onclick='java_page(this)' title='".(($page-1)?($page-1):1)."'>上一页</a>";
                $step = 4;
                for($i=$step;$i>0;$i--){
                    $page_tmp = $page-$i;
                    if($page_tmp > 0){
                        $page_array[] =  "<a href='javascript:void(0);' class='pagernum' onclick='java_page(this)' title='".$page_tmp."'>".$page_tmp."</a>";
                    }
                }
                $page_array[] = "<a href='javascript:void(0);' class='pagecurrent' onclick='java_page(this)' title='".$page."'>".$page."</a>";
                for($i=1;$i<=$step;$i++){
                    $page_tmp = $page+$i;
                    if($page_tmp <= $page_nums){
                        $page_array[] =  "<a href='javascript:void(0);' class='pagernum' onclick='java_page(this)' title='".$page_tmp."'>".$page_tmp."</a>";
                    }
                }
                $page_array[] = "<a href='javascript:void(0);' class='next last' onclick='java_page(this)' title='".(($page+1)<$page_nums ?($page+1):$page_nums)."'>下一页</a>";
            }


        }
        $this->pagedata['page_array'] = $page_array ? $page_array : '';
        $this->pagedata['getSubActListtype'] = $type;
        $this->pagedata['getSubActList'] = $getSubActList['RESULT_DATA'];
        $this->pagedata['msg']=$msg;
        echo $render->fetch('site/member/liushuizhanginfo_member.html');
    }

    //解密
    public function deCardPass()
    {
        $card_pass=$_POST['card_pass'];
        // $cpMdl = app::get('cardcoupons')->model('cards_pass');

        $re = kernel::single('cardcoupons_mysqlkey')->dePwByKey($card_pass);
        if (!empty($re)){
            $data['status'] = 'succ';
            $data['result'] = $re;
        }
            else{
            $data['status'] = 'false';
            $data['result'] = '';
        }
        echo json_encode($data);
    }


}
