<?php
	/*
	* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
				$output .= "<h1>".$this->loc->s("title-ip-supervision")."</h1>";
				$netfound = false;
				$tmpoutput = $this->loc->s("choose-net");
				$tmpoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
				$tmpoutput .= FS::$iMgr->select("f","submit()");
				$query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask","","netid");
				while($data = pg_fetch_array($query)) {
					if(!$netfound) $netfound = true;
					$formoutput .= FS::$iMgr->selElmt($data["netid"]."/".$data["netmask"],$data["netid"],($filter == $data["netid"] ? true : false));
				}
				$tmpoutput .= $formoutput;
				$tmpoutput .= "</select> ";
				$tmpoutput .= FS::$iMgr->submit("","Consulter");
				$tmpoutput .= "</form><br />";
				if(!$netfound)
                                        return $output.= FS::$iMgr->printError($this->loc->s("no-net-found"));

				if($filter && !FS::$secMgr->isIP($filter))
					return $output.FS::$iMgr->printError($this->loc->s("bad-filter"));

				$output .= $tmpoutput;
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&f=".$filter,$this->loc->s("Stats"),$showmodule);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid."&f=".$filter,$this->loc->s("History"),$showmodule);
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
					$netoutput .= "<h3>Réseau : ".$data["netid"]."/".$data["netmask"]."</h3>";
					$netoutput .= "<center><div id=\"".$data["netid"]."\"></div></center>";
					
					$netobj = new FSNetwork();
					$netobj->setNetAddr($data["netid"]);
					$netobj->setNetMask($data["netmask"]);
					
					$swfound = false;

					// Bufferize switch list
					$switchlist = array();

					$query2 = FS::$pgdbMgr->Select("device","ip,name");
					while($data2 = pg_fetch_array($query2))
						$switchlist[$data2["ip"]] = $data2["name"];

					// Initiate network IPs
					for($i=($netobj->getFirstUsableIPLong());$i<=($netobj->getLastUsableIPLong());$i++) {
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
						if(strlen($iparray[ip2long($data2["ip"])]["mac"]) > 0 && strlen($iparray[ip2long($data2["ip"])]["switch"]) == 0) {
							$sw = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'","time_last",2);
							$port = FS::$pgdbMgr->GetOneData("node","port","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'","time_last",2);
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
					
					$netoutput .= "<center><table><tr><th>".$this->loc->s("IP-Addr")."</th><th>".$this->loc->s("Status")."</th>
						<th>".$this->loc->s("MAC-Addr")."</th><th>".$this->loc->s("Hostname")."</th><th>";
					if($swfound)
						$netoutput .= $this->loc->s("Switch")."</th><th>".$this->loc->s("Port")."</th><th>";
					$netoutput .= "Fin du bail</th><th>Serveurs</th></tr>";

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
						// Show switch column only of a switch is here
						if($swfound) {
							$netoutput .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."\">".$value["switch"]."</a>" : "");
							$netoutput .= "</td><td>";
							$netoutput .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."&p=".$value["port"]."\">".$value["port"]."</a>" : "");
							$netoutput .= "</td><td>";
						}
						$netoutput .= $value["ltime"]."</td><td>";
						for($i=0;$i<count($value["servers"]);$i++) {
							if($i > 0) $netoutput .= "<br />";
							$netoutput .= $value["servers"][$i];
						}
						$netoutput .= "</td></tr>";
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
							series: [{ type: 'pie', data: [";
					if($used > 0) $netoutput .= "{ name: '".$this->loc->s("Baux")."', y: ".$used.", color: 'red' },";
					if($reserv > 0) $netoutput .= "{ name: '".$this->loc->s("Reservations")."', y: ".$reserv.", color: 'yellow'},";
					if($fixedip > 0) $netoutput .= "{ name: '".$this->loc->s("Stuck-IP")."', y: ".$fixedip.", color: 'orange'},";
					if($distrib > 0) $netoutput .= "{ name: '".$this->loc->s("Available-s")."', y: ".$distrib.", color: 'cyan'},";
					$netoutput .= "{ name: '".$this->loc->s("Free-s")."', y:".$free.", color: 'green'}]
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
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->hidden("obsdata",$filter);
					$output .= $this->loc->s("intval-days")." ".FS::$iMgr->numInput("ival")."<br />";
					$output .= FS::$iMgr->JSSubmit("search",$this->loc->s("Search"),"return searchobsolete();");
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
	                                $output .= FS::$iMgr->form("index.php?mod=".$this->mid."&f=".$filter."&act=3",array("id" => "monsubnet"));
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->check("enmon",array("check" => $enmon == 1 ? true : false,"label" => $this->loc->s("En-monitor")))."</li><li>";
                                        $output .= FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("warn-line"), "tooltip" => $this->loc->s("%use")))."</li><li>";
					$output .= FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("crit-line"), "tooltip" => $this->loc->s("%use")))."</li><li>";
					$output .= FS::$iMgr->numInput("maxage",($maxage > 0 ? $maxage : 0),array("size" => 7, "length" => 7, "label" => $this->loc->s("max-age"), "tooltip" => $this->loc->s("tooltip-max-age")))."</li><li>";
					$output .= FS::$iMgr->input("contact",$contact,20,40,$this->loc->s("Contact"),$this->loc->s("tooltip-contact"))."</li><li>";
					$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
					$output .= "<script type=\"text/javascript\">$('#monsubnet').submit(function(event) {
        	                                event.preventDefault();
                	                        $.post('index.php?mod=".$this->mid."&at=3&f=".$filter."&act=3', $('#monsubnet').serialize(), function(data) {
                        	                        $('#monsubnetres').html(data);
                                	        });
	                                });</script>";
				}
				else if($showmodule == 4) {
					$output .= "<script type=\"text/javascript\">function historyDateChange() {
						$('#hstcontent').fadeOut();
						$.post('index.php?mod=".$this->mid."&act=4',$('#hstfrm').serialize(), function(data) {
							$('#hstcontent').html(data);
							$('#hstcontent').fadeIn();
							});
						}</script>";

					$output .= "<div id=\"hstcontent\">".$this->showHistory($filter)."</div>";
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=4",array("id" => "hstfrm"));
					$output .= FS::$iMgr->hidden("filter",$filter);
					$date = FS::$pgdbMgr->GetMin("z_eye_dhcp_ip_history","collecteddate");
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

		private function showHistory($filter,$interval = 1) {
			$output = "<h3>".$this->loc->s("title-history-since")." ".$interval." ".$this->loc->s("days")."</h3>";
			$output .= "<div id=\"hstgr\"></div>";
			$results = array();
			$query = FS::$pgdbMgr->Select("z_eye_dhcp_ip_history","count(ip) as ct,distributed,collecteddate","collecteddate > (NOW()- '".$interval." day'::interval) and netid = '".$filter."' GROUP BY distributed,collecteddate ORDER BY collecteddate");
			while($data = pg_fetch_array($query)) {
				if(!isset($results[$data["collecteddate"]])) $results[$data["collecteddate"]] = array();
				switch($data["distributed"]) {
					case 1: break;
					case 2: $results[$data["collecteddate"]]["baux"] = $data["ct"]; break;
					case 3: $results[$data["collecteddate"]]["reserv"] = $data["ct"]; break;
					case 4: $results[$data["collecteddate"]]["avail"] = $data["ct"]; break;
				}
			}
			$netobj = new FSNetwork();
                        $netobj->setNetAddr($filter);
                        $netobj->setNetMask(FS::$pgdbMgr->GetOneData("z_eye_dhcp_subnet_cache","netmask","netid ='".$filter."'"));

			// JS Table
			$labels = $baux = $reserv = $avail = $free = $total = "";
			// To show or not if no data
			$reservshow = $bauxshow = $availshow = false;
			// Show only modifications
			$lastvalues = array();
			end($results);
			$lastres = key($results);
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
					}
					// Save this occur
					$lastvalues = array("baux" => $bauxval, "reserv" => $reservval, "avail" => $availval);
				}
			}
			$output .= "<script type=\"text/javascript\">$(function(){ var hstgr;
                        	$(document).ready(function() { hstgr = new Highcharts.Chart({
                                	chart: { renderTo: 'hstgr', type: 'line' },
                                        title: { text: '' },
					tooltip: { crosshairs: true },
                                        xAxis: { categories: [".$labels."], gridLineWidth: 1, tickInterval: ".round(count($results)/10)." },
                                        yAxis: { title: { text: 'Nombre d\'adresses' } },
                                        legend: { layout: 'vertical', align: 'right', verticalAlign: 'top',
                                        	x: -10, y: 100 },
                                        series: [ { name: '".addslashes($this->loc->s("Used")."s")."',
						data: [".$total."], color: 'black' },
						{ name: '".addslashes($this->loc->s("Free")."s")."',
                                                data: [".$free."], color: 'green' },";
					if($bauxshow) $output .= "{ name: '".addslashes($this->loc->s("Baux"))."',
						data: [".$baux."], color: 'red' },";
					if($reservshow) $output .= "{ name: '".addslashes($this->loc->s("Reservations"))."',
						data: [".$reserv."], color: 'yellow' },";
					if($availshow) $output .= "{ name: '".addslashes($this->loc->s("Available-s"))."',
						data: [".$avail."], color: 'cyan' }";
			$output .= "]});});});</script>";
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
				case 4:
					$filter = FS::$secMgr->checkAndSecurisePostData("filter");
					$daterange = FS::$secMgr->checkAndSecurisePostData("daterange");
					if(!$filter || !$daterange || !FS::$secMgr->isNumeric($daterange) || $daterange < 1) {
						echo FS::$iMgr->printError($this->loc->s("bad-datas"));
						return;
					}
					echo $this->showHistory($filter,$daterange);
			}
		}
	};
?>
