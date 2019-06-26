<?php


class ectools_mdl_refunds extends dbeav_model
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->use_meta();
    }

    var $has_many = array(
        'orders' => 'order_bills@ectools:contrast:refund_id^bill_id',
    );

    var $defaultOrder = array('t_payed', 'DESC');

    function gen_id()
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $refund_id = time() . str_pad($i, 4, '0', STR_PAD_LEFT);
            $row = $this->dump($refund_id, 'refund_id');
        } while ($row);
        return $refund_id;
    }

    /**
     * 模板统一保存的方法
     * @params array - 需要保存的支付信息
     * @params boolean - 是否需要强制保存
     * @return boolean - 保存的成功与否的进程
     */
    public function save($data, $mustUpdate = null)
    {
        // 异常处理    
        if (!isset($data) || !$data || !is_array($data)) {
            trigger_error(app::get('ectools')->_("支付单信息不能为空！"), E_USER_ERROR);
            exit;
        }

        $sdf = array();

        // 支付数据列表
        $background = true;//后台 todo

        $payment_data = $data;
        $sdf_payment = parent::dump($data['refund_id'], '*');

        if ($sdf_payment) {
            if ($sdf_payment['status'] == $data['status']
                || ($sdf_payment['status'] != 'progress' && $sdf_payment['status'] != 'ready')) {
                return true;
            }
            if ($data['currency'] && $sdf_payment['currency'] != $data['currency']) {
                return false;
            }
        }

        if ($sdf_payment) {
            $sdf = array_merge($sdf_payment, $data);
        } else {
            $sdf = $data;
            //$sdf['status'] = 'ready';
        }
        // 保存支付信息（可能是退款信息）
        $is_succ = parent::save($sdf);

        return $is_succ;
    }

    /**
     * 得到所有的支付账号
     * @param null
     * @return null
     */
    public function getAccount()
    {
        $query = 'SELECT DISTINCT bank, account FROM ' . $this->table_name(1) . ' WHERE status="succ"';
        return $this->db->select($query);
    }

    /**
     * 重写搜索的下拉选项方法
     * @param null
     * @return null
     */
    public function searchOptions()
    {
        $columns = array();
        foreach ($this->_columns() as $k => $v) {
            if (isset($v['searchtype']) && $v['searchtype']) {
                $columns[$k] = $v['label'];
            }
        }

        /** 添加店铺名称搜索 **/
        $columns['store_name'] = app::get('b2c')->_('店铺名称');
        /** end **/

        // 添加额外的
        $ext_columns = array('rel_id' => $this->app->_('订单号'));

        return array_merge($columns, $ext_columns);
    }

    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        if (!$filter)
            return parent::_filter($filter);
        //店铺名称模糊搜索
        if (isset($filter) && $filter && is_array($filter) && array_key_exists('store_name', $filter)) {
            $obj_business_storemanger = app::get('business')->model('storemanger');
            $store_filter = array(
                'store_name|has' => $filter['store_name'],
            );
            $row_store = $obj_business_storemanger->getList('*', $store_filter);
            $arr_store_id = array();
            if ($row_store) {
                foreach ($row_store as $str_store) {
                    $arr_store_id[] = $str_store['store_id'];
                }
                $filter['store_id|in'] = $arr_store_id;
            }
            unset($filter['store_name']);
        }

        if (array_key_exists('rel_id', $filter)) {
            $obj_order_bills = $this->app->model('order_bills');
            $bill_filter = array(
                'rel_id|has' => $filter['rel_id'],
                'bill_type|in' => array('refunds', 'blances'),
            );
            $row_order_bills = $obj_order_bills->getList('bill_id', $bill_filter);
            $arr_member_id = array();
            if ($row_order_bills) {
                $arr_order_bills = array();
                foreach ($row_order_bills as $arr) {
                    $arr_order_bills[] = $arr['bill_id'];
                }
                $filter['refund_id|in'] = $arr_order_bills;
            } else {
                $filter['refund_id'] = 'a';
            }
            unset($filter['rel_id']);
        }

        $filter = parent::_filter($filter);
        return $filter;
    }

    /**
     * delete 方法重载
     *
     * 根据条件删除条目
     * 不可以由pipe控制
     * 可以广播事件
     *
     * @param mixed $filter
     * @param mixed $named_action
     * @access public
     * @return void
     */
    public function delete($filter)
    {
        return parent::delete($filter);
    }

    /**
     * filter字段显示修改
     * @params string 字段的值
     * @return string 修改后的字段的值
     */
    public function modifier_member_id($row)
    {
        if (is_null($row) || empty($row)) {
            return app::get('ectools')->_('未知会员或非会员');
        }

        $obj_pam_account = app::get('pam')->model('account');
        $arr_pam_account = $obj_pam_account->getList('login_name', array('account_id' => $row));

        if ($arr_pam_account[0])
            return $arr_pam_account[0]['login_name'];
        else
            return '-';
    }

    /**
     * filter字段显示修改
     * @params string 字段的值
     * @return string 修改后的字段的值
     */
    public function modifier_op_id($row)
    {
        if ($row == 0) {
            return app::get('ectools')->_('auto');
        }

        if (is_null($row) || empty($row)) {
            return app::get('ectools')->_('未知操作员');
        }

        $obj_pam_account = app::get('pam')->model('account');
        $arr_pam_account = $obj_pam_account->getList('login_name', array('account_id' => $row));

        if ($arr_pam_account[0])
            return $arr_pam_account[0]['login_name'];
        else
            return app::get('ectools')->_('未知操作员');
    }

    /**
     * filter字段显示修改
     * @params string 字段的值
     * @return string 修改后的字段的值
     */
    public function modifier_pay_app_id($row)
    {
        $obj_payment_cfgs = $this->app->model('payment_cfgs');
        $arr_payment_cfgs = $obj_payment_cfgs->getPaymentInfo($row);

        if ($arr_payment_cfgs) {
            return $arr_payment_cfgs['app_name'];
        } else
            return 'app_name';
    }

    /**
     * 退款货币值
     */
    public function modifier_cur_money($row)
    {
        $currency = $this->app->model('currency');
        $filter = array('refund_id' => $this->pkvalue);
        $tmp = $this->getList('currency', $filter);
        $arr_cur = $currency->getcur($tmp[0]['currency']);
        $row = $currency->formatNumber($row, false, false);

        return $arr_cur['cur_sign'] . $row;
    }

    /**
     * 退款收款人帐号
     */
    public function modifier_account($row)
    {
        if (is_null($row) || empty($row)) {
            return app::get('ectools')->_('未知收款人');
        }

        return $row;
    }

    /**
     * 查询有效支付单号
     */
    public function get_payment($order_id)
    {
        if ($order_id) {
            $sql = "SELECT ob.bill_id  FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_payments p ON p.payment_id=ob.bill_id WHERE ob.rel_id=" . $order_id . " AND p.status='succ'";
            $row = $this->db->select($sql);
        } else {
            return false;
        }

        return $row[0];
    }

    /*
    * @method : get_all_payments_by_order_id
    * @description : 根据订单id获取退款单信息
    * @params :
    *       $order_id : 订单id
    * @return : array
    * @author : zlj
    * @date : 2013-6-3 15:21:58
    */
    public function get_all_refunds_by_order_id($order_id = 0)
    {
        if (!$order_id) {
            return array();
        }

        $rows = $this->db->select('SELECT refunds.* FROM ' . $this->table_name(1) . ' AS refunds INNER JOIN ' . kernel::database()->prefix . $this->app->app_id . '_order_bills AS bills ON bills.bill_id=refunds.refund_id WHERE bills.rel_id=' . $order_id);
        return $rows;
    }

    /**
     * 重写订单导出方法
     * @param array $data
     * @param array $filter
     * @param int $offset
     * @param int $exportType
     */
    public function fgetlist_csv(&$data, $filter, $offset, $exportType = 1)
    {
        $limit = 100;
        $cols = $this->_columns();
        if (!$data['title']) {
            $this->title = array();
            foreach ($this->getTitle($cols) as $titlek => $aTitle) {
                $this->title[$titlek] = $aTitle;
            }
            // service for add title when export
            foreach (kernel::servicelist('export_add_title') as $services) {
                if (is_object($services)) {
                    if (method_exists($services, 'addTitle')) {
                        $services->addTitle($this->title);
                    }
                }
            }
            $data['title'] = '"' . implode('","', $this->title) . '"';
        }

        if (!$list = $this->getList(implode(',', array_keys($cols)), $filter, $offset * $limit, $limit)) return false;

        // $data['contents'] = array();
        foreach ($list as $line => $row) {
            // service for add data when export
            foreach (kernel::servicelist('export_add_data') as $services) {
                if (is_object($services)) {
                    if (method_exists($services, 'addData')) {
                        $services->addData($row);
                    }
                }
            }
            $rowVal = array();
            foreach ($row as $col => $val) {

                if (in_array($cols[$col]['type'], array('time', 'last_modify')) && $val) {
                    $val = date('Y-m-d H:i', $val) . "\t";
                }
                if ($cols[$col]['type'] == 'longtext') {
                    if (strpos($val, "\n") !== false) {
                        $val = str_replace("\n", " ", $val);
                    }
                }

                if (strlen(intval($val)) > 8) {
                    $val .= "\t";
                }

                if (strpos((string)$cols[$col]['type'], 'table:') === 0) {
                    $subobj = explode('@', substr($cols[$col]['type'], 6));
                    if (!$subobj[1])
                        $subobj[1] = $this->app->app_id;
                    $subobj = &app::get($subobj[1])->model($subobj[0]);
                    $subVal = $subobj->dump(array($subobj->schema['idColumn'] => $val), $subobj->schema['textColumn']);
                    $val = $subVal[$subobj->schema['textColumn']] ? $subVal[$subobj->schema['textColumn']] : $val;
                }

                if (array_key_exists($col, $this->title))
                    $rowVal[] = addslashes((is_array($cols[$col]['type']) ? $cols[$col]['type'][$val] : $val));
            }
            $data['contents'][] = '"' . implode('","', $rowVal) . '"';
        }
        return true;

    }

    function getTitle(&$cols)
    {
        $title = array();
        foreach ($cols as $col => $val) {
            if (!$val['deny_export'])
                $title[$col] = $val['label'] . '(' . $col . ')';
        }
        return $title;
    }

    public function fgetlistitems_csv(&$data, $filter, $offset, $exportType = 1)
    {
        $limit = 100;
        if (!$data['title']) {
            $data['title'] = '"单号","订单ID","商品编号","商品名称","购买量","原始单价","实际单价","总金额","分类","抽成比率","运费抽成"';
        }
        $where = $this->_filter($filter);
        $where = str_replace('`sdb_ectools_refunds`', 'r', $where);
        $sql = "
select r.refund_id,oi.order_id,g.bn,g.name,oi.nums,g.mktprice,oi.price,oi.amount,gc.cat_name,gc.profit_point 
from sdb_ectools_refunds r 
left join sdb_ectools_order_bills ob on r.refund_id=ob.bill_id
left join  sdb_b2c_order_items oi on ob.rel_id=oi.order_id
left join sdb_b2c_orders o on oi.order_id=o.order_id  
left join sdb_b2c_goods g on oi.goods_id=g.goods_id 
left join sdb_b2c_goods_cat gc on g.cat_id=gc.cat_id 
where {$where}
        ";
        if (!$list = $this->db->selectLimit($sql, $limit, $offset * $limit)) return false;
        // $data['contents'] = array();
        foreach ($list as $line => $row) {
            $rowVal = array();
            foreach ($row as $col => $val) {

                if (strlen($val) > 8 && eregi("^[0-9]+$", $val)) {
                    $val .= "\r";
                }
                $rowVal[] = addslashes($val);
            }

            $rowVal[] = app::get('b2c')->getConf('member.profit');
            $data['contents'][] = '"' . implode('","', $rowVal) . '"';
        }
        return true;

    }

    function get_orders_no_blance()
    {
        $sql = "SELECT order_id FROM sdb_b2c_orders o
WHERE pay_status='1' AND ship_status='1' and `status`='finish' AND order_id NOT IN(
SELECT b.rel_id FROM sdb_b2c_orders o
LEFT JOIN sdb_ectools_order_bills b ON b.rel_id=o.order_id
WHERE b.bill_type='blances' AND o.`status`='finish'
) ORDER BY last_modified DESC LIMIT 0,50;";
        $n_finishs = $this->db->select($sql);
        return $n_finishs;
    }

    public function get_schema()
    {
        $table = $this->table_name();
        if (!isset($this->__exists_schema[$this->app->app_id][$table])) {
            if (!isset($this->table_define)) {
                $this->table_define = new base_application_dbtable;
            }
            $this->__exists_schema[$this->app->app_id][$table] = $this->table_define->detect($this->app, $table)->load(false);
        }
        $schema = $this->__exists_schema[$this->app->app_id][$table];
        //echo "<pre>";print_r($_SERVER);exit;
        if (strstr($_SERVER['QUERY_STRING'], 'balance')) {
            $schema['columns']['refund_id']['label'] = '结算单号';
            $schema['columns']['refund_bn']['label'] = '结算唯一单号';
            $schema['columns']['cur_money']['label'] = '结算金额';
        }
        return $schema;
    }

    /* 为批量退款统计重写count函数 */
    public function count($filter = null)
    {
        //此处paytype筛选条件是为了将批量退款与其他标签页区分开
        if ($filter['paytype'] == 'alipay') {
            $count = count($this->getList('*', $filter));
        } else {
            $count = parent::count($filter);
        }
        return $count;
    }

    /* 为批量退款统计重写getList函数 */
    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
    {
        $list = array();
        //此处paytype筛选条件是为了将批量退款与其他标签页区分开
        if ($filter['paytype'] == 'alipay') {
            $alipay_flag = true;
            unset($filter['paytype']);
            $list = parent::getList($cols, $filter, 0, -1, $orderType);
        } else {
            $list = parent::getList($cols, $filter, $offset, $limit, $orderType);
        }
        //以特殊方式筛选掉不能加入批量退款的退款单
        if ($alipay_flag) {
            $db = kernel::database();
            $obj_order_bills = app::get('ectools')->model('order_bills');
            foreach ($list as $k => $v) {
                $to_unset = false;
                //获取订单ID
                $order_refund = $obj_order_bills->getRow('*', array('bill_id' => $v['refund_id'], 'bill_type' => 'refunds'));
                $order_id = $order_refund['rel_id'];
                //筛选掉参与了合并支付的订单&对应多次支付的订单
                $sql_payments = 'SELECT * FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_payments p ON ob.bill_id=p.payment_id WHERE ob.rel_id="' . $order_id . '" AND ob.bill_type="payments" AND p.status="succ" AND p.pay_app_id="alipay"';
                $payments = $db->select($sql_payments);
                if (count($payments) > 1 || $payments[0]['merge_payment_id']) {
                    unset($list[$k]);
                    continue;
                }
                //筛选掉存在多笔退款的订单
                $sql_refunds = 'SELECT * FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_refunds p ON ob.bill_id=p.refund_id WHERE ob.rel_id="' . $order_id . '" AND ob.bill_type="refunds" AND p.status="ready" AND p.pay_app_id="alipay"';
                $refunds = $db->select($sql_refunds);
                if (count($refunds) > 1) {
                    unset($list[$k]);
                    continue;
                }
                //支付宝交易单号
                $trade_no = $payments[0]['trade_no'];
                //退款金额
                $money = $refunds[0]['cur_money'];
                if (!$trade_no || !$money) {
                    unset($list[$k]);
                    continue;
                }
            }
            //截取翻页
            if ($limit == -1) {
                $list = array_slice($list, $offset);
            } else {
                $list = array_slice($list, $offset, $limit);
            }
        }
        return $list;
    }

    //获取可结算的结算单和退款单
    function get_bills($str, $type = 'balance')
    {
        if ($type == 'balance') {
            $bulls_info = $this->getList('*', array('is_balance' => '2', 'store_id|in' => $str, 'refund_type' => '2'));
        } else {
            $bulls_info = $this->getList('*', array('is_balance' => '2', 'store_id|in' => $str, 'refund_type' => '1', 'is_safeguard' => '2'));
        }

        return $bulls_info;
    }

    //按照时间和订单类型获取可结算的结算单和退款单（>=start，<end）
    function get_bills_by_filter($str, $type = 'balance', $filter)
    {
        $obj_refunds = app::get('ectools')->model('refunds');
        if ($type == 'balance') {

            if ($filter['order_kind']) {
                $sql = "SELECT * FROM sdb_ectools_refunds rf LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id=ob.bill_id LEFT JOIN sdb_b2c_orders o ON ob.rel_id=o.order_id WHERE rf.is_balance='2' AND rf.refund_type='2' ";
                if ($str) $sql .= "AND rf.store_id in (" . $str . ") ";
                if ($filter['start']) $sql .= "AND rf.t_confirm>=" . $filter['start'] . " ";
                if ($filter['end']) $sql .= "AND rf.t_confirm<" . $filter['end'] . " ";
                if ($filter['order_kind']) $sql .= "AND o.order_kind in (" . $filter['order_kind'] . ") ";
            }
            if ($filter['card_id']) {
                $sql2 = " SELECT
					  rf.*, ob.*,o.*
					FROM
						sdb_ectools_refunds rf
					LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id = ob.bill_id
					LEFT JOIN sdb_b2c_orders o ON ob.rel_id = o.order_id
					LEFT JOIN sdb_cardcoupons_cards_pass cp ON  INSTR(cp.exchange_order_id,o.order_id)
					where o.order_kind = 'card' and cp.card_id in (" . $filter['card_id'] . ") ";

                if ($str) $sql2 .= "AND rf.store_id in (" . $str . ") ";
                if ($filter['start']) $sql2 .= "AND rf.t_confirm>=" . $filter['start'] . " ";
                if ($filter['end']) $sql2 .= "AND rf.t_confirm<" . $filter['end'] . " ";
                if ($filter['order_kind']) {
                    $sql .= ' union ' . $sql2;
                } else {
                    $sql = $sql2;
                }
            }

            if ($sql) {
                $result = $obj_refunds->db->select($sql);
            }
        } else {
            if (!($str && $filter['end'] && $filter['order_kind'])) return false;
            $sql = "SELECT * FROM sdb_ectools_refunds rf LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id=ob.bill_id LEFT JOIN sdb_b2c_orders o ON ob.rel_id=o.order_id WHERE rf.is_balance='2' AND rf.refund_type='1' AND rf.is_safeguard='2' ";
            if ($str) $sql .= "AND rf.store_id in (" . $str . ") ";
            if ($filter['start']) $sql .= "AND rf.t_confirm>=" . $filter['start'] . " ";
            if ($filter['end']) $sql .= "AND rf.t_confirm<" . $filter['end'] . " ";
            if ($filter['order_kind']) $sql .= "AND o.order_kind in (" . $filter['order_kind'] . ") ";

            $result = $obj_refunds->db->select($sql);
        }

        return $result;
    }

//按照时间和订单类型获取可结算的结算单和退款单（>=start，<end）
    function count_bills_by_filter($str, $type = 'balance', $filter)
    {
        $obj_refunds = app::get('ectools')->model('refunds');
        if ($type == 'balance') {

            if ($filter['order_kind']) {
                $sql = "SELECT count(*) FROM sdb_ectools_refunds rf LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id=ob.bill_id LEFT JOIN sdb_b2c_orders o ON ob.rel_id=o.order_id WHERE rf.is_balance='2' AND rf.refund_type='2' ";
                if ($str) $sql .= "AND rf.store_id in (" . $str . ") ";
                if ($filter['start']) $sql .= "AND rf.t_confirm>=" . $filter['start'] . " ";
                if ($filter['end']) $sql .= "AND rf.t_confirm<" . $filter['end'] . " ";
                if ($filter['order_kind']) $sql .= "AND o.order_kind in (" . $filter['order_kind'] . ") ";
            }
            if ($filter['card_id']) {
                $sql2 = " SELECT
					  count(*)
					FROM
						sdb_ectools_refunds rf
					LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id = ob.bill_id
					LEFT JOIN sdb_b2c_orders o ON ob.rel_id = o.order_id
					LEFT JOIN sdb_cardcoupons_cards_pass cp ON  INSTR(cp.exchange_order_id,o.order_id)
					where o.order_kind = 'card' and cp.card_id in (" . $filter['card_id'] . ") ";

                if ($str) $sql2 .= "AND rf.store_id in (" . $str . ") ";
                if ($filter['start']) $sql2 .= "AND rf.t_confirm>=" . $filter['start'] . " ";
                if ($filter['end']) $sql2 .= "AND rf.t_confirm<" . $filter['end'] . " ";
                if ($filter['order_kind']) {
                    $sql .= ' union ' . $sql2;
                } else {
                    $sql = $sql2;
                }
            }

            if ($sql) {
                $result = $obj_refunds->db->selectrow($sql);
            }
        } else {
            if (!($str && $filter['end'] && $filter['order_kind'])) return false;
            $sql = "SELECT count(*) FROM sdb_ectools_refunds rf LEFT JOIN sdb_ectools_order_bills ob ON rf.refund_id=ob.bill_id LEFT JOIN sdb_b2c_orders o ON ob.rel_id=o.order_id WHERE rf.is_balance='2' AND rf.refund_type='1' AND rf.is_safeguard='2' ";
            if ($str) $sql .= "AND rf.store_id in (" . $str . ") ";
            if ($filter['start']) $sql .= "AND rf.t_confirm>=" . $filter['start'] . " ";
            if ($filter['end']) $sql .= "AND rf.t_confirm<" . $filter['end'] . " ";
            if ($filter['order_kind']) $sql .= "AND o.order_kind in (" . $filter['order_kind'] . ") ";

            $result = $obj_refunds->db->selectrow($sql);
        }

        if (is_array($result)) {
            return array_pop($result);
        }

        return $result;
    }
}
