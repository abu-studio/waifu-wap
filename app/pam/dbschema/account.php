<?php

 
$db['account'] = array(
    'columns'=>array(
        'account_id'=>array('type'=>'number','pkey'=>true,'extra' => 'auto_increment',),
        'account_type'=>array('type'=>'varchar(30)'),
        'login_name'=>array('type'=>'varchar(100)','is_title'=>true,'required' => true, ),
        'login_password'=>array('type'=>'varchar(32)','required' => true,),
        'disabled'=>array('type'=>'bool','default'=>'false'),
        'createtime'=>array('type'=>'time'),
        'company_no'=>array('type'=>'varchar(100)'),
        'company_name'=>array('type'=>'varchar(200)'),
    ),
  'index' => array (
    'account' => array ('columns' => array ('account_type','login_name'),'prefix' => 'UNIQUE'),
  ),
  'engine' => 'innodb',
);
