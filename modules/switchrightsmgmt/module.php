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

	require_once(dirname(__FILE__)."/rules.php");

	if(!class_exists("iSwitchRightsMgmt")) {

	final class iSwitchRightsMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->rulesclass = new rSwitchRightsMgmt();
			
			$this->menu = _("Users and rights");
			$this->menutitle = _("Network devices (rights & backup)");

			$this->modulename = "switchrightsmgmt";
		}

		public function Load() {
			FS::$iMgr->setTitle(_("title-switchrightsmgmt"));

			return $this->showMain();;
		}

		private function addOrEditBackupServer($create = false) {
			$saddr = "";
			$slogin = "";
			$stype = 1;
			$spwd = "";
			$spath = "";
			$output = "";
			if (!$create) {
				FS::$iMgr->showReturnMenu(true);
				$output = FS::$iMgr->h2("title-edit-backup-switch-server");
				$addr = FS::$secMgr->checkAndSecuriseGetData("bck");
				$type = FS::$secMgr->checkAndSecuriseGetData("type");
				if (!$addr || $addr == "" || !$type || !FS::$secMgr->isNumeric($type)) {
					$output .= FS::$iMgr->printError("err-no-server-get");
					return $output;
				}
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","login,pwd,path","addr = '".$addr."' AND type = '".$type."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$stype = $type;
					$spath = $data["path"];
				}
				else {
					$output .= FS::$iMgr->printError("err-bad-server");
					return $output;
				}
				$output .= FS::$iMgr->aLink($this->mid, _("Return"))."<br />";
			}

			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("err-miss-bad-fields"); break;
				case 3: if ($create) $output .= FS::$iMgr->printError("err-server-exist"); break;
			}

			$output .= FS::$iMgr->js("function arangeform() {
				if (document.getElementsByName('stype')[0].value == 1) {
					$('#tohide1').fadeOut();
					$('#tohide2').fadeOut();
					$('#tohide3').fadeOut();
				} else if (document.getElementsByName('stype')[0].value == 2 || document.getElementsByName('stype')[0].value == 4 || document.getElementsByName('stype')[0].value == 5) {
					$('#tohide1').fadeIn();
					$('#tohide2').fadeIn();
					$('#tohide3').fadeIn();
				}};");
			$output .= FS::$iMgr->cbkForm("3");
			$output .= "<table>";
			if ($create) {
				$output .= FS::$iMgr->idxLine("ip-addr","saddr",array("value" => $saddr, "type" => "ip"));
				$output .= "<tr><td>"._("srv-type")."</td><td>";
				$output .= FS::$iMgr->select("stype",array("js" => "arangeform();"));
				$output .= FS::$iMgr->selElmt("TFTP",1);
				$output .= FS::$iMgr->selElmt("FTP",2);
				$output .= FS::$iMgr->selElmt("SCP",4);
				$output .= FS::$iMgr->selElmt("SFTP",5);
				$output .= "</select>";
				$output .= "</td></tr>";
			}
			else {
				$output .= FS::$iMgr->hidden("saddr",$saddr);
				$output .= FS::$iMgr->hidden("stype",$stype);
				$output .= FS::$iMgr->hidden("edit",1);

				$output .= "<tr><th>"._("ip-addr")."</th><th>".$saddr."</th></tr>";
				$output .= "<tr><td>"._("srv-type")."</td><td>";
				switch($stype) {
					case 1: $output .= "TFTP"; break;
					case 2: $output .= "FTP"; break;
					case 4: $output .= "SCP"; break;
					case 5: $output .= "SFTP"; break;
				}
			}
			$output .= "<tr id=\"tohide1\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>"._("User")."</td><td>".FS::$iMgr->input("slogin",$slogin)."</td></tr>";
			$output .= "<tr id=\"tohide2\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>"._("Password")."</td><td>".FS::$iMgr->password("spwd","")."</td></tr>";
			$output .= "<tr id=\"tohide3\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>"._("Password-repeat")."</td><td>".FS::$iMgr->password("spwd2","")."</td></tr>";
			$output .= FS::$iMgr->idxLine("server-path","spath",array("value" => $spath));
			$output .= FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function showBackupTab() {
			FS::$iMgr->setURL("sh=3");

			$output = FS::$iMgr->opendiv(1,_("New-Server"));

			$tmpoutput = "<table><tr><th>"._("Server")."</th><th>"._("Type")."</th><th>".
				_("server-path")."</th><th>"._("Login")."</th><th></th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if ($found == false) $found = true;
				$tmpoutput .= "<tr id=\"b".preg_replace("#[. ]#","-",$data["addr"]).$data["type"]."\"><td>".
					FS::$iMgr->aLink($this->mid."&bck=".$data["addr"]."&type=".$data["type"], $data["addr"])."</td><td>";
				$bcktype = "";
				switch($data["type"]) {
					case 1: $bcktype = "TFTP"; break;
					case 2: $bcktype = "FTP"; break;
					case 4: $bcktype = "SCP"; break;
					case 5: $bcktype = "SFTP"; break;
				}
				$tmpoutput .= $bcktype."</td><td>".$data["path"]."</td><td>".$data["login"]."</td><td><center>".
					FS::$iMgr->removeIcon(4,"addr=".$data["addr"]."&type=".$data["type"],array("js" => true,
					"confirmtext" => "confirm-remove-backupsrv",
					"confirmval" => $data["addr"]." (".$bcktype.")'"
				)).
				"</center></td></tr>";
			}
			if ($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError("err-no-backup-found");
			return $output;

		}

		private function showBySwitch() {
			FS::$iMgr->setURL("sh=2");

			// IP for ajax filtering
			$ip = (FS::isAjaxCall() ? FS::$secMgr->checkAndSecurisePostData("ip") : "");
			$output = "";
			$found = false;

			$grpoutput = FS::$iMgr->h1("group-rights")."<table><tr><th>"._("device")."</th><th>"._("Right")."</th><th>".
				_("Groups")."</th></tr>";
			$usroutput = FS::$iMgr->h1("user-rights")."<table><tr><th>"._("device")."</th><th>"._("Right")."</th><th>".
				_("Users")."</th></tr>";
			$formoutput = FS::$iMgr->selElmt(_("All"),"NULL0");

			$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
			if ($ip && $ip != "NULL0")
				$filteri = $ip;
			else if ($filter) $filteri = $filter;
			else $filteri = "";

			$query = FS::$dbMgr->Select("device","ip,name","","name");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$formoutput .=  FS::$iMgr->selElmt($data["name"],$data["ip"],$filter == $data["ip"]);
			}

			$query = FS::$dbMgr->Select("device","ip,name",$filteri ? "ip = '".$filteri."'" : "",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) $found = true;
				// Init array for device
				$grprules = $this->initIPRules();
				$usrrules = $this->initIPRules();
				// Groups
				$first = true;
				$grprules = $this->loadIPRules($grprules,1,$data["ip"]);
				foreach ($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if ($first) $first = false;
					$grpoutput .= $this->getRightForKey($key);
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showIPGroups($data["ip"],$key,$values,$filteri);
					$grpoutput .= "</td></tr>";
				}
				// Users
				$usrrules = $this->loadIPRules($usrrules,2,$data["ip"]);
				$first = true;
				foreach ($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if ($first) $first = false;
					$usroutput .= $this->getRightForKey($key);
					$usroutput .= "</td><td>";
					$usroutput .= $this->showIPUsers($data["ip"],$key,$values,$filteri);
					$usroutput .= "</td></tr>";
				}
			}
			if ($found) {
				if ($ip == "") {
					$output .= FS::$iMgr->cbkForm("?mod=".$this->mid."&sh=2",array("id" => "swfform"));
					$output .= FS::$iMgr->select("ip",array("js" => "filterSw()"));
					$output .= $formoutput;
					$output .= "</select> ".FS::$iMgr->button("",_("Filter"),"filterSw()")."</form>";
					$output .= FS::$iMgr->js("function filterSw() {
							$('#swfdiv').fadeOut('slow',function() {
								$.post('?mod=".$this->mid."&at=2&sh=2', $('#swfform').serialize(), function(data) {
									setJSONConten('#swfdiv',data);
								});
						});
              	        $('#swfdiv').fadeIn();
						}")."<div id=\"swfdiv\">";
				}
				$output .= $grpoutput."</table>";
				$output .= $usroutput."</table>";
				if ($ip == "")
					$output .= "</div>";
			}
			return $output;
		}

		private function initIPRules($rulefilter = "") {
			$rules = array();
			$rulelist = array("read","readswdetails","readswmodules","readswvlans","readportstats",
				"write","writeportmon","restorestartupcfg","exportcfg","retagvlan",
				"sshpwd","sshportinfos","sshshowstart","sshshowrun","portmod_portsec",
				"portmod_cdp","portmod_voicevlan","portmod_dhcpsnooping","dhcpsnmgmt");
			for ($i=0;$i<count($rulelist);$i++) {
				if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulelist[$i] == $rulefilter)
					$rules[$rulelist[$i]] = array();
			}
			return $rules;
		}

		private function loadIPRules($rules,$type,$ip,$rulefilter = "") {
			$idx = "";
			if ($type == 1) {
				$idx = "gid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_ip_".$ip."_%'");
			}
			else if ($type == 2) {
				$idx = "uid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_ip_".$ip."_%'");
			}
			else
				return NULL;
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$ruleidx = preg_replace("#mrule_switchmgmt_ip_".$ip."_#","",$data2["rulename"]);
				switch($ruleidx) {
					case "read": case "readportstats": case "readswdetails": case "readswmodules":
					case "readswvlans":
					case "write": case "writeportmon": case "restorestartupcfg": case "exportcfg":
					case "retagvlan":
					case "sshpwd": case "sshportinfos": case "sshshowstart": case "sshshowrun":
					case "portmod_portsec": case "portmod_cdp": case "portmod_voicevlan":
					case "portmod_dhcpsnooping": case "dhcpsnmgmt":
						if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $ruleidx == $rulefilter)
							$rules[$ruleidx][] = $data2[$idx];
						break;
				}
			}
			return $rules;
		}

		private function showIPUsers($ip,$right,$values,$filterIP) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("u","ip",$username,$values[$i],$right,$ip);
				}
			}
			$output .= "<span id=\"anchipusrr_".FS::$iMgr->formatHTMLId("u".$ip."-".$right)."\" style=\"display:none;\"></span>";

			$idsfx = FS::$iMgr->formatHTMLId($right.$ip);

			$tmpoutput = FS::$iMgr->cbkForm("1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$right."ip\">";
			$output .= $tmpoutput.$this->userSelect("uid".$idsfx,$values)."</span></form>";
			return $output;
		}

		private function showIPGroups($ip,$right,$values,$filterIP) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("g","ip",$gname,$values[$i],$right,$ip);
				}
			}
			$output .= "<span id=\"anchipgrpr_".FS::$iMgr->formatHTMLId("g".$ip."-".$right)."\" style=\"display:none;\"></span>";

			$idsfx = FS::$iMgr->formatHTMLId($right.$ip);

			$tmpoutput = FS::$iMgr->cbkForm("1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right)."<span id=\"lg".$right."ip\">";
			$output .= $tmpoutput.$this->groupSelect("gid".$idsfx,$values)."</span></form>";
			return $output;
		}

		private function showBySNMPCommunity() {
			FS::$iMgr->setURL("sh=1");

			$community = (FS::isAjaxCall() ? FS::$secMgr->checkAndSecurisePostData("snmp") : "");

			$formoutput = FS::$iMgr->selElmt(_("All"),"NULL0");
			$output = "";

			$found = false;
			$grpoutput = "<table><tr><th>"._("snmp-community")."</th><th>"._("Right")."</th><th>".
				_("Groups")."</th></tr>";
			$usroutput = "<table><tr><th>"._("snmp-community")."</th><th>"._("Right")."</th><th>".
				_("Users")."</th></tr>";

			$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
			if ($community && $community != "NULL0")
				$filterc = $community;
			else if ($filter) $filterc = $filter;
			else $filterc = "";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				$formoutput .= FS::$iMgr->selElmt($data["name"],$data["name"],$filter == $data["name"]);
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw",($filterc ? "name = '".$filterc."'" : ""),array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) $found = true;
				// Init SNMP rights
				$grprules = $this->initSNMPRules($data["ro"],$data["rw"]);
				$usrrules = $this->initSNMPRules($data["ro"],$data["rw"]);
				$first = true;
				$grprules = $this->loadSNMPRules($grprules,1,$data["name"],$data["ro"],$data["rw"]);
				foreach ($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if ($first) $first = false;
					$grpoutput .= $this->getRightForKey($key);
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showSNMPGroups($data["name"],$key,$values,$filterc);
					$grpoutput .= "</td></tr>";
				}
				// Users
				$usrrules = $this->loadSNMPRules($usrrules,2,$data["name"],$data["ro"],$data["rw"]);
				$first = true;
				foreach ($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if ($first) $first = false;
					$usroutput .= $this->getRightForKey($key);
					$usroutput .= "</td><td>";
					$usroutput .= $this->showSNMPUsers($data["name"],$key,$values,$filterc);
					$usroutput .= "</td></tr>";
				}
			}
			if ($found) {
				if ($community == "") {
					$output .= FS::$iMgr->form("?mod=".$this->mid."&sh=1",array("id" => "snmpfform")).
						FS::$iMgr->select("snmp",array("js" => "filterSNMP()")).
						$formoutput.
						"</select> ".FS::$iMgr->button("",_("Filter"),"filterSNMP()")."</form>".
						"<div id=\"snmpfdiv\">";
					
					FS::$iMgr->js("function filterSNMP() {
						$('#snmpfdiv').fadeOut('fast',function() {
							$.post('?mod=".$this->mid."&at=2&sh=1',
								$('#snmpfform').serialize(), function(data) {
										setJSONContent('#snmpfdiv',data);
								});
						});
						$('#snmpfdiv').fadeIn('fast');
					}");
				}
				$output .= FS::$iMgr->h1("group-rights").$grpoutput."</table>".
					FS::$iMgr->h1("user-rights").$usroutput."</table>";
				if ($community == "") {
					$output .= "</div>";
				}
			}
			else {
				$output .= FS::$iMgr->printError(_("err-no-snmp-community").
					"<br /><br />".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2", _("Go")),true);
			}
			return $output;
		}

		private function initSNMPRules($ro,$rw,$rulefilter = "") {
			$rules = array();
			$rorules = array("read","readportstats","readswdetails","readswmodules","readswvlans",
				"sshportinfos","sshshowstart","sshshowrun");
			$rwrules = array("write","writeportmon","restorestartupcfg","exportcfg","retagvlan",
				"sshpwd","portmod_portsec","portmod_cdp","portmod_voicevlan","portmod_dhcpsnooping",
				"dhcpsnmgmt","rmswitch");
			if ($ro == 't') {
				for ($i=0;$i<count($rorules);$i++) {
					if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulefilter == $rorules[$i])
						$rules[$rorules[$i]] = array();
				}
			}
			if ($rw == 't') {
				for ($i=0;$i<count($rwrules);$i++) {
					if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulefilter == $rwrules[$i])
						$rules[$rwrules[$i]] = array();
				}
			}
			return $rules;
		}

		private function loadSNMPRules($rules,$type,$name,$ro,$rw,$rulefilter = "") {
			$idx = "";
			if ($type == 1) {
				$idx = "gid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
			}
			else if ($type == 2) {
				$idx = "uid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
			}
			else
				return NULL;
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$ruleidx = preg_replace("#mrule_switchmgmt_snmp_".$name."_#","",$data2["rulename"]);
				switch($ruleidx) {
					// Read rules
					case "read": case "readportstats": case "readswdetails": case "readswmodules":
					case "readswvlans": case "sshportinfos": case "sshshowstart": case "sshshowrun":
						if ($ro == 't' && (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $ruleidx == $rulefilter)) {
							$rules[$ruleidx][] = $data2[$idx];
						}
						break;
					// Write rules
					case "write": case "writeportmon": case "restorestartupcfg": case "exportcfg":
					case "retagvlan": case "sshpwd": case "portmod_portsec": case "portmod_cdp":
					case "portmod_voicevlan": case "portmod_dhcpsnooping": case "dhcpsnmgmt":
					case "rmswitch":
						if ($rw == 't' && (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $ruleidx == $rulefilter))
							$rules[$ruleidx][] = $data2[$idx];
						break;
				}
			}
			return $rules;
		}

		/*
		* $type: g (group) u (user)
		* $type2: snmp/ip
		*/
		private function showRemoveSpan($type,$type2,$name,$id,$right,$snmpip) {
			$confirm = ($type == "g" ? _("confirm-remove-groupright") : _("confirm-remove-userright"));
			$output = "<span id=\"".$type.$id.$right.$type2."\">".$name." ".
				FS::$iMgr->removeIcon(2,$type."id=".$id."&".$type2."=".$snmpip."&right=".$right,
					array("js" => true,
						"confirmtext" => $confirm,
						"confirmval" => $name
					))."<br /></span>";

			return $output;
		}

		private function showSNMPGroups($snmp,$right,$values,$filterSNMP) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("g","snmp",$gname,$values[$i],$right,$snmp);
				}
			}
			$output .= "<span id=\"anchsnmpgrpr_".FS::$iMgr->formatHTMLId("g".$snmp."-".$right)."\" style=\"display:none;\"></span>";

			$idsfx = FS::$iMgr->formatHTMLId($right.$snmp);

			$tmpoutput = FS::$iMgr->cbkForm("1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right)."<span id=\"lg".$right."snmp\">";
			$tmpoutput .= $this->groupSelect("gid".$idsfx,$values);
			$output .= $tmpoutput."</span></form>";
			return $output;
		}

		private function showSNMPUsers($snmp,$right,$values,$filterSNMP) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("u","snmp",$username,$values[$i],$right,$snmp);
				}
			}
			$output .= "<span id=\"anchsnmpusrr_".FS::$iMgr->formatHTMLId("u".$snmp."-".$right)."\" style=\"display:none;\"></span>";

			$idsfx = FS::$iMgr->formatHTMLId($right.$snmp);

			$tmpoutput = FS::$iMgr->cbkForm("1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$right."snmp\">";
			$tmpoutput .= $this->userSelect("uid".$idsfx,$values);
			$output .= $tmpoutput."</span></form>";
			return $output;
		}

		private function userSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$users = $this->getUsers();
			$found = false;
			foreach ($users as $uid => $username) {
				if (!in_array($uid,$values)) {
					if (!$found) $found = true;
					$output .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",_("Add"));
			if (!$found) return "";
			else return $output;
		}

		private function groupSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$groups = $this->getUserGroups();
			$found = false;
			foreach ($groups as $gid => $gname) {
				if (!in_array($gid,$values)) {
					if (!$found) $found = true;
					$output .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",_("Add"));
			if (!$found) return "";
			else return $output;
		}

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function getUsers() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$users[$data["uid"]] = $data["username"];
			}
			return $users;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if (!FS::isAjaxCall()) {
				$backupfound = FS::$secMgr->checkAndSecuriseGetData("bck");
				$typefound = FS::$secMgr->checkAndSecuriseGetData("type");
				if ($backupfound && $typefound)
					$output .= $this->addOrEditBackupServer();
				else {
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
					$output = FS::$iMgr->h1("title-switchrightsmgmt");
					$panElmts = array(array(1,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),_("title-rightsbysnmp")));
					// Show only if there is devices
					if (FS::$dbMgr->Count("device","ip") > 0)
						$panElmts[] = array(2,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),_("title-rightsbyswitch"));
					$panElmts[] = array(3,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),_("title-device-backup"));
					$output .= FS::$iMgr->tabPan($panElmts,$sh);
				}
			}
			else if ($sh == 1)
				$output .= $this->showBySNMPCommunity();
			else if ($sh == 2)
				$output .= $this->showBySwitch();
			else if ($sh == 3)
				$output .= $this->showBackupTab();
			return $output;
		}

		private function getRightForKey($key) {
			switch($key) {
				case "read": return _("Reading");
				case "readportstats": return _("Read-port-stats");
				case "readswdetails": return _("Read-switch-details");
				case "readswmodules": return _("Read-switch-modules");
				case "readswvlans": return _("Read-switch-vlan");
				case "sshportinfos": return _("Read-ssh-portinfos");
				case "sshshowstart": return _("Read-ssh-showstart");
				case "sshshowrun": return _("Read-ssh-showrun");
				case "write": return _("Writing");
				case "writeportmon": return _("Write-port-mon");
				case "restorestartupcfg": return _("Restore-startup-cfg");
				case "exportcfg": return _("Export-cfg");
				case "retagvlan": return _("Retag-vlan");
				case "sshpwd": return _("Set-switch-sshpwd");
				case "portmod_portsec": return _("Portmod-portsec");
				case "portmod_cdp": return _("Portmod-cdp");
				case "portmod_voicevlan": return _("Portmod-voicevlan");
				case "portmod_dhcpsnooping": return _("Portmod-dhcpsnooping");
				case "dhcpsnmgmt": return _("DHCP-Snooping-mgmt");
				case "rmswitch": return _("Remove-Switch");
				default: return FS::$iMgr->printError("err-not-found");
			}
		}

		/*
		* $type snmp/ip
		* $id uid/gid
		*/
		private function jsUserGroupSelect($right, $type, $id, $snmpip) {
			$rules = "";
			if ($type == "ip") {
				$rules = $this->initIPRules($right);
				$rules = $this->loadIPRules($rules,preg_match("#^gid#",$id) ? 1 : 2,$snmpip,$right);
			}
			else {
				$rules = $this->initSNMPRules('t','t',$right);
				$rules = $this->loadSNMPRules($rules,preg_match("#^gid#",$id) ? 1 : 2,$snmpip,'t','t',$right);
			}
			$js = "";
			foreach ($rules as $key => $values) {
				if (preg_match("#^uid#",$id)) {
					$js = sprintf("%s$('#lu%s%s').html('%s');$('#%s').select2();",
						$js, $right, $type, $this->userSelect($id,$values),$id);
				}
				else if (preg_match("#^gid#",$id)) {
					$js = sprintf("%s$('#lg%s%s').html('%s');$('#%s').select2();",
						$js, $right, $type, $this->groupSelect($id,$values),$id);
				}
			}
			return $js;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->addOrEditBackupServer(true);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add group right for SNMP/IP community
					$snmp = FS::$secMgr->checkAndSecurisePostData("snmp");
					$ip = FS::$secMgr->checkAndSecurisePostData("ip");
					$right = FS::$secMgr->checkAndSecurisePostData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					$idsfx = FS::$iMgr->formatHTMLId($right.($snmp ? $snmp : $ip));

					$gid = FS::$secMgr->checkAndSecurisePostData("gid".$idsfx);
					$uid = FS::$secMgr->checkAndSecurisePostData("uid".$idsfx);

					if ((!$gid && !$uid) || (!$snmp && !$ip) || !$right) {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					$js = "";

					if ($snmp) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'") ||
								$right == "read" &&
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" &&
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","snmp",$gname,$gid,$right,$snmp);
							$js .= $this->jsUserGroupSelect($right,"snmp","gid".$idsfx,$snmp).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchsnmpgrpr_".
								FS::$iMgr->formatHTMLId("g".$snmp."-".$right)."');";
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'") ||
								$right == "read" &&
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" &&
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","snmp",$username,$uid,$right,$snmp);
							$js .= $this->jsUserGroupSelect($right,"snmp","uid".$idsfx,$snmp).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchsnmpusrr_".
								FS::$iMgr->formatHTMLId("u".$snmp."-".$right)."');";
						}
					}
					else if ($ip) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'") ||
								!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","ip",$gname,$gid,$right,$ip);
							$js .= $this->jsUserGroupSelect($right,"ip","gid".$idsfx,$ip).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchipgrpr_".
								FS::$iMgr->formatHTMLId("g".$ip."-".$right)."');";
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'") ||
								!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","ip",$username,$uid,$right,$ip);
							$js .= $this->jsUserGroupSelect($right,"ip","uid".$idsfx,$ip).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchipusrr_".
								FS::$iMgr->formatHTMLId("u".$ip."-".$right)."');";
						}
					}

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				case 2: // Remove group/ from SNMP community
					$snmp = FS::$secMgr->checkAndSecuriseGetData("snmp");
					$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					$idsfx = FS::$iMgr->formatHTMLId($right.($snmp ? $snmp : $ip));

					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");

					if ((!$uid && !$gid) || (!$ip && !$snmp) || !$right) {
						var_dump($gid);
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if ($snmp) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
						}
					}
					else if ($ip) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								return;
							}
						}
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
					}
					if ($gid) {
						if ($snmp) {
							$js = $this->jsUserGroupSelect($right,"snmp","gid".$idsfx,$snmp);
							FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#"."g".$gid.$right."snmp');".$js);
						}
						else if ($ip) {
							$js = $this->jsUserGroupSelect($right,"ip","gid".$idsfx,$ip);
							FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#"."g".$gid.$right."ip');".$js);
						}
					}
					else if ($uid) {
						if ($snmp) {
							$js = $this->jsUserGroupSelect($right,"snmp","uid".$idsfx,$snmp);
							FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#"."u".$uid.$right."snmp');".$js);
						}
						else if ($ip) {
							$js = $this->jsUserGroupSelect($right,"ip","uid".$idsfx,$ip);
							FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#"."u".$uid.$right."ip');".$js);
						}
					}
					return;
				case 3: // add or edit backup server
					if (!FS::$sessMgr->hasRight("backup")) {
						FS::$iMgr->echoNoRights("modify backup server");
						return;
					}
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					$spath = FS::$secMgr->checkAndSecurisePostData("spath");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if ($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || $stype == NULL || ($stype != 1 && $stype != 2 && $stype != 4 && $stype != 5) || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" || $spwd != $spwd2)) || ($stype == 1 && ($slogin != "" || $spwd != "" || $spwd2 != ""))) {
						$this->log(2,"Some fields are missing/wrong for saving switch config");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}
					if ($edit) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							$this->log(1,"Server '".$saddr."' already exists for saving switch config");
							FS::$iMgr->ajaxEchoErrorNC("err-not-found");
							return;
						}
					}
					else {
						if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							$this->log(1,"Server '".$saddr."' already exists for saving switch config");
							FS::$iMgr->ajaxEchoErrorNC("err-already-exists");
							return;
						}
					}

					if ($edit) {
						$this->log(0,"Edit server '".$saddr."' (type ".$stype.") for saving switch config");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
					}
					else
						$this->log(0,"Added server '".$saddr."' (type ".$stype.") for saving switch config");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login,pwd","'".$saddr."','".$stype."','".$spath."','".$slogin."','".$spwd."'");
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);

					return;
				case 4: // remove backup server
					if (!FS::$sessMgr->hasRight("backup")) {
						FS::$iMgr->echoNoRights("remove backup server");
						return;
					}
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$stype = FS::$secMgr->checkAndSecuriseGetData("type");
					if ($saddr && $stype) {
						$this->log(0,"Delete server '".$saddr."' for saving switch config");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
						FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#b".preg_replace("#[.]#","-",$saddr).$stype."');");
					}
					else {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
					}

					return;
				default: break;
			}
		}
	};

	}

	$module = new iSwitchRightsMgmt();
?>
