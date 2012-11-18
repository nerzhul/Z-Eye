<?php
	class genModule {
		function genModule() {
		}
		
		public function Load() { $this->iMgr->printError("Unknown module !"); }
		public function setModuleId($id) { $this->mid = $id; }
		protected $mid;
		protected $loc;
	}
?>
