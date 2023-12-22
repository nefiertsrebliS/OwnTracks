<?php

	class OwnTrackWaypoint extends IPSModule
	{

		#================================================================================================
		public function Create() 
		#================================================================================================
		{
			parent::Create();

	        $this->RegisterPropertyString('Name', '');
	        $this->RegisterPropertyBoolean('showPositionData', false);
	        $this->RegisterPropertyBoolean('showAddress', true);
			$this->RegisterAttributeString('attributes', '{}');
		}

		#================================================================================================
		public function ApplyChanges() 
		#================================================================================================
		{
			parent::ApplyChanges();
			//Connect to available splitter or create a new one
	        $this->ConnectParent('{73FEF334-3C55-222E-42B1-20800A4A63D0}');
			
			#	Filter setzen
			$Filter = '.*"'.$this->ReadPropertyString("Name").'("|:).*';
			$this->SendDebug("Filter", $Filter,0);
			$this->SetReceiveDataFilter($Filter);
		}
		
		#================================================================================================
		public function ReceiveData($JSONString)
		#================================================================================================
		{
			$this->SendDebug("Received", $JSONString, 0);

			$data = json_decode($JSONString);
			$this->SendDebug("Received Payload", json_encode($data->Payload), 0);
			#return;

			#----------------------------------------------------------------
			#		Weiterleitung an die Variablen
			#----------------------------------------------------------------

			$Payload = $data->Payload;
			if(isset($Payload->_type)){
				if($Payload->_type == 'location'){
					if(isset($Payload->inregions)){
						$attributes = json_decode($this->ReadAttributeString('attributes'));

						if(in_array($this->ReadPropertyString('Name'),$Payload->inregions)){
							$this->RegisterVariableBoolean(md5($Payload->topic), $Payload->topic,'~Presence',100);
							if(!$this->GetValue(md5($Payload->topic)))$this->SetValue(md5($Payload->topic), true);

							if(isset($Payload->lon) && isset($Payload->lat) && isset($Payload->tst) && $this->ReadPropertyBoolean('showPositionData')){
								if(isset($attributes->rad) && $attributes->rad < 0){
									$this->RegisterVariableString('position', $this->Translate('Position'));
									$position = array('tst'=> $Payload->tst, 'lat'=> $Payload->lat, 'lon'=> $Payload->lon);
									$this->SetValue('position', json_encode($position));
	
									if($this->ReadPropertyBoolean('showAddress')){
										$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
										$this->SetValue('place',$this->GetAddressString());
									}
								}
							}
	
						}

					}

				}elseif($Payload->_type == 'transition'){
					if(isset($Payload->event) && isset($Payload->desc) && $Payload->desc == $this->ReadPropertyString('Name')){
						$Payload->rid = md5($Payload->desc);
						$Payload->topic = $this->CutTopic($Payload->topic);
						$this->RegisterVariableBoolean(md5($Payload->topic), $Payload->topic,'~Presence',100);
						$entry = ($Payload->event == 'enter')?true:false;
						$this->SetValue(md5($Payload->topic), $entry);

						if(!$entry && isset($Payload->t) && $Payload->t == "b" && isset($Payload->lon) && isset($Payload->lat) && isset($Payload->tst)){
							$attributes = json_decode($this->ReadAttributeString('attributes'));
							$rid = $Payload->rid;
							if(isset($attributes->rad) && $attributes->rad < 0 && $this->ReadPropertyBoolean('showPositionData')){
								$this->RegisterVariableString('position', $this->Translate('Position'));
								$position = array('tst'=> $Payload->tst, 'lat'=> $Payload->lat, 'lon'=> $Payload->lon);
								$this->SetValue('position', json_encode($position));

								if($this->ReadPropertyBoolean('showAddress')){
									$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
									$this->SetValue('place',$this->GetAddressString());
								}

								$attributes->lon = $Payload->lon;
								$attributes->lat = $Payload->lat;
								$this->WriteAttributeString('attributes', json_encode($attributes));
								$this->SendDebug("Attributes", $this->ReadAttributeString('attributes'), 0);
							}
						}
					}

				}elseif($Payload->_type == 'waypoint'){

					if(isset($Payload->desc) && isset($Payload->rad) && isset($Payload->lon) && isset($Payload->lat)){
						$Payload = $this->GetWaypointData($Payload);
						if($Payload->rad >= 0){
							$this->RegisterVariableString('position', $this->Translate('Position'));
							$position = array('lat'=> $Payload->lat, 'lon'=> $Payload->lon);
							$this->SetValue('position', json_encode($position));	

							if($this->ReadPropertyBoolean('showAddress')){
								$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
								$this->SetValue('place',$this->GetAddressString());
							}
						}
						unset($Payload->lon);
						unset($Payload->lat);
						$this->WriteAttributeString('attributes', json_encode($Payload));
						$this->SendDebug("Waypoints", $this->ReadAttributeString('attributes'), 0);
					}

				}elseif($Payload->_type == 'waypoints'){

					if(isset($Payload->waypoints)){
						foreach($Payload->waypoints as $waypoint){
							if(isset($waypoint->desc) && isset($waypoint->lon) && isset($waypoint->lat)){
								$waypoint = $this->GetWaypointData($waypoint);
								if($waypoint->desc == $this->ReadPropertyString('Name')){
									if($waypoint->rad >= 0){
										$this->RegisterVariableString('position', $this->Translate('Position'));
										$position = array('lat'=> $waypoint->lat, 'lon'=> $waypoint->lon);
										$this->SetValue('position', json_encode($position));	

										if($this->ReadPropertyBoolean('showAddress')){
											$this->RegisterVariableString('place', $this->Translate('Place'), '~HTMLBox');
											$this->SetValue('place',$this->GetAddressString());
										}				
									}

									unset($waypoint->lon);
									unset($waypoint->lat);
									$this->WriteAttributeString('attributes', json_encode($waypoint));
									$this->SendDebug("Attributes", $this->ReadAttributeString('attributes'), 0);
									break;
								}
							}
						}
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
			if(!$ort)$ort = @$result->address->suburb;
			if(!$ort)$ort = @$result->address->city_district;
			$ctr = @$result->address->country;

			$place = $str." ".$nr."<br>".$plz." ".$ort."<br>".$ctr;
			if(isset($position->tst))$place .= "<br>".$ctr."<br>".date("(d.m. - H:i)", $position->tst);
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
				$payload->rid = md5($payload->desc);
			}
			return $payload;
		}

		#================================================================================================
		private function CutTopic($topic)
		#================================================================================================
		{
			#----------------------------------------------------------------
			#		Topic formatieren
			#----------------------------------------------------------------

			$parts = explode("/", $topic);
			array_pop($parts);
			return implode("/", $parts);
		}
	}
?>
