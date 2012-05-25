<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
			$output = "<div id=\"monoComponent\"><h3>Management de Nagios (icinga)</h3>";
			$node = FS::$secMgr->checkAndSecuriseGetData("n");
			$cont = FS::$secMgr->checkAndSecuriseGetData("ctct");
			if($node != NULL)
				$output .= $this->showNode($node);
			else if($cont != NULL)
				$output .= $this->showContact($cont);
			else
				$output .= $this->showMain();
			
			$output .= "</div>";
			return $output;
		}
		
		private function showContact($cont) {
			$output = "<h4>Gestion du contact ".$cont."</h4>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
			$output .= "<table class=\"standardTable\">";
			$output .= FS::$iMgr->addIndexedLine("Nom du contact","ctctname","contactname"); // compress spaces to make contct_name and use it as alias
			$output .= FS::$iMgr->addIndexedLine("Adresse E-Mail","ctctmail","admin@domain.tld");
			$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
			$output .= "</table></form>";
			return $output;	
		}

		private function showNode($node) {
			$output = "<h4>Gestion du noeud ".$node."</h4>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
			$output .= "<table class=\"standardTable\">";
			$output .= FS::$iMgr->addIndexedLine("Nom du serveur","srvname","servername"); // compress spaces to make contct_name and use it as alias
			$output .= FS::$iMgr->addIndexedLine("Adresse du serveur","srvaddr","servername");
			$output .= FS::$iMgr->addIndexedNumericLine("Intervalle de mise à jour (minutes)","updateint","5");
			$output .= FS::$iMgr->addIndexedNumericLine("Intervalle des tentatives en cas d'échec (minutes)","rtryint","1");
			$output .= FS::$iMgr->addIndexedNumericLine("Nombres d'essais avant de définir en échec","maxtry","10");
			$output .= FS::$iMgr->addIndexedNumericLine("Intervalle de notifications (minutes) (0 = 1 seule notification)","notifint","30");
			// ensemble de cases à cocher pour les contacts @ TODO
			$output .= FS::$iMgr->addIndexedNumericLine("","updateint","5");
			$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
			return $output;	
		}
		
		private function showMain() {
			$output = "<h4>Gestion des objets</h4>";
			
			$output .= "<hr><h4>Configuration globale</h4>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">Délais</th></tr>";
			$output .= FS::$iMgr->addIndexedLine("Intervalle de mise à jour des status","stupdateint",30);
			$output .= FS::$iMgr->addIndexedLine("Intervalle de vérification des commandes externes","cmdcheckint",15);
			$output .= FS::$iMgr->addIndexedLine("Intervalle de mise à jour des résultats des hôtes et services","resfreq",10);
			$output .= FS::$iMgr->addIndexedLine("Age maximal des résultats","maxresultage",3600);
			$output .= FS::$iMgr->addIndexedLine("Age maximal de l'état d'un hôte","cachehostlifetime",15);
			$output .= FS::$iMgr->addIndexedLine("Age maximal de l'état d'un service","cachesrvlifetime",15);
			$output .= FS::$iMgr->addIndexedLine("Temps maximal de recherche d'état d'un service","srvchecktimeout",60);
			$output .= FS::$iMgr->addIndexedLine("Temps maximal de recherche d'état d'un hôte","hostchecktimeout",30);
			$output .= FS::$iMgr->addIndexedLine("Temps maximal d'envoi d'une notification","notiftimeout",30);
			$output .= FS::$iMgr->addIndexedLine("Intervalle par défaut d'utilisation d'une sonde","interval",60);
			$output .= "<tr><th colspan=\"2\">Logs</th></tr>";
			// Log rotation method
			$output .= FS::$iMgr->addIndexedCheckLine("Log des notifications","lognotif",true);
			$output .= FS::$iMgr->addIndexedCheckLine("Log des tentatives sur les services","logsrvretries",true);
			$output .= FS::$iMgr->addIndexedCheckLine("Log des tentatives sur les hôtes","loghostretries",true);
			$output .= "<tr><th colspan=\"2\">Notifications</th></tr>";
			$output .= FS::$iMgr->addIndexedCheckLine("Activer","notifenable",true);
			// service_check_timeout_state
			$output .= "<tr><th colspan=\"2\">Autres</th></tr>";
			$output .= FS::$iMgr->addIndexedCheckLine("Détection des services instables","flapenable",true);
			// date_format
			$output .= FS::$iMgr->addIndexedCheckLine("Mail de l'administrateur","adminemail","admin@localhost");
			$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
			$output .= "</table></form>";
			return $output;	
		}
		
		private function checkIcingaMainConfig() {
			
			return true;	
		}
		
		private function writeIcingaMainConfig() {
			$file = fopen("/usr/local/etc/icinga/icinga.cfg","w+");
			if($file == NULL || $file == false)
				return 1;
				
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
			//fwrite($file,"status_update_interval=".$stupdateint."\n");
			fwrite($file,"icinga_user=icinga\n");
			fwrite($file,"icinga_group=icinga\n");
			fwrite($file,"check_external_commands=1\n");
			//fwrite($file,"command_check_interval=".$cmdcheckint."s\n");
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
			//fwrite($file,"log_notifications=".$lognotif."\n");
			//fwrite($file,"log_service_retries=".$logsrvretries."\n");
			//fwrite($file,"log_host_retries=".$loghretries."\n");
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
			//fwrite($file,"check_result_reaper_frequency=".$resfreq."\n");
			fwrite($file,"max_check_result_reaper_time=30\n");
			fwrite($file,"check_result_path=/var/spool/icinga/checkresults\n");
			//fwrite($file,"max_check_result_file_age=".$maxresultage."\n");
			//fwrite($file,"cached_host_check_horizon=".$cachehostlifetime."\n");
			//fwrite($file,"cached_service_check_horizon=".$cachesrvlifetime."\n");
			fwrite($file,"enable_predictive_host_dependency_checks=1\n");
			fwrite($file,"enable_predictive_service_dependency_checks=1\n");
			fwrite($file,"soft_state_dependencies=0\n");
			fwrite($file,"time_change_threshold=900\n");
			fwrite($file,"auto_reschedule_checks=0\n");
			fwrite($file,"auto_rescheduling_interval=30\n");
			fwrite($file,"auto_rescheduling_window=180\n");
			fwrite($file,"sleep_time=0.25\n");
			fwrite($file,"# ---- Timeouts ----\n");
			//fwrite($file,"service_check_timeout=".$srvchecktimeout."\n");
			//fwrite($file,"host_check_timeout=".$hostchecktimeout."\n");
			//fwrite($file,"notification_timeout=".$notiftimeout."\n");
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
			//fwrite($file,"enable_notifications=".$notifenable."\n");
			fwrite($file,"enable_event_handlers=1\n");
			fwrite($file,"process_performance_data=0\n");
			fwrite($file,"obsess_over_services=0\n");
			fwrite($file,"obsess_over_hosts=0\n");
			fwrite($file,"translate_passive_host_checks=0\n");
			fwrite($file,"passive_host_checks_are_soft=0\n");
			fwrite($file,"check_for_orphaned_services=1\n");
			fwrite($file,"check_for_orphaned_hosts=1\n");
			//fwrite($file,"service_check_timeout_state=".$timeoutstate."\n");
			fwrite($file,"check_service_freshness=1\n");
			fwrite($file,"service_freshness_check_interval=60\n");
			fwrite($file,"check_host_freshness=1\n");
			fwrite($file,"host_freshness_check_interval=60\n");
			fwrite($file,"additional_freshness_latency=15\n");
			//fwrite($file,"enable_flap_detection=".$flapenable."\n");
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
			//fwrite($file,"admin_email=".$adminemail."\n");
			//fwrite($file,"admin_pager=".$adminemail."\n");
			fwrite($file,"daemon_dumps_core=0\n");
			fwrite($file,"use_large_installation_tweaks=0\n");
			fwrite($file,"enable_environment_macros=0\n");
			fwrite($file,"debug_level=0\n");
			fwrite($file,"debug_verbosity=1\n");
			fwrite($file,"debug_file=/var/spool/icinga/icinga.debug\n");
			fwrite($file,"event_profiling_enabled=0\n");
			
			fclose($file);
			return 0;			
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					break;
				default: break;
			}
		}
	};
?>
