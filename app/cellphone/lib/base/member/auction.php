<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class cellphone_base_member_auction extends cellphone_cellphone{
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }

    /*会员中心我的出价相关接口*/
     function getmyprice(){
        $params = $this->params;

       //检查应用级必填参数
        $must_params = array(
            'session'=>'会员session',
			'act_id' =>'申请ID'
       );
        $this->check_params($must_params);
        $member=$this->get_current_member();
		$member_id = $member['member_id'];
		 if(!$member){
            $this->send(false,null,app::get('b2c')->_('该会员不存在'));
        }
        $mobj_activitylog = app :: get('auction')->model('activitylog');
        $data = $mobj_activitylog->db->select('select a.bid_price,a.bid_time from sdb_auction_activitylog as a  where a.act_id='.$params['act_id'].'  and a.bid_user='.$member_id);
		if($data){
	    $this->send(true,$data,app::get('b2c')->_('出价记录'));
		}else{
		$this->send(true,null,app::get('b2c')->_('没有数据'));
		}

	 }


	 /*会员中心我参加的竞拍相关接口*/
     function getmyauction(){
	 
	    $params = $this->params;

       //检查应用级必填参数
        $must_params = array(
            'session'=>'会员session',
       );
        $this->check_params($must_params);
      
        if($params['pageLimit']){
            $pagelimit=$params['pageLimit'];
        }else{
            $pagelimit=10;
        }

        if($params['npage']){
            $nPage=$params['npage'];
        }else{
            $nPage=1;
        }
		if($params['picSize']){
   	   	 $picSize = $params['picSize'];
   	   }else{
	     $picSize = "cs";
	   }
	    $member=$this->get_current_member();
        $member_id = $member['member_id'];
		if(!$member){
            $this->send(false,null,app::get('b2c')->_('该会员不存在'));
        }
        $mobj_activitylog = app :: get('auction')->model('activitylog');
        $aData = $mobj_activitylog->db->select('select distinct a.act_id,a.bid_user,b.gid,b.last_price,b.log_count,b.image_codeid,c.name,c.image_default_id from sdb_auction_activitylog as a  inner join sdb_auction_groupapply as b on a.act_id=b.id left join sdb_b2c_goods as c on b.gid=c.goods_id  where a.bid_user='.$member_id.' limit '.($nPage-1)*$pagelimit.','.$pagelimit);
        foreach($aData as $key=>&$val){
		$val['image_codeid'] = $this->get_img_url($val['image_codeid'],$picSize);
		$val['image_default_id'] = $this->get_img_url($val['image_default_id'],$picSize);
		}
        if($aData){
		
		$this->send(true,$aData,app::get('b2c')->_('商品列表'));

		}else{
		$this->send(true,null,app::get('b2c')->_('没有数据'));
		}
	 }

}