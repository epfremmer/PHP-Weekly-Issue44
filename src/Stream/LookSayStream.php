<?php
/**
 * File CharBufferStream.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44\Stream;

use React\Stream\ThroughStream;

/**
 * Class LookSayStream
 *
 * Steam to parse a single look-and-say sequence segment and
 * return the resulting segment
 *
 * @package PHPWeekly\Issue44\Stream
 */
class LookSayStream extends ThroughStream
{
    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        if ($data === PHP_EOL) {
            $this->end(PHP_EOL);
            return;
        }

        parent::write(sprintf('%s%s', strlen($data), $data[0]));
    }
}
