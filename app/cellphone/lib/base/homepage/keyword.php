
<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class  cellphone_base_homepage_keyword extends cellphone_cellphone{
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }
//  获得关键字列表
   function getlist(){
        $params = $this->params;
      
        if($params['pagelimit']){
            $pagelimit=$params['pagelimit'];
        }else{
            $pagelimit=7;
        }

        if($params['nPage']){
            $nPage=$params['nPage'];
        }else{
            $nPage=1;
        }
		$mobj_keyword = app :: get('cellphone')->model('keyword');
        $aData = $mobj_keyword->getList('keyword_id,keyword,d_order',array(),$pagelimit*($nPage-1),$pagelimit,'d_order  ASC');
		
        if($aData){
		
		$this->send(true,$aData,app::get('cellphone')->_('关键字列表'));

		}

        else{
		$this->send(true,null,app::get('cellphone')->_('没有数据'));
		}
        
      

        

       
   
   }
}