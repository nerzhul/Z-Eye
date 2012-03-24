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
		FS::$iMgr->addStylesheet("styles/fss1.css");
		FS::$iMgr->addJSFile("lib/jQuery/jQuery-1.5.2.js");
		FS::$iMgr->addJSFile("modules/js/canvas.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-math.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-Regex.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-interface.js");
		FS::$iMgr->addJSFile("modules/js/dhcp.js");
		FS::$iMgr->addJSFile("modules/js/dns.js");
		FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.core.js");
		FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.tooltips.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.effects.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.pie.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.context.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.annotate.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.common.zoom.js");
                FS::$iMgr->addJSFile("lib/RGraph/RGraph.line.js");

		echo FS::$iMgr->showHeader();
		echo FS::$iMgr->showContent();
		echo FS::$iMgr->showFooter();
	}
?>
