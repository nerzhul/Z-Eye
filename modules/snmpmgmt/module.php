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
	require_once(dirname(__FILE__)."/../netdisco/netdiscoCfg.api.php");

	if(!class_exists("iSNMPMgmt")) {

	final class iSNMPMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lSNMPmgmt();
			$this->rulesclass = new rSNMPmgmt($this->loc);
			$this->menu = $this->loc->s("menu-name");
			$this->modulename = "snmpmgmt";
		}

		public function Load() {
			FS::$iMgr->setURL("");
			return $this->showMain();
		}

		private function showMain() {
			$output = FS::$iMgr->h1("snmp-communities");
			FS::$iMgr->setTitle("snmp-communities");
			$found = false;

			$output .= FS::$iMgr->opendiv(1,$this->loc->s("Add-community"));


			// Div for Ajax modifications
			$tMgr = new HTMLTableMgr(array(
				"tabledivid" => "snmptable",
				"tableid" => "snmpList",
				"firstlineid" => "snmpthead",
				"sqltable" => "snmp_communities",
				"sqlattrid" => "name",
				"attrlist" => array(array("snmp-community","name",""), array("Read","ro","b"), array("Write","rw","b")),
				"sorted" => true,
				"odivnb" => 2,
				"odivlink" => "id=",
				"rmcol" => true,
				"rmlink" => "snmp",
				"rmactid" => 2,
				"rmconfirm" => "confirm-remove-community",
				"trpfx" => "sc"
			));
			$output .= $tMgr->render();
			return $output;
		}

		private function showCommunityForm($id = "") {
			if ($id) {
				return FS::$iMgr->fileGetContent("http://localhost:8080/snmpmgmt/forms/community?id=".$id);
			}
			else {
				return FS::$iMgr->fileGetContent("http://localhost:8080/snmpmgmt/forms/community");
			}
		}

		private function tableCommunityLine($name,$ro,$rw) {
			FS::$iMgr->setJSBuffer(1);
			return "<tr id=\"".FS::$iMgr->formatHTMLId($name)."tr\"><td>".FS::$iMgr->opendiv(2,$name,array("lnkadd" => "name=".$name)).
				"</td><td>".($ro ? "X" : "")."</td><td>".($rw ? "X": "")."</td><td>".
				FS::$iMgr->removeIcon(2,"snmp=".$name,array("js" => true,
					"confirmtext" => "confirm-remove-community",
					"confirmval" => $name
				))."</td></tr>";
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showCommunityForm();
				case 2:
					$id = FS::$secMgr->checkAndSecuriseGetData("id");
					if (!$id)
						return $this->loc->s("err-bad-datas");

					return $this->showCommunityForm($id);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add SNMP community
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$ro = FS::$secMgr->checkAndSecurisePostData("ro");
					$rw = FS::$secMgr->checkAndSecurisePostData("rw");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if (!$name || $ro && $ro != "on" || $rw && $rw != "on" || $edit && $edit != 1) {
						$this->log(2,"Invalid Adding data");
						FS::$iMgr->ajaxEchoErrorNC("err-invalid-data");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$name."'");
					if ($edit) {
						if (!$exist) {
							$this->log(1,"Community '".$name."' not exists");
							FS::$iMgr->ajaxEchoError("err-not-exist");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Community '".$name."' already in DB");
							FS::$iMgr->ajaxEchoErrorNC("err-already-exist");
							return;
						}
					}

					// User must choose read and/or write
					if ($ro != "on" && $rw != "on") {
						FS::$iMgr->ajaxEchoErrorNC("err-readorwrite");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if (!is_array($netdiscoCfg)) {
						$this->log(2,"Reading error on netdisco.conf");
						FS::$iMgr->ajaxEchoErrorNC("err-read-netdisco");
						return;
					}

					FS::$dbMgr->BeginTr();
					if ($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snmp_communities","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw","'".$name."','".($ro == "on" ? 't' : 'f')."','".
						($rw == "on" ? 't' : 'f')."'");
					FS::$dbMgr->CommitTr();

					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);

					$js = "";

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "snmptable",
						"sqltable" => "snmp_communities",
						"sqlattrid" => "name",
						"firstlineid" => "snmpthead",
						"odivnb" => 2,
						"odivlink" => "name=",
						"rmcol" => true,
						"rmlink" => "snmp",
						"rmactid" => 2,
						"rmconfirm" => "confirm-remove-community",
						"attrlist" => array(array("snmp-community","name",""), array("Read","ro","b"),
							array("Write","rw","b")),
						"trpfx" => "sc"
					));
					$js = $tMgr->addLine($name,$edit);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				case 2: // Remove SNMP community
					$name = FS::$secMgr->checkAndSecuriseGetData("snmp");
					if (!$name) {
						$this->log(2,"Invalid Deleting data");
						FS::$iMgr->ajaxEchoError("err-invalid-data");
						return;
					}
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$name."'")) {
						$this->log(2,"Community '".$name."' not in DB");
						FS::$iMgr->ajaxEchoError("err-not-exist");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if (!is_array($netdiscoCfg)) {
						$this->log(2,"Reading error on netdisco.conf");
						FS::$iMgr->ajaxEchoError("err-read-fail");
						return;
					}

					FS::$dbMgr->BeginTr();

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snmp_communities","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");

					FS::$dbMgr->CommitTr();

					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "snmptable",
						"sqltable" => "snmp_communities",
						"sqlattrid" => "name",
						"trpfx" => "sc"
					));

					$js = $tMgr->removeLine($name);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				default: break;
			}
		}
	};

	}

	$module = new iSNMPmgmt();
?>
