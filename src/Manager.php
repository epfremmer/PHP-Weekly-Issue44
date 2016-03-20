<?php
/**
 * File Manager.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44;

use PHPWeekly\Issue44\Output\ProcessingOutputHelper;

/**
 * Class Manager
 *
 * Look-an-Say manager service responsible for initializing socket server connections
 * used to process sequence parsing in parallel
 *
 * Each connection is created and then piped to it's previous sibling to allow streaming processed
 * sequence data as it is returned from the processing connections
 *
 * @package PHPWeekly\Issue44
 */
class Manager
{
    const STARTING_SOCKET = 1337;

    /**
     * @var int
     */
    private $iterations;

    /**
     * @var Connection[]
     */
    private $connections = [];

    /**
     * @var ProcessingOutputHelper
     */
    private $output;

    /**
     * Manager constructor
     *
     * @param int $iterations
     */
    public function __construct(int $iterations)
    {
        $this->iterations = $iterations;

        $this->loop = \React\EventLoop\Factory::create();
        $this->output = new ProcessingOutputHelper();
    }

    /**
     * Initialize internal processing server connections pipeline
     *
     * Handles piping output from each subsequent connection to the next
     * processor in line so data can be streamed through the chain as it is processed
     *
     * @reutrn void
     */
    private function initConnections()
    {
        for ($i = 0; $i < $this->iterations; $i++) {
            $connection = new Connection(self::STARTING_SOCKET + $i, $this->loop);

            $this->connections[$i] = $connection;

            $this->output->register($connection, $i);

            if (isset($this->connections[$i-1])) {
                $this->connections[$i-1]->pipe($connection);
            }
        }
    }

    /**
     * Start the sequence manager loop
     *
     * @param string $input
     * @reutrn void
     */
    public function start(string $input)
    {
        $this->initConnections();

        $this->connections[0]->write($input . PHP_EOL);
        $this->loop->run();
    }
}
