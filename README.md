SSDPd
========
Rozhuk Ivan <rozhuk.im@gmail.com> 2013 - 2017

SSDPd - Announces UPnP/DLNA device across network.
You can use PHP script, nginx config and static files to
build your own UPnP/DLNA server.


## Licence
BSD licence.
Website: http://www.netlab.linkpc.net/wiki/en:software:ssdpd:index


## Features
* can act as UPnP/DLNA ContentDirectory to share multimedia content
* can announce some remote UPnP/DLNA devices


## Compilation and Installation
```nohighlight
sudo apt-get install build-essential git fakeroot dpkg-dev
git clone --recursive https://github.com/rozhuk-im/ssdpd.git
cd ssdpd
mkdir build
cd build
cmake ..
make
```


## Setting

see ssdpd.conf

XML files are spread on a web server, do so were available from the network,
the server must give the correct content type:
Content-Type: text / xml; charset = "utf-8"
next you can put 48x48 png image, it will be displayed at the user device as icons.


## Usage
ssdpd [-d] [-v] [-c file]
       [-p PID file] [-u uid|usr -g gid|grp]
 -h           usage (this screen)
 -d           become daemon
 -c file      config file
 -p PID file  file name to store PID
 -u uid|user  change uid
 -g gid|group change gid
 -v           verboce

