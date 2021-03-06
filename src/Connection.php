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
     * @var bool
     */
    private $buffering;

    /**
     * Connection constructor
     *
     * @param int $port
     * @param LoopInterface $loop
     * @param bool $buffering
     */
    public function __construct(int $port, LoopInterface $loop, bool $buffering = false)
    {
        parent::__construct();

        $this->port = $port;
        $this->index = $port - Manager::STARTING_SOCKET;
        $this->addr = sprintf('tcp://127.0.0.1:%s', $port);
        $this->loop = $loop;
        $this->buffering = $buffering;
    }

    /**
     * Set buffering flag
     *
     * Allows output to be stored in internal buffer
     * for later use
     *
     * @param bool $buffering
     * @return void
     */
    public function setBuffering(bool $buffering)
    {
        $this->buffering = $buffering;
    }

    /**
     * Write data to the internal socket connection and
     * send result to any piped streams
     *
     * {@inheritdoc}
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

        $start = microtime(true);

        while (!$client = @stream_socket_client($this->addr)) {
            // try try again
            if (microtime(true) - $start > 1) {
                throw new \RuntimeException('Unable to connect to socket');
            }
        }

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
        if ($this->buffering) {
            $this->buffer .= $data;
        }

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
        $this->emit('drain', [$this->buffer, $this]);

        $this->process->stop();
        $this->buffer = null;
    }
}
