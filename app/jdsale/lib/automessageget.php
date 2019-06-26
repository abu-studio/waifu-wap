<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/6
 * Time: 15:54
 */

class jdsale_automessageget{
    function auto_getmessage(){
        $array = array(
            'type'=>'2,4,6,16,50',
        );
        do {
            $data = kernel::single('jdsale_api_area')->getMessage($array);
            if(!empty($data['result'])){
                foreach($data['result'] as $k=>$v){
                    if($v['type'] == 1){
                        //代表订单拆分变更  -- 按照现在情况已经不需要了
                        $this->split_orders($v);
                        continue;
                    }elseif($v['type'] == 2){
                        //商品销售价格变化--完成
                        $this->change_jdPrice($v);
                        continue;
                    }elseif($v['type'] == 4){
                        //商品上下架变更 --完成
                        $this->change_jdmarketable($v);
                        continue;
                    }elseif($v['type'] == 5){
                        //代表该订单已妥投 -- (买卖宝模式代表该订单已妥投到分拣中心)
                        $this->confirm_jdorders($v);
                        continue;
                    }elseif($v['type'] == 6){
                        //代表添加、删除商品池内商品  完成
                        $this->get_jdgoods($v);
                        continue;
                    }elseif($v['type'] == 10){
                        //代表订单取消 (不区分取消原因) --暂时不需要
                        $this->cancel_jdorders_nores($v);
                        continue;
                    }elseif($v['type'] == 11){
                        //申请开票信息  --完成
                        $this->jdbilling($v);
                        continue;
                    }elseif($v['type'] == 12){
                        //代表配送单生成(打包完成后推送，仅提供给买卖宝类型客户)  完成
                        $this->change_distribution($v);
                        continue;
                    }elseif($v['type'] == 13){
                        //换新订单生成(换新单下单后推送，仅提供给买卖宝类型客户)  完成
                        $this->jdcreateorder($v);
                        continue;
                    }elseif($v['type'] == 14){
                        //支付失败消息
                        $this->payment_fail($v);
                        continue;
                    }elseif($v['type'] == 15){
                        //7天未支付取消消息/未确认取消   --暂时不需要
                        $this->cancel_jdorders($v);
                        continue;
                    }elseif($v['type'] == 16){
                        //商品介绍及规格参数变更消息 ---完成
                        $this->change_jdgoods($v);
                        continue;
                    }elseif($v['type'] == 17){
                        //赠品促销变更消息 ---完成
                        $this->change_jdgift($v);
                        continue;
                    }elseif($v['type'] == 50){
                        //京东地址变更消息推送  ---完成
                        $this->change_area($v);
                        continue;
                    }
                }
            } else {
                break;
            }
            sleep(5);
        } while ( true );
    }

    private function split_orders($data){
        //拆单信息，现阶段业务逻辑，该拆单信息无用，暂时不做处理
    }

    private function change_jdPrice($data){
    if(empty($data)) return false;
    if($data['result']['skuId'] == "" || $data['result']['skuId'] === null){
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
        return false;
    }
    if(!is_int($data['result']['skuId'])){
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
        return false;
    }
    $array = array(
        'sku'=>$data['result']['skuId'],
    );
    $jddata_tmp = kernel::single('jdsale_api_goods')->getSellPrice($array);
	if(empty($jddata_tmp['result']) || $jddata_tmp['resultCode'] != '0000'){
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
		return false;
	}
	$jddata = $jddata_tmp['result'];

	if(!empty($jddata)){
        $goods_obj = app::get("b2c")->model("goods");
        $products_obj = app::get("b2c")->model("products");

        //查询价格接口，获取到的值如果为空，null 或者小于0是未上架商品
        if($jddata[0]['jdPrice'] == "" || $jddata[0]['price'] == "" || $jddata[0]['jdPrice'] === null || $jddata[0]['price'] === null || $jddata[0]['jdPrice'] <=0 ){
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            return false;
        }

        //同一套主数据，实物与图书商品都在此处理
		$goods_array = $goods_obj->dump(array('bn|tequal'=>(string)$jddata[0]['skuId'],'goods_kind'=>array('jdgoods','jdbook')),"goods_id,bn,price,agreed_price");

        //处理mysql查询结果出错问题
        if($goods_array['bn'] != (string)$jddata[0]['skuId']){
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            return false;
        }

		if(!empty($goods_array)){
            //由于本地价格储存按照2.000 做法所以 简单的乘以1000判断价格是否相等
            if($jddata[0]['jdPrice']*1000 == $goods_array['price']*1000 && $jddata[0]['price']*1000 == $goods_array['agreed_price']*1000){
                //两个价格都没有什么变化的话，直接调用京东的删除消息接口把该信息删除掉
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }else{
                $tmp_data = array(
                    'jdsku' => $data['result']['skuId'],
                    'sku' => (string)$jddata[0]['skuId'],
                    'bn' => $goods_array['bn'],
                    'bendi_price' => $goods_array['price'],
                    'jdPrice' =>  $jddata[0]['jdPrice'],
                    'price' => $jddata[0]['price'],
                    'time' => date("Y-d-m H:i:s",time()),
                );

                if($jddata[0]['jdPrice'] < $jddata[0]['price']){
                    kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                    return false;
                }

                $jdgoods = array(
                    'price' => $jddata[0]['jdPrice'],
                    'agreed_price' => $jddata[0]['price'],
                );
                //接口获取的价格和本地价格有出入，需要修改
                $goods_obj->update($jdgoods,array('goods_id'=>$goods_array['goods_id']));
                $jdproducts = array(
                    'price' => $jddata[0]['jdPrice'],
                );
                $products_obj->update($jdproducts,array('goods_id'=>$goods_array['goods_id']));
                //difference
                if($goods_array['goods_id']!="" && $goods_array['price']!="" && $jddata[0]['jdPrice']!=""){
                    $diff_array = array(
                        "goods_id"=>$goods_array['goods_id'],
                        "old_price"=>$goods_array['price'],
                        "new_price"=>$jddata[0]['jdPrice'],
                        "goods_kind"=>'jdgoods'
                    );
                    $obj_diffprice = app::get("jdsale")->model("diffprice");
                    $obj_diffprice->save($diff_array);
                }
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }
        }else{
            //查询该商品在本地没有信息
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
        }
    }


}

    private function  change_jdmarketable($data){
        if(empty($data)) return false;
        $array = array(
            'sku'=>$data['result']['skuId'],
        );
        $goods_obj = app::get("b2c")->model("goods");
        $products_obj = app::get("b2c")->model("products");
        $jddata_tmp = kernel::single('jdsale_api_goods')->queryProductState($array);
		
		if(empty($jddata_tmp['result']) || $jddata_tmp['resultCode'] != '0000'){
			return false;
		}
		$jddata = $jddata_tmp['result'];

        if(!empty($jddata)){
            //同一套主数据，实物与图书商品都在此处理
            $goods_array = $goods_obj->dump(array('bn|tequal'=>(string)$jddata[0]['sku'],'goods_kind'=>array('jdgoods','jdbook')),"goods_id,marketable,marketable_allow");
            if(!empty($goods_array)){
                //接口消息上架的话，就需要判断手动那边是否下架记录，如果下架记录存在，该消息不予处理
                if($jddata[0]['state'] == 1){
                    if($goods_array['marketable_allow'] == 'false'){
                        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                        return;
                    }

                }
                $goods_array['status'] = ($goods_array['status'] == 'false') ? 0 : 1;
                if($goods_array['status'] != $jddata[0]['state']){
                    $jdgoods = array(
                        'marketable'=>($jddata[0]['state'] == 0) ? 'false' : 'true',
                    );

                    $goods_obj->update($jdgoods,array('goods_id'=>$goods_array['goods_id'],'goods_kind'=>array('jdgoods','jdbook')));
                    $products_obj->update($jdgoods,array('goods_id'=>$goods_array['goods_id']));
                    kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                }else{
                    kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                }
            }else{
                //该商品在本地没有查询到
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }
        }
    }

    private function change_jdgoods($data){
        if(empty($data)) return false;
        $array = array(
            'sku'=>$data['result']['skuId'],
        );
        $goods_obj = app::get("b2c")->model("goods");
        $jddata_tmp = kernel::single('jdsale_api_goods')->queryProductDetail($array);

		if(empty($jddata_tmp['result']) || $jddata_tmp['resultCode'] != '0000'){
			return false;
		}
		$jddata = $jddata_tmp['result'];
		
		if(!empty($jddata)){

            //根据获取的商品详细数据确定使用实物还是图书类来继续处理
            if( strlen(strval($jddata['sku'])) == 8 ){
                $jdGoodsLib = kernel::single('jdsale_goods_importbook');
            }else{
                $jdGoodsLib = kernel::single('jdsale_goods_import');
            }
            //构建商品品牌信息 brandName
            $brand_id = $jdGoodsLib->buildBrand($jddata['brandName']);
            //构建商品分类信息 category
            $ret_cat = $jdGoodsLib->buildCategory($jddata['category']);
            $jddata['category'] = $ret_cat;
            $jddata['brand_id'] = $brand_id;
            //保存商品和货品信息
            $goods_id = $goods_obj->dump(array('bn|tequal'=>(string)$jddata['sku'],'goods_kind'=>array('jdgoods','jdbook')),"goods_id,bn");

            //防止mysql数据读错情况?
            if($goods_id['bn'] != (string)$jddata['sku']){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                return false;
            }

            $jddata['goods_id'] = $goods_id['goods_id'];
            $goods_array = $jdGoodsLib->updateGoodsData($jddata);
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));

        }

    }

    private function cancel_jdorders_nores($data){

    }

    private function get_jdgoods($data){
        //代表添加、删除商品池内商品
        if(empty($data)) return false;

        if($data['result']['state'] == 1){
            //添加商品
            if($data['result']['skuId'] == "" || $data['result']['skuId'] === null){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                return false;
            }
            if(!is_int($data['result']['skuId'])){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                return false;
            }
            $goods = app::get("b2c")->model("goods")->dump(array('bn|tequal'=>(string)$data['result']['skuId'],'goods_kind'=>array('jdgoods','jdbook')),"goods_id,bn");
            if ( $goods ) {
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                return false;
            }

            $array = array(
                'sku'=>$data['result']['skuId'],
            );

            $jddata_tmp = kernel::single('jdsale_api_goods')->queryProductDetail($array);

			if(empty($jddata_tmp['result']) || $jddata_tmp['resultCode'] != '0000'){
				return false;
			}
			$jddata = $jddata_tmp['result'];			
            if(!empty($jddata)){
                //根据获取的商品详细数据确定使用实物还是图书类来继续处理
                if( strlen(strval($jddata['sku'])) == 8 ){
                    $jdGoodsLib = kernel::single('jdsale_goods_importbook');
                    $jddata['shopID'] = app::get('site')->getConf('jdbook.shopId') ? app::get('site')->getConf('jdbook.shopId'): '';
                }else{
                    $jdGoodsLib = kernel::single('jdsale_goods_import');
                    $jddata['shopID'] = app::get('site')->getConf('jdsale.shopId') ? app::get('site')->getConf('jdsale.shopId'): '';
                }
                //构建商品品牌信息 brandName
                $brand_id = $jdGoodsLib->buildBrand($jddata['brandName']);
                //构建商品分类信息 category
                $ret_cat = $jdGoodsLib->buildCategory($jddata['category']);
                $jddata['category'] = $ret_cat;
                $jddata['brand_id'] = $brand_id;
		        $jddata['message_id'] = $data['id'];

                $goods_array = $jdGoodsLib->saveGoodsData($jddata);
		        if($goods_array){
		            $jdGoodsLib->saveImageAttach($jddata['sku'],$goods_array);
                    kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
                }
            }
        }elseif($data['state'] == 2){
            //删除商品, 只要将改商品下架即可
            $goods_obj = app::get("b2c")->model("goods");
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            $goods_obj->update(array('marketable'=>'false'),array('bn|tequal'=>(string)$data['result']['skuId'],'goods_kind'=>array('jdgoods','jdbook')));
        }else{
            //如果还有不知道的类型默认删除掉
            kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
        }

    }

    private function confirm_jdorders($data){
        //代表该订单已妥投
    }

    private function payment_fail($data){
		
    }


    private function cancel_jdorders($data){

    }

    private function change_area($data){
        //更换地址库信息
        if(empty($data)) return false;
        /*
            [
                {
                    "id": 1468773,
                    "result": {
                        "areaId": 36151,  京东地址编码
                        "areaName": "qunge_test",  京东地址名称
                        "parentId": 1930,  父京东ID编码
                        "areaLevel": 5,  地址等级(行政级别：国家(1)、省(2)、市(3)、县(4)、镇(5))
                        "operateType": 3  操作类型(插入数据为1，更新时为2，删除时为3)
                    },
                    "time": "2015-12-09 16:49:59",
                    "type": 50
                }
            ]
        */
        $jdsale_regions_operation = kernel::single('jdsale_regions_operation');
        $msg = "";
        if($data['result']['operateType'] == 1){
            //插入数据
            $aData['area_id']     = $data['result']['areaId'];
            $aData['local_name']  = $data['result']['areaName'];
            $aData['p_region_id'] = $data['result']['parentId'];
            if($jdsale_regions_operation->insertDlArea($aData,$msg)){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }
        }elseif($data['result']['operateType'] == 2){
            //更新数据
            $aData['area_id']     = $data['result']['areaId'];
            $aData['local_name']  = $data['result']['areaName'];
            $aData['p_region_id'] = $data['result']['parentId'];
            if($jdsale_regions_operation->updateDlArea($aData,$msg)){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }
        }elseif($data['result']['operateType'] == 3){
            //删除数据
            $regionId = $data['result']['areaId'];
            if($jdsale_regions_operation->toRemoveArea($regionId)){
                kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
            }
        }



    }

    private function jdcreateorder($data){
        ////换新订单生成（换新单下单后推送，仅提供给买卖宝类型客户）
        if(empty($data)) return false;
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
    }

    private function jdbilling($data){
        //申请开票信息  --完成
        if(empty($data)) return false;
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
    }

    private function change_distribution($data){
        //代表配送单生成（打包完成后推送，仅提供给买卖宝类型客户）
        if(empty($data)) return false;
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
    }

    private function change_jdgift($data){
        //京东赠品不属于此次开发范畴，所以屏蔽掉
        if(empty($data)) return false;
        kernel::single('jdsale_api_area')->delMessage(array('id'=>$data['id']));
    }

}