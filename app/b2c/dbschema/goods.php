<?php  
 $db['goods']=array ( 
  'columns' => 
  array (


    'goods_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
    'bn' => 
    array (
      'type' => 'varchar(200)',
      'label' => '商品编号',
      'width' => 110,
      'searchtype' => 'head',
      'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
    ),
	
	
	'tax_rate' => 
    array (
      'type' => 'varchar(200)',
      'label' => '税率',
      'width' => 110,
      'editable' => true,
      'in_list' => true,
    ),

	'ticket_type' => 
    array (
      'type' => 
      array (
        'increment' => '增票',
        'ordinary' => '普票',
      ),
      'label' => '票种',
      'width' => 110,
      'editable' => true,
      'in_list' => true,
    ),
		
    'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => '商品名称',
      'is_title' => true,
      'width' => 310,
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
      'in_list' => true,
      'default_in_list' => true,
      'order' => '1',
    ),
    'price' => 
    array (
      'type' => 'money',
      'sdfpath' => 'product[default]/price/price/price',
      'default' => '0',
      'required' => true,
      'label' => '销售价',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => true,
      'in_list' => true,
      'orderby' => true,
    ),
    'type_id' => 
    array (
      'type' => 'table:goods_type',
      'sdfpath' => 'type/type_id',
      'label' => '类型',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'yes',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'cat_id' => 
    array (
      'type' => 'table:goods_cat',
      'required' => true,
      'sdfpath' => 'category/cat_id',
      'default' => 0,
      'label' => '分类',
      'width' => 75,
      'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'orderby' => true,
    ),
    'brand_id' => 
    array (
      'type' => 'table:brand',
      'sdfpath' => 'brand/brand_id',
      'label' => '品牌',
      'width' => 75,
      'editable' => true,
      'hidden' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'marketable' => 
    array (
      'type' => 'bool',
      'default' => 'true',
      'sdfpath' => 'status',
      'required' => true,
      'label' => '上架',
      'width' => 30,
      'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'store' => 
    array (
      'type' => 'decimal(20,2)',
      'label' => '库存',
	  'required' => true,
	  'default' => 0,
      'width' => 30,
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'store_freeze' => 
    array (
      'type' => 'decimal(20,2)',
      'label' => '冻结库存',
	  'required' => true,
	  'default' => 0,
      'width' => 30,
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'notify_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '缺货登记',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'uptime' => 
    array (
      'type' => 'time',
      'depend_col' => 'marketable:true:now',
      'label' => '上架时间',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'downtime' => 
    array (
      'type' => 'time',
      'depend_col' => 'marketable:false:now',
      'label' => '下架时间',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'last_modify' => 
    array (
      'type' => 'last_modify',
      'label' => '更新时间',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'orderby' => true,
    ),
    'p_order' => 
    array (
      'type' => 'number',
      'default' => 30,
      'required' => true,
      'label' => '排序',
      'width' => 110,
      'editable' => false,
      'hidden' => true,
      'in_list' => false,
      'orderby' => true,
    ),
    'd_order' => 
    array (
      'type' => 'number',
      'default' => 30,
      'required' => true,
      'label' => '排序',
      'width' => 30,
      'editable' => true,
      'in_list' => true,
      'orderby' => true,
    ),
    'score' => 
    array (
      'type' => 'float unsigned',// number->float unsigned
      'sdfpath' => 'gain_score',
      'label' => '积分',
      'width' => 30,
      'editable' => false,
      'in_list' => true,
    ),
    'cost' => 
    array (
      'type' => 'money',
      'sdfpath' => 'product[default]/price/cost/price',
      'default' => '0',
      'required' => true,
      'label' => '成本价',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'number',
      'in_list' => true,
    ),
    'mktprice' => 
    array (
      'type' => 'money',
      'sdfpath' => 'product[default]/price/mktprice/price',
      'label' => '市场价',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'number',
      'in_list' => true,
    ),
    'weight' => 
    array (
      'type' => 'decimal(20,3)',
      'sdfpath' => 'product[default]/weight',
      'label' => '重量',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
    ),
    'unit' => 
    array (
      'type' => 'varchar(20)',
      'sdfpath' => 'unit',
      'label' => '单位',
      'width' => 30,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'brief' => 
    array (
      'type' => 'varchar(255)',
      'label' => '商品简介',
      'width' => 110,
      'hidden' => false,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'goods_type' => 
    array (
      'type' => 
      array (
        'normal' => '普通商品',
        'bind' => '捆绑商品',
        'gift' => '赠品',
      ),
      'sdfpath' => 'goods_type',
      'default' => 'normal',
      'required' => true,
      'label' => '销售类型',
      'width' => 110,
      'editable' => false,
      'hidden' => true,
      'in_list' => false,
    ),
    'image_default_id' => 
    array (
      'type' => 'varchar(255)',
      'label' => '默认图片',
      'width' => 75,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
    'udfimg' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'label' => '是否用户自定义图',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
    'thumbnail_pic' => 
    array (
      'type' => 'varchar(32)',
      'label' => '缩略图',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
    'small_pic' => 
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'big_pic' => 
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'intro' => 

    array (
      'type' => 'longtext',
      'sdfpath' => 'description',
      'label' => '详细介绍',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => false,
    ),
    'store_place' => 
    array (
      'type' => 'varchar(255)',
      'label' => '库位',
      'sdfpath' => 'product[default]/store_place',
      'width' => 30,
      'editable' => false,
      'hidden' => true,
    ),
    'min_buy' => 
    array (
      'type' => 'number',
      'label' => '起定量',
      'width' => 30,
      'editable' => false,
      'in_list' => false,
    ),
    'package_scale' => 
    array (
      'type' => 'decimal(20,2)',
      'label' => '打包比例',
      'width' => 30,
      'editable' => false,
      'in_list' => false,
    ),
    'package_unit' => 
    array (
      'type' => 'varchar(20)',
      'label' => '打包单位',
      'width' => 30,
      'editable' => false,
      'in_list' => false,
    ),
    'package_use' => 
    array (
      'type' => 'intbool',
      'label' => '是否开启打包',
      'width' => 30,
      'editable' => false,
      'in_list' => false,
    ),
    'score_setting' => 
    array (
      'type' => 
      array (
        'percent' => '百分比',
        'number' => '实际值',
      ),
      'default' => 'number',
      'editable' => false,
    ),
    'nostore_sell' => 
    array (
      'type' => 'intbool',
      'default' => '0',
      'label' => '是否开启无库存销售',
      'width' => 30,
      'editable' => false,
    ),
    'goods_setting' => 
    array (
      'type' => 'serialize',
      'label' => '商品设置',
      'deny_export' => true,
    ),
    'spec_desc' => 
    array (
      'type' => 'serialize',
      'label' => '物品',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'params' => 
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'editable' => false,
    ),
    'rank_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'comments_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'view_w_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'view_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'count_stat' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'buy_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'buy_w_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'p_1' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_1/value',
      'editable' => false,
    ),
    'p_2' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_2/value',
      'editable' => false,
    ),
    'p_3' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_3/value',
      'editable' => false,
    ),
    'p_4' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_4/value',
      'editable' => false,
    ),
    'p_5' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_5/value',
      'editable' => false,
    ),
    'p_6' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_6/value',
      'editable' => false,
    ),
    'p_7' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_7/value',
      'editable' => false,
    ),
    'p_8' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_8/value',
      'editable' => false,
    ),
    'p_9' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_9/value',
      'editable' => false,
    ),
    'p_10' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_10/value',
      'editable' => false,
    ),
    'p_11' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_11/value',
      'editable' => false,
    ),
    'p_12' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_12/value',
      'editable' => false,
    ),
    'p_13' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_13/value',
      'editable' => false,
    ),
    'p_14' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_14/value',
      'editable' => false,
    ),
    'p_15' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_15/value',
      'editable' => false,
    ),
    'p_16' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_16/value',
      'editable' => false,
    ),
    'p_17' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_17/value',
      'editable' => false,
    ),
    'p_18' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_18/value',
      'editable' => false,
    ),
    'p_19' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_19/value',
      'editable' => false,
    ),
    'p_20' => 
    array (
      'type' => 'number',
      'sdfpath' => 'props/p_20/value',
      'editable' => false,
    ),
    'p_21' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_21/value',
      'editable' => false,
    ),
    'p_22' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_22/value',
      'editable' => false,
    ),
    'p_23' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_23/value',
      'editable' => false,
    ),
    'p_24' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_24/value',
      'editable' => false,
    ),
    'p_25' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_25/value',
      'editable' => false,
    ),
    'p_26' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_26/value',
      'editable' => false,
    ),
    'p_27' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_27/value',
      'editable' => false,
    ),
    'p_28' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_28/value',
      'editable' => false,
    ),
    'p_29' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_29/value',
      'editable' => false,
    ),
    'p_30' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_30/value',
      'editable' => false,
    ),
    'p_31' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_31/value',
      'editable' => false,
    ),
    'p_32' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_32/value',
      'editable' => false,
    ),
    'p_33' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_33/value',
      'editable' => false,
    ),
    'p_34' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_34/value',
      'editable' => false,
    ),
    'p_35' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_35/value',
      'editable' => false,
    ),
    'p_36' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_36/value',
      'editable' => false,
    ),
    'p_37' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_37/value',
      'editable' => false,
    ),
    'p_38' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_38/value',
      'editable' => false,
    ),
    'p_39' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_39/value',
      'editable' => false,
    ),
    'p_40' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_40/value',
      'editable' => false,
    ),
    'p_41' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_41/value',
      'editable' => false,
    ),
    'p_42' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_42/value',
      'editable' => false,
    ),
    'p_43' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_43/value',
      'editable' => false,
    ),
    'p_44' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_44/value',
      'editable' => false,
    ),
    'p_45' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_45/value',
      'editable' => false,
    ),
    'p_46' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_46/value',
      'editable' => false,
    ),
    'p_47' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_47/value',
      'editable' => false,
    ),
    'p_48' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_48/value',
      'editable' => false,
    ),
    'p_49' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_49/value',
      'editable' => false,
    ),
    'p_50' => 
    array (
      'type' => 'varchar(255)',
      'sdfpath' => 'props/p_50/value',
      'editable' => false,
    ),
    'store_id' => 
    array (
      'type' => 'table:storemanger@business',
      'required' => false,
      'label' => '店铺名称',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'goods_state' => 
    array (
      'type' => 
      array (
        'new' => '全新',
        'used' => '二手',
      ),
      'required' => true,
      'default' => 'new',
      'label' => '是否全新',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'buy_m_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'view_m_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'fav_count' => 
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'freight_bear' => 
    array (
      'type' => 
      array (
        'business' => '商家',
        'member' => '会员',
      ),
      'required' => true,
      'default' => 'member',
      'label' => '运费承担',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'marketable_allow' => 
    array (
      'type' => 'bool',
      'default' => 'true',      
      'label' => '后台人工上架',
      'width' => 30,
      'editable' => true,
    ),
    'marketable_content' => 
    array (
      'type' => 'varchar(255)',
      'label' => '上下架原因',
      'width' => 110,
      'hidden' => false,
      'editable' => false,
    ),
    'act_type' => 
    array (
      'type' => 'varchar(50)',
      'required' => false,
      'default' => 'normal',
      'label' => '商品类型',
      'width' => 110,
      'editable' => false,
    ),
    'avg_point' =>
    array (
      'type' => 'decimal(8,2)',
      'default' => 0,
      'required' => true,
      'label' => app::get('b2c')->_('商品评分'),
     
    ),
    'goods_kind' => 
    array (
      'type' => 
      array (
        'virtual' => '实体商品',
        'entity' => '虚拟商品',
        'card' => '卡券商品',
        'jdgoods'=>'京东商品',
        'jdbook'=>'京东图书',
      ),
      'searchtype' => 'has',
      'default' => 'virtual',
      'required' => true,
      'label' => '商品种类',
      'width' => 110,
      'editable' => false,
      'hidden' => true,
      'default_in_list' => true,
      'in_list' => true,
    ),
    'goods_order_down' => 
    array (
      'type' => 'int unsigned',
      'default' => 100,
      'required' => true,
      'editable' => false,
    ),
	'goods_application' => 
    array (
      'type' => 
      array (
        '0' => '成本价待审核',
        '1' => '成本价审核不通过',
        '2' => '销售价待审核',
        '3' => '销售价审核不通过',
        '4' => '审核通过',
      ),
      'default' => '0',
      'required' => true,
      'label' => '审核状态',
      'width' => 110,
      'editable' => true,
      'default_in_list' => true,
      'in_list' => true,
      'order' =>'43',
    ),
    'agreed_price' =>
        array (
            'type' => 'money',
            'default' => '0',
            'required' => true,
            'label' => '商品协议价格',
            'width' => 75,
            'editable' => false,
            'in_list' => true,
            'orderby' => true,
        ),
    'jd_cat_id' =>
        array (
            'type' => 'table:goods_cat@jdsale',
            'default' => '0',
            'required' => true,
            'label' => '京东分类',
            'width' => 75,
            'editable' => true,
            'in_list' => true,
			'filtertype' => 'yes',
            'filterdefault' => true,
            'default_in_list' => true,
            'orderby' => true,
        ),
    'jd_brand_id' =>
        array (
            'type' => 'table:brand@jdsale',
            'label' => '京东品牌',
            'width' => 110,
			'filtertype' => 'yes',
            'filterdefault' => true,
            'editable' => true,
            'in_list' => true,
        ),
    'product_area' =>
        array (
            'type' => 'varchar(200)',
            'label' => '产地',
            'width' => 110,
            'editable' => true,
            'in_list' => true,
        ),
    'wareQD' =>
        array (
            'type' => 'varchar(1024)',
            'label' => '包装清单',
            'width' => 110,
            'editable' => true,
            'in_list' => true,
        ),
    'upc' =>
        array (
            'type' => 'varchar(25)',
            'label' => '条形码',
            'width' => 110,
            'editable' => true,
            'in_list' => true,
        ),
    'param' =>
        array (
            'type' => 'longtext',
            'label' => '京东规格参数',
            'width' => 75,
            'hidden' => true,
            'editable' => false,
            'in_list' => false,
        ),
    'averagescore' =>
        array(
            'type' => 'varchar(25)',
            'label' => '京东商品评分',
            'width' => 110,
            'required' => true,
            'default' => '0',
            'editable' => true,
            'in_list' => true,
        ),
  ),
  'comment' => '商品表',
  'index' => 
  array (
    'uni_bn' => 
    array (
      'columns' => 
      array (
        0 => 'bn',
      ),
      'prefix' => 'UNIQUE',
    ),
    'ind_frontend' => 
    array (
      'columns' => 
      array (
        0 => 'disabled',
        1 => 'goods_type',
        2 => 'marketable',
      ),
    ),
    'idx_goods_type' => 
    array (
      'columns' => 
      array (
        0 => 'goods_type',
      ),
    ),
    'idx_d_order' => 
    array (
      'columns' => 
      array (
        0 => 'd_order',
      ),
    ),
    'idx_goods_type_d_order' => 
    array (
      'columns' => 
      array (
        0 => 'goods_type',
        1 => 'd_order',
      ),
    ),
    'idx_marketable' => 
    array (
      'columns' => 
      array (
        0 => 'marketable',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
