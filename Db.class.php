<?php
/**
 *  DB - A simple database class 
 *
 * @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
 * @git 		https://github.com/indieteq/PHP-MySQL-PDO-Database-Class
 * @version      0.2ab
 *
 */

class DB
{
	# @object, The PDO object
	private $pdo;

	# @object, PDO statement object
	private $sQuery;

	# @array,  The database settings
	private $settings;

	# @bool ,  Connected to the database
	private $bConnected = false;

	# @object, Object for logging exceptions	
	private $log;

	# @array, The parameters of the SQL query
	private $parameters;
		
       /**
	*   Default Constructor 
	*
	*	1. Instantiate Log class.
	*	2. Connect to database.
	*	3. Creates the parameter array.
	*/
		public function __construct()
		{ 			

			$this->Connect();
			$this->parameters = array();
		}
		
       /**
	*	This method makes connection to the database.
	*	
	*	1. Reads the database settings from a ini file. 
	*	2. Puts  the ini content into the settings array.
	*	3. Tries to connect to the database.
	*	4. If connection failed, exception is displayed and a log file gets created.
	*/
		private function Connect()
		{
			$this->settings = parse_ini_file("settings.ini.php");
			$dsn = 'mysql:dbname='.$this->settings["dbname"].';host='.$this->settings["host"].'';
			try 
			{
				# Read settings from INI file, set UTF8
				$this->pdo = new PDO($dsn, $this->settings["user"], $this->settings["password"]);
				
				# We can now log any exceptions on Fatal error. 
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				# Disable emulation of prepared statements, use REAL prepared statements instead.
				$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				
				# Connection succeeded, set the boolean to true.
				$this->bConnected = true;
			}
			catch (PDOException $e) 
			{
				# Write into log
				echo $this->ExceptionLog($e->getMessage());
				echo $this->ExceptionLog($e->getMessage());
				die();
			}
		}
	/*
	 *   You can use this little method if you want to close the PDO connection
	 *
	 */
	 	public function CloseConnection()
	 	{
	 		# Set the PDO object to null to close the connection
	 		# http://www.php.net/manual/en/pdo.connections.php
	 		$this->pdo = null;
	 	}
		
       /**
	*	Every method which needs to execute a SQL query uses this method.
	*	
	*	1. If not connected, connect to the database.
	*	2. Prepare Query.
	*	3. Parameterize Query.
	*	4. Execute Query.	
	*	5. On exception : Write Exception into the log + SQL query.
	*	6. Reset the Parameters.
	*/	
		private function Init($query,$parameters = "")
		{
		# Connect to database
		if(!$this->bConnected) { $this->Connect(); }
		try {
				# Prepare query
				$this->sQuery = $this->pdo->prepare($query);
				
				# Add parameters to the parameter array	
				$this->bindMore($parameters);

				# Bind parameters
				if(!empty($this->parameters)) {
					foreach($this->parameters as $param)
					{
						$parameters = explode("\x7F",$param);
						$this->sQuery->bindParam($parameters[0],$parameters[1]);
					}		
				}

				# Execute SQL 
				$this->succes 	= $this->sQuery->execute();		
			}
			catch(PDOException $e)
			{
					# Write into log and display Exception
					echo $this->ExceptionLog($e->getMessage(), $query );
					die();
			}

			# Reset the parameters
			$this->parameters = array();
		}
		
       /**
	*	@void 
	*
	*	Add the parameter to the parameter array
	*	@param string $para  
	*	@param string $value 
	*/	
		public function bind($para, $value)
		{	
			$this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . $value;
		}
       /**
	*	@void
	*	
	*	Add more parameters to the parameter array
	*	@param array $parray
	*/	
		public function bindMore($parray)
		{
			if(empty($this->parameters) && is_array($parray)) {
				$columns = array_keys($parray);
				foreach($columns as $i => &$column)	{
					$this->bind($column, $parray[$column]);
				}
			}
		}
       /**
	*   	If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
	*	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows
	*
	*   	@param  string $query
	*	@param  array  $params
	*	@param  int    $fetchmode
	*	@return mixed
	*/			
		public function query($query,$params = null, $fetchmode = PDO::FETCH_ASSOC)
		{
			$query = trim($query);

			$this->Init($query,$params);

			$rawStatement = explode(" ", $query);
			
			# Which SQL statement is used 
			$statement = strtolower($rawStatement[0]);
			
			if ($statement === 'select' || $statement === 'show') {
				return $this->sQuery->fetchAll($fetchmode);
			}
			elseif ( $statement === 'insert' || $statement === 'rowcount' || $statement === 'update' || $statement === 'delete' ) {
				return $this->sQuery->rowCount();	
			}	
			else {
				return NULL;
			}
		}
		
      /**
       *  Returns the last inserted id.
       *  @return string
       */	
		public function lastInsertId() {
			return $this->pdo->lastInsertId();
		}	
		
       /**
	*	Returns an array which represents a column from the result set 
	*
	*	@param  string $query
	*	@param  array  $params
	*	@return array
	*/	
		public function column($query,$params = null)
		{
			$this->Init($query,$params);
			$Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);		
			
			$column = null;

			foreach($Columns as $cells) {
				$column[] = $cells[0];
			}

			return $column;
			
		}	
       /**
	*	Returns an array which represents a row from the result set 
	*
	*	@param  string $query
	*	@param  array  $params
	*   	@param  int    $fetchmode
	*	@return array
	*/	
		public function row($query,$params = null,$fetchmode = PDO::FETCH_ASSOC)
		{				
			$this->Init($query,$params);
			return $this->sQuery->fetch($fetchmode);			
		}
       /**
	*	Returns the value of one single field/column
	*
	*	@param  string $query
	*	@param  array  $params
	*	@return string
	*/	
		public function single($query,$params = null)
		{
			$this->Init($query,$params);
			return $this->sQuery->fetchColumn();
		}
       /**	
	* Writes the log and returns the exception
	*
	* @param  string $message
	* @param  string $sql
	* @return string
	*/
	function editlog($log,$date,$message) {

		$logcontent = "Time : " . $date->format('H:i:s')."\r\n" . $message ."\r\n\r\n";
		$logcontent = $logcontent . file_get_contents($log);
		file_put_contents($log, $logcontent);

	}
	public function writelog($message){
		$path = '/logs/';
		date_default_timezone_set('Europe/Amsterdam');
		$path  = dirname(__FILE__)  . $path;
		$date = new DateTime();

		$log = $path . $date->format('Y-m-d').".txt";

		if(is_dir($path)) {
			if(!file_exists($log)) {
				$fh  = fopen($log, 'a+') or die("Fatal Error !");
				$logcontent = "Time : " . $date->format('H:i:s')."\r\n" . $message ."\r\n";
				fwrite($fh, $logcontent);
				fclose($fh);
			}
			else {
				$this->editlog($log,$date, $message);
			}
		}
		else {
			if(mkdir($path,0777) === true)
			{
				$this->writelog($message);
			}
		}

	}
	private function ExceptionLog($message , $sql = "")
	{
		
		$exception  = '<meta charset="UTF-8">Įvyko klaida <br /> Klaida pranešta administratoriui <br />';
		$exception .= "<br /> <a href='index.php'>Grįžti į pagrindinį puslapį</a>";

		if(!empty($sql)) {
			# Add the Raw SQL to the Log
			if(!empty($sql)) {
				
			}
			$link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
				$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
			}else{
				$ip=$_SERVER['REMOTE_ADDR'];
			}
			$post = "";
			$get = "";
			$cookie = "";
			foreach ($_GET as $key => $value){
				$get .= "\r\n".$key." > ".$value;
			}
			foreach ($_POST as $key => $value){
				$post .= "\r\n".$key." > ".$value;
			}
			foreach ($_COOKIE as $key => $value){
				$cookie .= "\r\n".$key." > ".$value;
			}
			$message .= "\r\nRaw SQL : "  . $sql;
			$message .= "\r\nIP : ". $ip;
			$message .= "\r\nLINK : ". $link;
			$message .= "\r\nPOST : ". $post;
			$message .= "\r\nGET : ". $get;
			$message .= "\r\nCOOKIE : ". $cookie;
		}
			# Write into log
			$this->writelog($message);

		return $exception;
	}
	
}
?>
