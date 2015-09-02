
# Introduction to the use of coroutine mode in daemon process #

----------

## coroutine model example(master) ##

	namespace Daemon;

	class Async {
	
	/**
	 * @brief workers
	 * @var type 
	 */
	protected $_workers = array();
	
	/**
	 * @brief Aha实例
	 * @var type 
	 */
	protected $_objAha = null;

	/**
	 * @brief 异步后台进程初始化
	 */
	public function __construct() {
		define('APP_NAME','Daemon');
		define('APPLICATION_PATH', dirname(__DIR__));
		define('AHA_SRC_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'src');
		
		require_once AHA_SRC_PATH . '/Aha/Daemon.php';
		
		\Aha\Daemon::initLoader();
		
		$this->_objAha = \Aha\Daemon::getInstance(APP_NAME, 'product');
		$this->_objAha->getLoader()->registerNamespace(APP_NAME, APPLICATION_PATH);
		$this->_objAha->run();
	}
	
	/**
	 * @brief 启动子进程
	 */
	public function start() {
		$workerNum = $this->_objAha->getConfig()->get('aha','worker_num');
		for ( $i=0;$i<$workerNum;$i++ ) {
			$worker = new \Daemon\Asyncworker($this->_objAha);
			$process = new \swoole_process(array($worker, 'start'));
			//$process->daemon();
			$workerPid = $process->start();
			$this->_workers[$workerPid] = $process;
			$process->write("worker started!");
		}
		foreach($this->_workers as $process) {
			$workerPid = \swoole_process::wait();
			echo "[Worker Shutdown][WorkerId] $workerPid " . PHP_EOL;
			unset($this->_workers[$workerPid]);
		}
	}
	
	}

	$objDeamon = new \Daemon\Async();
	$objDeamon->start();

## coroutine model example(worker) ##
	namespace Daemon;

	class Asyncworker {
	
	/**
	 * @brief Aha实例
	 * @var type 
	 */
	protected $_objAha = null;
	
	protected $_worker = null;

	/**
	 * @brief 使用共享内存的数据
	 * @param type $objAha
	 * @return \Daemon\Asyncworker
	 */
	public function __construct($objAha) {
		$this->_objAha = $objAha;
		return $this;
	}
	
	/**
	 * @brief 启动子进程
	 * @param \swoole_process $worker
	 */
	public function start(\swoole_process $worker) {
		$this->_worker = $worker;
		swoole_event_add($worker->pipe, function($pipe) use ($worker) {
			echo $worker->read() . PHP_EOL;
		});
		$this->worker();
	}
	
	/**
	 * @brief 子进程做事情
	 */
	public function worker() {
		$scheduler = $this->_objAha->getScheduler();
		swoole_timer_tick(5000, function() use ($scheduler){
			for ($i=0;$i<50;$i++) {
				$coroutine = $this->dbTest();;
				if ( $coroutine instanceof \Generator ) {
					$scheduler->newTask($coroutine);
					$scheduler->run();
				}
			}
		});
	}
	
	/**
	 * @brief 数据库
	 */
	public function dbTest() {
		$ret = yield ( $this->dbTrans() );
		echo "[workerId]" . $this->_worker->pid . " [ret]" . serialize($ret) . PHP_EOL;
	}

	public function dbTrans() {
		$config = $this->_objAha->getConfig();
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
	
	}





