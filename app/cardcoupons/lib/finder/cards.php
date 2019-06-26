<?php
class cardcoupons_finder_cards {
	function __construct($app){
        $this->app = $app;
    }
	var $detail_basic = '基本信息';
	var $detail_basic_order = COLUMN_IN_HEAD;
	var $detail_goods = '关联产品';
	var $detail_goods_order = COLUMN_IN_HEAD;
	var $detail_passlist = '卡密列表';
	var $detail_passlist_order = COLUMN_IN_HEAD;

    var $column_control = '操作';
    var $column_price = '销售价';
    var $column_mktprice = '市场价';
    var $column_cost = '成本价';
    var $column_storee = '电子卡库存';
    var $column_store = '实体卡库存';    
	var $goods=array();
	function column_control($row){
		$card=kernel::single('cardcoupons_mdl_cards')->dump($row['card_id']);
		$return = '<a href="index.php?app=cardcoupons&ctl='.$_GET['ctl'].'_editor&act=edit&p[0]='.$card['goods_id'].'&finder_id='.$_GET['_finder']['finder_id'].'"  target="blank">'.app::get('b2c')->_('编辑').'</a> ';
		$return.= '<a href="index.php?app=cardcoupons&ctl='.$_GET['ctl'].'&act=setpass&card_id='.$card['card_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'].'"  target="dialog::{title:\''.app::get('cardcoupons')->_('创建卡密').'\', width:640, height:380}">'.app::get('b2c')->_('创建卡密').'</a>';
		
		return $return;
	}
	function column_price($row){
		$db=kernel::database();
		$sql="select goods.price,goods.cost,goods.store,goods.mktprice from sdb_cardcoupons_cards as cards left join sdb_b2c_goods as goods on cards.goods_id=goods.goods_id where cards.card_id='".$row['card_id']."' ";
		$card_goods=$db->select($sql);
		$this->goods=$card_goods[0];
		return $this->goods['price'];
	}
	function column_mktprice($row){
		$db=kernel::database();
		$sql="select goods.price,goods.cost,goods.store,goods.mktprice from sdb_cardcoupons_cards as cards left join sdb_b2c_goods as goods on cards.goods_id=goods.goods_id where cards.card_id='".$row['card_id']."' ";
		$card_goods=$db->select($sql);
		
		return $card_goods[0]['mktprice'];
	}
	function column_cost($row){
		
		$db=kernel::database();
		$sql="select goods.price,goods.cost,goods.store,goods.mktprice from sdb_cardcoupons_cards as cards left join sdb_b2c_goods as goods on cards.goods_id=goods.goods_id where cards.card_id='".$row['card_id']."' ";
		$card_goods=$db->select($sql);
		
		return $card_goods[0]['cost'];
	}
	function column_store($row){
		
		$db=kernel::database();
		//查询实体卡库存,一cards_pass表为准
		$sql="select card_id,type,count(card_pass_id) from sdb_cardcoupons_cards_pass where card_id ='".$row['card_id']."' and type='entity' and status = '0' and ex_status = 'true' and disabled='false' GROUP BY card_id,type";
		$card_goods=$db->select($sql);
		return $card_goods[0]['count(card_pass_id)']>0 ? $card_goods[0]['count(card_pass_id)']:0;
	}
	
	function column_storee($row){

	    $db=kernel::database();
	    //查询电子卡库存,一cards_pass表为准
	    $sql="select card_id,type,count(card_pass_id) from sdb_cardcoupons_cards_pass where card_id ='".$row['card_id']."' and type='virtual' and status = '0' and ex_status = 'true' and disabled='false' GROUP BY card_id,type";
	    $card_goods=$db->select($sql);
	    return $card_goods[0]['count(card_pass_id)']>0 ? $card_goods[0]['count(card_pass_id)']:0;
	}
	
	
	function detail_basic($cid){
		$good=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$cid),'*');
		$gid=$good['goods_id'];
        $render =  app::get('cardcoupons')->render();
        $o = kernel::single('b2c_mdl_goods');
        $goods=$o->getList(
            'name,
            bn,
            price,
			cost,
			mktprice,
            store,
            unit,
            brief,
            thumbnail_pic,
            udfimg,
            marketable,
            view_count,
            view_w_count,
            buy_count,
            buy_w_count,
            image_default_id,
            count_stat,
            comments_count,
            rank_count'
            ,array('goods_id'=>$gid));
        $goods = current($goods);
        $render->pagedata['goods'] = &$goods;
        $render->pagedata['is_pub'] = ($goods['marketable']!='false');
        $render->pagedata['url'] = app::get('site')->router()->gen_url(array('app'=>'cardcoupons','ctl'=>'site_product','full'=>1,'act'=>'index','arg'=>$gid));
        $render->pagedata['buy_w_count'] = $goods['buy_w_count'];
        $render->pagedata['view_w_count'] = $goods['view_w_count'];

        $render->pagedata['buy_count'] = $goods['buy_count'];
        $render->pagedata['view_count'] = $goods['view_count'];

        $render->pagedata['status'] = unserialize($goods['count_stat']);

        $goods_point = app::get('b2c')->model('comment_goods_point');
        $render->pagedata['goods_point'] = $goods_point->get_single_point($gid);
        return $render->fetch("admin/cards/detail/detail.html");
    }
	//关联产品
	function detail_goods($cid){
		$render =  app::get('cardcoupons')->render();
		$db=kernel::database();
		$goods_sql="SELECT cs.*,g.name ,g.bn,g.image_default_id as image ,g.price from sdb_cardcoupons_cards_solution as cs LEFT JOIN sdb_cardcoupons_cards as c on cs.card_id=c.card_id JOIN sdb_b2c_goods as g on cs.goods_id=g.goods_id  where c.card_id='".$cid."'";
		$card_goods=$db->select($goods_sql);
		$package_sql="SELECT cs.*,p.package_name as name ,p.package_code as bn,p.price,p.image from sdb_cardcoupons_cards_solution as cs LEFT JOIN sdb_cardcoupons_cards as c on cs.card_id=c.card_id JOIN sdb_physical_package as p on cs.services_id=p.package_id  where c.card_id='".$cid."';";
		$card_package=$db->select($package_sql);
		$data=array_merge($card_goods,$card_package);
		$render->pagedata['data']=$data;
		return $render->fetch("admin/cards/detail/goods.html");
		
	}
	
	function detail_passlist($cid){
		$render = $this->app->render();

		$ex_status_arr=array(
			'update'=>'待审核',
			'false'=>'审核失败',
			'true'=>'审核成功',
		);
		$render->pagedata['ex_status_arr']=$ex_status_arr;

		$status_arr=array(
			'-1'=>'已预售',
			'0'=>'未发放',
			'1'=>'已发放',
            '2'=>'已激活',
            '3'=>'已使用',
            '4'=>'已结算',
			'5'=>'已冻结',
		);
		$render->pagedata['status_arr']=$status_arr;

		$type_arr=array(
			'entity'=>'实体卡',
			'virtual'=>'电子码',
		);
		$render->pagedata['type_arr']=$type_arr;

		$disabled_arr=array(
			'false'=>'未失效',
			'true'=>'已失效',
		);
		$render->pagedata['disabled_arr']=$disabled_arr;
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$pass_list = $pass_obj->getList("*",array('card_id'=>$cid));

        $render->pagedata['pass_list']=$pass_list;
        return $render->fetch('admin/cards/pass_list.html');
    }
	
	
}