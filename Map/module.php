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
            $this->RegisterPropertyString('Height', '98vh');
            $this->RegisterPropertyString('Width', '100%');
            $this->RegisterAttributeString('IncorrectLogin', '{}');
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
            
            $this->SendDebug("Devices", $this->ReadPropertyString('Devices'), 0);
            foreach($this->GetMessageList() as $Message =>$Types) if(array_search(VM_UPDATE, $Types) !== false){
                $this->UnregisterMessage($Message, VM_UPDATE);
            }
            foreach(json_decode($this->ReadPropertyString('Devices')) as $device){
                $this->RegisterMessage(IPS_GetObjectIDByIdent('position', $device->InstanceID), VM_UPDATE);
            }

            $maplink = '<iframe src="./hook/'.$this->ReadPropertyString('HookName').'?'.$this->getSecret().'" title="OwnTracks"  width="'.$this->ReadPropertyString('Width').'" height="'.$this->ReadPropertyString('Height').'"></iframe>';
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
                    $maplink = '<iframe src="./hook/'.$this->ReadPropertyString('HookName').'?'.$this->getSecret().'" title="OwnTracks"  width="'.$this->ReadPropertyString('Width').'" height="'.$this->ReadPropertyString('Height').'"></iframe>';
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

            require(__DIR__ . '/map.php');
            
                    
        }
	 
        #=====================================================================================
        public function GetConfigurationForm() 
        #=====================================================================================
        {
            return parent::UpdateConfigurationForm(file_get_contents(__DIR__ . '/form.json'));
        }

    }
