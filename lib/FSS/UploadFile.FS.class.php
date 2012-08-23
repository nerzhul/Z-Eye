<?php
        /*
        * Copyright (c) 2012, Loïc BLOT, CNRS
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
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
