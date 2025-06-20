<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@main/dist/en/latest/ol/ol.css">
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
    <script src="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@main/dist/en/latest/ol/dist/ol.js"></script>
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
                    zoom: 10,
                    enableRotation: false   // Replace Hook
            })
        });
        var layers = [];
        var icons = [];
        var Markers = [];  // Replace Hook
        var numMovable = 0;  // Replace Hook

        let url = new URL(document.URL);
        let add = url.search==''?'?':'&';
        
        Markers.forEach(function(Marker, index){
            layers[index] = new ol.layer.Vector({
                source: new ol.source.Vector({
                    features: [
                        new ol.Feature({
                            geometry: new ol.geom.Point(ol.proj.fromLonLat(Marker.position))
                        })
                    ]
                }),
                style: [
                    new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 4,
                            fill: new ol.style.Fill({
                                color: (Marker.color == -1)?'rgba(0,0,0,0)':'rgba('+Marker.color+',1)'
                            })
                        }),
                        text: new ol.style.Text({
                            offsetY: 15,
                            font: '18px Calibri,sans-serif',
                            text: Marker.name,
                            fill: new ol.style.Fill({
                                color: (Marker.color == -1)?'rgba(0,0,0,0)':'rgba('+Marker.color+',1)'
                            }),
                            stroke: new ol.style.Stroke({
                                color: (Marker.color == -1)?'rgba(0,0,0,0)':'rgba('+Marker.color+',0.2)',
                                width: 3
                            })
                            
                        })
                    }),
                ],
                zoom:Marker.zoom
            });
            map.addLayer(layers[index]);
            icons[index] = new ol.layer.Vector({
                source: new ol.source.Vector({
                    features: [
                        new ol.Feature({
                            geometry: new ol.geom.Point(ol.proj.fromLonLat(Marker.position))
                        })
                    ]
                }),
                style: [
                    new ol.style.Style({
                        image: new ol.style.Icon({
                            scale: Marker.scale,
                            anchor: [0.5, 1.1],
                            src: document.URL+add+'icon='+Marker.id
                        })
                    }),
                ]
            });
            map.addLayer(icons[index]);
        })

        map.getView().setMaxZoom(21);
        var maxExtent = [0,0,0,0];
        for (let i = 0; i < 2; i++) {
            layers.forEach(function(item, index) {
                if(item.values_.zoom){
                    if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                    maxExtent[i] = Math.min(maxExtent[i],item.getSource().getExtent()[i]);
                }
            })
        } 
        for (let i = 2; i < 4; i++) {
            layers.forEach(function(item, index) {
                if(item.values_.zoom){
                    if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                    maxExtent[i] = Math.max(maxExtent[i],item.getSource().getExtent()[i]);
                }
            })
        } 
        
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

        // Initiate websocket which will deliver continuous position updates
        // We need to properly map our host/protocol to the websocket address

        window.socket = new WebSocket(url.protocol.replace(/^http/, 'ws') + "//" + url.host + url.pathname);

        // Listen for messages
        window.socket.addEventListener('message', function (event) {
            let data = JSON.parse(event.data);
            let pos = JSON.parse(data[0]);
            console.log('Position', data.id, pos);
            const source = new ol.source.Vector({
                features: [
                    new ol.Feature({
                        geometry: new ol.geom.Point(ol.proj.fromLonLat([pos.lon,pos.lat]))
                    })
                ]
            });      
            Markers.forEach(function(Marker, index){
                if(data.id == Marker.id){
                    layers[index].setSource(source);
                    icons[index].setSource(source);
                }
            });

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
