<?php
	/*
        * Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
        * Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
        *
        * This program is free software; you can redistribute it and/or modify
        * it under the terms of the GNU General Public License as published by
        * the Free Software Foundation; either version 2 of the License, or
        * (at your option) any later version.
        *
        * This program is distributed in the hope that it will be useful,
        * but WITHOUT ANY WARRANTY; without even the implied warranty of
        * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
        * GNU General Public License for more details.
        *
        * You should have received a copy of the GNU General Public License
        * along with this program; if not, write to the Free Software
        * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
        */
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iDefault extends genModule{
		function iDefault() { parent::genModule(); }

		public function Load() {
			$output = "";
			$output .= $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = "";
			if(!FS::isAjaxCall()) {
				$output = "<script type=\"text/javascript\">
			        var refreshId = setInterval(function()
        			{
	        		        $('#reports').fadeOut(1500);
		                	$('#reports').load('index.php?mod=".$this->mid."&at=2', function() {
			                       $('#reports').fadeIn(2500);
        			        });
		        	}, 30000);</script>";
				$output .= "<h2>Speed Reporting</h2>";
			}
			$output .= "<div id=\"reports\">";
			$tmpoutput = "<div style=\"width: 100%; display: inline-block;\">".$this->showIcingaReporting()."</div>";
			$tmpoutput .= $this->showNetworkReporting();
			$tmpoutput .= $this->showSecurityReporting();
                        $output .= "<div style=\"width: 100%; display: inline-block;\"><ul class=\"ulform\"><li>";
                        $output .= FS::$iMgr->progress("shealth",$this->totalicinga-$this->hsicinga,$this->totalicinga,"Etat des services")."</li><li>";
                        $output .= FS::$iMgr->progress("nhealth",$this->BWscore,$this->BWtotalscore,"Etat du réseau")."</li><li>";
                        $output .= FS::$iMgr->progress("sechealth",$this->SECscore,$this->SECtotalscore,"Etat de la sécurité")."</li></ul>";
                        $output .= "</div>";
			$output .= $tmpoutput;
			if(!FS::isAjaxCall()) $output .= "</div>";

			return $output;
		}

		private function showIcingaReporting() {
			// report nagios
		        $content = file_get_contents("http://localhost/cgi-bin/icinga/status.cgi");
        		preg_match_all("/<table [^>]*status[^>]*>.*<\/table>/si", $content, $body);
			if(isset($body[0][0])) {
			        $body = $body[0][0];

        			// remove all uneeded content
			       	$body = preg_replace("/<tr[^>]*><td colspan=[^>]*><\/td><\/tr>/si","",$body);
	        		$body = preg_replace("/<img[^>]*>/si","",$body);
			        $body = preg_replace("/<A[^>]*><\/A>/si","",$body);
        			$body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD>\n<\/TR>\n<\/TABLE>/si", "",$body);
			        $body = preg_replace('#<a[^>]*>(.*?)</a>#i',"$1", $body);
        			$body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*statusEven[^>]*>(.*?)<\/TD>\n<\/TR>\n<\/TABLE>#i',"$1",$body);
		        	$body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
		        	$body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><TD[^>]*><\/TD><\/TR>\n<\/TABLE>/si", "",$body);
			        $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><\/TR>\n<\/TABLE>/si", "",$body);
	        		$body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><TD[^>]*><\/TD><TD[^>]*><\/TD>\n<\/TR>\n<\/TABLE>/si", "",$body);
			        $body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
			        $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<\/TR>\n<\/TABLE>/si", "",$body);
	        		$body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
		        	$body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*>\n(.*?)\n<\/TD>\n\n<\/TR>\n<\/TABLE>/si", "$1",$body);
			        $body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*status(.+)[^>]*>(.*?)<\/TD>\n<\/TR>\n<\/TABLE>#i',"$2",$body);
        			$body = preg_replace('#<TABLE[^>]*><TR><TD[^>]*>(.*?)\n<\/TD>\n\n<\/TR><\/TABLE>#i',"$1",$body);

				$body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*status(.+)[^>]*>(.*?)<\/TD><\/TR>\n<\/TABLE>#i',"$2",$body);
				$body = preg_replace('#<TABLE[^>]*><TR><TD[^>]*>(.*?)\n<\/TD>\n\n<\/TR><\/TABLE>#i',"$1",$body);

				// At this time this is the table with status of all services

				$body = preg_replace("#<TR>\n<TH(.+)>\n</TR>#","",$body);
				$totalservices = $hsservices = 0;
				preg_match_all("#<TR>#",$body,$totalservices);
				$this->totalicinga = count($totalservices[0]);

				// report nagios
                	        $content = file_get_contents("http://localhost/cgi-bin/icinga/status.cgi?servicestatustypes=28");
                        	preg_match_all("/<table [^>]*status[^>]*>.*<\/table>/si", $content, $body);
	                        $body = $body[0][0];

        	                // remove all uneeded content
                	        $body = preg_replace("/<tr[^>]*><td colspan=[^>]*><\/td><\/tr>/si","",$body);
                        	$body = preg_replace("/<img[^>]*>/si","",$body);
	                        $body = preg_replace("/<A[^>]*><\/A>/si","",$body);
        	                $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD>\n<\/TR>\n<\/TABLE>/si", "",$body);
                	        $body = preg_replace('#<a[^>]*>(.*?)</a>#i',"$1", $body);
                        	$body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*statusEven[^>]*>(.*?)<\/TD>\n<\/TR>\n<\/TABLE>#i',"$1",$body);
	                        $body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
        	                $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><TD[^>]*><\/TD><\/TR>\n<\/TABLE>/si", "",$body);
                	        $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><\/TR>\n<\/TABLE>/si", "",$body);
                        	$body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*><\/TD><TD[^>]*><\/TD><TD[^>]*><\/TD>\n<\/TR>\n<\/TABLE>/si", "",$body);
	                        $body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
        	                $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<\/TR>\n<\/TABLE>/si", "",$body);
                        	$body = preg_replace('#<TD[^>]*>\n\n<\/TD>#i',"",$body);
		                $body = preg_replace("/<TABLE[^>]*>\n<TR>\n<TD[^>]*>\n(.*?)\n<\/TD>\n\n<\/TR>\n<\/TABLE>/si", "$1",$body);
                	        $body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*status(.+)[^>]*>(.*?)<\/TD>\n<\/TR>\n<\/TABLE>#i',"$2",$body);
                        	$body = preg_replace('#<TABLE[^>]*><TR><TD[^>]*>(.*?)\n<\/TD>\n\n<\/TR><\/TABLE>#i',"$1",$body);

	                        $body = preg_replace('#<TABLE[^>]*>\n<TR>\n<TD[^>]*status(.+)[^>]*>(.*?)<\/TD><\/TR>\n<\/TABLE>#i',"$2",$body);
        	                $body = preg_replace('#<TABLE[^>]*><TR><TD[^>]*>(.*?)\n<\/TD>\n\n<\/TR><\/TABLE>#i',"$1",$body);

				$body = preg_replace("#<TR>\n<TH(.+)>\n</TR>#","",$body);
        			preg_match_all("#<TR>#",$body,$hsservices);
				$this->hsicinga = count($hsservices[0]);
				if(count($hsservices[0]) > 0)
			        	$output = "<h4 style=\"font-size:16px; text-decoration: blink; color: red\">Erreur de services rapportées par Icinga: ".$this->hsicinga."/".$this->totalicinga."</h4>".$body;
				else $output = "";
			}
			else
				$output .= "<h4 style=\"font-size:24px; text-decoration: blink; color: red\">Service de monitoring OFFLINE</h4>";
			return $output;
		}

		private function showNetworkReporting() {
			$output = "";
			$query = FS::$pgdbMgr->Select("z_eye_port_monitor","device,port,climit,wlimit,description");

			$found = 0;
			$pbfound = 0;
			$total = 0;
			$this->BWscore = 0;
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput = "<h4 style=\"font-size:16px; text-decoration: blink; color: red\">Problème de bande passante</h4><table><tr><th>Lien</th><th>Débit Entrant</th><th>Débit Sortant</th></tr>";
				}
				$total++;
				$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$data["device"]."'");
				$pid = FS::$pgdbMgr->GetOneData("z_eye_port_id_cache","pid","device = '".$data["device"]."' AND portname = '".$data["port"]."'");
				$tmpoutput .= "<tr style=\"font-size: 12px;\"><td>".$data["description"]."</td>";
				$incharge = 0;
				$outcharge = 0;
				$mrtgfile = file(dirname(__FILE__)."/../../../datas/rrd/".$dip."_".$pid.".log");
				if($mrtgfile) {
					$res = preg_split("# #",$mrtgfile[1]);
		        	        if(count($res) == 5) {
			        	        $inputbw = $res[1];
                		        	$outputbw = $res[2];
					} else {
						$inputbw = 0;
						$outputbw = 0;
					}
				} else {
					$inputbw = 0;
					$outputbw = 0;
				}
				$incolor = "";
				$outcolor = "";

				if($inputbw > $data["climit"]*1024*1024) {
					$incolor = "red";
					$this->BWscore += 1;
				}
				else if($inputbw > $data["wlimit"]*1024*1024) {
					$incolor = "orange";
					$this->BWscore += 2;
				}
				else if($inputbw != 0)
					$this->BWscore += 5;

				if($inputbw > 1024*1024*1024) {
					$inputbw = round($inputbw/(1024*1024*1024),2). " Gbits";
				}
				else if($inputbw > 1024*1024) {
					$inputbw = round($inputbw/(1024*1024),2). " Mbits";
				}
				else if($inputbw > 1024) {
					$inputbw = round($inputbw/1024,2). " Kbits";
				}
				else if($inputbw == 0) {
					$inputbw = "0 Kbits";
					$incolor = "red";
				}

				if($outputbw > $data["climit"]*1024*1024) {
        	        	        $outcolor = "red";
					$this->BWscore += 1;
				}
	        	        else if($outputbw > $data["wlimit"]*1024*1024) {
        	        	        $outcolor = "orange";
					$this->BWscore += 2;
				}
				else if($outputbw != 0)
					$this->BWscore += 5;

				if($outputbw > 1024*1024*1024) {
        	                	$outputbw = round($outputbw/(1024*1024*1024),2). " Gbits";
				}
        	        	else if($outputbw > 1024*1024) {
                	        	$outputbw = round($outputbw/(1024*1024),2). " Mbits";
				}
        		        else if($outputbw > 1024) {
                		        $outputbw = round($outputbw/1024,2). " Kbits";
				}
				else if($outputbw == 0) {
        	        	        $outputbw = "0 Kbits";
                	        	$outcolor = "red";
	                	}
				if($outcolor == "red" || $outcolor == "orange") {
					$pbfound = 1;
					$tmpoutput .= "<td style=\"background-color: ".$incolor.";\">".$inputbw."</td><td style=\"background-color: ".$outcolor.";\">".$outputbw."</td></tr>";
				}
			}
			if($pbfound) $output .= $tmpoutput;
			$output .= "</table>";
			$this->BWtotalscore = $total*10;
			return $output;
		}

		private function showSecurityReporting() {
			$output = "";
			$this->SECtotalscore = 10000;

			$tmpoutput = "<h4>Attaques des 60 dernières minutes</h4>";
			$atkfound = 0;
			$snortDB = new FSPostgreSQLMgr();
                        $snortDB->setConfig("snort",5432,"localhost","snortuser","snort159");
                        $snortDB->Connect();
                        $query = $snortDB->Select("acid_event","sig_name,ip_src,ip_dst","timestamp > (SELECT NOW() - '60 minute'::interval) AND ip_src <> '0' GROUP BY ip_src,ip_dst,sig_name,timestamp","timestamp",1);
                        $tmpoutput .= "<table><tr><th>Source</th><th>Destination</th><th>Type</th></tr>";

                        $sigarray=array();

			$attacklist=array();
			$scannb = 0;
			$atknb = 0;
                        while($data = pg_fetch_array($query)) {
				if(!$atkfound) $atkfound = 1;
                                if(preg_match("#WEB-ATTACKS#",$data["sig_name"])) {
					if(!isset($attacklist[$data["ip_src"]])) $attacklist[$data["ip_src"]] = array();
					if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
					if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
					else $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					if(!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
                                                $sigarray[$data["ip_src"]]["scan"]=0;
                                                $sigarray[$data["ip_src"]]["atk"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
				}
                                else if(preg_match("#SSH Connection#",$data["sig_name"]) || preg_match("#spp_ssh#",$data["sig_name"]) || preg_match("#Open Port#",$data["sig_name"]) || preg_match("#MISC MS Terminal server#",$data["sig_name"])) {
					if(!isset($attacklist[$data["ip_src"]])) $attacklist[$data["ip_src"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
                                        else $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
                                        if(!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
						$sigarray[$data["ip_src"]]["scan"]=0;
						$sigarray[$data["ip_src"]]["atk"]=1;
					}
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
                                }
                                else if(!preg_match("#ICMP PING NMAP#",$data["sig_name"])) {
					if(!isset($attacklist[$data["ip_src"]])) $attacklist[$data["ip_src"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
                                        else $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					if(!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
                                                $sigarray[$data["ip_src"]]["scan"]=0;
                                                $sigarray[$data["ip_src"]]["atk"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
				}
				else {
					if(!isset($attacklist[$data["ip_src"]])) $attacklist[$data["ip_src"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
                                        if(!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
                                        else $attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					$scannb++;
					if(!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
						$sigarray[$data["ip_src"]]["atk"]=0;
                                                $sigarray[$data["ip_src"]]["scan"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["scan"]++;
				}
                        }

			$menace = 0;
			foreach($sigarray as $key => $value) {
				if($value["scan"] > 30 || $value["atk"] > 25) {
					if($menace == 0) {
						$menace = 1;
						$output .= "<h4 style=\"font-size:16px; text-decoration: blink; color: red\">Menace détectée !</h3>";
					}
					$output .= "<span style=\"font-size:15px;\">Adresse IP: ".long2ip($key)." (Scans ".$value["scan"]." Attaques ".$value["atk"].")</span><br />";
				}
			}
			ksort($attacklist);
			foreach($attacklist as $src => $valsrc) {
				foreach($valsrc as $dst => $valdst) {
					foreach($valdst as $atktype => $value)
						$tmpoutput .= "<tr><td>".long2ip($src)."</td><td>".long2ip($dst)."</td><td>".substr($atktype,0,35).(strlen($atktype) > 35 ? " ..." : "")." (".$value.")</td></tr>";
				}
			}

                        if($atkfound) $output .= $tmpoutput."</table>";
			$this->SECscore = 10000-$scannb-2*$atknb;
			if($this->SECscore < 0) $this->SECscore = 0;
			return $output;
		}

		private $totalicinga;
		private $hsicinga;
		private $BWtotalscore;
		private $BWscore;
		private $SECtotalscore;
		private $SECscore;
	};
?>