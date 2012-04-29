var map;
var markers = [];
var infowindows = [];

function initializeMap() {
    var myOptions = {
		center: new google.maps.LatLng(37.09, -95.71),
		zoom: 4,
		mapTypeId: google.maps.MapTypeId.ROADMAP
    };
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
}
function addStudent(){
	// first, do cleanup: hide student list, zoom out map, etc
	$("#spinner").show();
	$("#add_student").button('loading');
	$.ajax({
		type: "POST",
		url: "ajax_addStudent.php",
		data: $("#infoform").serialize()
	}).done(function(json){
		if(!json.success){
			showMessage(json.message,'error');
			return;
		}else{
			showMessage(json.message,'success');
		}
		// post a feed?
		if(json.feed){
			FB.ui({
				method: 'feed',
				link: json.feed.link,
				picture: json.feed.picture,
				name: json.feed.name,
				description: json.feed.description
			}, null);
		}
		var schoolid = json.school.id;
		if(schoolid in markers){
			markers[schoolid].setMap(null);
			markers[schoolid] = null;
		}
		var address;
		if(json.school.location.latitude == 0 && 
			json.school.location.longitude == 0){
			address = json.school.name;
		}
		if(json.first){
			addSchoolToMapWithOptions(json.school,json.school.image,address);
		}else{
			addSchoolToMap(json.school);
			// show information box
			showSchoolInfo(schoolid, markers[schoolid]);
		}
		// change add button to change button
		$("#remove_student").show();
		$("#add_student").button('change');
	}).fail(function(){
		showMessage('A fatal error has occurred.','error');
		$("#add_student").button('reset');
	}).always(function(){
		$("#spinner").hide();
	});
	return true;
}
function removeStudent(){
	$("#spinner").show();
	$("#remove_student").button('loading');
	$.ajax({
		type: "POST",
		url: "ajax_removeStudent.php",
		data: {
			school: schoolid,
			year: schoolyear
		}
	}).done(function(json){
		if(!json.success){
			showMessage(json.message,'error');
			return;
		}else{
			showMessage(json.message,'success');
			$("#remove_student").hide();
			$("#add_student").button('add');
		}
	}).fail(function(){
		showMessage('A fatal error has occurred.','error');
	}).always(function(){
		$("#remove_student").button('reset');
		$("#spinner").hide();
	});
	return true;
}
function addSchoolsToMap(){
	$("#spinner").show();
	$.ajax({
		type: "GET",
		url: "ajax_getSchools.php",
		data: {
			school: schoolid,
			year: schoolyear
		}
	}).done(function(json){
		if(!json.success){
			showMessage(json.message,'error');
			return;
		}
		if (markers) { // delete old markers
			for (var i = 0; i < markers.length; i++) {
				markers[i].setMap(null);
			}
			markers.length = 0;
		}
		for(var i = 0; i < json.schools.length; i++){
			addSchoolToMap(json.schools[i]);
		}
	}).fail(function(){
		showMessage('A fatal error has occurred.','error');
	}).always(function(){
		$("#spinner").hide();
	});
	return true;

}
function addSchoolToMap(school){
	$("#spinner").show();
	var icon = new google.maps.MarkerImage(
		school.image,
		null,
		null,
		null,
		new google.maps.Size(30, 30)
	);
	var position = new google.maps.LatLng(school.location.latitude, school.location.longitude);
	var marker = new google.maps.Marker({
		animation: google.maps.Animation.DROP,
		icon: icon,
		map: map,
		position: position,
		title: school.name
	});
	markers[school.id] = marker;
	google.maps.event.addListener(marker, 'click', function(){ 
		showSchoolInfo(school.id, marker);
	});
	google.maps.event.addListener(marker, 'dblclick', function(){ 
		marker.setMap(null); // remove old marker
		addSchoolToMapWithOptions(school, school.image, school.name);
	});
	$("#spinner").hide();
	return true;
}
function addSchoolToMapWithOptions(school,imageurl,address){
	$("#spinner").show();
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode(
		{
			address: address
		},
		function(results,status){
			var html = '<form class="form-stacked" id="schooloptions"><p>Please confirm the information for <strong>'+school.name+'</strong>.</p><fieldset>';
			var position = new google.maps.LatLng(school.location.latitude, school.location.longitude);
			if(status == google.maps.GeocoderStatus.OK){
				address = results[0].formatted_address;
				position = results[0].geometry.location;
			}else if(address){
				html += '<p>No results found.</p>';
			}
			if(imageurl){
				html += '<div class="clearfix"><label for="imageurl">Icon URL</label><input type="text" name="imageurl" class="span8" id="imageurl'+school.id+'" value="'+imageurl+'"></input></div>';
			}else{
				html += '<input type="hidden" id="imageurl'+school.id+'" name="imageurl" value=""></input>';
			}
			if(address){
				html += '<div class="clearfix"><label for="address">Address</label><input type="text" name="address" class="span8" id="address'+school.id+'" value="'+address+'"></input></div>';
			}else{
				html += '<input type="hidden" id="address'+school.id+'" name="address" value=""></input>';
			}
			html += '<div class="clearfix"><button type="button" class="btn" id="locate'+school.id+'">Locate</button></div><p>Once you have located your school, press the button below to save it.</p><div class="clearfix"><button type="button" class="btn primary" id="confirm'+school.id+'">Confirm</button></div></fieldset></form>';
			var infowindow = new google.maps.InfoWindow({
		    	content: html,
		    	maxWidth: 400
		    });
		    var icon = new google.maps.MarkerImage(imageurl, null, null, null, new google.maps.Size(30, 30));
			var marker = new google.maps.Marker({
				animation: google.maps.Animation.DROP,
				icon: icon,
				map: map,
				position: position,
				title: school.name
			});
			infowindow.open(map, marker);
			// stop loading spinner
			$("#spinner").hide();
			google.maps.event.addListener(infowindow, 'closeclick', function() {
				marker.setMap(null);
				addSchoolToMap(school);
			});
		    google.maps.event.addListener(infowindow, 'domready', function() {
				$("#locate"+school.id).click(function(event){
					addSchoolToMapWithOptions(school,$("#imageurl"+school.id).val(),$("#address"+school.id).val());
					infowindow.close(); // after since we need data
					marker.setMap(null);
				});
				$("#confirm"+school.id).click(function(event){
					// close dialog
					infowindow.close();
					// update information
					school.location.latitude = position.lat();
					school.location.longitude = position.lng();
					school.image = imageurl;
					// save changes
					$.ajax({
						type: "POST",
						url: "ajax_updateSchool.php",
						data: {
							school: schoolid,
							year: schoolyear,
							college: school.id,
							image: school.image,
							latitude: school.location.latitude,
							longitude: school.location.longitude
						}
					}).done(function(json){
						showMessage(json.message,json.success?'success':'error');
					}).fail(function(){
						showMessage('A fatal error has occurred.','error');
					});
					// remove temporary marker
					infowindow.close();
					marker.setMap(null);
					// add permanant marker
					addSchoolToMap(school);
					// show information box
					showSchoolInfo(school.id, markers[school.id]);
				});
			});
		}
	);
	return true;
}
function showSchoolInfo(id,marker) {
	var infowindow;
	$("#spinner").show();
	// close other windows
	for(sid in infowindows){
		infowindows[sid].close();
		if(sid == id){ // check if info window exists for this id
			infowindow = infowindows[id];
		}
	}
	if(!infowindow){
    	var content = 'Loading...';
    	infowindow = new google.maps.InfoWindow({
    		content: content,
    		maxWidth: 400
    	});
    	google.maps.event.addListener(infowindow, 'closeclick', function() {
    		$("#attend_info").slideUp(); // hide attend info
    		// show old content
    		$("#statistics").slideDown();
			$("#information").slideDown();
    	});
    	infowindows[id] = infowindow;
    	$.ajax({
    		type: "GET",
    		url: "ajax_getSchoolInfo.php",
    		data: {
    			school: schoolid,
    			year: schoolyear,
    			college: id
    		}
		}).done(function(json){
			if(!json.success){
				showMessage(json.message,'error');
				infowindow.setContent('Error loading.');
				return;
			}
			var html = '<h2><a href="https://www.facebook.com/'+json.information.id+'" target="_blank">'+json.information.name+'</a></h2><br><strong>Friends Attending: </strong>'+json.information.friendsAttending+'<br><strong>Total Attending: </strong>'+json.information.studentsAttending+'<br><strong>Percent of Class Attending: </strong>'+(json.information.studentsAttending/json.totalStudents*100).toFixed(2)+'%';
			infowindow.setContent(html);
		}).fail(function(){
			showMessage('A fatal error has occurred.','error');
		}).always(function(){
			$("#spinner").hide();
		});
    }else{
    	infowindow = infowindows[id];
		$("#spinner").hide();
    }
	infowindow.open(map, marker);
	showAttending(id);
	return true;
}
function showAttending(id){
	$("#spinner").show();
	$("#attend_info").slideUp();
	$.ajax({
		type: "GET",
		url: "ajax_getAttending.php",
		data: {
			school: schoolid,
			year: schoolyear,
			college: id
		}
	}).done(function(json){
		if(!json.success){
			showMessage(json.message,'error');
			return;
		}
		// hide old boxes
		$("#statistics").slideUp();
		$("#information").slideUp();
		// load attend info
		var html = '<div class="studentlist"><ul id="attendlist">'
		for(var i = 0; i < json.students.length; i++){
			var student = json.students[i];
			html += 
			'<li class="'+(i%2==0?'even ':'odd ')+(student.isFriend?'friend ':'')+'"><a href="https://www.facebook.com/'+student.id+'" target="_blank"><div class="picture"><img src="'+student.image+'" alt="'+student.name+'" /></div><span>'+student.name+'</span></a> ';
			if(student.rank > 0){
				html += '(Rank #'+student.rank+') ';
			}
			if(student.concentrations){
				html += 'is studying ' + student.concentrations;
			}
			html += '</li>';
		}
		html += '</ul></div>';
		$("#attend_info_content").html(html);
		// show attend info
		$("#attend_info").slideDown();
	}).fail(function(){
		showMessage('A fatal error has occurred.','error');
	}).always(function(){
		$("#spinner").hide();
	});
	return true;
}
function showMessage(message,type) {
	if($("#message").is(":visible")){
		$("#message").slideUp();
	}
	$("#message").removeClass('warning error success info');
	if(type){
		$("#message").addClass(type);
	}
	$("#message").text(message);
	$("#message").slideDown().delay(10000).slideUp();
	return true;
}
function addFriend(friendid){
	$("#add_friend_"+friendid).button('loading');
	$.ajax({
		type: "GET",
		url: "ajax_addFriend.php",
		data: {
			school: schoolid,
			year: schoolyear,
			friend: friendid
		}
	}).done(function(json){
		if(json.success){
			showMessage(json.message,'success');
		}
		if(json.feed){
			$("#add_friends_modal").modal('hide');
			FB.ui({
				method: 'feed',
				to: friendid,
				link: json.feed.link,
				picture: json.feed.picture,
				name: json.feed.name,
				description: json.feed.description
			}, null);
			var schoolid = json.school.id;
			if(schoolid in markers){
				markers[schoolid].setMap(null);
				markers[schoolid] = null;
			}
			addSchoolToMap(json.school);
			// show information box
			showSchoolInfo(schoolid, markers[schoolid]);
		}
		if(json.request){ // send a request?
			$("#add_friends_modal").modal('hide');
			showMessage('This person cannot be added without more information. You may send them an invite to add themselves.','info');
			FB.ui({
				method: 'apprequests',
				message: json.request.message,
				to: friendid,
				data: json.request.data,
				title: 'College Maps'
			}, null);
		}
		if(!json.request && !json.feed && json.message){
			showMessage(json.message,'error');
		}
	}).fail(function(){
		showMessage('A fatal error has occurred.','error');
	}).always(function(){
		$("#add_friend_"+friendid).button('reset');
	});
	return true;
}
