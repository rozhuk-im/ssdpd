
set(SSDPD_BIN		ssdpd.c
			liblcb/src/net/socket.c
			liblcb/src/net/socket_address.c
			liblcb/src/net/socket_options.c
			liblcb/src/net/utils.c
			liblcb/src/proto/http.c
			liblcb/src/proto/upnp_ssdp.c
			liblcb/src/threadpool/threadpool.c
			liblcb/src/threadpool/threadpool_msg_sys.c
			liblcb/src/threadpool/threadpool_task.c
			liblcb/src/utils/cmd_line_daemon.c
			liblcb/src/utils/sys.c
			liblcb/src/utils/buf_str.c
			liblcb/src/utils/info.c
			liblcb/src/utils/xml.c)

add_executable(ssdpd ${SSDPD_BIN})
set_target_properties(ssdpd PROPERTIES LINKER_LANGUAGE C)
target_link_libraries(ssdpd ${CMAKE_REQUIRED_LIBRARIES} ${CMAKE_EXE_LINKER_FLAGS})

install(TARGETS ssdpd RUNTIME DESTINATION bin)
