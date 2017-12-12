<?php 
/*-
 * Copyright (c) 2013 - 2017 Rozhuk Ivan <rozhuk.im@gmail.com>
 * All rights reserved.
 * 
 * Subject to the following obligations and disclaimer of warranty, use and
 * redistribution of this software, in source or object code forms, with or
 * without modifications are expressly permitted by Whistle Communications;
 * provided, however, that:
 * 1. Any and all reproductions of the source or object code must include the
 *    copyright notice above and the following disclaimer of warranties; and
 * 2. No rights are granted, in any manner or form, to use Whistle
 *    Communications, Inc. trademarks, including the mark "WHISTLE
 *    COMMUNICATIONS" on advertising, endorsements, or otherwise except as
 *    such appears in the above copyright notice or in the software.
 * 
 * THIS SOFTWARE IS BEING PROVIDED BY WHISTLE COMMUNICATIONS "AS IS", AND
 * TO THE MAXIMUM EXTENT PERMITTED BY LAW, WHISTLE COMMUNICATIONS MAKES NO
 * REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED, REGARDING THIS SOFTWARE,
 * INCLUDING WITHOUT LIMITATION, ANY AND ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.
 * WHISTLE COMMUNICATIONS DOES NOT WARRANT, GUARANTEE, OR MAKE ANY
 * REPRESENTATIONS REGARDING THE USE OF, OR THE RESULTS OF THE USE OF THIS
 * SOFTWARE IN TERMS OF ITS CORRECTNESS, ACCURACY, RELIABILITY OR OTHERWISE.
 * IN NO EVENT SHALL WHISTLE COMMUNICATIONS BE LIABLE FOR ANY DAMAGES
 * RESULTING FROM OR ARISING OUT OF ANY USE OF THIS SOFTWARE, INCLUDING
 * WITHOUT LIMITATION, ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * PUNITIVE, OR CONSEQUENTIAL DAMAGES, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES, LOSS OF USE, DATA OR PROFITS, HOWEVER CAUSED AND UNDER ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF WHISTLE COMMUNICATIONS IS ADVISED OF THE POSSIBILITY
 * OF SUCH DAMAGE.
 *
 * Author: Rozhuk Ivan <rozhuk.im@gmail.com>
 *
 */

/* xml-SOAP MediaServer/ContentDirectory:3 for UPnP/DLNA */
/* http://upnp.org/specs/av/UPnP-av-ContentDirectory-v3-Service.pdf */

 
/* Config.*/
$basedir = dirname(__FILE__)."/../../upnpdata";	/* File system path. */
$baseurl = "/upnpdata";				/* WEB URL path. */

date_default_timezone_set('UTC');


/* File types. */
$file_class = array (
	'm3u' => 'object.container.storageFolder',
	'xspf' => 'object.container.storageFolder',
	'xml' => 'object.container.storageFolder',

	'gif' => 'object.item.imageItem.photo',
	'png' => 'object.item.imageItem.photo',
	'jpg' => 'object.item.imageItem.photo',
	'jpeg' => 'object.item.imageItem.photo',

	'flac' => 'object.item.audioItem.musicTrack',
	'mp3' => 'object.item.audioItem.musicTrack', 
	'wav' => 'object.item.audioItem.musicTrack',
	'wma' => 'object.item.audioItem.musicTrack',

	'flv' => 'object.item.videoItem',
	'f4v' => 'object.item.videoItem',
	'3g2' => 'object.item.videoItem',
	'3gp' => 'object.item.videoItem',
	'3gp2' => 'object.item.videoItem',
	'3gpp' => 'object.item.videoItem',
	'asf' => 'object.item.videoItem',
	'asx' => 'object.item.videoItem',
	'avi' => 'object.item.videoItem.movie',
	'dat' => 'object.item.videoItem',
	'iso' => 'object.item.videoItem',
	'm2t' => 'object.item.videoItem',
	'm2ts' => 'object.item.videoItem',
	'm2v' => 'object.item.videoItem',
	'm4v' => 'object.item.videoItem',
	'mp2v' => 'object.item.videoItem',
	'mp4' => 'object.item.videoItem',
	'mp4v' => 'object.item.videoItem',
	'mpe' => 'object.item.videoItem',
	'mpeg' => 'object.item.videoItem',
	'mpg' => 'object.item.videoItem',
	'mod' => 'object.item.videoItem',
	'mov' => 'object.item.videoItem',
	'mkv' => 'object.item.videoItem.videoBroadcast',
	'mts' => 'object.item.videoItem',
	'swf' => 'object.item.videoItem',
	'vob' => 'object.item.videoItem',
	'ts' => 'object.item.videoItem',
	'wm' => 'object.item.videoItem',
	'wmv' => 'object.item.videoItem',
	'wmx' => 'object.item.videoItem',
);


/* Auto variables. */
if (substr($basedir, -1, 1) !== '/') {
	$basedir = $basedir . '/';
}
$baseurl = implode("/", array_map("rawurlencode", explode("/", $baseurl)));
$baseurlpatch = 'http://'.$_SERVER['HTTP_HOST'].$baseurl;
if (substr($baseurlpatch, -1, 1) !== '/') {
	$baseurlpatch = $baseurlpatch . '/';
}

/**
 * Apply workaround for the libxml PHP bugs:
 * @link https://bugs.php.net/bug.php?id=62577
 * @link https://bugs.php.net/bug.php?id=64938
 */
if (function_exists('libxml_disable_entity_loader')) {
	libxml_disable_entity_loader(false);
}

function xml_encode($string) {

	return (str_replace(
	    array("&",	"<",	">",	/*'"',*/	"'"),
	    array("&amp;",	"&lt;",	"&gt;",	/*"&quot;",*/	"&apos;"), 
	    $string));
}

function xml_decode($string) {

	return (str_replace(
	    array("&amp;",	"&lt;",	"&gt;",	"&quot;",	"&apos;"), 
	    array("&",	"<",	">",	'"',		"'"),
	    $string));
}


function upnp_url_encode($url) {

	if (substr($url, 0, 7) !== 'http://' ||
	    ($url_path_off = strrpos($url, '/', 8)) === $url_path_off)
		return (implode("/", array_map("rawurlencode", explode("/", $url))));
		//return (xml_encode(implode("/", array_map("rawurlencode", explode("/", $url)))));
		//return (xml_encode($url));
		//return ("<![CDATA[" . xml_encode($url) . "]]");

	return (substr($url, 0, $url_path_off).implode("/", array_map("rawurlencode", explode("/", substr($url, $url_path_off)))));
	//return (substr($url, 0, $url_path_off).xml_encode(implode("/", array_map("rawurlencode", explode("/", substr($url, $url_path_off))))));
	//return (substr($url, 0, $url_path_off).xml_encode(substr($url, $url_path_off)));
	//return ("<![CDATA[$url]]");
}


function upnp_get_class($file, $def) {
	global $file_class;

	if (!isset($file))
		return ($def);
	$dot = strrpos($file, '.');
	if (FALSE === $dot)
		return ($def);
	$ext = strtolower(substr($file, ($dot + 1)));
	if (isset($file_class[$ext])) /* Skip unsupported file type. */
		return ($file_class[$ext]);

	return ($def);
}


function get_named_val($name, $buf) { /* ...val_name="value"... */

	$st_off = strpos($buf, $name);
	if (FALSE === $st_off)
		return (null);
	$st_off += strlen($name);
	if (substr($buf, $st_off, 2) !== '="')
		return (null);
	$st_off += 2;
	$en_off = strpos($buf, '"', $st_off);
	if (-1 === $en_off)
		return (null);

	return (substr($buf, $st_off, ($en_off - $st_off)));
}




function upnp_mime_content_type($filename) {

	$def = 'video/mpeg';
	$mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',

		// images
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// audio/video
		'flv' => 'video/x-flv',
		'mp3' => 'audio/mpeg',
		'mp4' => 'video/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'mkv' => 'video/x-mkv',
	);
	if (!isset($filename))
		return ($def);
	$dot = strrpos($filename, '.');
	if (FALSE === $dot)
		return ($def);
	$ext = strtolower(substr($filename, ($dot + 1)));
	if (array_key_exists($ext, $mime_types)) {
		return ($mime_types[$ext]);
	} elseif (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $filename);
		finfo_close($finfo);
		return ($mimetype);
	}

	return ($def);
}


/* ContentDirectory funcs */

function GetSearchCapabilities() {
	$SearchCaps = ""; /* dc:title,upnp:class,upnp:artist */

	return ($SearchCaps);
}


function GetSortCapabilities() {
	$SortCaps = "";
	/* dc:title,upnp:genre,upnp:album,dc:creator,res@size,
	 * res@duration,res@bitrate,dc:publisher,
	 * upnp:originalTrackNumber,dc:date,upnp:producer,upnp:rating,
	 * upnp:actor,upnp:director,dc:description
	 */

	return ($SortCaps);
}


function GetSortExtensionCapabilities() {
	$SortExtensionCaps = "";

	return ($SortExtensionCaps);
}


function GetFeatureList() {
	$FeatureList = "";

	return ($FeatureList);
}


function GetSystemUpdateID() {
	$Id = "1";

	return ($Id);
}


function GetServiceResetToken() {
	$ResetToken = "";

	return ($ResetToken);
}


function Browse($ObjectID, $BrowseFlag, $Filter, $StartingIndex, $RequestedCount,
    $SortCriteria) {
	global $basedir, $baseurl, $file_class, $baseurlpatch;
	$Result =   "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
		    "<DIDL-Lite\n" .
		    " xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
		    " xmlns=\"urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/\"\n" .
		    " xmlns:upnp=\"urn:schemas-upnp-org:metadata-1-0/upnp/\"\n" .
		    " xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
		    " xsi:schemaLocation=\"\n" .
		    "	urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/ http://www.upnp.org/schemas/av/didl-lite.xsd\n" .
		    "	urn:schemas-upnp-org:metadata-1-0/upnp/ http://www.upnp.org/schemas/av/upnp.xsd\">\n";
	$NumberReturned = 0;
	$TotalMatches = 0;
	$UpdateID = 1;

	/* Check input param. */
	if (isset($ObjectID)) {
		if (strlen($ObjectID) === 1 && (
		    substr($ObjectID, 0, 1) === '0' ||
		    substr($ObjectID, 0, 1) === 'V' ||
		    substr($ObjectID, 0, 1) === 'I' ||
		    substr($ObjectID, 0, 1) === 'A')) { /* V, I, A - from X_GetFeatureList() */
			$dir = "";
		} else {
			$dir = rawurldecode(xml_decode($ObjectID));
			if (substr($dir, -1, 1) !== '/') {
				$dir = $dir . '/';
			}
			/* Sec check: .. in path */
			$dotdotdir = '';
			$dirnames = explode('/', $dir);
			for ($di = 0; $di < sizeof($dirnames); $di ++) {
				if ($dirnames[$di] === '..') {
					$dir = "";
					break;
				}
				if ($di < (sizeof($dirnames) - 1)) {
					$dotdotdir = $dotdotdir . $dirnames[$di] . '/';
				}
			}
			$dir = $dotdotdir;
			if (substr($dir, 0, 1) === '/' /*|| !is_dir($basedir.$dir)*/) {
				$dir = "";
			}
		}
	} else {
		$dir = "";
	}
	if (!isset($StartingIndex)) {
		$StartingIndex = 0;
	}

	if (!is_dir($basedir.$dir)) { /* Play list file? */
		/* Open the file. */
		$channels_cnt = 0;
		$dir = substr($dir, 0, (strlen($dir) - 1));
		$fd = fopen($basedir.$dir, "r");
		if (false === $fd) {
			$Result = $Result .
			    "	<container id=\"111111111\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
			    "		<dc:title>$basedir$dir</dc:title>\n" .
			    "		<upnp:class>object.container.storageFolder</upnp:class>\n" .
			    "	</container>\n";
			$NumberReturned ++;
			$TotalMatches ++;
			$Result = $Result . "</DIDL-Lite>\n";
			return (array('Result' => $Result, 'NumberReturned' => $NumberReturned, 'TotalMatches' => $TotalMatches, 'UpdateID' => $UpdateID));
		}
		$date = date("c", filectime($basedir.$dir));

		$logo_url_path = "http://iptvremote.ru/channels/android/160/";
		//$logo_url_path = "http://172.16.0.254/download/tmp/image/";
		while (!feof($fd)) { /* Read the file line by line... */
			$buffer = trim(fgets($fd));
			//if($buffer === false)
			//	break;
			if (false === strpos($buffer, '#EXTINF:')) { /* Skip empty/bad lines. */
				/*if (false !== strpos($buffer, "#EXTM3U")) {
					$logo_url_path = get_named_val("url-tvg-logo", $buffer);
					if (null !== $logo_url_path) {
						if (substr($logo_url_path, -1, 1) !== '/')
							$logo_url_path = $logo_url_path . '/';
					}else{
						$logo_url_path = "http://iptvremote.ru/channels/android/160/";
						$logo_url_path = "http://172.16.0.254/download/tmp/image/";
					}
				}*/
				continue;
			}
			$entry = trim(fgets($fd));
			if (false === strpos($entry, '://'))
				continue;
			if (0 < $StartingIndex) { /* Skip first items. */
				$StartingIndex --;
				continue;
			}
			$title = xml_encode(trim(substr($buffer, (strpos($buffer, ',') + 1))));
			$en_entry = upnp_url_encode($entry);
			$iclass = upnp_get_class($entry, 'object.item.videoItem.videoBroadcast');
			$mimetype = 'video/mpeg';
			$icon_url = "";
			if (!isset($iclass)) {
				$iclass = 'object.item.videoItem';
			}
			
			if ($iclass === 'object.container.storageFolder') { /* Play list as folder! */
				$Result = $Result .
				    "	<container id=\"$en_entry\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
				    "		<dc:title>$title</dc:title>\n" .
				    //"		<upnp:albumArtURI dlna:profileID=\"JPEG_TN\" xmlns:dlna=\"urn:schemas-dlna-org:metadata-1-0\">$icon_url</upnp:albumArtURI>\n" .
				    //"		<upnp:icon>$icon_url</upnp:icon>\n" .
				    "		<upnp:class>object.container.storageFolder</upnp:class>\n" .
				    "	</container>\n";
			} else {
				$logo = get_named_val("tvg-logo", $buffer);
				if (null === $logo) {
					$logo = trim(substr($buffer, (strpos($buffer, ',') + 1)));
				}
				//$icon_url = upnp_url_encode($logo_url_path . mb_convert_case($logo, MB_CASE_LOWER, "UTF-8") . '.png');
				$Result = $Result .
				    "	<item id=\"$en_entry\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
				    "		<dc:title>$title</dc:title>\n" .
				    "		<dc:date>$date</dc:date>\n" .
				    //"		<upnp:albumArtURI dlna:profileID=\"JPEG_TN\" xmlns:dlna=\"urn:schemas-dlna-org:metadata-1-0\">$icon_url</upnp:albumArtURI>\n" .
				    //"		<upnp:icon>$icon_url</upnp:icon>\n" .
				    "		<upnp:class>$iclass</upnp:class>\n" .
				    "		<res protocolInfo=\"http-get:*:$mimetype:*\">$en_entry</res>\n" .
				    "	</item>\n";
			}
			$channels_cnt ++;
		} 
		fclose ($fd);

		$Result = $Result . "</DIDL-Lite>\n";
		$NumberReturned = $channels_cnt;
		$TotalMatches = $channels_cnt;
		return (array('Result' => $Result, 'NumberReturned' => $NumberReturned, 'TotalMatches' => $TotalMatches, 'UpdateID' => $UpdateID));
	}

	$icon_url = "/upnp/icons/dev-icon-48x48.png";
	//$icon_url = implode("/", array_map("rawurlencode", explode("/", "/upnp/icons/dev-icon-48x48.png")));

	/* Scan directory and add to play list.*/
	$entries = scandir($basedir.$dir);
	$channels_cnt = 0;
	/* Add dirs to play list. */
	foreach ($entries as $entry) {
		if (substr($entry, 0, 1) === '.' || !is_dir($basedir.$dir.$entry)) /* Skip files. */
			continue;
		if (0 < $StartingIndex) { /* Skip first items. */
			$StartingIndex --;
			continue;
		}
		$title = xml_encode($entry);
		$en_entry = upnp_url_encode($dir.$entry);
		$Result = $Result .
		    "	<container id=\"$en_entry\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
		    "		<dc:title>$title</dc:title>\n" .
		    //"		<upnp:albumArtURI dlna:profileID=\"JPEG_TN\" xmlns:dlna=\"urn:schemas-dlna-org:metadata-1-0\">$icon_url</upnp:albumArtURI>\n" .
		    //"		<upnp:icon>$icon_url</upnp:icon>\n" .
		    "		<upnp:class>object.container.storageFolder</upnp:class>\n" .
		    "	</container>\n";
		$channels_cnt ++;
	}
	/* Add files to play list. */
	foreach ($entries as $entry) {
		$filename = $basedir.$dir.$entry;
		if (is_dir($filename)) /* Skip dirs. */
			continue;
		$iclass = upnp_get_class($entry, null);
		if (null === $iclass) /* Skip unsupported file type. */
			continue;
		if (0 < $StartingIndex) { /* Skip first items. */
			$StartingIndex --;
			continue;
		}
		$title = xml_encode($entry);
		$en_entry = upnp_url_encode($dir.$entry);
		if ($iclass === 'object.container.storageFolder') { /* Play list as folder! */
			$Result = $Result .
			    "	<container id=\"$en_entry\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
			    "		<dc:title>$title</dc:title>\n" .
			    //"		<upnp:albumArtURI dlna:profileID=\"JPEG_TN\" xmlns:dlna=\"urn:schemas-dlna-org:metadata-1-0\">$icon_url</upnp:albumArtURI>\n" .
			    //"		<upnp:icon>$icon_url</upnp:icon>\n" .
			    "		<upnp:class>object.container.storageFolder</upnp:class>\n" .
			    "	</container>\n";
		} else {
			$en_entry = $baseurlpatch.$en_entry;
			$size = filesize($filename);
			$date = date("c", filectime($filename));
			$mimetype = upnp_mime_content_type($filename);
			$Result = $Result .
			    "	<item id=\"$en_entry\" parentID=\"$ObjectID\" restricted=\"0\">\n" .
			    "		<dc:title>$title</dc:title>\n" .
			    "		<dc:date>$date</dc:date>\n" .
			    //"		<upnp:albumArtURI dlna:profileID=\"JPEG_TN\" xmlns:dlna=\"urn:schemas-dlna-org:metadata-1-0\">$icon_url</upnp:albumArtURI>\n" .
			    //"		<upnp:icon>$icon_url</upnp:icon>\n" .
			    //"		<dc:creator>Rozhuk Ivan</dc:creator>\n" .
			    "		<upnp:class>$iclass</upnp:class>\n" .
			    "		<res size=\"$size\" protocolInfo=\"http-get:*:$mimetype:*\">$en_entry</res>\n" .
			    "	</item>\n";
		}
		$channels_cnt ++;
	}	

	$Result = $Result . "</DIDL-Lite>\n";
	$NumberReturned = $channels_cnt;
	$TotalMatches = $channels_cnt;

	return (array('Result' => $Result, 'NumberReturned' => $NumberReturned, 'TotalMatches' => $TotalMatches, 'UpdateID' => $UpdateID));
}


function Search($ContainerID, $SearchCriteria, $Filter, $StartingIndex, $RequestedCount,
    $SortCriteria) {
	global $basedir, $baseurl, $file_class, $baseurlpatch;
	$Result =   "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
		    "<DIDL-Lite\n" .
		    " xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
		    " xmlns=\"urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/\"\n" .
		    " xmlns:upnp=\"urn:schemas-upnp-org:metadata-1-0/upnp/\"\n" .
		    " xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
		    " xsi:schemaLocation=\"\n" .
		    "	urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/ http://www.upnp.org/schemas/av/didl-lite.xsd\n" .
		    "	urn:schemas-upnp-org:metadata-1-0/upnp/ http://www.upnp.org/schemas/av/upnp.xsd\">\n";
	$NumberReturned = 0;
	$TotalMatches = 0;
	$UpdateID = 1;

	$Result = $Result . "</DIDL-Lite>\n";

	return (array('Result' => $Result, 'NumberReturned' => $NumberReturned, 'TotalMatches' => $TotalMatches, 'UpdateID' => $UpdateID));
}


function CreateObject($ContainerID, $Elements) {
	$ObjectID = "";
	$Result = "";

	return (array('ObjectID' => $ObjectID, 'Result' => $Result));
}


function DestroyObject($ObjectID) {
}


function UpdateObject($ObjectID, $CurrentTagValue, $NewTagValue) {
}


function MoveObject($ObjectID, $NewParentID, $NewObjectID) {
}


/* Samsung private. */
function X_GetFeatureList() {
	$FeatureList = 
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
		"<Features xmlns=\"urn:schemas-upnp-org:av:avs\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:schemas-upnp-org:av:avs http://www.upnp.org/schemas/av/avs.xsd\">\n" .
		"	<Feature name=\"samsung.com_BASICVIEW\" version=\"1\">\n" .
		"		<container id=\"I\" type=\"object.item.imageItem\"/>\n" .
		"		<container id=\"A\" type=\"object.item.audioItem\"/>\n" .
		"		<container id=\"V\" type=\"object.item.videoItem\"/>\n" .
		"		<container id=\"P\" type=\"object.item.playlistItem\"/>\n" .
		"	</Feature>\n" .
		"</Features>\n";

	return ($FeatureList);
}

# $server = new SoapServer(null, array('uri' => "urn:schemas-upnp-org:service:ContentDirectory:3"));
$server = new SoapServer(dirname(__FILE__)."/../descr/ContentDirectory.wdsl",
		array(	'cache_wsdl' => WSDL_CACHE_MEMORY,
			'trace' => true
		));

$server->addFunction(array(	"GetSearchCapabilities",
				"GetSortCapabilities",
				"GetSortExtensionCapabilities",
				"GetFeatureList",
				"GetSystemUpdateID",
				"GetServiceResetToken",
				"Browse",
				"Search",
				"CreateObject",
				"DestroyObject",
				"UpdateObject",
				"MoveObject",
				"X_GetFeatureList"
			)); 
$server->handle(); 


?>
