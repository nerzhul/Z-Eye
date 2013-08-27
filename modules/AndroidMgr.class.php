<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	class AndroidMgr {
		function __construct() {}

		public function Work() {
			$apikey = FS::$secMgr->checkAndSecurisePostData("apikey");
			$act = FS::$secMgr->checkAndSecurisePostData("act");

			// If no action we present the server
			if (!$act) {
				echo "{'server': 'Z-Eye','version':'".Config::getWebsiteName()."', 'code': ".AndroidMgr::$ZEYECODE_SERVER."}";
				return;
			}

			// APIKey and action are necessary
			if (!$apikey) {
				echo "{'code': ".AndroidMgr::$ZEYECODE_KEY_INVALID."}";
				return;
			}

			$cm = FS::$iMgr->loadModule(FS::$iMgr->getModuleIdByPath("connect",3));
			if ($cm) {
				echo "{'code': ".AndroidMgr::$ZEYECODE_KEY_INVALID."}";
				return;
			}
			
			// Now we use actions to determine what to do
			switch ($act) {
				case "auth":
					echo "{'code': ".AndroidMgr::$ZEYECODE_KEY_VALID."}";
					return;
				case "loadmm":
					echo "{'code': 5, 'monitor_allowed': true, 'serverinfos_allowed': true}";
					return;
				case "getsinfos":
					$uptime = shell_exec("/usr/bin/uptime | awk -F',' '{print $1}'");
					$charge = shell_exec("/usr/bin/uptime |awk -F': ' '{print $2}'");
					echo "{'code': ".AndroidMgr::$ZEYECODE_REQUEST_OK.", 'version':'".Config::getWebsiteName().
						"', 'uptime':'".$uptime."', 'charge':'".$charge."'}"; break;
					break;
				default:
					echo "{'code': ".AndroidMgr::$ZEYECODE_ACTION_INVALID."}";
					return;
			}
		}

		private static $ZEYECODE_SERVER = 1;
		private static $ZEYECODE_KEY_VALID = 2;
		private static $ZEYECODE_KEY_INVALID = 3;
		private static $ZEYECODE_ACTION_INVALID = 4;
		private static $ZEYECODE_REQUEST_OK = 5;
		private static $ZEYECODE_SERVER_ERROR = 6;
		private static $ZEYECODE_REQUEST_FAIL = 7;
	}
?>
