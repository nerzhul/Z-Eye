<?php
	class IndexedObject {
		function IndexedObject() {}
		
		public function	Load() {}
		
		public function setId($id) { $this->id = $id; }
		public function getId() { return $this->id; }
		protected $id;		
	}
?>