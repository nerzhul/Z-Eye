<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iSnortMgmt extends genModule{
		function iSnortMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Management du service de détection d'intrusions SNORT</h3>";
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
