<?php
	private function printScriptMap(){
		$returnstr = '
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,marker&key={INSERT_KEY}&callback=initmap&loading=async" async defer></script>
<script type="text/javascript">
var map, marker, geocoder, autocomplete, mapInitialized = false;

function initMap() {
    geocoder = new google.maps.Geocoder();

    // Default position if lat/lng not set
    var latVal = parseFloat(document.getElementById("lat").value);
    var lngVal = parseFloat(document.getElementById("lng").value);

    var defaultPos = (!isNaN(latVal) && !isNaN(lngVal)) ? {lat: latVal, lng: lngVal} : {lat: 52.2, lng: 5.5};

    map = new google.maps.Map(document.getElementById("google_maps"), {
        center: defaultPos,
        zoom: 12,
        mapId: "{INSERT_MAPID}",
        mapTypeId: "roadmap",
        tilt: 0
    });

    marker = new google.maps.Marker({
        position: defaultPos,
        map: map,
        draggable: true
    });

    // Update lat/lng on drag
    marker.addListener("dragend", function() {
        var pos = marker.getPosition();
        document.getElementById("lat").value = pos.lat();
        document.getElementById("lng").value = pos.lng();
    });

    // Setup Autocomplete
    var input = document.getElementById("pac-input");
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
    autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", function() {
        var place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) return;

        if (place.geometry.viewport) map.fitBounds(place.geometry.viewport);
        else map.setCenter(place.geometry.location);

        map.setZoom(17);
        marker.setPosition(place.geometry.location);

        document.getElementById("lat").value = place.geometry.location.lat();
        document.getElementById("lng").value = place.geometry.location.lng();
    });

    mapInitialized = true;
}

// Show/hide map and update marker based on lat/lng or address fields
document.addEventListener("DOMContentLoaded", function() {
    var showBtn = document.getElementById("show_map");
    var mapDiv = document.getElementById("google_maps");
    var getCoordBtn = document.getElementById("get_coordinates");

    showBtn.addEventListener("click", function() {
        if (mapDiv.style.display === "none") {
            mapDiv.style.display = "block";
            getCoordBtn.style.display = "inline-block";

            if (!mapInitialized) initMap();
            else {
                var latVal = parseFloat(document.getElementById("lat").value);
                var lngVal = parseFloat(document.getElementById("lng").value);

                if (!isNaN(latVal) && !isNaN(lngVal)) {
                    var pos = {lat: latVal, lng: lngVal};
                    map.setCenter(pos);
                    marker.setPosition(pos);
                } else {
                    var address = [
                        document.getElementById("address").value,
                        document.getElementById("number").value,
                        document.getElementById("city").value,
                        document.getElementById("country").value
                    ].filter(Boolean).join(" ");

                    if (address) {
                        geocoder.geocode({address: address}, function(results, status) {
                            if (status === "OK" && results[0].geometry.location) {
                                map.setCenter(results[0].geometry.location);
                                marker.setPosition(results[0].geometry.location);
                                document.getElementById("lat").value = results[0].geometry.location.lat();
                                document.getElementById("lng").value = results[0].geometry.location.lng();
                            }
                        });
                    }
                }
                google.maps.event.trigger(map, "resize");
            }

            showBtn.value = "Hide Map";
        } else {
            mapDiv.style.display = "none";
            getCoordBtn.style.display = "none";
            showBtn.value = "Show Map";
        }
    });

    getCoordBtn.addEventListener("click", function() {
        if (marker) {
            var pos = marker.getPosition();
            document.getElementById("lat").value = pos.lat();
            document.getElementById("lng").value = pos.lng();
        }
    });
});
</script>';
		return $returnstr;
	}
	
?>