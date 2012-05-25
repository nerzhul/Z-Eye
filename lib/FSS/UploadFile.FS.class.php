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
	
	class UploadFile {
		function UploadFile($data){
			$this->field_name = $data;
		}
		
		public function RandMoveTo($dest,$name = "") {
			return $this->MoveTo($dest, rand(10000,100000000000).($name != "" ? $name : $this->getRealName()));
		}
		
		public function MoveTo($dest,$name = "") {
			if(file_exists($dest.$name))
				return false;
			return move_uploaded_file($this->getTmpName(),$dest."/".$name);
		}
		
		public function getRealName() {
			return $_FILES[$this->field_name]['name'];			
		}
		
		public function getTmpName() {
			return $_FILES[$this->field_name]['tmp_name'];
		}
		
		public function getType() {
			return $_FILES[$this->field_name]['type'];	
		}
		
		public function getSize() {
			return $_FILES[$this->field_name]['size'];
		}
		
		public function getKbSize() {
			return ($this->getSize() / 1024.0);
		}
		
		public function getMbSize() {
			return ($this->getKbSize() / 1024.0);
		}
		
		public function getError() {
			return $_FILES[$this->field_name]['error'];
		}
		
		public function upWithoutError() {
			if(!$this->exist())
				return false;
			
			if($this->getError() != UPLOAD_ERR_OK)
				return false;
				
			return true;			
		}
		
		public function exist() {
			if(isset($_FILES[$this->field_name]))
				return true;
			return false;
		}	
			
		private $field_name;
	};

?>