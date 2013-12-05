<?php
require_once('config.php');

$fieldname = (isset($_REQUEST['field'])) ? trim($_REQUEST['field']) : 'net';
$daily = (isset($_REQUEST['daily'])) ? true : false;

$file_db = new PDO('sqlite:stats.sqlite');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all players
$contestants = 'SELECT name,fs_user_id FROM contestants';
$result = $file_db->query($contestants);

$players = [];
foreach($result as $player) { 
	$fs_user_id = $player['fs_user_id'];
	$name = $player['name'];
	
	$players[$fs_user_id] = [
		'fs_user_id' => $fs_user_id,
		'name' => $name, 
		'value' => 'null', 
		'observed' => []
	];
}

// Get the last values for each contestant
$balance = 'SELECT fs_user_id,observed,'.$fieldname.' FROM contestants_balance ORDER BY checked_on';
$result = $file_db->query($balance);
$dateObjectStrings = [];
$lastDateString = '';
foreach ($result as $balance) {
	$fs_user_id = $balance['fs_user_id'];
	$value = $balance[$fieldname];
	$observed = getdate($balance['observed']);
	
	$players[$fs_user_id]['value'] = $value;
	$players[$fs_user_id]['observed'] = $observed;
	
	if ($daily) {
		// [new Date(2008, 1 ,1), 1000, null, null, 2000, null, null, 3000, null, null]
		$dateString = "[new Date(".$observed['year'].",".($observed['mon']-1).",".$observed['mday']."),";
		if ($dateString == $lastDateString) {
			continue;
		}
	} else {
		// [new Date(2008, 1 ,1, 0, 0, 0), 1000, null, null, 2000, null, null, 3000, null, null]
		$dateString = "[new Date(".$observed['year'].",".($observed['mon']-1).",".$observed['mday'].",".$observed['hours'].",".$observed['minutes'].",".$observed['seconds']."),";
	}
	$lastDateString = $dateString;
	
	foreach ($players as $player) {
		$dateString .= $player['value'].',null,null,';
	}
	$dateString = substr($dateString, 0, strlen($dateString)-1).']';
	$dateObjectStrings[] = $dateString;
}

array_splice($dateObjectStrings, 0, count($players)-1);

$playerIndex = 0;
?>

<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Landlord Assistant">
    <meta name="author" content="Jens Kohl">

    <title>Landlord Assistant</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-glyphicons.css" rel="stylesheet">

    <style>
	body {
	  padding-top: 50px;
	}
	.starter-template {
	  padding: 40px 15px;
	  text-align: left;
	}
	</style>
	
	<script type='text/javascript' src='http://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {'packages':['annotatedtimeline']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('<?= ($daily) ? 'date' : 'datetime' ?>', 'Date');
		<?php foreach ($players as $player): ?>
		
        data.addColumn('number', '<?=$player['name']?>');
        data.addColumn('string', 'title<?=++$playerIndex;?>');
        data.addColumn('string', 'text<?=$playerIndex?>');
		<?php endforeach ?>
		
        data.addRows([
		<?= join(",".PHP_EOL."\t\t", $dateObjectStrings) ?>

]);

        var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
        chart.draw(data, {
			displayAnnotations: true,
			displayExactValues: true,
			dateFormat: 'EEEE dd.MM.yyyy HH:mm' 
			});
      }
    </script>
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <span class="navbar-brand">Landlord Assistant</span>
        <div class="nav-collapse collapse">
			<ul class="nav navbar-nav">
				<li><a href="/">Venues</a></li>
				<li><a href="addvenue.php">Add Venue</a></li>
				<li class="active"><a href="#">Contestants</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      <div class="starter-template">
		  <div class="page-header">
			  <h1>Contenstants <small>updated every <span class="label label-default"><?= ($daily) ? 'day' : '5 minutes' ?></span></small></h1>
		  </div>
		
		<ul class="nav nav-tabs">
			<li<?= ($fieldname == 'net') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=net">Net</a></li>
			<li<?= ($fieldname == 'cash') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=cash">Cash</a></li>
			<li<?= ($fieldname == 'assets') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=assets">Assets</a></li>
			<li<?= ($fieldname == 'coins') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=coins">Coins</a></li>
			<li<?= ($fieldname == 'overdraft') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=overdraft">Overdraft</a></li>
			<li<?= ($fieldname == 'assets_count') ? ' class="active"' : '' ?>><a href="<?= $_SERVER['PHP_SELF']; ?>?field=assets_count">Assets count</a></li>
		</ul>
		
		<br/>
		
		<div id='chart_div' style='width: 100%; height: 600px;'></div>
		
      </div>

    </div><!-- /.container -->

  </body>
</html>
