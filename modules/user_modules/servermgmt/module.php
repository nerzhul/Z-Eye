<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iServerMgmt extends genModule{
		function iServerMgmt() { parent::genModule(); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Gestion du moteur d'analyse des serveurs</h3>";
			$output .= $this->showServerList();
			$output .= "</div>";
			return $output;
		}
		
		private function showServerList() {
			$output = "";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>Serveur</th><th>Login</th><th>DHCP ?</th><th>DNS</th><th>Supprimer</th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select("fss_server_list","addr,login,dhcp,dns");
			while($data = mysql_fetch_array($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td>".$data["addr"]."</td><td>".$data["login"]."</td><td>".($data["dhcp"] > 0 ? "X" : "")."</td><td>".($data["dns"] > 0 ? "X" : "")."</td><td></td></tr>";
			}
			if($found) {
				$output .= $tmpoutput."</table>";
			}
			return $output;	
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				default: break;
			}
		}
	};
?>
