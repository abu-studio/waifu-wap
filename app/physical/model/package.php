<?php
class physical_mdl_package extends dbeav_model {
	var $has_tag = true;
	var $has_many=array(
		'cost_price'=>'store_package_price:replace:package_id^package_id',		
		'stores'=>'store_package_attach:replace:package_id^package_id',		
		);
	var $has_one = array(

    );
	var $subSdf = array(
        'default' => array(
            'cost_price' => array('*'),
            'stores' => array('*'),
        ),

         'delete'=>array( 
			'cost_price' => array('*'),
            'stores' => array('*'),
		),
    );

	function pre_recycle($rows){
        foreach($rows as $v){
            $package_ids[] = $v['package_id'];
        }

		$o_attach = &$this->app->model('store_package_attach');
		$rows = $o_attach->getList('attach_id',array('package_id'=>$package_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该套餐已被门店关联');
            return false;
        }

		$o_orders = &$this->app->model('orders');
		$rows = $o_orders->getList('id',array('package_id'=>$package_ids));
		if( $rows ){
			$this->recycle_msg = app::get('physical')->_('该套餐已被预约单选中');
			return false;
        }
		
		$card_solution=kernel::single('cardcoupons_mdl_cards_solution')->getList('*',array('services_id'=>$package_id));
		if( $card_solution ){
			$this->recycle_msg = app::get('physical')->_('该套餐已被卡券选为套餐内容');
			return false;
        }
        return true;
    }

	function get_attach_list($package_id) {
        $package_id = trim($package_id);
        $row = $this -> db -> select("SELECT a.*,b.store_name,b.store_code,b.area,b.address,c.organization_name ,pp.man,pp.unmdwoman,pp.mdwoman from sdb_physical_store_package_attach as a LEFT JOIN sdb_physical_store_package_price as pp on a.store_id=pp.store_id  LEFT JOIN  sdb_physical_store as b ON b.store_id =  a.store_id LEFT JOIN sdb_physical_organization as c ON c.organization_id =  b.organization_id WHERE  a.package_id ='{$package_id}' ");
        if ($row) {
            return $row;
        }
    }

    //体检产品列表
	function get_goods_list( $where="",$orderby="",$start=-1,$limit=0){
		$sql = "SELECT a.goods_id,a.name,a.price,a.mktprice,a.image_default_id,a.brief FROM sdb_b2c_goods as a JOIN sdb_cardcoupons_cards as b ON b.goods_id=a.goods_id WHERE b.type_id = '02' ";
		
		if($where){
			$sql .= " {$where}";
		}
		if($orderby){
			$sql .= " ORDER BY {$orderby}";
		}
		if($start >=0 && $limit>0){
			$sql .= " LIMIT {$start},{$limit}";
		}
		$row = $this -> db -> select($sql);
        if ($row) {
            return $row;
        }
	}

	//体检产品列表-数量
	function get_goods_list_num( $where=""){
		$sql = "SELECT a.goods_id FROM sdb_b2c_goods as a JOIN sdb_cardcoupons_cards as b ON b.goods_id=a.goods_id WHERE b.type_id = '02' ";
		if($where){
			$sql .= " {$where}";
		}
		$num = $this -> db -> count($sql);
        return $num;
	}


	//体检门店列表
	function get_store_list( $where="",$orderby="",$start=-1,$limit=0){
		$sql = "SELECT a.store_id,a.store_name,a.image,a.area,a.address,a.phone,a.open,a.close,a.weekday FROM sdb_physical_store as a LEFT JOIN sdb_physical_store_package_attach as b ON b.store_id = a.store_id LEFT JOIN sdb_physical_package as c ON c.package_id = b.package_id LEFT JOIN sdb_cardcoupons_cards_solution as d ON d.services_id = c.package_id and d.key_type = '02' LEFT JOIN sdb_cardcoupons_cards as e ON e.card_id = d.card_id and e.type_id = '02' where a.type = 1";
		if($where){
			$sql .= " {$where}";
		}
		if($orderby){
			$sql .= " ORDER BY {$orderby}";
		}
		$sql .= " GROUP BY a.store_id";
		if($start >=0 && $limit>0){
			$sql .= " LIMIT {$start},{$limit}";
		}
		$row = $this -> db -> select($sql);
        if ($row) {
            return $row;
        }
	}

	//体检门店列表-数量
	function get_store_list_num( $where=""){
		$sql = "SELECT a.store_id FROM sdb_physical_store as a LEFT JOIN sdb_physical_store_package_attach as b ON b.store_id = a.store_id LEFT JOIN sdb_physical_package as c ON c.package_id = b.package_id LEFT JOIN sdb_cardcoupons_cards_solution as d ON d.services_id = c.package_id and d.key_type = '02' LEFT JOIN sdb_cardcoupons_cards as e ON e.card_id = d.card_id and e.type_id = '02' where a.type = 1";
		if($where){
			$sql .= " {$where}";
		}
		$sql .= " GROUP BY a.store_id";
		$row = $this -> db -> select($sql);
		$num = count($row);
        return $num;
	}
    
	//体检产品详情-体检项目
	function get_all_project($goods_id=0){
		$sql1 = "SELECT a.project_ids FROM sdb_physical_package as a JOIN sdb_cardcoupons_cards_solution as b ON b.services_id = a.package_id and b.key_type = '02' LEFT JOIN sdb_cardcoupons_cards as c ON c.card_id = b.card_id and c.type_id = '02' WHERE c.goods_id = {$goods_id} ";
		$row1 = $this -> db -> select($sql1);

		$project_ids="";
		foreach($row1 as $val1){
			if($val1['project_ids']){
				$project_ids .= ",".$val1['project_ids'];
			}
		}

		$project_ids = trim($project_ids,",");
		if($project_ids){
			$sql2 = "SELECT a.project_id,a.project_name,a.introduction FROM sdb_physical_project as a WHERE a.project_id in ({$project_ids}) ";
			$row2 = $this -> db -> select($sql2);
			return $row2;
		}else{
			return false;
		}
	}

	//体检产品的预约套餐
	function get_all_package($goods_id=0){
		$sql = "SELECT a.package_id,a.package_name FROM sdb_physical_package as a JOIN sdb_cardcoupons_cards_solution as b ON b.services_id = a.package_id and b.key_type = '02' LEFT JOIN sdb_cardcoupons_cards as c ON c.card_id = b.card_id and c.type_id = '02' WHERE c.goods_id = {$goods_id} ";
		$row = $this -> db -> select($sql);
		return $row;
	}

}
