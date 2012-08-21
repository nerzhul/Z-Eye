<?php
	require_once("NamedObject.FS.class.php");
	class Menu extends NamedObject {
		function Menu() {}
		
		public function Load() {
			$query = FS::$pgdbMgr->Select("z_eye_menus","name,isconnected","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				$this->name = $data["name"];
				$this->isconnected = $data["isconnected"];
			}
		}
		
		public function Create() {
			$id = FS::$pgdbMgr->GetMax("z_eye_menus","id")+1;
			FS::$pgdbMgr->Insert("z_eye_menus","id,name,isconnected","'".$id."','".$this->name."','".$this->isconnected."'");
		}
		
		public function SaveToDB() {
			FS::$pgdbMgr->Update("z_eye_menus","name = '".$this->name."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			FS::$pgdbMgr->Delete("z_eye_menus","id = '".$this->id."'");
		}
		
		public function getConnected() { return $this->isconnected; }
		public function setConnected($conn) { $this->isconnected = $conn; }
		private $isconnected;
	};
?>
