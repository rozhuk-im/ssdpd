# SSDPd

[![Build-macOS-latest Actions Status](https://github.com/rozhuk-im/ssdpd/workflows/build-macos-latest/badge.svg)](https://github.com/rozhuk-im/ssdpd/actions)
[![Build-Ubuntu-latest Actions Status](https://github.com/rozhuk-im/ssdpd/workflows/build-ubuntu-latest/badge.svg)](https://github.com/rozhuk-im/ssdpd/actions)
[![Build-PHP Status](https://scrutinizer-ci.com/g/rozhuk-im/ssdpd/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rozhuk-im/ssdpd/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rozhuk-im/ssdpd/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rozhuk-im/ssdpd/?branch=master)


Rozhuk Ivan <rozhuk.im@gmail.com> 2013 - 2021

SSDPd - Announces UPnP/DLNA device across network.
You can use PHP script, nginx config and static files to
build your own UPnP/DLNA server.


## Licence
BSD licence.
Website: http://www.netlab.linkpc.net/wiki/en:software:ssdpd:index


## Features
* can act as UPnP/DLNA ContentDirectory to share multimedia content
* can announce remote UPnP/DLNA devices


## Compilation and Installation
```
sudo apt-get install build-essential git cmake fakeroot
git clone --recursive https://github.com/rozhuk-im/ssdpd.git
cd ssdpd
mkdir build
cd build
cmake ..
make -j
```


UPnP/DLNA PHP server requires
1. nginx with headers_more.
2. PHP with fpm, fileinfo, soap, xml.


## Usage
```
ssdpd [-d] [-v] [-c file]
       [-p PID file] [-u uid|usr -g gid|grp]
 -h           usage (this screen)
 -d           become daemon
 -c file      config file
 -p PID file  file name to store PID
 -u uid|user  change uid
 -g gid|group change gid
 -v           verboce
```


## Setup

### ssdpd
Copy %%ETCDIR%%/ssdpd.conf.sample to %%ETCDIR%%/ssdpd.conf
then replace lan0 with your network interface name.
Add more sections if needed.
Remove IPv4/IPv6 lines if not needed.

Add to /etc/rc.conf:
```
ssdpd_enable="YES"
```

Run:
```
service ssdpd restart
```



### PHP UPnP server

#### PHP
Add to /etc/rc.conf:
```
php_fpm_enable="YES"
```

Run:
```
service php-fpm restart
```


#### nginx
If nginx will serve only upnp then you can:
```
ln -f -s %%ETCDIR%%/nginx-upnp-full.conf %%CMAKE_INSTALL_PREFIX%%/etc/nginx/nginx.conf
```
If nginx build with DSO (dynamic modules load) then you need
uncomment "load_module" and set correth path to module.

Or add to existing nginx.conf following line:
include %%ETCDIR%%/nginx-upnp-handler.conf;
some where in existing http/server section.

Add to /etc/rc.conf:
```
nginx_enable="YES"
```

Run:
```
service nginx restart
```


#### Data
Place shared data in: %%DATADIR%%/www/upnpdata
or make in as simlink on existing data.
Make sure that permissions allow www access.


### Firewall
### ssdpd
Allow all IPv4 with options proto igmp.
Allow all udp dst port 1900.

### PHP UPnP server
Allow in tcp dst port 80.
