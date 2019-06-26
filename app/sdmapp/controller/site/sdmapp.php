<?php

	class sdmapp_ctl_site_sdmapp extends b2c_frontpage{	
		public function index(){
			$this->set_tmpl("shuidianmei");
			$this->page();
		}
	}