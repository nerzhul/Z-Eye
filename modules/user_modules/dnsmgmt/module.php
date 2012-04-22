<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
	class iDNSManager extends genModule{
		function iDNSManager() { parent::genModule(); }
		public function Load() {
			$output = "";
			$output .= "<div id=\"monoComponent\"><h3>Supervision DNS</h3>";
			$output .= $this->showStats();
			$output .= "</div>";
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$dnsoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= FS::$iMgr->addList("f");
			
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
			
			$query = FS::$dbMgr->Select("fss_dns_zone_cache","zonename","","zonename");
			while($data = mysql_fetch_array($query)) {
				$formoutput .= FS::$iMgr->addElementTolist($data["zonename"],$data["zonename"],($filter == $data["zonename"] ? true : false));
			}
			
			if(strlen($formoutput) == 0) {
				$output .= FS::$iMgr->printError("Aucune donnée récoltée sur les serveurs DNS !");
				return $output;
			}
			
			$rectypef = "";
			if(!$shA || !$shAAAA || !$shNS || !$shCNAME || !$shPTR || !$shSRV || !$shTXT || !$shother) {
				if($filter != NULL) $rectypef .= " AND ";
				$rectypef .= "rectype IN (";
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
				
				$rectype .= ")";
				if($shother) $rectypef .= " AND rectype NOT IN ('A','AAAA','CNAME','NS','PTR','SRV','TXT')";
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
			$output .= $formoutput;
			$output .= "</select>";
			$output .= FS::$iMgr->addCheck("sa",$shA)."A ";
			$output .= FS::$iMgr->addCheck("saaaa",$shAAAA)."AAAA ";
			$output .= FS::$iMgr->addCheck("scname",$shCNAME)."CNAME ";
			$output .= FS::$iMgr->addCheck("sns",$shNS)."NS ";
			$output .= FS::$iMgr->addCheck("ssrv",$shSRV)."SRV ";
			$output .= FS::$iMgr->addCheck("stxt",$shTXT)."TXT ";
			$output .= FS::$iMgr->addCheck("sptr",$shPTR)."PTR ";
			$output .= FS::$iMgr->addCheck("sother",$shother)."Autres ";
			$output .= FS::$iMgr->addSubmit("Filtrer","Filtrer");
			$output .= "</form>";
			if(strlen($dnsoutput) > 0)
				$output .= $dnsoutput."</table><hr>";

			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
					$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
					$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
					$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
					$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
					$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
					$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
					$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
					
					if($filtr == NULL && $shA == NULL && $shAAAA == NULL && $shNS == NULL && $shCNAME == NULL && $shSRV == NULL && $shPTR == NULL && $shTXT == NULL && $shother == NULL) 
						header("Location: index.php?mod".$this->mid."");
					else {
						if($shA == NULL) $shA = 1;
						else if($shA > 0) $shA = 1;
						else $shA = 0;
			
						if($shAAAA == NULL) $shAAAA = 1;
						else if($shAAAA > 0) $shAAAA = 1;
						else $shAAAA = 0;
						
			
						if($shNS == NULL) $shNS = 1;
						else if($shNS > 0) $shNS = 1;
						else $shNS = 0;
						
			
						if($shCNAME == NULL) $shCNAME = 1;
						else if($shCNAME > 0) $shCNAME = 1;
						else $shCNAME = 0;
						
			
						if($shSRV == NULL) $shSRV = 1;
						else if($shSRV > 0) $shSRV = 1;
						else $shSRV = 0;
						
			
						if($shPTR == NULL) $shPTR = 1;
						else if($shPTR > 0) $shPTR = 1;
						else $shPTR = 0;
						
			
						if($shTXT == NULL) $shTXT = 1;
						else if($shTXT > 0) $shTXT = 1;
						else $shTXT = 0;
						
			
						if($shother == NULL) $shother = 1;
						else if($shother > 0) $shother = 1;
						else $shother = 0;
						header("Location: index.php?mod=".$this->mid.($filtr != NULL ? "&f=".$filtr : "")."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother);
					}
					return;
			}
		}
	};
?>