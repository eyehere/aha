
# Introduction to the use of coroutine mode #

----------

## server ##
It's usage is no difference between the coroutine mode and the asynchronous mode.

## MVC example ##
It's usage is no difference between the coroutine mode and the asynchronous mode.

## coroutine model example ##

https://github.com/eyehere/aha/blob/master/example/Application/Models/Coroutine/Fetch.php

    namespace Application\Models\Coroutine;

	class Fetch {
	
	public function getMeituPage() {
		$http = \Aha\Client\Pool::getHttpClient('GET', 'http://www.meitu.com/');
		$ret = yield ( $http->setRequestId('contentLength') );
		yield($ret);
	}
	
	public function getFromTcp() {
		$tcpCli = \Aha\Client\Pool::getTcpClient('10.10.8.172','9602');
		$tcpCli->setRequestId('TcpRequest');
		$arrDara = array(
			'cmd' => 'demo-server-tcp',
			'body'=> 'from http request'
		);
		$ret = yield ( $tcpCli->setPackage(json_encode($arrDara)) );
		yield($ret);
	}
	
	public function getFromUdp() {
		$tcpCli = \Aha\Client\Pool::getUdpClient('10.10.8.172','9603');
		$tcpCli->setRequestId('UdpRequest');
		$arrDara = array(
			'cmd' => 'demo-server-udp',
			'body'=> 'from http request'
		);
		$ret = yield ( $tcpCli->setPackage(json_encode($arrDara)) );
		yield($ret);
	}
	
	public function getFromMulti() {
		$http1 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.qq.com/');
		$http1->setRequestId('trunked');
		$http2 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
		$http2->setRequestId('length');
		$mutli = new \Aha\Client\Multi();
		$mutli->register($http1);
		$ret = yield ( $mutli->register($http2) );
		yield($ret);
	}
	
	public function getFromDb($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$ret = yield ( $conn->createCoroutine()
			 ->query("select * from friends limit 10") );
		yield ($ret);
	}
	
	public function dbTrans($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$trans = $conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				})
				->queue('friendsPlus','insert into friends set user_id=100000,friend_id=1000000');
		$ret = yield ( $trans );
		yield ($ret);
	}
	
	public function redisDemo($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$instanceName = 'test';
		$conf = $config->get('redis', $instanceName);
		$conn = \Aha\Storage\Memory\Pool::getConnection($instanceName, $conf);

		$res1 = yield ( $conn->createCoroutine()->hmset('ms',  array('a'=>'12345','b'=>'wqerty')) );
		$res2 = yield ( $conn->createCoroutine()->hmget('ms',  array('a','b')) );
		$res3 = yield ( $conn->createCoroutine()->hgetall('ms') );
		
		yield ( compact('res1','res2','res3') );
	}
	
	}

## action db example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Db extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->getFromDb($this->_objDispatcher)) ;
		$result = isset($data['result']) ? $data['result'] : false;
		
		$arrData = array();
		if ( false === $result ) {
			$arrData = array('query_error');
		} else {
			$arrData = $result->fetch_all(MYSQLI_ASSOC);
		}
		$response->end(json_encode($arrData));
	}
	
	}

### action db transaction chanis example ###
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Trans extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->dbTrans($this->_objDispatcher)) ;

		$response->end(json_encode($data));
	}
	
	}

## action multi redis commands example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Rdb extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->redisDemo($this->_objDispatcher)) ;
		
		$response->end(json_encode($data));
	}
	
	}

## http client example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Http extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->getMeituPage()) ;
		
		if ( isset($data['data']['body']) ) {
			$response->end($data['data']['body']);
		} else {
			$response->end(json_encode($data));
		}
	}
	
	public function __destruct() {
		var_dump(__METHOD__);
	}
	
	} 

## tcp client example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Tcp extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->getFromTcp());

		$response->end(json_encode($data));
	}
	
	} 

## udp client example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Udp extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->getFromUdp());

		$response->end(json_encode($data));
	}
	
	} 

## multi client example ##
	namespace Application\Actions\Demo\Coroutine;
	use \Aha\Mvc\Action;

	class Multi extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = yield ($objFetch->getFromMulti()) ;
		
	//		if ( isset($data['data']['length']['data']['body']) ) {
	//			$response->end($data['data']['length']['data']['body']);
	//		} else {
	//			$response->end(json_encode($data['data']['length']));
	//		}
		
		if ( isset($data['data']['trunked']['data']['body']) ) {
			$response->end($data['data']['trunked']['data']['body']);
		} else {
			$response->end(json_encode($data['data']['trunked']));
		}
	}
	
	} 



