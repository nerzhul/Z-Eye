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

	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/PDFgen.FS.class.php");
	require_once(dirname(__FILE__)."/objects.php");

	final class iRadius extends FSModule {
		function __construct($locales) {
			parent::__construct($locales);
			$this->modulename = "radius";

			$raddbinfos = array();
		}

		public function Load() {
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "";

			if (!FS::isAjaxCall()) {
				if (FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= FS::$iMgr->h1("title-deleg");
					FS::$iMgr->setTitle($this->loc->s("title-deleg"));
				}
				else {
					$output .= FS::$iMgr->h1("title-usermgmt");
					FS::$iMgr->setTitle($this->loc->s("title-usermgmt"));
				}
			}

			switch($err) {
				case 3: $output .= FS::$iMgr->printError("err-exist"); break;
				case 6: $output .= FS::$iMgr->printError("err-invalid-table"); break;
				case 7: $output .= FS::$iMgr->printError("err-bad-server"); break;
			}

			if (!FS::isAjaxCall()) {
				$edit = FS::$secMgr->checkAndSecuriseGetData("edit");
				if ($edit && FS::$sessMgr->hasRight("mrule_radius_manage")) {
					$output .= $this->showCreateOrEditRadiusDB(false);
				}
				else {
					if (FS::$sessMgr->hasRight("mrule_radius_manage")) {
						FS::$iMgr->setJSBuffer(1);
						$output .= FS::$iMgr->opendiv(1,$this->loc->s("Manage-radius-db"));
					}
					$output .= $this->showRadiusList($radalias);
				}
			}
			if ($radalias) {
				if (FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= $this->showDelegTool($radalias,$raddb,$radhost,$radport);
				}
				else {
					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$output .= FS::$iMgr->printError("err-db-conn-fail");
						return $output;
					}
					$radentry = FS::$secMgr->checkAndSecuriseGetData("radentry");
					$radentrytype = FS::$secMgr->checkAndSecuriseGetData("radentrytype");
					if ($radentry && $radentrytype && ($radentrytype == 1 || $radentrytype == 2)) {
						$output .= $this->editRadiusEntry($radSQLMgr,$radentry,$radentrytype);
					}
					else {
						$output .= $this->showRadiusAdmin($radSQLMgr);
					}
		 		}
			}
			else if (isset($sh)) {
				$output .= FS::$iMgr->printError("err-invalid-tab");
			}

			return $output;
		}

		private function showRadiusServerMgmt() {
			$output = "";

			$tmpoutput = FS::$iMgr->h2("title-radius-db");
			$tmpoutput .= "<table id=\"tRadiusList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Server")."</th><th>".$this->loc->s("Port")."</th><th>".$this->loc->s("db-type")."</th><th>"
				.$this->loc->s("Host")."</th><th>".$this->loc->s("Login")."</th><th></th><th></th></tr></thead>";

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","addr,port,dbname,login,dbtype,radalias");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if ($found == false) {
					$found = true;
				}
				
				$tmpoutput .= "<tr id=\"".preg_replace("#[.]#","-",$data["dbname"].$data["addr"].$data["port"])."\"><td>".
					FS::$iMgr->aLink($this->mid."&edit=1&addr=".$data["addr"]."&pr=".$data["port"]."&db=".$data["dbname"], $data["addr"]);
				$tmpoutput .= "</td><td>".$data["port"]."</td><td>";

				switch($data["dbtype"]) {
					case "my": $tmpoutput .= "MySQL"; break;
					case "pg": $tmpoutput .= "PgSQL"; break;
				}

				$tmpoutput .= "</td><td>".$data["dbname"]."</td><td>".$data["login"]."</td>";
				$tmpoutput .= "<td><div id=\"radstatus".preg_replace("#[.]#","-",$data["addr"].$data["port"].$data["dbname"])."\">".
					FS::$iMgr->img("styles/images/loader.gif",24,24)."</div>";
					
				FS::$iMgr->js("$.post('index.php?mod=".$this->mid."&act=15', { saddr: '".$data["addr"]."', sport: '".$data["port"]."', sdbname: '".$data["dbname"]."' }, function(data) {
					$('#radstatus".preg_replace("#[.]#","-",$data["addr"].$data["port"].$data["dbname"])."').html(data); });");
				
				$tmpoutput .= "</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=14&alias=".$data["radalias"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-datasrc")."'".$data["radalias"]."'",
							"Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("tRadiusList");
			}

			$output .= $this->showCreateOrEditRadiusDB(true);

			return $output;
		}

		private function showCreateOrEditRadiusDB($create) {
			$saddr = "";
			$slogin = "";
			$sdbname = "";
			$sport = 3306;
			$spwd = "";
			$salias = "";
			$sdbtype = "";
			$tradcheck = "radcheck";
			$tradreply = "radreply";
			$tradgrpchk = "radgroupcheck";
			$tradgrprep = "radgroupreply";
			$tradusrgrp = "radusergroup";
			$tradacct = "radacct";
			if ($create)
				$output = FS::$iMgr->h2("title-add-radius");
			else {
				$output = FS::$iMgr->h2("title-edit-radius");
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$port = FS::$secMgr->checkAndSecuriseGetData("pr");
				$dbname = FS::$secMgr->checkAndSecuriseGetData("db");
				if (!$addr || $addr == "" || !$port || !FS::$secMgr->isNumeric($port) || !$dbname || $dbname == "") {
					$output .= FS::$iMgr->printError("err-no-db");
					return $output;
				}
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","radalias,login,pwd,dbtype,tradcheck,tradreply,tradgrpchk,tradgrprep,tradusrgrp,tradacct",
					"addr = '".$addr."' AND port = '".$port."' AND dbname = '".$dbname."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$salias = $data["radalias"];
					$sport = $port;
					$sdbname = $dbname;
					$sdbtype = $data["dbtype"];
					$tradcheck = $data["tradcheck"];
					$tradreply = $data["tradreply"];
					$tradgrpchk = $data["tradgrpchk"];
					$tradgrprep = $data["tradgrprep"];
					$tradusrgrp = $data["tradusrgrp"];
					$tradacct = $data["tradacct"];
				}
				else {
					$output .= FS::$iMgr->printError("err-invalid-db");
					return $output;
				}
			}

			if (!$create) {
				$output .= FS::$iMgr->aLink($this->mid, $this->loc->s("Return"))."<br />";
				$err = FS::$secMgr->checkAndSecuriseGetData("err");
				switch($err) {
					case 2: $output .= FS::$iMgr->printError("err-miss-bad-fields"); break;
					case 3: $output .= FS::$iMgr->printError("err-server-exist"); break;
					case 7: $output .= FS::$iMgr->printError("err-bad-server"); break;
				}
			}

			$output .= FS::$iMgr->cbkForm("13");

			if (!$create) {
				$output .= FS::$iMgr->hidden("saddr",$saddr).
					FS::$iMgr->hidden("sport",$sport).
					FS::$iMgr->hidden("sdbname",$sdbname).
					FS::$iMgr->hidden("salias",$salias).
					FS::$iMgr->hidden("edit",1);
			}

			$output .= "<table>";
			if ($create) {
				$output .= FS::$iMgr->idxLine("ip-addr-dns","saddr",array("value" => $saddr)).
					FS::$iMgr->idxLine("Port","sport",array("value" => $sport, "type" => "num", "tooltip" => "tooltip-port")).
					"<tr><td>".$this->loc->s("db-type")."</td><td class=\"ctrel\">".FS::$iMgr->select("sdbtype").
					FS::$iMgr->selElmt("MySQL","my").FS::$iMgr->selElmt("PgSQL","pg")."</select></td></tr>".
					FS::$iMgr->idxLine("db-name","sdbname",array("value" => $sdbname,"tooltip" => "tooltip-dbname"));
			}
			else {
				$dbtype = "";
				switch($sdbtype) {
					case "my": $dbtype = "MySQL"; break;
					case "pg": $dbtype = "PgSQL"; break;
				}
				
				$output .= FS::$iMgr->idxLine("ip-addr-dns","",array("type" => "raw", "value" => $saddr)).
					FS::$iMgr->idxLine("Port","",array("type" => "raw", "value" => $sport)).
					FS::$iMgr->idxLine("db-type","",array("type" => "raw", "value" => $dbtype)).
					FS::$iMgr->idxLine("db-name","",array("type" => "raw", "value" => $sdbname));
			}
			$output .= FS::$iMgr->idxLine("User","slogin",array("value" => $slogin,"tooltip" => "tooltip-user")).
				FS::$iMgr->idxLine("Password","spwd",array("type" => "pwd")).
				FS::$iMgr->idxLine("Password-repeat","spwd2",array("type" => "pwd"));
			if ($create) {
				$output .= FS::$iMgr->idxLine("Alias","salias",array("value" => $salias,"tooltip" => "tooltip-alias"));
			}
			else {
				$output .= FS::$iMgr->idxLine("Alias","",array("type" => "raw", "value" => $salias));
			}
			$output .= "<th colspan=2>".$this->loc->s("Tables")."</th>".
				FS::$iMgr->idxLine("table-radcheck","tradcheck",array("value" => $tradcheck,"tooltip" => "tooltip-radcheck")).
				FS::$iMgr->idxLine("table-radreply","tradreply",array("value" => $tradreply,"tooltip" => "tooltip-radreply")).
				FS::$iMgr->idxLine("table-radgrpchk","tradgrpchk",array("value" => $tradgrpchk,"tooltip" => "tooltip-radgrpchk")).
				FS::$iMgr->idxLine("table-radgrprep","tradgrprep",array("value" => $tradgrprep,"tooltip" => "tooltip-radgrprep")).
				FS::$iMgr->idxLine("table-radusrgrp","tradusrgrp",array("value" => $tradusrgrp,"tooltip" => "tooltip-radusrgrp")).
				FS::$iMgr->idxLine("table-radacct","tradacct",array("value" => $tradacct,"tooltip" => "tooltip-radacct")).
				FS::$iMgr->tableSubmit("Save");

			return $output;
		}

		private function showRadiusList($rad) {
			$output = "";
			$found = false;
			if (FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
				$tmpoutput = FS::$iMgr->cbkForm("1").
					FS::$iMgr->select("ra",array("js" => "submit()"));
				
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","radalias");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					
					$tmpoutput .= FS::$iMgr->selElmt($data["radalias"],$data["radalias"],$rad == $data["radalias"]);
				}
				if ($found) {
					$output .= $tmpoutput."</select> ".FS::$iMgr->submit("",$this->loc->s("Manage"))."</form><div id=\"radadmcont\"></div>";
				}
				else {
					$output .= FS::$iMgr->printDebug($this->loc->s("err-no-server"));
				}
			}
			else {
				$tmpoutput = FS::$iMgr->cbkForm("1").
					FS::$iMgr->select("ra",array("js" => "submit()"));
					
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","dbname,addr,port,radalias");
	            while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = 1;
					}
					
					$radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
					$tmpoutput .= FS::$iMgr->selElmt($data["radalias"]." (".$radpath.")",
						$data["radalias"],$rad == $data["radalias"]);
				}
				if ($found) {
					$output .= $tmpoutput."</select> ".FS::$iMgr->submit("",$this->loc->s("Administrate"))."</form><div id=\"radadmcont\"></div>";
				}
				else {
					$output .= FS::$iMgr->printDebug($this->loc->s("err-no-server"));
				}
			}
			return $output;
		}
		private function showDelegTool($radalias,$raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->tabPan(array(
					array(1,"mod=".$this->mid."&ra=".$radalias,$this->loc->s("mono-account")),
					array(2,"mod=".$this->mid."&ra=".$radalias,$this->loc->s("mass-account"))),$sh);
			}
			else if (!$sh || $sh == 1) {
				$radSQLMgr = $this->connectToRaddb($radhost,$radport,$raddb);
				if (!$radSQLMgr) {
					return FS::$iMgr->printError("err-db-conn-fail");
				}

				$output .= "<div id=\"adduserres\"></div>".
					FS::$iMgr->cbkForm("10",array("id" => "adduser")).
					"<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>".
					FS::$iMgr->hidden("ra",$radalias).
					FS::$iMgr->idxLine($this->loc->s("Name")." *","radname",array("rawlabel" => true)).
					FS::$iMgr->idxLine($this->loc->s("Subname")." *","radsurname",array("rawlabel" => true)).
					FS::$iMgr->idxLine($this->loc->s("Identifier")." *","radusername",array("rawlabel" => true)).
					"<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil").
					FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>".
					"<tr><td>".$this->loc->s("Validity")."</td><td>".
					FS::$iMgr->radioList("validity",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1).
					FS::$iMgr->calendar("startdate","","From")."<br />".
					FS::$iMgr->hourlist("limhours","limmins")."<br />".
					FS::$iMgr->calendar("enddate","","To")."<br />".
					FS::$iMgr->hourlist("limhoure","limmine",23,59).
					"</td></tr>".
					FS::$iMgr->tableSubmit("Save").

					FS::$iMgr->js("$('#adduser').submit(function(event) {
					event.preventDefault();
					$.post('index.php?mod=".$this->mid."&at=3&ra=".$radalias."&act=10', $('#adduser').serialize(), function(data) {
						$('#adduserres').html(data);
					});
				});");
			}
			else if ($sh == 2) {
				$radSQLMgr = $this->connectToRaddb($radhost,$radport,$raddb);
				if (!$radSQLMgr)
					return FS::$iMgr->printError("err-db-conn-fail");

				$output .= "<div id=\"adduserlistres\"></div>".
					FS::$iMgr->cbkForm("11",array("id" => "adduserlist")).
					"<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>".
					FS::$iMgr->hidden("ra",$radalias).
					"<tr><td>".$this->loc->s("Generation-type")."</td><td style=\"text-align: left;\">".
					FS::$iMgr->radio("typegen",1,false,$this->loc->s("random-name"))."<br />".
					FS::$iMgr->radio("typegen",2,false,$this->loc->s("Prefix")." ").FS::$iMgr->input("prefix","")."</td></tr>".
					FS::$iMgr->idxLine($this->loc->s("Account-nb")." *","nbacct",
						array("rawlabel" => true, "size" => 4, "length" => 4, "type" => "num")).
					"<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil2").FS::$iMgr->selElmt("","none").
					$this->addGroupList($radSQLMgr)."</select></td></tr>".
					"<tr><td>".$this->loc->s("Validity")."</td><td>".
					FS::$iMgr->radioList("validity2",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1).
					FS::$iMgr->calendar("startdate2","","From")."<br />".
					FS::$iMgr->hourlist("limhours2","limmins2")."<br />".
					FS::$iMgr->calendar("enddate2","","To")."<br />".
					FS::$iMgr->hourlist("limhoure2","limmine2",23,59).
					"</td></tr>".
					FS::$iMgr->tableSubmit("Save");
			}
			else if ($sh && $sh > 2) {
				$output .= FS::$iMgr->printError("err-bad-tab");
			}

			return $output;
		}

		private function showRadiusAdmin($radSQLMgr) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			$radalias = $this->raddbinfos["radalias"];
			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->tabPan(array(
					array(1,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("Users")),
					array(2,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("Profils")),
					array(3,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("mass-import")),
					array(4,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("auto-import-dhcp")),
					array(6,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("mono-account-deleg")),
					array(7,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("mass-account-deleg")),
					array(5,"mod=".$this->mid."&ra=".$this->raddbinfos["radalias"],$this->loc->s("advanced-tools"))),$sh);
			}
			else if (!$sh || $sh == 1) {
					$output .= FS::$iMgr->opendiv(2,$this->loc->s("New-User"),
						array("lnkadd" => "ra=".$this->raddbinfos["radalias"]));
					$found = 0;

					// Filtering
					FS::$iMgr->js("function filterRadiusDatas() {
						$('#radd').fadeOut();
						$.post('index.php?mod=".$this->mid."&act=12', $('#radf').serialize(), function(data) {
						$('#radd').html(data);
						$('#radd').fadeIn();
						});
					}");
					
					$output .= FS::$iMgr->cbkForm("12","Modification",false,array("id" => "radf")).
						FS::$iMgr->hidden("ra",$radalias).
						FS::$iMgr->select("uf",array("js" => "filterRadiusDatas()")).
						FS::$iMgr->selElmt("--".$this->loc->s("Type")."--","",true).
						FS::$iMgr->selElmt($this->loc->s("Mac-addr"),"mac").
						FS::$iMgr->selElmt($this->loc->s("Other"),"other").
						"</select>".
						FS::$iMgr->select("ug",array("js" => "filterRadiusDatas()")).
						FS::$iMgr->selElmt("--".$this->loc->s("Group")."--","",true);
						
					$query = $radSQLMgr->Select($this->raddbinfos["tradusrgrp"],"distinct groupname");
					while ($data = $radSQLMgr->Fetch($query)) {
						$output .= FS::$iMgr->selElmt($data["groupname"],$data["groupname"]);
					}

					$output .= "</select>".FS::$iMgr->button("but",$this->loc->s("Filter"),"filterRadiusDatas()");
					$output .= "<div id=\"radd\">".$this->showRadiusDatas($radSQLMgr)."</div>";
				}
				else if($sh == 2) {
					$output .= FS::$iMgr->opendiv(3,$this->loc->s("New-Profil"),
						array("lnkadd" => "ra=".$radalias));
					$tmpoutput = FS::$iMgr->h3("title-profillist");
					$found = 0;

					$groups=array();
					$query = $radSQLMgr->Select($this->raddbinfos["tradgrprep"],"distinct groupname");
					while ($data = $radSQLMgr->Fetch($query)) {
						$rcount = $radSQLMgr->Count($this->raddbinfos["tradusrgrp"],"distinct username","groupname = '".$data["groupname"]."'");
						if (!isset($groups[$data["groupname"]]))
							$groups[$data["groupname"]] = $rcount;
					}

					$query = $radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"distinct groupname");
					while ($data = $radSQLMgr->Fetch($query)) {
						$rcount = $radSQLMgr->Count($this->raddbinfos["tradusrgrp"],"distinct username","groupname = '".$data["groupname"]."'");
						if (!isset($groups[$data["groupname"]]))
								$groups[$data["groupname"]] = $rcount;
						else
							$groups[$data["groupname"]] += $rcount;
					}
					
					if (count($groups) > 0) {
						$output .= "<table id=\"radgrp\" style=\"width:30%;\"><tr><th>".$this->loc->s("Group")."</th><th style=\"width:30%\">".
							$this->loc->s("User-nb")."</th><th></th></tr>";
						foreach ($groups as $key => $value) {
							$output .= "<tr id=\"rdg_".FS::$iMgr->formatHTMLId($key)."\"><td>".
								FS::$iMgr->opendiv(4,$key,
								array("lnkadd" => "ra=".$radalias."&radentry=".$key))."</td><td>".$value."</td><td>".
								FS::$iMgr->removeIcon("mod=".$this->mid."&act=5&ra=".$radalias."&group=".$key,
									array("js" => true,
									"confirm" => array($this->loc->s("confirm-remove-group")."'".$key."'","Confirm","Cancel")))."</td></tr>";
						}
						$output .= "</table>";
					}
				}
				else if ($sh == 3) {
					$grouplist= FS::$iMgr->selElmt("","none");
					$groups=array();
					$query = $radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"distinct groupname");
					while ($data = $radSQLMgr->Fetch($query)) {
						if (!in_array($data["groupname"],$groups)) {
							$groups[] = $data["groupname"];
						}
					}
					$query = $radSQLMgr->Select($this->raddbinfos["tradgrprep"],"distinct groupname");
					while ($data = $radSQLMgr->Fetch($query)) {
						if (!in_array($data["groupname"],$groups)) {
							$groups[] = $data["groupname"];
						}
					}
					$count = count($groups);
					for ($i=0;$i<$count;$i++) {
						$grouplist .= FS::$iMgr->selElmt($groups[$i],$groups[$i]);
					}

					$output .= FS::$iMgr->h3("title-mass-import");
					FS::$iMgr->js("function changeUForm() {
						if (document.getElementsByName('usertype')[0].value == 1) {
							$('#uptype').show(); $('#csvtooltip').html(\"<b>Note: </b>Les noms d'utilisateurs ne peuvent pas contenir d'espace.<br />Les mots de passe doivent être en clair.<br />Caractère de formatage: <b>,</b>\");
						}
						else if (document.getElementsByName('usertype')[0].value == 2) {
							$('#uptype').hide(); $('#csvtooltip').html('<b>Note: </b> Les adresses MAC peuvent être de la forme <b>aa:bb:cc:dd:ee:ff</b>, <b>aa-bb-cc-dd-ee-ff</b> ou <b>aabbccddeeff</b> et ne sont pas sensibles à la casse.');
						}
						else if (document.getElementsByName('usertype')[0].value == 3) {
							$('#uptype').hide(); $('#csvtooltip').html('');
						}
					};");
					$output .= FS::$iMgr->cbkForm("6");
					$output .= "<ul class=\"ulform\"><li width=\"100%\">".
						FS::$iMgr->select("usertype",array("js" => "changeUForm()","label" => "Type d'authentification"));
					$output .= FS::$iMgr->selElmt($this->loc->s("User"),1);
					$output .= FS::$iMgr->selElmt($this->loc->s("Mac-addr"),2);
					//$formoutput .= FS::$iMgr->selElmt("Code PIN",3);
					$output .= "</select></li><li id=\"uptype\">".
						FS::$iMgr->hidden("ra",$radalias).
						FS::$iMgr->select("upwdtype",array("label" => $this->loc->s("Pwd-Type"))).
						FS::$iMgr->selElmt("Cleartext-Password",1).
						FS::$iMgr->selElmt("User-Password",2).
						FS::$iMgr->selElmt("Crypt-Password",3).
						FS::$iMgr->selElmt("MD5-Password",4).
						FS::$iMgr->selElmt("SHA1-Password",5).
						FS::$iMgr->selElmt("CHAP-Password",6).
						"</select></li><li>".
						FS::$iMgr->select("ugroup",array("label" => $this->loc->s("Profil"))).
						FS::$iMgr->selElmt("","none").
						$this->addGroupList($radSQLMgr)."</select></li><li>".FS::$iMgr->textarea("csvlist","",580,330,$this->loc->s("Userlist-CSV")).
						"</li><li id=\"csvtooltip\">".FS::$iMgr->tip("mass-import-restriction")."</li><li>".
						FS::$iMgr->submit("","Importer")."</li></ul></form>";
				}
				else if ($sh == 4) {
					$output = "";

					$found = 0;
					$formoutput = "";
					$formoutput .= FS::$iMgr->h3("title-auto-import");
					$formoutput .= FS::$iMgr->cbkForm("7");
					$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->select("subnet",array("label" => "Subnet DHCP"));
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask");
					while ($data = FS::$dbMgr->Fetch($query)) {
						if (!$found) $found = 1;
							$formoutput .= FS::$iMgr->selElmt($data["netid"]."/".$data["netmask"],$data["netid"]);
					}
					if ($found) {
						$found = 0;
						$formoutput .= "</select></li><li>".
							FS::$iMgr->hidden("ra",$radalias).
							FS::$iMgr->select("radgroup",array("label" => $this->loc->s("Radius-profile")));

						$groups=array();
						$query = $radSQLMgr->Select($this->raddbinfos["tradgrprep"],"distinct groupname");
						while ($data = $radSQLMgr->Fetch($query)) {
							if (!isset($groups[$data["groupname"]]))
								$groups[$data["groupname"]] = 1;
						}

						$query = $radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"distinct groupname");
						while ($data = $radSQLMgr->Fetch($query)) {
							if (!isset($groups[$data["groupname"]]))
								$groups[$data["groupname"]] = 1;
						}
						if (count($groups) > 0) {
							$found = 1;
							foreach ($groups as $key => $value)
							$formoutput .= FS::$iMgr->selElmt($key,$key);
						}
						$formoutput .= "</select></li><li>".FS::$iMgr->submit("",$this->loc->s("Add"))."</li></ul></form>";
					}
					if ($found) {
						$output .= $formoutput;
						$found = 0;
						$tmpoutput = "";
						$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_dhcp_import","dhcpsubnet,groupname",
							"addr='".$this->raddbinfos["addr"]."' AND port = '".$this->raddbinfos["port"].
							"' AND dbname='".$this->raddbinfos["dbname"]."'");
						while ($data = FS::$dbMgr->Fetch($query)) {
							if ($found == 0) {
								$found = 1;
								$tmpoutput .= FS::$iMgr->h3("title-auto-import2")."<table><tr><th>".$this->loc->s("DHCP-zone")."</th><th>".
								$this->loc->s("Radius-profile")."</th><th></th></tr>";
							}
							$tmpoutput .= "<tr id=\"".preg_replace("#[.]#","-",$data["dhcpsubnet"])."\"><td>".$data["dhcpsubnet"]."</td><td>".$data["groupname"]."</td><td>".
								FS::$iMgr->removeIcon("mod=".$this->mid."&ra=".$radalias."&act=8&subnet=".$data["dhcpsubnet"],array("js" => true,
									"confirm" => array($this->loc->s("confirm-remove-subnetlink")."'".$data["dhcpsubnet"]."/".$data["groupname"]."'",
										"Confirm","Cancel")))."</td></tr>";
						}
						if ($found) $output .= $tmpoutput."</table>";
					}
					else
						$output .= FS::$iMgr->printError("err-no-subnet-for-import");
				}
				else if ($sh == 5) {
					$output .= FS::$iMgr->h3("title-cleanusers").
						FS::$iMgr->cbkForm("9")."<table>".
						FS::$iMgr->hidden("ra",$radalias);

					$radexpenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options","optval",
						"optkey = 'rad_expiration_enable' AND addr = '".$this->raddbinfos["addr"].
						"' AND port = '".$this->raddbinfos["port"].
						"' AND dbname = '".$this->raddbinfos["dbname"]."'");
						
					$radexptable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options","optval",
						"optkey = 'rad_expiration_table' AND addr = '".$this->raddbinfos["addr"].
						"' AND port = '".$this->raddbinfos["port"].
						"' AND dbname = '".$this->raddbinfos["dbname"]."'");
						
					$radexpuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options","optval",
						"optkey = 'rad_expiration_user_field' AND addr = '".$this->raddbinfos["addr"].
						"' AND port = '".$this->raddbinfos["port"].
						"' AND dbname = '".$this->raddbinfos["dbname"]."'");
						
					$radexpdate = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options","optval",
						"optkey = 'rad_expiration_date_field' AND addr = '".$this->raddbinfos["addr"].
						"' AND port = '".$this->raddbinfos["port"].
						"' AND dbname = '".$this->raddbinfos["dbname"]."'");

					$output .= FS::$iMgr->idxLine("enable-autoclean","cleanradsqlenable", array("value" => ($radexpenable == 1),"type" => "chk")).
						FS::$iMgr->idxLine("SQL-table","cleanradsqltable",array("value" => $radexptable,"tooltip" => "tooltip-ac-sqltable")).
						FS::$iMgr->idxLine("user-field","cleanradsqluserfield",array("value" => $radexpuser,"tooltip" => "tooltip-ac-sqluserfield")).
						FS::$iMgr->idxLine("expiration-field","cleanradsqlexpfield",array("value" => $radexpdate,"tooltip" => "tooltip-ac-sqlexpirationfield")).
						FS::$iMgr->tableSubmit("Save");
				}
				else if ($sh == 6) {
					$output .= "<div id=\"adduserres\"></div>";
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&ra=".$radalias."&act=10",array("id" => "adduser"));
					$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>Valeur</th></tr>";
					$output .= FS::$iMgr->idxLine($this->loc->s("Name")." *","radname",array("rawlabel" => true));
					$output .= FS::$iMgr->idxLine($this->loc->s("Subname")." *","radsurname",array("rawlabel" => true));
					$output .= FS::$iMgr->idxLine($this->loc->s("Identifier")." *","radusername",array("rawlabel" => true));
					$output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".
						FS::$iMgr->select("profil").FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
					$output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".FS::$iMgr->radioList("validity",array(1,2),array("Toujours valide","Période"),1);
					$output .= FS::$iMgr->calendar("startdate","","From")."<br />";
					$output .= FS::$iMgr->hourlist("limhours","limmins")."<br />";
					$output .= FS::$iMgr->calendar("enddate","","To")."<br />";
					$output .= FS::$iMgr->hourlist("limhoure","limmine",23,59);
					$output .= "</td></tr>";
					$output .= FS::$iMgr->tableSubmit("Save");

					FS::$iMgr->js("$('#adduser').submit(function(event) {
						event.preventDefault();
						$.post('index.php?mod=".$this->mid."&at=3&ra=".$radalias."&act=10', $('#adduser').serialize(), function(data) {
							$('#adduserres').html(data);
						});
					});");
				}
				else if ($sh == 7) {
					$output .= "<div id=\"adduserlistres\"></div>";
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&ra=".$radalias.
						"&act=11",array("id" => "adduserlist"));
					$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>";
					$output .= "<tr><td>".$this->loc->s("Generation-type")."</td><td style=\"text-align: left;\">".
					FS::$iMgr->radio("typegen",1,false,$this->loc->s("random-name"))."<br />".
						FS::$iMgr->radio("typegen",2,false,$this->loc->s("Prefix")." ").FS::$iMgr->input("prefix","")."</td></tr>";
					$output .= FS::$iMgr->idxLine($this->loc->s("Account-nb")." *","nbacct",array("size" => 4,"length" => 4, "type" => "num","rawlabel" => true));
					$output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".
						FS::$iMgr->select("profil2").FS::$iMgr->selElmt("","none").
						$this->addGroupList($radSQLMgr)."</select></td></tr>";
					$output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".
						FS::$iMgr->radioList("validity2",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1);
					$output .= FS::$iMgr->calendar("startdate2","","From")."<br />";
					$output .= FS::$iMgr->hourlist("limhours2","limmins2")."<br />";
					$output .= FS::$iMgr->calendar("enddate2","","To")."<br />";
					$output .= FS::$iMgr->hourlist("limhoure2","limmine2",23,59);
					$output .= "</td></tr>";
					$output .= FS::$iMgr->tableSubmit("Save");
				}
				/*else {
					return FS::$iMgr->printError("err-bad-tab");
				}
			}*/
			return $output;
		}

		private function showRadiusDatas($radSQLMgr) {
			$found = false;
			$output = "";
			$ug = FS::$secMgr->checkAndSecurisePostData("ug");
			$uf = FS::$secMgr->checkAndSecurisePostData("uf");
			$tmpoutput = "";
			$expirationbuffer = array();
			
			$query = $radSQLMgr->Select($this->raddbinfos["tradcheck"],"id,username,value",
				"attribute IN ('Auth-Type','Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')".
				($ug ? " AND username IN (SELECT username FROM radusergroup WHERE groupname = '".$ug."')" : ""));
			while ($data = $radSQLMgr->Fetch($query)) {
				if (!$found && (!$uf || $uf != "mac" && $uf != "other" || $uf == "mac" && preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"]) 
					|| $uf == "other" && !preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"]))) {
					$found = true;
					$tmpoutput .= "<table id=\"raduser\" style=\"width:70%\"><thead><tr><th class=\"headerSortDown\">Id</th><th>Utilisateur</th><th>
						Mot de passe</th><th>".$this->loc->s("Groups")."</th><th>Date d'expiration</th><th></th></tr></thead>";
					if ($this->hasExpirationEnabled()) {
						$query2 = $radSQLMgr->Select(PGDbConfig::getDbPrefix()."radusers","username,expiration","expiration > 0");
						while ($data2 = $radSQLMgr->Fetch($query2)) {
							if (!isset($expirationbuffer[$data2["username"]])) {
								$expirationbuffer[$data2["username"]] = date("d/m/y h:i",strtotime($data2["expiration"]));
							}
						}
					}
				}	
				if (!$uf || $uf != "mac" && $uf != "other" || $uf == "mac" && preg_match('#^([0-9A-F]{12})$#i', $data["username"])
					|| $uf == "other" && !preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"])) {
					$tmpoutput .= "<tr id=\"rdu_".FS::$iMgr->formatHTMLId($data["username"])."\"><td>".$data["id"]."</td><td>".
						FS::$iMgr->opendiv(6,$data["username"],
							array("lnkadd" => "ra=".$this->raddbinfos["radalias"].
								"&radentrytype=1&radentry=".$data["username"])).
								"</a></td><td>".$data["value"]."</td><td>";
					$query2 = $radSQLMgr->Select($this->raddbinfos["tradusrgrp"],"groupname","username = '".$data["username"]."'");
					$found2 = 0;
					while ($data2 = $radSQLMgr->Fetch($query2)) {
						if ($found2 == 0) $found2 = 1;
						else $tmpoutput .= "<br />";
						$tmpoutput .= $data2["groupname"];
					}
					$tmpoutput .= "</td><td>".
						(isset($expirationbuffer[$data["username"]]) ? $expirationbuffer[$data["username"]] : "Jamais").
						"</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=4&ra=".$this->raddbinfos["radalias"].
							"&user=".$data["username"],
							array("js" => true,
								"confirm" => array($this->loc->s("confirm-remove-user")."'".$data["username"]." ?'","Confirm","Cancel"))).
						"</td></tr>";
				}
			}
			if ($found) {
				$output = $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("raduser");
			}
			return $output;
		}

		private function addGroupList($radSQLMgr,$selectEntry="") {
			$output = "";
			$groups=array();
			$query = $radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"distinct groupname");
			while ($data = $radSQLMgr->Fetch($query)) {
				if (!in_array($data["groupname"],$groups)) {
					$groups[] = $data["groupname"];
				}
			}
			$query = $radSQLMgr->Select($this->raddbinfos["tradgrprep"],"distinct groupname");
			while ($data = $radSQLMgr->Fetch($query)) {
				if (!in_array($data["groupname"],$groups)) {
					$groups[] = $data["groupname"];
				}
			}
			$count = count($groups);
			for ($i=0;$i<$count;$i++) {
				$output .= FS::$iMgr->selElmt($groups[$i],$groups[$i],($groups[$i] == $selectEntry ? true : false));
			}
			return $output;
		}

		private function connectToRaddb2($radalias) {
			if ($data = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."radius_db_list","addr,dbname,port","radalias = '".$radalias."'")) {
				return $this->connectToRaddb($data["addr"],$data["port"],$data["dbname"]);
			}
			return NULL;
		}
		
		private function connectToRaddb($radhost,$radport,$raddb) {
			// Load some other useful datas from DB
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list",
				"addr,port,dbname,login,pwd,radalias,dbtype,tradcheck,tradreply,tradgrpchk,tradgrprep,tradusrgrp,tradacct",
				"addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
			if ($data = FS::$dbMgr->Fetch($query)) {
				$this->raddbinfos = $data;
			}

			if ($this->raddbinfos["dbtype"] != "my" && $this->raddbinfos["dbtype"] != "pg") {
				return NULL;
			}

			$radSQLMgr = new AbstractSQLMgr();
			if ($radSQLMgr->setConfig($this->raddbinfos["dbtype"],$raddb,$radport,$radhost,$this->raddbinfos["login"],$this->raddbinfos["pwd"]) == 0) {
				if ($radSQLMgr->Connect() == NULL) {
					return NULL;
				}
			}
			return $radSQLMgr;
		}

		private function hasExpirationEnabled() {
			if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options",
				"optval","optkey = 'rad_expiration_enable' AND addr = '".$this->raddbinfos["addr"].
					"' AND port = '".$this->raddbinfos["port"].
					"' AND dbname = '".$this->raddbinfos["dbname"]."'") == 1) {
				return true;
			}
			return false;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showRadiusServerMgmt();
				case 2: 
					$ruObj = new radiusUser();
					return $ruObj->showForm();
				case 3:
					$rgObj = new radiusGroup();
					return $rgObj->showForm();
				case 4:
					$radentry = FS::$secMgr->checkAndSecuriseGetData("radentry");
					if (!$radentry) {
						return $this->loc->s("err-bad-datas");
					}
					$rgObj = new radiusGroup();
					return $rgObj->showForm($radentry);
				case 6:
					$radentry = FS::$secMgr->checkAndSecuriseGetData("radentry");
					if (!$radentry) {
						return $this->loc->s("err-bad-datas");
					}
					$ruObj = new radiusUser();
					return $ruObj->showForm($radentry);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Radius Admin
				case 1:
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias,true);
					return;
				case 2: // User add/edit
					$ruObj = new radiusUser();
					$ruObj->Modify();
					return;
				case 3: // Group edition
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$groupname = FS::$secMgr->checkAndSecurisePostData("groupname");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if (!$groupname) {
						$this->log(2,"Some fields are missing for group edition");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					// For Edition Only, don't delete acct/user-group links
					
					$radSQLMgr->BeginTr();
					if ($edit == 1) {
						$radSQLMgr->Delete($this->raddbinfos["tradgrpchk"],"groupname = '".$groupname."'");
						$radSQLMgr->Delete($this->raddbinfos["tradgrprep"],"groupname = '".$groupname."'");
					}

					$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"id","groupname='".$groupname."'");
					if ($groupexist && $edit != 1) {
						$this->log(1,"Trying to add existing group '".$groupname."'");
						FS::$iMgr->ajaxEchoNC("err-exist");
						return;
					}
					$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"id","groupname='".$groupname."'");
					if ($groupexist && $edit != 1) {
						$this->log(1,"Trying to add existing group '".$groupname."'");
						FS::$iMgr->ajaxEchoNC("err-exist");
						return;
					}
					$attrTab = array();
					foreach ($_POST as $key => $value) {
						if (preg_match("#attrval#",$key)) {
							$key = preg_replace("#attrval#","",$key);
							if (!isset($attrTab[$key])) {
								$attrTab[$key] = array();
							}
							$attrTab[$key]["val"] = $value;
						}
						else if (preg_match("#attrkey#",$key)) {
							$key = preg_replace("#attrkey#","",$key);
							if (!isset($attrTab[$key])) {
								$attrTab[$key] = array();
							}
							$attrTab[$key]["key"] = $value;
						}
						else if (preg_match("#attrop#",$key)) {
							$key = preg_replace("#attrop#","",$key);
							if (!isset($attrTab[$key])) {
								$attrTab[$key] = array();
							}
							$attrTab[$key]["op"] = $value;
						}
						else if (preg_match("#attrtarget#",$key)) {
							$key = preg_replace("#attrtarget#","",$key);
							if (!isset($attrTab[$key])) {
								$attrTab[$key] = array();
							}
							$attrTab[$key]["target"] = $value;
						}
					}
					
					$idxRep = $radSQLMgr->GetMax($this->raddbinfos["tradgrprep"],"id");
					$idxChk = $radSQLMgr->GetMax($this->raddbinfos["tradgrpchk"],"id");
					
					foreach ($attrTab as $attrKey => $attrValue) {
						if (!isset($attrValue["op"])) {
							FS::$iMgr->ajaxEcho("err-bad-datas");
							return;
						}
						if ($attrValue["target"] == "2") {
							$idxRep++;
							$radSQLMgr->Insert($this->raddbinfos["tradgrprep"],"id,groupname,attribute,op,value",
								"'".$idxRep."','".$groupname.
								"','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
						else if ($attrValue["target"] == "1") {
							$idxChk++;
							$radSQLMgr->Insert($this->raddbinfos["tradgrpchk"],"id,groupname,attribute,op,value",
								"'".$idxChk."','".$groupname.
								"','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
					}
					$radSQLMgr->CommitTr();
					$this->log(0,"Group '".$groupname."' edited/created");
					FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias."&sh=2",true);
					break;
				case 4: // user removal
					$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
					$username = FS::$secMgr->checkAndSecuriseGetData("user");

					if (!$radalias || !$username) {
						$this->log(2,"Some fields are missing user removal");
						FS::$iMgr->ajaxEcho("err-delete");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database ".$radalias);
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$radSQLMgr->BeginTr();
					$radSQLMgr->Delete($this->raddbinfos["tradcheck"],"username = '".$username."'");
					$radSQLMgr->Delete($this->raddbinfos["tradreply"],"username = '".$username."'");
					$radSQLMgr->Delete($this->raddbinfos["tradusrgrp"],"username = '".$username."'");
					if ($this->hasExpirationEnabled()) {
						$radSQLMgr->Delete(PGDbConfig::getDbPrefix()."radusers","username ='".$username."'");
					}
					$radSQLMgr->Delete("radpostauth","username = '".$username."'");
					$radSQLMgr->Delete($this->raddbinfos["tradacct"],"username = '".$username."'");
					$radSQLMgr->CommitTr();
					$this->log(0,"User '".$username."' removed");
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#rdu_".FS::$iMgr->formatHTMLId($username)."');");
					return;
				case 5: // group removal
					$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
					$groupname = FS::$secMgr->checkAndSecuriseGetData("group");

					if (!$radalias || !$groupname) {
						$this->log(2,"Some fields are missing for group removal");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$radSQLMgr->BeginTr();
					$radSQLMgr->Delete($this->raddbinfos["tradgrpchk"],"groupname = '".$groupname."'");
					$radSQLMgr->Delete($this->raddbinfos["tradgrprep"],"groupname = '".$groupname."'");
					$radSQLMgr->Delete($this->raddbinfos["tradusrgrp"],"groupname = '".$groupname."'");
					$radSQLMgr->CommitTr();

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_dhcp_import","groupname = '".$groupname."'");

					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#rdg_".FS::$iMgr->formatHTMLId($groupname)."');");
					$this->log(0,"Group '".$groupname."' removed");
					return;
				// Mass import
				case 6:
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$utype = FS::$secMgr->checkAndSecurisePostData("usertype");
					$pwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");
					$group = FS::$secMgr->checkAndSecurisePostData("ugroup");
					$userlist = FS::$secMgr->checkAndSecurisePostData("csvlist");

					if (!$radalias) {
						$this->log(2,"Some datas are missing for mass import");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					if (!$utype || $utype != 1 && $utype != 2 || !$userlist) {
						$this->log(2,"Some datas are missing or invalid for mass import");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$groupfound = NULL;
					if ($group != "none") {
						$groupfound = $radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$group."'");
						if (!$groupfound) {
							$groupfound = $radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$group."'");
						}
					}
					if ($utype == 1) {
						$userlist = str_replace('\r','\n',$userlist);
						$userlist = str_replace('\n\n',"\n",$userlist);
						$userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
						$userlist = array_filter($userlist);
						$fmtuserlist = array();
						$count = count($userlist);
						for ($i=0;$i<$count;$i++) {
							$tmp = preg_split("#[,]#",$userlist[$i]);
							if (count($tmp) != 2 || preg_match("#[ ]#",$tmp[0])) {
								$this->log(2,"Some datas are invalid for mass import");
								FS::$iMgr->ajaxEcho("err-bad-datas");
								return;
							}
							$fmtuserlist[$tmp[0]] = $tmp[1];
						}
						$userfound = 0;
						foreach ($fmtuserlist as $user => $upwd) {
							switch($pwdtype) {
								case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
								case 2: $attr = "User-Password"; $value = $upwd; break;
								case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
								case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
								case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
								case 6: $attr = "CHAP-Password"; $value = $upwd; break;
								default:
										$this->log(2,"Bad password type for mass import");
										FS::$iMgr->ajaxEcho("err-bad-datas");
										return;
							}
							if (!$radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"username","username = '".$user."'")) {
								$radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value","'','".$user."','".$attr."',':=','".$value."'");
							}
							
							if ($groupfound) {
								$usergroup = $radSQLMgr->GetOneData($this->raddbinfos["tradusrgrp"],"groupname","username = '".$user."' AND groupname = '".$group."'");
								if (!$usergroup)
									$radSQLMgr->Insert($this->raddbinfos["tradusrgrp"],"username,groupname,priority","'".$user."','".$group."','1'");
							}
						}
						if ($userfound) {
							$this->log(2,"Some users are already found for mass import");
                            FS::$iMgr->ajaxEchoNC("err-exist2");
							return;
						}
						$this->log(0,"Mass import done (list:".$userlist." group: ".$group.")");
					}
					else if ($utype == 2) {
						if (preg_match("#[,]#",$userlist)) {
							FS::$iMgr->ajaxEcho("err-bad-datas");
							return;
						}
						$userlist = str_replace('\r','\n',$userlist);
						$userlist = str_replace('\n\n',"\n",$userlist);
						$userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
						$userlist = array_filter($userlist);
						// Match & format mac addr
						$count = count($userlist);
						for ($i=0;$i<$count;$i++) {
							if (!FS::$secMgr->isMacAddr($userlist[$i]) && !preg_match('#^[0-9A-F]{12}$#i', $userlist[$i]) && !preg_match('#^([0-9A-F]{2}[-]){5}[0-9A-F]{2}$#i', $userlist[$i])) {
								$this->log(2,"Bad fields for Mass import (MAC addr)");
								FS::$iMgr->ajaxEcho("err-bad-datas");
								return;
							}
							$userlist[$i] = preg_replace("#[:-]#","",$userlist[$i]);
							$userlist[$i] = strtolower($userlist[$i]);
						}
						// Delete duplicate entries
						$userlist = array_unique($userlist);
						$userfound = 0;
						$count = count($userlist);
						for ($i=0;$i<$count;$i++) {
							if (!$radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"username","username = '".$userlist[$i]."'")) {
								$radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value","'','".$userlist[$i]."','Auth-Type',':=','Accept'");
							}
							else {
								$userfound = 1;
							}
							
							if ($groupfound) {
								$usergroup = $radSQLMgr->GetOneData($this->raddbinfos["tradusrgrp"],"groupname","username = '".$userlist[$i]."' AND groupname = '".$group."'");
								if (!$usergroup) {
									$radSQLMgr->Insert($this->raddbinfos["tradusrgrp"],"username,groupname,priority","'".$userlist[$i]."','".$group."','1'");
								}
							}
						}
						if ($userfound) {
							$this->log(1,"Some users already exists for mass import");
							FS::$iMgr->ajaxEchoNC("err-exist2");
							return;
						}
						$this->log(0,"Mass import done (list:".$userlist." group: ".$group.")");
					}
					FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias."&sh=3",true);
					return;
				case 7: // DHCP sync
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$radgroup = FS::$secMgr->checkAndSecurisePostData("radgroup");
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

					if (!$radalias) {
						$this->log(2,"Some fields are missing for DHCP sync");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}

					if (!$radgroup || !$subnet || !FS::$secMgr->isIP($subnet)) {
						$this->log(2,"Some fields are missing or invalid for DHCP sync");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"id","groupname='".$radgroup."'");
					if (!$groupexist) {
						$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"id","groupname='".$radgroup."'");
					}

					if (!$groupexist) {
						$this->log(1,"Group '".$radgroup."' doesn't exist, can't bind DHCP to radius");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}

					$subnetexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netmask","netid = '".$subnet."'");
					if (!$subnetexist) {
						$this->log(1,"Subnet '".$subnet."' doesn't exist can't bind DHCP to radius");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_dhcp_import",
						"dhcpsubnet","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".
						$raddb."' AND dhcpsubnet = '".$subnet."'")) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_dhcp_import",
							"addr,port,dbname,dhcpsubnet,groupname",
							"'".$this->raddbinfos["addr"]."','".$this->raddbinfos["port"].
							"','".$this->raddbinfos["dbname"]."','".$subnet."','".$radgroup."'");
					}
					$this->log(0,"DHCP subnet '".$subnet."' bound to '".$radgroup."'");
					FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias."&sh=4",true);
					return;
				case 8: // dhcp sync removal
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$subnet = FS::$secMgr->checkAndSecuriseGetData("subnet");

					if (!$radalias) {
						$this->log(2,"Some required fields are missing for DHCP sync removal");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}

					if (!$subnet) {
						$this->log(2,"No subnet given to DHCP sync removal");
						FS::$iMgr->ajaxEcho("err-miss-data");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_dhcp_import","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."' AND dhcpsubnet = '".$subnet."'");
					$this->log(0,"Remove sync between subnet '".$subnet."' and radius");
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#".preg_replace("#[.]#","-",$subnet)."');");
					return;
				case 9: // radius cleanup table
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					if (!$radalias) {
						$this->log(2,"Some fields are missing for radius cleanup table (radius server)");
						FS::$iMgr->ajaxEchoNC("err-invalid-table");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$cleanradenable = FS::$secMgr->checkAndSecurisePostData("cleanradsqlenable");
					$cleanradtable = FS::$secMgr->checkAndSecurisePostData("cleanradsqltable");
					$cleanradsqluserfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqluserfield");
					$cleanradsqlexpfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqlexpfield");

					if ($cleanradenable == "on" && (!$cleanradtable || !$cleanradsqluserfield || !$cleanradsqlexpfield)) {
						$this->log(2,"Some fields are missing for radius cleanup table (data fields)");
						FS::$iMgr->ajaxEchoNC("err-invalid-table");
                        return;
					}

					if ($radSQLMgr->Count($cleanradtable,$cleanradsqluserfield) == NULL) {
						$this->log(1,"Some fields are wrong for radius cleanup table");
						FS::$iMgr->ajaxEchoNC("err-invalid-table");
						return;
					}

					$radhost = $this->raddbinfos["addr"];
					$radport = $this->raddbinfos["port"];
					$raddb = $this->raddbinfos["dbname"];
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_options","addr = '".$radhost."' AND port = '".$radport."' and dbname = '".$raddb."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_enable','".($cleanradenable == "on" ? 1 : 0)."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_table','".$cleanradtable."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_user_field','".$cleanradsqluserfield."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_date_field','".$cleanradsqlexpfield."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Data Creation/Edition for radius cleanup table done (table: '".$cleanradtable."' userfield: '".$cleanradsqluserfield."' date field: '".$cleanradsqlexpfield."'");
					FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias."&sh=5",true);
					return;
				case 10: // account creation (deleg)
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$name = FS::$secMgr->checkAndSecurisePostData("radname");
					$surname = FS::$secMgr->checkAndSecurisePostData("radsurname");
					$username = FS::$secMgr->checkAndSecurisePostData("radusername");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure");
					$limme = FS::$secMgr->checkAndSecurisePostData("limmine");

					if (!$radalias) {
						$this->log(2,"Some fields are missing for radius deleg (radius server)");
						echo FS::$iMgr->printError("err-invalid-auth-server");
						return;
					}
					if (!$name || !$surname || !$username || !$valid || !$profil || 
						($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 ||
						!FS::$secMgr->isNumeric($limhs) || !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) || !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate) 
					))) {
						$this->log(2,"Some fields are missing for radius deleg (datas)");
						echo FS::$iMgr->printError("err-field-missing");
						return;
					}

					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
                    $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if (strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError("err-end-before-start");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$exist = $radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"id","username = '".$username."'");
					if (!$exist) {
						$exist = $radSQLMgr->GetOneData($this->raddbinfos["tradreply"],"id","username = '".$username."'");
					}
					
					if ($exist) {
						$this->log(1,"User '".$username."' already exists (Deleg)");
						echo FS::$iMgr->printError("err-no-user");
						return;
					}

					$password = FS::$secMgr->genRandStr(8);
					
					$radSQLMgr->BeginTr();
					$radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value","'','".$username."','Cleartext-Password',':=','".$password."'");
					//$radSQLMgr->Insert(PGDbConfig::getDbPrefix()."radusers","username,expiration,name,surname,startdate,creator,creadate","'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
					$radSQLMgr->Insert($this->raddbinfos["tradusrgrp"],"username,groupname,priority","'".$username."','".$profil."',0");
					$radSQLMgr->CommitTr();

					$this->log(0,"Creating delegated user '".$username."' with password '".$password."'. Account expiration: ".($valid == 2 ? $edate: "none"));
					echo FS::$iMgr->printDebug($this->loc->s("ok-user"))."<br /><hr><b>".$this->loc->s("User").": </b>".
						$username."<br /><b>".$this->loc->s("Password").": </b>".$password."<br /><b>".$this->loc->s("Validity").": </b>".
						($valid == 2 ? $this->loc->s("From")." ".$sdate." ".$this->loc->s("To")." ".$edate : $this->loc->s("Infinite"))."<hr><br />";
					return;
				case 11: // Rad deleg (massive)
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					$typegen = FS::$secMgr->checkAndSecurisePostData("typegen");
					$prefix = FS::$secMgr->checkAndSecurisePostData("prefix");
					$nbacct = FS::$secMgr->checkAndSecurisePostData("nbacct");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil2");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity2");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate2");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate2");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours2");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins2");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure2");
                    $limme = FS::$secMgr->checkAndSecurisePostData("limmine2");
                    
					if (!$radalias) {
						$this->log(2,"Some fields are missing for massive creation (Deleg) (radius server)");
						echo FS::$iMgr->printError("err-invalid-auth-server");
						return;
					}					

					if (!$typegen || $typegen == 2 && !$prefix || !$nbacct || !$valid || !$profil
						|| ($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 
						|| !FS::$secMgr->isNumeric($nbacct) || $nbacct <= 0 || !FS::$secMgr->isNumeric($limhs)
						|| !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) 
						|| !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate)
					))) {
						$this->log(2,"Some fields are missing or invalid for massive creation (Deleg) (datas)");
						echo FS::$iMgr->printError("err-field-missing");
							return;
					}
					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
			                $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if (strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError("err-end-before-start");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					$pdf = new PDFgen();
					$pdf->SetTitle($this->loc->s("Account").": ".($valid == 1 ? $this->loc->s("Permanent") : $this->loc->s("Temporary")));
					for ($i=0;$i<$nbacct;$i++) {
						$password = FS::$secMgr->genRandStr(8);
						if ($typegen == 2) {
							$username = $prefix.($i+1);
						}
						else {
							$username = FS::$secMgr->genRandStr(12);
						}

						$radSQLMgr->BeginTr();
						$radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value",
							"'','".$username."','Cleartext-Password',':=','".$password."'");
							
						if ($this->hasExpirationEnabled()) {
							$radSQLMgr->Insert(PGDbConfig::getDbPrefix()."radusers","username,expiration,name,surname,startdate,creator,creadate",
								"'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
						}
						
						$radSQLMgr->Insert($this->raddbinfos["tradusrgrp"],"username,groupname,priority","'".$username."','".$profil."',0");
						$radSQLMgr->CommitTr();

						$this->log(0,"Create user '".$username."' with password '".$password."' for massive creation (Deleg)");

						$pdf->AddPage();
						$pdf->WriteHTML(utf8_decode("<b>".$this->loc->s("User").": </b>".$username."<br /><br /><b>".$this->loc->s("Password").": </b>".$password."<br /><br /><b>".
						$this->loc->s("Validity").": </b>".($valid == 2 ? $this->loc->s("From")." ".date("d/m/y H:i",strtotime($sdate))." ".$this->loc->s("To")." ".
						date("d/m/y H:i",strtotime($edate)) : $this->loc->s("Infinite"))));
					}
					$pdf->CleanOutput();
					return;
				case 12:
					$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
					if (!$radalias) {
						$this->log(2,"Some fields are missing for radius filter entries (radius server)");
						FS::$iMgr->ajaxEcho("err-invalid-table");
						return;
					}

					$radSQLMgr = $this->connectToRaddb2($radalias);
					if (!$radSQLMgr) {
						$this->log(2,"Unable to connect to radius database '".$radalias."'");
						FS::$iMgr->ajaxEcho("err-db-conn-fail");
						return;
					}

					echo $this->showRadiusDatas($radSQLMgr);
					return;
				// Add/Edit radius db
				case 13:
					if (!FS::$sessMgr->hasRight("mrule_radius_manage")) {
						$this->log(2,"This user don't have rights to manage radius !");
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					}

					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					$salias = FS::$secMgr->checkAndSecurisePostData("salias");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					$tradcheck = FS::$secMgr->checkAndSecurisePostData("tradcheck");
					$tradreply = FS::$secMgr->checkAndSecurisePostData("tradreply");
					$tradgrpchk = FS::$secMgr->checkAndSecurisePostData("tradgrpchk");
					$tradgrprep = FS::$secMgr->checkAndSecurisePostData("tradgrprep");
					$tradusrgrp = FS::$secMgr->checkAndSecurisePostData("tradusrgrp");
					$tradacct = FS::$secMgr->checkAndSecurisePostData("tradacct");

					if(!$saddr || !$salias  || !$sport || !FS::$secMgr->isNumeric($sport) ||
						!$sdbname) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}
					
					$sdbtype = "";
					if ($edit) {
						// Check if DB exists
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_db_list","login",
							"radalias ='".$salias."' AND addr ='".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'")) {
							$this->log(1,"Radius DB not exists (".$sdbname."@".$saddr.":".$sport.")");
							FS::$iMgr->ajaxEcho("err-not-exist");
							return;
						}
						
						$sdbtype = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_db_list",
							"dbtype","radalias ='".$salias."'");
					}
					else {
						$sdbtype = FS::$secMgr->checkAndSecurisePostData("sdbtype");
					}

					if( !$sdbtype || !$slogin  || !$spwd || !$spwd2 ||
						$spwd != $spwd2 || !$tradcheck || !$tradreply || !$tradgrpchk ||
						!$tradgrprep || !$tradusrgrp || !$tradacct ||
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradcheck) ||
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradacct) ||
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradreply) ||
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradgrpchk) || 
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradgrprep) ||
						!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$tradusrgrp)) {
						$this->log(2,"Some fields are missing or wrong for radius db adding");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}
	
					// @TODO: test table exist on db

					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_db_list","radalias",
						"radalias = '".$salias."' AND (addr != '".$saddr."' OR port != '".$sport."' OR dbname != '".$sdbname."')")) {
						FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-alias-already-used"),$salias),"",true);
						return;
					}
						
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_db_list","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					}
					else {
						if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_db_list","login",
							"addr ='".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'")) {
							$this->log(1,"Radius DB already exists (".$sdbname."@".$saddr.":".$sport.")");
							FS::$iMgr->ajaxEcho("err-exist");
							return;
						}
					}
					
					$testDBMgr = new AbstractSQLMgr();
					$testDBMgr->setConfig($sdbtype,$sdbname,$sport,$saddr,$slogin,$spwd);

					$conn = $testDBMgr->Connect();
					if ($conn == NULL) {
						FS::$iMgr->ajaxEchoNC("err-bad-server");
						return;
					}
					FS::$dbMgr->Connect();
					
					$this->log(0,"Added radius DB ".$sdbname."@".$saddr.":".$sport);
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."radius_db_list",
						"addr,port,dbname,dbtype,login,pwd,radalias,tradcheck,tradreply,tradgrpchk,tradgrprep,tradusrgrp,tradacct",
						"'".$saddr."','".$sport."','".$sdbname."','".$sdbtype."','".$slogin."','".$spwd."','".$salias."','".$tradcheck."','".$tradreply."','".$tradgrpchk."','".$tradgrprep."','".
						$tradusrgrp."','".$tradacct."'");
					FS::$iMgr->redir("mod=".$this->mid,true);
					break;
				// Remove radius db
				case 14:
					if (!FS::$sessMgr->hasRight("mrule_radius_manage")) {
						$this->log(2,"This user don't have rights to manage radius !");
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					}

					$salias = FS::$secMgr->checkAndSecuriseGetData("alias");
					if ($salias) {
						if ($data = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."radius_db_list","addr,port,dbname","radalias = '".$salias."'")) {
							$saddr = $data["addr"];
							$sport = $data["port"];
							$sdbname = $data["dbname"];
							
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_db_list",
								"radalias = '".$salias."'");
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."radius_dhcp_import",
								"addr = '".$data["addr"]."' AND port = '".$data["port"]."' AND dbname = '".$data["dbname"]."'");

							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#".
								preg_replace("#[.]#","-",$$data["dbname"].$$data["addr"].$data["port"])."');");
							
							$this->log(0,"Remove Radius DB ".$salias. "(".$data["dbname"]."@".$data["addr"].":".$data["port"].")");
							return;
						}
					}
					FS::$iMgr->ajaxEcho("err-bad-data");
					return;
				// Ping radius db
				case 15:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					if (!$saddr || !$sport || !$sdbname) {
						echo "<span style=\"color:red;\">".$this->loc->s("Error")."#1</span>";
						return;
					}
					
					if ($radSQLMgr = $this->connectToRaddb($saddr,$sport,$sdbname)) {
						echo "<span style=\"color:green;\">".$this->loc->s("OK")."</span>";
					}
					else {	
						echo "<span style=\"color:red;\">".$this->loc->s("Error")."</span>";
					}
			}

		}
	
		private $raddbinfos;
	};
?>
