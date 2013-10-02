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

	require_once(dirname(__FILE__)."/../dnsmgmt/objects.php");
	require_once(dirname(__FILE__)."/../ipmanager/objects.php");
	require_once(dirname(__FILE__)."/../switches/objects.php");

	final class iSearch extends FSModule {
		function __construct($locales) { 
			parent::__construct($locales);
			$this->modulename = "search";
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("Search"));
			$autosearch = FS::$secMgr->checkAndSecuriseGetData("term");
			if ($autosearch) {
				return $this->findRefsAndShow($autosearch,true);
			}
			else {
				return FS::$iMgr->printError($this->loc->s("err-no-search"));
			}
		}

		public function LoadForAndroid() {
			$search = FS::$secMgr->checkAndSecurisePostData("s");
			if (!$search) {
				return NULL;
			}
			FS::$searchMgr->setMode(1);
			$this->findRefsAndShow($search);
		}

		private function findRefsAndShow($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			if (!$autocomp) {
				$output = FS::$iMgr->h1($this->loc->s("Search").": ".$search,true);
				if (FS::$secMgr->isMacAddr($search)) {
					$tmpoutput .= $this->showMacAddrResults($search);
				}
				else if (FS::$secMgr->isIP($search)) {
					$tmpoutput .= $this->showIPAddrResults($search);
				}
				else if (FS::$secMgr->isNumeric($search)) {
					$tmpoutput .= $this->showNumericResults($search);
				}
				else {
					$tmpoutput2 = $this->showNamedInfos($search);
					if (strlen($tmpoutput2) > 0) {
						$tmpoutput .= $tmpoutput2;
					}
				}
				
				$output .= FS::$iMgr->h2($this->loc->s("title-res-nb").": ".FS::$searchMgr->getResultsCount(),true).
					$tmpoutput;
				
				if (FS::$searchMgr->getMode() != 1) {
					if (count(FS::$searchMgr->getResults()) > 0) {
						foreach (FS::$searchMgr->getResults() as $title => $results) {
							$output .= "<div id=\"searchres\" style=\"width: auto; min-width:400px;\">".
									($title != "" ? FS::$iMgr->h3($title).FS::$iMgr->hr() : "").$results."</div>";
						}
					}
					else {
						return $output.FS::$iMgr->printError($this->loc->s("err-no-res"));
					}
				}
							
				$this->log(0,"searching '".$search."'");
			}
			else {
				if (preg_match('#^([0-9A-F]{2}:)#i',$search) || preg_match('#([0-9A-F]{2}-)#i',$search)) {
					$this->showMacAddrResults($search,true);
				}
				else if (preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.#",$search)) {
					$this->showIPAddrResults($search,true);
				}
				else if (FS::$secMgr->isNumeric($search)) {
					$this->showNumericResults($search,true);
				}
				else {
					$this->showNamedInfos($search,true);
				}
				
				$output = "[";
				$outresults = array();
				foreach (FS::$searchMgr->getAutoResults() as $key => $values) {
					for ($i=0;$i<count($values);$i++) {
						$outresults[] = $values[$i];
					}
				}
				$outresults = array_unique($outresults);
				sort($outresults);
				for ($i=0;$i<count($outresults) && $i<10;$i++) {
					if ($i!=0) $output .= ",";
					$output .= "{\"id\":\"".$outresults[$i]."\",\"value\":\"".$outresults[$i]."\"}";
				}
				$output .= "]";
			}
			return $output;
		}

		private function showNumericResults($search,$autocomp=false) {
			$tmpoutput = "";
			
			$objs = array(new netPlug(), new netRoom(), new netDevice(), new dhcpIP());
			
			$count = count($objs);
			for ($i=0;$i<$count;$i++) {
				if (!$autocomp) {
					$tmpoutput .= $objs[$i]->search($search);
				}
				else {
					$objs[$i]->search($search,true);
				}
			}

			if (!$autocomp) {
				// A numeric can be an IP
				if (FS::$secMgr->isNumeric($search) && $search < 256) {
					$tmpoutput .= $this->showIPAddrResults($search);
				}
				
				if (strlen($tmpoutput) > 0) {
					return $tmpoutput;
				}
			}
		}

		private function showNamedInfos($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";

			$objs = array(new dnsRecord(), new dnsZone(), new dnsACL(), new dnsCluster(), new dnsServer(), new dnsTSIGKey(),
				new dhcpSubnet(), new dhcpServer(), new dhcpCluster(), new dhcpIP(), new dhcpCustomOption(), new dhcpOption(),
				new dhcpOptionGroup(),
				new netDevice(), new netPlug(), new netRoom(), new netDevicePort() , new netNode());
				
			$count = count($objs);
			for ($i=0;$i<$count;$i++) {
				if (!$autocomp) {
					$tmpoutput .= $objs[$i]->search($search);
				}
				else {
					$objs[$i]->search($search,true);
				}
			}

			if (!$autocomp) {
				if (strlen($tmpoutput) > 0) {
					$output .= $tmpoutput;
				}

				return $output;
			}
		}

		private function showIPAddrResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			
			$objs = array(new dnsRecord(), new dhcpIP(), new dhcpServer(), new dhcpSubnet(),
				new netNode(), new netDevice());
			
			$count = count($objs);
			for ($i=0;$i<$count;$i++) {
				if (!$autocomp) {
					$tmpoutput .= $objs[$i]->search($search);
				}
				else {
					$objs[$i]->search($search,true);
				}
			}

			if (!$autocomp) {
				if (strlen($tmpoutput) > 0) {
					$output .= $tmpoutput;
				}
				return $output;
			}
		}

		private function showRadiusInfos($search,$autocomp=false) {
			$output = "";
			if (!$autocomp) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","addr,port,dbname,login,pwd,dbtype,tradcheck,tradreply,tradusrgrp,tradacct");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$radSQLMgr = new AbstractSQLMgr();
					$radSQLMgr->setConfig($data["dbtype"],$data["dbname"],$data["port"],$data["addr"],$data["login"],$data["pwd"]);
					$radSQLMgr->Connect();

					$raddatas = false;

					$found = 0;
					// Format MAC addr for radius users
					if (FS::$secMgr->isMacAddr($search))
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].$search[6].$search[7].$search[9].$search[10].$search[12].$search[13].$search[15].$search[16];
					else
						$tmpsearch = $search;
					$query2 = $radSQLMgr->Select($data["tradcheck"],"username","username = '".$tmpsearch."'",array("limit" => 1));
					while ($data2 = $radSQLMgr->Fetch($query2)) {
						if (!$found) {
							$found = 1;
							$output .= $this->loc->s("Username").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("radius")."&h=".$data["addr"]."&p=".$data["port"]."&r=".$data["dbname"]."&radentrytype=1&radentry=".$data2["username"]."\">".$data2["username"]."</a>";
						}
					}
					if (!$found) {
						$query2 = $radSQLMgr->Select($data["tradreply"],"username","username = '".$tmpsearch."'",array("limit" => 1));
						while ($data2 = $radSQLMgr->Fetch($query2)) {
							if (!$found) {
								$found = 1;
								$output .= $this->loc->s("Username").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("radius")."&h=".$data["addr"]."&p=".$data["port"]."&r=".$data["dbname"]."&radentrytype=1&radentry=".$data2["username"]."\">".$data2["username"]."</a>";
							}
						}
					}
					
					if ($found) {
						$found = 0;
						$raddatas = true;
						$query2 = $radSQLMgr->Select($data["tradusrgrp"],"groupname","username = '".$tmpsearch."'");
						while ($data2 = $radSQLMgr->Fetch($query2)) {
							if (!$found) {
								$found = 1;
								$output .= "<ul>";
							}
							$output .= "<li>".$data2["groupname"]."</li>";
						}
						if ($found) $output .= "</ul>";
					}

					if (FS::$secMgr->isMacAddr($search)) {
						// Format mac addr for some accounting
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].".".$search[6].$search[7].$search[9].$search[10].".".$search[12].$search[13].$search[15].$search[16];
						$found = 0;
						$query2 = $radSQLMgr->Select($data["tradacct"],"username,calledstationid,acctstarttime,acctstoptime","callingstationid = '".$tmpsearch."'");
						if ($data2 = $radSQLMgr->Fetch($query2)) {
							if ($found == 0) {
								$found = 1;
								$raddatas = true;
							}
							$fst = preg_split("#\.#",$data2["acctstarttime"]);
							$lst = preg_split("#\.#",$data2["acctstoptime"]);
							$locoutput = $this->loc->s("User").": ".$data2["username"]." / ".$this->loc->s("Device").": <a href=\"index.php?mod=".
							$this->mid."&s=".$data2["calledstationid"]."\">".$data2["calledstationid"]."</a>";
							$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
						}

						if ($found) $output = FS::$iMgr->h3("title-8021x-users").$output;
						$found = 0;
						$totinbw = 0;
						$totoutbw = 0;
						$locoutput = "";
						$query2 = $radSQLMgr->Select($data["tradacct"],"calledstationid, SUM(acctinputoctets) as input, SUM(acctoutputoctets) as output, MIN(acctstarttime) as fst, MAX(acctstoptime) as lst","callingstationid = '".$tmpsearch."'",array("group" => "calledstationid"));
						if ($data2 = $radSQLMgr->Fetch($query2)) {
							if ($found == 0) {
								$found = 1;
								$raddatas = true;
							}
							if ($data2["input"] > 1024*1024*1024)
								$inputbw = round($data2["input"]/1024/1024/1024,2)."Go";
							else if ($data2["input"] > 1024*1024)
								$inputbw = round($data2["input"]/1024/1024,2)."Mo";
							else if ($data2["input"] > 1024)
								$inputbw = round($data2["input"]/1024,2)."Ko";
							else
								$inputbw = $data2["input"]." ".$this->loc->s("Bytes");
								
							if ($data2["output"] > 1024*1024*1024)
								$outputbw = round($data2["output"]/1024/1024/1024,2)."Go";
							else if ($data2["output"] > 1024*1024)
								$outputbw = round($data2["output"]/1024/1024,2)."Mo";
							else if ($data2["output"] > 1024)
								$outputbw = round($data2["output"]/1024,2)."Ko";
							else
								$outputbw = $data2["output"]." ".$this->loc->s("Bytes");
							$fst = preg_split("#\.#",$data2["fst"]);
							$lst = preg_split("#\.#",$data2["lst"]);
							$locoutput .= $this->loc->s("Device").": ".$data2["calledstationid"]." Download: ".$inputbw." / Upload: ".$outputbw. "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
							(strlen($lst[0]) > 0 ? $this->loc->s("Between") : $this->loc->s("Since"))." ".$fst[0].(strlen($lst[0]) > 0 ? " ".$this->loc->s("and-the")." ".$lst[0] : "").")<br />".FS::$iMgr->hr();
							$totinbw += $data2["input"];
							$totoutbw += $data2["output"];
						}
						if ($found) {
							if (totinbw > 1024*1024*1024)
								$inputbw = round(totinbw/1024/1024/1024,2)."Go";
							else if ($totinbw > 1024*1024)
								$inputbw = round($data2["input"]/1024/1024,2)."Mo";
							else if (totinbw > 1024)
								$inputbw = round($totinbw/1024,2)."Ko";
							else
								$inputbw = $totinbw." ".$this->loc->s("Bytes");
								
							if ($totoutbw > 1024*1024*1024)
								$outputbw = round($totoutbw/1024/1024/1024,2)."Go";
							else if ($totoutbw > 1024*1024)
								$outputbw = round($data2["output"]/1024/1024,2)."Mo";
							else if ($totoutbw > 1024)
								$outputbw = round($totoutbw/1024,2)."Ko";
							else
								$outputbw = $totoutbw." ".$this->loc->s("Bytes");
							$tmpoutput = "<b>".$this->loc->s("Total")."</b> Download: ".$inputbw." / Upload: ".$outputbw."</div>";
							$output .= FS::$iMgr->h3("title-8021x-bw").$locoutput;
						}
					}
					$found = 0;
					if (FS::$secMgr->isMacAddr($search))
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].$search[6].$search[7].$search[9].$search[10].$search[12].$search[13].$search[15].$search[16];
					else
						$tmpsearch = $search;

					$query2 = $radSQLMgr->Select($data["tradacct"],"calledstationid,acctterminatecause,acctstarttime,acctterminatecause,acctstoptime,acctinputoctets,acctoutputoctets",
						"username = '".$tmpsearch."'",array("order" => "acctstarttime","ordersens" => 1,"limit" => 10));
					while ($data2 = $radSQLMgr->Fetch($query2)) {
						if ($found == 0) {
							$found = 1;
							$raddatas = true;
							$output .= "<table><tr><th>".$this->loc->s("Device")."</th><th>".$this->loc->s("start-session")."</th><th>".$this->loc->s("end-session")."</th><th>".$this->loc->s("Upload")."</th>
							<th>".$this->loc->s("Download")."</th><th>".$this->loc->s("end-session-cause")."</th></tr>";
						}
						if ($data2["acctinputoctets"] > 1024*1024*1024)
								$inputbw = round($data2["acctinputoctets"]/1024/1024/1024,2)." Go";
						else if ($data2["acctinputoctets"] > 1024*1024)
								$inputbw = round($data2["acctinputoctets"]/1024/1024,2)." Mo";
						else if ($data2["acctinputoctets"] > 1024)
								$inputbw = round($data2["acctinputoctets"]/1024,2)." Ko";
						else
								$inputbw = $data2["acctinputoctets"]." ".$this->loc->s("Bytes");

						if ($data2["acctoutputoctets"] > 1024*1024*1024)
								$outputbw = round($data2["acctoutputoctets"]/1024/1024/1024,2)." Go";
						else if ($data2["acctoutputoctets"] > 1024*1024)
								$outputbw = round($data2["acctoutputoctets"]/1024/1024,2)." Mo";
						else if ($data2["acctoutputoctets"] > 1024)
								$outputbw = round($data2["acctoutputoctets"]/1024,2)." Ko";
						else
								$outputbw = $data2["acctoutputoctets"]." ".$this->loc->s("Bytes");
						
						$macdev = "";
						if (strlen($data2["calledstationid"]) > 0) {
							$devportmac = preg_replace("[-]",":",$data2["calledstationid"]);
							if (FS::$secMgr->isMacAddr($devportmac)) {
								$macdevip = FS::$dbMgr->GetOneData("device_port","ip","mac = '".strtolower($devportmac)."'");
								$macdev = FS::$dbMgr->GetOneData("device","name","ip = '".$macdevip."'");
							}
							else if (preg_match('#^([0-9A-Fa-f]{4}[.]){2}[0-9A-Fa-f]{4}$#',$devportmac)) {
								$tmpmac = $devportmac[0].$devportmac[1].":".$devportmac[2].$devportmac[3].":".$devportmac[5].$devportmac[6].":".$devportmac[7].$devportmac[8].":".$devportmac[10].$devportmac[11].":".$devportmac[12].$devportmac[13];
								$macdevip = FS::$dbMgr->GetOneData("device_port","ip","mac = '".strtolower($tmpmac)."'");
								$macdev = FS::$dbMgr->GetOneData("device","name","ip = '".$macdevip."'");
							}
						}
						$output .= "<tr><td>".(strlen($macdev) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$macdev."\">".$macdev."</a>" : $this->loc->s("Unknown"))."</td>
							<td>".date("d-m-y H:i:s",strtotime($data2["acctstarttime"]))."</td><td>".
							($data2["acctstoptime"] != NULL ? date("d-m-y H:i:s",strtotime($data2["acctstoptime"])) : "").
						"</td><td>".$inputbw."</td><td>".$outputbw."</td>
						<td>".$data2["acctterminatecause"]."</td></tr>";
					}
					if ($found) {
						$output .= "</table>";
					}
					if ($raddatas) { 
						$output = $this->divEncapResults(FS::$iMgr->h3($this->loc->s("Radius-Server")." (".$data["dbname"].
							"@".$data["addr"].":".$data["port"].")",true).FS::$iMgr->hr().$output,"",true);
					}
				}
				return $output;
			}
		}

		private function showMacAddrResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$search = preg_replace("#[-]#",":",$search);

			if (!$autocomp) {
				if ($company = FS::$dbMgr->GetOneData("oui","company","oui = '".substr($search,0,8)."'")) {
					$tmpoutput .= $this->divEncapResults($company,"Manufacturer");
				}
			}

			if (FS::$sessMgr->hasRight("mrule_ipmanager_read")) {
				if (!$autocomp) {
					$tmpoutput .= (new dhcpIP())->search($search);
				}
				else {
					(new dhcpIP())->search($search,true);
				}
			}

			if (FS::$sessMgr->hasRight("mrule_switches_read")) {
				if (!$autocomp) {
					$tmpoutput .= (new netNode())->search($search);
				}
				else {
					(new netNode())->search($search,true);
				}
			}
	
			if (FS::$sessMgr->hasRight("mrule_radius_read")) {
				if (!$autocomp)
					$tmpoutput .= $this->showRadiusInfos($search);
				else
					$this->showRadiusInfos($search,true);
			}
			
			if (FS::$sessMgr->hasRight("mrule_switches_read")) {
				if (!$autocomp) {
					$tmpoutput .= (new netDevice())->search($search);
				}
				else {
					(new netDevice())->search($search,true);
				}
			}
			if (!$autocomp) {
				if (strlen($tmpoutput) > 0) {
					$output .= $tmpoutput;
				}
				return $output;
			}
		}

		private function divEncapResults($output,$title,$minwidth=false) {
			if ($output) {
				return "<div id=\"searchres\"".($minwidth ? " style=\"width: auto; min-width:400px;\"" : "").">".
					($title != "" ? FS::$iMgr->h3($title).FS::$iMgr->hr() : "").$output."</div>";
			}
			else {
				return "";
			}
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$search = FS::$secMgr->checkAndSecurisePostData("s");
					if ($search) {
						$js = "$('#main').html('".addslashes($this->findRefsAndShow($search))."');";
						FS::$iMgr->ajaxEcho("Done",$js);
					}
					return;
			}
		}
	};
?>
