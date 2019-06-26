<?php
class business_ctl_site_promotion extends business_ctl_site_member{
	public function __construct(&$app) {
        parent :: __construct($app);
        $this->cur_view = 'promotion';

        $obj_members = &app :: get('b2c') -> model('members');
        $this -> member = $obj_members -> get_current_member();

        $shopname = app :: get('b2c') -> getConf('system.shopname');
        $this -> header .= '<meta name="robots" content="noindex,noarchive,nofollow" />';
        $this -> _response -> set_header('Cache-Control', 'no-store');
        $this -> title = $this -> app -> _('我是卖家'). '_' . $shopname;
        $this -> keywords = app :: get('business') -> _('我是卖家') . '_' . $shopname;
        $this -> description = app :: get('business') -> _('我是卖家') . '_' . $shopname;

        $this->sto= kernel::single("business_memberstore",$this -> member['member_id']);
    } 
	public function order(){
		$storeid = ','. $this->sto->storeinfo['store_id'].','; 
        $store_id= $this->sto->storeinfo['store_id'];
		$rule_sql="select * from sdb_b2c_sales_rule_order where find_in_set('".$store_id."', store_id) and rule_type='N'";
		$rules=kernel::database()->select($rule_sql);
		$this->pagedata['rules']=$rules;
		$this -> pagedata['_PAGE_'] = 'sales_rule_list.html';
		
		$this->pagedata['rule']['sort_order'] = 50;
		//排斥状态显示优先级项  默认加载 addtime:14:09 2010-8-19
        $time = time();
        $storeid = ','. $this->sto->storeinfo['store_id'].','; 
        $filter = array('from_time|sthan'=>$time, 'to_time|bthan'=>$time,'store_id'=>$storeid, 'status'=>'true', 'rule_type'=>'N');
        if( $rule_id ) $filter['rule_id|noequal'] = $rule_id;
        $arr = app :: get('b2c')->model('sales_rule_order')->getList( 
                                                        'name,sort_order', 
                                                        $filter,
                                                        0,-1,'sort_order ASC'
                                                    );
        $this->pagedata['sales_list'] = $arr;
        $arr = null;
        //end  
        
        
        $this->pagedata['promotion_type'] = 'order'; // 规则类型 用于公用模板
        $this->pagedata['storeid'] = $storeid; // 规则类型 用于公用模板

        ////////////////////////////  模块  ////////////////////////////////
        $this->pagedata['sections'] = $this->_sections();

        //////////////////////////// 会员等级 //////////////////////////////
        $mMemberLevel = &app :: get('b2c')->model('member_lv');
        $this->pagedata['member_level'] = $mMemberLevel->getList('member_lv_id,name', array(), 0, -1, 'member_lv_id ASC');

        //////////////////////////// 过滤条件模板 //////////////////////////////
        $oSOP = kernel::single('b2c_sales_order_process');
		$pt_list=$oSOP->getTemplateList();
		$pt_list_key=array (
						'b2c_promotion_conditions_order_subtotalselectgoods' => 
							array (
							  'name' => '当订单商品总价满X,对指定的商品优惠',
							  'type' => NULL,
							),
						'proqgoods_conditions_goods_goodsofquantity' => 
							array (
							  'name' => '指定商品购买量满X,自定义优惠',
							  'type' => NULL,
							),
						'b2c_promotion_conditions_order_itemsquanityallgoods' => 
							array (
							  'name' => '当订单商品数量满X,给予优惠',
							  'type' => 'order',
							),
						'b2c_promotion_conditions_order_allorderallgoods' => 
							array (
							  'name' => '对所有订单给予优惠',
							  'type' => 'order',
							),
					);
		foreach($pt_list_key as $plkey=>$plvalue){
			if($pt_list[$plkey]) unset($pt_list[$plkey]);
		}
        $this->pagedata['pt_list'] = $pt_list;

        //////////////////////////// 优惠方案模板 //////////////////////////////
        $oSSP = kernel::single('b2c_sales_solution_process');
		$stpl_list= $oSSP->getTemplateList();
		unset($stpl_list['goods']);
		//去掉非订单免邮的规则
		$stpl_list_key=array(
			  'b2c_promotion_solutions_topercent' => '订单以固定折扣出售',
			  'b2c_promotion_solutions_tofixed' => '订单固定价格购买',
			  'b2c_promotion_solutions_bypercent' => '订单减去固定折扣出售',
			  'b2c_promotion_solutions_byfixed' => '订单减固定价格购买',
			  'progetcoupon_promotion_solutions_getcoupon' => '订单送优惠券'
		);
		foreach($stpl_list_key as $slkey=>$slvalue){
			if($stpl_list['order'][$slkey]) unset($stpl_list['order'][$slkey]);
		}
        $this->pagedata['stpl_list'] =$stpl_list;
		$this->output();
	}
    /**
     * 修改规则
     *
     * @param int $rule_id
     */
    public function editOrder($rule_id) {
		if(!$rule_id) $rule_id=$_POST['rule_id'];
		$storeid = ','. $this->sto->storeinfo['store_id'].','; 
		$this->pagedata['storeid'] = $storeid;
        $store_id= $this->sto->storeinfo['store_id'];
        $mOrderPromotion = app :: get('b2c')->model("sales_rule_order");
        $aRule = $mOrderPromotion->dump($rule_id,'*','default');
		if(!$aRule||$storeid!=$aRule['store_id']){
			echo app::get('business')->_('只能编辑本店铺优惠规则！');exit;
		}
        ///////////////////////////// 规则信息 ////////////////////////////
        $aRule['member_lv_ids'] = empty($aRule['member_lv_ids'])? null :explode(',',$aRule['member_lv_ids']);
        $aRule['conditions'] = empty($aRule['conditions'])? null : ($aRule['conditions']);
        $aRule['action_conditions'] = empty($aRule['conditions'])? null : ($aRule['action_conditions']);
        $aRule['action_solutions'] = empty($aRule['action_solutions'])? null : ($aRule['action_solutions']);
        $this->pagedata['rule'] = $aRule;

        ///////////////////////////// 过滤条件 ////////////////////////////
        $oSOP = kernel::single('b2c_sales_order_process');
        $aHtml = $oSOP->getTemplate($aRule['c_template'],$aRule);
        $this->_block($aHtml);

        ///////////////////////////// 优惠方案 ////////////////////////////
        $aRule['action_solution'] = empty($aRule['action_solution'])? null : ($aRule['action_solution']);
        $oSSP = kernel::single('b2c_sales_solution_process');
        $this->pagedata['solution_type'] = $oSSP->getType($aRule['action_solution'], $aRule['s_template']);
        $html = $oSSP->getTemplate($aRule['s_template'],$aRule['action_solution'], $this->pagedata['solution_type']);
        $this->pagedata['action_solution_name'] = $aRule['s_template'];
        $this->pagedata['action_solution'] = $html;

        $this->_editor( $rule_id );
    }
	/**
	*
	*删除订单优惠规则
	*
	**/
	public function deleteOrder($rule_id){
		$rule_id = $_POST['rule_id'];
        if(!$rule_id){
          echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app :: get('b2c') -> _('参数错误。') . '</span>'));
          return;
        }

        $obj_recycle = kernel::single('desktop_system_recycle');
        $filter = array(
            'rule_id'=>$rule_id,
        );
        if (!$obj_recycle->dorecycle('b2c_mdl_sales_rule_order', $filter))
        {
            echo json_encode(array('status' => 'false', 'message' => '<span class="font-red">&nbsp;' . app :: get('b2c') -> _('订单促销规则删除失败。') . '</span>'));

        }
        else
        {
            echo json_encode(array('status' => 'success', 'message' => '<span class="font-green">&nbsp;' . app :: get('b2c') -> _('订单促销规则删除成功。') . '</span>')); 

        }
	}

    /**
     * edit 公共部分
     *
     */
    public function _editor( $rule_id=0 ) {
        //排斥状态显示优先级项  默认加载 addtime:14:09 2010-8-19
		
        $time = time();
		$storeid = ','. $this->sto->storeinfo['store_id'].','; 
        $filter = array('from_time|sthan'=>$time, 'to_time|bthan'=>$time,'store_id'=>$storeid, 'status'=>'true', 'rule_type'=>'N');
        if( $rule_id ) $filter['rule_id|noequal'] = $rule_id;
        $arr = app :: get('b2c')->model('sales_rule_order')->getList( 
                                                        'name,sort_order', 
                                                        $filter,
                                                        0,-1,'sort_order ASC'
                                                    );
        $this->pagedata['sales_list'] = $arr;
        $arr = null;
        //end  
        
        
        $this->pagedata['promotion_type'] = 'order'; // 规则类型 用于公用模板

        ////////////////////////////  模块  ////////////////////////////////
        $this->pagedata['sections'] = $this->_sections();

        //////////////////////////// 会员等级 //////////////////////////////
        $mMemberLevel = &app :: get('b2c')->model('member_lv');
        $this->pagedata['member_level'] = $mMemberLevel->getList('member_lv_id,name', array(), 0, -1, 'member_lv_id ASC');

        //////////////////////////// 过滤条件模板 //////////////////////////////
        $oSOP = kernel::single('b2c_sales_order_process');
       $pt_list=$oSOP->getTemplateList();
		$pt_list_key=array (
						'b2c_promotion_conditions_order_subtotalselectgoods' => 
							array (
							  'name' => '当订单商品总价满X,对指定的商品优惠',
							  'type' => NULL,
							),
						'proqgoods_conditions_goods_goodsofquantity' => 
							array (
							  'name' => '指定商品购买量满X,自定义优惠',
							  'type' => NULL,
							),
						'b2c_promotion_conditions_order_itemsquanityallgoods' => 
							array (
							  'name' => '当订单商品数量满X,给予优惠',
							  'type' => 'order',
							),
						'b2c_promotion_conditions_order_allorderallgoods' => 
							array (
							  'name' => '对所有订单给予优惠',
							  'type' => 'order',
							),
					);
		foreach($pt_list_key as $plkey=>$plvalue){
			if($pt_list[$plkey]) unset($pt_list[$plkey]);
		}
        $this->pagedata['pt_list'] = $pt_list;

        //////////////////////////// 优惠方案模板 //////////////////////////////
        $oSSP = kernel::single('b2c_sales_solution_process');
		$stpl_list= $oSSP->getTemplateList();
		unset($stpl_list['goods']);
		//去掉非订单免邮的规则
		$stpl_list_key=array(
			  'b2c_promotion_solutions_topercent' => '订单以固定折扣出售',
			  'b2c_promotion_solutions_tofixed' => '订单固定价格购买',
			  'b2c_promotion_solutions_bypercent' => '订单减去固定折扣出售',
			  'b2c_promotion_solutions_byfixed' => '订单减固定价格购买',
			  'progetcoupon_promotion_solutions_getcoupon' => '订单送优惠券'
		);
		foreach($stpl_list_key as $slkey=>$slvalue){
			if($stpl_list['order'][$slkey]) unset($stpl_list['order'][$slkey]);
		}
        $this->pagedata['stpl_list'] =$stpl_list;
		$render = $this->app_current->render();
		header('Content-type: text/html; charset=utf-8');
        echo    $render->fetch('site/promotion/frame.html');
        //$this->singlepage('site/promotion/frame.html');
    }

    private function _sections() {
       return  array(
                 'basic'=> array(
                             'label'=>app::get('b2c')->_('基本信息'),
                             'options'=>'',
                             'app'=>'business',
                             'file'=>'site/promotion/basic.html',
                           ), // basic
               'conditions'=> array(

                                'label'=>app::get('b2c')->_('优惠条件'),
								'app'=>'business',
                                'options'=>'',
                                'file'=>'site/promotion/conditions.html',
                              ), // conditions
               'solution'=> array(
                              'label'=>app::get('b2c')->_('优惠方案'),
                              'options'=>'',
							  'app'=>'business',
                              'file'=>'site/promotion/solution.html',
                            ), // solutions
             );
    }

    public function toAddOrder() {
		$url = $this -> gen_url(array('app' => 'business', 'ctl' => 'site_promotion', 'act' => 'order'));
        $this->begin($url);
        $aData = $this->_prepareRuleData($_POST);
        if (isset($aData['conditions']['conditions'][0]['value']) && $aData['conditions']['conditions'][0]['value']){
            if(floatval($aData['conditions']['conditions'][0]['value']) <= 0){
                $this->end( false,'请输入正数！'  );
            }
        }
        if (isset($aData['action_solution']['b2c_promotion_solutions_byfixed']['total_amount']) && $aData['action_solution']['b2c_promotion_solutions_byfixed']['total_amount']){
            if(floatval($aData['action_solution']['b2c_promotion_solutions_byfixed']['total_amount']) <= 0){
                $this->end( false,'请输入正数！'  );
            }
        }
        $mSRO = app :: get('b2c')->model("sales_rule_order");
        $bResult = $mSRO->save($aData);

        $this->end($bResult,app::get('b2c')->_('操作成功'),$url);
    }

    /**
     * 这个MS可以放入model里处理
     */
    private function _prepareRuleData($aData) {
        ///////////////////////////// 基本信息 //////////////////////////////////
		$aData['rule']['store_id']=','. $this->sto->storeinfo['store_id'].','; 
		$aData['rule']['status']=false;//商家编辑订单促销规则时全部都是未启用状态
        $aResult = $aData['rule'];
        if( !$aResult['name'] ) $this->end( false,'促销名称不能为空！' );

        // 开始时间&结束时间
        foreach ($aData['_DTIME_'] as $val) {
            $temp['from_time'][] = $val['from_time'];
            $temp['to_time'][] = $val['to_time'];
        }
        $aResult['from_time'] = strtotime($aData['from_time'].' '. implode(':', $temp['from_time']));
        $aResult['to_time'] = strtotime($aData['to_time'].' '. implode(':', $temp['to_time']));
        if( $aResult['to_time']<=$aResult['from_time'] ) $this->end( false,'结束时间不能小于开始时间！' );
        
        // 会员等级
        $aResult['member_lv_ids'] = empty($aResult['member_lv_ids'])? null : implode(',',$aResult['member_lv_ids']);

        // 创建时间 (修改时不处理)
        if(empty($aResult['rule_id'])) $aResult['create_time'] = time();

        ////////////////////////////// 过滤规则 //////////////////////////////////
        $aResult['conditions'] = empty($aData['conditions'])? ( array('type'=>'combine','conditions'=>array())) : ($aData['conditions']);
        //if(is_null($aResult['conditions'])) $aResult['c_template'] = null;
        $aResult['action_conditions'] = empty($aData['action_conditions'])? ( array('type'=>'product_combine','conditions'=>array())) : ($aData['action_conditions']);

        ////////////////////////////// 优惠方案 //////////////////////////////////
        if ($aData['action_solution']['progetcoupon_promotion_solutions_getcoupon']['cpns_id']){
            if (!is_array($aData['action_solution']['progetcoupon_promotion_solutions_getcoupon']['cpns_id'])){
                $this->end( false,'请选择至少一张优惠券' );
                $aData['action_solution']['progetcoupon_promotion_solutions_getcoupon']['cpns_id'] = null;
            }
        }
        $aResult['action_solution'] = empty($aData['action_solution'])? null : ($aData['action_solution']);
        if( empty($aResult['sort_order']) && $aResult['sort_order']!==0 )
            $aResult['sort_order'] = 50;
        
        if( $aResult['sort_order'] ) $aResult['sort_order'] = (int)$aResult['sort_order'];
        
        /** 
         * 校验删选相应的表单元素
         */
	if(is_null($aData['rule']['c_template'])){
		$this->end(false,'优惠条件必选一项');
	}

        if(is_null($aData['rule']['s_template'])){
                $this->end(false,'优惠方案必选一项');
        }

        $obj_rule_c_template = kernel::single($aData['rule']['c_template']);
        if ($obj_rule_c_template){
            if (method_exists($obj_rule_c_template, 'verify_form'))
                if (!$obj_rule_c_template->verify_form($aData,$msg)){
                    $this->end( false, $msg);
                }
        }
        
        $obj_rule_s_template = kernel::single($aData['rule']['s_template']);
        if ($obj_rule_s_template){
            if (method_exists($obj_rule_s_template, 'verify_form'))
                if (!$obj_rule_s_template->verify_form($aData,$msg)){
                    $this->end( false, $msg);
                }
        }
        
        if($aResult['c_template'] == "proqgoods_conditions_goods_goodsofquantity" && (strpos($aResult['conditions']['conditions'][0]['value'],".") || strpos($aResult['conditions']['conditions'][0]['value'],".") === 0)){
            $goods_id = $aResult['conditions']['conditions'][0]['conditions'][0]['value'];
            $goodsinfo = app :: get('b2c')->model('goods')->getList('type_id',array('goods_id'=>$aResult['conditions']['conditions'][0]['conditions'][0]['value']));
            $typeinfo = app :: get('b2c')->model('goods_type')->getList('floatstore',array('type_id'=>$goodsinfo[0]['type_id']));
            if(!$typeinfo[0]['floatstore'])
                $aResult['conditions']['conditions'][0]['value'] = floor($aResult['conditions']['conditions'][0]['value']);
        }
        return $aResult;
    }
	
	
     private function _block($aHtml) {
        if((empty($aHtml)) || ( is_array($aHtml) && (empty($aHtml['conditions']) || empty($aHtml['action_conditions']))) ) die("<b align=\"center\">".app::get('b2c')->_("模板生成失败")."</b>");
        if(is_array($aHtml)) {
            $this->pagedata['conditions'] = $aHtml['conditions'];
            $this->pagedata['action_conditions'] = $aHtml['action_conditions'];
            $this->pagedata['multi_conditions'] = true;
        } else {
            $this->pagedata['multi_conditions'] = false;
            $this->pagedata['conditions'] = $aHtml;
        }
    }

     
    /**
     * 获取指定模板
     */
    public function template(){
      $render = app :: get('b2c')->render();   
      $oSOP = kernel::single('b2c_sales_order_process');
 
        // 只载入模板 有值的话也是没什么用的 
        $storeid =$_POST['storeid'];
        if($storeid){

            $store_id = explode(',',$_POST['storeid']);

             foreach($store_id as $key => $val) {
                 if ($val == '') unset($store_id[$key]);
             } 
            sort($store_id);
      
            $aHtml = $oSOP->getTemplate($_POST['template'],
                array('conditions'=>array('storeid_filter' =>array('store_id'=>$store_id),
                                           'isfront' =>'true'),
                      'action_conditions'=>array('storeid_filter' =>array('store_id'=>$store_id),
                                           'isfront' =>'true')
                     )
            );

        } else {
            $aHtml = $oSOP->getTemplate($_POST['template']);
        }
       $this->_block($aHtml);    
       echo   $render->fetch('site/store/promotion/order_rule.html');
       
      
    }



    /**
     * 用于优惠方案获取模板
     */
    public function solution() {

        $render = app :: get('b2c')->render();
        $oSSP = kernel::single('b2c_sales_solution_process');
        // 只载入模板 这里只是选择模板

        //设置前后台区分。
        $aData[$_POST['template']]['isfront'] ="true";

        //前台只有一个店铺
        if($_POST['store_id']){
            $aData[$_POST['template']]['store_id'] = ','.$_POST['store_id'].',';
        }
       
        $html = $oSSP->getTemplate($_POST['template'], $aData, $_POST['type']);
        if(empty($html)) die("<b align=\"center\">".app::get('b2c')->_("模板生成失败")."</b>");

        $this->pagedata['conditions'] = $html;
        //$this->display('site/store/promotion/goods_rule.html');

         echo   $render->fetch('site/store/promotion/goods_rule.html');
    }



    /**
     * 选择条件
     *
     */
    public function conditions(){
        // 传入的值为空的处理
        if(empty($_POST)) exit;

        // vpath
        $_POST['path'] .= '[conditions]['.$_POST['position'].']';
        $_POST['level'] += 1;

        $oSOP = kernel::single('b2c_sales_order_process');
        echo $oSOP->makeCondition($_POST);
    }
}
