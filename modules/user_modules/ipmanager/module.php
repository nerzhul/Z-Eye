<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
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
			$formoutput = "";
			$netoutput = "";
			
			$resarray = array();
			// @TODO filter
			$query = FS::$dbMgr->Select("fss_dhcp_subnet_cache","netid,netmask");
			
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= FS::$iMgr->addList("f");
				
			while($data = mysql_fetch_array($query)) {
				$iparray = array();
				$formoutput .= FS::$iMgr->addElementTolist($data["netid"]."/".$data["netmask"],$data["netid"]."/".$data["netmask"],($filter == $data["netid"]."/".$data["netmask"] ? true : false));
				$netoutput .= "<h4>Réseau : ".$data["netid"]."/".$data["netmask"]."</h4>";
				$netoutput .= "<center><canvas id=\"".$data["netid"]."\" height=\"300\" width=\"450\">HTML5 non support&eacute;</canvas></center>";
				$netoutput .= "<center><table><tr><th>Adresse IP</th><th>Statut</th><th>MAC address</th><th>Nom d'hote</th><th>Fin du bail</th></tr>";
				$netobj = new FSNetwork();
				$netobj->setNetAddr($data["netid"]);
				$netobj->setNetMask($data["netmask"]);
				for($i=ip2long($netobj->getFirstUsableIP());$i<=($netobj->getLastUsableIP());$i++) {
					$iparray[$i] = array();
					$iparray[$i]["mac"] = "";
					$iparray[$i]["host"] = "";
					$iparray[$i]["ltime"] = "";
					$iparray[$i]["distrib"] = 0;
				}
				$query2 = FS::$dbMgr->Select("fss_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed","netid = '".$data["netid"]."'");
				while($data2 = mysql_fetch_array($query2)) {
					$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
					$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
					$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
					$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
				}
				
				$used = 0;
				$reserv = 0;
				foreach($iparray as $key => $value) {
					$rstate = "";
					$style = "";
					switch($value["distrib"]) {
						case 1:
							$rstate = "Libre";
							$style = "background-color: #BFFFBF;";
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
						default:
							$rstate = "";
							$style = "";
							break;
					}
					$netoutput .= "<tr style=\"$style\"><td><a class=\"monoComponent_li_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&node=".long2ip($key)."\">";
					$netoutput .= long2ip($key)."</a>";
					$netoutput .= "</td><td>".$rstate."</td><td>";
					$netoutput .= "<a class=\"monoComponent_li_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&node=".$value["mac"]."\">".$value["mac"]."</a></td><td>";
					$netoutput .= $value["host"]."</td><td>";
					$netoutput .= $value["end"]."</td></tr>";
				}
				
				$netoutput .= "</table></center><br /><hr>";
				$netoutput .= "<script>
				{
					var pie3 = new RGraph.Pie('".$cc_keys["net"]."', [".$used.",".$reserv.",".$free."]);
					pie3.Set('chart.key', ['Used (".substr(($used/($netobj->getMaxHosts())*100),0,5)."%)', 'Reserved (".substr(($reserv/($netobj->getMaxHosts())*100),0,5)."%)', 'Free (".substr(($free/($netobj->getMaxHosts())*100),0,5)."%)']);
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
			$output .= $formoutput;
			$output .= "</select>";
			$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
			$output .= "</form>";
			$output .= $netoutput;

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