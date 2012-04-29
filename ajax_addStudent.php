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

// adds a student to the database

if(!$writeable){
	die(json_encode(array('success' => 0, 'message' => 'You cannot add yourself to this map.')));
}

// get list of high schools for use in forms later
$colleges = array();
$concentrations = array();
foreach($myfb['education'] as $education){
	if($education['type'] != "High School"){
		$colleges[] = $education['school'];
		$concentrations[] = $education['concentration'];
	}
}

if(!isset($_REQUEST['index']) || !array_key_exists($_REQUEST['index'], $colleges)){
	die(json_encode(array('success' => 0, 'message' => 'Invalid school number.')));
}

// remove old data if any
$db->removeAllAssociations($myself);

// get data
if($_REQUEST['index'] == -1){
	$college = new School(0);
}else{
	$college = new School($colleges[$_REQUEST['index']]['id']);
	// add concentration
	foreach($concentrations[$_REQUEST['index']] as $fb_concentration){
		$concentration = new Concentration($fb_concentration['id']);
		if(!$concentration->fromDatabase($db)){ // try getting cached data from db
			// if we fail, this concentration doesn't exist yet
			$concentration->fromFacebook($facebook);
			$concentration->updateDatabase($db);
		}
		$db->addAssociation($myself, $concentration->id, AssociationTypes::Concentration);
	}
}

// add college
if(!$college->fromDatabase($db)){ // try getting cached data from db
	// if we fail, this college doesn't exist yet
	if($college->id == 0){
		$college->name = 'No School';
	}else{
		$college->fromFacebook($facebook);
		$college->updateDatabase($db);
	}
}
$db->addAssociation($myself, $college->id, AssociationTypes::College);

// add highschool
if(!$myhs->fromDatabase($db)){
	$myhs->fromFacebook($facebook);
	$myhs->updateDatabase($db);
}
$db->addAssociation($myself, $myhs->id, AssociationTypes::HighSchool);

// add user
$myself->fromFacebook($facebook); // update information
if(is_numeric($_REQUEST['rank'])){
	$myself->rank = $_REQUEST['rank'];
}
$myself->year = $curyear;
$myself->updateDatabase($db);

// see if we're first
$first = $db->countAssociations($college->id) == 1;

$feed = array(
	'link' => AppInfo::getPageUrl("/?school=" . $curhs->id . "&year=" . $curyear),
	'picture' => 'http://home.web2go.com/basic/nfpi/3.90/ql/0/0/lg?' . $college->imageurl, // TODO: Get real image proxy
	'name' => $curhs->name . ' class of ' . $curyear . ' College Map',
	'description' => $myself-> name . ' was added to ' . $college->name . ' on the college map for ' . $curhs->name . ' class of ' . $curyear
);

echo json_encode(array(
	'success' => 1,
	'message' => 'You have been added.',
	'first' => (bool)$first,
	'feed' => $feed,
	'school' => array(
			'id' => $college->id,
			'name' => $college->name,
			'image' => $college->imageurl,
			'location' => array(
				'latitude' => (int)$college->latitude,
				'longitude' => (int)$college->longitude
			)
		)
));

?>