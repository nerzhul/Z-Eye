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

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= FS::$iMgr->addList("f");
			$query = FS::$dbMgr->Select("fss_dhcp_subnet_cache","netid,netmask");
			while($data = mysql_fetch_array($query)) {
				$formoutput .= FS::$iMgr->addElementTolist($data["netid"]."/".$data["netmask"],$data["netid"],($filter == $data["netid"] ? true : false));
			}
			
			$query = FS::$dbMgr->Select("fss_dhcp_subnet_cache","netid,netmask",($filter != NULL ? "netid = '".$filter."'" : ""));
			while($data = mysql_fetch_array($query)) {
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
				$query2 = FS::$dbMgr->Select("fss_dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed","netid = '".$data["netid"]."'");
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
