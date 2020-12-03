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
      if (strpos($topic,',') === false) {
        $client->subscribe($topic, config::byKey('easymqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
        log::add('easyMQTT', 'debug', 'Subscribe to topic ' . $topic);
      } else {
        $topics = explode(',',$topic);
        foreach ($topics as $value){
           $client->subscribe($value, config::byKey('easymqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
          log::add('easyMQTT', 'debug', 'Subscribe to topic ' . $value);
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

    $elogic = self::byLogicalId($nodeid, 'easyMQTT'); ## création d'un équipement - version d'origine du plugin
    if (!is_object($elogic)) {
      $elogic = new easyMQTT();
      $elogic->setEqType_name('easyMQTT');
      $elogic->setLogicalId($nodeid);
      $elogic->setName($nodeid);
      $elogic->setConfiguration('topic', $nodeid);
      $elogic->setConfiguration('type', $type);
      log::add('easyMQTT', 'info', 'Saving device ' . $nodeid);
      $elogic->save();
    }
    $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
    $elogic->save();

    if ($type == 'topic') {
		log::add('easyMQTT', 'debug', 'Boucle topic dans fonction message');
    $cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
    if (!is_object($cmdlogic)) {
      log::add('easyMQTT', 'info', 'Cmdlogic n existe pas, creation');
      $cmdlogic = new easyMQTTCmd();
      $cmdlogic->setEqLogic_id($elogic->getId());
      $cmdlogic->setEqType('easyMQTT');
      $cmdlogic->setSubType('string');
      $cmdlogic->setLogicalId($cmdId);
      $cmdlogic->setType('info');
      $cmdlogic->setName( $cmdId );
      $cmdlogic->setConfiguration('topic', $message->topic);
      $cmdlogic->save();
    }
    $elogic->checkAndUpdateCmd($cmdId,$value);

  } else {
      // payload is json
	  log::add('easyMQTT','debug','Func message - ELSE JSON - easyMQTT.class.php');
      $json = json_decode($value, true); 
	  log::add('easyMQTT', 'debug', 'Type de json : '. gettype($json) .'');
	  // log::add('easyMQTT', 'debug', 'Valeur de json : '. var_dump($json) .'');
	  log::add('easyMQTT', 'debug', 'Valeur de json : '. print_r($json, true) .'');
	  // if(array_search('Xiaomi',$json) !== 0){ // On cherche le mot Xiaomi dans le json - topic : zigbee2mqtt/bouton-double-test/#,homeassistant/sensor/#
	   // ####### Topic de test numéro 2 : pour zigbee2mqtt 
			// $xiaomi = "Oui";
			// log::add('easyMQTT', 'debug', 'C\'est du Xiaomi : '. $xiaomi .'');
		// }else {
			// $xiaomi = "Non";
			// log::add('easyMQTT', 'debug', 'Ce n\'est pas du XIAOMI : '. $xiaomi .'');
		// }
	  
	  foreach ($json as $cmdId => $value) { ### système historique conservé quelques jours pour comparer mes créations avec la mécanique d'origine
			$topicjson = $nodeid . '{' . $cmdId . '}'; # ici création de la commande pour le topic associé à la commande
			log::add('easyMQTT', 'info', 'Message json : ' . $value . ' pour information : ' . $cmdId);
			$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
			if (!is_object($cmdlogic)) {
			  log::add('easyMQTT', 'info', 'Cmdlogic n existe pas, creation');
			  $cmdlogic = new easyMQTTCmd();
			  $cmdlogic->setEqLogic_id($elogic->getId());
			  $cmdlogic->setEqType('easyMQTT');
			  $cmdlogic->setSubType('string');
			  $cmdlogic->setLogicalId($cmdId);
			  $cmdlogic->setType('info');
			  $cmdlogic->setName( $cmdId );
			  $cmdlogic->setConfiguration('topic', $topicjson);
			  $cmdlogic->save();
			}
			$elogic->checkAndUpdateCmd($cmdId,$value);
		}
	  
	  
	    foreach ($json as $equipment => $value) {
			log::add('easyMQTT', 'debug', '****************************** ************** ******************************');
			log::add('easyMQTT', 'debug', '****************************** NEW Equipement ******************************');
			log::add('easyMQTT', 'debug', '****************************** ************** ******************************');
			
			// log::add('easyMQTT', 'debug', 'Valeur de equipment : '. print_r($equipment, true) .'');
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
				
				$firstPart = explode("/", $message->topic); ##Testing - not working yet
				$topicJson = $firstPart[0] . '/' . $value['friendly_name'] ;##Testing - not working yet
				// $topicJson = $firstPart[0].'/#';
				// $topicJson = $firstPart[0];
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
				  log::add('easyMQTT', 'info', 'Saving device ' . $eqLogicId);
				  $elogic->save();
				}
				$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
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
					$eqCmdId = $exposes['name'].'-'.$value['ieee_address'];
					log::add('easyMQTT', 'debug', 'Valeur de eqCmdId : '. $eqCmdId .'');
					
					$topicJson = $firstPart[0] . '/' . $value['friendly_name'] .'{' . $exposes['property'] . '}'; # ici création de la commande pour le topic associé à la commande
					log::add('easyMQTT', 'debug', 'Valeur de topicJson : ' . $topicJson . ' pour l\'équipement '. $eqLogicName);
					$cmdlogic = easyMQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$eqCmdId);
					if (!is_object($cmdlogic)) {
					  if($exposes['access'] == 'r'){
						  log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
						  log::add('easyMQTT', 'info', 'Cmdlogic n\'existe pas, creation');
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
						  log::add('easyMQTT', 'info', 'Cmdlogic n\'existe pas, creation');
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
						  $elogic->checkAndUpdateCmd($eqCmdId,$value);
						  
						  log::add('easyMQTT', 'debug', 'Création de la commande info : ' . $exposes['name']. ' pour l\'équipement '. $eqLogicName);
						  log::add('easyMQTT', 'info', 'Cmdlogic n\'existe pas, creation');
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
						  log::add('easyMQTT', 'info', 'Cmdlogic n\'existe pas, creation');
						  $cmdlogic = new easyMQTTCmd();
						  $cmdlogic->setEqLogic_id($elogic->getId());
						  $cmdlogic->setEqType('easyMQTT');
						  $cmdlogic->setSubType($exposes['type']);
						  $cmdlogic->setLogicalId($eqCmdId);
						  $cmdlogic->setType('action');						  
						  $cmdlogic->setName($exposes['name']);
						  $cmdlogic->setConfiguration('topic', $topicJson);
						  $cmdlogic->save();
						  $elogic->checkAndUpdateCmd($eqCmdId,$value);
						}else{
								log::add('easyMQTT', 'debug', ' !!!!!!!!!!! Attention, on n\'a pas pu trouver de TYPE pour la commande');
						}
					}
					
					
					// cmd::setGeneric_Type
					// setGeneric_type(  $_generic_type)
				}
			}
			if($value['definition'] == $null){
				log::add('easyMQTT', 'debug', 'Valeur de description dans définition : est égale à NULL. Pour confirmer voici le type de l\'objet : '. $value['type'] .'');
			}
						
			// log::add('easyMQTT', 'debug', 'Valeur de définition - test 0 : '. $value['definition'] .'');
			// log::add('easyMQTT', 'debug', 'Valeur de définition - test 1 : '. $equipment['definition'] .'');
			// log::add('easyMQTT', 'debug', 'Valeur de définition - test 2 : '. var_dump($value['definition']) .'');
			// log::add('easyMQTT', 'debug', 'Valeur de définition - test 3 : '. var_dump($equipment['definition']) .'');
			// if(is_array($equipment)){
				// log::add('easyMQTT', 'debug', 'Equipment est un array : '. print_r($equipment, true) .'');
			// }
			// if(is_array($value)){
				// log::add('easyMQTT', 'debug', 'Value est un array : '. print_r($value, true) .'');				
			// }
		}
			// $elogic = self::byLogicalId($nodeid, 'easyMQTT'); # Création d'un nouvel équipement si inexistant
			// if (!is_object($elogic)) {
			  // $elogic = new easyMQTT();
			  // $elogic->setEqType_name('easyMQTT');
			  // $elogic->setLogicalId($nodeid);
			  // $elogic->setName($nodeid);
			  // $elogic->setConfiguration('topic', $nodeid);
			  // $elogic->setConfiguration('type', $type);
			  // log::add('easyMQTT', 'info', 'Saving device ' . $nodeid);
			  // $elogic->save();
			// }
			// $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
			// $elogic->save();
	    // }
	  
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

class easyMQTTCmd extends cmd {
  public function execute($_options = null) {
	  log::add('easyMQTT','debug','Func execute - easyMQTT.class.php');
    switch ($this->getType()) {
      case 'action' :
      $request = $this->getConfiguration('request','1');
      $topic = $this->getConfiguration('topic');
      switch ($this->getSubType()) {
        case 'slider':
        $request = str_replace('#slider#', $_options['slider'], $request);
        break;
        case 'color':
        $request = str_replace('#color#', $_options['color'], $request);
        break;
        case 'message':
        $request = str_replace('#title#', $_options['title'], $request);
        $request = str_replace('#message#', $_options['message'], $request);
        break;
      }
      $request = str_replace('\\', '', jeedom::evaluateExpression($request));
      $request = cmd::cmdToValue($request);
      easyMQTT::publishMosquitto($this->getId(), $topic, $request, $this->getConfiguration('retain','0'));
      }
      return true;
    }
  }
