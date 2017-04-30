<?php
/**
 * A PHP customizing judge script example with / without testdata for TAFree
 *
 * @authur Yu Tzu Wu <abby8050@gmail.com>
 */

ini_set('display_errors', '1');
ERROR_REPORTING(E_ALL);

interface ICustomInfo {
	const TESTDATA = 1; // 1) No testdata 2) Static testdata
	const NORMALIZE = 4; // 1) Raw output 2) Highlight output 3) Normalized output 4) Trim output
    const CLASSIC = 1; // 1) Standard comparison 2) Branch comparison 3) Post-process comparison 
}

class StdCmp implements Logic {
    
    public function parseMatches($matches, $found, &$response = null){
        if($found){
            return 0;
        }
        else {
            return 1;
        }
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
			$solution_output = $this->execute($solution_dir, $value);

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
            $solution_output[1] = $this->normalize($solution_output[1], ICustomInfo::NORMALIZE);
		
			// Verify output of student source code
			$pattern = $solution_output[1];
			$matcher = new Matcher($pattern, $student_output[1], new StdCmp(), $response);
			$retval = $matcher->returnVal();

			array_push($result, $retval);
			array_push(self::$student_output, $student_output[1]);
			array_push(self::$solution_output, $solution_output[1]);
		
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
			// Highlight output
			$whitespace = '/ /';	
			$tab = '/\t/';
			$nextline = '/[\n\r\f]/';
			$output = preg_replace($whitespace, '&#9633;', $output);
			$output = preg_replace($tab, '&#9633;&#9633;$#9633;&#9633;', $output);
			$output = preg_replace($nextline, '<br>', $output);
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
		if ($mode === 4){
			// Normalized output
			$leading = '/\A\s*/';
			$trailing = '/[\s]*\Z/';
			$whitespace = '/ /';	
			$tab = '/\t/';
			$nextline = '/[\n\r\f]/';
			$output = preg_replace($leading, '', $output);
			$output = preg_replace($trailing, '', $output);
			$output = preg_replace($whitespace, '&#9633;', $output);
			$output = preg_replace($tab, '&#9633;&#9633;$#9633;&#9633;', $output);
			$output = preg_replace($nextline, '<br>', $output);
		}
		return $output;
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
        switch(ICustomInfo::CLASSIC) {
            case 1:
                    $found = strcmp($this->pattern, $student_output);
                    if ($found === 0) {
                        $found = true;
                        $matches = $student_output;
                    }
                    else {
                        $found = false;
                        $matches = null;
                    }
                    return $logic->parseMatches($matches, $found, $response);
            case 2:
                    $found = true;
                    $matches = null;
                    return $logic->parseMatches($matches, $found, $response);
            case 3:
                    $found = preg_match($this->pattern, $student_output, $matches);
                    if ($found === 1) {
                        $found = true;
                    }
                    else {
                        $found = false;
                    }
                    return $logic->parseMatches($matches, $found, $response);
        }

    }
    public function returnVal(){
        return $this->retval;
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
			self::removeDir();

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
			self::$view .= UniversalResource::buildTag(self::$student_output, self::$solution_output, self::$testdata);
			    
			// Load resource from external links
			self::$view .= UniversalResource::loadResource();

		}
		
		return self::$view;
		
	}
	
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
JudgeBoy.view.Basic.candi_match();
</script>
EOF;
		return self::$resource;
	}
	
	public static function buildTag($student_output, $solution_output, $testdata) {
		$ip = self::$servername;
		$view = '';
		switch(ICustomInfo::CLASSIC) {
		
            case 1:
                for ($i = 0; $i < count($testdata); $i += 1) {
                    $view .=<<<EOF
<h2>Input: {$testdata[$i]}</h2>
<div class='WHOSE_DIV'>
<img class='UP_DOWN_IMG' src='http://$ip/svg/attention.svg'>
<div class='RES_DIV'>
<div class='SOL_DIV'><pre>{$solution_output[$i]}</pre></div>
<div class='STU_DIV'><pre>{$student_output[$i]}</pre></div>
</div>
</div>
<br>
EOF;
                }
            break;
            
            case 2:
                for ($i = 0; $i < count($testdata); $i += 1) {
                    $view .= '<h2>Input: ' . $testdata[$i] . '</h2>';
                    if (is_array($solution_output[$i])) {
                        $view .= '<h2>Expect to match one of the following: </h2>';
                        foreach($solution_output[$i] as $key => $value) {
                            $view .=<<<EOF
<div class='WHOSE_DIV'>
<input id='$i' class='CANDI_INPUT' type='radio' name='candi' value='$value'>
<div class='CANDI_DIV'><pre>$value</pre></div>
</div>
EOF;
                        }
                    }
                    else {
                        $view .=<<<EOF
<h2>Match: </h2>
<div class='WHOSE_DIV'>
<input id='$i' class='CANDI_INPUT' type='checkbox' name='candi' value='{$solution_output[$i]}'>
<div class='CANDI_DIV'><pre>{$solution_output[$i]}</pre></div>
</div>
EOF;
                    }
                    $view .= '<h2>Your output: </h2>';
                    $view .=<<<EOF
<div class='WHOSE_DIV'>
<div class='RES_DIV'>
<div id='$i' class='SOL_DIV'><pre></pre></div>
<div class='STU_DIV'><pre>{$student_output[$i]}</pre></div>
</div>
</div>
<br>
EOF;
                }
            break;
            
            case 3:
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
            break;
        }
        
		return $view;
	} 

}

interface Logic {
    public function parseMatches($matches, $found, &$response = null); 
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

interface IResourceInfo {

	const HOST = '45.32.107.147:83';

	public static function loadResource();
}

interface IConnectInfo {

	const HOST = '45.32.107.147';
	const UNAME = 'ghassho';
	const PW = 'ghassho_change';
	const DBNAME = 'TAFreeDev';

	public static function doConnect();
}

$judger = Must::run();

?>
