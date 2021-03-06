<?php
/**
 * @package util
 */
/**
 * Distance between 2 points in miles.
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return number
 */
function geo_distance($lat1,$lon1,$lat2,$lon2){
	$t = $lon1 - $lon2;
	$d = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($t));
	$d = acos($d);
	$d = rad2deg($d);
	return $d * 60 * 1.1515;
}
/**
 * Returns an array with the keys min_lat, max_lat, min_lon, and max_lon.
 * @param number $lat
 * @param number $lon
 * @param number $range in miles
 * @return array An array containing the min and max latitudes and longitudes
 */
function geo_range($lat,$lon,$range){
	$lat_range = $range / 69.172;
	$lon_range = abs($range / (cos($lon) * 69.172));

	return array(
		'min_lat'=>$lat - $lat_range,
		'max_lat'=>$lat + $lat_range,
		'min_lon'=>$lon - $lon_range,
		'max_lon'=>$lon + $lon_range
	);
}