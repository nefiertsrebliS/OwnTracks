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

        if (($this->ReadPropertyString('Username') != '') || ($this->ReadPropertyString('Password') != '')) {

            if (!isset($_SERVER['PHP_AUTH_USER'])) $_SERVER['PHP_AUTH_USER'] = '';
            if (!isset($_SERVER['PHP_AUTH_PW'])) $_SERVER['PHP_AUTH_PW'] = '';

            if($_SERVER['PHP_AUTH_USER'] !='' || $_SERVER['PHP_AUTH_PW']!='' || count($_GET) > 0){
                if($this->isBlocked($IP)){
                    header('WWW-Authenticate: Basic Realm="SecureHook"');
                    header('HTTP/1.0 423 Locked');
                    echo 'Page locked';
                    $this->SendDebug('Locked $_SERVER', json_encode($_SERVER), 0);
                    $this->SendDebug('Locked $_GET', json_encode($_GET), 0);
                    $this->SetStatus(205);
                    return false;
                }
            }

            $LogInOk = true;
            if ($_SERVER['PHP_AUTH_USER'] != $this->ReadPropertyString('Username'))$LogInOk = false;
            if ($_SERVER['PHP_AUTH_PW'] != $this->ReadPropertyString('Password'))$LogInOk = false;
            foreach($_GET as $index => $value) if($index == $this->getSecret())$LogInOk = true;
            if(!$LogInOk){
                header('WWW-Authenticate: Basic Realm="SecureHook"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization required';
                $this->SendDebug('Unauthorized $_SERVER', json_encode($_SERVER), 0);
                $this->SendDebug('Unauthorized $_GET', json_encode($_GET), 0);
                return false;
            }
        }
        $this->setValid($IP);
        return true;
    }
	 
        #=====================================================================================
        public function UpdateConfigurationForm($jsonform) 
        #=====================================================================================
        {
            $form = json_decode($jsonform);
            foreach($form->elements as &$element){
                if($element->name == "HookName"){
                    $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
                    $regex = '\\b(\\w+)';
                    if (count($ids) > 0) {
                        $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
                        foreach ($hooks as $index => $hook) {
                            if ($hook['TargetID'] != $this->InstanceID){
                                $word = @explode('/hook/', $hook['Hook'])[1];
                                if($word)$regex .= '\\b(?<!'.$word.')';
                            }
                        }
                    }
                    $element->validate = $regex;
                }
            }
            
            return json_encode($form);
        }
	 
        #=====================================================================================
        private function isBlocked($IP) 
        #=====================================================================================
        {
            $IncorrectLogins = json_decode($this->ReadAttributeString('IncorrectLogin'));

            $blocked = false;
            $found = false;
            $FailTries = 0;
            $NewIncorrectLogins = array();
            foreach($IncorrectLogins as $IncorrectLogin){
                if($IncorrectLogin->ts > time() - 24*3600){
                    $FailTries += $IncorrectLogin->tries;
                    if($IncorrectLogin->IP == $IP){
                        $IncorrectLogin->ts = time();
                        $IncorrectLogin->tries++;
                        if($IncorrectLogin->tries > 3)$blocked = true;
                        $found = true;
                    }
                    $NewIncorrectLogins[] = $IncorrectLogin;
                }
            }
            if($FailTries > 10)$blocked = true;
    
            if(!$found){
                $NewLogin['ts'] = time();
                $NewLogin['tries'] = 1;
                $NewLogin['IP'] = $IP;
                $NewIncorrectLogins[] = $NewLogin;
            }

            $this->SendDebug('IncorrectLogin', json_encode($NewIncorrectLogins), 0);
            $this->WriteAttributeString('IncorrectLogin', json_encode($NewIncorrectLogins));
    
            return $blocked;
        }
	 
        #=====================================================================================
        private function setValid($IP) 
        #=====================================================================================
        {
            $IncorrectLogins = json_decode($this->ReadAttributeString('IncorrectLogin'));

            $NewIncorrectLogins = array();
            foreach($IncorrectLogins as $IncorrectLogin){
                if($IncorrectLogin->IP != $IP) $NewIncorrectLogins[] = $IncorrectLogin;
            }

            $this->SendDebug('IncorrectLogin', json_encode($NewIncorrectLogins), 0);
            $this->WriteAttributeString('IncorrectLogin', json_encode($NewIncorrectLogins));
        }
	 
        #=====================================================================================
        public function ResetLock() 
        #=====================================================================================
        {
            $this->WriteAttributeString('IncorrectLogin', '{}');
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