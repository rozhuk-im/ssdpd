/*-
 * Copyright (c) 2011 - 2017 Rozhuk Ivan <rozhuk.im@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 *
 * Author: Rozhuk Ivan <rozhuk.im@gmail.com>
 *
 */


#include <sys/param.h>

#ifdef __linux__ /* Linux specific code. */
#	define _GNU_SOURCE /* See feature_test_macros(7) */
#	define __USE_GNU 1
#endif /* Linux specific code. */

#include <sys/types.h>
#include <net/if.h>

#include <errno.h>
#include <inttypes.h>
#include <stdio.h> /* snprintf, fprintf */
#include <string.h> /* bcopy, bzero, memcpy, memmove, memset, strerror... */
#include <stdlib.h> /* malloc, exit */
#include <unistd.h> /* close, write, sysconf */
#include <fcntl.h> /* open, fcntl */
#include <signal.h> /* SIGNAL constants. */

#include "utils/macro.h"
#include "utils/mem_utils.h"
#include "utils/str2num.h"
#include "utils/xml.h"
#include "utils/buf_str.h"
#include "utils/sys.h"
#include "utils/log.h"
#include "utils/cmd_line_daemon.h"
#include "net/socket_address.h"
#include "net/utils.h"
#include "proto/upnp_ssdp.h"
#include "config.h"

#define CFG_FILE_MAX_SIZE (1024 * 1024)


#define SSDPD_CFG_CALC_VAL_COUNT(args...)				\
	xml_calc_tag_count_args(cfg_file_buf, cfg_file_buf_size,	\
	    (const uint8_t*)"ssdpd", ##args)
#define SSDPD_CFG_GET_VAL_DATA(next_pos, data, data_size, args...)	\
	xml_get_val_args(cfg_file_buf, cfg_file_buf_size, next_pos,	\
	    NULL, NULL, data, data_size, (const uint8_t*)"ssdpd", ##args)
#define SSDPD_CFG_GET_VAL_UINT(next_pos, val_ret, args...)		\
	xml_get_val_uint32_args(cfg_file_buf, cfg_file_buf_size, next_pos, \
	    val_ret, (const uint8_t*)"ssdpd", ##args)
#define SSDPD_CFG_GET_VAL_SIZE(next_pos, val_ret, args...)		\
	xml_get_val_size_t_args(cfg_file_buf, cfg_file_buf_size, next_pos, \
	    val_ret, (const uint8_t*)"ssdpd", ##args)


int
main(int argc, char *argv[]) {
	int error;
	tp_p tp = NULL;
	upnp_ssdp_p upnp_ssdp = NULL; /* UPnP SSDP server. */
	int log_fd = -1;
	cmd_line_data_t cmd_line_data;


	if (0 != cmd_line_parse(argc, argv, &cmd_line_data)) {
		cmd_line_usage(PACKAGE_DESCRIPTION, PACKAGE_VERSION,
		    "Rozhuk Ivan <rozhuk.im@gmail.com>",
		    PACKAGE_URL);
		return (0);
	}
	if (0 != cmd_line_data.daemon) {
		make_daemon();
	}

    { // process config file
	const uint8_t *data, *ptm, *cur_pos, *cur_svc_pos, *cur_if_pos, *ann_data, *svc_data;
	uint8_t *cfg_file_buf, *cfg_dev_buf;
	size_t cfg_file_buf_size, cfg_dev_buf_size;
	size_t tm, data_size, ann_data_size, svc_data_size;
	const uint8_t *uuid, *domain_name, *type;
	size_t domain_name_size, type_size;
	uint32_t ver, config_id, max_age, ann_interval;
	upnp_ssdp_dev_p dev;
	const uint8_t *if_name, *url4, *url6;
	size_t if_name_size, url4_size, url6_size;
	upnp_ssdp_settings_t ssdp_st;
	uint8_t url4_buf[1024], url6_buf[1024];
	char strbuf[1024];
	struct sockaddr_storage addr;
	tp_settings_t tp_s;


	error = read_file(cmd_line_data.cfg_file_name, 0, 0, 0, CFG_FILE_MAX_SIZE,
	    &cfg_file_buf, &cfg_file_buf_size);
	if (0 != error) {
		g_log_fd = (uintptr_t)open("/dev/stdout", (O_WRONLY | O_APPEND));
		LOG_ERR(error, "config read_file()");
		goto err_out;
	}
	if (0 != xml_get_val_args(cfg_file_buf, cfg_file_buf_size,
	    NULL, NULL, NULL, NULL, NULL,
	    (const uint8_t*)"ssdpd", NULL)) {
		g_log_fd = (uintptr_t)open("/dev/stdout", (O_WRONLY | O_APPEND));
		LOG_INFO("Config file XML format invalid.");
		goto err_out;
	}

	/* Log file */
	if (0 == cmd_line_data.verbose &&
	    0 == SSDPD_CFG_GET_VAL_DATA(NULL, &data, &data_size,
	    "log", "file", NULL)) {
		if (sizeof(strbuf) > data_size) {
			memcpy(strbuf, data, data_size);
			strbuf[data_size] = 0;
			log_fd = open(strbuf, (O_WRONLY | O_APPEND | O_CREAT), 0644);
			if (-1 != log_fd) {
				g_log_fd = (uintptr_t)log_fd;
			} else {
				g_log_fd = (uintptr_t)open("/dev/stderr", (O_WRONLY | O_APPEND));
				LOG_ERR(errno, "Fail to open log file.");
				close((int)g_log_fd);
				g_log_fd = (uintptr_t)-1;
			}
		} else {
			g_log_fd = (uintptr_t)open("/dev/stderr", (O_WRONLY | O_APPEND));
			LOG_ERR(EINVAL, "Log file name too long.");
			close((int)g_log_fd);
			g_log_fd = (uintptr_t)-1;
		}
	} else if (0 != cmd_line_data.verbose) {
		log_fd = open("/dev/stdout", (O_WRONLY | O_APPEND));
		g_log_fd = (uintptr_t)log_fd;
	}
	fd_set_nonblocking(g_log_fd, 1);
	log_write("\n\n\n\n", 4);
	LOG_INFO(PACKAGE_STRING": started");
#ifdef DEBUG
	LOG_INFO("Build: "__DATE__" "__TIME__", DEBUG");
#else
	LOG_INFO("Build: "__DATE__" "__TIME__", Release");
#endif
	LOG_INFO_FMT("CPU count: %d", get_cpu_count());
	LOG_INFO_FMT("descriptor table size: %d (max files)", getdtablesize());

	/* Thread pool settings. */
	tp_def_settings(&tp_s);
	tp_s.flags = 0;
	tp_s.threads_max = 1;
	error = tp_create(&tp_s, &tp);
	if (0 != error) {
		LOG_ERR(error, "tp_create()");
		goto err_out;
	}
	tp_threads_create(tp, 1);// XXX exit rewrite


	/* SSDP receiver. */
	if (0 == SSDPD_CFG_CALC_VAL_COUNT("announceList", "announce", NULL)) {
		LOG_INFO("no announce devices specified, nothink to do...");
		goto err_out;
	}
	/* Default values. */
	upnp_ssdp_def_settings(&ssdp_st);
	/* Read from config. */
	SSDPD_CFG_GET_VAL_UINT(NULL, &ssdp_st.skt_rcv_buf, "skt", "rcvBuf", NULL);
	SSDPD_CFG_GET_VAL_UINT(NULL, &ssdp_st.skt_snd_buf, "skt", "sndBuf", NULL);
	SSDPD_CFG_GET_VAL_UINT(NULL, &ssdp_st.ttl, "skt", "ttl", NULL);
	SSDPD_CFG_GET_VAL_UINT(NULL, &ssdp_st.hop_limit, "skt", "hopLimit", NULL);
	ssdp_st.flags = 0;
	if (0 == SSDPD_CFG_GET_VAL_DATA(NULL, &data, &data_size,
	    "fEnableIPv4", NULL)) {
		yn_set_flag32(data, data_size, UPNP_SSDP_S_F_IPV4, &ssdp_st.flags);
	}
	if (0 == SSDPD_CFG_GET_VAL_DATA(NULL, &data, &data_size,
	    "fEnableIPv6", NULL)) {
		yn_set_flag32(data, data_size, UPNP_SSDP_S_F_IPV6, &ssdp_st.flags);
	}
	if (0 == SSDPD_CFG_GET_VAL_DATA(NULL, &data, &data_size,
	    "fEnableByebye", NULL)) {
		yn_set_flag32(data, data_size, UPNP_SSDP_S_F_BYEBYE, &ssdp_st.flags);
	}
	if (0 == SSDPD_CFG_GET_VAL_DATA(NULL, &data, &data_size,
	    "httpServer", NULL) &&
	    0 != data_size &&
	    (sizeof(ssdp_st.http_server) - 1) > data_size) {
		ssdp_st.http_server_size = data_size;
		memcpy(ssdp_st.http_server, data, data_size);
	}
	/* Create SSDP receiver. */
	error = upnp_ssdp_create(tp, &ssdp_st, &upnp_ssdp);
	if (0 != error) {
		LOG_ERR(error, "upnp_ssdp_create()");
		return (error);
	}

	/* Add Devices to announce on selected ifaces. */
	cur_pos = NULL;
	while (0 == SSDPD_CFG_GET_VAL_DATA(&cur_pos, &ann_data, &ann_data_size,
	    "announceList", "announce", NULL)) {
		if (0 == xml_calc_tag_count_args(ann_data, ann_data_size,
		    (const uint8_t*)"ifList", "if", NULL))
			continue; /* No network interfaces specified. */
		if (0 != xml_get_val_args(ann_data, ann_data_size, NULL,
		    NULL, NULL, &data, &data_size, (const uint8_t*)"xmlDevDescr", NULL))
			continue; /* No xml file with UPnP dev description. */
		error = read_file((const char*)data, data_size, 0, 0, CFG_FILE_MAX_SIZE,
		    &cfg_dev_buf, &cfg_dev_buf_size);
		if (0 != error) {
			LOG_ERR(error, "xmlDevDescr read_file()");
			continue;
		}
		/* Load device options and add. */
		if (0 != xml_get_val_args(cfg_dev_buf, cfg_dev_buf_size, NULL,
		    NULL, NULL, &uuid, &tm,
		    (const uint8_t*)"root", "device", "UDN", NULL) ||
		    (5 + 36) != tm) { /* 5 = "uuid:" */
			LOG_ERR(EINVAL, "Invalid device UUID");
			free(cfg_dev_buf);
			continue;
		}
		uuid += 5; /* Skip "uuid:". */
		if (0 != xml_get_val_args(cfg_dev_buf, cfg_dev_buf_size, NULL,
		    NULL, NULL, &data, &data_size,
		    (const uint8_t*)"root", "device", "deviceType", NULL)) {
no_dev_type:
			LOG_ERR(EINVAL, "No deviceType");
			free(cfg_dev_buf);
			continue;
		}
		/* Parce: "urn:schemas-upnp-org:device:MediaServer:3". */
		domain_name = (data + 4); /* Skip "urn:". */
		ptm = mem_chr_off(5, data, data_size, ':');
		if (NULL == ptm)
			goto no_dev_type;
		domain_name_size = (size_t)(ptm - domain_name);
		type = (ptm + 8); /* Skip ":device:". */
		ptm = mem_chr_ptr(type, data, data_size, ':');
		if (NULL == ptm)
			goto no_dev_type;
		type_size = (size_t)(ptm - type);
		ptm += 1; /* Skip ":". */
		ver = ustr2u32(ptm, (size_t)(data_size - (size_t)(ptm - data)));

		config_id = 1; /* XXX not read. */
		max_age = 0;
		ann_interval = 0;
		xml_get_val_uint32_args(ann_data, ann_data_size, NULL,
		    &max_age, (const uint8_t*)"maxAge", NULL);
		xml_get_val_uint32_args(ann_data, ann_data_size, NULL,
		    &ann_interval, (const uint8_t*)"interval", NULL);
		/* Add device. */
		error = upnp_ssdp_dev_add(upnp_ssdp, (const char*)uuid,
		    (const char*)domain_name, domain_name_size, 
		    (const char*)type, type_size, ver,
		    (uint32_t)time(NULL), config_id, max_age, ann_interval, &dev);
		if (0 != error) {
			LOG_ERR(error, "upnp_ssdp_dev_add()");
			free(cfg_dev_buf);
			continue;
		}
		/* Load services options and add. */
		cur_svc_pos = NULL;
		while (0 == xml_get_val_args(cfg_dev_buf, cfg_dev_buf_size, &cur_svc_pos, 
		    NULL, NULL, &svc_data, &svc_data_size,
		    (const uint8_t*)"root", "device", "serviceList", "service", NULL)) {
			if (0 != xml_get_val_args(svc_data, svc_data_size, NULL,
			    NULL, NULL, &data, &data_size,
			    (const uint8_t*)"serviceType", NULL)) {
no_svc_type:
				LOG_ERR(EINVAL, "No serviceType");
				continue;
			}
			/* Parce: "urn:schemas-upnp-org:service:ContentDirectory:3". */
			domain_name = (data + 4); /* Skip "urn:". */
			ptm = mem_chr_off(5, data, data_size, ':');
			if (NULL == ptm)
				goto no_svc_type;
			domain_name_size = (size_t)(ptm - domain_name);
			type = (ptm + 9); /* Skip ":service:". */
			ptm = mem_chr_ptr(type, data, data_size, ':');
			if (NULL == ptm)
				goto no_svc_type;
			type_size = (size_t)(ptm - type);
			ptm += 1; /* Skip ":". */
			ver = ustr2u32(ptm, (size_t)(data_size - (size_t)(ptm - data)));
			/* Add service to device. */
			error = upnp_ssdp_svc_add(dev,
			    (const char*)domain_name, domain_name_size, 
			    (const char*)type, type_size, ver);
			if (0 != error) {
				LOG_ERR(error, "upnp_ssdp_svc_add()");
				continue;
			}
		}
		free(cfg_dev_buf);
		/* UPnP Device and services added, now add network interfaces. */
		cur_if_pos = NULL;
		while (0 == xml_get_val_args(ann_data, ann_data_size, &cur_if_pos, 
		    NULL, NULL, &data, &data_size,
		    (const uint8_t*)"ifList", "if", NULL)) {
			url4_size = 0;
			url6_size = 0;
			xml_get_val_args(data, data_size, NULL, NULL, NULL,
			    &url4, &url4_size, (const uint8_t*)"DevDescrURL4", NULL);
			xml_get_val_args(data, data_size, NULL, NULL, NULL,
			    &url6, &url6_size, (const uint8_t*)"DevDescrURL6", NULL);
			if (0 != xml_get_val_args(data, data_size, NULL, NULL, NULL,
			    &if_name, &if_name_size, (const uint8_t*)"ifName", NULL) ||
			    (0 == url4_size && 0 == url6_size)) {
				LOG_ERR(EINVAL, "Bad device network interface");
				continue;
			}
			/* Autoreplace NULL addr to if addr. */
			if (14 < url4_size && /* http://0.0.0.0... */
			    0 == mem_cmp_cstr("0.0.0.0", (url4 + 7)) &&
			    (':' == url4[14] || '\\' == url4[14])) {
				LOG_INFO("Autoreplace NULL IPv4 addr to if addr.");
				error = get_if_addr_by_name((const char*)if_name,
				    if_name_size, AF_INET, &addr);
				if (0 != error) {
					LOG_ERR(error, "get_if_addr_by_name()");
					continue;
				}
				error = sa_addr_to_str(&addr, (char*)(url4_buf + 7),
				    (sizeof(url4_buf) - 8), &tm);
				if (0 != error) {
					LOG_ERR(error, "sa_addr_to_str()");
					continue;
				}
				if (sizeof(url4_buf) > (url4_size + (tm - 7))) {
					memcpy(url4_buf, "http://", 7);
					memcpy((url4_buf + 7 + tm),
					    (url4 + 14),
					    (url4_size - 14));
					url4 = url4_buf;
					url4_size += (tm - 7);
					url4_buf[url4_size] = 0;
					LOG_INFO((const char*)url4);
				} else {
					LOG_EV("URL to long, not enough buf space.");
					url4 = NULL;
					url4_size = 0;						
				}
			}
			if (13 < url6_size && /* http://[::]... */
			    0 == mem_cmp_cstr("[::]", (url6 + 7)) &&
			    (':' == url6[11] || '\\' == url6[11])) {
				LOG_INFO("Autoreplace NULL IPv6 addr to if addr.");
				error = get_if_addr_by_name((const char*)if_name,
				    if_name_size, AF_INET6, &addr);
				if (0 != error) {
					LOG_ERR(error, "get_if_addr_by_name()");
					continue;
				}
				error = sa_addr_to_str(&addr, (char*)(url6_buf + 7),
				    (sizeof(url6_buf) - 8), &tm);
				if (0 != error) {
					LOG_ERR(error, "sa_addr_to_str()");
					continue;
				}
				if (sizeof(url6_buf) > (url6_size + (tm - 7))) {
					memcpy(url6_buf, "http://", 7);
					memcpy((url6_buf + 7 + tm),
					    (url6 + 11),
					    (url6_size - 11));
					url6 = url6_buf;
					url6_size += (tm - 4);
					url6_buf[url6_size] = 0;
					LOG_INFO((const char*)url6);
				} else {
					LOG_EV("URL to long, not enough buf space.");
					url6 = NULL;
					url6_size = 0;						
				}
			}
			/* Add service to device. */
			error = upnp_ssdp_dev_if_add(upnp_ssdp, dev,
			    (const char*)if_name, if_name_size,
			    (const char*)url4, url4_size,
			    (const char*)url6, url6_size);
			if (0 != error) {
				LOG_ERR(error, "upnp_ssdp_dev_if_add()");
				continue;
			}
		}
	}
	free(cfg_file_buf);
	upnp_ssdp_send_notify(upnp_ssdp);
    } /* Done with config. */

	if (0 == upnp_ssdp_root_dev_count(upnp_ssdp) ||
	    0 == upnp_ssdp_if_count(upnp_ssdp)) {
		LOG_INFO("no announce devices specified or no network ifaces, nothink to do...");
		goto err_out;
	}

	tp_signal_handler_add_tp(tp);
	signal_install(tp_signal_handler);

	write_pid(cmd_line_data.pid_file_name); /* Store pid to file. */
	set_user_and_group(cmd_line_data.pw_uid, cmd_line_data.pw_gid); /* Drop rights. */

	/* Receive and process packets. */
	tp_thread_attach_first(tp);
	tp_shutdown_wait(tp);

err_out:
	/* Deinitialization... */
	upnp_ssdp_destroy(upnp_ssdp);
	if (NULL != cmd_line_data.pid_file_name) {
		unlink(cmd_line_data.pid_file_name); /* Remove pid file. */
	}
	tp_destroy(tp);
	LOG_INFO("exiting.");
	close(log_fd);

	return (error);
}

