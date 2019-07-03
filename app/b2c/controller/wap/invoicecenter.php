<?php
    class b2c_ctl_wap_invoicecenter extends b2c_ctl_wap_member
    {

        public function __construct(&$app) {
            parent::__construct($app);
            $shopname = app::get('wap')->getConf('wap.name');
            $this->shopname = $shopname;
            if ( isset($shopname) ) {
                $this->title = app::get('b2c')->_('发票中心页').'_'.$shopname;
                $this->keywords = app::get('b2c')->_('发票中心页').'_'.$shopname;
                $this->description = app::get('b2c')->_('发票中心页').'_'.$shopname;
            }
            $this->_response->set_header('Cache-Control', 'no-store, no-cache');
        }
        public function index($nPage = 1)
        {
            $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
            $this->path[] = array('title'=>app::get('b2c')->_('发票中心'),'link'=>'#');
            $GLOBALS['runtime']['path'] = $this->path;
            $memberInvoice = app::get('b2c')->model('member_invoice');
            if (empty($nPage))
            {
                $nPage = 1;
            }
            $limit = 10;
            $limitStart = ($nPage-1) * $limit;
            $filter = array('member_id'=>$this->member['member_id']);
            $aData = $memberInvoice->getList('*',array('member_id'=>$this->member['member_id']),$limitStart, $limit,'last_modify DESC');
            if (count($aData) > $limit)
            {
                // 生成分页组建
                $countRd = $memberInvoice->count($filter);
                $total = ceil($countRd/$limit);
                $current = $nPage;
                $token = '';
                $arrPager = array(
                    'current' => $current,
                    'total' => $total,
                    'token' => $token,
                );
                //修改分页链接参数 --start
                $arr_args = array($this->member['member_id']);
                //--end
                $this->pagination($nPage,$arrPager['total'],'index',$arr_args,'b2c','wap_invoicecenter');
            }
            $this->pagedata['invoices'] = $aData;
            $this->set_tmpl('invoicecenter');
            $this->page('wap/invoicecenter/index.html');
        }

        //添加新发票页面
        function modify_view($invoice_id)
        {
            $memberInvoice = app::get('b2c')->model('member_invoice');
            $this->pagedata['data'] = $memberInvoice->getRow('*',array('member_id'=>$this->member['member_id'],'invoice_id'=>$invoice_id));
            if (empty($this->pagedata['data']) && !empty($invoice_id))
            {
                $this->begin(array('app'=>'b2c','ctl'=>'wap_invoicecenter','act'=>'index'));
                $this->end(false, app::get('b2c')->_('参数错误！'));
            }
            $this->page('wap/invoicecenter/modify_view.html');
        }

        //添加新发票页面
        function doSave()
        {
            if(empty($_POST['invoice_default']))
            {
                $_POST['invoice_default'] = 0;
            }
            $_POST['member_id'] = $this->app->member_id;
            $_POST['last_modify'] = time();
            $obj_member =app::get('b2c')->model('members');
            $aData = $obj_member->checkRecInput($_POST);
            $memberInvoice = app::get('b2c')->model('member_invoice');
            if ($aData['invoice_default'] == 1)
            {
                $memberInvoice->update(array('invoice_default'=>0),array('member_id '=>$aData['member_id'] ));
            }
            else
            {
                $rows = $memberInvoice->getList('invoice_id',array('member_id '=>$aData['member_id'],'invoice_default'=>1 ));
                if (count($rows) == 1)
                {
                    $this->splash('failed',null,'需要有个默认设置','','',true);
                }
            }
            if ($memberInvoice->save($aData))
            {
                $this->splash('success',$this->gen_url(array('app'=>'b2c','ctl'=>'wap_invoicecenter','act'=>'index')),app::get('b2c')->_('保存成功'),'','',true);
            }
            $this->splash('failed',null,'保存失败','','',true);
        }

        /**
         * @Author: panbiao <panbiaophp@163.com>
         * @DateTime: 2019-07-03 13:28
         * @Desc: 设置和取消默认，$disabled 2为设置默认1为取消默认
         */
        function set_default()
        {
            $invoice_id = $_POST['invoice_id'];
            if(empty($invoice_id))
            {
                echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
            }
            $disabled = $_POST['disabled']==2?1:0;
            $member_id = $this->app->member_id;
            $memberInvoice = app::get('b2c')->model('member_invoice');
            if ($disabled == 1)
            {
                $memberInvoice->update(array('invoice_default'=>0),array('member_id '=>$member_id ));
            }
            else
            {
                $rows = $memberInvoice->getList('invoice_id',array('member_id '=>$member_id,'invoice_default'=>1 ));
                if (count($rows) == 1)
                {
                    echo json_encode(array('status'=>'failed','msg'=>'需要有个默认设置'));exit;
                }

            }
            if($memberInvoice->update(array('invoice_default'=>$disabled),array('invoice_id'=>$invoice_id,'member_id '=>$member_id )))
            {
                echo json_encode(array('status'=>'success','msg'=>'设置成功'));exit;
            }
            else
            {
                echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
            }
        }


        //删除
        function delInvoice()
        {
            $invoice_id = $_POST['invoice_id'];
            if($invoice_id)
            {
                $memberInvoice = app::get('b2c')->model('member_invoice');
                $filter = array('invoice_id'=>$invoice_id,'member_id' => $this->app->member_id);
                $row = $memberInvoice->getRow('invoice_id',$filter);
                if (empty($row))
                {
                    echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
                }
                $orderInvoice = app::get('b2c')->model('order_invoice');
                $invoice_data = $orderInvoice->getRow('id',$filter);
                if (!empty($invoice_data))
                {
                    echo json_encode(array('status'=>'failed','msg'=>'已绑定申请，不能删除'));exit;
                }

                if($memberInvoice->delete($filter))
                {
                    echo json_encode(array('status'=>'success','msg'=>'删除成功'));exit;
                }
                else{
                    $meesage = app::get('b2c')->_("删除失败");
                    echo json_encode(array('status'=>'failed','msg'=>$meesage));exit;
                }

            }else{
                echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
            }
        }

    }
