<?php
	require_once(dirname(__FILE__)."/../../../lib/FSS/objects/InterfaceModule.FS.class.php");
	require_once(dirname(__FILE__)."/module.php");
	
	class iModule extends InterfaceModule {
		function iModule() {
			parent::InterfaceModule();
			$this->conf->modulename = "iMenuMgmt";
			$this->conf->seclevel = 6;
			$this->conf->connected = 1;
			$this->moduleclass = new iMenuMgmt();
		}
	};
?>