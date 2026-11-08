<?php

declare(strict_types=1);

trait HookHelper
{
    #=====================================================================================
	protected function ProcessHookSecurity(): mixed 
	#=====================================================================================
    {

        #---------------------------------------------------------
        #   Client-IP
        #---------------------------------------------------------

        $IP = "0.0.0.0";
        if (!empty($_SERVER['HTTP_CLIENT_IP'])){
            $IP = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $IP = $_SERVER['REMOTE_ADDR'];
        }

        if ($this->VerifiedClient(@$_SERVER['HTTP_X_LIMIT_D'], @$_SERVER['HTTP_X_LIMIT_U'], @$_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW'])){

            #---------------------------------------------------------
            #   verified Client
            #---------------------------------------------------------

            $this->RefreshLoginStatus();
        }else{

            #---------------------------------------------------------
            #   Login authorized?
            #---------------------------------------------------------
            
            #   Instanz blockiert
            if ($this->GetStatus() == 205) {
                $this->LogMessage("Instance #".$this->InstanceID." is locked! Please unlock to process data! Access attempt from IP ".$IP, KL_ERROR);
                header('HTTP/1.0 423 Locked');
                echo 'Page locked';
                return false;
            }

            #   IP blockiert
            $this->RefreshLoginStatus();
            $LockedIPs = json_decode($this->ReadAttributeString('LoginStatus'),true)['LockedIP'];
            if (in_array($IP, $LockedIPs)) {
                $this->LogMessage("Instance #".$this->InstanceID." is locked for IP ".$IP."! Please unlock to process data!", KL_ERROR);
                header('HTTP/1.0 423 Locked');
                echo 'Page locked';
                return false;
            }
        }

        $LogInOk = false;
        if (@$_SERVER['PHP_AUTH_USER'] == $this->ReadPropertyString('Username') && @$_SERVER['PHP_AUTH_PW'] == $this->ReadPropertyString('Password'))$LogInOk = true;
        if(isset($_GET[$this->getSecret()]))$LogInOk = true;
        $this->SetLoginStatus($IP, $LogInOk);

        if(!$LogInOk){
            header('WWW-Authenticate: Basic Realm="WebHook"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authorization required';
            $this->SendDebug('Unauthorized $_SERVER', json_encode($_SERVER), 0);
            $this->SendDebug('Unauthorized $_GET', json_encode($_GET), 0);
            $this->LogMessage("Instance #".$this->InstanceID.": Unauthorized access attempt from IP ".$IP, KL_WARNING);
            return false;
        }

        return $LogInOk;
    }
    
    #=====================================================================================
    public function UpdateConfigurationForm(string $jsonform): string
    #=====================================================================================
    {
        $form = json_decode($jsonform);
        foreach($form->elements as &$element){
            if($element->name == "HookName"){
                $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
                $regex = '';
                if (count($ids) > 0) {
                    $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
                    foreach ($hooks as $index => $hook) {
                        if ($hook['TargetID'] != $this->InstanceID){
                            $word = @explode('/hook/', $hook['Hook'])[1];
                            if($word)$regex .= '(?!^'.$word.'$)';
                        }
                    }
                }
                $regex .= '(^[a-zA-Z0-9-_]+$)';
                $element->validate = $regex;
            }
        }
        
        return json_encode($form);
    }
    
    #=====================================================================================
    private function VerifiedClient(mixed $clientID, mixed $groupID, mixed $user, mixed $pass): bool
    #=====================================================================================
    {
        $verified = false;

        $this->SendDebug('verifiedClients', $this->ReadAttributeString('verifiedClients'),0);
        $verifiedClients = json_decode($this->ReadAttributeString('verifiedClients'));

        #---------------------------------------------------------
        #   reject old clients
        #---------------------------------------------------------

        foreach($verifiedClients as $ID => $client){
            if(time() - $client->time > 30*24*3600) unset($verifiedClients->$ID);
        }

        #---------------------------------------------------------
        #   verify client
        #---------------------------------------------------------

        if (($clientID != '')
            && ($clientID != NULL)
            && (property_exists($verifiedClients, $clientID))
            && ($verifiedClients->$clientID->name == $groupID)
        ) {
            if(($user == $this->ReadPropertyString('Username')) && ($pass == $this->ReadPropertyString('Password'))){
                $verifiedClients->$clientID = array("name" => $groupID, "time" => time());
                $verified = true;
            }else{
                unset($verifiedClients->$clientID);
            }
        }

        $this->WriteAttributeString('verifiedClients', json_encode($verifiedClients));
        return $verified;
    }

    #=====================================================================================
    private function RefreshLoginStatus(): void
    #=====================================================================================
    {
        $LoginStatus = json_decode($this->ReadAttributeString('LoginStatus'));

        $status = ($this->ReadPropertyString('Username') == '' || $this->ReadPropertyString('Password') == '')?206:102;
        $FailTries = 0;
        $NewLoginStatus = array('Data'=>array(), 'LockedIP'=>array(), 'Status'=>0);
        foreach($LoginStatus->Data as $LoginDetails){
            if($LoginDetails->ts > time() - 24*3600){
                $FailTries += $LoginDetails->tries;
                $NewLoginStatus['Data'][] = $LoginDetails;
                if($LoginDetails->tries > 3){
                    $status = 204;
                    $NewLoginStatus['LockedIP'][] = $LoginDetails->IP;
                }
            }
        }

        if($FailTries > 10)$status = 205;
        $NewLoginStatus['Status'] = $status;

        $this->SendDebug('RefreshLoginStatus', json_encode($NewLoginStatus), 0);
        $this->WriteAttributeString('LoginStatus', json_encode($NewLoginStatus));

        $this->SetStatus($status);
    }
    
    #=====================================================================================
    private function SetLoginStatus(string $IP, bool $valid): void
    #=====================================================================================
    {
        $LoginStatus = json_decode($this->ReadAttributeString('LoginStatus'));

        $status = ($this->ReadPropertyString('Username') == '' || $this->ReadPropertyString('Password') == '')?206:102;
        $found = false;
        $FailTries = 0;
        $NewLoginStatus = array('Data'=>array(), 'LockedIP'=>array(), 'Status'=>0);
        foreach($LoginStatus->Data as $LoginDetails){
            $FailTries += $LoginDetails->tries;
            if($LoginDetails->IP == $IP){
                $found = true;
                if(!$valid){
                    $LoginDetails->ts = time();
                    $LoginDetails->tries++;
                }else{
                    continue;
                }
            }
            if($LoginDetails->tries > 3){
                $status = 204;
                $NewLoginStatus['LockedIP'][] = $LoginDetails->IP;
            }
            $NewLoginStatus['Data'][] = $LoginDetails;
        }

        if($FailTries > 10)$status = 205;
        $NewLoginStatus['Status'] = $status;

        if(!$found && !$valid){
            $NewLogin['ts'] = time();
            $NewLogin['tries'] = 1;
            $NewLogin['IP'] = $IP;
            $NewLoginStatus['Data'][] = $NewLogin;
        }

        $this->SendDebug('SetLoginStatus', json_encode($NewLoginStatus), 0);
        $this->WriteAttributeString('LoginStatus', json_encode($NewLoginStatus));

        $this->SetStatus($status);
    }
    
    #=====================================================================================
    public function ResetLock() : void
    #=====================================================================================
    {
        $Status = ($this->ReadPropertyString('Username') == '' || $this->ReadPropertyString('Password') == '')?206:102;
        $this->WriteAttributeString('LoginStatus', '{"Data":[], "LockedIP":[], "Status":'.$Status.'}');
        $this->SendDebug('ResetLock', 'done', 0);
        $this->SetStatus($Status);
    }
    
    #=====================================================================================
    public function getSecret(): string
    #=====================================================================================
    {
        return md5($this->ReadPropertyString('Username').':'.$this->ReadPropertyString('Password'));
    }
}