# Aha #

----------

Aha is a high performance network framework which support both asynchronous and coroutine mode at the same time base on swoole,written in php.

# Road map #

----------

1. v1.0.0
	- Mvc asynchronous(http、tcp、udp)
	- Network asynchronous server(http、tcp、udp)
	- Network asynchronous client(http、tcp、udp、multi、pool)
	- asynchronous storage(mysql、transaction、redis、pool);
	- asynchronous logger
2. v1.1.0
	- Network(http、tcp、udp) coroutine support;
	- php daemon multi concurrent process support;
	- coroutine of multi task schedule for deamon support;
3. v1.+.+
	- more third party clients such as memcache/beanstalkd support;
4. **I will rewrite Aha framework in C because of these reasons below:**
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

# Aha coroutine mode #
https://github.com/eyehere/aha/blob/master/docs/Aha-coroutine-mode.md

# Aha asynchronous mode #
https://github.com/eyehere/aha/blob/master/docs/Aha-asynchronous-mode.md

# Aha daemon mode #
https://github.com/eyehere/aha/blob/master/docs/Aha-daemon.md

# Aha architecture #
![Aha框架架构](http://i.imgur.com/4KbCq1u.png)

# performance #
![Aha框架的性能测试数据(仅供参考)](http://i.imgur.com/YaBHyHi.png)

# swoole architecture #
![swoole架构](http://i.imgur.com/4nfMFp3.png)
