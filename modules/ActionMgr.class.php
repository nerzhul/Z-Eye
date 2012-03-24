<?php
	class ActionMgr {
		function ActionMgr() {}
		
		public function HoneyPot() {
			echo "Honeypot";
		}
		
		public function DoAction($act) {
			if(!isset($_GET["mod"])) $_GET["mod"] = 0;
			FS::$secMgr->SecuriseStringForDB($_GET["mod"]);
			
			switch($_GET["mod"]) {
				case 0:
					break;
				default:
					$mod_name = FS::$dbMgr->GetOneData("fss_modules","name","id = '".$_GET["mod"]."'");
					$mod_path = FS::$dbMgr->GetOneData("fss_modules","path","id = '".$_GET["mod"]."'");
					if(!$mod_name) {
						header("Location: index.php");
						return;
					}
					$mod_level = FS::$dbMgr->GetOneData("fss_modules","ulevel","id = '".$_GET["mod"]."'");
					$mod_conn = FS::$dbMgr->GetOneData("fss_modules","isconnected","id = '".$_GET["mod"]."'");
					if($mod_level != NULL && $mod_level > FS::$sessMgr->getUserLevel()) {
						header("Location: index.php");
						return;
					}
					
					if($mod_conn != NULL && $mod_conn > 0 && !FS::$sessMgr->isConnected()) {
						header("Location: index.php");
						return;
					}
					
					require_once(dirname(__FILE__)."/user_modules/mod_".$mod_path.".php");
					$module = FS::$iMgr->getObjectByName($mod_name);
					switch($mod_name) {
						case "iConnect":
							$name = FS::$secMgr->checkAndSecurisePostData("uname");
							$pwd = FS::$secMgr->checkAndSecurisePostData("upwd");
							$module->TryConnect($name,$pwd);
							break;
						case "iDisconnect":
							$module->Disconnect();
							break;
						case "iLinkMgmt":
							switch($act) {
								case 1: // new
									$module->RegisterLink();
									//$link = new HTTPLink(24);
									header("Location: index.php?mod=16");
									break;
								case 2: // edit
									$module->EditLink();
									//$link = new HTTPLink(24);
									header("Location: index.php?mod=16");
									break;
								case 3: // del	
									$module->RemoveLink();
									//$link = new HTTPLink(24);
									header("Location: index.php?mod=16");
									break;
								default: $this->HoneyPot();	break;
							}
							break;
						case "iModuleMgmt":
							switch($act) {
								case 1: // new
									$module->RegisterModule();
									//$link = new HTTPLink(32);
									header("Location: index.php?mod=17");
									break;
								case 2: // edit
									$module->EditModule();
									//$link = new HTTPLink(32);
									header("Location: index.php?mod=17");
									break;
								case 3: // del	
									$module->RemoveModule();
									//$link = new HTTPLink(32);
									header("Location: index.php?mod=17");
									break;
								default: $this->HoneyPot();	break;
							}
							break;
						case "iMenuMgmt":
							switch($act) {
								case 1: // new
									$module->RegisterMenu();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 2: // edit
									$module->EditMenu();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 3: // del	
									$module->RemoveMenu();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 4: // add elm
									$module->addMenuElement();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 5: // edit elem
									$module->EditMenuElement();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 6: // del elem
									$module->RemoveMenuElement();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								case 7: // add elmtomenu
									$module->addElementToMenu();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;								
								case 8: // del elmtomenu
									$module->RemoveElementFromMenu();
									//$link = new HTTPLink(35);
									header("Location: index.php?mod=19");
									break;
								default: $this->HoneyPot();	break;
							}
							break;
						case "iInscription":
							switch($act) {
								case 1:
									switch($module->RegisterUser()) {
										case 1: $link = new HTTPLink(52); break;
										case 2: $link = new HTTPLink(53); break;
										case 3: $link = new HTTPLink(54); break;
										case 4: $link = new HTTPLink(55); break;
										case 5: $link = new HTTPLink(56); break;
										case 6: $link = new HTTPLink(57); break;
										case 7: $link = new HTTPLink(58); break;
										case 8: $link = new HTTPLink(59); break;
										case 9: $link = new HTTPLink(60); break;
										case 10: $link = new HTTPLink(61); break;
										case 11: $link = new HTTPLink(62); break;
										case -1: $link = new HTTPLink(63); break;
										case 12: $link = new HTTPLink(65); break;
										case 13: $link = new HTTPLink(66); break;
										default: $this->HoneyPot(); return;
									}
									header("Location: ".$link->getIt());
									break;
								default: $this->HoneyPot(); break;
							}
							break;	
						case "iDHCPConfig":
							switch($act) {
								case 1: 
									switch($module->UpdateGeneralSection()) {
										case 0: $link = new HTTPLink(76); break;
										case 1: $link = new HTTPLink(73); break;
										case 2: $link = new HTTPLink(74); break;
										case 3: header("Location: index.php?mod=31&err=3"); return;
										case 4: header("Location: index.php?mod=31&err=4"); return;
										case 5: header("Location: index.php?mod=31&err=5"); return;
										default: $this->HoneyPot();
									}
									header("Location: ".$link->getIt());
									return;
								case 2:
									$err = $module->CreateSubnet();
									if($err < 0)
										header("Location: index.php?mod=31&do=1&err=".$err);
									else
										header("Location: index.php?mod=31&do=2&net=".$err);							
									return;
								case 3:
									$err = $module->UpdateSubnet();
									if(is_array($err))
										header("Location: index.php?mod=31&do=2&err=".$err[1]."&net=".$err[0]);
									else
										header("Location: index.php?mod=31&do=2&net=".$err);
									return;
								case 4: $module->DeleteSubnet(); break;
								case 5: 
									$net = $module->CreateDistributedRange();
									if(is_array($net))
										header("Location: index.php?mod=31&do=5&err=".$net[1]."&net=".$net[0]);
									else
										header("Location: index.php?mod=31&do=2&net=".$net);
									return;
								case 6: 
									$net = $module->UpdateDistributedRange(); 
									if(is_array($net))
										header("Location: index.php?mod=31&do=6&err=".$net[1]."&net=".$net[0]."&rid=".$net[2]);
									else
										header("Location: index.php?mod=31&do=2&net=".$net);
									return;
								case 7:
									$netid = $module->DeleteDistributedRange();
									header("Location: index.php?mod=31&do=2&net=".$netid);
									return;
								case 8: // add reserv
									if(FS::isAJAXCall()) {
										$err = $module->addOrUpdateReserv(true);
										echo $err;
									}
									else {
										$netid = $module->addOrUpdateReserv();
										if(is_array($netid))
											header("Location: index.php?mod=31&do=7&err=".$netid[1]."&ip=".$netid[0]);
										else
											header("Location: index.php?mod=31&do=2&net=".$netid);
									}
									return;
								case 10: // delete reserv
									$netid = $module->deleteReserv();
									header("Location: index.php?mod=31&do=2&net=".$netid);
									return;
								case 11:
									$fid = $module->CreateFailover();
									if(is_array($fid))
										header("Location: index.php?mod=31&do=3&err=".$fid[1]);
									else
										header("Location: index.php?mod=31");
									return;
								case 12: // update failover
									$fid = $module->UpdateFailover();
									if(is_array($fid))
										header("Location: index.php?mod=31&do=4&err=".$fid[1]."&fid=".$fid[0]);
									else
										header("Location: index.php?mod=31");
									return;
								case 13: // delete failover
									$module->DeleteFailover();
									break;
								case 14: // apply configuration
									$module->writeConfig();
									break;
								default: $this->HoneyPot(); break;
							}
							header("Location: index.php?mod=27");
							break;
						case "iDNS":
							switch($act) {
								case 1:
									$zid = $module->addDNSZone();
									if(is_array($zid))
										header("Location: index.php?mod=36&do=1&err=".$zid[1]);
									else
										header("Location: index.php?mod=36&do=2&zid=".$zid);
									return;
								case 2: 
									$zid = $module->updateDNSZone();
									if(is_array($zid))
										header("Location: index.php?mod=36&do=2&zid=".$zid[0]."&err=".$zid[1]);
									else
										header("Location: index.php?mod=36&do=2&zid=".$zid);
									return;
								case 3:
									$module->deleteDNZZone();
									header("Location: index.php?mod=36");
									return;
								case 4:
									$zid = $module->addRecord();
									if(is_array($zid))
										header("Location: index.php?mod=36&do=3&zid=".$zid[0]."&err=".$zid[1]);
									else
										header("Location: index.php?mod=36&do=2&zid=".$zid);
									return;
								case 5:
									$zid = $module->deleteRecord();
									if(FS::isAJAXCall())
										echo $zid;
									else
										header("Location: index.php?mod=36&do=2&zid=".$zid);
									return;
								default: $this->HoneyPot(); break;
							}
							header("Location: index.php?mod=36");
							break;
						case "iStats":
							switch($act) {
								case 1:
									$ech = FS::$secMgr->checkAndSecurisePostData("ech");
									if($ech == NULL) $ech = 7;
									$ec = FS::$secMgr->checkAndSecurisePostData("ec");
						                        if($ec == NULL) $ec = 365;
						                        if(!FS::$secMgr->isNumeric($ec)) $ec = 365;
									header("Location: index.php?mod=32&s=1&ech=".$ech."&ec=".$ec);
									return;
								case 2:
									$stype = FS::$secMgr->checkAndSecurisePostData("stype");
                                                                        if($stype == NULL) $stype = 1;
									header("Location: index.php?mod=32&s=".$stype);
                                                                        return;
								case 3: 
									$filtr = FS::$secMgr->checkAndSecurisePostData("f");
									if($filtr == NULL) header("Location: index.php?mod=32&s=2");
									else header("Location: index.php?mod=32&s=2&f=".$filtr);
									return;
								default: $this->HoneyPot(); break;
							}
							break;
						case "iSwitchMgmt":
                                                        switch($act) {
                                                                case 1:
                                                                        $search = FS::$secMgr->checkAndSecurisePostData("search");
                                                                        header("Location: index.php?mod=33&s=".$search);
                                                                        return;
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
									$sql = "REPLACE INTO fss_switch_port_prises VALUES ('".$sw."','".$port."','".$prise."')";
									mysql_query($sql);

									if($prise != "") {
										$piecetab = preg_split("#\.#",$prise);
										if(isset($piecetab[0]) && isset($piecetab[1]) && isset($piecetab[2]) && !isset($piecetab[3])) {
											if(FS::$secMgr->isNumeric($piecetab[1]) && FS::$secMgr->isNumeric($piecetab[2])) {
												$pname = $piecetab[0].".".$piecetab[1];
												for($i=1;$i<=$piecetab[2];$i++) {
													mysql_query("INSERT IGNORE INTO fss_piece_prises VALUES ('".$pname."','".$i."','')");
												}
											}
										}
									}
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
										if($module->setPortDesc($sw,$port,$desc) == 0) {
											echo $desc;
											if($save == "true")
												$module->writeMemory($sw);
											FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$sw."' AND port = '".$port."'");
										}
										else
											echo "ERROR";
									}
									return;
								case 4:
                                                                        $port = FS::$secMgr->checkAndSecurisePostData("swport");
                                                                        $sw = FS::$secMgr->checkAndSecurisePostData("sw");
                                                                        $st = FS::$secMgr->checkAndSecurisePostData("swst");
                                                                        $save = FS::$secMgr->checkAndSecurisePostData("wr");
                                                                        if($port == NULL || $sw == NULL || $st == NULL) {
                                                                                echo "ERROR";
                                                                                return;
                                                                        }

                                                                        if($lup = FS::$pgdbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'")) {
										$state = $st == "true" ? 2 : 1;
                                                                                if($module->setPortState($sw,$port,$state) == 0) {
                                                                                        if($save == "true")
                                                                                                $module->writeMemory($sw);
                                                                                        FS::$pgdbMgr->Update("device_port","up_admin = '".($st == "true" ? "down" : "up")."'","ip = '".$sw."' AND port = '".$port."'");
											if($state == 1) {
												if($lup == "up") $lupstr = "<span style=\"color: black;\">Actif</span>";
												else $lupstr = "<span style=\"color: orange;\">Inactif</span>";
											}
											echo ($state == 1 ? $lupstr : "<span style=\"color:red;\">Eteint</span>");
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
                                                                                if($module->setPortDuplex($sw,$port,$dup) == 0) {
                                                                                        if($save == "true")
                                                                                                $module->writeMemory($sw);

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
						                        exec("snmpwalk -v 2c -c Iota ".$device." ifDescr | grep ".$portname,$out);
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

                							$value = snmpget($device,"Iota","1.3.6.1.4.1.9.9.68.1.2.2.1.2.".$portid);
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
									if($module->setSwitchAccessVLAN($sw,$port,$vlan) != 0) {
										echo "ERROR";
										return;
									}
									if($save == "true")
                                                                             $module->writeMemory($sw);
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
									if($module->setSwitchTrunkVlan($sw,$port,$vlan) != 0) {
									        echo "ERROR";
                                                                                return;
                                                                        }

									if($save == "true")
                                                                             $module->writeMemory($sw);
									echo $vlan;
								case 9:
									$sw = FS::$secMgr->checkAndSecurisePostData("sw");
									$port = FS::$secMgr->checkAndSecurisePostData("port");
									$desc = FS::$secMgr->checkAndSecurisePostData("desc");
									$prise = FS::$secMgr->checkAndSecurisePostData("prise");
									$shut = FS::$secMgr->checkAndSecurisePostData("shut");
									$trunk = FS::$secMgr->checkAndSecurisePostData("trmode");
									$nvlan = FS::$secMgr->checkAndSecurisePostData("nvlan");
									$wr = FS::$secMgr->checkAndSecurisePostData("wr");
									if($port == NULL || $sw == NULL || $trunk == NULL || $nvlan == NULL) {
										header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=1");
										return;
									}

									$pid = $module->getPortId($sw,$port);
									if($pid == -1) {
										header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
										return;
									}

									if($trunk == 1) {
										$vlanlist = FS::$secMgr->checkAndSecurisePostData("vllist");

										$module->setSwitchAccessVLANWithPID($sw,$pid,1);
										if($module->setSwitchTrunkEncapWithPID($sw,$pid,4) != 0) {
											header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
										if($module->setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
										if($module->setSwitchTrunkVlanWithPID($sw,$pid,$vlanlist) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
										if($module->setSwitchTrunkNativeVlanWithPID($sw,$pid,$nvlan) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
									} else if($trunk == 2) {
										$module->setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
										$module->setSwitchNoTrunkVlanWithPID($sw,$pid);
										if($module->setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
										if($module->setSwitchTrunkEncapWithPID($sw,$pid,5) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
										if($module->setSwitchAccessVLANWithPID($sw,$pid,$nvlan) != 0) {
                                                                                        header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                        return;
                                                                                }
									}
									if($module->setPortStateWithPID($sw,$pid,($shut == "on" ? 2 : 1)) != 0) {
                                                                                header("Location: index.php?mod=33&d=".$sw."&p=".$port."&err=2");
                                                                                return;
                                                                        }
									$module->setPortDescWithPID($sw,$pid,$desc);
									if($wr == "on")
                                                                             $module->writeMemory($sw);

									$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$sw."'");

									if($prise == NULL) $prise = "";
                                                                        mysql_query("REPLACE INTO fss_switch_port_prises VALUES ('".$dip."','".$port."','".$prise."')");

                                                                        if($prise != "") {
                                                                                $piecetab = preg_split("#\.#",$prise);
                                                                                if(isset($piecetab[0]) && isset($piecetab[1]) && isset($piecetab[2]) && !isset($piecetab[3])) {
                                                                                        if(FS::$secMgr->isNumeric($piecetab[1]) && FS::$secMgr->isNumeric($piecetab[2])) {
                                                                                                $pname = $piecetab[0].".".$piecetab[1];
                                                                                                for($i=1;$i<=$piecetab[2];$i++) {
                                                                                                        mysql_query("INSERT IGNORE INTO fss_piece_prises VALUES ('".$pname."','".$i."','')");
                                                                                                }
                                                                                        }
                                                                                }
                                                                        }
									FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$dip."' AND port = '".$port."'");
									FS::$pgdbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$dip."' AND port = '".$port."'");
									$sql = "UPDATE device_port SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."'";
                                                                        pg_query($sql);
                                                                        $sql = "UPDATE device_port_vlan SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."' and native='t'";
                                                                        pg_query($sql);
									if(FS::$secMgr->checkAndSecurisePostData("vllist") != NULL) {
										$vlantab = preg_split("/,/",FS::$secMgr->checkAndSecurisePostData("vllist"));
										FS::$pgdbMgr->Delete("device_port_vlan","ip = '".$dip."' AND port='".$port."'");
										for($i=0;$i<count($vlantab);$i++)
											FS::$pgdbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$vlantab[$i]."','f',NOW(),NOW()");
									}
									$sql = "UPDATE device_port_vlan SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."' and native='t'";
                                                                        pg_query($sql);
									header("Location: index.php?mod=33&d=".$sw."&p=".$port);
									return;
								default: $this->HoneyPot(); break;
                                                        }
                                                        break;
						case "iPriseMgmt":
							switch($act) {
                                                                case 1:
									$nbpr = FS::$secMgr->checkAndSecurisePostData("nbpr");
									$piece = FS::$secMgr->checkAndSecurisePostData("piece");
									if($nbpr == NULL || $piece == NULL || !FS::$secMgr->isNumeric($nbpr) || strlen($piece) > 10) {
										echo "ERROR"; return;
									}
									FS::$dbMgr->Delete("fss_piece_prises","piece = '".$piece."'");
									for($i=1;$i<=$nbpr;$i++)
										FS::$dbMgr->Insert("fss_piece_prises","piece,prise,comment","'".$piece."','".$i."',''");
									echo $nbpr;
									return;
								default: $this->HoneyPot(); break;
							}
							break;
						case "iNetdisco":
							switch($act) {
                                                                case 1:
									$suffix = FS::$secMgr->checkAndSecurisePostData("suffix");
									$dir = FS::$secMgr->checkAndSecurisePostData("dir");
									$nodetimeout = FS::$secMgr->checkAndSecurisePostData("nodetimeout");
									$devicetimeout = FS::$secMgr->checkAndSecurisePostData("devicetimeout");
									$pghost = FS::$secMgr->checkAndSecurisePostData("pghost");
									$dbname = FS::$secMgr->checkAndSecurisePostData("dbname");
									$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
									$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
									$snmpro = FS::$secMgr->checkAndSecurisePostData("snmpro");
									$snmprw = FS::$secMgr->checkAndSecurisePostData("snmprw");
									$snmptimeout = FS::$secMgr->checkAndSecurisePostData("snmptimeout");
									$snmptry = FS::$secMgr->checkAndSecurisePostData("snmptry");
									$snmpver = FS::$secMgr->checkAndSecurisePostData("snmpver");
									$snmpmibs = FS::$secMgr->checkAndSecurisePostData("snmpmibs");
									$fnode = FS::$secMgr->checkAndSecurisePostData("fnode");
									if($module->checkNetdiscoConf($suffix,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$fnode) == true) {
										$module->writeNetdiscoConf($suffix,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$fnode);
										header("Location: m-36.html");
										
										return;
									}
									header("Location: index.php?m=36&err=1");	
									return;
								default: $this->HoneyPot(); break;
							}
							break;
						default: $this->HoneyPot();	break;
					}
					break;
			}
		}
	};

?>
