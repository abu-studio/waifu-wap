<?php
/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2018/6/8
 * Time: 下午2:26
 */
error_reporting(E_ERROR);
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php');
date_default_timezone_set("Asia/Shanghai");
require(ROOT_DIR.'/config/mapper.php');
require(ROOT_DIR.'/app/base/kernel.php');
require(ROOT_DIR.'/app/ectools/lib/order_settlement_result.php');

order_settlement_result::boot();