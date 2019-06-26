<?php
class business_mdl_violation extends dbeav_model{
	/**
	 * 构造方法
	 * @param object model相应app的对象
	 * @return null
	 */
    public function __construct($app){
        parent::__construct($app);
        $this->use_meta();
    }

	public function count_finder($filter = null) {
        $row = $this -> db -> select('SELECT count( DISTINCT violation_id) as _count FROM ' . $this -> table_name(1) . ' WHERE ' . $this -> _filter($filter));
        return intval($row[0]['_count']);
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null) {
       
        $tmp = parent::getList($cols, $filter, $offset, $limit, $orderType);

        $objviolationcat = &$this->app->model('violationcat');

        foreach($tmp as $key => &$row) {
            if ( $row['cat_id']) {
                $gradename = $objviolationcat -> getList('cat_name', array('cat_id' => $row['cat_id']));
                $row['violationcat'] = $gradename['0']['cat_name']; 
            }

        }
        return $tmp;
    }
	
	function _filter($filter,$tableAlias=null,$baseWhere=null){
		 if (isset($filter) && $filter && is_array($filter) && array_key_exists('cat_id', $filter))
        {
            $obj_violationcat = app::get('business')->model('violationcat');
            $violationcat_filter = array(
                'cat_name|has'=>$filter['cat_id'],
            );
				
            $row_violationcat = $obj_violationcat->getList('*',$violationcat_filter);
            $arr_cat_id = array();
            if ($row_violationcat)
            {
                foreach ($row_violationcat as $str_violationcat)
                {
                    $arr_cat_id[] = $str_violationcat['cat_id'];
                }
                $filter['cat_id|in'] = $arr_cat_id;
            }
			unset($filter['cat_id']);
        }
		$dbeav_filter = kernel::single('dbeav_filter');
		$dbeav_filter_ret = $dbeav_filter->dbeav_filter_parser($filter,$tableAlias,$baseWhere,$this);
		return $dbeav_filter_ret;
	 }
}
