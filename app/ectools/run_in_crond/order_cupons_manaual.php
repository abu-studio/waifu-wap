<?php
/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2018/1/27
 * Time: 下午5:26
 */
error_reporting(E_ERROR);
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php');
require(ROOT_DIR . '/config/mapper.php');
require(ROOT_DIR . '/app/base/kernel.php');
require(ROOT_DIR . '/app/ectools/lib/order_cupons_manaual.php');

order_cupons_manaual::boot();