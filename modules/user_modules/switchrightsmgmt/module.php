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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	
	class iSwitchRightsMgmt extends genModule{
		function iSwitchRightsMgmt() { parent::genModule(); $this->loc = new lSwitchRightsMgmt(); }
		
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
			if(!$create) {
				FS::$iMgr->showReturnMenu(true);
				$output = FS::$iMgr->h2("title-edit-backup-switch-server");
				$addr = FS::$secMgr->checkAndSecuriseGetData("bck");
				$type = FS::$secMgr->checkAndSecuriseGetData("type");
				if(!$addr || $addr == "" || !$type || !FS::$secMgr->isNumeric($type)) {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-server-get")." !");
					return $output;
				}
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","login,pwd,path","addr = '".$addr."' AND type = '".$type."'");
				if($data = FS::$dbMgr->Fetch($query)) {
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
				case 3: if($create) $output .= FS::$iMgr->printError($this->loc->s("err-server-exist")." !"); break;
			}

			$output .= FS::$iMgr->js("function arangeform() {
				if(document.getElementsByName('stype')[0].value == 1) {
					$('#tohide1').hide();
					$('#tohide2').hide();
					$('#tohide3').hide();
				} else if(document.getElementsByName('stype')[0].value == 2 || document.getElementsByName('stype')[0].value == 4 || document.getElementsByName('stype')[0].value == 5) {
					$('#tohide1').show();
					$('#tohide2').show();
					$('#tohide3').show();
				}};");
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=3",array("id" => "swbckfrm"));
			$output .= "<table>";
			if($create) {
				$output .= FS::$iMgr->idxLine($this->loc->s("ip-addr"),"saddr",$saddr,array("type" => "ip"));
				$output .= "<tr><td>".$this->loc->s("srv-type")."</td><td>";
				$output .= FS::$iMgr->select("stype","arangeform();");
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
			$output .= FS::$iMgr->idxLine($this->loc->s("server-path"),"spath",$spath);
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=3","swbckfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}

		private function showBackupTab() {
			$formoutput = $this->addOrEditBackupServer(true);

			$output = FS::$iMgr->opendiv($formoutput,$this->loc->s("New-Server"));

			$tmpoutput = "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Type")."</th><th>".
				$this->loc->s("server-path")."</th><th>".$this->loc->s("Login")."</th><th></th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&bck=".$data["addr"]."&type=".$data["type"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>";
				switch($data["type"]) {
					case 1: $tmpoutput .= "TFTP"; break;
					case 2: $tmpoutput .= "FTP"; break;
					case 4: $tmpoutput .= "SCP"; break;
					case 5: $tmpoutput .= "SFTP"; break;
				}
				$tmpoutput .= "</td><td>".$data["path"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=4&addr=".$data["addr"]."&type=".$data["type"]);
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
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
			if($ip && $ip != "NULL0")
				$filteri = $ip;
			else if($filter) $filteri = $filter;
			else $filteri = ""; 

			$query = FS::$dbMgr->Select("device","ip,name","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				$formoutput .=  FS::$iMgr->selElmt($data["name"],$data["ip"],$filter == $data["ip"]);
			}

			$query = FS::$dbMgr->Select("device","ip,name",$filteri ? "ip = '".$filteri."'" : "","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				// Init array for device
				$grprules = array("read" => array(), "readswdetails" => array(), "readswmodules" => array(), "readswvlans" => array(), "readportstats" => array(), 
					"write" => array(), "writeportmon" => array(), "restorestartupcfg" => array(), "exportcfg" => array(), "retagvlan" => array(),
					"sshpwd" => array(), "sshportinfos" => array(), "sshshowstart" => array(), "sshshowrun" => array(), "portmod_portsec" => array(),
					"portmod_cdp" => array(), "portmod_voicevlan" => array(), "portmod_dhcpsnooping" => array(), "dhcpsnmgmt" => array());
				$usrrules = array("read" => array(), "readswdetails" => array(), "readswmodules" => array(), "readswvlans" => array(), "readportstats" => array(), 
					"write" => array(), "writeportmon" => array(), "restorestartupcfg" => array(), "exportcfg" => array(), "retagvlan" => array(),
					"sshpwd" => array(), "sshportinfos" => array(), "sshshowstart" => array(), "sshshowrun" => array(), "portmod_portsec" => array(),
					"portmod_cdp" => array(), "portmod_voicevlan" => array(), "portmod_dhcpsnooping" => array(), "dhcpsnmgmt" => array());
				// Groups
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_swip_".$data["ip"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_read")
						array_push($grprules["read"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readportstats")
						array_push($grprules["readportstats"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswdetails")
						array_push($grprules["readswdetails"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswmodules")
						array_push($grprules["readswmodules"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswvlans")
						array_push($grprules["readswvlans"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_write")
						array_push($grprules["write"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_writeportmon")
						array_push($grprules["writeportmon"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_restorestartupcfg")
						array_push($grprules["restorestartupcfg"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_exportcfg")
						array_push($grprules["exportcfg"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_retagvlan")
						array_push($grprules["retagvlan"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshpwd")
						array_push($grprules["sshpwd"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshportinfos")
						array_push($grprules["sshportinfos"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshshowstart")
						array_push($grprules["sshshowstart"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshshowrun")
						array_push($grprules["sshshowrun"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_portsec")
						array_push($grprules["portmod_portsec"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_cdp")
						array_push($grprules["portmod_cdp"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_voicevlan")
						array_push($grprules["portmod_voicevlan"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_dhcpsnooping")
						array_push($grprules["portmod_dhcpsnooping"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_dhcpsnmgmt")
						array_push($grprules["dhcpsnmgmt"],$data2["gid"]);
				}
				$first = true;
				foreach($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					$grpoutput .= $this->getRightForKey($key);
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showIPGroups($data["ip"],$key,$values,$filteri);
				}
				// Users			
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_swip_".$data["ip"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_read")
						array_push($usrrules["read"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readportstats")
						array_push($usrrules["readportstats"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswdetails")
						array_push($usrrules["readswdetails"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswmodules")
						array_push($usrrules["readswmodules"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswvlans")
						array_push($usrrules["readswvlans"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_write")
						array_push($usrrules["write"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_writeportmon")
						array_push($usrrules["writeportmon"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_restorestartupcfg")
						array_push($usrrules["restorestartupcfg"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_exportcfg")
						array_push($usrrules["exportcfg"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_retagvlan")
						array_push($usrrules["retagvlan"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshpwd")
						array_push($usrrules["sshpwd"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshportinfos")
						array_push($usrrules["sshportinfos"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshshowstart")
						array_push($usrrules["sshshowstart"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_sshshowrun")
						array_push($usrrules["sshshowrun"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_portsec")
						array_push($usrrules["portmod_portsec"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_cdp")
						array_push($usrrules["portmod_cdp"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_voicevlan")
						array_push($usrrules["portmod_voicevlan"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_portmod_dhcpsnooping")
						array_push($usrrules["portmod_dhcpsnooping"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_dhcpsnmgmt")
						array_push($usrrules["dhcpsnmgmt"],$data2["uid"]);
				}
				$first = true;
				foreach($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					$usroutput .= $this->getRightForKey($key);
					$usroutput .= "</td><td>";
					$usroutput .= $this->showIPUsers($data["ip"],$key,$values,$filteri);
				}
			}
			if($found) {
				if($ip == "") {
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&sh=2",array("id" => "swfform"));
					$output .= FS::$iMgr->select("ip","filterSw()");
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
				if($ip == "")
					$output .= "</div>";
			}
			return $output;
		}

		private function showIPUsers($ip,$right,$values,$filterIP) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'")." ".
						FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&uid=".$values[$i]."&ip=".$ip."&right=".$right.($filterIP ? "&filter=".$filterIP : ""))."<br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$users = $this->getUsers();
			$found = false;
			foreach($users as $uid => $username) {
				if(!in_array($uid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function showIPGroups($ip,$right,$values,$filterIP) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'")." ".
						FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&gid=".$values[$i]."&ip=".$ip."&right=".$right.($filterIP ? "&filter=".$filterIP : ""))."<br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1".($filterIP ? "&filter=".$filterIP : ""));
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$groups = $this->getUserGroups();
			$found = false;
			foreach($groups as $gid => $gname) {
				if(!in_array($gid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
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
			if($community && $community != "NULL0")
				$filterc = $community;
			else if($filter) $filterc = $filter;
			else $filterc = ""; 

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				$formoutput .= FS::$iMgr->selElmt($data["name"],$data["name"],$filter == $data["name"]);
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw",($filterc ? "name = '".$filterc."'" : ""),"name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				// Init SNMP rights
				$grprules = array();
				$usrrules = array();
				if($data["ro"] == 't') {
					$grprules["read"] = array();
					$usrrules["read"] = array();
					$grprules["readportstats"] = array();
					$usrrules["readportstats"] = array();
					$grprules["readswdetails"] = array();
					$usrrules["readswdetails"] = array();
					$grprules["readswmodules"] = array();
					$usrrules["readswmodules"] = array();
					$grprules["readswvlans"] = array();
					$usrrules["readswvlans"] = array();
					$grprules["sshportinfos"] = array();
					$usrrules["sshportinfos"] = array();
					$grprules["sshshowstart"] = array();
					$usrrules["sshshowstart"] = array();
					$grprules["sshshowrun"] = array();
					$usrrules["sshshowrun"] = array();
				}
				if($data["rw"] == 't') {
					$grprules["write"] = array();
					$usrrules["write"] = array();
					$grprules["writeportmon"] = array();
					$usrrules["writeportmon"] = array();
					$grprules["restorestartupcfg"] = array();
					$usrrules["restorestartupcfg"] = array();
					$grprules["exportcfg"] = array();
					$usrrules["exportcfg"] = array();
					$grprules["retagvlan"] = array();
					$usrrules["retagvlan"] = array();
					$grprules["sshpwd"] = array();
					$usrrules["sshpwd"] = array();
					$grprules["portmod_portsec"] = array();
					$usrrules["portmod_portsec"] = array();
					$grprules["portmod_cdp"] = array();
					$usrrules["portmod_cdp"] = array();
					$grprules["portmod_voicevlan"] = array();
					$usrrules["portmod_voicevlan"] = array();
					$grprules["portmod_dhcpsnooping"] = array();
					$usrrules["portmod_dhcpsnooping"] = array();
					$grprules["dhcpsnmgmt"] = array();
					$usrrules["dhcpsnmgmt"] = array();
				}
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					// Read rules
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($grprules["read"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readportstats" && $data["ro"] == 't')
						array_push($grprules["readportstats"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswdetails" && $data["ro"] == 't')
						array_push($grprules["readswdetails"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswmodules" && $data["ro"] == 't')
						array_push($grprules["readswmodules"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswvlans" && $data["ro"] == 't')
						array_push($grprules["readswvlans"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshportinfos" && $data["ro"] == 't')
						array_push($grprules["sshportinfos"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshshowstart" && $data["ro"] == 't')
						array_push($grprules["sshshowstart"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshshowrun" && $data["ro"] == 't')
						array_push($grprules["sshshowrun"],$data2["gid"]);
					// Write rules
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($grprules["write"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_writeportmon" && $data["rw"] == 't')
						array_push($grprules["writeportmon"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_restorestartupcfg" && $data["rw"] == 't')
						array_push($grprules["restorestartupcfg"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_exportcfg" && $data["rw"] == 't')
						array_push($grprules["exportcfg"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_retagvlan" && $data["rw"] == 't')
						array_push($grprules["retagvlan"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshpwd" && $data["rw"] == 't')
						array_push($grprules["sshpwd"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_portsec" && $data["rw"] == 't')
						array_push($grprules["portmod_portsec"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_cdp" && $data["rw"] == 't')
						array_push($grprules["portmod_cdp"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_voicevlan" && $data["rw"] == 't')
						array_push($grprules["portmod_voicevlan"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_dhcpsnooping" && $data["rw"] == 't')
						array_push($grprules["portmod_dhcpsnooping"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_dhcpsnmgmt" && $data["rw"] == 't')
						array_push($grprules["dhcpsnmgmt"],$data2["gid"]);
				}
				$first = true;
				foreach($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					$grpoutput .= $this->getRightForKey($key);
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showSNMPGroups($data["name"],$key,$values,$filterc);
				}			
				// Users
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					// Read rules
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($usrrules["read"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readportstats" && $data["ro"] == 't')
						array_push($usrrules["readportstats"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswdetails" && $data["ro"] == 't')
						array_push($usrrules["readswdetails"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswmodules" && $data["ro"] == 't')
						array_push($usrrules["readswmodules"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswvlans" && $data["ro"] == 't')
						array_push($usrrules["readswvlans"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshportinfos" && $data["ro"] == 't')
						array_push($usrrules["sshportinfos"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshshowstart" && $data["ro"] == 't')
						array_push($usrrules["sshshowstart"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshshowrun" && $data["ro"] == 't')
						array_push($usrrules["sshshowrun"],$data2["uid"]);
					// Write rules
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($usrrules["write"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_writeportmon" && $data["rw"] == 't')
						array_push($usrrules["writeportmon"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_restorestartupcfg" && $data["rw"] == 't')
						array_push($usrrules["restorestartupcfg"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_exportcfg" && $data["rw"] == 't')
						array_push($usrrules["exportcfg"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_retagvlan" && $data["rw"] == 't')
						array_push($usrrules["retagvlan"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_sshpwd" && $data["rw"] == 't')
						array_push($usrrules["sshpwd"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_portsec" && $data["rw"] == 't')
						array_push($usrrules["portmod_portsec"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_cdp" && $data["rw"] == 't')
						array_push($usrrules["portmod_cdp"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_voicevlan" && $data["rw"] == 't')
						array_push($usrrules["portmod_voicevlan"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_portmod_dhcpsnooping" && $data["rw"] == 't')
						array_push($usrrules["portmod_dhcpsnooping"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_dhcpsnmgmt" && $data["rw"] == 't')
						array_push($usrrules["dhcpsnmgmt"],$data2["uid"]);
				}
				$first = true;
				foreach($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					$usroutput .= $this->getRightForKey($key);
					$usroutput .= "</td><td>";
					$usroutput .= $this->showSNMPUsers($data["name"],$key,$values,$filterc);
				}			
			}
			if($found) {
				if($community == "") {
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&sh=1",array("id" => "snmpfform"));
					$output .= FS::$iMgr->select("snmp","filterSNMP()");
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
				if($community == "")
					$output .= "</div>";
			}
			else {
				$output .= FS::$iMgr->printError($this->loc->s("err-no-snmp-community").
                                        "<br /><br /><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("snmpmgmt")."&sh=2\">".$this->loc->s("Go")."</a>");
			}
			return $output;
		}

		private function showSNMPGroups($snmp,$right,$values,$filterSNMP) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'")." ".
						FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&gid=".$values[$i]."&snmp=".$snmp."&right=".$right.($filterSNMP ? "&filter=".$filterSNMP : ""))."<br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$groups = $this->getUserGroups();
			$found = false;
			foreach($groups as $gid => $gname) {
				if(!in_array($gid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function showSNMPUsers($snmp,$right,$values,$filterSNMP) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'")." ".
						FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&uid=".$values[$i]."&snmp=".$snmp."&right=".$right.($filterSNMP ? "&filter=".$filterSNMP : ""))."<br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1".($filterSNMP ? "&filter=".$filterSNMP : ""));
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("uid");
			$users = $this->getUsers();
			$found = false;
			foreach($users as $uid => $username) {
				if(!in_array($uid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function getUsers() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username");
			while($data = FS::$dbMgr->Fetch($query)) {
				$users[$data["uid"]] = $data["username"];
			}
			return $users;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$backupfound = FS::$secMgr->checkAndSecuriseGetData("bck");
				$typefound = FS::$secMgr->checkAndSecuriseGetData("type");
				if($backupfound && $typefound)
					$output .= $this->addOrEditBackupServer();
				else {
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
					$output = FS::$iMgr->h1("title-switchrightsmgmt");
					$panElmts = array(array(1,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-rightsbysnmp")));
					// Show only if there is devices
					if(FS::$dbMgr->Count("device","ip") > 0)
						array_push($panElmts,array(2,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-rightsbyswitch")));
					array_push($panElmts,array(3,"mod=".$this->mid.($filter ? "&filter=".$filter : ""),$this->loc->s("title-device-backup")));
					$output .= FS::$iMgr->tabPan($panElmts,$sh);
				}
			}
			else if($sh == 1)
				$output .= $this->showBySNMPCommunity();	
			else if($sh == 2)
				$output .= $this->showBySwitch();
			else if($sh == 3)
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

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add group right for SNMP/IP community 
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					$snmp = FS::$secMgr->checkAndSecurisePostData("snmp");
					$ip = FS::$secMgr->checkAndSecurisePostData("ip");
					$right = FS::$secMgr->checkAndSecurisePostData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					if((!$gid && !$uid) || (!$snmp && !$ip) || !$right) {
						FS::$iMgr->redir("mod=".$this->mid."&err=1".($filter ? "&filter=".$filter : ""));
						return;
					}

					if($snmp) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'") ||
								$right == "read" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=2".($filter ? "&filter=".$filter : ""));
								return;
							}
							if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=3".($filter ? "&filter=".$filter : ""));
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'") ||
								$right == "read" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" && 
									!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=2".($filter ? "&filter=".$filter : ""));
								return;
							}
							if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=3".($filter ? "&filter=".$filter : ""));
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
						}
					}
					else if($ip) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'") ||
							!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
							FS::$iMgr->redir("mod=".$this->mid."&err=2".($filter ? "&filter=".$filter : ""));
							return;
						}
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
							$ip."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
							FS::$iMgr->redir("mod=".$this->mid."&err=3".($filter ? "&filter=".$filter : ""));
							return;
						}
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
					}

					FS::$iMgr->redir("mod=".$this->mid.($filter ? "&filter=".$filter : ""));
					return;
				case 2: // Remove group from SNMP community
					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					$snmp = FS::$secMgr->checkAndSecuriseGetData("snmp");
					$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");

					if((!$uid && !$gid) || (!$ip && !$snmp) || !$right) {
						FS::$iMgr->redir("mod=".$this->mid."&err=1".($filter ? "&filter=".$filter : ""));
						return;
					}

					if($snmp) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=4".($filter ? "&filter=".$filter : ""));
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->redir("mod=".$this->mid."&err=4".($filter ? "&filter=".$filter : ""));
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
						}
					}
					else if($ip) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
							$ip."_".$right."' AND gid = '".$gid."'")) {
							FS::$iMgr->redir("mod=".$this->mid."&err=4".($filter ? "&filter=".$filter : ""));
							return;
						}
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
					}
					FS::$iMgr->redir("mod=".$this->mid.($filter ? "&filter=".$filter : ""));
					return;
				case 3: // add or edit backup server
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_backup")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",2,"User don't have rights to add/edit server '".$saddr."' from switches backup");
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
					if($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || $stype == NULL || ($stype != 1 && $stype != 2 && $stype != 4 && $stype != 5) || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" || $spwd != $spwd2)) || ($stype == 1 && ($slogin != "" || $spwd != "" || $spwd2 != ""))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",2,"Some fields are missing/wrong for saving switch config");
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-datas");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
						return;
					}
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",1,"Server '".$saddr."' already exists for saving switch config");
							if(FS::isAjaxCall())
								echo $this->loc->s("err-not-found");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=4");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",1,"Server '".$saddr."' already exists for saving switch config");
							if(FS::isAjaxCall())
								echo $this->loc->s("err-already-exists");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=3");
							return;
						}
					}

					if($edit) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",0,"Edit server '".$saddr."' (type ".$stype.") for saving switch config");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
					}
					else
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",0,"Added server '".$saddr."' (type ".$stype.") for saving switch config");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."save_device_servers","addr,type,path,login,pwd","'".$saddr."','".$stype."','".$spath."','".$slogin."','".$spwd."'");
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);

					return;
				case 4: // remove backup server
					if(!FS::$sessMgr->hasRight("mrule_switchmgmt_backup")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",2,"User don't have rights to remove server '".$saddr."' from switches backup");
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-rights");
						else
						FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=99");
						return;
					}
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$stype = FS::$secMgr->checkAndSecuriseGetData("type");
					if($saddr && $stype) {
						FS::$log->i(FS::$sessMgr->getUserName(),"switchmgmt",0,"Delete server '".$saddr."' for saving switch config");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
						FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					}
					else {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-datas");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
					}
					
					return;
				default: break;
			}
		}
	};
?>
