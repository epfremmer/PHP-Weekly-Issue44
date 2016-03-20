<?php
/**
 * File ProgressBar.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44\Output;

/**
 * Class ProgressBar
 *
 * @package PHPWeekly\Issue44
 */
class ProgressBar
{
    const LEFT_BRACE = '[';
    const RIGHT_BRACE = '] ';
    const EMPTY_BAR_CHAR = '-';
    const FULL_BAR_CHAR = '=';
    const POS_ARROW = '>';
    const PERCENT_FORMAT = ' (%.2F%%)';
    const BYTES_FORMAT = ' %s bytes';
    const NAME_FORMAT = "\033[34m%s\e[0m";
    const COMPLETED = " \033[32m[done]\e[0m";

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $maxSteps;

    /**
     * @var int
     */
    private $progress;

    /**
     * @var int
     */
    private $bytes;

    /**
     * @var bool
     */
    private $complete = false;

    /**
     * @var int
     */
    private $width = 50;

    /**
     * @var int
     */
    private $written = 0;

    /**
     * ProgressBar constructor
     *
     * @param $name
     */
    public function __construct(string $name = null)
    {
        $this->name = $name ? $name . ' ' : '';
    }

    /**
     * Return max steps
     *
     * @return int
     */
    public function getMaxSteps()
    {
        return $this->maxSteps;
    }

    /**
     * Set max steps
     *
     * @param int|null $max
     * @return void
     */
    public function setMaxSteps(int $max = null)
    {
        $this->maxSteps = $max ?: $this->width;
    }

    /**
     * Return current progress
     *
     * @return int
     */
    public function getProgress() : int
    {
        return $this->progress;
    }

    /**
     * Advance the progress bar
     *
     * @param int $steps
     * @return void
     */
    public function advance(int $steps)
    {
        $this->bytes += $steps;
        $this->progress += $steps;

        if ($this->progress > $this->maxSteps) {
            $this->maxSteps = $this->progress;
        }
    }

    /**
     * Complete progress bar
     *
     * @return void
     */
    public function complete()
    {
        $this->progress = $this->maxSteps;
        $this->complete = true;
    }

    /**
     * Render progress bar and return rendered line count
     *
     * @return int
     */
    public function render()
    {
        $this->clear();
        $this->write(sprintf(self::NAME_FORMAT, $this->name));
        $this->write(self::LEFT_BRACE);
        $this->write(str_repeat(self::FULL_BAR_CHAR, $progress = floor($this->progress / $this->maxSteps * $this->width)));
        $this->write(self::POS_ARROW);
        $this->write(str_repeat(self::EMPTY_BAR_CHAR, $this->width - $progress));
        $this->write(self::RIGHT_BRACE);
        $this->write(sprintf(self::BYTES_FORMAT, number_format($this->bytes)));
        $this->write(sprintf(self::PERCENT_FORMAT, $this->progress / $this->maxSteps * 100));
        $this->write($this->complete ? self::COMPLETED : '');
        $this->write(PHP_EOL);

        return 1;
    }

    /**
     * Print output and record written character length
     *
     * @param string|null $out
     */
    private function write(string $out = null)
    {
        echo $out;
        $this->written += strlen($out);
    }

    /**
     * Clear/overwrite last written characters from the
     * screen with whitespace
     *
     * @return void
     */
    private function clear()
    {
        echo sprintf("\r%s\r", str_repeat(' ', $this->written));
        $this->written = 0;
    }
}
