            <!doctype html>
            <html lang="en">
              <head>
                <meta charset="utf-8">
                <link rel="stylesheet" href="https://openlayers.org/en/v6.9.0/css/ol.css" type="text/css">
                <style>
                    .map {
                        background-color: rgba(0, 0, 0, 1);
                        margin: -3px;
                    }

                    .overlay {
                        position: fixed;
                        margin: -3px;
                        display: none;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0,0,0,0.5);
                        z-index: 2;
                        cursor: pointer;
                    }
                </style>
                <script src="https://openlayers.org/en/v6.9.0/build/ol.js"></script>
                <title>OwnTracks Positionen</title>
              </head>
              <body>
                <div class="overlay" id="overlay" onclick=OverlayOff()></div>
                <div id="map" class="map"></div>
                <script type="text/javascript">
                        // Resize Map

                        document.getElementById("map").style.height = (window.innerHeight-10)+"px";
                        document.getElementById("map").style.width = (window.innerWidth-10)+"px";
            
            
                    var map = new ol.Map({
                        target: 'map',
                        layers: [
                            new ol.layer.Tile({
                                source: new ol.source.OSM()
                            })
                        ],
                        view: new ol.View({
                              center: ol.proj.fromLonLat([11.41, 52.82]),
                              zoom: 10
                        })
                    });
                    var layers = [];
                    var icons = "";
                    var Markers = <?
                        $Markers = array();
                        $numMovable = 0;

                        $places = json_decode($this->ReadPropertyString('Places'));
                        array_multisort(array_column($places,'Order'), $places);

                        foreach($places as $place){
                            if(@$place->Movable)$numMovable++;
                            $color = substr("000000".dechex($place->Color),-6);
                            $colorStr = hexdec(substr($color,0, 2)).','.hexdec(substr($color,2, 2)).','.hexdec(substr($color,4, 2));
                            if($place->Color == -1)$colorStr = -1;
                            $position = json_decode($place->Location);
                            $place->Name = ($place->Show)?$place->Name:"";
                    
                            $Markers[] = array(
                                $place->Name,
                                $colorStr,
                                array($position->longitude, $position->latitude),
                                $place->Scale,
                                md5($place->Location)
                            );
                        }

                        $homeID = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
                        $devices = json_decode($this->ReadPropertyString('Devices'));
                        array_multisort(array_column($devices,'Order'), $devices);

                        foreach($devices as $device){
                            if($device->InstanceID == $homeID){
                                $position = json_decode(IPS_GetProperty($homeID, 'Location'));
                                $position->lat = $position->latitude;
                                $position->lon = $position->longitude;
                            }else{
                                $position = json_decode(GetValue(IPS_GetObjectIDByIdent('position', $device->InstanceID)));
                            }
                            $color = substr("000000".dechex($device->Color),-6);
                            $colorStr = hexdec(substr($color,0, 2)).','.hexdec(substr($color,2, 2)).','.hexdec(substr($color,4, 2));
                            if($device->Color == -1)$colorStr = -1;
                            $device->Name = ($device->Show)?$device->Name:"";
                    
                            $Markers[] = array(
                                $device->Name,
                                $colorStr,
                                array($position->lon, $position->lat),
                                $device->Scale,
                                $device->InstanceID,
                                $device->Zoom
                            );
                        }
                        echo json_encode($Markers);
                    ?>;
                    var numPlaces = <?echo count($places);?>;
                    var numMovable = <?echo $numMovable;?>;

                    let url = new URL(document.URL);
                    let add = url.search==''?'?':'&';
                    
                    Markers.forEach(function(Marker, index){
                        layers[index] = new ol.layer.Vector({
                            source: new ol.source.Vector({
                                features: [
                                    new ol.Feature({
                                        geometry: new ol.geom.Point(ol.proj.fromLonLat(Marker[2]))
                                    })
                                ]
                            }),
                            style: [
                                new ol.style.Style({
                                    image: new ol.style.Circle({
                                        radius: 4,
                                        fill: new ol.style.Fill({
                                            color: (Marker[1] == -1)?'rgba(0,0,0,0)':'rgba('+Marker[1]+',1)'
                                        })
                                    }),
                                    text: new ol.style.Text({
                                        offsetY: 15,
                                        font: '18px Calibri,sans-serif',
                                        text: Marker[0],
                                        fill: new ol.style.Fill({
                                            color: (Marker[1] == -1)?'rgba(0,0,0,0)':'rgba('+Marker[1]+',1)'
                                        }),
                                        stroke: new ol.style.Stroke({
                                            color: (Marker[1] == -1)?'rgba(0,0,0,0)':'rgba('+Marker[1]+',0.2)',
                                            width: 3
                                        })
                                        
                                    })
                                }),
                            ],
                            zoom:Marker[5]
                        });
                        map.addLayer(layers[index]);
                        icons = new ol.layer.Vector({
                            source: new ol.source.Vector({
                                features: [
                                    new ol.Feature({
                                        geometry: new ol.geom.Point(ol.proj.fromLonLat(Marker[2]))
                                    })
                                ]
                            }),
                            style: [
                                new ol.style.Style({
                                    image: new ol.style.Icon({
                                        scale: Marker[3],
                                        anchor: [0.5, 1.1],
                                        src: document.URL+add+'icon='+Marker[4]
                                    })
                                }),
                            ]
                        });
                        map.addLayer(icons);
                    })
            
                    map.getView().setMaxZoom(21);
                    var maxExtent = [0,0,0,0];
                    for (let i = 0; i < 2; i++) {
                        layers.forEach(function(item, index) {
                            if((index > numPlaces - 1) && (item.A.zoom)){
                                if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                                maxExtent[i] = Math.min(maxExtent[i],item.getSource().getExtent()[i]);
                            }
                        })
                    } 
                    for (let i = 2; i < 4; i++) {
                        layers.forEach(function(item, index) {
                            if((index > numPlaces - 1) && (item.A.zoom)){
                                if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                                maxExtent[i] = Math.max(maxExtent[i],item.getSource().getExtent()[i]);
                            }
                        })
                    } 
                    
                    var layerExtent = layers[0].getSource().getExtent();
                    map.getView().fit(maxExtent , map.getSize());
                    var mapZoom = ((map.getView().getZoom()> 18)?18:map.getView().getZoom()) * 0.98;
                    map.getView().setZoom(mapZoom);

                    map.on('click', function(evt){
                        if(numMovable > 0){
                            var NewPosition = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                            if(window.innerWidth / numMovable > window.innerHeight){
                                var height = (window.innerHeight * 0.5 > 150)?150:window.innerHeight * 0.5;
                            }else{
                                var height = (window.innerWidth / numMovable * 0.9 > 150)?150:window.innerWidth / numMovable * 0.9;
                            }
                            OverlayIframeOn(height, height * numMovable, document.URL+add+"Position="+JSON.stringify(NewPosition));
                        }
                    });

                    //---------------------------------------------------------------------------------------------------------
                    //		Overlays
                    //---------------------------------------------------------------------------------------------------------

                    function OverlayIframeOn(height, width, content) {
                        var marginh = (window.innerHeight - 20 - height)/2;
                        var marginw = (window.innerWidth -20 - width)/2;
                        var inner = '<div style="height:'+height+'px; width:'+width+'px; margin: '+marginh+'px '+marginw+'px '+marginh+'px '+marginw+'px;"><iframe style="height: 100%; width:100%; " SRC="'+content+'"></iframe></div>';
                        document.getElementById("overlay").style.display = "block";
                        document.getElementById("overlay").innerHTML = inner;
                    }
            
                    function OverlayOff() {
                        document.getElementById("overlay").innerHTML = "";
                        document.getElementById("overlay").style.display = "none";
                    }
                </script>
              </body>
            </html>
