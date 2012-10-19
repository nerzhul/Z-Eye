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
	require_once(dirname(__FILE__)."/locales.php");
	
	class iSnortMgmt extends genModule{
		function iSnortMgmt() { parent::genModule(); $this->loc = new lSnort(); }
		public function Load() {
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("fail-snort-conf-wr")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("fail-cron-wr")); break;
			}
			$output .= $this->showMainConf();
			return $output;
		}
		
		private function showMainConf() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<h3>".$this->loc->s("page-title")."</h3>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("General"),$sh,true);
				$output .= FS::$iMgr->tabPanElmt(10,"index.php?mod=".$this->mid,$this->loc->s("Reports"),$sh);
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid,$this->loc->s("Remote"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,"DNS",$sh);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid,"Mail",$sh);
				$output .= FS::$iMgr->tabPanElmt(7,"index.php?mod=".$this->mid,"FTP",$sh);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid,"HTTP",$sh);
				$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid,"DB",$sh);
				$output .= FS::$iMgr->tabPanElmt(8,"index.php?mod=".$this->mid,"SNMP",$sh);
				$output .= FS::$iMgr->tabPanElmt(9,"index.php?mod=".$this->mid,"SIP",$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
				$output .= "</div>";
			}
			else if(!$sh || $sh == 1) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				// Load snort keys for db config
				$dbname = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dbname'");
				$dbhost = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dbhost'");
				$dbuser = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dbuser'");
				$dbpwd = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dbpwd'");
				$output .= "<table>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("data-storage")."</th></tr>";
				$output .= FS::$iMgr->addIndexedLine($this->loc->s("pg-host",$dbhost),"dbhost");
				$output .= FS::$iMgr->addIndexedLine($this->loc->s("Database",$dbname),"dbname");
				$output .= FS::$iMgr->addIndexedLine($this->loc->s("User",$dbuser),"dbuser");
				$output .= FS::$iMgr->addIndexedLine($this->loc->s("Password",$dbpwd),"dbpwd","",true);
				$output .= "<tr><td>".$this->loc->s("lan-list")."</td><td>";
				$output .= FS::$iMgr->textarea("srvlist","",250,100);

				$output .= "</td></tr>";
				
				$output .= "<tr><td colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</td></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 2) {
				$dnsenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dnsenable'");
				$dnslist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dnslist'");
				if(!$dnsenable) $dnsenable = 0;
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"dnsenable",$dnsenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$output .= "<tr><td>".$this->loc->s("srv-dns")."</td><td>".FS::$iMgr->textarea("dnslist",$dnslist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 3) {
				$smtpenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtpenable'");
				$smtplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtplist'");
				$smtpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtpports'");
				if(!$smtpenable) $smtpenable = 0;
				if(!$smtpports) $smtpports = "25,465,587,691";
				$imapenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imapenable'");
				$imaplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imaplist'");
				$imapports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imapports'");
				if(!$imapenable) $imapenable = 0;
				if(!$imapports) $imapports = "143,993";
				$popenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'popenable'");
				$poplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'poplist'");
				$popports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'popports'");
				if(!$popenable) $popenable = 0;
				if(!$popports) $popports = "109,110,995";
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("en-smtp-sensor"),"ensmtp",$smtpenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-smtp")."</td><td>".FS::$iMgr->textarea("smtplist",$smtplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-smtp")."</td><td>".FS::$iMgr->textarea("smtpports",$smtpports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes IMAP","enimap",$imapenable);
				$output .= "<tr><td>".$this->loc->s("srv-imap")."</td><td>".FS::$iMgr->textarea("imaplist",$imaplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-imap")."</td><td>".FS::$iMgr->textarea("imapports",$imapports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes POP","enpop",$popenable);
				$output .= "<tr><td>".$this->loc->s("srv-pop")."</td><td>".FS::$iMgr->textarea("poplist",$poplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-pop")."</td><td>".FS::$iMgr->textarea("poppports",$popports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			
			}
			else if($sh == 4) {
				$httpenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httpenable'");
				$httplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httplist'");
				$httpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httpports'");
				if(!$httpenable) $httpenable = 0;
				if(!$httpports) $httpports = "80,443";
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"enhttp",$httpenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-http")."</td><td>".FS::$iMgr->textarea("httplist",$httplist,250,100,NULL,$tooltip)."</td></tr>";
				
				$output .= "<tr><td>".$this->loc->s("port-http")."</td><td>".FS::$iMgr->textarea("httpports",$httpports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 5) {
				$sqlenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sqlenable'");
				$sqllist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sqllist'");
				if(!$sqlenable) $sqlenable = 0;
				$oracleenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'oracleenable'");
				$oraclelist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'oraclelist'");
				$oracleports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'oracleports'");
				if(!$oracleenable) $oracleenable = 0;
				if(!$oracleports) $oracleports = "1525,1527,1529,2005";
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"ensql",$sqlenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-sql")."</td><td>".FS::$iMgr->textarea("sqllist",$sqllist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"enoracle",$oracleenable);
				$output .= "<tr><td>".$this->loc->s("sql-oracle")."</td><td>".FS::$iMgr->textarea("oraclelist",$oraclelist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-oracle")."</td><td>".FS::$iMgr->textarea("oracleports",$oracleports,250,100,NULL,$tooltip2)."</td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 6) {
				$telnetenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'telnetenable'");
				$telnetlist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'telnetlist'");
				if(!$telnetenable) $telnetenable = 0;
				$sshenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshenable'");
				$sshlist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshlist'");
				$sshports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshports'");
				if(!$sshenable) $sshenable = 0;
				if(!$sshports) $sshports = "22";
				$tseenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'tseenable'");
				$tselist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'tselist'");
				if(!$tseenable) $tseenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("en-telnet-sensor"),"entelnet",$telnetenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-telnet")."</td><td>".FS::$iMgr->textarea("telnetlist",$telnetlist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("en-ssh-sensor"),"enssh",$sshenable);
				$output .= "<tr><td>".$this->loc->s("srv-ssh")."</td><td>".FS::$iMgr->textarea("sshlist",$sshlist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-ssh")."</td><td>".FS::$iMgr->textarea("sshports",$sshports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("en-tse-sensor"),"entse",$tseenable);
				$output .= "<tr><td>".$this->loc->s("srv-tse")."</td><td>".FS::$iMgr->textarea("tselist",$tselist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 7) {
				$ftpenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftpenable'");
				$ftplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftplist'");
				$ftpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftpports'");
				if(!$ftpenable) $ftpenable = 0;
				if(!$ftpports) $ftpports = "21";
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"enftp",$ftpenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-ftp")."</td><td>".FS::$iMgr->textarea("ftplist",$ftplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-ftp")."</td><td>".FS::$iMgr->textarea("ftpports",$ftpports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 8) {
				$snmpenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'snmpenable'");
				$snmplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'snmplist'");
				if(!$snmpenable) $snmpenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"ensnmp",$snmpenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$output .= "<tr><td>".$this->loc->s("srv-snmp")."</td><td>".FS::$iMgr->textarea("snmplist",$snmplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("",$this->loc->s("Register"))."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 9) {
				$sipenable = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sipenable'");
				$siplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'siplist'");
				$sipports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sipports'");
				if(!$sipenable) $sipenable = 0;
				if(!$sipports) $sipports = "5060,5061";

				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"),"ensip",$sipenable);
				$tooltip = $this->loc->s("tooltip-ipv4");
				$tooltip2 = $this->loc->s("tooltip-port");
				$output .= "<tr><td>".$this->loc->s("srv-sip")."</td><td>".FS::$iMgr->textarea("siplist",$siplist,250,100,NULL,$tooltip)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("port-sip")."</td><td>".FS::$iMgr->textarea("sipports",$sipports,250,100,NULL,$tooltip2)."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->submit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 10) {
				$nightreport = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_nighten'");
				$wereport = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_ween'");
				$nighth = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_nighthour'");
				$nightm = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_nightmin'");
				$nightback = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_nightback'");
				$weh = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_wehour'");
				$wem = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'report_wemin'");
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("title-nightreport")."</th></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"), "nightreport", $nightreport == 1 ? true : false);
				$output .= "<tr><td>".$this->loc->s("sent-hour")."</td><td>".FS::$iMgr->hourlist("hnight","mnight",$nighth,$nightm)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("prev-hour")."</td><td>".FS::$iMgr->addNumericInput("nightintval",$nightback > 0 ? $nigthback : 7,2,2,NULL,$this->loc->s("tooltip-prev-hour"))."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("title-we")."</th></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine($this->loc->s("Activate"), "wereport", $wereport == 1 ? true : false);
				$output .= "<tr><td>".$this->loc->s("sent-hour")."</td><td>".FS::$iMgr->hourlist("hwe","mwe",$weh,$wem)."</td></tr>";
				$output .= FS::$iMgr->addTableSubmit("",$this->loc->s("Register"));
				$output .= "</table>";
			}
			return $output;
		}
		private function writeConfiguration() {
			if(Config::getOS() == "Debian")
				$file = fopen("/etc/snort/snort.z_eye.conf","w");	
			else
				$file = fopen("/usr/local/etc/snort/snort.z_eye.conf","w");
			if(!$file) return 1;

			$homenetworks = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'home_net'");
			if(!$homenetworks) $homenetworks = "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16";
			fwrite($file,"#\n# Snort configuration, generated by Z-Eye (".date('d-m-Y G:i:s').")\n#\n\n");
			fwrite($file,"var HOME_NET [".$homenetworks."]\n");
			fwrite($file,'var EXTERNAL_NET !$HOME_NET'."\n");
			
			fwrite($file,"\n#\n# Global preprocessors\n#\n\npreprocessor normalize_ip4\npreprocessor normalize_tcp: ips ecn stream\npreprocessor normalize_icmp4\n");
			fwrite($file,"preprocessor normalize_ip6\npreprocessor normalize_icmp6\n");
			
			fwrite($file,"preprocessor frag3_global: max_frags 65536\npreprocessor frag3_engine: policy windows detect_anomalies overlap_limit 10 min_fragment_length 100 timeout 180\n");
			
			fwrite($file,"preprocessor stream5_global: track_tcp yes, track_udp yes, track_icmp no, max_tcp 262144, max_udp 131072, max_active_responses 2, min_response_seconds 5\n");
			fwrite($file,"preprocessor stream5_tcp: policy windows, detect_anomalies, require_3whs 180, \\
   overlap_limit 10, small_segments 3 bytes 150, timeout 180, \\
    ports client 21 22 23 25 42 53 79 109 110 111 113 119 135 136 137 139 143 161 445 513 514 587 593 691 1433 1521 2100 3306 6070 6665 6666 6667 6668 6669 7000 8181 32770 32771 32772 32773 32774 32775 32776 32777 32778 32779, \\
    ports both 80 81 311 443 465 563 591 593 636 901 989 992 993 994 995 1220 1414 1830 2301 2381 2809 3128 3702 4343 5250 7907 7001 7145 7510 7802 7777 7779 7801 7900 7901 7902 7903 7904 7905 7906 7908 7909 7910 7911 7912 7913 7914 7915 7916 7917 7918 7919 7920 8000 8008 8014 8028 8080 8088 8118 8123 8180 8243 8280 8800 8888 8899 9080 9090 9091 9443 9999 11371 55555\n");
			fwrite($file,"preprocessor stream5_udp: timeout 180\n");
			
			$dns = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dnsenable'");
			// DNS tab
			if($dns) {
				$dnslist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'dnslist'");
				fwrite($file,"\n#\n# DNS Section\n#\n\nvar DNS_SERVERS [".$dnslist."]\n");
				fwrite($file,'preprocessor dns: ports { 53 } enable_rdata_overflow'."\n");
				fwrite($file,'include $RULE_PATH/dns.rules'."\n");
			}
			
			$smtp = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtpenable'");
			$imap = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imapenable'");
			$pop = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'popenable'");
			// Mail tab
			// SMTP
			if($smtp) {
				$smtplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtplist'");
				$smtpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'smtpports'");
				if(!$smtpports) $smtpports = "25,465,587,691";
				fwrite($file,"\n#\n# SMTP Section\n#\n\nvar SMTP_SERVERS [".$smtplist."]
portvar SMTP_PORTS [".$smtpports."]
preprocessor smtp: ports { ".preg_replace("#[,]#"," ",$smtpports)." } inspection_type stateful b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0 log_mailfrom log_rcptto log_filename log_email_hdrs normalize cmds normalize_cmds { ATRN AUTH BDAT CHUNKING DATA DEBUG EHLO EMAL ESAM ESND ESOM ETRN EVFY EXPN HELO HELP IDENT MAIL NOOP ONEX QUEU QUIT RCPT RSET SAML SEND SOML STARTTLS TICK TIME TURN TURNME VERB VRFY X-ADAT X-DRCP X-ERCP X-EXCH50 X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } max_command_line_len 512 max_header_line_len 1000 max_response_line_len 512 alt_max_command_line_len 260 { MAIL } alt_max_command_line_len 300 { RCPT } alt_max_command_line_len 500 { HELP HELO ETRN EHLO } alt_max_command_line_len 255 { EXPN VRFY ATRN SIZE BDAT DEBUG EMAL ESAM ESND ESOM EVFY IDENT NOOP RSET } alt_max_command_line_len 246 { SEND SAML SOML AUTH TURN ETRN DATA RSET QUIT ONEX QUEU STARTTLS TICK TIME TURNME VERB X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } valid_cmds { ATRN AUTH BDAT CHUNKING DATA DEBUG EHLO EMAL ESAM ESND ESOM ETRN EVFY EXPN HELO HELP IDENT MAIL NOOP ONEX QUEU QUIT RCPT RSET SAML SEND SOML STARTTLS TICK TIME TURN TURNME VERB VRFY X-ADAT X-DRCP X-ERCP X-EXCH50 X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } xlink2state { enabled }
".'include $RULE_PATH/smtp.rules
include $SO_RULE_PATH/smtp.rules'."\n");
			}
			// IMAP
			if($imap) {
				$imaplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imaplist'");
				$imapports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'imapports'");
				if(!$imapports) $imapports = "143,993";
				fwrite($file,"\n#\n# IMAP Section\n#\n\nvar IMAP_SERVERS [".$imaplist."]
portvar IMAP_PORTS [".$imapports."]
".'preprocessor imap: ports { '.preg_replace("#[,]#"," ",$imapports).' } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0
include $RULE_PATH/imap.rules
include $SO_RULE_PATH/imap.rules'."\n");
			}
			// POP
			if($pop) {
				$poplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'poplist'");
				$popports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'popports'");
				if(!$popports) $popports = "109,110,995";
				fwrite($file,"\n#\n# POP Section\n#\n\nvar POP_SERVERS [".$poplist."]\n");
				fwrite($file,"portvar POP_PORTS [".$popports."]\n");
				fwrite($file,'preprocessor pop: ports { '.preg_replace("#[,]#"," ",$popports).' } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0'."\n");
				fwrite($file,'include $RULE_PATH/pop2.rules'."\n");
				fwrite($file,'include $RULE_PATH/pop3.rules'."\n");
			}
			
			$http = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httpenable'");
			// HTTP Tab
			if($http) {
				$httplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httplist'");
				$httpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'httpports'");
				if(!$httpports) $httpports = "80,443";
				fwrite($file,"\n#\n# HTTP Section\n#\n\nvar HTTP_SERVERS [".$httplist."]\n");
				fwrite($file,"portvar HTTP_PORTS ".$httpports."\n");
				fwrite($file,'portvar FILE_DATA_PORTS [$HTTP_PORTS,110,143]'."
preprocessor http_inspect: global iis_unicode_map unicode.map 1252 compress_depth 65535 decompress_depth 65535
preprocessor http_inspect_server: server default \\\n
    http_methods { GET POST PUT SEARCH MKCOL COPY MOVE LOCK UNLOCK NOTIFY POLL BCOPY BDELETE BMOVE LINK UNLINK OPTIONS HEAD DELETE TRACE TRACK CONNECT SOURCE SUBSCRIBE UNSUBSCRIBE PROPFIND PROPPATCH BPROPFIND BPROPPATCH RPC_CONNECT PROXY_SUCCESS BITS_POST CCM_POST SMS_POST RPC_IN_DATA RPC_OUT_DATA RPC_ECHO_DATA } \\
    chunk_length 500000 \\
    server_flow_depth 0 \\
    client_flow_depth 0 \\
    post_depth 65495 \\
    oversize_dir_length 500 \\
    max_header_length 750 \\
    max_headers 100 \\
    max_spaces 0 \\
    small_chunk_length { 10 5 } \\
    ports { ".preg_replace("#[,]#"," ",$httpports)." } \\
    non_rfc_char { 0x00 0x01 0x02 0x03 0x04 0x05 0x06 0x07 } \\
    enable_cookie \\
    extended_response_inspection \\
    inspect_gzip \\
    normalize_utf \\
    unlimited_decompress \\
    normalize_javascript \\
    apache_whitespace no \\
    ascii no \\
    bare_byte no \\
    directory no \\
    double_decode no \\
    iis_backslash no iis_delimiter no iis_unicode no \\
    multi_slash no \\
    utf_8 no u_encode yes \\
    webroot no\n");
				fwrite($file,'include $RULE_PATH/web-activex.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-attacks.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-cgi.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-client.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-coldfusion.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-frontpage.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-iis.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-misc.rules'."\n");
				fwrite($file,'include $RULE_PATH/web-php.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/web-activex.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/web-client.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/web-iis.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/web-misc.rules'."\n");
				
				// Shellcode
				fwrite($file,"portvar SHELLCODE_PORTS !80\n");
				fwrite($file,'include $RULE_PATH/shellcode.rules'."\n");
			}
			
			$sql = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sqlenable'");
			$oracle = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'oracleenable'");
			// DB Tab
			// SQL 
			// @ TODO: SQL servers contains oracle servers
			if($sql) {
				$sqllist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sqllist'");
				fwrite($file,"\n#\n# SQL Section\n#\n\nvar SQL_SERVERS [".$sqllist."]\n");
				fwrite($file,'include $RULE_PATH/mysql.rules'."\n");
				fwrite($file,'include $RULE_PATH/sql.rules'."\n");
			}
			// Oracle
			if($oracle) {
				$oracleports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'oracleports'");
				if(!$oracleports) $oracleports = "1525,1527,1529,2005";
				fwrite($file,"\n#\n# Oracle Section\n#\n\nportvar ORACLE_PORTS [".$oracleports."]\n");
				fwrite($file,'include $RULE_PATH/oracle.rules'."\n");
			}
			
			$ftp = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftpenable'");
			// FTP tab
			if($ftp) {
				$ftplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftplist'");
				$ftpports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'ftpports'");
				if(!$ftpports) $ftpports = "20,21";
				fwrite($file,"\n#\n# FTP Section\n#\n\nvar FTP_SERVERS [".$ftplist."]\n");
				fwrite($file,"portvar FTP_PORTS [".$ftpports."]\n");
				fwrite($file,'include $RULE_PATH/ftp.rules'."\n");
			}
			
			$telnet = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'telnetenable'");
			$ssh = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshenable'");
			$tse = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'tseenable'");
			// Remote access tab
			// Telnet
			if($telnet) {
					$telnetlist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'telnetlist'");
					fwrite($file,"\n#\n# Telnet Section\n#\n\nvar TELNET_SERVERS [".$telnetlist."]\n");
					fwrite($file,'include $RULE_PATH/telnet.rules'."\n");
			}
			// ssh
			if($ssh) {
				$sshlist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshlist'");
				$sshports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sshports'");
				if(!$sshports) $sshports = "22";
				fwrite($file,"\n#\n# SSH Section\n#\n\nvar SSH_SERVERS [".$sshlist."]\n");
				fwrite($file,"portvar SSH_PORTS [".$sshports."]\n");
				fwrite($file,"preprocessor ssh: server_ports { ".preg_replace("#[,]#"," ",$sshports)." } autodetect max_client_bytes 19600 max_server_version_len 100 max_encrypted_packets 20 enable_respoverflow enable_ssh1crc32 enable_srvoverflow enable_protomismatch\n");
			}
			// TSE
			if($tse) {
				$tselist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'tselist'");
				fwrite($file,"\n#\n# TSE Section\n#\n\nvar TSE_SERVERS [".$tselist."]\n");
			}
			
			// SNMP
			$snmp = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'snmpenable'");
			if($snmp) {
				$snmplist = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'snmplist'");
				fwrite($file,"\n#\n# SNMP Section\n#\n\nvar SNMP_SERVERS [".$snmplist."]\n");
				fwrite($file,'include $RULE_PATH/snmp.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/snmp.rules'."\n");
			}
			
			$sip = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sipenable'");
			if($sip) {
				$sipports = FS::$pgdbMgr->GetOneData("z_eye_snortmgmt_keys","val","mkey = 'sipports'");
				if(!$sipports) $sipports = "5060,5061";
				fwrite($file,"\n#\n# SNMP Section\n#\n\nportvar SIP_PORTS [".$sipports."]\n");
				fwrite($file,'preprocessor sip: max_sessions 10000, ports { '.preg_replace("#[,]#"," ",$sipports).' }, methods { invite cancel ack bye register options refer subscribe update join info message notify benotify do qauth sprack publish service unsubscribe prack }, max_uri_len 512, max_call_id_len 80, max_requestName_len 20, max_from_len 256, max_to_len 256, max_via_len 1024, max_contact_len 512, max_content_len 1024'."\n");
				fwrite($file,'include $RULE_PATH/voip.rules'."\n");
			}
			
			fwrite($file,"\n#\n# Misc Section\n#\n\npreprocessor rpc_decode: 111 32770 32771 32772 32773 32774 32775 32776 32777 32778 32779 no_alert_multiple_requests no_alert_large_fragments no_alert_incomplete\n");
			fwrite($file,"preprocessor bo\n");
			
			// FTP & telnet only
			if($ftp && $telnet) {
				fwrite($file,"preprocessor ftp_telnet: global inspection_type stateful encrypted_traffic yes\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: telnet ayt_attack_thresh 20 normalize ports { 23 } detect_anomalies\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: ftp server default \\
    def_max_param_len 100 ports { 21 2100 3535 } \\
    telnet_cmds yes ignore_telnet_erase_cmds yes ftp_cmds { ABOR ACCT ADAT ALLO APPE AUTH CCC CDUP CEL CLNT CMD CONF CWD DELE ENC EPRT EPSV ESTA ESTP FEAT HELP LANG LIST LPRT LPSV MACB MAIL MDTM MIC MKD MLSD MLST MODE NLST NOOP OPTS PASS PASV PBSZ PORT PROT PWD QUIT REIN REST RETR RMD RNFR RNTO SDUP SITE SIZE SMNT STAT STOR STOU STRU SYST TEST TYPE USER XCUP XCRC XCWD XMAS XMD5 XMKD XPWD XRCP XRMD XRSQ XSEM XSEN XSHA1 XSHA256 } \\
    alt_max_param_len 0 { ABOR CCC CDUP ESTA FEAT LPSV NOOP PASV PWD QUIT REIN STOU SYST XCUP XPWD } \\
    alt_max_param_len 200 { ALLO APPE CMD HELP NLST RETR RNFR STOR STOU XMKD } \\
    alt_max_param_len 256 { CWD RNTO } \\
    alt_max_param_len 400 { PORT } \\
    alt_max_param_len 512 { SIZE } \\
    chk_str_fmt { ACCT ADAT ALLO APPE AUTH CEL CLNT CMD CONF CWD DELE ENC EPRT EPSV ESTP HELP LANG LIST LPRT MACB MAIL MDTM MIC MKD MLSD MLST MODE NLST OPTS PASS PBSZ PORT PROT REST RETR RMD RNFR RNTO SDUP SITE SIZE SMNT STAT STOR STRU TEST TYPE USER XCRC XCWD XMAS XMD5 XMKD XRCP XRMD XRSQ XSEM XSEN XSHA1 XSHA256 } \\
    cmd_validity ALLO < int [ char R int ] > \\
    cmd_validity EPSV < [ { char 12 | char A char L char L } ] > \\
    cmd_validity MACB < string > \\
    cmd_validity MDTM < [ date nnnnnnnnnnnnnn[.n[n[n]]] ] string > \\
    cmd_validity MODE < char ASBCZ > \\
    cmd_validity PORT < host_port > \\
    cmd_validity PROT < char CSEP > \\
    cmd_validity STRU < char FRPO [ string ] > \\
    cmd_validity TYPE < { char AE [ char NTC ] | char I | char L [ number ] } >\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: ftp client default max_resp_len 256 bounce yes ignore_telnet_erase_cmds yes telnet_cmds yes\n");
			}
			
			fwrite($file,'preprocessor sfportscan: proto { all } memcap { 10000000 } sense_level { low } ignore_scanners { $HOME_NET }'."\n");
			fwrite($file,"preprocessor sensitive_data: alert_threshold 25\n");
			
			// @ TODO: debug fwrite($file,"preprocessor dcerpc2\n");
			// @ TODO: debug fwrite($file,'dcerpc2_server: default, policy WinXP, detect [smb [139,445], tcp 135, udp 135, rpc-over-http-server 593], autodetect [tcp 1025:, udp 1025:, rpc-over-http-server 1025:], smb_max_chain 3, smb_invalid_shares '."[\"C$\", \"D$\", \"ADMIN$\"]\n");
			fwrite($file,"preprocessor ssl: ports { 443 465 563 636 989 992 993 994 995 7801 7802 7900 7901 7902 7903 7904 7905 7906 7907 7908 7909 7910 7911 7912 7913 7914 7915 7916 7917 7918 7919 7920 }, trustservers, noinspect_encrypted\n");
			//fwrite($file,"output database: log, pgsql, user="." password="." dbname="." host="."\n");
			fwrite($file,"include classification.config\n");
			fwrite($file,"include reference.config\n");
			fwrite($file,'include $RULE_PATH/local.rules'."\n");
			
			// Misc rules
			// @ TODO: split rules fwrite($file,'include $RULE_PATH/backdoor.rules'."\n");
			fwrite($file,'include $RULE_PATH/bad-traffic.rules'."\n");
			// @ TODO: split rules fwrite($file,'include $RULE_PATH/blacklist.rules'."\n");
			// @ TODO fwrite($file,'include $RULE_PATH/botnet-cnc.rules'."\n");
			fwrite($file,'include $RULE_PATH/chat.rules'."\n");
			fwrite($file,'include $RULE_PATH/content-replace.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/ddos.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/dos.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/exploit.rules'."\n");
			fwrite($file,'include $RULE_PATH/finger.rules'."\n");
			fwrite($file,'include $RULE_PATH/icmp.rules'."\n");
			fwrite($file,'include $RULE_PATH/icmp-info.rules'."\n");
			fwrite($file,'include $RULE_PATH/info.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/misc.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/multimedia.rules'."\n");
			// @ TODO: debug fwrite($file,'include $RULE_PATH/netbios.rules'."\n");
			fwrite($file,'include $RULE_PATH/nntp.rules'."\n");
			fwrite($file,'include $RULE_PATH/other-ids.rules'."\n");
			fwrite($file,'include $RULE_PATH/p2p.rules'."\n");
			fwrite($file,'include $RULE_PATH/phishing-spam.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/policy.rules'."\n");
			fwrite($file,'include $RULE_PATH/rpc.rules'."\n");
			fwrite($file,'include $RULE_PATH/rservices.rules'."\n");
			fwrite($file,'include $RULE_PATH/scada.rules'."\n");
			fwrite($file,'include $RULE_PATH/scan.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/specific-threats.rules'."\n");
			// @ TODO: split fwrite($file,'include $RULE_PATH/spyware-put.rules'."\n");
			fwrite($file,'include $RULE_PATH/tftp.rules'."\n");
			fwrite($file,'include $RULE_PATH/virus.rules'."\n");
			fwrite($file,'include $RULE_PATH/x11.rules'."\n");
			
			fwrite($file,'include $PREPROC_RULE_PATH/preprocessor.rules'."\n");
			fwrite($file,'include $PREPROC_RULE_PATH/decoder.rules'."\n");
			// @ TODO: debug fwrite($file,'include $PREPROC_RULE_PATH/sensitive-data.rules'."\n");
			
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/bad-traffic.rules'."\n");
			fwrite($file,'include $SO_RULE_PATH/chat.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/dos.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/exploit.rules'."\n");
			fwrite($file,'include $SO_RULE_PATH/icmp.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/misc.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/multimedia.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/netbios.rules'."\n");
			fwrite($file,'include $SO_RULE_PATH/nntp.rules'."\n");
			fwrite($file,'include $SO_RULE_PATH/p2p.rules'."\n");
			// @ TODO: split fwrite($file,'include $SO_RULE_PATH/specific-threats.rules'."\n");
			
			fclose($file);
			
			/*$file = fopen(dirname(__FILE__)."/../../../datas/tmp/snort");
			fwrite($file,"1");
			fclose($file);*/
			return 0;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$dbhost = FS::$secMgr->checkAndSecurisePostData("dbhost");
					$dbname = FS::$secMgr->checkAndSecurisePostData("dbname");
					$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
					$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
					//$dbport = FS::$secMgr->checkAndSecurisePostData("dbport");

					if(!$dbhost || !$dbname || !$dbuser || !$dbpwd) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					
					$tmppgconn = new FSPostgreSQLMgr();
					$tmppgconn->setConfig($dbname,5432,$dbhost,$dbuser,$dbpwd);
					$tmppgconn->Connect();
					
					FS::$pgdbMgr->Connect();
					
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('dbhost','dbuser','dbpwd','dbname')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dbhost','".$dbhost."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dbuser','".$dbuser."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dbpwd','".$dbpwd."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dbname','".$dbname."'");
					header("Location: index.php?mod=".$this->mid);
					break;
				case 2:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("dnslist");
					$enable = FS::$secMgr->checkAndSecurisePostData("dnsenable");

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('dnsenable','dnslist')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dnsenable',".($enable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'dnslist','".$srvlist."'");
					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=2&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=2");
					break;
				case 3:
					$smtplist = FS::$secMgr->checkAndSecurisePostData("smtplist");
					$smtpports = FS::$secMgr->checkAndSecurisePostData("smtpports");
					$enablesmtp = FS::$secMgr->checkAndSecurisePostData("ensmtp");
					$imaplist = FS::$secMgr->checkAndSecurisePostData("imaplist");
					$imapports = FS::$secMgr->checkAndSecurisePostData("imapports");
					$enableimap = FS::$secMgr->checkAndSecurisePostData("enimap");
					$poplist = FS::$secMgr->checkAndSecurisePostData("poplist");
					$popports = FS::$secMgr->checkAndSecurisePostData("popports");
					$enablepop = FS::$secMgr->checkAndSecurisePostData("enpop");

					$smtplist = trim($smtplist);
					$srvs = preg_split("#[,]#",$smtplist);
					if(strlen($smtplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$smtpports = trim($smtpports);
					$ports = preg_split("#[,]#",$smtpports);
					if(strlen($smtpports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$imaplist = trim($imaplist);
					$srvs = preg_split("#[,]#",$imaplist);
					if(strlen($imaplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$imapports = trim($imapports);
					$ports = preg_split("#[,]#",$imapports);
					if(strlen($imapports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}
					
					$poplist = trim($poplist);
					$srvs = preg_split("#[,]#",$poplist);
					if(strlen($poplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$popports = trim($popports);
					$ports = preg_split("#[,]#",$popports);
					if(strlen($popports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('smtpenable','smtplist','smtpports')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'smtpenable',".($enablesmtp == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'smtplist','".$smtplist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'smtpports','".$smtpports."'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('imapenable','imaplist','imapports')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'imapenable',".($enableimap == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'imaplist','".$imaplist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'imapports','".$imapports."'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('popenable','poplist','popports')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'popenable',".($enablepop == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'poplist','".$poplist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'popports','".$popports."'");
					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=3");
					break;
				case 4:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("httplist");
					$httpports = FS::$secMgr->checkAndSecurisePostData("httpports");
					$enable = FS::$secMgr->checkAndSecurisePostData("enhttp");

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
								return;
							}
						}
					}

					$httpports = trim($httpports);
					$ports = preg_split("#[,]#",$httpports);
					if(strlen($httpports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('httpenable','httplist','httpports')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'httpenable',".($enable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'httplist','".$srvlist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'httpports','".$httpports."'");
					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=4&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=4");
					break;
				case 5:
					$sqllist = FS::$secMgr->checkAndSecurisePostData("sqllist");
					$sqlenable = FS::$secMgr->checkAndSecurisePostData("ensql");
					$oraclelist = FS::$secMgr->checkAndSecurisePostData("oraclelist");
					$oracleports = FS::$secMgr->checkAndSecurisePostData("oracleports");
					$oracleenable = FS::$secMgr->checkAndSecurisePostData("enoracle");

					$sqllist = trim($sqllist);
					$srvs = preg_split("#[,]#",$sqllist);
					if(strlen($sqllist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					$oraclelist = trim($oraclelist);
					$srvs = preg_split("#[,]#",$oraclelist);
					if(strlen($oraclelist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					$oracleports = trim($oracleports);
					$ports = preg_split("#[,]#",$oracleports);
					if(strlen($oracleports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sqlenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sqllist'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sqlenable',".($sqlenable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sqllist','".$sqllist."'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey IN ('oracleenable','oraclelist','oracleports')");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'oracleenable',".($oracleenable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'oraclelist','".$oraclelist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'oracleports','".$oracleports."'");
					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=5&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=5");
					break;
				case 6:
					$telnetlist = FS::$secMgr->checkAndSecurisePostData("telnetlist");
					$telnetenable = FS::$secMgr->checkAndSecurisePostData("entelnet");
					$sshlist = FS::$secMgr->checkAndSecurisePostData("sshlist");
					$sshports = FS::$secMgr->checkAndSecurisePostData("sshports");
					$sshenable = FS::$secMgr->checkAndSecurisePostData("enssh");
					$tselist = FS::$secMgr->checkAndSecurisePostData("tselist");
					$tseenable = FS::$secMgr->checkAndSecurisePostData("entse");

					$telnetlist = trim($telnetlist);
					$srvs = preg_split("#[,]#",$telnetlist);
					if(strlen($telnetlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$sshlist = trim($sshlist);
					$srvs = preg_split("#[,]#",$sshlist);
					if(strlen($sshlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$sshports = trim($sshports);
					$ports = preg_split("#[,]#",$sshports);
					if(strlen($sshports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$tselist = trim($tselist);
					$srvs = preg_split("#[,]#",$tselist);
					if(strlen($tselist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sshenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sshlist'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sshports'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sshenable',".($sshenable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sshlist','".$sshlist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sshports','".$sshports."'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'telnetenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'telnetlist'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'telnetenable',".($telnetenable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'telnetlist','".$telnetlist."'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'tseenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'tselist'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'tseenable',".($tseenable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'tselist','".$tselist."'");

					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=6&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=6");
					break;
				case 7:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("ftplist");
					$ftpports = FS::$secMgr->checkAndSecurisePostData("ftpports");
					$enable = FS::$secMgr->checkAndSecurisePostData("enftp");

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=7&err=1");
								return;
							}
						}
					}

					$ftpports = trim($ftpports);
					$ports = preg_split("#[,]#",$ftpports);
					if(strlen($ftpports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=7&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'ftpenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'ftplist'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'ftpports'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'ftpenable',".($enable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'ftplist','".$srvlist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'ftpports','".$ftpports."'");

					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=7&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=7");
					break;
				case 8:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("snmplist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensnmp");

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=8&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'snmpenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'snmplist'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'snmpenable',".($enable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'snmplist','".$srvlist."'");

					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=8&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=8");
					break;
				case 9:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("siplist");
					$sipports = FS::$secMgr->checkAndSecurisePostData("sipports");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensip");

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=9&err=1");
								return;
							}
						}
					}

					$sipports = trim($sipports);
					$ports = preg_split("#[,]#",$sipports);
					if(strlen($sipports) > 0 && count($ports) > 0) {
						for($i=0;$i<count($ports);$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								header("Location: index.php?mod=".$this->mid."&sh=9&err=1");
								return;
							}
						}
					}

					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sipenable'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'siplist'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'sipports'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sipenable',".($enable == "on" ? 1 : 0));
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'siplist','".$srvlist."'");
					FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'sipports','".$sipports."'");

					if($this->writeConfiguration() != 0)
						header("Location: index.php?mod=".$this->mid."&sh=9&err=2");
					else
						header("Location: index.php?mod=".$this->mid."&sh=9");
					break;
				
				case 10:
					$nightreport = FS::$secMgr->checkAndSecurisePostData("nightreport");
					$hnight = FS::$secMgr->getPost("hnight","n+=");
					$mnight = FS::$secMgr->getPost("mnight","n+=");
					$nightback = FS::$secMgr->getPost("nightintval","n+");
					$wereport = FS::$secMgr->checkAndSecurisePostData("wereport");
					$hwe = FS::$secMgr->getPost("hwe","n+=");
					$mwe = FS::$secMgr->getPost("mwe","n+=");
					
					if($hnight == NULL || $hnight > 23 || $mnight == NULL || $mnight > 59 || 
						$hwe == NULL || $hwe > 23 || $mwe == NULL || $mwe > 59 || $nightback == NULL || $nightback > 23) {
						header("Location: index.php?mod=".$this->mid."&sh=10&err=1");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_nighten'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_nighthour'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_nightmin'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_nightback'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_ween'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_wehour'");
					FS::$pgdbMgr->Delete("z_eye_snortmgmt_keys","mkey = 'report_wemin'");
					
					$file = fopen(dirname(__FILE__)."/../../../datas/system/snort.crontab");
					if(!$file) {
						header("Location: index.php?mod=".$this->mid."&sh=10&err=3");
						return;
					}
						
					if($nightreport == "on") {
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_nighten','1'");
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_nighthour','".$hnight."'");
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_nightmin','".$mnight."'");
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_nightback','".$nightback."'");
						if(Config::getOS() == "Debian")
							fwrite($file,$mnight." ".$hnight."\t* * * root /var/www/scripts/snort_report.py night\n");
						else
							fwrite($file,$mnight." ".$hnight."\t* * * root /usr/local/www/apache22/data/scripts/snort_report.py night\n");
					}
					
					if($wereport == "on") {
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_ween','1'");
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_wehour','".$hwe."'");
						FS::$pgdbMgr->Insert("z_eye_snortmgmt_keys","mkey,val","'report_wemin','".$mwe."'");
						if(Config::getOS() == "Debian")
							fwrite($file,$mnight." ".$hnight."\t* * * root /var/www/scripts/snort_report.py we\n");
						else
							fwrite($file,$mnight." ".$hnight."\t* * * root /usr/local/www/apache22/data/scripts/snort_report.py we\n");
					}
					
					// TODO: write special cron for tasks & create cron entries for modules
					fclose($file);
					header("Location: index.php?mod=".$this->mid."&sh=10");
					break;
				default: break;
			}
		}
	};
?>
