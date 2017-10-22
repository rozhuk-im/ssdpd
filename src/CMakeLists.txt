SET(SSDPD_BIN		ssdpd.c
			liblcb/src/net/socket.c
			liblcb/src/net/socket_address.c
			liblcb/src/net/utils.c
			liblcb/src/proto/http.c
			liblcb/src/proto/upnp_ssdp.c
			liblcb/src/threadpool/threadpool.c
			liblcb/src/threadpool/threadpool_msg_sys.c
			liblcb/src/threadpool/threadpool_task.c
			liblcb/src/utils/cmd_line_daemon.c
			liblcb/src/utils/helpers.c
			liblcb/src/utils/info.c
			liblcb/src/utils/log.c
			liblcb/src/utils/xml.c)

ADD_EXECUTABLE(ssdpd ${SSDPD_BIN})
SET_TARGET_PROPERTIES(ssdpd PROPERTIES LINKER_LANGUAGE C)
TARGET_LINK_LIBRARIES(ssdpd ${SSDPD_REQUIRED_LIBRARIES})

INSTALL(TARGETS ssdpd RUNTIME DESTINATION bin)

