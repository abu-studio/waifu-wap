<?php



class groupbuy_goods_promotion
{
    function get_type(){
       return 'group';
    }
    function gen_url($goods_id,$promotion_id){
		$args=array($goods_id,null,null,$promotion_id);
		$sql = "SELECT a.start_time ,a.end_time FROM sdb_groupbuy_groupapply g LEFT JOIN sdb_groupbuy_activity a ON g.aid = a.act_id WHERE g.status = '2' AND a.act_open = 'true' AND g.gid = ".$goods_id;
		$result = app::get('groupbuy')->model('activity')->db->select($sql);
		$time = time();
		$start_time = $result[0]['start_time'];
		$end_time = $result[0]['end_time'];
		//团购活动结束商品显示正常商品页
		if($time<=$end_time&&$time>=$start_time){
			return app::get('site')->router()->gen_url( array('app'=>'groupbuy','ctl'=>'site_product','act'=>'index','args'=>$args) );
		}else{
			return app::get('site')->router()->gen_url( array('app'=>'b2c','ctl'=>'site_product','act'=>'index','args'=>$args) );
		}
    }
    function get_icon($p_type){
        return '';
    }
}
