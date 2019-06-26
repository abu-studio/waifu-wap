<?php
class cellphone_ctl_site_member extends business_ctl_site_member
{
    function tags($nPage=1)
    {
        $this->path[] = array('title'=>app::get('business')->_('店铺管理'),'link'=>$this->gen_url(array('app'=>'business', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('business')->_('标签列表'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
		
        $this->pagedata['type'] = $type = (isset($_GET['type'])&&$_GET['type']==1)?1:0;
        $oTag = &app::get('cellphone')->model('tag');
        
        if(!$type){
            $filter = array('store_id'=>$this->store_id,'tag_type'=>'goods','app_id'=>'cellphone');
        }else{
            $filter = array('store_id'=>$this->store_id,'tag_type'=>'activity','app_id'=>'cellphone');
        }
        $count = $oTag->count($filter);
        $aPage = $this->get_start($nPage,$count);
        $this->pagedata['data'] = $oTag->getList('tag_id,tag_name,tag_abbr',$filter,$aPage['start'],$this->pagesize);
        $this->pagedata['total'] = $count;
        $this->pagedata['pager'] = array(
                'current'=>$nPage,
                'total'=>$aPage['maxPage'],
                'link' =>$this->gen_url(array('app'=>'cellphone', 'ctl'=>'site_member','act'=>'tags','args'=>array(($tmp = time())))),
                'token'=>$tmp,
                );
        $this->pagedata['_PAGE_'] = 'tag_list.html';
        $this->output('cellphone');
    }
    
    function edit_tags($tag_id)
    {
        $this->path[] = array('title'=>app::get('business')->_('店铺管理'),'link'=>$this->gen_url(array('app'=>'business', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('business')->_('标签维护'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        
        $this->pagedata['type'] = array(
            'goods'=>'商品标签',
            'activity'=>'活动标签',
        );
        $oTag = &app::get('cellphone')->model('tag');
        if(!$tag_id){
            // nothing
        }elseif($data=$oTag->getList('*',array('tag_id'=>$tag_id))){
            $this->pagedata['data'] = $data[0];
        }else{
            // nothing
        }
        $this->pagedata['_PAGE_'] = 'tag_edit.html';
        $this->output('cellphone');
    }
    
    function save_tags()
    {
        $oTag = &app::get('cellphone')->model('tag');
        if(isset($_POST['tag_id']) && $_POST['tag_id']){
            $count = $oTag->count(array('tag_id'=>$_POST['tag_id'],'store_id'=>$this->store_id));
            if($count && $count > 0){
                $data = array(
                    'tag_id' => $_POST['tag_id'],
                    'tag_name' => $_POST['tag_name'],
                    'app_id' => 'cellphone',
                    'tag_type' => $_POST['tag_type'],
                    'tag_abbr' => $_POST['tag_abbr'],
                    'store_id' => $this->store_id,
                );
            }else{
                $this->splash('failed','',app::get('b2c')->_('添加失败: 参数提交错误！！'),'','',true);
            }
        }else{
            $data = array(
                'tag_name' => $_POST['tag_name'],
                'app_id' => 'cellphone',
                'tag_type' => $_POST['tag_type'],
                'tag_abbr' => $_POST['tag_abbr'],
                'store_id' => $this->store_id,
            );
        }
        
        $flag = $oTag->save($data);
        if($flag){
            $this->splash('success','',app::get('b2c')->_('添加成功！'),'','',true);
        }else{
            $this->splash('failed','',app::get('b2c')->_('添加失败！'),'','',true);
        }
    }
    
    function delete_tags()
    {
        $type = (isset($_GET['type'])&&$_GET['type']==1)?'activity':'goods';
        if(!isset($_POST['delete']) || empty($_POST['delete'])){
            $this->splash('failed','',app::get('b2c')->_('删除失败: 没有选中任何记录！！'),'','',true);
        }
        $oTag = &app::get('cellphone')->model('tag');
        $count = $oTag->count(array('tag_id'=>$_POST['delete'],'tag_type'=>$type,'store_id'=>$this->store_id));
        if($count != count($_POST['delete'])){
            $this->splash('failed','',app::get('b2c')->_('删除失败: 参数提交错误！！'),'','',true);
        }
        $oTagRel = &app::get('cellphone')->model('tag_rel');
        $count = $oTagRel->count(array('tag_id'=>$_POST['delete']));
        if($count > 0){
            $this->splash('failed','',app::get('b2c')->_('删除失败: 有标签关联数据！！'),'','',true);
        }
        $flag = $oTag->delete(array('tag_id'=>$_POST['delete'],'app_id'=>'cellphone','tag_type'=>$type));
        $url = $this->gen_url(array('app' => 'cellphone', 'ctl' => 'site_member', 'act' => 'tags'))."?type={$_GET['type']}";
        if($flag){
            $this->splash('success',$url,app::get('b2c')->_('删除成功！'),'','',true);
        }else{
            $this->splash('failed','',app::get('b2c')->_('删除失败！'),'','',true);
        }
    }
    
    function set_tags()
    {
        $this->pagedata['app_id'] = $_POST['app_id'];
        $this->pagedata['tag_type'] = $_POST['tag_type'];
        $this->pagedata['gdata'] = $_POST['item'];
        $this->pagedata['gfilter'] = array('store_id'=>$this->store_id);
        $oTagRel = &app::get('cellphone')->model('tag_rel');
        
        $this->pagedata['tdata'] = array();
        if($this->pagedata['gdata']){
            foreach($oTagRel->getList('tag_id',array('rel_id'=>$this->pagedata['gdata'],'tag_type'=>$_POST['tag_type'])) as $row){
                $this->pagedata['tdata'][] = $row['tag_id'];
            }
        }
        
        $this->pagedata['tfilter'] = array('store_id'=>$this->store_id,'tag_type'=>$_POST['tag_type']);
        $this->display('site/member/tag_set.html', 'cellphone');
    }
    
    function toadd()
    {
        $oTagRel = &app::get('cellphone')->model('tag_rel');
        $flag = $oTagRel->delete(array('rel_id'=>$_POST['rel_id'],'app_id'=>$_POST['app_id'],'tag_type'=>$_POST['tag_type']));
        foreach((array)$_POST['tag_id'] as $tag){
            foreach((array)$_POST['rel_id'] as $object){
                $data = array(
                    'tag_id' => $tag,
                    'rel_id' => $object,
                    'app_id' => $_POST['app_id'],
                    'tag_type' => $_POST['tag_type'],
                );
                if($flag)$flag = $oTagRel->save($data);
            }
        }
        if($flag){
            if($_POST['tag_type'] == 'goods'){
                $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_member', 'act' => 'goods_onsell'));
            }else{
                $url = $this->gen_url(array('app' => 'business', 'ctl' => 'site_activity', 'act' => 'myAttend'));
            }
            
            $this->splash('success',$url,app::get('b2c')->_('添加成功！'),'','',true);
        }else{
            $this->splash('failed','',app::get('b2c')->_('添加失败！'),'','',true);
        }
    }
}