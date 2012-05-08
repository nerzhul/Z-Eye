<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iSecReport extends genModule{
		function iSecReport() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Rapports de Sécurité</h3>";
			$output .= $this->loadAttackGraph();
			$output .= "</div>";
			return $output;
		}
		
		private function loadAttackGraph() {
			$output = "";
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$ech = FS::$secMgr->checkAndSecuriseGetData("ech");
			if($ech == NULL) $ech = 7;
			$ec = FS::$secMgr->checkAndSecuriseGetData("ec");
			if(!FS::$secMgr->isNumeric($ec)) $ec = 365;
			
			if($ec == NULL) $ec = 365;
			$shscan = FS::$secMgr->checkAndSecuriseGetData("sc");
			if($shscan == NULL) $shscan = true;
			else if($shscan > 0) $shscan = true;
			else $shscan = false;
			$shtse = FS::$secMgr->checkAndSecuriseGetData("tse");
			if($shtse == NULL) $shtse = true;
			else if($shtse > 0) $shtse = true;
			else $shtse = false;
			$shssh = FS::$secMgr->checkAndSecuriseGetData("ssh");
			if($shssh == NULL) $shssh = true;
			else if($shssh > 0) $shssh = true;
			else $shssh = false;
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=1".(!$shscan ? "&sc=0" : "").(!$shtse ? "&tse=0" : "").(!$shssh ? "&ssh=0" : "")."&ech=".$ech."&ec=".$ec."\">Graphique d'attaques</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			}
			else {
				if(!$showmodule || $showmodule == 1) {
					$output .= "<form action=\"index.php?mod=".$this->mid."&act=1\" method=\"post\">";
					
					
					$output .= FS::$iMgr->addHidden("mod",$this->mid);
					$output .= "Pas: ".FS::$iMgr->addNumericInput("ech",$ech,2,2)." jours <br />";
					$output .= "Echelle: ".FS::$iMgr->addNumericInput("ec",$ec,3,3)." jours <br />";
					$output .= "Filtres: ";
					$output .= FS::$iMgr->addCheck("sc",$shscan)."Scans ";
					$output .= FS::$iMgr->addCheck("tse",$shtse)."TSE ";
					$output .= FS::$iMgr->addCheck("ssh",$shssh)."SSH ";
		
					$output .= FS::$iMgr->addSubmit("Mise à jour","Mise à jour")."<br />";
					$output .= "</form><canvas id=\"atkst\" height=\"450\" width=\"1175\"></canvas>";
		
					$year = date("Y");
					$month = date("m");
					$day = date("d");
		
					$sql_date = $year."-".$month."-".$day." 00:00:00";
					$fields = "";
					
					if($shscan) $fields .= ",scans";
					if($shtse) $fields .= ",tse";
					if($shssh) $fields .= ",ssh";
					
					$sql = "select atkdate".$fields." from attack_stats where atkdate > (SELECT DATE_SUB('".$sql_date."', INTERVAL ".$ec." DAY))";
					mysql_select_db("snort");
					$query = mysql_query($sql);
					$labels = $scans = $tse = $ssh = "[";
					$cursor = 0;
					$subline = false;
					$temp1 = $temp2 = $temp3 = $temp4 = "";
					while($data = mysql_fetch_array($query)) {
							if($cursor == $ech) {
									if($subline)
											$labels .= "'\\r\\n".$temp1."',";
									else
											$labels .= "'".$temp1."',";
									if($shscan) $scans .= $temp2.",";
									if($shtse) $tse .= $temp3.",";
									if($shssh) $ssh .= $temp4.",";
									$cursor = $temp1 = $temp2 = $temp3 = $temp4 = 0;
									$subline = ($subline ? false : true);
							} else {
									$cursor++;
									$temp1 = substr($data["atkdate"],8,2)."/".substr($data["atkdate"],5,2);
									if($shscan) $temp2 += $data["scans"];
									if($shtse) $temp3 += $data["tse"];
									if($shssh) $temp4 += $data["ssh"];
							}
					}
		
					$labels .= "]";
					if($shscan) $scans .= "]";
					if($shtse) $tse .= "]";
					if($shssh) $ssh .= "]";
					$output .= "<script type=\"text/javascript\">var data = ";
		
					$output .= "[";
					if($shscan) $output .= $scans;
					if($shtse) {
						if($shscan) $output .= ",";
						$output .= $tse;
					}
					if($shssh) {
						if($shscan || $shtse) $output .= ",";
						$output .= $ssh;
					}
					$output .= "];";
		
					$output .= "var line = new RGraph.Line(\"atkst\", data);";
					$output .= "line.Set('chart.yaxispos', 'right');
					line.Set('chart.hmargin', 15);
					line.Set('chart.tickmarks', 'endcircle');
					line.Set('chart.linewidth', 2);
					line.Set('chart.shadow', true);
					line.Set('chart.gutter.top', 5);
					line.Set('chart.gutter.right', 100);
					line.Set('chart.key', [";
					if($shscan) $output .= "'Scans'";
					if($shtse) {
						if($shscan) $output .= ",";
						$output .= "'Attaques TSE'";
					}
					if($shssh) {
						if($shscan || $shtse) $output .= ",";
						$output .= "'Attaques SSH'";
					}
					$output .= "]);
					line.Set('chart.gutter.bottom', 45); ";
					$output .= "line.Set('chart.labels', ".$labels.");";
					$output .= "line.Draw();</script>";
					mysql_select_db("fssmanager");
				}
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$ech = FS::$secMgr->checkAndSecurisePostData("ech");
					$ec = FS::$secMgr->checkAndSecurisePostData("ec");
					$sc = FS::$secMgr->checkAndSecurisePostData("sc");
					($sc != NULL && $sc == "on") ? $sc = 1 : $sc = 0;
					$tse = FS::$secMgr->checkAndSecurisePostData("tse");
					($tse != NULL && $tse == "on") ? $tse = 1 : $tse = 0;
					$ssh = FS::$secMgr->checkAndSecurisePostData("ssh");
					($ssh != NULL && $ssh == "on") ? $ssh = 1 : $ssh = 0;
					header("Location: index.php?mod=".$this->mid."&sh=1&ech=".$ech."&ec=".$ec."&ssh=".$ssh."&tse=".$tse."&sc=".$sc."");
					break;
				default: break;
			}
		}
	};
?>
