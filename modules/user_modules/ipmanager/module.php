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
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
	class iIPManager extends genModule{
		function iIPManager() { parent::genModule(); }
		public function Load() {
			$output = "";
			$output .= "<div id=\"module_connect\">";
			$output .= $this->showStats();
			$output .= "</div>";
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$netoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<h3>Supervision IP</h3>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
				$output .= FS::$iMgr->addList("f");
				$query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask");
				while($data = pg_fetch_array($query)) {
					$formoutput .= FS::$iMgr->addElementTolist($data["netid"]."/".$data["netmask"],$data["netid"],($filter == $data["netid"] ? true : false));
				}
				$output .= $formoutput;
				$output .= "</select>";
				$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
				$output .= "</form><br />";
				if(!$filter)
					return "Veuillez choisir le réseau IP à monitorer: <br /><br />".$output;

				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."\">Statistiques</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."&sh=2\">Outils avancés</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
			} else {
				if(!$showmodule || $showmodule == 1) {
				$query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask","netid = '".$filter."'");
				while($data = pg_fetch_array($query)) {
					$iparray = array();
					$netoutput .= "<h4>Réseau : ".$data["netid"]."/".$data["netmask"]."</h4>";
					$netoutput .= "<center><div id=\"".$data["netid"]."\"></div></center>";
					$netoutput .= "<center><table><tr><th>Adresse IP</th><th>Statut</th><th>MAC address</th><th>Nom d'hote</th><th>Fin du bail</th></tr>";
					$netobj = new FSNetwork();
					$netobj->setNetAddr($data["netid"]);
					$netobj->setNetMask($data["netmask"]);
					for($i=($netobj->getFirstUsableIPLong());$i<=($netobj->getLastUsableIPLong());$i++) {
						$iparray[$i] = array();
						$iparray[$i]["mac"] = "";
						$iparray[$i]["host"] = "";
						$iparray[$i]["ltime"] = "";
						$iparray[$i]["distrib"] = 0;
					}
					$query2 = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed","netid = '".$data["netid"]."'");
					while($data2 = mysql_fetch_array($query2)) {
						$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
						$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
						$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
						$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
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
								$rstate = "Libre";
								$style = "background-color: #BFFFBF;";
								$free++;
								break;
							case 2:
								$rstate = "Utilis&eacute;";
								$style = "background-color: #FF6A6A;";
								$used++;
								break;
							case 3:
								$rstate = "R&eacute;serv&eacute;";
								$style = "background-color: #FFFF80;";
								$reserv++;
								break;
							default: {
									$rstate = "Libre";
									$style = "background-color: #BFFFBF;";
									$mac = FS::$pgdbMgr->GetOneData("node_ip","mac","ip = '".long2ip($key)."' AND time_last > (current_timestamp - interval '1 hour') AND active = 't'");
									if($mac) {
										$query3 = FS::$pgdbMgr->Select("node","switch,port,time_last","mac = '".$mac."' AND active = 't'");
										if($data3 = pg_fetch_array($query3)) {
											$rstate = "IP fixe";
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
						$netoutput .= "<tr style=\"$style\"><td><a class=\"monoComponent_li_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".long2ip($key)."\">";
						$netoutput .= long2ip($key)."</a>";
						$netoutput .= "</td><td>".$rstate."</td><td>";
						$netoutput .= "<a class=\"monoComponent_li_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$value["mac"]."\">".$value["mac"]."</a></td><td>";
						$netoutput .= $value["host"]."</td><td>";
						$netoutput .= $value["ltime"]."</td></tr>";
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
								[{ name: 'Baux', y: ".$used.", color: 'red' },
								{ name: 'Reservations', y: ".$reserv.", color: 'yellow'},
								{ name: 'Adresses fixes', y: ".$fixedip.", color: 'orange'},
								{ name: 'Libres', y:".$free.", color: 'green'}]
							}]});</script>";
				}
				$output .= $netoutput;
				} else if($showmodule == 2) {
					$output .= "<h4>Recherche de réservations obsolètes</h4>";
					$output .= "<script type=\"text/javascript\">function searchobsolete() {";
					$output .= "$('#obsres').html('".FS::$iMgr->addImage('styles/images/loader.gif')."');";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=2', { ival: document.getElementsByName('ival')[0].value, obsdata: document.getElementsByName('obsdata')[0].value}, function(data) {";
					$output .= "$('#obsres').html(data);";
					$output .= "});return false;}</script>";
					$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->addHidden("obsdata",$filter);
					$output .= "Intervalle (jours) ".FS::$iMgr->addNumericInput("ival")."<br />";
					$output .= FS::$iMgr->addJSSubmit("search","Rechercher","return searchobsolete();");
					$output .= "</form><div id=\"obsres\"></div>";
				}
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) header("Location: index.php?mod".$this->mid."");
					else header("Location: index.php?mod=".$this->mid."&f=".$filtr);
					return;
				case 2:
					$filter = FS::$secMgr->checkAndSecurisePostData("obsdata");
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$filter || !FS::$secMgr->isIP($filter) || !$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						echo FS::$iMgr->printError("Requête invalide !");
						return;
					}

					$found = false;
					$output = "";
					$query = FS::$pgdbMgr->Select("z_eye_dhcp_ip_cache","ip,macaddr,hostname","netid = '".$filter."' AND distributed = 3");
					while($data = mysql_fetch_array($query)) {
						$query2 = FS::$pgdbMgr->Select("node_ip","mac,time_last","ip = '".$data["ip"]."' AND time_last < NOW() - INTERVAL '".$interval." day'","time_last",1);
						while($data2 = pg_fetch_array($query2)) {
							if($data2["mac"] == $data["macaddr"]) {
								$foundrecent = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND active = 't' AND time_last > NOW() - INTERVAL '".$interval." day'","time_last",1);
								if(!$foundrecent) {
									if(!$found) $found = true;
									$output .= $data["ip"]." / <a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data2["mac"]."\">".$data2["mac"]."</a><br />";
								}
							}
						}
					}
					if($found) echo "<h4>Réservations obsolètes trouvées !</h4>".$output;
					else echo FS::$iMgr->printDebug("Aucune réservation obsolète trouvée");
					return;
			}
		}
	};
?>
