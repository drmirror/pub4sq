<?php

# File:        pub4sq.php
# Author:      Andre Spiegel <spiegel@drmirror.net>
# Description: Displays a map with your last few Foursquare checkins.

# Below is my Foursquare RSS feed URL.  Get your own from
# http://foursquare.com/feeds and put between the quotes. 

$url = "https://api.foursquare.com/v2/users/self/checkins?oauth_token=ENTER_YOUR_TOKEN_HERE&v=20190101";
$count = 7; # number of checkins to show

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

$data = file_get_contents($url . "&limit=" . $count);
$contents = json_decode($data);
$items = $contents->{'response'}->{'checkins'}->{'items'};

foreach ($items as $key=>$item) {

  $name = $item->{'venue'}->{'name'};
  $checkins[$key]["name"] = $name;
  $checkins[$key]["time"] = $item->{'createdAt'};
  if ($name == "One Sixty") {
    $checkins[$key]["coord"] = "40.7776,-73.9815";
  } else {
    $checkins[$key]["coord"] = $item->{'venue'}->{'location'}->{'lat'} . ',' . $item->{'venue'}->{'location'}->{'lng'};
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
	width: 15%;
	margin-right: 2%;
      }
      ul { margin: 0; padding: 0; }
      #checkin-list li {
        margin-bottom: .2em;
	padding: .2em;
	list-style-type: none;
	background-color: #f0f0f0;
      }
      #checkin-list li:hover        { background-color: #e0e0e0;
                                      cursor: pointer; }
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

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-40543116-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

    <script src="https://maps.googleapis.com/maps/api/js?key=ENTER_YOUR_GOOGLE_MAPS_API_KEY_HERE&v=3.exp&sensor=false"></script>
    <script>
google.maps.visualRefresh = true;
var marker = [];
var map = null;

function initialize() {
  var myLatlng = new google.maps.LatLng(<?= $checkins[0]["coord"] ?>);
  var mapOptions = {
    mapTypeId: google.maps.MapTypeId.ROADMAP
  }
  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

  marker[0] = new google.maps.Marker({
      position: myLatlng,
      icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
      map: map,
      title: "<?= str_replace('"', '\\"', $checkins[0]["name"]) ?>",
      zIndex: 1
  });

  <?php for ($i=1; $i<sizeof($checkins); $i++) {?>
    marker[<?= $i ?>] = new google.maps.Marker({
      position: new google.maps.LatLng(<?= $checkins[$i]["coord"] ?>),
      icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
      map: map,
      title: "<?= str_replace('"', '\\"', $checkins[$i]["name"]) ?>",
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
