<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/HookHelper.php';

class OwnTracksHook extends IPSModuleStrict
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
        $this->RegisterAttributeString('LoginStatus', '{"Data":[], "LockedIP":[], "Status":102}');
        $this->RegisterAttributeString('verifiedClients', '{}');
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
		}
    }

    #=====================================================================================
    protected function ProcessHookData(): void
    #=====================================================================================
    {
        $this->SendDebug('$_SERVER', json_encode($_SERVER), 0);
        if(!$this->ProcessHookSecurity())return;

        header("Content-type: application/json");
        $payload = json_decode(file_get_contents("php://input"));

        $response = array();

        $this->SendDebug('Data', json_encode($payload), 0);
    
        if (!isset($payload->topic)) {
            if(isset($_SERVER['HTTP_X_LIMIT_D']) && isset($_SERVER['HTTP_X_LIMIT_U'])){
                $payload->topic = 'owntracks/'.$_SERVER['HTTP_X_LIMIT_U'].'/'.$_SERVER['HTTP_X_LIMIT_D'];
            }else{
                $this->SendDebug('Malformed', json_encode($payload), 0);
                $response = array("result"=>"not ok", "error"=>"data malformed");
                print json_encode($response);
                return;
            }
        }

        $Data = '{"DataID":"{80C20F91-3E29-85FA-9702-3A6B22C1D276}","Topic":"'.$payload->topic.'", "Payload":'.json_encode($payload).'}';
        $this->SendDebug("SendDataToChildren", $Data, 0);
        $this->SendDataToChildren($Data);

        print json_encode($response);
    }
    
    #=====================================================================================
    public function GetConfigurationForm(): string
    #=====================================================================================
    {
        return $this->UpdateConfigurationForm(file_get_contents(__DIR__ . '/form.json'));
    }
}
