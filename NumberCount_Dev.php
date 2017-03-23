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
	const UNAME = 'ghassho';
	const PW = 'ghassho_change';
	const DBNAME = 'TAFreeDev';

	public static function doConnect();
}

interface ICustomInfo {
	const TESTDATA = 2; // 1) No testdata 2) Static testdata
	const NORMALIZE = 3; // 1) Raw output 2) Trim output 3) Normalized output
}

class NumberCount implements Logic {
    
    private $student_number;
    private $student_times = array();
    private $solution_times;
    private $response = '';

    public function parseMatches($matches, $found, &$response = null){
       
	if ($found === 1) { // Matched
	    // Calculated items
	    $this->solution_times = array_fill(0, 10, 0);
            $this->convertType($matches[1], 'int');
	    $this->student_number = $matches[1];
	    for($i = 2; $i <= $this->student_number + 1; $i++){
		$this->convertType($matches[$i], 'int');
		$this->solution_times[$matches[$i]]++;
	    }
	    
	    // Get items
	    for($i = $this->student_number + 2; $i < count($matches); $i++){
	    	$this->convertType($matches[$i], 'int');
		$this->student_times[$i - $this->student_number - 2] = $matches[$i];
	    }

	    if ($this->solution_times == $this->student_times){	
		$this->buildTag('Good job!', 'Msg');
		$response = $this->response;
		return 0;    
	    }
	    else {
		$this->response .= '<tr><th class=\'ITEM_TH\'></th><th class=\'ITEM_TD\'>Output</th><th class=\'ITEM_TD\'>Calculation</th></tr>';
		for ($i = 0; $i <= 9; $i++){
			$this->buildTag(array($this->student_times[$i], $this->solution_times[$i]), 'Cmp', $i);
		}
		$response = $this->response;
		return 1;
	    }

        }
        else { // No matched
            	$this->buildTag('Match Nothing: your output might have format error.', 'Msg');
		$response = $this->response;
		return 2;
        }
    }

    private function convertType(&$variable, $type) {
	switch($type) {
		case 'int':
			$variable = intval($variable);
		break;
		case 'double':
			$variable = doubleval($variable);
		break;
	}
    }

    private function buildTag($inner, $mode, $item = null) {
	switch($mode) {
		case 'Msg':
			$this->response .= '<tr><td class=\'MESSAGE_TD\'><p>' . $inner . '</p></td></tr>';
			break;
		case 'Cmp':
			if ($inner[0] != $inner[1]) {
				$this->response .=<<<EOF
<tr><th class='ITEM_TH'>$item</th><td class='ITEM_TD'><p>{$inner[0]}</p></td><td class='ITEM_TD'><p class='WRONG_P'>{$inner[1]}</p></td></tr>
EOF;
			}
			else{
				$this->response .=<<<EOF
<tr><th class='ITEM_TH'>$item</th><td class='ITEM_TD'><p>{$inner[0]}</p></td><td class='ITEM_TD'><p>{$inner[1]}</p></td></tr>
EOF;
			}
			break;
   	 }
    }		

    private function range($calculated, $student, $range) {
	$min = $calculated - $range;
	$max = $calculated + $range;
	if ($student >= $min && $student <= $max) {
		return true;
	}
	return false;
    }
    
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
			
			// Normalize output of student source code
			$student_output[1] = $this->normalize($student_output[1], ICustomInfo::NORMALIZE);
		
			// Verify output of student source code
			$subpattern = '';
			for ($i = 1; $i <= $value; $i++){
				$subpattern .= '(\d)[ ]?';
			}
			$pattern = '/Enter the number of single-digit random numbers to be generated: (\d) random numbers are:<br>' . $subpattern . '<br>0 appears (\d) times\.<br>1 appears (\d) times\.<br>2 appears (\d) times\.<br>3 appears (\d) times\.<br>4 appears (\d) times\.<br>5 appears (\d) times\.<br>6 appears (\d) times\.<br>7 appears (\d) times\.<br>8 appears (\d) times\.<br>9 appears (\d) times\./';
			$matcher = new Matcher($pattern, $student_output[1], new NumberCount(), $response);
			$retval = $matcher->returnVal();

			array_push($result, $retval);
			array_push(self::$student_output, $student_output[1]);
			array_push(self::$solution_output, $response);
		
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
	
    private function normalize ($input, $mode) {
		$output = $input;
		if ($mode === 1) {
			// Raw output
		}
		if ($mode === 2){
			// Trim output
			$leading = '/\A\s*/';
			$trailing = '/[\s]*\Z/';
			$output = preg_replace($leading, '', $output);
			$output = preg_replace($trailing, '', $output);
		}
		if ($mode === 3){
			// Normalized output
			$leading = '/\A\s*/';
			$trailing = '/[\s]*\Z/';
			$linebreak = '/[\s]*[\n\r\f][ \t]*/';
			$whitespace = '/[ \t]+([^\n])/';	
			$output = preg_replace($leading, '', $output);
			$output = preg_replace($trailing, '', $output);
			$output = preg_replace($linebreak, '<br>', $output);
			$output = preg_replace($whitespace, ' $1', $output);
		}
		return $output;
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

class Matcher {

    private $pattern;
    private $retval;

    public function __construct(String $pattern, String $student_output, Logic $logic, &$response){
        $this->setPattern($pattern);
        $this->retval = $this->verify($student_output, $logic, $response);
    }
    private function setPattern(String $pattern){
        $this->pattern = $pattern;
    }
    private function verify($student_output, $logic, &$response){
        $found = preg_match($this->pattern, $student_output, $matches);
        return $logic->parseMatches($matches, $found, $response);
    }
    public function returnVal(){
	return $this->retval;
    }
}

interface Logic {
    public function parseMatches($matches, $found, &$response = null); 
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
<table>{$solution_output[$i]}</table>
<div class='WHOSE_DIV'>
<div class='RES_DIV'>
<div class='STU_DIV'><pre>{$student_output[$i]}</pre></div>
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
