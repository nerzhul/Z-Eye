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

	function loadNetdiscoCommunities(&$snmpro,&$snmprw) {

                $file = file("/usr/local/etc/netdisco/netdisco.conf");
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
