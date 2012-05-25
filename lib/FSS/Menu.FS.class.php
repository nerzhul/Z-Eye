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
