<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNagios extends genModule{
		function iNagios($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Management de Nagios (icinga)</h3>";
			$node = FS::$secMgr->checkAndSecuriseGetData("n");
			
			if($node != NULL)
				$this->showNode($node);
			else
				$this->showMain();
			
			$output .= "</div>";
			return $output;
		}

		private function showNode($node) {
			$output = "<h4>Gestion du noeud ".$node."</h4>";
			return $output;	
		}
		
		private function showMain() {
			$output = "<h4>Configuration générale</h4>";
			
			
			return $output;	
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
