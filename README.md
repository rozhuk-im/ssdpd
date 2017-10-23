SSDPd (c) Rozhuk Ivan <rozhuk.im@gmail.com> 2013 - 2017
BSD licence. Website: http://www.netlab.linkpc.net/wiki/en:software:ssdpd:index


SSDPd - Announces UPnP/DLNA device across network.


usage: [-d] [-v] [-c file]
       [-p PID file] [-u uid|usr -g gid|grp]
 -h           usage (this screen)
 -d           become daemon
 -c file      config file
 -p PID file  file name to store PID
 -u uid|user  change uid
 -g gid|group change gid
 -v           verboce


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

The file root.xml
root / device:
friendlyName - the display name of UPnP devices;
UDN - UUID device id, it needs to be changed only if you want more than
one device (or someone already took it);
presentationURL - there you can specify a page with a description or the admin

root / device / iconList / icon:
url - URL to png icon 48x48;

root / device / serviceList / service:
SCPDURL - URL to the CML file with the description;
controlURL - URL which will arrive HTTP POST requests to the services;
eventSubURL - URL will go to HTTP SUBSCRIBE / UNSUBSCRIBE, can be left blank.
root.xml described three services, you need to edit for all three!

