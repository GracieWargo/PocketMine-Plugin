<?php
/*
__PocketMine Plugin__
name=Chat
description=Since craftyourbukkit wanted me to make it.
version=0.8
author=Gracie
class=Chat
apiversion=11,12
*/
class Chat implements Plugin {
	private $api;
	private $openChat;
	public function __construct(ServerAPI $api, $server = false) {
		$this->api = $api;
		$this->openChat = array();
	}
	
	public function init() {
		$this->api->addHandler("player.chat", array($this, "eventHandle"), 50);
		$this->api->addHandler("player.join", array($this, "eventHandle"), 50);
		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array("chat"=>"on","botid"=>"f5d922d97e345aa1","botname"=>"PeepzCraft"));
		$this->api->console->register('chat', "[on|off] Enable or disable PandoraBot.",array($this, 'commandHandle'));
		$this->api->console->alias("cb", "chat);
	}
	
	public function commandHandle($cmd, $params, $issuer, $alias){
		switch($cmd){
			case 'chat':
				if(isset($params[0]) && ($params[0] == "on" or $params[0] == "off")) {
					$this->config->set("chat",$params[0]);
					$this->config->save();
					$output = "[Chat] Set to " . $params[0] . ".\n";
				}else{
					$output = "Usage: /$cmd [on/off]\n";
				}
			break;
		}
		return $output;
	}
	
	public function eventHandle($data, $event) {
		switch ($event) {
			case "player.chat":
				if($this->config->get("chat") == "on") {
					$player = $data["player"];
					$message = $data["message"];
					if ((isset($this->openChat[$player->eid]) != false) or ((isset($this->openChat[$player->eid]) == false) and ($this->InStr(strtolower($message),strtolower($this->config->get("botname"))) != -1))) {
						$this->openChatBot[$player->eid] = true;
						$player->sendChat($message,$player);
						$messageURL = $this->curlpost(
							"",
							array(
								 "botid" => $this->config->get("botid"),
								 "input" => $message,
								 "custid" => $player->username
							)
						);
						$response = "Sorry ".$player->username.", can you repeat your last message please ?";
						if($messageURL != false) {
							preg_match("/<that(.*)?>(.*)?<\/that>/", $messageURL, $match);
							$response = substr($match[2], 1, -1);
						}
						$player->sendChat($response,$this->config->get("botname"));
						if(($this->InStr(strtolower($message),"bye") != -1) and ($this->InStr(strtolower($message),strtolower($this->config->get("botname"))) != -1)) {
							unset($this->openChatBot[$player->eid]);
							//console($this->InStr(strtolower($message),"bye") . " " . $this->InStr(strtolower($message),strtolower($this->config->get("botname"))));
						}
						return false;
					}
				}
			break;
		}
	}
	
	public function InStr($haystack, $needle) { 
		$pos=strpos($haystack, $needle); 
		if ($pos !== false) 
		{ 
			return $pos; 
		} 
		else 
		{ 
			return -1; 
		} 
	} 
	
	public function curlpost($url, array $post = NULL, array $options = array()) {
		$defaults = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_POSTFIELDS => http_build_query($post)
		);
		$ch = curl_init();
		curl_setopt_array($ch, ($options + $defaults));
		if (!$result = curl_exec($ch))
		{
			//trigger_error(curl_error($ch));
			return false;
		}
		curl_close($ch);
		return $result;
	}
		
	public function __destruct() {
	}
}
