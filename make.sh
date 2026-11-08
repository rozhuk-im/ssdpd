#!/bin/sh
### Rozhuk Ivan 2026
# shellcheck disable=SC1090,SC2034,SC2086,SC2153

# Exit on error.
set -e

# Init constans.
THIS_SCRIPT_NAME="$(readlink -nf "${0}")"
THIS_DIR="$(dirname "${THIS_SCRIPT_NAME}")"
export THIS_DIR
BUILD_SYS_SCRIPT_NAME="${THIS_DIR}/3rdparty/ssbs/ssbs.sh"
. "${BUILD_SYS_SCRIPT_NAME}"


APP_NAME='ssdpd'
APP_VERSION='1.9.1'
APP_URL='https://github.com/rozhuk-im/ssdpd'
APP_DESCR='SSDP announce daemon for UPnP 1.1'


# Options.
#BUILD_TYPE='debug'


BUILD_DIR="${THIS_DIR}/build-${BUILD_TYPE}"
SRC_DIR="${THIS_DIR}/src"


CFLAGS="${CFLAGS} -DHAVE_CONFIG_H -DSOCKET_XML_CONFIG -I${THIS_DIR}/3rdparty/liblcb/include"
LIBS="${LIBS} -pthread"


# Global profile.
CFLAGS_OPTIONAL='
-fno-delete-null-pointer-checks'
CFLAGS_OPTIONAL_ONE_OF='
-std=c11 -std=c99'
# Hardening flags
CFLAGS_OPTIONAL="${CFLAGS_OPTIONAL}
-U_FORTIFY_SOURCE
-D_FORTIFY_SOURCE=2
-fsanitize=safe-stack
-fsanitize=cfi
-mretpoline
-fwrapv
-fPIE"
CFLAGS_OPTIONAL_ONE_OF="${CFLAGS_OPTIONAL_ONE_OF}
-fstack-protector-strong -fstack-protector-all"
LDFLAGS_OPTIONAL='
-Wl,-z,retpolineplt
-Wl,-z,relro
-Wl,-z,now
-Wl,-z,noexecstack'

# Release profile.
CFLAGS_RELEASE='-DNDEBUG'
CFLAGS_RELEASE_OPTIONAL='
-funroll-loops'

# Debug profile.
CFLAGS_DEBUG='-g3 -ggdb -O0 -DDEBUG'
CFLAGS_DEBUG_OPTIONAL='
-W
-Wall
-Weverything
-Wpointer-arith
-Wstrict-prototypes
-pedantic
-Wnull-dereference
-Wduplicated-cond
-Wimplicit-fallthrough
-Wno-c23-extensions
-Wno-cast-function-type
-Wno-cast-function-type-mismatch
-Wno-cast-function-type-strict
-Wno-cast-qual
-Wno-date-time
-Wno-documentation
-Wno-documentation-unknown-command
-Wno-format-nonliteral
-Wno-padded
-Wno-implicit-void-ptr-cast
-Wno-reserved-identifier
-Wno-switch-default
-Wno-switch-enum
-Wno-unsafe-buffer-usage
-Wno-unused-macros
-Wno-variadic-macros
-Wno-gnu-zero-variadic-macro-arguments
-Wno-zero-length-array'

# Debug + ASAN profile.
CFLAGS_DEBUG_ASAN="${CFLAGS_DEBUG} -fsanitize=address -fsanitize-recover=address"
CFLAGS_DEBUG_ASAN_OPTIONAL="${CFLAGS_DEBUG_OPTIONAL}"
LDFLAGS_DEBUG_ASAN="${LDFLAGS_DEBUG} -fsanitize=address -fsanitize-recover=address"


FUNC_TO_CHECK='
explicit_bzero
timingsafe_bcmp
memrchr
memmem
strlcpy
strncasecmp
reallocarray
freezero
pipe2
accept4
kqueuex
rtprio
pthread_setname_np
pthread_set_name_np
posix_spawn_file_actions_addclosefrom_np'

SYMBOLS_TO_CHECK='
SOCK_CLOEXEC sys/socket.h
SOCK_NONBLOCK sys/socket.h
fork unistd.h'

CONFIG_H="
eq PACKAGE_NAME		APP_NAME
eq PACKAGE_VERSION	APP_VERSION
tq PACKAGE_STRING	${APP_NAME} ${APP_VERSION}
eq PACKAGE_URL		APP_URL
eq PACKAGE_DESCRIPTION	APP_DESCR"

SRCS='
ssdpd.c
../3rdparty/liblcb/src/net/socket.c
../3rdparty/liblcb/src/net/socket_address.c
../3rdparty/liblcb/src/net/socket_options.c
../3rdparty/liblcb/src/net/utils.c
../3rdparty/liblcb/src/proto/http.c
../3rdparty/liblcb/src/proto/upnp_ssdp.c
../3rdparty/liblcb/src/threadpool/threadpool.c
../3rdparty/liblcb/src/threadpool/threadpool_msg_sys.c
../3rdparty/liblcb/src/threadpool/threadpool_task.c
../3rdparty/liblcb/src/utils/cmd_line_daemon.c
../3rdparty/liblcb/src/utils/sys.c
../3rdparty/liblcb/src/utils/buf_str.c
../3rdparty/liblcb/src/utils/info.c
../3rdparty/liblcb/src/utils/xml.c'



target_build() {

	echo "Building libtls ${LIBTLS_VER_FULL} ${BUILD_TYPE}"

	case "${SYSTEM_NAME}" in
	*BSD|DragonFly)
		CFLAGS="${CFLAGS} -D_BSD_SOURCE -DFREEBSD -I/usr/local/include"
		;;
	Darwin*)
		CFLAGS="${CFLAGS} -D_BSD_SOURCE -DDARWIN -I/usr/local/include"
		;;
	Linux*)
		CFLAGS="${CFLAGS} -D_GNU_SOURCE -DLINUX -D__USE_GNU=1"
		LIBS="${LIBS} -lrt"
		CFLAGS_OPTIONAL="${CFLAGS_OPTIONAL}
-Wno-discarded-qualifiers"
		;;
	esac

	build_profile_appy

	check_c_functions_exists ${FUNC_TO_CHECK}
	# Check macros.
	check_c_symbols_exists ${SYMBOLS_TO_CHECK}

	rm -rf "${BUILD_DIR}"
	mkdir -p "${BUILD_DIR}"
	config_h_generate "${BUILD_DIR}/config.h" ${CONFIG_H}

	c_compiler_compile ${SRCS}
	c_compiler_link_executable "${APP_NAME}" ${SRCS}
}


build_sys_start "${@}"
