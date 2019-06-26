<?php

 
class cardcoupons_mdl_cards_balance extends dbeav_model{
		 //创建新的结算单号
     public function GetBillNo(){
	    $i = rand(0,999);
        do{
            if(999==$i){
                $i=0;
            }
            $i++;
            $bill_id = date('ymdHis').str_pad($i,3,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT settlement_no from sdb_cardcoupons_cards_balance where settlement_no ='.$bill_id);
        }while($row);
        return $bill_id;
     }
}