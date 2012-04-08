<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetSpeedMgmt extends genModule{
		function iNetSpeedMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Management du moteur d'Analyse des Débits</h3>";
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
