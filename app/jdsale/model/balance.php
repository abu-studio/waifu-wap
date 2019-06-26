<?php


/**
* 京东账户余额的model
*/
class jdsale_mdl_balance extends dbeav_model
{
    /**
	* @var string 排序方式
	*/
     var $defaultOrder = 'create_time desc';
	/*
	 public function fgetlist_csv( &$data,$filter,$offset,$exportType =1 ){
		$limit = 100;
		@ini_set('memory_limit','512M');
		$jdorders = app::get('jdsale')->model('jdorders');
		$order = app::get('b2c')->model('orders');
		$cols = $this->_columns();
		if(!$data['title']){
			$this->title = array();
			foreach( $this->getTitle($cols) as $titlek => $aTitle ){
				$this->title[$titlek] = $aTitle;
			}
			//京东本地订单 本地订单价格
			array_push($this->title,'京东本地订单','本地订单价格');
			$data['title'] = '"'.implode('","',$this->title).'"';
		}
		
		if(!$list = $this->getList(implode(',',array_keys($cols)),$filter,$offset*$limit,$limit))return false;

		foreach( $list as $line => $row ){
			
			$info = $jdorders->getRow('order_id',array('jdorders_id'=>$row['order_id']));
			$orderDate = $order->getRow('order_id,final_amount',array('order_id'=>$info['order_id']));
			array_push($row,$orderDate['order_id'],$orderDate['final_amount']);
			$rowVal = array();
			foreach( $row as $col => $val ){
				
				if( in_array( $cols[$col]['type'],array('time','last_modify') ) && $val ){
				   $val = date('Y-m-d H:i',$val)."\t";
				}
				if ($cols[$col]['type'] == 'longtext'){
					if (strpos($val, "\n") !== false){
						$val = str_replace("\n", " ", $val);
					}
				}

				if(strlen($val) > 8 && eregi("^[0-9]+$",$val)){
					$val .= "\r";
				}
				
				if( strpos( (string)$cols[$col]['type'], 'table:')===0 ){
					$subobj = explode( '@',substr($cols[$col]['type'],6) );
					if( !$subobj[1] )
						$subobj[1] = $this->app->app_id;
					$subobj = &app::get($subobj[1])->model( $subobj[0] );
					$subVal = $subobj->dump( array( $subobj->schema['idColumn']=> $val ),$subobj->schema['textColumn'] );
					$val = $subVal[$subobj->schema['textColumn']]?$subVal[$subobj->schema['textColumn']]:$val;
				}

				if( array_key_exists( $col, $this->title ) )
					$rowVal[] = addslashes(  (is_array($cols[$col]['type'])?$cols[$col]['type'][$val]:$val ) );
			}
			$data['contents'][] = '"'.implode('","',$rowVal).'"';
		}
		return true;
	}

	function getTitle(&$cols){
        $title = array();
        foreach( $cols as $col => $val ){
            $title[$col] = $val['label'].'('.$col.')';
        }
        return $title;
    }
	*/

	function modifier_amount($row){
		return '￥'.sprintf("%.2f",$row);
	}

}