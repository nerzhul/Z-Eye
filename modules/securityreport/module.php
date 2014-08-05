<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	require_once(dirname(__FILE__)."/rules.php");

	if(!class_exists("iSecReport")) {

	final class iSecReport extends FSModule {
		function __construct() {
			parent::__construct();
			$this->modulename = "securityreport";
			$this->rulesclass = new rSecurityReport();
			
			$this->menu = _("Supervision");
			$this->menutitle = _("Security reports");
		}

		public function Load() {
			FS::$iMgr->setTitle(_("title-attack-report"));

			// Load snort keys for db config
			$dbname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbname'");
			if ($dbname == "") $dbname = "snort";
			$dbhost = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbhost'");
			if ($dbhost == "") $dbhost = "localhost";
			$dbuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbuser'");
			if ($dbuser == "") $dbuser = "snort";
			$dbpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbpwd'");
			if ($dbpwd == "") $dbpwd = "snort";

			$this->snortDB = new AbstractSQLMgr();
			$this->snortDB->setConfig("pg",$dbname,5432,$dbhost,$dbuser,$dbpwd);
			$this->snortDB->Connect();
			$output = "";
			if (!FS::isAjaxCall())
				$output .= FS::$iMgr->h1("title-attack-report");
			$output .= $this->loadAttackGraph();
			FS::$dbMgr->Connect();
			return $output;
		}

		private function loadAttackGraph() {
			$output = "";
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$ech = FS::$secMgr->checkAndSecuriseGetData("ech");
			if ($ech == NULL) $ech = 7;

			$ec = FS::$secMgr->checkAndSecuriseGetData("ec");
			if (!FS::$secMgr->isNumeric($ec)) $ec = 365;
			if ($ec == NULL) $ec = 365;

			$shscan = FS::$secMgr->checkAndSecuriseGetData("sc");
			if ($shscan == NULL) $shscan = true;
			else if ($shscan > 0) $shscan = true;
			else $shscan = false;

			$shtse = FS::$secMgr->checkAndSecuriseGetData("tse");
			if ($shtse == NULL) $shtse = true;
			else if ($shtse > 0) $shtse = true;
			else $shtse = false;

			$shssh = FS::$secMgr->checkAndSecuriseGetData("ssh");
			if ($shssh == NULL) $shssh = true;
			else if ($shssh > 0) $shssh = true;
			else $shssh = false;

			$topmax = FS::$secMgr->checkAndSecuriseGetData("max");
			if ($topmax == NULL || !FS::$secMgr->isNumeric($topmax) || $topmax < 1) $topmax = 10;

			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->tabPan(array(
					array(1,"mod=".$this->mid."&max=".$topmax."&ec=".$ec."&ech=".$ech."&ssh=".($shssh ? 1 : 0)."&tse=".($shtse ? 1 : 0)."&scan=".($shscan ? 1 : 0),_("General")),
					array(5,"mod=".$this->mid."&max=".$topmax,_("Last-logs")),
					array(2,"mod=".$this->mid."&max=".$topmax,_("Scans")),
					array(3,"mod=".$this->mid."&max=".$topmax,_("TSE")),
					array(4,"mod=".$this->mid."&max=".$topmax,_("SSH"))),$showmodule);
			}
			else {
				if (!$showmodule || $showmodule == 1) {
					FS::$iMgr->setURL("sh=1");
					$output .= FS::$iMgr->h3("title-z-eye-report");
					$totalips = $this->snortDB->Count(PGDbConfig::getDbPrefix()."collected_ips","ip");
					$totalscan = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","scans");
					$totaltse = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","tse");
					$totalssh = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","ssh");
					$totalatk = $totalscan + $totaltse + $totalssh;

					$output .= _("total-atk").": ".$totalatk."<br />";
					$output .= _("nb-ip-atk").": ".$totalips."<br />";
					$output .= _("nb-scan-port").": ".$totalscan."<br />";
					$output .= _("nb-tse-atk").": ".$totaltse."<br />";
					$output .= _("nb-ssh-atk").": ".$totalssh."<br /><hr>";

					$output .= FS::$iMgr->form("?mod=".$this->mid."&act=1");
					$output .= FS::$iMgr->hidden("mod",$this->mid);
					$output .= "Pas: ".FS::$iMgr->numInput("ech",$ech,array("size" => 2, "length" => 2))." jours <br />";
					$output .= "Echelle: ".FS::$iMgr->numInput("ec",$ec,array("size" => 3, "length" => 3))." jours <br />";

					$output .= FS::$iMgr->submit("",_("Update"))."<br />";
					$output .= "</form>";
					$output .= "<div id=\"atkst\"></div>";
					$year = date("Y");
					$month = date("m");
					$day = date("d");

					$sql_date = $year."-".$month."-".$day." 00:00:00";
					$fields = "";

					$fields .= ",scans";
					$fields .= ",tse";
					$fields .= ",ssh";

					$sqlcalc = "(SELECT '".$sql_date."'::timestamp - '".($ec+15)." day'::interval)";
					$sql = "select atkdate".$fields." from z_eye_attack_stats where atkdate > ".$sqlcalc." ORDER BY atkdate";
					$query = $this->snortDB->Select("z_eye_attack_stats","atkdate".$fields,"atkdate > ".$sqlcalc,array("order" => "atkdate"));
					$labels = $scans = $tse = $ssh = "[";
					$cursor = 0;
					$temp1 = $temp2 = $temp3 = $temp4 = 0;
					while ($data = $this->snortDB->Fetch($query)) {
						if ($cursor != $ech || $ech == 1) {
							$cursor++;
							$temp1 = substr($data["atkdate"],8,2)."/".substr($data["atkdate"],5,2);
							$temp2 += $data["scans"];
							$temp3 += $data["tse"];
							$temp4 += $data["ssh"];
						}

						if ($cursor == $ech) {
							$labels .= "'".$temp1."',";
							$scans .= $temp2.",";
							$tse .= $temp3.",";
							$ssh .= $temp4.",";
							$cursor = $temp1 = $temp2 = $temp3 = $temp4 = 0;
						}
					}

					$labels .= "]";
					$scans .= "]";
					$tse .= "]";
					$ssh .= "]";

					$output .= FS::$iMgr->js("(function($){ var hchart;
							hchart = new Highcharts.Chart({
							chart: { renderTo: 'atkst', type: 'line' },
							title: { text: 'Graphique d\'attaques SNORT' },
							xAxis: { categories: ".$labels." },
							yAxis: { title: { text: 'Nombre d\'attaques' } },
							legend: { layout: 'vertical', align: 'right', verticalAlign: 'top',
									x: -10, y: 100 },
							series: [ { name: 'Scans', data: ".$scans." },
									{ name: '"._("TSE-atk")."', data: ".$tse." },
									{ name: '"._("SSH-atk")."', data: ".$ssh." }]
							});
						})(jQuery);");
				}
				else if ($showmodule == 2) {
					FS::$iMgr->setURL("sh=2");
					$found = 0;

					$output .= FS::$iMgr->form("?mod=".$this->mid."&act=2");
					$output .= _("Maximum").": ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",_("Update"))."<br />";
					$output .= "</form>";

					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." ("._("Scans").")",true)."<table><tr><th>"._("IP-addr")."</th><th>"._("Last-visit")."</th><th>"._("Action-nb")."</th></tr>";

					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,scans","",array("order" => "scans","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["scans"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";

					$found = 0;
					$tmpoutput = FS::$iMgr->h3(_("The")." ".$topmax." "._("violent-days"),true)."<table><tr><th>Date</th><th>"._("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,scans","",array("order" => "scans","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["scans"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";
				}
				else if ($showmodule == 3) {
					FS::$iMgr->setURL("sh=3");
					$found = 0;

					$output .= FS::$iMgr->form("?mod=".$this->mid."&act=3");
					$output .= "Maximum: ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",_("Update"))."<br />";
					$output .= "</form>";

					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." ("._("TSE-atk").")",true)."<table><tr><th>"._("IP-addr")."</th><th>"._("Last-visit")."</th><th>"._("Action-nb")."</th></tr>";

					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,tse","",array("order" => "tse","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["tse"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";

					$found = 0;
					$tmpoutput = FS::$iMgr->h3(_("The")." ".$topmax." "._("violent-days"),true)."<table><tr><th>"._("Date")."<th>"._("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,tse","",array("order" => "tse","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["tse"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";
				}
				else if ($showmodule == 4) {
					FS::$iMgr->setURL("sh=4");
					$found = 0;

					$output .= FS::$iMgr->form("?mod=".$this->mid."&act=4");
					$output .= _("Maximum").": ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",_("Update"))."<br />";
					$output .= "</form>";

					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." ("._("SSH-atk").")",true)."<table><tr><th>"._("IP-addr")."</th><th>"._("Last-visit")."</th><th>"._("Action-nb")."</th></tr>";

					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,ssh","",array("order" => "ssh","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["ssh"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";

					$found = 0;
					$tmpoutput = FS::$iMgr->h3(_("The")." ".$topmax." "._("violent-days"),true)."<table><tr><th>"._("Date")."</th><th>"._("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,ssh","",array("order" => "ssh","ordersens" => 1,"limit" => $topmax));
					while ($data = $this->snortDB->Fetch($query)) {
						if ($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["ssh"]."</td></tr>";
					}
					if ($found)
						$output .= $tmpoutput."</table>";
				}
				else if ($showmodule == 5) {
					FS::$iMgr->setURL("sh=5");
					$found = false;
					$output .= FS::$iMgr->h3("last-100");
					$query = $this->snortDB->Select("acid_event","sig_name,timestamp,ip_src,ip_dst,ip_proto,layer4_sport,layer4_dport","",array("order" => "timestamp","ordersens" => 1,"limit" => 100));
					while ($data = $this->snortDB->Fetch($query)) {
						if (!$found) {
							$found = true;
							$output .= "<table><tr><th>"._("Date")."</th><th>"._("Source")."</th><th>"._("Destination")."</th><th>"._("Alert")."</th></tr>";
						}
						$output .= "<tr><td>".$data["timestamp"]."</td><td>".long2ip($data["ip_src"]).":".$data["layer4_sport"]."</td><td>".long2ip($data["ip_dst"]).":".$data["layer4_dport"].
							"</td><td>".$data["sig_name"]."</td></tr>";
					}
					if ($found) {
						$output .= "</table>";
					}
					else {
						$output .= FS::$iMgr->printError("No-alert-found");
					}
				}
			}
			return $output;
		}

		public function loadFooterPlugin() {
			// Only users with icinga read right can use this module
			if (FS::$sessMgr->hasRight("read")) {
				$pluginTitle = _("Security");
				$pluginContent = "";

				// Load snort keys for db config
				$dbname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbname'");
				if ($dbname == "") {
					$dbname = "snort";
				}
				$dbhost = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbhost'");
				if ($dbhost == "") {
					$dbhost = "localhost";
				}
				$dbuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbuser'");
				if ($dbuser == "") {
					$dbuser = "snort";
				}
				$dbpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbpwd'");
				if ($dbpwd == "") {
					$dbpwd = "snort";
				}

				$snortDB = new AbstractSQLMgr();
				if ($snortDB->setConfig("pg",$dbname,5432,$dbhost,$dbuser,$dbpwd) == 0) {
					$snortDB->Connect();
				}
				$query = $snortDB->Select("acid_event","sig_name,ip_src,ip_dst","timestamp > (SELECT NOW() - '60 minute'::interval) AND ip_src <> '0'",
					array("group" => "ip_src,ip_dst,sig_name,timestamp","order" => "timestamp","ordersens" => 1));

				$scannb = 0;
				$atknb = 0;
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (preg_match("#WEB-ATTACKS#",$data["sig_name"])) {
						$atknb++;
					}
					else if (preg_match("#SSH Connection#",$data["sig_name"]) || preg_match("#spp_ssh#",$data["sig_name"]) ||
						preg_match("#Open Port#",$data["sig_name"]) || preg_match("#MISC MS Terminal server#",$data["sig_name"])) {
						$atknb++;
					}
					else if (!preg_match("#ICMP PING NMAP#",$data["sig_name"])) {
						$atknb++;
					}
					else {
						$scannb++;
					}
				}
				$securityScore = round((10000-$scannb-2*$atknb)/100);
				FS::$dbMgr->Connect();

				// If score < 50%: critical
				if ($securityScore < 50) {
					$pluginTitle = sprintf("%s: %s%% %s",
						_("Security"),
						$securityScore,
						FS::$iMgr->img("/styles/images/monitor-crit.png",15,15)
					);
				}
				// If score < 93%: warn
				else if ($securityScore < 93) {
					$pluginTitle = sprintf("%s: %s%% %s",
						_("Security"),
						$securityScore,
						FS::$iMgr->img("/styles/images/monitor-warn.png",15,15)
					);
				}
				//
				else {
					$pluginTitle = sprintf("%s: %s%% %s",
						_("Security"),
						$securityScore,
						FS::$iMgr->img("/styles/images/monitor-ok.png",15,15)
					);
				}

				$this->registerFooterPlugin($pluginTitle, $pluginContent);
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$ech = FS::$secMgr->checkAndSecurisePostData("ech");
					$ec = FS::$secMgr->checkAndSecurisePostData("ec");
					FS::$iMgr->redir("mod=".$this->mid."&sh=1&ech=".$ech."&ec=".$ec);
					break;
				case 2: case 3: case 4:
					$topmax = FS::$secMgr->checkAndSecurisePostData("max");
					FS::$iMgr->redir("mod=".$this->mid."&sh=".$act."&max=".$topmax."");
					break;
				default: break;
			}
		}

		private $snortDB;
	};

	}

	$module = new iSecReport();
?>
