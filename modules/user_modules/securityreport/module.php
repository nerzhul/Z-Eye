<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iSecReport extends genModule{
		function iSecReport() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Rapports de Sécurité</h3>";
			$output .= "</div>";
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					break;
				default: break;
			}
		}
	};
?>
