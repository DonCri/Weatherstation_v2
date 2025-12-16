<?php

/**
 * This is a Symcon module for handling weather station data from DominoSwiss devices.
 * It processes sensor data like light intensity, wind speed, rain detection, and solar azimuth.
 * The module also provides automation capabilities for shading control based on weather conditions.
 */

class DominoSwissWeatherstation_v2 extends IPSModule {

  /*
   * Create function - Initializes the module and registers all necessary properties and variables
   */
  public function Create() {
    //Never delete this line!
    parent::Create();

    // properties
    $this->RegisterPropertyInteger("ID", 1);


    // Sensor Variable
    $this->RegisterVariableFloat("ColdCap", "ColdCap", "", 0);
    $this->RegisterVariableInteger("LightValue", $this->Translate("Light"), "~Illumination", 1);
    $this->RegisterVariableFloat("WindValue", "Wind", "~WindSpeed.kmh", 2);
    $this->RegisterVariableBoolean("Raining", $this->Translate("Raining"), "~Raining", 3);
    $this->RegisterVariableFloat("SunAzimuth", $this->Translate("Azimuth"), "", 4);

    // Automation variable
    $this->RegisterVariableBoolean("ShadingAutomationState", $this->Translate("ShadingTriggeredAutomation"), "", 5);
    $this->EnableAction("ShadingAutomationState");
    $this->RegisterVariableBoolean("UseAzimuth", $this->Translate("UseAzimuth"), "", 6);
    $this->EnableAction("UseAzimuth");
    $this->RegisterVariableInteger("BrightnessUpperThreshold", $this->Translate("BrightnessUpperThreshold"), "", 7);
    $this->EnableAction("BrightnessUpperThreshold");
    $this->RegisterVariableInteger("BrightnessLowerThreshold", $this->Translate("BrightnessLowerThreshold"), "", 8);
    $this->EnableAction("BrightnessLowerThreshold");
    $this->RegisterVariableFloat("AzimuthFrom", $this->Translate("AzimuthFrom"), "", 9);
    $this->EnableAction("AzimuthFrom");
    $this->RegisterVariableFloat("AzimuthTo", $this->Translate("AzimuthTo"), "", 10);
    $this->EnableAction("AzimuthTo");
    $this->RegisterVariableBoolean("ShadingTriggered", $this->Translate("ShadingTriggeredTriggered"), "", 11);
    $this->RegisterVariableInteger("WindUpperThreshold", $this->Translate("WindUpperThreshold"), "", 12);
    $this->EnableAction("WindUpperThreshold");
    $this->RegisterVariableInteger("WindLowerThreshold", $this->Translate("WindLowerThreshold"), "", 14);
    $this->EnableAction("WindLowerThreshold");

    // Required parent
    $this->ConnectParent("{1252F612-CF3F-4995-A152-DA7BE31D4154}"); //DominoSwiss eGate

    // Register Azimuth from location Instance
    $azimuthId = IPS_GetObjectIDByIdent("Azimuth", IPS_GetInstanceListByModuleID("{45E97A63-F870-408A-B259-2933F7EABF74}")[0]);
    $this->RegisterMessage($azimuthId, 10603);
  }



  /**
   * Destroy function - Cleans up the module
   */
  public function Destroy() {
    //Never delete this line!
    parent::Destroy();
  }



  /**
   * ApplyChanges function - Applies configuration changes
   */
  public function ApplyChanges() {
    //Never delete this line!
    parent::ApplyChanges();
  }



  /**
   * ReceiveData function - Processes incoming data from the weather station
   * Handles different command types and updates corresponding sensor values
   */
  public function ReceiveData($JSONString) {

    $data = json_decode($JSONString);

    $this->SendDebug("BufferIn", print_r($data->Values, true), 0);
    if ($data->Values->ID == $this->ReadPropertyInteger("ID")) {
      switch ($data->Values->Command) {
        case 32:
          SetValue($this->GetIDForIdent("LightValue"), $this->GetLightValue(intval($data->Values->Value / 8), ($data->Values->Value % 8)));
          $this->ShadingTriggered();
          break;

        case 33:
          SetValue($this->GetIDForIdent("WindValue"), $this->GetWindValue(intval($data->Values->Value / 8), ($data->Values->Value % 8)));
          $this->WindTriggered();
          break;

        case 34:
          if ($data->Values->Value >= 112) {
            SetValue($this->GetIDForIdent("Raining"), true);
          } else {
            SetValue($this->GetIDForIdent("Raining"), false);
          }
          break;

        case 39:
          SetValue($this->GetIDForIdent("ColdCap"), $data->Values->Value);
          break;
      }
    }
  }

  /**
   * GetLightValue function - Converts raw light sensor data to actual illumination values
   * Uses a lookup table with different ranges and steps for accurate conversion
   */
  function GetLightValue($Category, $Modulo) {

    $base = 0;
    $step = 0;

    switch ($Category) {
      case 0:
        $base = 0;
        $step = 5;
        break;

      case 1:
        $base = 5;
        $step = 3;
        break;

      case 2:
        $base = 8;
        $step = 2;
        break;

      case 3:
        $base = 10;
        $step = 20;
        break;

      case 4:
        $base = 30;
        $step = 70;
        break;

      case 5:
        $base = 100;
        $step = 4900;
        break;

      case 6:
        $base = 5000;
        $step = 5000;
        break;

      case 7:
        $base = 10000;
        $step = 2000;
        break;

      case 8:
        $base = 12000;
        $step = 3000;
        break;

      case 9:
        $base = 15000;
        $step = 5000;
        break;

      case 10:
        $base = 20000;
        $step = 5000;
        break;

      case 11:
        $base = 25000;
        $step = 5000;
        break;

      case 12:
        $base = 30000;
        $step = 10000;
        break;

      case 13:
        $base = 40000;
        $step = 20000;
        break;

      case 14:
        $base = 60000;
        $step = 20000;
        break;

      case 15:
        $this->SendDebug("ValuesID", "häh", 0);
        return 80000;
    }

    return $base + $Modulo * ($step / 8);
  }



  /**
   * GetWindValue function - Converts raw wind sensor data to actual wind speed values
   * Uses a lookup table with different ranges and steps for accurate conversion
   */
  function GetWindValue($Category, $Modulo) {

    $base = 0;
    $step = 0;

    switch ($Category) {
      case 0:
        $base = 0;
        $step = 10;
        break;

      case 1:
        $base = 10;
        $step = 5;
        break;

      case 2:
        $base = 15;
        $step = 5;
        break;

      case 3:
        $base = 20;
        $step = 5;
        break;

      case 4:
        $base = 25;
        $step = 5;
        break;

      case 5:
        $base = 30;
        $step = 5;
        break;

      case 6:
        $base = 35;
        $step = 5;
        break;

      case 7:
        $base = 40;
        $step = 10;
        break;

      case 8:
        $base = 50;
        $step = 10;
        break;

      case 9:
        $base = 60;
        $step = 10;
        break;

      case 10:
        $base = 70;
        $step = 10;
        break;

      case 11:
        $base = 80;
        $step = 10;
        break;

      case 12:
        $base = 90;
        $step = 10;
        break;

      case 13:
        $base = 100;
        $step = 10;
        break;

      case 14:
        $base = 110;
        $step = 10;
        break;

      case 15:
        return 120;
    }

    return $base + $Modulo * ($step / 8);
  }

  /**
   * MessageSink function - Handles messages from other modules
   * Specifically processes azimuth data from the location module
   */
  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $locationAzimuthId = IPS_GetObjectIDByIdent("Azimuth", IPS_GetInstanceListByModuleID("{45E97A63-F870-408A-B259-2933F7EABF74}")[0]);
    if ($SenderID == $locationAzimuthId) {
      $this->SetValue("SunAzimuth", GetValue($locationAzimuthId));
      if ($this->GetValue("UseAzimuth")) {
        $this->ShadingTriggered();
      }
    }
  } 

  /**
   * ShadingTriggered function - Handles shading automation logic
   * Can use either simple brightness thresholds or azimuth-based logic
   */
  public function ShadingTriggered() {
    if ($this->GetValue("ShadingAutomationState")) {
      if (!$this->GetValue("UseAzimuth")) {
        // TODO: insert code for shading
      } else {
        //TODO: insert code for shading
      }
    };
  }

  /**
   * WindTriggered function - Handles wind-based automation
   * Triggers actions when wind exceeds upper or lower thresholds
   */
  public function WindTriggered() {
    if ($this->GetValue("Wind") > $this->GetValue("WindUpperThreshold")) {
      // TODO: insert code for wind
    } elseif ($this->GetValue("Wind") < $this->GetValue("WindLowerThreshold")) {
      // TODO: insert code for wind
    }
  }

  /**
   * RainTriggered function - Handles rain-based automation
   * Triggers different actions based on whether it's raining or not
   */
  public function RainTriggered() {
    if ($this->GetValue("Rain")) {
      // TODO: insert code for rain
    } else {
      // TODO: insert code for rain
    }
  }
}
