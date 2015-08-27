# Aha #

----------

Aha is a high performance pure asynchronous network framework base on swoole,written in php.

# Road map #

----------

1. v1.0.0
	- Mvc asynchronous
	- Network asynchronous server(http、tcp、udp)
	- Network asynchronous client(http、tcp、udp、multi、pool)
	- asynchronous storage(mysql、transaction、redis、pool);
	- asynchronous logger
2. v1.+.+
	- more third party clients such as memcache/beanstalkd support;
	- php daemon multi concurrent process support;
	- coroutine of multi task schedule for deamon support;
3. **I will rewrite Aha framework in C because of these reasons below:**
	- **Lower CPU occupancy;**
	- **Faster memory recovery cycles;**
	- **Just install a php extension named Aha** 

# Features #

----------

1. HTTP/TCP/UDP server support.Tt's easy to create a server application base on Aha framework;

2. HTTP/TCP/UDP client pool.In this case,you can make your third part request more efficient because of the reasons below:

	- reduced three times handshark when connect;
	- reduced four times handshark when close;
	- break through the limit of local port( if close immediately,the local port will wait 2MSL for reuse);

3. multi clients concurrent support;

4. MVC which contains loader,router,filter,dispatcher,action and config can use not only in http server,but also in tcp,udp server;
	- loader:you can use it anywhere for classes autoload;
	- router:recurive router depend on your router element and delemiter;
	- filter:provided preRouter,postRouter,preDispatch,postDispatch phases for your filer requires,each pahse can register more then one hook;
	- dispatcher:it contains all elements which you needed when appication development anywhere;
	- action: your application actions must extend from this abstract class;
	- config: it will load all config item on worker start;

5. asynchronous log writter support;

6. asynchronous redis client:
	- redis protocal support ;
	- redis connection pool manager.
	- It can also help you to put your redis request to queue when concurrent higher then your system processing capability;

7. asynchronous mysql query:
	- asynchronous sql query;
	- Asynchronous transaction.More important,the next transaction can build sql depend on the prev transaction result by anonymous function; 
	- Asynchronous mysql connection manager;
	- Asynchronous sql queue manager and trigger when concurrent higher then your databases processing capability;;

# Introduction #

----------

## server ##
### http server example ###

	namespace Application\Server;

	define('AHA_SRC_PATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'src');
	require_once AHA_SRC_PATH . '/Aha/Bootstrap.php';
	\Aha\Bootstrap::initLoader();

	use \Aha\Server\Http;

	class HttpServer extends Http {
	
	//Aha实例 
	private $_objAha = null;

	public function __construct() {
		$server = new \swoole_http_server('0.0.0.0', 9601);
		
		$this->setVarDirectory(dirname(__DIR__) .'/Var/');
		
		$arrSetting = array('log_file' => dirname(__DIR__) .'/Logs/Aha.log');
		parent::__construct($server, 'HttpServer', $arrSetting);
		$server->start();
	}
	
	/**
	 * @brief 初始化MVC
	 * @param \swoole_server $server
	 * @param int $workerId
	 */
	public function onWorkerStart(\swoole_server $server, $workerId) {
		parent::onWorkerStart($server, $workerId);
		define('APP_NAME','Application');
		define('APPLICATION_PATH', dirname(dirname(__DIR__)));
		$this->_objAha = \Aha\Bootstrap::getInstance(APP_NAME, 'product');
		$this->_objAha->setServer($server);
		$this->_objAha->getLoader()->registerNamespace(APP_NAME, APPLICATION_PATH);
		$this->_objAha->run();
		/**
		$filter = new \Application\Filters\Track();
		$this->_objAha->getFilter()
				->registerPreRouter(array($filter, 'preRouterOne'))
				->registerPreRouter(array($filter, 'preRouterTwo'))
				->registerPostRouter(array($filter, 'postRouterOne'))
				->registerPostRouter(array($filter, 'postRouterTwo'))
				->registerPreDispatch(array($filter, 'preDispatchOne'))
				->registerPreDispatch(array($filter, 'preDispatchTwo'))
				->registerPostDispatch(array($filter, 'postDispatchOne'))
				->registerPostDispatch(array($filter, 'postDispatchTwo'));
		 */
	}
	
	/**
	 * @brief 请求初始化
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 */
	public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
		parent::onRequest($request, $response);
		try {
			$uri	= isset($request->server['request_uri']) ? $request->server['request_uri'] : '';
			$router = new \Aha\Mvc\Router($this->_objAha, $uri);
			$dispatcher = new \Aha\Mvc\Dispatcher($this->_objAha);
			$dispatcher->setRequest($request)->setResponse($response);
			$dispatcher->dispatch($router);
		} catch  (\Exception $ex) {
			$message = '[onRequest_callBack_excaption] [code]' . $ex->getCode() . ' [message]' .
				$ex->getMessage() . '[file]' . $ex->getFile() . '[line]' . $ex->getLine() . PHP_EOL;
			switch ( $ex->getCode() ) {
				case AHA_ROUTER_EXCEPTION : 
					$response->status(404);
					break;
				default :
					$response->status(500);
					break;
			}
			echo $message;
			$response->end($message);
		}
	}
	
	}

	$httpServer = new \Application\Server\HttpServer();


### tcp server example ###
https://github.com/eyehere/aha/blob/master/example/Application/Server/TcpServer.php

### udp server example ###
https://github.com/eyehere/aha/blob/master/example/Application/Server/UdpServer.php

### operation shell scripts ###
https://github.com/eyehere/aha/tree/master/example/Application/Var

- loadhttp.sh {start|stop|restart|reload}
- loadtcp.sh {start|stop|restart|reload}
- loadudp.sh {start|stop|restart|reload}

## MVC example ##
https://github.com/eyehere/aha/tree/master/example

## mysql query ##
when use mysql client in your aplication,you can written your query in models.	

	namespace Application\Actions\Demo\Storage;
	use \Aha\Mvc\Action;

	class Db extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$conn->query("select * from friends limit 10", array($this, 'QueryDbCallback'));
	}
	
	public function QueryDbCallback($result, $dbObj, $dbSock) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = array();
		if ( false === $result ) {
			$arrData = array('query_error');
		} else {
			$arrData = $result->fetch_all(MYSQLI_ASSOC);
		}
		$response->end(json_encode($arrData));
	}
	
	}

## mysql transaction chains ##
when use mysql client in your aplication,you need written your query in db layer.	

	namespace Application\Actions\Demo\Storage;
	use \Aha\Mvc\Action;

	class Trans extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				})
				//->queue('friendsPlus','insert into friends set user_id=100000,friend_id=1000000')
				->setCallback(array($this, 'queryDbCallback'))
				->execute();
	}
	
	public function queryDbCallback($result, $dbObj, $dbSock) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = $result;
		if ( false === $result ) {
			$arrData = array('query_error');
		}
		$response->end(json_encode($arrData));
	}
	
	}

## redis client ##
when use redis client in your aplication,you can written your cmd in models.

	namespace Application\Actions\Demo\Storage;
	use \Aha\Mvc\Action;

	class Rdb extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$instanceName = 'test';
		$conf = $config->get('redis', $instanceName);
		$conn = \Aha\Storage\Memory\Pool::getConnection($instanceName, $conf);

		//$conn->zadd('sortedSet', 10, 'aaaa', 20, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->zrange('sortedSet',0, -1, 'WITHSCORES',array($this, 'QueryCallback'));
		
		//$conn->sadd('settest', 10, 'aaaa', 20, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->smembers('settest', array($this, 'QueryCallback'));
		
		//$conn->rpush('listtest', 12, 'aaaa', 27, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->lrange('listtest', 0, -1, array($this, 'QueryCallback'));
		
		//$conn->hmset('ms',  array('a'=>'12345','b'=>'wqerty'), array($this, 'QueryCallback'));
		//$conn->hmget('ms',  array('a','b'), array($this, 'QueryCallback'));
		//$conn->hgetall('ms', array($this, 'QueryCallback'));
		
		//$conn->set('setA',  str_repeat('A', 20), array($this, 'QueryCallback'));
		//$conn->mset('setB',  str_repeat('B', 20), 'setC', str_repeat('C', 20), array($this, 'QueryCallback'));
		//$conn->mget('setB', 'setC', array($this, 'QueryCallback'));
		$conn->get('setA', array($this, 'QueryCallback'));
	}
	
	public function QueryCallback($result, $error) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = compact('result','error');
		$response->end(json_encode($arrData));
	}
	
	}

## http client ##
    namespace Application\Actions\Demo\Client;
	use \Aha\Mvc\Action;
	use \Aha\Client\Multi;

	class Http extends Action {
	
	public function excute() {
		
	//		$http1 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.qq.com/');
	//		$http1->setRequestId('trunked');
	//		$http2 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
	//		$http2->setRequestId('length');
	//		$mutli = new Multi();
	//		$mutli->register($http1);
	//		$mutli->register($http2);
	//		$mutli->loop(array($this,'output'));
		
		$http = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
		$http->setRequestId('contentLength');
		$http->setCallback( array($this, 'output') );
		$http->loop();
	}
	
	public function output($data) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		if ( isset($data['data']['body']) ) {
			$response->end($data['data']['body']);
		} else {
			$response->end(json_encode($data));
		}
		
	//		if ( isset($data['data']['length']['data']['body']) ) {
	//			$response->end($data['data']['length']['data']['body']);
	//		} else {
	//			$response->end(json_encode($data['data']['length']));
	//		}
		
	//		if ( isset($data['data']['trunked']['data']['body']) ) {
	//			$response->end($data['data']['trunked']['data']['body']);
	//		} else {
	//			$response->end(json_encode($data['data']['trunked']));
	//		}
	}
	
	public function __destruct() {
		//var_dump(__METHOD__);
	}
	
	}


## tcp client ##
    namespace Application\Actions\Demo\Client;
	use \Aha\Mvc\Action;

	class Tcp extends Action {
	
	public function excute() {
		$tcpCli = \Aha\Client\Pool::getTcpClient('10.10.8.172','9602');
		$tcpCli->setRequestId('TcpRequest');
		$tcpCli->setCallback( array($this, 'output') );
		$arrDara = array(
			'cmd' => 'demo-server-tcp',
			'body'=> 'from http request'
		);
		$tcpCli->setPackage(json_encode($arrDara));
		$tcpCli->loop();
	}
	
	public function output($data) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$response->end(json_encode($data));
	}
	
	} 

## udp client ##
	namespace Application\Actions\Demo\Client;
	use \Aha\Mvc\Action;

	class Udp extends Action {
	
	public function excute() {
		$tcpCli = \Aha\Client\Pool::getUdpClient('10.10.8.172','9603');
		$tcpCli->setRequestId('UdpRequest');
		$tcpCli->setCallback( array($this, 'output') );
		$arrDara = array(
			'cmd' => 'demo-server-udp',
			'body'=> 'from http request'
		);
		$tcpCli->setPackage(json_encode($arrDara));
		$tcpCli->loop();
	}
	
	public function output($data) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$response->end(json_encode($data));
	}
	
	} 

## performance ##
![Aha框架的性能测试数据(仅供参考)](http://i.imgur.com/YaBHyHi.png)