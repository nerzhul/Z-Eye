<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	require_once(dirname(__FILE__)."/user_objects/NTPServer.class.php");
	class iNTP extends genModule{
		function iNTP($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "";
			if($do = FS::$secMgr->checkAndSecuriseGetData("do")) {
				switch($do) {
					case 1: $output .= $this->showAddingServer(); break;
					case 2: $output .= $this->showAddingServer(true); break;
					default: $output .= $this->showMain(); break;
				}
			}
			else
				$output .= $this->showMain();
				
			return $output;
		}
		
		private function showMain() {
			$output = "<div id=\"monoComponent\"><h3>Configuration du serveur de Temps</h3>".FS::$iMgr->addJSONLink('{ "at": "2", "mid": "37", "do": "1" }',"Ajouter un serveur NTP")."<br /><br />";
			
			
			
			$output .= "</div>";
			return $output;
		}
		
		private function showAddingServer($edit = false) {
			$link = new HTTPLink(100);
			$output = "<div id=\"monoComponent\"><h3>Ajouter un serveur de Temps</h3>";
			$output .= FS::$iMgr->addForm($link->getIt());
			if($edit) {
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$ntpserver = new NTPServer($addr);
				if(!$ntpserver->Load()) {
					$output .= FS::$iMgr->printError("Ce serveur NTP n'existe pas !")."</div>";
					return $output;
				}
			}
			$output .= "<table class=\"standardTable\" width=\"60%\">";
			$output .= $this->iMgr->addIndexedLine("Adresse IP du serveur","ipaddr",$edit ? $ntpserver->getServerAddr() : "");
			$output .= $this->iMgr->addIndexedCheckLine("Préféré ?","pref", $edit ? $ntpserver->getPrefered() : false);
			$output .= $this->iMgr->addIndexedCheckLine("Désactivé ?","disable",$edit ? $ntpserver->getDisabled() : false);
			$output .= FS::$iMgr->addTableSubmit("submit",$edit ? "Enregistrer" : "Ajouter");
			$output .= "</table></form>";
			$output .= "</div>";
			return $output;
		}
	};
?>