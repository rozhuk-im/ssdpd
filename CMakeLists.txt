#
# Cmake configuration file
#

############################# INITIAL SECTION ##########################
CMAKE_MINIMUM_REQUIRED(VERSION 3.1 FATAL_ERROR)

PROJECT(ssdpd C)

SET(PACKAGE_VERSION_MAJOR	1)
SET(PACKAGE_VERSION_MINOR	07)
SET(PACKAGE_VERSION_PATCH	0)

SET(PACKAGE_NAME		"${PROJECT_NAME}")
SET(PACKAGE_VERSION		"${PACKAGE_VERSION_MAJOR}.${PACKAGE_VERSION_MINOR}.${PACKAGE_VERSION_PATCH}")
SET(PACKAGE_URL			"https://github.com/rozhuk-im/ssdpd")
SET(PACKAGE_BUGREPORT		"https://github.com/rozhuk-im/ssdpd")
SET(PACKAGE_STRING 		"${PACKAGE_NAME} ${PACKAGE_VERSION}")
SET(PACKAGE_DESCRIPTION		"SSDP announce daemon for UPnP 1.1")
SET(PACKAGE_TARNAME		"${PACKAGE_NAME}-${PACKAGE_VERSION}")

############################# OPTIONS SECTION ##########################

OPTION(ENABLE_COVERAGE		"Build with code coverage options [default: OFF]"	OFF)
OPTION(ENABLE_FULL_DEBUG	"Build with all possible debug [default: OFF]"		OFF)
OPTION(INSTALL_PHP_MEDIA_SERVER	"Install PHP UPnP/DLNA Media server [default: ON]"	OFF)

# Now CMAKE_INSTALL_PREFIX is a base prefix for everything.
IF(NOT CONFDIR)
	SET(CONFDIR "${CMAKE_INSTALL_PREFIX}/etc/ssdpd")
ENDIF()

IF(NOT RUNDIR)
	SET(RUNDIR "/var/run")
ENDIF()

IF(NOT WWWDIR)
	SET(WWWDIR "${CMAKE_INSTALL_PREFIX}/share/ssdpd/www")
ENDIF()


############################# INCLUDE SECTION ##########################

#INCLUDE(CheckIncludeFiles)
INCLUDE(CheckFunctionExists)
INCLUDE(CheckSymbolExists)
INCLUDE(CheckCCompilerFlag)

FIND_LIBRARY(PTHREAD_LIBRARY pthread)
LIST(APPEND CMAKE_REQUIRED_LIBRARIES ${PTHREAD_LIBRARY})

############################# MACRO SECTION ############################
MACRO(TRY_C_FLAG PROP FLAG)
	# Try flag once on the C compiler
	CHECK_C_COMPILER_FLAG("-Werror ${FLAG}" C_FLAG_${PROP})
	IF(C_FLAG_${PROP})
		SET(CMAKE_C_FLAGS "${CMAKE_C_FLAGS} ${FLAG}")
	ENDIF()
ENDMACRO()

MACRO(TRY_LINKER_FLAG PROP FLAG)
	# Check with the C compiler
	SET(CMAKE_REQUIRED_FLAGS ${FLAG})
	CHECK_C_COMPILER_FLAG(${FLAG} LINKER_FLAG_${PROP})
	SET(CMAKE_REQUIRED_FLAGS "")
	IF(LINKER_FLAG_${PROP})
		SET(CMAKE_EXE_LINKER_FLAGS "${CMAKE_EXE_LINKER_FLAGS} ${FLAG}")
		SET(CMAKE_SHARED_LINKER_FLAGS "${CMAKE_SHARED_LINKER_FLAGS} ${FLAG}")
		SET(CMAKE_MODULE_LINKER_FLAGS "${CMAKE_MODULE_LINKER_FLAGS} ${FLAG}")
	ENDIF()
ENDMACRO()

FUNCTION(INSTALL_IF_NOT_EXISTS src dest destname suffix)
	IF(NOT IS_ABSOLUTE "${src}")
		SET(src "${CMAKE_CURRENT_SOURCE_DIR}/${src}")
	ENDIF()
	IF(NOT IS_ABSOLUTE "${dest}")
		SET(dest "${CMAKE_INSTALL_PREFIX}/${dest}")
	ENDIF()
	GET_FILENAME_COMPONENT(src_name "${src}" NAME)
	GET_FILENAME_COMPONENT(dest_name "${destname}" NAME)
	IF(NOT EXISTS "${dest}/${dest_name}${suffix}")
		SET(src_tmp "${CMAKE_CURRENT_BINARY_DIR}/${dest_name}${suffix}")
		CONFIGURE_FILE(${src} ${src_tmp} @ONLY)
		INSTALL(FILES ${src_tmp} DESTINATION ${dest})
	ENDIF()
ENDFUNCTION()

FUNCTION(INSTALL_SCRIPT src dest)
	IF(NOT IS_ABSOLUTE "${src}")
		SET(src "${CMAKE_CURRENT_SOURCE_DIR}/${src}")
	ENDIF()
	IF(NOT IS_ABSOLUTE "${dest}")
		SET(dest "${CMAKE_INSTALL_PREFIX}/${dest}")
	ENDIF()
	GET_FILENAME_COMPONENT(src_name "${src}" NAME)
	SET(src_tmp "${CMAKE_CURRENT_BINARY_DIR}/${src_name}")
	CONFIGURE_FILE(${src} ${src_tmp} @ONLY)
	INSTALL(PROGRAMS ${src_tmp} DESTINATION ${dest})
ENDFUNCTION()

FUNCTION(INSTALL_CONFIG src dest)
	IF(NOT IS_ABSOLUTE "${src}")
		SET(src "${CMAKE_CURRENT_SOURCE_DIR}/${src}")
	ENDIF()
	IF(NOT IS_ABSOLUTE "${dest}")
		SET(dest "${CMAKE_INSTALL_PREFIX}/${dest}")
	ENDIF()
	GET_FILENAME_COMPONENT(src_name "${src}" NAME)
	SET(src_tmp "${CMAKE_CURRENT_BINARY_DIR}/${src_name}")
	CONFIGURE_FILE(${src} ${src_tmp} @ONLY)
	INSTALL(FILES ${src_tmp} DESTINATION ${dest})
ENDFUNCTION()

############################# CONFIG SECTION ###########################

# Prefer local include dirs to system ones.
INCLUDE_DIRECTORIES("${CMAKE_CURRENT_SOURCE_DIR}/"
		"${CMAKE_CURRENT_SOURCE_DIR}/src"
		"${CMAKE_CURRENT_SOURCE_DIR}/src/liblcb/include"
		"${CMAKE_BINARY_DIR}/src")

SET(TAR "tar")

# Platform specific configuration.
IF(CMAKE_SYSTEM_NAME MATCHES "^.*BSD$|DragonFly")
	ADD_DEFINITIONS(-D_BSD_SOURCE -DFREEBSD)
	INCLUDE_DIRECTORIES("/usr/local/include/")
	MESSAGE(STATUS "Configuring for BSD system")
	SET(TAR "gtar")
ENDIF()

IF(CMAKE_SYSTEM_NAME STREQUAL "Darwin")
	ADD_DEFINITIONS(-D_BSD_SOURCE -DDARWIN)
	MESSAGE(STATUS "Configuring for Darwin")
	SET(TAR "gnutar")
ENDIF()


IF(CMAKE_SYSTEM_NAME STREQUAL "Linux")
	ADD_DEFINITIONS(-D_GNU_SOURCE -DLINUX -D__USE_GNU=1)
	IF(BUILD_CPU_MODE STREQUAL "32")
		ADD_DEFINITIONS(-D_FILE_OFFSET_BITS=64 -D_LARGEFILE64_SOURCE)
	ENDIF()

	LIST(APPEND CMAKE_REQUIRED_LIBRARIES rt)
ENDIF()


TRY_C_FLAG(PIPE "-pipe")

# Process with warn flags.
IF(NOT CMAKE_C_OPT_FLAGS AND ENABLE_FULL_DEBUG)
	TRY_C_FLAG(W "-W")
	TRY_C_FLAG(WALL "-Wall")
	TRY_C_FLAG(WPOINTER "-Wpointer-arith")
	TRY_C_FLAG(WPARAM "-Wno-unused-parameter")
	TRY_C_FLAG(WFUNCTION "-Wno-unused-function")
	TRY_C_FLAG(WUNUSED_VAR "-Wno-unused-variable")
	TRY_C_FLAG(WPOINTER_SIGN "-Wno-pointer-sign")
	TRY_C_FLAG(WSIGN_COMPARE "-Wno-sign-compare")
	TRY_C_FLAG(WSTRICT_PROTOTYPES "-Wstrict-prototypes")
	TRY_C_FLAG(PEDANTIC "-pedantic")
	TRY_C_FLAG(WNO_UNUSED_CONST "-Wno-unused-const-variable")
	TRY_C_FLAG(WNULL_DEREFERENCE "-Wnull-dereference")
	TRY_C_FLAG(WDUPLICATED_COND "-Wduplicated-cond")
	TRY_C_FLAG(WIMPLICIT_FALLTHROUGH "-Wimplicit-fallthrough")
ENDIF()

IF(NOT "${CMAKE_C_COMPILER_ID}" MATCHES SunPro)
	TRY_C_FLAG(STD11 "-std=c11")
	IF(NOT C_FLAG_STD11)
		TRY_C_FLAG(STD99 "-std=c99")
	ENDIF()
ENDIF()


# Hardening flags
TRY_C_FLAG(FORTIFY_SOURCE2 "-D_FORTIFY_SOURCE=2")
TRY_C_FLAG(FSTACK_PROTECTOR_ALL "-fstack-protector-all")
TRY_C_FLAG(FSANITIZE_SAFE_STACK "-fsanitize=safe-stack")
TRY_C_FLAG(FSANITIZE_CFI "-fsanitize=cfi")
TRY_C_FLAG(MRETPOLINE "-mretpoline")
TRY_C_FLAG(MFUNCTION_RETURN "-mfunction-return=thunk")
TRY_C_FLAG(MINDIRECT_BRANCH "-mindirect-branch=thunk")
TRY_C_FLAG(FWRAPV "-fwrapv")
TRY_C_FLAG(FPIE "-fPIE")
TRY_LINKER_FLAG(PIE "-pie")
TRY_LINKER_FLAG(RETPOLINEPLT "-Wl,-z,retpolineplt")
TRY_LINKER_FLAG(ZRELRO "-Wl,-z,relro")
TRY_LINKER_FLAG(ZNOW "-Wl,-z,now")
TRY_LINKER_FLAG(ZNOEXECSTACK "-Wl,-z,noexecstack")


# Optimization flags
IF(ENABLE_COVERAGE)
	SET(CMAKE_C_OPT_FLAGS "-g -O0 -fno-strict-aliasing")
	SET(CMAKE_C_FLAGS "${CMAKE_C_FLAGS} --coverage")
	SET(CMAKE_EXE_LINKER_FLAGS "${CMAKE_EXE_LINKER_FLAGS} --coverage")
ELSE()
	IF(NOT CMAKE_C_OPT_FLAGS)
		IF(ENABLE_FULL_DEBUG)
			SET(CMAKE_C_OPT_FLAGS "-g -O0 -fstrict-aliasing")
		ELSE()
			SET(CMAKE_C_OPT_FLAGS "-g -O2 -fstrict-aliasing")
		ENDIF()
	ENDIF()
ENDIF()


SET(CMAKE_C_FLAGS "${CMAKE_C_OPT_FLAGS} ${CMAKE_C_FLAGS}")
# Silently strip whitespace
STRING(STRIP "${CMAKE_C_FLAGS}" CMAKE_C_FLAGS)
STRING(STRIP "${CMAKE_EXE_LINKER_FLAGS}" CMAKE_EXE_LINKER_FLAGS)
STRING(STRIP "${CMAKE_MODULE_LINKER_FLAGS}" CMAKE_MODULE_LINKER_FLAGS)
STRING(STRIP "${CMAKE_SHARED_LINKER_FLAGS}" CMAKE_SHARED_LINKER_FLAGS)


# Check platform specific includes.
#CHECK_INCLUDE_FILES(sys/types.h		HAVE_SYS_TYPES_H)

# Check platform API.
CHECK_FUNCTION_EXISTS(memset_s			HAVE_MEMSET_S)
CHECK_FUNCTION_EXISTS(explicit_bzero		HAVE_EXPLICIT_BZERO)
CHECK_FUNCTION_EXISTS(memrchr			HAVE_MEMRCHR)
CHECK_FUNCTION_EXISTS(memmem			HAVE_MEMMEM)
CHECK_FUNCTION_EXISTS(strncasecmp		HAVE_STRNCASECMP)
CHECK_FUNCTION_EXISTS(reallocarray		HAVE_REALLOCARRAY)
CHECK_FUNCTION_EXISTS(pipe2			HAVE_PIPE2)
CHECK_FUNCTION_EXISTS(accept4			HAVE_ACCEPT4)

# Check macros.
CHECK_SYMBOL_EXISTS(SOCK_NONBLOCK sys/socket.h	HAVE_SOCK_NONBLOCK)

CONFIGURE_FILE(config.h.cmake src/config.h)
ADD_DEFINITIONS(-DHAVE_CONFIG_H)

################################ SUBDIRS SECTION #######################

ADD_SUBDIRECTORY(src)

############################ TARGETS SECTION ###########################

ADD_CUSTOM_TARGET(dist ${CMAKE_CURRENT_SOURCE_DIR}/dist.sh
	"${CMAKE_BINARY_DIR}/${PACKAGE_TARNAME}.tar.xz" "${TAR}"
	COMMENT "Create source distribution"
	WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR})

##################### INSTALLATION #####################################

# Configs
INSTALL(CODE "FILE(MAKE_DIRECTORY ${CONFDIR})")

INSTALL_IF_NOT_EXISTS(ssdpd.conf ${CONFDIR} "ssdpd.conf" ".sample")

IF(CMAKE_SYSTEM_NAME MATCHES "^.*BSD$|DragonFly")
	INSTALL_SCRIPT(freebsd/ssdpd "${CMAKE_INSTALL_PREFIX}/etc/rc.d/")
ENDIF()

# Install PHP UPnP/DLNA Media server
IF(INSTALL_PHP_MEDIA_SERVER)
	INSTALL(DIRECTORY "www/upnp" DESTINATION ${WWWDIR})
	INSTALL(DIRECTORY DESTINATION "${WWWDIR}/upnpdata")
	INSTALL_CONFIG("nginx/nginx-upnp-full.conf" ${CONFDIR})
	INSTALL_CONFIG("nginx/nginx-upnp-server.conf" ${CONFDIR})
	INSTALL_CONFIG("nginx/nginx-upnp-handler.conf" ${CONFDIR})
	INSTALL_CONFIG("php/upnp-server.conf" "${CMAKE_INSTALL_PREFIX}/etc/php-fpm.d/")
ENDIF()

