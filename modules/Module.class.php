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
	
	require_once(dirname(__FILE__)."/../lib/FSS/NamedObject.FS.class.php");
	class Module extends NamedObject {
		function Module() {}
		
		public function Load($id) {
			$this->id = $id;
			$query = FS::$dbMgr->Select("fss_modules","name,path,ulevel,isconnected","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->name = $data["name"];
				$this->path = $data["path"];
				$this->ulevel = $data["ulevel"];
				$this->isconnected = $data["isconnected"];	
			}			
		}
		
		public function Create() {
			FS::$dbMgr->Insert("fss_modules","name,path,ulevel,isconnected","'".$this->name."','".$this->path."','".$this->ulevel."','".$this->isconnected."'");
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_modules","name = '".$this->name."', path = '".$this->path."', ulevel = '".$this->ulevel."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			FS::$dbMgr->Delete("fss_modules","id = '".$this->id."'");
		}
		
		public function setPath($path) { $this->path = $path; }
		public function setUlevel($ulevel) { $this->ulevel = $ulevel; }
		public function setConnected($conn) { $this->isconnected = $conn; }
		public function getPath() { return $this->path; }
		public function getUlevel() { return $this->ulevel; }
		public function getConnected() { return $this->isconnected; }
		
		private $path;
		private $ulevel;
		private $isconnected;
		
	};


?>