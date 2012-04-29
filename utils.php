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

/**
 * @return the value at $index in $array or $default if $index is not set.
 */
function idx(array $array, $key, $default = null) {
  return array_key_exists($key, $array) ? $array[$key] : $default;
}

function he($str) {
  return htmlentities($str, ENT_QUOTES);
}

function arraytocsv($arr) {
	if(!is_array($arr))
		return "";
	$str = "";
	for($i = 0; $i < count($arr); $i++){
		if(is_array($arr[$i]))
			$str .= $arr[$i]['name'];
		else
			$str .= $arr[$i];
		if($i < count($arr)-1)
			$str .= ", ";
	}
	return $str;
}

function getrealurl($url){
	$headers = get_headers($url);
	foreach($headers as $header){
		if(strpos($header, 'Location: ', 0) > -1){
			return substr($header, strlen('Location: '));
		}
	}
	if(isset($headers['Location']))
		return $headers['Location'];
	else
		return $url;
}
