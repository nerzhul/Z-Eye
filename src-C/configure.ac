AC_INIT(z-eye-utils,0.3c,loic.blot@frostsapphirestudios.com)
AM_INIT_AUTOMAKE

AC_PROG_CC

AC_HEADER_STDC
AC_CHECK_HEADERS([stdlib.h])
AC_CHECK_LIB(m, pow)
AC_CHECK_LIB(z, deflate)
MYSQL_CPPFLAGS=`mysql_config --cflags`
MYSQL_LDADD=`mysql_config --libs`
AC_SUBST(MYSQL_CPPFLAGS)
AC_SUBST(MYSQL_LDADD)
AC_CONFIG_FILES([
Makefile
make-snmp-cache/Makefile
mrtg-config-discover/Makefile
mrtg-data-refresh/Makefile
])
AC_OUTPUT
