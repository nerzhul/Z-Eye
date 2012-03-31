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
			
			$output .= "<table class=\"standardTable\">";
			$output .= FS::$iMgr->addIndexedLine("Adresse IP/DNS","saddr",$saddr);
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
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Serveur</th><th>Login</th><th>DHCP ?</th><th>DNS</th><th>Supprimer</th></tr>";
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
