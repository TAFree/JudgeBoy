<?php

ini_set('display_errors', '1');
ERROR_REPORTING(E_ALL);

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

interface IConnectInfo {

	const HOST = 'localhost';
	const UNAME = 'tafreedev';
	const PW = 'ewre3571';
	const DBNAME = 'TAFreeDev';

	public static function doConnect();
}


class Proto {

	private $stu_account;
	private $item;
	private $subitem;
	private $id;
	private $main;
	private $dir_name;
	private $status;
	private $solution_output = array();
	private $student_output = array();
	private $testdata = array();
	private $result = array();
	private $view;

	private $hookup;

	public function __construct () {
		
		// Arguments: student account, item, subitem
		$this->stu_account = $_SERVER['argv'][1];
		$this->item = $_SERVER['argv'][2];
		$this->subitem = $_SERVER['argv'][3];
		$this->id = $_SERVER['argv'][4];

		try {
			// Connect to MySQL database TAFreeDB
			$this->hookup = UniversalConnect::doConnect();						

			// Create directory to put source codes temporarily
			$this->createDir();
			
			// Fetch student and solution source from table [item]_[subitem]
			$this->fetchSource();
			
			// Fetch testdata
			$this->fetchTestdata();
			
			// Start judge 
			$this->startJudge();
			
			// Update judge status
			$this->updateStatus();

			// Remove directory
			$this->removeDir();

			$this->hookup = null;
			
			exit();
		}
		catch (PDOException $e) {
			echo 'Error: ' . $e->getMessage() . '<br>';
		}

	}
	

	public function createDir () {
		$this->dir_name = '../process';
		mkdir($this->dir_name);
		mkdir($this->dir_name . '/student');
		mkdir($this->dir_name . '/solution');
	}

	public function removeDir () {
		system('rm -rf ' . $this->dir_name, $retval);
		if ($retval !== 0 ) {
			echo 'Directory can not be removed...';
			exit();
		}
	}
	
	public function fetchSource () {
		$stmt = $this->hookup->prepare('SELECT main, classname, original_source, ' . $this->stu_account . ' FROM ' . $this->item . '_' . $this->subitem);
		$stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($row['main'] === 'V') {
			    $this->main = $row['classname'];
			}
			$student = fopen($this->dir_name . '/student/' . $row['classname'], 'w');
			fwrite($student, $row[$this->stu_account]);
			fclose($student);
			
			$solution = fopen($this->dir_name . '/solution/' . $row['classname'], 'w');
			fwrite($solution, $row['original_source']);
			fclose($solution);

		}
	}

	public function fetchTestdata () {
		$stmt = $this->hookup->prepare('SELECT content FROM ' . $this->item . '_' . $this->subitem . '_testdata');
		$stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			array_push($this->testdata, $row['content']);
		}
	}

	public function startJudge () {
	
		// Solution and student directory whose source is in
		$solution_dir = $this->dir_name . '/solution';
		$student_dir = $this->dir_name . '/student';

		// Compile source code from both solution and student
		$solution_CE = $this->compile($solution_dir);
		if (!empty($solution_CE)) {
			
			// Configure result that will response to client side
			$error_msg = '<h1>Solution has compiler error</h1>' . '<pre><code>' . $solution_CE . '</code></pre>';
			$this->configureView($error_msg);
			
			// Sava view
			$this->saveView();
		
			// System error
			$this->status = 'SE';
			return;
	
		}
		$student_CE = $this->compile($student_dir);
		if (!empty($student_CE)) {
			
			// Configure result that will response to client side
			$error_msg = '<h1>Your source code has compiler error</h1>' . '<pre><code>' . $student_CE . '</code></pre>';
			$this->configureView($error_msg);
			
			// Sava view
			$this->saveView();
		
			// Compiler error
			$this->status = 'CE';
			return;
	
		}
	
		
		// Execute source code from both solution and student	
		foreach ($this->testdata as $key => $value) {
			$solution_RE = $this->execute($solution_dir, 2, $value);
			if (!empty($solution_RE)) {
				
				// Configure result that will response to client side
				$error_msg = '<h1>Solution has runtime error</h1>' . '<pre><code>' . $solution_RE . '</code></pre>';
				$this->configureView($error_msg);
			
				// Sava view
				$this->saveView();
			
				// System error
				$this->status = 'SE';
				return;
		
			}
			$student_RE = $this->execute($student_dir, 2, $value);
			if (!empty($student_RE)) {

				// Configure result that will response to client side
				$error_msg = '<h1>Your source code has runtime error</h1>' . '<pre><code>' . $student_RE . '</code></pre>';
				$this->configureView($error_msg);

				// Sava view
				$this->saveView();
	
				// Runtime error
				$this->status = 'RE';
				return;
		
			}
		}
		
		// Compare output from both solution and student
		foreach ($this->testdata as $key => $value) {
			
			$solution_output = $this->execute($solution_dir, 1, $value);
			$student_output = $this->execute($student_dir, 1, $value);
			
			$retval = strcmp($solution_output, $student_output);
			
			array_push($this->result, $retval);
			array_push($this->solution_output, $solution_output);
			array_push($this->student_output, $student_output);
		}

		if (in_array(0, $this->result)) {	
			if (array_count_values($this->result)['0'] === count($this->result)) {
				// Accept
				$this->status = 'AC';
			}else{
				// Not Accept
				$this->status = 'NA';
			}
		}
		else {
			// Wrong Answer
			$this->status = 'WA';
		}

		// Configure result that will response to client side
		$error_msg = null;
		$this->configureView($error_msg);
		
		// Sava view
		$this->saveView();
		
		return;
		
	}

	public function compile ($dir) {
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
	
	public function execute ($dir, $pipe_id, $testdata) {
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

	public function updateStatus () {
		$stmt = $this->hookup->prepare('UPDATE ' . $this->item . ' SET ' . $this->stu_account . '=\'' . $this->status . '\' WHERE subitem=\'' . $this->subitem . '\'');
		$stmt->execute();
	}

	public function configureView ($error_msg) {
		if (!is_null($error_msg)) {
			 $this->view = $error_msg;
		}
		else {
			$result = '';
			if ($this->status === 'WA') {
				$result = 'Wrong Answer';
			}
			if ($this->status === 'AC') {
				$result = 'Accept';
			}
			if ($this->status === 'NA') {
				$result = 'Not Accept';
			}
			
			$this->view .= '<h1>' . $result . '</h1>';

			for ($i = 0; $i < count($this->testdata); $i += 1) {
				$this->view .=<<<EOF
<h2>Input: {$this->testdata[$i]}</h2>
<div class='WHOSE_DIV'>
<img class='UP_DOWN_IMG' src='http://45.32.107.147:83/svg/attention.svg'>
<div class='RES_DIV'>
<div class='SOL_DIV'>{$this->solution_output[$i]}</div>
<div class='STU_DIV'>{$this->student_output[$i]}</div>
</div>
</div>
<br>
EOF;

			}

			$this->view .=<<<EOF
<link type='text/css' rel='stylesheet' href='http://45.32.107.147:83/css/stdcmp.css'>
<script src='http://45.32.107.147:83/js/stdcmp.js'></script>
EOF;
		}
		return;
	}
	
	public function saveView () {
		$stmt = $this->hookup->prepare('UPDATE process SET view=:view WHERE id=\'' . $this->id . '\'');
		$stmt->bindParam(':view', $this->view);
		$stmt->execute();

	}
	
}

$judger = new Proto();

?>
