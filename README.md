# JudgeBoy
A PHP repository offering examples for customizing judge script. 

## Usage
**1. Resource Website**   
_JudgeBoy/public/_ is root of 45.32.107.147:83 that is placed css, js, and svg files for configuring the response of each submission. 
Its configuration file is _JudgeBoy/config/000-judgeboy.conf_.    
  
**2. Judge Script Examples**     
We hope you can modify _JudgeBoy/src/ClassicXX_ as your customizing one, so please change database and custom information:  
```
interface IConnectInfo {
	const HOST = '45.32.107.147';
	const UNAME = 'account';
	const PW = 'password';
	const DBNAME = 'TAFreeDB';
	public static function doConnect();
}

interface ICustomInfo {
	const TESTDATA = 2; // 1) No testdata 2) Static testdata
	const NORMALIZE = 3; // 1) Raw output 2) Trim output 3) Normalized output
	const CLASSIC = 1; // 1) Standard comparison 2) Branch comparison 3) Post-process comparison 
}
```  
  
**3. The Detailed Ideas**  
Yu-Tzu Wu (2017). _An Automatic Assessment System for Marking Programming Exercises with Random Output_ (Master's thesis).

## License
JudgeBoy is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
