<?php
	require_once(dirname(__FILE__)."/IndexedObject.FS.class.php");
	class HTTPLink {
		function HTTPLink($id) {
			$this->id = $id;
		}
		
		public function getIt() {
			$query = FS::$pgdbMgr->Select("z_eye_http_links","type,args","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				switch($data["type"]) {
					case 0: // external
						return "http://".$data["args"];
					case 1: // action
						return "index.php?act=".$data["args"];
					case 2: // module
						return "index.php?mod=".FS::$iMgr->getModuleIdByPath($data["args"]);
					case 3: // JS
						return "javascript:".$data["args"].";";
					case 4: // rewrite-module
						return "m-".FS::$iMgr->getModuleIdByPath($data["args"]).".html";
					case 5: // rewrite-others
						return $data["args"];
					default:
						return "index.php";
				}
			}
			else
				return "index.php";
		}
		
		public function Load() {
			$query = FS::$pgdbMgr->Select("z_eye_http_links","type,args","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				$this->type = $data["type"];
				$this->args = $data["args"];	
			}
		}
		
		public function Create() {
			$id = FS::$pgdbMgr->GetMax("z_eye_http_links","id")+1;
			FS::$pgdbMgr->Insert("z_eye_http_links","id,type,args","'".$id."','".$this->type."','".$this->args."'");
		}
		
		public function SaveToDB() {
			FS::$pgdbMgr->Update("z_eye_http_links","type = '".$this->type."', args = '".$this->args."'","id = '".$this->id."'");	
		}
		
		public function Delete() {
			FS::$pgdbMgr->Delete("z_eye_http_links","id = '".$this->id."'");
		}
		
		public function CreateSelect($idsel = 0) {
			$output = "";
			$output .= FS::$iMgr->addList("link_id");
			$query = FS::$pgdbMgr->Select("z_eye_http_links","id");
			while($data = pg_fetch_array($query)) {
				$this->id = $data["id"];
				$this->Load();
				$output .= FS::$iMgr->addElementTolist($this->getIt(),$data["id"], $idsel > 0 && $idsel == $data["id"] ? true : false);
			}
			$output .= "</select>";
			return $output;		
		}
		
		public function getType() { return $this->type; }
		public function getArgs() { return $this->args; }
		public function setType($type) { $this->type = $type; }
		public function setArgs($args) { $this->args = $args; }
		private $type;
		private $args;
	}
?>
