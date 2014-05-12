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
			if (!isset($options) || !is_array($options))
				return NULL;

			if (isset($options["htmgrid"])) {
				$this->tableDivId = $options["htmgrid"]."list";
				$this->tableId = $options["htmgrid"]."table";
				$this->firstLineId = $options["htmgrid"]."ftr";
				$this->trPrefix = $options["htmgrid"]."tr-";
				$this->trSuffix = "-tr";
			}
			else {
				if (isset($options["tabledivid"]))
					$this->tableDivId = $options["tabledivid"];	
				if (isset($options["tableid"]))
					$this->tableId = $options["tableid"];	
				if (isset($options["firstlineid"]))
					$this->firstLineId = $options["firstlineid"];	
				if (isset($options["trpfx"]))
					$this->trPrefix = $options["trpfx"];	
				else
					$this->trPrefix = "";
				if (isset($options["trsfx"]))
					$this->trSuffix = $options["trsfx"];	
				else
					$this->trSuffix = "tr";
			}

			if (isset($options["sqltable"]))
				$this->sqlTable = $options["sqltable"];	

			if (isset($options["prefixsqltable"]))
				$this->prefixSQLTable = $options["prefixsqltable"];	
			else
				$this->prefixSQLTable = true;

			if (isset($options["sqlattrid"]))
				$this->sqlAttrId = $options["sqlattrid"];	

			if (isset($options["sqlcond"]))
				$this->sqlCondition= $options["sqlcond"];	
			else
				$this->sqlCondition = "";

			if (isset($options["attrlist"]))
				$this->attrList = $options["attrlist"];	
			// enable sort table JS
			if (isset($options["sorted"]))
				$this->sorted = $options["sorted"];	

			if (isset($options["odivnb"]))
				$this->opendivNumber = $options["odivnb"];	
			if (isset($options["odivlink"]))
				$this->opendivLink = $options["odivlink"];	

			if (isset($options["rmcol"]))
				$this->removeColumn = $options["rmcol"];	
			if (isset($options["rmconfirm"]))
				$this->removeConfirm = $options["rmconfirm"];	
			// enable remove link
			if (isset($options["rmlink"]))
				$this->removeLink = $options["rmlink"];	
			// multiple ID, need group in one line
			if (isset($options["multiid"]))
				$this->groupMultipleId = $options["multiid"];	
			else
				$this->groupMultipleId = false;	


		}

		public function render() {
			$found = false;

			$output = "";
			$tmpoutput = "";
			
			// for optimization we calcule this number here
			$attrCount = count($this->attrList);

			$sqlAttrList = ""; 
			for ($i=0;$i<$attrCount;$i++) {
				if ($i != 0) {
					$sqlAttrList .= ",";
				}

				$sqlAttrList .= $this->attrList[$i][1];
			}

			if ($this->groupMultipleId) {
				// For this type of show we need to bufferize
				$rowBuf = array();
				$query = FS::$dbMgr->Select(($this->prefixSQLTable ? PGDbConfig::getDbPrefix() : "").
					$this->sqlTable,$sqlAttrList,$this->sqlCondition,array("order" => $this->sqlAttrId));
					
				while($data = FS::$dbMgr->Fetch($query)) {
					if (!$found)
						$found = true;
					// Bufferize entry
					if (!isset($rowBuf[$data[$this->sqlAttrId]])) {
						$rowBuf[$data[$this->sqlAttrId]] = array();
					}

					// Store values into a buffer
					$entry = array();
					for ($i=1;$i<$attrCount;$i++) {
						$entry[] = $data[$this->attrList[$i][1]];
					}

					// Write buffer to row buffer
					$rowBuf[$data[$this->sqlAttrId]][] = $entry;
                }
				$tmpoutput = sprintf("%s%s",$tmpoutput,$this->showLineM($rowBuf,$attrCount));
			}
			else {
				$query = FS::$dbMgr->Select(($this->prefixSQLTable ? PGDbConfig::getDbPrefix() : "").
					$this->sqlTable,$sqlAttrList,$this->sqlCondition,array("order" => $this->sqlAttrId));
				while($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					$tmpoutput = sprintf("%s%s",$tmpoutput,$this->showLine($data,$attrCount));
				}
			}
            if ($found) {
				$output = sprintf("%s%s</table>%s",$this->showHeader(),$tmpoutput,FS::$iMgr->jsSortTable($this->tableId));
			}

			return sprintf("<div id=\"%s\">%s</div>",$this->tableDivId,$output);
		}

		private function showLineM($rowBuf,$attrCount) {
			FS::$iMgr->setJSBuffer(1);
			$output = "";

			foreach ($rowBuf as $rowIdx => $values) {
				$output = sprintf("%s<tr id=\"%s%s%s\"><td>%s</td>",$output,$this->trPrefix,FS::$iMgr->formatHTMLId($rowIdx),$this->trSuffix,
					FS::$iMgr->opendiv($this->opendivNumber,$rowIdx,
						array("lnkadd" => $this->opendivLink.$rowIdx)));
		
				$valueCount = count($values);
				// Read attributes
				for ($i=0;$i<$attrCount-1;$i++) {
					$output = sprintf("%s<td><ul>",$output);

					// Read values
					for ($j=0;$j<$valueCount;$j++) {
						$locoutput = "";
						// Boolean 
						if ($this->attrList[$i][2] == "b") {
							$locoutput = ($values[$j][$i] == 't' ? FS::$iMgr->img("styles/images/okay.png",15,15) : "");
						}
						// Select values
						else if ($this->attrList[$i][2] == "s") {
							$locoutput = FS::$iMgr->getLocale($this->attrList[$i][3][$values[$j][$i]]);
						}
						// Select values (raw mode)
						else if ($this->attrList[$i][2] == "sr") {
							$locoutput = $this->attrList[$i][3][$values[$j][$i]];
						}
						// Raw values
						else {
							$locoutput = $values[$j][$i];
						}
						$output = sprintf("%s<li>%s</li>",$output,$locoutput);
					}
					$output = sprintf("%s</ul></td>",$output);
				}

				if ($this->removeColumn) {
					$output = sprintf("%s<td>%s</td>",$output,FS::$iMgr->removeIcon($this->removeLink."=".$rowIdx,
						array("js" => true, "confirm" =>
						FS::$iMgr->getLocale($this->removeConfirm)."'".$rowIdx."' ?")));
				}
				$output = sprintf("%s</tr>",$output);
			}

			return $output;
		}

		private function showLine($sqlDatas,$attrCount) {
			FS::$iMgr->setJSBuffer(1);
			$output = "";
			
			for ($i=1;$i<$attrCount;$i++) {
				$locoutput = "";
				// Boolean 
				if ($this->attrList[$i][2] == "b") {
					$locoutput = ($sqlDatas[$this->attrList[$i][1]] == 't' ? FS::$iMgr->img("styles/images/okay.png",15,15) : "");	
				}
				// Select values
				else if ($this->attrList[$i][2] == "s") {
					$locoutput = FS::$iMgr->getLocale($this->attrList[$i][3][$sqlDatas[$this->attrList[$i][1]]]);
				}
				else if ($this->attrList[$i][2] == "sr") {
					$locoutput = $this->attrList[$i][3][$sqlDatas[$this->attrList[$i][1]]];
				}
				// Raw values
				else {
					$locoutput = $sqlDatas[$this->attrList[$i][1]];
				}
				
				$output = sprintf("%s<td>%s</td>",$output,$locoutput);
			}

			if ($this->removeColumn) {
				$output = sprintf("%s<td>%s</td>",$output,FS::$iMgr->removeIcon($this->removeLink."=".$sqlDatas[$this->sqlAttrId],
					array("js" => true, "confirm" =>
					FS::$iMgr->getLocale($this->removeConfirm)."'".$sqlDatas[$this->sqlAttrId]."' ?")));
			}

			return sprintf("<tr id=\"%s%s%s\"><td>%s</td>%s</tr>",$this->trPrefix,FS::$iMgr->formatHTMLId($sqlDatas[$this->sqlAttrId]),
				$this->trSuffix,FS::$iMgr->opendiv($this->opendivNumber,$sqlDatas[$this->sqlAttrId],
					array("lnkadd" => $this->opendivLink.$sqlDatas[$this->sqlAttrId])),$output);
		}

		public function addLine($idx,$edit) {
			$output = "";
			$jscontent = "";

			$count = FS::$dbMgr->Count(($this->prefixSQLTable ? PGDbConfig::getDbPrefix() : "").
				$this->sqlTable,$this->sqlAttrId);
			if ($count == 1) {
				$jscontent = sprintf("%s</table>",$this->showHeader());
				$output = sprintf("$('#%s').html('%s'); $('#%s').show('slow');",$this->tableDivId,
					FS::$secMgr->cleanForJS($jscontent),
					$this->tableDivId);
			}

			if ($edit) {
				$output = sprintf("%shideAndRemove('#%s'); setTimeout(function() {",$output,
					FS::$iMgr->formatHTMLId($this->trPrefix.$idx.$this->trSuffix));
			}

			$attrCount = count($this->attrList);
			$sqlAttrList = ""; 
			for ($i=0;$i<$attrCount;$i++) {
				if ($i != 0)
					$sqlAttrList .= ",";
				$sqlAttrList .= $this->attrList[$i][1];
			}

			$query = FS::$dbMgr->Select(($this->prefixSQLTable ? PGDbConfig::getDbPrefix() : "").
				$this->sqlTable,$sqlAttrList,$this->sqlAttrId."='".$idx."'",array("order" => $this->sqlAttrId));
			if ($this->groupMultipleId) {
				$rowBuf = array();
				while($data = FS::$dbMgr->Fetch($query)) {
					// Bufferize entry
					if (!isset($rowBuf[$data[$this->sqlAttrId]]))
						$rowBuf[$data[$this->sqlAttrId]] = array();

					// Store values into a buffer
					$entry = array();
					for ($i=1;$i<$attrCount;$i++) {
						$entry[] = $data[$this->attrList[$i][1]];
					}

					// Write buffer to row buffer
					$rowBuf[$data[$this->sqlAttrId]][] = $entry;
                }
				$jscontent = $this->showLineM($rowBuf,$attrCount);
			}
			else {
				if ($data = FS::$dbMgr->Fetch($query)) {
					$jscontent = $this->showLine($data,$attrCount);
				}
				else {
					$jscontent = "";
				}
			}

			$output = sprintf("%s$('%s').insertAfter('#%s');",$output,
				FS::$secMgr->cleanForJS($jscontent),$this->firstLineId);

			if ($edit) {
				$output = sprintf("%s},1000);",$output);
			}

			return $output;
		}

		private function showHeader() {
			FS::$iMgr->setJSBuffer(1);
			$attrCount = count($this->attrList);

            $output = sprintf("<table id=\"%s\"><thead><tr id=\"%s\"><th class=\"headerSortDown\">",
				$this->tableId,$this->firstLineId);
			
			for ($i=0;$i<$attrCount;$i++) {
				$output = sprintf("%s%s</th>",$output,FS::$iMgr->getLocale($this->attrList[$i][0]));
				
				if ($i < $attrCount-1) {
					$output = sprintf("%s<th>",$output);
				}
			}
			if ($this->removeColumn) {
				$output = sprintf("%s<th></th>",$output);
			}
			$output = sprintf("%s</tr></thead>",$output);
			return $output;
		}
		
		public function removeLine($id) {
			$count = FS::$dbMgr->Count(($this->prefixSQLTable ? PGDbConfig::getDbPrefix() : "").
				$this->sqlTable,$this->sqlAttrId);
			if ($count == 0) {
				return sprintf("hideAndEmpty('#%s');",$this->tableDivId);
			}
			else {
				return sprintf("hideAndRemove('#%s');",FS::$iMgr->formatHTMLId($this->trPrefix.$id.$this->trSuffix));
			}
		}

		private $tableId;
		private $tableDivId;
		private $firstLineId;
		private $sorted;
		private $attrList;

		// Showing related
		private $opendivNumber;
		private $opendivLink;
		private $trPrefix;
		private $trSuffix;
		private $groupMultipleId;

		// Remove related
		private $removeColumn;
		private $removeLink;
		private $removeConfirm;
		
		// SQL related
		private $sqlTable;
		private $prefixSQLTable;
		private $sqlAttrId;
		private $sqlCondition;
	};
?>
