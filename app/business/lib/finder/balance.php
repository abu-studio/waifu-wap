<?php

class business_finder_balance
{
	var $column_control = '操作';
    var $column_control_width = 100;

 	function column_control($row){
		$refund = app::get('ectools')->model('refunds');
        $refund_data = $refund->dump($row['refund_id'],'*');

		$str_app = "";
        $pay_app_id = ($refund_data['pay_app_id']) ? $refund_data['pay_app_id'] : $refund_data['pay_type'];
        $obj_app_plugins = kernel::servicelist("ectools_refund.ectools_mdl_refund_cfgs");
        foreach ($obj_app_plugins as $obj_app)
        {
            $app_class_name = get_class($obj_app);
            $arr_class_name = explode('_', $app_class_name);
            if (isset($arr_class_name[count($arr_class_name)-1]) && $arr_class_name[count($arr_class_name)-1])
            {
                if ($arr_class_name[count($arr_class_name)-1] == $pay_app_id)
                {
                    $pay_app_ins = $obj_app;
                    $str_app = $app_class_name;
                }
            }
			else
			{
				if ($app_class_name == $pay_app_id)
				{
					$pay_app_ins = $obj_app;
					$str_app = $app_class_name;
				}
			}
        }

		if($refund_data['status'] == 'ready' && $refund_data['refund_type'] == '1' && ( method_exists($pay_app_ins, 'dorefund') || $refund_data['pay_app_id'] == 'deposit')){
			return '<a href="index.php?app=business&ctl=admin_balance&act=balance_refund&refund_id='.$row['refund_id'].'"  >'.app::get('business')->_('退款').'</a>';
		}

		if($refund_data['status'] == 'ready' && $refund_data['refund_type'] == '1' && ( !method_exists($pay_app_ins, 'dorefund') || $refund_data['pay_type'] == 'offline')){
			return '<a href="index.php?app=business&ctl=admin_balance&act=balance_refund_confirm&refund_id='.$row['refund_id']. '" target="dialog::{title:\''.app::get('business')->_('确认退款信息').'\', width:500, height:300}">'.app::get('business')->_('确认退款信息').'</a>';
		}

    }

   
        
}