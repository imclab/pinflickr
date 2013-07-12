<?php

$API_KEY = "ffdc6e7cef69d201a7c79bc80477a0ec"; // change this when in prod
$SECRET	 = "a48a5c5114b7ec99"; // change this in prod too

function getFlickrData($SECRET, $API_KEY, $user_id, $tags) {
	$url = "http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=" . $API_KEY . "&user_id=" . $user_id;
	
	// tags should be passed as a comma separated list
	if($tags != ""){
		$url .= "&tags=" . $tags;
	}
	$url .= "&format=json";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl);
	// need to strip this invalid json callback crap
	$dat = str_replace( 'jsonFlickrApi(', '', $res );
	$dat = substr( $dat, 0, strlen( $dat ) - 1 ); //strip out last paren
	$dat = json_decode($dat, TRUE);
	return getFlickrUrls($dat);
}

// title is stored in $pic['title']
function getFlickrUrls($dat){
	$urls = array();
	foreach($dat['photos']['photo'] as $pic){  
		$photo_url	  = 'http://farm' . $pic['farm'] . '.staticflickr.com/' . $pic['server'] . 
						'/' . $pic['id'] . '_' . $pic['secret'] . ".jpg";
		array_push($urls, $photo_url);
	}

	return $urls;
}


?>

