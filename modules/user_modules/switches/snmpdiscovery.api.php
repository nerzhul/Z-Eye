<?php
	function loadNetdiscoCommunities(&$snmpro,&$snmprw) {

                if(Config::getOS() == "FreeBSD")
                        $file = file("/usr/local/etc/netdisco/netdisco.conf");
                else if(Config::getOS() == "Debian")
                        $file = file("/etc/netdisco/netdisco.conf");
                if(!$file) {
                        echo "[".Config::getWebsiteName()."][SNMP-Caching][FATAL] Cannot find/read netdisco.conf !";
                        exit(1);
                }

                foreach ($file as $lineNumber => $buf) {
                        $buf = trim($buf);
                        $buf = str_replace("\t", "", $buf);
                        $buf = preg_replace("# #", "", $buf);
                        $res = preg_split("#=#",$buf);

                        if(count($res) == 2) {
                                if(preg_match("#^community$#",$res[0])) {
                                        $tmpro = preg_replace("# #","",$res[1]);
                                        $snmpro = preg_split("#[,]#",$tmpro);
                                }
                                else if(preg_match("#^community_rw$#",$res[0])) {
                                        $tmprw = preg_replace("# #","",$res[1]);
                                        $snmprw = preg_split("#[,]#",$tmprw);
                                }
                        }
                }
	}

	function checkSNMP($device,$community) {
                if(snmpget($device,$community,"snmpSetSerialNo.0") == false)
                        return 1;
                return 0;
        }
?>
