<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
	class iDNSManager extends genModule{
		function iDNSManager() { parent::genModule(); }
		public function Load() {
			$output = "";
			$output .= "<div id=\"monoComponent\">";
			$output .= $this->showStats();
			$output .= "</div>";
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$dnsoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<h3>Supervision DNS</h3>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
				$output .= FS::$iMgr->addList("f");
				
				$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
				if($shA == NULL) $shA = 1;
				
				$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
				if($shAAAA == NULL) $shAAAA = 1;
				
				$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
				if($shNS == NULL) $shNS = 1;
				
				$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
				if($shCNAME == NULL) $shCNAME = 1;
				
				$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
				if($shSRV == NULL) $shSRV = 1;
				
				$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
				if($shPTR == NULL) $shPTR = 1;
				
				$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
				if($shTXT == NULL) $shTXT = 1;
				
				$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
				if($shother == NULL) $shother = 1;
				
				$query = FS::$dbMgr->Select("fss_dns_zone_cache","zonename","","zonename");
				while($data = mysql_fetch_array($query)) {
					$formoutput .= FS::$iMgr->addElementTolist($data["zonename"],$data["zonename"],($filter == $data["zonename"] ? true : false));
				}
				$output .= $formoutput;
				$output .= "</select><br />";
				$output .= FS::$iMgr->addCheck("sa",$shA)."A ";
				$output .= FS::$iMgr->addCheck("saaaa",$shAAAA)."AAAA ";
				$output .= FS::$iMgr->addCheck("scname",$shCNAME)."CNAME ";
				$output .= FS::$iMgr->addCheck("sns",$shNS)."NS ";
				$output .= FS::$iMgr->addCheck("ssrv",$shSRV)."SRV ";
				$output .= FS::$iMgr->addCheck("stxt",$shTXT)."TXT ";
				$output .= FS::$iMgr->addCheck("sptr",$shPTR)."PTR ";
				$output .= FS::$iMgr->addCheck("sother",$shother)."Autres ";
				$output .= "<br />";
				$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
				$output .= "</form>";
				
				if($filter) {
					$output .= "<div id=\"contenttabs\"><ul>";
					$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother."\">Statistiques</a>";
					$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother."&sh=2\">Outils avancés</a>";
					$output .= "</ul></div>";
					$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
					$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				}
			} else {
				if(!$showmodule || $showmodule == 1) {
					$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
					if($shA == NULL) $shA = true;
					else if($shA > 0) $shA = true;
					else $shA = false;
					
					$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
					if($shAAAA == NULL) $shAAAA = true;
					else if($shAAAA > 0) $shAAAA = true;
					else $shAAAA = false;
					
					$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
					if($shNS == NULL) $shNS = true;
					else if($shNS > 0) $shNS = true;
					else $shNS = false;
					
					$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
					if($shCNAME == NULL) $shCNAME = true;
					else if($shCNAME > 0) $shCNAME = true;
					else $shCNAME = false;
					
					$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
					if($shSRV == NULL) $shSRV = true;
					else if($shSRV > 0) $shSRV = true;
					else $shSRV = false;
					
					$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
					if($shPTR == NULL) $shPTR = true;
					else if($shPTR > 0) $shPTR = true;
					else $shPTR = false;
					
					$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
					if($shTXT == NULL) $shTXT = true;
					else if($shTXT > 0) $shTXT = true;
					else $shTXT = false;
					
					$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
					if($shother == NULL) $shother = true;
					else if($shother > 0) $shother = true;
					else $shother = false;
					
					if(!$filter) {
						$output .= FS::$iMgr->printError("Aucune zone DNS spécifiée !");
						return $output;
					}
					
					$rectypef = "";
					if(!$shA || !$shAAAA || !$shNS || !$shCNAME || !$shPTR || !$shSRV || !$shTXT || !$shother) {
						$rectypef .= " AND rectype IN (";
						$found = false;
						if($shA) {
							$rectypef .= "'A'";
							$found = true;
						}
						if($shAAAA) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'AAAA'";
						}
						if($shNS) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'NS'";
						}
						if($shCNAME) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'CNAME'";
						}
						if($shPTR) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'PTR'";
						}
						if($shSRV) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'SRV'";
						}
						if($shTXT) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'TXT'";
						}
						
						$rectypef .= ")";
						if($shother) $rectypef .= " OR rectype NOT IN ('A','AAAA','CNAME','NS','PTR','SRV','TXT')";
					}
					
					$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","zonename,record,rectype,recval",($filter != NULL ? "zonename = '".$filter."'" : "").$rectypef,"zonename,record",2);
					$curzone = "";
					while($data = mysql_fetch_array($query)) {
						if($curzone != $data["zonename"]) {
							$curzone = $data["zonename"];
							if($curzone != "") $dnsoutput .= "</table>";
							$dnsoutput .= "<h4>Zone: ".$data["zonename"]."</h4><table><th>Enregistrement</th><th>Type</th><th>Valeur</th></tr>";
						}
						switch($data["rectype"]) {
							case "A": case "AAAA":
								$style = "background-color: #FFFF80;"; break;
							case "CNAME":
								$style = "background-color: #BFFFBF;"; break;
							case "SRV":
								$style = "background-color: #B3FFFF;"; break;
							case "NS":
								$style = "background-color: #FF8888;"; break;
							default: $style = ""; break;
						}
						$dnsoutput .= "<tr style=\"$style\"><td style=\"padding: 2px\">".$data["record"]."</td><td>".$data["rectype"]."</td><td>";
						if($data["rectype"] == "A")
							$dnsoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&node=".$data["recval"]."\">".$data["recval"]."</a>";
						else
							$dnsoutput .= $data["recval"];
						$dnsoutput .= "</td></tr>";
					}
					
					if(strlen($dnsoutput) > 0)
						$output .= $dnsoutput."</table>";
				}
				else if($showmodule == 2) {
					$output .= "<h4>Recherche d'enregistrements obsolètes</h4>";
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
					$shA = FS::$secMgr->checkAndSecurisePostData("sa");
					$shAAAA = FS::$secMgr->checkAndSecurisePostData("saaaa");
					$shNS = FS::$secMgr->checkAndSecurisePostData("sns");
					$shCNAME = FS::$secMgr->checkAndSecurisePostData("scname");
					$shSRV = FS::$secMgr->checkAndSecurisePostData("ssrv");
					$shPTR = FS::$secMgr->checkAndSecurisePostData("sptr");
					$shTXT = FS::$secMgr->checkAndSecurisePostData("stxt");
					$shother = FS::$secMgr->checkAndSecurisePostData("sother");
					
					if($filtr == NULL && $shA == NULL && $shAAAA == NULL && $shNS == NULL && $shCNAME == NULL && $shSRV == NULL && $shPTR == NULL && $shTXT == NULL && $shother == NULL) 
						header("Location: index.php?mod".$this->mid."");
					else {
						if($shA == "on") $shA = 1;
						else $shA = 0;
			
						if($shAAAA == "on") $shAAAA = 1;
						else $shAAAA = 0;
						
			
						if($shNS == "on") $shNS = 1;
						else $shNS = 0;
						
			
						if($shCNAME == "on") $shCNAME = 1;
						else $shCNAME = 0;
						
			
						if($shSRV == "on") $shSRV = 1;
						else $shSRV = 0;
						
			
						if($shPTR == "on") $shPTR = 1;
						else $shPTR = 0;
						
			
						if($shTXT == "on") $shTXT = 1;
						else $shTXT = 0;
						
			
						if($shother == "on") $shother = 1;
						else $shother = 0;
						header("Location: index.php?mod=".$this->mid.($filtr != NULL ? "&f=".$filtr : "")."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother);
					}
					return;
				case 2:
					$filter = FS::$secMgr->checkAndSecurisePostData("obsdata");
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$filter || !$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						echo FS::$iMgr->printError("Requête invalide !");
						return;
					}

					$found = false;
					$output = "";
					
					// Search deprecated records
					$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'A'");
					while($data = mysql_fetch_array($query)) {
						$query2 = FS::$pgdbMgr->Select("node_ip","mac,time_last","ip = '".$data["recval"]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'","time_last",1);
						while($data2 = pg_fetch_array($query2)) {
							$foundrecent = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'","time_last",1);
							if(!$foundrecent) {
								if(!$found) $found = true;
								$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$data["recval"]."<br />";
							}
						}
					}
					
					$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'CNAME'");
					while($data = mysql_fetch_array($query)) {
						$toquery = "";
						if($data["recval"][strlen($data["recval"])-1] == ".") {
							$toquery = $data["recval"];
							$toquery[strlen($toquery)-1] = '\0';
						}
						else
							$data["record"].".".$filter;
						$out = array();
						exec("dig -t A ".$toquery." +short|grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}$'",$out);
						if(count($out) == 0) {
							$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / Orphelin<br />";
						}
						else {
							for($i=0;$i<count($out);$i++) {
								$query2 = FS::$pgdbMgr->Select("node_ip","mac,time_last","ip = '".$out[$i]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'","time_last",1);
								while($data2 = pg_fetch_array($query2)) {
									$foundrecent = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'","time_last",1);
									if(!$foundrecent) {
										if(!$found) $found = true;
										$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$out[$i]."<br />";
									}
								}
							}
						}
					}
					if($found) echo "<h4>Enregistrements obsolètes trouvés !</h4>".$output;
					else echo FS::$iMgr->printDebug("Aucun enregistrement obsolète trouvé");
					return;
			}
		}
	};
?>