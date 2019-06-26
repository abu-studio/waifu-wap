<?php

 
/**
 * 支付方式的选择
 * 
 * @version 0.1
 * @package ectools.lib.payment
 */
class ectools_payment_select
{	
	/**
	 * 支付方式的过滤
	 * @param object controller
	 * @param array sdf construct
	 * @param string member id
	 * @param boolean backend or not
	 * @return string 结果html
	 */
	public function select_pay_method(&$controller, &$sdf, $member_id=0, $is_backend=false){
		$payment_cfg = app::get('ectools')->model('payment_cfgs');
		$currency = app::get('ectools')->model('currency');
		$payments = array();
		$arrPayments = $payment_cfg->getList('*', array('status' => 'true', 'platform'=>'ispc', 'is_frontend' => true));
		$arrDefCurrency = $currency->getDefault();
		if (!$sdf['cur_code'])
		{			
			$strDefCurrency = $arrDefCurrency['cur_code'];
		}
		else
			$strDefCurrency = $sdf['cur_code'];
		$currency = $sdf['cur'] ? $sdf['cur'] : $strDefCurrency;
		$def_payments = $sdf['def_payment'] ? $sdf['def_payment'] : '';
		$is_def_payment_match = false;
		$shop_def_currency = $arrDefCurrency['cur_code'];
		
		if ($def_payments)
		{
			$controller->pagedata['arr_def_payment'] = $payment_cfg->getPaymentInfo($def_payments);
			if ($def_payments == '-1')
				$is_def_payment_match = true;
		}
		
		if (!$member_id)
		{
			if (!$is_backend)
			{
				$arr_members = $controller->get_current_member();
				$member_id = $arr_members['member_id'];
			}
		}
		if ($arrPayments)
		{
            $isHasOrgPayments = false;
		    //判断雇员和企业登录商社登录的情况
            if($member_id && $_SESSION['account']['SETUP_MANAGER_TYPE'] != ''){
                $obj_com = app::get('qiyecenter')->model('abcommercial_sfscpayment_setting');
                $member_sel = $obj_com->getList('target_id,target_type',array('m_id'=>$member_id));
                if(! empty($member_sel[0]['target_id'])){
                    $isHasOrgPayments = true;
                }
            }
			foreach($arrPayments as $key=>$payment)
			{

				if (!$member_id)
				{
					if (trim($payment['app_id']) == 'deposit')
					{
						unset($arrPayments[$key]);
						continue;
					}
				}
				
                //预存款支付
                 if(!$isHasOrgPayments && $_SESSION['account']['SETUP_MANAGER_TYPE'] != 'I03702' && $_SESSION['account']['SETUP_MANAGER_TYPE'] != 'I03703'){
					if (trim($payment['app_id']) == 'deposit')
					{
						if ($shop_def_currency == $currency)
						{
							if (isset($controller->pagedata['arr_def_payment']) && $controller->pagedata['arr_def_payment'])
							{
								$arr_def_payment = $controller->pagedata['arr_def_payment'];
								if ($arr_def_payment['app_id'] == $payment['app_id'])
									$is_def_payment_match = true;
							}
							$payments[] = $payment;
						}
						continue;
					}
				}
				//zxc支付宝支付
			  if($_SESSION['account']['SETUP_MANAGER_TYPE'] == 'I03701' || $_SESSION['account']['SETUP_MANAGER_TYPE'] == 'I03702' || $_SESSION['account']['SETUP_MANAGER_TYPE'] == 'I03703'){
				if (trim($payment['app_id']) == 'alipay')
				{
					if ($shop_def_currency == $currency)
					{
						if (isset($controller->pagedata['arr_def_payment']) && $controller->pagedata['arr_def_payment'])
						{
							$arr_def_payment = $controller->pagedata['arr_def_payment'];
							if ($arr_def_payment['app_id'] == $payment['app_id'])
								$is_def_payment_match = true;
						}
						$payments[] = $payment;
					}
					continue;
				}
			  }


				if (trim($payment['app_id']) == 'offline')
				{
					if (isset($controller->pagedata['arr_def_payment']) && $controller->pagedata['arr_def_payment'])
					{
						$arr_def_payment = $controller->pagedata['arr_def_payment'];
						if ($arr_def_payment['app_id'] == $payment['app_id'])
							$is_def_payment_match = true;
					}
					$payments[] = $payment;					
					continue;
				}
				//福点,支付宝总的支付
			   if(!$isHasOrgPayments && $_SESSION['account']['SETUP_MANAGER_TYPE'] != 'I03702' && $_SESSION['account']['SETUP_MANAGER_TYPE'] != 'I03703'){
					if ($payment['supportCurrency'] && is_array($payment['supportCurrency']))
					{
						if (array_key_exists($currency, $payment['supportCurrency']))
						{
							if (isset($controller->pagedata['arr_def_payment']) && $controller->pagedata['arr_def_payment'])
							{
								$arr_def_payment = $controller->pagedata['arr_def_payment'];
								if ($arr_def_payment['app_id'] == $payment['app_id'])
									$is_def_payment_match = true;
							}
							$payments[] = $payment;
						}							
					}
			  }
			}


			//获取java支付公司账户
            //error_log(var_export($_SESSION,true)."\n", 3, ROOT_DIR."/shaojun.txt");
			if($_SESSION['account']['SETUP_MANAGER_TYPE'] !=''){
                //I03701	商社管理员
                //I03702	部门管理员
                //I03703	群组管理员
                if('I00102' == $member_sel[0]['target_type']){
                    $serviceNo="SubAccountService";
                    $_sjson = array(
                        'METHOD' => 'getCompanyToPayByCompanyNo',
                        'COMPANY_NO' => $member_sel[0]['target_id'],
                    );
                }else if('I00103' == $member_sel[0]['target_type']){
                    $serviceNo="AccountService";
                    $_sjson = array(
                        'METHOD' => 'getDeptAccountByDeptId',
                        'DEPT_ID' => $member_sel[0]['target_id'],
                    );
                }else if('I00104' == $member_sel[0]['target_type']){
                    $serviceNo="AccountService";
                    $_sjson = array(
                        'METHOD' => 'getGroupAccountByGroupId',
                        'GROUP_ID' => $member_sel[0]['target_id'],
                    );
                }else{
                    $serviceNo="AccountService";
                    $_sjson = array(
                        'METHOD' => 'getDeptOrGroupAccountByHumbasNo',
                        'SETUP_MANAGER_TYPE' =>$_SESSION['account']['SETUP_MANAGER_TYPE'],
                        'HUMBAS_NO' => $_SESSION['account']['HUMBAS_NO'],
                    );
                }

                $sfscdata = SFSC_HttpClient::doLifCostMain($_sjson,$serviceNo);
                header('Content-Type:text/html;charset=utf-8');
                //error_log(var_export($sfscdata,true)."\n", 3, ROOT_DIR."/shaojun.txt");
                /*
                array (
                  'BALANCE' => 1000,
                  'SUB_ACT_ID' => 'a5d97404e4074c669d4532bd6bc6e2e9',
                  'CUSTOMER_NAME' => 'US4960',
                  'ACT_ID' => '615d93eb120646959966c06d9c5ddccb',
                  'ACT_TYPE' => 'I00102',
                  'RELATION_ID' => 'US4960',
                ),
                */
                //error_log(var_export($payments,true)."\n", 3, ROOT_DIR."/shaojun.txt");
                if(!empty($sfscdata['RESULT_DATA'])){
                    /*
                      array (
                         'platform' => 'ispc',
                         'app_platform' => '标准版',
                         'app_name' => '福点支付',
                         'app_staus' => '开启',
                         'app_version' => '1.0',
                         'app_id' => 'sfscpay',
                         'app_rpc_id' => 'sfscpay',
                         'app_class' => 'ectools_payment_plugin_sfscpay',
                         'app_des' => '&nbsp;',
                         'app_pay_type' => 'true',
                         'app_display_name' => ' 福点支付',
                         'app_info' => '福点支付自定义描述',
                         'support_cur' => '',
                         'pay_fee' => NULL,
                         'supportCurrency' =>
                         array (
                           'CNY' => '01',
                         ),
                     ),
                    */
                    foreach($sfscdata['RESULT_DATA'] as $k=>$v){
                        if(!empty($v['CUSTOMER_NAME'])){
                            $v['CUSTOMER_NAME'] =  $v['CUSTOMER_NAME'] . '-';
                        }

                        $payments[] = array(
                            'platform' => 'ispc',
                            'app_platform' => '标准版',
                            'app_name' => '福点支付',
                            'app_staus' => '开启',
                            'app_version' => '1.0',
                            'app_id' => 'sfscpay',
                            'app_rpc_id' => 'sfscpay',
                            'app_class' => 'ectools_payment_plugin_sfscpay',
                            'app_des' => '&nbsp;',
                            'app_pay_type' => 'true',
                            'app_display_name' => $v['RELATION_NAME']? $v['RELATION_NAME'] :($v['DEPT_NAME'] ? $v['DEPT_NAME'] : $v['GROUP_NAME']),
                            'app_display_company' => $v['CUSTOMER_NAME'],
                            'app_info' => '福点支付自定义描述',
                            'support_cur' => '',
                            'QS_payment_id' => $v['RELATION_ID'],
                            'pay_fee' => NULL,
                            'supportCurrency' =>
                                array (
                                    'CNY' => '01',
                                ),
                        );
                    }
                }
            }

            $controller->pagedata['def_payments'] = $def_payments;
			$controller->pagedata['arr_def_payment'] = (isset($_COOKIE['purchase']['payment']) && $_COOKIE['purchase']['payment']) ? unserialize($_COOKIE['purchase']['payment']) : '';
			$controller->pagedata['is_def_payment_match'] = ($is_def_payment_match) ? 1 : 0;
			$controller->pagedata['payments'] = &$payments;
            $controller->pagedata['payments_num'] = count($payments);
			$controller->pagedata['order'] = &$sdf;
			if (!$is_backend)
			{
			    $str_html = $controller->fetch("site/common/paymethod.html",$controller->pagedata['app_id']);
				$obj_ajax_View_help = kernel::service('ectools_payment_ajax_html');
				if (!$obj_ajax_View_help)
					return $str_html;
				else
					return $obj_ajax_View_help->get_html($str_html,'ectools_payment_select','select_pay_method');
			}
			else
			{
				$str_html = $controller->fetch("admin/order/paymethod.html",$controller->pagedata['app_id']);
				$obj_ajax_View_help = kernel::service('ectools_payment_ajax_html');
				if (!$obj_ajax_View_help)
					return $str_html;
				else
					return $obj_ajax_View_help->get_html($str_html,'ectools_payment_select','select_pay_method');
			}

		}
	}

	public function change_def_payment(&$controller, $pay_app_id='alipay')
	{
		$payment_cfg = app::get('ectools')->model('payment_cfgs');
		if ($pay_app_id)
		{
			if ($pay_app_id == -1)
				$pay_app_id = app::get('ectools')->_('货到付款');
			$controller->pagedata['arr_def_payment'] = $payment_cfg->getPaymentInfo($pay_app_id);
			$str_html = $controller->fetch("site/common/paymethod_def_info.html");
			$obj_ajax_View_help = kernel::service('ectools_payment_ajax_html');
			if (!$obj_ajax_View_help)
				return $str_html;
			else
				return $obj_ajax_View_help->get_html($str_html,'ectools_payment_select','change_def_payment');
		}
	}
}
