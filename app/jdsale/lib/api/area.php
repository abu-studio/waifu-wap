<?php

/**
 * 获取京东地址信息的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/9
 * Time: 18:56
 */
class jdsale_api_area extends jdsale_api_base {
    
    public function __construct($app){
        parent::__construct($app);

    }

    /**
     * 1.3.1	查询京东一级地址
     * @param $params = array();
     * @return array
     */
    public function getAllProvinces($params,$jdgoodsKind='normal'){
        $api_function = '查询京东一级地址';
        $method = 'area/getProvince';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.3.2	查询京东二级地址
     * @param $params = array('id'=>1);
     * @return Array ( [顺义区] => 2812 [门头沟] => 2807 [崇文区] => 2803 [怀柔区] => 2814 [大兴区] => 2810 [延庆县] => 3065 [西城区] => 2801 [宣武区] => 2804 [平谷区] => 2953 [密云区] => 2816 [东城区] => 2802 [昌平区] => 2901 [丰台区] => 2805 [朝阳区] => 72 [石景山区] => 2806 [房山区] => 2808 [海淀区] => 2800 [通州区] => 2809 )
     */
    public function getCitysByProvinceId($params,$jdgoodsKind='normal'){
        $api_function = '查询京东二级地址';
        $method = 'area/getCity';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.3.3	查询京东三级地址
     * @param $params = array('id'=>2812);
     * @return Array ( [光明街道] => 51130 [北石槽镇] => 51125 [杨镇地区] => 51147 [北务镇] => 51126 [仁和地区] => 51141 [南法信地区] => 51139 [李遂镇] => 51134 [南彩镇] => 51138 [北小营镇] => 51127 [马坡地区] => 51136 [龙湾屯镇] => 51135 [牛栏山地区] => 51140 [胜利街道] => 51142 [旺泉街道] => 51146 [后沙峪地区] => 51131 [李桥镇] => 51133 [大孙各庄镇] => 51128 [赵全营镇] => 51149 [木林镇] => 51137 [石园街道] => 51143 [空港街道] => 51132 [高丽营镇] => 51129 [双丰街道] => 51144 [天竺地区] => 51145 [张镇] => 51148 )
     */
    public function getCountysByCityId($params,$jdgoodsKind='normal'){
        $api_function = '查询京东三级地址';
        $method = 'area/getCounty';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.3.4	查询京东四级地址
     * @param $params = array('id'=>51130);
     * @return array ();
     */
    public function getTownsByCountyId($params,$jdgoodsKind='normal'){
        $api_function = '查询京东四级地址';
        $method = 'area/getTown';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 1.4.2	批量获取库存接口
     * @param $params array(
     *                  "skuNums"=>array(
     *                       array('skuId'=>2542893,'num'=>1),
     *                       array('skuId'=>2543186,'num'=>1),
     *                   ),
     *                  'area'=> implode('_',array('1','2812','51130','0')),
     *                 );
     * @return  Array (
     *              [0] => Array (
     *                  [skuId] => 2542893
     *                  [areaId] => 1_2812_51130_0
     *                  [stockStateId] => 33
     *                  [stockStateDesc] => 有货
     *                  [remainNum] => -1
     *              )
     *              [1] => Array (
     *                  [skuId] => 2543186
     *                  [areaId] => 1_2812_51130_0
     *                  [stockStateId] => 33
     *                  [stockStateDesc] => 有货
     *                  [remainNum] => -1 )
     *          )
     */
    public function getFororder($params,$jdgoodsKind='normal'){
        $api_function = '批量获取库存接口';
        $method = 'stock/getNewStockById';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        if(isset($result['result']) && is_string($result['result'])){
            $result['result'] = json_decode($result['result'] , true);
        }

        return $result;
    }


    /**
     * 1.5.1	信息推送接口
     * @param $params $array = array(
                        'type'=>'1,2,4,5,6,10,11,12,13,14,15,16,17,20',
                        );
     * @return array  array(
                        [0] => Array(
                            [id] => 559433746
                            [result] => Array(
                                [price] =>
                                [skuId] => 1978758
                                [jdPrice] =>
                            )
                            [time] => 2016-11-07 11:36:00
                            [type] => 2
                        )
                    )
     */
    public function getMessage($params,$jdgoodsKind='normal'){
        $api_function = '信息推送接口';
        $method = 'message/get';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }


    /**
     * 1.5.2	删除推送信息接口
     * @param $params $array = array('id'=>'602473823')
     * @return bool 是否删除成功   1 or 0
     *
     * Array
    (
    [biz_message_del_response] => Array
    (
    [success] => 1
    [resultMessage] =>
    [resultCode] => 0000
    [result] => 1
    [code] => 0
    )

    )
     *
     *
     */
    public function  delMessage($params,$jdgoodsKind='normal'){
        $api_function = '信息推送接口';
        $method = 'message/del';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

}