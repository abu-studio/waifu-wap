<?php
class unionpay_finder_logs {
    
    function __construct(&$app){
        $this->app = $app;
        $this->router = app::get('unionpay')->router();
    }

    public $detail_basic = '日志详情';
    public function detail_basic($id){
        $render = $this->app->render();
        $arr_logs = $this->app->model('logs')->dump($id);
        $render->pagedata['arr_logs'] = $arr_logs;
        return $render->fetch('admin/detail.html',$this->app->app_id);
    }
  
}
