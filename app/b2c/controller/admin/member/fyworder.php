<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/14
 * Time: 13:26
 */

class b2c_ctl_admin_member_fyworder extends desktop_controller{

    Var $workground = 'b2c_ctl_admin_order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'b2c_mdl_orders_fyw', array(
                'title' => $this->app->_('福员外订单'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
                'base_filter' =>array('order_status' => '1'),
                'use_buildin_export'=>true,
                'use_view_tab'=>true,
                'force_view_tab'=>true,
            )
        );
    }

    public function _views() {

        $mdl_order = $this->app->model('orders_fyw');
        $refund_filter = array('order_status'=>2,
                               'tradeStatus'=>'SUCC');
        $oriTradeNoList = $mdl_order->getList('oriTradeNo',$refund_filter);
        $oriTradeNoArr = array();
        foreach($oriTradeNoList as $k2=>$v2){
            $oriTradeNoArr[]=$v2['oriTradeNo'];
        }
        if (empty($oriTradeNoArr)){
            $hasNoRefund = array('order_status'=>'1',
                                 'tradeStatus' =>array('SUCC'),
            );
        }else{
            $hasNoRefund = array('order_id|notin'=>$oriTradeNoArr,
                                 'order_status'=>'1',
                                 'tradeStatus' =>array('SUCC'),
            );
            $hasRefund = array('order_id|in'=>$oriTradeNoArr,
                               'tradeStatus' =>array('SUCC'));
        }
        $sub_menu  = array(
            0 => array(
                'label'    => app::get('b2c')->_('全部'),
                'optional' => false,
                'filter'   => array(
                'order_status' => array('1'),
                )
            ),
            1 => array(
                'label'    => app::get('b2c')->_('已支付'),
                'optional' => false,
                'filter'   => array(
                'order_status' => array('1'),
                'tradeStatus' =>array('SUCC'),
                )
            ),
            2 => array(
                'label'    => app::get('b2c')->_('已退款'),
                'optional' => false,
                'filter'   => array(
                'order_status' => array('2'),
                'tradeStatus' =>array('SUCC'),
                )
            ),
            3 => array(
                'label'    => app::get('b2c')->_('支付失败'),
                'optional' => false,
                'filter'   => array(
                'order_status' => array('1'),
                'tradeStatus' =>array('ING','FAIL'),
                )
            ),
        );

        foreach($sub_menu as $k=>$v){
            if($v['optional']==false){
                $show_menu[$k] = $v;

                if($k === 1){
                    $show_menu[$k]['filter'] = $hasNoRefund?$hasNoRefund:null;
                    $show_menu[$k]['addon'] = $mdl_order->count($hasNoRefund);
                }else if($k === 2){

                    $show_menu[$k]['filter'] = $hasRefund?$hasRefund:null;
                    $show_menu[$k]['addon'] = $mdl_order->count($hasRefund);
                }else{
                    $show_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
                    $show_menu[$k]['addon'] = $mdl_order->count($v['filter']);
                }
                $show_menu[$k]['href'] = 'index.php?app=b2c&ctl=admin_member_fyworder&act=index&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['view']){
                $show_menu[$k] = $v;
            }
        }
        return $show_menu;
    }
}
