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

	class lInstall extends FSLocales {
		function __construct() {
			parent::__construct();
			$locales = array(
				"fr" => array(
					"err-fields-missing" => "Certains champs sont manquants",
					"err-mail-invalid" => "Adresse mail invalide",
					"err-mail-match" => "Les adresses mails ne correspondent pas",
					"err-name-invalid" => "Le nom spécifié est invalide (lettres uniquement)",
					"err-pwd-match" => "Les mots de passe ne correspondent pas",
					"err-pwd-too-weak" => "Le mot de passe n'est pas assez complexe",
					"err-step-invalid" => "étape demandée invalide !",
					"err-surname-invalid" => "Le prénom spécifié est invalide (lettres uniquement)",
					"err-username-invalid" => "Nom d'utilisateur invalide",
					"Finish" => "Terminer",
					"Lets-Go" => "C'est parti !",
					"Mail" => "Adresse mail",
					"Mail-repeat" => "Répétez l'adresse mail",
					"menu-title" => "Installation",
					"Option" => "Option",
					"Password" => "Mot de passe",
					"Password-repeat" => "Répétez le mot de passe",
					"Send" => "Envoyer",
					"Surname" => "Prénom",
					"text-admin-set" => "L'administrateur a tous les droits sur Z-Eye, il est impossible de lui restreindre l'accès à un module, quand bien même on lui aurait retiré les droits. Il possède également un accès privilégié à quelques fonctions de développement comme la réorganisation des menus.<br > Vous pourrez spécifier ici un mot de passe et une adresse mail afin que vos utilisateurs puissent vous contacter en cas de problème",
					"text-finish" => "Félicitations ! L'installation est désormais terminée. L'application est désormais pleinement utilisable avec les identifiants fournis. Appuyez sur le bouton Terminer pour commenter à utiliser Z-Eye !",  
					"text-welcome" => "Bienvenue dans l'installeur de Z-Eye.<br /><br />Merci d'avoir choisi cette solution pour vos besoins en terme de gestion réseau et monitoring. 
						Cette solution propose un ensemble de services optionnels. Il n'est pas nécessaire d'utiliser tous les systèmes embarqués, néanmoins, 
							dans un souci de visibilité, il est conseillé d'utiliser les modules réseau ensemble<br /><br />Cet installeur va 
						vous permettre de configurer les premiers paramètres de Z-Eye",
					"title-admin-set" => "Configuration de l'administrateur",
					"title-install-finished" => "Installation terminée",
					"title-master-install" => "Installation de Z-Eye",
					"title-welcome" => "Bienvenue !",
					"Username" => "Nom d'utilisateur",
					"Value" => "Valeur",
				),
				"en" => array(
					"err-fields-missing" => "Some fields are missing",
					"err-mail-invalid" => "Invalid mail address",
					"err-mail-match" => "Mail addresses don't match",
					"err-name-invalid" => "Name is invalid (letters only)",
					"err-pwd-match" => "Passwords don't match", 
					"err-pwd-too-weak" => "Password is too weak",
					"err-step-invalid" => "Asked step is invalid !!",
					"err-surname-invalid" => "Surname is invalid (letters only)",
					"err-username-invalid" => "Username invalid",
					"Finish" => "Finish",
					"Lets-Go" => "Let's go !",
					"Mail" => "Mail address",
					"Mail-repeat" => "Repeat mail address",
					"menu-title" => "Install",
					"Option" => "Option",
					"Password" => "Password",
					"Password-repeat" => "Repeat password",
					"Send" => "Send",
					"Surname" => "Surname",
					"title-admin-set" => "Admin configuration",
					"title-master-install" => "Z-Eye install",
					"title-welcome" => "Welcome !",
					"Username" => "Username",
					"Value" => "Value",
				)
			);
			$this->concat($locales);
		}
	};
?>
