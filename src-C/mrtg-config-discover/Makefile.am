bin_PROGRAMS = zeye-mrtg-config-discover
AM_CPPFLAGS = @MYSQL_CPPFLAGS@ -I/usr/include/postgresql/

zeye_mrtg_config_discover_SOURCES = \
	common.h \
	main.c \
	stdmgmt.c \
	stdmgmt.h


zeye_mrtg_config_discover_LDADD = @MYSQL_LDADD@ -lpq -L/usr/lib/postgresql
zeye_mrtg_config_discover_LDFLAGS = 
