<?php
use Jamlee\Coroutine\Scheduler;
use Jamlee\Coroutine\Task;
use Jamlee\Coroutine\SystemCall;
use Jamlee\Coroutine\CoSocket;

require '../vendor/autoload.php';

function server($port)
{
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new Exception($errStr, $errNo);
    stream_set_blocking($socket, 0);
    $socket = new CoSocket($socket);
    while (true) {
        yield SystemCall::newTask(
            handleClient(yield $socket->accept())
        );
    }
}

function handleClient($socket)
{
    $data = (yield $socket->read(8192));
    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
    HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r\n
$msg
RES;

    yield $socket->write($response);
    yield $socket->close();
}

$scheduler = new Scheduler;
$scheduler::$debug = true;
$scheduler->newTask(server(8000));
$scheduler->withIoPoll()->run();