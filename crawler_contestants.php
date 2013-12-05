<?php
require_once('config.php');

date_default_timezone_set('Europe/Berlin');

define('VENUE_ENDPOINT', 'https://api.foursquare.com/v2/venues/');

define('LANDLORD_ENDPOINT', 'http://apiv2.landlordgame.com:8080/competitors/competition?oauth_token='.LANDLORD_4SQ_TOKEN);
define('LANDLORD_PORTFOLIO_ENDPOINT', 'http://apiv2.landlordgame.com:8080/players/%fs_user_id%/portfolio?oauth_token='.LANDLORD_4SQ_TOKEN);

function crawlLandlordContestants() {
	echo "Updating contestants from Landlord … ";
	
	$json = json_decode(file_get_contents(LANDLORD_ENDPOINT));
	
	$i = 0;
	$output = [];
	foreach ($json->response->ranking as $rank) {
		$contestant = $rank->contestant;
		
		$name = trim($contestant->name);
		$fs_user_id = intVal($contestant->fsUserId);
		
		$balance = $contestant->balance;
		$output[$fs_user_id] = [
			'fs_user_id' => $fs_user_id,
			'name' => $name,
			'observed' => intVal($balance->observed / 1000),
			'net' => $balance->net,
			'assets' => $balance->assets,
			'cash' => $balance->cash,
			'coins' => $balance->coins,
			'overdraft' => $balance->overdraft
		];
		$i++;
	}
	
	echo "$i contestants\n";
	
	return $output;
}

function crawlLandlordContestantPortfolio($fs_user_id) {
	$url = str_replace('%fs_user_id%', intVal($fs_user_id), LANDLORD_PORTFOLIO_ENDPOINT);
	$json = json_decode(file_get_contents($url));
	
	$assets = $json->response->assets;
	echo "Assets count for ".$fs_user_id.": ".count($assets).PHP_EOL;
	
	return count($assets);
}

function hashValue($net, $assets, $cash, $coins, $overdraft) {
	return hash('md5', $net.$assets.$cash.$coins.$overdraft);
}

$file_db = new PDO('sqlite:stats.sqlite');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stats = crawlLandlordContestants();
$timestamp = strftime('%Y-%m-%d %H:%M:%S');

$existingUsers = [];
$checkSql = "SELECT fs_user_id FROM contestants";
$checkResult = $file_db->query($checkSql);
foreach($checkResult as $result) {
	$existingUsers[$result['fs_user_id']] = true;
}

foreach ($stats as $stat) {
	$fs_user_id = $stat['fs_user_id'];
	$name = $stat['name'];
	
	if (array_key_exists($fs_user_id, $existingUsers) && $existingUsers[$fs_user_id] == true) {
		$userExists = true;
	} else {
		// User wasn't already found during the run time of this script > lookup the database
		$checkSql = "SELECT name FROM contestants WHERE fs_user_id = '$fs_user_id'";
		$checkResult = $file_db->query($checkSql);
		$rows = 0;
		foreach($checkResult as $foo) { $rows++; }
		
		$userExists = ($rows == 0) ? false : true;
		// die('U: '.$fs_user_id.PHP_EOL);
	}
	
	if ($userExists) {
		echo "Updating contestant $name …".PHP_EOL;
		
		$updateContestants = "UPDATE contestants SET name = '$name', checked_on = '$timestamp' WHERE fs_user_id = '$fs_user_id'";
		$file_db->exec($updateContestants);
	} else {
		echo "Inserting contestant $name …".PHP_EOL;
		
		$insertContestants = "INSERT INTO contestants (fs_user_id, name, checked_on) VALUES ('$fs_user_id', '$name', '$timestamp')";			
		$file_db->exec($insertContestants);
	}
	
	$existingUsers[$fs_user_id] = true;
}

// Get the last values for each contestant
$balance = 'SELECT * FROM contestants_balance GROUP BY fs_user_id ORDER BY id DESC';
$result = $file_db->query($balance);
$lastObserved = [];
foreach ($result as $contestant_balance) {
	$hashValue = hashValue(
		$contestant_balance['net'],
		$contestant_balance['assets'],
		$contestant_balance['cash'],
		$contestant_balance['coins'],
		$contestant_balance['overdraft']
	);
	$lastObserved[$contestant_balance['fs_user_id']] = ['observed' => $contestant_balance['observed'], 'hashValue' => $hashValue];
}

// Insert stats
foreach($stats as $stat) {
	$fs_user_id = $stat['fs_user_id'];
	$name = $stat['name'];
	$net = $stat['net'];
	$assets = $stat['assets'];
	$cash = $stat['cash'];
	$coins = $stat['coins'];
	$overdraft = $stat['overdraft'];
	
	
	echo "Updating contestants ${name}'s balance … ";
	
	$contestantObservedSql = "SELECT observed FROM contestants WHERE fs_user_id = '$fs_user_id'";
	$result = $file_db->query($contestantObservedSql);
	$observed = 0;
	foreach ($result as $thisResult) {
		$observed = $thisResult['observed'];
	}
	
	// var_dump($lastObserved[$fs_user_id]['observed']);
	// var_dump($observed);
	
	if ($lastObserved[$fs_user_id]['observed'] != $observed) {
		// contestant checked its balance
		// perhaps fire a push message
		
		// Update contestant
		$updateContestants = "UPDATE contestants SET observed = '".$lastObserved[$fs_user_id]['observed']."' WHERE fs_user_id = '$fs_user_id'";
		$file_db->exec($updateContestants);
		
		echo "has checked its balance … ";
	}
	
	if ($lastObserved[$stat['fs_user_id']]['hashValue'] == hashValue($net, $assets, $cash, $coins, $overdraft)) {
		// nothing changed, skip this update
		echo "nothing changed".PHP_EOL;
		continue;
	}
	$assetsCount = crawlLandlordContestantPortfolio($fs_user_id);
	
	echo number_format($net, 2, ',', '.')." €".PHP_EOL;
	
	$insertStats = "INSERT INTO contestants_balance (fs_user_id, net, assets, cash, coins, overdraft, observed, checked_on, assets_count) VALUES (
		'$fs_user_id', '$net', '$assets', '$cash', '$coins', '$overdraft', '".$stat['observed']."', '$timestamp', '$assetsCount'
	)";			
	$file_db->exec($insertStats);
}
?>
