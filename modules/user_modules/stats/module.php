<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../user_objects/rrd.php");
	class iStats extends genModule{
		function iStats() { parent::genModule(); }

		public function Load() {
			$stype = FS::$secMgr->checkAndSecuriseGetData("s");
			if($stype == NULL) $stype = 1;

			$output = "<div id=\"monoComponent\">";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
			$output .= FS::$iMgr->addList("stype");
			$output .= FS::$iMgr->addElementToList("Cartographie Icinga (Nagios)",3, $stype == 3 ? true : false);
			$output .= FS::$iMgr->addElementToList("Débits up/down Switches en cours",4, $stype == 4 ? true : false);
			$output .= "</select>";
			$output .= FS::$iMgr->addSubmit("Aller","Aller")."</form><br />";
			switch($stype) {
				case 3: $output .= $this->loadNagiosMap(); break;
				case 4: $output .= $this->loadWeathermaps(); break;
				default: $output .= $this->loadWeathermaps(); break;
			}
			$output .= "</div>";
			return $output;
		}

		private function loadWeathermaps() {
			$output = "<center><h4>Récapitulatif des bandes passantes physiques</h4>";

			global $rrdtool;
			$rrdtool="/usr/bin/rrdtool";
			$rrdstep=300;
			$filename=dirname(__FILE__)."/../../central/weathermap.conf";

			if (! extension_loaded('gd')) {
				$output .= "PHP GD module is needed for Weathermap4RRD.";
				return;
			}

			global	$link,$nodea,$nodeb,$displayvalue;
			global	$bandwidth,$maxbytesin,$maxbytesout;
			global	$target,$targetin,$targetout,$forcemrtg,$inpos,$outpos,$unit,$coef;
			if (! file_exists($filename)) {
				$output .= "file $filename not found. You should check that $filename file exists.";
				return;
			} else {
				$lines=file($filename);
				$autoscale=0;
				foreach ($lines as $line_num => $line) {

					if (preg_match("/^\s*\bNODE\b\s+(\S+)/i",$line,$out)) {
						$node=$out[1];
					}

					if (preg_match("/^\s*\bIP\b\s+(\d+.\d+.\d+.\d+)/i",$line,$out)) {
						$ip[$node]=$out[1];
					}

					if (preg_match("/^\s*\bLINK\b\s+(\S+)/i",$line,$out)) {
						$link=$out[1];
					}

					if (preg_match("/^\s*\bTARGETIN\b\s+(\S+)/i",$line,$out)) {
						$targetin[$link]=$out[1];
						$target[$link]=$out[1];
					}

					if (preg_match("/^\s*\bTARGETOUT\b\s+(\S+)/i",$line,$out)) {
						$targetout[$link]=$out[1];
					}

					if (preg_match("/^\s*\bTARGET\b\s+(\S+)/i",$line,$out)) {
						$target[$link]=$out[1];
						$targetin[$link]=$target[$link];
						$targetout[$link]=$target[$link];
					}

					if (preg_match("/^\s*\bFORCEMRTG\b\s+(\d+)/i",$line,$out)) {
						$forcemrtg[$link]=$out[1];
					}

					if (preg_match("/^\s*\bBANDWIDTH\b\s+(\d+)/i",$line,$out)) {
						$bandwidth[$link]=$out[1]; # Read in Kbits
						$maxbytesin[$link]=$bandwidth[$link]*1000/8;
						$maxbytesout[$link]=$maxbytesin[$link];
					}

					if (preg_match("/^\s*\bBANDWIDTH\b\s+(\d+)\s+(\d+)/i",$line,$out)) {
						$bandwidthin[$link]=$out[1]; # Read in Kbits
						$bandwidthout[$link]=$out[2]; # Read in Kbits
						$maxbytesin[$link]=$bandwidthin[$link]*1000/8;
						$maxbytesout[$link]=$bandwidthout[$link]*1000/8;
					}

					if (preg_match("/^\s*\bDISPLAYVALUE\b\s+(\d+)/i",$line,$out)) {
						$displayvalue[$link]=$out[1];
					}

					if (preg_match("/^\s*\bUNIT\b\s+(\S+)/i",$line,$out)) {
						$unit[$link]=$out[1];
						if ( $unit[$link] == "Mbits" ) {
							$coef[$link]=1000*1000/8;
						}
						if ( $unit[$link] == "Kbits" ) {
							$coef[$link]=1000/8;
						}
						if ( $unit[$link] == "bits" ) {
							$coef[$link]=1/8;
						}
						if ( $unit[$link] == "Mbytes" ) {
							$coef[$link]=1024*1024;
						}
						if ( $unit[$link] == "Kbytes" ) {
							$coef[$link]=1024;
						}
						if ( $unit[$link] == "bytes" ) {
							$coef[$link]=1;
						}
					}

					if (preg_match("/^\s*\bINPOS\b\s+(\d+)/i",$line,$out)) {
						$inpos[$link]=$out[1];
					}

					if (preg_match("/^\s*\bOUTPOS\b\s+(\d+)/i",$line,$out)) {
						$outpos[$link]=$out[1]; 
					}

					if (preg_match("/^\s*\bNODES\b\s+(\S+)\s+(\S+)/i",$line,$out)) {
						$nodea[$link]=$out[1];
						$nodeb[$link]=$out[2];
						$coef[$link]=1; // By default set coef to bytes value
					}
				}
			}

			if (empty($target)) {
				$output .= "No link has been defined. Unable to generate graph.";
			}
			$output .= "<table><tr style=\"font-size: 13px;\"><th>Lien</th><th>Débit Entrant</th><th>Débit Sortant</th></tr>";
			foreach ($target as $link => $i) {
				if (empty($date) ) {
					$start=rrdtool_last($targetin[$link]);
					$unixtime=$start;
					if (rrdtool_getversion() >= 1.2) { 
						$end=$start;
						$start=$start-$rrdstep; 
					} else
						$end=$start;
				} else {
					$start=$date;
					$end=$date;
				}
				$result=rrdtool_function_fetch($targetin[$link],$start,$end);
				$input[$link]=$result["values"][$inpos[$link]-1][0]*$coef[$link];

				if (empty($date) ) {
					$start=rrdtool_last($targetout[$link]);
					$unixtime=$start;
					if (rrdtool_getversion() >= 1.2) {
						$end=$start;
						$start=$start-$rrdstep;
					} else
						$end=$start;
				} else {
					$start=$date;
					$end=$date;
				}
				$result=rrdtool_function_fetch($targetout[$link],$start,$end);
				$outputbp[$link]=$result["values"][$outpos[$link]-1][0]*$coef[$link];
				if ( (int)(($outputbp[$link]/$maxbytesout[$link]+0.005)*100) > 100 ) {
					$outrate=100;
				} else {
					$outrate=(int)(($outputbp[$link]/$maxbytesout[$link]+0.005)*100);
				}

				if ( (int)(($input[$link]/$maxbytesin[$link]+0.005)*100) > 100 ) {
					$inrate=100;
				} else {
					$inrate=(int)(($input[$link]/$maxbytesin[$link]+0.005)*100);
				}

				if($outputbp[$link] != 0 && $outrate == 0)  $outrate=1; 
				if($input[$link] != 0 && $inrate == 0) $inrate=1;

				if ($outputbp[$link] >=125000) {
                		    $coefdisplay=8/(1024*1024);
				    $unitdisplay="Mbits";
                		} else {
		                    $coefdisplay=8/1024;
                		    $unitdisplay="Kbits";
		                }
                		$todisplay=round($outputbp[$link]*$coefdisplay,1). "$unitdisplay";
				$outputrate=($outputbp[$link]*8/(1024*1024*1024))." ";
				if ($input[$link] >=125000) {
		                     $coefdisplay=8/(1024*1024);
                		     $unitdisplay="Mbits";
		                } else {
                		     $coefdisplay=8/1024;
		                     $unitdisplay="Kbits";
                		}
		                $todisplay2=round($input[$link]*$coefdisplay,1). "$unitdisplay";
				$inputrate=($input[$link]*8/(1024*1024*1024))." ";
				$link=preg_replace("#\-#"," - ",$link);
				$output .= "<tr style=\"font-size: 13px;\" align=\"center\"><td>$link</td>";

				if($inputrate < 0.000001) // < 1Ko
					$output .= "<td style=\"background-color: red;\">$todisplay2</td>";
				else if($inputrate > 0.0025)
					$output .= "<td style=\"background-color: cyan;\">$todisplay2</td>"; 
				else if($inputrate > 0.0055)
					$output .= "<td style=\"background-color: yellow;\">$todisplay2</td>"; 
				else if($inputrate > 0.0070)
					$output .= "<td style=\"background-color: orange;\">$todisplay2</td>"; 
				else if($inputrate > 0.0085)
					$output .= "<td style=\"background-color: red;\">$todisplay2</td>";
				else
					$output .= "<td>$todisplay2</td>";

				if($outputrate < 0.000001) // < 1Ko
					$output .= "<td style=\"background-color: red;\">$todisplay</td>"; 
				else if($outputrate > 0.0025)
		                        $output .= "<td style=\"background-color: cyan;\">$todisplay</td>"; 
                		else if($outputrate > 0.0055)
		                        $output .= "<td style=\"background-color: yellow;\">$todisplay</td>"; 
                		else if($outputrate > 0.0070)
		                        $output .= "<td style=\"background-color: orange;\">$todisplay</td>"; 
                		else if($outputrate > 0.0085)
		                        $output .= "<td style=\"background-color: red;\">$todisplay</td>"; 
                		else
		                        $output .= "<td>$todisplay</td>";
				$output .= "</tr>";
			}
			$output .= "</table>";

			$output .= "<h4>Carte par pile de switches</h4>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/weathermap/weathermap.png",1032,804);
			$output .= "<h4>Carte par réseaux</h4>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/weathermap/weathermapvlan.png",1032,804);
			$output .= "</center>";
			return $output;
		}

		private function loadNagiosMap() {
			$output = "<center>";
			$output .= FS::$iMgr->addImage("http://demeter.srv.iogs/cgi-bin/icinga/statusmap.cgi?host=all&createimage&canvas_x=0&canvas_y=0&canvas_width=770&canvas_height=794&max_width=0&max_height=0&layout=5&layermode=exclude",770,794);
			$output .= "</center>";
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 2:
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					if($stype == NULL) $stype = 1;
					header("Location: index.php?mod=".$this->mid."&s=".$stype);
					return;
				default: break;
			}
		}
	};
?>
