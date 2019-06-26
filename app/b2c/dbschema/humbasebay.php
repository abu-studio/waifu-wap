<?php

$db['humbasebay'] = array(
    'columns' => array(
        'id'          => array(
            'type'     => 'int(11)',
            'required' => true,
            'pkey'     => true,
            'label'    => 'id',
            'editable' => false,
            'extra'    => 'auto_increment',
        ),
        'humbas'      => array(
            'type'            => 'varchar(32)',
            'label'           => app::get('b2c')->_('人才号'),
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
        ),
        'ebay'        => array(
            'type'            => 'varchar(32)',
            'label'           => app::get('b2c')->_('ebay账号'),
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
        ),

        'status'      => array(
            'type'            => array(
                'bind'   => app::get('b2c')->_('绑定'),
                'unbind' => app::get('b2c')->_('未绑定'),
            ),
            'default'         => 'unbind',
            'label'           => app::get('b2c')->_('绑定状态'),
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
        ),

        'create_time' => array(
            'type'     => 'time',
            'comment'  => app::get('b2c')->_('绑定时间'),
            'editable' => false,
            'label'    => app::get('b2c')->_('绑定时间'),
            'in_list'  => true,
        ),
    ),
    'comment' => app::get('b2c')->_('ebay绑定表'),

);
