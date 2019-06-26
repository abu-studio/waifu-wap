<?php
/**
 * @Author:      zyp
 * @Email:       
 * @DateTime:    2019-04-28 15:48:00
 * @Description: 根据kv中存储的skuID去获取京东那边的价格并更新本地 
 */


class jdsale_getjdgoodsprice{


	public function getJdGoodsPrice(){
		$this->_getJdGoods();
		$this->_getJdGoodsBook();
	}

	//一般商品
	function _getJdGoods(){
		base_kvstore::instance('jdsaleNeedUpdataAgreedPrice')->fetch('normal', $skus);
		if (empty($skus)){
			return fasle;
		}
		base_kvstore::instance('jdsaleNeedUpdataAgreedPrice')->delete('normal');
		$this->_getJdGoodsPrice($skus,'normal');
	}

	//图书类商品
	function _getJdGoodsBook(){
		base_kvstore::instance('jdsaleNeedUpdataAgreedPrice')->fetch('book', $skus);
		if (empty($skus)){
			return fasle;
		}
		base_kvstore::instance('jdsaleNeedUpdataAgreedPrice')->delete('book');
		$this->_getJdGoodsPrice($skus,'book');
	}

	//获取京东价格，与本地对照，有差异则更新（主要参照jdsale_automessageget->change_jdPrice）
	//目前只更新京东协议价
	//$sku = array(bn=>agreed_price), $jdgoodsKind = 'normal'/'book'
	function _getJdGoodsPrice($skus,$jdgoodsKind){
		// $skusArr = json_decode($skus,true);
		$tmp_sku_arr = array_chunk($skus, 100, true);//查询可售接口支持100个批量查询
		foreach ($tmp_sku_arr as $key => $value) {
			foreach ($value as $k => $v) {
			 	$querySkus[] = $k;
			 } 
			 $querySkuStr = implode(',', $querySkus);
			 
			 $jddata_tmp = kernel::single('jdsale_api_goods')->getSellPrice(array('sku' => $querySkuStr),$jdgoodsKind);
			
		 	if(empty($jddata_tmp['result']) || $jddata_tmp['resultCode'] != '0000'){
				return false;
			}
			$jddataArr = $jddata_tmp['result'];

			if(!empty($jddataArr)){
				foreach ($jddataArr as $key => $jddata) {

			        $goods_obj = app::get("b2c")->model("goods");
			        $products_obj = app::get("b2c")->model("products");

			        //查询价格接口，获取到的值如果为空，null 或者小于0是未上架商品
			        if($jddata['jdPrice'] == "" || $jddata['price'] == "" || $jddata['jdPrice'] === null || $jddata['price'] === null || $jddata['jdPrice'] <=0 ){
			        
			            continue;
			        }

					$goods_array = $goods_obj->dump(array('bn|tequal'=>(string)$jddata['skuId']),"goods_id,bn,price,agreed_price");

			        //处理mysql查询结果出错问题
			        if($goods_array['bn'] != (string)$jddata['skuId']){
			            continue;
			        }

					if(!empty($goods_array)){
			            //由于本地价格储存按照2.000 做法所以 简单的乘以1000判断价格是否相等
			            if($jddata['jdPrice']*1000 == $goods_array['price']*1000 && $jddata['price']*1000 == $goods_array['agreed_price']*1000){
			                //两个价格都没有什么变化的话，直接调用京东的删除消息接口把该信息删除掉
			            }else{

			                if($jddata['jdPrice'] < $jddata['price']){
			                    continue;
			                }

			                $jdgoods = array(
			                    // 'price' => $jddata['jdPrice'],
			                    'agreed_price' => $jddata['price'],
			                );
			                //接口获取的价格和本地价格有出入，需要修改
			                $goods_obj->update($jdgoods,array('goods_id'=>$goods_array['goods_id']));
			                // $jdproducts = array(
			                //     'price' => $jddata['jdPrice'],
			                // );
			                // $products_obj->update($jdproducts,array('goods_id'=>$goods_array['goods_id']));
			                //difference
			                // if($goods_array['goods_id']!="" && $goods_array['price']!="" && $jddata['jdPrice']!=""){
			                //     $diff_array = array(
			                //         "goods_id"=>$goods_array['goods_id'],
			                //         "old_price"=>$goods_array['price'],
			                //         "new_price"=>$jddata['jdPrice'],
			                //         "goods_kind"=>($jdgoodsKind == 'normal')?'jdgoods':'jdbook',
			                //     );
			                //     $obj_diffprice = app::get("jdsale")->model("diffprice");
			                //     $obj_diffprice->save($diff_array);
			                // }
			            
			            	$logData = array(
			            			'goods_id' => $goods_array['goods_id'],
			            			'bn' => $jddata['skuId'],
			            			// 'old_price' => $goods_array['price'],
			            			'old_agreed_price' => $goods_array['agreed_price'],
			            			// 'new_price' => $jddata['jdPrice'],
			            			'new_agreed_price' => $jddata['price'],
			            			'time' => date("Y-d-m H:i:s",time()),
			            		);
			            	error_log("=-=-=-log=-=-=-=-=-".PHP_EOL.var_export($logData,1).PHP_EOL,3,DATA_DIR."/".date('Ymd',time())."_update_jd_price.log");
			            }
			        }else{
			            //查询该商品在本地没有信息
			        	continue;
			        }
			    }
			}

		}
	}
}