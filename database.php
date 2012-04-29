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

abstract class AssociationTypes {
	const HighSchool = 'HighSchool';
	const College = 'College';
	const Concentration = 'Concentration';
	const Student = 'Student';
}

class Database {
	private $db;
	
	function __construct($db) {
		$this->db = $db;
		//$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	}
	
	function countRows($table, $id){
		$st = $this->db->prepare("SELECT COUNT(id) FROM ".$table." WHERE id = ?");
		$st->bindValue(1, $id, PDO::PARAM_STR);
		$st->execute();
		$count = $st->fetchAll(PDO::FETCH_COLUMN);
		return $count[0];
	}
	
	function fillSchool($school){
		$st = $this->db->prepare("SELECT id, name, imageurl, latitude, longitude FROM schools WHERE id = ? LIMIT 1");
		$st->bindValue(1, $school->id, PDO::PARAM_INT);
		$st->setFetchMode(PDO::FETCH_INTO, $school);
		$st->execute();
		$st->fetch();
		return $this->countRows('schools', $school->id);
	}
	
	function fillConcentration($concentration){
		$st = $this->db->prepare("SELECT id, name FROM concentrations WHERE id = ? LIMIT 1");
		$st->bindValue(1, $concentration->id, PDO::PARAM_INT);
		$st->setFetchMode(PDO::FETCH_INTO, $concentration);
		$st->execute();
		$st->fetch();
		return $this->countRows('concentrations', $concentration->id);
	}
	
	function fillStudent($student){
		$st = $this->db->prepare("SELECT id, name, year, rank, imageurl FROM students WHERE id = ? LIMIT 1");
		$st->bindValue(1, $student->id, PDO::PARAM_INT);
		$st->setFetchMode(PDO::FETCH_INTO, $student);
		$st->execute();
		$st->fetch();
		return $this->countRows('students', $student->id);
	}

	function addSchool($school){
		$st1 = $this->db->prepare("DELETE FROM schools WHERE id = ?");
		$st2 = $this->db->prepare("INSERT INTO schools (id, name, imageurl, latitude, longitude) VALUES (:id, :name, :imageurl, :latitude, :longitude)");
		$st1->execute(array($school->id));
		$st2->execute(array('id' => $school->id, 'name' => $school->name, 'imageurl' => $school->imageurl, 'latitude' => $school->latitude, 'longitude' => $school->longitude));
		return $st2->rowCount();
	}

	function addConcentration($concentration){
		$st = $this->db->prepare("INSERT INTO concentrations (id, name) VALUES (:id, :name)");
		$st->execute(array('id' => $concentration->id, 'name' => $concentration->name));
		return $st->rowCount();
	}

	function addStudent($student){
		$this->removeStudent($student); // remove old data
		$st = $this->db->prepare("INSERT INTO students (id, name, year, rank, imageurl) VALUES (:id, :name, :year, :rank, :imageurl)");
		$st->execute(array('id' => $student->id, 'name' => $student->name, 'year' => $student->year, 'rank' => $student->rank, 'imageurl' => $student->imageurl));
		return $st->rowCount();
	}
	
	function removeAllAssociations($student){
		$st = $this->db->prepare("DELETE FROM associations WHERE studentid = ?");
		$st->execute(array($student->id));
		return $st->rowCount();
	}
	
	function removeStudent($student){
		$st = $this->db->prepare("DELETE FROM students WHERE id = ?");
		$st->execute(array($student->id));
		return $st->rowCount();
	}
	
	function addAssociation($student, $associd, $type){
		$st = $this->db->prepare("INSERT INTO associations (studentid, associd, type) VALUES (:studentid, :associd, :type)");
		$st->execute(array('studentid' => $student->id, 'associd' => $associd, 'type' => $type));
		return $st->rowCount();
	}
	
	function getAssociations($student, $type){
		$st = $this->db->prepare("SELECT associd FROM associations WHERE studentid = ? AND type = ?");
		$st->bindValue(1, $student->id, PDO::PARAM_INT);
		$st->bindValue(2, $type, PDO::PARAM_STR);
		$st->execute();
		return $st->fetchAll(PDO::FETCH_COLUMN);
	}
	
	function countAssociations($associd){
		$st = $this->db->prepare("SELECT COUNT(associd) FROM associations WHERE associd = ?");
		$st->bindValue(1, $associd, PDO::PARAM_INT);
		$st->execute();
		$count = $st->fetchAll(PDO::FETCH_COLUMN);
		return $count[0];
	}
	
	function getCollegeList($highschool, $year){
		$st = $this->db->prepare("SELECT colleges.id, colleges.name, colleges.imageurl, colleges.latitude, colleges.longitude, COUNT(colleges.id) AS attendcount FROM students INNER JOIN associations AS hsassoc ON students.id = hsassoc.studentid INNER JOIN associations AS collegeassoc ON students.id = collegeassoc.studentid INNER JOIN schools AS colleges ON colleges.id = collegeassoc.associd WHERE collegeassoc.type = 'College' AND hsassoc.type = 'HighSchool' AND students.year = :year AND hsassoc.associd = :hsid GROUP BY colleges.id, colleges.name, colleges.imageurl, colleges.latitude, colleges.longitude ORDER BY attendcount DESC");
		$st->bindValue('year', $year, PDO::PARAM_INT);
		$st->bindValue('hsid', $highschool->id, PDO::PARAM_INT);
		$st->execute();
		$colleges = array();
		while($row = $st->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)){
			$college = new School($row[0]);
			$college->name = $row[1];
			$college->imageurl = $row[2];
			$college->latitude = $row[3];
			$college->longitude = $row[4];
			$colleges[] = $college;
		}
		return $colleges;
	}
	
	function getTotalCount($highschool, $year){
		$st = $this->db->prepare("SELECT COUNT(colleges.id) AS attendcount FROM students INNER JOIN associations AS hsassoc ON students.id = hsassoc.studentid INNER JOIN associations AS collegeassoc ON students.id = collegeassoc.studentid INNER JOIN schools AS colleges ON colleges.id = collegeassoc.associd WHERE collegeassoc.type = 'College' AND hsassoc.type = 'HighSchool' AND students.year = :year AND hsassoc.associd = :hsid GROUP BY colleges.id, colleges.name, colleges.imageurl, colleges.latitude, colleges.longitude");
		$st->bindValue('year', $year, PDO::PARAM_INT);
		$st->bindValue('hsid', $highschool->id, PDO::PARAM_INT);
		$st->execute();
		$column = $st->fetchAll(PDO::FETCH_COLUMN);
		return array_sum($column);
	}
	
	function getStudentsAttending($college, $highschool, $year, $facebook = null, $friend=null){
		$st = $this->db->prepare("SELECT students.id, students.name, students.rank, students.imageurl, collegeassoc.associd FROM students INNER JOIN associations AS hsassoc ON students.id = hsassoc.studentid INNER JOIN associations AS collegeassoc ON students.id = collegeassoc.studentid WHERE collegeassoc.type = 'College' AND hsassoc.type = 'HighSchool' AND students.year = :year AND  hsassoc.associd = :hsid AND collegeassoc.associd = :collegeid ORDER BY students.name ASC");
		$st->bindValue('year', $year, PDO::PARAM_INT);
		$st->bindValue('hsid', $highschool->id, PDO::PARAM_INT);
		$st->bindValue('collegeid', $college->id, PDO::PARAM_INT);
		$st->execute();
		$students = array();
		while($row = $st->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)){
			$student = new Student($row[0]);
			$student->name = $row[1];
			$student->rank = $row[2];
			$student->imageurl = $row[3];
			if($friend){
				if(!$student->isFriends($facebook, $friend)){
					return;
				}
			}
			$students[] = $student;
		}
		return $students;
	}
	
	function getAttendingCount($college, $highschool, $year, $facebook = null, $friend=null){
		$students = $this->getStudentsAttending($college, $highschool, $year, $facebook, $friend);
		return count($students);
	}
	
	function getConcentrations($student){
		$st = $this->db->prepare("SELECT concentrations.name FROM concentrations LEFT JOIN associations ON concentrations.id = associations.associd WHERE associations.studentid = :studentid ORDER BY concentrations.name ASC");
		$st->bindValue('studentid', $student->id, PDO::PARAM_INT);
		$st->execute();
		return $st->fetchAll(PDO::FETCH_COLUMN);
	}
}

?>