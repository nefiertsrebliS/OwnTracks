<?php

declare(strict_types=1);

//Constants will be defined with IP-Symcon 5.0 and newer
if (!defined('IPS_KERNELMESSAGE')) {
    define('IPS_KERNELMESSAGE', 10100);
}
if (!defined('KR_READY')) {
    define('KR_READY', 10103);
}

class WebHookModule extends IPSModule
{
    private $hook = '';

    public function Create()
    {

        //Never delete this line!
        parent::Create();

        //We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function Destroy() 
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);

            $newhooks = array();
            foreach ($hooks as $index => $hook) {
                if ($hook['TargetID'] != $this->InstanceID) $newhooks[] = $hook;
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($newhooks));
            IPS_ApplyChanges($ids[0]);
        }

        //Never delete this line!
        parent::Destroy();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {

        //Never delete this line!
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }

    public function ApplyChanges()
    {

        //Never delete this line!
        parent::ApplyChanges();

        //Only call this in READY state. On startup the WebHook instance might not be available yet
        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }

    private function RegisterHook($WebHook)
    {
        if($WebHook == '/hook/')return;
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                }elseif($hook['TargetID'] == $this->InstanceID){
                    $hooks[$index]['Hook'] = $WebHook;
                    $found = true;
                }
            }
            if(!$found)$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];

            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    protected function SetHook(string $WebHook)
    {
        $this->hook = $WebHook;
    }
    
    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
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

        if (!isset($_SERVER['PHP_AUTH_USER'])) $_SERVER['PHP_AUTH_USER'] = '';
        if (!isset($_SERVER['PHP_AUTH_PW'])) $_SERVER['PHP_AUTH_PW'] = '';

        $LogInOk = false;
        if ($_SERVER['PHP_AUTH_USER'] == $this->ReadPropertyString('Username') && $_SERVER['PHP_AUTH_PW'] == $this->ReadPropertyString('Password'))$LogInOk = true;
        if(isset($_GET[$this->getSecret()]))$LogInOk = true;
        $this->SetLoginStatus($IP, $LogInOk);

        if(!$LogInOk){
            header('WWW-Authenticate: Basic Realm="WebHook"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authorization required';
            $this->SendDebug('Unauthorized $_SERVER', json_encode($_SERVER), 0);
            $this->SendDebug('Unauthorized $_GET', json_encode($_GET), 0);
            if ($_SERVER['PHP_AUTH_USER'] != '' || $_SERVER['PHP_AUTH_PW'] != '')$this->LogMessage("Instance #".$this->InstanceID.": Unauthorized access attempt from IP ".$IP, KL_WARNING);
            return false;
        }

        return $LogInOk;
    }
	 
        #=====================================================================================
        public function UpdateConfigurationForm(string $jsonform) 
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
        private function RefreshLoginStatus() 
        #=====================================================================================
        {
            $LoginStatus = json_decode($this->ReadAttributeString('LoginStatus'));

            $status = 102;
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
        private function SetLoginStatus($IP, $valid) 
        #=====================================================================================
        {
            $LoginStatus = json_decode($this->ReadAttributeString('LoginStatus'));

            $status = 102;
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
        public function ResetLock() 
        #=====================================================================================
        {
            $this->WriteAttributeString('LoginStatus', '{"Data":[], "LockedIP":[], "Status":102}');
            $this->SendDebug('ResetLock', 'done', 0);
            $this->SetStatus(102);
        }
	 
        #=====================================================================================
        public function getSecret() 
        #=====================================================================================
        {
            return md5($this->ReadPropertyString('Username').':'.$this->ReadPropertyString('Password'));
        }
}