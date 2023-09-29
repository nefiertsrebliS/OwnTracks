<?php

declare(strict_types=1);

    include_once __DIR__ . '/../libs/WebHookModule.php';

    class OwnTracksMap extends WebHookModule
    {

        #=====================================================================================
        public function Create()
        #=====================================================================================
        {

            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('Username', '');
            $this->RegisterPropertyString('Password', '');
            $this->RegisterPropertyString('HookName', '');
            $this->RegisterPropertyString('Devices', '{}');
            $this->RegisterPropertyString('Places', '{}');
            $this->RegisterPropertyString('Height', '98vh');
            $this->RegisterPropertyString('Width', '100%');
            $this->RegisterAttributeString('LoginStatus', '{"Data":[], "LockedIP":[], "Status":102}');
            $this->RegisterVariableString('maplink', 'Map', '~HTMLBox');
        }

        #=====================================================================================
        public function Destroy() 
        #=====================================================================================
        {
            //Never delete this line!
            parent::Destroy();
        }

        #=====================================================================================
        public function ApplyChanges()
        #=====================================================================================
        {
            parent::SetHook($this->ReadPropertyString('HookName'));
            
            foreach($this->GetMessageList() as $Message =>$Types) if(array_search(VM_UPDATE, $Types) !== false){
                $this->UnregisterMessage($Message, VM_UPDATE);
            }
            $homeID = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
            foreach(json_decode($this->ReadPropertyString('Devices')) as $device){
                if($device->InstanceID != $homeID){
                    $this->RegisterMessage(IPS_GetObjectIDByIdent('position', $device->InstanceID), VM_UPDATE);
                }
            }

            $maplink = '<iframe src="/hook/'.$this->ReadPropertyString('HookName').'?'.$this->getSecret().'" title="OwnTracks"  width="'.$this->ReadPropertyString('Width').'" height="'.$this->ReadPropertyString('Height').'"></iframe>';
            $this->SetValue('maplink', $maplink);

            //Never delete this line!
            parent::ApplyChanges();

        }

        #================================================================================================
        public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
        #================================================================================================
        {

            switch ($Message) {
                case IM_CHANGESTATUS:
                    break;
                case VM_UPDATE:
                    $maplink = '<iframe src="/hook/'.$this->ReadPropertyString('HookName').'?'.$this->getSecret().'" title="OwnTracks"  width="'.$this->ReadPropertyString('Width').'" height="'.$this->ReadPropertyString('Height').'"></iframe>';
                    $this->SetValue('maplink', $maplink);
                    break;
            }
        }

        #=====================================================================================
        protected function ProcessHookData()
        #=====================================================================================
        {
            //Never delete this line!
            if(!parent::ProcessHookData())return;

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
                require(__DIR__ . '/map.php');
            }
                    
        }
	 
        #=====================================================================================
        public function GetConfigurationForm() 
        #=====================================================================================
        {
            return parent::UpdateConfigurationForm(file_get_contents(__DIR__ . '/form.json'));
        }

        #=====================================================================================
        private function getBytesFromHexString($hexdata){
        #=====================================================================================
        for($count = 0; $count < strlen($hexdata); $count+=2){
                $bytes[] = chr(hexdec(substr($hexdata, $count, 2)));
            }
            return implode($bytes);
        }
    
        #=====================================================================================
        private function getImageMimeType($imagedata)
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
