<?php

class cardcoupons_mdl_cards_exchangelog_items extends dbeav_model{

    function __construct($app){
        parent::__construct($app);
        $this->use_meta();
        $this->mySqlKeyLib = kernel::single('cardcoupons_mysqlkey');
    }

    /** 
     * 重写getList方法，取出数据后对密码字段解密
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        //fiter条件中有card_pass时，对card_pass加密
        if (isset($filter['card_pass'])){
            $tmp_filter = $ththis->mySqlKeyLibis->enPwByKeyList($filter['card_pass']);
            $filter['card_pass'] = $tmp_filter;
            unset($tmp_filter);
        }

        $arr_list = parent::getList($cols,$filter,$offset,$limit,$orderType);

        //如果cols中有card_pass字段则需解密
        if ($this->mySqlKeyLib->_hasCardPass($cols)){
            //对取出的密码解密
            foreach ($arr_list as $key => &$value) {
                $tmp_v = $value['card_pass'];
                $value['card_pass_ori'] = $tmp_v;
                $re = $this->mySqlKeyLib->dePwByKey($tmp_v);
                $value['card_pass'] = $re;
                unset($tmp_v,$re);
            }
        }

        return $arr_list;
    }


    /** 
     * 重写save方法，save前对密码加密
     */
    public function save(&$sdf)
    {   
    // error_log("=-=-=-=-=-=-=-=-".PHP_EOL.var_export($sdf,1).PHP_EOL,3,DATA_DIR."/".date('Ymd',time())."xxxxxxxxx.log");
        //对将要存储的密码加密处理
        if (isset($sdf['card_pass'])){
            $tmp_v = $sdf['card_pass'];
            // $ssql="select HEX(AES_ENCRYPT('{$tmp_v}','{$this->currentKey}')) as ep";
            // $re = kernel::database()->select($ssql);
            $sdf['card_pass'] = $this->mySqlKeyLib->enPwByCurrentKey($tmp_v); 
            unset($tmp_v);           
        }

        $result = parent::save($sdf);

        return $result;
    }

    public function getListOri($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {

        $arr_list = parent::getList($cols,$filter,$offset,$limit,$orderType);
        return $arr_list;
    }


    public function insert(&$data)
    {
        if (isset($data['card_pass'])){
            $data['card_pass'] = kernel::single('cardcoupons_mysqlkey')->enPwByCurrentKey($data['card_pass']);
        }
        return parent::insert($data);
    }
    
}