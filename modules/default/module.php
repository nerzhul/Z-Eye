<?php
	/*
        * Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/../icinga/icingaBroker.api.php");
	
	if(!class_exists("iDefault")) {
		
	final class iDefault extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lDefault();
			$this->rulesclass = new rDefault($this->loc);
			$this->menu = $this->loc->s("menu-name");
			
			$this->icingaAPI = new icingaBroker();
			$this->BWtotalscore = 0;
		}

		public function Load() {
			FS::$iMgr->setTitle("Speed Reporting");
			return $this->showMain();
		}

		private function showMain() {
			$output = "";
			if (!FS::isAjaxCall()) {
				FS::$iMgr->setURL("");
				FS::$iMgr->js("var refreshId = setInterval(function()
				{
				$.get('index.php?mod=".$this->mid."&at=2', function(data) {
							$('#reports').fadeOut(1500,function() {
						$('#reports').html(data);
								$('#reports').fadeIn(1500);
					});
						});
				}, 20000);");
				$output = FS::$iMgr->h1("Speed Reporting",true);
				$output .= "<div id=\"reports\">";
			}
	
			$alerts = array();

			$icingabuffer = $this->showIcingaReporting();
			$alerts["icinga"] = array("<b>".$this->loc->s("state-srv")."</b> ".
				FS::$iMgr->progress("shealth",$this->totalicinga-$this->hsicinga,$this->totalicinga),$icingabuffer);

			if ($this->hsicinga > 0) {
				$alerts["icinga"][0] .= "<br />".($this->hsicinga)." ".
					$this->loc->s("alert-on")." ".$this->totalicinga." ".$this->loc->s("sensors");
			}

			$netbuffer = $this->showNetworkReporting();
			// Fake score for BW if there is now results
			if ($this->BWtotalscore == -1) {
				$this->BWtotalscore = 100;
				$this->BWscore = 100;
			}
			$alerts["net"] = array("<b>".$this->loc->s("state-net")."</b> ".FS::$iMgr->progress("nhealth",
				$this->BWscore,$this->BWtotalscore),$netbuffer);

			$secbuffer = $this->showSecurityReporting();
			$alerts["sec"] = array("<b>".$this->loc->s("state-security")."</b> ".FS::$iMgr->progress("sechealth",
				$this->SECscore,$this->SECtotalscore),$secbuffer);

			$output .= FS::$iMgr->accordion("alertreport",$alerts);
			if (!FS::isAjaxCall()) $output .= "</div>";

			return $output;
		}

		private function showIcingaReporting() {
			$problems = array();
			$output = "";	

			$iStates = $this->icingaAPI->readStates(array("plugin_output","current_state","current_attempt","max_attempts",
				"state_type","last_time_ok","last_time_up"));

			// Loop hosts
			foreach ($iStates as $host => $hostvalues) {
				// Loop types
				foreach ($hostvalues as $hos => $hosvalues) {
					if ($hos == "servicestatus") {
						// Loop sensors
						foreach ($hosvalues as $sensor => $svalues) {
							$this->totalicinga++;
							if ($svalues["current_state"] > 0) {
								$outstate = "";
								$stylestate = "";
								$timedown = $this->loc->s("Since-icinga-start");

								// Initialize host error array
								if (!isset($problems[$host])) {
									/*
									* Fields:
									* 1: label for accordion
									* 2: accordion buffer for this entry
									* 3: warning count
									* 4: critical count
									*/
									$problems[$host] = array($host,"",0,0);
								}

								if ($svalues["current_state"] == 1) {
									$outstate = $this->loc->s("WARN");
									$stylestate = "color: orange; font-size: 18px;";
									if ($svalues["last_time_ok"])
										$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
									$problems[$host][2]++;
									$this->warnicinga++;
								}
								else if ($svalues["current_state"] == 2) {
									$outstate = $this->loc->s("CRITICAL");
									$stylestate = "color: red; font-size: 20px;";
									if ($svalues["last_time_ok"])
										$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);

									$problems[$host][3]++;
									$this->criticinga++;
								}
									
								$this->hsicinga++;
								$problems[$host][1] .= "<tr><td>".$sensor."</td><td style=\"".$stylestate."\">".$outstate.
	                                "</td><td>".$timedown."</td><td>".$svalues["plugin_output"]."</td></tr>"; 
										
							}
						}
					}
					else if ($hos == "hoststatus") {
						$this->totalicinga++;
						if ($hosvalues["current_state"] > 0) {
							$this->hsicinga++;
							$outstate = "";
							$stylestate = "";
							$timedown = $this->loc->s("Since-icinga-start");

							// Initialize host error array
							if (!isset($problems[$host])) {
								/*
								* Fields:
								* 1: label for accordion
								* 2: accordion buffer for this entry
								* 3: warning count
								* 4: critical count
								*/
								$problems[$host] = array($host,"",0,0);
							}

							if ($hosvalues["current_state"] == 1) {
								$outstate = $this->loc->s("DOWN");
								$stylestate = "color: red; font-size: 20px;";
								if ($hosvalues["last_time_up"])
									$timedown = FSTimeMgr::timeSince($hosvalues["last_time_up"]);

								$problems[$host][3]++;
								$this->criticinga++;
							}
							$problems[$host][1] .= "<tr><td>".$this->loc->s("Availability")."</td><td style=\"".$stylestate."\">".$outstate.
	                            "</td><td>".$timedown."</td><td>".$hosvalues["plugin_output"]."</td></tr>"; 
						}
					}
				}
			}

			if ($this->hsicinga > 0) {
				foreach ($problems as $key => $values) {
					// Rewrite label
					$problems[$key][0] .= ": ".($problems[$key][2]+$problems[$key][3])." ".$this->loc->s("alert-s")." (";

					if ($problems[$key][2] > 0) {
						$problems[$key][0] .= $problems[$key][2]." ".$this->loc->s("warning-s");
					}

					if ($problems[$key][3] > 0) {
						if ($problems[$key][2] > 0) {
							$problems[$key][0] .= " / ";
						}
						$problems[$key][0] .= $problems[$key][3]." ".$this->loc->s("critical-s");
					}
					$problems[$key][0] .= ")";

					// Write proper table
					$problems[$key][1] = "<table>".$problems[$key][1]."</table>";
				}

				$output .= FS::$iMgr->accordion("icingapb",$problems)."</table>";		

				$js = "";

				if (($this->hsicinga / $this->totalicinga) > 15.0 || $this->criticinga > 0) {
					$js = 	"$('#accicingah3').css('background-color','#4A0000');".
						"$('#accicingah3').css('background-image','linear-gradient(#4A0000, #8A0000)');".
						"$('#accicingah3').css('background-image','-webkit-linear-gradient(#4A0000, #8A0000)');";
				}
				else {
					$js = 	"$('#accicingah3').css('background-color','#ff8e00');".
						"$('#accicingah3').css('background-image','linear-gradient(#ff4e00, #ff8e00)');".
						"$('#accicingah3').css('background-image','-webkit-linear-gradient(#ff4e00, #ff8e00)');";
				}

				FS::$iMgr->js($js);
			}
			else {
				$js = 	"$('#accicingah3').css('background-color','#222');".
					"$('#accicingah3').css('background-image','linear-gradient(#000, #333)');".
					"$('#accicingah3').css('background-image','-webkit-linear-gradient(#000, #333)');";
				FS::$iMgr->js($js);
			}
				
			return $output;
		}

		private function showNetworkReporting() {
			$output = "";
			$found = 0;
			$pbfound = 0;
			$total = 0;
			$this->BWscore = 0;

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."port_monitor","device,port,climit,wlimit,description");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = 1;
					$tmpoutput = "<h4 style=\"font-size:16px; text-decoration: blink; color: red\">".
						$this->loc->s("err-net")."</h4><table><tr><th>".$this->loc->s("Link")."</th><th>".
						$this->loc->s("inc-bw")."</th><th>".$this->loc->s("out-bw")."</th></tr>";
				}
				$total++;

				$dip = FS::$dbMgr->GetOneData("device","ip","name = '".$data["device"]."'");

				$pid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."port_id_cache","pid","device = '".
					$data["device"]."' AND portname = '".$data["port"]."'");

				$incharge = 0;
				$outcharge = 0;
				$mrtgfile = file(dirname(__FILE__)."/../../datas/rrd/".$dip."_".$pid.".log");
				if ($mrtgfile) {
					$res = preg_split("# #",$mrtgfile[1]);
		        	        if (count($res) == 5) {
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

				if ($inputbw > $data["climit"]*1024*1024) {
					$incolor = "red";
					$this->BWscore += 1;
				}
				else if ($inputbw > $data["wlimit"]*1024*1024) {
					$incolor = "orange";
					$this->BWscore += 2;
				}
				else if ($inputbw != 0)
					$this->BWscore += 5;

				if ($inputbw > 1024*1024*1024) {
					$inputbw = round($inputbw/(1024*1024*1024),2). " Gbits";
				}
				else if ($inputbw > 1024*1024) {
					$inputbw = round($inputbw/(1024*1024),2). " Mbits";
				}
				else if ($inputbw > 1024) {
					$inputbw = round($inputbw/1024,2). " Kbits";
				}
				else if ($inputbw == 0) {
					$inputbw = "0 Kbits";
					$incolor = "red";
				}

				if ($outputbw > $data["climit"]*1024*1024) {
					$outcolor = "red";
					$this->BWscore += 1;
				}
				else if ($outputbw > $data["wlimit"]*1024*1024) {
						$outcolor = "orange";
					$this->BWscore += 2;
				}
				else if ($outputbw != 0)
					$this->BWscore += 5;

				if ($outputbw > 1024*1024*1024) {
						$outputbw = round($outputbw/(1024*1024*1024),2). " Gbits";
				}
				else if ($outputbw > 1024*1024) {
						$outputbw = round($outputbw/(1024*1024),2). " Mbits";
				}
				else if ($outputbw > 1024) {
						$outputbw = round($outputbw/1024,2). " Kbits";
				}
				else if ($outputbw == 0) {
						$outputbw = "0 Kbits";
						$outcolor = "red";
				}
				if ($outcolor == "red" || $outcolor == "orange") {
					$pbfound = 1;
					$tmpoutput .= "<tr style=\"font-size: 12px;\"><td>".$data["description"]."</td>".
						"<td style=\"background-color: ".$incolor.";\">".$inputbw.
						"</td><td style=\"background-color: ".$outcolor.";\">".$outputbw."</td></tr>";
				}
			}
			if ($pbfound) $output .= $tmpoutput;
			$output .= "</table>";
			if ($pbfound) {
				$this->BWtotalscore = $total*10;
				FS::$iMgr->js("$('#accneth3').css('background-color','#4A0000');");
				FS::$iMgr->js("$('#accneth3').css('background-image','linear-gradient(#4A0000, #8A0000)');");
				FS::$iMgr->js("$('#accneth3').css('background-image','-webkit-linear-gradient(#4A0000, #8A0000)');");
			}
			else {
				$this->BWtotalscore = -1;
				FS::$iMgr->js("$('#accneth3').css('background-color','#008A00');");
				FS::$iMgr->js("$('#accneth3').css('background-image','linear-gradient(#004A00, #008A00)');");
				FS::$iMgr->js("$('#accneth3').css('background-image','-webkit-linear-gradient(#004A00, #008A00)');");
			}
			return $output;
		}

		private function showSecurityReporting() {
			$output = "";
			$this->SECtotalscore = 10000;

			$tmpoutput = FS::$iMgr->h4("err-security");
			$atkfound = 0;
			
			// Load snort keys for db config
			$dbname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbname'");
			if ($dbname == "") $dbname = "snort";
			$dbhost = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbhost'");
			if ($dbhost == "") $dbhost = "localhost";
			$dbuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbuser'");
			if ($dbuser == "") $dbuser = "snort";
			$dbpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbpwd'");
			if ($dbpwd == "") $dbpwd = "snort";
			
			$snortDB = new AbstractSQLMgr();
			if ($snortDB->setConfig("pg",$dbname,5432,$dbhost,$dbuser,$dbpwd) == 0)
				$snortDB->Connect();
			$query = $snortDB->Select("acid_event","sig_name,ip_src,ip_dst","timestamp > (SELECT NOW() - '60 minute'::interval) AND ip_src <> '0'",
				array("group" => "ip_src,ip_dst,sig_name,timestamp","order" => "timestamp","ordersens" => 1));
			$tmpoutput .= "<table><tr><th>Source</th><th>Destination</th><th>Type</th></tr>";

			$sigarray=array();

			$attacklist=array();
			$scannb = 0;
			$atknb = 0;
                        while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$atkfound) $atkfound = 1;
                                if (preg_match("#WEB-ATTACKS#",$data["sig_name"])) {
					if (!isset($attacklist[$data["ip_src"]])) {
						$attacklist[$data["ip_src"]] = array();
					}

					if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
					}

					if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
					}
					else {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					}

					if (!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
                                                $sigarray[$data["ip_src"]]["scan"]=0;
                                                $sigarray[$data["ip_src"]]["atk"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
				}
                                else if (preg_match("#SSH Connection#",$data["sig_name"]) || preg_match("#spp_ssh#",$data["sig_name"]) || 
					preg_match("#Open Port#",$data["sig_name"]) || preg_match("#MISC MS Terminal server#",$data["sig_name"])) {
					if (!isset($attacklist[$data["ip_src"]])) {
						$attacklist[$data["ip_src"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
					}
                                        else {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					}

                                        if (!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
						$sigarray[$data["ip_src"]]["scan"]=0;
						$sigarray[$data["ip_src"]]["atk"]=1;
					}
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
                                }
                                else if (!preg_match("#ICMP PING NMAP#",$data["sig_name"])) {
					if (!isset($attacklist[$data["ip_src"]])) {
						$attacklist[$data["ip_src"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
					}
                                        else {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					}

					if (!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
                                                $sigarray[$data["ip_src"]]["scan"]=0;
                                                $sigarray[$data["ip_src"]]["atk"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["atk"]++;
					$atknb++;
				}
				else {
					if (!isset($attacklist[$data["ip_src"]])) {
						$attacklist[$data["ip_src"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]] = array();
					}

                                        if (!isset($attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]])) {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]] = 1;
					}
                                        else {
						$attacklist[$data["ip_src"]][$data["ip_dst"]][$data["sig_name"]]++;
					}

					$scannb++;
					if (!isset($sigarray[$data["ip_src"]])) {
                                                $sigarray[$data["ip_src"]]=array();
						$sigarray[$data["ip_src"]]["atk"]=0;
                                                $sigarray[$data["ip_src"]]["scan"]=1;
                                        }
                                        else
                                                $sigarray[$data["ip_src"]]["scan"]++;
				}
                        }

			$menace = 0;
			foreach ($sigarray as $key => $value) {
				if ($value["scan"] > 30 || $value["atk"] > 25) {
					if ($menace == 0) {
						$menace = 1;
						$output .= "<h4 style=\"font-size:16px; text-decoration: blink; color: red\">".
							$this->loc->s("err-detect-atk")."</h4>";
					}
					$output .= "<span style=\"font-size:15px;\">".$this->loc->s("ipaddr").": ".long2ip($key).
						" (Scans ".$value["scan"]." ".$this->loc->s("Attack")." ".$value["atk"].")</span><br />";
				}
			}
			ksort($attacklist);
			foreach ($attacklist as $src => $valsrc) {
				foreach ($valsrc as $dst => $valdst) {
					foreach ($valdst as $atktype => $value) {
						$tmpoutput .= "<tr><td>".long2ip($src)."</td><td>".long2ip($dst)."</td><td>".
							substr($atktype,0,35).(strlen($atktype) > 35 ? " ..." : "")." (".$value.")</td></tr>";
					}
				}
			}

                        if ($atkfound) $output .= $tmpoutput."</table>";
			$this->SECscore = 10000-$scannb-2*$atknb;
			if ($this->SECscore < 0) $this->SECscore = 0;
			if ($this->SECscore < 10000) {
				FS::$iMgr->js("$('#accsech3').css('background-color','#4A0000');");
				FS::$iMgr->js("$('#accsech3').css('background-image','linear-gradient(#4A0000, #8A0000)');");
				FS::$iMgr->js("$('#accsech3').css('background-image','-webkit-linear-gradient(#4A0000, #8A0000)');");
			}
			else {
				$this->BWtotalscore = -1;
				FS::$iMgr->js("$('#accsech3').css('background-color','#008A00');");
				FS::$iMgr->js("$('#accsech3').css('background-image','linear-gradient(#004A00, #008A00)');");
				FS::$iMgr->js("$('#accsech3').css('background-image','-webkit-linear-gradient(#004A00, #008A00)');");
			}
			FS::$dbMgr->Connect();
			return $output;
		}

		private $totalicinga;
		private $hsicinga;
		private $warnicinga;
		private $criticinga;

		private $BWtotalscore;
		private $BWscore;
		private $SECtotalscore;
		private $SECscore;

		private $icingaAPI;
	};
	
	}
	
	$module = new iDefault();
?>
