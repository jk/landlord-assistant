<?php
require_once('config.php');

$venue = (isset($_REQUEST['venue'])) ? trim($_REQUEST['venue']) : '4adcf782f964a520016321e3';

$file_db = new PDO('sqlite:stats.sqlite');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$goodDay = ['good' => 0, 'bad' => 0];
$template = ['checkins' => 0, 'good' => 0, 'bad' => 0, 'goodValue' => 0];
$weekDayCheckins = [];
for ($i=0; $i <= 6; $i++) { 
	$weekDayCheckins[$i] = $template;
}

// Get the last values for each contestant
$stats = "SELECT s.timestamp,v.name,s.checkinsCount,s.usersCount,s.tipCount,s.rating,s.likes,s.photos,s.lists,s.valuation,s.costs,s.ll_average_checkins,s.owner FROM stats as s, venues as v WHERE s.venue_id = '".$venue."' AND s.venue_id=v.venue_id ORDER BY s.timestamp";

$result = $file_db->query($stats);
$dateObjectStrings = [];
$graph2 = [];
$lastCheckinsCount = 0;
$lastUsersCount = 0;
$i = 0;
foreach ($result as $stat) {
	$observed = getdate(strtotime($stat['timestamp']));
	$name = $stat['name'];
	
	$checkinsCount			= $stat['checkinsCount'];
	$usersCount				= $stat['usersCount'];
	$tipCount				= $stat['tipCount'];
	$rating					= $stat['rating'];
	$valuation				= $stat['valuation'];
	$ll_average_checkins	= $stat['ll_average_checkins'];
	$costs					= $stat['costs'];
	
	$diffCheckinsCount		= abs($checkinsCount - $lastCheckinsCount);
	$lastCheckinsCount		= $checkinsCount;
	
	$diffUsersCount			= abs($usersCount - $lastUsersCount);
	$lastUsersCount			= $usersCount;
	
	if ($i != 0) {
		$weekDayCheckins[$observed['wday']]['checkins'] += $diffCheckinsCount;
	}
	
	if ($i != 0 && goodDay($diffCheckinsCount, $ll_average_checkins)) {
		$goodDay['good']++;
		$weekDayCheckins[$observed['wday']]['good'] += 1;
	} else {
		$goodDay['bad']++;
		$weekDayCheckins[$observed['wday']]['bad'] += 1;
	}
	
	
	
	// [new Date(2008, 1 ,1), checkins]
	$dateString = "[new Date(".$observed['year'].",".($observed['mon']-1).",".$observed['mday']."),$valuation,$costs]";
	$graph2String = "[new Date(".$observed['year'].",".($observed['mon']-1).",".$observed['mday']."),$diffCheckinsCount,$ll_average_checkins,$diffUsersCount]";
	$dateObjectStrings[] = $dateString;
	$graph2[] = $graph2String;
	$i++;
}

array_splice($dateObjectStrings, 0, 1);
array_splice($graph2, 0, 1);
// $dateObjectStrings = array_reverse($dateObjectStrings);
// array_pop($dateObjectStrings);
$playerIndex = 0;


function f($value, $dec = 0, $dec_point = ',', $thousands_sep = '.') {
	return number_format($value, $dec, $dec_point, $thousands_sep);
}

function goodDay($diff, $avg) {
	$compareValue = $diff * 0.9;
	return ($compareValue >= $avg);
}

function checkinsColor($diff, $avg) {
	$badColor = 'red';
	$goodColor = 'green';
	
	return (goodDay($diff, $avg)) ? $goodColor : $badColor;
}

$goodDayValue = $goodDay['good'] / ($goodDay['good'] + $goodDay['bad']) * 100;

for ($i=0; $i <= 6; $i++) { 
	$weekDayCheckins[$observed['wday']]['goodDayValue'] = $weekDayCheckins[$observed['wday']]['good'] / ( $weekDayCheckins[$observed['wday']]['good'] + $weekDayCheckins[$observed['wday']]['bad'] ) * 100;
}

// var_dump($weekDayCheckins);
?>
<html>
  <head>
	  <style>
	  body {
		  font-family: Helvetica, sans-serif;
		  font-size: 12pt;
	  }
	  td {
		  padding-left: 5px;
		  padding-right: 5px;
	  }
	  </style>
	<script type='text/javascript' src='http://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {'packages':['annotatedtimeline']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Date');
		data.addColumn('number', 'Valuation');
		data.addColumn('number', 'Costs');
		
        data.addRows([
		<?= join(",".PHP_EOL."\t\t", $dateObjectStrings) ?>

]);

        var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
        chart.draw(data, {
			scaleType: 'allmaximized',
			displayAnnotations: false,
			displayExactValues: true,
			dateFormat: 'EEEE dd.MM.yyyy'
		});
		
		// graph 2
        var data2 = new google.visualization.DataTable();
        data2.addColumn('date', 'Date');
		data2.addColumn('number', 'Checkins (Diff)');
		data2.addColumn('number', 'Checkins (Avg)');
		data2.addColumn('number', 'Users (Diff)');
		
        data2.addRows([
		<?= join(",".PHP_EOL."\t\t", $graph2) ?>

]);
		
		var graph2 = new google.visualization.AnnotatedTimeLine(document.getElementById('graph2_div'));
		graph2.draw(data2, {
			displayAnnotations: false,
			displayExactValues: true,
			dateFormat: 'EEEE dd.MM.yyyy'
		});
      }
    </script>
  </head>

  <body>
	  <h3><?=$name?></h3>
	<div style="margin-bottom: 5px">
		<table border=1>
			<thead>
				<tr>
					<td>Valuation</td>
					<td>Cost</td>
					<td>Checkins</td>
					<td>Checkins (Diff)</td>
					<td>Checkins (Avg)</td>
					<td>Users</td>
					<td>4SQ Rating</td>
					<td>Good Days</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td align="right"><?= f($valuation, 2) ?></td>
					<td align="right"><?= f($costs, 2) ?></td>
					<td align="right"><?= f($checkinsCount, 0) ?></td>
					<td align="right"><span style="font-weight: bold; color: <?=checkinsColor($diffCheckinsCount, $ll_average_checkins)?>"><?= f($diffCheckinsCount, 0) ?></span></td>
					<td align="right"><?= f($ll_average_checkins, 1) ?></td>
					<td align="right"><?= f($usersCount, 0) ?></td>
					<td align="right"><?= f($rating, 3) ?></td>
					<td align="right"><?= f($goodDayValue, 1) ?> %</td>
				</tr>
			</tbody>
		</table>
	</div>
    <div id='chart_div' style='width: 100%; height: 30%;'></div>
	<div id='graph2_div' style='width: 100%; height: 58%;'></div>
  </body>
</html>
