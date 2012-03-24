<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNagios extends genModule{
		function iNagios($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<h3>Management de Nagios (icinga)</h3>";
			return $output;
		}

		protected function getHostList() {
			$hl = array();
			return $hl;
		}
	};
?>
