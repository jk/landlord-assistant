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
				<li class="active"><a href="#">Venues</a></li>
				<li><a href="addvenue.php">Add Venue</a></li>
				<li><a href="contestants.php?field=net&daily=1">Contestants</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      <div class="starter-template">
		  <div class="page-header">
			  <h1>Venues</h1>
		  </div>

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<td>Name</td>
					<td>Crawled On</td>
					<td>Venue Id</td>
					<td>&nbsp;</td>
				</tr>
			</thead>
			<tbody>
<?php
$file_db = new PDO('sqlite:stats.sqlite');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$venues = 'SELECT name,venue_id,crawled_on,canonical_url FROM venues ORDER BY name ASC';
$result = $file_db->query($venues);

foreach ($result as $venue):
	if(strlen($venue['name']) == 0) continue;
?>
				<tr>
					<td><?= $venue['name'] ?></td>
					<td><?= $venue['crawled_on'] ?></td>
					<td><?= $venue['venue_id'] ?></td>
					<td align="right"><a href="venue.php?venue=<?= $venue['venue_id'] ?>" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-dashboard" ></span> Details</a>&nbsp;<a href="<?= $venue['canonical_url'] ?>" class="btn btn-info btn-xs">Foursquare</a></td>
				</tr>
<?php endforeach; ?>
			</tbody>
		</table>
		
      </div>

    </div><!-- /.container -->

  </body>
</html>
