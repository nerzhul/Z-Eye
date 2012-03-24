<?php
	require_once(dirname(__FILE__)."/../lib/FSS/NamedObject.FS.class.php");
	class Category extends NamedObject {
		function Category() {}
		
		public function Load() {
			$query = FS::$dbMgr->Select("fss_category","name,parent,description","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->name = $data["name"];
				$this->_parent = $data["parent"];	
				$this->description = $data["description"];
			}	
		}
		
		public function Create() {
			FS::$dbMgr->Insert("fss_category","name,parent,description","'".$this->name."','".$this->_parent."','".$this->description."'");	
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_category","name = '".$this->name."', parent = '".$this->_parent."', description = '".$this->description."'","id = '".$this->id."'");	
		}
		
		public function Delete() {
			FS::$dbMgr->Delete("fss_category","id = '".$this->id."'");
		}			
		
		public function CreateSelect($idsel = 0) {
			$output = FS::$iMgr->addList("categories");
			$output .= FS::$iMgr->addElementToList("------------",0);
			
			$query = FS::$dbMgr->Select("fss_category","id, name, parent");
			while($data = mysql_fetch_array($query)) {
				$name = "";
				if($data["parent"] > 0)
					$name .= FS::$dbMgr->GetOneData("fss_category","name","id = '".$data["parent"]."'")." - ";
					
				$name .= $data["name"];
				$output .= FS::$iMgr->addElementToList($name,$data["id"], $idsel > 0  && $idsel == $data["id"] ? true : false);
			}
			$output .= "</select>";
			return $output;			
		}
		
		public function setParent($parent) { $this->_parent = $parent; }
		public function getParent() { return $this->_parent; }
		public function setDesc($desc) { $this->description = $desc; }
		public function getDesc() { return $this->description; }
		
		private $_parent;
		private $description;
	};
?>