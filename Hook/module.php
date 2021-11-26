<?php

declare(strict_types=1);

    include_once __DIR__ . '/../libs/WebHookModule.php';

    class OwnTracksHook extends WebHookModule
    {
        public function __construct($InstanceID)
        {
            parent::__construct($InstanceID, 'owntracks');
        }

        public function Create()
        {

            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('Username', '');
            $this->RegisterPropertyString('Password', '');
        }

        public function ApplyChanges()
        {

            //Never delete this line!
            parent::ApplyChanges();

            //Cleanup old hook script
            $id = @IPS_GetObjectIDByIdent('Hook', $this->InstanceID);
            if ($id > 0) {
                IPS_DeleteScript($id, true);
            }
        }

        /**
         * This function will be called by the hook control. Visibility should be protected!
         */
        protected function ProcessHookData()
        {

            //Never delete this line!
            parent::ProcessHookData();

            if ((IPS_GetProperty($this->InstanceID, 'Username') != '') || (IPS_GetProperty($this->InstanceID, 'Password') != '')) {
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                    $_SERVER['PHP_AUTH_USER'] = '';
                }
                if (!isset($_SERVER['PHP_AUTH_PW'])) {
                    $_SERVER['PHP_AUTH_PW'] = '';
                }

                if (($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($this->InstanceID, 'Username')) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($this->InstanceID, 'Password'))) {
                    header('WWW-Authenticate: Basic Realm="Geofency WebHook"');
                    header('HTTP/1.0 401 Unauthorized');
                    echo 'Authorization required';
                    $this->SendDebug('Unauthorized', print_r($_POST, true), 0);
                    return;
                }
            }
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
            $this->SendDebug("Data", $Data, 0);
            $this->SendDataToChildren($Data);
        
        }
    }
