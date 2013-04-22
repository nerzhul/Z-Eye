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
	
	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");

	class iIPManager extends FSModule{
		function iIPManager($locales) { parent::FSModule($locales); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-ip-management"));
			$output = "";
			$output .= $this->showStats();
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$netoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-ip-management");

				if(FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt")) {
					$err = FS::$secMgr->checkAndSecuriseGetData("err");
					switch($err) {
						case 1: case 7: case 8: $output .= FS::$iMgr->printError($this->loc->s("err-bad-datas")); break;
						case 2: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-not-match")); break;
						case 3: $output .= FS::$iMgr->printError($this->loc->s("err-ssh-conn-failed")); break;
						case 4: $output .= FS::$iMgr->printError($this->loc->s("err-ssh-auth-failed")); break;
						case 5: $output .= FS::$iMgr->printError($this->loc->s("err-already-exists")); break;
						case 6: {
							$file = FS::$secMgr->checkAndSecuriseGetData("file");
							if($file)
								$output .= FS::$iMgr->printError($this->loc->s("err-unable-read")." '".$file."'");
							else
								$output .= FS::$iMgr->printError($this->loc->s("bad-datas"));
							break;
						}
						default: break;
					}
					// To add servers
					FS::$iMgr->setJSBuffer(1);
					$formoutput = $this->showDHCPSrvForm();
	                                $output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("title-add-server"),array("width" => 600,"line" => true));

					// To edit/delete servers
					if(FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_servers","addr") > 0) {
						FS::$iMgr->setJSBuffer(1);
						$formoutput = $this->showDHCPSrvList();
	                            		$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Server-mgmt"),array("width" => 400));
					}
        	                }

				$netfound = false;
				$tmpoutput = FS::$iMgr->h2("title-subnet-management").$this->loc->s("choose-net");
				$tmpoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
				$tmpoutput .= FS::$iMgr->select("f","submit()");
				$formoutput = "";
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask","",array("order" => "netid"));
				while($data = FS::$dbMgr->Fetch($query)) {
					if(!$netfound) $netfound = true;
					$formoutput .= FS::$iMgr->selElmt($data["netid"]."/".$data["netmask"],$data["netid"],($filter == $data["netid"] ? true : false));
				}
				$tmpoutput .= $formoutput;
				$tmpoutput .= "</select> ";
				$tmpoutput .= FS::$iMgr->submit("","Consulter");
				$tmpoutput .= "</form><br />";
				if(!$netfound)
                                        return $output.= FS::$iMgr->printError($this->loc->s("no-net-found"));

				$output .= $tmpoutput;

				if($filter) {
					if(!FS::$secMgr->isIP($filter))
						return $output.FS::$iMgr->printError($this->loc->s("bad-filter"));
					$panElmts = array(array(1,"mod=".$this->mid."&f=".$filter,$this->loc->s("Stats")),
						array(4,"mod=".$this->mid."&f=".$filter,$this->loc->s("History")),
						array(3,"mod=".$this->mid."&f=".$filter,$this->loc->s("Monitoring")),
						array(2,"mod=".$this->mid."&f=".$filter,$this->loc->s("Expert-tools")));
					$output .= FS::$iMgr->tabPan($panElmts,$showmodule);
				}
			} else {
				if(!$showmodule || $showmodule == 1) {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask","netid = '".$filter."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						$iparray = array();
						$netoutput .= FS::$iMgr->h3("Réseau : ".$data["netid"]."/".$data["netmask"],true);
						$netoutput .= "<center><div id=\"".$data["netid"]."\"></div></center>";

						$netobj = new FSNetwork();
						$netobj->setNetAddr($data["netid"]);
						$netobj->setNetMask($data["netmask"]);

						$swfound = false;

						// Bufferize switch list
						$switchlist = array();

						$query2 = FS::$dbMgr->Select("device","ip,name");
						while($data2 = FS::$dbMgr->Fetch($query2))
							$switchlist[$data2["ip"]] = $data2["name"];

						// Initiate network IPs
						$lastip = $netobj->getLastUsableIPLong()+1;
						for($i=($netobj->getFirstUsableIPLong());$i<$lastip;$i++) {
							$iparray[$i] = array();
							$iparray[$i]["mac"] = "";
							$iparray[$i]["host"] = "";
							$iparray[$i]["ltime"] = "";
							$iparray[$i]["distrib"] = 0;
							$iparray[$i]["servers"] = array();
							$iparray[$i]["switch"] = "";
							$iparray[$i]["port"] = "";
						}

						// Fetch datas
						$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,server","netid = '".$data["netid"]."'");
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							// If it's reserved on a host don't override status
							if($iparray[ip2long($data2["ip"])]["distrib"] != 3) {
								$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
								$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
								$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
								$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
							}
							// List servers where the data is
							array_push($iparray[ip2long($data2["ip"])]["servers"],$data2["server"]);
							if(strlen($iparray[ip2long($data2["ip"])]["mac"]) > 0 && strlen($iparray[ip2long($data2["ip"])]["switch"]) == 0) {
								$sw = FS::$dbMgr->GetOneData("node","switch","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'",array("order" => "time_last","ordersens" => 2));
								$port = FS::$dbMgr->GetOneData("node","port","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'",array("order" => "time_last","ordersens" => 2));
								if($sw && $port) {
									$iparray[ip2long($data2["ip"])]["switch"] = $switchlist[$sw];
									$iparray[ip2long($data2["ip"])]["port"] = $port;
									$swfound = true;
								}
							}

						}

						$used = 0;
						$reserv = 0;
						$free = 0;
						$distrib = 0;
						$fixedip = 0;

						$netoutput .= "<center><table id=\"tipList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("IP-Addr")."</th><th>".$this->loc->s("Status")."</th>
							<th>".$this->loc->s("MAC-Addr")."</th><th>".$this->loc->s("Hostname")."</th><th>";
						if($swfound)
							$netoutput .= $this->loc->s("Switch")."</th><th>".$this->loc->s("Port")."</th><th>";
						$netoutput .= "Fin du bail</th><th>Serveurs</th></tr></thead>";

						foreach($iparray as $key => $value) {
							$rstate = "";
							$style = "";
							switch($value["distrib"]) {
								case 1:
									$rstate = $this->loc->s("Free");
									$style = "background-color: #BFFFBF;";
									$free++;
									break;
								case 2:
									$rstate = $this->loc->s("Used");
									$style = "background-color: #FF6A6A;";
									$used++;
									break;
								case 3:
									$rstate = $this->loc->s("Reserved");
									$style = "background-color: #FFFF80;";
									$reserv++;
									break;
								case 4:
									$rstate = $this->loc->s("Distributed");
									$style = "background-color: #BFFBFF;";
									$distrib++;
									break;
								default: {
										$rstate = $this->loc->s("Free");
										$style = "background-color: #BFFFBF;";
										$mac = FS::$dbMgr->GetOneData("node_ip","mac","ip = '".long2ip($key)."' AND time_last > (current_timestamp - interval '1 hour') AND active = 't'");
										if($mac) {
											$query3 = FS::$dbMgr->Select("node","switch,port,time_last","mac = '".$mac."' AND active = 't'");
											if($data3 = FS::$dbMgr->Fetch($query3)) {
												$rstate = $this->loc->s("Stuck-IP");
												$style = "background-color: orange;";
												$fixedip++;
											}
											else
												$free++;
										}
										else
											$free++;
									}
									break;
							}
							$netoutput .= "<tr style=\"$style\"><td><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".long2ip($key)."\">";
							$netoutput .= long2ip($key)."</a>";
							$netoutput .= "</td><td>".$rstate."</td><td>";
							$netoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$value["mac"]."\">".$value["mac"]."</a></td><td>";
							$netoutput .= $value["host"]."</td><td>";
							// Show switch column only of a switch is here
							if($swfound) {
								$netoutput .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."\">".$value["switch"]."</a>" : "");
								$netoutput .= "</td><td>";
								$netoutput .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."&p=".$value["port"]."\">".$value["port"]."</a>" : "");
								$netoutput .= "</td><td>";
							}
							$netoutput .= $value["ltime"]."</td><td>";
							$count = count($value["servers"]);
							for($i=0;$i<$count;$i++) {
								if($i > 0) $netoutput .= "<br />";
								$netoutput .= $value["servers"][$i];
							}
							$netoutput .= "</td></tr>";
						}
						$netoutput .= "</table></center><br /><hr>";
						FS::$iMgr->jsSortTable("tipList");
						$js = "var chart = new Highcharts.Chart({
							chart: { renderTo: '".$data["netid"]."', plotBackgroundColor: null, plotBorderWidth: null, plotShadow: false },
							title: { text: '' },
							tooltip: { formatter: function() { return '<b>'+this.point.name+'</b>: '+this.y+' ('+
										Math.round(this.percentage*100)/100+' %)'; } },
							plotOptions: {
								pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: {
										enabled: true,formatter: function() { return '<b>'+this.point.name+'</b>: '+
										this.y+' ('+Math.round(this.percentage*100)/100+' %)'; }
							}}},
							series: [{ type: 'pie', data: [";
						if($used > 0) $js .= "{ name: '".$this->loc->s("Baux")."', y: ".$used.", color: 'red' },";
						if($reserv > 0) $js .= "{ name: '".$this->loc->s("Reservations")."', y: ".$reserv.", color: 'yellow'},";
						if($fixedip > 0) $js .= "{ name: '".$this->loc->s("Stuck-IP")."', y: ".$fixedip.", color: 'orange'},";
						if($distrib > 0) $js .= "{ name: '".$this->loc->s("Available-s")."', y: ".$distrib.", color: 'cyan'},";
						$js .= "{ name: '".$this->loc->s("Free-s")."', y:".$free.", color: 'green'}]
							}]});";
						$netoutput .= FS::$iMgr->js($js);
					}
					$output .= $netoutput;
				}
				else if($showmodule == 2) {
					$output .= FS::$iMgr->h4("title-search-old");
					$output .= FS::$iMgr->js("function searchobsolete() {
						$('#obsres').html('".FS::$iMgr->img('styles/images/loader.gif')."');
						$.post('index.php?at=3&mod=".$this->mid."&act=2', { ival: document.getElementsByName('ival')[0].value, obsdata: document.getElementsByName('obsdata')[0].value}, function(data) {
							$('#obsres').html(data);
						});return false;}");
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->hidden("obsdata",$filter);
					$output .= $this->loc->s("intval-days")." ".FS::$iMgr->numInput("ival")."<br />";
					$output .= FS::$iMgr->JSSubmit("search",$this->loc->s("Search"),"return searchobsolete();");
					$output .= "</form><div id=\"obsres\"></div>";
				}
				else if($showmodule == 3) {
					$output .= FS::$iMgr->h4("Monitoring",true);
					$wlimit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","warnuse","subnet = '".$filter."'");
					$climit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","crituse","subnet = '".$filter."'");
					$maxage = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","maxage","subnet = '".$filter."'");
					$enmon = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","enmon","subnet = '".$filter."'");
					$eniphistory = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","eniphistory","subnet = '".$filter."'");
					$contact = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","contact","subnet = '".$filter."'");
					$output .= "<div id=\"monsubnetres\"></div>";
	                                $output .= FS::$iMgr->form("index.php?mod=".$this->mid."&f=".$filter."&act=3",array("id" => "monsubnet"));
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->check("eniphistory",array("check" => $eniphistory == 't',"label" => $this->loc->s("En-IP-history")))."</li>
						<li>".FS::$iMgr->check("enmon",array("check" => $enmon == 1,"label" => $this->loc->s("En-monitor")))."</li><li>";
                                        $output .= FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("warn-line"), "tooltip" => "tooltip-%use"))."</li><li>";
					$output .= FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("crit-line"), "tooltip" => "tooltip-%use"))."</li><li>";
					$output .= FS::$iMgr->numInput("maxage",($maxage > 0 ? $maxage : 0),array("size" => 7, "length" => 7, "label" => $this->loc->s("max-age"), "tooltip" => "tooltip-max-age"))."</li><li>";
					$output .= FS::$iMgr->input("contact",$contact,20,40,$this->loc->s("Contact"),"tooltip-contact")."</li><li>";
					$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
					$output .= FS::$iMgr->js("$('#monsubnet').submit(function(event) {
        	                                event.preventDefault();
                	                        $.post('index.php?mod=".$this->mid."&at=3&f=".$filter."&act=3', $('#monsubnet').serialize(), function(data) {
                        	                        $('#monsubnetres').html(data);
                                	        });
	                                });");
				}
				else if($showmodule == 4) {
					$output .= FS::$iMgr->js("function historyDateChange() {
						$('#hstcontent').hide(\"slow\",function() { $('#hstcontent').html(''); 
						$.post('index.php?mod=".$this->mid."&act=4',$('#hstfrm').serialize(), function(data) {
							$('#hstcontent').show(\"fast\",function() { $('#hstcontent').html(data); });
						}); }); }");

					$output .= "<div id=\"hstcontent\">".$this->showHistory($filter)."</div>";
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=4",array("id" => "hstfrm"));
					$output .= FS::$iMgr->hidden("filter",$filter);
					$date = FS::$dbMgr->GetMin(PGDbConfig::getDbPrefix()."dhcp_subnet_history","collecteddate");
					if(!$date) $date = "now";
					$diff = ceil((strtotime("now")-strtotime($date))/(24*60*60));
					$output .= FS::$iMgr->slider("hstslide","daterange",1,$diff,array("hidden" => "jour(s)","width" => "200px","value" => "1"));
					$output .= FS::$iMgr->button("but",$this->loc->s("change-interval"),"historyDateChange()")."</form>";
				}
				else
					$output .= FS::$iMgr->printError($this->loc->s("no-tab"));
			}
			return $output;
		}

		private function showDHCPSrvForm($addr = "",$user = "",$dhcpdpath = "",$leasepath = "",$reservconfpath = "",$subnetconfpath = "") {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=5").$this->loc->s("note-needed")."<table>";
			if($addr)
				$output .= "<tr><td>".$this->loc->s("server-addr")."</td><td>".$addr.FS::$iMgr->hidden("addr",$addr).FS::$iMgr->hidden("edit",1);
			else
				FS::$iMgr->idxLine($this->loc->s("server-addr")." (*)","addr",$addr,array("length" => 128));
			$output .= FS::$iMgr->idxLine($this->loc->s("ssh-user")." (*)","sshuser",$user,array("length" => 128)).
				FS::$iMgr->idxLine($this->loc->s("ssh-pwd")." (*)","sshpwd","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("ssh-pwd-repeat")." (*)","sshpwd2","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("dhcpd-path"),"dhcpdpath",$dhcpdpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-dhcpdpath")).
				FS::$iMgr->idxLine($this->loc->s("lease-path"),"leasepath",$leasepath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-leasepath")).
				FS::$iMgr->idxLine($this->loc->s("reservconf-path"),"reservconfpath",$reservconfpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-reservconfpath")).
				FS::$iMgr->idxLine($this->loc->s("subnetconf-path"),"subnetconfpath",$subnetconfpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-subnetconfpath")).
				FS::$iMgr->tableSubmit($this->loc->s("Save")).
                        	"</table></form>";
			return $output;
		}

		private function showDHCPSrvList() {
			$output = "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("ssh-user")."</th><th></th></tr>";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","addr,sshuser,dhcpdpath,leasespath,reservconfpath,subnetconfpath");
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= "<tr><td>".FS::$iMgr->opendiv($this->showDHCPSrvForm($data["addr"],$data["sshuser"],$data["dhcpdpath"],$data["leasespath"],$data["reservconfpath"],
					$data["subnetconfpath"]),$data["addr"])."</td><td>".$data["sshuser"]."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=6&addr=".$data["addr"],array("js" => true, "confirm" =>
					array($this->loc->s("confirm-remove-dhcp").$data["addr"]."' ?","Confirm","Cancel"))).
					"</td></tr>";
			}
			$output .= "</table>";
			return $output;
		}

		private function showHistory($filter,$interval = 1) {
			$output = FS::$iMgr->h3($this->loc->s("title-history-since")." ".$interval." ".$this->loc->s("days"),true);
			$output .= "<div id=\"hstgr\"></div>";
			$results = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_history","ipfree,ipactive,ipreserved,ipdistributed,collecteddate","collecteddate > (NOW()- '".$interval." day'::interval) and subnet = '".$filter."'",
				array("order" => "collecteddate","ordersens" => 2));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($results[$data["collecteddate"]])) $results[$data["collecteddate"]] = array();
				$results[$data["collecteddate"]]["baux"] = $data["ipactive"];
				$results[$data["collecteddate"]]["reserv"] = $data["ipreserved"];
				$results[$data["collecteddate"]]["avail"] = $data["ipdistributed"];
			}
			$netobj = new FSNetwork();
                        $netobj->setNetAddr($filter);
                        $netobj->setNetMask(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netmask","netid ='".$filter."'"));

			// JS Table
			$labels = $baux = $reserv = $avail = $free = $total = "";
			// To show or not if no data
			$reservshow = $bauxshow = $availshow = false;
			// Show only modifications
			$lastvalues = array();
			end($results);
			$lastres = key($results);
			$totalvals = 0;
			foreach($results as $date => $values) {
				if($labels == "") {
					// Bufferize vals
                                        $bauxval = (isset($values["baux"]) ? $values["baux"] : 0);
                                        $reservval = (isset($values["reserv"]) ? $values["reserv"] : 0);
                                        $availval = (isset($values["avail"]) ? $values["avail"] : 0);

                                        // Write js table
                                        $labels .= "'".$date."'";
                                        if($bauxval > 0) $bauxshow = true;
                                        $baux .= $bauxval;
                                        if($reservval > 0) $reservshow = true;
                                        $reserv .= $reservval;
                                        if($availval > 0) $availshow = true;
                                        $avail .= $availval;

                                        $totdistrib = ($bauxval+$reservval+$availval);
                                        $total .= $totdistrib;
                                        $free .= ($netobj->getMaxHosts() - $totdistrib);
                                        // Save this occur
                                        $lastvalues = array("baux" => $bauxval, "reserv" => $reservval, "avail" => $availval);
					$totalvals++;
				}
				else {
					// Bufferize vals
					$bauxval = (isset($values["baux"]) ? $values["baux"] : 0);
					$reservval = (isset($values["reserv"]) ? $values["reserv"] : 0);
					$availval = (isset($values["avail"]) ? $values["avail"] : 0);

					if($bauxval != $lastvalues["baux"] || $reservval != $lastvalues["reserv"] ||
						$availval != $lastvalues["avail"] || $date == $lastres) {
						// Write js table
						$labels .= ",'".$date."'";
						if($bauxval > 0) $bauxshow = true;
						$baux .= ",".$bauxval;
						if($reservval > 0) $reservshow = true;
                	                	$reserv .= ",".$reservval;
						if($availval > 0) $availshow = true;
        	                                $avail .= ",".$availval;

						$totdistrib = ($bauxval+$reservval+$availval);
                	                        $total .= ",".$totdistrib;
                        	                $free .= ",".($netobj->getMaxHosts() - $totdistrib);
						$totalvals++;
					}
					// Save this occur
					$lastvalues = array("baux" => $bauxval, "reserv" => $reservval, "avail" => $availval);
				}
			}
			$js = "$(function(){ var hstgr;
                        	$(document).ready(function() { hstgr = new Highcharts.Chart({
                                	chart: { renderTo: 'hstgr', type: 'line' },
                                        title: { text: '' },
					tooltip: { crosshairs: true },
                                        xAxis: { categories: [".$labels."], gridLineWidth: 1, tickInterval: ".round($totalvals/10)." },
                                        yAxis: { title: { text: 'Nombre d\'adresses' } },
                                        legend: { layout: 'vertical', align: 'right', verticalAlign: 'top',
                                        	x: -10, y: 100 },
                                        series: [ { name: '".addslashes($this->loc->s("Usable"))."',
						data: [".$total."], color: 'green' },
						{ name: '".addslashes($this->loc->s("not-usable"))."',
                                                data: [".$free."], color: 'black' },";
					if($bauxshow) $js .= "{ name: '".addslashes($this->loc->s("Baux"))."',
						data: [".$baux."], color: 'red' },";
					if($reservshow) $js .= "{ name: '".addslashes($this->loc->s("Reservations"))."',
						data: [".$reserv."], color: 'yellow' },";
					if($availshow) $js .= "{ name: '".addslashes($this->loc->s("Available-s"))."',
						data: [".$avail."], color: 'cyan' }";
			$js .= "]});});});";
			$output .= FS::$iMgr->js($js);
			return $output;
		}

		private function writeConfigToServer($server = NULL) {

			$conns = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."server_list","addr,login,pwd","dhcp = 1");
		        while($data = FS::$dbMgr->Fetch($query)) {
		                $conn = ssh2_connect($data["addr"],22);
                		if(!$conn) {
		                        return $this->loc->s("error-fail-connect-ssh").$data["addr"];
		                }
                		else if(!ssh2_auth_password($conn, $data["login"], $data["pwd"])) {
                			return "Authentication error for server '".$data["addr"]."' with login '".$data["login"];
		                }
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to filter values");
						FS::$iMgr->redir("mod=".$this->mid."");
					}
					else {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User filter by ".$filtr);
						FS::$iMgr->redir("mod=".$this->mid."&f=".$filtr);
					}
					return;
				case 2:
					$filter = FS::$secMgr->checkAndSecurisePostData("obsdata");
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$filter || !FS::$secMgr->isIP($filter) || !$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						echo FS::$iMgr->printError($this->loc->s("err-invalid-req"));
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when trying to find obsolete datas");
						return;
					}

					$output = "";
					$obsoletes = array();
					$found = false;
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname","netid = '".$filter."' AND distributed = 3");
					while($data = FS::$dbMgr->Fetch($query)) {
						$ltime = FS::$dbMgr->GetOneData("node","time_last","mac = '".$data["macaddr"]."'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
						if($ltime) {
							if(strtotime($ltime) < strtotime("-".$interval." day",strtotime(date("y-m-d H:i:s")))) {
								$obsoletes[$data["ip"]] = $data["ip"]." - <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a>";
								$obsoletes[$data["ip"]] .= " (".$this->loc->s("last-view")." ".date("d/m/y H:i",strtotime($ltime)).")";
								$obsoletes[$data["ip"]] .= "<br />";
								if(!$found) $found = true;
							}
						}
					}
					if($found) {
						echo FS::$iMgr->h4("title-old-record");
						$logbuffer = "";
						foreach($obsoletes as $key => $value) {
							$logbuffer .= $value;
							echo $value;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User find obsolete datas :<br />".$logbuffer);
					}
					else echo FS::$iMgr->printDebug($this->loc->s("no-old-record"));
					
					
					return;
				case 3:
					$filtr = FS::$secMgr->checkAndSecuriseGetData("f");
					$warn = FS::$secMgr->checkAndSecurisePostData("wlimit");
					$crit = FS::$secMgr->checkAndSecurisePostData("climit");
					$maxage = FS::$secMgr->checkAndSecurisePostData("maxage");
					$contact = FS::$secMgr->checkAndSecurisePostData("contact");
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
					$eniphistory = FS::$secMgr->checkAndSecurisePostData("eniphistory");
					if(!$filtr || !FS::$secMgr->isIP($filtr) || !$warn || !FS::$secMgr->isNumeric($warn) || $warn < 0 || $warn > 100|| !$crit || !FS::$secMgr->isNumeric($crit) || $crit < 0 || $crit > 100 ||
						!FS::$secMgr->isNumeric($maxage) || $maxage < 0 || !$contact || !FS::$secMgr->isMail($contact)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to monitor subnet");
						echo FS::$iMgr->printError($this->loc->s("err-miss-data"));
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid","netid = '".$filtr."'");
					if(!$exist) {
						echo FS::$iMgr->printError($this->loc->s("err-bad-subnet"));
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"User try to monitor inexistant subnet '".$filtr."'");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet = '".$filtr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet,warnuse,crituse,contact,enmon,maxage,eniphistory","'".$filtr."','".$warn."','".$crit."','".$contact."','".($enmon == "on" ? "1" : "0").
						"','".$maxage."','".($eniphistory == "on" ? "t" : "f")."'");
					echo FS::$iMgr->printDebug($this->loc->s("modif-record"));

					FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User ".($enmon == "on" ? "enable" : "disable")." monitoring for subnet '".$filtr."'");
					return;
				case 4:
					$filter = FS::$secMgr->checkAndSecurisePostData("filter");
					$daterange = FS::$secMgr->checkAndSecurisePostData("daterange");
					if(!$filter || !$daterange || !FS::$secMgr->isNumeric($daterange) || $daterange < 1) {
						echo FS::$iMgr->printError($this->loc->s("bad-datas"));
						return;
					}
					echo $this->showHistory($filter,$daterange);
					return;
				// Add/Edit DHCP server
				case 5:
					$saddr = FS::$secMgr->checkAndSecurisePostData("addr");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
                                        $slogin = FS::$secMgr->checkAndSecurisePostData("sshuser");
                                        $spwd = FS::$secMgr->checkAndSecurisePostData("sshpwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("sshpwd2");
                                        $dhcpdpath = FS::$secMgr->checkAndSecurisePostData("dhcpdpath");
                                        $leasepath = FS::$secMgr->checkAndSecurisePostData("leasepath");
					$reservconfpath = FS::$secMgr->checkAndSecurisePostData("reservconfpath");
					$subnetconfpath = FS::$secMgr->checkAndSecurisePostData("subnetconfpath");
                                        if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
                                                $dhcpdpath == NULL || $dhcpdpath == "" || !FS::$secMgr->isPath($dhcpdpath) ||
                                                $leasepath == NULL || $leasepath == "" || !FS::$secMgr->isPath($leasepath) ||
						$reservconfpath && ($reservconfpath == "" || !FS::$secMgr->isPath($reservconfpath)) ||
						$subnetconfpath && ($subnetconfpath == "" || !FS::$secMgr->isPath($subnetconfpath)) ||
						$edit && $edit != 1
                                        ) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are invalid or wrong for add server");
						FS::$iMgr->ajaxEcho("err-bad-datas");
                                                return;
                                        }
					if($spwd != $spwd2) {
						FS::$iMgr->ajaxEcho("err-pwd-not-match");
                                                return;
                                        }

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr ='".$saddr."'");
					if($edit) {
						if(!$exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to edit server '".$saddr."': not exists");
							FS::$iMgr->ajaxEcho("err-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to add server '".$saddr."': already exists");
							FS::$iMgr->ajaxEcho("err-already-exists");
							return;
						}

					}
					$conn = ssh2_connect($saddr,22);
                                        if(!$conn) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"SSH Connection failed for '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-ssh-conn-failed");
                                                return;
                                        }
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"SSH Auth failed for '".$slogin."'@'".$saddr."'");
						FS::$iMgr->ajaxEcho("err-ssh-auth-failed");
                                                return;
                                        }
					/*
					* We try to read files
					*/
					// dhcpd.conf
					$stream = ssh2_exec($conn,"if [ -r ".$dhcpdpath." ]; then; echo 0; else; echo 1; fi;");
					$cmdret = "";
		        	        stream_set_blocking($stream, true);
	        	        	while ($buf = fread($stream, 4096))
						$cmdret .= $buf;

					if($cmdret != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$dhcpdpath."' on '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-unable-read")." '".$dhcpdpath."'";
                                                return;
					}

					// dhcpd.leases
					$stream = ssh2_exec($conn,"if [ -r ".$leasepath." ]; then; echo 0; else; echo 1; fi;");
                                        $cmdret = "";
                                        stream_set_blocking($stream, true);
                                        while ($buf = fread($stream, 4096))
                                                $cmdret .= $buf;

                                        if($cmdret != 0) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$leasepath."' on '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-unable-read")." '".$leasepath."'";
                                                return;
                                        }

					if($reservconfpath && strlen($reservconfpath) > 0) {
						$stream = ssh2_exec($conn,"if [ -r ".$reservconfpath." -a -w ".$reservconfpath." ]; then; echo 0; else; echo 1; fi;");
        	                                $cmdret = "";
                	                        stream_set_blocking($stream, true);
                        	                while ($buf = fread($stream, 4096))
                                	                $cmdret .= $buf;

                                        	if($cmdret != 0) {
                                                	FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$reservconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEcho("err-unable-read")." '".$reservconfpath."'";
        	                                        return;
                	                        }
					}

					if($subnetconfpath && strlen($subnetconfpath) > 0) {
						$stream = ssh2_exec($conn,"if [ -r ".$subnetconfpath." -a -w ".$subnetconfpath." ]; then; echo 0; else; echo 1; fi;");
        	                                $cmdret = "";
                	                        stream_set_blocking($stream, true);
                        	                while ($buf = fread($stream, 4096))
                                	                $cmdret .= $buf;

                                        	if($cmdret != 0) {
                                                	FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$subnetconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEcho("err-unable-read")." '".$subnetconfpath."'";
        	                                        return;
                	                        }
					}

					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers","addr = '".$addr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_servers","addr,sshuser,sshpwd,dhcpdpath,leasespath,reservconfpath,subnetconfpath","'".$saddr."','".$slogin."','".$spwd."','".
						$dhcpdpath."','".$leasepath."','".$reservconfpath."','".$subnetconfpath."'");
					FS::$dbMgr->CommitTr();

					if($edit)
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Edited DHCP server '".$saddr."' (login: '".$slogin."')");
					else
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Added DHCP server '".$saddr."' (login: '".$slogin."')");
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				// Delete DHCP Server
				case 6:
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if(!$addr) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"No DHCP server specified to remove");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr = '".$addr."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Unknown DHCP server specified to remove");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_history","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_cache","server = '".$addr."'");
					// Later
					// FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers","addr = '".$addr."'");
					FS::$dbMgr->CommitTr();

					FS::$iMgr->ajaxEcho("Done");
					FS::$iMgr->redir("mod=".$this->mid,true);
                                        return;
			}
		}
	};
?>
