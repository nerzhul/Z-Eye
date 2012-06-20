bin_PROGRAMS = zeye-mrtg-config-discover
AM_CPPFLAGS = @MYSQL_CPPFLAGS@ -I/usr/local/pgsql/include

zeye_mrtg_config_discover_SOURCES = \
	common.h \
	main.c \
	mysqlmgmt.c \
	mysqlmgmt.h \
	stdmgmt.c \
	stdmgmt.h


zeye_mrtg_config_discover_LDADD = @MYSQL_LDADD@ -L/usr/local/pgsql/lib -lpq
zeye_mrtg_config_discover_LDFLAGS = 
