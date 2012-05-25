<?php
	require_once(dirname(__FILE__)."/IndexedObject.FS.class.php");
	class NamedObject extends IndexedObject {
		function NamedObject() {}
		
		public function Load() {}
		
		public function setName($name) { $this->name = $name; }
		public function getName() { return $this->name; }
		protected $name;
	};
?>
