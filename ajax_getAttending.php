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
	die(json_encode(array('success' => 0, 'message' => 'Invalid college.')));
}
$collegeid = $_REQUEST['college'];

$school = new School($collegeid);
if(!$school->fromDatabase($db)){
	die(json_encode(array('success' => 0, 'message' => 'Cannot find college.')));
}

$students = $db->getStudentsAttending($school, $curhs, $curyear);
$data = array('success' => 1, 'students' => array());

// return a JSON list of students given the college's id
foreach($students as $student){
	$concentrations = arraytocsv($db->getConcentrations($student));
	$data['students'][] = array(
		'id' => $student->id,
		'name' => $student->name,
		'image' => $student->imageurl,
		'rank' => $student->rank,
		'isFriend' => (boolean)$myself->isFriends($facebook, $student),
		'concentrations' => $concentrations
	);
}

echo json_encode($data);

?>