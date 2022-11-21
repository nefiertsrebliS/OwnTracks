<?php

	class ExternalData extends IPSModule
	{

#================================================================================================
		public function Create() {
#================================================================================================
			parent::Create();

	        $this->RegisterPropertyInteger('VariableID', 0);
			$this->RegisterPropertyString('waypoints', '{}');
		}

#================================================================================================
		public function ApplyChanges() {
#================================================================================================
		    parent::ApplyChanges();
			
			$this->RegisterMessage ($this->ReadPropertyInteger('VariableID'), VM_UPDATE);
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
						$this->SendDebug('Data', $Data[0], 0);
						$Payload = json_decode($Data[0]);
						if(isset($Payload->lon) && isset($Payload->lat)){
							$waypoints = json_decode($this->ReadPropertyString('waypoints'));
							foreach($waypoints as $waypoint){
								if(isset($waypoint->Name) && isset($waypoint->Location)){
									$Location = json_decode($waypoint->Location);
									$dx = 71.5 * ($Payload->lon - $Location->longitude);
									$dy = 111.3 * ($Payload->lat - $Location->latitude);
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
							$this->RegisterVariableString('position', $this->Translate('Position'));
							$position = array('lat'=> $Payload->lat, 'lon'=> $Payload->lon);
							$this->SetValue('position', json_encode($position));
	
							$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
							$this->SetValue('place',$this->GetAdressString());
						}
							break;
					default:
						$this->UnregisterMessage($SenderID, VM_UPDATE);
				}
        }
    }
		
#================================================================================================
		private function GetAdressString() {
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
        public function RequestAction($Ident, $Value) {
#================================================================================================
            switch($Ident) {
                default:
                    throw new Exception("Invalid Ident");
            }
         
        }
	}
?>
