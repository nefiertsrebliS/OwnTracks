<?php

declare(strict_types=1);

    include_once __DIR__ . '/../libs/WebHookModule.php';

    class OwnTracksHook extends WebHookModule
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
            $this->RegisterAttributeString('IncorrectLogin', '{}');
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
            
            //Never delete this line!
            parent::ApplyChanges();

        }

        #=====================================================================================
        protected function ProcessHookData()
        #=====================================================================================
        {

            //Never delete this line!
            parent::ProcessHookData();

            header("Content-type: application/json");
            $payload = json_decode(file_get_contents("php://input"));

            $response = array();
            # optionally add objects to return to the app (e.g.
            # friends or cards)
            print json_encode($response);

            $this->SendDebug('Data', json_encode($payload), 0);
        
            if (!isset($payload->topic)) {
                if(isset($_SERVER['HTTP_X_LIMIT_D']) && isset($_SERVER['HTTP_X_LIMIT_U'])){
                    $payload->topic = 'owntracks/'.$_SERVER['HTTP_X_LIMIT_U'].'/'.$_SERVER['HTTP_X_LIMIT_D'];
                }else{
                    $this->SendDebug('Malformed', json_encode($payload), 0);
                    return;
                }
            }

            $Data = '{"DataID":"{80C20F91-3E29-85FA-9702-3A6B22C1D276}","Topic":"'.$payload->topic.'", "Payload":'.json_encode($payload).'}';
            $this->SendDebug("SendDataToChildren", $Data, 0);
            $this->SendDataToChildren($Data);
        
        }
	 
        #=====================================================================================
        public function GetConfigurationForm() 
        #=====================================================================================
        {
            return parent::UpdateConfigurationForm(file_get_contents(__DIR__ . '/form.json'));
        }
    }
