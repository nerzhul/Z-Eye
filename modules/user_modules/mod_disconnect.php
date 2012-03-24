<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	class iDisconnect extends genModule{
		function iDisconnect($iMgr) { parent::genModule($iMgr); }
		
		public function Load() {
			$link = new HTTPLink(16);
			$output = "<div id=\"module_connect\"><h4>Déconnexion</h4><form action=\"".$link->getIt()."\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
			$output .= $this->iMgr->addSubmit("disconnect","Confirmer");
			$output .= "</form></div>";
			return $output;
		}
		
		public function Disconnect() {
			$act = FS::$secMgr->checkGetData("act");
			switch($act) {
				case 1: FS::$sessMgr->Close(); break;
				default: break;
			}
			header("Location: index.php");
		}
	};
?>