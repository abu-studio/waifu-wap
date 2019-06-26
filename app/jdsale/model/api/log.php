<?php


/**
* api接口调用日志记录的MODEL
*/
class jdsale_mdl_api_log extends dbeav_model
{
    /**
	* @var string 排序方式
	*/
    var $defaultOrder = 'createtime desc';
    /**
     * 重写getList方法
     * @param string column
     * @param array filter
     * @param int offset
     * @param int limit
     * @param string order by
     * @param name zxc
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
    	$firstday =strtotime(date("Y-m-01", time()));
    	$endday =strtotime(date("Y-m-d 23:59:59", time()));
    	$filter = array('createtime|between' =>array($firstday,$endday));
        $arr_member = parent::getList($cols, $filter, $offset, $limit, $orderType);
        return $arr_member;
    }

    /**
     * 重写count方法
     * @param name zxc
     */
    public function count( $filter=array() ) {

    	$firstday =strtotime(date("Y-m-01", time()));
    	$endday =strtotime(date("Y-m-d 23:59:59", time()));
    	$filter = array('createtime|between' =>array($firstday,$endday));
        return parent::count($filter);
    }

	
}//End Class