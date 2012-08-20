<?php
	require_once("NamedObject.FS.class.php");
	class MenuElement extends NamedObject {
		function MenuElement() {}
		
		public function Load() {
			$query = FS::$pgdbMgr->Select("z_eye_menu_items","id,title,link,ulevel,isconnected","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				$this->name = $data["title"];
				$this->link = $data["link"];
				$this->ulevel = $data["ulevel"];
				$this->isconnected = $data["isconnected"];	
			}
		}
		
		public function Create() {
			$id = FS::$pgdbMgr->GetMax("z_eye_menu_items","id")+1;
			FS::$pgdbMgr->Insert("z_eye_menu_items","id,title,link,ulevel,isconnected","'".$id."','".$this->name."','".$this->link."','".$this->ulevel."','".$this->isconnected."'");
		}
		
		public function SaveToDB() {
			FS::$pgdbMgr->Update("z_eye_menu_items","title = '".$this->name."', link = '".$this->link."', ulevel = '".$this->ulevel."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			FS::$pgdbMgr->Delete("z_eye_menu_items","id = '".$this->id."'");
			FS::$pgdbMgr->Delete("z_eye_menu_link","id_menu_item = '".$this->id."'");
		}
		
		public function CreateSelect($idsel = 0) {
			$output = FS::$iMgr->addList("m_elem_id");
			$query = FS::$pgdbMgr->Select("z_eye_menu_items","id");
			while($data = pg_fetch_array($query)) {
				$this->id = $data["id"];
				$this->Load();
				$output .= FS::$iMgr->addElementTolist($this->getName(),$data["id"], $idsel > 0 && $idsel == $data["id"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}
		
		public function setLink($link) { $this->link = $link; }
		public function setULevel($ulevel) { $this->ulevel = $ulevel; }
		public function setConn($conn) { $this->isconnected = $conn; }
		public function getLink() { return $this->link; }
		public function getULevel() { return $this->ulevel; }
		public function getConnected() { return $this->isconnected; }
		
		private $link;
		private $ulevel;
		private $isconnected;
		
	};

?>
