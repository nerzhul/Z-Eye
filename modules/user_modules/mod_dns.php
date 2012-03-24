<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	require_once(dirname(__FILE__)."/user_objects/DNSZone.class.php");
	require_once(dirname(__FILE__)."/user_objects/DNSRecord.class.php");
	class iDNS extends genModule{
		function iDNS($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "";
			if($do = FS::$secMgr->checkGetData("do")) {
				switch($do) {
					case 1: $output .= $this->showDNSZoneForm(); break;
					case 2: $output .= $this->showDNSZoneForm(true); break;
					case 3: $output .= $this->showRecordForm(); break;
					default: $output .= $this->showMain(); break;
				}
			}
			else
				$output .= $this->showMain();
			return $output;
		}
		
		private function showMain() {
			$output = "<div id=\"monoComponent\"><h3>Configuration DNS</h3>
			<h4>Gestion des Zones DNS</h4>".FS::$iMgr->addJSONLink('{ "at": "2", "mid": "36", "do": "1" }',"Ajouter une nouvelle zone DNS")."<br /><br />";
			$query = FS::$dbMgr->Select("fss_dns_zones","id,zonename,type");
			$exists = false;
			while($data = mysql_fetch_array($query)) {
					if(!$exists) {
						$output .= "<table class=\"standardTable\" width=\"80%\"><tr><th>Zone</th><th width=\"20%\">Type</th><th width=\"15px\"></th></tr>";
						$exists = true;
					}
					$output .= "<tr><td>".FS::$iMgr->addJSONLink('{ "at": "2", "mid": "36", "do": "2", "zid": "'.$data["id"].'"}',$data["zonename"])."</td><td><center>".($data["type"] == 1 ? "Maître" : "Esclave")."</center></td><td><a href=\"index.php?mod=36&act=3&zid=".$data["id"]."\">";
					$output .= $this->iMgr->addImage("styles/images/cross.png",15,15);
					$output .= "</a></tr>";
			}
			
			if($exists)
				$output .= "</table>";
				
			$output .= "</div>";
			return $output;
		}
		
		/* Zone Management */
		
		private function showDNSZoneForm($edit = false) {
			$output = "<div id=\"monoComponent\"><h3>Configuration DNS - ".($edit ? "Edition de Zone DNS" : "Création d'une nouvelle Zone DNS")."</h3>";
			if($edit) {
				$zid = FS::$secMgr->checkAndSecuriseGetData("zid");
				$zone = new DNSZone();
				$zone->setId($zid);
				if(!$zone->Load()) {
					$output .= FS::$iMgr->printError("Cette zone DNS n'existe pas !")."</div>";
					return $output;
				}
				
				$query = FS::$dbMgr->Select("fss_dns_zones","id, zonename, type","zonename LIKE '%.".$zone->getZoneName()."'");
				$exists = false;
				$subzone = "<h4>Sous-Zones</h4>";
				$subzone .= "<table class=\"standardTable\" width=\"80%\"><tr><th>Zone</th><th width=\"20%\">Type</th></tr>";
				while($data = mysql_fetch_array($query)) {
					if(!$exists) {
						$output .= $subzone;
						$exists = true;
					}
					$output .= "<tr><td>".FS::$iMgr->addJSONLink('{ "at": "2", "mid": "36", "do": "2", "zid": "'.$data["id"].'"}',$data["zonename"])."</td><td><center>".($data["type"] == 1 ? "Maître" : "Esclave")."</center></td></tr>";
				}
				
				if($exists)
					$output .= "</table><br />";
			}
			$link = new HTTPLink($edit ? 95 : 94);
			$output .= FS::$iMgr->addForm($link->getIt());
			if($edit)
				$output .= "<h4>Configuration de la zone</h4>";
			$output .= "<table class=\"standardTable\" width=\"80%\">";
			if($edit) $output .= FS::$iMgr->addHidden("zid",$zid);
			$output .= FS::$iMgr->addIndexedLine("Nom de la zone","zonename",$edit ? $zone->getZoneName() : "");
			$output .= "<tr><td>Maître</td><td>";
			$output .= FS::$iMgr->addCheck("type",$edit ? ($zone->getType() == 1 ? true : false): "");
			$output .= "</td></tr>";
			if($edit) {
				if($zone->getType() == 1) {
					$output .= FS::$iMgr->addIndexedNumericLine("Délai minimum de Rafraîchissement","minimum",$zone->getMinimum());
					$output .= FS::$iMgr->addIndexedNumericLine("Délai de Rafraîchissement","refresh",$zone->getRefresh());
					$output .= FS::$iMgr->addIndexedNumericLine("Délai en cas d'échec","retry",$zone->getRetry());
					$output .= FS::$iMgr->addIndexedNumericLine("Délai d'expiration","expire",$zone->getExpire());
					$output .= FS::$iMgr->addIndexedLine("Autorité de la zone","soa",$zone->getSOA());
					$output .= FS::$iMgr->addIndexedLine("Reponsable de la zone","hostmaster",$zone->getHostMaster());
				}
				else {
					$output .= FS::$iMgr->addIndexedLine("DNS primaire de la zone","dns1",$zone->getDNS1());
					$output .= FS::$iMgr->addIndexedLine("DNS secondaire de la zone","dns2",$zone->getDNS2());
				}
			}
			$output .= FS::$iMgr->addTableSubmit("submit","Enregistrer");
			$output .= "</table></form>";
			
			if($edit && $zone->getType() == 1) {
				$output .= "<h4>Enregistrements de la zone</h4>".FS::$iMgr->addJSONLink('{ "at": "2", "mid": "36", "do": "3", "zid": "'.$zid.'" }',"Ajouter un enregistrement")."<br /><br />";
				$exists = false;
				$output2 = "<table class=\"standardTable\" width=\"70%\"><tr><th>Nom</th><th width=\"12%\">Type</th><th>Valeur</th><th width=\"15px\"></th></tr>";
				$query = FS::$dbMgr->Select("fss_dns_records","id,name,type,value,zoneid","zoneid = '".$zid."'","name");
				while($data = mysql_fetch_array($query)) {
					if(!$exists) {
						$output .= $output2;
						$exists = true;
					}
					switch($data["type"]) {
						case 1: $rectype = "A"; break;
						case 2: $rectype = "AAAA"; break;
						case 3: $rectype = "CNAME"; break;
						case 4: $rectype = "MX"; break;
						case 5: $rectype = "SRV"; break;
						default: $rectype = "UNK"; break;
					}
					$output .= "<tr><td><center>".$data["name"]."</center></td><td><center><b>".$rectype."</b></center></td><td><center>".$data["value"]."</center></td><td><a href=\"javascript:deleteRecord(".$zid.",".$data["id"].");\">";
					$output .= $this->iMgr->addImage("styles/images/cross.png",15,15);
					$output .= "</a></td></tr>";
				}
				if($exists)
					$output .= "</table>";
			}
			$output .= "</div>";
			return $output;
		}
		
		public function addDNSZone() {
			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");
			$type = FS::$secMgr->checkAndSecurisePostData("type");
			
			/*
			 * check if zonename doesnt exists
			 * 
			 */
			 
			if(!$zonename)
				return array(0,-1);
				
			$zone = new DNSZone();
			$zone->setZoneName($zonename);
			$zone->setType($type == "on" ? 1 : 0);
			$zone->Save();
			return FS::$dbMgr->GetOneData("fss_dns_zones","id","zonename = '".$zonename."'");
		}
		
		public function deleteDNZZone() {
			$zid = FS::$secMgr->checkAndSecurisePostData("zid");
			if($zid) {
				$zone = new DNSZone();
				$zone->setId($zid);
				$zone->Delete();
			}
		}
		
		public function updateDNSZone() {
			$zid = FS::$secMgr->checkAndSecurisePostData("zid");
			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");
			$type = FS::$secMgr->checkAndSecurisePostData("type");
			
			
			/*
			 * check if zonename doesnt exists
			 * 
			 */
			 
			if(!$zonename)
				return array($zid,-1);
				
			$zone = new DNSZone();
			$zone->setId($zid);
			$zone->Load();
			$oldtype = $zone->getType();
			$zone->setZoneName($zonename);
			$zone->setType($type == "on" ? 1 : 0);
			
			if($oldtype == 1) {
				$refresh = FS::$secMgr->checkAndSecurisePostData("refresh");
				$retry = FS::$secMgr->checkAndSecurisePostData("retry");
				$minimum = FS::$secMgr->checkAndSecurisePostData("minimum");
				$expire = FS::$secMgr->checkAndSecurisePostData("expire");
				$soa = FS::$secMgr->checkAndSecurisePostData("soa");
				$hostmaster = FS::$secMgr->checkAndSecurisePostData("hostmaster");
				
				if(!$soa || !$hostmaster)
				 	return array($zid,-2);
					
				/* 
				* check times (< , >)
				* check soa is hostname, hostmaster is hostname
				*/
				
				if(!FS::$secMgr->isNumeric($refresh) || !FS::$secMgr->isNumeric($retry) || !FS::$secMgr->isNumeric($minimum) || !FS::$secMgr->isNumeric($expire))
					return array($zid,-3);
					
				$zone->setRefresh($refresh);
				$zone->setRetry($retry);
				$zone->setMinimum($minimum);
				$zone->setExpire($expire);
				$zone->setSOA($soa);
				$zone->setHostMaster($hostmaster);
			}
			else {
				$dns1 = FS::$secMgr->checkAndSecurisePostData("dns1");
				$dns2 = FS::$secMgr->checkAndSecurisePostData("dns2");
				
				if(!$dns1 && !$dns2)
					return array($zid,-2);
					
				if(!$dns1 && $dns2)  {
					$dns1 = $dns2;
					$dns2 = NULL;
				}
				
				if(!FS::$secMgr->isIP($dns1) || $dns2 && !FS::$secMgr->isIP($dns2))
					return -2;
					
				$zone->setDNS1($dns1);
				$zone->setDNS2($dns2);				
			}
			$zone->Save();
			return $zid;
		}
		
		/*
		* Records
		*/
		
		private function showRecordForm() {
			$zid = FS::$secMgr->checkAndSecuriseGetData("zid");
			if(!$zid) $zid = FS::$secMgr->checkAndSecurisePostData("zid");
			if(!$zid || !FS::$secMgr->isNumeric($zid)) {
				header("Location: index.php");
				return "";	
			}
			$type = FS::$secMgr->checkAndSecuriseGetData("t");
			if(!$type || $type > 5) $type = 1;
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "<div id=\"monoComponent\"><h4>Ajouter un enregistrement</h4>";
			switch($err) {
				case -1: $output .= FS::$iMgr->printError("Erreur fatale, veuillez redémarrer votre session."); break;
				case -2: $output .= FS::$iMgr->printError("Un des champs requis est manquant."); break;
				case -3: $output .= FS::$iMgr->printError("Certains champs ont une valeur non attendue."); break;
				case -4: $output .= FS::$iMgr->printError("Cette entrée existe déjà."); break;
				case 0:
				default:
					break;
			}
			$output .= "<span class=\"required_text\">(*) champ requis</span>";
			$link = new HTTPLink(96);
			$output .= FS::$iMgr->addFormWithReturn($link,"checkRecord()");
			$output .= "<table class=\"standardTable\" width=\"60%\">";
			$output .= FS::$iMgr->addHidden("zid",$zid);
			$output .= FS::$iMgr->addIndexedLine(($type == 5 ? "Hôte" : "Nom")." <span class=\"required_text\">(*)</span>","name","");
			$output .= "<tr><td>Type d'enregistrement  <span class=\"required_text\">(*)</span></td><td><center>";
			$output .= FS::$iMgr->addList("regtype","recordChange('regtype')");
			$output .= FS::$iMgr->addElementToList("A",1, $type == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("AAAA",2, $type == 2 ? true : false);
			$output .= FS::$iMgr->addElementToList("CNAME",3, $type == 3 ? true : false);
			$output .= FS::$iMgr->addElementToList("MX",4, $type == 4 ? true : false);
			$output .= FS::$iMgr->addElementToList("SRV",5, $type == 5 ? true : false);
			$output .= "</select></center></td></tr>";
			switch($type) {
				case 1: $text = "IPv4"; break;
				case 2: $text = "IPv6"; break;
				case 3: $text = "FQDN"; break;
				case 4:	$text = "Hôte"; break; 
				case 5: $text = "Service"; break;
					break;
			}
			if($type == 1)
				$output .= FS::$iMgr->addIndexedIPLine($text." <span class=\"required_text\">(*)</span>","value","");
			else
				$output .= FS::$iMgr->addIndexedLine($text." <span class=\"required_text\">(*)</span>","value","");
			if($type == 4)
				$output .= FS::$iMgr->addIndexedNumericLine("Priorité","prio","1");
			else if($type == 5) {
				$output .= "<tr><td>Protocole <span class=\"required_text\">(*)</span></td><td><center>";
				$output .= FS::$iMgr->addRadioList("srvtype",array("TCP","UDP","TLS","SCTP"),"TCP");
				$output .= FS::$iMgr->addIndexedNumericLine("Port <span class=\"required_text\">(*)</span>","port",0,5,5);
				$output .= FS::$iMgr->addIndexedNumericLine("Priorité","prio",0,3,3);
				$output .= FS::$iMgr->addIndexedNumericLine("Poids","wgt",0,10,10);
				
				$output .= "</center></td></tr>";
			}
			$output .= FS::$iMgr->addTableSubmit("submit","Enregistrer");
			$output .= "</form></table></div>";
			return $output;
		}
		
		public function addRecord() {
			$name = FS::$secMgr->checkAndSecurisePostData("name");
			$type = FS::$secMgr->checkAndSecurisePostData("regtype");
			$value = FS::$secMgr->checkAndSecurisePostData("value");
			$zid = FS::$secMgr->checkAndSecurisePostData("zid");
			
			if(!$zid)
				return array(0,-1);
				
			if(!$name || !$type || !$value)
				return array($zid,-2);
				
			if(!FS::$secMgr->isNumeric($type) || $type < 0 || $type > 5)
				return array($zid,-3);

			switch($type) {
				case 1:
					if(!FS::$secMgr->isIP($value))
						return array($zid,-3);
					break;
				case 2:
					if(!FS::$secMgr->isIPv6($value))
						return array($zid,-3);
					break;
				case 3:
					if(!FS::$secMgr->isDNSAddr($value))
						return array($zid,-3);
					break;
				case 4:
					$priority = FS::$secMgr->checkAndSecurisePostData("prio");
					if($priority == NULL) $priority = 0;
					
					if(!FS::$secMgr->isIP($value) && !FS::$secMgr->isDNSAddr($value) || !FS::$secMgr->isNumeric($priority))
						return array($zid,-3);
					break;
				case 5:
					$priority = FS::$secMgr->checkAndSecurisePostData("prio");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$wgt = FS::$secMgr->checkAndSecurisePostData("wgt");
					$srvtype = FS::$secMgr->checkAndSecurisePostData("srvtype");
					if($priority == NULL) $priority = 0;
					if(!$wgt) $wgt = 0;
					
					if(!$port || !$srvtype)
						return array($zid,-2);
						
					if(!FS::$secMgr->isSocketPort($port) || !FS::$secMgr->isNumeric($wgt) || $srvtype != "TCP" && $srvtype != "UDP" && $srvtype != "TLS" && $srvtype != "SCTP")
						return array($zid,-3);
					break;
			}
			
			$rec = new DNSRecord();
			$rec->setName(strtolower($name));
			$rec->setType($type);
			$rec->setValue(strtolower($value));
			$rec->setZone($zid);
			if($rec->Exists())
				return array($zid,-4);
				
			if($type == 4 || $type == 5) {
				$rec->setPriority($priority);
				if($type == 5) {
					$rec->setServerPort($port);
					$rec->setServerWeight($wgt);
					$rec->setServerProtocol($srvtype);
				}
			}
			$rec->Save();
			
			return $zid;
		}
		
		public function deleteRecord() {
			$zid = FS::$secMgr->checkAndSecuriseGetData("zid");
			$rid = FS::$secMgr->checkAndSecuriseGetData("rid");
			if(!$rid)
				return $zid;
				
			$rec = new DNSRecord();
			$rec->setId($rid);
			$rec->Delete();
			
			return $zid;
		}
	};
?>
