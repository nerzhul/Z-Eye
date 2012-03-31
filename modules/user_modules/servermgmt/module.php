<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iServerMgmt extends genModule{
		function iServerMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Gestion du moteur d'analyse des serveurs</h3>";
			$do = FS::$secMgr->checkAndSecuriseGetData("do");
			if($do)
				$output .= $this->CreateOrEditServer($do == 1 ? true : false);
			else
				$output .= $this->showServerList();
			$output .= "</div>";
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
				$query = FS::$dbMgr->Select("fss_server_list","login,dhcp,dns","addr = '".$addr."'");
				if($data = mysql_fetch_array($query)) {
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
			$query = FS::$dbMgr->Select("fss_server_list","addr,login,dhcp,dns");
			while($data = mysql_fetch_array($query)) {
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
			return $output;	
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dhcp = FS::$secMgr->checkAndSecurisePostData("slogin");
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
					break;
				case 2:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dhcp = FS::$secMgr->checkAndSecurisePostData("slogin");
					$dns = FS::$secMgr->checkAndSecurisePostData("dns");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
						$spwd != $spwd2) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=1");
						return;
					}
					$conn = ssh2_connect($saddr,22);
					if(!$conn) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=2");
						return;
					}
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=3");
						return;
					}
					break;
				case 3: {
					if($srv = FS::$secMgr->checkAndSecuriseGetData("srv")) {
							FS::$dbMgr->Delete("fss_server_list","addr = '".$srv."'");
					}	
					header('Location: m-'.$this->mid.'.html');				
				}
				break;
				default: break;
			}
		}
	};
?>
