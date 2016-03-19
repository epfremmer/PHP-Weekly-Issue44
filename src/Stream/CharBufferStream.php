<?php
/**
 * File CharBufferStream.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44\Stream;

use React\Stream\ThroughStream;

/**
 * Class CharBufferStream
 *
 * Buffers written characters until a complete look-and-say sequence segment has been written.
 *
 * This is used so we can pipe both partial and/or complete segments to the stream without
 * fear of parsing an incomplete segment
 *
 * @package PHPWeekly\Issue44\Stream
 */
class CharBufferStream extends ThroughStream
{
    /**
     * @var string
     */
    private $buffer;

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        for ($i = 0; $i < strlen($data); $i++) {
            $char = @$data[$i];

            if (!is_numeric($char)) {
                parent::write($this->buffer);
                parent::write(PHP_EOL);

                $this->buffer = null;
            }

            if (!$this->buffer || $this->buffer[0] === $char) {
                $this->buffer .= $char;
                continue;
            }

            parent::write($this->buffer);

            $this->buffer = $char;
        }
    }
}
