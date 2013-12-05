<?php
if(isset($_POST['venue_id']) && strlen($_POST['venue_id']) == 24) {
	$venue_id = trim($_POST['venue_id']);
	
	// add venue
	$file_db = new PDO('sqlite:stats.sqlite');
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	$stmt = $file_db->prepare('INSERT INTO venues (venue_id) VALUES (:venue_id)');
	$stmt->bindValue(':venue_id', $venue_id, SQLITE3_TEXT);
	$stmt->execute() or die($file_db->errorInfo());
	
	echo $venue_id, " added.<br>",PHP_EOL;
}
?>

<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Landlord Assistant">
    <meta name="author" content="Jens Kohl">

    <title>Landlord Assistant</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
	<link href="/css/bootstrap-glyphicons.css" rel="stylesheet">

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
				<li><a href="/">Venues</a></li>
				<li class="active"><a href="#">Add Venue</a></li>
				<li><a href="contestants.php?field=net&daily=1">Contestants</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      <div class="starter-template">
        <h1>Add Venue</h1>

		<form class="form-horizontal" action="<?= $_SERVER['PHP_SELF']?>" name="pushForm" method="post">
		  <div class="form-group">
		    <label for="inputVenueId" class="col-lg-2 control-label">Foursquare Venue Id</label>
		    <div class="col-lg-10">
		      <input type="text" class="form-control" id="inputVenueId" name="venue_id" placeholder="4b3a8164f964a520e46825e3">
		    </div>
		  </div>
		  <div class="form-group">
		    <div class="col-lg-offset-2 col-lg-10">
		      <button type="submit" class="btn btn-primary">Add</button>
		    </div>
		  </div>
		</form>
		
      </div>

    </div><!-- /.container -->

  </body>
</html>
