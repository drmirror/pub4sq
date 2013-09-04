<?php

# File:        pub4sq.php
# Author:      Andre Spiegel <spiegel@drmirror.net>
# Description: Displays a map with your last few Foursquare checkins.

# put your foursquare RSS feed address between the quotes below
# (get it from http://foursquare.com/feeds)
$url = "";   
$count = 7;  # number of checkins to show 

date_default_timezone_set("UTC");

function time2str($ts)   # this one thanks to somebody on stackoverflow
{
    if(!ctype_digit($ts))
        $ts = strtotime($ts);

    $diff = time() - $ts;
    if($diff == 0)
        return 'now';
    else
    {
        $day_diff = floor($diff / 86400);
        if($day_diff == 0)
        {
            if($diff < 60) return 'just now';
            if($diff < 120) return '1 minute ago';
            if($diff < 3600) return floor($diff / 60) . ' minutes ago';
            if($diff < 7200) return '1 hour ago';
            if($diff < 86400) return floor($diff / 3600) . ' hours ago';
        }
        if($day_diff == 1) return 'yesterday';
        if($day_diff < 7) return $day_diff . ' days ago';
        if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
        if($day_diff < 60) return 'last month';
        return date('F Y', $ts);
    }
}

$contents = file_get_contents($url . ";count=" . $count);

$p = xml_parser_create();
xml_parse_into_struct($p, $contents, $values, $tags);
xml_parser_free($p);

//echo "Tags array\n";
//print_r($tags);
//echo "Values array\n";
//print_r($values);

$i = 0;

foreach ($values as $key=>$value) {

  if ($value["tag"] == "TITLE"
      && substr($value["value"],0,26) != "foursquare checkin history")
    $checkins[$i]["name"] = $value["value"];
  if ($value["tag"] == "PUBDATE")
    $checkins[$i]["time"] = strtotime($value["value"]);
  if ($value["tag"] == "LINK")
    $checkins[$i]["link"] = $value["value"];
  if ($value["tag"] == "GEORSS:POINT") {
    $checkins[$i]["coord"] = $value["value"];
    $i++;
  }

}

?>
<html>
  <head>
    <meta charset="utf-8">
    <style>
      #content {
        margin-top: 2em;
        width: 90%;
	margin-left: auto;
	margin-right: auto;
      }
      #checkin-list {
        float: left;
	width: 20%;
	margin-right: 2%;
      }
      #checkin-list li {
        margin-bottom: .2em;
	padding: .2em;
	list-style-type: none;
	background-color: #f0f0f0;
      }
      #checkin-list li:hover        { background-color: #e0e0e0; }
      #checkin-list .selected:hover { background-color: #c0c0c0; } 
      #checkin-list .selected       { background-color: #c0c0c0; }     
      .place {
        color: black;
        font-weight: bold;
	padding-left: .6em;
	border-left: solid #5680fc;
      }
      .time {
        color: black;
        font-style: italic;
	padding-left: .6em;
	border-left: solid #5680fc;
      }
      #checkin-0 div {
        border-left: solid #fc6355; 
      }
      #map-canvas {
	height: 80%;
      }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <script>
google.maps.visualRefresh = true;
var marker = [];
var map = null;

function initialize() {
  var myLatlng = new google.maps.LatLng(<?=str_replace(" ", ",", $checkins[0]["coord"])?>);
  var mapOptions = {
    mapTypeId: google.maps.MapTypeId.ROADMAP
  }
  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

  marker[0] = new google.maps.Marker({
      position: myLatlng,
      icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
      map: map,
      title: "<?= $checkins[0]["name"] ?>",
      zIndex: 1
  });

  <?php for ($i=1; $i<sizeof($checkins); $i++) {?>
    marker[<?= $i ?>] = new google.maps.Marker({
      position: new google.maps.LatLng(<?=str_replace(" ", ",", $checkins[$i]["coord"])?>),
      icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
      map: map,
      title: "<?= $checkins[$i]["name"] ?>",
      zIndex: 0
    });
  <?php } ?>

  map.fitBounds(getMarkerBounds());
}

function toggleHighlight (num) {

  if (marker[num].getAnimation() == null) {
    for (i=0; i<marker.length; i++) {
      if (marker[i].getAnimation() != null) {
        unhighlight(i);
      }
    }
    highlight(num);    
  } else {
    unhighlight(num);
  }
}

function highlight (num) {

   if (!map.getBounds().contains(marker[num].getPosition())) {
     map.panTo(marker[num].getPosition());
   }
   marker[num].setAnimation(google.maps.Animation.BOUNCE);
   document.getElementById("checkin-"+num).className = "checkin selected";

}

function unhighlight (num) {

   marker[num].setAnimation(google.maps.Animation.null);
   document.getElementById("checkin-"+num).className = "checkin";

}

function getMarkerBounds() {

   result = new google.maps.LatLngBounds();
   for (i=0; i<marker.length; i++) {
     result.extend(marker[i].getPosition());
   }
   return result;

}

google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>

<body>
<div id="content">
<div id="checkin-list">
<ul>
  <?php foreach ($checkins as $num=>$checkin) {
    if ($num == 0) { ?> 
  <li id="checkin-0" class="checkin"
      onClick="toggleHighlight(0)"> 
  <?php } else { ?>
  <li id="checkin-<?= $num ?>"
      class="checkin"
      onClick="toggleHighlight(<?= $num ?>)">
  <?php } ?>
    <div id="place-<?= $num ?>"
         class="place">
	 <?= $checkin["name"] ?>
    </div>
    <div class="time"><?= time2str($checkin["time"]) ?></div>
   </li>	
  <?php } ?>
</ul>
</div>
<div id="map-canvas"></div>
</div>
</body>
</html>
