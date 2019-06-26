<?php
/*
 * @Title: cards_pass_log.php
 * @Description: 卡密重发送记录
 *
 * @author Comsys-zhyu
 * @date Jun 30, 2015 2:16:17 PM
 * @version V1.0
 */
 $db['cards_pass_log']=array(
 	'columns'=>
    array(
	   'log_id' =>
	    array (
	      'type' => 'int(11)',
	      'required' => true,
	      'pkey' => true,
	      'extra' => 'auto_increment',
	      'editable' => false,
	    ),
 		'card_no' =>
	    array (
	      'type' => 'varchar(50)',
	      'required' => true,
	      'label' => app::get('cardcoupons')->_('卡券编号'),
	      'in_list' => true,
	      'default_in_list' => true,
		  'searchtype' => 'has',
	      'editable' => true,
	      'filtertype' => 'custom',
	      'filterdefault' => true,
	      'filtercustom' =>
	      array (
	        'has' => '包含',
	        'tequal' => '等于',
	        'head' => '开头等于',
	        'foot' => '结尾等于',
	      ),
	    ),
	    'card_pass' =>
	    array (
	      'type' => 'varchar(256)',
	      'required' => true,
	      'label' => app::get('cardcoupons')->_('卡密'),
		  'searchtype' => 'has',
	      'editable' => false,
	      'filtertype' => 'custom',
	      'filterdefault' => true,
	      'filtercustom' =>
	      array (
	        'has' => '包含',
	        'tequal' => '等于',
	        'head' => '开头等于',
	        'foot' => '结尾等于',
	      ),
	    ),
	    'mobile'=>
		array(
			'type'			=>'varchar(11)',
			'required'		=>true,
			'label'			=>app::get('cardcoupons')->_('手机号'),
			'searchtype'	=>'has',
			'editable'		=>false,
			'filtertype'	=> 'custom',
	        'filterdefault' => true,
		),
	    'status' =>
    	array (
	      'type' =>
			array(
				'0'=>'未发送',
				'1'=>'发送成功',
	            '2'=>'发送失败',
	            '3'=>'接口调用失败',
			),
		  'filtertype' => 'yes',
		  'default'=>'0',
	      'filterdefault' => true,
	      'required' => true,
	      'label' => app::get('cardcoupons')->_('发送状态'),
	      'editable' => true,
	      'in_list' => true,
	      'default_in_list' => true,
	    ),
	    'time' =>
	    array (
	      'type' =>'time',
	      'required' => true,
	      'label' => app::get('cardcoupons')->_('创建时间'),
	      'editable' => false,
	      'in_list' => true,
	    ),
	    'memo'=>
	    array(
	    	'type'=>'varchar(255)',
	    	'label'=>app::get('cardcoupons')->_('备注'),
	    	'editable'=>false,
	    ),
 	),
	'index'=>array(
		'ind_card_no'=>
		array(
		   'columns'=>
		    array(
				0=>'card_no',
			),
		),
		'ind_card_pass'=>
		array(
		   'columns'=>
		   array(
			   0=>'card_pass',
		  ),
		),
	  ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',

 );
?>
