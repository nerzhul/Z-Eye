<?php
	class ActionMgr {
		function ActionMgr() {}
		
		public function HoneyPot() {
			echo "Honeypot";
		}
		
		public function DoAction($act) {
			if(!isset($_GET["mod"])) $_GET["mod"] = 0;
			$mid = FS::$secMgr->checkAndSecuriseGetData("mod");
			
			switch($mid) {
				case 0:
					break;
				default:					
					$dir = opendir(dirname(__FILE__)."/user_modules");
					$found = false;
					$moduleid = 0;
					while(($elem = readdir($dir)) && $found == false) {
						$dirpath = dirname(__FILE__)."/user_modules/".$elem;
						if(is_dir($dirpath)) $moduleid++;
						if(is_dir($dirpath) && /*$elem == $mod_path*/$moduleid == $mid) {
							$dir2 = opendir($dirpath);
							while(($elem2 = readdir($dir2)) && $found == false) {
								if(is_file($dirpath."/".$elem2) && $elem2 == "main.php")
									$found = true;
									$mod_path = $elem;
							}
						}
					}
					
					if($found == false) {
						header("Location: index.php");
						return;
					}				
				
					require_once(dirname(__FILE__)."/user_modules/".$mod_path."/main.php");
					$md = new iModule();
					
					if(!($md->getConfig()->connected == 1 && FS::$sessMgr->isConnected() || 
						$md->getConfig()->connected == 0 && !FS::$sessMgr->isConnected() || $md->getConfig()->connected == 2)) {
						
						header("Location: index.php");
						return;
					}
					
					if($md->getConfig()->seclevel > FS::$sessMgr->getUserLevel()) {
						header("Location: index.php");
						return;
					}
							
					$module = $md->getModuleClass();
					$md->getModuleClass()->setModuleId($mid);
					$md->getModuleClass()->handlePostDatas($act);
					
					break;
			}
		}
	};
?>
