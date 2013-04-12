<?php
	/*
        * 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */

	session_start();
	require_once(dirname(__FILE__)."/lib/FSS/FS.main.php");
	require_once(dirname(__FILE__)."/modules/ActionMgr.class.php");
	require_once(dirname(__FILE__)."/modules/Ajax.class.php");

	//$start_time = microtime(true);

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
		FS::$iMgr->jsinc("lib/jQuery/jquery.jqzoom.js");
		FS::$iMgr->jsinc("lib/jQuery/jquery.tablesorter.js");
		FS::$iMgr->jsinc("lib/FSS/js/FSS.js");
		FS::$iMgr->jsinc("lib/HighCharts/highcharts.min.js");

		echo FS::$iMgr->content();
		echo FS::$iMgr->renderJS();
		echo FS::$iMgr->footer();
	}
	/* For optimize times
	$end_time = microtime(true);
        $script_time = $end_time - $start_time;
	echo $script_time; */
?>
