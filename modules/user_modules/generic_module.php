<?php
	class genModule {
		function genModule() {
		}
		
		public function Load() { $this->iMgr->printError("Module inconnu !"); }
		public function setModuleId($id) { $this->mid = $id; }
		protected $mid;
		protected $loc;
	}
?>
