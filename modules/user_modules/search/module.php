<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iSearch extends genModule{
		function iSearch() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\">";
			$search = FS::$secMgr->checkAndSecurisePostData("s");
			if(!$search)
				$search = FS::$secMgr->checkAndSecuriseGetData("s");
			if($search && strlen($search) > 0)
				$output .= $this->findRefsAndShow($search);
			else
				$output .= FS::$iMgr->printError("Pas de données à rechercher");
			$output .= "</div>";
			return $output;
		}
		
		private function findRefsAndShow($search) {
			$output = "<h3>Recherche: ".$search."</h3>";
			if(FS::$secMgr->isMacAddr($search)) {
				$output .= $this->showMacAddrResults($search);
            }
			else if(FS::$secMgr->isIP($search)) {
				$output .= $this->showIPAddrResults($search);
			}
			else if(is_numeric($search)) {
				$output .= $this->showNumericResults($search);
			}
			else {
				$tmpoutput = $this->showNamedInfos($search);
				if(strlen($tmpoutput) > 0)
					$output .= $tmpoutput;
				else
					$output .= FS::$iMgr->printError("Aucune donnée trouvée");
			}
			
			return $output;
		}
		
		private function showNumericResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			
			// Prise number
			$query = FS::$dbMgr->Select("fss_switch_port_prises","ip,port","prise = '".$search."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Prise référencée</h4>";
				}
				$swname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
				$convport = preg_replace("#\/#","-",$data["port"]);
				$tmpoutput .= "Equipement: <a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."\">".$swname."</a> (";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."#".$convport."\">".$data["port"]."</a>";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."&p=".$data["port"]."\">".FS::$iMgr->addImage("styles/images/pencil.gif",12,12)."</a>) <br />";
			}
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			// VLAN on a device
			$query = FS::$pgdbMgr->Select("device_vlan","ip,description","vlan = '".$search."'","ip");
			while($data = pg_fetch_array($query)) {
				if($dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'")) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>VLAN présent dans ces équipements</h4>";
					}
					$tmpoutput .= "<li> <a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$dname."&fltr=".$search."\">".$dname."</a> (".$data["description"].")<br />";
				}
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError("Aucune donnée trouvée !");

			return $output;
		}
		
		private function showNamedInfos($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;

			// Devices
			$query = FS::$pgdbMgr->Select("device","mac,ip,description,model","name ILIKE '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>Equipement Réseau</h4>";
				$tmpoutput .= "<b><i>Informations: </i></b><a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				if(strlen($data["mac"]) > 0)
					$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a> - ";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
				$tmpoutput .= "<b><i>Modèle:</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>Description: </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
			}
			
			// Prise number
			$query = FS::$dbMgr->Select("fss_switch_port_prises","ip,port","prise = '".$search."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Prise référencée</h4>";
				}
				$swname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
				$convport = preg_replace("#\/#","-",$data["port"]);
				$tmpoutput .= "Equipement: <a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."\">".$swname."</a> [";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."#".$convport."\">".$data["port"]."</a> ";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$swname."&p=".$data["port"]."\">".FS::$iMgr->addImage("styles/images/pencil.gif",12,12)."</a>]<br />";
			}
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			// DNS infos
			$searchsplit = preg_split("#\.#",$search);
			if(count($searchsplit) > 1) {
				$hostname = $searchsplit[0];
				$dnszone = "";
				for($i=1;$i<count($searchsplit);$i++) {
					$dnszone .= $searchsplit[$i];
					if($i != count($searchsplit)-1)
						$dnszone .= ".";
				}
				$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","rectype,recval","record = '".$hostname."' AND zonename = '".$dnszone."'");
				while($data = mysql_fetch_array($query)) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>Enregistrements DNS</h4>";
					}
					switch($data["rectype"]) {
						case "A": $tmpoutput .= "Adresse IPv4: "; break;
						case "AAAA": $tmpoutput .= "Adresse IPv6: "; break;
						case "CNAME": $tmpoutput .= "Alias: "; break;
						default: $tmpoutput .= "Autre (".$data["rectype"]."): "; break;
					}
					if(FS::$secMgr->isIP($data["recval"]))
						$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["recval"]."\">".$data["recval"]."</a><br />";
					else
						$tmpoutput .= $data["recval"]."<br />";
				}
				if($found) $tmpoutput .= "</div>";
				$found = 0;
			}
			
			// Netbios INFOS
			$query = FS::$pgdbMgr->Select("node_nbt","mac,ip,domain,nbname,nbuser,time_first,time_last","domain ILIKE '".$search."' OR nbname ILIKE '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Domaine/Groupe de Travail Netbios</h4>";
					$tmpoutput = "<table class=\"standardTable\"><tr><th>Noeud</th><th>Nom</th><th>Utilisateur</th><th>Première vue</th><th>Dernière vue</th></tr>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a></td><td>";
				$tmpoutput .= "\\\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>";
				$tmpoutput .= ($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a></td><td>".$fst[0]."</td><td>".$lst[0]."</td></tr>";
			}
			
			if($found) $tmpoutput .= "</table></div>";
			$found = 0;

			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError("Aucune donnée trouvée !");

			return $output;
		}
		
		private function showIPAddrResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$lastmac = "";
			$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","zonename,record","recval = '".$search."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Noms DNS associés</h4>";
				}
				$tmpoutput .= $data["record"].".".$data["zonename"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$dbMgr->Select("fss_dhcp_ip_cache","macaddr,hostname,leasetime,distributed","ip = '".$search."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Distribution DHCP</h4>";
				}
				if(strlen($data["hostname"]) > 0)
					$tmpoutput .= "Nom d'hôte DHCP: ".$data["hostname"]."<br />";
				if(strlen($data["macaddr"]) > 0)
					$tmpoutput .= "Adresse MAC liée: <a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
				$tmpoutput .= "Type d'attribution: ".($data["distributed"] != 3 ? "Dynamique" : "Statique")."<br />";
				if($data["distributed"] != 3)
					$tmpoutput .= "Validité : ".$data["leasetime"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("node_ip","mac,time_first,time_last","ip = '".$search."'","time_last",1);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Adresses MAC</h4>";
					$lastmac = $data["mac"];
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			if($lastmac) {
				$query = FS::$pgdbMgr->Select("node","switch,port,time_first,time_last","mac = '".$lastmac."' AND active = 't'","time_last",1,1);
				if($data = pg_fetch_array($query)) {
					$tmpoutput .= "<div><h4>Dernier équipement</h4>";
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$switch = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
					$piece = FS::$dbMgr->GetOneData("fss_switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
					$convport = preg_replace("#\/#","-",$data["port"]);
					$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
					$tmpoutput .= "[<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
					$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->addImage("styles/images/pencil.gif",10,10)."</a>";
					if($piece) $tmpoutput .= "/ Prise ".$piece;
					$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
					$tmpoutput .= "</div>";
				}
			}
			
			$query = FS::$pgdbMgr->Select("node_nbt","domain,nbname,nbuser,time_first,time_last","ip = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Domaine Netbios</h4>";
				}
				
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				
				$tmpoutput .= "Machine Netbios: \\\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>";
				$tmpoutput .= "\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a><br />";
				$tmpoutput .= "Utilisateur Netbios: ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";
				$tmpoutput .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			// Devices
			$query = FS::$pgdbMgr->Select("device","mac,name,description,model","ip = '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>Equipement Réseau</h4>";
				$tmpoutput .= "<b><i>Nom: </i></b><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
				$tmpoutput .= "<b><i>Informations: </i></b><a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a>)<br />";
				$tmpoutput .= "<b><i>Modèle:</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>Description: </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
			}
			
			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError("Aucune donnée trouvée !");
			return $output;
		}
		
		private function showMacAddrResults($search) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			
			$query = FS::$dbMgr->Select("fss_dhcp_ip_cache","ip,hostname,leasetime,distributed","macaddr = '".$search."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Distribution DHCP</h4>";
				}
				if(strlen($data["hostname"]) > 0)
					$tmpoutput .= "Nom d'hôte DHCP: ".$data["hostname"]."<br />";
				if(strlen($data["macaddr"]) > 0)
					$tmpoutput .= "Adresse IP liée: <a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />";
				$tmpoutput .= "Type d'attribution: ".($data["distributed"] != 3 ? "Dynamique" : "Statique")."<br />";
				if($data["distributed"] != 3)
					$tmpoutput .= "Validité : ".$data["leasetime"]."<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			$query = FS::$pgdbMgr->Select("node_ip","ip,time_first,time_last","mac = '".$search."'","time_last",2);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Adresses IP</h4>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$pgdbMgr->Select("node","switch,port,time_first,time_last","mac = '".$search."'","time_last",2);
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Emplacements réseau</h4>";
				}
				$switch = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
				$piece = FS::$dbMgr->GetOneData("fss_switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
				$convport = preg_replace("#\/#","-",$data["port"]);
				$tmpoutput .=  "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
				$tmpoutput .= "[<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->addImage("styles/images/pencil.gif",10,10)."</a>";
				$tmpoutput .= ($piece == NULL ? "" : " / Prise ".$piece);
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
			
			if($found) $tmpoutput .= "</div>";
			$found = 0;

			$query = FS::$pgdbMgr->Select("node_nbt","nbname,domain,nbuser,time_first,time_last","mac = '".$search."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0) {
					$found = 1;
					$tmpoutput .= "<div><h4>Noms Netbios</h4>";
				}
				$fst = preg_split("#\.#",$data["time_first"]);
				$lst = preg_split("#\.#",$data["time_last"]);
				$tmpoutput .= ($data["domain"] != "" ? "\\\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&nb=".$data["domain"]."\">".$data["domain"]."</a>" : "")."\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
			}
		
			if($found) $tmpoutput .= "</div>";
			$found = 0;
			
			$query = FS::$dbMgr->Select("fss_radius_db_list","addr,port,dbname,login,pwd");
			while($data = mysql_fetch_array($query)) {
				$radSQLMgr = new FSMySQLMgr();
				$radSQLMgr->setConfig($data["dbname"],$data["port"],$data["addr"],$data["login"],$data["pwd"]);
				$radSQLMgr->Connect();
				
				$ciscomac = $search[0].$search[1].$search[3].$search[4].".".$search[6].$search[7].$search[9].$search[10].".".$search[12].$search[13].$search[15].$search[16];
				$query2 = $radSQLMgr->Select("radacct","username,calledstationid,acctstarttime,acctstoptime","callingstationid = '".$ciscomac."'");
				if($data2 = mysql_fetch_array($query2)) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>Utilisateurs 802.1X</h4>";
					}
					$fst = preg_split("#\.#",$data2["acctstarttime"]);
					$lst = preg_split("#\.#",$data2["acctstoptime"]);
					$tmpoutput .= "Utilisateur: ".$data2["username"]." / Station: <a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data2["calledstationid"].">".$data2["calledstationid"]."</a>";
					$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
				}
				
				if($found) $tmpoutput .= "</div>";
				$found = 0;
				$totinbw = 0;
				$totoutbw = 0;
				$query2 = $radSQLMgr->Select("radacct","calledstationid, SUM(acctinputoctets) as input, SUM(acctoutputoctets) as output, MIN(acctstarttime) as fst, MAX(acctstoptime) as lst","callingstationid = '".$ciscomac."' GROUP BY calledstationid");
				if($data2 = mysql_fetch_array($query2)) {
					if($found == 0) {
						$found = 1;
						$tmpoutput .= "<div><h4>Bande passante 802.1X</h4>";
					}
					if($data2["input"] > 1024*1024*1024)
						$inputbw = round($data2["input"]/1024/1024/1024,2)."Go";
					else if($data2["input"] > 1024*1024)
						$inputbw = round($data2["input"]/1024/1024,2)."Mo";
					else if($data2["input"] > 1024)
						$inputbw = round($data2["input"]/1024,2)."Ko";
					else
						$inputbw = $data2["input"]."o";
						
					if($data2["output"] > 1024*1024*1024)
						$outputbw = round($data2["output"]/1024/1024/1024,2)."Go";
					else if($data2["output"] > 1024*1024)
						$outputbw = round($data2["output"]/1024/1024,2)."Mo";
					else if($data2["output"] > 1024)
						$outputbw = round($data2["output"]/1024,2)."Ko";
					else
						$outputbw = $data2["output"]."o";
					$fst = preg_split("#\.#",$data2["fst"]);
					$lst = preg_split("#\.#",$data2["lst"]);
					$tmpoutput .= "Station: ".$data2["calledstationid"]." Download: ".$inputbw." / Upload: ".$outputbw. "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br /><hr>";
					$totinbw += $data2["input"];
					$totoutbw += $data2["output"];
				}
				if($found) {
					if(totinbw > 1024*1024*1024)
						$inputbw = round(totinbw/1024/1024/1024,2)."Go";
					else if($totinbw > 1024*1024)
						$inputbw = round($data2["input"]/1024/1024,2)."Mo";
					else if(totinbw > 1024)
						$inputbw = round($totinbw/1024,2)."Ko";
					else
						$inputbw = $totinbw."o";
						
					if($totoutbw > 1024*1024*1024)
						$outputbw = round($totoutbw/1024/1024/1024,2)."Go";
					else if($totoutbw > 1024*1024)
						$outputbw = round($data2["output"]/1024/1024,2)."Mo";
					else if($totoutbw > 1024)
						$outputbw = round($totoutbw/1024,2)."Ko";
					else
						$outputbw = $totoutbw."o";
					$tmpoutput .= "Bande passante totale consommée: Téléchargement => ".$inputbw." / Upload: ".$outputbw."</div>";
				}
			}
			
			// Devices
			$query = FS::$pgdbMgr->Select("device","ip,name,description,model","mac = '".$search."'");
			if($data = pg_fetch_array($query)) {
				$tmpoutput .= "<div><h4>Equipement Réseau</h4>";
				$tmpoutput .= "<b><i>Nom: </i></b><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
				$tmpoutput .= "<b><i>Informations: </i></b><a class=\"monoComponentt_a\" href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
				$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
				$tmpoutput .= "<b><i>Modèle:</i></b> ".$data["model"]."<br />";
				$tmpoutput .= "<b><i>Description: </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
			}
			if(strlen($tmpoutput) > 0)
				$output .= $tmpoutput;
			else
				$output .= FS::$iMgr->printError("Aucune donnée trouvée !");
			return $output;
		}
	};
?>
