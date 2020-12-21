<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class easyMQTT extends eqLogic {
  public static function health() {
	  log::add('easyMQTT','debug','Func health - easyMQTT.class.php');
    $return = array();
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    $server = socket_connect ($socket , config::byKey('easymqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('easymqttPort', 'easyMQTT', '1883'));
    $return[] = array(
      'test' => __('Mosquitto', __FILE__),
      'result' => ($server) ? __('OK', __FILE__) : __('NOK', __FILE__),
      'advice' => ($server) ? '' : __('Indique si Mosquitto est disponible', __FILE__),
      'state' => $server,
    );
    return $return;
  }

  public static function deamon_info() {
	  // log::add('easyMQTT','debug','Func deamon_info - easyMQTT.class.php');
    $return = array();
    $return['log'] = '';
    $return['state'] = 'nok';
    $cron = cron::byClassAndFunction('easyMQTT', 'daemon');
    if (is_object($cron) && $cron->running()) {
      $return['state'] = 'ok';
    }
    // $dependancy_info = self::dependancy_info();
    // if ($dependancy_info['state'] == 'ok') {
      // $return['launchable'] = 'ok';
    // }
	$return['launchable'] = 'ok';
    return $return;
  }

  public static function deamon_start($_debug = false) {
	  log::add('easyMQTT','debug','Func deamon_start - easyMQTT.class.php');
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    $cron = cron::byClassAndFunction('easyMQTT', 'daemon');
    if (!is_object($cron)) {
      throw new Exception(__('Tache cron introuvable', __FILE__));
    }
    $cron->run();
  }

  public static function deamon_stop() {
	  log::add('easyMQTT','debug','Func deamon_stop - easyMQTT.class.php');
    $cron = cron::byClassAndFunction('easyMQTT', 'daemon');
    if (!is_object($cron)) {
      throw new Exception(__('Tache cron introuvable', __FILE__));
    }
    $cron->halt();
  }

  // public static function dependancy_info() {
    // $return = array();
    // $return['log'] = 'easyMQTT_dep';
    // $return['state'] = 'nok';
    // $cmd = "dpkg -l | grep mosquitto";
    // exec($cmd, $output, $return_var);
    ///////////lib PHP exist
    // $libphp = extension_loaded('mosquitto');
    // if ($output[0] != "" && $libphp) {
      // $return['state'] = 'ok';
    // }
    // return $return;
  // }

    // public static function dependancy_install() {
        // log::remove(__CLASS__ . '_dep');
        // return array('script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('easyMQTT') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_dep'));
    // }

  public static function daemon() {
	  log::add('easyMQTT','debug','Func deamon - easyMQTT.class.php');
	  #log::add('easyMQTT', 'debug', 'Paramètres utilisés, Host : ' . config::byKey('easymqttAdress', 'easyMQTT', '127.0.0.1') . ', Port : ' . config::byKey('easymqttPort', 'easyMQTT', '1883') . ', ID : ' . config::byKey('easymqttId', 'easyMQTT', 'Jeedom'));
    log::add('easyMQTT', 'info', 'Paramètres utilisés, Host : ' . config::byKey('easymqttAdress', 'easyMQTT', '127.0.0.1') . ', Port : ' . config::byKey('easymqttPort', 'easyMQTT', '1883') . ', ID : ' . config::byKey('easymqttId', 'easyMQTT', 'Jeedom'));
    $client = new Mosquitto\Client(config::byKey('easymqttId', 'easyMQTT', 'Jeedom'));
    $client->onConnect('easyMQTT::connect');
    $client->onDisconnect('easyMQTT::disconnect');
    $client->onSubscribe('easyMQTT::subscribe');
    $client->onMessage('easyMQTT::message');
    $client->onLog('easyMQTT::logmq');
    $client->setWill('/jeedom', "Client died :-(", 1, 0);

    try {
      if (config::byKey('easymqttUser', 'easyMQTT', 'none') != 'none') {
        $client->setCredentials(config::byKey('easymqttUser', 'easyMQTT'), config::byKey('easymqttPass', 'easyMQTT'));
      }
      $client->connect(config::byKey('easymqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('easymqttPort', 'easyMQTT', '1883'), 90);
      $topic = config::byKey('easymqttTopic', 'easyMQTT', '#');
      if (strpos($topic,'|') === false) {
        log::add('easyMQTT', 'debug', 'Subscribe to topic ' . $topic);
		$client->subscribe($topic, config::byKey('easymqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
      } else {
        $topics = explode('|',$topic);
        foreach ($topics as $value){
			$value = trim($value); // on efface les espace si l'utilisateur à mis un espace entre son topic et le | 
			log::add('easyMQTT', 'debug', 'Subscribe to topic ' . $value);
			$client->subscribe($value, config::byKey('easymqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
        }
      }  
      //$client->loopForever();
      while (true) { $client->loop(); }
    }
    catch (Exception $e){
      log::add('easyMQTT', 'error', $e->getMessage());
    }
  }

  public static function connect( $r, $message ) {
	  log::add('easyMQTT','debug','Func connect - easyMQTT.class.php');
	  log::add('easyMQTT', 'debug', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
    log::add('easyMQTT', 'info', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
    config::save('status', '1',  'easyMQTT');
  }

  public static function disconnect( $r ) {
	  log::add('easyMQTT','debug','Func disconnect - easyMQTT.class.php');
    log::add('easyMQTT', 'debug', 'Déconnexion de Mosquitto avec code ' . $r);
    config::save('status', '0',  'easyMQTT');
  }

  public static function subscribe( ) {
	  log::add('easyMQTT','debug','Func subscribe - easyMQTT.class.php');
    log::add('easyMQTT', 'debug', 'Subscribe to topics');
  }

  public static function logmq( $code, $str ) {
	  log::add('easyMQTT','debug','Func logmq - easyMQTT.class.php');
	  log::add('easyMQTT', 'debug', $code . ' : ' . $str);
    if (strpos($str,'PINGREQ') === false && strpos($str,'PINGRESP') === false) {
      log::add('easyMQTT', 'debug', $code . ' : ' . $str);
    }
  }

  public static function message( $message ) {
	log::add('easyMQTT','debug','Func message - easyMQTT.class.php');
    log::add('easyMQTT', 'debug', 'Message ' . $message->payload . ' sur ' . $message->topic);
	
    if (is_string($message->payload) && is_array(json_decode($message->payload, true)) && (json_last_error() == JSON_ERROR_NONE)) {
      //json message
      $nodeid = $message->topic;
      $value = $message->payload;
      $type = 'json';
      log::add('easyMQTT', 'info', 'Message json : ' . $value . ' pour information sur : ' . $nodeid);
    } else {
      $topicArray = explode("/", $message->topic);
      $cmdId = end($topicArray);
      $key = count($topicArray) - 1;
      unset($topicArray[$key]);
      $nodeid = implode($topicArray,'/');
      $value = $message->payload;
      $type = 'topic';
      log::add('easyMQTT', 'info', 'Message texte : ' . $value . ' pour information : ' . $cmdId . ' sur : ' . $nodeid);
    }
	
	####################################################
	#################### YEELIGHT ######################
	####################################################
	
	// if (stripos($message->topic,'yeelight') !== false){	
	if (stripos($message->topic,'yeelight') !== false){	
		log::add('easyMQTT', 'info', 'C\'est du yeelight que l\'on vient de trouver : '  . $nodeid);
		if (strpos($message->topic,'/set') !== false ){	
			log::add('easyMQTT', 'debug', '-- On filtre car c\'est une information /set: ' . $message->topic);
//			$goOnYeeligght = "no";
		}else{
			$nameYeelight = explode("/", $nodeid);
			$nameYeelight = $nameYeelight[2];
			$eqLogicId =  'yeelight-'.$nameYeelight ; // Création de l'ID logique pour l'équipement yeelight
			$eqLogicName = 'Yeelight - '. $nameYeelight; // Création du nom pour l'équipement yeelight
			log::add('easyMQTT', 'debug', 'On cherche si un équipement existe déjà avec l\'ID : '  . $eqLogicId . ' et le nom : ' . $eqLogicName);
			$elogic = self::byLogicalId($eqLogicId, 'easyMQTT');
				if (!is_object($elogic)) {
				  $elogic = new easyMQTT();
				  $elogic->setEqType_name('easyMQTT');
				  $elogic->setLogicalId($eqLogicId);
				  $elogic->setName($eqLogicName);
				  $elogic->setConfiguration('topic', $nodeid);
				  // $elogic->setConfiguration('topic', $topicJson);
				  //$elogic->setConfiguration('type', $type);
				  //$elogic->setConfiguration('modelShort', $value['definition']['model']);					  
				  //$elogic->setConfiguration('modelLong', $value['definition']['description']);					  
				  log::add('easyMQTT', 'info', 'Saving device ' . $eqLogicId);
				  $elogic->save();
				}
				$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
				$elogic->setConfiguration('type','yeelight');
				$elogic->save();
				
			log::add('easyMQTT', 'debug', 'On va créer les commandes associées à l\'équipement : ' . $eqLogicName);
			$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				if (!is_object($cmdlogic)) {
					log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $cmdId. ' pour l\'équipement '. $eqLogicName);
					log::add('easyMQTT', 'info', 'Création d\'une commande');
					$cmdlogic = new easyMQTTCmd();
					$cmdlogic->setEqLogic_id($elogic->getId());
					$cmdlogic->setEqType('easyMQTT');
					$cmdlogic->setSubType('string');
					$cmdlogic->setLogicalId($cmdId);
					$cmdlogic->setType('info');						  
					$cmdlogic->setName($cmdId);
					$cmdlogic->setConfiguration('topic', $topic);
					$cmdlogic->save();
				}$elogic->checkAndUpdateCmd($cmdId,$value);
			
			$topic = $message->topic.'/set'; # ici création de la commande action pour le topic associé à la commande info
			$cmdId = $cmdId.'-Action';
			$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				if (!is_object($cmdlogic)) {
					log::add('easyMQTT', 'debug', 'Création de la commande action : ' . $cmdId. ' pour l\'équipement '. $eqLogicName);
					log::add('easyMQTT', 'info', 'Création d\'une commande');
					$cmdlogic = new easyMQTTCmd();
					$cmdlogic->setEqLogic_id($elogic->getId());
					$cmdlogic->setEqType('easyMQTT');
					if (stripos($cmdId,'bright') !== false){
						$cmdlogic->setSubType('slider');
						$cmdlogic->setConfiguration('minValue', 0);
						$cmdlogic->setConfiguration('maxValue', 100);
					}elseif (stripos($cmdId,'rgb') !== false){
						$cmdlogic->setSubType('color');
					}elseif (stripos($cmdId,'ct') !== false){
						$cmdlogic->setSubType('slider');
						$cmdlogic->setConfiguration('minValue', 1700);
						$cmdlogic->setConfiguration('maxValue', 6500);
					}else {
						$cmdlogic->setSubType('other');
					}
					$cmdlogic->setLogicalId($cmdId);
					$cmdlogic->setType('action');						  
					$cmdlogic->setName($cmdId);
					$cmdlogic->setConfiguration('topic', $topic);
					$cmdlogic->save();
				}//$elogic->checkAndUpdateCmd($cmdId,$value);
		}
	}
	
	
	####################################################
	##################### ZIGBEE #######################
	####################################################
	if (stripos($message->topic,'zigbee2mqtt') !== false){	
		log::add('easyMQTT', 'info', 'C\'est du ZIGBEE que l\'on vient de trouver : '  . $message->topic . ' On va le filtrer');
		// if (strpos($nodeid,'zigbee2mqtt/bridge/info') !== false || strpos($nodeid,'zigbee2mqtt/bridge/groups') !== false || strpos($nodeid,'zigbee2mqtt/bridge/config') !== false || strpos($nodeid,'zigbee2mqtt/bridge/logging') !== false || strpos($nodeid,'zigbee2mqtt/bridge') !== false){	
		if (strpos($message->topic,'zigbee2mqtt/bridge/info') !== false || strpos($message->topic,'zigbee2mqtt/bridge/groups') !== false || strpos($message->topic,'zigbee2mqtt/bridge/config') !== false || strpos($message->topic,'zigbee2mqtt/bridge/logging') !== false || strpos($message->topic,'zigbee2mqtt/bridge/state') !== false){	
			log::add('easyMQTT', 'debug', '-- On vient de filtrer : ' . $message->topic);
			$goOnZigbee = "no";
		}else{
			$goOnZigbee = "yes";
		}
	}
	if($goOnZigbee == "yes"){
		  log::add('easyMQTT','debug','Func message - ELSE JSON - easyMQTT.class.php');
		  $json = json_decode($value, true); 
		  log::add('easyMQTT', 'debug', 'Type de json : '. gettype($json) .'');
		  // log::add('easyMQTT', 'debug', 'Valeur de json : '. var_dump($json) .'');
		  log::add('easyMQTT', 'debug', 'Valeur de json : '. print_r($json, true) .'');
		 						
			foreach ($json as $equipment => $value) {
				log::add('easyMQTT', 'debug', '********************* ************************** ***********************');
				log::add('easyMQTT', 'debug', '********************* Equipement CREATION/UPDATE ***********************');
				log::add('easyMQTT', 'debug', '********************* ************************** ***********************');
				
				//log::add('easyMQTT', 'debug', 'Valeur de equipment : '. print_r($equipment, true) .'');
				log::add('easyMQTT', 'debug', 'Valeur de equipment : '. $equipment);
				log::add('easyMQTT', 'debug', 'Valeur de value : '. print_r($value, true) .'');
				if(is_array($value['definition'])){ ## ZIGBEE2MQTT : on a trouver un tableau, donc on peut avancer dans la création d'un équipement jeedom
					log::add('easyMQTT', 'debug', 'Valeur de [définition][description] : '. $value['definition']['description'] .'');
					log::add('easyMQTT', 'debug', 'Valeur de [définition][model] : '. $value['definition']['model'] .'');
					log::add('easyMQTT', 'debug', 'Valeur de [définition][vendor] : '. $value['definition']['vendor'] .'');
					log::add('easyMQTT', 'debug', 'Valeur de [friendly_name] : '. $value['friendly_name'] .'');
					log::add('easyMQTT', 'debug', 'Valeur de [power_source] : '. $value['power_source'] .'');
					log::add('easyMQTT', 'debug', 'Valeur de [ieee_address] : '. $value['ieee_address'] .'');
					
					$eqLogicName = $value['definition']['vendor'].' - '.$value['definition']['description'];
					log::add('easyMQTT', 'debug', 'Valeur de eqLogicName : '. $eqLogicName .'');
					$eqLogicId = $value['definition']['model'].'-'.$value['ieee_address'];
					log::add('easyMQTT', 'debug', 'Valeur de eqLogicId : '. $eqLogicId .'');
					
					$firstPart = explode("/", $message->topic); 
					$topicJson = $firstPart[0] . '/' . $value['friendly_name'] ;
					log::add('easyMQTT', 'debug', 'Valeur de topicJson : '. $topicJson .'');
					$elogic = self::byLogicalId($eqLogicId, 'easyMQTT');
					if (!is_object($elogic)) {
					  $elogic = new easyMQTT();
					  $elogic->setEqType_name('easyMQTT');
					  $elogic->setLogicalId($eqLogicId);
					  $elogic->setName($eqLogicName);
					  // $elogic->setConfiguration('topic', $nodeid);
					  $elogic->setConfiguration('topic', $topicJson);
					  $elogic->setConfiguration('type', $type);
					  $elogic->setConfiguration('modelShort', $value['definition']['model']);					  
					  $elogic->setConfiguration('modelLong', $value['definition']['description']);					  
					  log::add('easyMQTT', 'info', 'Saving device ' . $eqLogicId);
					  $elogic->save();
					}
					$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
					$elogic->setConfiguration('type','zigbee');
					$elogic->save();
					
					
					
					foreach($value['definition']['exposes'] as $exposes){
						log::add('easyMQTT', 'debug', 'Valeur de [définition][exposes] : '. print_r($exposes, true) .'');
						log::add('easyMQTT', 'debug', 'Valeur de [definition][exposes][access]  : '. $exposes['access'] .'');
						log::add('easyMQTT', 'debug', 'Valeur de [definition][exposes][name] : '. $exposes['name'] .'');
						log::add('easyMQTT', 'debug', 'Valeur de [definition][exposes][property] : '. $exposes['property'] .'');
						log::add('easyMQTT', 'debug', 'Valeur de [definition][exposes][type]  : '. $exposes['type'] .'');
						log::add('easyMQTT', 'debug', 'Valeur de [definition][exposes][unit]  : '. $exposes['unit'] .'');
						
						$firstPart = explode("/", $message->topic);
						#log::add('easyMQTT', 'debug', 'Valeur de firstPart si Array : '. print_r($firstPart) .'');
						#log::add('easyMQTT', 'debug', 'Valeur de firstPart[0] : '. $firstPart[0] .'');
						// $eqCmdName = $exposes['name'].' - '.$value['definition']['model'];
						// log::add('easyMQTT', 'debug', 'Valeur de eqCmdName : '. $eqCmdName .'');
						// $eqCmdId = $exposes['name'].'-'.$value['ieee_address']; // ETAIT FONCTIONNEL mais trop spécifique
						$eqCmdId = $exposes['name'];
						log::add('easyMQTT', 'debug', 'Valeur de eqCmdId : '. $eqCmdId .'');
						
						$topicJson = $firstPart[0] . '/' . $value['friendly_name'] .'{' . $exposes['property'] . '}'; # ici création de la commande pour le topic associé à la commande
						log::add('easyMQTT', 'debug', 'Valeur de topicJson : ' . $topicJson . ' pour l\'équipement '. $eqLogicName);
						$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$eqCmdId);
						if (!is_object($cmdlogic)) {
						  if($exposes['access'] == 'r'){
							  log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
							  log::add('easyMQTT', 'info', 'Création d\'une commande');
							  $cmdlogic = new easyMQTTCmd();
							  $cmdlogic->setEqLogic_id($elogic->getId());
							  $cmdlogic->setEqType('easyMQTT');
							  if($exposes['type'] == 'enum'){
								$cmdlogic->setSubType('string');
							  }else{
								$cmdlogic->setSubType($exposes['type']);
							  }
							  $cmdlogic->setLogicalId($eqCmdId);
							  $cmdlogic->setType('info');						  
							  $cmdlogic->setName($exposes['name']);
							  $cmdlogic->setUnite($exposes['unit']);
							  $cmdlogic->setConfiguration('topic', $topicJson);
							  $cmdlogic->save();
							  $elogic->checkAndUpdateCmd($eqCmdId,$value);
							}elseif($exposes['access'] == 'rw'){
							  log::add('easyMQTT', 'debug', 'Création de la commande action : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
							  log::add('easyMQTT', 'info', 'Création d\'une commande');
							  $cmdlogic = new easyMQTTCmd();
							  $cmdlogic->setEqLogic_id($elogic->getId());
							  $cmdlogic->setEqType('easyMQTT');
							  #$cmdlogic->setSubType($exposes['type']);
							  $cmdlogic->setLogicalId('r-'.$eqCmdId);
							  $cmdlogic->setType('action');						  
							  $cmdlogic->setName($exposes['name']);
							  $cmdlogic->setUnite($exposes['unit']);
							  $cmdlogic->setConfiguration('topic', $topicJson);
							  $cmdlogic->save();
							  //$elogic->checkAndUpdateCmd($eqCmdId,$value);
							  
							  log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
							  log::add('easyMQTT', 'info', 'Création d\'une commande');
							  $cmdlogic = new easyMQTTCmd();
							  $cmdlogic->setEqLogic_id($elogic->getId());
							  $cmdlogic->setEqType('easyMQTT');
							  #$cmdlogic->setSubType($exposes['type']);
							  $cmdlogic->setLogicalId('w-'.$eqCmdId);
							  $cmdlogic->setType('info');						  
							  $cmdlogic->setName($exposes['name']);
							  $cmdlogic->setConfiguration('topic', $topicJson);
							  $cmdlogic->save();
							  $elogic->checkAndUpdateCmd($eqCmdId,$value);
							  
							}elseif($exposes['access'] == 'w'){
							  log::add('easyMQTT', 'debug', 'Création de la commande action : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
							  log::add('easyMQTT', 'info', 'Création d\'une commande');
							  $cmdlogic = new easyMQTTCmd();
							  $cmdlogic->setEqLogic_id($elogic->getId());
							  $cmdlogic->setEqType('easyMQTT');
							  $cmdlogic->setSubType($exposes['type']);
							  $cmdlogic->setLogicalId($eqCmdId);
							  $cmdlogic->setType('action');						  
							  $cmdlogic->setName($exposes['name']);
							  $cmdlogic->setConfiguration('topic', $topicJson);
							  $cmdlogic->save();
							 // $elogic->checkAndUpdateCmd($eqCmdId,$value);
							}else{
									log::add('easyMQTT', 'debug', ' !!!!!!!!!!! Attention, on n\'a pas pu trouver de TYPE pour la commande');
							}
						}
												
						// cmd::setGeneric_Type
						// setGeneric_type(  $_generic_type)
					}
				}else {
					log::add('easyMQTT', 'debug', 'On va chercher un équipement existant afin de mettre à jour les commandes qui lui sont associées');
					log::add('easyMQTT', 'debug', 'Valeur de equipment : '. $equipment);
					log::add('easyMQTT', 'debug', 'Valeur de value : '. print_r($value, true) .'');
					foreach (self::byType('easyMQTT') as $eqLogicEasyMQTT) { // parcours tous les équipements du plugin easyMQTT
						log::add('easyMQTT', 'debug', 'FOREACH On est sur l\'équipement : ' . $eqLogicEasyMQTT->getConfiguration('topic'));
						//if($eqLogicEsxiHost->getConfiguration("type") == 'ESXi'){
						if (strpos($message->topic,$eqLogicEasyMQTT->getConfiguration('topic')) !== false){
							log::add('easyMQTT', 'debug', ' UPDATE de l\équipement '.$eqLogicEasyMQTT->getName());	
							
							$topic = $eqLogicEasyMQTT->getConfiguration('topic') .'{' . $equipment . '}'; # ici création de la commande pour le topic associé à la commande
							$eqCmdId = $equipment;
							
							$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($eqLogicEasyMQTT->getId(),$eqCmdId);
							if (!is_object($cmdlogic)) {
								log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
								log::add('easyMQTT', 'info', 'Création d\'une commande');
								$cmdlogic = new easyMQTTCmd();
								$cmdlogic->setEqLogic_id($eqLogicEasyMQTT->getId());
								$cmdlogic->setEqType('easyMQTT');
								$cmdlogic->setSubType('string');
								$cmdlogic->setLogicalId($eqCmdId);
								$cmdlogic->setType('info');						  
								$cmdlogic->setName($equipment);
								$cmdlogic->setConfiguration('topic', $topic);
								$cmdlogic->save();
							}$eqLogicEasyMQTT->checkAndUpdateCmd($eqCmdId,$value);			
							
							//$goOnZigbee = "no"; // Car on a trouvé un équipement déjà existant -> confirmer que ça ne bloque pas la création des commandes supplémentaires qui ne sont pas définie dans le json DEVICES
						}
					}
				}
				
				if($value['definition'] == $null){
					log::add('easyMQTT', 'debug', 'Valeur de description dans définition : est égale à NULL. Pour confirmer voici le type de l\'objet : '. $value['type'] .'');
				}
							
			}
    }
	
  }

  public static function publishMosquitto($_id, $_subject, $_message, $_retain) {
	  log::add('easyMQTT','debug','Func publishMosquitto - easyMQTT.class.php');
    if ($_message == '') {
      return;
    }
    log::add('easyMQTT', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
    $publish = new Mosquitto\Client(config::byKey('easymqttId', 'easyMQTT', 'Jeedom') . '_pub_' . $_id);
    if (config::byKey('easymqttUser', 'easyMQTT', 'none') != 'none') {
      $publish->setCredentials(config::byKey('easymqttUser', 'easyMQTT'), config::byKey('easymqttPass', 'easyMQTT'));
    }
    $publish->connect(config::byKey('easymqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('easymqttPort', 'easyMQTT', '1883'), 60);
    $publish->publish($_subject, $_message, config::byKey('easymqttQos', 'easyMQTT', '1'), $_retain);
    for ($i = 0; $i < 100; $i++) {
      // Loop around to permit the library to do its work
      $publish->loop(1);
    }
    $publish->disconnect();
    unset($publish);
  }

}

// Permet de convertir les couleurs Hexadecimale en valeur RGB 
function hex2rgbrgb($hexhex) {
	log::add('easyMQTT','debug','Func hex2rgbrgb - Valeur pour hexhex : ' . $hexhex);
	$hexhex = str_replace("#", "", $hexhex);
	if(strlen($hexhex) == 3) {
		$r = hexdec(substr($hexhex,0,1).substr($hexhex,0,1));
		$g = hexdec(substr($hexhex,1,1).substr($hexhex,1,1));
		$b = hexdec(substr($hexhex,2,1).substr($hexhex,2,1));
	} else {
		$r = hexdec(substr($hexhex,0,2));
		$g = hexdec(substr($hexhex,2,2));
		$b = hexdec(substr($hexhex,4,2));
	}
  
  	//$rgbrgb = array($r, $g, $b);
	//$rgbrgb = $r.$g.$b;
	 // return '"'.$r.'","'.$g .'","'.$b.'"';
	 return $r.','.$g .','.$b;
	//return $rgbrgb;
}

 // Permet de convertir des valeurs RGB en XY 
function convertRGBToXY($red, $green, $blue) {
		log::add('easyMQTT','debug','Func convertRGBToXY - Valeur pour red : ' . $red);
		log::add('easyMQTT','debug','Func convertRGBToXY - Valeur pour green : ' . $green);
		log::add('easyMQTT','debug','Func convertRGBToXY - Valeur pour blue : ' . $blue);
		$normalizedToOne['red'] = $red / 255; 
		$normalizedToOne['green'] = $green / 255; 
		$normalizedToOne['blue'] = $blue / 255; 
		foreach ($normalizedToOne as $key => $normalized) { 
			if ($normalized > 0.04045) { 
				$color[$key] = pow(($normalized + 0.055) / (1.0 + 0.055), 2.4);
			} else { 
				$color[$key] = $normalized / 12.92; 
			} 
		} 
		$xyz['x'] = $color['red'] * 0.664511 + $color['green'] * 0.154324 + $color['blue'] * 0.162028; 
		$xyz['y'] = $color['red'] * 0.283881 + $color['green'] * 0.668433 + $color['blue'] * 0.047685; 
		$xyz['z'] = $color['red'] * 0.000000 + $color['green'] * 0.072310 + $color['blue'] * 0.986039; 
		
		if (array_sum($xyz) == 0) { 
			$x = 0; $y = 0; 
		} else { 
			$x = $xyz['x'] / array_sum($xyz);
			$y = $xyz['y'] / array_sum($xyz);
		}
		return $x . ',' . $y;
 }

// permet de convertir des valeurs hexadecimale en decimale (pour yeelight par exemple)
function HexToDez($s) {
	log::add('easyMQTT','debug','Func HexToDez - Valeur pour $s : ' . $s);
	$s = str_replace("#", "", $s);
    $output = 0;
    for ($i=0; $i<strlen($s); $i++) {
        $c = $s[$i]; // you don't need substr to get 1 symbol from string
        if ( ($c >= '0') && ($c <= '9') )
            $output = $output*16 + ord($c) - ord('0'); // two things: 1. multiple by 16 2. convert digit character to integer
        elseif ( ($c >= 'A') && ($c <= 'F') ) // care about upper case
            $output = $output*16 + ord($s[$i]) - ord('A') + 10; // note that we're adding 10
        elseif ( ($c >= 'a') && ($c <= 'f') ) // care about lower case
            $output = $output*16 + ord($c) - ord('a') + 10;
    }

    return $output;
}


class easyMQTTCmd extends cmd {
  public function execute($_options = null) {
	  log::add('easyMQTT','debug','Func execute - easyMQTT.class.php');
    switch ($this->getType()) {
      case 'action' :
	  log::add('easyMQTT','debug','Func execute - type : ' . $this->getType());
	  log::add('easyMQTT','debug','Func execute - subType : ' . $this->getSubType());
      $request = $this->getConfiguration('request','1');
	  log::add('easyMQTT','debug','Func execute - request : ' . $request);
      $topic = $this->getConfiguration('topic');
	  log::add('easyMQTT','debug','Func execute - Contenu de topic : '. $topic);
      switch ($this->getSubType()) {
        case 'slider':
        //$request = str_replace('#slider#', $_options['slider'], $request);
		$request = $_options['slider'];
		log::add('easyMQTT','debug','Func execute - Case Slider : ' . $request);
        break;
        case 'color':
        //$request = str_replace('#color#', $_options['color'], $request);
		log::add('easyMQTT','debug','Func execute - Case color  contenu de $_options[color] : ' . $_options['color']);
		// yeelight alors on convertit la valeur hexadécimale
		if (stripos($topic,'yeelight') !== false){
			log::add('easyMQTT','debug','Func execute - C\'est du yeelight pour le Case color : ' . $request);
			//$RGBValue = hex2rgbrgb($_options['color']);
			//$RGBValue = explode(",", $RGBValue);
						
			//list($r, $g, $b) = $RGBValue;
			//log::add('easyMQTT','debug','Func execute - Case color RGB: ' . $r . ' - ' . $g ' . ' $b );
			// log::add('easyMQTT','debug','Func execute - Case color RGB: ' . $RGBValue );
			// log::add('easyMQTT','debug','Func execute - Case color RGB: ' . print_r($RGBValue) );
			// $request = convertRGBToXY("25","97","56");
			// $request = convertRGBToXY($RGBValue[0],$RGBValue[1],$RGBValue[2]);
			// log::add('easyMQTT','debug','Func execute - Case color Decimale: ' . $request);
			// $request = hex2dec($_options['color']);
			$request = HexToDez($_options['color']);
			log::add('easyMQTT','debug','Func execute - Case color Decimale: ' . $request);
		}
		log::add('easyMQTT','debug','Func execute - Case color : ' . $request);
        break;
        case 'message':
        $request = str_replace('#title#', $_options['title'], $request);
        $request = str_replace('#message#', $_options['message'], $request);
		log::add('easyMQTT','debug','Func execute - Case message : ' . $request);
        break;
      }
      $request = str_replace('\\', '', jeedom::evaluateExpression($request));
      $request = cmd::cmdToValue($request);
      easyMQTT::publishMosquitto($this->getId(), $topic, $request, $this->getConfiguration('retain','0'));
      }
      return true;
    }
  }
