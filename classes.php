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

class School {
	public $id;
	public $name;
	public $imageurl;
	public $latitude;
	public $longitude;

	function __construct($id) {
		$this->id = $id;
	}

	function fromFacebook($facebook){
		try {
			$data = $facebook->api('/' . $this->id);
		} catch(FacebookApiException $e) {
			return false;
		}
		$this->name = $data['name'];
		$this->imageurl = $data['picture'];
		$this->latitude = $data['location']['latitude'];
		$this->longitude = $data['location']['longitude'];
		if(!$this->imageurl){
			$this->imageurl = AppInfo::getUrl('/images/unknown.gif');
		}
		return true;
	}
	
	function fromDatabase($database){
		return $database->fillSchool($this);
	}
	
	function updateDatabase($database){
		return $database->addSchool($this);
	}
}

class Concentration {
	public $id;
	public $name;

	function __construct($id) {
		$this->id = $id;
	}

	function fromFacebook($facebook){
		try {
			$data = $facebook->api('/' . $this->id);
		} catch(FacebookApiException $e) {
			return false;
		}
		$this->name = $data['name'];
		return true;
	}
	
	function fromDatabase($database){
		return $database->fillConcentration($this);
	}
	
	function updateDatabase($database){
		return $database->addConcentration($this);
	}
}

class Student {
	public $id;
	public $name;
	public $year;
	public $rank;
	public $imageurl;
	
	function __construct($id) {
		$this->id = $id;
	}
	
	function fromFacebook($facebook){
		try {
			$data = $facebook->api('/' . $this->id);
		} catch(FacebookApiException $e) {
			return false;
		}
		$this->name = $data['name'];
		$this->year = $this->estYearFromEducation($data['education']);
		if(!$this->year)
			$this->year = $this->estYearFromBirth($data['birthday']);
		$this->imageurl = 'https://graph.facebook.com/' . $this->id . '/picture';
		if(!$this->rank)
			$this->rank = 0;
		return true;
	}
	
	function fromDatabase($database){
		return $database->fillStudent($this);
	}
	
	function updateDatabase($database){
		return $database->addStudent($this);
	}
	
	function estYearFromEducation($educations){
		foreach($educations as $education){
			if($education['type'] == "High School"){
				if($education['year']){
					return $education['year']['name'];
				}
			}
		}
		return null;
	}
	
	function estYearFromBirth($datestr){
		if(!$datestr)
			return 0;
		$birthdate = DateTime::createFromFormat("m/d/Y", $datestr);
		if(!$birthdate)
			return 2000;
		$birthmonth = $birthdate->format("m");
		$birthyear = $birthdate->format("Y");
		if($birthmonth >= 9){
			$birthyear++;
		}
		return $birthyear + 19;
	}
	
	function isFriends($facebook, $student){
		try {
			$data = $facebook->api('/' . $this->id . '/friends/' . $student->id );
		} catch(FacebookApiException $e) {
			return false;
		}
		return isset($data['data'][0]['id']);
	}
}

?>