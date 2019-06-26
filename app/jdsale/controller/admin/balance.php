<?php


class jdsale_ctl_admin_balance extends desktop_controller{

    var $workground = 'jdsale.workground.order';
    var $pageLimit = 20;
    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        //导入最新余额明细并保存到数据库
        $jdsale_account_import = kernel::single('jdsale_account_import');
        $jdsale_account_import->importBalanceDetail();


        $this->finder(
            'jdsale_mdl_balance', array(
                'title' => $this->app->_('账户余额：￥'.$this->getBalance().'元'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
				'use_buildin_export'=>true,
                'base_filter'=>array('order_kind'=>array('entity','virtual','b2c_card','card','jdorder')),
            )
        );

    }

    //lpc 新增京东图书余额
    public function bookindex(){

        //导入最新余额明细并保存到数据库
        $jdsale_account_import = kernel::single('jdsale_account_import');
        $jdsale_account_import->jdgoodsKind = "book";
        $jdsale_account_import->importBalanceDetail();


        $this->finder(
            'jdsale_mdl_balance', array(
                'title' => $this->app->_('账户余额：￥'.$this->getBalance("book").'元'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
                'use_buildin_export'=>true,
                'base_filter'=>array('order_kind'=>'jdbook'),
            )
        );

    }

    private function getBalance($jdgoodsKind="normal"){
        $api_account = kernel::single('jdsale_api_account');
        $balance = $api_account->queryBalance(array('payType'=>4),$jdgoodsKind);
        return $balance['result'];
    }

}
