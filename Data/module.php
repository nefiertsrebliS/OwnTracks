<?php

	class OwnTrackData extends IPSModule
	{

		#================================================================================================
		public function Create()
		#================================================================================================
		{
			parent::Create();

	        $this->RegisterPropertyString('Topic', '');
	        $this->RegisterPropertyBoolean('showPositionData', false);
	        $this->RegisterPropertyBoolean('showAddress', true);
			$this->RegisterAttributeString('waypoints', '{}');
		}

		#================================================================================================
		public function ApplyChanges()
		#================================================================================================
		{
		    parent::ApplyChanges();
			//Connect to available splitter or create a new one
	        $this->ConnectParent('{73FEF334-3C55-222E-42B1-20800A4A63D0}');
			
			#	Filter setzen
			$Topic = '.*"Topic":"'.$this->ReadPropertyString("Topic").'("|/).*';
			$this->SendDebug("Topic", $Topic,0);
			$this->SetReceiveDataFilter($Topic);
		}
		
		#================================================================================================
		public function ReceiveData($JSONString)
		#================================================================================================
		{
			$this->SendDebug("Received", $JSONString, 0);

			$data = json_decode($JSONString);
			$this->SendDebug("Received Payload", json_encode($data->Payload), 0);

			#----------------------------------------------------------------
			#		Weiterleitung an die Variablen
			#----------------------------------------------------------------

			$Payload = $data->Payload;
			if(isset($Payload->_type)){
				if($Payload->_type == 'location'){
					foreach($Payload as $key=>$data){
						switch($key){
							case 'p':
								$this->RegisterVariableFloat($key, $this->Translate('Airpressure'), "~AirPressure.F",0);
								$this->SetValue($key, $data*10);
								break;
							case 'batt':
								$this->RegisterVariableInteger($key, $this->Translate('Battery'), "~Intensity.100",0);
								$this->SetValue($key, $data);
								break;
							case 'acc':
							case 'alt':
							case 'vac':
								if($this->ReadPropertyBoolean('showPositionData')){
									if (!IPS_VariableProfileExists('Distance.OTR')) {
										IPS_CreateVariableProfile('Distance.OTR', 1);
										IPS_SetVariableProfileIcon('Distance.OTR', 'Distance');
										IPS_SetVariableProfileText('Distance.OTR', '', ' m');
									}
									$this->RegisterVariableInteger($key, $this->Translate($key), 'Distance.OTR',0);
									$this->SetValue($key, $data);
								}
								break;
							case 'bs':
								if (!IPS_VariableProfileExists('Chargestatus.OTR')) {
									IPS_CreateVariableProfile('Chargestatus.OTR', 1);
									IPS_SetVariableProfileIcon('Chargestatus.OTR', 'Battery');
									IPS_SetVariableProfileValues('Chargestatus.OTR', 0, 3, 1);
									IPS_SetVariableProfileAssociation('Chargestatus.OTR', 0, $this->Translate('unknown'), '', -1);
									IPS_SetVariableProfileAssociation('Chargestatus.OTR', 1, $this->Translate('unplugged'), '', -1);
									IPS_SetVariableProfileAssociation('Chargestatus.OTR', 2, $this->Translate('charging'), '', -1);
									IPS_SetVariableProfileAssociation('Chargestatus.OTR', 3, $this->Translate('full'), '', -1);
								}
								$this->RegisterVariableInteger($key, $this->Translate('Chargestatus'), 'Chargestatus.OTR',0);
								$this->SetValue($key, $data);
								break;
						}

					}

					if(isset($Payload->inregions)){
						if(!isset($Payload->inrids)){
							$Payload->inrids = $Payload->inregions;
							foreach($Payload->inrids as &$regionID){$regionID = md5($regionID);}
						}
						$waypoints = json_decode($this->ReadAttributeString('waypoints'));

						$Idents = array();
						foreach(IPS_GetChildrenIDs($this->InstanceID) as $wpID)$Idents[$wpID] = IPS_GetObject($wpID)['ObjectIdent'];
						foreach($waypoints as $rid=>$waypoint) if(!array_search($rid, $Idents))unset($waypoints->$rid);

						foreach($waypoints as &$waypoint){
							if(isset($waypoint->rid) && isset($waypoint->desc)){
								$entry = (array_search($waypoint->rid, $Payload->inrids) !== false)?true:false;
								$this->RegisterVariableBoolean($waypoint->rid, $waypoint->desc,'~Presence',100);
								if($entry != $this->GetValue($waypoint->rid)) $this->SetValue($waypoint->rid,$entry);
								if($entry){
									if(isset($Payload->lon) && isset($Payload->lat) && isset($Payload->tst) && $this->ReadPropertyBoolean('showPositionData')){
										if(isset($waypoint->rad) && $waypoint->rad < 0){
											$waypoint->lon = $Payload->lon;
											$waypoint->lat = $Payload->lat;
										}
									}
								}
							}
						}

						$this->SendDebug('Waypoints', json_encode($waypoints), 0);
						$this->WriteAttributeString('waypoints', json_encode($waypoints));
					}

					if(isset($Payload->lon) && isset($Payload->lat) && isset($Payload->alt) && isset($Payload->tst) && $this->ReadPropertyBoolean('showPositionData')){
						$waypoints = json_decode($this->ReadAttributeString('waypoints'));
						foreach($waypoints as $waypoint){
							if(isset($waypoint->rid) && isset($waypoint->desc) && isset($waypoint->lon) && isset($waypoint->lat)){
								$dx = 71.5 * ($Payload->lon - $waypoint->lon);
								$dy = 111.3 * ($Payload->lat - $waypoint->lat);
								$distance = round(sqrt($dx * $dx + $dy * $dy),3);
								if (!IPS_VariableProfileExists('Distance.km.OTR')) {
									IPS_CreateVariableProfile('Distance.km.OTR', 2);
									IPS_SetVariableProfileIcon('Distance.km.OTR', 'Distance');
									IPS_SetVariableProfileText('Distance.km.OTR', '', ' km');
									IPS_SetVariableProfileDigits('Distance.km.OTR', 3);
								}
								$this->RegisterVariableFloat('distance'.$waypoint->rid, $this->Translate('Distance to').' '.$waypoint->desc, 'Distance.km.OTR',100);
								$this->SetValue('distance'.$waypoint->rid, $distance);
							}
						}
						$this->RegisterVariableString('position', $this->Translate('Position'));
						$position = array('tst'=> $Payload->tst, 'lat'=> $Payload->lat, 'lon'=> $Payload->lon, 'alt'=> $Payload->alt);
						$this->SetValue('position', json_encode($position));

						if($this->ReadPropertyBoolean('showAddress')){
							$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
							$this->SetValue('place',$this->GetAddressString());
						}
					}

				}elseif($Payload->_type == 'transition'){

					if(isset($Payload->event) && isset($Payload->desc)){
						$Payload = $this->GetWaypointData($Payload);
						$this->RegisterVariableBoolean($Payload->rid, $Payload->desc,'~Presence',100);
						$entry = ($Payload->event == 'enter')?true:false;
						$this->SetValue($Payload->rid, $entry);
						if($entry){
							if(isset($Payload->lon) && isset($Payload->lat) && isset($Payload->tst) && $this->ReadPropertyBoolean('showPositionData')){
								$rid = $Payload->rid;
								$waypoints = json_decode($this->ReadAttributeString('waypoints'));
								if(isset($waypoints->$rid->rad) && $waypoints->$rid->rad < 0){
									$waypoints->$rid->lon = $Payload->lon;
									$waypoints->$rid->lat = $Payload->lat;
									$this->WriteAttributeString('waypoints', json_encode($waypoints));
									$this->SendDebug("Waypoints", $this->ReadAttributeString('waypoints'), 0);
								}
							}
						}
					}

				}elseif($Payload->_type == 'waypoint'){

					if(isset($Payload->desc) && isset($Payload->lon) && isset($Payload->lat)){
						if(strpos($Payload->desc, "follow") === false){
							$Payload = $this->GetWaypointData($Payload);
							$this->RegisterVariableBoolean($Payload->rid, $Payload->desc,'~Presence',100);
	
							$waypoints = json_decode($this->ReadAttributeString('waypoints'));
							$rid = $Payload->rid;
							$waypoints->$rid = $Payload;
							$this->WriteAttributeString('waypoints', json_encode($waypoints));
							$this->SendDebug("Waypoints", $this->ReadAttributeString('waypoints'), 0);
						}
					}

				}elseif($Payload->_type == 'waypoints'){

					if(isset($Payload->waypoints)){
						$waypoints = new class{};
						foreach($Payload->waypoints as $waypoint){
							if(isset($waypoint->desc) && isset($waypoint->lon) && isset($waypoint->lat)){
								if(strpos($waypoint->desc, "follow") === false){
									$waypoint = $this->GetWaypointData($waypoint);
									$this->RegisterVariableBoolean($waypoint->rid, $waypoint->desc,'~Presence',100);
									$rid = $waypoint->rid;
									$waypoints->$rid = $waypoint;	
								}
							}
						}
						$this->WriteAttributeString('waypoints', json_encode($waypoints));
						$this->SendDebug("Waypoints", $this->ReadAttributeString('waypoints'), 0);
					}
				}
			}
		}

		#================================================================================================
		private function GetAddressString()
		#================================================================================================
		{

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
			if($ort && isset($result->address->municipality)){
				$names = explode(', ', $result->display_name);
				$ort = $names[array_search($result->address->municipality, $names)-1];
			}
			if(!$ort)$ort = @$result->address->suburb;
			if(!$ort)$ort = @$result->address->city_district;
			$ctr = @$result->address->country;

			$place = $str." ".$nr."<br>".$plz." ".$ort."<br>".$ctr."<br>".date("(d.m. - H:i)", $position->tst);
			return $place;
		}

		#================================================================================================
		public function RequestAction($Ident, $Value)
		#================================================================================================
		{
			switch($Ident) {
				default:
					throw new Exception("Invalid Ident");
			}
		}

		#================================================================================================
		private function GetWaypointData($payload)
		#================================================================================================
		{
			#----------------------------------------------------------------
			#		Waypoint formatieren
			#----------------------------------------------------------------

			if(isset($payload->desc)){
				if(strpos($payload->desc, ":") !== false){
					$desc = explode(":",$payload->desc);
					$payload->desc = $desc[0];
					if(count(explode("-", $desc[1])) == 5 && strlen($desc[1]) == 35){
						$payload->uuid = $desc[1];
						$nr = 2;
					}else{
						$nr = 1;
					}
					if(isset($desc[$nr])) $payload->major = $desc[$nr];
					if(isset($desc[$nr + 1])) $payload->minor = $desc[$nr + 1];
				}
				if(!isset($payload->rid))$payload->rid = md5($payload->desc);
			}
			return $payload;
		}
	}
?>
