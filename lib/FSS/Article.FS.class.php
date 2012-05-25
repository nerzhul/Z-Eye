<?php
	/** This code is Property of Frost Sapphire Studios, all rights reserved.
	*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
	**/
	
	require_once(dirname(__FILE__)."/NamedObject.FS.class.php");
	class Article extends NamedObject {
		function Article() {
			$this->dbMgr = FS::$dbMgr;
			}
		
		public function Load($id) {
			$this->id = $id;
		
			$query = $this->dbMgr->Select("fss_article"," title, content, post_date, update_date, author, category, `source`, is_mainpage","id ='".$id."'");
			if($data = mysql_fetch_array($query)) {
				$this->title = $data["title"];
				$this->content = $data["content"];
				$this->post_date = $data["post_date"];
				$this->update_date = $data["update_date"];
				$this->author = $data["author"];
				$this->category = $data["category"];
				$this->source = $data["source"];
				$this->isMainpage = $data["is_mainpage"] == 1 ? true : false;
				return true;
			}
			
			return false;
		}
		
		public function Create() {
			$this->dbMgr->Insert("fss_article","id, title, content, post_date, update_date, author, category, `source`, is_mainpage","'".$this->id."','".$this->title.
				"','".$this->content."',NOW(), NOW(), '".$this->author."','".$this->category."','".$this->source."','".($this->isMainpage == true ? 1 : 0)."'");
			return $this->dbMgr->GetOneData("fss_article","id","","post_date",1,1);
		}
		
		public function SaveToDB() {
			$this->dbMgr->Update("fss_article","title = '".$this->title."', content = '".$this->content."', update_date = NOW(), author = '".
				$this->author."', category = '".$this->category."', `source` = '".$this->source. "', is_mainpage = '".($this->isMainpage == true ? 1 : 0)."'","id = '".$this->id."'");
		}
		
		public function Delete() {
			$this->dbMgr->Delete("fss_article","id = '".$this->id."'");	
		}
		
		public function getTitle() { return $this->title; }
		public function getContent() { return $this->content; }
		public function getPostDate() { return $this->post_date; }
		public function getUpdateDate() { return $this->update_date; }
		public function getAuthor() { return $this->author; }
		public function getCategory() { return $this->category; }
		public function getSource() { return $this->source; }
		public function isMainPage() { return $this->isMainpage; }
		
		public function setTitle($title) { $this->title = $title; }
		public function setContent($content) { $this->content = $content; }
		public function setUpdateDate($update) { $this->update_date = $update; }
		public function setAuthor($author) { $this->author = $author; }
		public function setCategory($category) { $this->category = $category; }
		public function setSource($source) { $this->source = $source; }
		public function setMainPage($mp) { $this->isMainpage = $mp;}
		
		private $title;
		private $content;
		private $post_date;
		private $update_date;
		private $author;
		private $category;
		private $source;
		private $isMainpage;
		
		private $dbMgr;
	};
?>
