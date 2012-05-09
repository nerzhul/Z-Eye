<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iSnortMgmt extends genModule{
		function iSnortMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Management de l'IDS SNORT</h3>";
			$output .= $this->showMainConf();
			$output .= "</div>";
			return $output;
		}
		
		private function showMainConf() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."\">Général</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=2\">DNS</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=3\">SMTP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=4\">HTTP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=5\">SQL</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=6\">Telnet</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=7\">FTP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=8\">SNMP</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."&sh=9\">ORacle</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			}
			else if(!$sh || $sh == 1) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Liste des LANs</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
			
				$output .= "</textarea></td></tr>";	
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 2) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs DNS</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 3) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs SMTP</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			
			}
			else if($sh == 4) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs HTTP</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 5) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs SQL</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 6) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs Telnet</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 7) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs FTP</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 8) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs SNMP</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 9) {
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".$sh);
				$output .= "<table>";
				$output .= "<tr><td>Serveurs Oracle</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				
				$output .= "</textarea></td></tr>";
				
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
				
			fwrite($file,"# Snort configuration, generated by Z-Eye (".date('d-m-Y G:i:s').")\n");
			fwrite($file,"var HOME_NET ["."]\n");
			fwrite($file,"var EXTERNAL_NET !$HOME_NET\n");
			fwrite($file,"var DNS_SERVERS ["."]\n");
			fwrite($file,"var SMTP_SERVERS ["."]\n");
			fwrite($file,"var HTTP_SERVERS ["."]\n");
			fwrite($file,"var SQL_SERVERS ["."]\n");
			fwrite($file,"var TELNET_SERVERS ["."]\n");
			fwrite($file,"var FTP_SERVERS ["."]\n");
			fwrite($file,"var SNMP_SERVERS ["."]\n");
			fwrite($file,"portvar HTTP_PORTS 80\n");
			fwrite($file,"portvar SMTP_PORTS ["."]\n");
			fwrite($file,"portvar SHELLCODE_PORTS !80\n");
			fwrite($file,"portvar ORACLE_PORTS ["."]\n");
			fwrite($file,"portvar FTP_PORTS ["."]\n");
			if(Config::getOS() == "Debian") {
				fwrite($file,"var RULE_PATH /etc/snort/rules\n");
				fwrite($file,"var PREPROC_RULE_PATH /etc/snort/preproc_rules\n");
				fwrite($file,"dynamicpreprocessor directory /usr/lib/snort_dynamicpreprocessor/\n");
				fwrite($file,"dynamicengine /usr/lib/snort_dynamicengine/libsf_engine.so\n");
			} else {
				fwrite($file,"var RULE_PATH /usr/local/etc/snort/rules\n");
				fwrite($file,"var PREPROC_RULE_PATH /usr/local/etc/snort/preproc_rules\n");
				fwrite($file,"dynamicpreprocessor directory /usr/local/lib/snort/dynamicpreprocessor/\n");
				fwrite($file,"dynamicengine /usr/local/lib/snort/dynamicengine/libsf_engine.so\n");
			}
			fwrite($file,"preprocessor frag3_global: max_frags 65536\n");
			fwrite($file,"preprocessor frag3_engine: policy first detect_anomalies overlap_limit 10\n");
			fwrite($file,"preprocessor stream5_global: max_tcp 8192, track_tcp yes, track_udp no\n");
			fwrite($file,"preprocessor stream5_tcp: policy first\n");
			fwrite($file,"preprocessor http_inspect: global iis_unicode_map unicode.map 1252\n");
			fwrite($file,"preprocessor http_inspect_server: server default ports { $HTTP_SERVERS }\n");
			fwrite($file,"preprocessor http_inspect_server: server { $HTTP_SERVERS } ports { $HTTP_PORTS }\n");
			fwrite($file,"preprocessor rpc_decode: 111 32771\n");
			fwrite($file,"preprocessor bo\n");
			fwrite($file,"preprocessor ftp_telnet: global encrypted_traffic yes inspection_type stateful\n");
			fwrite($file,"preprocessor ftp_telnet_protocol: telnet normalize ayt_attack_thresh 200\n");
			fwrite($file,"preprocessor ftp_telnet_protocol: ftp server default def_max_param_len 100 alt_max_param_len 200 { CWD } cmd_validity MODE < char ASBCZ > cmd_validity MDTM < [ date nnnnnnnnnnnnnn[.n[n[n]]] ] string > chk_str_fmt { USER PASS RNFR RNTO SITE MKD } telnet _mds yes data_chan\n");
			fwrite($file,"preprocessor ftp_telnet_protocol: ftp client default max_resp_len 256 bounce yes telnet_cmds yes\n");
			fwrite($file,"preprocessor smtp: ports { $SMTP_PORTS } inspection_type stateful normalize cmds normalize_cmds { EXPN VRFY RCPT } alt_max_command_line_len 260 { MAIL } alt_max_command_line_len 300 { RCPT } alt_max_command_line_len 500 { HELP HELO ETRN } alt_max_command_line_len 255 { EXPN VRFY }\n");
			fwrite($file,"preprocessor sfportscan: proto { all } memcap { 10000000 } sense_level { low } ignore_scanners { $HOME_NET }\n");
			fwrite($file,"preprocessor ssh: server_ports { 22 } max_client_bytes 19600 max_encrypted_packets 20 enable_respoverflow enable_ssh1crc32 enable_srvoverflow enable_protomismatch\n");
			fwrite($file,"preprocessor dcerpc2\n");
			fwrite($file,"dcerpc2_server: default\n");
			fwrite($file,"preprocessor dns: ports { 53 } enable_rdata_overflow\n");
			fwrite($file,"preprocessor ssl: noinspect_encrypted, trustservers\n");
			fwrite($file,"output database: log, mysql, user="." password="." dbname="." host="."\n");
			fwrite($file,"include classification.config\n");
			fwrite($file,"include reference.config\n");
			fwrite($file,"include $RULE_PATH/local.rules\n");
			
			fclose($file);
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					break;
				default: break;
			}
		}
	};
?>
