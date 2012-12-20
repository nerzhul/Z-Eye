<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.unix-experience.fr/>
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
