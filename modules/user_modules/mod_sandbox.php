<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	class iSandbox extends genModule{
		function iSandbox($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<img src=\"index.php?mod=35&at=2\"/>";
			return $output;
		}
	};
?>
