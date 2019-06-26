<?php

/**
 * 获取商品信息的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/9
 * Time: 18:56
 */
class jdsale_api_goods extends jdsale_api_base {

    public function __construct($app){
        parent::__construct($app);

    }

    /**
     * 1.1.1	查询商品池编号接口
     * @return array
        array(2) {
            [0]=>
            array(2) {
                ["name"]=>
                string(6) "测试"
                ["page_num"]=>
                string(8) "20161107"
            }
            [1]=>
            array(2) {
                ["name"]=>
                string(6) "测试"
                ["page_num"]=>
                string(3) "105"
            }
        }
     */
    public function queryPageNum($jdgoodsKind='normal'){
        $api_function = '查询商品池编号接口';
        $method = 'product/getPageNum';
        //param_json={ }，无入参的情况
        $params = null;
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;

    }

    /**
     * 1.1.2	查询池内商品编号接口
     * @param $params = array('pageNum'=>'20161107')
     * @return array
     *Array
    (
    [biz_product_sku_query_response] => Array
    (
    [success] => 1
    [resultMessage] =>
    [resultCode] => 0000
    [result] => 3133827,1024513,1545844,910135,845321,1029044,851630,
     1656917,1374810,1115509,690352,209951,1163863,1082266,1150551,
     1299741,958912,2857483,1003061,253394,1656919,1203982,1115507,
     1032887,594797,695467,2304459,2297112,526825,843489,885987,1305498...
    [code] => 0
    )

    )
     */
    public function querySku($params,$jdgoodsKind='normal'){
        $api_function = '查询池内商品编号接口';
        $method='product/getSku';
        $sku_result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        if (empty($sku_result['result'])){
            $result=array();
        }else{
            $result=explode(',',$sku_result['result']);
        }
        return $result;
    }

    /**
     * 1.1.3	查询商品详细信息接口
     * @param $params array('sku'=>102194)
     * @return array (
            'saleUnit' => '原厂包装',
            'weight' => '0.08',
            'productArea' => '瑞士',
            'wareQD' => '包装盒 × 1  军刀 × 1  刀套 × 1',
            'imagePath' => 'g16/M00/0C/07/rBEbRVOISE8IAAAAAAGnZMOUVnQAACYowLP9gMAAad8822.jpg',
            'param' => '<table cellpadding="0" cellspacing="1" width="100%" border="0" class="Ptable"><tr><th class="tdTitle" colspan="2">主体</th><tr><tr><td class="tdTitle">品牌</td><td>维氏VICTORINOX</td></tr><tr><td class="tdTitle">材质</td><td>不锈钢</td></tr><tr><td class="tdTitle">净尺寸(mm)</td><td>58</td></tr><tr><td class="tdTitle">净重(kg)</td><td>0.08</td></tr><tr><td class="tdTitle">颜色</td><td>黄色</td></tr><tr><th class="tdTitle" colspan="2">特性</th><tr><tr><td class="tdTitle">功能数量（个）</td><td>7</td></tr></table>',
            'state' => 1,
            'sku' => 102194,
            'brandName' => '维氏（VICTORINOX）',
            'upc' => '7611160017758',
            'category' => '1672;2599;1443',
            'name' => '维氏VICTORINOX瑞士军刀 典范-水瓶星座（7种功能）黄色光面0.6223.8.AQUA',
            'introduction' => '<table width="750" border="0" align="center" cellpadding="0" cellspacing="0">
     *
     */
    public function queryProductDetail($params,$jdgoodsKind='normal'){
        $api_function = '查询商品详细信息接口';
        $method = 'product/getDetail';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.1.4	查询商品上下架状态接口
     * @param $params array('sku'=>'107164,179638,208671,657300')
     * @return array
    array(4) {
        [0]=>
        array(2) {
            ["state"]=>
            int(1)
            ["sku"]=>
            int(107164)
        }
        [1]=>
        array(2) {
            ["state"]=>
            int(0)
            ["sku"]=>
            int(179638)
        }
        [2]=>
        array(2) {
        ["state"]=>
        int(1)
        ["sku"]=>
        int(208671)
        }
        [3]=>
        array(2) {
        ["state"]=>
        int(1)
        ["sku"]=>
        int(657300)
        }
    }
     */
    public function queryProductState($params,$jdgoodsKind='normal'){
        $api_function = '查询商品上下架状态接口';
        $method = 'product/skuState';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.1.5	查询所有图片信息接口
     * @param $params array('sku'=>'107164,179638')
     * @return array
     *
        array(2) {
        [107164]=>
        array(4) {
        [0]=>
        array(11) {
        ["id"]=>
        int(22509)
        ["skuId"]=>
        int(107164)
        ["path"]=>
        string(64) "g15/M04/02/0D/rBEhWVHH4BUIAAAAAAG9TptsKdIAAAeaQFbhUAAAb1m221.jpg"
        ["created"]=>
        string(19) "2013-06-24 13:58:49"
        ["modified"]=>
        string(19) "2016-04-13 10:55:45"
        ["yn"]=>
        int(1)
        ["isPrimary"]=>
        int(1)
        ["orderSort"]=>
        NULL
        ["position"]=>
        NULL
        ["type"]=>
        int(0)
        ["features"]=>
        NULL
        }
     * ...
     */
    public function querySkuImage($params,$jdgoodsKind='normal'){
        $api_function = '查询所有图片信息接口';
        $method = 'product/skuImage';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);

        return $result;
    }

    /**
     * 1.1.6查询商品好评度接口
     * @param $params array('sku'=>'1')
     * @return array
     *  array (
            0 =>
            array (
            'averageScore' => 4,
            'generalRate' => 0,
            'goodRate' => 1,
            'skuId' => 1,
            'poorRate' => 0,
            ),
        )
     */
    public function queryProductComment($params,$jdgoodsKind='normal'){
        $api_function = '查询商品好评度接口';
        $method = 'product/getCommentSummarys';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.1.7	查询商品区域购买限制接口
     * @param $params array('skuIds'=>'208671,657300',"province"=>'11','city'=>'0','county'=>'0','town'=>'0')
     * @return array
     *    array(2) {
            [0]=>
            array(2) {
            ["skuId"]=>
            int(208671)
            ["isAreaRestrict"]=>
            bool(false)
            }
            [1]=>
            array(2) {
            ["skuId"]=>
            int(657300)
            ["isAreaRestrict"]=>
            bool(false)
            }
            }
     */
    public function queryCheckAreaLimit($params,$jdgoodsKind='normal'){
        $api_function = '查询商品区域购买限制接口';
        $method = 'product/checkAreaLimit';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.1.10	运费查询接口
     *
     * @param $params
     * array(
        'sku'=>array(array('skuId'=>2542855,'num'=>1)),
        'province'=>7,'city'=>527,'county'=>530,'Town'=>0,'paymentType'=>1)
     * @return array
     * Array
        (
        [success] => 1
        [resultMessage] =>
        [resultCode] => 0000
        [result] => Array
        (
        [freight] => 6
        [baseFreight] => 6
        [remoteRegionFreight] => 0
        [remoteSku] => []
        )

        [code] => 0
        )
     */
    public function queryOrderFreight($params,$jdgoodsKind='normal'){
        $api_function = '运费查询接口';
        $method = 'order/getFreight';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.1.11	商品可售验证接口
     * @param $params array('skuIds'=>'1656917,1374810')
     * @return array
     *[result] => Array
            (
            [0] => Array
            (
            [is7ToReturn] => 1
            [isCanVAT] => 1
            [name] => 【京东超市】新良面包粉 新良高筋面粉  烘焙原料 优质面包小麦粉 500g
            [saleState] => 1
            [skuId] => 1656917
            )

            [1] => Array
            (
            [is7ToReturn] => 1
            [isCanVAT] => 1
            [name] => 【京东超市】维达（Vinda) 手帕纸 超韧系列 4层纸巾*36包(无香)
            [saleState] => 1
            [skuId] => 1374810
            )
    )
     */
    public function checkProduct($params,$jdgoodsKind='normal'){
        $api_function = '商品可售验证接口';
        $method = 'product/check';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.2.1	批量查询价格
     * @param $params array('sku'=>'1656917,1374810')
     * @return array
     *  Array
                (
                [0] => Array
                    (
                    [price] => 16.9
                    [skuId] => 1374810
                    [jdPrice] => 16.9
                    )

                [1] => Array
                    (
                    [price] => 3.8
                    [skuId] => 1656917
                    [jdPrice] => 3.8
                    )

                )

     *
     * getSellPrice 2016-11-16 16:39:30
    array (
    'code' => '0',
    'resultCode' => '1003',
    'resultMessage' => 'sku数量过多，目前最大支持100个商品',
    )

     */
    public function getSellPrice($params,$jdgoodsKind='normal'){
        $api_function = '批量查询价格';
        $method = 'price/getSellPrice';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 查询分类列表信息接口
     * @param $params array('pageNo'=>1,'pageSize'=>100,'parentId'=>'9987','catClass'=>'1')
     * @return array
     * Array
    (
    [categorys] => Array
    (
    [0] => Array
    (
    [catId] => 653
    [parentId] => 9987
    [name] => 手机通讯
    [catClass] => 1
    [state] => 1
    )

    [1] => Array
    (
    [catId] => 6880
    [parentId] => 9987
    [name] => 运营商
    [catClass] => 1
    [state] => 1
    )

    [2] => Array
    (
    [catId] => 830
    [parentId] => 9987
    [name] => 手机配件
    [catClass] => 1
    [state] => 1
    )

    [3] => Array
    (
    [catId] => 12854
    [parentId] => 9987
    [name] => 手机服务
    [catClass] => 1
    [state] => 1
    )

    [4] => Array
    (
    [catId] => 10973
    [parentId] => 9987
    [name] => 赠品
    [catClass] => 1
    [state] => 1
    )

    )

    [totalRows] => 5
    [pageNo] => 1
    [pageSize] => 100
    )

    [code] => 0
    )
     */
    public function getCategorys($params,$jdgoodsKind='normal'){
        $api_function = '查询分类列表信息接口';
        $method = 'product/getCategorys';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 查询分类信息接口
     * @param $params array('cid'=>670)
     * @return array
     * Array
        (
        [success] => 1
        [resultMessage] =>
        [resultCode] => 0000
        [result] => Array
        (
        [catId] => 670
        [parentId] => 0
        [name] => 电脑、办公
        [catClass] => 0
        [state] => 1
        )

        [code] => 0
        )
     *
     */
    public function getCategory($params,$jdgoodsKind='normal'){
        $api_function = '查询分类信息接口';
        $method = 'product/getCategory';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }
}