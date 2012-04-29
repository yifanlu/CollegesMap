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

// deletes a student from a database
$db->removeStudent($myself);
$db->removeAllAssociations($myself);

echo json_encode(array('success' => 1, 'message' => 'You have been deleted.'));

?>