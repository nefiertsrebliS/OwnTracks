<?php

	class ExternalData extends IPSModule
	{

#================================================================================================
		public function Create() {
#================================================================================================
			parent::Create();

	        $this->RegisterPropertyBoolean('separatePosVar', false);
	        $this->RegisterPropertyInteger('VariableID', 0);
	        $this->RegisterPropertyInteger('latID', 0);
	        $this->RegisterPropertyInteger('lonID', 0);
	        $this->RegisterPropertyBoolean('showAddress', true);
			$this->RegisterPropertyString('waypoints', '{}');
			$this->RegisterVariableString('position', $this->Translate('Position'));
			if($this->GetValue('position') == '') $this->SetValue('position', '{"lat":52.5163,"lon":13.3777}');
		}

#================================================================================================
		public function ApplyChanges() {
#================================================================================================
		    parent::ApplyChanges();
			
			if($this->ReadPropertyBoolean('separatePosVar')){
				if($this->ReadPropertyInteger('latID') > 0)$this->RegisterMessage ($this->ReadPropertyInteger('latID'), VM_UPDATE);
				if($this->ReadPropertyInteger('lonID') > 0)$this->RegisterMessage ($this->ReadPropertyInteger('lonID'), VM_UPDATE);
				$this->UnregisterMessage ($this->ReadPropertyInteger('VariableID'), VM_UPDATE);
			}else{
				if($this->ReadPropertyInteger('VariableID') > 0)$this->RegisterMessage ($this->ReadPropertyInteger('VariableID'), VM_UPDATE);
				$this->UnregisterMessage ($this->ReadPropertyInteger('latID'), VM_UPDATE);
				$this->UnregisterMessage ($this->ReadPropertyInteger('lonID'), VM_UPDATE);
			}
		}

#================================================================================================
public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
#================================================================================================
    {
        switch ($Message) {
		    case IM_CHANGESTATUS:
		        break;
		    case VM_UPDATE:
				switch($SenderID){
					case $this->ReadPropertyInteger('VariableID'):
						$this->SendDebug($this->ReadPropertyInteger('VariableID'), $Data[0], 0);
						$Payload = json_decode($Data[0]);
						if(isset($Payload->lon) && isset($Payload->lat)){
							$value = $this->GetValue('position');
							if($value == '') $value = '{"lat":0, "lon":0}';
							$position = json_decode($value);
							$position->lat = $Payload->lat;
							$position->lon = $Payload->lon;
	
							$this->SetValue('position', json_encode($position));
							$this->CheckPosition();
						}
						break;
					case $this->ReadPropertyInteger('latID'):
						$this->SendDebug($this->ReadPropertyInteger('latID'), $Data[0], 0);

						$value = $this->GetValue('position');
						if($value == '') $value = '{"lat":0, "lon":0}';
						$position = json_decode($value);
						$position->lat = $Data[0];

						$this->SetValue('position', json_encode($position));
						$this->CheckPosition();
						break;
					case $this->ReadPropertyInteger('lonID'):
						$this->SendDebug($this->ReadPropertyInteger('lonID'), $Data[0], 0);

						$value = $this->GetValue('position');
						if($value == '') $value = '{"lat":0, "lon":0}';
						$position = json_decode($value);
						$position->lon = $Data[0];

						$this->SetValue('position', json_encode($position));
						$this->CheckPosition();
						break;

					default:
						$this->UnregisterMessage($SenderID, VM_UPDATE);
				}
        }
    }
		
#================================================================================================
		private function GetAddressString() {
#================================================================================================

			#----------------------------------------------------------------
			#		Adresse von OSM holen
			#----------------------------------------------------------------

			$position = json_decode($this->GetValue('position'));
			$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=".str_replace (",",".",$position->lat)."&lon=".str_replace (",",".",$position->lon);
			$result = json_decode(@file_get_contents($url));
			if(!is_object($result))return false;

			$str = @$result->address->road; 
			$nr  = @$result->address->house_number;
			$plz = @$result->address->postcode; 
			$ort = @$result->address->city;
			if(!$ort)$ort = @$result->address->town;
			if(!$ort)$ort = @$result->address->village;
			if(!$ort)$ort = @$result->address->suburb;
			if(!$ort)$ort = @$result->address->city_district;
			$ctr = @$result->address->country;

			$place = $str." ".$nr."<br>".$plz." ".$ort."<br>".$ctr."<br>".date("(d.m. - H:i)");
			return $place;
		}
        
		#================================================================================================
		private function CheckPosition()
		#================================================================================================
		{
			$position = json_decode($this->GetValue('position'));
			if($position->lon != 0 && $position->lat != 0){
				$waypoints = json_decode($this->ReadPropertyString('waypoints'));
				foreach($waypoints as $waypoint){
					if(isset($waypoint->Name) && isset($waypoint->Location)){
						$Location = json_decode($waypoint->Location);
						$dx = 71.5 * ($position->lon - $Location->longitude);
						$dy = 111.3 * ($position->lat - $Location->latitude);
						$distance = round(sqrt($dx * $dx + $dy * $dy),3);
						if (!IPS_VariableProfileExists('Distance.km.OTR')) {
							IPS_CreateVariableProfile('Distance.km.OTR', 2);
							IPS_SetVariableProfileIcon('Distance.km.OTR', 'Distance');
							IPS_SetVariableProfileText('Distance.km.OTR', '', ' km');
							IPS_SetVariableProfileDigits('Distance.km.OTR', 3);
						}
						$this->RegisterVariableFloat(MD5($waypoint->Name), $this->Translate('Distance to').' '.$waypoint->Name, 'Distance.km.OTR',100);
						$this->SetValue(MD5($waypoint->Name), $distance);

						$entry = ($distance * 1000 < $waypoint->Radius)?true:false;
						$this->RegisterVariableBoolean('p'.MD5($waypoint->Name), $waypoint->Name,'~Presence',100);
						if($entry != $this->GetValue('p'.MD5($waypoint->Name))) $this->SetValue('p'.MD5($waypoint->Name),$entry);
					}
				}

				if($this->ReadPropertyBoolean('showAddress')){
					$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
					$this->SetValue('place',$this->GetAddressString());
				}
			}
		}
        
		#================================================================================================
		public function SeparateParameter(bool $separate)
		#================================================================================================
		{
			if($separate){
				$this->UpdateFormField("lonID", "visible", true);
				$this->UpdateFormField("latID", "visible", true);
				$this->UpdateFormField("VariableID", "visible", false);
			}else{
				$this->UpdateFormField("lonID", "visible", false);
				$this->UpdateFormField("latID", "visible", false);
				$this->UpdateFormField("VariableID", "visible", true);
			}
        }
	 
		#=====================================================================================
		public function GetConfigurationForm()
		#=====================================================================================
		{
			$form = json_decode(file_get_contents(__DIR__ . '/form.json'));
			$sep = $this->ReadPropertyBoolean('separatePosVar');
			foreach($form->elements as &$element){
				if(isset($element->name)){
					if($element->name == "VariableID")$element->visible = !$sep;
					if($element->name == "lonID")$element->visible = $sep;
					if($element->name == "latID")$element->visible = $sep;	
				}
			}
			return json_encode($form);
		}
	
#================================================================================================
        public function RequestAction($Ident, $Value) {
#================================================================================================
            switch($Ident) {
                default:
                    throw new Exception("Invalid Ident");
            }
         
        }
	}
?>
