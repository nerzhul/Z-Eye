<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iDisconnect extends genModule{
		function iDisconnect() { parent::genModule(); }
		
		public function Load() {
			$output = "<div id=\"module_connect\"><h4>Déconnexion</h4><form action=\"index.php?mod=".$this->mid."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
			$output .= FS::$iMgr->addSubmit("disconnect","Confirmer");
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
		
		public function handlePostDatas($act) {
			$this->Disconnect();
		}
	};
?>