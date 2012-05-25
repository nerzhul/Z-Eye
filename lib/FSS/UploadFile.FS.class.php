<?php
	/** This code is Property of Frost Sapphire Studios, all rights reserved.
	*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
	**/
	
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