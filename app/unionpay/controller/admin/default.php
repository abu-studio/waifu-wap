<?php
/**
* 查看操作员日志控制器
*/
class unionpay_ctl_admin_default extends desktop_controller
{
    /**
    * 操作员日志列表
    * @access public 
    */
    public function index() 
    {
          $this->finder(
            'unionpay_mdl_logs', array(
                'title' => $this->app->_('数据交互日志'),
                'use_buildin_recycle' => true,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
            )
        );
    }//End Function

}//End Class