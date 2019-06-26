<?php
/**
 * @Author:      zyp
 * @Email:
 * @DateTime:    2019-05-16 16:57:31
 * @Description: 加密key存储类
 */

class cardcoupons_mysqlkey
{
    function __construct($app){
        // $this->app = $app;
        $this->storeKey();
        $this->currentKey = $this->getCurrentMysqlKey();
        $this->keyHistoryList = $this->getMysqlKeyList();
    }

    //获取文件中的key
    public function getFileKey()
    {
        $file = ROOT_DIR . "/../yoofuu_db.key";
        if (file_exists($file)){
            $fileKey = file_get_contents($file);
            if (empty($fileKey)) {
                return false;
            }
        }
        else{
            return false;
        }
        return $fileKey;
    }

    /*将key存入kv，存入前判断kv中最新的key是否与文件的key相同，相同不做处理，不同则新增
     *$keyInKv =array (
    0=>array('time'=>timestamp1,'key'=>key1),
    1=>array('time'=>timestamp2,'key'=>key2),
    2=>array('time'=>timestamp3,'key'=>key3),
    )
     */
    public function storeKey($key='mysql_key_arr')
    {
        if (empty($key)) {
            return false;
        }
        $fileKey = $this->getFileKey();
        if (!$fileKey) {
            return false;
        }
        base_kvstore::instance('cardcouponsCardPass')->fetch($key, $keyInKv);
        if (!empty($keyInKv)) {
            $lastestKey = end($keyInKv);

            if ($lastestKey['key'] !== $fileKey) {
                $newKeytoKv = array('time' => time(), 'key' => $fileKey);
                array_push($keyInKv, $newKeytoKv);
            }
        } else {
            $keyInKv = array(array('time' => time(), 'key' => $fileKey));

        }

        base_kvstore::instance('cardcouponsCardPass')->store($key, $keyInKv);
    }


    //获取key列表
    public function getMysqlKeyList($key='mysql_key_arr')
    {
    	$mysqlKey =array();
    	base_kvstore::instance('cardcouponsCardPass')->fetch($key, $keyInKv);
    	if (empty($keyInKv)){
    		$mysqlKey = array($this->getFileKey());

    	}else{
    		foreach ($keyInKv as $key => $value) {
    			$mysqlKey[]=$value['key'];
    		}
    	}
		return $mysqlKey;
    }

    //获取当前key
    public function getCurrentMysqlKey($key='mysql_key_arr'){
    	$currentKey='';
    	$mysqlKeyList = $this->getMysqlKeyList();
    	return $currentKey = end($mysqlKeyList);

    }

    //判断cols中是否包含card_pass
    function _hasCardPass($cols)
    {
        // if ($cols == '*'){
        //     return true;
        // }elseif($cols == '`card_pass`'||$cols == 'card_pass'){
        //     return true;
        // }else{
        //     if(strpos($cols,'card_pass')!==false){
        //         return true;
        //     }else{
        //         return false;
        //     }
        // }
        if($cols == '*'){
            return true;
        }else{
            if(preg_match('/card_pass\b/', $cols)){
                return true;
            }else{
                return false;
            }
        }
    }

    //用缓存记录的key解密，解密结果不为null则为正确，立即返回
    function dePwByKey($card_pass)
    {
        foreach ($this->keyHistoryList as $key => $value) {
            $ssql="select AES_DECRYPT(UNHEX('{$card_pass}'),'{$value}') as cp";
            $re = kernel::database()->select($ssql);
            if ($re[0]['cp']===null){
                continue;
            }else{
                $result = $re[0]['cp'];
                break;
            }
        }
        return $result;
    }

    //用当前key进行加密
    function enPwByCurrentKey($card_pass){
        $ssql="select HEX(AES_ENCRYPT('{$card_pass}','{$this->currentKey}')) as ep";
        $re = kernel::database()->select($ssql);
        return $re[0]['ep'];
    }

    //当使用card_pass作为条件查询时，因不知道加密时使用了哪个key，所以用缓存的每一个key加密一下
    function enPwByKeyList($tmp_condition)
    {
        if (is_array($tmp_condition)){
            foreach ($tmp_condition as $key => $value) {
                $tmp_v = $value;
                foreach ($this->keyHistoryList as $k1 => $v1) {  
                    $ssql="select HEX(AES_ENCRYPT('{$tmp_v}','{$v1}')) as ep";
                    $re = kernel::database()->select($ssql);
                    $tmp_condition_after[] = $re[0]['ep'];
                }
            }
        }else{
            $tmp_v = $tmp_condition;
            foreach ($this->keyHistoryList as $k2 => $v2) {  
                $ssql="select HEX(AES_ENCRYPT('{$tmp_v}','{$v2}')) as ep";
                $re = kernel::database()->select($ssql);
                $tmp_condition_after[] = $re[0]['ep'];
            }
        }

        return $tmp_condition_after;
    }

}
