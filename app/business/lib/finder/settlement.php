<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class business_finder_settlement
{
    var $detail_item;
    var $detail_settlement;
    var $detail_refund;
    var $pagelimit = 10;

    public function __construct($app)
    {
        $this->app = $app;
        $this->controller = app::get('business')->controller('admin_settlement');
        $this->detail_detail = app::get('business')->_('店铺结算明细');
        $this->detail_item = app::get('business')->_('当期订单明细');
        $this->detail_product = app::get('business')->_('当期商品明细');
        $this->detail_settlement = app::get('business')->_('往期负结算单据');
        $this->detail_refund = app::get('business')->_('往期未结算售后');
        $this->detail_result = app::get('business')->_('结算数据');
    }

    function detail_detail($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_detail'] ? $_GET['detail_detail'] : 1;
        $app = app::get('business');
        $detail = $app->model('settlement_detail');
        $data = $detail->getList('*', array('settlement_no' => $bill_no), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
		//计算各订单的结算总金额
		$order_ids = array();
		foreach($data as $k=>$v){
			$order_ids[] = $v['order_id'];
		}
		$cmplt_data = $detail->getList('*', array('settlement_no' => $bill_no,'order_id|in' => $order_ids));
		$order_account = array();
		foreach($cmplt_data as $k=>$v){
			if($current_order_id==$v['order_id']){
				$order_account[$current_order_id] += $v['account'];
			}else{
				$current_order_id = $v['order_id'];
				$order_account[$current_order_id] = $v['account'] + $v['cost_freight'];
			}
		}
		foreach($data as $k=>$v){
			$data[$k]['order_account'] = $order_account[$v['order_id']];
		}

        if ($bill_no)
        {
            $row = $detail->getList('id', array('settlement_no' => $bill_no));
            $count = sizeof($row);
        }
        $render = $app->render();
        $render->pagedata['detail'] = $data;
        $render->pagedata['settlement_no'] = $bill_no;
        if ($_GET['page'])
            unset($_GET['page']);
        $_GET['page'] = 'detail_detail';
        $this->controller->pagination($nPage, $count, $_GET);
        return $render->fetch('admin/settlement/page_detail.html');
    }

    function detail_item($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_item'] ? $_GET['detail_item'] : 1;
        $app = app::get('business');
        $item = $app->model('settlement_item');
        $data = $item->getList('*', array('settlement_no' => $bill_no,'item_type'=>"order"), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
        $account_info = $item->getList('sum(account) as account_all,sum(order_cut) as order_cut_all,sum(cost_freight) as cost_freight_all', array('settlement_no' => $bill_no,'item_type'=>"order"));
        if ($bill_no)
        {
            $row = $item->getList('id', array('settlement_no' => $bill_no,'item_type'=>"order"));
            $count = sizeof($row);
        }
        $render = $app->render();
        $render->pagedata['items'] = $data;
        $render->pagedata['account_info'] = $account_info;
        if ($_GET['page'])
            unset($_GET['page']);
        $_GET['page'] = 'detail_item';
        $this->controller->pagination($nPage, $count, $_GET);
        return $render->fetch('admin/settlement/page_item.html');
    }

    function detail_product($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_product'] ? $_GET['detail_product'] : 1;
        $app = app::get('business');
        $product = $app->model('settlement_product');
        $data = $product->getList('*', array('settlement_no' => $bill_no), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
        if ($bill_no)
        {
            $row = $product->getList('id', array('settlement_no' => $bill_no));
            $count = sizeof($row);
        }
        $render = $app->render();
        $render->pagedata['products'] = $data;
        if ($_GET['page'])
            unset($_GET['page']);
        $_GET['page'] = 'detail_product';
        $this->controller->pagination($nPage, $count, $_GET);
        return $render->fetch('admin/settlement/page_product.html');
    }

    function detail_settlement($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_settlement'] ? $_GET['detail_settlement'] : 1;
        $app = app::get('business');
        $item = $app->model('settlement');
        $data = $item->dump(array('settlement_no' => $bill_no),'pre_settlement,pre_settlement_cut');
        if($data){
            $settlement_nos = explode(',',$data['pre_settlement']);
            $datas = $item->getList('*', array('settlement_no|in' => $settlement_nos), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
            if ($settlement_nos)
            {
                $count = sizeof($settlement_nos);
            }

            $render = $app->render();
            $render->pagedata['items'] = $datas;
            $render->pagedata['data'] = $data;
            if ($_GET['page'])
                unset($_GET['page']);
            $_GET['page'] = 'detail_settlement';
            $this->controller->pagination($nPage, $count, $_GET);
            return $render->fetch('admin/settlement/detail_settlement.html');
        }else{
            return null;
        }
        
    }

    function detail_refund($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_refund'] ? $_GET['detail_refund'] : 1;
        $app = app::get('business');
        $item = $app->model('settlement_item');
        $obj = $app->model('settlement');
        $pre_refund_recut = $obj->dump(array('settlement_no' => $bill_no),'pre_refund_recut,b_pre_refund_recut,p_pre_refund_recut');
        $data = $item->getList('*', array('settlement_no' => $bill_no,'item_type'=>'refund'), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
        if ($bill_no)
        {
            $row = $item->getList('id', array('settlement_no' => $bill_no,'item_type'=>'refund'));
            $count = sizeof($row);
        }

        $render = $app->render();
        $render->pagedata['items'] = $data;
        $render->pagedata['pre_refund_recut'] = $pre_refund_recut;
        if ($_GET['page'])
            unset($_GET['page']);
        $_GET['page'] = 'detail_refund';
        $this->controller->pagination($nPage, $count, $_GET);
        return $render->fetch('admin/settlement/detail_refund.html');
    }

    function detail_result($bill_no = null)
    {
        if (!$bill_no)
            return null;
        $nPage = $_GET['detail_result'] ? $_GET['detail_result'] : 1;
        $app = app::get('business');
        $result = $app->model('settlement_result');
        $data = $result->getList('*', array('settlement_no' => $bill_no), $this->
            pagelimit * ($nPage - 1), $this->pagelimit);
        if ($bill_no)
        {
            $row = $result->getList('id', array('settlement_no' => $bill_no));
            $count = sizeof($row);
        }
        $render = $app->render();
        $render->pagedata['result'] = $data;
        $render->pagedata['settlement_no'] = $bill_no;
        if ($_GET['page'])
            unset($_GET['page']);
        $_GET['page'] = 'detail_result';
        $this->controller->pagination($nPage, $count, $_GET);
        return $render->fetch('admin/settlement/page_result.html');
    }

    /*
	var $column_control = '操作';
    var $column_control_width = 100;

 	function column_control($row){
		$settlement = app::get('business')->model('settlement');
        $settlement_data = $settlement->dump($row['settlement_no'],'*');

		$url = explode('=',$_SERVER['QUERY_STRING']);
		$string = $url[3];
		
        $res = '';
		if($settlement_data['is_balance'] == '2' || $settlement_data['is_balance'] == '4'){
			$res = $res.'<a href="index.php?app=business&ctl=admin_settlement&act=settlement_balance&callback='.$_GET['act'].'&settlement_no='.$row['settlement_no'].'"  >'.app::get('business')->_('结算').'</a>';
		}

        return $res;
    }
	*/

}
