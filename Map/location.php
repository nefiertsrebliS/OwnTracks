<?php
    $Menu = array();

    $places = json_decode($this->ReadPropertyString('Places'));
    array_multisort(array_column($places,'Order'), $places);

    foreach($places as $place){
        if(!$place->Movable)continue;
        $color = substr("000000".dechex($place->Color),-6);
        $colorStr = 'rgb('.hexdec(substr($color,0, 2)).','.hexdec(substr($color,2, 2)).','.hexdec(substr($color,4, 2)).')';
        if($place->Color == -1)$colorStr = -1;
        $Menu[] = array(
            "Id"=>$place->Name,
            "Color"=>$colorStr,
            "Source"=>md5($place->Location),
        );
    }

    if(isset($_GET['Source'])){
        $newPosition = json_encode([
            "latitude"=>json_decode($_GET['Position'])[1],
            "longitude"=>json_decode($_GET['Position'])[0]
        ]);

        $places = json_decode($this->ReadPropertyString('Places'));
        foreach($places as &$place){
            if(md5($place->Location) == $_GET['Source']){
                $place->Location = $newPosition;
                break;
            }
        }
        # SetProperty und ApplyChanges ist erforderlich, um dem User ein Verschieben der Objekte im Webfront zu ermöglichen
        IPS_SetProperty($this->InstanceID, 'Places', json_encode($places));
        IPS_ApplyChanges($this->InstanceID);
        return;
    }

?>

<!DOCTYPE html>
	<head>
        <meta charset="utf-8">
        <title>OwnTracks Positionen</title>
		<style type="text/css">
            body{
                height:100vh;
                width:100vw;
                margin:0;
                padding: 0;
                background-color: rgba(0,0,0,0.7);
                overflow: hidden;
                position: fixed;
            }
            menu {
                margin: 0;
                padding: 0;
                overflow: hidden;
                width: 100vw;
            }
            item {
                border-radius:0.5vh;
                border:2vh solid white;
            }
            item:hover{
                background-color: #111;
            }
            IMG {
                position: relative;
                top: 50%;
                left: 50%;
                transform: translate(-50%,-50%);
                -ms-transform: translate(-50%,-50%);
            }
        </style>
		<script>
            let url = new URL(document.URL);
            let params = new URLSearchParams(url.search);
            params.delete('Position'); 
            params.append('icon', ''); 
            url.search = params.toString();

            var Menu = <?echo json_encode($Menu);?>;

            function menu() {
                var inner = "";
                var image;
                var size = 0.86 * window.innerHeight;
                var top = 0.05 * window.innerHeight;
                var left = (window.innerWidth - Menu.length * 0.9 * window.innerHeight) / (Menu.length + 1);

                Menu.forEach(function(item, i) {
                    image = '<IMG src="'+url.toString()+item.Source+'" style="max-height: '+0.95*size+'px; max-width: '+0.95*size+'px;";>';
                    inner += '<item style="float: left; height: '+size+'px; width: '+size+'px; margin-top: '+top+'px; margin-left: '+left+'px; border-color: '+ item.Color+'"';          // >
                    inner += '><a href="'+document.URL+'&Source='+item.Source+'">'+image+'</a></item>';
                });
                document.getElementById("menu").innerHTML = inner;
            }

            window.onload = function(){
                menu();
            }
        </script>
	</head>

	<body>
   		<menu id="menu"></menu>
	</body>
</html>