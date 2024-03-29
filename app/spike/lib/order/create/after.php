<?php
class spike_order_create_after{
    
    function __construct($app)
    {
        $this->app = $app;
    }

    public function get_order(){
        return 95;
    }

    public function generate(&$item){
        $object = kernel::single('spike_cart_process_goods');
        $memberbuy = app::get('spike')->model('memberbuy');
        $business = app::get('spike')->model('spikeapply');
        $gObj = app::get('b2c')->model('goods');
        foreach($item['order_objects'] as $key=>$value){
            $inAct = $object->checkgoods($value['goods_id']);
            if($inAct){
                $data['gid'] = $value['goods_id'];
                $data['aid'] = $inAct['aid'];
                $data['member_id'] = $item['member_id'];
                $data['order_id'] = $item['order_id'];
                $data['nums'] = $value['quantity'];
                $rs = $memberbuy->insert($data);
                if($inAct['nums']){
                    $arr['remainnums'] = intval($inAct['remainnums'])-intval($value['quantity']);
                    $re = $business->update($arr,array('id'=>$inAct['id']));
                    $garr = array('store_freeze'=>$arr['remainnums']);
                    $gObj->update($garr,array('goods_id'=>$data['gid']));
                }
            }
        }
        return true;
    }
}