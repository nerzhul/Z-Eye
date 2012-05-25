<?php
	class SVNRevision {
		function SVNRevision() {
			$this->revision = "";
			if($svn = File('.svn/entries')) {
				$this->revision = trim($svn[3]);
				unset($svn);
			}
		}
		
		public function getRevision() {
				return $this->revision;
		}
		
		private $revision;
	}
?>