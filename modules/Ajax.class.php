<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	
	require_once(dirname(__FILE__)."/ActionMgr.class.php");
	class AjaxManager {
		function __construct() {}
		
		private function honeyPot() {
		}
		
		public function handle() {
			$type = FS::$secMgr->checkAndSecuriseGetData("at");
			switch($type) {
				// menu
				case 1:
					echo json_encode(array(
						"htmldatas" => FS::$iMgr->showWindowHead(),
						"jscode" => ""
					));
					break;
				// module
				case 2: 
					$mod = FS::$secMgr->checkAndSecuriseGetData("mod");
					if($mod == 0) {
						$mod = FS::$iMgr->getModuleIdByPath("default");
					}
					
					$noJSON = FS::$secMgr->checkAndSecuriseGetData("nojson");
					if ($noJSON == 1) {
						echo FS::$iMgr->loadModule($mod);
						// noJSON is for specific JS modules, then JS mustn't appear here
					}
					else {
						echo json_encode(array(
							"htmldatas" => FS::$iMgr->loadModule($mod),
							"jscode" => FS::$iMgr->renderJS()
						));
					}
					break;
				// Action Handler
				case 3:
					$aMgr = new ActionMgr();
					$aMgr->DoAction(FS::$secMgr->checkAndSecuriseGetData("act"));
					break;
				// module: getIfaceElmt()
				case 4: 
					$mod = FS::$secMgr->checkAndSecuriseGetData("mod");
					echo json_encode(array(
						"htmldatas" => FS::$iMgr->loadModule($mod,2),
						"jscode" => FS::$iMgr->renderJS()
					));
					break;
				// special case: disconnect user
				case 5:
					if ($module = FS::$iMgr->getModuleByPath("connect")) {
						$module->Disconnect(true);
						echo json_encode(array(
							"htmldatas" => "",
							"jscode" => FS::$iMgr->renderJS()
						));
					}
					break;
				// Reload all footer plugins
				case 6:
					FS::$iMgr->loadFooterPlugins();
					//echo FS::$iMgr->renderJS();
					echo json_encode(array(
						"htmldatas" => "",
						"jscode" => FS::$iMgr->renderJS()
					));
					break;
				default: $this->honeyPot(); break;
			}
		}
	}


?>
