<?php
/**
 * File Connection.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44;

use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;
use Symfony\Component\Process\Process;

/**
 * Class Connection
 *
 * Manages a processing server child process and connection. Data written to the connection stream
 * is piped directly to the socket connector for processing and returned to the stream as the socket connector
 * returns parsed results.
 *
 * @package PHPWeekly\Issue44
 */
class Connection extends ThroughStream
{
    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $index;

    /**
     * @var string
     */
    private $addr;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var \React\Socket\Connection
     */
    private $conn;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $buffer;

    /**
     * Connection constructor
     *
     * @param int $port
     * @param LoopInterface $loop
     */
    public function __construct(int $port, LoopInterface $loop)
    {
        parent::__construct();

        $this->port = $port;
        $this->index = $port - Manager::STARTING_SOCKET;
        $this->addr = sprintf('tcp://127.0.0.1:%s', $port);
        $this->loop = $loop;
    }

    /**
     * @param string $data
     */
    public function write($data)
    {
        if (!$this->conn) {
            $this->openConnection();

            $this->conn->on('data', [$this, 'onData']);
            $this->conn->on('close', [$this, 'onClose']);
        }

        $this->conn->write($data);
    }

    /**
     * Start a new processing server process and open a
     * streaming connection to it
     *
     * @return void
     */
    private function openConnection()
    {
        $this->process = new Process(sprintf('php server.php %s', $this->port));
        $this->process->start();

        usleep(1000 * 100);

        $client = stream_socket_client($this->addr);
        $this->conn = new \React\Stream\Stream($client, $this->loop);
    }

    /**
     * Buffer data received from the server
     *
     * @param string $data
     * @return void
     */
    public function onData(string $data)
    {
        //$size = strlen(trim($data));

        $this->buffer .= $data;

        //echo sprintf("\033[33m[Sequence %s - Data Received]\e[0m: %s bytes", $this->index, $size) . PHP_EOL;

        parent::write($data);
    }

    /**
     * Handle connection close
     *
     * Server closes the connection when the entire sequence
     * has been processed
     *
     * @return void
     */
    public function onClose()
    {
        //$size = strlen(trim($this->buffer));

        //echo sprintf("\033[32m[Sequence %s - Complete]\e[0m: %s bytes", $this->index, $size) . PHP_EOL;

        $this->buffer = null;
        $this->process->stop();
        $this->emit('drain', [$this]);
    }
}
