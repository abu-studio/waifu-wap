<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
/**
* 该类主要是用来记录银盛支付平台对接数据交互日志
*/
class unionpay_tools_logs{

    public function inlogs($arr_data, $key, $type='default') {

        $data['dateline'] = time();
        $data['operate_type'] = $type;
        $data['order_id'] = $arr_data['order_id'];
        $data['bill_id'] = $arr_data['bill_id'];
        $data['operate_key'] = $key;
        $data['resp_result'] = $arr_data['resp_result'];
        $data['memo'] = $arr_data['memo'];

        app::get('unionpay')->model('logs')->insert($data);
    }//End Function
}//End Class
