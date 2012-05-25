<?php
/////////////////////////////////////////////////////////////////////////////////////////
//  LDAP-related config
/////////////////////////////////////////////////////////////////////////////////////////
        class LDAPConfig {
			public static $baseDN = "ou=people,dc=domain,dc=tld";
			public static $ldapServer = "ldaps.domain.tld";
			public static $ldapServerPort = 636;
			public static $SSL = true;
			public static $DN = "cn=admin,dc=domain,dc=tld";
			public static $LDAPmdp = "admPwd";
			public static $LDAPname = "uid";
			public static $LDAPuid = "uid";
			public static $LDAPsurname = "displayName";
			public static $LDAPmail = "mail";
			public static $LDAPfilter = "";
        };
?>

