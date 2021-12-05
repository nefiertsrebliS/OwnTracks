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
                </style>
                <script src="https://openlayers.org/en/v6.9.0/build/ol.js"></script>
                <title>OwnTracks Positionen</title>
              </head>
              <body>
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
                    var Markers = <?
                        $Markers = array();
                        foreach(json_decode($this->ReadPropertyString('Devices')) as $device){
                            $position = json_decode(GetValue(IPS_GetObjectIDByIdent('position', $device->InstanceID)));
                            $color = substr("000000".dechex($device->Color),-6);
                            $colorStr = hexdec(substr($color,0, 2)).','.hexdec(substr($color,2, 2)).','.hexdec(substr($color,4, 2));
                    
                            $Markers[] = array(
                                $device->Name,
                                $colorStr,
                                array($position->lon, $position->lat)
                            );
                        }
                        echo json_encode($Markers);
                    ?>;
                    console.log(Markers);
                    
                    Markers.forEach(function(Marker, index){
                        console.log(index, Marker);
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
                                        radius: 10,
                                        fill: new ol.style.Fill({
                                            color: 'rgba('+Marker[1]+',0.2)'
                                        }),
                                        stroke: new ol.style.Stroke({
                                            color: 'rgb('+Marker[1]+')',
                                            width : 3    
                                        })
                                    }),
                                    text: new ol.style.Text({
                                        offsetY: 20,
                                        font: '18px Calibri,sans-serif',
                                        text: Marker[0],
                                        fill: new ol.style.Fill({
                                            color: 'rgb('+Marker[1]+')',
                                        }),
                                        stroke: new ol.style.Stroke({
                                            color: 'rgba('+Marker[1]+',0.2)',
                                            width: 3
                                        })
                                        
                                    })
                                }),
                            ]
                        });
                        map.addLayer(layers[index]);
                    })
            
                    map.getView().setMaxZoom(18);
                    var maxExtent = [0,0,0,0];
                    for (let i = 0; i < 2; i++) {
                        layers.forEach(function(item) {
                            if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                            maxExtent[i] = Math.min(maxExtent[i],item.getSource().getExtent()[i]);
                        })
                    } 
                    for (let i = 2; i < 4; i++) {
                        layers.forEach(function(item) {
                            if(maxExtent[i] == 0)maxExtent[i] = item.getSource().getExtent()[i];
                            maxExtent[i] = Math.max(maxExtent[i],item.getSource().getExtent()[i]);
                        })
                    } 
                    console.log(maxExtent);
                    
                    
                    
                    
                    var layerExtent = layers[0].getSource().getExtent();
                    console.log(map);
                    console.log(layerExtent);
                    map.getView().fit(maxExtent , map.getSize());
                    console.log(map.getView().getZoom());
                    map.getView().setZoom(map.getView().getZoom() * 0.98);
                    console.log(map.getView().getZoom());
            
            map.addControl(new ol.control.LayerSwitcher(
             [
                 {
                     layer : layers[0],
                     config : {
                         title : "test layer name 1",
                         description : "test layer desc 1",
                     }
                 }
             ],
             {
                 collapsed : true
             }
            ));
            
                </script>
              </body>
            </html>
