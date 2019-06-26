<?php
class physical_view_input {
	function input_calendar($params){
		$render = new base_render(app::get('physical'));
		$tools=new physical_tools();
		$time=$tools->calendar($params['store']);
		$render->pagedata['time_status']=$time;
		$base=kernel::base_url();
		$render->pagedata['base_url']=$base;
		$render->pagedata['params']=$params;
		$html=$render->fetch('admin/calendar.html');
		return $html;
	}
	
	
	
}