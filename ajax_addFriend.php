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

require_once('master.php');

header('Content-type: application/json');

if(!$authenticated){
	die(json_encode(array('success' => 0, 'message' => 'You are not logged in.')));
}

if($invalidparameters){
	die(json_encode(array('success' => 0, 'message' => 'Invalid parameters.')));
}

$request_message_generic = array('message' => 'Add yourself to the college map for your school', 'data' => array());
$request_message_specific = array('message' => 'Add yourself to the college map for '.$curhs->name.' class of '.$curyear, 'data' => array('school' => $curhs->id, 'year' => $curyear));

if(!$writeable){
	die(json_encode(array('success' => 0, 'message' => 'You cannot add this person to this map.', 'request' => $request_message_specific)));
}

$friendid = $_REQUEST['friend'];
if(empty($friendid)){
	die(json_encode(array('success' => 0, 'message' => 'Invalid friend.')));
}

$friend = new Student($friendid);

if($friend->fromDatabase($db)){ // already exists
	die(json_encode(array('success' => 0, 'message' => 'Friend already added.')));
}

if(!$friend->fromFacebook($facebook)){
	die(json_encode(array('success' => 0, 'message' => 'Cannot access friend.', 'request' => $request_message_generic)));
}

$fbfriend = $facebook->api('/'.$friendid);
$college = null;
$concentrations = null;
$incurschool = false;
foreach($fbfriend['education'] as $education){
	if($education['type'] != "High School"){
		if($college){ // more than one college in profile, can't add as friend
			die(json_encode(array('success' => 0, 'message' => 'Choice required.', 'request' => $request_message_specific)));
		}
		$college = new School($education['school']['id']);
		// add concentrations
		$concentrations = $education['concentration'];
	}else{
		if($education['school']['id'] == $curhs->id){
			$incurschool = true;
		}
	}
}
if(!$incurschool){ // not in this school
	die(json_encode(array('success' => 0, 'message' => 'Friend not in same school.', 'request' => $request_message_generic)));
}
if(!$college){ // no college added
	die(json_encode(array('success' => 0, 'message' => 'Friend has no college defined.', 'request' => $request_message_specific)));
}

if(!$college->fromDatabase($db)){
	if(!$college->fromFacebook($facebook)){
		die(json_encode(array('success' => 0, 'message' => 'Cannot load school.', 'request' => $request_message_specific)));
	}
	if($college->latitude == 0 || $college->longitude == 0){
		die(json_encode(array('success' => 0, 'message' => 'Cannot locate school.', 'request' => $request_message_specific)));
	}
	// new school, add to database
	$college->updateDatabase($db);
}
// add friend to hs
$db->addAssociation($friend, $curhs->id, AssociationTypes::HighSchool);
// add friend to college
$db->addAssociation($friend, $college->id, AssociationTypes::College);

// add concentrations
if(!$concentrations){
	foreach($concentrations as $fb_concentration){
		$concentration = new Concentration($fb_concentration['id']);
		if(!$concentration->fromDatabase($db)){ // try getting cached data from db
			// if we fail, this concentration doesn't exist yet
			$concentration->fromFacebook($facebook);
			$concentration->updateDatabase($db);
		}
		$db->addAssociation($friend, $concentration->id, AssociationTypes::Concentration);
	}
}

// add student
$friend->year = $curyear;
$friend->updateDatabase($db);

$feed = array(
	'link' => AppInfo::getPageUrl("/?school=" . $curhs->id . "&year=" . $curyear),
	'picture' => 'http://home.web2go.com/basic/nfpi/3.90/ql/0/0/lg?' . $college->imageurl, // TODO: Get real image proxy, facebook blocks images from their CDN
	'name' => $curhs->name . ' class of ' . $curyear . ' College Map',
	'description' => 'Your friend ' . $myself->name . ' added you to ' . $college->name . ' on the college map for ' . $curhs->name . ' class of ' . $curyear
);

$school = array(
	'id' => $college->id,
	'name' => $college->name,
	'image' => $college->imageurl,
	'location' => array(
		'latitude' => (int)$college->latitude,
		'longitude' => (int)$college->longitude
	)
);

echo json_encode(array('success' => 1, 'message' => 'Friend added.', 'feed' => $feed, 'school' => $school));

?>