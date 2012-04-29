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

if(!$authenticated){
	die("You are not logged in.");
}

// we will use FQL to be faster
$fbfriends = $facebook->api(array('method' => 'fql.multiquery', 'queries' => '{"friends":"SELECT uid2 FROM friend WHERE uid1 = me()","info":"SELECT uid,name,pic_small,education FROM user WHERE uid IN (SELECT uid2 FROM #friends)"}'));

if(empty($fbfriends[1])){
	die("No friends found or cannot load friends list.");
}

function getHighschool($educations){
	foreach($educations as $education){
		if($education['type'] == "High School"){
			return $education['school']['name'];
		}
	}
}
function getCollege($educations){
	foreach($educations as $education){
		if($education['type'] != "High School"){
			return $education['school']['name'];
		}
	}
}

$friends = $fbfriends[1]['fql_result_set'];
?>

<div class="studentlist">
	<ul id="friendslist">
<?php
foreach($friends as $i => $friend){
	echo '<li class="'.($i%2==0?'even':'odd').'">';
	echo '	<div class="action"><button type="button" class="btn primary" data-loading-text="Adding…" id="add_friend_'.$friend['uid'].'">Add</button></div>';
	echo '	<div class="picture"><img src="'.$friend['pic_small'].'" alt="'.$friend['name'].'" /></div>';
	echo '	<div class="name"><span><strong>'.he($friend['name']).'</strong></span><br><span>'.he(getHighschool($friend['education'])).'</span><br><span>'.he(getCollege($friend['education'])).'</span></div>';
	echo '</li>';
}
?>

	</ul>
</div>