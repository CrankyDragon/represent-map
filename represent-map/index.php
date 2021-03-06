<?php
include "header.php";

// get places
$places = mysql_query("SELECT * FROM places WHERE approved='1'");

?>

<!DOCTYPE html>
<html>
  <head>
    <!--
    This site was based on the Represent.LA project by:
    - Alex Benzer
    - Tara Tiger Brown
    - Sean Bonner
    
    Create a map for your startup community!
    https://github.com/abenzer/represent-map
    -->
    <title>represent.la - map of the Los Angeles startup community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta charset="UTF-8">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:700|Open+Sans:400,700' rel='stylesheet' type='text/css'>
    <link href="/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="map.css" type="text/css" />
    <link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" />
    <script src="/scripts/jquery-1.7.1.js" type="text/javascript" charset="utf-8"></script>
    <script src="/bootstrap/js/bootstrap.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="/scripts/label.js"></script>
    <script type="text/javascript">
      var map;
      var infowindow = null;
      var gmarkers = [];
      var highestZIndex = 0;  
      var agent = "default";
      var zoomControl = true;
      
      
      // detect browser agent
      $(document).ready(function(){
        if(navigator.userAgent.toLowerCase().indexOf("iphone") > -1 || navigator.userAgent.toLowerCase().indexOf("ipod") > -1) {
          agent = "iphone";
          zoomControl = false;
        }
        if(navigator.userAgent.toLowerCase().indexOf("ipad") > -1) {
          agent = "ipad";
          zoomControl = false;
        }
      }); 

      
      
      
      function initialize() {
        // set map styles
        var mapStyles = [
          {
            featureType: "administrative.land_parcel",
            stylers: [
              { visibility: "off" }
            ]
          },{
            featureType: "water",
            stylers: [
              { visibility: "on" },
              { saturation: 31 },
              { lightness: 39 }
            ]
          },{
            featureType: "road.highway",
            stylers: [
              { visibility: "simplified" },
              { lightness: 18 }
            ]
          }
        ];
        
        // set map options
        var myOptions = {
          zoom: 12,
          minZoom: 10,
          center: new google.maps.LatLng(34.034453,-118.341293),
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          panControl: false,
          streetViewControl: false,
          mapTypeControl: false,
          zoomControl: zoomControl,
          styles: mapStyles,
          zoomControlOptions: {
            style: google.maps.ZoomControlStyle.SMALL,
            position: google.maps.ControlPosition.TOP_LEFT
          }
        };
        map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
        zoomLevel = map.getZoom();
            
        // prepare infowindow
        infowindow = new google.maps.InfoWindow({
          content: "holding..."
        });
        
        // only show marker labels if zoomed in
        google.maps.event.addListener(map, 'zoom_changed', function() {
          zoomLevel = map.getZoom();
          if(zoomLevel <= 15) {
            $(".marker_label").css("display", "none");
          } else {
            $(".marker_label").css("display", "inline");
          }
        });
       

        // markers array: name, type (icon), lat, long, description, uri, address
        markers = new Array();
        <?php
          while($place = mysql_fetch_assoc($places)) {
            $place[title] = addslashes(htmlspecialchars($place[title]));
            $place[description] = addslashes(htmlspecialchars($place[description]));
            $place[uri] = addslashes(htmlspecialchars($place[uri]));
            $place[address] = addslashes(htmlspecialchars($place[address]));
            echo "
              markers.push(['".$place[title]."', '".$place[type]."', '".$place[lat]."', '".$place[lng]."', '".$place[description]."', '".$place[uri]."', '".$place[address]."']); 
            "; 
            switch($place[type]) {
              case "startup":
                $count[startup]++; break;
              case "incubator":
                $count[incubator]++; break;
              case "accelerator":
                $count[accelerator]++; break;
              case "coworking":
                $count[coworking]++; break;
              case "investor":
                $count[investor]++; break;
              case "event":
                $count[event]++; break;
            }
          }
        ?>
        
        // add markers
        jQuery.each(markers, function(i, val) {
          infowindow = new google.maps.InfoWindow({
            content: ""
          });
          
          // offset latlong ever so slightly to prevent marker overlap
          rand_x = Math.random();
          rand_y = Math.random();
          val[2] = parseFloat(val[2]) + parseFloat(parseFloat(rand_x) / 6000);
          val[3] = parseFloat(val[3]) + parseFloat(parseFloat(rand_y) / 6000);
          
          // show smaller marker icons on mobile
          if(agent == "iphone") {
            var iconSize = new google.maps.Size(16,19);
          } else {
            iconSize = null;
          }
          
          // build this marker
          var markerImage = new google.maps.MarkerImage("./images/icons/"+val[1]+".png", null, null, null, iconSize);
          var marker = new google.maps.Marker({
            position: new google.maps.LatLng(val[2],val[3]),
            map: map,
            title: '',
            clickable: true,
            infoWindowHtml: '',
            zIndex: 10 + i,
            icon: markerImage
          });
          marker.type = val[1];
          gmarkers.push(marker);
          
          // add marker hover events (if not viewing on mobile)
          if(agent == "default") {
            google.maps.event.addListener(marker, "mouseover", function() {
              this.old_ZIndex = this.getZIndex(); 
              this.setZIndex(9999); 
              $("#marker"+i).css("display", "inline");
              $("#marker"+i).css("z-index", "99999");
            });
            google.maps.event.addListener(marker, "mouseout", function() { 
              if (this.old_ZIndex && zoomLevel <= 15) {
                this.setZIndex(this.old_ZIndex); 
                $("#marker"+i).css("display", "none");
              }
            }); 
          }
          
          // format marker URI for display and linking
          var markerURI = val[5];
          if(markerURI.substr(0,7) != "http://") {
            markerURI = "http://" + markerURI; 
          }
          var markerURI_short = markerURI.replace("http://", "");
          var markerURI_short = markerURI_short.replace("www.", "");
          
          // add marker click effects (open infowindow)
          google.maps.event.addListener(marker, 'click', function () {
            infowindow.setContent(
              "<div class='marker_title'>"+val[0]+"</div>"
              + "<div class='marker_uri'><a target='_blank' href='"+markerURI+"'>"+markerURI_short+"</a></div>"
              + "<div class='marker_desc'>"+val[4]+"</div>"
              + "<div class='marker_address'>"+val[6]+"</div>"
            );
            infowindow.open(map, this);
          });
          
          // add marker label
          var latLng = new google.maps.LatLng(val[2], val[3]);
          var label = new Label({
            map: map,
            id: i
          });
          label.bindTo('position', marker);
          label.set("text", val[0]);
          label.bindTo('visible', marker);
          label.bindTo('clickable', marker);
          label.bindTo('zIndex', marker);
        });
      } 
      
      // toggle (hide/show) markers of a given type
      function toggle(type) {
        if($("#filter_"+type).attr('checked') == "checked") {
          show(type); 
        } else {
          hide(type); 
        }
      }
      
      // hide all markers of a given type
      function hide(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(false);
          }
        }
      }

      // show all markers of a given type
      function show(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(true);
          }
        }
      }
      
      google.maps.event.addDomListener(window, 'load', initialize);
      
      
    </script>
    
    <!-- google analytics -->
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-32378786-1']);
      _gaq.push(['_trackPageview']);
      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
    
  </head>
  <body>
    
    <!-- facebook like button code -->
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=421651897866629";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>
    
    <!-- google map -->
    <div id="map_canvas"></div>
    
    <!-- main menu bar -->
    <div class="menu">
      <div class="wrapper">
        <div class="logo">
          <a href="./">
            <img src="images/logo.png" alt="" />
          </a>
        </div>
        <ul class="filters">
          <li>
            <input type="checkbox" id="filter_startup" checked="checked" onClick="toggle('startup')">
            <label for="filter_startup">Startups <span>(<?=0+$count[startup]?>)</span></label>
          </li>
          <li>
            <input type="checkbox" id="filter_accelerator" checked="checked" onClick="toggle('accelerator')">
            <label for="filter_accelerator">Accelerators <span>(<?=0+$count[accelerator]?>)</span></label>
          </li>
          <li>
            <input type="checkbox" id="filter_incubator" checked="checked" onClick="toggle('incubator')">
            <label for="filter_incubator">Incubators <span>(<?=0+$count[incubator]?>)</span></label>
          </li>
          <li>
            <input type="checkbox" id="filter_coworking" checked="checked" onClick="toggle('coworking')">
            <label for="filter_coworking">Coworking <span>(<?=0+$count[coworking]?>)</span></label>
          </li>
          <li>
            <input type="checkbox" id="filter_investor" checked="checked" onClick="toggle('investor')">
            <label for="filter_investor">Investors <span>(<?=0+$count[investor]?>)</span></label>
          </li>
          <!--
          <li>
            <input type="checkbox" id="filter_event" checked="checked" onClick="toggle('event')">
            <label for="filter_event">Upcoming Events <span>(<?=0+$count[event]?>)</span></label>
          </li>
          -->
        </ul>
        <div class="add">
          <a href="#modal_add" class="btn btn-large btn-inverse" data-toggle="modal">Add Something!</a>
        </div>
        <div class="info">
          <a href="#modal_info" class="btn btn-large" data-toggle="modal">More Info</a>
        </div>
        <div class="blurb">
          This map was made to connect and promote the Los Angeles tech startup community.
          Let's put LA on the map!
        </div>
        <div class="share">
          <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.represent.la" data-text="Let's put Los Angeles startups on the map:" data-via="representla" data-count="none">Tweet</a>
          <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
          <div class="fb-like" data-href="http://www.represent.la" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false" data-font="arial"></div>
        </div>
        <div class="blurb">
          <!-- per our license, you may not remove this line -->
          <?=$attribution?>
        </div>
      </div>
    </div>
    
    
    <!-- main menu bar (mobile) -->
    <div class="menu_mobile">
      <div class="wrapper">
        <div class="buttons">
          <a href="#modal_add" class="btn btn-large btn-inverse" data-toggle="modal">Add</a>
          <a href="#modal_info" class="btn btn-large" data-toggle="modal">Info</a>
        </div>
        <div class="logo">
          <a href="http://represent.la/">
            <img src="images/logo.png" alt="RepresentLA" />
          </a>
        </div>
      </div>
    </div>
    
    
    <!-- more info modal -->
    <div class="modal hide" id="modal_info">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>About This Map</h3>
      </div>
      <div class="modal-body">
        <p>
          We built this map to connect and promote the tech startup community
          in our beloved Los Angeles. We've seeded the map but we need
          your help to keep it fresh. If you don't see your company,
          please <a href="#modal_add" data-toggle="modal" data-dismiss="modal">submit it here</a>.
          Let's put LA on the map together!
        </p>
        <p>
          Questions? Feedback? Connect with us: <a href="http://www.twitter.com/representla" target="_blank">@representla</a>
        </p>
        <p>
          If you want to support the community by linking to this map from your website,
          here are some badges you might like to use. You can also grab the <a href="./images/badges/LA-icon.ai">LA icon AI file</a>.
        </p>
        <ul class="badges">
          <li>
            <img src="./images/badges/badge1.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge1_small.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge2.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge2_small.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge3.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge3_small.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge4.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge4_small.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge5.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge5_small.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge6.png" alt="">
          </li>
          <li>
            <img src="./images/badges/badge6_small.png" alt="">
          </li>
        </ul>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
      </div>
    </div>
    
    
    
    <!-- add something modal -->
    <div class="modal hide" id="modal_add">
      <form action="add.php" id="modal_addform" class="form-horizontal">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h3>Add Something!</h3>
        </div>
        <div class="modal-body">
          <p>
            Want to add your company to this map?
            Submit it below and we'll review it ASAP.
          </p>
          <div id="result"></div>
          <fieldset>
            <div class="control-group">
              <label class="control-label" for="add_owner_name">Your Name</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_name" id="add_owner_name" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_owner_email">Your Email</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_email" id="add_owner_email" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_title">Company Name</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="title" id="add_title" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="input01">Company Type</label>
              <div class="controls">
                <select name="type" id="add_type" class="input-xlarge">
                  <option value="startup">Startup</option>
                  <option value="accelerator">Accelerator</option>
                  <option value="incubator">Incubator</option>
                  <option value="coworking">Coworking</option>
                  <option value="investor">VC/Angel</option>
                </select>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_address">Street Address</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="address" id="add_address">
                <p class="help-block">
                  Should be your full street address (including city and zip).
                  If it works on Google Maps, it will work here.
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">Website URL</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_uri" name="uri" placeholder="http://">
                <p class="help-block">
                  Should be your full URL with no trailing slash, e.g. "http://www.yoursite.com"
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_description">Description</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_description" name="description" maxlength="150">
                <p class="help-block">
                  Brief, concise description. What's your product? What problem do you solve? Max 150 chars.
                </p>
              </div>
            </div>
          </fieldset>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Submit for Review</button>
          <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
        </div>
      </form>
    </div>
    <script>
      // add modal form submit
      $("#modal_addform").submit(function(event) {
        event.preventDefault(); 
        // get values
        var $form = $( this ),
            owner_name = $form.find( '#add_owner_name' ).val(),
            owner_email = $form.find( '#add_owner_email' ).val(),
            title = $form.find( '#add_title' ).val(),
            type = $form.find( '#add_type' ).val(),
            address = $form.find( '#add_address' ).val(),
            uri = $form.find( '#add_uri' ).val(),
            description = $form.find( '#add_description' ).val(),
            url = $form.attr( 'action' );

        // send data and get results
        $.post( url, { owner_name: owner_name, owner_email: owner_email, title: title, type: type, address: address, uri: uri, description: description },
          function( data ) {
            var content = $( data ).find( '#content' );
            
            // if submission was successful, show info alert
            if(data == "success") {
              $("#modal_addform #result").html("We've received your submission and will review it shortly. Thanks!"); 
              $("#modal_addform #result").addClass("alert alert-info");
              $("#modal_addform p").css("display", "none");
              $("#modal_addform fieldset").css("display", "none");
              $("#modal_addform .btn-primary").css("display", "none");
              
            // if submission failed, show error
            } else {
              $("#modal_addform #result").html(data); 
              $("#modal_addform #result").addClass("alert alert-danger");
            }
          }
        );
      });
    </script>
    
    
  </body>
</html>
