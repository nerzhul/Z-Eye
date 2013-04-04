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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lSecReport extends zLocales {
		function lSecReport() {
			parent::zLocales();
			$locales = array(
				"fr" => array(
					"Action-nb" => "Nombre d'actions",
					"Alert" => "Alerte",
					"Date" => "Date",
					"Destination" => "Destination",
					"fail-tab" => "Impossible de charger l'onglet, le lien est peut être faux ou la page indisponible",
					"General" => "Général",
					"IP-addr" => "Adresse IP",
					"last-100" => "100 derniers enregistrements",
					"Last-logs" => "Derniers logs",
					"Last-visit" => "Dernière visite",
					"Maximum" => "Maximum",
					"menu-title" => "Rapports de sécurité",
					"nb-ip-atk" => "Nombre d'IP attaquantes",
					"nb-scan-port" => "Nombre de scans de ports",
					"nb-ssh-atk" => "Nombre d'attaques SSH",
					"nb-tse-atk" => "Nombre d'attaques TSE",
					"No-alert-found" => "Aucune alerte trouvée",
					"Scans" => "Scans",
					"Source" => "Source",
					"SSH" => "SSH",
					"SSH-atk" => "Attaques SSH",
					"The" => "Les",
					"total-atk" => "Total des attaques",
					"TSE" => "Terminal Server",
					"TSE-atk" => "Attaques TSE",
					"title-attack-report" => "Rapports de Sécurité",
					"title-z-eye-report" => "Rapport d'attaques compressé en base Z-Eye",
					"Update" => "Mise à jour",
					"violent-days" => "jours les plus violents",
				),
				"en" => array(
					"Action-nb" => "Action count",
					"Alert" => "Alert",
					"Date" => "Date",
					"Destination" => "Destination",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"General" => "General",
					"IP-addr" => "IP address",
					"last-100" => "Last 100 records",
					"Last-logs" => "Last logs",
					"Last-visit" => "Last visit",
					"Maximum" => "Maximum",
					"menu-title" => "Security reports",
					"nb-ip-atk" => "Attacker IP count",
					"nb-scan-port" => "Portscan count",
					"nb-ssh-atk" => "SSH attack count",
					"nb-tse-atk" => "TSE attack count",
					"No-alert-found" => "No alert found",
					"Scans" => "Scans",
					"Source" => "Source",
					"SSH" => "SSH",
					"SSH-atk" => "SSH attacks",
					"The" => "The",
					"total-atk" => "Attack count",
					"TSE" => "Terminal Server",
					"TSE-atk" => "TSE attacks",
					"title-attack-report" => "Security reports",
					"title-z-eye-report" => "Attack reports (compressed in Z-Eye DB)",
					"Update" => "Update",
					"violent-days" => " most violent days",
				)
			);
			$this->concat($locales);
		}
	};
?>
