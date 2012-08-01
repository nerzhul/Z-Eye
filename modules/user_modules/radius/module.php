<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iRadius extends genModule{
		function iRadius() { parent::genModule(); }
		public function Load() {
			$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
			$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
			$radport = FS::$secMgr->checkAndSecuriseGetData("p");
			$rad = $raddb."@".$radhost.":".$radport;
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err && FS::$secMgr->isNumeric($err)) {
				switch($err) {
					case 1: $output = FS::$iMgr->printError("Serveur radius non référencé !"); break;
					case 2: $output = FS::$iMgr->printError("Les valeurs entrées ne sont pas valides !"); break;
					case 3: $output = FS::$iMgr->printError("Le groupe/utilisateur inscrit est déjà référencé !"); break;
					case 4: $output = FS::$iMgr->printError("Echec de la suppression, données invalides !"); break;
				}
			}
			else
				$output = "";
			if(!FS::isAjaxCall()) {
				$found = 0;
				$output .= "<div draggable=\"true\" id=\"trash\">".FS::$iMgr->addImage("http://findicons.com/files//icons/1580/devine_icons_part_2/128/trash_recyclebin_empty_closed_w.png",96,96)."</div>";
				$output .= "<h4>Gestion des utilisateurs/Groupes Radius</h4>";
				$tmpoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1").FS::$iMgr->addList("radius","submit()");
				$query = FS::$pgdbMgr->Select("z_eye_radius_db_list","addr,port,dbname,login,pwd");
                	        while($data = pg_fetch_array($query)) {
					if($found == 0) $found = 1;
					$radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
					$tmpoutput .= FS::$iMgr->addElementToList($radpath,$radpath,$rad == $radpath);
				}
				if($found) $output .= $tmpoutput."</select>".FS::$iMgr->addSubmit("","Administrer")."</form>";
				else $output .= FS::$iMgr->printError("Aucun serveur radius référencé");
			}
			if($raddb && $radhost && $radport) {
				$output .= $this->showRadiusDatas($raddb,$radhost,$radport);
			}
			return $output;
		}

		public function showRadiusDatas($raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&r=".$raddb."&h=".$radhost."&p=".$radport."\">Utilisateurs</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&r=".$raddb."&h=".$radhost."&p=".$radport."&sh=2\">Profils</a>";
	
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
			}
			else if(!$sh || $sh == 1) {
                                $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();
				$grouplist= FS::$iMgr->addElementToList("","none");
                                $groups=array();
                                $query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        if(!in_array($data["groupname"])) array_push($groups,$data["groupname"]);
                                }
                                $query = $radSQLMgr->Select("radgroupreply","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        if(!in_array($data["groupname"])) array_push($groups,$data["groupname"]);
                                }
                                for($i=0;$i<count($groups);$i++) {
                                        $grouplist .= FS::$iMgr->addElementToList($groups[$i],$groups[$i]);
                                }
				$formoutput = "<script type=\"text/javascript\"> function changeUForm() {
					if(document.getElementsByName('utype')[0].value == 1) {
						$('#macdf').hide(); $('#pindf').hide(); $('#userdf').show();
					}
					else if(document.getElementsByName('utype')[0].value == 2) {
						$('#macdf').show(); $('#pindf').hide(); $('#userdf').hide();
					}
					else if(document.getElementsByName('utype')[0].value == 3) {
						$('#macdf').hide(); $('#pindf').show(); $('#userdf').hide();
					}
				}; grpidx = 0; function addGrpForm() {
					$('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->addList("ugroup'+grpidx+'","","Profil").$grouplist."</select> <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertAfter('#upwdtype');
					grpidx++;
				}
				function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }
				</script>";

				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=2");
				$formoutput .= "<ul style=\"list-style-type:none;\"><li>".
				FS::$iMgr->addList("utype","changeUForm()","Type d'authentification");
				$formoutput .= FS::$iMgr->addElementToList("Utilisateur",1);
				$formoutput .= FS::$iMgr->addElementToList("Adresse MAC",2);
				$formoutput .= FS::$iMgr->addElementToList("Code PIN",3);
				$formoutput .= "</select></li><li>".
				FS::$iMgr->addInput("username","",20,40,"Utilisateur")."</li><li>";
				$formoutput .= "<fieldset id=\"userdf\" style=\"border:0;\">".
				FS::$iMgr->addPasswdField("pwd","","Mot de passe")."<br />".
				FS::$iMgr->addList("upwdtype","","Type de mot de passe").
				FS::$iMgr->addElementToList("Cleartext-Password",1).
				FS::$iMgr->addElementToList("User-Password",2).
				FS::$iMgr->addElementToList("Crypt-Password",3).
				FS::$iMgr->addElementToList("MD5-Password",4).
				FS::$iMgr->addElementToList("SHA1-Password",5).
				FS::$iMgr->addElementToList("CHAP-Password",6).
				"</select></fieldset></li><li>".FS::$iMgr->addButton("newgrp","Nouveau Groupe","addGrpForm()").
				FS::$iMgr->addSubmit("","Enregistrer")."</form></li></ul>";
				$output .= FS::$iMgr->addOpenableDiv($formoutput,"Nouvel Utilisateur");
				$found = 0;
				$tmpoutput = "<h4>Liste des Utilisateurs</h4>";
				$tmpoutput .= "<script type=\"text/javascript\">
				$.event.props.push('dataTransfer');
				$('#raduser td').on({
					dragstart: function(e) { e.dataTransfer.setData('text/html', $(this).text()); },
					dragenter: function(e) { $('#trash').show(); e.preventDefault();},
					dragover: function(e) { e.preventDefault(); },
					dragleave: function(e) { },
					drop: function(e) {},
					dragend: function() { $('#trash').hide(); }
				});
				$('#trash').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer l\'utilisateur \''+e.dataTransfer.getData('text/html')+'\' ?".
						FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=4").
						FS::$iMgr->addHidden("user","'+e.dataTransfer.getData('text/html')+'").
						FS::$iMgr->addCheck("logdel",false,"Supprimer les logs ?")."<br />".
						FS::$iMgr->addCheck("acctdel",false,"Supprimer l\\'accounting ?")."<br />".
						FS::$iMgr->addSubmit("","Supprimer").
						FS::$iMgr->addButton("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
						$('#pop').show();
					}
				});
				</script>";
                                $query = $radSQLMgr->Select("radcheck","id,username,value","attribute IN ('Auth-Type','Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')");
				while($data = mysql_fetch_array($query)) {
					if(!$found) {
                                                $found = 1;
                                                $tmpoutput .= "<table id=\"raduser\" style=\"width:70%\"><tr><th>Id</th><th>Utilisateur</th><th>Mot de passe</th><th>Groupes</th></tr>";
                                        }
                                        $tmpoutput .= "<tr><td>".$data["id"]."</td><td draggable=true>".$data["username"]."</td><td>".$data["value"]."</td><td>";
					$query2 = $radSQLMgr->Select("radusergroup","groupname","username = '".$data["username"]."'");
					$found2 = 0;
					while($data2 = mysql_fetch_array($query2)) {
						if($found2 == 0) $found2 = 1;
						else $tmpoutput .= "<br />";
						$tmpoutput .= $data2["groupname"];
					}
                                        $tmpoutput .= "</td></tr>";
				}
				if($found) $output .= $tmpoutput."</table>";
			}
			else if($sh == 2) {
				$formoutput = "<script type=\"text/javascript\">attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->addInput("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." ".
				FS::$iMgr->addInput("attrval'+attridx+'","'+attrval+'",10,40,"Valeur")." Op ".FS::$iMgr->addList("attrop'+attridx+'").
				FS::$iMgr->addElementToList("=","=").
				FS::$iMgr->addElementToList("==","==").
				FS::$iMgr->addElementToList(":=",":=").
				FS::$iMgr->addElementToList("+=","+=").
				FS::$iMgr->addElementToList("!=","!=").
                                FS::$iMgr->addElementToList(">",">").
                                FS::$iMgr->addElementToList(">=",">=").
                                FS::$iMgr->addElementToList("<","<").
				FS::$iMgr->addElementToList("<=","<=").
                                FS::$iMgr->addElementToList("=~","=~").
                                FS::$iMgr->addElementToList("!~","!~").
                                FS::$iMgr->addElementToList("=*","=*").
				FS::$iMgr->addElementToList("!*","!*").
				"</select> Cible ".FS::$iMgr->addList("attrtarget'+attridx+'").
				FS::$iMgr->addElementToList("check",1).
				FS::$iMgr->addElementToList("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertAfter('#groupname');
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
                                $('#radgrp td').on({
                                        dragstart: function(e) { e.dataTransfer.setData('text/html', $(this).text()); },
                                        dragenter: function(e) { $('#trash').show(); e.preventDefault();},
                                        dragover: function(e) { e.preventDefault(); },
                                        dragleave: function(e) { },
                                        drop: function(e) {},
                                        dragend: function() { $('#trash').hide(); }
                                });
				$('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer le profil \''+e.dataTransfer.getData('text/html')+'\' ?".
						FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=5").
                                                FS::$iMgr->addHidden("group","'+e.dataTransfer.getData('text/html')+'").
                                                FS::$iMgr->addSubmit("","Supprimer").
                                                FS::$iMgr->addButton("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                                $('#pop').show();
                                        }
                                });</script>";
				$formoutput .= FS::$iMgr->addList("radgrptpl","addTemplAttributes()","Template");
				$formoutput .= FS::$iMgr->addElementToList("Aucun",0);
				$formoutput .= FS::$iMgr->addElementToList("VLAN",1);
				$formoutput .= "</select>";
				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=3").
				"<ul style=\"list-style-type:none;\"><li>".
				FS::$iMgr->addInput("groupname","",20,40,"Nom du profil")."</li><li>".
				FS::$iMgr->addButton("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->addSubmit("","Enregistrer").
				"</li></ul></form>";
				$output .= FS::$iMgr->addOpenableDiv($formoutput,"Nouveau profil");
				$tmpoutput = "<h4>Liste des profils</h4>";
				$found = 0;
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$groups=array();
				$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
				while($data = mysql_fetch_array($query)) {
					$rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
					if(!isset($groups[$data["groupname"]]))
						$groups[$data["groupname"]] = $rcount;
				}

				$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        $rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
                                        if(!isset($groups[$data["groupname"]]))
                                                $groups[$data["groupname"]] = $rcount;
					else
						$groups[$data["groupname"]] += $rcount;
                                }
				if(count($groups) > 0) {
					$output .= "<table id=\"radgrp\" style=\"width:30%;\"><tr><th>Groupe</th><th style=\"width:30%\">Nombre d'utilisateurs</th></tr>";
					foreach($groups as $key => $value)
						$output .= "<tr><td draggable=true>".$key."</td><td>".$value."</td></tr>";
					$output .= "</table>";
				}
			}
			else {
				$output .= FS::$iMgr->printError("Cet onglet n'existe pas !");
			}
			return $output;
		}

		public function showUserAddForm($raddb,$radhost,$radport) {
			$output = "";
			return $output;
		}

		public function showGroupAddForm($raddb,$radhost,$radport) {
			$output = "";
			return $output;
		}

		public function handlePostDatas($act) {
                        switch($act) {
				case 1:
					$rad = FS::$secMgr->checkAndSecurisePostData("radius");
					if(!$rad) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$radcut1 = preg_split("[@]",$rad);
					if(count($radcut1) != 2) {
						header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
					}
					$radcut2 = preg_split("[:]",$radcut1[1]);
					if(count($radcut2) != 2) {
                                                header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
                                        }
					header("Location: index.php?mod=".$this->mid."&h=".$radcut2[0]."&p=".$radcut2[1]."&r=".$radcut1[0]);
					return;
				case 2:
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
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					// if type 2: must be a mac addr
					if($utype == 2 && !FS::$secMgr->isMacAddr($username)) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

                                	$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                               		$radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                	$radSQLMgr = new FSMySQLMgr();
                                	$radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                	$radSQLMgr->Connect();
					$groupfound = 0;
					$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
                                	while($data = mysql_fetch_array($query)) {
						if($data["groupname"] == $ugroup) {
							$groupfound = 1;
							break;
						}
        	                        }
					$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                                        while($data = mysql_fetch_array($query)) {
                                                if($data["groupname"] == $ugroup) {
                                                        $groupfound = 1;
                                                        break;
                                                }
                                        }

					$userexist = $radSQLMgr->GetOneData("radcheck","username","username = '".$username."'");
					if(!$userexist) {
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
								if(!FS::$secMgr->isMacAddr($username)) {
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
			                                        $groupfound = $radSQLMgr->Select("radgroupreply","distinct groupname","groupname = '".$value."'");
								if(!$groupfound)
	                        			                $groupfound = $radSQLMgr->Select("radgroupcheck","distinct groupname","groupname = '".$value."'");
								if($groupfound) {
									$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$username."' AND groupname = '".$value."'");
									if(!$usergroup)
										$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$value."','1'");
								}
							}
						}
					}
					else {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
                                                return;
					}
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb);
					break;
				case 3:
                                        $raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("groupname");

					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					$groupexist = $radSQLMgr->GetOneData("radgroupcheck","id","groupname='".$groupname."'");
					if($groupexist) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
						return;
					}
					$groupexist = $radSQLMgr->GetOneData("radgroupreply","id","groupname='".$groupname."'");
					if($groupexist) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
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
					for($i=0;$i<count($attrTab);$i++) {
						if($attrTab[$i]["target"] == "2") {
							$radSQLMgr->Insert("radgroupreply","id,groupname,attribute,op,value","'','".$groupname.
							"','".$attrTab[$i]["key"]."','".$attrTab[$i]["op"]."','".$attrTab[$i]["val"]."'");
						}
						else if($attrTab[$i]["target"] == "1") {
							$radSQLMgr->Insert("radgroupcheck","id,groupname,attribute,op,value","'','".$groupname.
                                                        "','".$attrTab[$i]["key"]."','".$attrTab[$i]["op"]."','".$attrTab[$i]["val"]."'");
						}
					}

					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."");
					break;
				case 4:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$username = FS::$secMgr->checkAndSecurisePostData("user");
					$acctdel = FS::$secMgr->checkAndSecurisePostData("acctdel");
					$logdel = FS::$secMgr->checkAndSecurisePostData("logdel");

					if(!$raddb || !$radhost || !$radport || !$username) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();
					$radSQLMgr->Delete("radcheck","username = '".$username."'");
					$radSQLMgr->Delete("radreply","username = '".$username."'");
					$radSQLMgr->Delete("radusergroup","username = '".$username."'");
					if($logdel == "on") $radSQLMgr->Delete("radpostauth","username = '".$username."'");
					if($acctdel == "on") $radSQLMgr->Delete("radacct","username = '".$username."'");
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."");
					return;
				case 5:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("group");

					if(!$raddb || !$radhost || !$radport || !$groupname) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();
					$radSQLMgr->Delete("radgroupcheck","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radgroupreply","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radusergroup","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radhuntgroup","groupname = '".$groupname."'");
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."");
					return;
			}
		}
	};
?>
