<?php
	class genModule {
		function genModule($interfaceMgr) {
			$this->iMgr = $interfaceMgr;
		}
		
		public function Load() { $this->iMgr->printError("Module inconnu !"); }
		protected $iMgr;
	}
?>
