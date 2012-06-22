bin_PROGRAMS = zeye-mrtg-config-discover
AM_CPPFLAGS = @MYSQL_CPPFLAGS@

zeye_mrtg_config_discover_SOURCES = \
	common.h \
	main.c \
	mysqlmgmt.c \
	mysqlmgmt.h \
	stdmgmt.c \
	stdmgmt.h


zeye_mrtg_config_discover_LDADD = @MYSQL_LDADD@ -L/usr/local/pgsql/lib
zeye_mrtg_config_discover_LDFLAGS = 
