<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/HookHelper.php';

class OwnTracksMap extends IPSModuleStrict
{
    use HookHelper;

    #=====================================================================================
    public function Create(): void
    #=====================================================================================
    {

        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('HookName', '');
        $this->RegisterPropertyString('Devices', '{}');
        $this->RegisterPropertyString('Places', '{}');
        $this->RegisterPropertyString('Height', '98%');
        $this->RegisterPropertyString('Width', '100%');
        $this->RegisterPropertyBoolean('AllowMapRotation', false);
        $this->RegisterPropertyBoolean('AllowMapAutoZoom', true);
        $this->RegisterAttributeString('LoginStatus', '{"Data":[], "LockedIP":[], "Status":102}');
        $this->RegisterAttributeString('verifiedClients', '{}');
        $this->RegisterVariableString('maplink', 'Map', '~HTMLBox');
    }

    #=====================================================================================
    public function Destroy(): void
    #=====================================================================================
    {
        //Never delete this line!
        parent::Destroy();
    }

    #=====================================================================================
    public function ApplyChanges(): void
    #=====================================================================================
    {
        //Never delete this line!
        parent::ApplyChanges();

        $Hookname = $this->ReadPropertyString('HookName');
		if($Hookname !== ''){
			$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
			$formWebHook = json_decode(IPS_GetConfigurationForm($ids[0]));
			$actions = $formWebHook->actions;
			foreach($actions as $action){
				if($action->caption == 'Hooks (Internal)'){
					foreach($action->values as $value){
						if ($value->TargetID == $this->InstanceID){
							if($value->Hook != $Hookname)$this->UnregisterHook($value->Hook);
						}
					}
				}
			}
			$this->RegisterHook($Hookname);
            $maplink = '<iframe src="/hook/'.$Hookname.'?'.$this->getSecret().'" title="OwnTracks"  width="'.$this->ReadPropertyString('Width').'" height="'.$this->ReadPropertyString('Height').'"></iframe>';
            $this->SetValue('maplink', $maplink);
		}

        $this->ResetLock();

        foreach($this->GetMessageList() as $Message =>$Types) if(array_search(VM_UPDATE, $Types) !== false){
            $this->UnregisterMessage($Message, VM_UPDATE);
        }
        $homeID = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
        foreach(json_decode($this->ReadPropertyString('Devices')) as $device){
            if($device->InstanceID != $homeID){
                $this->RegisterMessage(IPS_GetObjectIDByIdent('position', $device->InstanceID), VM_UPDATE);
            }
        }
    }

    #================================================================================================
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, mixed $Data): void
    #================================================================================================
    {

        switch ($Message) {
            case IM_CHANGESTATUS:
                break;
            case VM_UPDATE:
                $hookID = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}')[0];
                $Data["id"]= IPS_GetParent($SenderID);
                WC_PushMessage($hookID, '/hook/'.$this->ReadPropertyString('HookName'), json_encode($Data));
                break;
        }
    }

    #=====================================================================================
    protected function ProcessHookData(): void
    #=====================================================================================
    {
        if (isset($_SERVER['HTTP_UPGRADE']) && (trim($_SERVER['HTTP_UPGRADE']) == 'websocket')) { 
            if(file_get_contents("php://input") == $this->getSecret()){
                $hookID = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}')[0];
                foreach($this->GetMessageList() as $SenderID=>$message){
                    $Data = array();
                    $Data[0]= GetValue($SenderID);
                    $Data["id"]= IPS_GetParent($SenderID);
                    WC_PushMessage($hookID, '/hook/'.$this->ReadPropertyString('HookName'), json_encode($Data));
                }
            }
            return;
		}
        if(!$this->ProcessHookSecurity())return;

        if(isset($_GET['icon'])){
            if(is_numeric($_GET['icon'])){
                foreach(json_decode($this->ReadPropertyString('Devices')) as $device)if($device->InstanceID == $_GET['icon'])break;
                $imgdata = base64_decode($device->Icon);
            }else{
                foreach(json_decode($this->ReadPropertyString('Places')) as $place)if(md5($place->Location) == $_GET['icon'])break;
                $imgdata = base64_decode($place->Icon);
            }
            $mimetype = $this->getImageMimeType($imgdata);
            $headhtml =  'Content-Type: image/'.$mimetype;
            header($headhtml);
            echo $imgdata;
        }elseif(isset($_GET['Position'])){
            require(__DIR__ . '/location.php');
        }else{
            echo $this->GetMapData();
        }
                
    }

    #=====================================================================================
    private function GetMapData(): string
    #=====================================================================================
    {
        $map = file_get_contents(__DIR__ . '/map.php');
        $map = str_replace('var socketOpen = "";   // Replace Hook', 'var socketOpen = "'.$this->getSecret().'";', $map);
        $map = str_replace('enableRotation: false   // Replace Hook', 'enableRotation: '.($this->ReadPropertyBoolean("AllowMapRotation")?"true":"false"), $map);
        $map = str_replace('var enableAutoZoom = true;   // Replace Hook', 'var enableAutoZoom = '.($this->ReadPropertyBoolean("AllowMapAutoZoom")?"true;":"false;"), $map);

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
                "name"=>$place->Name,
                "color"=>$colorStr,
                "position"=>array($position->longitude, $position->latitude),
                "scale"=>$place->Scale,
                "id"=>md5($place->Location),
                "zoom"=>false
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
                "name"=>$device->Name,
                "color"=>$colorStr,
                "position"=>array($position->lon, $position->lat),
                "scale"=>$device->Scale,
                "id"=>$device->InstanceID,
                "zoom"=>$device->Zoom
            );
        }
        $map = str_replace('var Markers = [];  // Replace Hook', 'var Markers = '.json_encode($Markers).';', $map);
        $map = str_replace('var numMovable = 0;  // Replace Hook', 'var numMovable = '.$numMovable.';', $map);

        return $map;
    }
        
    #=====================================================================================
    public function GetConfigurationForm(): string
    #=====================================================================================
    {
        return $this->UpdateConfigurationForm(file_get_contents(__DIR__ . '/form.json'));
    }

    #=====================================================================================
    private function getBytesFromHexString(string $hexdata): string
    #=====================================================================================
    {
        for($count = 0; $count < strlen($hexdata); $count+=2){
            $bytes[] = chr(hexdec(substr($hexdata, $count, 2)));
        }
        return implode($bytes);
    }

    #=====================================================================================
    private function getImageMimeType(string $imagedata): mixed
    #=====================================================================================
    {
        $imagemimetypes = array( 
            "jpeg" => "FFD8", 
            "png" => "89504E470D0A1A0A", 
            "gif" => "474946",
            "bmp" => "424D", 
            "tiff" => "4949",
            "tiff" => "4D4D"
        );

        foreach ($imagemimetypes as $mime => $hexbytes){
            $bytes = $this->getBytesFromHexString($hexbytes);
            if (substr($imagedata, 0, strlen($bytes)) == $bytes)
            return $mime;
        }
        return false;
    }
}
