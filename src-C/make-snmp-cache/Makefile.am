bin_PROGRAMS = zeye-make-snmp-cache

AM_CPPFLAGS = @MYSQL_CPPFLAGS@ -I/usr/include/postgresql/

zeye_make_snmp_cache_SOURCES = \
	common.h \
	main.c \
	stdmgmt.c \
	stdmgmt.h


zeye_make_snmp_cache_LDADD = @MYSQL_LDADD@ -lpq

zeye_make_snmp_cache_LDFLAGS = 
