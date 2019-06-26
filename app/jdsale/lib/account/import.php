<?php
/**
 * 导入账户的余额明细信息
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/14
 * Time: 14:22
 */
class jdsale_account_import {

    /**
     * 构造方法
     * @param object 当前应用app的对象
     * @return null
     */
    function __construct($app){
        $this->app = $app;
        $this->api_account = kernel::single('jdsale_api_account');
    }

    public $jdgoodsKind='normal';

    public function importBalanceDetail(){
        $balanceDetail = $this->getBalanceDetail(1,20);
        if($balanceDetail['data'][0]){
            $isUpToDate = $this->isUpToDate($balanceDetail['data'][0]);
            if ($isUpToDate['isUpToDate']){
                return false;
            }

            $this->saveBalanceDetail($balanceDetail['data'],$isUpToDate['trade_no']);
            $pageCount = $balanceDetail['pageCount'];
            if ($pageCount >1){
                for($pageNo =2;$pageNo <= $pageCount; $pageNo++){
                    $balanceDetail_next = $this->getBalanceDetail($pageNo,20);
                    $this->saveBalanceDetail($balanceDetail_next['data'],$isUpToDate['trade_no']);
                }

            }
            return true;
        }

    }

    public function saveBalanceDetail($balanceDetail ,$tradeNo){
        $mdl_balance = $this->app->model('balance');
        foreach ($balanceDetail as $k=>$v) {
            if ($v['tradeNo'] > $tradeNo){
                //echo '<br>save '.$v['tradeNo'].'-'.$k;
                $detail = array(
                    'trade_no' => $v['tradeNo'],
                    'order_kind' => 'jdorder',
                    'order_id' => $v['orderId'],
                    'trade_type' => $v['tradeType'],
                    'trade_type_name' => $v['tradeTypeName'],
                    'amount' => $v['amount'],
                    'note_pub' => $v['notePub'],
                    'create_time' => strtotime($v['createdDate']),
                );
                $mdl_balance->insert($detail);
            }
        }
    }

    public function isUpToDate($balanceDetail){
        $mdl_balance = $this->app->model('balance');
        $filter = array('order_kind' => 'jdorder');
        $row = $mdl_balance->getRow('*',$filter,'trade_no desc');
        if ($row){
            if ($balanceDetail['trade_no'] == $row['trade_no']){
                $retVal = array('isUpToDate'=>true);
            }else{
                $retVal = array('isUpToDate'=>false,
                                'trade_no' => $row['trade_no']);
            }
        }else{
            $retVal = array('isUpToDate'=>false,
                            'trade_no' => 0);
        }
        return  $retVal;

    }

    public function getBalanceDetail($pageNum=1,$pageSize=20,$startDate=null,$endDate=null,$orderId=null){
        $api_account = kernel::single('jdsale_api_account');
        $params = array(
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );
        if (!empty($startDate) && !empty($endDate)){
            $params['startDate'] = date('Ymd',strtotime($startDate));
            $params['endDate'] = date('Ymd',strtotime($endDate));
        }
        if (!empty($orderId)){
            $params['orderId'] = $orderId;
        }
        $balanceDetail = $api_account->queryBalancedetail($params,$this->jdgoodsKind);

        return $balanceDetail['result'];
    }

}