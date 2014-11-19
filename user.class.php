<?php
require_once 'Db.class.php';


class user{
	private $_error = "";
	
	function __construct(){

		$this->data = "";
		$this->db = new db;
		$this->settings = require_once("config.php");
		if($this->settings["firstfield"] == ""){
			die("Config file not found");
		}
	}
	function __get($value){
		return $this->data[$value];
	}
	function logout()
	{
		setcookie("token", '', time() - 3600);
		$this->token = "logout";
	}
	
	function auth(){
		if(isset($this->token) and $this->token != ""){
			$token = $this->token;
		}else{
			$token = $_COOKIE["token"];
		}
		
		if($token == "" or $token == "0" or $token == "logout"){
			$this->_error["auth"] = "not logged in";
			
			return false;
			
		}
		$this->db->bind("token",$token);
		
		$tablename = $this->settings["tablename"];
		$tokenfield = $this->settings["tokenfield"];
		
		$check = $this->db->row("SELECT * FROM `$tablename` WHERE `$tokenfield` = :token");
		$this->data = $check;
		if($check["id"] == ""){
			$this->_error["auth"] = "denied";
			return false;
		}
		if($this->settings["adminactivation"] == 1){
			if($check["activated"] != 1){
				$this->_error["auth"] = "not activated";
				return false;
			
			}
		}
	}
	function __filterForm(){
		$firstfield = $this->settings["firstfield"];
		$secondfield = $this->settings["secondfield"];
		

		if (!isset($this->settings["regdata"][$firstfield])) {
			$this->settings["regdata"][$firstfield] = array();
		}
		if (!isset($this->settings["regdata"][$secondfield])) {
			$this->settings["regdata"][$secondfield] = array();
		}
		
		
		foreach($this->settings["regdata"] as $key => $item){
			if($this->settings["method"] == "get"){
				$userinput = $_GET[$key];
			}else{
				$userinput = $_POST[$key];
			}
			

			if (!array_key_exists("notRequired", $item)) {
				if($userinput == ""){
					$this->_error["register"] = "$key empty";
					return false;
				}
			}
			if (array_key_exists("isEmail", $item)) {
				if(!filter_var($userinput, FILTER_VALIDATE_EMAIL)){
					$this->_error["register"] = "$key not email";
					return false;
				}
			}
			if (array_key_exists("isNumeric", $item)) {
				if($item["isNumeric"] == true){
					if (!is_numeric($userinput)) {
						$this->_error["register"] = "$key not numeric";
						return false;
					}
				}

			}
			if (array_key_exists("maxChar", $item)) {
				if (strlen($userinput)>$item["maxChar"]) {
					echo strlen($userinput);
					echo $userinput;
					$this->_error["register"] = "$key too long";
					return false;
				}
			}
			if (array_key_exists("mustContain", $item)) {
				if (!in_array($userinput, $item["mustContain"])){
					if($item["notRequired"] != true){
						$this->_error["register"] = "$key doesn't contain required value";
						return false;
					}else{
						if($userinput != ""){
							$this->_error["register"] = "$key doesn't contain required value";
							return false;
						}
					}
				}
			}
			if (array_key_exists("maxNumber", $item)) {
				if($item["isNumeric"] == false){
					if($userinput > $item["maxNumber"]){
						$this->_error["register"] = "$key too high";
						return false;
					}
				}else{
					if($userinput > $item["maxNumber"]){
						$this->_error["register"] = "$key too high";
						return false;
					}
					if(!ctype_digit($userinput)){
						$this->_error["register"] = "$key not whole";
						return false;
					}
				}

			}
			if (array_key_exists("notSymbols", $item)) {
				if (!ctype_alnum($userinput)) {
					if($item["notRequired"] == true and $userinput != ""){
						$this->_error["register"] = "$key has symbols";
						return false;
					}
				}
			}
			if (array_key_exists("formatDowncase", $item)) {
				if($this->settings["method"] == "get"){
					$_GET[$key] = strtolower($userinput);
				}else{
					$_POST[$key] = strtolower($userinput);
				}

			}
			if (array_key_exists("minNumber", $item)) {
				if($item["isNumeric"] == false){
					if($userinput < $item["minNumber"]){
						$this->_error["register"] = "$key too low";
						return false;
					}
				}else{
					if($userinput < $item["minNumber"]){
						$this->_error["register"] = "$key too low";
						return false;
					}
					if(!ctype_digit($userinput)){
						$this->_error["register"] = "$key not whole";
						return false;
					}
				}

			}
			if (array_key_exists("minChar", $item)) {
				if (strlen($userinput)<=$item["minChar"]) {
					$this->_error["register"] = "$key too short";
					return false;
				}
			}
		}
		

	}
	function error($type = "all"){
		if($this->_error == ""){
			return "";
		}
		if($type == "all"){
			return implode(" ",$this->_error);
		}
		return $this->_error[$type];
	}

	function register()
	{
		$this->__filterForm();
		if($this->error() != ""){
			return false;
		}
		$bindarray = array();
		
		$firstfield = $this->settings["firstfield"];
		$secondfield = $this->settings["secondfield"];

		if($this->settings["method"] == "get"){
			$userinput = $_GET;
		}else{
			$userinput = $_POST;
		}
		
		if($this->settings["method"] == "get"){
			$_GET[$secondfield] = password_hash($_GET[$secondfield], PASSWORD_DEFAULT);
		}else{
			$_POST[$secondfield] = password_hash($_POST[$secondfield], PASSWORD_DEFAULT);
		}
		
		$tablename = $this->settings["tablename"];
		
		$this->db->bind("firstfield",$userinput[$firstfield]);
		$checkmail = $this->db->column("SELECT COUNT(`$firstfield`) FROM `$tablename` WHERE `$firstfield` = :firstfield");
		if ($checkmail[0] != 0){
			$this->_error["register"] = "user with $firstfield already exists";
			return false;
		}
		
		foreach($this->settings["regdata"] as $key => $item){
			if($this->settings["method"] == "get"){
				$bindarray[$key] = $_GET[$key];
			}else{
				$bindarray[$key] = $_POST[$key];
			}
		}
	
		$this->db->bindMore($bindarray);
		$dbtest ="INSERT INTO `$tablename` (`".implode("`,`",array_keys($this->settings["regdata"]))."`)
		VALUES (:".implode(",:",array_keys($this->settings["regdata"])).");";
		$this->db->query($dbtest);
		
	}
	function tokengenerate($length){
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}
	function login($inputfield1,$inputfield2){
		$this->loginstatus = 0;
		$tablename = $this->settings["tablename"];
		$firstfield = $this->settings["firstfield"];
		$secondfield = $this->settings["secondfield"];
		
		$this->db->bind("firstfield", $inputfield1);
		$userinfo = $this->db->row("SELECT * FROM $tablename where $firstfield = :firstfield");
		if($userinfo["id"] == ""){
			$this->_error["login"] = 1;  //incorrect email/username
			return false;
		}
		if($this->settings["adminactivation"] == 1 and $userinfo["activated"] == 0){
			$this->_error["login"] = 2; // account not activated
			return false;
		}
		if (!password_verify($inputfield2, $userinfo[$secondfield])) {
			$this->_error["login"] = 3; // invalid password
			return false;
		}
		
		if($userinfo["token"] == "" or $userinfo["token"] == "0"){
			$token = $this->tokengenerate(64);
			$this->db->bind("token", $token);
			$this->db->bind("firstfield", $inputfield1);
			$this->db->query("UPDATE $tablename SET `token`=:token WHERE  `$firstfield`= :firstfield");
			setcookie("token", $token, time() + 360000000);
			$this->token = $token;
		}else{
			setcookie("token", $userinfo["token"], time() + 360000000);
			$this->token = $userinfo["token"];
		}
		
		
		return true;
		}
	
}