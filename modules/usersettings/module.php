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
	
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	
	if(!class_exists("iUserSettings")) {
	
	final class iUserSettings extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lUserSettings();
			$this->rulesclass = new rUserSettings($this->loc);
			$this->menu = $this->loc->s("menu-name");
		}
		
		public function Load() {
			FS::$iMgr->setURL("");
			
			return sprintf("%s%s%s",
				FS::$iMgr->h1("Settings"),
				$this->showUserForm(),
				$this->showAndroidForm()
			);
		}
		
		private function showUserForm() {
			$lang = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","lang","uid = '".FS::$sessMgr->getUid()."'");
			$inactivityTimer = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","inactivity_timer","uid = '".FS::$sessMgr->getUid()."'");
			
			$langSelect = sprintf("%s%s%s%s</select>",
				FS::$iMgr->select("lang"),
				FS::$iMgr->selElmt($this->loc->s("Default"),"none",($lang != "en" && $lang != "fr")),
				FS::$iMgr->selElmt($this->loc->s("English"),"en",($lang == "en")),
				FS::$iMgr->selElmt($this->loc->s("French"),"fr",($lang == "fr"))
			);
			
			$inactSelect = sprintf("%s%s%s%s%s%s%s%s%s</select>",
				FS::$iMgr->select("intim"),
				FS::$iMgr->selElmt($this->loc->s("Default"),"0",true),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(60),"1",($inactivityTimer == 1)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(120),"2",($inactivityTimer == 2)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(300),"5",($inactivityTimer == 5)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(600),"10",($inactivityTimer == 10)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(1200),"20",($inactivityTimer == 20)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(1800),"30",($inactivityTimer == 30)),
				FS::$iMgr->selElmt(FSTimeMgr::genStr(3600),"60",($inactivityTimer == 60))
			);
			
			return sprintf("%s%s<table>%s%s",
				FS::$iMgr->h2("Account-parameters"),
				FS::$iMgr->cbkForm("2"),
				FS::$iMgr->idxLines(array(
					array("App-Lang","",array("type" => "raw", "value" => $langSelect)),
					array("Disconnect-after","",array("type" => "raw", "value" => $inactSelect, "tooltip" => "tooltip-disconnect-after")),
				)),
				FS::$iMgr->aeTableSubmit(false)
			);
		}

		private function showAndroidForm() {
			$apiKey = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","android_api_key","uid = '".FS::$sessMgr->getUid()."'");
			$data = FS::$dbMgr->getOneEntry(PgDbConfig::getDbPrefix()."user_settings_android","enable_monitor","uid = '".FS::$sessMgr->getUid()."'");

			$enmon = ($data["enable_monitor"] == 't');

			return sprintf("%s%s<table>%s%s",
				FS::$iMgr->h2("Android-options"),
				FS::$iMgr->cbkForm("1"),
				FS::$iMgr->idxLines(array(
					array("API-Key","",array("type" => "raw", "value" => $apiKey)),
					array("Enable-Monitoring","enmon",array("type" => "chk", "value" => $enmon)),
				)),
				FS::$iMgr->aeTableSubmit(false)
			);
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
				// Set android options
				case 1:
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");

					FS::$dbMgr->BeginTr();

					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."user_settings_android","uid = '".FS::$sessMgr->getUid()."'");

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."user_settings_android","uid,enable_monitor",
						"'".FS::$sessMgr->getUid()."','".($enmon == "on" ? 't' : 'f')."'");
					FS::$dbMgr->CommitTr();
					FS::$iMgr->ajaxEchoOK("Done");
					return;
				// Set account options
				case 2:
					$lang = FS::$secMgr->checkAndSecurisePostData("lang");
					$inactivityTimer = FS::$secMgr->checkAndSecurisePostData("intim");
					
					if (!$lang || !$inactivityTimer || !FS::$secMgr->isNumeric($inactivityTimer)) {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}
					
					if ($lang == "none") {
						$lang = "";
					}
					
					if ($lang && $lang != "fr" && $lang != "en") {
						FS::$iMgr->ajaxEchoError("err-bad-lang");
						return;
					}
					
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Update(PgDbConfig::getDbPrefix()."users","lang = '".$lang."', inactivity_timer = '".$inactivityTimer."'","uid = '".FS::$sessMgr->getUid()."'");
					FS::$dbMgr->CommitTr();
					
					// Set lang
					if ($_SESSION["lang"] != $lang) {
						$_SESSION["lang"] = $lang;
						FS::$iMgr->js("loadWindowHead();");
					}
					else {
						$_SESSION["lang"] = FS::$sessMgr->getBrowserLang();
					}
			
					$js = "setMaxIdleTimer('".$inactivityTimer."');"; 
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				default: break;
			}
		}
	};
	
	}
	
	$module = new iUserSettings();
?>
