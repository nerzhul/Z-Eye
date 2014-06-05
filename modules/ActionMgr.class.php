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

	class ActionMgr {
		function __construct() {}

		public function HoneyPot() {
			echo "Honeypot";
		}

		public function DoAction($act) {
			if(!isset($_GET["mod"])) $_GET["mod"] = 0;
			$mid = FS::$secMgr->checkAndSecuriseGetData("mod");

			switch($mid) {
				case 0:
					break;
				default:
					$dir = opendir(dirname(__FILE__));
					$found = false;
					$moduleid = 0;
					while(($elem = readdir($dir)) && $found == false) {
						$dirpath = dirname(__FILE__)."/".$elem;
						if(is_dir($dirpath)) $moduleid++;
						if(is_dir($dirpath) && /*$elem == $mod_path*/$moduleid == $mid) {
							$dir2 = opendir($dirpath);
							while(($elem2 = readdir($dir2)) && $found == false) {
								if(is_file($dirpath."/".$elem2) && $elem2 == "module.php")
									$found = true;
									$mod_path = $elem;
							}
						}
					}

					if($found == false) {
						FS::$iMgr->redir("/", true);
						return;
					}

					require_once(dirname(__FILE__)."/".$mod_path."/module.php");
					
					
					
					$ruleAccess = $module->getRulesClass()->canAccessToModule();
					if($ruleAccess === false) {
						if(FS::isAjaxCall()) {
							FS::$iMgr->echoNoRights("access to module");
						}
						else {
							FS::$iMgr->redir("/", true);
						}
					}
					else if($ruleAccess === -1) {
						FS::$iMgr->js(sprintf("setLoginCbkMsg('<span style=\"color: red;\">%s</span>');openLogin();",
							FS::$iMgr->getLocale("err-must-be-connected")));
					}
					else {
						FS::$iMgr->setCurrentModule($module);
						$module->setModuleId($mid);
						$module->handlePostDatas($act);
					}

					echo json_encode(array(
						"htmldatas" => FS::$iMgr->getAjaxEchoBuffer(),
						"jscode" => FS::$iMgr->renderJS()
					));
					break;
			}
		}
	};
?>
