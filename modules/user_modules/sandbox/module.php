<?php
require_once(dirname(__FILE__)."/../generic_module.php");
include_once(dirname(__FILE__)."/../../../lib/FSS/modules/RGraph.FS.class.php");
	class iSandBox extends genModule{
		function iSandBox() { parent::genModule(); }
		
		public function Load() {
			$output = "";
			$output .= FS::$iMgr->addCanvas("testcanvas");
			$graph = new FSRGraph();
			$graph->setType(1);
			$graph->setName("testcanvas");
			$graph->setProperty("background.barcolor1","'#000'");
			$graph->setProperty("shadow","true");
			$graph->setProperty("shadow.blur","15");
			$graph->setValues("1,6,9,8,36,54,4,8,6,5,7,5,2,3");
			// ALl properties
			$output .= $graph->create();
			return $output;
		}
	};
?>
