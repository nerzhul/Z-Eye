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
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iServerMgmt extends genModule{
		function iServerMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Gestion du moteur d'analyse des serveurs</h3>";
			$do = FS::$secMgr->checkAndSecuriseGetData("do");
			switch($do) {
				case 1: case 2:
					$output .= $this->CreateOrEditServer($do == 1 ? true : false);
					break;
				case 4: case 5:
					$output .= $this->CreateOrEditRadiusDB($do == 4 ? true : false);
					break;
				case 7: case 8:
					$output .= $this->CreateOrEditDeviceSaveServer($do == 7 ? true : false);
					break;
				default:
					$output .= $this->showServerList();
					break;
			}
			$output .= "</div>";
			return $output;
		}
		
		private function CreateOrEditDeviceSaveServer($create) {
			$saddr = "";
			$slogin = "";
			$stype = 1;
			$spwd = "";
			$spath = "";
			
			if($create)
				$output = "<h4>Ajouter un serveur de sauvegarde (configuration des équipements réseau)</h4>";
			else {
				$output = "<h4>Edition des informations du serveur de sauvegarde (configuration des équipements réseau)</h4>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$type = FS::$secMgr->checkAndSecuriseGetData("type");
				if(!$addr || $addr == "" || !$type || !FS::$secMgr->isNumeric($type)) {
					$output .= FS::$iMgr->printError("Aucun serveur à éditer spécifié!");
					return $output;
				}
				$query = FS::$pgdbMgr->Select("z_eye_save_device_servers","login,pwd,path","addr = '".$addr."' AND type = '".$type."'");
				if($data = pg_fetch_array($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$stype = $type;
					$spath = $data["path"];
				}
				else {
					$output .= FS::$iMgr->printError("Aucun serveur enregistré avec ces coordonnées !");
					return $output;
				}
			}
			
			$output .= "<a class=\"monoComponentt_a\" href=\"m-".$this->mid.".html\">Retour</a><br />";
			
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("Certains champs sont invalides ou vides !"); break;
				case 3: if($create) $output .= FS::$iMgr->printError("Ce serveur est déjà référencé !"); break;
			}
			
			$output .= "<script type=\"text/javascript\">function arangeform() {";
			$output .= "if(document.getElementsByName('stype')[0].value == 1) {";
			$output .= "$('#tohide1').hide();";
			$output .= "$('#tohide2').hide();";
			$output .= "$('#tohide3').hide();";
			$output .= "} else if(document.getElementsByName('stype')[0].value == 2 || document.getElementsByName('stype')[0].value == 4 || document.getElementsByName('stype')[0].value == 5) {";
			$output .= "$('#tohide1').show();";
			$output .= "$('#tohide2').show();";
			$output .= "$('#tohide3').show();";
			$output .= "}";
			$output .= "};</script>";
					
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($create ? 7 : 8));
			
			if($create == false) {
				$output .= FS::$iMgr->addHidden("saddr",$saddr);
				$output .= FS::$iMgr->addHidden("stype",$stype);
			}
	
			$output .= "<table class=\"standardTable\">";
			if($create) {
				$output .= FS::$iMgr->addIndexedIPLine("Adresse IP","saddr",$saddr);
				$output .= "<tr><td>Type de service</td><td>";
				$output .= FS::$iMgr->addList("stype","arangeform();");
				$output .= FS::$iMgr->addElementToList("TFTP",1);
				$output .= FS::$iMgr->addElementToList("FTP",2);
				$output .= FS::$iMgr->addElementToList("SCP",4);
				$output .= FS::$iMgr->addElementToList("SFTP",5);
				$output .= "</select>";
				$output .= "</td></tr>";
			}
			else {
				$output .= "<tr><td>Adresse IP</td><td>".$saddr."</td></tr>";
				$output .= "<tr><td>Type de service</td><td>";
				switch($stype) {
					case 1: $output .= "TFTP"; break;
					case 2: $output .= "FTP"; break;
					case 4: $output .= "SCP"; break;
					case 5: $output .= "SFTP"; break;
				}
			}
			$output .= "<tr id=\"tohide1\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>Utilisateur</td><td>".FS::$iMgr->addInput("slogin",$slogin)."</td></tr>";
			$output .= "<tr id=\"tohide2\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>Mot de passe</td><td>".FS::$iMgr->addPasswdField("spwd","")."</td></tr>";
			$output .= "<tr id=\"tohide3\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>Mot de passe (répétition)</td><td>".FS::$iMgr->addPasswdField("spwd2","")."</td></tr>";
			$output .= FS::$iMgr->addIndexedLine("Chemin sur le serveur","spath",$spath);
			$output .= FS::$iMgr->addTableSubmit("Enregistrer","Enregistrer");
			$output .= "</table>";
			
			return $output;
		}
		
		private function CreateOrEditRadiusDB($create) {
			$saddr = "";
			$slogin = "";
			$sdbname = "";
			$sport = 0;
			$spwd = "";
			
			if($create)
				$output = "<h4>Ajouter une base de données Radius au moteur</h4>";
			else {
				$output = "<h4>Edition des informations de la base de données Radius</h4>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$port = FS::$secMgr->checkAndSecuriseGetData("pr");
				$dbname = FS::$secMgr->checkAndSecuriseGetData("db");
				if(!$addr || $addr == "" || !$port || !FS::$secMgr->isNumeric($port) || !$dbname || $dbname == "") {
					$output .= FS::$iMgr->printError("Aucune base de données à éditer spécifiée !");
					return $output;
				}
				$query = FS::$pgdbMgr->Select("z_eye_radius_db_list","login,pwd","addr = '".$addr."' AND port = '".$port."' AND dbname = '".$dbname."'");
				if($data = pg_fetch_array($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$sport = $port;
					$sdbname = $dbname;
				}
				else {
					$output .= FS::$iMgr->printError("Aucune base de données avec ces informations en base !");
					return $output;
				}
			}
			
			$output .= "<a class=\"monoComponentt_a\" href=\"m-".$this->mid.".html\">Retour</a><br />";
			
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("Certains champs sont invalides ou vides !"); break;
				case 2: $output .= FS::$iMgr->printError("Impossible de se connecter au serveur MySQL spécifié !"); break;
				case 3: if($create) $output .= FS::$iMgr->printError("Ce serveur MySQL est déjà référencé !"); break;
			}
			
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($create ? 4 : 5));
			
			if($create == false) {
				$output .= FS::$iMgr->addHidden("saddr",$saddr);
				$output .= FS::$iMgr->addHidden("sport",$sport);
				$output .= FS::$iMgr->addHidden("sdbname",$sdbname);
			}
	
			$output .= "<table class=\"standardTable\">";
			if($create) {
				$output .= FS::$iMgr->addIndexedLine("Adresse IP/DNS","saddr",$saddr);
				$output .= FS::$iMgr->addIndexedLine("Port","sport",$sport);
				$output .= FS::$iMgr->addIndexedLine("Nom de la base","sdbname",$sdbname);
			}
			else {
				$output .= "<tr><td>Adresse IP/DNS</td><td>".$saddr."</td></tr>";
				$output .= "<tr><td>Port</td><td>".$sport."</td></tr>";
				$output .= "<tr><td>Nom de la base</td><td>".$sdbname."</td></tr>";
			}
			$output .= FS::$iMgr->addIndexedLine("Utilisateur","slogin",$slogin);
			$output .= FS::$iMgr->addIndexedLine("Mot de passe","spwd","",true);
			$output .= FS::$iMgr->addIndexedLine("Répétition du mot de passe","spwd2","",true);
			$output .= FS::$iMgr->addTableSubmit("Enregistrer","Enregistrer");
			$output .= "</table>";
			
			return $output;
		}
		
		private function CreateOrEditServer($create) {
			$saddr = "";
			$slogin = "";
			$dhcp = 0;
			$dns = 0;
			
			if($create)
				$output = "<h4>Ajouter un serveur au moteur</h4>";
			else {
				$output = "<h4>Edition du serveur</h4>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				if(!$addr || $addr == "") {
					$output .= FS::$iMgr->printError("Aucun serveur à éditer spécifié !");
					return $output;
				}
				$query = FS::$pgdbMgr->Select("z_eye_server_list","login,dhcp,dns","addr = '".$addr."'");
				if($data = pg_fetch_array($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$dhcp = $data["dhcp"];
					$dns = $data["dns"];
				}
				else {
					$output .= FS::$iMgr->printError("Aucun serveur avec cette adresse en base !");
					return $output;
				}
			}
			
			$output .= "<a class=\"monoComponentt_a\" href=\"m-".$this->mid.".html\">Retour</a><br />";
			
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("Certains champs sont invalides ou vides !"); break;
				case 2: $output .= FS::$iMgr->printError("Impossible de se connecter au serveur spécifié !"); break;
				case 3: $output .= FS::$iMgr->printError("Login au serveur incorrect !"); break;
				case 4: if($create) $output .= FS::$iMgr->printError("Ce serveur est déjà référencé !"); break;
			}
			
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($create ? 1 : 2));
			
			if($create == false)
				$output .= FS::$iMgr->addHidden("saddr",$saddr);
	
			$output .= "<table class=\"standardTable\">";
			if($create)
				$output .= FS::$iMgr->addIndexedLine("Adresse IP/DNS","saddr",$saddr);
			else
				$output .= "<tr><td>Adresse IP/DNS</td><td>".$saddr."</td></tr>";		
			$output .= FS::$iMgr->addIndexedLine("Utilisateur SSH","slogin",$slogin);
			$output .= FS::$iMgr->addIndexedLine("Mot de passe","spwd","",true);
			$output .= FS::$iMgr->addIndexedLine("Répétition du mot de passe","spwd2","",true);
			$output .= FS::$iMgr->addIndexedCheckLine("DHCP ?","dhcp",$dhcp > 0 ? true : false);
			$output .= FS::$iMgr->addIndexedCheckLine("DNS ?","dns",$dns > 0 ? true : false);
			$output .= FS::$iMgr->addTableSubmit("Enregistrer","Enregistrer");
			$output .= "</table>";
			
			return $output;
		}
		
		private function showServerList() {
			$output = "<h4>Liste des serveurs</h4>";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=1\">Nouveau Serveur</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Serveur</th><th>Login</th><th>DHCP</th><th>DNS</th><th>Supprimer</th></tr>";
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_server_list","addr,login,dhcp,dns");
			while($data = pg_fetch_array($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=2&addr=".$data["addr"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>".$data["login"]."</td><td>";
				$tmpoutput .= "<center>".($data["dhcp"] > 0 ? "X" : "")."</center></td><td><center>".($data["dns"] > 0 ? "X" : "")."</center></td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=3&srv=".$data["addr"]."\">";
				$tmpoutput .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError("Aucun serveur trouvé !");

			$output .= "<h4>Liste des bases Radius</h4>";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=4\">Nouvelle base</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Serveur</th><th>Port</th><th>Hôte</th><th>Login</th><th>Supprimer</th></tr>";
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_radius_db_list","addr,port,dbname,login");
			while($data = pg_fetch_array($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=5&addr=".$data["addr"]."&pr=".$data["port"]."&db=".$data["dbname"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>".$data["port"]."</td><td>".$data["dbname"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=6&addr=".$data["addr"]."&pr=".$data["port"]."&db=".$data["dbname"]."\">";
				$tmpoutput .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError("Aucune base renseignée !");
				
			$output .= "<h4>Liste des serveurs de sauvegarde (configurations des équipements réseau)</h4>";
			$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=7\">Nouveau serveur</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Serveur</th><th>Type</th><th>Chemin sur le serveur</th><th>Login</th><th>Supprimer</th></tr>";
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_save_device_servers","addr,type,path,login");
			while($data = pg_fetch_array($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=8&addr=".$data["addr"]."&type=".$data["type"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>";
				switch($data["type"]) {
					case 1: $tmpoutput .= "TFTP"; break;
					case 2: $tmpoutput .= "FTP"; break;
					case 4: $tmpoutput .= "SCP"; break;
					case 5: $tmpoutput .= "SFTP"; break;
				}
				$tmpoutput .= "</td><td>".$data["path"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=9&addr=".$data["addr"]."&type=".$data["type"]."\">";
				$tmpoutput .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError("Aucune base renseignée !");
			return $output;	
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dhcp = FS::$secMgr->checkAndSecurisePostData("dhcp");
					$dns = FS::$secMgr->checkAndSecurisePostData("dns");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
						$spwd != $spwd2) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}
					$conn = ssh2_connect($saddr,22);
					if(!$conn) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=2");
						return;
					}
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					if(FS::$pgdbMgr->GetOneData("z_eye_server_list","login","addr ='".$saddr."'")) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=4");
						return;
					}
					FS::$pgdbMgr->Insert("z_eye_server_list","addr,login,pwd,dhcp,dns","'".$saddr."','".$slogin."','".$spwd."','".($dhcp == "on" ? 1 : 0)."','".($dns == "on" ? 1 : 0)."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 2:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dhcp = FS::$secMgr->checkAndSecurisePostData("dhcp");
					$dns = FS::$secMgr->checkAndSecurisePostData("dns");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd != $spwd2) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=1");
						return;
					}
					if($spwd != NULL || $spwd != "") {
						$conn = ssh2_connect($saddr,22);
						if(!$conn) {
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=2");
							return;
						}
						if(!ssh2_auth_password($conn,$slogin,$spwd)) {
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=3");
							return;
						}
						if($spwd == $spwd2) FS::$pgdbMgr->Update("z_eye_server_list","pwd = '".$spwd."'","addr = '".$saddr."'");
					}
					FS::$pgdbMgr->Update("z_eye_server_list","login = '".$slogin."', dhcp = '".($dhcp == "on" ? 1 : 0)."', dns = '".($dns == "on" ? 1 : 0)."'","addr = '".$saddr."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 3: {
					if($srv = FS::$secMgr->checkAndSecuriseGetData("srv")) {
							FS::$pgdbMgr->Delete("z_eye_server_list","addr = '".$srv."'");
					}	
					header('Location: m-'.$this->mid.'.html');				
				}
				case 4:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					if($saddr == NULL || $saddr == "" || $sport == NULL || !FS::$secMgr->isNumeric($sport) || $sdbname == NULL || $sdbname == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
						$spwd != $spwd2) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}
					
					FS::$dbMgr->Close();
					$testDBMgr = new FSMySQLMgr();
					$testDBMgr->setConfig($sdbname,$sport,$saddr,$slogin,$spwd);
					
					$conn = $testDBMgr->Connect();
					if($conn != 0) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=2");
						return;
					}
					FS::$dbMgr->Connect();
					if(FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr ='".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'")) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					FS::$pgdbMgr->Insert("z_eye_radius_db_list","addr,port,dbname,login,pwd","'".$saddr."','".$sport."','".$sdbname."','".$slogin."','".$spwd."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 5:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd != $spwd2) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&pr=".$sport."&db=".$sdbname."&err=1");
						return;
					}
					if($spwd != NULL || $spwd != "") {
						FS::$dbMgr->Close();
						$testDBMgr = new FSMySQLMgr();
						$testDBMgr->setConfig($saddr,$sport,$sdbname,$slogin,$spwd);
						
						$conn = $testDBMgr->Connect();
						if(!$conn) {
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&pr=".$sport."&db=".$sdbname."&err=2");
							return;
						}
						FS::$dbMgr->Connect();
						if($spwd == $spwd2) FS::$pgdbMgr->Update("z_eye_radius_db_list","pwd = '".$spwd."'","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					}
					FS::$pgdbMgr->Update("z_eye_radius_db_list","login = '".$slogin."'","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 6: {
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$sport = FS::$secMgr->checkAndSecuriseGetData("pr");
					$sdbname = FS::$secMgr->checkAndSecuriseGetData("db");
					if($saddr && $sport && $sdbname) {
							FS::$pgdbMgr->Delete("z_eye_radius_db_list","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					}	
					header('Location: m-'.$this->mid.'.html');				
				}
				case 7:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					$spath = FS::$secMgr->checkAndSecurisePostData("spath");
					if($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || $stype == NULL || ($stype != 1 && $stype != 2 && $stype != 4 && $stype != 5) || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" || $spwd != $spwd2)) || ($stype == 1 && ($slogin != "" || $spwd != "" || $spwd2 != ""))) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}

					if(FS::$pgdbMgr->GetOneData("z_eye_save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					FS::$pgdbMgr->Insert("z_eye_save_device_servers","addr,type,path,login,pwd","'".$saddr."','".$stype."','".$spath."','".$slogin."','".$spwd."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 8:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					$spath = FS::$secMgr->checkAndSecurisePostData("spath");
					if($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd != $spwd2))) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&type=".$stype."&err=1");
						return;
					}
					FS::$pgdbMgr->Update("z_eye_save_device_servers","path = '".$spath."', pwd = '".$spwd."', login = '".$slogin."'","addr = '".$saddr."' AND type = '".$stype."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 9: {
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$stype = FS::$secMgr->checkAndSecuriseGetData("type");
					if($saddr && $stype) {
							FS::$pgdbMgr->Delete("z_eye_save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
					}	
					header('Location: m-'.$this->mid.'.html');				
				}
				break;
				default: break;
			}
		}
	};
?>
