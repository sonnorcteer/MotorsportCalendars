<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">    
<title>Motorsport Calendars</title>    

<!--<link rel="stylesheet" type="text/css" media="screen" href="css/style.css">-->
<link rel="stylesheet" type="text/css" media="screen" href="css/style.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/jquery.multiselect.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.theme.min.css">

<style>
    #map {
        width: 500px;
        height: 400px;
		background-color: #CCC;
    }
	  
	html {
		padding: 0;
		margin: 0;
	}
	
	body {
		background-color: #F9F9F9;
		text-align: center;
		width: 100%;
		font-family: Verdana, Arial, sans-serif;
		padding: 0;
		margin: 0;
	}
	
	h1 {
		font-size: 64px;
		margin: 0;
	}
	h2 {
		font-size: 24px;
		margin: 0 0 40px 0;
	}
	
	h6 {
		font-size: 20px;
		margin: 0;
	}
	
	#intro{
	font-size: 20px;
	margin: 0;
	padding: 0;
	}
	
	#intro2{
	font-size: 14px;
	margin: 0;
	}
	
	.button{
	background-color: #007fff;
	color: white;
	border: 2px solid #2D5ED0;
	border-radius: 8px;
	padding: 5px 15px;
	font-size: 16px;
	}
	
	.button:hover{
	background-color: #2D5ED0;
	}
	
	.button2{
	background-color: #F9F9F9;
	border: 1px solid black;
	font-size: 16px;
	padding: 5px 10px;
	border-radius: 4px;
	}
	
	.button2:hover{
	background-color: #e0e0e0;
	}
	
	.button3{
	font-size: 24px;
	border-radius: 8px;
	padding: 10px 15px;
	background-color: #007fff;
	color: white;
	border: 2px solid #2D5ED0;
	}
	
	.button3:hover{
	background-color: #2D5ED0;
	}
	
	#test {
	background-color: #007fff;
	width: 100%;
	color: white;
	padding: 5px 5px;
	}
	
	#rest {
		height: 100%;
		margin-left: 10%;
		max-width: 80%;
	}
	
	#botbanner {
	background-color: #007fff;
	font-size: 10px;
	color: white;
	}
	
	#botbanner a:link {
	color: white;
	}
</style>
	
<?php

$pdo = new PDO('mysql:host=;dbname=', '', '');

if (!$pdo) {
		die('Could not connect: ');
	}
	//echo 'Connected successfully';
    
if (isset($_POST["series"])) {
	
	
	$randStr = chr( mt_rand( ord( 'a' ) ,ord( 'z' ) ) ) .substr( md5( time( ) ) ,23 );
	$filename = "calendar/".$randStr.".ics";
	$myfile = fopen($filename, "w");
	
	if ($_POST["session"] == 'Practice'){
		$sessionList = array('Practice','Qualifying','Race');}
	elseif ($_POST["session"] == 'Qualifying'){
		$sessionList = array('Qualifying','Race');}
	elseif ($_POST["session"] == 'Race'){
		$sessionList = array('Race');}
	elseif ($_POST["session"] == 'Event'){
		$sessionList = array('Event');}
	
	$output = "BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nVERSION:2.0\r\nPRODID:-//Sonnorcteer//MSCalendar//EN\r\nX-WR-CALNAME:MSCalendar\r\nX-WR-CALDESC:MSCalendar\r\nX-WR-TIMEZONE:Etc/GMT\r\n";

	foreach ($_POST["series"] as $serie){
		foreach ($sessionList as $sessio){
			$query = 'SELECT * FROM event_setup WHERE series = ? AND session = ?';
			$mysql = $pdo->prepare($query);
			$mysql->execute(array($serie,$sessio));
			$result = $mysql->fetchAll();
			
			foreach ($result as $resul){
				$output .=
				"BEGIN:VEVENT"."\r\n".
				"SUMMARY:".$resul['summary']."\r\n".
				"DESCRIPTION:".$resul['description']."\r\n".
				"LOCATION:".$resul['location']."\r\n".
				"UID:".$resul['UID']."\r\n";
				if (strpos($resul['summary'],'Event') !== false){
				$output .= "DTSTART;VALUE=DATE:".substr($resul['debut'],0,8)."\r\n"
				."DTEND;VALUE=DATE:".substr($resul['fin'],0,8)."\r\n";}
				else{
				$output .= "DTSTART:".$resul['debut']."\r\n"."DTEND:".$resul['fin']."\r\n";}
				$output .= "DTSTAMP:".date('Ymd\THis\Z')."\r\n".
				"END:VEVENT\r\n";
				//STATUS:" . strtoupper($appointment->status) . "
				//LAST-MODIFIED:" . date(DATE_ICAL, strtotime($appointment->last_update)) . "
				//echo nl2br($resul['summary']."\n");
			}
		}
	}
	
	$output .= "END:VCALENDAR";
	
	fwrite($myfile, $output);
	fclose($myfile);
	
	$weblink = "webcal://sonnorcteer.com/".$filename;
	$googlink = "https://www.google.com/calendar/render?cid=".rawurlencode("http://sonnorcteer.com/".$filename);
	$downlink = "http://sonnorcteer.com/".$filename;
	
	$queryStore = 'INSERT INTO calendar_ids (calendar_ids, series, session) VALUES (?,?,?)';
	$mysqlStore = $pdo->prepare($queryStore);
	$mysqlStore->execute(array($randStr,join(',',$_POST["series"]),$_POST["session"]));
}

$queryAll = 'SELECT DISTINCT series FROM event_setup ORDER BY series';
$mysqlAll = $pdo->prepare($queryAll);
$mysqlAll->execute();
$resultAll = $mysqlAll->fetchAll();
  ?> 

<script type="text/javascript" src="javascripts/jquery-1.12.3.min.js"></script>
<script type="text/javascript" src="javascripts/jquery-ui.min.js"></script>
<script type="text/javascript" src="javascripts/jquery.multiselect.min.js"></script>
<script>
	$(function() {
		$("select").multiselect({
		selectedList: 10
		});
	});
</script>
  
</head>
<body>
	<div id="test">
			<h1>Motorsports Calendars</h1>
			<br>
			<h2>Over 100 series up to date with session times</h2>
	</div>
	<div id="rest">
				<!--This is a test calendar generator. Use it at your own risk and report back errors to sonnorcteer (sonnor93@gmail.com).
				<br>
				Otherwise, select the series you want (Ctrl or Shift click for more than one series), select what sessions you want to have and hit go. 
				<br>
				You'll get a link that you can put in Google or Download! 
				<br>
				These WILL get updated assuming you use the webcal link, although this is still in testing. Downloaded files WILL NOT be updated, thats impossible.-->

	  <br>
	  <h6>1. Select your category...</h6><br>
      <form method="post" name="createform" action="">  
		<input type="submit" name="category" value="All" class="button">
		<input type="submit" name="category" value="F1 Feeder Series" class="button">
		<input type="submit" name="category" value="WEC" class="button">
		<input type="submit" name="category" value="NASCAR" class="button">
		<input type="submit" name="category" value="GT" class="button">
		<?php
		if (isset($_POST["category"])){
			if ($_POST["category"] == "F1 Feeder Series"){$cats = array("GP2 Series","GP3 Series","Indy Lights");}
			elseif($_POST["category"] == "WEC"){$cats = array("WEC","IWSC","Intercontinental GT Challenge");}
			elseif ($_POST["category"] == "NASCAR"){$cats = array("NASCAR NSCS","NASCAR NCWTS","NASCAR NXS");}
			elseif ($_POST["category"] == "GT"){$cats = array("Blancpain GT","Super GT","GT Asia");}
		}
		?>
		<br><br>
		<h6>... Or your individual series</h6><br>
      <form method="post" name="createform" action="">
        <select name="series[]" multiple="multiple" class="MultSelect" size='6'>
            <?php 
			foreach($resultAll as $seriesRow){
				if (isset($_POST["category"])){
					if ($_POST["category"] == "All"){
						echo '<option value="'.$seriesRow['series'].'" selected="selected">'.$seriesRow['series'].'</option>';
					}
					else {
						if (in_array($seriesRow['series'],$cats)){
							echo '<option value="'.$seriesRow['series'].'" selected="selected">'.$seriesRow['series'].'</option>';
						}
						else {
							echo '<option value="'.$seriesRow['series'].'">'.$seriesRow['series'].'</option>';
						}
					}
				}
				else {
					echo '<option value="'.$seriesRow['series'].'">'.$seriesRow['series'].'</option>';
				}
			}
			?>
        </select>
		<br><br><br><br>
		<h6>2. Select your sessions</h6><br>
			<Input type=radio name="session" value="Event" checked='checked'>Event
            <Input type=radio name="session" value="Race">Race
            <Input type=radio name="session" value="Qualifying">Qualifying + Race
            <Input type=radio name="session" value="Practice">All Sessions
		<br><br><br><br>
        <span class="error"></span>
		<h6>3. Hit the button</h6><br>
             <input type="submit" name="submit" value="Generate" class="button3">
      </form>
      </p>
	
		<?php if (isset($googlink)){?>
		Google Calendar: <a href=<?php echo $googlink;?>>Click Here</a>
		<?php } ?>
		<br><br>
		<?php if (isset($weblink)){?>
		Outlook/Apple/Other Calendars: <a href=<?php echo $weblink;?>><?php echo $weblink;?></a>
		<?php } ?>
		<br><br>
		<?php if (isset($downlink)){?>
		Download Link: <a href=<?php echo $downlink;?>>Click Here</a>
		<?php } ?>
		<br><br>
		
		<p id="intro">
		All Calendars will be updated with times as and when they appear 
		</p>
		<p id="intro2">
		(Unless downloaded, I can't update your computers files...)

		</p>
	</div>
	<br><br>
<div id="botbanner">
<table border="0" cellpadding="5" cellspacing="1" width="95%" align="center" id="tablebot">
<tr>
<td align='left'>Developed by Sonnorcteer</td>
<td align='right'>Any issues? Head to <a href="http://www.reddit.com/r/MotorsportsCalendar">reddit</a> or email me at NO</a></td>
</tr>
</div>
	
</body>
</html>
