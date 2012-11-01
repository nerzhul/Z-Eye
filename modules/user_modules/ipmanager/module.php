<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
	
	class iIPManager extends genModule{
		function iIPManager() { parent::genModule(); $this->loc = new lIPManager(); }
		public function Load() {
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
				$output .= "<h3>".$this->loc->s("title-ip-supervision")."</h3>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
				$output .= FS::$iMgr->addList("f","submit()");
				$query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask","","netid");
				while($data = pg_fetch_array($query)) {
					$formoutput .= FS::$iMgr->addElementTolist($data["netid"]."/".$data["netmask"],$data["netid"],($filter == $data["netid"] ? true : false));
				}
				$output .= $formoutput;
				$output .= "</select> ";
				$output .= FS::$iMgr->submit("","Consulter");
				$output .= "</form><br />";
				if(!$filter || !FS::$secMgr->isIP($filter))
					return $this->loc->s("choose-net").": <br /><br />".$output;

				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&f=".$filter,$this->loc->s("Stats"),$showmodule);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid."&f=".$filter,$this->loc->s("Monitoring"),$showmodule);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&f=".$filter,$this->loc->s("Expert-tools"),$showmodule);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
			} else {
				if(!$showmodule || $showmodule == 1) {
				$query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask","netid = '".$filter."'");
				while($data = pg_fetch_array($query)) {
					$iparray = array();
					$netoutput .= "<h4>Réseau : ".$data["netid"]."/".$data["netmask"]."</h4>";
					$netoutput .= "<center><div id=\"".$data["netid"]."\"></div></center>";
					$netoutput .= "<center><table><tr><th>".$this->loc->s("IP-Addr")."</th><th>".$this->loc->s("Status")."</th>
						<th>".$this->loc->s("MAC-Addr")."</th><th>".$this->loc->s("Hostname")."</th><th>Fin du bail</th><th>Serveurs</th></tr>";
					$netobj = new FSNetwork();
					$netobj->setNetAddr($data["netid"]);
					$netobj->setNetMask($data["netmask"]);
					for($i=($netobj->getFirstUsableIPLong());$i<=($netobj->getLastUsableIPLong());$i++) {
						$iparray[$i] = array();
						$iparray[$i]["mac"] = "";
						$iparray[$i]["host"] = "";
						$iparray[$i]["ltime"] = "";
						$iparray[$i]["distrib"] = 0;
						$iparray[$i]["servers"] = array();
					}
					$query2 = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,server","netid = '".$data["netid"]."'");
					while($data2 = pg_fetch_array($query2)) {
						// If it's reserved on a host don't override status
						if($iparray[ip2long($data2["ip"])]["distrib"] != 3) {
							$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
							$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
							$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
							$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
						}
						// List servers where the data is
						array_push($iparray[ip2long($data2["ip"])]["servers"],$data2["server"]);
					}
					
					$used = 0;
					$reserv = 0;
					$free = 0;
					$fixedip = 0;
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
							default: {
									$rstate = $this->loc->s("Free");
									$style = "background-color: #BFFFBF;";
									$mac = FS::$pgdbMgr->GetOneData("node_ip","mac","ip = '".long2ip($key)."' AND time_last > (current_timestamp - interval '1 hour') AND active = 't'");
									if($mac) {
										$query3 = FS::$pgdbMgr->Select("node","switch,port,time_last","mac = '".$mac."' AND active = 't'");
										if($data3 = pg_fetch_array($query3)) {
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
						$netoutput .= $value["ltime"]."</td><td>";
						for($i=0;$i<count($value["servers"]);$i++) {
							if($i > 0) $netoutput .= "<br />";
							$netoutput .= $value["servers"][$i];
						}
						$netouput .= "</td></tr>";
					}
					$netoutput .= "</table></center><br /><hr>";
					$netoutput .= "<script type=\"text/javascript\">
						var chart = new Highcharts.Chart({
							chart: { renderTo: '".$data["netid"]."', plotBackgroundColor: null, plotBorderWidth: null, plotShadow: false },
							title: { text: '' },
							tooltip: { formatter: function() { return '<b>'+this.point.name+'</b>: '+this.y+' ('+
										Math.round(this.percentage*100)/100+' %)'; } },
							plotOptions: {
								pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: {
										enabled: true,formatter: function() { return '<b>'+this.point.name+'</b>: '+
										this.y+' ('+Math.round(this.percentage*100)/100+' %)'; }
							}}},
							series: [{ type: 'pie', data: 
								[{ name: '".$this->loc->s("Baux")."', y: ".$used.", color: 'red' },
								{ name: '".$this->loc->s("Reservations")."', y: ".$reserv.", color: 'yellow'},
								{ name: '".$this->loc->s("Stuck-IP")."', y: ".$fixedip.", color: 'orange'},
								{ name: '".$this->loc->s("Free")."', y:".$free.", color: 'green'}]
							}]});</script>";
					}
					$output .= $netoutput;
				}
				else if($showmodule == 2) {
					$output .= "<h4>".$this->loc->s("title-search-old")."</h4>";
					$output .= "<script type=\"text/javascript\">function searchobsolete() {";
					$output .= "$('#obsres').html('".FS::$iMgr->img('styles/images/loader.gif')."');";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=2', { ival: document.getElementsByName('ival')[0].value, obsdata: document.getElementsByName('obsdata')[0].value}, function(data) {";
					$output .= "$('#obsres').html(data);";
					$output .= "});return false;}</script>";
					$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->addHidden("obsdata",$filter);
					$output .= $this->loc->s("intval-days")." ".FS::$iMgr->addNumericInput("ival")."<br />";
					$output .= FS::$iMgr->addJSSubmit("search",$this->loc->s("Search"),"return searchobsolete();");
					$output .= "</form><div id=\"obsres\"></div>";
				}
				else if($showmodule == 3) {
					$output .= "<h4>Monitoring</h4>";
					$wlimit = FS::$pgdbMgr->GetOneData("z_eye_dhcp_monitoring","warnuse","subnet = '".$filter."'");
					$climit = FS::$pgdbMgr->GetOneData("z_eye_dhcp_monitoring","crituse","subnet = '".$filter."'");
					$maxage = FS::$pgdbMgr->GetOneData("z_eye_dhcp_monitoring","maxage","subnet = '".$filter."'");
					$enmon = FS::$pgdbMgr->GetOneData("z_eye_dhcp_monitoring","enmon","subnet = '".$filter."'");
					$contact = FS::$pgdbMgr->GetOneData("z_eye_dhcp_monitoring","contact","subnet = '".$filter."'");
					$output .= "<div id=\"monsubnetres\"></div>";
	                                $output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&f=".$filter."&act=3","monsubnet");
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->addCheck("enmon",$enmon == 1 ? true : false,$this->loc->s("En-monitor"))."</li><li>";
                                        $output .= FS::$iMgr->addNumericInput("wlimit",($wlimit > 0 ? $wlimit : 0),3,3,$thsi->loc->s("warn-line"),$this->loc->s("%use"))."</li><li>";
					$output .= FS::$iMgr->addNumericInput("climit",($climit > 0 ? $climit : 0),3,3,$this->loc->s("crit-line"),$this->loc->s("%use"))."</li><li>";
					$output .= FS::$iMgr->addNumericInput("maxage",($maxage > 0 ? $maxage : 0),7,7,$this->loc->s("max-age"),$this->loc->s("tooltip-max-age"))."</li><li>";
					$output .= FS::$iMgr->input("contact",$contact,20,40,$this->loc->s("Contact"),$this->loc->s("tooltip-contact"))."</li><li>";
					$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
					$output .= "<script type=\"text/javascript\">$('#monsubnet').submit(function(event) {
        	                                event.preventDefault();
                	                        $.post('index.php?mod=".$this->mid."&at=3&f=".$filter."&act=3', $('#monsubnet').serialize(), function(data) {
                        	                        $('#monsubnetres').html(data);
                                	        });
	                                });</script>";
				}
				else
					$output .= FS::$iMgr->printError($this->loc->s("no-tab"));
			}
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to filter values");
						header("Location: index.php?mod".$this->mid."");
					}
					else {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User filter by ".$filtr);
						header("Location: index.php?mod=".$this->mid."&f=".$filtr);
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
					$query = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","ip,macaddr,hostname","netid = '".$filter."' AND distributed = 3");
					while($data = pg_fetch_array($query)) {
						$ltime = FS::$pgdbMgr->GetOneData("node","time_last","mac = '".$data["macaddr"]."'","time_last",1,1);
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
						echo "<h4>".$this->loc->s("title-old-record")."</h4>";
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
					if(!$filtr || !FS::$secMgr->isIP($filtr) || !$warn || !FS::$secMgr->isNumeric($warn) || $warn < 0 || $warn > 100|| !$crit || !FS::$secMgr->isNumeric($crit) || $crit < 0 || $crit > 100 ||
						!FS::$secMgr->isNumeric($maxage) || $maxage < 0 || !$contact || !FS::$secMgr->isMail($contact)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to monitor subnet");
						echo FS::$iMgr->printError($this->loc->s("err-miss-data"));
						return;
					}
					$exist = FS::$pgdbMgr->GetOneData("z_eye_dhcp_subnet_cache","netid","netid = '".$filtr."'");
					if(!$exist) {
						echo FS::$iMgr->printError($this->loc->s("err-bad-subnet"));
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"User try to monitor inexistant subnet '".$filtr."'");
						return;
					}

					FS::$pgdbMgr->Delete("z_eye_dhcp_monitoring","subnet = '".$filtr."'");
					if($enmon == "on")
						FS::$pgdbMgr->Insert("z_eye_dhcp_monitoring","subnet,warnuse,crituse,contact,enmon,maxage","'".$filtr."','".$warn."','".$crit."','".$contact."','1','".$maxage."'");
					echo FS::$iMgr->printDebug($this->loc->s("modif-record"));
					
					FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User ".($enmon == "on" ? "enable" : "disable")." monitoring for subnet '".$filtr."'");
					return;
			}
		}
	};
?>
