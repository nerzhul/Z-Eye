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

	class iSnortMgmt extends FSModule{
		function iSnortMgmt() { parent::FSModule(); $this->loc = new lSnort(); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("page-title"));
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
				$output .= FS::$iMgr->h1("page-title");
				$panElmts = array(array(1,"mod=".$this->mid,$this->loc->s("General")),
					array(10,"mod=".$this->mid,$this->loc->s("Reports")),
					array(6,"mod=".$this->mid,$this->loc->s("Remote")),
					array(2,"mod=".$this->mid,"DNS"),
					array(3,"mod=".$this->mid,"Mail"),
					array(7,"mod=".$this->mid,"FTP"),
					array(4,"mod=".$this->mid,"HTTP"),
					array(5,"mod=".$this->mid,"DB"),
					array(8,"mod=".$this->mid,"SNMP"),
					array(9,"mod=".$this->mid,"SIP"));
				$output .= FS::$iMgr->tabPan($panElmts,$sh);
			}
			else if(!$sh || $sh == 1) {
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".$sh);
				// Load snort keys for db config
				$dbname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbname'");
				$dbhost = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbhost'");
				$dbuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbuser'");
				$dbpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbpwd'");
				$lanlist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'lanlist'");
				$output .= "<table>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("data-storage")."</th></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("pg-host"),"dbhost",$dbhost);
				$output .= FS::$iMgr->idxLine($this->loc->s("Database"),"dbname",$dbname);
				$output .= FS::$iMgr->idxLine($this->loc->s("User"),"dbuser",$dbuser);
				$output .= FS::$iMgr->idxLine($this->loc->s("Password"),"dbpwd",$dbpwd,array("type" => "pwd"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("lan-list"),"lanlist",array("width" => 250, "height" => 100, "value" => $lanlist));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 2) {
				$dnsenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dnsenable'");
				$dnslist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dnslist'");
				if(!$dnsenable) $dnsenable = 0;
				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"dnsenable",$dnsenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-dns"),"dnslist",array("value" => $dnslist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 3) {
				$smtpenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtpenable'");
				$smtplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtplist'");
				$smtpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtpports'");
				if(!$smtpenable) $smtpenable = 0;
				if(!$smtpports) $smtpports = "25,465,587,691";
				$imapenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imapenable'");
				$imaplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imaplist'");
				$imapports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imapports'");
				if(!$imapenable) $imapenable = 0;
				if(!$imapports) $imapports = "143,993";
				$popenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'popenable'");
				$poplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'poplist'");
				$popports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'popports'");
				if(!$popenable) $popenable = 0;
				if(!$popports) $popports = "109,110,995";

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("en-smtp-sensor"),"ensmtp",$smtpenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-smtp"),"smtplist",array("value" => $smtplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-smtp"),"smtpports",array("value" => $smtpports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->idxLine("Activer les sondes IMAP","enimap",$imapenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-imap"),"imaplist",array("value" => $imaplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-imap"),"imapports",array("value" => $imapports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->idxLine("Activer les sondes POP","enpop",$popenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-pop"),"poplist",array("value" => $poplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-pop"),"popports",array("value" => $popports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 4) {
				$httpenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httpenable'");
				$httplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httplist'");
				$httpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httpports'");
				if(!$httpenable) $httpenable = 0;
				if(!$httpports) $httpports = "80,443";

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"enhttp",$httpenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-http"),"httplist",array("value" => $httplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));

				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-http"),"httpports",array("value" => $httpports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 5) {
				$sqlenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sqlenable'");
				$sqllist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sqllist'");
				if(!$sqlenable) $sqlenable = 0;
				$oracleenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'oracleenable'");
				$oraclelist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'oraclelist'");
				$oracleports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'oracleports'");
				if(!$oracleenable) $oracleenable = 0;

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"ensql",$sqlenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-sql"),"sqllist",array("value" => $sqllist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"enoracle",$oracleenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("sql-oracle"),"oraclelist",array("value" => $oraclelist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-oracle"),"oracleports",array("value" => $oracleports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));

				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 6) {
				$telnetenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'telnetenable'");
				$telnetlist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'telnetlist'");
				if(!$telnetenable) $telnetenable = 0;
				$sshenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshenable'");
				$sshlist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshlist'");
				$sshports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshports'");
				if(!$sshenable) $sshenable = 0;
				if(!$sshports) $sshports = "22";
				$tseenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'tseenable'");
				$tselist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'tselist'");
				if(!$tseenable) $tseenable = 0;

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("en-telnet-sensor"),"entelnet",$telnetenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-telnet"),"telnetlist",array("value" => $telnetlist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->idxLine($this->loc->s("en-ssh-sensor"),"enssh",$sshenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-ssh"),"sshlist",array("value" => $sshlist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-ssh"),"sshports",array("value" => $sshports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->idxLine($this->loc->s("en-tse-sensor"),"entse",$tseenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-tse"),"tselist",array("value" => $tselist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 7) {
				$ftpenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftpenable'");
				$ftplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftplist'");
				$ftpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftpports'");
				if(!$ftpenable) $ftpenable = 0;
				if(!$ftpports) $ftpports = "21";

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"enftp",$ftpenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-ftp"),"ftplist",array("value" => $ftplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-ftp"),"ftpports",array("value" => $ftpports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 8) {
				$snmpenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'snmpenable'");
				$snmplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'snmplist'");
				if(!$snmpenable) $snmpenable = 0;

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"ensnmp",$snmpenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-snmp"),"snmplist",array("value" => $snmplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 9) {
				$sipenable = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sipenable'");
				$siplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'siplist'");
				$sipports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sipports'");
				if(!$sipenable) $sipenable = 0;

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"),"ensip",$sipenable,array("type" => "chk"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("srv-sip"),"siplist",array("value" => $siplist, "width" => 250, "height" => 100, "tooltip" => "tooltip-ipv4"));
				$output .= FS::$iMgr->tabledTextArea($this->loc->s("port-sip"),"sipports",array("value" => $sipports, "width" => 250, "height" => 100, "tooltip" => "tooltip-port"));
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			else if($sh == 10) {
				$nightreport = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_nighten'");
				$wereport = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_ween'");
				$nighth = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_nighthour'");
				$nightm = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_nightmin'");
				$nightback = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_nightback'");
				$weh = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_wehour'");
				$wem = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'report_wemin'");

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("title-nightreport")."</th></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"), "nightreport", $nightreport == 1 ? true : false,array("type" => "chk"));
				$output .= "<tr><td>".$this->loc->s("sent-hour")."</td><td>".FS::$iMgr->hourlist("hnight","mnight",$nighth,$nightm)."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("prev-hour")."</td><td>".FS::$iMgr->numInput("nightintval",$nightback > 0 ? $nightback : 7,array("size" => 2, "length" => 2, "tooltip" => "tooltip-prev-hour"))."</td></tr>";
				$output .= "<tr><th colspan=\"2\">".$this->loc->s("title-we")."</th></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Activate"), "wereport", $wereport == 1 ? true : false,array("type" => "chk"));
				$output .= "<tr><td>".$this->loc->s("sent-hour")."</td><td>".FS::$iMgr->hourlist("hwe","mwe",$weh,$wem)."</td></tr>";
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Register"));
				$output .= "</table></form>";
			}
			return $output;
		}

		private function writeConfiguration() {
			$file = fopen("/usr/local/etc/snort/snort.z_eye.conf","w+");
			if(!$file) return 1;

			$homenetworks = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'lanlist'");
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
			
			$dns = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dnsenable'");
			// DNS tab
			if($dns) {
				$dnslist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dnslist'");
				fwrite($file,"\n#\n# DNS Section\n#\n\nvar DNS_SERVERS [".$dnslist."]\n");
				fwrite($file,'preprocessor dns: ports { 53 } enable_rdata_overflow'."\n");
				fwrite($file,'include $RULE_PATH/dns.rules'."\n");
			}
			
			$smtp = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtpenable'");
			$imap = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imapenable'");
			$pop = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'popenable'");
			// Mail tab
			// SMTP
			if($smtp) {
				$smtplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtplist'");
				$smtpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'smtpports'");
				if(!$smtpports) $smtpports = "25,465,587,691";
				fwrite($file,"\n#\n# SMTP Section\n#\n\nvar SMTP_SERVERS [".$smtplist."]
portvar SMTP_PORTS [".$smtpports."]
preprocessor smtp: ports { ".preg_replace("#[,]#"," ",$smtpports)." } inspection_type stateful b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0 log_mailfrom log_rcptto log_filename log_email_hdrs normalize cmds normalize_cmds { ATRN AUTH BDAT CHUNKING DATA DEBUG EHLO EMAL ESAM ESND ESOM ETRN EVFY EXPN HELO HELP IDENT MAIL NOOP ONEX QUEU QUIT RCPT RSET SAML SEND SOML STARTTLS TICK TIME TURN TURNME VERB VRFY X-ADAT X-DRCP X-ERCP X-EXCH50 X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } max_command_line_len 512 max_header_line_len 1000 max_response_line_len 512 alt_max_command_line_len 260 { MAIL } alt_max_command_line_len 300 { RCPT } alt_max_command_line_len 500 { HELP HELO ETRN EHLO } alt_max_command_line_len 255 { EXPN VRFY ATRN SIZE BDAT DEBUG EMAL ESAM ESND ESOM EVFY IDENT NOOP RSET } alt_max_command_line_len 246 { SEND SAML SOML AUTH TURN ETRN DATA RSET QUIT ONEX QUEU STARTTLS TICK TIME TURNME VERB X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } valid_cmds { ATRN AUTH BDAT CHUNKING DATA DEBUG EHLO EMAL ESAM ESND ESOM ETRN EVFY EXPN HELO HELP IDENT MAIL NOOP ONEX QUEU QUIT RCPT RSET SAML SEND SOML STARTTLS TICK TIME TURN TURNME VERB VRFY X-ADAT X-DRCP X-ERCP X-EXCH50 X-EXPS X-LINK2STATE XADR XAUTH XCIR XEXCH50 XGEN XLICENSE XQUE XSTA XTRN XUSR } xlink2state { enabled }
".'include $RULE_PATH/smtp.rules
include $SO_RULE_PATH/smtp.rules'."\n");
			}
			// IMAP
			if($imap) {
				$imaplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imaplist'");
				$imapports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'imapports'");
				if(!$imapports) $imapports = "143,993";
				fwrite($file,"\n#\n# IMAP Section\n#\n\nvar IMAP_SERVERS [".$imaplist."]
portvar IMAP_PORTS [".$imapports."]
".'preprocessor imap: ports { '.preg_replace("#[,]#"," ",$imapports).' } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0
include $RULE_PATH/imap.rules
include $SO_RULE_PATH/imap.rules'."\n");
			}
			// POP
			if($pop) {
				$poplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'poplist'");
				$popports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'popports'");
				if(!$popports) $popports = "109,110,995";
				fwrite($file,"\n#\n# POP Section\n#\n\nvar POP_SERVERS [".$poplist."]\n");
				fwrite($file,"portvar POP_PORTS [".$popports."]\n");
				fwrite($file,'preprocessor pop: ports { '.preg_replace("#[,]#"," ",$popports).' } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0'."\n");
				fwrite($file,'include $RULE_PATH/pop2.rules'."\n");
				fwrite($file,'include $RULE_PATH/pop3.rules'."\n");
			}
			
			$http = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httpenable'");
			// HTTP Tab
			if($http) {
				$httplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httplist'");
				$httpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'httpports'");
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
			
			$sql = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sqlenable'");
			$oracle = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'oracleenable'");
			// DB Tab
			// SQL 
			// @ TODO: SQL servers contains oracle servers
			if($sql) {
				$sqllist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sqllist'");
				fwrite($file,"\n#\n# SQL Section\n#\n\nvar SQL_SERVERS [".$sqllist."]\n");
				fwrite($file,'include $RULE_PATH/mysql.rules'."\n");
				fwrite($file,'include $RULE_PATH/sql.rules'."\n");
			}
			// Oracle
			if($oracle) {
				$oracleports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'oracleports'");
				if(!$oracleports) $oracleports = "1525,1527,1529,2005";
				fwrite($file,"\n#\n# Oracle Section\n#\n\nportvar ORACLE_PORTS [".$oracleports."]\n");
				fwrite($file,'include $RULE_PATH/oracle.rules'."\n");
			}
			
			$ftp = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftpenable'");
			// FTP tab
			if($ftp) {
				$ftplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftplist'");
				$ftpports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'ftpports'");
				if(!$ftpports) $ftpports = "20,21";
				fwrite($file,"\n#\n# FTP Section\n#\n\nvar FTP_SERVERS [".$ftplist."]\n");
				fwrite($file,"portvar FTP_PORTS [".$ftpports."]\n");
				fwrite($file,'include $RULE_PATH/ftp.rules'."\n");
			}
			
			$telnet = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'telnetenable'");
			$ssh = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshenable'");
			$tse = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'tseenable'");
			// Remote access tab
			// Telnet
			if($telnet) {
					$telnetlist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'telnetlist'");
					fwrite($file,"\n#\n# Telnet Section\n#\n\nvar TELNET_SERVERS [".$telnetlist."]\n");
					fwrite($file,'include $RULE_PATH/telnet.rules'."\n");
			}
			// ssh
			if($ssh) {
				$sshlist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshlist'");
				$sshports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sshports'");
				if(!$sshports) $sshports = "22";
				fwrite($file,"\n#\n# SSH Section\n#\n\nvar SSH_SERVERS [".$sshlist."]\n");
				fwrite($file,"portvar SSH_PORTS [".$sshports."]\n");
				fwrite($file,"preprocessor ssh: server_ports { ".preg_replace("#[,]#"," ",$sshports)." } autodetect max_client_bytes 19600 max_server_version_len 100 max_encrypted_packets 20 enable_respoverflow enable_ssh1crc32 enable_srvoverflow enable_protomismatch\n");
			}
			// TSE
			if($tse) {
				$tselist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'tselist'");
				fwrite($file,"\n#\n# TSE Section\n#\n\nvar TSE_SERVERS [".$tselist."]\n");
			}
			
			// SNMP
			$snmp = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'snmpenable'");
			if($snmp) {
				$snmplist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'snmplist'");
				fwrite($file,"\n#\n# SNMP Section\n#\n\nvar SNMP_SERVERS [".$snmplist."]\n");
				fwrite($file,'include $RULE_PATH/snmp.rules'."\n");
				fwrite($file,'include $SO_RULE_PATH/snmp.rules'."\n");
			}
			
			$sip = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sipenable'");
			if($sip) {
				$sipports = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'sipports'");
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

			$file = fopen("/tmp/snort_restart","w+");
			if($file) {
				fwrite($file,"1");
				fclose($file);
			}
			return 0;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Snort Main config
					$dbhost = FS::$secMgr->checkAndSecurisePostData("dbhost");
					$dbname = FS::$secMgr->checkAndSecurisePostData("dbname");
					$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
					$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
					$lanlist = FS::$secMgr->checkAndSecurisePostData("lanlist");
					//$dbport = FS::$secMgr->checkAndSecurisePostData("dbport");

					if(!$dbhost || !$dbname || !$dbuser || !$dbpwd || !$lanlist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for main configuration");
						FS::$iMgr->redir("mod=".$this->mid."&err=1");
						return;
					}

					$tmppgconn = new AbstractSQLMgr();
					$tmppgconn->setConfig("pg",$dbname,5432,$dbhost,$dbuser,$dbpwd);
					$tmppgconn->Connect();

					FS::$dbMgr->Connect();

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('dbhost','dbuser','dbpwd','dbname','lanlist')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dbhost','".$dbhost."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dbuser','".$dbuser."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dbpwd','".$dbpwd."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dbname','".$dbname."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'lanlist','".$lanlist."'");
					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=2");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change snort database values to ".$dbuser.":".$dbpwd." on ".$dbname."@".$dbhost." lanlist: ".$lanlist);
					FS::$iMgr->redir("mod=".$this->mid);
					break;
				case 2: // DNS edit
					$srvlist = FS::$secMgr->checkAndSecurisePostData("dnslist");
					$enable = FS::$secMgr->checkAndSecurisePostData("dnsenable");

					if($enable == "on" && !$srvlist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for DNS sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					$count = count($srvs);
					if(strlen($srvlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for DNS sensors configuration (CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('dnsenable','dnslist')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dnsenable',".($enable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'dnslist','".$srvlist."'");
					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change DNS values to srvlist: ".$srvlist." enable: ".$enable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=2",true);
					break;
				case 3: // Mail conf
					$smtplist = FS::$secMgr->checkAndSecurisePostData("smtplist");
					$smtpports = FS::$secMgr->checkAndSecurisePostData("smtpports");
					$enablesmtp = FS::$secMgr->checkAndSecurisePostData("ensmtp");
					$imaplist = FS::$secMgr->checkAndSecurisePostData("imaplist");
					$imapports = FS::$secMgr->checkAndSecurisePostData("imapports");
					$enableimap = FS::$secMgr->checkAndSecurisePostData("enimap");
					$poplist = FS::$secMgr->checkAndSecurisePostData("poplist");
					$popports = FS::$secMgr->checkAndSecurisePostData("popports");
					$enablepop = FS::$secMgr->checkAndSecurisePostData("enpop");

					if(($enablesmtp == "on" && (!$smtplist || !$smtpports)) || ($enableimap == "on" && (!$imaplist || !$imapports)) || ($enablepop == "on" && (!$poplist || !$popports))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for Mail sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
						return;
					}

					$smtplist = trim($smtplist);
					$srvs = preg_split("#[,]#",$smtplist);
					$count = count($srvs);
					if(strlen($smtplist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (SMTP not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$smtpports = trim($smtpports);
					$ports = preg_split("#[,]#",$smtpports);
					$count = count($ports);
					if(strlen($smtpports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (smtp port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$imaplist = trim($imaplist);
					$srvs = preg_split("#[,]#",$imaplist);
					$count = count($srvs);
					if(strlen($imaplist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (IMAP not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$imapports = trim($imapports);
					$ports = preg_split("#[,]#",$imapports);
					$count = count($ports);
					if(strlen($imapports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (imap port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$poplist = trim($poplist);
					$srvs = preg_split("#[,]#",$poplist);
					$count = count($srvs);
					if(strlen($poplist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (POP not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}

					$popports = trim($popports);
					$ports = preg_split("#[,]#",$popports);
					$count = count($ports);
					if(strlen($popports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Mail sensors configuration (pop port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('smtpenable','smtplist','smtpports')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'smtpenable',".($enablesmtp == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'smtplist','".$smtplist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'smtpports','".$smtpports."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('imapenable','imaplist','imapports')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'imapenable',".($enableimap == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'imaplist','".$imaplist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'imapports','".$imapports."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('popenable','poplist','popports')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'popenable',".($enablepop == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'poplist','".$poplist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'popports','".$popports."'");
					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change SMTP values to smtplist: ".$smtplist." smtpports: ".$smtpports." smtenable: ".$enablesmtp);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change POP values to poplist: ".$poplist." popports: ".$popports." popenable: ".$enablepop);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change IMAP values to imaplist: ".$imaplist." imapports: ".$imapports. " imapenable: ".$enableimap);
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					break;
				case 4: // HTTP config
					$srvlist = FS::$secMgr->checkAndSecurisePostData("httplist");
					$httpports = FS::$secMgr->checkAndSecurisePostData("httpports");
					$enable = FS::$secMgr->checkAndSecurisePostData("enhttp");

					if($enable == "on" && (!$srvlist || !$httpports)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for HTTP sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					$count = count($srvs);
					if(strlen($srvlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for HTTP sensors configuration (not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
								return;
							}
						}
					}

					$httpports = trim($httpports);
					$ports = preg_split("#[,]#",$httpports);
					$count = count($ports);
					if(strlen($httpports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for HTTP (port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('httpenable','httplist','httpports')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'httpenable',".($enable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'httplist','".$srvlist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'httpports','".$httpports."'");
					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change HTTP values to srvlist: ".$srvlist." ports: ".$httpports." enable: ".$enable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					break;
				case 5: // Sql config
					$sqllist = FS::$secMgr->checkAndSecurisePostData("sqllist");
					$sqlenable = FS::$secMgr->checkAndSecurisePostData("ensql");
					$oraclelist = FS::$secMgr->checkAndSecurisePostData("oraclelist");
					$oracleports = FS::$secMgr->checkAndSecurisePostData("oracleports");
					$oracleenable = FS::$secMgr->checkAndSecurisePostData("enoracle");

					if($sqlenable == "on" && !$sqllist || $oracleenable && (!$oraclelist || !$oracleports)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for SQL sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}

					$sqllist = trim($sqllist);
					$srvs = preg_split("#[,]#",$sqllist);
					$count = count($srvs);
					if(strlen($sqllist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SQL sensors configuration (SQL not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					$oraclelist = trim($oraclelist);
					$srvs = preg_split("#[,]#",$oraclelist);
					$count = count($srvs);
					if(strlen($oraclelist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SQL sensors configuration (Oracle not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					$oracleports = trim($oracleports);
					$ports = preg_split("#[,]#",$oracleports);
					$count = count($ports);
					if(strlen($oracleports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SQL sensors configuration (Oracle port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sqlenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sqllist'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sqlenable',".($sqlenable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sqllist','".$sqllist."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey IN ('oracleenable','oraclelist','oracleports')");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'oracleenable',".($oracleenable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'oraclelist','".$oraclelist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'oracleports','".$oracleports."'");
					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=2");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change SQL values to sqllist: ".$sqllist." sqlenable: ".$sqlenable);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change SQL values to oraclelist: ".$oraclelist." oracleports: ".$oracleports." oracleenable: ".$oracleenable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=5",true);
					break;
				case 6: // Remote access
					$telnetlist = FS::$secMgr->checkAndSecurisePostData("telnetlist");
					$telnetenable = FS::$secMgr->checkAndSecurisePostData("entelnet");
					$sshlist = FS::$secMgr->checkAndSecurisePostData("sshlist");
					$sshports = FS::$secMgr->checkAndSecurisePostData("sshports");
					$sshenable = FS::$secMgr->checkAndSecurisePostData("enssh");
					$tselist = FS::$secMgr->checkAndSecurisePostData("tselist");
					$tseenable = FS::$secMgr->checkAndSecurisePostData("entse");

					if(($telnetenable == "on" && !$telnetlist) || ($sshenable && (!$sshlist || !$sshports)) || ($tseenable && !$tselist)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for remote access sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
						return;
					}

					$telnetlist = trim($telnetlist);
					$srvs = preg_split("#[,]#",$telnetlist);
					$count = count($srvs);
					if(strlen($telnetlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Remote access sensors configuration (telnet not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data")." #2";
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$sshlist = trim($sshlist);
					$srvs = preg_split("#[,]#",$sshlist);
					$count = count($srvs);
					if(strlen($sshlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Remote access sensors configuration (SSH not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$sshports = trim($sshports);
					$ports = preg_split("#[,]#",$sshports);
					$count = count($ports);
					if(strlen($sshports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Remote access sensors configuration (SSH port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					$tselist = trim($tselist);
					$srvs = preg_split("#[,]#",$tselist);
					$count = count($srvs);
					if(strlen($tselist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for Remote access sensors configuration (TSE not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sshenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sshlist'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sshports'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sshenable',".($sshenable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sshlist','".$sshlist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sshports','".$sshports."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'telnetenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'telnetlist'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'telnetenable',".($telnetenable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'telnetlist','".$telnetlist."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'tseenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'tselist'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'tseenable',".($tseenable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'tselist','".$tselist."'");

					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change remote access values to sshlist: ".$sshlist." sshports: ".$sshports." sshenable: ".$sshenable);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change remote access values to telnetlist: ".$telnetlist." telnetenable: ".$telnetenable);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change remote access values to tselist: ".$tselist." tseenable: ".$tseenable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=6",true);
					break;
				case 7: // FTP sensors
					$srvlist = FS::$secMgr->checkAndSecurisePostData("ftplist");
					$ftpports = FS::$secMgr->checkAndSecurisePostData("ftpports");
					$enable = FS::$secMgr->checkAndSecurisePostData("enftp");

					if($enable == "on" && (!$srvlist || !$ftpports)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for FTP sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
						return;
					}

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					$count = count($srvs);
					if(strlen($srvlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for FTP sensors configuration (not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
								return;
							}
						}
					}

					$ftpports = trim($ftpports);
					$ports = preg_split("#[,]#",$ftpports);
					$count = count($ports);
					if(strlen($ftpports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for FTP sensors configuration (port = ".$ports[$i].")");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'ftpenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'ftplist'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'ftpports'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'ftpenable',".($enable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'ftplist','".$srvlist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'ftpports','".$ftpports."'");

					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change FTP values to ftplist: ".$ftplist." ftpports: ".$ftpports." ftpenable: ".$enable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=7",true);
					break;
				case 8: // SNMP
					$srvlist = FS::$secMgr->checkAndSecurisePostData("snmplist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensnmp");

					if($enable == "on" && !$srvlist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for SNMP sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=1");
						return;
					}

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					$count = count($srvs);
					if(strlen($srvlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SNMP sensors configuration (not a CIDR)");
								if(FS::isAjaxCall())
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'snmpenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'snmplist'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'snmpenable',".($enable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'snmplist','".$srvlist."'");

					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change SNMP values to snmplist: ".$srvlist." snmpenable: ".$enable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=8",true);
					break;
				case 9: // SIP
					$srvlist = FS::$secMgr->checkAndSecurisePostData("siplist");
					$sipports = FS::$secMgr->checkAndSecurisePostData("sipports");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensip");

					if($enable == "on" && (!$srvlist || !$sipports)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing for SIP sensors configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=9&err=1");
						return;
					}

					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					$count = count($srvs);
					if(strlen($srvlist) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isIPorCIDR($srvs[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SIP sensors configuration (not a CIDR)");
								if(FS::isAjaxCall())	
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=9&err=1");
								return;
							}
						}
					}

					$sipports = trim($sipports);
					$ports = preg_split("#[,]#",$sipports);
					$count = count($ports);
					if(strlen($sipports) > 0 && $count > 0) {
						for($i=0;$i<$count;$i++) {
							if($ports[$i]<1||$ports[$i]>65535) {
								FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",1,"Some fields are wrong for SIP sensors configuration (port = ".$ports[$i].")");
								if(FS::isAjaxCall())	
									echo $this->loc->s("bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=9&err=1");
								return;
							}
						}
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sipenable'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'siplist'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'sipports'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sipenable',".($enable == "on" ? 1 : 0));
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'siplist','".$srvlist."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'sipports','".$sipports."'");

					if($this->writeConfiguration() != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort configuration !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-snort-conf-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=9&err=2");
						return;
					}

					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change SIP values to siplist: ".$srvlist." sippports: ".$sipports." sipenable: ".$enable);
					FS::$iMgr->redir("mod=".$this->mid."&sh=9",true);
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
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Some fields are missing/wrong for night/week report configuration");
						if(FS::isAjaxCall())
							echo $this->loc->s("bad-data");
						else 
							FS::$iMgr->redir("mod=".$this->mid."&sh=10&err=1");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_nighten'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_nighthour'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_nightmin'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_nightback'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_ween'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_wehour'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey = 'report_wemin'");

					$file = fopen(dirname(__FILE__)."/../../../datas/system/snort.crontab","w+");
					if(!$file) {
						FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",2,"Unable to write snort.crontab file !");
						if(FS::isAjaxCall())
							echo $this->loc->s("fail-cron-wr");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=10&err=3");
						return;
					}

					if($nightreport == "on") {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_nighten','1'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_nighthour','".$hnight."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_nightmin','".$mnight."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_nightback','".$nightback."'");
						fwrite($file,$mnight." ".$hnight."\t* * * root /usr/local/www/z-eye/scripts/snort_report.py night\n");
					}

					if($wereport == "on") {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_ween','1'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_wehour','".$hwe."'");
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snortmgmt_keys","mkey,val","'report_wemin','".$mwe."'");
						fwrite($file,$mnight." ".$hnight."\t* * * root /usr/local/www/z-eye/scripts/snort_report.py we\n");
					}
					
					// TODO: write special cron for tasks & create cron entries for modules
					fclose($file);
					FS::$log->i(FS::$sessMgr->getUserName(),"snortmgmt",0,"Change night reports");
					FS::$iMgr->redir("mod=".$this->mid."&sh=10",true);
					break;
				default: break;
			}
		}
	};
?>
