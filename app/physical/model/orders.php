<?php
class physical_mdl_orders extends dbeav_model {
	var $has_tag = true;
    function getInfobyid($id) {
        $id = trim($id);
        $row = $this -> db -> selectrow("SELECT a.*,b.package_name,b.project_ids,c.card_no from sdb_physical_orders as a LEFT JOIN sdb_physical_package as b ON b.package_id =  a.package_id LEFT JOIN sdb_cardcoupons_cards_pass as c ON c.card_pass_id =  a.card_pass_id WHERE  a.id ='{$id}' ");
        if ($row) {
			if($row['project_ids']){
				$project_list = $this -> db -> select("SELECT a.*,b.subject_name from sdb_physical_project as a LEFT JOIN sdb_physical_subject as b ON b.subject_id =  a.subject_id WHERE  a.project_id in ({$row['project_ids']}) ");
				$row['project_list'] = $project_list;
			}
			return $row;
        }
    }

}
