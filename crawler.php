<?php
require_once('config.php');

define('VENUE_ENDPOINT', 'https://api.foursquare.com/v2/venues/');
define('LANDLORD_ENDPOINT', 'http://apiv2.landlordgame.com:8080/assets/%venue_id%/valuation?oauth_token='.LANDLORD_4SQ_TOKEN);

function crawlVenue($venue_id) {
	echo "Updating $venue_id from Foursquare … ";
	
	$url = VENUE_ENDPOINT.trim($venue_id).'?client_id='.CLIENT_ID.'&client_secret='.CLIENT_SECRET.'&v=20130717';
	
	$json = json_decode(file_get_contents($url));
	
	// Meta
	$name		= trim($json->response->venue->name);
	echo $name."\n";
	$url		= trim($json->response->venue->canonicalUrl);
	
	// Location
	$latitude	= trim($json->response->venue->location->lat);
	$longitude	= trim($json->response->venue->location->lng);
	
	// Stats
	$checkins	= intVal($json->response->venue->stats->checkinsCount);
	$users		= intVal($json->response->venue->stats->usersCount);
	$tips		= intVal($json->response->venue->stats->tipCount);
	$rating		= (isset($json->response->venue->rating)) ? floatval($json->response->venue->rating) : 0.0;
	$likes		= intVal($json->response->venue->likes->count);
	$photos		= intVal($json->response->venue->photos->count);
	$lists		= intVal($json->response->venue->listed->count);
	
	return ['name' => $name, 'canonical_url' => $url, 'checkins' => $checkins, 'users' => $users, 'tips' => $tips, 'crawled_on' => strftime('%Y-%m-%d %H:%M:%S'), 'latitude' => $latitude, 'longitude' => $longitude, 'rating' => $rating, 'likes' => $likes, 'photos' => $photos, 'lists' => $lists];
}

function crawlLandlordVenueValuation($venue_id) {
	echo "Updating $venue_id from Landlord … ";
	
	$url = str_replace('%venue_id%', trim($venue_id), LANDLORD_ENDPOINT);
	
	$json = json_decode(file_get_contents($url));
	
	$owner		= (isset($json->response->valuation->owner)) ? intVal($json->response->valuation->owner) : 0;
	$valuation	= intVal($json->response->valuation->valuation);
	$costs		= intVal($json->response->valuation->costs);
	$avgCheckins = floatval($json->response->valuation->averageCheckins);
	
	if ($valuation+$costs+$avgCheckins < 1) {
		die('Error while crawling landlord api');
	}
	
	echo number_format($valuation, 2, ',', '.')." €".PHP_EOL;
	
	return ['owner' => $owner,'valuation' => $valuation, 'costs' => $costs, 'averageCheckins' => $avgCheckins];
}

$file_db = new PDO('sqlite:stats.sqlite');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$venues = 'SELECT venue_id,crawled_on FROM venues';
$result = $file_db->query($venues);

foreach ($result as $venue) {
	$crawledOn = (!empty($venue['crawled_on'])) ? new DateTime($venue['crawled_on']) : null;
	// $crawledOn = null; // comment out to crawl all venues
	if (!isset($crawledOn)) {
		$d = new DateTime();
		$d->add(DateInterval::createFromDateString('yesterday'));
		$crawledOn = $d;
	}
	$crawledOn->setTime(0, 0, 0);
	
	$venue_id = $venue['venue_id'];
	
	$diff = $crawledOn->diff(new DateTime());
	if ($diff->d > 0) {
		$crawlResult = crawlVenue($venue_id);
		$landlordResult = crawlLandlordVenueValuation($venue_id);
		
		// var_dump($venue_id);
		// var_dump($crawlResult);
		// var_dump($landlordResult);
		// die();
		
		// Update crawledOn field		
		$u = $file_db->prepare("UPDATE venues SET 
			name = ?,
			canonical_url = ?,
			crawled_on = ?,
			latitude = ?,
			longitude = ?
		WHERE venue_id = ?");
		$u->bindValue(1, addslashes($crawlResult['name']), 		SQLITE3_TEXT);
		$u->bindValue(2, $crawlResult['canonical_url'], 		SQLITE3_TEXT);
		$u->bindValue(3, $crawlResult['crawled_on'], 			SQLITE3_TEXT);
		$u->bindValue(4, $crawlResult['latitude'], 				SQLITE3_TEXT);
		$u->bindValue(5, $crawlResult['longitude'], 			SQLITE3_TEXT);
		$u->bindValue(6, $venue_id, 							SQLITE3_TEXT);
		
		$u->execute() or die($file_db->errorInfo());
		
		// Update checkin table		
		$i = $file_db->prepare("INSERT INTO stats (venue_id, timestamp, checkinsCount, usersCount, tipCount, rating, likes, photos, lists, valuation, costs, ll_average_checkins, owner) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
		$i->bindValue( 1, $venue_id, 							SQLITE3_TEXT);
		$i->bindValue( 2, $crawlResult['crawled_on'], 			SQLITE3_TEXT);
		$i->bindValue( 3, $crawlResult['checkins'], 			SQLITE3_INTEGER);
		$i->bindValue( 4, $crawlResult['users'], 				SQLITE3_INTEGER);
		$i->bindValue( 5, $crawlResult['tips'], 				SQLITE3_INTEGER);
		$i->bindValue( 6, $crawlResult['rating'], 				SQLITE3_FLOAT);
		$i->bindValue( 7, $crawlResult['likes'], 				SQLITE3_INTEGER);
		$i->bindValue( 8, $crawlResult['photos'], 				SQLITE3_INTEGER);
		$i->bindValue( 9, $crawlResult['lists'], 				SQLITE3_INTEGER);
		$i->bindValue(10, $landlordResult['valuation'], 		SQLITE3_INTEGER);
		$i->bindValue(11, $landlordResult['costs'], 			SQLITE3_INTEGER);
		$i->bindValue(12, $landlordResult['averageCheckins'],	SQLITE3_FLOAT);
		$i->bindValue(13, $landlordResult['owner'], 			SQLITE3_INTEGER);

		$i->execute() or die($file_db->errorInfo());
	}
}
?>
