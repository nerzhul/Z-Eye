<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/
	
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
		FS::$iMgr->addStylesheet("styles/jQueryUI.css");
		FS::$iMgr->addJSFile("lib/jQuery/jQuery.js");
		FS::$iMgr->addJSFile("lib/jQuery/jQueryUI.js");
		FS::$iMgr->addJSFile("lib/jQuery/jQuery.mousewheel.js");
		FS::$iMgr->addJSFile("lib/jQuery/jQuery.mapbox.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-math.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-Regex.js");
		FS::$iMgr->addJSFile("lib/FSS/js/FS-interface.js");
		FS::$iMgr->addJSFile("lib/HighCharts/highcharts.min.js");

		echo FS::$iMgr->showHeader();
		echo FS::$iMgr->showContent();
		echo FS::$iMgr->showFooter();
	}
?>
