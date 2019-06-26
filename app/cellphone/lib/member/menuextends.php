<?php

class cellphone_member_menuextends
{
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_extends_menu(&$arr_menus, $args=array())
    {
       /*
        $arr_extends = array(
            array(
                'label' => app::get('b2c')->_('标签管理'),
                'mid' => 11,
                'items' => array(
                    array('label' => app::get('b2c')->_('设置标签'), 'app' => 'cellphone', 'ctl' => 'site_member', 'link' => 'tags'),
                )
            ),
        );

        $arr_menus = array_merge($arr_menus, $arr_extends);
        */

       /* foreach($arr_menus as $key=>&$group){
          if($group['label']=='店铺管理'){
                 array_push($arr_menus[$key]['items'], array('label' => app::get('b2c')->_('设置标签'), 'app' => 'cellphone', 'ctl' => 'site_member', 'link' => 'tags'));
          }
        }*/

        return true;
    }
}