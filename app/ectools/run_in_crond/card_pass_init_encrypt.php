<?php
/**
 * 使用key对数据库中已有卡密进行加密
 * ！！！注意：只运行一次！！！
 * 1. 校验加密的文件key是否存在
 * 2. 防止重复执行
 * 3. 对以下表格执行加密
 *     3.1 cards_pass
 *     3.2 cards_pass_log
 *     3.3 cards_balance_items
 *     3.4 cards_exchangelog_items
 * 
 */
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ERROR);
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php');
require(ROOT_DIR . '/config/mapper.php');
require(ROOT_DIR . '/app/base/kernel.php');
if (!kernel::register_autoload()) {
    require(APP_DIR . '/base/autoload.php');
}

//获取传入参数
$method = $argv[1];

if (empty($method))
{
    die('no word!!!'."\n");
}

$date = date('Ymd',time());
$word = $date.'initdb';

if ($method != $word){
    die('wrong word!!!'."\n");
}

app_boot();

//检测是否已执行过初始化
base_kvstore::instance('cardcouponsCardPass')->fetch('card_pass_init_encrypt', $inited);
// var_export($inited);
if ($inited){
    die('inited!!!'."\n");
}else{
    base_kvstore::instance('cardcouponsCardPass')->store('card_pass_init_encrypt', $date);
}

//生成文件key
if (!gene_key()){
    die('generate key file failed!!!'."\n");
}

$key = check_key();
if (empty($key))
{
    die('no key!!!'."\n");
}


//备份下密码
$db = kernel::database();
$transaction_status = $db->beginTransaction();

$sql = "create table sdb_cardcoupons_cards_pass_bak0611 select card_pass_id,card_pass from sdb_cardcoupons_cards_pass  where 1=1";

$re = $db->exec($sql);
if ($re){
    $db->commit($transaction_status);
    echo "cards_pass bak ok"."\n";
}else{
    $db->rollback();
    echo "cards_pass bak failed"."\n";
}

//开始执行加密更新
$db = kernel::database();
$transaction_status = $db->beginTransaction();

$sql = "update sdb_cardcoupons_cards_pass set card_pass = hex(AES_ENCRYPT(card_pass,'{$key}')) where 1=1";

$re = $db->exec($sql);
if ($re){
    $db->commit($transaction_status);
    echo "sdb_cardcoupons_cards_pass ok"."\n";
}else{
    $db->rollback();
    echo "sdb_cardcoupons_cards_pass failed"."\n";
}

$transaction_status = $db->beginTransaction();
$sql = "update sdb_cardcoupons_cards_pass_log set card_pass = hex(AES_ENCRYPT(card_pass,'{$key}')) where 1=1";

$re = $db->exec($sql);
if ($re){
    $db->commit($transaction_status);
    echo "sdb_cardcoupons_cards_pass_log ok"."\n";
}else{
    $db->rollback();
    echo "sdb_cardcoupons_cards_pass_log failed"."\n";
}

$transaction_status = $db->beginTransaction();
$sql = "update sdb_cardcoupons_cards_balance_items set card_pass = hex(AES_ENCRYPT(card_pass,'{$key}')) where 1=1";

$re = $db->exec($sql);
if ($re){
    $db->commit($transaction_status);
    echo "sdb_cardcoupons_cards_balance_items ok"."\n";
}else{
    $db->rollback();
    echo "sdb_cardcoupons_cards_balance_items failed"."\n";
}

$transaction_status = $db->beginTransaction();
$sql = "update sdb_cardcoupons_cards_exchangelog_items set card_pass = hex(AES_ENCRYPT(card_pass,'{$key}')) where 1=1";

$re = $db->exec($sql);
if ($re){
    $db->commit($transaction_status);
    echo "sdb_cardcoupons_cards_exchangelog_items ok"."\n";
}else{
    $db->rollback();
    echo "sdb_cardcoupons_cards_exchangelog_items failed"."\n";
}

function check_key(){
//校验加密的文件key是否存在
    $mysqlKeyLib = kernel::single('cardcoupons_mysqlkey');
    $key = $mysqlKeyLib->getCurrentMysqlKey();
    return $key;
}



echo "finish"."\n";



function app_boot()
{
    include(APP_DIR . '/base/defined.php');

    cachemgr::init();
    cacheobject::init();
}


/*
 * 生成随机key的文件
 * 
 * 
 */

function gene_key(){
    
    $file = ROOT_DIR . "/../yoofuu_db.key";
    if (file_exists($file)){
        $fileKey = file_get_contents($file);
        if (!empty($fileKey)) {
            return true;
        }
    }
    $fp = fopen($file,'w');
    $key = str_rand(256);
    if (file_put_contents($file,$key)){
        fclose($fp);
        return true;
    }else{
        return fasle;
    }
}

/*
 * 生成随机字符串
 * @param int $length 生成随机字符串的长度
 * @param string $char 组成随机字符串的字符串
 * @return string $string 生成的随机字符串
 */
function str_rand($length = 128, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    if(!is_int($length) || $length < 0) {
        return false;
    }

    $string = '';
    for($i = $length; $i > 0; $i--) {
        $string .= $char[mt_rand(0, strlen($char) - 1)];
    }

    return $string;
}

