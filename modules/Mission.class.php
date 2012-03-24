<?php
	require_once(dirname(__FILE__)."/../lib/FSS/NamedObject.FS.class.php");
	class Mission extends NamedObject{
		function Mission() {}
		
		public function Load() {
		}
		
		public function Create() {
		}
		
		public function Delete() {
		}
		
		public function SaveToDB() {
		}
		
		public function setDescription($desc) { $this->description = $desc; }
		public function setDurationDone($duration) { $this->duration_done = $duration; }
		public function setStatus($status) { $this->status = $status; }
		public function getDescription() { return $this->description; }
		public function getDurationDone() { return $this->duration_done; }
		public function getStatus() { return $this->status; }
		private $status;
		private $description;
		private $duration_done;
	}
?>