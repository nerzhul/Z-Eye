<?php
        /*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
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

	final class HTMLTableMgr {
		/*
		* All parameters are optionals and contained in an array
		*/
		function __construct($options) {
			if(!isset($options) || !is_array($options))
				return NULL;

			if(isset($options["tabledivid"]))
				$this->tableDivId = $options["tabledivid"];	
			if(isset($options["tableid"]))
				$this->tableId = $options["tableid"];	
			if(isset($options["firstlineid"]))
				$this->firstLineId = $options["firstlineid"];	
			if(isset($options["sqltable"]))
				$this->sqlTable = $options["sqltable"];	
			if(isset($options["sqlattrid"]))
				$this->sqlAttrId = $options["sqlattrid"];	
			if(isset($options["attrlist"]))
				$this->attrList = $options["attrlist"];	
			if(isset($options["sorted"]))
				$this->sorted = $options["sorted"];	
			if(isset($options["odivnb"]))
				$this->opendivNumber = $options["odivnb"];	
			if(isset($options["odivlink"]))
				$this->opendivLink = $options["odivlink"];	
			if(isset($options["rmcol"]))
				$this->removeColumn = $options["rmcol"];	
			if(isset($options["rmconfirm"]))
				$this->removeConfirm = $options["rmconfirm"];	
			if(isset($options["rmlink"]))
				$this->removeLink = $options["rmlink"];	
		}

		public function render() {
			$found = false;

			$output = "<div id=\"".$this->tableDivId."\">";
			$tmpoutput = $this->showHeader();
			
			// for optimization we calcule this number here
			$attrCount = count($this->attrList);
			$sqlAttrList = ""; 
			for($i=0;$i<$attrCount;$i++) {
				if($i != 0)
					$sqlAttrList .= ",";
				$sqlAttrList .= $this->attrList[$i][1];
			}

			$query = FS::$dbMgr->Select($this->sqlTable,$sqlAttrList,"",array("order" => $this->sqlAttrId));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found)
					$found = true;
				$tmpoutput .= $this->showLine($data,$attrCount);
                        }
                        if($found)
				$output .= $tmpoutput."</table>".FS::$iMgr->jsSortTable($this->tableId);

                        $output .= "</div>";
			return $output;
		}

		private function showLine($sqlDatas,$attrCount) {
			FS::$iMgr->setJSBuffer(1);
                        $output = "<tr id=\"tr".FS::$iMgr->formatHTMLId($sqlDatas[$this->sqlAttrId])."at\"><td>".
				FS::$iMgr->opendiv($this->opendivNumber,$sqlDatas[$this->sqlAttrId],
					array("lnkadd" => $this->opendivLink.$sqlDatas[$this->sqlAttrId]))."</td>";
	
			for($i=1;$i<$attrCount;$i++) {
				$output .= "<td>";
				if($this->attrList[$i][2] == "b")
					$output .= ($sqlDatas[$this->attrList[$i][1]] == 't' ? "X" : "");	
				else
					$output .= $sqlDatas[$this->attrList[$i][1]];
				$output .= "</td>";
			}

			if($this->removeColumn) {
                                $output .= "<td>".FS::$iMgr->removeIcon($this->removeLink."=".$sqlDatas[$this->sqlAttrId],
					array("js" => true, "confirm" =>
                                        array(FS::$iMgr->getLocale($this->removeConfirm)."'".$sqlDatas[$this->sqlAttrId]."' ?","Confirm","Cancel"))).
					"</td>";
			}
			$output .= "</tr>";
			return $output;
		}

		private function showHeader() {
			FS::$iMgr->setJSBuffer(1);
			$attrCount = count($this->attrList);

                        $output = "<table id=\"".$this->tableId."\"><thead><tr id=\"".$this->firstLineId."\"><th class=\"headerSortDown\">";
			
			for($i=0;$i<$attrCount;$i++) {
				$output .= FS::$iMgr->getLocale($this->attrList[$i][0])."</th>";
				if($i < $attrCount-1) 
					$output .= "<th>";
			}
			if($this->removeColumn)
				$output .= "<th></th>";
			$output .= "</tr></thead>";
			return $output;
		}
		
		public function removeLine($id) {
			$output = "";
			$count = FS::$dbMgr->Count($this->sqlTable,$this->sqlAttrId);
			if($count == 0)
				$output .= "hideAndEmpty('#".$this->tableId."');";
			else
				$output .= "hideAndRemove('#tr".$id."at');";
			return $output;
		}

		private $tableId;
		private $tableDivId;
		private $firstLineId;
		private $sorted;
		private $attrList;

		// Showing related
		private $opendivNumber;
		private $opendivLink;

		// Remove related
		private $removeColumn;
		private $removeLink;
		private $removeConfirm;
		
		// SQL related
		private $sqlTable;
		private $sqlAttrId;
	};
?>
