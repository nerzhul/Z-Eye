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
	require_once(dirname(__FILE__)."/cisco.func.php");
	
	class iSwitchMgmt extends genModule{
		function iSwitchMgmt() { parent::genModule(); }
		public function Load() {
			$output = "";
			if(!FS::isAjaxCall())
				$output .= "<h3>Management des Equipements réseau</h3>";
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$port = FS::$secMgr->checkAndSecuriseGetData("p");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			if($port != NULL && $device != NULL)
				$output .= $this->showPortInfos();
			else if($device != NULL)
				$output .= $this->showDeviceInfos();
			else
				$output .= $this->showDeviceList();

			return $output;
		}

		private function showPortInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
		        $port = FS::$secMgr->checkAndSecuriseGetData("p");
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			switch($err) {
				case 1:	$output .= FS::$iMgr->printError("Echec de la modification des données sur le switch !"); break;
				case 2: $output .= FS::$iMgr->printError("Données incorectes ou invalides"); break;
				default: break;
			}
			if(!FS::isAjaxCall()) {
				$output .= "<h4>".$port." sur ".$device."</h4>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li".((!$sh || $sh == 1) ? " class=\"ui-tabs-selected ui-state-active\"": "")."><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."\">Configuration</a>";
				$output .= "<li".($sh == 2 ? " class=\"ui-tabs-selected ui-state-active\"": "")."><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=2\">Statistiques de débit</a>";
				$output .= "<li".($sh == 3 ? " class=\"ui-tabs-selected ui-state-active\"": "")."><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=3\">Monitoring</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			} else {
				// Get Port ID
				$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
				$portid = getPortId($device,$port);
				// Port modification
				if(!$sh || $sh == 1) {
					// # todo, place JS before output
					$output .= "<script type=\"text/javascript\">function arangeform() {";
					$output .= "if(document.getElementsByName('trmode')[0].value == 1) {";
					$output .= "$('#vltr').show();
						$('#mabtr').hide();
						$('#vllabel').html('Vlan Natif');
					} else if(document.getElementsByName('trmode')[0].value == 2) {
						$('#vltr').hide();
						$('#mabtr').hide();
						$('#vllabel').html('Vlan');
					} else if(document.getElementsByName('trmode')[0].value == 3) {
						$('#vltr').hide();
						$('#vllabel').html('Vlan Fail');
						$('#mabtr').show();
					}";
					$output .= "};";
					$output .= "function showwait() {";
					$output .= "$('#subpop').html('Modification en cours...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
					$output .= "$('#pop').show();";
					$output .= "};";
					$output .= "</script>";
					$query = FS::$pgdbMgr->Select("device_port","name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."' AND port ='".$port."'");
					if($data = pg_fetch_array($query)) {
						$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=9");
						$output .= FS::$iMgr->addHidden("portid",$portid);
						$output .= FS::$iMgr->addHidden("sw",$device);
						$output .= FS::$iMgr->addHidden("port",$port);
						$output .= "<table><tr><th>Champ</th><th>Valeur</th></tr>";
						$output .= "<tr><td>Description</td><td>".FS::$iMgr->input("desc",$data["name"])."</td></tr>";
						$piece = FS::$pgdbMgr->GetOneData("z_eye_switch_port_prises","prise","ip = '".$dip."' AND port = '".$port."'");
						$output .= "<tr><td>Prise</td><td>".FS::$iMgr->input("prise",$piece)."</td></tr>";
						$output .= "<tr><td>Adresse MAC</td><td>".$data["mac"]."</td></tr>";
						$output .= "<tr><td>Etat / Duplex / Vitesse</td><td>";
						if($data["up_admin"] == "down")
								$output .= "<span style=\"color: red;\">Eteint</span>";
						else if($data["up_admin"] == "up" && $data["up"] == "down")
								$output .= "<span style=\"color: orange;\">Inactif</span>";
						else if($data["up"] == "up")
								$output .= "<span style=\"color: black;\">Actif</span>";
						else
								$output .= "unk";
						$output .= " / ".($data["duplex"] == "" ? "[NA]" : $data["duplex"])." / ".$data["speed"]."</td></tr>";
						$output .= "<tr><td>Eteindre</td><td>".FS::$iMgr->addCheck("shut",($data["up_admin"] == "down" ? true : false))."</td></tr>";
						$output .= "<tr><td>Vitesse Administrative</td><td>";
                                                $sp = getPortSpeedWithPID($device,$portid);
						if($sp > 0) {
							$output .= FS::$iMgr->addList("speed");
							$output .= FS::$iMgr->addElementToList("Auto",1,$sp == 1 ? true : false);
							if(preg_match("#Ethernet#",$port)) {
								$output .= FS::$iMgr->addElementToList("10 Mbits",10000000,$sp == 10000000 ? true : false);
								if(preg_match("#FastEthernet#",$port))
									$output .= FS::$iMgr->addElementToList("100 Mbits",100000000,$sp == 100000000 ? true : false);
								if(preg_match("#GigabitEthernet#",$port)) {
									$output .= FS::$iMgr->addElementToList("100 Mbits",100000000,$sp == 100000000 ? true : false);
									$output .= FS::$iMgr->addElementToList("1 Gbit",1000000000,$sp == 1000000000 ? true : false);
									if(preg_match("#TenGigabitEthernet#",$port))
										$output .= FS::$iMgr->addElementToList("10 Gbits",10,$sp == 10 ? true : false);
								}
							}
							$output .= "</select>";
						}
						else
							$output .= "Non disponible";
						$output .= "</td></tr>";
						$output .= "<tr><td>Duplex Administratif</td><td>";
						$dup = getPortDuplexWithPID($device,$portid);
						/*if($dup > 0 && $dup < 5) {
							$output .= FS::$iMgr->addList("duplex");
							$output .= FS::$iMgr->addElementToList("Auto",4,$dup == 1 ? true : false);
							$output .= FS::$iMgr->addElementToList("Half",1,$dup == 2 ? true : false);
							$output .= FS::$iMgr->addElementToList("Full",2,$dup == 3 ? true : false);
							$output .= "</select>";
						}
						else*/
							$output .= "Non disponible";
						$output .= "</td></tr>";
						$output .= "<tr><td>Switchport Mode</td><td>";
						$trmode = FS::$snmpMgr->get($dip,"1.3.6.1.4.1.9.9.46.1.6.1.1.13.".$portid);
						$trmode = preg_split("# #",$trmode);
						$trmode = $trmode[1];
						if(getSwitchportMABState($device,$portid) == 1)
                                                        $trmode = 3;
						$output .= FS::$iMgr->addList("trmode","arangeform()");
						$output .= FS::$iMgr->addElementToList("Access",2,$trmode == 2 ? true : false);
						$output .= FS::$iMgr->addElementToList("Trunk",1,$trmode == 1 ? true : false);
						$output .= FS::$iMgr->addElementToList("802.1X - MAB",3,$trmode == 3 ? true : false);
						$output .= "</select>";
						$output .= "<tr><td id=\"vllabel\">";
						$portoptlabel = "";
						$nvlan = 1;
						$vllist = array();
						switch($trmode) {
							case 1:
								$output .= "VLAN Natif";
								$portoptlabel = "Vlans Encapsulés";
								$nvlan = getSwitchTrunkNativeVlanWithPID($device,$portid);
								$vllist = getSwitchportTrunkVlansWithPid($device,$portid);
								break;
							case 2:
								$output .= "VLAN";
								$nvlan = getSwitchAccessVLANWithPID($device,$portid);
								break;
							case 3:
								$output .= "VLAN Fail";
								$portoptlabel = "Options MAB";
								$nvlan = getSwitchportAuthNoRespVLAN($device,$portid);
								break;
						}
						$output .= "</td><td id=\"vln\">";

						$vlanlist = "";
						for($i=0;$i<count($vllist);$i++)
							$vlanlist .= $vllist[$i].",";
						$vlanlist = substr($vlanlist,0,strlen($vlanlist)-1);
						$voicevlanoutput = FS::$iMgr->addElementToList("Aucun",4096);
						$voicevlan = getSwitchportVoiceVlanWithPID($device,$portid);
						$output .= FS::$iMgr->addList("nvlan","");
						$query = FS::$pgdbMgr->Select("device_vlan","vlan,description,creation","ip = '".$dip."'","vlan");
	                                        while($data = pg_fetch_array($query)) {
							$output .= FS::$iMgr->addElementToList($data["vlan"]." - ".$data["description"],$data["vlan"],$nvlan == $data["vlan"] ? true : false);
							$voicevlanoutput .= FS::$iMgr->addElementToList($data["vlan"]." - ".$data["description"],$data["vlan"],$voicevlan == $data["vlan"] ? true : false);
                                	        }
						$output .= "</select></td></tr>";
						$output .= "<tr id=\"vltr\" ".($trmode != 1 ? "style=\"display:none;\"" : "")."><td>Vlans encapsulés</td><td>";
						$output .= FS::$iMgr->textarea("vllist",$vlanlist,250,100);
						$output .= "</td></tr>";

						$output .= "<tr id=\"mabtr\" ".($trmode != 3 ? "style=\"display:none;\"" : "")."><td>Vlans encapsulés</td><td>";
						$mabeap = getSwitchportMABType($device,$portid);
						$dot1xhostmode = getSwitchportAuthHostMode($device,$portid);
						$output .= FS::$iMgr->addCheck("mabeap",$mabeap == 2 ? true : false)." EAP<br />";
						$output .= "Dot1x HostMode ".FS::$iMgr->addList("dot1xhostmode","");
						$output .= FS::$iMgr->addElementToList("Single Host",1,$dot1xhostmode == 1 ? true : false);
						$output .= FS::$iMgr->addElementToList("Multi Host",2,$dot1xhostmode == 2 ? true : false);
						$output .= FS::$iMgr->addElementToList("Multi Auth",3,$dot1xhostmode == 3 ? true : false);
						$output .= FS::$iMgr->addElementToList("Multi Domain",4,$dot1xhostmode == 4 ? true : false);
						$output .= "</select>";

						$output .= "</td></tr><tr><td>Vlan Voix</td><td>";
						$output .= FS::$iMgr->addList("voicevlan","");
						$output .= $voicevlanoutput;
						$output .= "</select></td></tr>";
						$output .= "<tr><td>Sauver ?</td><td>".FS::$iMgr->addCheck("wr")."</td></tr>";
						$output .= "</table>";
						$output .= "<center><br />".FS::$iMgr->addJSSubmit("Enregistrer","Enregistrer","showwait();")."</center>";
						$output .= "</form>";
					}
					else
						$output .= FS::$iMgr->printError("Les données demandées n'existent pas !");
				}
				// Port Stats
				else if($sh == 2) {
					$file = file(dirname(__FILE__)."/../../../datas/rrd/".$dip."_".$portid.".html");
					if($file) {
						$filebuffer = "";
						$stopbuffer = 0;
						for($i=0;$i<count($file);$i++) {
							$file[$i] = preg_replace("#src=\"(.*)\"#","src=\"datas/rrd/$1\"",$file[$i]);
							if(preg_match("#<head>#",$file[$i]) || preg_match("#<div id=\"footer#",$file[$i]) || 
								 preg_match("#<div id=\"legend#",$file[$i]))
															$stopbuffer = 1;
							else if($stopbuffer == 1 && (preg_match("#</head>#",$file[$i]) || preg_match("#</div>#",$file[$i])))
								$stopbuffer = 0;
							else if($stopbuffer == 0 && !preg_match("#<title>#",$file[$i]) && !preg_match("#<meta#",$file[$i]) 
								&& !preg_match("#<h1>(.*)</h1>#",$file[$i]) && !preg_match("#<body#",$file[$i]) &&
								!preg_match("#<html#",$file[$i]) && !preg_match("#<!--#",$file[$i]))
								$filebuffer .= $file[$i];
						}
						$output .= "<br />".$filebuffer."<br /><center><span style=\"font-size: 9px;\">Generated with MRTG</span></center>";
					}
					else
						$output .= FS::$iMgr->printError("Aucun débit réseau collecté pour ce port !");
				}
				// Monitoring
				else if($sh == 3) {
					$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=16");
					$output .= FS::$iMgr->addHidden("device",$device).FS::$iMgr->addHidden("port",$port);
					$climit = FS::$pgdbMgr->GetOneData("z_eye_port_monitor","climit","device = '".$device."' AND port = '".$port."'");
					$wlimit = FS::$pgdbMgr->GetOneData("z_eye_port_monitor","wlimit","device = '".$device."' AND port = '".$port."'");
					$desc = FS::$pgdbMgr->GetOneData("z_eye_port_monitor","description","device = '".$device."' AND port = '".$port."'");
					$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->addCheck("enmon",(($climit > 0 || $wlimit) > 0 ? true : false),"Activer le monitoring")."</li><li>";
					$output .= FS::$iMgr->input("desc",$desc,20,200,"Libellé")."</li><li>";
					$output .= FS::$iMgr->addNumericInput("wlimit",($wlimit > 0 ? $wlimit : 0),10,10,"Seuil avertissement (Mo)")."</li><li>";
					$output .= FS::$iMgr->addNumericInput("climit",($climit > 0 ? $climit : 0),10,10,"Seuil critique (Mo)")."</li><li>";
					$output .= FS::$iMgr->submit("","Enregister")."</li>";
					$output .= "</ul>";
					$output .= "</form>";
				}
			}
			return $output;
		}

		protected function showDeviceInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			$od = FS::$secMgr->checkAndSecuriseGetData("od");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			if($od == NULL) $od = "port";
			else if($od == "desc" || $od == "name") $od = "name";
			else if($od != "vlan" && $od != "prise" && $od != "port") $od = "port";

			$output = "";
			if(!FS::isAjaxCall()) {
				FS::$iMgr->showReturnMenu(true);
				$output = "<h4>Equipement ";
	
				$output .= $device." (";
				$output .= $dip;
	
				$dloc = FS::$pgdbMgr->GetOneData("device","location","name = '".$device."'");
				if($dloc != NULL)
				$output .= " - ".$dloc;
				$output .= ")</h4>";
				
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device.($od ? "&od=".$od : "").($filter ? "&fltr=".$filter : "")."\">Liste des ports</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&sh=5\">Liste des VLANs</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&sh=3\">Vue de façade</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&sh=1\">Modules internes</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&sh=2\">Détails</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&sh=4\">Outils avancés</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			} else {
				if($dip == NULL) {
					$output .= FS::$iMgr->printError("Equipement inexistant !");
					return $output;
				}
	
				if($showmodule == 1) {
					$query = FS::$pgdbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
					$found = 0;
					$devmod = array();
					while($data = pg_fetch_array($query)) {
						if($found == 0) $found = 1;
						if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
						$idx = count($devmod[$data["parent"]]);
						$devmod[$data["parent"]][$idx] = array();
						$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
						$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
						$devmod[$data["parent"]][$idx]["name"] = $data["name"];
						$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
						$devmod[$data["parent"]][$idx]["type"] = $data["type"];
						$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
						$devmod[$data["parent"]][$idx]["model"] = $data["model"];
						$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
						$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
					}
					if($found == 1) {
						$output .= "<h4>Modules internes</h4>";
						$output .= $this->showDeviceModules($devmod,1);
					}
	
					return $output;
				}
				else if($showmodule == 2) {
					$query = FS::$pgdbMgr->Select("device","*","name ='".$device."'");
					if($data = pg_fetch_array($query)) {
						$output .= "<h4>Détails de l'équipement</h4>";
						$output .= "<table class=\"standardTable\">";
						$output .= "<tr><td>Nom</td><td>".$data["name"]."</td></tr>";
						$output .= "<tr><td>Lieu / contact</td><td>".$data["location"]." / ".$data["contact"]."</td></tr>";
						$output .= "<tr><td>Modèle / # de série</td><td>".$data["model"]." / ".$data["serial"]."</td></tr>";
						$output .= "<tr><td>OS / Version</td><td>".$data["os"]." / ".$data["os_ver"]."</td></tr>";
						$output .= "<tr><td>Description</td><td>".$data["description"]."</td></tr>";
						$output .= "<tr><td>Uptime</td><td>".$data["uptime"]."</td></tr>";
						$found = 0;
						$tmpoutput = "<tr><td>Energie</td><td>";
	
						$query2 = FS::$pgdbMgr->Select("device_power","module,power,status","ip = '".$dip."'");
						while($data2 = pg_fetch_array($query2)) {
							$found = 1;
							$query3 = FS::$pgdbMgr->Select("device_port_power","module,class","module = '".$data2["module"]."' AND ip = '".$dip."'");
							$pwcount = 0;
							while($data3 = pg_fetch_array($query3)) {
								if($data3["class"] == "class2") $pwcount += 7;
								else if($data3["class"] == "class3") $pwcount += 15;
							}
							$tmpoutput .= "Module ".$data2["module"]." : ".$pwcount." / ".$data2["power"]." Watts (statut: ";
							$tmpoutput .= ($data2["status"] == "on" ? "<span style=\"color: green;\">".$data2["status"]."</span>" : $data2["status"]);
							$tmpoutput .= ")<br />";
						}
	
						$tmpoutput .= "</td></tr>";
						if($found == 1) $output .= $tmpoutput;
						$output .= "<tr><td>Adresse IP</td><td>".$data["ip"]."</td></tr>";
						$iswif = (preg_match("#AIR#",$data["model"]) ? true : false);
						if($iswif == false) {
							$output .= "<tr><td>Adresse MAC</td><td>".$data["mac"]."</td></tr>";
							$output .= "<tr><td>Domaine VTP</td><td>".$data["vtp_domain"]."</td></tr>";
						}
						$output .= "</table>";
						return $output;
					}
				}
				else if($showmodule == 3) {
					$query = FS::$pgdbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
					$found = 0;
					$devmod = array();
					while($data = pg_fetch_array($query)) {
						if($found == 0) $found = 1;
						if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
						$idx = count($devmod[$data["parent"]]);
						$devmod[$data["parent"]][$idx] = array();
						$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
						$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
						$devmod[$data["parent"]][$idx]["name"] = $data["name"];
						$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
						$devmod[$data["parent"]][$idx]["type"] = $data["type"];
						$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
						$devmod[$data["parent"]][$idx]["model"] = $data["model"];
						$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
						$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
					}
					if($found == 1) {
						$output .= "<h4>Vue de façade</h4>";
						$output .= "<script>
							// 3750 48 ports	
							var c3750p48x = [59,71,87,98,115,126,143,154,171,182,199,210,227,238,254,266,299,310,326,338,355,366,382,394,
							411,422,438,450,466,478,494,506,539,550,567,578,595,606,623,635,651,663,679,690,707,718,735,746];
							var c3750p48y = 38;
							var c3750g48 = [[815,58],[866,58],[815,90],[866,90]];
							// poe
							var c3750poe48x = [61,61,89,89,117,117,144,144,172,172,200,200,229,229,254,254,300,300,327,327,356,356,383,383,
													412,412,439,439,467,467,495,495,540,540,568,568,596,596,624,624,652,652,680,680,708,708,736,736];
													var c3750impp48y = 66;
													var c3750pp48y = 94;
							// 3750 24 ports
							var c3750p24x = [355,366,383,394,411,422,439,450,467,478,495,506,596,607,624,635,652,663,680,691,708,719,736,747];
							var c3750p24y = 36;
							var c3750g24 = [[816,89],[867,89]];
							// poe
							var c3750poe24x = [357,357,385,385,413,413,441,441,470,470,498,498,597,597,625,625,652,652,681,681,710,710,738,738];
													var c3750impp24y = 65;
													var c3750pp24y = 94;
							// 2960 - 24 ports
							var c2960p24x = [411,424,440,451,467,478,495,507,523,536,552,564,593,605,621,633,649,660,676,687,704,717,732,744];
							var c2960p24y = 14;
							var c2960g24 = [[778,85],[808,85],[834,85],[865,85]];
							// poe
													var c2960poe24x = [357,357,385,385,413,413,441,441,470,470,498,498,597,597,625,625,652,652,681,681,710,710,738,738];
													var c2960impp24y = 65;
													var c2960pp24y = 94;
		
							function drawContext(obj,type,ptab,gptab,poetab) {
								var canvas = document.getElementById(obj);
								var context = canvas.getContext(\"2d\");
								context.fillStyle = \"rgba(0,118,176,0)\";
								context.fillRect(0, 0, context.canvas.width, context.canvas.height);
								context.translate(context.canvas.width/2, context.canvas.height/2);
								context.translate(-context.canvas.width/2, -context.canvas.height/2);
								context.beginPath();
								context.moveTo(-2,-2);
								context.lineTo(892,-2);
								context.lineTo(892,119);
								context.lineTo(-2,119);
								context.lineTo(-2,-2);	
								context.closePath(); // complete custom shape
		
								context.moveTo(0,0);
								var img = new Image();
								img.onload = function() {
									context.drawImage(img, 0,0,892,119);
									var normportX = null; var normportY = null;
									var trunkport = null; var icsize = null;
									var poeX = null; var poePY = null; var poeIMPY = null;
									switch(type) {
										case 1: normportX = c3750p48x; normportY = c3750p48y; trunkport = c3750g48; icsize = 7; 
											poeX = c3750poe48x; poePY = c3750pp48y; poeIMPY = c3750impp48y; break;
										case 2: normportX = c3750p24x; normportY = c3750p24y; trunkport = c3750g24; icsize = 7; 
											poeX = c3750poe24x; poePY = c3750pp24y; poeIMPY = c3750impp24y; break;
										case 3: normportX = c2960p24x; normportY = c2960p24y; trunkport = c2960g24; icsize = 6; 
											poeX = c2960poe24x; poePY = c2960pp24y; poeIMPY = c2960impp24y; break;
										case 4: break;
									}
									for(i=0;i<normportX.length;i++) {
										if(ptab[i] == 0)
											context.fillStyle = \"rgba(200, 0, 0, 0.5)\";
										else if(ptab[i] == 1)
											context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
										else if(ptab[i] == 2)
											context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
										else
											context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
										context.fillRect(normportX[i], normportY, icsize, icsize);
										context.fillStyle = \"rgba(200, 200, 0, 0.6)\";
																			if(poetab[i] == 1)
																					context.fillText(\"7.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));
																			else if(poetab[i] == 2)
																					context.fillText(\"15.0\",poeX[i]-4, (i%2 == 0 ? poeIMPY : poePY));
									}
									for(i=0;i<trunkport.length;i++) {
											if(gptab[i] == 0)
													context.fillStyle = \"rgba(255, 0, 0, 0.6)\";
											else if(gptab[i] == 1)
													context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
											else if(gptab[i] == 2)
													context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
											else
													context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
											context.fillRect(trunkport[i][0], trunkport[i][1], icsize, icsize);
									}
								}
								switch(type) {
									case 1:	img.src = '/uploads/WS-C3750-48PS-S_front.jpg'; break;
									case 2:	img.src = '/uploads/WS-C3750-24PS-S_front.jpg'; break;
									case 3: img.src = '/uploads/2960-24.jpg'; break;
									case 4: img.src = '/uploads/2960-48.jpg'; break;
								}
							}
						</script>";
						$swlist = $this->getDeviceSwitches($devmod,1);
						$swlist = preg_split("#\/#",$swlist);
						for($i=count($swlist)-1;$i>=0;$i--) {
							switch($swlist[$i]) {
								case "WS-C3750-48P": case "WS-C3750-48TS": case "WS-C3750-48PS": case "WS-C3750G-48TS": case "WS-C3750-48PS": { // 100 Mbits switches
									$poearr = array();
									// POE States
									$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
									while($data = pg_fetch_array($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}
	
									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=1;$j<=count($arr_res);$j++) {
										$output .= $arr_res[$j];
										if($j < count($arr_res)) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=1;$j<=count($arr_res);$j++) {
										$output .= $arr_res[$j];
										if($j < count($arr_res)) $output .= ",";
									}
									$output .= "]; var poetab = [";
									for($j=1;$j<=count($poearr);$j++) {
										$output .= $poearr[$j];
										if($j < count($poearr)) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',1,ptab,gptab,poetab);</script>";
									break;
								}
								case "WS-C3750-24TS": case "WS-C3750G-24TS": case "WS-C3750G-24WS": case "WS-C3750G-24T": case "WS-C3750-FS":
								case "WS-C3750-24PS": case "WS-C3750-24P": // 100 Mbits switches
									$poearr = array();
									// POE States
									$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
									while($data = pg_fetch_array($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}
	
									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=1;$j<=count($arr_res);$j++) {
										$output .= $arr_res[$j];
										if($j < count($arr_res)) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=1;$j<=count($arr_res);$j++) {
										$output .= $arr_res[$j];
										if($j < count($arr_res)) $output .= ",";
									}
																   $output .= "]; var powport = [";
									for($j=1;$j<=count($poearr);$j++) {
										$output .= $poearr[$j];
										if($j < count($poearr)) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',2,ptab,gptab,powport);</script>";
									break;
								case "WS-C2960S-24TS-L": // Gbit switches
									$poearr = array();
									$portlist = "";
									for($j=1;$j<25;$j++) {
										$portlist .= "'GigabitEthernet".($i+1)."/0/".$j."'";
										if($j < 24)
											$portlist .= ",";
									}
									// POE States
									$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port IN (".$portlist.")");
									while($data = pg_fetch_array($query)) {
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										switch($data["class"]) {
											case "class0": $poearr[$pid] = 0; break;
											case "class2": $poearr[$pid] = 1; break;
											case "class3": $poearr[$pid] = 2; break;
										}
									}
	
									$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN (".$portlist.")","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=1;$j<=count($arr_res);$j++) {
										$output .= $arr_res[$j];
										if($j < count($arr_res)) $output .= ",";
									}
									$output .= "]; var gptab = [";
									$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN ('GigabitEthernet".($i+1)."/0/25', 'GigabitEthernet".($i+1)."/0/26',
											'GigabitEthernet".($i+1)."/0/27','GigabitEthernet".($i+1)."/0/28')","port");
									$arr_res = array();
									while($data = pg_fetch_array($query)) {
										if(preg_match("#unrouted#",$data["port"]))
											continue;
										$pid = preg_split("#\/#",$data["port"]);
										$pid = $pid[2];
										if($data["up_admin"] == "down")
											$arr_res[$pid] = 0;
										else if($data["up_admin"] == "up" && $data["up"] == "down")
											$arr_res[$pid] = 1;
										else if($data["up"] == "up")
											$arr_res[$pid] = 2;
										else
											$arr_res[$pid] = 3;
									}
	
									uksort($arr_res,"strnatcasecmp");
									for($j=25;$j<=(25+count($arr_res));$j++) {
										$output .= $arr_res[$j];
										if($j < (24+count($arr_res))) $output .= ",";
									}
																   $output .= "]; var powport = [";
									for($j=1;$j<=count($poearr);$j++) {
										$output .= $poearr[$j];
										if($j < count($poearr)) $output .= ",";
									}
									$output .= "]; drawContext('canvas_".($i+1)."',3,ptab,gptab,powport);</script>";
									break;
								default: break;
							}
						}
					}
					return $output;			
				}
				else if($showmodule == 4) {
					$err = FS::$secMgr->checkAndSecuriseGetData("err");
					$output .= "<script type=\"text/javascript\">";
					$output .= "function searchports() {";
					$output .= "$('#subpop').html('Recherche des ports concernés en cours...<br /><br /><br />');";
					$output .= "$('#pop').show();
					var ovlid = document.getElementsByName('oldvl')[0].value;";
					$output .= "$.get('index.php?mod=".$this->mid."&at=3&act=10&d=".$device."&vlan='+ovlid, function(data) {
						$('#pop').hide();
						$('#vlplist').html(data); });";
					$output .= "return false;";
					$output .= "};";
					$output .= "function checkTagForm() {
						if($('#vlplist') == null || $('#vlplist').html().length < 1) {
							alert('Vous devez vérifier si des ports sont concernés !');
							return false;
						}
						if(document.getElementsByName('accept')[0].checked == false) {
							alert('Vous devez confirmer la modification !');
							return false;
						}
						return true;
					};";
					$output .= "</script>";
					$output .= "<h4>Retaguer un VLAN</h4>";
					if($err && $err == 1) $output .= FS::$iMgr->printError("L'une des valeurs entrées est invalide !");
					$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&d=".$device."&act=11");
					$output .= "Ancien ID de VLAN ".FS::$iMgr->addNumericInput("oldvl")."<br />";
					$output .= "Nouvel ID de VLAN ".FS::$iMgr->addNumericInput("newvl")."<br />";
					$output .= "Confirmer ".FS::$iMgr->addCheck("accept",false);
					$output .= FS::$iMgr->addJSSubmit("modify","Appliquer","return checkTagForm();")."</form><br />";
					$output .= FS::$iMgr->addJSSubmit("search","Vérifier les ports concernés","return searchports();")."<div id=\"vlplist\"></div>";
					
					// Common JS
					$output .= "<script type=\"text/javascript\">function checkCopyState(copyId) {
							setTimeout(function() {
								$.post('index.php?at=3&mod=".$this->mid."&act=13&d=".$device."&saveid='+copyId, function(data) {
									if(data == 2) {
										$('#subpop').html('Copie en cours ...');
										checkCopyState(copyId);
									}
									else if(data == 3) {
										$('#subpop').html('Succès !');
										setTimeout(function() { $('#pop').hide(); },1000);
									}
									else if(data == 4) {
										$.post('index.php?at=3&mod=".$this->mid."&act=14&d=".$device."&saveid='+copyId, function(data) {
											$('#subpop').html('Echec !<br />Cause: '+data); 
										});
										setTimeout(function() { $('#pop').hide(); },5000);
									}
									else
										$('#subpop').html('Unknown response: '+data);
								}); }, 1000);
						}</script>";
					
					// Copy startup-config -> TFTP/FTP server
					$output .= "<script type=\"text/javascript\">function arangeform() {";
					$output .= "if(document.getElementsByName('exportm')[0].value == 2 || document.getElementsByName('exportm')[0].value == 4 || document.getElementsByName('exportm')[0].value == 5) {";
					$output .= "$('#slogin').show();";
					$output .= "} else if(document.getElementsByName('exportm')[0].value == 1) {";
					$output .= "$('#slogin').hide(); }};";
					$output .= "function sendbackupreq() {";
					$output .= "$('#subpop').html('Envoi de la requête en cours...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
					$output .= "$('#pop').show();";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=12&d=".$device."', { exportm: document.getElementsByName('exportm')[0].value, srvip: document.getElementsByName('srvip')[0].value,
					srvfilename: document.getElementsByName('srvfilename')[0].value, srvuser: document.getElementsByName('srvuser')[0].value, srvpwd: document.getElementsByName('srvpwd')[0].value,
					io: document.getElementsByName('io')[0].value },
					function(data) { 
						var copyId = data;
						$('#subpop').html('Copie en cours...');
						checkCopyState(copyId);
					});";
					$output .= "return false;";
					$output .= "};";
					$output .= "</script>";
					$output .= "<h4>Transférer la configuration</h4>";
					$output .= "Type de serveur ".FS::$iMgr->addList("exportm","arangeform();");
					$output .= FS::$iMgr->addElementToList("TFTP",1);
					$output .= FS::$iMgr->addElementToList("FTP",2);
					$output .= FS::$iMgr->addElementToList("SCP",4);
					$output .= FS::$iMgr->addElementToList("SFTP",5);
					$output .= "</select><br />";
					$output .= "Sens de transfert ".FS::$iMgr->addList("io");
					$output .= FS::$iMgr->addElementToList("Export",1);
					$output .= FS::$iMgr->addElementToList("Import",2);
					$output .= "</select><br />";
					$output .= "Adresse du serveur ".FS::$iMgr->addIPInput("srvip")."<br />";
					$output .= "Nom de fichier ".FS::$iMgr->input("srvfilename")."<br />";
					$output .= "<div id=\"slogin\" style=\"display:none;\">Utilisateur ".FS::$iMgr->input("srvuser");
					$output .= " Mot de passe ".FS::$iMgr->password("srvpwd")."</div>";
					$output .= FS::$iMgr->addJSSubmit("send","Envoyer","return sendbackupreq();");
					
					// Copy startup-config -> running-config
					$output .= "<script type=\"text/javascript\">function restorestartupconfig() {";
					$output .= "$('#subpop').html('Envoi de la requête en cours...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
					$output .= "$('#pop').show();";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=15&d=".$device."', function(data) { 
						var copyId = data;
						$('#subpop').html('Restauration en cours...');
						checkCopyState(copyId);
					});";
					$output .= "return false;";
					$output .= "};";
					$output .= "</script>";
					$output .= "<h4>Restaurer la startup-config</h4>";
					$output .= FS::$iMgr->addJSSubmit("restore","Restaurer","return restorestartupconfig();");
					return $output;
				}
				else if($showmodule == 5) {
					$output .= "<h4>Liste des VLANs</h4>";
					$found = 0;
					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
					$query = FS::$pgdbMgr->Select("device_vlan","vlan,description,creation","ip = '".$dip."'","vlan");
					$tmpoutput = "<table><tr><th>ID</th><th>Description</th><th>Date de création</th></tr>";
					while($data = pg_fetch_array($query)) {
						if(!$found) $found = 1;
						$crdate = preg_split("#\.#",$data["creation"]);
						$tmpoutput .= "<tr><td>".$data["vlan"]."</td><td>".$data["description"]."</td><td>".$crdate[0]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
					else
						$output .= FS::$iMgr->printError("Aucun VLAN trouvé sur cet équipement !");
					return $output;	
				}
	
				$iswif = (preg_match("#AIR#",FS::$pgdbMgr->GetOneData("device","model","name = '".$device."'")) ? true : false);
	
				if($iswif == false) {
					$poearr = array();
					// POE States
					$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'");
					while($data = pg_fetch_array($query))
						$poearr[$data["port"]] = $data["class"];
				}
	
				$prisearr = array();
				$query = FS::$pgdbMgr->Select("z_eye_switch_port_prises","port,prise","ip = '".$dip."'");
				while($data = pg_fetch_array($query))
					$prisearr[$data["port"]] = $data["prise"];
	
				$found = 0;
				if($iswif == false) {
					// Script pour modifier le nom de la prise
					$output .= "<script type=\"text/javascript\">";
					$output .= "function modifyPrise(src,sbmit,sw_,swport_,swpr_) { ";
					$output .= "if(sbmit == true) { ";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=2', { sw: sw_, swport: swport_, swprise: document.getElementsByName(swpr_)[0].value }, function(data) { ";
					$output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
					$output .= "}); } ";
					$output .= "else $(src).toggle(); }";
					$output .= "</script>";
				}
				$tmpoutput = "<table class=\"standardTable\"><tr><th><a href=\"index.php?mod=".$this->mid."&d=".$device."&od=port\">Port</a></th><th>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&d=".$device."&od=desc\">Description</a></th><th>Prise</th><th>MAC Interface</th><th>Up (Link/Admin)</th>";
				if($iswif == false)
					$tmpoutput .= "<th>Duplex (Link/Admin)</th>";
				$tmpoutput .= "<th>Vitesse</th>";
				if($iswif == false)
					$tmpoutput .= "<th>POE</th>";
				$tmpoutput .= "<th>";
				if($iswif == true) $tmpoutput .= "Canal</th><th>Puissance</th><th>SSID";
				else $tmpoutput .= "Vlans</th><th>Equipements connectés</th></tr>";
				$query = FS::$pgdbMgr->Select("device_port","port,name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."'",$od);
				while($data = pg_fetch_array($query)) {
					if(preg_match("#unrouted#",$data["port"]))
						continue;
					$filter_ok = 0;
					if($filter == NULL) $filter_ok = 1;
	
					if($found == 0) $found = 1;
					$convport = preg_replace("#\/#","-",$data["port"]);
					$swpdata = (isset($prisearr[$data["port"]]) ? $prisearr[$data["port"]] : "");
					$tmpoutput2 = "<tr id=\"".$convport."\"><td><a href=\"index.php?mod=".$this->mid."&d=".$device."&p=".$data["port"]."\">".$data["port"]."</a></td><td>";
	
					// Editable Desc
					$tmpoutput2 .= $data["name"];
					$tmpoutput2 .= "</td><td>";
					// Editable piece
					$tmpoutput2 .= "<div id=\"swpr_".$convport."\">";
					$tmpoutput2 .= "<a onclick=\"javascript:modifyPrise('#swpr_".$convport." a',false);\"><div id=\"swpr_".$convport."l\" class=\"modport\">";
					$tmpoutput2 .= ($swpdata == "" ? "Modifier" : $swpdata);
					$tmpoutput2 .= "</div></a><a style=\"display: none;\">";
								$tmpoutput2 .= FS::$iMgr->input("swprise-".$convport,$swpdata,10,10);
								$tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPrise('#swpr_".$convport."',true,'".$dip."','".$data["port"]."','swprise-".$convport."');\" />";
					$tmpoutput2 .= "</a></div>";
					$tmpoutput2 .= "</td><td>".$data["mac"]."</td><td>";
					// Editable state
					$tmpoutput2 .= "<div id=\"swst_".$convport."\">";
					if($data["up_admin"] == "down")
						$tmpoutput2 .= "<span style=\"color: red;\">Eteint</span>";
					else if($data["up_admin"] == "up" && $data["up"] == "down")
						$tmpoutput2 .= "<span style=\"color: orange;\">Inactif</span>";
					else if($data["up"] == "up")
						$tmpoutput2 .= "<span style=\"color: black;\">Actif</span>";
					else
						$tmpoutput2 .= "unk";
					$tmpoutput2 .= "</td><td>";
					if($iswif == false) {
	
						$tmpoutput2 .= "<div id=\"swdp_".$convport."\">";
						$tmpoutput2 .= "<div id=\"swdp_".$convport."l\" class=\"modport\"><span style=\"color: black;\">";
						$dup = (strlen($data["duplex"]) > 0 ? $data["duplex"] : "[NA]");
						$dupadm = (strlen($data["duplex_admin"]) > 0 ? $data["duplex_admin"] : "[NA]");
						if($dup == "half" && $dupadm != "half") $dup = "<span style=\"color: red;\">half</span>";
						$tmpoutput2 .= $dup." / ".$dupadm;
						$tmpoutput2 .= "</span></div></div>";
	
						$tmpoutput2 .= "</td><td>";
					}
					$tmpoutput2 .= $data["speed"]."</td><td>";
	
					if($iswif == false) {
						// POE
						if(isset($poearr[$data["port"]])) {
							if($poearr[$data["port"]] == "class0") $tmpoutput2 .= "0.0 Watts";
							else if($poearr[$data["port"]] == "class2") $tmpoutput2 .= "7.0 Watts";
							else if($poearr[$data["port"]] == "class3") $tmpoutput2 .= "15.0 Watts";
							else $tmpoutput2 .= "Unk class";
						}
						else
						$tmpoutput2 .= "N";
						$tmpoutput2 .= "</td><td>";
					}
	
					$query2 = FS::$pgdbMgr->Select("device_port_vlan","vlan,native,voice","ip = '".$dip."' AND port = '".$data["port"]."'","vlan");
	
					$nvlan = $data["vlan"];
					$vlanlist = "";
					$vlancount = 0;
					while($data2 = pg_fetch_array($query2)) {
						if($data2["native"] == "t" && $data2["vlan"] != 1) $nvlan = $data2["vlan"];
						if($data2["vlan"] == $filter) $filter_ok = 1;
						if($vlancount == 3) {
							$vlancount = 0;
							$vlanlist .= "<br />";
						}
						$vlanlist .= "<a href=\"index.php?mod=".$this->mid."&vlan=".$data2["vlan"]."\">".$data2["vlan"]."</a>";
						if($data2["native"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(n)</span>";
						if($data2["voice"] == "t") $vlanlist .= "<span style=\"font-size:10px\">(v)</span>";
						$vlanlist .= ",";
						$vlancount++;
					}
	
					if($iswif == false) {
						$tmpoutput2 .= substr($vlanlist,0,strlen($vlanlist)-1);
	
					}
					if($iswif == false) {
						$tmpoutput2 .= "</td><td>";
						$query2 = FS::$pgdbMgr->Select("node","mac","switch = '".$dip."' AND port = '".$data["port"]."'","mac");
						while($data2 = pg_fetch_array($query2)) {
							$tmpoutput2 .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data2["mac"]."\">".$data2["mac"]."</a><br />";
							$query3 = FS::$pgdbMgr->Select("node_ip","ip","mac = '".$data2["mac"]."'","time_last",1,1);
							while($data3 = pg_fetch_array($query3)) {
								$tmpoutput2 .= "&nbsp;&nbsp;<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
								$query4 = FS::$pgdbMgr->Select("node_nbt","nbname,domain,nbuser","mac = '".$data2["mac"]."' AND ip = '".$data3["ip"]."'");
								if($data4 = pg_fetch_array($query4)) {
									if($data4["domain"] != "")
										$tmpoutput2 .= "&nbsp;&nbsp;\\\\<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["domain"]."\">".$data4["domain"]."</a>\\<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
									else
										$tmpoutput2 .= "&nbsp;&nbsp;<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
									$tmpoutput2 .= "&nbsp;&nbsp;".($data4["nbuser"] == "" ? "[UNK]" : $data4["nbuser"])."@<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
								}
							}
						}
					}
					else {
						$channel = FS::$pgdbMgr->GetOneData("device_port_wireless","channel","ip = '".$dip."' AND port = '".$data["port"]."'");
						$power = FS::$pgdbMgr->GetOneData("device_port_wireless","power","ip = '".$dip."' AND port = '".$data["port"]."'");
						$ssid = FS::$pgdbMgr->GetOneData("device_port_ssid","ssid","ip = '".$dip."' AND port = '".$data["port"]."'");
						$tmpoutput2 .= $channel."</td><td>".$power."</td><td>".$ssid;
					}
					$tmpoutput2 .= "</td></tr>";
	
					if($filter_ok == 1)
						$tmpoutput .= $tmpoutput2;
				}
	
				if($found != 0) {
					$output .= $tmpoutput;
					$output .= "</table>";
				}
				else
					$output .= FS::$iMgr->printError("Impossible de trouver des informations sur l'équipement");
			}
			return $output;
		}

		private function showDeviceModules($devmod,$idx,$level=10) {
			if($level == 0)
				return "";
			if(!isset($devmod[$idx])) return "";
			$output = "<ul>";
			for($i=0;$i<count($devmod[$idx]);$i++) {
				$output .= "<li>" .$devmod[$idx][$i]["desc"]." (".$devmod[$idx][$i]["name"].") ";
				if(strlen($devmod[$idx][$i]["hwver"]) > 0)
					$output .= "[hw: ".$devmod[$idx][$i]["hwver"]."] ";
				if(strlen($devmod[$idx][$i]["fwver"]) > 0)
                                        $output .= "[fw: ".$devmod[$idx][$i]["fwver"]."] ";
				if(strlen($devmod[$idx][$i]["swver"]) > 0)
                                        $output .= "[sw: ".$devmod[$idx][$i]["swver"]."] ";
				if(strlen($devmod[$idx][$i]["serial"]) > 0)
                                        $output .= "[serial: ".$devmod[$idx][$i]["serial"]."] ";
				$output .= "/ Type: ".$devmod[$idx][$i]["type"];
				if(strlen($devmod[$idx][$i]["model"]) > 0)
                                        $output .= " Modèle: ".$devmod[$idx][$i]["model"];
				if($idx != 0)
					$output .= $this->showDeviceModules($devmod,$devmod[$idx][$i]["idx"],$level-1);
				$output .= "</li>";

			}
			$output .= "</ul>";
			return $output;
		}

		private function getDeviceSwitches($devmod,$idx) {
			if(!isset($devmod[$idx])) return "";
			$output = "";
			for($i=0;$i<count($devmod[$idx]);$i++) {
				$output .= $devmod[$idx][$i]["desc"];
				if($i+1<count($devmod[$idx])) $output .= "/";
			}
			return $output;
		}

		protected function showDeviceList() {
			$formoutput = "<script type=\"text/javascript\">function showwait() {";
                        $formoutput .= "$('#subpop').html('Découverte en cours...<br /><br /><br />".FS::$iMgr->img("styles/images/loader.gif",32,32)."');";
                        $formoutput .= "$('#pop').show();";
                        $formoutput .= "};</script>".FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=18");
                        $formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->addIPInput("dip","",20,40,"Adresse IP:");
                        $formoutput .= "</li><li>".FS::$iMgr->addJSSubmit("","Découvrir","showwait()")."</li>";
                        $formoutput .= "</ul></form>";
                        $output = FS::$iMgr->opendiv($formoutput,"Découvrir un équipement");

                        $query = FS::$pgdbMgr->Select("device","*","","name");

			$foundsw = 0;
			$foundwif = 0;
			$outputswitch = "<h4>Switches et routeurs</h4>";
			$outputswitch .= "<table id=\"dev\"><tr><th>Nom</th><th>Adresse IP</th><th>Adresse MAC</th><th>Modèle</th><th>OS</th><th>Lieu</th><th>Numéro de série</th></tr>";

			$outputwifi = "<h4>Bornes WiFi</h4>";
			$outputwifi .= "<table id=\"dev\"><tr><th>Nom</th><th>Adresse IP</th><th>Modèle</th><th>OS</th><th>Lieu</th><th>Numéro de série</th></tr>";
			while($data = pg_fetch_array($query)) {
				if(preg_match("#AIR#",$data["model"])) {
					if($foundwif == 0) $foundwif = 1;
					$outputwifi .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>";
	                                $outputwifi .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
				}
				else {
					if($foundsw == 0) $foundsw = 1;
					$outputswitch .= "<tr><td id=\"draga\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>".$data["mac"]."</td><td>";
					$outputswitch .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
				}
			}
			if($foundsw != 0) {
				$output .= $outputswitch;
				$output .= "</table>";
			}
			if($foundwif != 0) {
                                $output .= $outputwifi;
                                $output .= "</table>";
			}
			if($foundsw != 0 || $foundwif != 0) {
				$output .= "<script type=\"text/javascript\">
                                $.event.props.push('dataTransfer');
                                $('#dev #draga').on({
                                        mouseover: function(e) { $('#trash').show(); },
                                        mouseleave: function(e) { $('#trash').hide(); },
                                        dragstart: function(e) { $('#trash').show(); e.dataTransfer.setData('text/html', $(this).text()); },
                                        dragenter: function(e) { e.preventDefault();},
                                        dragover: function(e) { e.preventDefault(); },
                                        dragleave: function(e) { },
                                        drop: function(e) {},
                                        dragend: function() { $('#trash').hide(); }
                                });
                                $('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer l\'équipement \''+e.dataTransfer.getData('text/html')+'\' ?".
                                                FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=17").
                                                FS::$iMgr->addHidden("device","'+e.dataTransfer.getData('text/html')+'").
                                                FS::$iMgr->submit("","Supprimer").
                                                FS::$iMgr->button("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                                $('#pop').show();
                                        }
                                });
                                </script>";
                        }

			if($foundsw == 0 && $foundwif == 0)
				$output .= FS::$iMgr->printError("Aucun equipement trouvé");
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 2:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$prise = FS::$secMgr->checkAndSecurisePostData("swprise");
					if($port == NULL || $sw == NULL /*|| $prise != NULL && !preg_match("#^[A-Z][1-9]\.[1-9A-Z][0-9]?\.[1-9][0-9A-Z]?$#",$prise)*/) {
						echo "ERROR";
						return;
					}

					if($prise == NULL) $prise = "";
					// Modify prise for switch port
					FS::$pgdbMgr->Delete("z_eye_switch_port_prises","ip = '".$sw."' AND port = '".$port."'");
					FS::$pgdbMgr->Insert("z_eye_switch_port_prises","ip,port,prise","'".$sw."','".$port."','".$prise."'");

					// Return text for AJAX call
					if($prise == "") $prise = "Modifier";
					echo $prise;
					return;
				case 3:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$desc = FS::$secMgr->checkAndSecurisePostData("swdesc");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $desc == NULL) {
						echo "ERROR";
						return;
					}
					if(FS::$pgdbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if(setPortDesc($sw,$port,$desc) == 0) {
							echo $desc;
							if($save == "true")
								writeMemory($sw);
							FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$sw."' AND port = '".$port."'");
						}
						else
							echo "ERROR";
					}
					return;
				case 5:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$dup = FS::$secMgr->checkAndSecurisePostData("swdp");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $dup == NULL) {
							echo "ERROR";
							return;
					}
		
					if(FS::$pgdbMgr->GetOneData("device_port","type","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if($this->setPortDuplex($sw,$port,$dup) == 0) {
								if($save == "true")
										writeMemory($sw);

								$duplex = "auto";
								if($dup == 1) $duplex = "half";
								else if($dup == 2) $duplex = "full";
				
								FS::$pgdbMgr->Update("device_port","duplex_admin = '".$duplex."'","ip = '".$sw."' AND port = '".$port."'");
								$ldup = FS::$pgdbMgr->GetOneData("device_port","duplex","ip = '".$sw."' AND port = '".$port."'");
								$ldup = (strlen($ldup) > 0 ? $ldup : "[NA]");
								if($ldup == "half" && $duplex != "half") $ldup = "<span style=\"color: red;\">".$ldup."</span>";
								echo "<span style=\"color:black;\">".$ldup." / ".$duplex."</span>";
							}
							else
									echo "ERROR";
					}
					return;
				case 6:
					$device = FS::$secMgr->checkAndSecuriseGetData("dev");
					$portname = FS::$secMgr->checkAndSecuriseGetData("port");
					$out = "";
					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
					if($dip == NULL) {
						echo "-1";
						return;
					}
					$community = FS::$pgdbMgr->GetOneData("z_eye_snmp_cache","snmpro","device = '".$device."'");
					if(!$community) $community = SNMPConfig::$SNMPReadCommunity;
					exec("snmpwalk -v 2c -c ".$community." ".$dip." ifDescr | grep ".$portname,$out);
					if(strlen($out[0]) < 5) {
							echo "-1";
						return;
					}
								$out = explode(" ",$out[0]);
								$out = explode(".",$out[0]);
					if(!FS::$secMgr->isNumeric($out[1])) {
						echo "-1";
						return;
					}
					$portid = $out[1];

					$value = FS::$snmpMgr->get($dip,"1.3.6.1.4.1.9.9.68.1.2.2.1.2.".$portid);
					if($value == false)
							echo "-1";
					else
						echo $value;
					return;
				case 7:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$vlan = FS::$secMgr->checkAndSecurisePostData("vlan");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $vlan == NULL) {
							echo "ERROR";
							return;
					}
					if(setSwitchAccessVLAN($sw,$port,$vlan) != 0) {
						echo "ERROR";
						return;
					}
					if($save == "true")
						 writeMemory($sw);
					$sql = "UPDATE device_port SET vlan ='".$vlan."' WHERE ip='".$sw."' and port='".$port."'";
					pg_query($sql);
					$sql = "UPDATE device_port_vlan SET vlan ='".$vlan."' WHERE ip='".$sw."' and port='".$port."' and native='t'";
					pg_query($sql);
					echo $vlan;
					return;
				case 8:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					if($port == NULL || $sw == NULL || $vlan == NULL) {
							echo "ERROR";
							return;
					}
					if(setSwitchTrunkVlan($sw,$port,$vlan) != 0) {
						echo "ERROR";
						return;
					}

					if($save == "true")
						writeMemory($sw);
					echo $vlan;
				case 9:
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					$prise = FS::$secMgr->checkAndSecurisePostData("prise");
					$shut = FS::$secMgr->checkAndSecurisePostData("shut");
					$trunk = FS::$secMgr->checkAndSecurisePostData("trmode");
					$nvlan = FS::$secMgr->checkAndSecurisePostData("nvlan");
					$duplex = FS::$secMgr->checkAndSecurisePostData("duplex");
					$speed = FS::$secMgr->checkAndSecurisePostData("speed");
					$voicevlan = FS::$secMgr->checkAndSecurisePostData("voicevlan");
					$wr = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $trunk == NULL || $nvlan == NULL) {
						header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=1");
						return;
					}

					$pid = getPortId($sw,$port);
					if($pid == -1) {
						header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
						return;
					}

					if($duplex && FS::$secMgr->isNumeric($duplex)) {
						if($duplex < 1 || $duplex > 4) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=1");
							return;
						}
						$idx = getPortIndexes($sw,$pid);
						if($idx != NULL) {
							setPortDuplexWithPid($sw,$idx[0].".".$idx[1],$duplex);
						}
					}

					if($speed && FS::$secMgr->isNumeric($speed)) {
						$idx = getPortIndexes($sw,$pid);
                                                if($idx != NULL) {
                                                        setPortSpeedWithPid($sw,$idx[0].".".$idx[1],$speed);
                                                }
					}
					if($trunk == 1) {
						$vlanlist = FS::$secMgr->checkAndSecurisePostData("vllist");

						setSwitchAccessVLANWithPID($sw,$pid,1);
						// mab disable
                                                setSwitchportMABEnableWithPID($sw,$pid,2);
                                                setSwitchportAuthFailVLAN($sw,$pid,0);
                                                setSwitchportAuthNoRespVLAN($sw,$pid,0);
                                                setSwitchportControlMode($sw,$pid,3);
						// dot1x disable
						setSwitchportAuthHostMode($sw,$pid,1);
                                                // set settings
						if(setSwitchTrunkEncapWithPID($sw,$pid,4) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if(setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if(setSwitchTrunkVlanWithPID($sw,$pid,$vlanlist) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if(setSwitchTrunkNativeVlanWithPID($sw,$pid,$nvlan) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
					} else if($trunk == 2) {
						setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
						setSwitchNoTrunkVlanWithPID($sw,$pid);
						// mab disable
						setSwitchportMABEnableWithPID($sw,$pid,2);
						setSwitchportAuthFailVLAN($sw,$pid,0);
						setSwitchportAuthNoRespVLAN($sw,$pid,0);
						setSwitchportControlMode($sw,$pid,3);
						// dot1x disable
						setSwitchportAuthHostMode($sw,$pid,1);
						// set settings
						if(setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if(setSwitchTrunkEncapWithPID($sw,$pid,5) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if(setSwitchAccessVLANWithPID($sw,$pid,$nvlan) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
					} else if($trunk == 3) {
						$dot1xhostmode = FS::$secMgr->checkAndSecurisePostData("dot1xhostmode");
						$mabeap = FS::$secMgr->checkAndSecurisePostData("mabeap");
						if($dot1xhostmode < 1 || $dot1xhostmode > 4) {
                                                	header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
                                                        return;
                                                }
						// switchport mode access & no vlan assigned
						setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
                                                setSwitchNoTrunkVlanWithPID($sw,$pid);
						setSwitchportModeWithPID($sw,$pid,2);
						setSwitchTrunkEncapWithPID($sw,$pid,5);
						setSwitchAccessVLANWithPID($sw,$pid,1);

						// enable mab
						setSwitchportMABEnableWithPID($sw,$pid,1);
						// set MAB to EAP or not
						if($mabeap == "on")
							setSwitchMABTypeWithPID($sw,$pid,2);
						else
							setSwitchMABTypeWithPID($sw,$pid,1);
						// enable authfail & noresp vlans
						setSwitchportAuthFailVLAN($sw,$pid,$nvlan);
						setSwitchportAuthNoRespVLAN($sw,$pid,$nvlan);
						// authentication port-control auto
						setSwitchportControlMode($sw,$pid,2);
						// Host Mode for Authentication
						setSwitchportAuthHostMode($sw,$pid,$dot1xhostmode);
					}
					if(setPortStateWithPID($sw,$pid,($shut == "on" ? 2 : 1)) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
					}
					if(setSwitchportVoiceVlanWithPID($sw,$pid,$voicevlan) != 0) {
                                                header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
                                                return;
                                        }
					setPortDescWithPID($sw,$pid,$desc);
					if($wr == "on")
						writeMemory($sw);

					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$sw."'");

					if($prise == NULL) $prise = "";
					pg_query("DELETE FROM z_eye_switch_port_prises where ip = '".$dip."' AND port = '".$port."'");
					pg_query("INSERT INTO z_eye_switch_port_prises (ip,port,prise) VALUES ('".$dip."','".$port."','".$prise."')");

					FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$dip."' AND port = '".$port."'");
					FS::$pgdbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$dip."' AND port = '".$port."'");
					$sql = "UPDATE device_port SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."'";
					pg_query($sql);
					$sql = "UPDATE device_port_vlan SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."' and native='t'";
					pg_query($sql);
					FS::$pgdbMgr->Delete("device_port_vlan","ip = '".$dip."' AND port='".$port."'");
					$vllist = FS::$secMgr->checkAndSecurisePostData("vllist");
					if($trunk == 1) {
						if($vllist != NULL) {
							$vlantab = preg_split("/,/",$vllist);
							for($i=0;$i<count($vlantab);$i++)
								FS::$pgdbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$vlantab[$i]."','f',NOW(),NOW()");
						}
					}
					else if ($trunk == 2) {
						FS::$pgdbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$nvlan."','t',NOW(),NOW()");
					}
					header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port);
					return;
				case 10:
					echo "<h4>Liste des ports concernés par la modification</h4>";
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$vlan = FS::$secMgr->checkAndSecuriseGetData("vlan");
					if(!$device) {
						echo FS::$iMgr->printError("Cet équipement n'existe pas !");	
						return;
					}
					
					if(!$vlan || !FS::$secMgr->isNumeric($vlan)) {
						echo FS::$iMgr->printError("Le Vlan recherché est inexistant ou invalide !");
						return;
					}
					
					$plist = getPortList($device,$vlan);

					if(count($plist) > 0) {
						echo "<ul>";
						for($i=0;$i<count($plist);$i++)
							echo "<li>".$plist[$i]."</li>";
						echo "</ul>";
					}
					else
						FS::$iMgr->printError("Ce VLAN n'est pas présent sur l'équipement");
					return;
				case 11:
					$old = FS::$secMgr->checkAndSecurisePostData("oldvl");
					$new = FS::$secMgr->checkAndSecurisePostData("newvl");
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					if(!$old || !$new || !FS::$secMgr->isNumeric($old) || !FS::$secMgr->isNumeric($new) || $old > 4096 || $new > 4096 || $old < 0 || $new < 0) {
						header("Location: index.php?mod=".$this->mid."&d=".$device."&sh=4&err=1");
						return;
					}
					
					replaceVlan($device,$old,$new);
					header("Location: index.php?mod=".$this->mid."&d=".$device."&sh=4");
					return;
				/*
				* Backup startup-config
				*/
				case 12:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$trmode = FS::$secMgr->checkAndSecurisePostData("exportm");
					$sip =  FS::$secMgr->checkAndSecurisePostData("srvip");
					$filename = FS::$secMgr->checkAndSecurisePostData("srvfilename");
					$io = FS::$secMgr->checkAndSecurisePostData("io");
					if(!$device || !$trmode || ($trmode != 1 && $trmode != 2 && $trmode != 4 && $trmode != 5) ||
						!$sip || !FS::$secMgr->isIP($sip) || !$filename || strlen($filename) == 0 || !$io || ($io != 1 && $io != 2)) {
						echo FS::$iMgr->printError("Les données entrées sont erronées ou invalides !");
						return;
					}
					if($trmode == 2 || $trmode == 4 || $trmode == 5) {
						$username = FS::$secMgr->checkAndSecurisePostData("srvuser");
						$password = FS::$secMgr->checkAndSecurisePostData("srvpwd");
						if(!$username || $username == "" || !$password || $password == "") {
							echo FS::$iMgr->printError("Les données entrées sont erronées ou invalides !");
							return;
						}
						if($io == 1)
							echo exportConfigToAuthServer($device,$sip,$trmode,$filename,$username,$password);
						else if($io == 2)
							echo importConfigFromAuthServer($device,$sip,$trmode,$filename,$username,$password);
					}
					else if($trmode == 1) {
						if($io == 1)
							echo  exportConfigToTFTP($device,$sip,$filename);
						else
							echo  importConfigFromTFTP($device,$sip,$filename);
					} else
						FS::$iMgr->printError("Type d'export invalide !");
					return;
				/*
				* Verify backup state
				*/
				case 13:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
					if(!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
						echo FS::$iMgr->printError("Les données demandées sont invalides !");
						return;
					}
					echo getCopyState($device,$saveid);
					return;
				case 14:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					$saveid = FS::$secMgr->checkAndSecuriseGetData("saveid");
					if(!$device || !$saveid || !FS::$secMgr->isNumeric($saveid)) {
						echo FS::$iMgr->printError("Les données demandées sont invalides !");
						return;
					}
					$err = getCopyError($device,$saveid);
					switch($err) {
						case 2: echo "Nom de fichier, chemin ou droits incorrects"; break;
						case 3: echo "Temps de connexion dépassé<br />Le serveur distant ne répond pas"; break;
						case 4: echo "Pas de mémoire disponible"; break;
						case 5: echo "La source de la copie est incorrecte"; break;
						case 6: echo "Protocole non supporté"; break;
						case 7: echo "Erreur d'application de la configuration"; break;
						case 8: echo "Système non prêt"; break;
						case 9: echo "Requête abandonnée"; break;
						default: echo "Erreur inconnue"; break;
					}
					return;
				/*
				* Restore startup-config
				*/
				case 15:
					$device = FS::$secMgr->checkAndSecuriseGetData("d");
					if(!$device) {
						echo FS::$iMgr->printError("Les données entrées sont erronées ou invalides !");
						return;
					}
					echo restoreStartupConfig($device);
					return;
				// Port monitoring
				case 16:
					$device = FS::$secMgr->checkAndSecurisePostData("device");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
					$climit = FS::$secMgr->checkAndSecurisePostData("climit");
					$wlimit = FS::$secMgr->checkAndSecurisePostData("wlimit");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					if(!$device || !$port) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}

					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
					if(!$dip) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
                                                return;
					}

					$dport = FS::$pgdbMgr->GetOneData("device_port","name","ip = '".$dip."' AND port = '".$port."'");
					if(!$dport) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
                                                return;
                                        }
					if($enmon == "on") {
						if(!$climit || !$wlimit || !FS::$secMgr->isNumeric($wlimit) || !FS::$secMgr->isNumeric($climit) || $climit <= 0 || $wlimit <= 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$device."&p=".$port."&sh=3&err=2");
	                                                return;
						}
						FS::$pgdbMgr->Delete("z_eye_port_monitor","device = '".$device."' AND port = '".$port."'");
						FS::$pgdbMgr->Insert("z_eye_port_monitor","device,port,climit,wlimit,description","'".$device."','".$port."','".$climit."','".$wlimit."','".$desc."'");
					}
					else
						FS::$pgdbMgr->Delete("z_eye_port_monitor","device = '".$device."' AND port = '".$port."'");
					header("Location: index.php?mod=".$this->mid."&d=".$device."&p=".$port."&sh=3");
					return;
				case 17:
					$device = FS::$secMgr->checkAndSecurisePostData("device");
					if(!$device) {
                                                header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
                                        }
					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
					FS::$pgdbMgr->Delete("device_ip","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("device_module","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("device_port","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("device_port_power","ip = '".$dip."'");
					FS::$pgdbMgr->Delete("device_port_wireless","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("device_port_ssid","ip = '".$dip."'");
					FS::$pgdbMgr->Delete("device_port_vlan","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("device_power","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("node","switch = '".$dip."'");
                                        FS::$pgdbMgr->Delete("node_ip","ip = '".$dip."'");
                                        FS::$pgdbMgr->Delete("node_nbt","ip = '".$dip."'");
					FS::$pgdbMgr->Delete("admin","device = '".$dip."'");
					FS::$pgdbMgr->Delete("z_eye_port_id_cache","device = '".$device."'");
					FS::$pgdbMgr->Delete("z_eye_port_monitor","device = '".$device."'");
					FS::$pgdbMgr->Delete("z_eye_switch_port_prises","ip = '".$dip."'");
					FS::$pgdbMgr->Delete("z_eye_snmp_cache","device = '".$device."'");
					FS::$pgdbMgr->Delete("device","ip = '".$dip."'");
					header("Location: index.php?mod=".$this->mid);
				case 18:
					$dip = FS::$secMgr->checkAndSecurisePostData("dip");
					if(!$dip || !FS::$secMgr->isIP($dip)) {
						header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
                                        }
					exec("netdisco -d ".$dip);
					header("Location: index.php?mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
