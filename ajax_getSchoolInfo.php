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

if($invalidparameters){
	die(json_encode(array('success' => 0, 'message' => 'Invalid parameters.')));
}

if(!isset($_REQUEST['college']) || !is_numeric($_REQUEST['college'])){
	die(json_encode(array('success' => 0, 'message' => 'Invalid school.')));
}
$schoolid = $_REQUEST['college'];

$school = new School($schoolid);
if(!$school->fromDatabase($db)){
	if(!$school->fromFacebook($facebook)){ // we shouldn't have to do this!
		die(json_encode(array('success' => 0, 'message' => 'Cannot find school.')));
	}
}

$data = array(
	'success' => 1,
	'totalStudents' => $db->getTotalCount($curhs, $curyear),
	'information' => array(
		'id' => $school->id,
		'name' => $school->name,
		'image' => $school->imageurl,
		'location' => array(
			'latitude' => (int)$school->latitude,
			'longitude' => (int)$school->longitude
		),
		'studentsAttending' => $db->getAttendingCount($school, $curhs, $curyear),
		'friendsAttending' => $db->getAttendingCount($school, $curhs, $curyear, $facebook, $myself)
	)
);

echo json_encode($data);

?>