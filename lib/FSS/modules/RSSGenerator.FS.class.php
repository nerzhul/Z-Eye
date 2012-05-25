<?php
	require_once(dirname(__FILE__)."/FileMgr.FS.class.php");
	class FSRSSGenerator {
		function FSRSSGenerator() {
			$filename = "";
			$title = "";
			$link = "";
			$description = "";
			$this->elements = array();
		}		
		
		public function addElement($title,$link,$desc,$pubdate) {
			$id = count($this->elements);
			$this->elements[$id]["title"] = $title;
			$this->elements[$id]["link"] = $link;
			$this->elements[$id]["description"] = $desc;
			$this->elements[$id]["pubdate"] = $pubdate;
		}
		
		public function writeRSS() {
			$fileMgr = new FSFileMgr();
			$fileMgr->setOpeningMode("w+");
			$fileMgr->setPath($this->filename);
			if(!$fileMgr->open())
				return false;
			$fileMgr->writeLine("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
			$fileMgr->writeLine("<rss version=\"2.0\">");
			$fileMgr->writeLine("    <channel>");
			$fileMgr->writeLine("        <title>".$this->title."</title>");
			$fileMgr->writeLine("        <link>".$this->link."</link>");
			$fileMgr->writeLine("        <description>".$this->description."</description>");
			if(count($this->elements) > 0) {
				for($i=0;$i<count($this->elements);$i++) {
					$fileMgr->writeLine("<item>");
					$fileMgr->writeLine("<title>".$this->elements[$i]["title"]."</title>");
					$fileMgr->writeLine("<link>".$this->elements[$i]["link"]."</link>");
					$fileMgr->writeLine("<guid isPermaLink=\"true\">".$this->elements[$i]["link"]."</guid>");
					$fileMgr->writeLine("<description>".$this->elements[$i]["description"]."</description>");
					$fileMgr->writeLine("<pubDate>".$this->elements[$i]["pubdate"]."</pubDate>");
					$fileMgr->writeLine("</item>");
				}				
			}
			$fileMgr->writeLine("    </channel>");
			$fileMgr->writeLine("</rss>");
			$fileMgr->close();
			return true;
		}
		
		/* Setters */
		public function setFilename($file) { $this->filename = $file; }
		public function setTitle($title) { $this->title = $title; }
		public function setLink($link) { $this->link = $link; }
		public function setDesc($desc) { $this->description = $desc; }
		
		/* Attributes */
		private $filename;
		private $title;
		private $link;
		private $description;
		private $elements; // array
	};
?>
