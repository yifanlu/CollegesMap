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

$loginurl = $facebook->getLoginUrl(array('scope' => 'user_education_history,user_birthday,friends_education_history,friends_birthday', 'redirect_uri' => AppInfo::getPageUrl("/?school=" . $curhs->id . "&year=" . $curyear)));

if(isset($_REQUEST['request_ids'])){
	$request_ids = explode(',', $_REQUEST['request_ids']);
	foreach ($request_ids as $request_id){
		$full_request_id = $request_id . '_' . $myself->id;
		try {
			$request = $facebook->api('/' . $full_request_id);
			$facebook->api('/' . $full_request_id, 'DELETE'); // delete request
		} catch (FacebookApiException $e) {
			continue;
		}
		$data = json_decode($request['data'], true);
		if(!empty($data)){
			$curhs = new School($data['school']);
			$curyear = $data['year'];
		}
	}
}

if($invalidparameters){
	if(!$authenticated){
		die("<script>top.location.href='".$loginurl."';</script>");
	}else{
		if($curhs->id == 0){
			die("You must add a high school to <a href=\"https://www.facebook.com/profile.php?sk=info&amp;edit=1\">your profile</a> to use this app.");
		}else{
			die("<script>top.location.href='".AppInfo::getPageUrl("/?school=" . $curhs->id . "&year=" . $curyear)."';</script>");
		}
	}
}

// refresh user data from facebook
$myself->fromFacebook($facebook);

// get chosen college
$chosen = $db->getAssociations($myself, AssociationTypes::College);
if($chosen){
	$chosenid = $chosen[0];
}

// get list of high schools for use in forms later
$colleges = array();
$concentrations = array();
$selected = 0;
if($authenticated){
	foreach($myfb['education'] as $education){
		if($education['type'] != "High School"){
			$colleges[] = $education['school'];
			$concentrations[] = $education['concentration'];
			if($education['school']['id'] == $chosenid){
				$selected = array_search($education['school'], $colleges, true);
			}
		}
	}
}
// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());
$app_name = idx($app_info, 'name', '');
?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=1200, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />
		
		<title><?php echo he($app_name); ?></title>
		<link rel="stylesheet" href="stylesheets/bootstrap.css">
		<link rel="stylesheet" href="stylesheets/collegesmap.css" media="Screen">
		
		<meta property="og:title" content="<?php echo he($app_name); ?>" />
		<meta property="og:type" content="website" />
		<meta property="og:url" content="<?php echo he(AppInfo::getUrl("/?school=" . $curhs->id . "&year=" . $curyear)); ?>" />
		<meta property="og:image" content="<?php echo he(AppInfo::getUrl('/logo.png')); ?>" />
		<meta property="og:site_name" content="<?php echo he($app_name); ?>" />
		<meta property="og:description" content="See what colleges your classmates will be going to." />
		<meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />
		
		<script src="javascript/jquery-1.7.1.min.js"></script>
		<script src="javascript/bootstrap-alerts.js"></script>
		<script src="javascript/bootstrap-buttons.js"></script>
		<script src="javascript/bootstrap-modal.js"></script>
		<script src="javascript/bootstrap-twipsy.js"></script>
		<script src="javascript/bootstrap-popover.js"></script>
		<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo AppInfo::mapsKey(); ?>&amp;sensor=false"></script>
	</head>
	<body>
		<div id="fb-root"></div>
		<script>
			window.fbAsyncInit = function() {
				FB.init({
					appId      : '<?php echo AppInfo::appID(); ?>',
					channelUrl : '<?php echo AppInfo::getUrl('/channel.php'); ?>',
					status     : true,
					cookie     : true,
					xfbml      : true
				});
				FB.Canvas.setAutoGrow();
			};
			(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=<?php echo AppInfo::appID(); ?>";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
		</script>
		<script>
			var schoolname = '<?php echo $curhs->name; ?>';
			var schoolid = <?php echo $curhs->id; ?>;
			var schoolyear = <?php echo $curyear; ?>;
			var loadedFriends = false;
		</script>
		<script src="javascript/collegesmap.js"></script>
		<script>
			$(document).ready(function(){
				$("#add_student").popover({
					html: true,
					placement: 'right'
				});
				// add listeners
				$("#authenticate").click(function(event){
					top.location.href = '<?php echo $loginurl; ?>';
				});
				$("#add_student").click(function(event){
					$(this).popover('hide'); // this should be automatic, bug?
					addStudent();
				});
				$("#remove_student").click(function(event){
					removeStudent();
				});
				$("#add_friends").click(function(event){
					$("#add_friends_modal").modal('show');
					if(!loadedFriends){
						$.ajax({
							type: "POST",
							url: "addFriends.php",
							data: {
								school: schoolid,
								year: schoolyear
							},
							dataType: "html"
						}).done(function(html){
							loadedFriends = true;
							$("#add_friends_content").html(html);
							$("[id^=add_friend_]").click(function(event){
								var id = this.id.substr(11);
								addFriend(id);
							});
						});
					}
				});
				$("#add_friends_close").click(function(event){
					$("#add_friends_modal").modal('hide');
				});
				// set up map
				initializeMap();
				addSchoolsToMap();
				// some stuff
			<?php if($myself->fromDatabase($db)): ?>
				$("#add_student").button('change');
			<?php else: ?>
				$("#remove_student").hide();
			<?php endif; ?>
			});
		</script>
		<div id="sidebar">
			<section id="myself">
		<?php if($writeable && $authenticated): ?>
				<h5 class="tab" id="myself_title">Myself</h5>
				<div class="box" id="me">
					<form method="GET" id="infoform" class="form-stacked" action="#">
						<input type="hidden" name="school" value="<?php echo he($curhs->id); ?>" />
						<input type="hidden" name="year" value="<?php echo he($curyear); ?>" />
						<div class="field">
							<label for="name">Name</label>
							<div class="nonedit"><?php echo he($myself->name); ?></div>
						</div>
						<div class="field">
							<label for="school">School</label>
							<?php
							if(count($colleges) > 1){
								foreach($colleges as $i => $college){
									echo '<input type="radio" name="index" onclick="javascript:$(\'#concentration\').text(\''.he(arraytocsv($concentrations[$i])).'\');" value="'.$i.'" '.($i==$selected?'checked':'').'></input> '.he($college['name']).'<br>';
								}
							}else if(is_array($colleges)){
								echo '<input type="hidden" name="index" value="0" />';
								echo '<div class="nonedit">'.he($colleges[0]['name']).'</div>';
							}else{
								echo '<input type="hidden" name="index" value="-1" />';
								echo '<div class="nonedit">No School</div>';
							}
							?>
						</div>
						<div class="field">
							<label for="concentration">Concentration</label>
							<div class="nonedit" id="concentration"><?php echo (is_array($concentrations) && count($concentrations) > 0) ? arraytocsv($concentrations[$selected]) : 'None'; ?></div>
						</div>
						<div class="field">
							<label for="rank">Rank (optional)</label>
							<input type="text" class="span4" name="rank" value="<?php echo $myself->rank > 0 ? he($myself->rank) : ''; ?>"></input>
						</div>
						<div class="field">
							<div class="spinner" id="spinner"></div>
							<div id="action_buttons">
								<button type="button" class="btn danger" data-loading-text="Deleting…" id="remove_student">Delete</button>
								<button type="button" class="btn primary" 
									data-change-text="Change" 
									data-add-text="Add Me" 
									data-loading-text="Adding…" 
									title="Read This!" 
									data-content="You are adding yourself to the class of <strong><?php echo he($curyear); ?></strong> for <strong><?php echo he($curhs->name); ?></strong>. If this isn't right, please change your profile and come back." 
									id="add_student">Add Me</button>
							</div>
						</div>
					</form>
				</div>
		<?php elseif($authenticated): ?>
				<h5 class="tab" id="myself_title">No Touching!</h5>
				<div class="box" id="myself_content">
					<p>Only <?php echo he($curhs->name); ?> students of <?php echo he($curyear); ?> may add themselves to this map. You can still look at how pretty it is.</p>
				</div>
		<?php else: ?>
				<h5 class="tab" id="myself_title">Authentication Required</h5>
				<div class="box" id="myself_content">
					<p>To add yourself to the college map, you must authenticate with Facebook.</p>
					<div class="field">
						<a href="#" class="btn large primary" id="authenticate">Authenticate</a>
					</div>
				</div>
		<?php endif; ?>
			</section>
			<section id="attend_info" style="display:none;">
				<h5 class="tab" id="attend_info_title">Attending This School</h5>
				<div class="scroll box" id="attend_info_content"></div>
			</section>
			<section id="statistics">
				<h5 class="tab" id="statistics_title">Statistics</h5>
				<div class="box" id="statistics_content">
					<div class="stat"><strong><?php echo $db->getTotalCount($curhs, $curyear); ?></strong> registered users.</div>
					<div class="stat"><strong><?php $allcolleges = $db->getCollegeList($curhs, $curyear); echo count($allcolleges); ?></strong> colleges attended.</div>
					<div class="stat"><strong><?php echo he($allcolleges[0]->name); ?></strong> is the most popular college.</div>
				</div>
			</section>
			<section id="information">
				<h5 class="tab" id="information_title">Information</h5>
				<div class="box" id="information_content">
					<p>Welcome to the college map for the <strong><?php echo he($curhs->name); ?></strong> class of <strong><?php echo he($curyear); ?></strong>. Here, you can see what schools your friends are going to and which high school classmates you will meet at your school. To add yourself, make sure the information is correct, and press the button above. If the information is incorrect, please fix it in <a href="https://www.facebook.com/profile.php?sk=info&amp;edit=1">your profile</a>. To add your friends, press the button below.</p>
					<p>
						<button type="button" class="btn success" data-loading-text="Loading…" id="add_friends">Add Friends</button>
					</p>
				</div>
			</section>
		</div>
		<div id="modals">
			<div id="add_friends_modal" class="modal hide fade">
				<div class="modal-header">
					<a href="#" class="close">&times;</a>
					<h3>Add Friends</h3>
				</div>
				<div class="modal-body" id="add_friends_content">
					<div class="spinner"></div><p>Loading…</p>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn secondary" id="add_friends_close">Close</a>
				</div>
			</div>
			<div id="privacy_modal" class="modal hide fade">
				<div class="modal-header">
					<a href="#" class="close">&times;</a>
					<h3>Privacy Policy</h3>
				</div>
				<div class="modal-body">
					<p>We hate long legalese too, so we'll keep it simple.</p>
					<ul>
						<li>We cache the following information about you in our databases: your name, your Facebook ID, your high school graduation year, your school rank (if you provided it), an link to your profile picture, and your education history (from your profile).</li>
						<li>All information we store are for caching purposes only (so the site will load faster), and we will NEVER sell your information.</li>
						<li>If you choose to remove yourself via the "Delete" button, all the information we have about you will be wiped from our databases.</li>
						<li>If you add your friend to the map, this applies to them too.</li>
					</ul>
				</div>
			</div>
			<div id="credits_modal" class="modal hide fade">
				<div class="modal-header">
					<a href="#" class="close">&times;</a>
					<h3>Credits</h3>
				</div>
				<div class="modal-body">
					<p>This has been Yifan's attempt at making a real web app. He designed, coded, and tested this site by himself, so please excuse the quality. You can find his site <a href="http://yifan.lu/" target="_blank">here</a>. UI elements are courtesy of <a href="http://ckrack.github.com/fbootstrapp/" target="_blank">Fbootstrapp</a>. Javascript code is powered by <a href="http://jquery.com/" target="_blank">jQuery</a>. The app icon is from <a href="http://www.famfamfam.com/lab/icons/silk/" target="_blank">FamFamFam silk icon set</a>. Thanks to anyone forgotten. Bug reports should be directed <a href="https://www.facebook.com/CollegesMapCommunity" target="_blank">to the creator directly</a>. If you don't know him… well, your problem will never be solved.</p>
				</div>
			</div>
		</div>
		<div id="content">
			<div class="messagebox" id="message" style="display:none;"></div>
			<div class="map" id="map_canvas"></div>
			<div class="comments">
				<div class="fb-comments" data-href="<?php echo he(AppInfo::getPageUrl('/?school=' . $curhs->id . '&year=' . $curyear)); ?>" data-num-posts="4"></div>
			</div>
		</div>
		<footer>Made with love by <a href="http://yifan.lu/" target="_blank">Yifan Lu</a> &bull; <a href="#" data-controls-modal="privacy_modal" data-backdrop="true" data-keyboard="true">Privacy Policy</a> &bull; <a href="#" data-controls-modal="credits_modal" data-backdrop="true" data-keyboard="true">Credits</a> &bull; <a href="https://www.facebook.com/CollegesMapCommunity" target="_blank">Community</a></footer>
	</body>
</html>
