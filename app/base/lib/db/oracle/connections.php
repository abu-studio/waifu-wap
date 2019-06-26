<?php
/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2017/12/5
 * Time: 下午12:10
 */

class base_db_oracle_connections
{

    protected $prefix;
    protected $_rw_lnk = null;
    protected $_use_transaction = false;
    public static $oracle_query_executions;

    public function __construct(){

    }

    public function getDb()
    {
        return $this->_rw_conn();
    }

    //lpc 
    public function getEbayDb()
    {
        return $this->_rw_conn_eb();
    }

    //lpc 
    public function getYFDb()
    {
        return $this->_rw_conn_yf();
    }

    public function exec($sql , $skipModifiedMark=false, $db_lnk=null){
        if($this->prefix!='sdb_'){
            //$sql = preg_replace('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',"\${1}".$this->prefix."\\3\\4",$sql);
            $sql = preg_replace_callback('/([`\s\(,])(sdb_)([0-9a-z\_]+)([`\s\.]{0,1})/is', array($this, 'fix_dbprefix'), $sql); //todo: 兼容有特殊符号的表名前缀
        }

        if(!is_resource($db_lnk)){
            if($this->_rw_lnk){
                $db_lnk = &$this->_rw_lnk;
            }else{
                $db_lnk = &$this->_rw_conn();
            }
        }

        $stid = oci_parse($db_lnk, $sql);

        if($stid){
            oci_execute($stid);

            self::$oracle_query_executions++;
            $db_result = array('rs'=>&$stid,'sql'=>$sql);
            return $db_result;
        }else{
            trigger_error($sql.':'.oci_error($db_lnk),E_USER_WARNING);
            return false;
        }
    }

    public function bindSelect($sql , $bindParames){
        $rs = $this->bindExec($sql , $bindParames);
        if(false === $rs){
            return $rs;
        }

        $rowsData = array();
        while($row = oci_fetch_array($rs['rs'], OCI_ASSOC+OCI_RETURN_NULLS)){
            array_push($rowsData , $row);
        }

        return $rowsData;
    }

    public function bindExec($sql , $bindParames, $db_lnk=null){
        if($this->prefix!='sdb_'){
            //$sql = preg_replace('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',"\${1}".$this->prefix."\\3\\4",$sql);
            $sql = preg_replace_callback('/([`\s\(,])(sdb_)([0-9a-z\_]+)([`\s\.]{0,1})/is', array($this, 'fix_dbprefix'), $sql); //todo: 兼容有特殊符号的表名前缀
        }

        if(!is_resource($db_lnk)){
            if($this->_rw_lnk){
                $db_lnk = &$this->_rw_lnk;
            }else{
                $db_lnk = &$this->_rw_conn();
            }
        }

        $stid = oci_parse($db_lnk, $sql);

        if($stid){
            foreach($bindParames as $bindKey => $bindVal){
                oci_bind_by_name($stid , $bindKey , $bindVal);
            }
            oci_execute($stid);

            self::$oracle_query_executions++;
            $db_result = array('rs'=>&$stid,'sql'=>$sql);
            return $db_result;
        }else{
            trigger_error($sql.':'.oci_error($db_lnk),E_USER_WARNING);
            return false;
        }
    }

    protected function fix_dbprefix($matchs)
    {
        return $matchs[1] . ((trim($matchs[1])=='`') ? $this->prefix.$matchs[3] : '`'.$this->prefix.$matchs[3].'`') . $matchs[4];
    }//End Function

    public function select($sql, $skipModifiedMark=false){
        if($this->_rw_lnk){
            $db_lnk = &$this->_rw_lnk;
        }

        if($this->prefix!='sdb_'){
            //$sql = preg_replace('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',"\${1}".$this->prefix."\\3\\4",$sql);
            $sql = preg_replace_callback('/([`\s\(,])(sdb_)([0-9a-z\_]+)([`\s\.]{0,1})/is', array($this, 'fix_dbprefix'), $sql); //todo: 兼容有特殊符号的表名前缀
        }//todo:为了配合check_expries判断表名，冗余执行

        $rs = $this->exec($sql, $skipModifiedMark, $db_lnk);
        if($rs['rs']){
            $data = array();
            while($row = oci_fetch_assoc($rs['rs'])){
                $data[]=$row;
            }
            oci_free_statement($rs['rs']);
            return $data;
        }else{
            return false;
        }
    }

    public function selectrow($sql){
        $row = &$this->selectlimit($sql,1,0);
        return $row[0];
    }

    //针对mysql的查询分页
//    public function selectlimit($sql,$limit=10,$offset=0){
//        if ($offset >= 0 || $limit >= 0){
//            $offset = ($offset >= 0) ? $offset . "," : '';
//            $limit = ($limit >= 0) ? $limit : '18446744073709551615';
//            $sql .= ' LIMIT ' . $offset . ' ' . $limit;
//        }
//        $data = &$this->select($sql);
//        return $data;
//    }


    public function getRows($rs,$row=10){
        $i=0;
        $data = array();
        while(($row = oci_fetch_assoc($rs['rs'])) && $i++<$row){
            $data[]=$row;
        }
        return $data;
    }

    protected function _rw_conn(){
        $this->_rw_lnk = &$this->_connect(ORACLE_DB_HOST,ORACLE_DB_USER,ORACLE_DB_PASSWORD,ORACLE_DB_NAME);
        return $this->_rw_lnk;
    }

    //lpc 查询分离库数据，ebay登录
    protected function _rw_conn_eb(){
        $this->_rw_lnk = &$this->_connect(EB_ORACLE_DB_HOST,EB_ORACLE_DB_USER,EB_ORACLE_DB_PASSWORD,EB_ORACLE_DB_NAME);
        return $this->_rw_lnk;
    }

    //lpc 链接那个数据：172.16.108.19
    protected function _rw_conn_yf(){
        $this->_rw_lnk = &$this->_connect(YF_ORACLE_DB_HOST,YF_ORACLE_DB_USER,YF_ORACLE_DB_PASSWORD,YF_ORACLE_DB_NAME);
        return $this->_rw_lnk;
    }

    protected function _connect($host,$user,$passwd,$dbname){
        $dbCharset = null;
        if(defined('ORACLE_DB_CHARSET') && constant('ORACLE_DB_CHARSET')){
            $dbCharset = ORACLE_DB_CHARSET ;
        }
        if(defined('ORACLE_DB_PCONNECT') && constant('ORACLE_DB_PCONNECT')){
            $lnk = oci_pconnect($user, $passwd, $host.'/'.$dbname , $dbCharset);
        }else{
            $lnk = oci_connect($user, $passwd, $host.'/'.$dbname , $dbCharset);
        }
        if(!$lnk){
            $errorMsg = app::get('base')->_('无法连接Oracle数据库:'). json_encode(oci_error(),1);
            trigger_error($errorMsg  , E_USER_ERROR);
        }

        if(preg_match('/[0-9\.]+/is',oci_server_version($lnk),$match)){
            $dbver = $match[0];
            if(version_compare($dbver,'4.1.1','<')){
                define('DB_OLDVERSION',1);
                $this->dbver = 3;
            }else{
                if(!version_compare($dbver,'6','<')){
                    $this->dbver = 6;
                }
            }
        }

        return $lnk;
    }

    public function count($sql) {
        $sql = preg_replace(array(
            '/(.*\s)LIMIT .*/i',
            '/^select\s+(.+?)\bfrom\b/is'
        ),array(
            '\\1',
            'select count(*) as c from'
        ),trim($sql));
        $row = $this->select($sql);
        return intval($row[0]['c']);
    }

    /**
     * _whereClause
     *
     * @param mixed $queryString
     * @access protected
     * @return void
     */
    protected function _whereClause($queryString){

        preg_match('/\sWHERE\s(.*)/is', $queryString, $whereClause);

        $discard = false;
        if ($whereClause) {
            if (preg_match('/\s(ORDER\s.*)/is', $whereClause[1], $discard));
            else if (preg_match('/\s(LIMIT\s.*)/is', $whereClause[1], $discard));
            else preg_match('/\s(FOR UPDATE.*)/is', $whereClause[1], $discard);
        } else
            $whereClause = array(false,false);

        if ($discard)
            $whereClause[1] = substr($whereClause[1], 0, strlen($whereClause[1]) - strlen($discard[1]));
        return $whereClause[1];
    }

    public function quote($string){
        $result = addslashes($string);
        //$string=addslashes($string);
        //return "'" . $string . "'";
        return "'" . $result . "'";
    }

    public function errorinfo(){
        if($this->_rw_lnk){
            $db_lnk = &$this->_rw_lnk;
        }
        return oci_error($db_lnk);
    }

    public function beginTransaction(){
        if(!$this->_in_transaction){
            $this->_in_transaction = true;
            if(!$this->_use_transaction){
                $this->_use_transaction = true;
            }//todo:第一次使用事务后即通知程序当前进程ro_conn至主库
            return $this->exec('SET TRANSACTION READ WRITE');
        }else{
            return false;
        }
    }

    public function commit($status=true){
        if($status){
            oci_commit($this->_rw_lnk);
            $this->_in_transaction = false;
            return true;
        }else{
            return false;
        }
    }

    public function rollBack(){
        oci_rollback($this->_rw_lnk);
        $this->_in_transaction = false;
    }

    public function close(){
        if($this->_rw_lnk && oci_close($this->_rw_lnk)){
            $this->_rw_lnk = null;
            return true;
        }
        return false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
