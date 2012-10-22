<?php
	/*
        * Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	
	class iSearch extends genModule{
		function iSearch() { parent::genModule(); $this->loc = new lSearch(); }
		public function Load() {
			$output = "";
			$search = FS::$secMgr->checkAndSecuriseGetData("s");
			if($search && strlen($search) > 0)
				$output .= $this->findRefsAndShow($search);
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-search"));
			return $output;
		}

		private function findRefsAndShow($search) {
			$output = "<h3>".$this->loc->s("Search").": ".$search."</h3>";
			if(FS::$secMgr->isMacAddr($search)) {
				$output .= $this->showMacAddrResults($search);
		        }
			else if(FS::$secMgr->isIP($search)) {
				$output .= $this->showIPAddrResults($search);
			}
			else if(is_numeric($search)) {
				$output .= $this->showNumericResults($search);
			}
			else {
				$tmpoutput = $this->showNamedInfos($search);
				if(strlen($tmpoutput) > 0)
					$output .= $tmpoutput;
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
			}

			return $output;
		}

		private function showNumericResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$swmodid = FS::$iMgr->getModuleIdByPath("switches");

			// Prise number
			$query = FS::$pgdbMgr->Select("z_eye_switch_port_prises","ip,port,prise","prise ILIKE '".$search."%'","port");
			$devprise = array();
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("Ref-plug")."</h4>";
				}
				$swname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
				if(!isset($devprise[$swname]))
					$devprise[$swname] = array();

				$devprise[$swname][$data["port"]] = $data["prise"];
			}
			if($found) {
				foreach($devprise as $device => $devport) {
					$tmpoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
					foreach($devport as $port => $prise) {
						$convport = preg_replace("#\/#","-",$port);
        		                        $tmpoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
	                	                $tmpoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> (".$this->loc->s("Plug");" ".$prise.")</li>";
					}
					$tmpoutput .= "</ul>";
				}
				$tmpoutput .= "</div>";
			}
			$found = 0;

			// VLAN on a device
			$query = FS::$pgdbMgr->Select("device_vlan","ip,description","vlan = '".$search."'","ip");
			while($data = pg_fetch_array($query)) {
				if($dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'")) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>".$this->loc->s("title-vlan-device")."</h4>";
					}
					$tmpoutput .= "<li> <a href=\"index.php?mod=".$swmodid."&d=".$dname."&fltr=".$search."\">".$dname."</a> (".$data["description"].")<br />";
				}
			}

			if($found) $tmpoutput .= "</div>";
			$found = 0;

			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));

			return $output;
		}

		private function showNamedInfos($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$nbresults = 0;
			$swmodid = FS::$iMgr->getModuleIdByPath("switches");

			// Devices
			$query = FS::$pgdbMgr->Select("device","mac,ip,description,model","name ILIKE '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>".$this->loc->s("Network-device")."</h4>";
				$tmpoutput .= "<b>".$this->loc->s("Informations")."<i>: </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				if(strlen($data["mac"]) > 0)
					$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a> - ";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
				$nbresults++;
			}

			// Prise number
			$query = FS::$pgdbMgr->Select("z_eye_switch_port_prises","ip,port,prise","prise ILIKE '".$search."%'","port");
			$devprise = array();
			while($data = pg_fetch_array($query)) {
					if($found == 0) {
							$found = 1;
							$tmpoutput .= "<div><h4>".$this->loc->s("Ref-plug")."</h4>";
					}
					$swname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
					if(!isset($devprise[$swname]))
							$devprise[$swname] = array();

					$devprise[$swname][$data["port"]] = $data["prise"];
				$nbresults++;
			}
			if($found) {
					foreach($devprise as $device => $devport) {
							$tmpoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
							foreach($devport as $port => $prise) {
									$convport = preg_replace("#\/#","-",$port);
									$tmpoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
									$tmpoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> (".$this->loc->s("Plug")." ".$prise.")</li>";
							}
							$tmpoutput .= "</ul>";
					}
					$tmpoutput .= "</div>";
			}
			$found = 0;
			
			// DNS infos
			$searchsplit = preg_split("#\.#",$search);
			if(count($searchsplit) > 1) {
				$hostname = $searchsplit[0];
				$dnszone = "";
				for($i=1;$i<count($searchsplit);$i++) {
					$dnszone .= $searchsplit[$i];
					if($i != count($searchsplit)-1)
						$dnszone .= ".";
				}
				$query = FS::$pgdbMgr->Select("z_eye_dns_zone_record_cache","rectype,recval","record ILIKE '".$hostname."' AND zonename ILIKE '".$dnszone."'");
				while($data = pg_fetch_array($query)) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>".$this->loc->s("title-dns-records")."</h4>";
					}
					switch($data["rectype"]) {
						case "A": $tmpoutput .= $this->loc->s("ipv4-addr").": "; break;
						case "AAAA": $tmpoutput .= $this->loc->s("ipv6-addr").": "; break;
						case "CNAME": $tmpoutput .= $this->loc->s("Alias").": "; break;
						default: $tmpoutput .= $this->loc->s("Other")." (".$data["rectype"]."): "; break;
					}
					if(FS::$secMgr->isIP($data["recval"]))
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["recval"]."\">".$data["recval"]."</a><br />";
					else
						$tmpoutput .= $data["recval"]."<br />";
					$nbresults++;
				}
				if($found) $tmpoutput .= "</div>";
				$found = 0;
			}
			
			// Netbios INFOS
			$query = FS::$pgdbMgr->Select("node_nbt","mac,ip,domain,nbname,nbuser,time_first,time_last","domain ILIKE '".$search."' OR nbname ILIKE '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-netbios")."</h4>";
					$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Node")."</th><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("First-view")."</th><th>".$this->loc->s("Last-view")."</th></tr>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a></td><td>";
				$tmpoutput .= "\\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>";
				$tmpoutput .= ($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a></td><td>".$fst[0]."</td><td>".$lst[0]."</td></tr>";
				$nbresults++;
			}
			
			if($found) $tmpoutput .= "</table></div>";
			$found = 0;
			
			$tmpoutput .= $this->showRadiusInfos($search);

			if(strlen($tmpoutput) > 0)
				$output .= "<h4>".$this->loc->s("title-res-nb").": ".$nbresults."</h4>".$tmpoutput;
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));

			return $output;
		}
		
		private function showIPAddrResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$lastmac = "";
			$query = FS::$pgdbMgr->Select("z_eye_dns_zone_record_cache","zonename,record","recval ILIKE '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-dns-assoc")."</h4>";
				}
				$tmpoutput .= $data["record"].".".$data["zonename"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","macaddr,hostname,leasetime,distributed","ip = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-dhcp-distrib")."</h4>";
				}
				if(strlen($data["hostname"]) > 0)
					$tmpoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
				if(strlen($data["macaddr"]) > 0)
					$tmpoutput .= $this->loc->s("link-mac-addr").": <a href=\"index.php?mod=".$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
				$tmpoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))."<br />";
				if($data["distributed"] != 3)
					$tmpoutput .= $this->loc->s("Validity")." : ".$data["leasetime"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("node_ip","mac,time_first,time_last","ip = '".$search."'","time_last",1);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-mac-addr")."</h4>";
					$lastmac = $data["mac"];
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			if($lastmac) {
				$query = FS::$pgdbMgr->Select("node","switch,port,time_first,time_last","mac ILIKE '".$lastmac."' AND active = 't'","time_last",1,1);
				if($data = pg_fetch_array($query)) {
					$tmpoutput .= "<div><h4>".$this->loc->s("title-last-device")."</h4>";
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$switch = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
					$piece = FS::$pgdbMgr->GetOneData("z_eye_switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
					$convport = preg_replace("#\/#","-",$data["port"]);
					$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
					$tmpoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
					$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
					if($piece) $tmpoutput .= "/ ".$this->loc->s("Plug")." ".$piece;
					$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
					$tmpoutput .= "</div>";
				}
			}
			
			$query = FS::$pgdbMgr->Select("node_nbt","domain,nbname,nbuser,time_first,time_last","ip = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-netbios")."</h4>";
				}
				
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				
				$tmpoutput .= $this->loc->s("netbios-machine").": \\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>";
				$tmpoutput .= "\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a><br />";
				$tmpoutput .= $this->loc->s("netbios-user").": ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";
				$tmpoutput .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			// Devices
			$query = FS::$pgdbMgr->Select("device","mac,name,description,model","ip = '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>".$this->loc->s("Network-device")."</h4>";
				$tmpoutput .= "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a>)<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
			}
			
			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
			return $output;
		}
		
		private function showRadiusInfos($search) {
			$output = "";
			$query = FS::$pgdbMgr->Select("z_eye_radius_db_list","addr,port,dbname,login,pwd");
			while($data = pg_fetch_array($query)) {
				$radSQLMgr = new FSMySQLMgr();
				$radSQLMgr->setConfig($data["dbname"],$data["port"],$data["addr"],$data["login"],$data["pwd"]);
				$radSQLMgr->Connect();
				
				$found = 0;
				
				$query = $radSQLMgr->Select("radcheck","username","username = '".$search."'","",0,1);
				while($data2 = mysql_fetch_array($query)) {
					if(!$found) {
						$found = 1;
						$output .= $this->loc->s("Username").": ".$data2["username"];
					}
				}
				
				if(!$found) {
					$query = $radSQLMgr->Select("radreply","username","username = '".$search."'","",0,1);
					while($data2 = mysql_fetch_array($query)) {
						if(!$found) {
							$found = 1;
							$output .= $this->loc->s("Username").": ".$data2["username"];
						}
					}
				}
				
				if($found) {
					$found = 0;
					$query = $radSQLMgr->Select("radusergroup","groupname","username = '".$search."'");
					while($data2 = mysql_fetch_array($query)) {
						if(!$found) {
							$found = 1;
							$output .= "<br />Groupes:<ul>";
						}
						$output .= "<li>".$data2["groupname"]."</li>";
					}
					if($found) $output .= "</ul>";
				}
					
				
				if(FS::$secMgr->isMacAddr($search)) {
					$tmpsearch = $search[0].$search[1].$search[3].$search[4].".".$search[6].$search[7].$search[9].$search[10].".".$search[12].$search[13].$search[15].$search[16];
					$found = 0;
					$query2 = $radSQLMgr->Select("radacct","username,calledstationid,acctstarttime,acctstoptime","callingstationid = '".$tmpsearch."'");
					if($data2 = mysql_fetch_array($query2)) {
						if($found == 0) {
							$found = 1;
							$output .= "<div><h4>".$this->loc->s("title-8021x-users")."</h4>";
						}
						$fst = preg_split("#\.#",$data2["acctstarttime"]);
						$lst = preg_split("#\.#",$data2["acctstoptime"]);
						$output .= $this->loc->s("User").": ".$data2["username"]." / ".$this->loc->s("Device").": <a href=\"index.php?mod=".
						$this->mid."&s=".$data2["calledstationid"]."\">".$data2["calledstationid"]."</a>";
						$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
					}
					
					if($found) $output .= "</div>";
					$found = 0;
					$totinbw = 0;
					$totoutbw = 0;
					$query2 = $radSQLMgr->Select("radacct","calledstationid, SUM(acctinputoctets) as input, SUM(acctoutputoctets) as output, MIN(acctstarttime) as fst, MAX(acctstoptime) as lst","callingstationid = '".$tmpsearch."' GROUP BY calledstationid");
					if($data2 = mysql_fetch_array($query2)) {
						if($found == 0) {
							$found = 1;
							$output .= "<div><h4>".$this->loc->s("title-8021x-bw")."</h4>";
						}
						if($data2["input"] > 1024*1024*1024)
							$inputbw = round($data2["input"]/1024/1024/1024,2)."Go";
						else if($data2["input"] > 1024*1024)
							$inputbw = round($data2["input"]/1024/1024,2)."Mo";
						else if($data2["input"] > 1024)
							$inputbw = round($data2["input"]/1024,2)."Ko";
						else
							$inputbw = $data2["input"]." ".$this->loc->s("Bytes");
							
						if($data2["output"] > 1024*1024*1024)
							$outputbw = round($data2["output"]/1024/1024/1024,2)."Go";
						else if($data2["output"] > 1024*1024)
							$outputbw = round($data2["output"]/1024/1024,2)."Mo";
						else if($data2["output"] > 1024)
							$outputbw = round($data2["output"]/1024,2)."Ko";
						else
							$outputbw = $data2["output"]." ".$this->loc->s("Bytes");
						$fst = preg_split("#\.#",$data2["fst"]);
						$lst = preg_split("#\.#",$data2["lst"]);
						$output .= $this->loc->s("Device").": ".$data2["calledstationid"]." Download: ".$inputbw." / Upload: ".$outputbw. "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
						(strlen($lst[0]) > 0 ? $this->loc->s("Between") : $this->loc->s("Since"))." ".$fst[0].(strlen($lst[0]) > 0 ? " ".$this->loc->s("and-the")." ".$lst[0] : "").")<br /><hr>";
						$totinbw += $data2["input"];
						$totoutbw += $data2["output"];
					}
					if($found) {
						if(totinbw > 1024*1024*1024)
							$inputbw = round(totinbw/1024/1024/1024,2)."Go";
						else if($totinbw > 1024*1024)
							$inputbw = round($data2["input"]/1024/1024,2)."Mo";
						else if(totinbw > 1024)
							$inputbw = round($totinbw/1024,2)."Ko";
						else
							$inputbw = $totinbw." ".$this->loc->s("Bytes");
							
						if($totoutbw > 1024*1024*1024)
							$outputbw = round($totoutbw/1024/1024/1024,2)."Go";
						else if($totoutbw > 1024*1024)
							$outputbw = round($data2["output"]/1024/1024,2)."Mo";
						else if($totoutbw > 1024)
							$outputbw = round($totoutbw/1024,2)."Ko";
						else
							$outputbw = $totoutbw." ".$this->loc->s("Bytes");
						$output .= "<b>".$this->loc->s("Total")."</b> Download: ".$inputbw." / Upload: ".$outputbw."</div>";
					}
				}
				$found = 0;
				if(FS::$secMgr->isMacAddr($search))
					$tmpsearch = $search[0].$search[1].$search[3].$search[4].$search[6].$search[7].$search[9].$search[10].$search[12].$search[13].$search[15].$search[16];
				else
					$tmpsearch = $search;
                $query2 = $radSQLMgr->Select("radacct","calledstationid,acctterminatecause,acctstarttime,acctterminatecause,acctstoptime,acctinputoctets,acctoutputoctets","username = '".$tmpsearch."'","acctstarttime",1,10);
				while($data2 = mysql_fetch_array($query2)) {
					if($found == 0) {
						$found = 1;
						$output .= "<div><h4>".$this->loc->s("Accounting")."</h4>
						<table><tr><th>".$this->loc->s("Device")."</th><th>".$this->loc->s("start-session")."</th><th>".$this->loc->s("end-session")."</th><th>".$this->loc->s("Upload")."</th>
						<th>".$this->loc->s("Download")."</th><th>".$this->loc->s("end-session-cause")."</th></tr>";
					}
					if($data2["acctinputoctets"] > 1024*1024*1024)
							$inputbw = round($data2["acctinputoctets"]/1024/1024/1024,2)." Go";
					else if($data2["acctinputoctets"] > 1024*1024)
							$inputbw = round($data2["acctinputoctets"]/1024/1024,2)." Mo";
					else if($data2["acctinputoctets"] > 1024)
							$inputbw = round($data2["acctinputoctets"]/1024,2)." Ko";
					else
							$inputbw = $data2["acctinputoctets"]." ".$this->loc->s("Bytes");

					if($data2["acctoutputoctets"] > 1024*1024*1024)
							$outputbw = round($data2["acctoutputoctets"]/1024/1024/1024,2)." Go";
					else if($data2["acctoutputoctets"] > 1024*1024)
							$outputbw = round($data2["acctoutputoctets"]/1024/1024,2)." Mo";
					else if($data2["acctoutputoctets"] > 1024)
							$outputbw = round($data2["acctoutputoctets"]/1024,2)." Ko";
					else
							$outputbw = $data2["acctoutputoctets"]." ".$this->loc->s("Bytes");
					
					$macdev = "";
					if(strlen($data2["calledstationid"]) > 0) {
						$devportmac = preg_replace("[-]",":",$data2["calledstationid"]);
						if(FS::$secMgr->isMacAddr($devportmac)) {
							$macdevip = FS::$pgdbMgr->GetOneData("device_port","ip","mac = '".strtolower($devportmac)."'");
							$macdev = FS::$pgdbMgr->GetOneData("device","name","ip = '".$macdevip."'");
						}
						else if(preg_match('#^([0-9A-Fa-f]{4}[.]){2}[0-9A-Fa-f]{4}$#',$devportmac)) {
							$tmpmac = $devportmac[0].$devportmac[1].":".$devportmac[2].$devportmac[3].":".$devportmac[5].$devportmac[6].":".$devportmac[7].$devportmac[8].":".$devportmac[10].$devportmac[11].":".$devportmac[12].$devportmac[13];
							$macdevip = FS::$pgdbMgr->GetOneData("device_port","ip","mac = '".strtolower($tmpmac)."'");
							$macdev = FS::$pgdbMgr->GetOneData("device","name","ip = '".$macdevip."'");
						}
					}
					$output .= "<tr><td>".(strlen($macdev) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$macdev."\">".$macdev."</a>" : $this->loc->s("Unknown"))."</td>";
					$output .= "<td>".date("d-m-y H:i:s",strtotime($data2["acctstarttime"]))."</td><td>";
					$output .= ($data2["acctstoptime"] != NULL ? date("d-m-y H:i:s",strtotime($data2["acctstoptime"])) : "");
					$output .= "</td><td>".$inputbw."</td><td>".$outputbw."</td>";
					$output .= "<td>".$data2["acctterminatecause"]."</td></tr>";
					
				}
				if($found) { 
					$output .= "</table></div>";
					$output = "<h4>".$this->loc->s("Radius-Server")." (".$data["dbname"]."@".$data["addr"].":".$data["port"].")</h4>".$output;
				}
			}
			return $output;
		}
		
		private function showMacAddrResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","ip,hostname,leasetime,distributed","macaddr = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-dhcp-distrib")."</h4>";
				}
				if(strlen($data["hostname"]) > 0)
					$tmpoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
				if(strlen($data["ip"]) > 0)
					$tmpoutput .= $this->loc->s("link-ip").": <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />";
				$tmpoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? "Dynamique" : "Statique")."<br />";
				if($data["distributed"] != 3)
					$tmpoutput .= $this->loc->s("Validity")." : ".$data["leasetime"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			$query = FS::$pgdbMgr->Select("node_ip","ip,time_first,time_last","mac = '".$search."'","time_last",2);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-ip-addr")."</h4>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("node","switch,port,time_first,time_last","mac = '".$search."'","time_last",2);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-network-places")."</h4>";
				}
				$switch = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
				$piece = FS::$pgdbMgr->GetOneData("z_eye_switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
				$convport = preg_replace("#\/#","-",$data["port"]);
				$tmpoutput .=  "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
				$tmpoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
				$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
				$tmpoutput .= ($piece == NULL ? "" : " / Prise ".$piece);
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;

			$query = FS::$pgdbMgr->Select("node_nbt","nbname,domain,nbuser,time_first,time_last","mac = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>".$this->loc->s("title-netbios-name")."</h4>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= ($data["domain"] != "" ? "\\\\<a href=\"index.php?mod=".$this->mid."&nb=".$data["domain"]."\">".$data["domain"]."</a>" : "").
				"\\<a href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
				$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
			}
		
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$tmpoutput .= $this->showRadiusInfos($search);
			
			// Devices
			$query = FS::$pgdbMgr->Select("device","ip,name,description,model","mac = '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>".$this->loc->s("Network-device")."</h4>";
				$tmpoutput .= "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
			}
			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
			return $output;
		}
	};
?>
