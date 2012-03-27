<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iSwitchMgmt extends genModule{
		function iConnect($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Management des Switches</h3>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= "<center>".FS::$iMgr->addInput("search","Recherche");
			$output .= FS::$iMgr->addSubmit("Rechercher","Rechercher")."</center></form>";
			$search = FS::$secMgr->checkAndSecuriseGetData("s");
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$port = FS::$secMgr->checkAndSecuriseGetData("p");
			$mac = FS::$secMgr->checkAndSecuriseGetData("node");
			$nb = FS::$secMgr->checkAndSecuriseGetData("nb");
			$vlan = FS::$secMgr->checkAndSecuriseGetData("vlan");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			if($port != NULL && $device != NULL)
				$output .= $this->showPortInfos();
			else if($search != NULL)
				$output .= $this->showSearchResults();
			else if($device != NULL)
				$output .= $this->showDeviceInfos();
			else if($mac != NULL)
				$output .= $this->showNodeInfos();
			else if($nb != NULL)
				$output .= $this->showNetbiosInfos();
			else if($vlan != NULL)
				$output .= $this->showVlanInfos();
			else
				$output .= $this->showDeviceList();

			$output .= "</div>";
			return $output;
		}

		private function showPortInfos() {
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
                        $port = FS::$secMgr->checkAndSecuriseGetData("p");
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "";
			if($err != NULL)
				$output .= FS::$iMgr->printError("Erreur lors de la modification des données sur le switch !");
			$output .= "<h4>".$port." sur ".$device."</h4>";
			$output .= "<script type=\"text/javascript\">function arangeform() {";
			$output .= "if(document.getElementsByName('trmode')[0].value == 1) {";
			$output .= "$('#vltr').show();";
			$output .= "$('#vllabel').html('Vlan Natif');";
			$output .= "} else if(document.getElementsByName('trmode')[0].value == 2) {";
			$output .= "$('#vltr').hide();";
			$output .= "$('#vllabel').html('Vlan');";
			$output .= "}";
			$output .= "};";
			$output .= "function showwait() {";
			$output .= "$('#subpop').html('Modification en cours...<br /><br /><br />".FS::$iMgr->addImage("styles/images/loader.gif",32,32)."');";
			$output .= "$('#pop').show();";
			$output .= "};";
			$output .= "</script>";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$query = FS::$pgdbMgr->Select("device_port","name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."' AND port ='".$port."'");
			if($data = pg_fetch_array($query)) {
				$out = "";
				exec("snmpwalk -v 2c -c ".SNMPConfig::$SNMPReadCommunity." ".$device." ifDescr | grep ".$port,$out);
				if(strlen($out[0]) < 5)
						return -1;
				$out = explode(" ",$out[0]);
				$out = explode(".",$out[0]);
				if(!FS::$secMgr->isNumeric($out[1]))
						return -1;
				$portid = $out[1];

				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=9");
				$output .= FS::$iMgr->addHidden("portid",$portid);
				$output .= FS::$iMgr->addHidden("sw",$device);
				$output .= FS::$iMgr->addHidden("port",$port);
				$output .= "<table><tr><th>Champ</th><th>Valeur</th></tr>";
				$output .= "<tr><td>Description</td><td>".FS::$iMgr->addInput("desc",$data["name"])."</td></tr>";
				$piece = FS::$dbMgr->GetOneData("fss_switch_port_prises","prise","ip = '".$dip."' AND port = '".$port."'");
				$output .= "<tr><td>Prise</td><td>".FS::$iMgr->addInput("prise",$piece)."</td></tr>";
				$output .= "<tr><td>Adresse MAC</td><td>".$data["mac"]."</td></tr>";
				$output .= "<tr><td>Etat / Duplex / Vitesse</td><td>";
				if($data["up_admin"] == "down")
                                        $output .= "<span style=\"color: red;\">Eteint</span>";
                                else if($data["up_admin"] == "up" && $data["up"] == "down")
                                        $output .= "<span style=\"color: orange;\">Inactif</span>";
                                else if($data["up"] == "up")
                                        $output .= "<span style=\"color: black;\">Actif</span>";
                                else
                                        $output .= "unk";
				$output .= " / ".($data["duplex"] == "" ? "[NA]" : $data["duplex"])." / ".$data["speed"]."</td></tr>";
				$output .= "<tr><td>Eteindre</td><td>".FS::$iMgr->addCheck("shut",($data["up_admin"] == "down" ? true : false))."</td></tr>";
				$output .= "<tr><td>Vitesse</td><td>Non disponible</td></tr>";
				$output .= "<tr><td>Duplex</td><td>Non disponible</td></tr>";
				$output .= "<tr><td>Switchport Mode</td><td>";
				$trmode = FS::$snmpMgr->get($device,"1.3.6.1.4.1.9.9.46.1.6.1.1.13.".$portid);
				$trmode = preg_split("# #",$trmode);
				$trmode = $trmode[1];
				$output .= FS::$iMgr->addList("trmode","arangeform();");
				$output .= FS::$iMgr->addElementToList("Trunk",1,$trmode == 1 ? true : false);
				$output .= FS::$iMgr->addElementToList("Access",2,$trmode == 2 ? true : false);
				$output .= "</select>";
				$output .= "<tr><td id=\"vllabel\">Vlan natif</td><td id=\"vln\">";

				$query2 = FS::$pgdbMgr->Select("device_port_vlan","vlan,native","ip = '".$dip."' AND port = '".$port."'","vlan");
                                $nvlan = $data["vlan"];
                                $vlanlist = "";
                                $vlancount = 0;
                                while($data2 = pg_fetch_array($query2)) {
                                        if($data2["native"] == "t" && $data2["vlan"] != 1) $nvlan = $data2["vlan"];
                                        $vlanlist .= $data2["vlan"].",";
                                }
				$vlanlist = substr($vlanlist,0,strlen($vlanlist)-1);
				$output .= FS::$iMgr->addInput("nvlan",$nvlan,4,4);
				$output .= "</td></tr>";
				$output .= "<tr id=\"vltr\" ".($trmode == 2 ? "style=\"display:none;\"" : "")."><td>Vlans Encapsulés</td><td>";
				$output .= "<textarea name=\"vllist\" rows=10 cols=40>";
				$output .= $vlanlist;
				$output .= "</textarea></td></tr>";
				$output .= "<tr><td>Sauver ?</td><td>".FS::$iMgr->addCheck("wr")."</td></tr>";
				$output .= "</table>";
				$output .= "<center><br /><input class=\"buttonStyle\" type=\"submit\" name=\"Enregistrer\" value=\"Enregistrer\" onclick=\"showwait();\"/></center>";
				$output .= "</form>";
			}
			else
				$output .= FS::$iMgr->printError("Les données demandées n'existent pas !");
			return $output;
		}

		private function showVlanInfos() {
			$output = "<h4>VLAN ";
			$vlan = FS::$secMgr->checkAndSecuriseGetData("vlan");
			$output .= $vlan."</h4>";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Equipement</th><th>Description</th><th>Date de création</th></tr>";
			$found = 0;
			$query = FS::$pgdbMgr->Select("device_vlan","ip,description,creation","vlan = '".$vlan."'","ip");
			while($data = pg_fetch_array($query)) {
				if($dname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'")) {
					if($found == 0) $found = 1;
					$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$dname."&fltr=".$vlan."\">".$dname."</a></td><td>".$data["description"]."</td><td>".$data["creation"]."</td></tr>";
				}
			}
			if($found == 1) $output .= $tmpoutput;
			return $output;
		}

		private function showSearchResults() {
			$output = "<h4>Résultats de la recherche : \"";
			$search = FS::$secMgr->checkAndSecuriseGetData("s");
			$output .= $search."\"</h4>";

			if(preg_match("#^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$#",$search)) {
				$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&node=".$search."\"</script>";
            }
			else if(preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$#",$search)) {
				$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&node=".$search."\"</script>";
			}
			else if(is_numeric($search) && $search < 4096) {
				$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&vlan=".$search."\"</script>";
			}
			else {
				if($device = FS::$pgdbMgr->GetOneData("device","ip","name = '".$search."'"))
					$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&d=".$search."\"</script>";
				else if($domain = FS::$pgdbMgr->GetOneData("node_nbt","domain","domain ILIKE '".$search."'"))
					$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&nb=".$domain."\"</script>";
				else if($nb = FS::$pgdbMgr->GetOneData("node_nbt","nbname","nbname ILIKE '".$search."'"))
					$output .= "<script type=\"text/javascript\">document.location.href=\"index.php?mod=".$this->mid."&node=".$nb."\"</script>";
				else {
					$found = 0;
					$query = FS::$dbMgr->Select("fss_switch_port_prises","ip,port","prise = '".$search."'");
					while($data = mysql_fetch_array($query)) {
						if($found == 0) $found = 1;
						$output .= "<table class=\"standardTable\"><tr><th>Prise</th><th>Switch</th><th>Port</th></tr>";
						$swname = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
						$convport = preg_replace("#\/#","-",$data["port"]);
						$output .= "<tr><td>".$search."</td><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$swname."\">".$swname."</a></td><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$swname."#".$convport."\">".$data["port"]."</a></td></tr>";
						$output .= "</table>";
					}

					if($found == 0)	$output .= FS::$iMgr->printError("Aucune donnée trouvée");
				}
			}
			return $output;
		}

		private function showNetbiosInfos() {
			$output = "<h4>Inventaire Netbios du domaine/groupe de travail : <i>";
                        $nb = FS::$secMgr->checkAndSecuriseGetData("nb");
                        $output .= $nb."</i></h4>";

			$tmpoutput = "<table class=\"standardTable\"><tr><th>Noeud</th><th>Nom</th><th>Utilisateur</th><th>Première vue</th><th>Dernière vue</th></tr>";
			$found = 0;
			$query = FS::$pgdbMgr->Select("node_nbt","mac,ip,nbname,nbuser,time_first,time_last","domain = '".$nb."'");
			while($data = pg_fetch_array($query)) {
				if($found == 0)	$found = 1;
				$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["mac"]."\">".$data["mac"]."</a></td><td>\\\\".$nb."\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["ip"]."\">".$data["ip"]."</a></td><td>".$data["time_first"]."</td><td>".$data["time_last"]."</td></tr>";
			}

			if($found == 0)
				$output .= FS::$iMgr->printError("Aucune donnée trouvée");
			else
				$output .= $tmpoutput."</table>";

			return $output;
		}

		protected function showNodeInfos() {
			$output = "<h4>Noeud ";
			$node = FS::$secMgr->checkAndSecuriseGetData("node");
			$output .= $node."</h4>";

			$mac = "";
			$ipaddr = "";

			if(preg_match("#^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$#",$node)) {
				$mac = $node;
			}
			else if(preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$#",$node)) {
				$ipaddr = $node;
				$query = FS::$pgdbMgr->Select("node_ip","mac","ip = '".$node."'");
				if($data = pg_fetch_array($query))
                                	$mac = $data["mac"];
			}
			else {
				$mac = FS::$pgdbMgr->GetOneData("node_nbt","mac","nbname = '".$node."'");
			}

			if($node == $ipaddr && $mac == "")
				$output .= FS::$iMgr->printError("Aucune donnée sur ce noeud");
			else {
				$tmpoutput = "<table class=\"standardTable\"><tr><th>MAC</th><th>Type</th><th>Equipement ou noeud</th><th>Première info</th><th>Dernière info</th></tr><tr>";
				$tmpoutput .= "<td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$mac."\">".$mac."</td><td>";
				$mactoip = array();
				$query = FS::$pgdbMgr->Select("node_ip","ip,time_first,time_last","mac = '".$mac."'");
				while($data = pg_fetch_array($query)) {
					$idx = count($mactoip);
					$mactoip[$idx] = array();
					$mactoip[$idx]["type"] = "Mac -> IP";
					$mactoip[$idx]["dat"] = "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["ip"]."\">".$data["ip"]."</a>";
					$mactoip[$idx]["fst"] = $data["time_first"];
					$mactoip[$idx]["lst"] = $data["time_last"];
				}

				$query = FS::$pgdbMgr->Select("node","switch,port,time_first,time_last","mac = '".$mac."'");
				while($data = pg_fetch_array($query)) {
                                        $idx = count($mactoip);
                                        $mactoip[$idx] = array();
                                        $mactoip[$idx]["type"] = "Switch port";
					$switch = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
					$piece = FS::$dbMgr->GetOneData("fss_switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
					$convport = preg_replace("#\/#","-",$data["port"]);
                                        $mactoip[$idx]["dat"] = "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$switch."\">".$switch."</a> [<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$switch."#".$convport."\">".$data["port"]."</a>]".($piece == NULL ? "" : " Prise ".$piece);
                                        $mactoip[$idx]["fst"] = $data["time_first"];
                                        $mactoip[$idx]["lst"] = $data["time_last"];
                                }

				$query = FS::$pgdbMgr->Select("node_nbt","nbname,domain,nbuser,time_first,time_last","mac = '".$mac."'");
                                while($data = pg_fetch_array($query)) {
					$idx = count($mactoip);
                                        $mactoip[$idx] = array();
                                        $mactoip[$idx]["type"] = "Netbios";
                                        $mactoip[$idx]["dat"] = ($data["domain"] != "" ? "\\\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&nb=".$data["domain"]."\">".$data["domain"]."</a>" : "")."\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a>";
                                        $mactoip[$idx]["fst"] = $data["time_first"];
                                        $mactoip[$idx]["lst"] = $data["time_last"];
				}

				for($i=0;$i<count($mactoip);$i++)
					$tmpoutput .= $mactoip[$i]["type"]."<br />";

				$tmpoutput .= "</td><td>";
				for($i=0;$i<count($mactoip);$i++)
                                        $tmpoutput .= $mactoip[$i]["dat"]."<br />";
				$tmpoutput .= "</td><td>";
				for($i=0;$i<count($mactoip);$i++) {
					$res = preg_split("#\.#",$mactoip[$i]["fst"]);
                                        $tmpoutput .= $res[0]."<br />";
				}
				$tmpoutput .= "</td><td>";
				for($i=0;$i<count($mactoip);$i++) {
					$res = preg_split("#\.#",$mactoip[$i]["lst"]);
                                        $tmpoutput .= $res[0]."<br />";
				}
				$tmpoutput .= "</td></tr></table>";
				if(count($mactoip) > 0)
					$output .= $tmpoutput;
				else
					$output .= FS::$iMgr->printError("Aucune donnée sur ce noeud");
			}
			return $output;
		}

		protected function showDeviceInfos() {
			$output = "<h4>Equipement ";
			$device = FS::$secMgr->checkAndSecuriseGetData("d");
			$filter = FS::$secMgr->checkAndSecuriseGetData("fltr");
			$od = FS::$secMgr->checkAndSecuriseGetData("od");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");

			if($od == NULL) $od = "port";
			else if($od == "desc") $od = "name";
			else if($od != "vlan" && $od != "prise" && $od != "port") $od = "port";

			$output .= $device." (";
			$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$device."'");
			$output .= $dip;

                        $dloc = FS::$pgdbMgr->GetOneData("device","location","name = '".$device."'");
			if($dloc != NULL)
	                        $output .= " - ".$dloc;
			$output .= ")</h4>";

			if($dip == NULL) {
				$output .= FS::$iMgr->printError("Equipement inexistant !");
				return $output;
			}

			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."\">Liste des ports</a> | ";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&sh=3\">Vue de façade</a> | ";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&sh=1\">Modules internes</a> | ";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&sh=2\">Détails</a> | ";
			$output .= "<a class=\"monoComponentt_a\" href=\"m-33.html\">Retour</a><br />";
			if($showmodule == 1) {
				$query = FS::$pgdbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
				$found = 0;
				$devmod = array();
				while($data = pg_fetch_array($query)) {
					if($found == 0) $found = 1;
					if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
					$idx = count($devmod[$data["parent"]]);
					$devmod[$data["parent"]][$idx] = array();
					$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
					$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
					$devmod[$data["parent"]][$idx]["name"] = $data["name"];
					$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
					$devmod[$data["parent"]][$idx]["type"] = $data["type"];
					$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
					$devmod[$data["parent"]][$idx]["model"] = $data["model"];
					$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
					$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
				}
				if($found == 1) {
					$output .= "<h4>Modules internes</h4>";
					$output .= $this->showDeviceModules($devmod,1);
				}

				return $output;
			}
			else if($showmodule == 2) {
				$query = FS::$pgdbMgr->Select("device","*","name ='".$device."'");
				if($data = pg_fetch_array($query)) {
					$output .= "<h4>Détails de l'équipement</h4>";
					$output .= "<table class=\"standardTable\">";
					$output .= "<tr><td>Nom</td><td>".$data["name"]."</td></tr>";
					$output .= "<tr><td>Lieu / contact</td><td>".$data["location"]." / ".$data["contact"]."</td></tr>";
					$output .= "<tr><td>Modèle / # de série</td><td>".$data["model"]." / ".$data["serial"]."</td></tr>";
					$output .= "<tr><td>OS / Version</td><td>".$data["os"]." / ".$data["os_ver"]."</td></tr>";
					$output .= "<tr><td>Description</td><td>".$data["description"]."</td></tr>";
					$output .= "<tr><td>Uptime</td><td>".$data["uptime"]."</td></tr>";
					$found = 0;
					$tmpoutput = "<tr><td>Energie</td><td>";

					$query2 = FS::$pgdbMgr->Select("device_power","module,power,status","ip = '".$dip."'");
					while($data2 = pg_fetch_array($query2)) {
						$found = 1;
						$query3 = FS::$pgdbMgr->Select("device_port_power","module,class","module = '".$data2["module"]."' AND ip = '".$dip."'");
						$pwcount = 0;
						while($data3 = pg_fetch_array($query3)) {
							if($data3["class"] == "class2") $pwcount += 7;
							else if($data3["class"] == "class3") $pwcount += 15;
						}
						$tmpoutput .= "Module ".$data2["module"]." : ".$pwcount." / ".$data2["power"]." Watts (statut: ";
						$tmpoutput .= ($data2["status"] == "on" ? "<span style=\"color: green;\">".$data2["status"]."</span>" : $data2["status"]);
						$tmpoutput .= ")<br />";
					}

					$tmpoutput .= "</td></tr>";
					if($found == 1) $output .= $tmpoutput;
					$output .= "<tr><td>Adresse IP</td><td>".$data["ip"]."</td></tr>";
					if($iswif == false) {
						$output .= "<tr><td>Adresse MAC</td><td>".$data["mac"]."</td></tr>";
						$output .= "<tr><td>Domaine VTP</td><td>".$data["vtp_domain"]."</td></tr>";
					}
					$output .= "</table>";
					return $output;
				}
			}
			else if($showmodule == 3) {
				$query = FS::$pgdbMgr->Select("device_module","parent,index,description,name,hw_ver,type,serial,fw_ver,sw_ver,model","ip ='".$dip."'","parent,name");
				$found = 0;
				$devmod = array();
				while($data = pg_fetch_array($query)) {
					if($found == 0) $found = 1;
					if(!isset($devmod[$data["parent"]])) $devmod[$data["parent"]] = array();
					$idx = count($devmod[$data["parent"]]);
					$devmod[$data["parent"]][$idx] = array();
					$devmod[$data["parent"]][$idx]["idx"] = $data["index"];
					$devmod[$data["parent"]][$idx]["desc"] = $data["description"];
					$devmod[$data["parent"]][$idx]["name"] = $data["name"];
					$devmod[$data["parent"]][$idx]["hwver"] = $data["hw_ver"];
					$devmod[$data["parent"]][$idx]["type"] = $data["type"];
					$devmod[$data["parent"]][$idx]["serial"] = $data["serial"];
					$devmod[$data["parent"]][$idx]["model"] = $data["model"];
					$devmod[$data["parent"]][$idx]["fwver"] = $data["fw_ver"];
					$devmod[$data["parent"]][$idx]["swver"] = $data["sw_ver"];
				}
				if($found == 1) {
					$output .= "<h4>Vue de façade</h4>";
					$output .= "<script>
						// 3750 48 ports	
						var c3750p48x = [59,71,87,98,115,126,143,154,171,182,199,210,227,238,254,266,299,310,326,338,355,366,382,394,
						411,422,438,450,466,478,494,506,539,550,567,578,595,606,623,635,651,663,679,690,707,718,735,746];
						var c3750p48y = 38;
						var c3750g48 = [[815,58],[866,58],[815,90],[866,90]];
						// poe
						var c3750poe48x = [61,61,89,89,117,117,144,144,172,172,200,200,229,229,254,254,300,300,327,327,356,356,383,383,
                                                412,412,439,439,467,467,495,495,540,540,568,568,596,596,624,624,652,652,680,680,708,708,736,736];
                                                var c3750impp48y = 66;
                                                var c3750pp48y = 94;
						// 3750 24 ports
						var c3750p24x = [355,366,383,394,411,422,439,450,467,478,495,506,596,607,624,635,652,663,680,691,708,719,736,747];
						var c3750p24y = 36;
						var c3750g24 = [[816,89],[867,89]];
						// poe
						var c3750poe24x = [357,357,385,385,413,413,441,441,470,470,498,498,597,597,625,625,652,652,681,681,710,710,738,738];
                                                var c3750impp24y = 65;
                                                var c3750pp24y = 94;
						// 2960 - 24 ports
						var c2960p24x = [411,424,440,451,467,478,495,507,523,536,552,564,593,605,621,633,649,660,676,687,704,717,732,744];
						var c2960p24y = 14;
						var c2960g24 = [[778,85],[808,85],[834,85],[865,85]];
						
						function drawContext(obj,type,ptab,gptab,poetab) {
							var canvas = document.getElementById(obj);
							var context = canvas.getContext(\"2d\");
							context.fillStyle = \"rgba(0,118,176,0)\";
							context.fillRect(0, 0, context.canvas.width, context.canvas.height);
							context.translate(context.canvas.width/2, context.canvas.height/2);
							context.translate(-context.canvas.width/2, -context.canvas.height/2);
							context.beginPath();
							context.moveTo(-2,-2);
							context.lineTo(892,-2);
							context.lineTo(892,119);
							context.lineTo(-2,119);
							context.lineTo(-2,-2);	
							context.closePath(); // complete custom shape
	
							context.moveTo(0,0);
							var img = new Image();
							img.onload = function() {
								context.drawImage(img, 0,0,892,119);
								var normportX = null; var normportY = null;
								var trunkport = null; var icsize = null;
								var poeX = null; var poePY = null; var poeIMPY = null;
								switch(type) {
									case 1: normportX = c3750p48x; normportY = c3750p48y; trunkport = c3750g48; icsize = 7; 
										poeX = c3750poe48x; poePY = c3750pp48y; poeIMPY = c3750impp48y; break;
									case 2: normportX = c3750p24x; normportY = c3750p24y; trunkport = c3750g24; icsize = 7; break;
									case 3: normportX = c2960p24x; normportY = c2960p24y; trunkport = c2960g24; icsize = 6; break;
									case 4: break;
								}
								for(i=0;i<normportX.length;i++) {
									if(ptab[i] == 0)
										context.fillStyle = \"rgba(200, 0, 0, 0.5)\";
									else if(ptab[i] == 1)
										context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
									else if(ptab[i] == 2)
										context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
									else
										context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
									context.fillRect(normportX[i], normportY, icsize, icsize);
									context.fillStyle = \"rgba(200, 200, 0, 0.6)\";
                                                                        if(poetab[i] == 1)
                                                                                context.fillText(\"7.0\",poeX[i], (i%2 == 0 ? poeIMPY : poePY));
                                                                        else if(poetab[i] == 2)
                                                                                context.fillText(\"15.0\",poeX[i]-4, (i%2 == 0 ? poeIMPY : poePY));
								}
								for(i=0;i<trunkport.length;i++) {
										if(gptab[i] == 0)
												context.fillStyle = \"rgba(255, 0, 0, 0.6)\";
										else if(gptab[i] == 1)
												context.fillStyle = \"rgba(255, 150, 0, 0.0)\";
										else if(gptab[i] == 2)
												context.fillStyle = \"rgba(0, 255, 50, 0.6)\";
										else
												context.fillStyle = \"rgba(255, 150, 0, 0.6)\";
										context.fillRect(trunkport[i][0], trunkport[i][1], icsize, icsize);
								}
							}
							switch(type) {
								case 1:	img.src = '/uploads/WS-C3750-48PS-S_front.jpg'; break;
								case 2:	img.src = '/uploads/WS-C3750-24PS-S_front.jpg'; break;
								case 3: img.src = '/uploads/2960-24.jpg'; break;
								case 4: img.src = '/uploads/2960-48.jpg'; break;
							}
						}
					</script>";
					$swlist = $this->getDeviceSwitches($devmod,1);
					$swlist = preg_split("#\/#",$swlist);
					for($i=count($swlist)-1;$i>=0;$i--) {
						switch($swlist[$i]) {
							case "WS-C3750-48P": case "WS-C3750-48TS": case "WS-C3750-48PS": case "WS-C3750G-48TS": case "WS-C3750-48PS": { // 100 Mbits switches
								$poearr = array();
								// POE States
								$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
								while($data = pg_fetch_array($query)) {
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									switch($data["class"]) {
										case "class0": $poearr[$pid] = 0; break;
										case "class2": $poearr[$pid] = 1; break;
										case "class3": $poearr[$pid] = 2; break;
									}
								}

								$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=1;$j<=count($arr_res);$j++) {
									$output .= $arr_res[$j];
									if($j < count($arr_res)) $output .= ",";
								}
								$output .= "]; var gptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=1;$j<=count($arr_res);$j++) {
									$output .= $arr_res[$j];
									if($j < count($arr_res)) $output .= ",";
								}
								$output .= "]; var poetab = [";
								for($j=1;$j<=count($poearr);$j++) {
									$output .= $poearr[$j];
									if($j < count($poearr)) $output .= ",";
								}
								$output .= "]; drawContext('canvas_".($i+1)."',1,ptab,gptab,poetab);</script>";
								break;
							}
							case "WS-C3750-24TS": case "WS-C3750G-24TS": case "WS-C3750G-24WS": case "WS-C3750G-24T": case "WS-C3750-FS":
							case "WS-C3750-24PS": case "WS-C3750-24P": // 100 Mbits switches
								$poearr = array();
								// POE States
								$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port LIKE 'FastEthernet".($i+1)."/0/%'");
								while($data = pg_fetch_array($query)) {
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									switch($data["class"]) {
										case "class0": $poearr[$pid] = 0; break;
										case "class1": $poearr[$pid] = 1; break;
										case "class2": $poearr[$pid] = 2; break;
									}
								}

								$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'FastEthernet".($i+1)."/0/%'","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=1;$j<=count($arr_res);$j++) {
									$output .= $arr_res[$j];
									if($j < count($arr_res)) $output .= ",";
								}
								$output .= "]; var gptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port LIKE 'GigabitEthernet".($i+1)."/0/%'","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=1;$j<=count($arr_res);$j++) {
									$output .= $arr_res[$j];
									if($j < count($arr_res)) $output .= ",";
								}
                                                               $output .= "]; var powport = [";
								for($j=1;$j<=count($poearr);$j++) {
									$output .= $poearr[$j];
									if($j < count($poearr)) $output .= ",";
								}
								$output .= "]; drawContext('canvas_".($i+1)."',2,ptab,gptab,powport);</script>";
								break;
							case "WS-C2960S-24TS-L": // Gbit switches
								$poearr = array();
								$portlist = "";
								for($j=1;$j<25;$j++) {
									$portlist .= "'GigabitEthernet".($i+1)."/0/".$j."'";
									if($j < 24)
										$portlist .= ",";
								}
								// POE States
								$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'  AND port IN (".$portlist.")");
								while($data = pg_fetch_array($query)) {
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									switch($data["class"]) {
										case "class0": $poearr[$pid] = 0; break;
										case "class1": $poearr[$pid] = 1; break;
										case "class2": $poearr[$pid] = 2; break;
									}
								}

								$output .= "<canvas id=\"canvas_".($i+1)."\" width=\"892\" height=\"119\"></canvas><script> var ptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN (".$portlist.")","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=1;$j<=count($arr_res);$j++) {
									$output .= $arr_res[$j];
									if($j < count($arr_res)) $output .= ",";
								}
								$output .= "]; var gptab = [";
								$query = FS::$pgdbMgr->Select("device_port","port,up,up_admin","ip ='".$dip."' AND port IN ('GigabitEthernet".($i+1)."/0/25', 'GigabitEthernet".($i+1)."/0/26',
										'GigabitEthernet".($i+1)."/0/27','GigabitEthernet".($i+1)."/0/28')","port");
								$arr_res = array();
								while($data = pg_fetch_array($query)) {
									if(preg_match("#unrouted#",$data["port"]))
										continue;
									$pid = preg_split("#\/#",$data["port"]);
									$pid = $pid[2];
									if($data["up_admin"] == "down")
										$arr_res[$pid] = 0;
									else if($data["up_admin"] == "up" && $data["up"] == "down")
										$arr_res[$pid] = 1;
									else if($data["up"] == "up")
										$arr_res[$pid] = 2;
									else
										$arr_res[$pid] = 3;
								}

								uksort($arr_res,"strnatcasecmp");
								for($j=25;$j<=(25+count($arr_res));$j++) {
									$output .= $arr_res[$j];
									if($j < (24+count($arr_res))) $output .= ",";
								}
                                                               $output .= "]; var powport = [";
								for($j=1;$j<=count($poearr);$j++) {
									$output .= $poearr[$j];
									if($j < count($poearr)) $output .= ",";
								}
								$output .= "]; drawContext('canvas_".($i+1)."',3,ptab,gptab,powport);</script>";
								break;
							default: break;
						}
					}
				}
				return $output;			
			}

			$iswif = (preg_match("#AIR#",FS::$pgdbMgr->GetOneData("device","model","name = '".$device."'")) ? true : false);

			if($iswif == false) {
				$poearr = array();
				// POE States
				$query = FS::$pgdbMgr->Select("device_port_power","port,class","ip = '".$dip."'");
				while($data = pg_fetch_array($query))
					$poearr[$data["port"]] = $data["class"];
			}

			$prisearr = array();
			$query = FS::$dbMgr->Select("fss_switch_port_prises","port,prise","ip = '".$dip."'");
			while($data = mysql_fetch_array($query))
                                $prisearr[$data["port"]] = $data["prise"];

			$found = 0;
			if($iswif == false) {
				// Script pour modifier le nom de la prise
				$output .= "<script type=\"text/javascript\">";
				$output .= "function modifyPrise(src,sbmit,sw_,swport_,swpr_) { ";
				$output .= "if(sbmit == true) { ";
				$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=2', { sw: sw_, swport: swport_, swprise: document.getElementsByName(swpr_)[0].value }, function(data) { ";
				$output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
				$output .= "}); } ";
				$output .= "else $(src).toggle(); }";
				$output .= "</script>";

				// Script pour modifier le nom du port
				$output .= "<script type=\"text/javascript\">";
                                $output .= "function modifyPortDesc(src,sbmit,sw_,swport_,swds_,swdsc_) { ";
                                $output .= "if(sbmit == true) { ";
                                $output .= "$.post('index.php?at=3&mod=".$this->mid."&act=3', { sw: sw_, swport: swport_, swdesc: document.getElementsByName(swds_)[0].value, wr: document.getElementsByName(swdsc_)[0].checked }, function(data) { ";
                                $output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
                                $output .= "}); } ";
                                $output .= "else $(src).toggle(); }";
                                $output .= "</script>";

				// Script pour modifier le statut du port
                                $output .= "<script type=\"text/javascript\">";
                                $output .= "function modifyPortState(src,sbmit,sw_,swport_,swst_,swstc_) { ";
                                $output .= "if(sbmit == true) { ";
                                $output .= "$.post('index.php?at=3&mod=".$this->mid."&act=4', { sw: sw_, swport: swport_, swst: document.getElementsByName(swst_)[0].checked, wr: document.getElementsByName(swstc_)[0].checked }, function(data) { ";
                                $output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
                                $output .= "}); } ";
                                $output .= "else $(src).toggle(); }";
                                $output .= "</script>";

				// Script pour modifier le duplex du port
                                /*$output .= "<script type=\"text/javascript\">";
                                $output .= "function modifyPortDuplex(src,sbmit,sw_,swport_,swdp_,swdpc_) { ";
                                $output .= "if(sbmit == true) { ";
                                $output .= "$.post('index.php?at=3&mod=".$this->mid."&act=5', { sw: sw_, swport: swport_, swdp: document.getElementsByName(swdp_)[0].value, wr: document.getElementsByName(swdpc_)[0].checked }, function(data) { ";
                                $output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
                                $output .= "}); } ";
                                $output .= "else $(src).toggle(); }";
                                $output .= "</script>";*/

			}
			$tmpoutput = "<table class=\"standardTable\"><tr><th><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&od=port\">Port</a></th><th>";
			$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&od=desc\">Description</a></th><th>Prise</th><th>MAC Interface</th><th>Up (Link/Admin)</th>";
			if($iswif == false)
				$tmpoutput .= "<th>Duplex (Link/Admin)</th>";
			$tmpoutput .= "<th>Vitesse</th>";
			if($iswif == false)
				$tmpoutput .= "<th>POE</th>";
			$tmpoutput .= "<th><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&od=vlan\">Vlan natif</a></th><th>";
			if($iswif == true) $tmpoutput .= "Canal</th><th>Puissance</th><th>SSID";
			else $tmpoutput .= "Vlan Trunk</th><th>Equipements connectés</th></tr>";
			$query = FS::$pgdbMgr->Select("device_port","port,name,mac,up,up_admin,duplex,duplex_admin,speed,vlan","ip ='".$dip."'",$od);
			while($data = pg_fetch_array($query)) {
				if(preg_match("#unrouted#",$data["port"]))
					continue;
				$filter_ok = 0;
				if($filter == NULL) $filter_ok = 1;

				if($found == 0) $found = 1;
				$convport = preg_replace("#\/#","-",$data["port"]);
				$swpdata = (isset($prisearr[$data["port"]]) ? $prisearr[$data["port"]] : "");
				$tmpoutput2 = "<tr id=\"".$convport."\"><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$device."&p=".$data["port"]."\">".$data["port"]."</a></td><td>";

				// Editable Desc
				$tmpoutput2 .= "<div id=\"swds_".$convport."\">";
                                $tmpoutput2 .= "<a onclick=\"javascript:modifyPortDesc('#swds_".$convport." a',false);\"><div id=\"swds_".$convport."l\" class=\"modport\">";
                                $tmpoutput2 .= ($data["name"] == "" ? "Modifier" : $data["name"]);
                                $tmpoutput2 .= "</div></a><a style=\"display: none;\">";
                                $tmpoutput2 .= FS::$iMgr->addInput("swdesc-".$convport,$data["name"],30,40);
				$tmpoutput2 .= "<br />Sauver ? ".FS::$iMgr->addCheck("swdescchk-".$convport);
                                $tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPortDesc('#swds_".$convport."',true,'".$dip;
				$tmpoutput2 .= "','".$data["port"]."','swdesc-".$convport."','swdescchk-".$convport."');\" />";
                                $tmpoutput2 .= "</a></div>";

				$tmpoutput2 .= "</td><td>";
				// Editable piece
				$tmpoutput2 .= "<div id=\"swpr_".$convport."\">";
				$tmpoutput2 .= "<a onclick=\"javascript:modifyPrise('#swpr_".$convport." a',false);\"><div id=\"swpr_".$convport."l\" class=\"modport\">";
				$tmpoutput2 .= ($swpdata == "" ? "Modifier" : $swpdata);
				$tmpoutput2 .= "</div></a><a style=\"display: none;\">";
        	                $tmpoutput2 .= FS::$iMgr->addInput("swprise-".$convport,$swpdata,10,10);
                	        $tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPrise('#swpr_".$convport."',true,'".$dip."','".$data["port"]."','swprise-".$convport."');\" />";
				$tmpoutput2 .= "</a></div>";
				$tmpoutput2 .= "</td><td>".$data["mac"]."</td><td>";
				// Editable state
				$tmpoutput2 .= "<div id=\"swst_".$convport."\">";
				$tmpoutput2 .= "<a onclick=\"javascript:modifyPortState('#swst_".$convport." a',false);\"><div id=\"swst_".$convport."l\" class=\"modport\">";
				if($data["up_admin"] == "down")
					$tmpoutput2 .= "<span style=\"color: red;\">Eteint</span>";
				else if($data["up_admin"] == "up" && $data["up"] == "down")
					$tmpoutput2 .= "<span style=\"color: orange;\">Inactif</span>";
				else if($data["up"] == "up")
					$tmpoutput2 .= "<span style=\"color: black;\">Actif</span>";
				else
					$tmpoutput2 .= "unk";
				$tmpoutput2 .= "</div></a><a style=\"display: none;\">";
				$tmpoutput2 .= "Eteindre: ".FS::$iMgr->addCheck("swst-".$convport,$data["up_admin"] == "down" ? true : false);
				$tmpoutput2 .= "<br />Sauver ? ".FS::$iMgr->addCheck("swstchk-".$convport);
				$tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPortState('#swst_".$convport."',true,'".$dip."','".$data["port"]."',";
				$tmpoutput2 .= "'swst-".$convport."','swstchk-".$convport."');\" />";
				$tmpoutput2 .= "</a></div>";
				$tmpoutput2 .= "</td><td>";
				if($iswif == false) {

					// Editable duplex
	                                $tmpoutput2 .= "<div id=\"swdp_".$convport."\">";
					$tmpoutput2 .= "<a onclick=\"javascript:modifyPortDuplex('#swdp_".$convport." a',false);\"><div id=\"swdp_".$convport."l\" class=\"modport\"><span style=\"color: black;\">";
					$dup = (strlen($data["duplex"]) > 0 ? $data["duplex"] : "[NA]");
					$dupadm = (strlen($data["duplex_admin"]) > 0 ? $data["duplex_admin"] : "[NA]");
					if($dup == "half" && $dupadm != "half") $dup = "<span style=\"color: red;\">half</span>";
					$tmpoutput2 .= $dup." / ".$dupadm;
					$tmpoutput2 .= "</span></div></a><a style=\"display: none;\">Duplex :";
					$tmpoutput2 .= "<select name=\"swdp-".$convport."\" id=\"swdp-".$convport."\">";
					$tmpoutput2 .= FS::$iMgr->addElementToList("auto",4,$dupadm == "auto" ? true : false);
					$tmpoutput2 .= FS::$iMgr->addElementToList("half",1,$dupadm == "half" ? true : false);
					$tmpoutput2 .= FS::$iMgr->addElementToList("full",2,$dupadm == "full" ? true : false);
					$tmpoutput2 .= "</select>";
					$tmpoutput2 .= "<br />Sauver ? ".FS::$iMgr->addCheck("swdpchk-".$convport);
					$tmpoutput2 .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPortDuplex('#swdp_".$convport."',true,'".$dip."','".$data["port"]."',";
	                                $tmpoutput2 .= "'swdp-".$convport."','swdpchk-".$convport."');\" />";
        	                        $tmpoutput2 .= "</a></div>";

					$tmpoutput2 .= "</td><td>";
				}
				$tmpoutput2 .= $data["speed"]."</td><td>";

				if($iswif == false) {
					// POE
					if(isset($poearr[$data["port"]])) {
						if($poearr[$data["port"]] == "class0") $tmpoutput2 .= "0.0 Watts";
						else if($poearr[$data["port"]] == "class2") $tmpoutput2 .= "7.0 Watts";
						else if($poearr[$data["port"]] == "class3") $tmpoutput2 .= "15.0 Watts";
						else $tmpoutput2 .= "Unk class";
					}
					else
					$tmpoutput2 .= "N";
					$tmpoutput2 .= "</td><td>";
				}

				$query2 = FS::$pgdbMgr->Select("device_port_vlan","vlan,native","ip = '".$dip."' AND port = '".$data["port"]."'","vlan");

				$nvlan = $data["vlan"];
				$vlanlist = "";
				$vlancount = 0;
				while($data2 = pg_fetch_array($query2)) {
					if($data2["native"] == "t" && $data2["vlan"] != 1) $nvlan = $data2["vlan"];
					if($data2["vlan"] == $filter) $filter_ok = 1;
					if($vlancount == 3) {
						$vlancount = 0;
						$vlanlist .= "<br />";
					}
					$vlanlist .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&vlan=".$data2["vlan"]."\">".$data2["vlan"]."</a>,";
					$vlancount++;
				}
				if($iswif == false) {
                                        $tmpoutput2 .= "<a>";
                                        $tmpoutput2 .= $nvlan;
                                        $tmpoutput2 .= "</a></td><td>";
                                }
				else
					$tmpoutput2 .= $nvlan."</td><td>";

				if($iswif == false) {
					$tmpoutput2 .= substr($vlanlist,0,strlen($vlanlist)-1);

				}
				if($iswif == false) {
					$tmpoutput2 .= "</td><td>";
					$query2 = FS::$pgdbMgr->Select("node","mac","switch = '".$dip."' AND port = '".$data["port"]."'","mac");
					while($data2 = pg_fetch_array($query2)) {
						$tmpoutput2 .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data2["mac"]."\">".$data2["mac"]."</a><br />";
						$query3 = FS::$pgdbMgr->Select("node_ip","ip","mac = '".$data2["mac"]."'");
						while($data3 = pg_fetch_array($query3)) {
							$tmpoutput2 .= "&nbsp;&nbsp;<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
							$query4 = FS::$pgdbMgr->Select("node_nbt","nbname,domain,nbuser","mac = '".$data2["mac"]."' AND ip = '".$data3["ip"]."'");
							if($data4 = pg_fetch_array($query4)) {
								if($data4["domain"] != "")
									$tmpoutput2 .= "&nbsp;&nbsp;\\\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&nb=".$data4["domain"]."\">".$data4["domain"]."</a>\\<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
								else
									$tmpoutput2 .= "&nbsp;&nbsp;<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data4["nbname"]."\">".$data4["nbname"]."</a><br />";
								$tmpoutput2 .= "&nbsp;&nbsp;".($data4["nbuser"] == "" ? "[UNK]" : $data4["nbuser"])."@<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&node=".$data3["ip"]."\">".$data3["ip"]."</a><br />";
							}
						}
					}
				}
				else {
					$channel = FS::$pgdbMgr->GetOneData("device_port_wireless","channel","ip = '".$dip."' AND port = '".$data["port"]."'");
					$power = FS::$pgdbMgr->GetOneData("device_port_wireless","power","ip = '".$dip."' AND port = '".$data["port"]."'");
					$ssid = FS::$pgdbMgr->GetOneData("device_port_ssid","ssid","ip = '".$dip."' AND port = '".$data["port"]."'");
					$tmpoutput2 .= $channel."</td><td>".$power."</td><td>".$ssid;
				}
				$tmpoutput2 .= "</td></tr>";

				if($filter_ok == 1)
					$tmpoutput .= $tmpoutput2;
			}

			if($found != 0) {
				$output .= $tmpoutput;
				$output .= "</table>";
			}
			else
				$output .= FS::$iMgr->printError("Impossible de trouver des informations sur l'équipement");

			return $output;
		}

		private function showDeviceModules($devmod,$idx,$level=10) {
			if($level == 0)
				return "";
			if(!isset($devmod[$idx])) return "";
			$output = "<ul>";
			for($i=0;$i<count($devmod[$idx]);$i++) {
				$output .= "<li>" .$devmod[$idx][$i]["desc"]." (".$devmod[$idx][$i]["name"].") ";
				if(strlen($devmod[$idx][$i]["hwver"]) > 0)
					$output .= "[hw: ".$devmod[$idx][$i]["hwver"]."] ";
				if(strlen($devmod[$idx][$i]["fwver"]) > 0)
                                        $output .= "[fw: ".$devmod[$idx][$i]["fwver"]."] ";
				if(strlen($devmod[$idx][$i]["swver"]) > 0)
                                        $output .= "[sw: ".$devmod[$idx][$i]["swver"]."] ";
				if(strlen($devmod[$idx][$i]["serial"]) > 0)
                                        $output .= "[serial: ".$devmod[$idx][$i]["serial"]."] ";
				$output .= "/ Type: ".$devmod[$idx][$i]["type"];
				if(strlen($devmod[$idx][$i]["model"]) > 0)
                                        $output .= " Modèle: ".$devmod[$idx][$i]["model"];
				if($idx != 0)
					$output .= $this->showDeviceModules($devmod,$devmod[$idx][$i]["idx"],$level-1);
				$output .= "</li>";

			}
			$output .= "</ul>";
			return $output;
		}

		private function getDeviceSwitches($devmod,$idx) {
			if(!isset($devmod[$idx])) return "";
			$output = "";
			for($i=0;$i<count($devmod[$idx]);$i++) {
				$output .= $devmod[$idx][$i]["desc"];
				if($i+1<count($devmod[$idx])) $output .= "/";
			}
			return $output;
		}

		protected function showDeviceList() {
			$output = "<h4>Liste des Equipements</h4>";
                        $query = FS::$pgdbMgr->Select("device","*","","name");

			$foundsw = 0;
			$foundwif = 0;
			$outputswitch = "<h4>Switches et routeurs</h4>";
			$outputswitch .= "<table class=\"standardTable\"><tr><th>Nom</th><th>Adresse IP</th><th>Adresse MAC</th><th>Modèle</th><th>OS</th><th>Lieu</th><th>Numéro de série</th></tr>";

			$outputwifi = "<h4>Bornes WiFi</h4>";
			$outputwifi .= "<table class=\"standardTable\"><tr><th>Nom</th><th>Adresse IP</th><th>Modèle</th><th>OS</th><th>Lieu</th><th>Numéro de série</th></tr>";
			while($data = pg_fetch_array($query)) {
				if(preg_match("#AIR#",$data["model"])) {
					if($foundwif == 0) $foundwif = 1;
					$outputwifi .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>";
	                                $outputwifi .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
				}
				else {
					if($foundsw == 0) $foundsw = 1;
					$outputswitch .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&d=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["ip"]."</td><td>".$data["mac"]."</td><td>";
					$outputswitch .= $data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>".$data["location"]."</td><td>".$data["serial"]."</td></tr>";
				}
			}
			if($foundsw != 0) {
				$output .= $outputswitch;
				$output .= "</table>";
			}
			if($foundwif != 0) {
                                $output .= $outputwifi;
                                $output .= "</table>";
                        }

			if($foundsw == 0 && $foundwif == 0)
				$output .= FS::$iMgr->printError("Aucun equipement trouvé");
			return $output;
		}

		private function getPortDesc($device,$portname) {
			return $this->getFieldForPort($device, $portname, "ifAlias");
		}

		private function getFieldForPort($device, $portname, $field) {
			if($device == "" || $portname == "" || $field == "")
				return NULL;

			$resw = FS::$snmpMgr->walk($device,"ifDescr");
			if(count($resw) < 1)
				return NULL;

                        $resultwalk = "";
                        for($i=0;$i<count($resw);$i++) {
                                $expl = explode(" ",$resw[$i],2);
                                if($expl[1] == $portname)
                                        $resultwalk = $i;
                        }

                        if($resultwalk == "")
                                return NULL;

                        $result = FS::$snmpMgr->get($device,"ifAlias.".$resultwalk);
			if(count($result) < 1)
				return NULL;
                        $result = explode(" ",$result,2);

			return $result;
		}

		public function setPortState($device,$portname,$value) {
			if($value != 1 && $value != 2)
				return NULL;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.2.1.2.2.1.7","i",$value);
		}

		public function setPortStateWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || ($value != 1 && $value != 2))
				return NULL;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.2.1.2.2.1.7","i",$value);
		}

		public function setPortDesc($device,$portname,$value) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"ifAlias","s",$value);
		}

		public function setPortDescWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"ifAlias","s",$value);
		}

		public function setPortDuplex($device,$portname,$value) {
			if($value < 1 || $value > 4)
				return NULL;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
                }

		public function setPortDuplexWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || $value < 1 || $value > 4)
				return NULL;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.5.1.4.1.1.10","i",$value);
                }


		public function setSwitchAccessVLAN($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value))
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2","i",$value);
		}

		public function setSwitchAccessVLANWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value))
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.68.1.2.2.1.2","i",$value);
		}

		public function setSwitchTrunkVlan($device,$portname,$values) {
			if(!preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			$res = preg_split("/,/",$values);

			$str = "";
			$tmpstr="";
			$count=0;
			for($i=0;$i<1024;$i++) {
			        if(in_array($i,$res))
                			$tmpstr .= "1";
		        	else
                			$tmpstr .= "0";
			        $count++;
				if($count == 8) {
		                	$tmpchar = base_convert($tmpstr,2,16);
                			if(strlen($tmpchar) == 1)
			                        $tmpchar = "0".$tmpchar;
        	        		$str .= $tmpchar;

			                $tmpstr = "";
                			$count = 0;
		        	}
			}

			$tmpstr = "";
			$str2 = "";
			$count=0;
			for($i=0;$i<1024;$i++) {
                                $tmpstr .= "0";
                                $count++;
                                if($count == 8) {
                                        $tmpchar = base_convert($tmpstr,2,16);
                                        if(strlen($tmpchar) == 1)
                                                $tmpchar = "0".$tmpchar;
                                        $str2 .= $tmpchar;

                                        $tmpstr = "";
                                        $count = 0;
                                }
                        }
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str2);

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str);
		}

		public function setSwitchTrunkVlanWithPID($device,$pid,$values) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !preg_match("#^(([1-9]([0-9]){0,3}),)*([1-9]([0-9]){0,3})$#",$values))
				return -1;

			

			$res = preg_split("/,/",$values);

			$str = "";
			$tmpstr="";
			$count=0;
			for($i=0;$i<1024;$i++) {
			        if(in_array($i,$res))
                			$tmpstr .= "1";
		        	else
                			$tmpstr .= "0";
			        $count++;
				if($count == 8) {
		                	$tmpchar = base_convert($tmpstr,2,16);
                			if(strlen($tmpchar) == 1)
			                        $tmpchar = "0".$tmpchar;
        	        		$str .= $tmpchar;

			                $tmpstr = "";
                			$count = 0;
		        	}
			}

			$tmpstr = "";
			$str2 = "";
			$count=0;
			for($i=0;$i<1024;$i++) {
                                $tmpstr .= "0";
                                $count++;
                                if($count == 8) {
                                        $tmpchar = base_convert($tmpstr,2,16);
                                        if(strlen($tmpchar) == 1)
                                                $tmpchar = "0".$tmpchar;
                                        $str2 .= $tmpchar;

                                        $tmpstr = "";
                                        $count = 0;
                                }
                        }
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str2);
			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str2);

			return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str);
		}

		public function setSwitchNoTrunkVlan($device,$portname) {
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			$tmpstr1 = "0";
			$tmpstr4 = "1";
                        $str1 = "";
			$str23 = "";
			$str4 = "";
                        $count=1;
                        for($i=1;$i<1023;$i++) {
                                $tmpstr1 .= "1";
				$tmpstr4 .= "1";
                                $count++;
				if($i == 1022) {
					$tmpstr1 .= "1";
					$tmpstr4 .= "0";
					$count++;
				}
                                if($count == 8) {
                                        $tmpchar1 = base_convert($tmpstr1,2,16);
					$tmpchar4 = base_convert($tmpstr4,2,16);
                                        $str1 .= $tmpchar1;
					$str4 .= $tmpchar4;
                                        $tmpstr1 = "";
					$tmpstr4 = "";
                                        $count = 0;
                                }
                        }

			$tmpstr = "";
                        $str23 = "";
                        $count=0;
                        for($i=0;$i<1024;$i++) {
                                $tmpstr .= "1";
                                $count++;
                                if($count == 8) {
                                        $tmpchar = base_convert($tmpstr,2,16);
                                        if(strlen($tmpchar) == 1)
                                                $tmpchar = "0".$tmpchar;
                                        $str23 .= $tmpchar;

                                        $tmpstr = "";
                                        $count = 0;
                                }
                        }

			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str23);
                        $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str23);
                        $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str4);
                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str1);
		}

		public function setSwitchNoTrunkVlanWithPID($device,$pid) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1)
				return -1;

			$tmpstr1 = "0";
			$tmpstr4 = "1";
                        $str1 = "";
			$str23 = "";
			$str4 = "";
                        $count=1;
                        for($i=1;$i<1023;$i++) {
                                $tmpstr1 .= "1";
				$tmpstr4 .= "1";
                                $count++;
				if($i == 1022) {
					$tmpstr1 .= "1";
					$tmpstr4 .= "0";
					$count++;
				}
                                if($count == 8) {
                                        $tmpchar1 = base_convert($tmpstr1,2,16);
					$tmpchar4 = base_convert($tmpstr4,2,16);
                                        $str1 .= $tmpchar1;
					$str4 .= $tmpchar4;
                                        $tmpstr1 = "";
					$tmpstr4 = "";
                                        $count = 0;
                                }
                        }

			$tmpstr = "";
                        $str23 = "";
                        $count=0;
                        for($i=0;$i<1024;$i++) {
                                $tmpstr .= "1";
                                $count++;
                                if($count == 8) {
                                        $tmpchar = base_convert($tmpstr,2,16);
                                        if(strlen($tmpchar) == 1)
                                                $tmpchar = "0".$tmpchar;
                                        $str23 .= $tmpchar;

                                        $tmpstr = "";
                                        $count = 0;
                                }
                        }

			$this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.17","x",$str23);
                        $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.18","x",$str23);
                        $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.19","x",$str4);
                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.4","x",$str1);
		}

		public function setSwitchTrunkNativeVlan($device,$portname,$value) {
			if(!FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;
			
			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}

		public function setSwitchTrunkNativeVlanWithPID($device,$pid,$value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value > 1005)
				return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.5","i",$value);
		}

		public function setSwitchTrunkEncap($device,$portname,$value) {
                        if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
                                return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.3","i",$value);
                }

		public function setSwitchTrunkEncapWithPID($device,$pid,$value) {
                        if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
                                return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.3","i",$value);
                }

		public function setSwitchportMode($device, $portname, $value) {
			if(!FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
                                return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13","i",$value);
		}

		public function setSwitchportModeWithPID($device, $pid, $value) {
			if(!FS::$secMgr->isNumeric($pid) || $pid == -1 || !FS::$secMgr->isNumeric($value) || $value < 1 || $value > 5)
                                return -1;

                        return $this->setFieldForPortWithPID($device,$pid,"1.3.6.1.4.1.9.9.46.1.6.1.1.13","i",$value);
		}

		private function setFieldForPort($device, $portname, $field, $vtype, $value) {
			if($device == "" || $portname == "" || $field == "" || $vtype == "")
				return -1;

			$pid = $this->getPortId($device,$portname);
			if($pid == -1)
				return -1;

			return $this->setFieldForPortWithPID($device,$pid,$field,$vtype,$value);
		}

		private function setFieldForPortWithPID($device, $pid, $field, $vtype, $value) {
			if($device == "" || $field == "" || $pid == "" || $vtype == "" || !FS::$secMgr->isNumeric($pid))
				return -1;
			FS::$snmpMgr->set($device,$field.".".$pid,$vtype,$value);
			return 0;
		}

		public function getPortId($device,$portname) {
			$out = "";
			exec("snmpwalk -v 2c -c ".SNMPConfig::$SNMPReadCommunity." ".$device." ifDescr | grep ".$portname,$out);
			if(strlen($out[0]) < 5)
				return -1;
			$out = explode(" ",$out[0]);
			$out = explode(".",$out[0]);
			if(!FS::$secMgr->isNumeric($out[1]))
				return -1;
			return $out[1];
		}

		public function writeMemory($device) {
			$rand = rand(1,100);
			FS::$snmpMgr->setInt($device,"1.3.6.1.4.1.9.9.96.1.1.1.1.2.".$rand,"1");
			FS::$snmpMgr->setInt($device,"1.3.6.1.4.1.9.9.96.1.1.1.1.3.".$rand,"4");
			FS::$snmpMgr->setInt($device,"1.3.6.1.4.1.9.9.96.1.1.1.1.4.".$rand,"3");
			FS::$snmpMgr->setInt($device,"1.3.6.1.4.1.9.9.96.1.1.1.1.14.".$rand,"1");

			FS::$snmpMgr->get($device,"1.3.6.1.4.1.9.9.96.1.1.1.1.10.".$rand);

			return 0;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$search = FS::$secMgr->checkAndSecurisePostData("search");
					header("Location: index.php?mod=".$this->mid."&s=".$search);
					return;
				case 2:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$prise = FS::$secMgr->checkAndSecurisePostData("swprise");
					if($port == NULL || $sw == NULL /*|| $prise != NULL && !preg_match("#^[A-Z][1-9]\.[1-9A-Z][0-9]?\.[1-9][0-9A-Z]?$#",$prise)*/) {
						echo "ERROR";
						return;
					}

					if($prise == NULL) $prise = "";
					// Modify prise for switch port
					$sql = "REPLACE INTO fss_switch_port_prises VALUES ('".$sw."','".$port."','".$prise."')";
					mysql_query($sql);

					if($prise != "") {
						$piecetab = preg_split("#\.#",$prise);
						if(isset($piecetab[0]) && isset($piecetab[1]) && isset($piecetab[2]) && !isset($piecetab[3])) {
							if(FS::$secMgr->isNumeric($piecetab[1]) && FS::$secMgr->isNumeric($piecetab[2])) {
								$pname = $piecetab[0].".".$piecetab[1];
								for($i=1;$i<=$piecetab[2];$i++) {
									mysql_query("INSERT IGNORE INTO fss_piece_prises VALUES ('".$pname."','".$i."','')");
								}
							}
						}
					}
					// Return text for AJAX call
					if($prise == "") $prise = "Modifier";
					echo $prise;
					return;
				case 3:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$desc = FS::$secMgr->checkAndSecurisePostData("swdesc");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $desc == NULL) {
						echo "ERROR";
						return;
					}
					if(FS::$pgdbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if($this->setPortDesc($sw,$port,$desc) == 0) {
							echo $desc;
							if($save == "true")
								$this->writeMemory($sw);
							FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$sw."' AND port = '".$port."'");
						}
						else
							echo "ERROR";
					}
					return;
				case 4:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$st = FS::$secMgr->checkAndSecurisePostData("swst");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $st == NULL) {
							echo "ERROR";
							return;
					}

					if($lup = FS::$pgdbMgr->GetOneData("device_port","up","ip = '".$sw."' AND port = '".$port."'")) {
						$state = $st == "true" ? 2 : 1;
						if($this->setPortState($sw,$port,$state) == 0) {
							if($save == "true")
									$this->writeMemory($sw);
							FS::$pgdbMgr->Update("device_port","up_admin = '".($st == "true" ? "down" : "up")."'","ip = '".$sw."' AND port = '".$port."'");
							if($state == 1) {
								if($lup == "up") $lupstr = "<span style=\"color: black;\">Actif</span>";
								else $lupstr = "<span style=\"color: orange;\">Inactif</span>";
							}
							echo ($state == 1 ? $lupstr : "<span style=\"color:red;\">Eteint</span>");
							}
							else
									echo "ERROR";
					}
					return;
				case 5:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$dup = FS::$secMgr->checkAndSecurisePostData("swdp");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $dup == NULL) {
							echo "ERROR";
							return;
					}
		
					if(FS::$pgdbMgr->GetOneData("device_port","type","ip = '".$sw."' AND port = '".$port."'") != NULL) {
						if($this->setPortDuplex($sw,$port,$dup) == 0) {
								if($save == "true")
										$this->writeMemory($sw);

								$duplex = "auto";
								if($dup == 1) $duplex = "half";
								else if($dup == 2) $duplex = "full";
				
																			FS::$pgdbMgr->Update("device_port","duplex_admin = '".$duplex."'","ip = '".$sw."' AND port = '".$port."'");
								$ldup = FS::$pgdbMgr->GetOneData("device_port","duplex","ip = '".$sw."' AND port = '".$port."'");
								$ldup = (strlen($ldup) > 0 ? $ldup : "[NA]");
													if($ldup == "half" && $duplex != "half") $ldup = "<span style=\"color: red;\">".$ldup."</span>";
								echo "<span style=\"color:black;\">".$ldup." / ".$duplex."</span>";
							}
							else
									echo "ERROR";
					}
					return;
				case 6:
					$device = FS::$secMgr->checkAndSecuriseGetData("dev");
					$portname = FS::$secMgr->checkAndSecuriseGetData("port");
					$out = "";
					exec("snmpwalk -v 2c -c ".SNMPConfig::$SNMPReadCommunity." ".$device." ifDescr | grep ".$portname,$out);
					if(strlen($out[0]) < 5) {
							echo "-1";
						return;
					}
								$out = explode(" ",$out[0]);
								$out = explode(".",$out[0]);
					if(!FS::$secMgr->isNumeric($out[1])) {
						echo "-1";
						return;
					}
					$portid = $out[1];

					$value = FS::$snmpMgr->get($device,"1.3.6.1.4.1.9.9.68.1.2.2.1.2.".$portid);
					if($value == false)
							echo "-1";
					else
						echo $value;
					return;
				case 7:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$vlan = FS::$secMgr->checkAndSecurisePostData("vlan");
					$save = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $vlan == NULL) {
							echo "ERROR";
							return;
					}
					if($this->setSwitchAccessVLAN($sw,$port,$vlan) != 0) {
						echo "ERROR";
						return;
					}
					if($save == "true")
						 $this->writeMemory($sw);
					$sql = "UPDATE device_port SET vlan ='".$vlan."' WHERE ip='".$sw."' and port='".$port."'";
					pg_query($sql);
					$sql = "UPDATE device_port_vlan SET vlan ='".$vlan."' WHERE ip='".$sw."' and port='".$port."' and native='t'";
					pg_query($sql);
					echo $vlan;
					return;
				case 8:
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$port = FS::$secMgr->checkAndSecurisePostData("swport");
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					if($port == NULL || $sw == NULL || $vlan == NULL) {
							echo "ERROR";
							return;
					}
					if($this->setSwitchTrunkVlan($sw,$port,$vlan) != 0) {
						echo "ERROR";
						return;
					}

					if($save == "true")
						$this->writeMemory($sw);
					echo $vlan;
				case 9:
					$sw = FS::$secMgr->checkAndSecurisePostData("sw");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					$prise = FS::$secMgr->checkAndSecurisePostData("prise");
					$shut = FS::$secMgr->checkAndSecurisePostData("shut");
					$trunk = FS::$secMgr->checkAndSecurisePostData("trmode");
					$nvlan = FS::$secMgr->checkAndSecurisePostData("nvlan");
					$wr = FS::$secMgr->checkAndSecurisePostData("wr");
					if($port == NULL || $sw == NULL || $trunk == NULL || $nvlan == NULL) {
						header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=1");
						return;
					}

					$pid = $this->getPortId($sw,$port);
					if($pid == -1) {
						header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
						return;
					}

					if($trunk == 1) {
						$vlanlist = FS::$secMgr->checkAndSecurisePostData("vllist");

						$this->setSwitchAccessVLANWithPID($sw,$pid,1);
						if($this->setSwitchTrunkEncapWithPID($sw,$pid,4) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if($this->setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if($this->setSwitchTrunkVlanWithPID($sw,$pid,$vlanlist) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if($this->setSwitchTrunkNativeVlanWithPID($sw,$pid,$nvlan) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
					} else if($trunk == 2) {
						$this->setSwitchTrunkNativeVlanWithPID($sw,$pid,1);
						$this->setSwitchNoTrunkVlanWithPID($sw,$pid);
						if($this->setSwitchportModeWithPID($sw,$pid,$trunk) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if($this->setSwitchTrunkEncapWithPID($sw,$pid,5) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
						if($this->setSwitchAccessVLANWithPID($sw,$pid,$nvlan) != 0) {
								header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
								return;
						}
					}
					if($this->setPortStateWithPID($sw,$pid,($shut == "on" ? 2 : 1)) != 0) {
							header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port."&err=2");
							return;
					}
					$this->setPortDescWithPID($sw,$pid,$desc);
					if($wr == "on")
						$this->writeMemory($sw);

					$dip = FS::$pgdbMgr->GetOneData("device","ip","name = '".$sw."'");

					if($prise == NULL) $prise = "";
					mysql_query("REPLACE INTO fss_switch_port_prises VALUES ('".$dip."','".$port."','".$prise."')");

					if($prise != "") {
							$piecetab = preg_split("#\.#",$prise);
							if(isset($piecetab[0]) && isset($piecetab[1]) && isset($piecetab[2]) && !isset($piecetab[3])) {
									if(FS::$secMgr->isNumeric($piecetab[1]) && FS::$secMgr->isNumeric($piecetab[2])) {
											$pname = $piecetab[0].".".$piecetab[1];
											for($i=1;$i<=$piecetab[2];$i++) {
													mysql_query("INSERT IGNORE INTO fss_piece_prises VALUES ('".$pname."','".$i."','')");
											}
									}
							}
					}
					FS::$pgdbMgr->Update("device_port","name = '".$desc."'","ip = '".$dip."' AND port = '".$port."'");
					FS::$pgdbMgr->Update("device_port","up_admin = '".($shut == "on" ? "down" : "up")."'","ip = '".$dip."' AND port = '".$port."'");
					$sql = "UPDATE device_port SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."'";
					pg_query($sql);
					$sql = "UPDATE device_port_vlan SET vlan ='".$nvlan."' WHERE ip='".$dip."' and port='".$port."' and native='t'";
					pg_query($sql);
					FS::$pgdbMgr->Delete("device_port_vlan","ip = '".$dip."' AND port='".$port."'");
					if(FS::$secMgr->checkAndSecurisePostData("vllist") != NULL) {
						$vlantab = preg_split("/,/",FS::$secMgr->checkAndSecurisePostData("vllist"));
						for($i=0;$i<count($vlantab);$i++)
							FS::$pgdbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$vlantab[$i]."','f',NOW(),NOW()");
					}
					else {
						FS::$pgdbMgr->Insert("device_port_vlan","ip,port,vlan,native,creation,last_discover","'".$dip."','".$port."','".$nvlan."','t',NOW(),NOW()");
					}
					header("Location: index.php?mod=".$this->mid."&d=".$sw."&p=".$port);
					return;
				default: break;
			}
		}
	};
?>
