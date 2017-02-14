# JudgeBoy
A PHP library offering examples for customizing judge script. 
  
# Usage 1.
* Description  
 >JudgeBoy/public/ is root of 45.32.107.147:83 that is placed css, js, and svg files for configuring the response of each submission.  
* Configuraion  
 >JudgeBoy/config/000-judgeboy.conf  
  
# Usage 2.
* Description  
 >JudgeBoy/src is placed judge scripts of lab assignments.  
* Configuration   
 >Please change database and custom information in src/JudgeScript.php.example
```
interface IConnectInfo {
	const HOST = 'ip';
	const UNAME = 'account';
	const PW = 'password';
	const DBNAME = 'TAFreeDB';
	public static function doConnect();
}

interface ICustomInfo {
	const TESTDATA = 1; // 1) No testdata 2) Static testdata 
}
```
