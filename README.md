=== Sephardic Zmanim generator ===
Contributors: adatosystems
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4ABF2RK76DKK4
Tags: Prayer Times, Davening times, davening, Zman, zmanim
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Pulls information from external sites via API, uses statically assigned items (lat/long, zman calculations), Formats output as HTML page or on the command line.

USAGE
-----------
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
--------------------
https://www.hebcal.com/home/developer-apis
http://www.geonames.org/ (using this API requires a login)

== Installation ==
Stick it in a folder and run it from there.


== Frequently asked questions ==

(none yet!)

== Screenshots ==


== Changelog ==

= 1.0 =
* Initial Release

== Upgrade notice ==


