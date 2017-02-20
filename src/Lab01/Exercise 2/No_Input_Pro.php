<?php
/**
 * A PHP customizing judge script example with / without testdata for TAFree
 *
 * @authur Yu Tzu Wu <abby8050@gmail.com>
 */

ini_set('display_errors', '1');
ERROR_REPORTING(E_ALL);

interface IConnectInfo {

	const HOST = '45.32.107.147';
	const UNAME = 'account';
	const PW = 'password';
	const DBNAME = 'TAFreeDB';

	public static function doConnect();
}

interface ICustomInfo {
	const TESTDATA = 1; // 1) No testdata 2) Static testdata 
}

class Custom {

	private $stu_account;
	private $item;
	private $subitem;
	private $id;
	private $main;
	private $dir_name;
        private $testdata;
	private static $solution_output = array();
	private static $student_output = array();

	public function __construct () {

		// Get basic parameters
		$this->stu_account = Must::$stu_account;
		$this->item = Must::$item;
		$this->subitem = Must::$subitem;
		$this->id = Must::$id;
		$this->main = Must::$main;
	 	$this->dir_name = Must::$dir_name;
	        $this->testdata = Must::$testdata;
		
		// Solution and student source directory
		$solution_dir = $this->dir_name . '/solution';
		$student_dir = $this->dir_name . '/student';
		
		// Execute and compare output from both solution and student
		$result = array();
		foreach ($this->testdata as $key => $value) {
			
			$solution_output = $this->execute($solution_dir, $value);
			$student_output = $this->execute($student_dir, $value);
			
			// Check time limit from student's source code
			if ($student_output[0] === 'TLE') { 
				// Configure result that will response to client side
				$error_msg = '<h1>Failed: your program executed over time limit (1 sec).</h1>';
				Must::$view = Viewer::config($error_msg);
				// Time limit exceed
				Must::$status = 'TLE';
				return;
			}
			
			// Check runtime error from student's source code
			if (!empty($student_output[2])) { 
				// Configure result that will response to client side
				$error_msg = '<h1>Your source code has runtime error</h1>' . '<pre><code>' . $student_output[2] . '</code></pre>';
				Must::$view = Viewer::config($error_msg);
				// Runtime error
				Must::$status = 'RE';
				return;
			}
			
			$retval = strcmp($solution_output[1], $student_output[1]);
			array_push($result, $retval);
			array_push(self::$solution_output, $solution_output[1]);
			array_push(self::$student_output, $student_output[1]);
		
		}

		if (in_array(0, $result)) {	
			if (array_count_values($result)['0'] === count($result)) {
				// Accept
				Must::$status = 'AC';
			}else{
				// Not Accept
				Must::$status = 'NA';
			}
		}
		else {
			// Wrong Answer
			Must::$status = 'WA';
		}

		// Configure result that will response to client side
		$error_msg = null;
		Must::$view = Viewer::config($error_msg);
		
		return;
		
	}
	
	public static function getOutput($who) {
		switch($who) {
		    case 'student': 
			return self::$student_output;
		    case 'solution':
			return self::$solution_output;
		}
	}
	
	private function execute ($dir, $testdata) {
		
		// Declare output and error array
		$out_err = array();

		// Configure descriptor array
		$desc = array (
				0 => array ('pipe', 'r'), // STDIN for process
				1 => array ('pipe', 'w'), // STDOUT for process
				2 => array ('pipe', 'w') // STDERR for process
		);

		// Configure execution command
		$cmd = 'exec java -classpath ' . $dir . ' ';
	        $last_pos = strrpos($this->main, '.java');
	        $classname = substr($this->main, 0, $last_pos);
	        $cmd .= $classname;
	
		// Create execution process
		$process = proc_open($cmd, $desc, $pipes);
		
		// Get pid of execution process
		$process_status = proc_get_status($process);
		$pid = $process_status['pid'];	

		// Send input to command 
	        fwrite($pipes[0], $testdata);
		// Close STDIN pipe
		fclose($pipes[0]);
		
		// Wait seconds for executing student's source code
		if ($dir === $this->dir_name . '/student') {
			usleep(1000000);
			$exitcode = proc_get_status($process)['exitcode'];
			if ($exitcode === -1) {
				// Kill execution process
				posix_kill($pid, SIGTERM);
				$out_err[0] = 'TLE';
				return $out_err;
			}
			else {
				$out_err[0] = null;
			}
		}
		else {
			$out_err[0] = null;
		}
		
		// Get output of STDOUT or STDERR pipe
		$out_err[1] = stream_get_contents($pipes[1]);
		$out_err[2] = stream_get_contents($pipes[2]);
	
		// Close STDOUT and STDERR pipe
		fclose($pipes[1]);
		fclose($pipes[2]);

		return $out_err;
	}
	
}

class Must {

	public static $stu_account;
	public static $item;
	public static $subitem;
	public static $id;
	public static $testdata = array();
	public static $main;
	public static $status;
	public static $dir_name;
	public static $view;

	private static $hookup;

	public static function run () {
		
		// Arguments: student account, item, subitem, id
		self::$stu_account = $_SERVER['argv'][1];
		self::$item = $_SERVER['argv'][2];
		self::$subitem = $_SERVER['argv'][3];
		self::$id = $_SERVER['argv'][4];

		try {
	
			// Connect to MySQL database TAFreeDB
			self::$hookup = UniversalConnect::doConnect();						

			// Create directory to put source codes temporarily
			self::createDir();
			
			// Fetch student and solution source from table [item]_[subitem]
			self::fetchSource();
			
			// Fetch testdata
			self::fetchTestdata(ICustomInfo::TESTDATA);
				
			// Run and compare if both student and standard sources are compilable
			if (self::isCompilable()) {
				$runner = new Custom();
			}
			
           		 // Sava view
			self::saveView();
			
			// Update judge status
			self::updateStatus();
 
			// Do not remove directory for research
			//self::removeDir();

			self::$hookup = null;
			
			exit();
		}
		catch (PDOException $e) {
			echo 'Error: ' . $e->getMessage() . '<br>';
		}

	}
	
	public static function saveView () {
		$stmt = self::$hookup->prepare('UPDATE process SET view=:view WHERE id=\'' . self::$id . '\'');
		$stmt->bindParam(':view', self::$view);
		$stmt->execute();
	}
	
	private static function createDir () {
		self::$dir_name = './.process/process-' . self::$id;
		mkdir(self::$dir_name);
		mkdir(self::$dir_name . '/student');
		mkdir(self::$dir_name . '/solution');
	}

	private static function removeDir () {
		system('rm -rf ' . self::$dir_name, $retval);
		if ($retval !== 0 ) {
			echo 'Directory can not be removed...';
			exit();
		}
	}
	
	private static function fetchSource () {
		$stmt = self::$hookup->prepare('SELECT main, classname, original_source, ' . self::$stu_account . ' FROM ' . self::$item . '_' . self::$subitem);
		$stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($row['main'] === 'V') {
			    self::$main = $row['classname'];
			}
			$student = fopen(self::$dir_name . '/student/' . $row['classname'], 'w');
			fwrite($student, $row[self::$stu_account]);
			fclose($student);
			
			$solution = fopen(self::$dir_name . '/solution/' . $row['classname'], 'w');
			fwrite($solution, $row['original_source']);
			fclose($solution);

		}
	}
	
	private static function updateStatus () {
		
		// Update [item] table
		$stmt_item = self::$hookup->prepare('UPDATE ' . self::$item . ' SET ' . self::$stu_account . '=\'' . self::$status . '\' WHERE subitem=\'' . self::$subitem . '\'');
		$stmt_item->execute();
		
		// Update process table
		$stmt_process = self::$hookup->prepare('UPDATE process SET status=\'' . self::$status . '\' WHERE id=\'' . self::$id . '\'');
		$stmt_process->execute();

	}

	private static function fetchTestdata ($case) {
		switch ($case) {
			case 1: // No testdata
			    self::$testdata = array('');
			    return;
			case 2:
			    $stmt = self::$hookup->prepare('SELECT content FROM ' . self::$item . '_' . self::$subitem . '_testdata');
			    $stmt->execute();
			    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				array_push(self::$testdata, $row['content']);
			    }
			    return;
			default:
			    return;
            	}
	}

	private static function isCompilable () {

		// Solution and student source directory
		$solution_dir = self::$dir_name . '/solution';
		$student_dir = self::$dir_name . '/student';

		// Compile source code from student
		$solution_CE = self::compile($solution_dir);
		$student_CE = self::compile($student_dir);
		if (!empty($student_CE)) {
			
		        // Compile error
			self::$status = 'CE';
			
			// Configure result that will response to client side
			$error_msg = '<h1>Your source code has compile error</h1>' . '<pre><code>' . $student_CE . '</code></pre>';
			self::$view = Viewer::config($error_msg);
		
			return false;
	
		}
		return true;
	}

	private static function compile ($dir) {
		// Configure descriptor array
		$desc = array (
				0 => array ('pipe', 'r'), // STDIN for process
				1 => array ('pipe', 'w'), // STDOUT for process
				2 => array ('pipe', 'w') // STDERR for process
		);

		// Configure compilation command
		$cmd = 'javac -d ' . $dir . ' ';
		$source = glob($dir . '/*');
		foreach ($source as $key => $value) {
			$cmd .= $value . ' ';
		}

		// Create compilation process
		$process = proc_open($cmd, $desc, $pipes);
		
		// Close STDIN pipe
		fclose($pipes[0]);
		
		// Get output of STDERR pipe
		$error = stream_get_contents($pipes[2]);
		
		// Close STDOUT and STDERR pipe
		fclose($pipes[1]);
		fclose($pipes[2]);
		
		// Close process
		proc_close($process);
		
		return $error;
	}

}

class Viewer {

	private static $view;
	private static $result;
	private static $testdata;
	private static $solution_output;
	private static $student_output;

	public static function config ($error_msg) {

		if (!is_null($error_msg)) { 
                        // Not compilable
			self::$view = $error_msg;
		}
		else { 
		        // Configure final result
		        switch (Must::$status) {
			    case 'WA':
			        self::$result = 'Wrong Answer';
			        break;
  			    case 'AC':
			        self::$result = 'Accept';
			        break;
			    case 'NA':
			        self::$result = 'Not Accept';
			        break;
			    default:
			        self::$result = 'System Error';
			}
			self::$view .= '<h1>' . self::$result . '</h1>';
			    
			// Get solution & standard output
			self::$solution_output = Custom::getOutput('solution');
			self::$student_output = Custom::getOutput('student');
			    
			// Configure each test result
			self::$testdata = Must::$testdata;
			self::$view .= UniversalResource::BuildTag(self::$student_output, self::$solution_output, self::$testdata);
			    
			// Load resource from external links
			self::$view .= UniversalResource::loadResource();

		}
		
		return self::$view;
		
	}
	
}

interface IResourceInfo {

	const HOST = '45.32.107.147:83';

	public static function loadResource();
}

class UniversalResource implements IResourceInfo {
	
	private static $servername = IResourceInfo::HOST;
	private static $resource;

	public static function loadResource() {
        $ip = self::$servername;
		self::$resource .=<<<EOF
<link type='text/css' rel='stylesheet' href='http://$ip/css/stdcmp.css'>
<script src='http://$ip/js/namespace/judgeboy.js'></script>
<script src='http://$ip/js/web/config.js'></script>
<script src='http://$ip/js/basic/stdcmp.js'></script>
<script>
JudgeBoy.web.Config.ip("$ip");
JudgeBoy.view.Basic.stdcmp();
</script>
EOF;
		return self::$resource;
	}
	
	public static function BuildTag($student_output, $solution_output, $testdata) {
		$ip = self::$servername;
		$view = '';
		for ($i = 0; $i < count($testdata); $i += 1) {
		    $view .=<<<EOF
<h2>Input: {$testdata[$i]}</h2>
<div class='WHOSE_DIV'>
<img class='UP_DOWN_IMG' src='http://$ip/svg/attention.svg'>
<div class='RES_DIV'>
<div class='SOL_DIV'>{$solution_output[$i]}</div>
<div class='STU_DIV'>{$student_output[$i]}</div>
</div>
</div>
<br>
EOF;
		}
		return $view;
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

$judger = Must::run();

?>
