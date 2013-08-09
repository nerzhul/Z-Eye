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
	
	final class iSwitchRightsMgmt extends FSModule {
		function __construct($locales) {
			parent::__construct($locales);
			$this->modulename = "switchrightsmgmt";
		}
		
		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-switchrightsmgmt"));

			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-datas")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-snmpgid-not-found")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-already-exist")); break;
				case 4: $output .= FS::$iMgr->printError($this->loc->s("err-not-found")); break;
				case 99: $output .= FS::$iMgr->printError($this->loc->s("err-no-rights")); break;
				default: break;
			}
			$output .= $this->showMain();
			return $output;
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
					$output .= FS::$iMgr->printError($this->loc->s("err-no-server-get")." !");
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
					$output .= FS::$iMgr->printError($this->loc->s("err-bad-server")." !");
					return $output;
				}
				$output .= "<a href=\"index.php?mod=".$this->mid."\">".$this->loc->s("Return")."</a><br />";
			}

			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-miss-bad-fields")." !"); break;
				case 3: if ($create) $output .= FS::$iMgr->printError($this->loc->s("err-server-exist")." !"); break;
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
				$output .= "<tr><td>".$this->loc->s("srv-type")."</td><td>";
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

				$output .= "<tr><th>".$this->loc->s("ip-addr")."</th><th>".$saddr."</th></tr>";
				$output .= "<tr><td>".$this->loc->s("srv-type")."</td><td>";
				switch($stype) {
					case 1: $output .= "TFTP"; break;
					case 2: $output .= "FTP"; break;
					case 4: $output .= "SCP"; break;
					case 5: $output .= "SFTP"; break;
				}
			}
			$output .= "<tr id=\"tohide1\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("User")."</td><td>".FS::$iMgr->input("slogin",$slogin)."</td></tr>";
			$output .= "<tr id=\"tohide2\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("Password")."</td><td>".FS::$iMgr->password("spwd","")."</td></tr>";
			$output .= "<tr id=\"tohide3\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("Password-repeat")."</td><td>".FS::$iMgr->password("spwd2","")."</td></tr>";
			$output .= FS::$iMgr->idxLine("server-path","spath",array("value" => $spath));
			$output .= FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function showBackupTab() {
			$output = FS::$iMgr->opendiv(1,$this->loc->s("New-Server"));

			$tmpoutput = "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Type")."</th><th>".
				$this->loc->s("server-path")."</th><th>".$this->loc->s("Login")."</th><th></th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if ($found == false) $found = true;
				$tmpoutput .= "<tr id=\"b".preg_replace("#[. ]#","-",$data["addr"]).$data["type"]."\"><td>
					<a href=\"index.php?mod=".$this->mid."&bck=".$data["addr"]."&type=".$data["type"]."\">".$data["addr"]."</td><td>";
				$bcktype = "";
				switch($data["type"]) {
					case 1: $bcktype = "TFTP"; break;
					case 2: $bcktype = "FTP"; break;
					case 4: $bcktype = "SCP"; break;
					case 5: $bcktype = "SFTP"; break;
				}
				$tmpoutput .= $bcktype."</td><td>".$data["path"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=4&addr=".$data["addr"]."&type=".$data["type"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-backupsrv")."'".$data["addr"]." (".$bcktype.")' ?","Confirm","Cancel")));
				$tmpoutput .= "</center></td></tr>";
			}
			if ($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-backup-found")." !");
			return $output;

		}

		private function showBySwitch() {
			// IP for ajax filtering
			$ip = (FS::isAjaxCall() ? FS::$secMgr->checkAndSecurisePostData("ip") : "");
			$output = "";	
			$found = false;

			$grpoutput = FS::$iMgr->h1("group-rights")."<table><tr><th>".$this->loc->s("device")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = FS::$iMgr->h1("user-rights")."<table><tr><th>".$this->loc->s("device")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";
			$formoutput = FS::$iMgr->selElmt($this->loc->s("All"),"NULL0");

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
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&sh=2",array("id" => "swfform"));
					$output .= FS::$iMgr->select("ip",array("js" => "filterSw()"));
					$output .= $formoutput;
					$output .= "</select> ".FS::$iMgr->button("",$this->loc->s("Filter"),"filterSw()")."</form>";
					$output .= FS::$iMgr->js("function filterSw() {
                                   		     	$('#swfdiv').fadeOut('slow',function() {
                                        			$.post('index.php?mod=".$this->mid."&at=2&sh=2', $('#swfform').serialize(), function(data) {
                                                        		$('#swfdiv').html(data);
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
			$tmpoutput = FS::$iMgr->cbkForm("1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$right."ip\">";
			$output .= $tmpoutput.$this->userSelect("uid",$values)."</span></form>";
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
			$tmpoutput = FS::$iMgr->cbkForm("1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right)."<span id=\"lg".$right."ip\">";
			$output .= $tmpoutput.$this->groupSelect("gid",$values)."</span></form>";
			return $output;
		}

		private function showBySNMPCommunity() {
			$community = (FS::isAjaxCall() ? FS::$secMgr->checkAndSecurisePostData("snmp") : "");

			$formoutput = FS::$iMgr->selElmt($this->loc->s("All"),"NULL0");
			$output = "";
			
			$found = false;
			$grpoutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";

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
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&sh=1",array("id" => "snmpfform"));
					$output .= FS::$iMgr->select("snmp",array("js" => "filterSNMP()"));
					$output .= $formoutput;
					$output .= "</select> ".FS::$iMgr->button("",$this->loc->s("Filter"),"filterSNMP()")."</form>";
					$output .= FS::$iMgr->js("function filterSNMP() {
        	                                $('#snmpfdiv').fadeOut('fast',function() {
	                                       		$.post('index.php?mod=".$this->mid."&at=2&sh=1', $('#snmpfform').serialize(), function(data) {
                                        	                $('#snmpfdiv').html(data);
                        	                        });
						});
						$('#snmpfdiv').fadeIn('fast');
                	                        }");
					$output .= "<div id=\"snmpfdiv\">";
				}
				$output .= FS::$iMgr->h1("group-rights").$grpoutput."</table>";
				$output .= FS::$iMgr->h1("user-rights").$usroutput."</table>";
				if ($community == "")
					$output .= "</div>";
			}
			else {
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
                                        "<br /><br /><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2\">".$this->loc->s("Go")."</a>");
			}
			return $output;
		}

		private function initSNMPRules($ro,$rw,$rulefilter = "") {
			$rules = array();
			$rorules = array("read","readportstats","readswdetails","readswmodules","readswvlans",
				"sshportinfos","sshshowstart","sshshowrun");
			$rwrules = array("write","writeportmon","restorestartupcfg","exportcfg","retagvlan",
				"sshpwd","portmod_portsec","portmod_cdp","portmod_voicevlan","portmod_dhcpsnooping",
				"dhcpsnmgmt");
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
			$confirm = ($type == "g" ? $this->loc->s("confirm-remove-groupright") : $this->loc->s("confirm-remove-userright"));
			$output = "<span id=\"".$type.$id.$right.$type2."\">".$name." ".
				FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&".$type."id=".$id."&".$type2."=".$snmpip."&right=".$right,
					array("js" => true, "confirm" => array($confirm."'".$name."' ?","Confirm","Cancel")))."<br /></span>";

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
			$tmpoutput = FS::$iMgr->cbkForm("1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right)."<span id=\"lg".$right."snmp\">";
			$tmpoutput .= $this->groupSelect("gid",$values);
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
			$tmpoutput = FS::$iMgr->cbkForm("1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$right."snmp\">";
			$tmpoutput .= $this->userSelect("uid",$values);
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
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
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
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
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
					$panElmts = array(array(1,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-rightsbysnmp")));
					// Show only if there is devices
					if (FS::$dbMgr->Count("device","ip") > 0)
						$panElmts[] = array(2,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-rightsbyswitch"));
					$panElmts[] = array(3,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-device-backup"));
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
				case "read": return $this->loc->s("Reading");
				case "readportstats": return $this->loc->s("Read-port-stats");
				case "readswdetails": return $this->loc->s("Read-switch-details");
				case "readswmodules": return $this->loc->s("Read-switch-modules");
				case "readswvlans": return $this->loc->s("Read-switch-vlan");
				case "sshportinfos": return $this->loc->s("Read-ssh-portinfos");
				case "sshshowstart": return $this->loc->s("Read-ssh-showstart");
				case "sshshowrun": return $this->loc->s("Read-ssh-showrun");
				case "write": return $this->loc->s("Writing");
				case "writeportmon": return $this->loc->s("Write-port-mon"); 
				case "restorestartupcfg": return $this->loc->s("Restore-startup-cfg");
				case "exportcfg": return $this->loc->s("Export-cfg");
				case "retagvlan": return $this->loc->s("Retag-vlan");
				case "sshpwd": return $this->loc->s("Set-switch-sshpwd");
				case "portmod_portsec": return $this->loc->s("Portmod-portsec");
				case "portmod_cdp": return $this->loc->s("Portmod-cdp");
				case "portmod_voicevlan": return $this->loc->s("Portmod-voicevlan");
				case "portmod_dhcpsnooping": return $this->loc->s("Portmod-dhcpsnooping");
				case "dhcpsnmgmt": return $this->loc->s("DHCP-Snooping-mgmt");
				default: return FS::$iMgr->printError($this->loc->s("err-not-found"));
			}
		}

		/*
		* $type snmp/ip
		* $id uid/gid
		*/
		private function jsUserGroupSelect($right,$type,$id,$snmpip) {
			$rules = "";
			if ($type == "ip") {
				$rules = $this->initIPRules($right);
				$rules = $this->loadIPRules($rules,$id == "gid" ? 1 : 2,$snmpip,$right);
			}
			else {
				$rules = $this->initSNMPRules('t','t',$right);
				$rules = $this->loadSNMPRules($rules,$id == "gid" ? 1 : 2,$snmpip,'t','t',$right);
			}
			$js = "";
			foreach ($rules as $key => $values) {
				if ($id == "uid")
					$js .= "$('#lu".$right.$type."').html('".$this->userSelect("uid",$values)."');";
				else if ($id == "gid")
					$js .= "$('#lg".$right.$type."').html('".$this->groupSelect("gid",$values)."');";
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
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					$snmp = FS::$secMgr->checkAndSecurisePostData("snmp");
					$ip = FS::$secMgr->checkAndSecurisePostData("ip");
					$right = FS::$secMgr->checkAndSecurisePostData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					if ((!$gid && !$uid) || (!$snmp && !$ip) || !$right) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
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
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","snmp",$gname,$gid,$right,$snmp);
							$js .= $this->jsUserGroupSelect($right,"snmp","gid",$snmp);
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchsnmpgrpr_".FS::$iMgr->formatHTMLId("g".$snmp."-".$right)."');";
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'") ||
								$right == "read" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","snmp",$username,$uid,$right,$snmp);
							$js .= $this->jsUserGroupSelect($right,"snmp","uid",$snmp);
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchsnmpusrr_".FS::$iMgr->formatHTMLId("u".$snmp."-".$right)."');";
						}
					}
					else if ($ip) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'") ||
								!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","ip",$gname,$gid,$right,$ip);
							$js .= $this->jsUserGroupSelect($right,"ip","gid",$ip);
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchipgrpr_".FS::$iMgr->formatHTMLId("g".$ip."-".$right)."');";
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'") ||
								!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","ip",$username,$uid,$right,$ip);
							$js .= $this->jsUserGroupSelect($right,"ip","uid",$ip);
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchipusrr_".FS::$iMgr->formatHTMLId("u".$ip."-".$right)."');";
						}
					}

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				case 2: // Remove group/ from SNMP community
					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					$snmp = FS::$secMgr->checkAndSecuriseGetData("snmp");
					$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					if ((!$uid && !$gid) || (!$ip && !$snmp) || !$right) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if ($snmp) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
						}
					}
					else if ($ip) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
								$ip."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
						}
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
					}
					if ($gid) {
						if ($snmp) {
							$js = $this->jsUserGroupSelect($right,"snmp","gid",$snmp);
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."g".$gid.$right."snmp');".$js);
						}
						else if ($ip) {
							$js = $this->jsUserGroupSelect($right,"ip","gid",$ip);
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."g".$gid.$right."ip');".$js);
						}
					}
					else if ($uid) {
						if ($snmp) {
							$js = $this->jsUserGroupSelect($right,"snmp","uid",$snmp);
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."u".$uid.$right."snmp');".$js);
						}
						else if ($ip) {
							$js = $this->jsUserGroupSelect($right,"ip","uid",$ip);
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."u".$uid.$right."ip');".$js); 
						}
					}
					return;
				case 3: // add or edit backup server
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_backup")) {
						$this->log(2,"User don't have rights to add/edit server '".$saddr."' from switches backup");
						FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=99");
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
						if (FS::isAjaxCall())
							FS::$iMgr->ajaxEchoNC("err-bad-datas");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
						return;
					}
					if ($edit) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							$this->log(1,"Server '".$saddr."' already exists for saving switch config");
							if (FS::isAjaxCall())
								FS::$iMgr->ajaxEchoNC("err-not-found");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=4");
							return;
						}
					}
					else {
						if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							$this->log(1,"Server '".$saddr."' already exists for saving switch config");
							if (FS::isAjaxCall())
								FS::$iMgr->ajaxEchoNC("err-already-exists");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=3");
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
					if (!FS::$sessMgr->hasRight("mrule_switchmgmt_backup")) {
						$this->log("User don't have rights to remove server '".$saddr."' from switches backup");
						if (FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-rights");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=99");
						return;
					}
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$stype = FS::$secMgr->checkAndSecuriseGetData("type");
					if ($saddr && $stype) {
						$this->log(0,"Delete server '".$saddr."' for saving switch config");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
						if (FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#b".preg_replace("#[.]#","-",$saddr).$stype."');");
						else FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					}
					else {
						if (FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-datas");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
					}
					
					return;
				default: break;
			}
		}
	};
?>
