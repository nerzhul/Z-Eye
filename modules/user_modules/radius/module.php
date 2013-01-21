<?php
	/*
        * Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/PDFgen.FS.class.php");
	
	class iRadius extends genModule{
		function iRadius() { parent::genModule(); $this->loc = new lRadius(); }
		public function Load() {
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
			$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
			$radport = FS::$secMgr->checkAndSecuriseGetData("p");
			$rad = $raddb."@".$radhost.":".$radport;
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err && FS::$secMgr->isNumeric($err)) {
				switch($err) {
					case 1: $output = FS::$iMgr->printError($this->loc->s("err-not-exist")); break;
					case 2: $output = FS::$iMgr->printError($this->loc->s("err-miss-data")); break;
					case 3: $output = FS::$iMgr->printError($this->loc->s("err-exist")); break;
					case 4: $output = FS::$iMgr->printError($this->loc->s("err-delete")); break;
					case 5: $output = FS::$iMgr->printError($this->loc->s("err-exist2")); break;
					case 6: $output = FS::$iMgr->printError($this->loc->s("err-invalid-table")); break;
				}
			}
			else
				$output = "";
			if(!FS::isAjaxCall()) {
				$found = 0;
				if(FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= "<h1>".$this->loc->s("title-deleg")."</h1>";
					$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1").FS::$iMgr->select("radius","submit()");
					$query = FS::$dbMgr->Select("z_eye_radius_db_list","addr,port,dbname,radalias");
					while($data = FS::$dbMgr->Fetch($query)) {
							if($found == 0) $found = 1;
							$radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
							$tmpoutput .= FS::$iMgr->selElmt($data["radalias"],$radpath,$rad == $radpath);
					}
					if($found) $output .= $tmpoutput."</select> ".FS::$iMgr->submit("",$this->loc->s("Manage"))."</form>";
					else $output .= FS::$iMgr->printError($this->loc->s("err-no-server"));
				}
				else {
					$output .= "<h1>".$this->loc->s("title-usermgmt")."</h1>";
					$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1").FS::$iMgr->select("radius","submit()");
					$query = FS::$dbMgr->Select("z_eye_radius_db_list","addr,port,dbname");
	                	        while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) $found = 1;
						$radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
						$tmpoutput .= FS::$iMgr->selElmt($radpath,$radpath,$rad == $radpath);
					}
					if($found) $output .= $tmpoutput."</select> ".FS::$iMgr->submit("",$this->loc->s("Administrate"))."</form>";
					else $output .= FS::$iMgr->printError($this->loc->s("err-no-server"));
				}
			}
			if($raddb && $radhost && $radport) {
				if(FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= $this->showDelegTool($radalias,$raddb,$radhost,$radport);
				}
				else {
					$radentry = FS::$secMgr->checkAndSecuriseGetData("radentry");
					$radentrytype = FS::$secMgr->checkAndSecuriseGetData("radentrytype");
					if($radentry && $radentrytype && ($radentrytype == 1 || $radentrytype == 2))
						$output .= $this->editRadiusEntry($raddb,$radhost,$radport,$radentry,$radentrytype);
					else
						$output .= $this->showRadiusAdmin($raddb,$radhost,$radport);
				}
			}
			else if(isset($sh))
				$output .= FS::$iMgr->printError($this->loc->s("err-invalid-tab"));

			return $output;
		}

		private function showDelegTool($radalias,$raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("mono-account"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("mass-account"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
                                $output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
			}
			else if(!$sh || $sh == 1) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$output .= "<div id=\"adduserres\"></div>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10",array("id" => "adduser"));
				$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Name")." *","radname");
				$output .= FS::$iMgr->idxLine($this->loc->s("Subname")." *","radsurname");
				$output .= FS::$iMgr->idxLine($this->loc->s("Identifier")." *","radusername");
				$output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil","","").FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
				$output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".
					FS::$iMgr->radioList("validity",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1);
				$output .= FS::$iMgr->calendar("startdate","",$this->loc->s("From"))."<br />";
				$output .= FS::$iMgr->hourlist("limhours","limmins")."<br />";
				$output .= FS::$iMgr->calendar("enddate","",$this->loc->s("To"))."<br />";
				$output .= FS::$iMgr->hourlist("limhoure","limmine",23,59);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"))."</table></form>";

				$output .= "<script type=\"text/javascript\">$('#adduser').submit(function(event) {
					event.preventDefault();
					$.post('index.php?mod=".$this->mid."&at=3&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10', $('#adduser').serialize(), function(data) {
						$('#adduserres').html(data);
					});
				});</script>";
			}
			else if($sh == 2) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$output .= "<div id=\"adduserlistres\"></div>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=11",array("id" => "adduserlist"));
				$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$output .= "<tr><td>".$this->loc->s("Generation-type")."</td><td style=\"text-align: left;\">".
					FS::$iMgr->radio("typegen",1,false,$this->loc->s("random-name"))."<br />".
					FS::$iMgr->radio("typegen",2,false,$this->loc->s("Prefix")." ").FS::$iMgr->input("prefix","")."</td></tr>";
		                $output .= FS::$iMgr->idxLine($this->loc->s("Account-nb")." *","nbacct","",array("size" => 4, "length" => 4, "type" => "num"));
	        	        $output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil2","","").FS::$iMgr->selElmt("","none").
					$this->addGroupList($radSQLMgr)."</select></td></tr>";
		                $output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".FS::$iMgr->radioList("validity2",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1);
                		$output .= FS::$iMgr->calendar("startdate2","",$this->loc->s("From"))."<br />";
				$output .= FS::$iMgr->hourlist("limhours2","limmins2")."<br />";
		                $output .= FS::$iMgr->calendar("enddate2","",$this->loc->s("To"))."<br />";
                		$output .= FS::$iMgr->hourlist("limhoure2","limmine2",23,59);
		                $output .= "</td></tr>";
                		$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"))."</table></form>";
			}
			else if($sh && $sh > 2)
				$output .= FS::$iMgr->printError($this->loc->s("err-bad-tab"));

			return $output;
		}

		private function showRadiusAdmin($raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("Users"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("Profils"),$sh);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("mass-import"),$sh);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("auto-import-dhcp"),$sh);
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("mono-account-deleg"),$sh);
				$output .= FS::$iMgr->tabPanElmt(7,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("mass-account-deleg"),$sh);
				$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,$this->loc->s("advanced-tools"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
			}
			else if(!$sh || $sh == 1) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();
				$formoutput = "<script type=\"text/javascript\"> function changeUForm() {
					if(document.getElementsByName('utype')[0].value == 1) {
						$('#userdf').show();
					}
					else if(document.getElementsByName('utype')[0].value == 2) {
						$('#userdf').hide();
					}
					else if(document.getElementsByName('utype')[0].value == 3) {
						$('#userdf').hide();
					}
				}; grpidx = 0; function addGrpForm() {
					$('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->select("ugroup'+grpidx+'","","Profil").FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select>";
				$formoutput .= " <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
					grpidx++;
				}
				function delGrpElmt(grpidx) {
						$('.ugroupli'+grpidx).remove();
				}
				attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->select("attrop'+attridx+'").
				FS::$iMgr->selElmt("=","=").
				FS::$iMgr->selElmt("==","==").
				FS::$iMgr->selElmt(":=",":=").
				FS::$iMgr->selElmt("+=","+=").
				FS::$iMgr->selElmt("!=","!=").
				FS::$iMgr->selElmt(">",">").
				FS::$iMgr->selElmt(">=",">=").
				FS::$iMgr->selElmt("<","<").
				FS::$iMgr->selElmt("<=","<=").
				FS::$iMgr->selElmt("=~","=~").
				FS::$iMgr->selElmt("!~","!~").
				FS::$iMgr->selElmt("=*","=*").
				FS::$iMgr->selElmt("!*","!*").
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40,"")." Cible ".FS::$iMgr->select("attrtarget'+attridx+'").
				FS::$iMgr->selElmt("check",1).
				FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertBefore('#formactions');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}</script>";

				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=2");
				$formoutput .= "<ul class=\"ulform\"><li>".
				FS::$iMgr->select("utype","changeUForm()",$this->loc->s("Auth-Type"));
				$formoutput .= FS::$iMgr->selElmt($this->loc->s("User"),1);
				$formoutput .= FS::$iMgr->selElmt($this->loc->s("Mac-addr"),2);
				//$formoutput .= FS::$iMgr->selElmt("Code PIN",3);
				$formoutput .= "</select></li><li>".
				FS::$iMgr->input("username","",20,40,$this->loc->s("User"))."</li><li>";
				$formoutput .= "<fieldset id=\"userdf\" style=\"border:0; padding:0; margin-left: -1px;\"><li>".
				FS::$iMgr->password("pwd","",$this->loc->s("Password"))."</li><li>".
				FS::$iMgr->select("upwdtype","",$this->loc->s("Pwd-Type")).
				FS::$iMgr->selElmt("Cleartext-Password",1).
				FS::$iMgr->selElmt("User-Password",2).
				FS::$iMgr->selElmt("Crypt-Password",3).
				FS::$iMgr->selElmt("MD5-Password",4).
				FS::$iMgr->selElmt("SHA1-Password",5).
				FS::$iMgr->selElmt("CHAP-Password",6).
				"</select></li></li><li id=\"formactions\">".FS::$iMgr->button("newgrp",$this->loc->s("New-Group"),"addGrpForm()").
				FS::$iMgr->button("newattr",$this->loc->s("New-Attribute"),"addAttrElmt('','','','')").
				FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("New-User"));
				$found = 0;

				// Filtering
				$output = "<script type=\"text/javascript\">function filterRadiusDatas() {
					$('#radd').fadeOut();
					$.post('index.php?mod=".$this->mid."&act=12&r=".$raddb."&h=".$radhost."&p=".$radport."', $('#radf').serialize(), function(data) {
							$('#radd').html(data);
							$('#radd').fadeIn();
							});
					}</script>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=12&r=".$raddb."&h=".$radhost."&p=".$radport,array("id" => "radf"));
				$output .= FS::$iMgr->select("uf","filterRadiusDatas()");
				$output .= FS::$iMgr->selElmt("--".$this->loc->s("Type")."--","",true);
				$output .= FS::$iMgr->selElmt($this->loc->s("Mac-addr"),"mac");
				$output .= FS::$iMgr->selElmt($this->loc->s("Other"),"other");
				$output .= "</select>";
				$output .= FS::$iMgr->select("ug","filterRadiusDatas()");
				$output .= FS::$iMgr->selElmt("--".$this->loc->s("Group")."--","",true);
				$query = $radSQLMgr->Select("radusergroup","distinct groupname");
				while($data = $radSQLMgr->Fetch($query))
						$output .= FS::$iMgr->selElmt($data["groupname"],$data["groupname"]);

				$output .= "</select>".FS::$iMgr->button("but",$this->loc->s("Filter"),"filterRadiusDatas()");
				$output .= "<div id=\"radd\">".$this->showRadiusDatas($radSQLMgr,$raddb,$radhost,$radport)."</div>";
			}
			else if($sh == 2) {
				$formoutput = "<script type=\"text/javascript\">attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->select("attrop'+attridx+'").
				FS::$iMgr->selElmt("=","=").
				FS::$iMgr->selElmt("==","==").
				FS::$iMgr->selElmt(":=",":=").
				FS::$iMgr->selElmt("+=","+=").
				FS::$iMgr->selElmt("!=","!=").
				FS::$iMgr->selElmt(">",">").
				FS::$iMgr->selElmt(">=",">=").
				FS::$iMgr->selElmt("<","<").
				FS::$iMgr->selElmt("<=","<=").
				FS::$iMgr->selElmt("=~","=~").
				FS::$iMgr->selElmt("!~","!~").
				FS::$iMgr->selElmt("=*","=*").
				FS::$iMgr->selElmt("!*","!*").
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40)." ".$this->loc->s("Target")." ".FS::$iMgr->select("attrtarget'+attridx+'").
				FS::$iMgr->selElmt("check",1).
				FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertAfter('#groupname');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}
				function addTemplAttributes() {
					switch($('#radgrptpl').val()) {
						case '1':
							addAttrElmt('Tunnel-Private-Group-Id','','=','2');
							addAttrElmt('Tunnel-Type','13','=','2');
							addAttrElmt('Tunnel-Medium-Type','6','=','2');
							break;
					}
				};
				$.event.props.push('dataTransfer');
					$('#radgrp #dragtd').on({
					mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
							mouseleave: function(e) { $('#trash').hide(); $('#editf').hide(); },
							dragstart: function(e) { $('#trash').show(); $('#editf').show(); e.dataTransfer.setData('text/html', $(this).text()); },
							dragenter: function(e) { e.preventDefault();},
							dragover: function(e) { e.preventDefault(); },
							drop: function(e) {},
							dragend: function() { $('#trash').hide(); $('#editf').hide(); }
					});
				$('#trash').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $('#subpop').html('".$this->loc->s("Delete-profil")." \''+e.dataTransfer.getData('text/html')+'\' ?".
					FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=5").
					FS::$iMgr->hidden("group","'+e.dataTransfer.getData('text/html')+'").
					FS::$iMgr->submit("",$this->loc->s("Delete")).
					FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"$(\'#pop\').hide()")."</form>');
					$('#pop').show();
						}
				});
				$('#editf').on({
						dragover: function(e) { e.preventDefault(); },
						drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=2&radentry='+e.dataTransfer.getData('text/html')); }
				});
				</script>";
				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=3");
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->select("radgrptpl","addTemplAttributes()","Template");
				$formoutput .= FS::$iMgr->selElmt($this->loc->s("None"),0);
				$formoutput .= FS::$iMgr->selElmt("VLAN",1);
				$formoutput .= "</select></li><li>".
				FS::$iMgr->input("groupname","",20,40,$this->loc->s("Profilname"))."</li><li>".
				FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->submit("",$this->loc->s("Save")).
				"</li></ul></form>";
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("New-Profil"));
				$tmpoutput = "<h3>".$this->loc->s("title-profillist")."</h3>";
				$found = 0;
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$groups=array();
				$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
				while($data = $radSQLMgr->Fetch($query)) {
					$rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
					if(!isset($groups[$data["groupname"]]))
						$groups[$data["groupname"]] = $rcount;
				}

				$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
				while($data = $radSQLMgr->Fetch($query)) {
					$rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
					if(!isset($groups[$data["groupname"]]))
							$groups[$data["groupname"]] = $rcount;
					else
						$groups[$data["groupname"]] += $rcount;
                }
				if(count($groups) > 0) {
					$output .= "<table id=\"radgrp\" style=\"width:30%;\"><tr><th>".$this->loc->s("Group")."</th><th style=\"width:30%\">".$this->loc->s("User-nb")."</th></tr>";
					foreach($groups as $key => $value)
						$output .= "<tr><td id=\"dragtd\" draggable=\"true\">".$key."</td><td>".$value."</td></tr>";
					$output .= "</table>";
				}
			}
			else if($sh == 3) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();
				$grouplist= FS::$iMgr->selElmt("","none");
				$groups=array();
				$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
				while($data = $radSQLMgr->Fetch($query)) {
						if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
				}
				$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
				while($data = $radSQLMgr->Fetch($query)) {
						if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
				}
				$count = count($groups);
				for($i=0;$i<$count;$i++) {
					$grouplist .= FS::$iMgr->selElmt($groups[$i],$groups[$i]);
				}

				$output .= "<h3>".$this->loc->s("title-mass-import")."</h3>";
				$output .= "<script type=\"text/javascript\"> function changeUForm() {
						if(document.getElementsByName('usertype')[0].value == 1) {
								$('#uptype').show(); $('#csvtooltip').html(\"<b>Note: </b>Les noms d'utilisateurs ne peuvent pas contenir d'espace.<br />Les mots de passe doivent être en clair.<br />Caractère de formatage: <b>,</b>\");
						}
						else if(document.getElementsByName('usertype')[0].value == 2) {
								$('#uptype').hide(); $('#csvtooltip').html('<b>Note: </b> Les adresses MAC peuvent être de la forme <b>aa:bb:cc:dd:ee:ff</b>, <b>aa-bb-cc-dd-ee-ff</b> ou <b>aabbccddeeff</b> et ne sont pas sensibles à la casse.');
						}
						else if(document.getElementsByName('usertype')[0].value == 3) {
								$('#uptype').hide(); $('#csvtooltip').html('');
						}
				}; </script>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=6"); // #todo change this
				$output .= "<ul class=\"ulform\"><li width=\"100%\">".FS::$iMgr->select("usertype","changeUForm()","Type d'authentification");
				$output .= FS::$iMgr->selElmt($this->loc->s("User"),1);
				$output .= FS::$iMgr->selElmt($this->loc->s("Mac-addr"),2);
				//$formoutput .= FS::$iMgr->selElmt("Code PIN",3);
				$output .= "</select></li><li id=\"uptype\">".FS::$iMgr->select("upwdtype","",$this->loc->s("Pwd-Type")).
				FS::$iMgr->selElmt("Cleartext-Password",1).
				FS::$iMgr->selElmt("User-Password",2).
				FS::$iMgr->selElmt("Crypt-Password",3).
				FS::$iMgr->selElmt("MD5-Password",4).
				FS::$iMgr->selElmt("SHA1-Password",5).
				FS::$iMgr->selElmt("CHAP-Password",6).
				"</select></li><li>".FS::$iMgr->select("ugroup","",$this->loc->s("Profil")).FS::$iMgr->selElmt("","none").
				$this->addGroupList($radSQLMgr)."</select></li><li>".FS::$iMgr->textarea("csvlist","",580,330,$this->loc->s("Userlist-CSV")).
					"</li><li id=\"csvtooltip\">".$this->loc->s("mass-import-restriction")."</li><li>".
				FS::$iMgr->submit("","Importer")."</li></ul></form>";
			}
			else if($sh == 4) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$output = "";

				$found = 0;
				$formoutput = "";
				$formoutput .= "<h3>".$this->loc->s("title-auto-import")."</h3>";
				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=7");
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->select("subnet","","Subnet DHCP");
				$query = FS::$dbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask");
				while($data = FS::$dbMgr->Fetch($query)) {
					if(!$found) $found = 1;
						$formoutput .= FS::$iMgr->selElmt($data["netid"]."/".$data["netmask"],$data["netid"]);
				}
				if($found) {
					$found = 0;
					$formoutput .= "</select></li><li>".FS::$iMgr->select("radgroup","",$this->loc->s("Radius-profile"));

					$groups=array();
					$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
					while($data = $radSQLMgr->Fetch($query)) {
							if(!isset($groups[$data["groupname"]]))
									$groups[$data["groupname"]] = 1;
					}

					$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
					while($data = $radSQLMgr->Fetch($query)) {
							if(!isset($groups[$data["groupname"]]))
									$groups[$data["groupname"]] = 1;
					}
					if(count($groups) > 0) {
						$found = 1;
						foreach($groups as $key => $value)
						$formoutput .= FS::$iMgr->selElmt($key,$key);
					}
					$formoutput .= "</select></li><li>".FS::$iMgr->submit("",$this->loc->s("Add"))."</li></ul></form>";
				}
				if($found) {
					$output .= $formoutput;
					$found = 0;
					$tmpoutput = "";
					$tmpoutput .= "<script type=\"text/javascript\">
						$.event.props.push('dataTransfer');
						$('#radsubnet #dragtd').on({
								mouseover: function(e) { $('#trash').show(); },
								mouseleave: function(e) { $('#trash').hide(); },
								dragstart: function(e) { $('#trash').show(); e.dataTransfer.setData('text/html', $(this).text()); },
								dragenter: function(e) { e.preventDefault();},
								dragover: function(e) { e.preventDefault(); },
								dragleave: function(e) { },
							drop: function(e) {},
							dragend: function() { $('#trash').hide(); $('#editf').hide();}
						});
						$('#trash').on({
								dragover: function(e) { e.preventDefault(); },
								drop: function(e) { $('#subpop').html('".$this->loc->s("Delete-subnet-import")." \''+e.dataTransfer.getData('text/html')+'\' ?".
										FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=8").
										FS::$iMgr->hidden("subnet","'+e.dataTransfer.getData('text/html')+'").
										FS::$iMgr->submit("","Supprimer").
										FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"$(\'#pop\').hide()")."</form>');
										$('#pop').show();
								}
						});
					</script>";
					$query = FS::$dbMgr->Select("z_eye_radius_dhcp_import","dhcpsubnet,groupname","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= "<h3>".$this->loc->s("title-auto-import2")."</h3><table id=\"radsubnet\"><tr><th>".$this->loc->s("DHCP-zone")."</th><th>".
							$this->loc->s("Radius-profile")."</th></tr>";
						}
						$tmpoutput .= "<tr><td draggable=\"true\" id=\"dragtd\">".$data["dhcpsubnet"]."</td><td>".$data["groupname"]."</td></tr>";
					}
					if($found) $output .= $tmpoutput."</table>";
				}
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-subnet-for-import"));
			}
			else if($sh == 5) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new AbstractSQLMgr();
                                if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
	                                $radSQLMgr->Connect();

				$output .= "<h3>".$this->loc->s("title-cleanusers")."</h3>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=9");
				$output .= "<table>";

				$radexpenable = FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexptable = FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_table' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexpuser = FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_user_field' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexpdate = FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_date_field' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				
				$output .= FS::$iMgr->idxLine($this->loc->s("enable-autoclean"),"cleanradsqlenable", ($radexpenable == 1 ? true : false), array("type" => "chk"));
				$output .= FS::$iMgr->idxLine($this->loc->s("SQL-table"),"cleanradsqltable",$radexptable);
				$output .= FS::$iMgr->idxLine($this->loc->s("user-field"),"cleanradsqluserfield",$radexpuser);
				$output .= FS::$iMgr->idxLine($this->loc->s("expiration-field"),"cleanradsqlexpfield",$radexpdate);
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"))."</form>";
			}
			else if($sh == 6) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$output .= "<div id=\"adduserres\"></div>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10",array("id" => "adduser"));
				$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>Valeur</th></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Name")." *","radname");
				$output .= FS::$iMgr->idxLine($this->loc->s("Subname")." *","radsurname");
				$output .= FS::$iMgr->idxLine($this->loc->s("Identifier")." *","radusername");
				$output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil","","").FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
				$output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".FS::$iMgr->radioList("validity",array(1,2),array("Toujours valide","Période"),1);
				$output .= FS::$iMgr->calendar("startdate","",$this->loc->s("From"))."<br />";
				$output .= FS::$iMgr->hourlist("limhours","limmins")."<br />";
				$output .= FS::$iMgr->calendar("enddate","",$this->loc->s("To"))."<br />";
				$output .= FS::$iMgr->hourlist("limhoure","limmine",23,59);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"))."</table></form>";

				$output .= "<script type=\"text/javascript\">$('#adduser').submit(function(event) {
					event.preventDefault();
					$.post('index.php?mod=".$this->mid."&at=3&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10', $('#adduser').serialize(), function(data) {
						$('#adduserres').html(data);
					});
				});</script>";
			}
			else if($sh == 7) {
				$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new AbstractSQLMgr();
				if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
					$radSQLMgr->Connect();

				$output .= "<div id=\"adduserlistres\"></div>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=11",array("id" => "adduserlist"));
				$output .= "<table><tr><th>".$this->loc->s("entitlement")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$output .= "<tr><td>".$this->loc->s("Generation-type")."</td><td style=\"text-align: left;\">".
				FS::$iMgr->radio("typegen",1,false,$this->loc->s("random-name"))."<br />".FS::$iMgr->radio("typegen",2,false,$this->loc->s("Prefix")." ").FS::$iMgr->input("prefix","")."</td></tr>";
				$output .= FS::$iMgr->idxLine($this->loc->s("Account-nb")." *","nbacct","",array("size" => 4,"length" => 4, "type" => "num"));
				$output .= "<tr><td>".$this->loc->s("Profil")."</td><td>".FS::$iMgr->select("profil2","","").FS::$iMgr->selElmt("","none").
				$this->addGroupList($radSQLMgr)."</select></td></tr>";
				$output .= "<tr><td>".$this->loc->s("Validity")."</td><td>".FS::$iMgr->radioList("validity2",array(1,2),array($this->loc->s("Already-valid"),$this->loc->s("Period")),1);
				$output .= FS::$iMgr->calendar("startdate2","",$this->loc->s("From"))."<br />";
				$output .= FS::$iMgr->hourlist("limhours2","limmins2")."<br />";
				$output .= FS::$iMgr->calendar("enddate2","",$this->loc->s("To"))."<br />";
				$output .= FS::$iMgr->hourlist("limhoure2","limmine2",23,59);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"))."</table></form>";
			}
			else {
				$output .= FS::$iMgr->printError($this->loc->s("err-bad-tab"));
			}
			return $output;
		}
		
		private function showRadiusDatas($radSQLMgr,$raddb,$radhost,$radport) {
			$found = false;
			$output = "";
			$ug = FS::$secMgr->checkAndSecurisePostData("ug");
			$uf = FS::$secMgr->checkAndSecurisePostData("uf");
			$tmpoutput = "<h3>".$this->loc->s("title-userlist")."</h3>";
			$tmpoutput .= "<script type=\"text/javascript\">
				$.event.props.push('dataTransfer');
				$('#raduser #dragtd').on({
					mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
					mouseleave: function(e) { $('#trash').hide(); $('#editf').hide(); },
					dragstart: function(e) { $('#trash').show(); $('#editf').show(); e.dataTransfer.setData('text/html', $(this).text()); },
					dragenter: function(e) { e.preventDefault();},
					dragover: function(e) { e.preventDefault(); },
					dragleave: function(e) { },
					drop: function(e) {},
					dragend: function() { $('#trash').hide(); $('#editf').hide();}
				});
				$('#trash').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $('#subpop').html('".$this->loc->s("sure-delete-user")." \''+e.dataTransfer.getData('text/html')+'\' ?".
						FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=4").
						FS::$iMgr->hidden("user","'+e.dataTransfer.getData('text/html')+'").
						FS::$iMgr->check("logdel",array("label" => $this->loc->s("Delete-logs")." ?"))."<br />".
						FS::$iMgr->check("acctdel",array("label" => $this->loc->s("Delete-accounting")." ?"))."<br />".
						FS::$iMgr->submit("",$this->loc->s("Delete")).
						FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"$(\'#pop\').hide()")."</form>');
						$('#pop').show();
					}
				});
				$('#editf').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=1&radentry='+e.dataTransfer.getData('text/html')); }
				});
				</script>";
			$query = $radSQLMgr->Select("radcheck","id,username,value","attribute IN ('Auth-Type','Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')".($ug ? " AND username IN (SELECT username FROM radusergroup WHERE groupname = '".$ug."')" : ""));
			$expirationbuffer = array();
			while($data = $radSQLMgr->Fetch($query)) {
				if(!$found && (!$uf || $uf != "mac" && $uf != "other" || $uf == "mac" && preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"]) 
					|| $uf == "other" && !preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"]))) {
					$found = true;
					$tmpoutput .= "<table id=\"raduser\" style=\"width:70%\"><tr><th>Id</th><th>Utilisateur</th><th>Mot de passe</th><th>Groupes</th><th>Date d'expiration</th></tr>";
					$query2 = $radSQLMgr->Select("z_eye_radusers","username,expiration","expiration > 0");
					while($data2 = $radSQLMgr->Fetch($query2)) {
						if(!isset($expirationbuffer[$data2["username"]])) $expirationbuffer[$data2["username"]] = date("d/m/y h:i",strtotime($data2["expiration"]));
					}
				}
				if(!$uf || $uf != "mac" && $uf != "other" || $uf == "mac" && preg_match('#^([0-9A-F]{12})$#i', $data["username"])
					|| $uf == "other" && !preg_match('#^([0-9A-Fa-f]{12})$#i', $data["username"])) {
					$tmpoutput .= "<tr><td>".$data["id"]."</td><td id=\"dragtd\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=1&radentry=".$data["username"]."\">".$data["username"]."</a></td><td>".$data["value"]."</td><td>";
					$query2 = $radSQLMgr->Select("radusergroup","groupname","username = '".$data["username"]."'");
					$found2 = 0;
					while($data2 = $radSQLMgr->Fetch($query2)) {
						if($found2 == 0) $found2 = 1;
						else $tmpoutput .= "<br />";
						$tmpoutput .= $data2["groupname"];
					}
					$tmpoutput .= "</td><td>";
					$tmpoutput .= (isset($expirationbuffer[$data["username"]]) ? $expirationbuffer[$data["username"]] : "Jamais");
					$tmpoutput .= "</td></tr>";
				}
			}
			if($found) $output = $tmpoutput."</table>";
			return $output;
		}

		private function editRadiusEntry($raddb,$radhost,$radport,$radentry,$radentrytype) {
			$output = "";
			FS::$iMgr->showReturnMenu(true);
			$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
			$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
			$radSQLMgr = new AbstractSQLMgr();
			if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
				$radSQLMgr->Connect();

			if($radentrytype == 1) {
				$userexist = $radSQLMgr->GetOneData("radcheck","username","username = '".$radentry."'");
				if(!$userexist) {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-user"));
					return $output;
				}
				$userpwd = $radSQLMgr->GetOneData("radcheck","value","username = '".$radentry."' AND op = ':=' AND attribute IN('Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')");
				if($userpwd)
					$upwdtype = $radSQLMgr->GetOneData("radcheck","attribute","username = '".$radentry."' AND op = ':=' AND value = '".$userpwd."'");
				$grpcount = $radSQLMgr->Count("radusergroup","groupname","username = '".$radentry."'");
				$attrcount = $radSQLMgr->Count("radcheck","username","username = '".$radentry."'");
				$attrcount += $radSQLMgr->Count("radreply","username","username = '".$radentry."'");
				$formoutput = "<script type=\"text/javascript\">grpidx = ".$grpcount."; 
				function addGrpForm() {
					$('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->select("ugroup'+grpidx+'","","Profil").FS::$iMgr->selElmt("","none").$this->addGroupList($radSQLMgr)."</select> <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
					grpidx++;
				}
				function delGrpElmt(grpidx) {
						$('.ugroupli'+grpidx).remove();
				}
				attridx = ".$attrcount."; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Valeur".
				FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40,"")." Op ".FS::$iMgr->select("attrop'+attridx+'").
				FS::$iMgr->selElmt("=","=").
				FS::$iMgr->selElmt("==","==").
				FS::$iMgr->selElmt(":=",":=").
				FS::$iMgr->selElmt("+=","+=").
				FS::$iMgr->selElmt("!=","!=").
				FS::$iMgr->selElmt(">",">").
				FS::$iMgr->selElmt(">=",">=").
				FS::$iMgr->selElmt("<","<").
				FS::$iMgr->selElmt("<=","<=").
				FS::$iMgr->selElmt("=~","=~").
				FS::$iMgr->selElmt("!~","!~").
				FS::$iMgr->selElmt("=*","=*").
				FS::$iMgr->selElmt("!*","!*").
				"</select> Cible ".FS::$iMgr->select("attrtarget'+attridx+'").
				FS::$iMgr->selElmt("check",1).
				FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertBefore('#formactions');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}</script>";

				if(FS::$secMgr->isMacAddr($radentry) || preg_match('#^[0-9A-F]{12}$#i', $radentry))
					$utype = 2;
				else
					$utype = 1;
				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=2");
				$formoutput .= FS::$iMgr->hidden("uedit",1);
				$formoutput .= "<h3>".$this->loc->s("title-usermod")." '".$radentry."'</h3>";
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->hidden("utype",$utype)."<b>".$this->loc->s("User-type").": </b>".
				($utype == 1 ? "Normal" : $this->loc->s("Mac-addr"));
				$formoutput .= "</li><li>".
				FS::$iMgr->hidden("username",$radentry)."</li>";
				if(FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'") == 1) {
					$creadate = $radSQLMgr->GetOneData("z_eye_radusers","creadate","username='".$radentry."'");
					$formoutput .= "<li><b>".$this->loc->s("Creation-date").": </b>".$creadate."</li>";
				}
				if($utype == 1) {
					$formoutput .= "<li><fieldset id=\"userdf\" style=\"border:0;\">";
					$pwd = $radSQLMgr->GetOneData("radcheck","value","username = '".$radentry."' AND attribute = 'Cleartext-Password' AND op = ':='");
					$formoutput .= FS::$iMgr->password("pwd",$pwd,$this->loc->s("Password"))."<br />".
					FS::$iMgr->select("upwdtype","",$this->loc->s("Pwd-Type")).
					FS::$iMgr->selElmt("Cleartext-Password",1,($upwdtype && $upwdtype == "Cleartext-Password" ? true : false)).
					FS::$iMgr->selElmt("User-Password",2,($upwdtype && $upwdtype == "User-Password" ? true : false)).
					FS::$iMgr->selElmt("Crypt-Password",3,($upwdtype && $upwdtype == "Crypt-Password" ? true : false)).
					FS::$iMgr->selElmt("MD5-Password",4,($upwdtype && $upwdtype == "MD5-Password" ? true : false)).
					FS::$iMgr->selElmt("SHA1-Password",5,($upwdtype && $upwdtype == "SHA1-Password" ? true : false)).
					FS::$iMgr->selElmt("CHAP-Password",6,($upwdtype && $upwdtype == "CHAP-Password" ? true : false)).
					"</select></fieldset></li>";
				}
				$query = $radSQLMgr->Select("radusergroup","groupname","username = '".$radentry."'");
				$grpidx = 0;
				while($data = $radSQLMgr->Fetch($query)) {
					$formoutput .= "<li class=\"ugroupli".$grpidx."\">".FS::$iMgr->select("ugroup".$grpidx,"",$this->loc->s("Profil")).
					$this->addGroupList($radSQLMgr,$data["groupname"])."</select> <a onclick=\"javascript:delGrpElmt(".$grpidx.");\">X</a></li>";
					$grpidx++;
				}
				$attridx = 0;
				$query = $radSQLMgr->Select("radcheck","attribute,op,value","username = '".$radentry."' AND attribute <> 'Cleartext-Password'");
				while($data = $radSQLMgr->Fetch($query)) {
					if(!($utype == 2 && $data["attribute"] == "Auth-Type" && $data["op"] == ":=" && $data["value"] == "Accept")) {
						$formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
						FS::$iMgr->select("attrop".$attridx).
						FS::$iMgr->selElmt("=","=",($data["op"] == "=" ? true : false)).
						FS::$iMgr->selElmt("==","==",($data["op"] == "==" ? true : false)).
						FS::$iMgr->selElmt(":=",":=",($data["op"] == ":=" ? true : false)).
						FS::$iMgr->selElmt("+=","+=",($data["op"] == "+=" ? true : false)).
						FS::$iMgr->selElmt("!=","!=",($data["op"] == "!=" ? true : false)).
						FS::$iMgr->selElmt(">",">",($data["op"] == ">" ? true : false)).
						FS::$iMgr->selElmt(">=",">=",($data["op"] == ">=" ? true : false)).
						FS::$iMgr->selElmt("<","<",($data["op"] == "<" ? true : false)).
						FS::$iMgr->selElmt("<=","<=",($data["op"] == "<=" ? true : false)).
						FS::$iMgr->selElmt("=~","=~",($data["op"] == "=~" ? true : false)).
						FS::$iMgr->selElmt("!~","!~",($data["op"] == "!~" ? true : false)).
						FS::$iMgr->selElmt("=*","=*",($data["op"] == "=*" ? true : false)).
						FS::$iMgr->selElmt("!*","!*",($data["op"] == "!*" ? true : false)).
						"</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->select("attrtarget".$attridx).
						FS::$iMgr->selElmt("check",1,true).
						FS::$iMgr->selElmt("reply",2)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
						$attridx++;
					}
				}
				$query = $radSQLMgr->Select("radreply","attribute,op,value","username = '".$radentry."'");
				while($data = $radSQLMgr->Fetch($query)) {
						$formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
						FS::$iMgr->select("attrop".$attridx).
						FS::$iMgr->selElmt("=","=",($data["op"] == "=" ? true : false)).
						FS::$iMgr->selElmt("==","==",($data["op"] == "==" ? true : false)).
						FS::$iMgr->selElmt(":=",":=",($data["op"] == ":=" ? true : false)).
						FS::$iMgr->selElmt("+=","+=",($data["op"] == "+=" ? true : false)).
						FS::$iMgr->selElmt("!=","!=",($data["op"] == "!=" ? true : false)).
						FS::$iMgr->selElmt(">",">",($data["op"] == ">" ? true : false)).
						FS::$iMgr->selElmt(">=",">=",($data["op"] == ">=" ? true : false)).
						FS::$iMgr->selElmt("<","<",($data["op"] == "<" ? true : false)).
						FS::$iMgr->selElmt("<=","<=",($data["op"] == "<=" ? true : false)).
						FS::$iMgr->selElmt("=~","=~",($data["op"] == "=~" ? true : false)).
						FS::$iMgr->selElmt("!~","!~",($data["op"] == "!~" ? true : false)).
						FS::$iMgr->selElmt("=*","=*",($data["op"] == "=*" ? true : false)).
						FS::$iMgr->selElmt("!*","!*",($data["op"] == "!*" ? true : false)).
						"</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->select("attrtarget".$attridx).
						FS::$iMgr->selElmt("check",1).
						FS::$iMgr->selElmt("reply",2,true)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
						$attridx++;
				}

				// if expiration module is activated, show the options
				if(FS::$dbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'") == 1) {
					$expdate = $radSQLMgr->GetOneData("z_eye_radusers","expiration","username='".$radentry."'");
					$startdate = $radSQLMgr->GetOneData("z_eye_radusers","startdate","username='".$radentry."'");
					$formoutput .= "<li>".FS::$iMgr->calendar("starttime",$startdate ? date("d-m-y",strtotime($startdate)) : "",$this->loc->s("Acct-start-date"))."</li>";
					$formoutput .= "<li>".FS::$iMgr->calendar("expiretime",$expdate ? date("d-m-y",strtotime($expdate)) : "",$this->loc->s("Acct-expiration-date"))."</li>";
				}
				$formoutput .= "<li id=\"formactions\">".FS::$iMgr->button("newgrp",$this->loc->s("New-Group"),"addGrpForm()").
				FS::$iMgr->button("newattr",$this->loc->s("New-Attribute"),"addAttrElmt('','','','')").
				FS::$iMgr->submit("",$this->loc->s("Save"))."</form></li></ul>";
				$output .= $formoutput;
			}
			else if($radentrytype == 2) {
				$groupexist = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$radentry."'");
				if(!$groupexist)
					$groupexist = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$radentry."'");
				if(!$groupexist) {
					$output .= FS::$iMgr->printError("Groupe inexistant !");
					return $output;
				}
				$attrcount = $radSQLMgr->Count("radgroupcheck","groupname","groupname = '".$radentry."'");
				$attrcount += $radSQLMgr->Count("radgroupreply","groupname","groupname = '".$radentry."'");
				$formoutput = "<script type=\"text/javascript\">attridx = ".$attrcount."; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->select("attrop'+attridx+'").
				FS::$iMgr->selElmt("=","=").
				FS::$iMgr->selElmt("==","==").
				FS::$iMgr->selElmt(":=",":=").
				FS::$iMgr->selElmt("+=","+=").
				FS::$iMgr->selElmt("!=","!=").
				FS::$iMgr->selElmt(">",">").
				FS::$iMgr->selElmt(">=",">=").
				FS::$iMgr->selElmt("<","<").
				FS::$iMgr->selElmt("<=","<=").
				FS::$iMgr->selElmt("=~","=~").
				FS::$iMgr->selElmt("!~","!~").
				FS::$iMgr->selElmt("=*","=*").
				FS::$iMgr->selElmt("!*","!*").
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40)." ".$this->loc->s("Target")." ".FS::$iMgr->select("attrtarget'+attridx+'").
				FS::$iMgr->selElmt("check",1).
				FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertAfter('#groupname');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
						$('.attrli'+attridx).remove();
				}</script>";
				$formoutput .= "<h3>Modification du groupe '".$radentry."'</h3>";
				$formoutput .= "<ul class=\"ulform\">";
				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=3");
                $formoutput .= FS::$iMgr->hidden("uedit",1).FS::$iMgr->hidden("groupname",$radentry);
				$attridx = 0;
				$query = $radSQLMgr->Select("radgroupcheck","attribute,op,value","groupname = '".$radentry."'");
				while($data = $radSQLMgr->Fetch($query)) {
					 $formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
					 FS::$iMgr->select("attrop".$attridx).
					 FS::$iMgr->selElmt("=","=",($data["op"] == "=" ? true : false)).
					 FS::$iMgr->selElmt("==","==",($data["op"] == "==" ? true : false)).
					 FS::$iMgr->selElmt(":=",":=",($data["op"] == ":=" ? true : false)).
					 FS::$iMgr->selElmt("+=","+=",($data["op"] == "+=" ? true : false)).
					 FS::$iMgr->selElmt("!=","!=",($data["op"] == "!=" ? true : false)).
					 FS::$iMgr->selElmt(">",">",($data["op"] == ">" ? true : false)).
					 FS::$iMgr->selElmt(">=",">=",($data["op"] == ">=" ? true : false)).
					 FS::$iMgr->selElmt("<","<",($data["op"] == "<" ? true : false)).
					 FS::$iMgr->selElmt("<=","<=",($data["op"] == "<=" ? true : false)).
					 FS::$iMgr->selElmt("=~","=~",($data["op"] == "=~" ? true : false)).
					 FS::$iMgr->selElmt("!~","!~",($data["op"] == "!~" ? true : false)).
					 FS::$iMgr->selElmt("=*","=*",($data["op"] == "=*" ? true : false)).
					 FS::$iMgr->selElmt("!*","!*",($data["op"] == "!*" ? true : false)).
					 "</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->select("attrtarget".$attridx).
					 FS::$iMgr->selElmt("check",1,true).
					 FS::$iMgr->selElmt("reply",2)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
					 $attridx++;
				}

				$query = $radSQLMgr->Select("radgroupreply","attribute,op,value","groupname = '".$radentry."'");
				while($data = $radSQLMgr->Fetch($query)) {
					$formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
					FS::$iMgr->select("attrop".$attridx).
					FS::$iMgr->selElmt("=","=",($data["op"] == "=" ? true : false)).
					FS::$iMgr->selElmt("==","==",($data["op"] == "==" ? true : false)).
					FS::$iMgr->selElmt(":=",":=",($data["op"] == ":=" ? true : false)).
					FS::$iMgr->selElmt("+=","+=",($data["op"] == "+=" ? true : false)).
					FS::$iMgr->selElmt("!=","!=",($data["op"] == "!=" ? true : false)).
					FS::$iMgr->selElmt(">",">",($data["op"] == ">" ? true : false)).
					FS::$iMgr->selElmt(">=",">=",($data["op"] == ">=" ? true : false)).
					FS::$iMgr->selElmt("<","<",($data["op"] == "<" ? true : false)).
					FS::$iMgr->selElmt("<=","<=",($data["op"] == "<=" ? true : false)).
					FS::$iMgr->selElmt("=~","=~",($data["op"] == "=~" ? true : false)).
					FS::$iMgr->selElmt("!~","!~",($data["op"] == "!~" ? true : false)).
					FS::$iMgr->selElmt("=*","=*",($data["op"] == "=*" ? true : false)).
					FS::$iMgr->selElmt("!*","!*",($data["op"] == "!*" ? true : false)).
					"</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->select("attrtarget".$attridx).
					FS::$iMgr->selElmt("check",1).
					FS::$iMgr->selElmt("reply",2,true)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
					$attridx++;
				}
				$formoutput .= "<li>".FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
				$output .= $formoutput;
			}
			else
				$output .= FS::$iMgr->printError("Type d'entrée invalide !");
			return $output;
		}

		private function addGroupList($radSQLMgr,$selectEntry="") {
			$output = "";
			$groups=array();
			$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
			while($data = $radSQLMgr->Fetch($query)) {
				if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
			}
			$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
			while($data = $radSQLMgr->Fetch($query)) {
				if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
			}
			$count = count($groups);
			for($i=0;$i<$count;$i++) {
				$output .= FS::$iMgr->selElmt($groups[$i],$groups[$i],($groups[$i] == $selectEntry ? true : false));
			}
			return $output;
		}

		public function handlePostDatas($act) {
                        switch($act) {
				case 1:
					$rad = FS::$secMgr->checkAndSecurisePostData("radius");
					if(!$rad) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Missing datas for radius selection");
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$radcut1 = preg_split("[@]",$rad);
					if(count($radcut1) != 2) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Wrong datas for radius selection");
						header("Location: index.php?mod=".$this->mid."&err=1");
                        return;
					}
					$radcut2 = preg_split("[:]",$radcut1[1]);
					if(count($radcut2) != 2) {
							FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Wrong datas for radius selection");
							header("Location: index.php?mod=".$this->mid."&err=1");
							return;
					}
					header("Location: index.php?mod=".$this->mid."&h=".$radcut2[0]."&p=".$radcut2[1]."&r=".$radcut1[0]);
					return;
				case 2: // User edition
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$utype = FS::$secMgr->checkAndSecurisePostData("utype");
					$username = FS::$secMgr->checkAndSecurisePostData("username");
					$upwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$upwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");

					// Check all fields
					if(!$username || $username == "" || !$utype || !FS::$secMgr->isNumeric($utype) ||
						$utype < 1 || $utype > 3 || 
						($utype == 1 && (!$upwd || $upwd == "" || !$upwdtype || $upwdtype < 1 || $upwdtype > 6))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for user edition");
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					// if type 2: must be a mac addr
					if($utype == 2 && (!FS::$secMgr->isMacAddr($username) && !preg_match('#^[0-9A-F]{12}$#i', $username))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Wrong datas for user edition");
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
					$radSQLMgr = new AstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					// For Edition Only, don't delete acct records
					$edit = FS::$secMgr->checkAndSecurisePostData("uedit");
					if($edit == 1) {
						$radSQLMgr->Delete("radcheck","username = '".$username."'");
						$radSQLMgr->Delete("radreply","username = '".$username."'");
						$radSQLMgr->Delete("radusergroup","username = '".$username."'");
						$radSQLMgr->Delete("z_eye_radusers","username = '".$username."'");
					}
					$userexist = $radSQLMgr->GetOneData("radcheck","username","username = '".$username."'");
					if(!$userexist || $edit == 1) {
						if($utype == 1) {
							switch($upwdtype) {
								case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
								case 2: $attr = "User-Password"; $value = $upwd; break;
								case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
								case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
								case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
								case 6: $attr = "CHAP-Password"; $value = $upwd; break;
							}
						}
						else {
							if($utype == 2) {
								if(!FS::$secMgr->isMacAddr($username) && !preg_match('#^[0-9A-F]{12}$#i', $username)) {
									FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Wrong datas for user edition");
									header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
			                        return;
								}
								$username = preg_replace("#[:]#","",$username);
							}
							$attr = "Auth-Type";
							$value = "Accept";
						}
						$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','".$attr."',':=','".$value."'");
						foreach($_POST as $key => $value) {
						if(preg_match("#^ugroup#",$key)) {
								$groupfound = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$value."'");
								if(!$groupfound)
									$groupfound = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$value."'");
								if($groupfound) {
									$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$username."' AND groupname = '".$value."'");
									if(!$usergroup)
										$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$value."','1'");
								}
							}
						}
						$attrTab = array();
						foreach($_POST as $key => $value) {
								if(preg_match("#attrval#",$key)) {
										$key = preg_replace("#attrval#","",$key);
										if(!isset($attrTab[$key])) $attrTab[$key] = array();
										$attrTab[$key]["val"] = $value;
								}
								else if(preg_match("#attrkey#",$key)) {
										$key = preg_replace("#attrkey#","",$key);
										if(!isset($attrTab[$key])) $attrTab[$key] = array();
										$attrTab[$key]["key"] = $value;
								}
								else if(preg_match("#attrop#",$key)) {
										$key = preg_replace("#attrop#","",$key);
										if(!isset($attrTab[$key])) $attrTab[$key] = array();
										$attrTab[$key]["op"] = $value;
								}
								else if(preg_match("#attrtarget#",$key)) {
										$key = preg_replace("#attrtarget#","",$key);
										if(!isset($attrTab[$key])) $attrTab[$key] = array();
										$attrTab[$key]["target"] = $value;
								}
						}
						foreach($attrTab as $attrKey => $attrEntry) {
								if($attrEntry["target"] == "2") {
										$radSQLMgr->Insert("radreply","id,username,attribute,op,value","'','".$username.
										"','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
								}
								else if($attrEntry["target"] == "1") {
										$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username.
										"','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
								}
						}

						$radSQLMgr->Delete("z_eye_radusers","username = '".$username."'");
						$expiretime = FS::$secMgr->checkAndSecurisePostData("expiretime");
						if($expiretime)
							$radSQLMgr->Insert("z_eye_radusers","username,expiration","'".$username."','".date("y-m-d",strtotime($expiretime))."'");
					}
					else {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Try to add user ".$username." but user already exists");
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
                        return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"User '".$username."' edited/created");
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb);
					break;
				case 3: // Group edition
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("groupname");

					if(!$groupname) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for group edition");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					// For Edition Only, don't delete acct/user-group links
					$edit = FS::$secMgr->checkAndSecurisePostData("uedit");
					if($edit == 1) {
						$radSQLMgr->Delete("radgroupcheck","groupname = '".$groupname."'");
						$radSQLMgr->Delete("radgroupreply","groupname = '".$groupname."'");
					}

					$groupexist = $radSQLMgr->GetOneData("radgroupcheck","id","groupname='".$groupname."'");
					if($groupexist && $edit != 1) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Trying to add existing group '".$groupname."'");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
						return;
					}
					$groupexist = $radSQLMgr->GetOneData("radgroupreply","id","groupname='".$groupname."'");
					if($groupexist && $edit != 1) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Trying to add existing group '".$groupname."'");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
						return;
					}
					$attrTab = array();
					foreach($_POST as $key => $value) {
						if(preg_match("#attrval#",$key)) {
							$key = preg_replace("#attrval#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
							$attrTab[$key]["val"] = $value;
						}
						else if(preg_match("#attrkey#",$key)) {
							$key = preg_replace("#attrkey#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["key"] = $value;
						}
						else if(preg_match("#attrop#",$key)) {
							$key = preg_replace("#attrop#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["op"] = $value;
						}
						else if(preg_match("#attrtarget#",$key)) {
							$key = preg_replace("#attrtarget#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["target"] = $value;
						}
					}
					foreach($attrTab as $attrKey => $attrValue) {
						if($attrValue["target"] == "2") {
							$radSQLMgr->Insert("radgroupreply","id,groupname,attribute,op,value","'','".$groupname.
							"','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
						else if($attrValue["target"] == "1") {
							$radSQLMgr->Insert("radgroupcheck","id,groupname,attribute,op,value","'','".$groupname.
                                                        "','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Group '".$groupname."' edited/created");
					header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."");
					break;
				case 4: // user removal
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$username = FS::$secMgr->checkAndSecurisePostData("user");
					$acctdel = FS::$secMgr->checkAndSecurisePostData("acctdel");
					$logdel = FS::$secMgr->checkAndSecurisePostData("logdel");

					if(!$raddb || !$radhost || !$radport || !$username) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing user removal");
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();
					$radSQLMgr->Delete("radcheck","username = '".$username."'");
					$radSQLMgr->Delete("radreply","username = '".$username."'");
					$radSQLMgr->Delete("radusergroup","username = '".$username."'");
					$radSQLMgr->Delete("z_eye_radusers","username ='".$username."'");
					if($logdel == "on") $radSQLMgr->Delete("radpostauth","username = '".$username."'");
					if($acctdel == "on") $radSQLMgr->Delete("radacct","username = '".$username."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"User '".$username."' removed".($logdel == "on" ? " Also remove logs" : "").($acctdel == "on" ? " Also remove acct": ""));
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."");
					return;
				case 5: // group removal
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("group");

					if(!$raddb || !$radhost || !$radport || !$groupname) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for group removal");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();
					$radSQLMgr->Delete("radgroupcheck","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radgroupreply","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radusergroup","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radhuntgroup","groupname = '".$groupname."'");
					FS::$dbMgr->Delete("z_eye_radius_dhcp_import","groupname = '".$groupname."'");
					header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."");
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Group '".$groupname."' removed");
					return;

				case 6:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$utype = FS::$secMgr->checkAndSecurisePostData("usertype");
					$pwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");
					$group = FS::$secMgr->checkAndSecurisePostData("ugroup");
					$userlist = FS::$secMgr->checkAndSecurisePostData("csvlist");

					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some datas are missing for mass import");
							header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
							return;
					}
					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					if(!$utype || $utype != 1 && $utype != 2 || !$userlist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some datas are missing or invalid for mass import");
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					$groupfound = NULL;
					if($group != "none") {
						$groupfound = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$group."'");
						if(!$groupfound)
							$groupfound = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$group."'");
					}
					if($utype == 1) {
						$userlist = str_replace('\r','\n',$userlist);
						$userlist = str_replace('\n\n',"\n",$userlist);
						$userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
						$userlist = array_filter($userlist);
						$fmtuserlist = array();
						$count = count($userlist);
						for($i=0;$i<$count;$i++) {
							$tmp = preg_split("#[,]#",$userlist[$i]);
							if(count($tmp) != 2 || preg_match("#[ ]#",$tmp[0])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some datas are invalid for mass import");
								header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
								return;
							}
							$fmtuserlist[$tmp[0]] = $tmp[1];
						}
						$userfound = 0;
						foreach($fmtuserlist as $user => $upwd) {
							switch($pwdtype) {
								case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
								case 2: $attr = "User-Password"; $value = $upwd; break;
								case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
								case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
								case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
								case 6: $attr = "CHAP-Password"; $value = $upwd; break;
								default:
										FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Bad password type for mass import");
										header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
										return;
							}
							if(!$radSQLMgr->GetOneData("radcheck","username","username = '".$user."'"))
								$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$user."','".$attr."',':=','".$value."'");
							if($groupfound) {
								$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$user."' AND groupname = '".$group."'");
								if(!$usergroup)
									$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$user."','".$group."','1'");
							}
						}
						if($userfound) {
							FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some users are already found for mass import");
                            header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=5");
							return;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Mass import done (list:".$userlist." group: ".$group.")");
					}
                    else if($utype == 2) {
						if(preg_match("#[,]#",$userlist)) {
							header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
							return;
						}
						$userlist = str_replace('\r','\n',$userlist);
						$userlist = str_replace('\n\n',"\n",$userlist);
						$userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
						$userlist = array_filter($userlist);
						// Match & format mac addr
						$count = count($userlist);
						for($i=0;$i<$count;$i++) {
							if(!FS::$secMgr->isMacAddr($userlist[$i]) && !preg_match('#^[0-9A-F]{12}$#i', $userlist[$i]) && !preg_match('#^([0-9A-F]{2}[-]){5}[0-9A-F]{2}$#i', $userlist[$i])) {
								FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Bad fields for Mass import (MAC addr)");
								header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
				                        	return;
							}
							$userlist[$i] = preg_replace("#[:-]#","",$userlist[$i]);
							$userlist[$i] = strtolower($userlist[$i]);
						}
						// Delete duplicate entries
						$userlist = array_unique($userlist);
						$userfound = 0;
						$count = count($userlist);
						for($i=0;$i<$count;$i++) {
							if(!$radSQLMgr->GetOneData("radcheck","username","username = '".$userlist[$i]."'"))
								$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$userlist[$i]."','Auth-Type',':=','Accept'");
							else $userfound = 1;
							if($groupfound) {
								$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$userlist[$i]."' AND groupname = '".$group."'");
								if(!$usergroup)
									$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$userlist[$i]."','".$group."','1'");
							}
						}
						if($userfound) {
							FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Some users already exists for mass import");
							header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=5");
							return;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Mass import done (list:".$userlist." group: ".$group.")");
					}
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=3");
					return;
				case 7: // DHCP sync
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$radgroup = FS::$secMgr->checkAndSecurisePostData("radgroup");
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for DHCP sync");
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
						return;
					}

					if(!$radgroup || !$subnet || !FS::$secMgr->isIP($subnet)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing or invalid for DHCP sync");
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=2");
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					$groupexist = $radSQLMgr->GetOneData("radgroupcheck","id","groupname='".$radgroup."'");
					if(!$groupexist)
						$groupexist = $radSQLMgr->GetOneData("radgroupreply","id","groupname='".$radgroup."'");

					if(!$groupexist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Group '".$radgroup."' doesn't exist, can't bind DHCP to radius");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
						return;
					}

					$subnetexist = FS::$dbMgr->GetOneData("z_eye_dhcp_subnet_cache","netmask","netid = '".$subnet."'");
					if(!$subnetexist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Subnet '".$subnet."' doesn't exist can't bind DHCP to radius");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
                        return;
					}
					if(!FS::$dbMgr->GetOneData("z_eye_radius_dhcp_import","dhcpsubnet","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."' AND dhcpsubnet = '".$subnet."'"))
						FS::$dbMgr->Insert("z_eye_radius_dhcp_import","addr,port,dbname,dhcpsubnet,groupname","'".$radhost."','".$radport."','".$raddb."','".$subnet."','".$radgroup."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"DHCP subnet '".$subnet."' bound to '".$radgroup."'");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4");
					return;
				case 8: // dhcp sync removal
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some required fields are missing for DHCP sync removal");
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
						return;
					}

					if(!$subnet) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"No subnet given to DHCP sync removal");
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=2");
						return;
					}

					FS::$dbMgr->Delete("z_eye_radius_dhcp_import","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."' AND dhcpsubnet = '".$subnet."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Remove sync between subnet '".$subnet."' and radius");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4");
					return;
				case 9: // radius cleanup table
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for radius cleanup table (radius server)");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					$cleanradenable = FS::$secMgr->checkAndSecurisePostData("cleanradsqlenable");
					$cleanradtable = FS::$secMgr->checkAndSecurisePostData("cleanradsqltable");
					$cleanradsqluserfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqluserfield");
					$cleanradsqlexpfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqlexpfield");

					if($cleanradenable == "on" && (!$cleanradtable || !$cleanradsqluserfield || !$cleanradsqlexpfield)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for radius cleanup table (data fields)");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
                        			return;
					}

					if($radSQLMgr->Count($cleanradtable,$cleanradsqluserfield) == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"Some fields are wrong for radius cleanup table");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
						return;
					}

					FS::$dbMgr->Delete("z_eye_radius_options","addr = '".$radhost."' AND port = '".$radport."' and dbname = '".$raddb."'");
					FS::$dbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_enable','".($cleanradenable == "on" ? 1 : 0)."'");
					FS::$dbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_table','".$cleanradtable."'");
					FS::$dbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_user_field','".$cleanradsqluserfield."'");
					FS::$dbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_date_field','".$cleanradsqlexpfield."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Data Creation/Edition for radius cleanup table done (table: '".$cleanradtable."' userfield: '".$cleanradsqluserfield."' date field: '".$cleanradsqlexpfield."'");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5");
					return;
				case 10: // account creation (deleg)
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$name = FS::$secMgr->checkAndSecurisePostData("radname");
					$surname = FS::$secMgr->checkAndSecurisePostData("radsurname");
					$username = FS::$secMgr->checkAndSecurisePostData("radusername");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure");
					$limme = FS::$secMgr->checkAndSecurisePostData("limmine");

					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for radius deleg (radius server)");
						echo FS::$iMgr->printError($this->loc->s("err-invalid-auth-server"));
						//header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
						return;
					}
					if(!$name || !$surname || !$username || !$valid || !$profil || ($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 
						|| !FS::$secMgr->isNumeric($limhs) || !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) || !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate) 
					))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for radius deleg (datas)");
						echo FS::$iMgr->printError($this->loc->s("err-field-missing"));
						//header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
                    $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if(strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError($this->loc->s("err-end-before-start"));
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					$exist = $radSQLMgr->GetOneData("radcheck","id","username = '".$username."'");
					if(!$exist) $exist = $radSQLMgr->GetOneData("radreply","id","username = '".$username."'");
					if($exist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",1,"User '".$username."' already exists (Deleg)");
						echo FS::$iMgr->printError($this->loc->s("err-no-user"));
						//header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
                        return;
					}

					$password = FS::$secMgr->genRandStr(8);
					$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','Cleartext-Password',':=','".$password."'");
					$radSQLMgr->Insert("z_eye_radusers","username,expiration,name,surname,startdate,creator,creadate","'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
					$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$profil."',0");

					FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Creating delegated user '".$username."' with password '".$password."'. Account expiration: ".($valid == 2 ? $edate: "none"));
					echo FS::$iMgr->printDebug($this->loc->s("ok-user"))."<br /><hr><b>".$this->loc->s("User").": </b>".$username."<br /><b>".$this->loc->s("Password").": </b>".$password."<br /><b>".$this->loc->s("Validity").": </b>".
						($valid == 2 ? $this->loc->s("From")." ".$sdate." ".$this->loc->s("To")." ".$edate : $this->loc->s("Infinite"))."<hr><br />";
					return;
				case 11: // Rad deleg (massive)
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$typegen = FS::$secMgr->checkAndSecurisePostData("typegen");
					$prefix = FS::$secMgr->checkAndSecurisePostData("prefix");
					$nbacct = FS::$secMgr->checkAndSecurisePostData("nbacct");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil2");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity2");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate2");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate2");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours2");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins2");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure2");
                                        $limme = FS::$secMgr->checkAndSecurisePostData("limmine2");
					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for massive creation (Deleg) (radius server)");
						echo FS::$iMgr->printError($this->loc->s("err-invalid-auth-server"));
						//header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
						return;
					}

					if(!$typegen || $typegen == 2 && !$prefix || !$nbacct || !$valid || !$profil || ($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 
						|| !FS::$secMgr->isNumeric($nbacct) || $nbacct <= 0 || !FS::$secMgr->isNumeric($limhs) || !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) || !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate) 
					))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing or invalid for massive creation (Deleg) (datas)");
						echo FS::$iMgr->printError($this->loc->s("err-field-missing"));
							//header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
							return;
					}
					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
                    $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if(strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError($this->loc->s("err-end-before-start"));
						return;
					}

					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig("my",$raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();

					$pdf = new PDFgen();
					$pdf->SetTitle($this->loc->s("Account").": ".($valid == 1 ? $this->loc->s("Permanent") : $this->loc->s("Temporary")));
					for($i=0;$i<$nbacct;$i++) {
						$password = FS::$secMgr->genRandStr(8);
						if($typegen == 2) $username = $prefix.($i+1);
						else $username = FS::$secMgr->genRandStr(12);
						$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','Cleartext-Password',':=','".$password."'");
						$radSQLMgr->Insert("z_eye_radusers","username,expiration,name,surname,startdate,creator,creadate","'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
						$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$profil."',0");
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",0,"Create user '".$username."' with password '".$password."' for massive creation (Deleg)");
						$pdf->AddPage();
						$pdf->WriteHTML(utf8_decode("<b>".$this->loc->s("User").": </b>".$username."<br /><br /><b>".$this->loc->s("Password").": </b>".$password."<br /><br /><b>".
						$this->loc->s("Validity").": </b>".($valid == 2 ? $this->loc->s("From")." ".date("d/m/y H:i",strtotime($sdate))." ".$this->loc->s("To")." ".
						date("d/m/y H:i",strtotime($edate)) : $this->loc->s("Infinite"))));
					}
					$pdf->CleanOutput();
					return;
				case 12:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					if(!$raddb || !$radhost || !$radport) {
						FS::$log->i(FS::$sessMgr->getUserName(),"radius",2,"Some fields are missing for radius filter entries (radius server)");
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
						return;
					}
					$radlogin = FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radpwd = FS::$dbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
					$radSQLMgr = new AbstractSQLMgr();
					if($radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd) == 0)
						$radSQLMgr->Connect();
					echo $this->showRadiusDatas($radSQLMgr,$raddb,$radhost,$radport);
					return;
			}
			
		}
	};
?>
