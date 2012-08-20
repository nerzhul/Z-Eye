<?php
	require_once(dirname(__FILE__)."/ModuleConfig.FS.class.php");
	class InterfaceModule {
		function InterfaceModule() {
			$this->conf = new ModuleConfig(); 
		}
		
		public function handlePostDatas() {}
		
		public function setModuleId($mid) { $this->mid = $mid; }
		public function getConfig() { return $this->conf; }
		public function getModuleClass() { return $this->moduleclass; }
		public function getRulesClass() { return $this->rulesclass; }

		protected $moduleclass;
		protected $conf;
		protected $mid;
		protected $rulesclass;
	};

?>
