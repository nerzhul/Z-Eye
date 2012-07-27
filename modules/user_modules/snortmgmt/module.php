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
	class iSnortMgmt extends genModule{
		function iSnortMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<h3>Management de l'IDS SNORT</h3>";
			$output .= $this->showMainConf();
			return $output;
		}
		
		private function showMainConf() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."\">Général</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=6\">Accès distant</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=2\">DNS</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=3\">Mail</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=7\">FTP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=4\">HTTP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=5\">DB</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=8\">SNMP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=9\">SIP</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			}
			else if(!$sh || $sh == 1) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td colspan=\"2\">Stockage des données</td></td><td>";
				$output .= FS::$iMgr->addIndexedLine("Hôte MySQL","dbhost");
				$output .= FS::$iMgr->addIndexedLine("Base de données","dbname");
				$output .= FS::$iMgr->addIndexedLine("Utilisateur","dbuser");
				$output .= FS::$iMgr->addIndexedLine("Mot de passe","dbpwd","",true);
				$output .= "<tr><td>Liste des LANs</td><td>";
				$output .= "<textarea name=\"srvlist\" rows=10 cols=40>";
			
				$output .= "</textarea></td></tr>";	
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 2) {
				$dnsenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'DNS'");
				$srvlist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'DNS'");
				if(!$dnsenable) $dnsenable = 0;
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","endns",$dnsenable);
				$output .= "<tr><td>Serveurs DNS</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$srvlist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 3) {
				$smtpenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'SMTP'");
				$smtplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'SMTP'");
				if(!$smtpenable) $smtpenable = 0;
				$imapenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'IMAP'");
				$imaplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'IMAP'");
				if(!$imapenable) $imapenable = 0;
				$popenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'POP'");
				$poplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'POP'");
				if(!$popenable) $popenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes SMTP","ensmtp",$smtpenable);
				$output .= "<tr><td>Serveurs SMTP</td><td><textarea name=\"smtplist\" rows=10 cols=40>".$smtplist."</textarea></td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes IMAP","enimap",$imapenable);
				$output .= "<tr><td>Serveurs IMAP</td><td><textarea name=\"imaplist\" rows=10 cols=40>".$imaplist."</textarea></td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes POP","enpop",$popenable);
				$output .= "<tr><td>Serveurs POP</td><td><textarea name=\"poplist\" rows=10 cols=40>".$poplist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			
			}
			else if($sh == 4) {
				$httpenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'HTTP'");
				$httplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'HTTP'");
				if(!$httpenable) $httpenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","enhttp",$httpenable);
				$output .= "<tr><td>Serveurs HTTP</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$httplist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 5) {
				$sqlenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'SQL'");
				$sqllist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'SQL'");
				if(!$sqlenable) $sqlenable = 0;
				$oracleenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'ORACLE'");
				$oraclelist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'ORACLE'");
				if(!$oracleenable) $oracleenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","ensql",$sqlenable);
				$output .= "<tr><td>Serveurs SQL</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$sqllist."</textarea></td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","enoracle",$oracleenable);
				$output .= "<tr><td>Serveurs Oracle</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$oraclelist."</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 6) {
				$telnetenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'TELNET'");
				$telnetlist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'TELNET'");
				if(!$telnetenable) $telnetenable = 0;
				$sshenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'SSH'");
				$sshlist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'SSH'");
				if(!$sshenable) $sshenable = 0;
				$tseenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'TSE'");
				$tselist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'TSE'");
				if(!$tseenable) $tseenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes Telnet","entelnet",$telnetenable);
				$output .= "<tr><td>Serveurs Telnet</td><td><textarea name=\"telnetlist\" rows=10 cols=40>".$telnetlist."</textarea></td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes SSH","enssh",$sshenable);
				$output .= "<tr><td>Serveurs SSH</td><td><textarea name=\"sshlist\" rows=10 cols=40>".$sshlist."</textarea></td></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer les sondes TSE","entse",$tseenable);
				$output .= "<tr><td>Serveurs TSE</td><td><textarea name=\"tselist\" rows=10 cols=40>".$tselist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 7) {
				$ftpenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'FTP'");
				$ftplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'FTP'");
				if(!$ftpenable) $ftpenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","enftp",$ftpenable);
				$output .= "<tr><td>Serveurs FTP</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$ftplist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 8) {
				$snmpenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'SNMP'");
				$snmplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'SNMP'");
				if(!$snmpenable) $snmpenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","ensnmp",$snmpenable);
				$output .= "<tr><td>Serveurs SNMP</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$snmplist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 9) {
				$sipenable = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleenable","rulecat = 'SIP'");
				$siplist = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","rulecat = 'SIP'");
				if(!$sipenable) $sipenable = 0;
				
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","ensip",$sipenable);
				$output .= "<tr><td>Serveurs SIP</td><td><textarea name=\"srvlist\" rows=10 cols=40>".$siplist."</textarea></td></tr>";
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			
			return $output;
		}
		private function writeConfiguration() {
			if(Config::getOS() == "Debian")
				$file = fopen("/etc/snort/snort.conf","w");	
			else
				$file = fopen("/usr/local/etc/snort/snort.conf","w");
			
			$homenetworks = FS::$dbMgr->GetOneData("fss_snort_mgmt","ruleippool","HOME_NET");
			if(!$homenetworks) $homenetworks = "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16";
			fwrite($file,"# Snort configuration, generated by Z-Eye (".date('d-m-Y G:i:s').")\n");
			fwrite($file,"var HOME_NET [".$homenetworks."]\n");
			fwrite($file,'var EXTERNAL_NET !$HOME_NET');
			
			if(Config::getOS() == "Debian") {
				$RULE_PATH = "/etc/snort/rules";
				$SO_RULE_PATH = "/etc/snort/so_rules";
				$PREPROC_RULE_PATH = "/etc/snort/preproc_rules";
				fwrite($file,"\nvar RULE_PATH /etc/snort/rules\n");
				fwrite($file,"var PREPROC_RULE_PATH /etc/snort/preproc_rules\n");
				fwrite($file,"dynamicpreprocessor directory /usr/lib/snort_dynamicpreprocessor/\n");
				fwrite($file,"dynamicengine /usr/lib/snort_dynamicengine/libsf_engine.so\n");
			} else {
				$RULE_PATH = "/usr/local/etc/snort/rules";
				$SO_RULE_PATH = "/usr/local/etc/snort/so_rules";
				$PREPROC_RULE_PATH = "/usr/local/etc/snort/preproc_rules";
				fwrite($file,"\nvar RULE_PATH $RULE_PATH\n");
				fwrite($file,"var SO_RULE_PATH $SO_RULE_PATH\n");
				fwrite($file,"var PREPROC_RULE_PATH $PREPROC_RULE_PATH\n");
				fwrite($file,"dynamicpreprocessor directory /usr/local/lib/snort/dynamicpreprocessor/\n");
				fwrite($file,"dynamicengine /usr/local/lib/snort/dynamicengine/libsf_engine.so\n");
			}
			
			$dns = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","DNS");
			// DNS tab
			if($dns) {
				fwrite($file,"var DNS_SERVERS ["."]\n");
				fwrite($file,"preprocessor dns: ports { 53 } enable_rdata_overflow\n");
				fwrite($file,'include $RULE_PATH/dns.rules\n');
			}
			
			$smtp = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","SMTP");
			$imap = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","IMAP");
			$pop = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","POP");
			// Mail tab
			// SMTP
			if($smtp) {
				fwrite($file,"var SMTP_SERVERS ["."]\n");
				fwrite($file,"portvar SMTP_PORTS ["."]\n");
				fwrite($file,"preprocessor smtp: ports { $SMTP_PORTS } inspection_type stateful normalize cmds normalize_cmds { EXPN VRFY RCPT } alt_max_command_line_len 260 { MAIL } alt_max_command_line_len 300 { RCPT } alt_max_command_line_len 500 { HELP HELO ETRN } alt_max_command_line_len 255 { EXPN VRFY }\n");
				fwrite($file,"include $RULE_PATH/smtp.rules\n");
				fwrite($file,"include $SO_RULE_PATH/smtp.rules\n");
			}
			// IMAP
			if($imap) {
				fwrite($file,"var IMAP_SERVERS ["."]\n");
				fwrite($file,"portvar IMAP_PORTS ["."]\n");
				fwrite($file,"preprocessor imap: ports { $IMAP_PORTS } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0\n");
				fwrite($file,"include $RULE_PATH/imap.rules\n");
				fwrite($file,"include $SO_RULE_PATH/imap.rules\n");
			}
			// POP
			if($pop) {
				fwrite($file,"var POP_SERVERS ["."]\n");
				fwrite($file,"portvar POP_PORTS ["."]\n");
				fwrite($file,"preprocessor pop: ports { $POP_PORTS } b64_decode_depth 0 qp_decode_depth 0 bitenc_decode_depth 0 uu_decode_depth 0\n");
				fwrite($file,"include $RULE_PATH/pop2.rules\n");
				fwrite($file,"include $RULE_PATH/pop3.rules\n");
			}
			
			$http = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","HTTP");
			// HTTP Tab
			if($http) {
				fwrite($file,"var HTTP_SERVERS ["."]\n");
				fwrite($file,"portvar HTTP_PORTS 80\n");
				fwrite($file,"preprocessor http_inspect: global iis_unicode_map unicode.map 1252\n");
				fwrite($file,"preprocessor http_inspect_server: server default ports { $HTTP_SERVERS }\n");
				fwrite($file,"preprocessor http_inspect_server: server { $HTTP_SERVERS } ports { $HTTP_PORTS }\n");
				fwrite($file,"include $RULE_PATH/web-activex.rules\n");
				fwrite($file,"include $RULE_PATH/web-attacks.rules\n");
				fwrite($file,"include $RULE_PATH/web-cgi.rules\n");
				fwrite($file,"include $RULE_PATH/web-client.rules\n");
				fwrite($file,"include $RULE_PATH/web-coldfusion.rules\n");
				fwrite($file,"include $RULE_PATH/web-frontpage.rules\n");
				fwrite($file,"include $RULE_PATH/web-iis.rules\n");
				fwrite($file,"include $RULE_PATH/web-misc.rules\n");
				fwrite($file,"include $RULE_PATH/web-php.rules\n");
				fwrite($file,"include $SO_RULE_PATH/web-activex.rules\n");
				fwrite($file,"include $SO_RULE_PATH/web-client.rules\n");
				fwrite($file,"include $SO_RULE_PATH/web-iis.rules\n");
				fwrite($file,"include $SO_RULE_PATH/web-misc.rules\n");
			}
			
			$sql = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","SQL");
			$oracle = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","ORACLE");
			// DB Tab
			// SQL
			if($sql) {
				fwrite($file,"var SQL_SERVERS ["."]\n");
				fwrite($file,"include $RULE_PATH/mysql.rules\n");
				fwrite($file,"include $RULE_PATH/sql.rules\n");
			}
			// Oracle
			if($oracle) {
				fwrite($file,"portvar ORACLE_PORTS ["."]\n");
				fwrite($file,"include $RULE_PATH/oracle.rules\n");
			}
			
			$ftp = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","FTP");
			// FTP tab
			if($ftp) {
				fwrite($file,"var FTP_SERVERS ["."]\n");
				fwrite($file,"portvar FTP_PORTS ["."]\n");
				fwrite($file,"include $RULE_PATH/ftp.rules\n");
			}
			
			$telnet = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","telnet");
			$ssh = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","SSH");
			$tse = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","TSE");
			// Remote access tab
			// Telnet
			if($telnet) {
					fwrite($file,"var TELNET_SERVERS ["."]\n");
					fwrite($file,"include $RULE_PATH/telnet.rules\n");
			}
			// ssh
			if($ssh) {
				fwrite($file,"var SSH_SERVERS ["."]\n");
				fwrite($file,"portvar SSH_PORTS 22\n");
				fwrite($file,"preprocessor ssh: server_ports { 22 } autodetect max_client_bytes 19600 max_encrypted_packets 20 enable_respoverflow enable_ssh1crc32 enable_srvoverflow enable_protomismatch\n");
			}
			// TSE
			if($tse)
				fwrite($file,"var TSE_SERVERS ["."]\n");
			
			if($snmp) {
				fwrite($file,"var SNMP_SERVERS ["."]\n");
				fwrite($file,"include $RULE_PATH/snmp.rules\n");
				fwrite($file,"include $SO_RULE_PATH/snmp.rules\n");
			}
			
			$sip = FS::$dbMgr->GetOneData("fss_snort_mgmt","enable","SIP");
			if($sip) {
				fwrite($file,"portvar SIP_PORTS ["."]\n");
				fwrite($file,"preprocessor sip: maxsession 10000, ports { $SIP_PORTS }, methods { invite cancel ack bye register options refer subscribe update join info message notify benotify do qauth sprack publish service unsubscribe prack }, max_uri_len 512, max_call_id_len 80, max_requestName_len 20, max_from_len 256, max_to_len 256, max_via_len 1024, max_contact_len 512, max_content_len 1024\n");
				fwrite($file,"include $RULE_PATH/voip.rules\n");
			}
			
			fwrite($file,"portvar SHELLCODE_PORTS !80\n");
			fwrite($file,"include $RULE_PATH/shellcode.rules\n");
			
			fwrite($file,"preprocessor frag3_global: max_frags 65536\n");
			fwrite($file,"preprocessor frag3_engine: policy first detect_anomalies overlap_limit 10\n");
			fwrite($file,"preprocessor stream5_global: max_tcp 8192, track_tcp yes, track_udp yes, track_icmp no\n");
			fwrite($file,"preprocessor stream5_tcp: policy first\n");
			
			fwrite($file,"preprocessor rpc_decode: 111 32771\n");
			fwrite($file,"preprocessor bo\n");
			
			// FTP & telnet only
			if($ftp && $telnet) {
				fwrite($file,"preprocessor ftp_telnet: global encrypted_traffic yes inspection_type stateful\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: telnet normalize ayt_attack_thresh 200\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: ftp server default def_max_param_len 100 alt_max_param_len 200 { CWD } cmd_validity MODE < char ASBCZ > cmd_validity MDTM < [ date nnnnnnnnnnnnnn[.n[n[n]]] ] string > chk_str_fmt { USER PASS RNFR RNTO SITE MKD } telnet _mds yes data_chan\n");
				fwrite($file,"preprocessor ftp_telnet_protocol: ftp client default max_resp_len 256 bounce yes telnet_cmds yes\n");
			}
			
			fwrite($file,'preprocessor sfportscan: proto { all } memcap { 10000000 } sense_level { low } ignore_scanners { $HOME_NET }\n');
			
			fwrite($file,"preprocessor dcerpc2\n");
			fwrite($file,"dcerpc2_server: default\n");
			fwrite($file,"preprocessor ssl: noinspect_encrypted, trustservers\n");
			fwrite($file,"output database: log, mysql, user="." password="." dbname="." host="."\n");
			fwrite($file,"include classification.config\n");
			fwrite($file,"include reference.config\n");
			fwrite($file,"include $RULE_PATH/local.rules\n");
			
			// Misc rules
			fwrite($file,"include $RULE_PATH/backdoor.rules\n");
			fwrite($file,"include $RULE_PATH/bad-traffic.rules\n");
			fwrite($file,"include $RULE_PATH/blacklist.rules\n");
			fwrite($file,"include $RULE_PATH/botnet-cnc.rules\n");
			fwrite($file,"include $RULE_PATH/chat.rules\n");
			fwrite($file,"include $RULE_PATH/content-replace.rules\n");
			fwrite($file,"include $RULE_PATH/ddos.rules\n");
			fwrite($file,"include $RULE_PATH/dos.rules\n");
			fwrite($file,"include $RULE_PATH/exploit.rules\n");
			fwrite($file,"include $RULE_PATH/finger.rules\n");
			fwrite($file,"include $RULE_PATH/icmp.rules\n");
			fwrite($file,"include $RULE_PATH/icmp-info.rules\n");
			fwrite($file,"include $RULE_PATH/info.rules\n");
			fwrite($file,"include $RULE_PATH/misc.rules\n");
			fwrite($file,"include $RULE_PATH/multimedia.rules\n");
			fwrite($file,"include $RULE_PATH/netbios.rules\n");
			fwrite($file,"include $RULE_PATH/nntp.rules\n");
			fwrite($file,"include $RULE_PATH/other-ids.rules\n");
			fwrite($file,"include $RULE_PATH/p2p.rules\n");
			fwrite($file,"include $RULE_PATH/phishing-spam.rules\n");
			fwrite($file,"include $RULE_PATH/policy.rules\n");
			fwrite($file,"include $RULE_PATH/rpc.rules\n");
			fwrite($file,"include $RULE_PATH/rservices.rules\n");
			fwrite($file,"include $RULE_PATH/scada.rules\n");
			fwrite($file,"include $RULE_PATH/scan.rules\n");
			fwrite($file,"include $RULE_PATH/specific-threats.rules\n");
			fwrite($file,"include $RULE_PATH/spyware-put.rules\n");
			fwrite($file,"include $RULE_PATH/tftp.rules\n");
			fwrite($file,"include $RULE_PATH/virus.rules\n");
			fwrite($file,"include $RULE_PATH/x11.rules\n");
			
			fwrite($file,"include $PREPROC_RULE_PATH/preprocessor.rules\n");
			fwrite($file,"include $PREPROC_RULE_PATH/decoder.rules\n");
			fwrite($file,"include $PREPROC_RULE_PATH/sensitive-data.rules\n");
			
			fwrite($file,"include $SO_RULE_PATH/bad-traffic.rules\n");
			fwrite($file,"include $SO_RULE_PATH/chat.rules\n");
			fwrite($file,"include $SO_RULE_PATH/dos.rules\n");
			fwrite($file,"include $SO_RULE_PATH/exploit.rules\n");
			fwrite($file,"include $SO_RULE_PATH/icmp.rules\n");
			fwrite($file,"include $SO_RULE_PATH/misc.rules\n");
			fwrite($file,"include $SO_RULE_PATH/multimedia.rules\n");
			fwrite($file,"include $SO_RULE_PATH/netbios.rules\n");
			fwrite($file,"include $SO_RULE_PATH/nntp.rules\n");
			fwrite($file,"include $SO_RULE_PATH/p2p.rules\n");
			fwrite($file,"include $SO_RULE_PATH/specific-threats.rules\n");
			
			fclose($file);
			
			$file = fopen(dirname(__FILE__)."/../../../datas/tmp/snort");
			fwrite($file,"1");
			fclose($file);
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$dbhost = FS::$secMgr->checkAndSecurisePostData("dbhost");
					$dbname = rFS::$secMgr->checkAndSecurisePostData("dbname");
					$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
					$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
					//$dbport = FS::$secMgr->checkAndSecurisePostData("dbport");
					
					if(!$dbhost || !$dbname || !$dbuser || $dbpwd) {
						
					}
					break;
				case 2: 
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("endns");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'DNS','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 3:
					$smtplist = FS::$secMgr->checkAndSecurisePostData("smtplist");
					$enablesmtp = FS::$secMgr->checkAndSecurisePostData("ensmtp");
					$imaplist = FS::$secMgr->checkAndSecurisePostData("imaplist");
					$enableimap = FS::$secMgr->checkAndSecurisePostData("enimap");
					$poplist = FS::$secMgr->checkAndSecurisePostData("poplist");
					$enablepop = FS::$secMgr->checkAndSecurisePostData("enpop");
									
					$smtplist = trim($smtplist);
					$srvs = preg_split("#[,]#",$smtplist);
					if(strlen($smtplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$imaplist = trim($imaplist);
					$srvs = preg_split("#[,]#",$imaplist);
					if(strlen($imaplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$poplist = trim($poplist);
					$srvs = preg_split("#[,]#",$poplist);
					if(strlen($poplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SMTP','".($enablesmtp == "on" ? 1 : 0)."','".$smtplist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'IMAP','".($enableimap == "on" ? 1 : 0)."','".$imaplist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'POP','".($enablepop == "on" ? 1 : 0)."','".$poplist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 4:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("enhttp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'HTTP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 5:
					$sqllist = FS::$secMgr->checkAndSecurisePostData("sqllist");
					$sqlenable = FS::$secMgr->checkAndSecurisePostData("sqlenable");
					$oraclelist = FS::$secMgr->checkAndSecurisePostData("oraclelist");
					$oracleenable = FS::$secMgr->checkAndSecurisePostData("oracleenable");
									
					$sqllist = trim($sqllist);
					$srvs = preg_split("#[,]#",$sqllist);
					if(strlen($sqllist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$oraclelist = trim($oraclelist);
					$srvs = preg_split("#[,]#",$oraclelist);
					if(strlen($oraclelist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SQL','".($sqlenable == "on" ? 1 : 0)."','".$sqllist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'ORACLE','".($oracleenable == "on" ? 1 : 0)."','".$oraclelist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 6:
					$telnetlist = FS::$secMgr->checkAndSecurisePostData("telnetlist");
					$telnetenable = FS::$secMgr->checkAndSecurisePostData("entelnet");
					$sshlist = FS::$secMgr->checkAndSecurisePostData("sshlist");
					$sshenable = FS::$secMgr->checkAndSecurisePostData("enssh");
					$tselist = FS::$secMgr->checkAndSecurisePostData("tselist");
					$tseenable = FS::$secMgr->checkAndSecurisePostData("entse");
									
					$telnetlist = trim($telnetlist);
					$srvs = preg_split("#[,]#",$telnetlist);
					if(strlen($telnetlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$sshlist = trim($sshlist);
					$srvs = preg_split("#[,]#",$sshlist);
					if(strlen($sshlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$tselist = trim($tselist);
					$srvs = preg_split("#[,]#",$tselist);
					if(strlen($tselist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'TELNET','".($telnetenable == "on" ? 1 : 0)."','".$telnetlist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SSH','".($sshenable == "on" ? 1 : 0)."','".$sshlist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'TSE','".($tseenable == "on" ? 1 : 0)."','".$tselist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 7:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("enftp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'FTP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 8:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensnmp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SNMP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 9:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensip");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SIP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 2: 
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("endns");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'DNS','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 3:
					$smtplist = FS::$secMgr->checkAndSecurisePostData("smtplist");
					$enablesmtp = FS::$secMgr->checkAndSecurisePostData("ensmtp");
					$imaplist = FS::$secMgr->checkAndSecurisePostData("imaplist");
					$enableimap = FS::$secMgr->checkAndSecurisePostData("enimap");
					$poplist = FS::$secMgr->checkAndSecurisePostData("poplist");
					$enablepop = FS::$secMgr->checkAndSecurisePostData("enpop");
									
					$smtplist = trim($smtplist);
					$srvs = preg_split("#[,]#",$smtplist);
					if(strlen($smtplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$imaplist = trim($imaplist);
					$srvs = preg_split("#[,]#",$imaplist);
					if(strlen($imaplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$poplist = trim($poplist);
					$srvs = preg_split("#[,]#",$poplist);
					if(strlen($poplist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SMTP','".($enablesmtp == "on" ? 1 : 0)."','".$smtplist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'IMAP','".($enableimap == "on" ? 1 : 0)."','".$imaplist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'POP','".($enablepop == "on" ? 1 : 0)."','".$poplist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 4:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("enhttp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'HTTP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 5:
					$sqllist = FS::$secMgr->checkAndSecurisePostData("sqllist");
					$sqlenable = FS::$secMgr->checkAndSecurisePostData("sqlenable");
					$oraclelist = FS::$secMgr->checkAndSecurisePostData("oraclelist");
					$oracleenable = FS::$secMgr->checkAndSecurisePostData("oracleenable");
									
					$sqllist = trim($sqllist);
					$srvs = preg_split("#[,]#",$sqllist);
					if(strlen($sqllist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$oraclelist = trim($oraclelist);
					$srvs = preg_split("#[,]#",$oraclelist);
					if(strlen($oraclelist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SQL','".($sqlenable == "on" ? 1 : 0)."','".$sqllist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'ORACLE','".($oracleenable == "on" ? 1 : 0)."','".$oraclelist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 6:
					$telnetlist = FS::$secMgr->checkAndSecurisePostData("telnetlist");
					$telnetenable = FS::$secMgr->checkAndSecurisePostData("entelnet");
					$sshlist = FS::$secMgr->checkAndSecurisePostData("sshlist");
					$sshenable = FS::$secMgr->checkAndSecurisePostData("enssh");
					$tselist = FS::$secMgr->checkAndSecurisePostData("tselist");
					$tseenable = FS::$secMgr->checkAndSecurisePostData("entse");
									
					$telnetlist = trim($telnetlist);
					$srvs = preg_split("#[,]#",$telnetlist);
					if(strlen($telnetlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$sshlist = trim($sshlist);
					$srvs = preg_split("#[,]#",$sshlist);
					if(strlen($sshlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					$tselist = trim($tselist);
					$srvs = preg_split("#[,]#",$tselist);
					if(strlen($tselist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'TELNET','".($telnetenable == "on" ? 1 : 0)."','".$telnetlist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SSH','".($sshenable == "on" ? 1 : 0)."','".$sshlist."','"."'");
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'TSE','".($tseenable == "on" ? 1 : 0)."','".$tselist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 7:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("enftp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'FTP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 8:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensnmp");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SNMP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				case 9:
					$srvlist = FS::$secMgr->checkAndSecurisePostData("srvlist");
					$enable = FS::$secMgr->checkAndSecurisePostData("ensip");
									
					$srvlist = trim($srvlist);
					$srvs = preg_split("#[,]#",$srvlist);
					if(strlen($srvlist) > 0 && count($srvs) > 0) {
						for($i=0;$i<count($srvs);$i++) {
							if(!FS::$secMgr->isIP($srvs[$i])) {
								header("Location: index.php?mod=".$this->mid."&err=1");
								return;
							}
						}
					}
					
					FS::$dbMgr->Replace("fss_snort_mgmt","rulecat,ruleenable,ruleippool,ruleports","'SIP','".($enable == "on" ? 1 : 0)."','".$srvlist."','"."'");
					$this->writeConfiguration();
					header("Location: m-".$this->mid.".html");
					break;
				default: break;
			}
		}
	};
?>
