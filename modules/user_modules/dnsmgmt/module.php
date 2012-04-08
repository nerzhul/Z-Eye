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
			$query = FS::$dbMgr->Select("fss_dns_zone_cache","zonename","","zonename");
			while($data = mysql_fetch_array($query)) {
				$formoutput .= FS::$iMgr->addElementTolist($data["zonename"],$data["zonename"],($filter == $data["zonename"] ? true : false));
			}
			
			if(strlen($formoutput) == 0) {
				$output .= FS::$iMgr->printError("Aucune donnée récoltée sur les serveurs DNS !");
				return $output;
			}
			
			$query = FS::$dbMgr->Select("fss_dns_zone_record_cache","zonename,record,rectype,recval",($filter != NULL ? "zonename = '".$filter."'" : ""),"zonename",1);
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
					if($filtr == NULL) header("Location: index.php?mod".$this->mid."");
					else header("Location: index.php?mod=".$this->mid."&f=".$filtr);
					return;
			}
		}
	};
?>