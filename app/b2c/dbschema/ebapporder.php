<?php 
 
 
$db['ebapporder']=array (
  'columns' => 
  array (
    'order_id' =>
    array (
      'type' => 'bigint unsigned',
      'pkey' => true,
      'required' => true,
      'default' => 0,
      'label' => '订单号',
      'editable' => false,
    ),
	'organization_code' =>
    array (
      'type' => 'varchar(200)',
      'label' => '机构编号',
      'editable' => true,
    ),
    'organization_name' =>
    array (
      'type' => 'varchar(200)',
	  'editable' => true,
      'label' => '机构名称',
	  'hidden'	=>true,
    ),
  'barcode' =>
  array (
      'type' => 'varchar(255)',
      'editable' => true,
    ),
	'ebapporder_id' =>
    array (
      'type' => 'varchar(255)',
      'editable' => true,
	  'label'=>'水电煤订单',
    ),
	'organization_type' =>
    array (
      'type' =>
		array(
			'GAS'=>'燃气费',
			'WATER'=>'水费',
			'ELECTRICITY'=>'电费',
			'PHONE'=>'话费'
		),
	 'label'=>'付款方式',
	 'default' => 'PHONE',
	 'hidden'	=>true,
     'editable' => true,
    )
   ),
    'index'=>array(
        'ind_source' =>
            array (
                'columns' =>
                    array (
                        0 => 'ebapporder_id',
                    ),
            ),
    )
);