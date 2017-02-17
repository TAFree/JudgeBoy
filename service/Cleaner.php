<?php
/**
 * A cleaner to restart Docker container when one judge client is getting stuck.
 *
 * @authur Yu Tzu Wu <abby8050@gmail.com>
 */

ini_set('display_errors', '1');
ERROR_REPORTING(E_ALL);

interface IConnectInfo {

	const HOST = '45.32.107.147';
	const UNAME = 'ghassho';
	const PW = 'ghassho_change';
	const DBNAME = 'TAFreeDev';

	public static function doConnect();
}

class Cleaner {

	private $hookup;
	private $container_id;
	private $unique_key;

	public function __construct () {

		try {
	
			// Connect to MySQL database TAFreeDB
			$this->hookup = UniversalConnect::doConnect();
			
			// Sign up process table and get container ID when status is TLE
			$this->unique_key = 'Free<br>(' . time() . ')';
			$stmt_sign = $this->hookup->prepare('UPDATE process SET status=\'' . $this->unique_key . '\' WHERE status=\'TLE\''); 
			$stmt_sign->execute();
			$stmt_get = $this->hookup->prepare('SELECT judger FROM process WHERE status=\'' . $this->unique_key . '\''); 
			$stmt_get->execute();
			
			// Restart container
			while ($row = $stmt_get->fetch(\PDO::FETCH_ASSOC)) {
				$pre = strpos($row['judger'], '(') + 1;
				$pos = strpos($row['judger'], ')') ;
				$this->container_id = substr($row['judger'], $pre, $pos - $pre);
				exec('docker stop ' . $this->container_id . ' && docker start ' . $this->container_id);
			}

			$this->hookup = null;
	
		}
		catch (\PDOException $e) {
			echo 'Error: ' . $e->getMessage() . '<br>';
		}

	}

}

class UniversalConnect implements IConnectInfo {
	
	private static $servername = IConnectInfo::HOST;
	private static $dbname = IConnectInfo::DBNAME;
	private static $username = IConnectInfo::UNAME;
	private static $password = IConnectInfo::PW;
	private static $conn;

	public static function doConnect() {
		self::$conn = new \PDO('mysql:host=' . self::$servername . ';dbname=' . self::$dbname, self::$username, self::$password);
		self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return self::$conn;	
	}

}

$cleaner = new Cleaner();

?>
