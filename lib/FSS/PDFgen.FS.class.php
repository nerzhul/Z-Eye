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

	require_once("fpdf.php");

	class PDFgen extends FPDF {
		public function Header() {
			// Arial bold 15
			$this->SetFont('helvetica','B',15);
			// Title
			$this->Cell(0,0,$this->title,0,0,'C');
			// Line break
			$this->Ln(20);
		}

		public function Footer() {
		}

		function WriteHTML($html) {
			// HTML parser
			$html = str_replace("\n",' ',$html);
			$a = preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
			foreach($a as $i=>$e) {
				if($i%2==0) {
					// Text
					if($this->HREF)
						$this->PutLink($this->HREF,$e);
					else
						$this->Write(5,$e);
				}
				else {
					// Tag
					if($e[0]=='/')
						$this->CloseTag(strtoupper(substr($e,1)));
					else {
						// Extract attributes
						$a2 = explode(' ',$e);
						$tag = strtoupper(array_shift($a2));
						$attr = array();
						foreach($a2 as $v) {
							if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
								$attr[strtoupper($a3[1])] = $a3[2];
                				}
						$this->OpenTag($tag,$attr);
            				}
        			}
    			}
		}

		function OpenTag($tag, $attr) {
			// Opening tag
			if($tag=='B' || $tag=='I' || $tag=='U')
				$this->SetStyle($tag,true);
			if($tag=='A')
				$this->HREF = $attr['HREF'];
			if($tag=='BR')
				$this->Ln(5);
		}

		function CloseTag($tag) {
			// Closing tag
			if($tag=='B' || $tag=='I' || $tag=='U')
				$this->SetStyle($tag,false);
			if($tag=='A')
				$this->HREF = '';
		}

		function SetStyle($tag, $enable) {
			// Modify style and select corresponding font
			$this->$tag += ($enable ? 1 : -1);
			$style = '';
			foreach(array('B', 'I', 'U') as $s) {
				if($this->$s>0)
			        	$style .= $s;
			}
			$this->SetFont('',$style);
		}

		function PutLink($URL, $txt) {
			// Put a hyperlink
			$this->SetTextColor(0,0,255);
			$this->SetStyle('U',true);
			$this->Write(5,$txt,$URL);
			$this->SetStyle('U',false);
			$this->SetTextColor(0);
		}

		function CleanOutput() {
			while (ob_get_level())
				ob_end_clean();
			header("Content-Encoding: None", true);
			$this->Output();
		}
		var $B;
		var $I;
		var $U;
		var $HREF;
	}
?>
