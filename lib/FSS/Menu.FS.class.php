<?php
	require_once("NamedObject.FS.class.php");
	class Menu extends NamedObject {
		function Menu() {}
		
		public function Load() {
			$query = FS::$dbMgr->Select("fss_menus","name,ulevel,isconnected","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->name = $data["name"];
				$this->ulevel = $data["ulevel"];
				$this->isconnected = $data["isconnected"];
			}
		}
		
		public function Create() {
			FS::$dbMgr->Insert("fss_menus","name,ulevel,isconnected","'".$this->name."','".$this->ulevel."','".$this->isconnected."'");
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_menus","name = '".$this->name."', ulevel = '".$this->ulevel."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			FS::$dbMgr->Delete("fss_menus","id = '".$this->id."'");
		}
		
		public function getUlevel() { return $this->ulevel; }
		public function getConnected() { return $this->isconnected; }
		public function setUlevel($level) { $this->ulevel = $level; }
		public function setConnected($conn) { $this->isconnected = $conn; }
		private $ulevel;
		private $isconnected;
	};
?>
