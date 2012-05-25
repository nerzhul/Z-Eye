<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
	
	require_once(dirname(__FILE__)."/IndexedObject.FS.class.php");
	class HTTPLink {
		function HTTPLink($id) {
			$this->id = $id;
		}
		
		public function getIt() {
			$query = FS::$dbMgr->Select("fss_http_links","type,args","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
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
			$query = FS::$dbMgr->Select("fss_http_links","type,args","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->type = $data["type"];
				$this->args = $data["args"];	
			}
		}
		
		public function Create() {
			FS::$dbMgr->Insert("fss_http_links","type,args","'".$this->type."','".$this->args."'");
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_http_links","type = '".$this->type."', args = '".$this->args."'","id = '".$this->id."'");	
		}
		
		public function Delete() {
			FS::$dbMgr->Delete("fss_http_links","id = '".$this->id."'");
		}
		
		public function CreateSelect($idsel = 0) {
			$output = "";
			$output .= FS::$iMgr->addList("link_id");
			$query = FS::$dbMgr->Select("fss_http_links","id");
			while($data = mysql_fetch_array($query)) {
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