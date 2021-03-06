<?php
//this the username for http://www.geonames.org/, used to locate time zone based on lat/long information
$tzusername="ADD USER NAME HERE";

/*
INFORMAITON BLOCK
========================
FILENAME: sephardic_zmanim.php
NAME: Get Sephardic Zmanim
AUTHOR: Leon Adato
VERSION HISTORY
	0.0.1 - 0.0.10 - development
	0.1.0 - first pre-prod version
	0.2.0 - sanitization, improved output, added switches
	0.3.0 - 0.5.0 lots of fixes, formatting, updates 
	0.6.0 - declare all variables, other cleanup
	0.7.0 - additional cleanup
	0.8.0 - change to summer Friday mincha calculation
	0.9.0 - sanitize and validate inputs
	0.10.0 - debugging expansion, cleanup
DESCRIPTION	
Pulls information from external sites via API
uses statically assigned items (lat/long, zman calculations)
Formats output as HTML page
    
USAGE
==========
this page is served from a web server or at the commandline
along with the URL/URI, variables can include:

shabbat=1 / -s
	Go to the next upcoming Friday and pull dates
debug=1 / -u
	include all calculations and outputs for troubleshooting.
date=yyyy-mm-dd  / -dyyyy-mm-dd
	the date you want zmanim for. if you couple this with shabbat=1/-s, this date must be a friday
lat=##.### / -a##.### 
	latitude. Must also include longitude and tzid. Mutually exclusive from zip, city, or geoname.
long=##.### / -o##.###
	longitude. Must also include latitude and tzid. Mutually exclusive from zip, city, or geoname.
zip=##### / -z#####
	zip code. Mutually exclusive from lat and long. Mutually exclusive from lat/long, city, or geoname.
geoname=(######) / -g#####
	location specified by GeoNames.org numeric ID (See cities5000.zip from https://download.geonames.org/export/dump/.). Mutually exclusive from zip, city, or lat/long.
city=(city name) / -c(cityname)
	location specified by one of the Hebcal.com legacy city identifiers (https://github.com/hebcal/dotcom/blob/master/hebcal.com/dist/cities2.txt). Mutually exclusive from zip, geoname, or lat/long.

EXTERNAL SOURCE(S)
======================
https://www.hebcal.com/home/developer-apis
http://www.geonames.org/ (using this API requires a login)
*/

//initial variables
date_default_timezone_set('America/New_York');
// 1/0 variables
$cli=0;
$debug=0;
$isdst=0;
$shabbat=0;
$mevarchim = 0;
$chodeshcount = 0;
$setdate=0;
$molad=0;

// date variables
$usedate="";
$zmanday="";
$friday="";
$nextfriday="";
$nextsaturday="";
$friyr="";
$frimo="";
$frid="";

// time variables
$satshema="";
$frimincha="";
$hebrewparashat="";
$englishparashat="";
$chodeshtext="";
$candles="";
$fritzet="";
$sattzet="";
$latemotzei="";
$satmincha="";
$satarvit="";
$frialot="";
$satalot="";
$frishaa="";
$satshaa="";
$friminchged="";
$satminchged="";
$friminchkat="";
$satminchkat="";
$satshema="";
$friplag="";
$satplag="";
$zmansunrise="";
$zmansunset="";
$zmantzet="";
$zmanalot="";
$zmanshaa="";
$zmanplag="";
$frisunrise="";
$frisunset="";
$satsunrise="";
$frishir="";

// text variables
$chodeshtext = "";
$molad="";

// location variables
$geostring="";
$zipcode="";
$latitude="";
$longitude="";
$city="";
$geoname="";
$kabshab="";
$locstring="";

// CURL variables
$friurl="";
$saturl="";
$zmanurl="";
$get_fritimes="";
$friresponse="";
$get_sattimes="";
$satresponse="";
$set_zmanim="";
$zmanresponse="";

//get commandline variables
if(isset($_GET['date'])) {$usedate=stripcslashes($_GET['date']);}
if(isset($_GET['zipcode'])) {$zipcode=stripcslashes($_GET['zipcode']); }
if(isset($_GET['city'])) {$city=stripcslashes($_GET['city']); }
if(isset($_GET['geoname'])) {$geoname=stripcslashes($_GET['geoname']); }
if(isset($_GET['lat'])) {$latitude=stripcslashes($_GET['lat']); }
if(isset($_GET['long'])) {$longitude=stripcslashes($_GET['long']); }
if(isset($_GET['debug'])) {$debug=stripcslashes($_GET['debug']); }
if(isset($_GET['shabbat'])) {$shabbat=stripcslashes($_GET['shabbat']); }
if (PHP_SAPI === 'cli') {
	$cli=1;
	echo "running in cli mode\n";
	//if commandline, look for:
		// -d yyyy-mm-dd (the date)
		// -z ##### (zipcode)
		// -c (name)
		// -g (geoname)
		// -a ##.#### (latitude)
		// -o ##.#### (longitude)
		// -u (debug)
		// -s (shabbat)
	$options = getopt("d:z:c:g:a:o:us");
	//var_dump($options);
	if(isset($options['d'])) {$usedate=$options['d'];}
    if(isset($options['z'])) {$zipcode=$options['z'];}
    if(isset($options['c'])) {$city=$options['c'];}
    if(isset($options['g'])) {$geoname=$options['g'];}
    if(isset($options['a'])) {$latitude=$options['a'];}
    if(isset($options['o'])) {$longitude=$options['o'];}
    if(isset($options['u']) && $options['u'] == false) {$debug=1;}
    if(isset($options['s']) && $options['s'] == false) {$shabbat=1;}
}

//sanitize some initial inputs
if ($debug ==1 || $debug == 0) {
} else {
    echo("<H2>Debug must be 0 or 1</h2>\n");
    exit(1);
}
if ($shabbat ==1 || $shabbat == 0) {
} else {
    echo("<H2>Shabbat must be 0 or 1</h2>\n");
    exit(1);
}

if ($zipcode){
	if (preg_match('/^[0-9]{5}$/', $zipcode)) {
	} else {
    	echo("<H2>not a valid 5 digit zip code</h2>\n");
    	exit(1);
	}
}
if ($geoname){
	if (preg_match('/^[0-9]{7}$/', $geoname)) {
	} else {
    	echo("<H2>not a valid 7 digit Geoname code</h2>\n");
    	exit(1);
	}
}
if ($latitude){
	if ($latitude >= -90 && $latitude <=-90) {
	} else {
    	echo("<H2>Not a valid latitude coordinate</h2>\n");
    	exit(1);
	}
}
if ($longitude){
	if ($longitude >= -180 && $longitude <=-180) {
	} else {
    	echo("<H2>Not a valid longitude coordinate</h2>\n");
    	exit(1);
	}
}


//Date handler
//if usedate exists, make sure it's in the correct format and that it's a real date
if ($usedate) {
	if (strtotime($usedate) == '') {
		echo "<H2>This is not a valid date format.</H2>";
		exit(0);
	}
	$usedate=date('Y-m-d', strtotime($usedate));
}

//if date given and shabbat specified, check if it's friday (otherwise end with error)
if ($usedate && $shabbat == 1) {
	if(date('l', strtotime($usedate)) != 'Friday') {
		exit("<H2>Date given isn't a Friday!</h2>\n");
	} else {
		$setdate=1;
		$friday=$usedate;
		$zmanday = $friday;
		$nextfriday = date('Y-m-d', strtotime( $friday . " +7 days"));
		$saturday= date('Y-m-d', strtotime( $friday . " +1 days"));
		$nextsaturday = date('Y-m-d', strtotime( $saturday . " +7 days"));
	}
}
//if date given and shabbat NOT specified, use the date and set shabbat = 0
if ($usedate && $shabbat == 0) {
	$setdate=1;
	$zmanday = $usedate;
}
//if no date given and shabbat specified, use next friday and set Shabbat == 1
if (!$usedate && $shabbat == 1) {
	$setdate=0;
	$friday = date_create('next Friday')->format('Y-m-d');
	$zmanday = $friday;
	$nextfriday = date('Y-m-d', strtotime( $friday . " +7 days"));
	$saturday= date('Y-m-d', strtotime( $friday . " +1 days"));
	$nextsaturday = date('Y-m-d', strtotime( $saturday . " +7 days"));
}
//if no date given and shabbat NOT specified, use today and set shabbat == 0
if(!$usedate && $shabbat == 0) {
	$setdate=0;
	$zmanday = date('Y-m-d');
}

//set location
if ($zipcode) {
	$geostring="&zip=$zipcode";
	$locstring = "Zipcode $zipcode";
}elseif ($geoname) {
	$geostring="&geo=geoname&geonameid=$geoname";
	$locstring = "Geoname ID $geoname";
} elseif ($city) {
	$geostring="&geo=city&city=$city";
	$locstring = "City $city";
} elseif ($latitude && $longitude ) {
	$tzurl = "http://api.geonames.org/timezoneJSON?lat=$latitude&lng=$longitude&username=$tzusername";
	$get_tzname = callAPI('GET', $tzurl, false);
	$tzresponse = json_decode($get_tzname, true);
	$tzid = $tzresponse['timezoneId'];
	$geostring = "&geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "Lat: $latitude, Long $longitude, Timezone $tzid";
} else {
	$geostring = "&geo=pos&latitude=41.4902062&longitude=-81.517477&tzid=America/New_York";
	$locstring = "Cleveland Sephardic Minyan Kollel Building";
}

//figure out if it's DST or not
$isdst = date('I', strtotime($zmanday));

// get sunrise and sunset for the day; or Friday and Saturday
if ($shabbat == 1) { //get times for Shabbat
	$friurl = "https://www.hebcal.com/zmanim?cfg=json$geostring&date=$friday";
	$get_fritimes = callAPI('GET', $friurl, false);
	$friresponse = json_decode($get_fritimes, true);
	//if ( $sunresponse['response']['errors'] ) { $sunerrors = $sunresponse['response']['errors'] };
	//if ( $sunresponse['response']['data'][0] ) { $sundata = $sunresponse['response']['data'][0] };

	$saturl = "https://www.hebcal.com/zmanim?cfg=json$geostring&date=$saturday";
	$get_sattimes = callAPI('GET', $saturl, false);
	$satresponse = json_decode($get_sattimes, true);
	//if ( $sunresponse['response']['errors'] ) { $sunerrors = $sunresponse['response']['errors'] };
	//if ( $sunresponse['response']['data'][0] ) { $sundata = $sunresponse['response']['data'][0] };

	//FIXED TIMES
	$frisunrise = date('g:i a', strtotime($friresponse['times']['sunrise']));
	$frisunset = date('g:i a', strtotime($friresponse['times']['sunset']));
	$satsunrise = date('g:i a', strtotime($satresponse['times']['sunrise']));
	$satsunset = date('g:i a', strtotime($satresponse['times']['sunset']));
	$friyr = date('Y',strtotime($friday));
	$frimo = date('m',strtotime($friday));
	$frid = date('d',strtotime($friday));

	//GET SHABBAT, ROSH CHODESH, AND MOLAD INFO
	$zmanurl = "https://www.hebcal.com/hebcal?v=1&cfg=json&maj=on&min=on&nx=on&mf=on&ss=on&s=on&c=on&b=18&m=0&i=off&leyning=off$geostring&start=$friday&end=$nextsaturday";
	$get_zmanim = callAPI('GET', $zmanurl, false);
	$zmanresponse = json_decode($get_zmanim, true);
	//$zmanerrors = $zmanresponse['response']['errors'];
	//$zmandata = $zmanresponse['response']['data'][0];
	//echo "print_r zmanresponse: \n";
	//print_r ($zmanresponse);
	foreach($zmanresponse['items'] as $zmanitem) {
		//print_r($zmanitem);
		if (date('Y-m-d', strtotime($zmanitem['date'])) == $saturday) {
			if ($zmanitem['category'] == "mevarchim") {
				$mevarchim = 1;
				$molad = $zmanitem['memo'];
			}
			if ($zmanitem['category'] == "parashat") {
				$hebrewparashat = $zmanitem['hebrew'];
				$englishparashat = $zmanitem['title'];
			}
		}
		if ($mevarchim == 1 and $zmanitem['category'] == "roshchodesh" and strtotime($zmanitem['date']) > strtotime($friday) and strtotime($zmanitem['date']) <= strtotime($nextsaturday) ) {
			//if $mevarchim = 1 and if date is > friday and < next saturday
			//check for Rosh Chodesh and get that info if needed
			if ($chodeshcount == 0) {
				$chodeshtext = $zmanitem['title'] . " will be " . date('D m/d', strtotime($zmanitem['date']));
				$chodeshcount++;
			} else {
				$chodeshtext = $chodeshtext . " and " . date('D m/d', strtotime($zmanitem['date']));
			}
		}	
	}
	//SIMPLE CALCULATIONS
		// Shabbat candles = fri shkia - 18
	$candles = date('g:i a', strtotime( $frisunset . " -18 minutes"));
		// tzet hakochavim = shkia + 45
	    // early Motzi Shabbat is the same as tzet
	$fritzet = date('g:i a', strtotime( $frisunset . " +45 minutes"));
	$sattzet = date('g:i a', strtotime( $satsunset . " +45 minutes"));
	    // Late Motzi Shabbat Shkia+72 
	$latemotzei = date('g:i a', strtotime( $satsunset . " +72 minutes"));
	    // Saturday Mincha = Shkia-40 minutes 
	$satmincha = date('g:i a', strtotime( $satsunset . " -40 minutes"));
	    // Saturday Arvit = Shkia+50 minutes
	$satarvit = date('g:i a', strtotime( $satsunset . " +50 minutes"));
		// Alot Hashachar ("alot") = netz-((shkia-netz)/10)
	$frialot = date('g:i a', strtotime($frisunrise)-((strtotime($frisunset) - strtotime($frisunrise))/10));
	$satalot = date('g:i a', strtotime($satsunrise)-((strtotime($satsunset) - strtotime($satsunrise))/10));
	    // Sha'a (halachic hour) = (tzait - Alot) / 12 
	$frishaa = (strtotime($fritzet)-strtotime($frialot))/12;
	$satshaa = (strtotime($sattzet)-strtotime($satalot))/12;

	//COMPOUND CALCULATIONS
		// Mincha Gedola = 6.5 sha???a after ???alot 
	$friminchged = date('g:i a', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12))*6.5);
	$satminchged = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12))*6.5);
	    // Mincha ketana = 9.5 sha???a after ???alot 
	$friminchkat = date('g:i a', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12))*9.5);
	$satminchkat = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12))*9.5);
	    // Sof zman kria shema (latest time for shema in the morning = Alot + (sha'a * 3)
	$satshema = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*3));
	    // Plag Hamincha ("plag") = mincha ketana+((tzet - mincha ketana) / 2)
	$friplag = date('g:i a', strtotime($friminchkat)+(((strtotime($fritzet))-strtotime($friminchkat))/2));
	$satplag = date('g:i a', strtotime($satminchkat)+(((strtotime($sattzet))-strtotime($satminchkat))/2));
	    // "winter" mincha = Shkia-20 
	if ($isdst == 0) { 
		$frimincha = date('g:i a', strtotime( $frisunset . " -20 minutes"));
		$candletext = date('m/d', strtotime($friday)) . " Candle Lighting: $candles";
	} else {
		$candletext = date('m/d', strtotime($friday)) . " Candle Lighting: $friplag / $candles";
		$frimincha = date('g:i a', strtotime( $friplag . " -20 minutes"));
		$frishir = date('g:i a', strtotime( $friplag . " -40 minutes"));
		$kabshab = "Following Mincha";
	}
} else {	//get times for just $zmanday
	$zmanurl = "https://www.hebcal.com/zmanim?cfg=json$geostring&date=$zmanday";
	$get_zmantimes = callAPI('GET', $zmanurl, false);
	$zmanresponse = json_decode($get_zmantimes, true);
	//if ( $zmanresponse['response']['errors'] ) { $zmanerrors = $zmanresponse['response']['errors']; }
	//if ( $zmanresponse['response']['data'][0] ) { $zmandata = $zmanresponse['response']['data'][0]; }
	$zmansunrise = date('g:i a', strtotime($zmanresponse['times']['sunrise']));
	$zmansunset = date('g:i a', strtotime($zmanresponse['times']['sunset']));
	$zmantzet = date('g:i a', strtotime( $zmansunset . " +45 minutes"));
		// Alot Hashachar ("alot") = netz-((shkia-netz)/10)
	$zmanalot = date('g:i a', strtotime($zmansunrise)-((strtotime($zmansunset) - strtotime($zmansunrise))/10));
	    // Sha'a (halachic hour) = (tzait - Alot) / 12
	$zmanshaa = (strtotime($zmantzet)-strtotime($zmanalot))/12;
	//COMPOUND CALCULATIONS
		// Mincha Gedola = 6.5 sha???a after ???alot 
	$zmanminchged = date('g:i a', strtotime($zmanalot)+(((strtotime($zmantzet)-strtotime($zmanalot))/12))*6.5);
	    // Mincha ketana = 9.5 sha???a after ???alot 
	$zmanminchkat = date('g:i a', strtotime($zmanalot)+(((strtotime($zmantzet)-strtotime($zmanalot))/12))*9.5);
	    // Sof zman kria shema (latest time for shema in the morning = Alot + (sha'a * 3)
	$zmanshema = date('g:i a', strtotime($zmanalot)+(((strtotime($zmantzet)-strtotime($zmanalot))/12)*3));
	    // Plag Hamincha ("plag") = mincha ketana+((tzet - mincha ketana) / 2)
	$zmanplag = date('g:i a', strtotime($zmanminchkat)+(((strtotime($zmantzet))-strtotime($zmanminchkat))/2));
}

function callAPI($method, $url, $data){
   $curl = curl_init();
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
         break;
      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'APIKEY: 111111111111111111111',
      'Content-Type: application/json',
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}

if ($debug == 1) {
	if ($shabbat == 1) {
		echo "DEBUG INFO<br>\n";
		echo "Server time: " . date('d-m-Y h:i:s A') . "<br>\n";
		echo "Friurl is " . $friurl . "<br>\n";
		echo "Saturl is " . $saturl . "<br>\n";
		echo "Zmanurl is " . $zmanurl . "<br>\n";
		echo "Location $locstring<br>\n";
		echo "Friday: $friday<br>\n";
		echo "Next friday  $nextfriday<br>\n";
		echo "Friday alot (netz-(shkia-netz)/10) $frialot<br>\n";
		echo "Friday netz $frisunrise<br>\n";
		echo "Friday mincha (summer: zman. Winter: shkia-20) $frimincha<br>\n";
		echo "Friday mincha gedola (6.5 sha'a after alot) $friminchged<br>\n";
		echo "Friday mincha ketana (9.5 sha'a after alot) $friminchkat<br>\n";
		echo "Friday plag (mincha ketana + (tzet-mincha ketana / 2) ) $friplag<br>\n";
		echo "Friday Kabbalat Shabbat (summer: fixed time winter: mincha+20) $kabshab<br>\n";
		echo "Friday shkia $frisunset<br>\n";
		echo "Friday tzet (shkia+45) $fritzet<br>\n";
		echo "Sha'a " . number_format((float)$frishaa/60, 2, '.', '') ." minutes<br>\n";
		echo "Saturday $saturday<br>\n";
		echo "Saturday Alot (netz-(shkia-netz)/10) is $satalot<br>\n";
		echo "Saturday netz $satsunrise<br>\n";
		echo "Saturday Shema (3 sha'a after alot) $satshema<br>\n";
		echo "Saturday Mincha gedola (6.5 sha'a after alot) $satminchged<br>\n";
		echo "Saturday Mincha ketatna (9.5 sha'a after alot) $satminchkat<br>\n";
		echo "Saturday plag (mincha ketana + (tzet-mincha ketana / 2) ) $satplag<br>\n";
		echo "Saturday shkia $satsunset<br>\n";
		echo "Saturday tzet (shkia+45) $sattzet<br>\n";
		echo "Saturday Sha'a " . number_format((float)$satshaa/60, 2, '.', '') ." minutes<br>\n";
		echo "Molad: $molad<br>\n";
		echo "Chodesh text: $chodeshtext<br>\n";
		echo "END DEBUG INFO<br><br>\n\n";

	} else {
		//debug output for regular day
		echo "DEBUG INFO<br>\n";
		echo "Server time " . date('d-m-Y h:i:s A') . "<br>\n";
		echo "Zmanurl $zmanurl<br>\n";
		echo "Location $locstring<br>\n";
		echo "Date $zmanday<br>\n";
		echo "Alot (netz-(shkia-netz)/10) $zmanalot<br>\n";
		echo "Sunrise/Netz $zmansunrise<br>\n";
		echo "Sof Zman Kria Shema $zmanshema<br>\n";
		echo "Mincha Gedola (6.5 sha'a after alot) $zmanminchged<br>\n";
		echo "Mincha Ketana (9.5 sha'a after alot) $zmanminchkat<br>\n";
		echo "Plag Hamincha (mincha ketana + (tzet-mincha ketana / 2) ) $zmanplag<br>\n";
		echo "Sunset/Shkia $zmansunset<br>\n";
		echo "Tzet (shkia+45) $zmantzet<br>\n";
		echo "Sha'a " . number_format((float)$zmanshaa/60, 2, '.', '') ." minutes<br>\n";
		echo "END DEBUG INFO<br><br>\n\n";
	}
}

if ($cli == 1) {
	if ($shabbat == 1) {
		echo "Date: $zmanday\n";
		echo "Location: $locstring\n";
		echo "$hebrewparashat - $englishparashat\n";
		echo "$candletext\n";
		echo "\n";
		echo "Erev Shabbat\n";
		echo "Mincha: $frimincha\n";
		echo "Kabbalat Shabbat: $kabshab\n";
		echo "\n";
		echo "Shabbat Day\n";
		echo "Shacharit (korbonot): 8:15am\n";
		echo "Hodu: 8:30am\n";
		echo "Mincha: $satmincha\n";
		echo "Arvit: $satarvit\n";
		echo "\n\n";
		echo "Zmanim\n";
		echo "Friday\n";
		echo "Plag: $friplag\n";
		echo "Shkia: $frisunset\n";
		echo "Repeat Kria Shema: $fritzet\n";
		echo "\n\n";
		echo "Saturday\n";
		echo "Kriat Shema: $satshema\n";
		echo "Shkia: $satsunset\n";
		echo "Shabbat ends: \n";
		echo "$sattzet / $latemotzei\n";
		if ($mevarchim == 1) {
			echo "Molad: $molad\n";
			echo "Rosh Chodesh: $chodeshtext\n";
		}
	} else {
		echo "Date: $zmanday\n";
		echo "Location: $locstring\n";
		echo "Alot haShachar: $zmanalot\n";
		echo "Sunrise / Netz: $zmansunrise\n";
		echo "Sof zman kria shema: $zmanshema\n";
		echo "Mincha Gedola: $zmanminchged\n";
		echo "Mincha Ketana: $zmanminchkat\n";
		echo "Plag haMincha: $zmanplag\n";
		echo "Sunset / shkia: $zmansunset\n";
		echo "Tzet: $zmantzet\n";
		echo "Sha'a: ". number_format((float)$zmanshaa/60, 2, '.', '') . " minutes \n";
	}
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sephardic Congregation of Cleveland Zmanim</title>
    <style>
		table {
  			table-layout: fixed;
  			width: 7.5in;
  			border-collapse: collapse;
  		}

  		th, td {
  			border: 1px solid black;
  			padding: 0.5rem;
  			text-align: left;
  			vertical-align: top;
  		}	
</style>
</head>

<body>
<img src="header.png" width="774">
<table border=1>
	<tr>
		<td style="width:1.25in;">
			<P><strong>Rav<br>Rabbi Tzvi Maimon</strong><br><br>
			Officers are listed <A HREF="https://www.clevelandsephardiminyan.com/officers/">on our website:</A></P>
		</td>
<?php if($shabbat == 1) : ?>
		<td style="width: 4in">
			<center><h3><?php echo "$hebrewparashat - $englishparashat"; ?></h3>
			<P><?php echo "$candletext"; ?></P></center>
			<h3>Erev Shabbat</h3>
			<P><?php if($dst=1) {echo "Shir haShirim, Dvar halacha: $frishir<br>";}?>
			<?php echo "Mincha: $frimincha"; ?><br>
			<?php echo "Kabbalat Shabbat: $kabshab"; ?></P>
			<h3>Shabbat Day</h3>
			<P><?php echo "Shacharit (korbonot): 8:15am"; ?><br> 
			<?php echo "Hodu: 8:30am"; ?><br> 
			<?php echo "Mincha: $satmincha"; ?><br> 
			<?php echo "Arvit: $satarvit"; ?></P>
		</td>
		<td style="width: 2.25in">
			<small><h3>Zmanim</h3>
			<br>
			<h3>Friday</h3>
			<P><?php echo "Plag haMincha: $friplag"; ?><br>
			<?php echo "Shkia: $frisunset"; ?><br>
			<?php echo "Repeat Kria Shema: $fritzet"; ?></P>
			<br>
			<h3>Saturday</h3>
			<P><?php echo "Kriat Shema: $satshema"; ?><br>
			<?php echo "Shkia: $satsunset"; ?><br>
			<?php echo "Shabbat ends: $sattzet / $latemotzei"; ?></P>

			<?php if ($mevarchim == 1) : ?>
				<h3>Molad</h3>
				<P><?php echo "$molad";?></P>
				<h3>Rosh Chodesh</h3>
				<P><?php echo "$chodeshtext";?></P>
			<?php endif; ?>
			</small>
		</td>
<?php else : ?>
		<td style="width: 4in">
			<h3><?php echo "Zmanim for Date: $zmanday"; ?></h3><br>
			<P><?php echo "Location: $locstring"; ?><br>
			<?php echo "Alot haShachar: $zmanalot"; ?><br>
			<?php echo "Sunrise / Netz: $zmansunrise"; ?><br>
			<?php echo "Sof zman kria shema: $zmanshema"; ?><br>
			<?php echo "Mincha Gedola: $zmanminchged"; ?><br>
			<?php echo "Mincha Ketana: $zmanminchkat"; ?><br>
			<?php echo "Plag haMincha: $zmanplag"; ?><br>
			<?php echo "Sunset / shkia: $zmansunset"; ?><br>
			<?php echo "Tzet: $zmantzet"; ?><br>
			<?php echo "Sha'a: ". number_format((float)$zmanshaa/60, 2, '.', '') . " minutes"; ?></P>
		</td><td style="width: 2.25in"></td>
<?php endif; ?>
	</tr>
</table>
<P>For information on how to use this webpage, click <a href="usage.html">here</a></P>
<P>NOTE: Times are calculated automatically based on the location informatin provided. Because zip codes can cover a large area; and because of variations in things like the source of sunrise/sunset, height of elevation, rounding seconds to minutes, etc. times may be off by as much as 2 minutes. Please plan accordingly.</P>
</body>
</html>
