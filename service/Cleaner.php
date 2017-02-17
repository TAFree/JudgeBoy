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

	public function __construct () {

		try {
	
			// Connect to MySQL database TAFreeDB
			#$this->hookup = UniversalConnect::doConnect();
			
			
			while(true) {
                exec('docker stop 2d1', $output);
                print_r($output);
			}

			$this->hookup = null;
			
			exit();
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
