<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	class iNagios extends genModule{
		function iNagios() { parent::genModule(); }
		public function Load() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) $output .= "<h3>Management de Nagios (icinga)</h3>";
			
			$output .= $this->showMain();
			return $output;
		}
		
		private function showMain() {			
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&d=".$device."&p=".$port."\">Général</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=2\">Contacts (tpl)</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=3\">Hôtes (tpl)</a>";
				$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=4\">Services (tpl)</a>";
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
				$output .= "</div>";
			}
			else if(!$sh || $sh == 1) {
				$stupdateint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'STATUS_UPDATE_INT'");
				$cmdcheckint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'CMD_CHECK_INT'");
				$resfreq = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'RESULT_UPDATE_INT'");
				$maxresultage = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'RESULT_MAX_AGE'");
				$cachehostlifetime = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'HOST_CACHE_LIFETIME'");
				$cachesrvlifetime = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SERVICE_CACHE_LIFETIME'");
				$srvchecktimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SRV_STATE_TIMEOUT'");
				$hostchecktimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'HOST_STATE_TIMEOUT'");
				$notiftimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'NOTIF_TIMEOUT'");
				$sensorint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SENSOR_TIME'");
				$lognotif = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_NOTIF'");
				$logsrvretries = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_SRV_RETRIES'");
				$loghostretries = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_HOST_RETRIES'");
				$notifenable = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ENABLE_NOTIF'");
				$flapenable = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ENABLE_FLAG'");
				$adminmail = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ADMIN_MAIL'");
			
				$output .= "<h3>Configuration globale</h3>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
				$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">Délais</th></tr>";
				$output .= FS::$iMgr->addIndexedLine("Intervalle de mise à jour des status","stupdateint",$stupdateint);
				$output .= FS::$iMgr->addIndexedLine("Intervalle de vérification des commandes externes","cmdcheckint",$cmdcheckint);
				$output .= FS::$iMgr->addIndexedLine("Intervalle de mise à jour des résultats des hôtes et services","resfreq",$resfreq);
				$output .= FS::$iMgr->addIndexedLine("Age maximal des résultats","maxresultage",$maxresultage);
				$output .= FS::$iMgr->addIndexedLine("Age maximal de l'état d'un hôte","cachehostlifetime",$cachehostlifetime);
				$output .= FS::$iMgr->addIndexedLine("Age maximal de l'état d'un service","cachesrvlifetime",$cachesrvlifetime);
				$output .= FS::$iMgr->addIndexedLine("Temps maximal de recherche d'état d'un service","srvchecktimeout",$srvchecktimeout);
				$output .= FS::$iMgr->addIndexedLine("Temps maximal de recherche d'état d'un hôte","hostchecktimeout",$hostchecktimeout);
				$output .= FS::$iMgr->addIndexedLine("Temps maximal d'envoi d'une notification","notiftimeout",$notiftimeout);
				$output .= FS::$iMgr->addIndexedLine("Intervalle par défaut d'utilisation d'une sonde","sensorint",$sensorint);
				$output .= "<tr><th colspan=\"2\">Logs</th></tr>";
				// Log rotation method
				$output .= FS::$iMgr->addIndexedCheckLine("Log des notifications","lognotif",$lognotif);
				$output .= FS::$iMgr->addIndexedCheckLine("Log des tentatives sur les services","logsrvretries",$logsrvretries);
				$output .= FS::$iMgr->addIndexedCheckLine("Log des tentatives sur les hôtes","loghostretries",$loghostretries);
				$output .= "<tr><th colspan=\"2\">Notifications</th></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Activer","notifenable",$notifenable);
				// service_check_timeout_state
				$output .= "<tr><th colspan=\"2\">Autres</th></tr>";
				$output .= FS::$iMgr->addIndexedCheckLine("Détection des services instables","flapenable",$flapenable);
				// date_format
				$output .= FS::$iMgr->addIndexedCheckLine("Mail de l'administrateur","adminmail",$adminmail);
				$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
				$output .= "</table></form>";
			}
			else if($sh == 2) {
				$output .= "<h3>Template de contacts</h3>";
				$output .= "<h4>Nouveau template</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
				$output .= "<table class=\"standardTable\">";
				$output .= FS::$iMgr->addIndexedLine("Nom du template","");
				$output .= "<tr><td>Période des notifications (Hôte)</td><td>";
				$output .= FS::$iMgr->addList("notifhost");
				$output .= FS::$iMgr->addElementToList("24x7",1,true);
				$output .= FS::$iMgr->addElementToList("Heures travaillées",2);
				$output .= "</select></td></tr><tr><td>Période des notifications (Services)</td><td>";
				$output .= FS::$iMgr->addList("notifsrv");
				$output .= FS::$iMgr->addElementToList("24x7",1,true);
				$output .= FS::$iMgr->addElementToList("Heures travaillées",2);
				$output .= "</select></td></tr>";
				$output .= "<tr><td>Activer les notifications hôte: </td><td>";
				$output .= FS::$iMgr->addIndexedCheckLine("Down","hostdown",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Avertissements","hostwarn",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Statut Inconnu","hostunk",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Critique","hostcrit",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Instabilité","hostunstable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Retour à la normale","hostrecovery",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Désactiver les notifications","hostnotifdisable",false);
				$output .= "</td></tr><tr><td>Options des notifications (Services)</td><td>";
				$output .= FS::$iMgr->addIndexedCheckLine("Down","srvdown",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Avertissements","srvwarn",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Statut Inconnu","srvunk",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Critique","srvcrit",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Instabilité","srvunstable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Retour à la normale","srvrecovery",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Désactiver les notifications","srvnotifdisable",false);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->addTableSubmit("submit","Ajouter");
				$output .= "</table></form>";
				$output .= "<h4>Liste des templates</h4>";
			} else if($sh == 3) {
				$output .= "<h3>Template d'hôtes</h3>";
				$output .= "<h4>Nouveau template</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=3");
				$output .= "<table class=\"standardTable\">";
				$output .= FS::$iMgr->addIndexedLine("Nom du template","");
				$output .= FS::$iMgr->addIndexedCheckLine("Notifications","notifenable",true);
				$output .= "<tr><td>Période des notifications</td><td>";
				$output .= FS::$iMgr->addList("notifperiod");
				$output .= FS::$iMgr->addElementToList("24x7",1,true);
				$output .= FS::$iMgr->addElementToList("Heures travaillées",2);
				$output .= "</select>";
				$output .= FS::$iMgr->addIndexedCheckLine("Gestionnaire d'évènements","eventhdlenable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Alertes d'instabilité","flapenable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Prédiction d'échecs","failpredictenable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Effectuer les tests de performances","perfdata",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Retenir les informations (statut)","retainstatus",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Retenir les informations (!statut)","retainnonstatus",true);
				$output .= FS::$iMgr->addIndexedLine("Intervalle de notification","notifinterval",true);
				$output .= "<tr><td>Options des notifications</td><td>";
				$output .= FS::$iMgr->addIndexedCheckLine("Down","hostdown",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Avertissements","hostwarn",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Statut Inconnu","hostunk",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Critique","hostcrit",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Instabilité","hostunstable",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Retour à la normale","hostrecovery",true);
				$output .= FS::$iMgr->addIndexedCheckLine("Désactiver les notifications","hostnotifdisable",false);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->addTableSubmit("submit","Ajouter");
				$output .= "</table></form>";
				$output .= "<h4>Liste des templates</h4>";
			} else if($sh == 4) {
				$output .= "<h3>Template de services</h3>";
				$output .= "<h4>Nouveau template</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=4");
				$output .= FS::$iMgr->addIndexedLine("Nom du template","");
				$output .= "</form>";
				$output .= "<h4>Liste des templates</h4>";
			}
			return $output;	
		}
		
		private function checkIcingaMainConfig() {
			
			return true;	
		}
		
		private function writeIcingaMainConfig() {
			$file = fopen("/usr/local/etc/icinga/icinga.cfg","w+");
			if($file == NULL || $file == false)
				return 1;
				
			$stupdateint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'STATUS_UPDATE_INT'");
			$cmdcheckint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'CMD_CHECK_INT'");
			$resfreq = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'RESULT_UPDATE_INT'");
			$maxresultage = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'RESULT_MAX_AGE'");
			$cachehostlifetime = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'HOST_CACHE_LIFETIME'");
			$cachesrvlifetime = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SERVICE_CACHE_LIFETIME'");
			$srvchecktimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SRV_STATE_TIMEOUT'");
			$hostchecktimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'HOST_STATE_TIMEOUT'");
			$notiftimeout = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'NOTIF_TIMEOUT'");
			//$sensorint = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'SENSOR_TIME'");
			$lognotif = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_NOTIF'");
			$logsrvretries = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_SRV_RETRIES'");
			$loghostretries = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'LOG_HOST_RETRIES'");
			$notifenable = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ENABLE_NOTIF'");
			$flapenable = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ENABLE_FLAG'");
			$adminmail = FS::$dbMgr->GetOneData("fss_icinga_main","icingavalue","icingakey = 'ADMIN_MAIL'");
				
			fwrite($file,"# ---- Logs ----\n");
			fwrite($file,"log_file=/var/log/icinga.log\n");
			fwrite($file,"# ---- Configuration directory ----\n");
			fwrite($file,"cfg_dir=/usr/local/etc/icinga/objects\n");
			fwrite($file,"# ---- Configuration module ----\n");
			fwrite($file,"cfg_dir=/usr/local/etc/icinga/modules\n");
			fwrite($file,"# ---- Misc ----\n");
			fwrite($file,"object_cache_file=/var/spool/icinga/objects.cache\n");
			fwrite($file,"precached_object_file=/var/spool/icinga/objects.precache\n");
			fwrite($file,"resource_file=/usr/local/etc/icinga/resource.cfg\n");
			fwrite($file,"status_file=/var/spool/icinga/status.dat\n");
			fwrite($file,"status_update_interval=".$stupdateint."\n");
			fwrite($file,"icinga_user=icinga\n");
			fwrite($file,"icinga_group=icinga\n");
			fwrite($file,"check_external_commands=1\n");
			fwrite($file,"command_check_interval=".$cmdcheckint."s\n");
			fwrite($file,"command_file=/var/spool/icinga/rw/icinga.cmd\n");
			fwrite($file,"external_command_buffer_slots=10240\n");
			fwrite($file,"lock_file=/var/spool/icinga/icinga.lock\n");
			fwrite($file,"temp_file=/var/spool/icinga/icinga.tmp\n");
			fwrite($file,"temp_path=/tmp\n");
			fwrite($file,"event_broker_options=-1\n");
			//fwrite($file,"log_rotation_method=".$logrotate."\n");
			fwrite($file,"log_archive_path=/var/spool/icinga/archives\n");
			fwrite($file,"use_daemon_log=1\n");
			fwrite($file,"use_syslog=1\n");
			fwrite($file,"use_syslog_local_facility=0\n");
			fwrite($file,"syslog_local_facility=5\n");
			fwrite($file,"log_notifications=".$lognotif."\n");
			fwrite($file,"log_service_retries=".$logsrvretries."\n");
			fwrite($file,"log_host_retries=".$loghretries."\n");
			fwrite($file,"log_event_handlers=1\n");
			fwrite($file,"log_initial_states=0\n");
			fwrite($file,"log_current_states=1\n");
			fwrite($file,"log_external_commands=1\n");
			fwrite($file,"log_passive_checks=1\n");
			fwrite($file,"service_inter_check_delay_method=s\n");
			fwrite($file,"max_service_check_spread=30\n");
			fwrite($file,"service_interleave_factor=s\n");
			fwrite($file,"host_inter_check_delay_method=s\n");
			fwrite($file,"max_host_check_spread=30\n");
			fwrite($file,"max_concurrent_checks=0\n");
			fwrite($file,"check_result_reaper_frequency=".$resfreq."\n");
			fwrite($file,"max_check_result_reaper_time=30\n");
			fwrite($file,"check_result_path=/var/spool/icinga/checkresults\n");
			fwrite($file,"max_check_result_file_age=".$maxresultage."\n");
			fwrite($file,"cached_host_check_horizon=".$cachehostlifetime."\n");
			fwrite($file,"cached_service_check_horizon=".$cachesrvlifetime."\n");
			fwrite($file,"enable_predictive_host_dependency_checks=1\n");
			fwrite($file,"enable_predictive_service_dependency_checks=1\n");
			fwrite($file,"soft_state_dependencies=0\n");
			fwrite($file,"time_change_threshold=900\n");
			fwrite($file,"auto_reschedule_checks=0\n");
			fwrite($file,"auto_rescheduling_interval=30\n");
			fwrite($file,"auto_rescheduling_window=180\n");
			fwrite($file,"sleep_time=0.25\n");
			fwrite($file,"# ---- Timeouts ----\n");
			fwrite($file,"service_check_timeout=".$srvchecktimeout."\n");
			fwrite($file,"host_check_timeout=".$hostchecktimeout."\n");
			fwrite($file,"notification_timeout=".$notiftimeout."\n");
			fwrite($file,"event_handler_timeout=30\n");
			fwrite($file,"ocsp_timeout=5\n");
			fwrite($file,"perfdata_timeout=5\n");
			fwrite($file,"retain_state_information=1\n");
			fwrite($file,"state_retention_file=/var/spool/icinga/rentention.dat\n");
			fwrite($file,"sync_retention_file=/var/spool/icinga/sync.dat\n");
			fwrite($file,"retention_update_interval=60\n");
			fwrite($file,"use_retain_program_state=1\n");
			fwrite($file,"dump_retained_host_service_states_to_neb=1\n");
			fwrite($file,"use_retained_scheduling_info=1\n");
			fwrite($file,"retained_host_attribute_mask=0\n");
			fwrite($file,"retained_service_attribute_mask=0\n");
			fwrite($file,"retained_process_host_attribute_mask=0\n");
			fwrite($file,"retained_process_service_attribute_mask=0\n");
			fwrite($file,"retained_contact_host_attribute_mask=0\n");
			fwrite($file,"retained_contact_service_attribute_mask=0\n");
			//fwrite($file,"interval_length=".$interval."\n");
			fwrite($file,"use_aggressive_host_checking=0\n");
			fwrite($file,"execute_service_checks=1\n");
			fwrite($file,"accept_passive_service_checks=1\n");
			fwrite($file,"execute_host_checks=1\n");
			fwrite($file,"accept_passive_host_checks=1\n");
			fwrite($file,"enable_notifications=".$notifenable."\n");
			fwrite($file,"enable_event_handlers=1\n");
			fwrite($file,"process_performance_data=0\n");
			fwrite($file,"obsess_over_services=0\n");
			fwrite($file,"obsess_over_hosts=0\n");
			fwrite($file,"translate_passive_host_checks=0\n");
			fwrite($file,"passive_host_checks_are_soft=0\n");
			fwrite($file,"check_for_orphaned_services=1\n");
			fwrite($file,"check_for_orphaned_hosts=1\n");
			///write($file,"service_check_timeout_state=".$timeoutstate."\n");
			fwrite($file,"check_service_freshness=1\n");
			fwrite($file,"service_freshness_check_interval=60\n");
			fwrite($file,"check_host_freshness=1\n");
			fwrite($file,"host_freshness_check_interval=60\n");
			fwrite($file,"additional_freshness_latency=15\n");
			fwrite($file,"enable_flap_detection=".$flapenable."\n");
			fwrite($file,"low_service_flap_threshold=5.0\n");
			fwrite($file,"high_service_flag_threshold=20.0\n");
			fwrite($file,"low_host_flap_threshold=5.0\n");
			fwrite($file,"high_host_flap_threshold=20.0\n");
			//fwrite($file,"date_format.".$dateformat."\n");
			fwrite($file,"pl_file=/usr/local/lib/pl.pl\n");
			fwrite($file,"enable_embedded_perl=0\n");
			fwrite($file,"use_embedded_perl_implicitly=1\n");
			fwrite($file,"stalking_event_handlers_for_hosts=0\n");
			fwrite($file,"stalking_event_handlers_for_services=0\n");
			fwrite($file,"stalking_notifications_for_hosts=0\n");
			fwrite($file,"stalking_notifications_for_services=0\n");
			fwrite($file,"illegal_object_name_chars=`~!$%^&*|'\"<>?;()=\n");
			fwrite($file,"illegal_macro_output_chars=`~$&|'\"<>\n");
			fwrite($file,"use_regexp_matching=0\n");
			fwrite($file,"use_true_regexp_matching=0\n");
			fwrite($file,"admin_email=".$adminemail."\n");
			//fwrite($file,"admin_pager=".$adminemail."\n");
			fwrite($file,"daemon_dumps_core=0\n");
			fwrite($file,"use_large_installation_tweaks=0\n");
			fwrite($file,"enable_environment_macros=0\n");
			fwrite($file,"debug_level=0\n");
			fwrite($file,"debug_verbosity=1\n");
			fwrite($file,"debug_file=/var/spool/icinga/icinga.debug\n");
			fwrite($file,"event_profiling_enabled=0\n");
			
			fclose($file);
			
			$file = fopen("/usr/local/etc/icinga/objects/templates.cfg","w+");
			if($file == NULL || $file == false)
				return 1;
				
			// Write Templates
			$query = FS::$dbMgr->Select("fss_icinga_contact_template","name,hostnotifperiod,srvnotifperiod,srvnotifopt,hostnotifopt");
			while($data = mysql_fetch_array($query)) {
				fwrite($file,"define contact{\n");
				fwrite($file,"\tname\t".$data["name"]."\n");
				fwrite($file,"\thost_notification_period\t".$data["hostnotifperiod"]."\n");
				fwrite($file,"\tservice_notification_options\t".$data["srvnotifperiod"]."\n");
				fwrite($file,"\thost_notification_options\t".$data["hostnotifopt"]."\n");
				fwrite($file,"\tservice_notification_period\t".$data["srvnotifopt"]."\n");
				fwrite($file,"\thost_notification_commands\tnotify-host-by-email\n");
				fwrite($file,"\tservice_notification_commands\tnotify-service-by-email\n");
				fwrite($file,"\tregister\t0\n");
				fwrite($file,"}\n");
			}
			
			$query = FS::$dbMgr->Select("fss_icinga_host_template","name,notifenable,eventhdlenable,flapenable,failpredictenable,
				perfenable,retainstatus,retainnonstatus,notifperiod,checkinterval,retryinterval,notifinterval,notifopts,
				contactgroups");
			while($data = mysql_fetch_array($query)) {
				fwrite($file,"define host{\n");
				fwrite($file,"\tname\t".$data["name"]."\n");
				fwrite($file,"\tnotification_period\t".$data["notifperiod"]."\n");
				fwrite($file,"\tevent_handler_enabled\t".$data["eventhdlenable"]."\n");
				fwrite($file,"\tnotifications_enabled\t".$data["notifenable"]."\n");
				fwrite($file,"\tflap_detection_enabled\t".$data["flapenable"]."\n");
				fwrite($file,"\tfailure_prediction_enabled\t".$data["failpredictenable"]."\n");
				fwrite($file,"\tprocess_perf_data\t".$data["perfenable"]."\n");
				fwrite($file,"\tretain_status_information\t".$data["retainstatus"]."\n");
				fwrite($file,"\tretain_nonstatus_information\t".$data["retainnonstatus"]."\n");
				fwrite($file,"\tcheck_interval\t".$data["checkinterval"]."\n");
				fwrite($file,"\tretry_interval\t".$data["retryinterval"]."\n");
				fwrite($file,"\tnotification_interval\t".$data["notifinterval"]."\n");
				fwrite($file,"\tnotification_options\t".$data["notifopts"]."\n");
				//fwrite($file,"\tcontact_groups\t".$data["contactgroups"]."\n");
				//fwrite($file,"\thostgroups\t"."\n"); @TODO
				fwrite($file,"\tregister\t0\n");
				fwrite($file,"}\n");
			}
			
			$query = FS::$dbMgr->Select("fss_icinga_service_template","name,actcheckenable,passivecheckenable,parallelizeenable,obsess,
				checkfreshness,notifenable,eventhdlenable,flapenable,failenable,processperf,retainstatus,retainnonstatus,volatile,
				checkperiod,maxcheck,normalcheckinterval,retrycheckinterval,contactgroups,notifopts,notifinterval,notifperiod");
			while($data = mysql_fetch_array($query)) {
				fwrite($file,"define service{\n");
				fwrite($file,"\tname\t".$data["name"]."\n");
				fwrite($file,"\tactive_checks_enabled\t".$data["actcheckenable"]."\n");
				fwrite($file,"\tpassive_checks_enabled\t".$data["passivecheckenable"]."\n");
				fwrite($file,"\tparallelize_check\t".$data["parallelizeenable"]."\n");
				fwrite($file,"\tobsess_over_service\t".$data["obsess"]."\n");
				fwrite($file,"\tcheck_freshness\t".$data["checkfreshness"]."\n");
				fwrite($file,"\tnotifications_enabled\t".$data["notifenable"]."\n");
				fwrite($file,"\tevent_handler_enabled\t".$data["eventhdlenable"]."\n");
				fwrite($file,"\tflap_detection_enabled\t".$data["flapenable"]."\n");
				fwrite($file,"\tfailure_prediction_enabled\t".$data["failenable"]."\n");
				fwrite($file,"\tprocess_perf_data\t".$data["processperf"]."\n");
				fwrite($file,"\tretain_status_information\t".$data["retainstatus"]."\n");
				fwrite($file,"\tretain_nonstatus_information\t".$data["retainnonstatus"]."\n");
				fwrite($file,"\tis_volatile\t".$data["volatile"]."\n");
				fwrite($file,"\tcheck_period\t".$data["checkperiod"]."\n");
				fwrite($file,"\tmax_check_attempts\t".$data["maxcheck"]."\n");
				fwrite($file,"\tnormal_check_interval\t".$data["normalcheckinterval"]."\n");
				fwrite($file,"\tretry_check_interval\t".$data["retrycheckinterval"]."\n");
				//fwrite($file,"\tcontact_groups\t"."\n"); @TODO
				fwrite($file,"\tnotification_options\t".$data["notifopts"]."\n");
				fwrite($file,"\tnotification_interval\t".$data["notifinterval"]."\n");
				fwrite($file,"\tnotification_period\t".$data["notifperiod"]."\n");
				fwrite($file,"\tregister\t0\n");
				fwrite($file,"}\n");
			}
			// Write contacts
			
			// Write commands
			
			// Write hosts
			return 0;			
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // main configuration
					break;
				default: break;
			}
		}
	};
?>
