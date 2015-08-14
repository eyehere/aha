<?php
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
//�����¼��ص�����
$client->on("connect", function($cli) {
    $arrData = array(
		'cmd' => 'demo-server-tcp',
		'body'=> 'from http request'
	);
	$cli->send(json_encode($arrData) . "\r\n\r\n");
});
$client->on("receive", function($cli, $data){
    echo "Received: ".$data."\n";
	$cli->close();
});
$client->on("error", function($cli){
    echo "Connect failed\n";
});
$client->on("close", function($cli){
    echo "Connection close\n";
});
//������������
$client->connect('127.0.0.1', 9602, 0.5);
