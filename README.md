# JudgeBoy
A PHP library offering examples for customizing judge script. 
  
## Usage
**1. Resource Website**   
_JudgeBoy/public/_ is root of 45.32.107.147:83 that is placed css, js, svg, and php files for configuring the response of each submission. 
Its configuration file is _JudgeBoy/config/000-judgeboy.conf_.    
  
**2. Judge Script Library**  
_JudgeBoy/src_ is placed judge scripts of lab assignments.  
Please change database and custom information:
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
