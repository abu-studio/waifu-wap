<?php
 

class b2c_ctl_admin_invoice extends desktop_controller{
    var $workground = 'b2c_ctl_admin_goods';

    function index(){
        $actions_base['title'] = app::get('b2c')->_('雇员申请发票列表');
        $actions_base['use_buildin_filter'] = true;
        $actions_base['allow_detail_popup'] = true;
        $actions_base['use_view_tab'] = true;
        $actions_base['actions'] = array(
        );
        $this->finder('b2c_mdl_order_invoice',$actions_base);
    }

   function _views(){
        $sub_menu = array();
        return $sub_menu;
    }

    function add_send(){
        $obj_invoice = app::get('b2c')->model('order_invoice');
        $array_invoice = $obj_invoice->dump(array('id'=>$_REQUEST['id']),"id,send_message,is_send");
        $this->pagedata['data'] = $array_invoice;
        $this->pagedata['finder_id'] = $_REQUEST['_finder']['finder_id'];
        $this->display('admin/invoice/send.html');
    }


    function save(){
        $this->begin('index.php?app=b2c&ctl=admin_invoice&act=index');
        if($_POST['id'] == ''){
            $this->end(false, app::get('b2c')->_('参数错误'));
        }

        $obj_invoice = app::get('b2c')->model('order_invoice');

        $invoice_flag = $obj_invoice->update(array('is_send'=>$_POST['is_send'],'send_message'=>$_POST['send_message']),array('id'=>$_POST['id']));
        if($invoice_flag){
            $this->end(true, app::get('b2c')->_('保存成功'));
        }else{
            $this->end(false, app::get('b2c')->_('保存失败'));
        }


    }


}
