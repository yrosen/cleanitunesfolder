<?php
/**
 * Clean iTunes Folder - v1.0.0
 *
 * This script will scan your iTunes library and your iTunes music folder,
 * and will list the files that appear in the folder but not in the library.
 *
 * It's a good way to keep track of the files you THOUGHT you deleted but
 * are actually still hiding (and taking up space) on your hard drive.
 *
 * This works on WINDOWS only, you'll need PHP for Windows.
 *
 * @package cleanitunesfolder
 * @link    http://github.com/yrosen/cleanitunesfolder
 * @author  Yudi Rosen <yudi42@gmail.com>
 * @license BSD
 */

// Connect to iTunes via the COM interface:
echo "Clean iTunes Folder v1.0.0\r\n";
echo "Connecting to iTunes...";
$itunes		   = new COM("iTunes.Application");
echo "Done!\r\n";

$library       = $itunes->LibraryPlaylist->Tracks;
$numtracks     = $library->Count;

// Always nice to make sure things are clean, even if it's pointless:
$itunestracks  = array();
$librarytracks = array();
$matches	   = array();

//Find our iTunes library dir:
echo "Searching for iTunes music folder (this may take a minute)...";

// I should be ashamed of this, but since I can't use simplexml...
$itlxml = fopen($itunes->LibraryXMLPath, 'r');

while(!feof($itlxml)) {
    $xml = fgets($itlxml);

	if(preg_match('/<key>Music Folder<\/key><string>file:\/\/localhost\/(.+)<\/string>/', $xml, $matches)) {
		break;
	}
}

fclose($itlxml);

if($matches[1]) {
	echo "Done!\r\n";
}
else {
	die("FAILED!\r\n");
}

// And here's our library folder:
$librarydir = urldecode($matches[1]) . 'Music';

// Get our track listing from iTunes:
echo "Loading iTunes library (this may take a few minutes)...";
while($numtracks != 0) {
	$currtrack = $library->Item($numtracks);
	$itunestracks[] = strtolower(trim($currtrack->Location));
	$numtracks--;
}
echo "Done! ({$library->Count} found)\r\n";

// Get our file listing from the library dir:
echo "Loading file list from library folder (this may take a few minutes)...";
ScanFolder($librarydir);

function ScanFolder($dir) {
	$listDir = array();
	global $librarytracks;
		
	if($handler = opendir($dir)) {
		while (($sub = readdir($handler)) !== FALSE) {
			if ($sub != '.' && $sub != '..') {
				if(is_file($dir . '/' . $sub)) {
					$librarytracks[] = $dir . '/' . $sub;
                		}
				elseif(is_dir($dir . '/' . $sub)) {
					$listDir[$sub] = ScanFolder($dir . '/' . $sub);
				}
			}
        }

		closedir($handler);
	}
}

$numfiles = count($librarytracks);
echo "Done! ({$numfiles} found)\r\n";

// Compare the two:
echo "Potentially problematic files found:\r\n";
foreach($librarytracks as $libtrack) {
	$libtrack = strtolower(str_replace('/', '\\', trim($libtrack)));

	$i = 0;
	
	// Ignore .jpg files btw:
	if( (!in_array($libtrack, $itunestracks)) && (substr($libtrack, -3) != 'jpg')) {
		$i++;
		echo $libtrack . "\r\n";
	}
}

echo "{$i} bad files found!\r\n";

// Wait for user before we exit:
echo "Press any key to exit...\r\n";
$handle = fopen ("php://stdin", "r");
if(fgets($handle)) {
    die();
}

//TODO: How do we disconnect from iTunes?
?>