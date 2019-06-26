<?php

class fastbuy_cart_fastbuy_goods{
	/** 
	* return 立即购买cart
	*/
	public function get_fastbuy_arr($goods,$coupon,&$aResult){
		$oCartGoods=kernel::single('fastbuy_cart_object_goods');
		$oCartCoupon=kernel::single('fastbuy_cart_object_coupon');
        
		//$oCartGoods->no_database=true;
		if(is_array($goods) && !empty($goods)){
			$goods_status=$oCartGoods->add_object($goods); //添加商品
			$aResult['object']['goods']= $oCartGoods->getAll(true);
			
        }
		if(is_array($coupon) && !empty($coupon)){
		    $coupon_status=$oCartCoupon->add_object($coupon); //添加优惠券
		    $aResult['object']['coupon']=$oCartCoupon->getAll(true); //添加优惠券
		}
		
		$config=array();
		$oCartGoods->count($aResult);
		//商品促销
		$goods_process=kernel::single('b2c_cart_process_prefilter');
		$goods_process->process($aData,$aResult,$config);

		//显示抢购促销
		$timebuy_process=kernel::single('timedbuy_cart_process_goods');
		$timebuy_process->process($aData,$aResult,$config,'timedbuy');

		//订单促销
		$order_process=kernel::single('b2c_cart_process_postfilter');
		$order_process->process($aData,$aResult,$config);
		

		//店铺信息
		$business_process=kernel::single('business_cart_process_business');
		$business_process->process($aData,$aResult,$config);
		
	}


    /**
     * return 水电煤模拟购物车行为
     * todo 去除库存判断，去除上下架判断
     */
    public function get_shuidianmei_arr($goods,$coupon,&$aResult){
        $oCartGoods=kernel::single('fastbuy_cart_object_goods');
        $oCartCoupon=kernel::single('fastbuy_cart_object_coupon');

        if(is_array($goods) && !empty($goods)){
            $goods_status=$oCartGoods->add_object($goods); //添加商品
            $aResult['object']['goods']= $oCartGoods->getAll(true,true);

        }

        if(is_array($coupon) && !empty($coupon)){
            $coupon_status=$oCartCoupon->add_object($coupon); //添加优惠券
            $aResult['object']['coupon']=$oCartCoupon->getAll(true); //添加优惠券
        }

        $config=array();
        $oCartGoods->count($aResult);
        //商品促销
        $goods_process=kernel::single('b2c_cart_process_prefilter');
        $goods_process->process($aData,$aResult,$config);

        //显示抢购促销
        $timebuy_process=kernel::single('timedbuy_cart_process_goods');
        $timebuy_process->process($aData,$aResult,$config,'timedbuy');

        //订单促销
        $order_process=kernel::single('b2c_cart_process_postfilter');
        $order_process->process($aData,$aResult,$config);

        //店铺信息
        $business_process=kernel::single('business_cart_process_business');
        $business_process->process($aData,$aResult,$config);

    }


    /**
     * return 大客户订单模拟购物车流程()
     * todo 去除库存判断,去除上下架判断
     */
    function get_sfsccreateorder_arr($goods,$coupon,&$aResult){
        $oCartGoods=kernel::single('fastbuy_cart_object_goods');
        $oCartCoupon=kernel::single('fastbuy_cart_object_coupon');

        if(is_array($goods) && !empty($goods)){
            $goods_status=$oCartGoods->add_object($goods); //添加商品
            $aResult['object']['goods']= $oCartGoods->getAll(true,true);

        }

        if(is_array($coupon) && !empty($coupon)){
            $coupon_status=$oCartCoupon->add_object($coupon); //添加优惠券
            $aResult['object']['coupon']=$oCartCoupon->getAll(true); //添加优惠券
        }

        $config=array();
        $oCartGoods->count($aResult);
        //商品促销
        $goods_process=kernel::single('b2c_cart_process_prefilter');
        $goods_process->process($aData,$aResult,$config);

        //显示抢购促销
        $timebuy_process=kernel::single('timedbuy_cart_process_goods');
        $timebuy_process->process($aData,$aResult,$config,'timedbuy');

        //订单促销
        $order_process=kernel::single('b2c_cart_process_postfilter');
        $order_process->process($aData,$aResult,$config);

        //店铺信息
        $business_process=kernel::single('business_cart_process_business');
        $business_process->process($aData,$aResult,$config);
    }

}