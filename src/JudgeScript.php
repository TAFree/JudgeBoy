<?php
/**
 * A PHP customizing judge script example with / without testdata for TAFree
 *
 * @authur Yu Tzu Wu <abby8050@gmail.com>
 */

ini_set('display_errors', '1');
ERROR_REPORTING(E_ALL);

interface IConnectInfo {

	const HOST = 'localhost';
	const UNAME = 'tafreedev';
	const PW = 'ewre3571';
	const DBNAME = 'TAFreeDev';

	public static function doConnect();
}

interface ICustomInfo {
	const TESTDATA = 2; // 1) No testdata 2) Static testdata 
}

class Custom {

	private $stu_account = Must::stu_account;
	private $item = Must::item;
	private $subitem = Must::subitem;
	private $id = Must::id;
	private $main = Must::main;
	private $dir_name = Must::dir_name;
    private $testdata = Must::getTestdata();
	private static $solution_output = array();
	private static $student_output = array();

	public function __construct () {
		
		// Solution and student directory whose source is in
		$solution_dir = $this->dir_name . '/solution';
		$student_dir = $this->dir_name . '/student';
		
		// Execute source code from student	
		foreach ($this->testdata as $key => $value) {
			$student_RE = $this->execute($student_dir, 2, $value);
			if (!empty($student_RE)) {

				// Configure result that will response to client side
				$error_msg = '<h1>Your source code has runtime error</h1>' . '<pre><code>' . $student_RE . '</code></pre>';
				Must::view = Viewer::config($error_msg);
	
				// Runtime error
				Must::status = 'RE';
				
				return;
		
			}
		}
		
		// Compare output from both solution and student
		$result = array();
		foreach ($this->testdata as $key => $value) {
			$solution_output = $this->execute($solution_dir, 1, $value);
			$student_output = $this->execute($student_dir, 1, $value);
			
			$retval = strcmp($solution_output, $student_output);
			
			array_push($result, $retval);
			array_push(self::$solution_output, $solution_output);
			array_push(self::$student_output, $student_output);
		}

		if (in_array(0, $result)) {	
			if (array_count_values($result)['0'] === count($result)) {
				// Accept
				Must::status = 'AC';
			}else{
				// Not Accept
				Must::status = 'NA';
			}
		}
		else {
			// Wrong Answer
			Must::status = 'WA';
		}

		// Configure result that will response to client side
		$error_msg = null;
		Must::view = Viewer::config($error_msg);
		
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
	
	public function getStatus($who) {
        return $this->status;
	}
	
	private function execute ($dir, $pipe_id, $testdata) {
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
		
		// Wait seconds
		//usleep(1000000);
		
		// Kill execution process
		//posix_kill($pid, SIGTERM);
		
		// Get output of STDOUT or STDERR pipe
		$output = stream_get_contents($pipes[$pipe_id]);
	
		// Close STDOUT and STDERR pipe
		fclose($pipes[1]);
		fclose($pipes[2]);
	
		return $output;
	}
	
}

class Must {

	private static $stu_account;
	private static $item;
	private static $subitem;
	private static $id;
	private static $testdata = array();
	private static $main;
	private static $status;
	private static $dir_name = './process';
	private static $view;

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
			self::$createDir();
			
			// Fetch student and solution source from table [item]_[subitem]
			self::$fetchSource();
			
			// Fetch testdata
			self::$fetchTestdata(ICustomInfo::TESTDATA);
				
			// Run and compare if both student and standard sources are compilable
			if (self::$areBothCompile()) {
				$runner = new Custom();
				self::$status = $runner->getStatus(); 
			}
			
            // Sava view
			self::$saveView();
			
			// Update judge status
			self::$updateStatus();

			// Remove directory
			self::$removeDir();

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
	
	public static function getTestdata () {
		return self::$testdata;
	}

	private static function createDir () {
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
		$stmt = self::$hookup->prepare('UPDATE ' . self::$item . ' SET ' . self::$stu_account . '=\'' . self::$status . '\' WHERE subitem=\'' . self::$subitem . '\'');
		$stmt->execute();
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

	private static function areBothCompilable () {
		// Solution and student directory whose source is in
		$solution_dir = self::$>dir_name . '/solution';
		$student_dir = self::$dir_name . '/student';

		// Compile source code from both solution and student
		$solution_CE = self::$compile($solution_dir);
		if (!empty($solution_CE)) {
			
            // System error
			self::$status = 'SE';
			
			// Configure result that will response to client side
			$error_msg = '<h1>Solution has compiler error</h1>' . '<pre><code>' . $solution_CE . '</code></pre>';
			self::$view = Viewer::config($error_msg);

			return false;
	
		}
		$student_CE = self::$compile($student_dir);
		if (!empty($student_CE)) {
			
            // Compiler error
			self::$status = 'CE';
			
			// Configure result that will response to client side
			$error_msg = '<h1>Your source code has compiler error</h1>' . '<pre><code>' . $student_CE . '</code></pre>';
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

	private $view;
	private $result;
	private $testdata;
	private $solution_output;
	private $student_output;

	public function config ($error_msg) {

		if (!is_null($error_msg)) { 
            // Not compilable
			 $this->view = $error_msg;
		}
		else { 
            
            // Configure final result
            switch (Must::$status) {
                case 'WA':
                    $this->result = 'Wrong Answer';
                    break;
                case 'AC':
                    $this->result = 'Accept';
                    break;
                case 'NA':
                    $this->result = 'Not Accept';
                    break;
                default:
                    $this->result = 'System Error';
            }
			$this->view .= '<h1>' . $this->result . '</h1>';
            
            // Get solution & standard output
            $this->solution_output = Custom::getOutput('solution');
            $this->student_output = Custom::getOutput('student');
            
            // Configure each test result
            $this->testdata = Must::getTestdata();
            $this->view .= UniversalResource::BuildTag($this->student_output, $this->solution_output, $this->testdata);
            
            // Load resource from external links
			$this->view .= UniversalResource::loadResource();

		}
		
		return $this->view;
		
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
        for ($i = 0; $i < count($testdata); $i += 1) {
            $view =<<<EOF
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
		self::$conn = new \PDO('mysql::host=' . self::$servername . ';dbname=' . self::$dbname, self::$username, self::$password);
		self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return self::$conn;	
	}

}

$judger = Must::run();

?>
