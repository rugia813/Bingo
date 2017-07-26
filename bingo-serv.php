<?php

$table = new swoole_table(1024);
$table->column('fd', swoole_table::TYPE_INT, 4);
$table->column('is_host', swoole_table::TYPE_INT, 1);
$table->column('alias', swoole_table::TYPE_STRING, 128);
$table->create();
//创建websocket服务器对象，监听0.0.0.0:9502端口
$ws = new swoole_websocket_server("0.0.0.0", 9502);
$ws->table = $table;

$dimension = 6;

$ws->on('start', function ($ws) {  
    echo 'server started.' . "\n";
});

//监听WebSocket连接打开事件
$ws->on('open', function ($ws, $frame) {  
	echo 'open' . "\n";
    $ws->push($frame->fd, "hello, welcome");
	// var_dump($frame);
    $ws->table->set($frame->fd, array('fd' => $frame->fd));
    broadcast($ws, $frame->data->alias ?? '???' . ' connected.');

    // $ws->table->set($frame->fd, array('id' => $arr[0])); //member_id
    // $ws->table->set($frame->fd, array('session_id' => $arr[1])); //member_id
    //获取客户端id插入table
    //var_dump($ws->table->get(1));
    
    // $swoole_mysql = new Swoole\Coroutine\MySQL();
    // $swoole_mysql->connect([
    //     'host' => '192.168.1.135',
    //     'port' => 3306,
    //     'user' => 'root',
    //     'password' => '0000',
    //     'database' => 'test',
    // ]);
    // $res = $swoole_mysql->query('select * from t_broadcast;');
    // echo print_r($res) . "\n";

    // swoole_timer_tick(1000, function($timer_id,$parmas){
    //     echo "QQ:542684913\n" . "\n";
    //     echo "{$parmas} \n" . "\n";
    // }, "hello");
    
    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1', 6379);
    while (true) {
        $val = $redis->subscribe(['bingo']);

        if($val){
            $ws->push($frame->fd, $val[2]);//消息广播给所有客户端

            //print_r($val[2]);
            //print_r($msg);
            // switch($msg['method']){
            //     case 'logout':
            //         foreach ($ws->table as $u) {
            //             if($u->id == $id && $u->session_id != $session_id)
            //                 $ws->push($u['fd'], json_encode(['method' => 'logout', 'data' => $msg['data']]));
            //         }
            //         break;
            //     case 'unread':
            //         foreach ($ws->table as $u) {
            //             if($u['id'] == $id){
            //                 $ws->push($u['fd'], json_encode(['method' => 'unread', 'data' => $msg['data']]));
            //                 var_dump($u);
            //             }
            //         }
            //         break;
            //     case 'notice':
            //         foreach ($ws->table as $u) {
            //             if($u->id == $id)
            //                 $ws->push($u['fd'], json_encode(['method' => 'notice', 'data' => $msg['data']]));
            //         }
            //         break;
            //     default:
            //     break;
            // }
        }  
            if(!$ws->table->exist($frame->fd)){
                break;
            }
        

    }
}); 

//监听WebSocket消息事件
$ws->on('message', function ($ws, $frame) {
    echo $frame->fd.":{$frame->data}" . "\n";
    // var_dump($ws->connections);

    $data = json_decode($frame->data);
    $id = $data->id??'';
    $alias = $data->alias??$id;
    $txt = $data->txt??'';
    echo 'id: '.$id . "\n";
    echo 'alias: '.$alias . "\n";
    echo 'txt: '.$txt . "\n";
    if ($id && $ws->table->get($id)) {
        echo 'pm' . "\n";
        $ws->push($id, $alias . ': ' . $txt );
        $ws->push($frame->fd, 'To ' . $id . ': ' . $txt );
    }else{
        foreach ($ws->table as $u) {
            $ws->push($u['fd'], $alias . ': ' . $data->txt );//消息广播给所有客户端
        } 
    }
 
    // foreach($ws->connections as $fd) { 
    // 	$ws->push($fd, json_encode($data)); 
    // }    
});

//监听WebSocket连接关闭事件
$ws->on('close', function ($ws, $frame) {
    echo "client-{$frame} is closed\n"; 
    foreach ($ws->table as $u) {
        $ws->push($u['fd'], $frame->data->alias ?? '???' . ' left.');//消息广播给所有客户端
    }
    $ws->table->del($frame);//从table中删除断开的id
});

function broadcast($ws, $msg){
    foreach ($ws->table as $u) {
        $ws->push($u['fd'], $msg);//消息广播给所有客户端
    }
}


function onWorkerStart($serv, $worker_id){
    //当worker id =0 的时候我们才创建这个tick
    if($worker_id == 0)
    {
        swoole_timer_tick(1000, function($timer_id,$parmas){
            echo "QQ:542684913\n";
            echo "{$parmas} \n";
        }, "hello");
    }
}

//设置异步任务的工作进程数量
$ws->set(array('task_worker_num' => 4));

$ws->on('receive', function($serv, $fd, $from_id, $data) {
    //投递异步任务
    $task_id = $ws->task($data);
    echo "Dispath AsyncTask: id=$task_id\n";
});

//处理异步任务
$ws->on('task', function ($serv, $task_id, $from_id, $data) {
    echo "New AsyncTask[id=$task_id]".PHP_EOL . "\n";
    //返回任务执行的结果
    $ws->finish("$data -> OK");
});

//处理异步任务的结果
$ws->on('finish', function ($serv, $task_id, $data) {
    echo "AsyncTask[$task_id] Finish: $data".PHP_EOL . "\n";
});

$ws->start();
