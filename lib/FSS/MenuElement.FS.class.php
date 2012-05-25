<?php
	require_once("NamedObject.FS.class.php");
	class MenuElement extends NamedObject {
		function MenuElement() {}
		
		public function Load() {
			$query = FS::$dbMgr->Select("fss_menu_items","id,title,link,ulevel,isconnected","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->name = $data["title"];
				$this->link = $data["link"];
				$this->ulevel = $data["ulevel"];
				$this->isconnected = $data["isconnected"];	
			}
		}
		
		public function Create() {
			FS::$dbMgr->Insert("fss_menu_items","title,link,ulevel,isconnected","'".$this->name."','".$this->link."','".$this->ulevel."','".$this->isconnected."'");
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_menu_items","title = '".$this->name."', link = '".$this->link."', ulevel = '".$this->ulevel."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			FS::$dbMgr->Delete("fss_menu_items","id = '".$this->id."'");
			FS::$dbMgr->Delete("fss_menu_link","id_menu_item = '".$this->id."'");
		}
		
		public function CreateSelect($idsel = 0) {
			$output = FS::$iMgr->addList("m_elem_id");
			$query = FS::$dbMgr->Select("fss_menu_items","id");
			while($data = mysql_fetch_array($query)) {
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