# JudgeBoy
A PHP library offering examples for customizing judge script. 
  
## Usage
**1. Resource Website**   
_JudgeBoy/public/_ is root of 45.32.107.147:83 that is placed css, js, and svg files for configuring the response of each submission. 
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
   
**3. Container Controller**  
Run _JudgeBoy/service/cleaner.sh_ in the backend to restart stuck containers (for example, an unstoppable code snippet inside student's submission) all the time.
```
sudo git clone http://github.com/TAFree/JudgeBoy
cd ./JudgeBoy/service
sudo mv ContainerController.php.example ContainerController.php
sudo vi ContainerController.php
interface IConnectInfo {

	const HOST = '45.32.107.147';
	const UNAME = 'account';
	const PW = 'password';
	const DBNAME = 'TAFreeDB';

	public static function doConnect();
}
sudo ./cleaner.sh start
```
  
## License
JudgeBoy is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
