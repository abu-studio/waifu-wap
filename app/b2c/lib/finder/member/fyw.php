<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2
 * Time: 16:14
 */
class b2c_finder_member_fyw {
    var $column_edit = '编辑';
    function column_edit($row){
        return '<a href="index.php?app=b2c&ctl=admin_member_fyw&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['member_fyw_id'].'" target="dialog::{title:\''.app::get('b2c')->_('编辑福员外会员信息').'\', width:680, height:250}">'.app::get('b2c')->_('编辑').'</a>';

    }
}