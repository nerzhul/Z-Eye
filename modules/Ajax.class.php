<?php
	require_once(dirname(__FILE__)."/ActionMgr.class.php");
	class AjaxManager {
		function AjaxManager() {}
		
		private function honeyPot() {
		}
		
		public function handle() {
			$type = FS::$secMgr->checkAndSecuriseGetData("at");
			switch($type) {
				case 1: // menu
					$mid = FS::$secMgr->checkAndSecuriseGetData("mid");
					echo FS::$iMgr->LoadMenu($mid);
					break;
				case 2: // module
					$mid = FS::$secMgr->checkAndSecuriseGetData("mod");
					echo FS::$iMgr->loadModule($mid);
					break;
				case 3: // Action Handler
					$aMgr = new ActionMgr();
					$aMgr->DoAction(FS::$secMgr->checkAndSecuriseGetData("act"));
					break;
				default: $this->honeyPot(); break;
			}
		}
	}


?>
