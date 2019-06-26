<?php
/**
 * 导入京东商品信息的工具类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/15
 * Time: 16:51
 */

class jdsale_goods_importbook {

    public static $image_url_max = 'http://img13.360buyimg.com/n0/';
	public static $image_url_mid = 'http://img13.360buyimg.com/n1/';
	public static $image_url_min = 'http://img13.360buyimg.com/n5/';
	public static $image_url_n3 = 'http://img13.360buyimg.com/n3/';
    /**
     * 构造方法
     * @param object 当前应用app的对象
     * @return null
     */
    function __construct($app){
        $this->app = $app;
        $this->db = kernel::database();
        $this->api_goods = kernel::single('jdsale_api_goods');
        $this->gift_arr = array('赠品','优惠券');
    }

    /**
     *  京东商品数据初始化
     */
    public function initialData($shopID){
        $retval = true;
        $is_initial = 0 ;//app::get('jdsale')->getConf('goodsbookdata.isinitial');

        if (isset($is_initial) && $is_initial == '1'){
            return $retval;
        }


		$conn=@file_get_contents(ROOT_DIR.'/initialization_jdgoods.txt');
        if($conn){
            $conn_array1 = explode(',',$conn);
            // if(!empty($conn_array1)){
            //     foreach($conn_array1 as $chi_k=>$chi_v){
            //         if($chi_v && $chi_v){

            //         }
            //     }
            // }
        }else{
            $conn_array1 = array();
        }
        //jdbook_pagenum.txt为所有图书类的商品池
        $conn_init=@file_get_contents(ROOT_DIR.'/jdgoods_pagenum.txt');
        if($conn_init){
            $conn_array2 = explode('|',$conn_init);
        }else{
            $conn_array2 = array();
        }
        //$conn_array为将被过滤不作处理的商品池 by zyp@2018-12-20
        $conn_array = array_merge($conn_array1,$conn_array2);


        // 调用1.1.1 获取商品池编号
        // 过滤掉一部分 类别
        $pageNum = $this->api_goods->queryPageNum('book');
		 if(!empty($pageNum['result']) && !empty($conn_array)){
            foreach($pageNum['result'] as $p_k=>$p_v){
                foreach($conn_array as $c_k=>$c_v){
                    if($c_v && $c_v == $p_v['name']."_".$p_v['page_num']){
                        unset($pageNum['result'][$p_k]);
                    }
                }
            }

        }

        foreach($pageNum['result'] as $k1 => $v1){
            error_log("=-=-=-=-商品池=-=-=-=-".var_export($v1,1).PHP_EOL,3,DATA_DIR."/jdimportbook-pagenum.log");
            //商品池与三级分类ctaId是一样的，那根据商品池编号获取一级分类，如果是图书，就放到图书里，其他放到普通实物
            $rootCat = $this->getRootCatInfoByThirdCatId($v1['page_num']);
            if(!$rootCat || $rootCat['name'] != '图书')
            {
                error_log("=-=-=-=-商品池=-=-=-=-".PHP_EOL.var_export($v1,1)."====一级分类====".$rootCat['name'].PHP_EOL,3,DATA_DIR."/jdimportbook.log");
                unset($rootCat);
                continue;
            }else{
                unset($rootCat);
            }
            //调用1.1.2获取商品编号
            $params1 = array('pageNum' => $v1['page_num']);
            $sku_result_all =$this->api_goods->querySku($params1 , 'book');

            if(empty($sku_result_all)){
                continue;
            }

            //调用是否商品可售接口
            $tmp_sku_arr = array_chunk($sku_result_all, 100);//查询可售接口支持100个批量查询
            foreach ($tmp_sku_arr as $tk1 => $tv1) {
                $tmp_sku ='';
                $tmp_sku = implode(',', $tv1);
                $tmp_param = array('skuIds'=>$tmp_sku);
                $sku_status = $this->api_goods->checkProduct($tmp_param,'book');
                $sku_status = $sku_status['result'];
                foreach ($sku_status as $tk2 => $tv2) {
                    if ($tv2['saleState'] == '1'){
                        $sku_result[] = $tv2['skuId'];//如果可售为1则记录到sku_result中进行后续处理
                    }
                }
            }
            unset($sku_result_all,$tmp_sku_arr);
            if(empty($sku_result)){
                continue;
            }
            //

            foreach($sku_result as $k2 => $v2){
                error_log("=-=-=-=-sku=-=-=-=-".PHP_EOL.var_export($v2,1).PHP_EOL,3,DATA_DIR."/jdimportbook.log");
                $params2 = array('sku' =>$v2 );
                //$params2 =  array('sku' =>'11988821' );
                //$sku_id = $sku_detail['sku'];
                //判断是否是8位SKU商品

                //if (strlen(strval($sku_id)) == 8){
                //    $is_book = true;
                //    error_log(var_export($sku_detail,1)."\n\r",3,ROOT_DIR.'/shaojun.txt');
                //
                //}else{
                //    $is_book = false;
                //}

                //调用1.1.3获取商品详细信息
                $sku_detail_result  =$this->api_goods->queryProductDetail($params2 , 'book');
                $sku_detail = $sku_detail_result['result'];
                if (empty($sku_detail)){
                    continue;
                }
                if ($sku_detail['skuType'] != 'book') {
                    continue;
                }

                //构建商品分类信息 category
                $ret_cat = $this->buildCategory($sku_detail['category']);
                //判断分类如果是赠品类型，需过滤该产品,不导入
                if ($ret_cat === -1){
                    continue;
                }

                //构建商品品牌信息 brandName
                if(empty($sku_detail['brandName'])){
                    $brand_id = $this->buildBrand($sku_detail['Brand']);
                }else{
                    $brand_id = $this->buildBrand($sku_detail['brandName']);
                }
                //获取商品评价信息
                $averagescore = $this->api_goods->queryProductComment(array('sku' =>$v2) , 'book');
                
                $sku_detail['category'] = $ret_cat;
                $sku_detail['shopID'] = $shopID;
                $sku_detail['brand_id'] = $brand_id;
                $sku_detail['averagescore'] = $averagescore['result'][0]['averageScore'] ? $averagescore['result'][0]['averageScore'] : 0;

                //保存商品和货品信息
                $goods_id = $this->saveGoodsData($sku_detail);
                //获取并保存图片信息
                $this->saveImageAttach($sku_detail['sku'],$goods_id);

            }
			error_log($v1['name']."_".$v1['page_num'].",",3,ROOT_DIR.'/initialization_jdgoods.txt');
        }
        return $retval;
    }

    /**
     * 构建商品分类信息 category
     * @param $category
     * @return mixed
     */
    public function buildCategory($category){

        $catArr = explode(';',$category);
        $ret_cat = $catArr[2];
        //不能只检测一级分类。。。
        if($this->catIsExists($catArr[0]) && $this->catIsExists($catArr[2])){
            return  $ret_cat;
        }

        $catInfo_level_1_arr = $this->api_goods->getCategory(array('cid'=>$catArr[0]) , 'book');
        $catInfo_level_1 = $catInfo_level_1_arr['result'];
        if ( in_array($catInfo_level_1['name'],$this->gift_arr)){
            return -1;
        }
        $catInfo_level_2_arr = $this->api_goods->getCategorys(
            array('pageNo'=>1,'pageSize'=>5000,'parentId'=>$catInfo_level_1['catId'],'catClass'=>'1') , 'book');
        $catInfo_level_2 = $catInfo_level_2_arr['result'];

        if($catInfo_level_2['totalRows']>0){
            $catInfo_level_1['child_count'] = $catInfo_level_2['totalRows'];
        }
        $cat_path_1 = ','.$catInfo_level_1['catId'].',';

        $this->saveCategory($catInfo_level_1,0);

        foreach($catInfo_level_2['categorys'] as $k1 => $v1){
            if ( in_array($v1['name'],$this->gift_arr)){
                continue;
            }

            $catInfo_level_3_arr = $this->api_goods->getCategorys(
                array('pageNo'=>1,'pageSize'=>5000,'parentId'=>$v1['catId'],'catClass'=>'2') , 'book');
            $catInfo_level_3 = $catInfo_level_3_arr['result'];

            if($catInfo_level_3['totalRows']>0){
                $v1['child_count'] = $catInfo_level_3['totalRows'];
            }
            $this->saveCategory($v1,1,$cat_path_1);
            $cat_path_2 = $cat_path_1.$v1['catId'].',';

            foreach($catInfo_level_3['categorys'] as $k2 => $v2){
                if (in_array($v2['name'],$this->gift_arr)){
                    continue;
                }
                $this->saveCategory($v2,2,$cat_path_2);
            }
        }

        return  $ret_cat;
    }

    private function catIsExists($cat_id){
        $mdl_goods_cat = $this->app->model('goods_cat');
        $c = $mdl_goods_cat->count(array('jd_cat_id'=>$cat_id));
        if ($c > 0)
            return true;
        else
            return false;
    }

    private function saveCategory($catInfo,$level,$cat_path=''){
        if ($this->catIsExists($catInfo['catId'])){
            return false;
        }

        $cat = array(
            'jd_cat_id' => $catInfo['catId'],
            'parent_id' => $catInfo['parentId'],
            'cat_name' => $catInfo['name'],
            'child_count' => $catInfo['child_count'],
            'cat_kind' => 'jdbook',
            'disabled' => $catInfo['state']==1?'false':'true',
        );
        if ($level >0){
            $cat['cat_path']=$cat_path;
            if ( $level==2){
                $cat['is_leaf'] = 'true';
            }
        }

        $mdl_goods_cat = $this->app->model('goods_cat');
        return $mdl_goods_cat->save($cat);
    }

    public function buildBrand($brand_name){
        $mdl_brand = $this->app->model('brand');
        $c = $mdl_brand->count(array('brand_name' => $brand_name));
        if ($c > 0){
            $result = $this->db->selectrow('select * from sdb_jdsale_brand where brand_name ="'.$brand_name.'"');

            return $result['brand_id'];
        }
        $brand = array(
            'brand_name' => $brand_name,
        );
        $mdl_brand->save($brand);
        return $brand['brand_id'];
    }

    public function saveGoodsData($sku_detail){
        $sku_id = $sku_detail['sku'];
        $mdl_goods = app::get('b2c')->model('goods');
        //判断是否是8位SKU商品
        if (strlen(strval($sku_id)) == 8){
            $is_book = true;
        }else{
            $is_book = false;
        }

        //对图书和音频等类型商品进行特别处理
        if ($is_book && ($sku_detail['skuType'] == 'book')){

            $param = '<table cellpadding="0" cellspacing="1" width="100%" border="0" class="Ptable">';
            $param = $param.'<tr><td class="tdTitle">出版社：</td><td>'.$sku_detail['Publishers'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">ISBN：</td><td>'.$sku_detail['ISBN'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">版次：</td><td>'.$sku_detail['BatchNo'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">商品编码：</td><td>'.$sku_id.'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">包装：</td><td>'.$sku_detail['Package'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">开本：</td><td>'.$sku_detail['Sheet'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">出版时间：</td><td>'.date('Y-m-d',strtotime($sku_detail['PublishTime'])).'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">用纸：</td><td>'.$sku_detail['Papers'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">页数：</td><td>'.$sku_detail['Pages'].'</td></tr>';
            $param = $param.'</table>';
            $sku_detail['param'] =$param;

            $description = '';
            if(!empty($sku_detail['contentDesc'])){
                $description .= '<h3>内容描述:</h3>';
                $description .= $sku_detail['contentDesc'];
                $description .= "<br/>";
            }

            if(!empty($sku_detail['bookAbstract'])){
                $description .= '<h3>内容简介:</h3>';
                $description .= $sku_detail['bookAbstract'];
                $description .= "<br/>";
            }

            if(!empty($sku_detail['catalogue'])){
                $description .= '<h3>目录结构:</h3>';
                $description .= $sku_detail['catalogue'];
                $description .= "<br/>";
            }
        }elseif($is_book && ($sku_detail['skuType'] == 'vedio')){

            $param = '<table cellpadding="0" cellspacing="1" width="100%" border="0" class="Ptable">';
            $param = $param.'<tr><td class="tdTitle">演唱者：</td><td>'.$sku_detail['Singer'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">ISBN：</td><td>'.$sku_detail['ISBN'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">商品编码：</td><td>'.$sku_id.'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">介质：</td><td>'.$sku_detail['Media'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">碟数：</td><td>'.$sku_detail['Soundtrack'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">包装：</td><td>'.$sku_detail['saleUnit'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">发行公司：</td><td>'.$sku_detail['Publishing_Company'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">地区：</td><td>'.$sku_detail['Dregion'].'</td></tr>';
            $param = $param.'</table>';
            $sku_detail['param'] =$param;

            $description = $sku_detail['contentDesc'];

        }else{

            $description = $sku_detail['introduction'];
        }

        //查询价格
        $sku_price = $this->api_goods->getSellPrice(array('sku' => $sku_id) , 'book');
        error_log("=-=-=-=-sku_price=-=-=-=-".PHP_EOL.var_export($sku_price,1).PHP_EOL,3,DATA_DIR."/jdimportbook.log");
        if($sku_price['result'][0]['jdPrice'] <= 0.01){
            return false;
        }
        $origin_goods_id = $mdl_goods->getRow("goods_id" , array('bn|tequal'=> $sku_id));
        $goods_detail =  array (
            'bn' => $sku_id,
            'name' => $sku_detail['name'],
            'unit' => $sku_detail['saleUnit'],
            'product_area' => $sku_detail['productArea'],
            'wareQD' => $sku_detail['wareQD'],
            'image_default_id' => $sku_detail['imagePath'],
            'param' => $sku_detail['param'],
            'status' => ($sku_detail['state'] == 0) ? 'false' : 'true',//按实际状态保存
            'jd_brand_id' => $sku_detail['brand_id'],
            'upc' => $sku_detail['upc'],
            'jd_cat_id' => $sku_detail['category'],
            'description' => $description,
            'agreed_price' => $sku_price['result'][0]['price'],
            'averagescore' =>$sku_detail['averagescore'],
            'product'=> array(
                0 => array(
                    'price' => array(
                        'price' => array(
                            'price' => $sku_price['result'][0]['jdPrice']
                        )
                    ),
                    'weight' => $sku_detail['weight'],
                    'bn' => $sku_id,
                    'barcode' => $sku_detail['upc'],
                )),
            'store_id' => $sku_detail['shopID'],
			//无库存也可销售
			'nostore_sell'=>'1',
			'store'=>0,
            'goods_kind' => 'jdbook',
            'goods_application'=>'4',
        );
        if ($origin_goods_id['goods_id'] > 0){
            $goods_detail['goods_id'] = $origin_goods_id['goods_id'];
        }
        //error_log(var_export($goods_detail,1)."\n\r",3,ROOT_DIR.'/shaojun.txt');
        //return ;
        
        if ($mdl_goods->save($goods_detail)){
            error_log("=-=-=-=-goods_detail['goods_id']=-=-=-=-".PHP_EOL.var_export($goods_detail['goods_id'],1).PHP_EOL,3,DATA_DIR."/jdimportbook.log");
            return $goods_detail['goods_id'];
        }else{
            return false;
        }
    }

    public function updateGoodsData($sku_detail){
        $sku_id = $sku_detail['sku'];

        $mdl_goods = app::get('b2c')->model('goods');

        $c = $mdl_goods->count(array('bn|tequal'=> $sku_detail['sku']));
        if (!($c>0)){
            return false ;
        }

        //判断是否是8位SKU商品
        if (strlen(strval($sku_id)) == 8){
            $is_book = true;
        }else{
            $is_book = false;
        }
        //对图书和音频等类型商品进行特别处理
        if ($is_book && ($sku_detail['skuType'] == 'book')){

            $param = '<table cellpadding="0" cellspacing="1" width="100%" border="0" class="Ptable">';
            $param = $param.'<tr><td class="tdTitle">出版社：</td><td>'.$sku_detail['Publishers'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">ISBN：</td><td>'.$sku_detail['ISBN'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">版次：</td><td>'.$sku_detail['BatchNo'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">商品编码：</td><td>'.$sku_id.'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">包装：</td><td>'.$sku_detail['Package'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">开本：</td><td>'.$sku_detail['Sheet'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">出版时间：</td><td>'.date('Y-m-d' , strtotime($sku_detail['PublishTime'])).'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">用纸：</td><td>'.$sku_detail['Papers'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">页数：</td><td>'.$sku_detail['Pages'].'</td></tr>';
            $param = $param.'</table>';
            $sku_detail['param'] =$param;

            $description = '';
            if(!empty($sku_detail['contentDesc'])){
                $description .= '<h3>内容描述:</h3>';
                $description .= $sku_detail['contentDesc'];
                $description .= "<br/>";
            }

            if(!empty($sku_detail['bookAbstract'])){
                $description .= '<h3>内容简介:</h3>';
                $description .= $sku_detail['bookAbstract'];
                $description .= "<br/>";
            }

            if(!empty($sku_detail['catalogue'])){
                $description .= '<h3>目录结构:</h3>';
                $description .= $sku_detail['catalogue'];
                $description .= "<br/>";
            }
        }elseif($is_book && ($sku_detail['skuType'] == 'vedio')){

            $param = '<table cellpadding="0" cellspacing="1" width="100%" border="0" class="Ptable">';
            $param = $param.'<tr><td class="tdTitle">演唱者：</td><td>'.$sku_detail['Singer'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">ISBN：</td><td>'.$sku_detail['ISBN'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">商品编码：</td><td>'.$sku_id.'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">介质：</td><td>'.$sku_detail['Media'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">碟数：</td><td>'.$sku_detail['Soundtrack'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">包装：</td><td>'.$sku_detail['saleUnit'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">发行公司：</td><td>'.$sku_detail['Publishing_Company'].'</td></tr>';
            $param = $param.'<tr><td class="tdTitle">地区：</td><td>'.$sku_detail['Dregion'].'</td></tr>';
            $param = $param.'</table>';
            $sku_detail['param'] =$param;

            $description = $sku_detail['contentDesc'];

        }else{

            $description = $sku_detail['introduction'];
        }


        $sku_price = $this->api_goods->getSellPrice(array('sku' => $sku_detail['sku']) , 'book');
        if($sku_price['result'][0]['jdPrice'] <= 0.01){
            return false;
        }
        $goods_detail =  array (
            'goods_id' => $sku_detail['goods_id'],
            'goods_kind' => 'jdbook',
            'bn' => $sku_id,
            'name' => $sku_detail['name'],
            'unit' => $sku_detail['saleUnit'],
            'product_area' => $sku_detail['productArea'],
            'wareQD' => $sku_detail['wareQD'],
            'image_default_id' => $sku_detail['imagePath'],
            'param' => $sku_detail['param'],
            'status' => ($sku_detail['state'] == 0) ? 'false' : 'true',
            'jd_brand_id' => $sku_detail['brand_id'],
            'upc' => $sku_detail['upc'],
            'jd_cat_id' => $sku_detail['category'],
            'description' => $description,
            'product'=> array(
                0 => array(
                    'price' => array(
                        'price' => array(
                            'price' => $sku_price['result'][0]['jdPrice']
                        )
                    ),
                    'weight' => $sku_detail['weight'],
                    'bn' => $sku_detail['sku'],
                    'barcode' => $sku_detail['upc'],
                )),
        );
        if ($mdl_goods->save($goods_detail)){
            return $goods_detail['goods_id'];
        }else{
            return false;
        }
    }

    public function saveImageAttach($skuId,$goods_id){
        if (!$goods_id){
            return ;
        }

        $image_mdl = app::get('jdsale')->model('image');
        $imageCount = $image_mdl->count(array("goods_id" => $goods_id));
        if($imageCount){
            return ;
        }
        $result = $this->api_goods->querySkuImage(array('sku'=>$skuId) , 'book');


        //lpc
        $isfirst = true;
        $mdl_goods = app::get('b2c')->model('goods');
        $goodsData = $mdl_goods->dump(array('goods_id'=>$goods_id),"image_default_id");

        foreach($result['result'][$skuId] as $k => $v){
//            if ($v['isPrimary'] == 1){
//                continue;
//            }

            //lpc 判断京东默认图片是否存在
            if ($isfirst && !$goodsData['image_default_id']) {
                $jdIMageBugLogDir = ROOT_DIR . "/data/log/jd_image_bug/" . date('Ymd');
                if(!file_exists($jdIMageBugLogDir)){
                    @mkdir($jdIMageBugLogDir,0777,true);
                    @chgrp($jdIMageBugLogDir,'www');
                    @chown($jdIMageBugLogDir,'www');
                }

                $message = date('Y-m-d H:i:s') . "\t";
                $message .= "\tgoodsData: ";
                $message .= var_export($goodsData,true);
                $message .= "\tv: ";
                $message .= var_export($v,true);
                $message .= "\tgoods_id: ";
                $message .= var_export($goods_id,true);
                error_log($message . "\r\n",3, $jdIMageBugLogDir . '/log.txt');
                $mdl_goods->update(array('image_default_id'=>$v['path']),array('goods_id'=>$goods_id));
                $isfirst = false;
            }

            $jd_image = array(
                'goods_id' => $goods_id,
                'image_path' => $v['path'],
                'goods_kind' => 'jdbook',
                'order_sort' => $v['orderSort'],
            );
            $image_mdl->save($jd_image);
        }
    }

    //根据三级分类编号获取一级分类信息
    public function getRootCatInfoByThirdCatId($catId){
        $catParams = array('cid'=>$catId);
        //三级分类信息
        $tmp_catInfo_level_1_arr = $this->api_goods->getCategory($catParams,'book');
        $tmp_catInfo_level_1 = $tmp_catInfo_level_1_arr['result'];

        //二级分类信息
        if ($tmp_catInfo_level_1['catClass'] != '0' && $tmp_catInfo_level_1['parentId'] != '0'){
            $tmp_catInfo_level_2_arr = $this->api_goods->getCategory(array('cid'=>$tmp_catInfo_level_1['parentId']),'book');
            $tmp_catInfo_level_2 = $tmp_catInfo_level_2_arr['result'];
        }else{
            return false;
        }

        //一级分类信息
        if ($tmp_catInfo_level_2['catClass'] != '0' && $tmp_catInfo_level_2['parentId'] != '0'){
            $tmp_catInfo_level_3_arr = $this->api_goods->getCategory(array('cid'=>$tmp_catInfo_level_2['parentId']),'book');
            $tmp_catInfo_level_3 = $tmp_catInfo_level_3_arr['result'];
        }else{
            return false;
        }

        if ($tmp_catInfo_level_3 && $tmp_catInfo_level_3_arr['resultCode'] == '0000')
        {
            return $tmp_catInfo_level_3;
        }else{
            return false;
        }
    }
}