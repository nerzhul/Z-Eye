<?php
	session_start();
	require_once(dirname(__FILE__)."/lib/FSS/FS.main.php");
	require_once(dirname(__FILE__)."/modules/ActionMgr.class.php");
	require_once(dirname(__FILE__)."/modules/Ajax.class.php");
	FS::LoadFSModules();
	if(FS::isAJAXCall()) {
		FS::$ajaxMgr->handle();
	}
	else if(FS::isActionToDo()) {
		$aMgr = new ActionMgr();
		$aMgr->DoAction(FS::$secMgr->checkAndSecuriseGetData("act"));
	}
	else {
		FS::$sessMgr->InitSessionIfNot();
		FS::$iMgr->stylesheet("styles/fss1.css");
		FS::$iMgr->stylesheet("styles/jQueryUI.css");
		FS::$iMgr->jsinc("lib/jQuery/jQuery.js");
		FS::$iMgr->jsinc("lib/jQuery/jQueryUI.js");
		FS::$iMgr->jsinc("lib/jQuery/jQuery.mousewheel.js");
		FS::$iMgr->jsinc("lib/jQuery/jQuery.mapbox.js");
		FS::$iMgr->jsinc("lib/FSS/js/FS-math.js");
		FS::$iMgr->jsinc("lib/FSS/js/FS-Regex.js");
		FS::$iMgr->jsinc("lib/FSS/js/FS-interface.js");
		FS::$iMgr->jsinc("lib/HighCharts/highcharts.min.js");

		echo FS::$iMgr->header();
		echo FS::$iMgr->content();
		echo FS::$iMgr->footer();
	}
?>
