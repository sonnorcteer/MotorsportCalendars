<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">    
    <title>Update Calendars</title>    
	<link rel="stylesheet" type="text/css" href="css/style.css" />    
</head>	
<?php
$pdo = new PDO('mysql:host=;dbname=', '', '');
    
if (isset($_POST["submit"])) {
	
	$queryCals = 'SELECT * FROM calendar_ids';
	$mysqlCals = $pdo->prepare($queryCals);
	$mysqlCals->execute();
	$resultCals = $mysqlCals->fetchAll();
	
	foreach ($resultCals as $strCals){
		$filename = "calendar/".$strCals['calendar_ids'].".ics";
		$myfile = fopen($filename, "w");
		
		$output = "BEGIN:VCALENDAR
METHOD:PUBLISH
VERSION:2.0
PRODID:-//Sonnorcteer//MSCalendar//EN
X-WR-CALNAME:MSCalendar
X-WR-CALDESC:MSCalendar
X-WR-TIMEZONE:Etc/GMT\r\n";
	
		if ($strCals['session'] == 'Practice'){
			$sessionList = array('Practice','Qualifying','Race');}
		elseif ($strCals['session'] == 'Qualifying'){
			$sessionList = array('Qualifying','Race');}
		elseif ($strCals['session'] == 'Race'){
			$sessionList = array('Race');}
		elseif ($strCals['session'] == 'Event'){
			$sessionList = array('Event');}

		$series = explode(",",$strCals['series']);	
		foreach ($series as $serie){
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
					"UID:".$resul['UID']."\r\n".
					"LOCATION:".$resul['location']."\r\n";
					if (strpos($resul['summary'],'Event') !== false){
					$output .= "DTSTART;VALUE=DATE:".substr($resul['debut'],0,8)."\r\n"
					."DTEND;VALUE=DATE:".substr($resul['fin'],0,8)."\r\n";}
					else{
					$output .= "DTSTART:".$resul['debut']."\r\n"."DTEND:".$resul['fin']."\r\n";}
					$output .= "DTSTAMP:".date('Ymd\THis\Z')."\r\n".
					"END:VEVENT\r\n";
				}
			}
		}
		
		$output .= "END:VCALENDAR";
		
		fwrite($myfile, $output);
		fclose($myfile);
		
	}
}

if (isset($_POST["grab"])) {
	$spreadsheet="";
	/*$handle = fopen($spreadsheet,"r");
	$data = fgetcsv($handle,1000,",");
	$spreadsheet_data[]=$data;
	print_r($spreadsheet_data);
	fclose($handle);*/
	
	$query = 'SELECT event_summaries_id FROM event_summaries';
	$mysql = $pdo->prepare($query);
	$mysql->execute();
	$result = $mysql->fetchAll();
	
	$ids = array();
	foreach ($result as $resul){
	array_push($ids,$resul['event_summaries_id']);}
	
	$csv = array_map("str_getcsv",file($spreadsheet,FILE_SKIP_EMPTY_LINES));
	$keys = array_shift($csv);
	foreach ($csv as $i=>$row) {
	$csv[$i] = array_combine($keys,$row);}
	
	$insert=array();
	$update=array();
	foreach ($csv as $cs){
		if (in_array($cs['UID'],$ids)){
			array_push($update,$cs['UID']);}
		else {
			array_push($insert,$cs['UID']);}
	}
	foreach ($update as $updat){
		$upval = ((intval($updat))-1);
		$debutup = date_create_from_format('d/m/Y H:i:s',$csv[$upval]['Debut']);
		$finup = date_create_from_format('d/m/Y H:i:s',$csv[$upval]['Fin']);
		$queryup = 'UPDATE event_summaries 
		SET Championship = ?, RoundNo = ?, Event = ?, Location = ?, Round_Name = ?, Debut = ?, Fin = ?, Session = ? 
		WHERE event_summaries_id = ?';
		$mysqlup = $pdo->prepare($queryup);
		$mysqlup->execute(array($csv[$upval]['Championship'],$csv[$upval]['RoundNo'],$csv[$upval]['Event'],$csv[$upval]['Location'],
		$csv[$upval]['Round_Name'],date_format($debutup,'Y-m-d H:i:s'),date_format($finup,'Y-m-d H:i:s'),$csv[$upval]['Session'],$csv[$upval]['UID']));
	}
	foreach ($insert as $inser){
		$insval = ((intval($inser))-1);
		$debutin = date_create_from_format('d/m/Y H:i:s',$csv[$insval]['Debut']);
		$finin = date_create_from_format('d/m/Y H:i:s',$csv[$insval]['Fin']);
		$queryin = 'INSERT INTO event_summaries (event_summaries_id,Championship,RoundNo,Event,Location,Round_Name,Debut,Fin,Session)
		VALUES (?,?,?,?,?,?,?,?,?)';
		$mysqlin = $pdo->prepare($queryin);
		$mysqlin->execute(array($csv[$insval]['UID'],$csv[$insval]['Championship'],$csv[$insval]['RoundNo'],$csv[$insval]['Event'],
		$csv[$insval]['Location'],$csv[$insval]['Round_Name'],date_format($debutin,'Y-m-d H:i:s'),date_format($finin,'Y-m-d H:i:s'),$csv[$insval]['Session']));
	}
	
	$querydes = "TRUNCATE TABLE event_setup";
	$mysqldes = $pdo -> prepare($querydes);
	$mysqldes->execute();
	
	$queryend = "INSERT INTO event_setup (UID, summary, description, location, debut, fin, series, session) 
	SELECT CONCAT(event_summaries_id,'@sonnorcteer.com'),
	CONCAT_WS(' ',Championship,Round_Name,Event),
	CONCAT_WS(' ','Round:',RoundNo,Event),
	CONCAT_WS(', ',Location,Round_Name),
	DATE_FORMAT(Debut, '%Y%m%dT%H%i%sZ'),
	DATE_FORMAT(Fin, '%Y%m%dT%H%i%sZ'),
	Championship,Session FROM event_summaries";
	$mysqlend = $pdo->prepare($queryend);
	$mysqlend->execute();
	
	
	//print_r($insert);
	//print_r($update);
}

?>
	 
<body>
	 
	<form method="post" name="createform" action="">
	   <input type="submit" name="submit" value="Update Calendars">
	   <input type="submit" name="grab" value="Grab Data">
    </form>
	
	<?php 
	if (isset($_POST["grab"])) {
		echo "<h1>Data has been grabbed. Click Update to put in calendars.</h1>";
	}
	
	if (isset($_POST["submit"])) {
		echo "<h1>Calendars have been updated.</h1>";
	}
	?>
	
</body>
</html>