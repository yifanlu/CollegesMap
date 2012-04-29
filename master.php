<?php
/***
   Colleges Map
   Copyright (C) 2012  Yifan Lu

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
***/

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

require_once('classes.php');
require_once('utils.php');
require_once('database.php');
require_once('sdk/src/facebook.php');

$db = new Database(new PDO(AppInfo::getDatabaseDSN(), null, null, array(PDO::ATTR_PERSISTENT => true)));

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
));

$user_id = $facebook->getUser();
if ($user_id) {
	try {
		// Fetch the viewer's basic information
		$myfb = $facebook->api('/me');
	} catch (FacebookApiException $e) {
		// If the call fails we check if we still have a user. The user will be
		// cleared if the error is because of an invalid accesstoken
		if (!$facebook->getUser()) {
			header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
			exit();
		}
	}
	$authenticated = true;
}else{
	$authenticated = false;
}

if(isset($myfb['error_code'])){
	die('Error connecting to Facebook: '.$myfb['error_code'].': '.$myfb['error_msg']);
}

$myself = new Student($user_id);
if(!$myself->fromDatabase($db)){
	$myself->fromFacebook($facebook);
}

// get my high school data
$hs_associds = $db->getAssociations($myself->id, AssociationTypes::HighSchool);
$myhs = $hs_associds[0];
if(!$myhs){
	if(!isset($myfb['education'])){
		$myhs = new School(0);
	}else{
		foreach($myfb['education'] as $education){
			if($education['type'] == "High School"){
				$hsid = $education['school']['id'];
				break;
			}
		}
		$myhs = new School($hsid);
		if(!$myhs->fromDatabase($db)){
			$myhs->fromFacebook($facebook);
		}
	}
}

// get current page data
$schoolid = $_REQUEST['school'];
$curyear = $_REQUEST['year'];

$curhs = new School($schoolid);
$invalidparameters = false;
if(!isset($_REQUEST['school']) || !isset($_REQUEST['year']) || !$curhs->fromFacebook($facebook) || !is_numeric($curyear) || $curyear < 1900 || $curyear > 2900){
	$invalidparameters = true;
	$curyear = $myself->year;
	$curhs = $myhs;
}

// writable if we are in the current hs
$writeable = $curhs->id == $myhs->id;

?>