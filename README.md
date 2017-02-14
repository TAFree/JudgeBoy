# JudgeBoy
A PHP library offering examples for customizing judge script. 
  
## Usage
**1. Resource Website**   
JudgeBoy/public/ is root of 45.32.107.147:83 that is placed css, js, and svg files for configuring the response of each submission. 
Its configuration file is JudgeBoy/config/000-judgeboy.conf.    
  
**2. Judge Script Library**  
JudgeBoy/src is placed judge scripts of lab assignments.  
Please change database and custom information in src/JudgeScript.php.example:
```
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
```
## License
JudgeBoy is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
