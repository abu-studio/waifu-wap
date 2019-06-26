<?php


class b2c_finder_goods{
    var $detail_basic = '基本信息';
    var $column_control = '操作';


    function __construct($app){
        $this->app = $app;
    }


    var $column_goods_pic = "缩略图";
	var $column_gross_profit = "销售毛利率";
	
    var $column_goods_pic_order = COLUMN_IN_HEAD;
    var $column_goods_pic_order_field = 'image_default_id';
    function column_goods_pic($row){
        $o =  app::get('b2c')->model('goods');
        $g =$o->db_dump(array('goods_id'=>$row['goods_id']),'image_default_id');
        $img_src = base_storager::image_path($g['image_default_id'],'s' );
        if(!$img_src)return '';
        return "<a href='$img_src' class='img-tip pointer' target='_blank' onmouseover='bindFinderColTip(event);'><span>&nbsp;pic</span></a>";
    }
	
	//销售毛利 
	function column_gross_profit($row){
		$goodsObj = $this->app->model('goods');
		$goodsDate = $goodsObj->getRow('price,agreed_price,goods_kind',array('goods_id'=>$row['goods_id'],'cat_id'=>array('0')));
		if(in_array($goodsDate['goods_kind'] , array('jdgoods' , 'jdbook'))){
            return  (sprintf("%.3f", ($goodsDate['price']-$goodsDate['agreed_price'])/$goodsDate['price'])*100).'%';
        }else{
			return false;
		}
	}

    function column_control($row){
		if(in_array($row['goods_kind'] , array('jdgoods' , 'jdbook'))){
			$returnValue ='<a href="index.php?app=b2c&ctl=admin_goods_editor&act=editprice&goods_id='.$row['goods_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'].'"  target="dialog::{title:\''.app::get('b2c')->_('编辑价格').'\', width:300, height:200}">'.app::get('b2c')->_('编辑价格').'</a>';
			
		}else{
			$returnValue = '<a href="index.php?app=b2c&ctl=admin_goods_editor&act=edit&p[0]='.$row['goods_id'].'&finder_id='.$_GET['_finder']['finder_id'].'"  target="blank">'.app::get('b2c')->_('编辑').'</a>';
			$aGoods = app::get('b2c')->model('goods')->getList('spec_desc,marketable_allow',array('goods_id'=>$row['goods_id']));
			if($aGoods && $aGoods[0]['spec_desc']){
				$returnValue .= ' <a href="index.php?app=b2c&ctl=admin_products&act=set_spec_index&nospec=0&goods_id='.$row['goods_id'].'"  target="blank">'.app::get('b2c')->_('编辑货品').'</a>';
			}
			
			$goods_id_arr = app::get('b2c')->model('goods_marketable_application')->count(array('goods_id'=>$row['goods_id'],'status'=>'0'));
			if($aGoods && $goods_id_arr>0&&$_GET['view']!='5'){
				$returnValue .= ' <a href="index.php?app=b2c&ctl=admin_products&act=set_audit&goods_id='.$row['goods_id'].'&finder_id='.$_GET['_finder']['finder_id'].'"  target="blank">'.app::get('b2c')->_('审核').'</a>';
			}
			if($_GET['view']=='5'){
				$returnValue .= ' <a href="index.php?app=b2c&ctl=admin_goods&act=prices_allow&type=cost&goods_id='.$row['goods_id'].'&finder_id='.$_GET['_finder']['finder_id'].'"  target="blank">'.app::get('b2c')->_('审核').'</a>';
			}
		}
       
        return $returnValue;
    }
    function detail_basic($gid){

        $render =  app::get('b2c')->render();
        $o = $render->app->model('goods');
        $goods=$o->getList(
            'name,
            bn,
            price,
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
			goods_kind,
            rank_count'
            ,array('goods_id'=>$gid));

        $goods = current($goods);
        $render->pagedata['goods'] = &$goods;
        $render->pagedata['is_pub'] = ($goods['marketable']!='false');
		if($goods['goods_kind'] =='jdgoods' || $goods['goods_kind'] =='jdbook'){
			$render->pagedata['url'] = app::get('site')->router()->gen_url(array('app'=>'jdsale','ctl'=>'site_product','full'=>1,'act'=>'index','arg'=>$gid));
		}else{
			$render->pagedata['url'] = app::get('site')->router()->gen_url(array('app'=>'b2c','ctl'=>'site_product','full'=>1,'act'=>'index','arg'=>$gid));
		}
        $render->pagedata['buy_w_count'] = $goods['buy_w_count'];
        $render->pagedata['view_w_count'] = $goods['view_w_count'];

        $render->pagedata['buy_count'] = $goods['buy_count'];
        $render->pagedata['view_count'] = $goods['view_count'];

        $render->pagedata['status'] = unserialize($goods['count_stat']);

        $goods_point = app::get('b2c')->model('comment_goods_point');
        $render->pagedata['goods_point'] = $goods_point->get_single_point($gid);
        /*
        $today = $this->day(time());
        $view_chardata=array();
        $buy_chardata=array();
        foreach(range($today-14,$today) as $day){
            $view_chardata[$day]=intval($render->pagedata['status']['view'][$day]);
            $buy_chardata[$day]=intval($render->pagedata['status']['buy'][$day]);
        }

        $render->pagedata['view_chart'] = $this->_linechart($view_chardata);
        $render->pagedata['buy_chart'] = $this->_linechart($buy_chardata);
        $imageDefault = app::get('image')->getConf('image.set');
        $render->pagedata['defaultImage'] = $imageDefault['S']['default_image'];//print_R($render->pagedata['defaultImage']);exit;
*/

        return $render->fetch('admin/goods/detail/detail.html');
    }


     function day($time=null){
        if(!isset($GLOBALS['_day'][$time])){
            return $GLOBALS['_day'][$time] = floor($time/86400);
        }else{
            return $GLOBALS['_day'][$time];
        }
     }

/*
     function _linechart($chardata){

        $max = max(10,intval(max($chardata)*1.25));

        $w = 300;
        $y = array();
        $xday = array();
        $i=0;
        $xmonty = array();
        $lastMonth = null;
        $count = count($chardata);
        $xmark=array('B,76A4FB,0,0,0');
        $color_1 = '000099';

        foreach($chardata as $d=>$v){

            $d = $d*3600*24;

            if($lastMonth!=($month=date('Y.m',$d))){
                $lastMonth = $month;
                $xmonth[intval($i*(100/$count))] = $month;
                if($i>0)$xmark[] = 'V,'.$color_1.',0,'.$i.',1';
            }
            $i++;
            $xday[] = date('d',$d);
        }
        return 'chs='.$w.'x100&chd=t:'.implode(',',$chardata).
        '&chxt=x,y,x,r&chco=224499&chxl=0:|'.implode('|',$xday).'|1:|0|'.round($max/2).'|'.$max.'|2:|'.implode('|',$xmonth).
        '|3:|0|'.round($max/2).'|'.$max.'&cht=lc&chds=0,'.$max.'&chxp=2,'.implode(',',array_keys($xmonth)).'&chxs=2,'.$color_1.',13&chm='.implode('|',$xmark).'&chg=5,25,1';
     }


*/

}
