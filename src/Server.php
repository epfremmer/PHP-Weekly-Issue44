<?php
/**
 * File Server.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44;

use PHPWeekly\Issue44\Stream\CharBufferStream;
use PHPWeekly\Issue44\Stream\LookSayStream;
use React\EventLoop\Factory;
use React\Socket\Connection;
use React\Socket\Server as SocketServer;

/**
 * Class Server
 *
 * Look-and-Say parsing server. Buffers and parses a look-and-say sequence and returns incremental
 * results as they are parsed. This is used so we can process a partial sequence in parallel while the
 * previous one is still in flight.
 *
 * @package PHPWeekly\Issue44
 */
class Server
{
    /**
     * @var int
     */
    private $port;

    /**
     * Server constructor
     *
     * @param int $port
     */
    public function __construct(int $port)
    {
        $this->port = $port;
        $this->loop = Factory::create();
        $this->socket = new SocketServer($this->loop);
    }

    /**
     * Run the server
     *
     * @throws \React\Socket\ConnectionException
     */
    public function start()
    {
        $this->socket->listen($this->port);
        $this->socket->on('connection', [$this, 'onConnection']);

        $this->loop->run();
    }

    /**
     * Handle new connection
     *
     * Setup processing streams and pipe connection data
     * through before returning to the client
     *
     * @param Connection $conn
     */
    public function onConnection(Connection $conn)
    {
        $bufferStream = new CharBufferStream();
        $lookSayStream = new LookSayStream();

        $conn->pipe($bufferStream)->pipe($lookSayStream)->pipe($conn);

        $lookSayStream->on('end', [$this->socket, 'shutdown']);
    }
}
