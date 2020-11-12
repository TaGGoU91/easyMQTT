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
    $return = array();
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    $server = socket_connect ($socket , config::byKey('mqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('mqttPort', 'easyMQTT', '1883'));
    $return[] = array(
      'test' => __('Mosquitto', __FILE__),
      'result' => ($server) ? __('OK', __FILE__) : __('NOK', __FILE__),
      'advice' => ($server) ? '' : __('Indique si Mosquitto est disponible', __FILE__),
      'state' => $server,
    );
    return $return;
  }

  public static function deamon_info() {
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
    return $return;
  }

  public static function deamon_start($_debug = false) {
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
    log::add('easyMQTT', 'info', 'Paramètres utilisés, Host : ' . config::byKey('MQTTAdress', 'easyMQTT', '127.0.0.1') . ', Port : ' . config::byKey('mqttPort', 'easyMQTT', '1883') . ', ID : ' . config::byKey('mqttId', 'easyMQTT', 'Jeedom'));
    $client = new Mosquitto\Client(config::byKey('mqttId', 'easyMQTT', 'Jeedom'));
    $client->onConnect('easyMQTT::connect');
    $client->onDisconnect('easyMQTT::disconnect');
    $client->onSubscribe('easyMQTT::subscribe');
    $client->onMessage('easyMQTT::message');
    $client->onLog('easyMQTT::logmq');
    $client->setWill('/jeedom', "Client died :-(", 1, 0);

    try {
      if (config::byKey('mqttUser', 'easyMQTT', 'none') != 'none') {
        $client->setCredentials(config::byKey('mqttUser', 'easyMQTT'), config::byKey('mqttPass', 'easyMQTT'));
      }
      $client->connect(config::byKey('mqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('mqttPort', 'easyMQTT', '1883'), 60);
      $topic = config::byKey('mqttTopic', 'easyMQTT', '#');
      if (strpos($topic,',') === false) {
        $client->subscribe($topic, config::byKey('mqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
        log::add('easyMQTT', 'debug', 'Subscribe to topic ' . $topic);
      } else {
        $topics = explode(',',$topic);
        foreach ($topics as $value){
           $client->subscribe($value, config::byKey('mqttQos', 'easyMQTT', 1)); // !auto: Subscribe to root topic
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
    log::add('easyMQTT', 'info', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
    config::save('status', '1',  'easyMQTT');
  }

  public static function disconnect( $r ) {
    log::add('easyMQTT', 'debug', 'Déconnexion de Mosquitto avec code ' . $r);
    config::save('status', '0',  'easyMQTT');
  }

  public static function subscribe( ) {
    log::add('easyMQTT', 'debug', 'Subscribe to topics');
  }

  public static function logmq( $code, $str ) {
    if (strpos($str,'PINGREQ') === false && strpos($str,'PINGRESP') === false) {
      log::add('easyMQTT', 'debug', $code . ' : ' . $str);
    }
  }

  public static function message( $message ) {
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

    $elogic = self::byLogicalId($nodeid, 'easyMQTT');
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
      $json = json_decode($value, true);
      foreach ($json as $cmdId => $value) {
        $topicjson = $nodeid . '{' . $cmdId . '}';
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
    }
  }

  public static function publishMosquitto($_id, $_subject, $_message, $_retain) {
    if ($_message == '') {
      return;
    }
    log::add('easyMQTT', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
    $publish = new Mosquitto\Client(config::byKey('mqttId', 'easyMQTT', 'Jeedom') . '_pub_' . $_id);
    if (config::byKey('mqttUser', 'easyMQTT', 'none') != 'none') {
      $publish->setCredentials(config::byKey('mqttUser', 'easyMQTT'), config::byKey('mqttPass', 'easyMQTT'));
    }
    $publish->connect(config::byKey('mqttAdress', 'easyMQTT', '127.0.0.1'), config::byKey('mqttPort', 'easyMQTT', '1883'), 60);
    $publish->publish($_subject, $_message, config::byKey('mqttQos', 'easyMQTT', '1'), $_retain);
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
