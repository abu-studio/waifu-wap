<?php



class spike_goods_promotion
{
    function get_type(){
       return 'spike';
    }
    function gen_url($goods_id,$promotion_id){
       $args=array($goods_id,null,null,$promotion_id);
      return app::get('site')->router()->gen_url( array('app'=>'spike','ctl'=>'site_product','act'=>'index','args'=>$args) );
    }
    function get_icon($p_type){
        return '';
    }
}
