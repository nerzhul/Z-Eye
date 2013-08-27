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
	
	final class iUserSettings extends FSModule {
		function __construct($locales) {
			parent::__construct($locales);
		}
		
		public function Load() {
			$output = FS::$iMgr->h1("Settings").
				$this->showAndroidForm();

			return $output;
		}

		private function showAndroidForm() {
			$apiKey = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","android_api_key","uid = '".FS::$sessMgr->getUid()."'");
			$data = FS::$dbMgr->getOneEntry(PgDbConfig::getDbPrefix()."user_settings_android","enable_monitor","uid = '".FS::$sessMgr->getUid()."'");

			$enmon = ($data["enable_monitor"] == 't' ? true : false);

			$output = FS::$iMgr->h2("Android-options").FS::$iMgr->cbkForm("1")."<table>".
				FS::$iMgr->idxLines(array(
					array("API-Key","",array("type" => "raw", "value" => $apiKey)),
					array("Enable-Monitoring","enmon",array("type" => "chk", "value" => $enmon)),
				)).
				FS::$iMgr->aeTableSubmit(false);
			return $output;
		}

		public function getMonitorOption() {
			$apikey = FS::$secMgr->checkAndSecurisePostData("apikey");
			// true and false are strings, it's a JSON answer 
			$enmon = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."user_settings_android","enable_monitor",
				"uid = (SELECT uid FROM ".PgDbConfig::getDbPrefix()."users WHERE android_api_key = '".$apikey."')");
			if ($enmon == 't') {
				return "true";
			}

			return "false";
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");

					FS::$dbMgr->BeginTr();

					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."user_settings_android","uid = '".FS::$sessMgr->getUid()."'");

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."user_settings_android","uid,enable_monitor",
						"'".FS::$sessMgr->getUid()."','".($enmon == "on" ? 't' : 'f')."'");
					FS::$dbMgr->CommitTr();
					FS::$iMgr->ajaxEcho("Done");
					return;
				default: break;
			}
		}
	};
?>
