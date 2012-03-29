<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iIPManager extends genModule{
		function iIPManager() { parent::genModule(); }
		public function Load() {
			$output = "";
			$output .= "<div id=\"module_connect\"><h3>Supervision IP</h3>";
			$output .= $this->showStats();
			$output .= "</div>";
			return $output;
		}

		private function showStats() {
			$output = "";
			
			$query = FS::$dbMgr->Select("fss_dhcp_subnet_cache","netid,netmask");


			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			if(count($sort_result) > 0) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
				$output .= FS::$iMgr->addList("f");
				foreach ($sort_result as $class_a => $ca_keys) {
					foreach ($ca_keys as $class_b => $cb_keys) {
							foreach ($cb_keys as $class_c => $cc_keys) {
								if(isset($cc_keys["net"])) {
									$netw = $cc_keys["net"]."/".$cc_keys["mask"];
									$output .= FS::$iMgr->addElementTolist($netw,$netw,($filter == $cc_keys["net"]."/".$cc_keys["mask"] ? true : false));
								}
						}
					}
				}
				$output .= "</select>";
				$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
				$output .= "</form>";
				foreach ($sort_result as $class_a => $ca_keys) {
					foreach ($ca_keys as $class_b => $cb_keys) {
						if($filter == NULL) $output .= "<h3>Classe B: $class_a.$class_b.0.0/16</h3>";
						foreach ($cb_keys as $class_c => $cc_keys) {
							if(isset($cc_keys["mask"]) && ($filter == NULL || $filter == $cc_keys["net"]."/".$cc_keys["mask"])) {
								$usableaddr = ~(ip2long($cc_keys["mask"]));
								$usableaddr += 4294967295 - 4;
								$output .= "<h4>Classe C: ".$cc_keys["net"]."/".$cc_keys["mask"]."</h4>";
								$output .= "<center><canvas id=\"".$cc_keys["net"]."\" height=\"300\" width=\"450\">HTML5 non support&eacute;</canvas></center>";
								$usedaddr = (count($cc_keys)-2);
								$output .= "<center><table><tr><th>Adresse IP</th><th>Statut</th><th>MAC address</th><th>Nom d'hote</th><th>Fin du bail</th></tr>";
								$used = 0;
								$reserv = 0;
								foreach($cc_keys as $ipKey => $ipData) {
									if($ipKey == "mask" || $ipKey == "net")
										continue;
									if(isset($ipData["state"])) $rstate = $ipData["state"];
									else $rstate = "";
									switch($rstate) {
									case "free":
										$rstate = "Libre";
										$style = "background-color: #BFFFBF;";
										break;
									case "active":
									case "expired":
									case "abandoned":
										$rstate = "Utilis&eacute;";
										$style = "background-color: #FF6A6A;";
										$used++;
										break;
									case "reserved":
										$rstate = "R&eacute;serv&eacute;";
										$style = "background-color: #FFFF80;";
										$reserv++;
										break;
									default:
										$rstate = "";
										$style = "";
										break;
									}
	
									if($rstate) {
										if(isset($ipData["hw"])) $iwh = $ipData["hw"];
										else $iwh = "";
										if(isset($ipData["hostname"])) $ihost = $ipData["hostname"];
																		else $ihost = "";
										if(isset($ipData["end"])) $iend = $ipData["end"];
																		else $iend = "";
		
										$output .= "<tr style=\"$style\"><td><a class=\"monoComponent_li_a\" href=\"index.php?mod=33&node=".$class_a.".".$class_b.".".$class_c.".".$ipKey."\">";
										$output .= $class_a.".".$class_b.".".$class_c.".".$ipKey."</a>";
										$output .= "</td><td>".$rstate."</td><td>";
										$output .= "<a class=\"monoComponent_li_a\" href=\"index.php?mod=33&node=".$iwh."\">".$iwh."</a></td><td>";
										$output .= $ihost."</td><td>";
										$output .= $iend."</td></tr>";
									}
								}
		
								$output .= "</table></center><br /><hr>";
								$free = $usableaddr - $used - $reserv;
								$output .= "<script>
								{
									var pie3 = new RGraph.Pie('".$cc_keys["net"]."', [".$used.",".$reserv.",".$free."]);
									pie3.Set('chart.key', ['Used (".substr(($used/$usableaddr*100),0,5)."%)', 'Reserved (".substr(($reserv/$usableaddr*100),0,5)."%)', 'Free (".substr(($free/$usableaddr*100),0,5)."%)']);
									pie3.Set('chart.key.interactive', true);
									pie3.Set('chart.colors', ['red', 'yellow', 'green']);
									pie3.Set('chart.shadow', true);
									pie3.Set('chart.shadow.offsetx', 0);
									pie3.Set('chart.shadow.offsety', 0);
									pie3.Set('chart.shadow.blur', 25);
									pie3.Set('chart.strokestyle', 'white');
									pie3.Set('chart.linewidth', 3);
									pie3.Draw();
								   }</script>";
							}
						}
					}
				}
			}
			else
				FS::$iMgr->printError("Aucun réseau IP n'a été trouvé dans le(s) serveur(s) DHCP !");

			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					if($filtr == NULL) header("Location: index.php?mod".$this->mid."");
					else header("Location: index.php?mod=".$this->mid."&f=".$filtr);
					return;
			}
		}
	};
?>