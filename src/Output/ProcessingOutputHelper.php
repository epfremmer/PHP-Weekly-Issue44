<?php
/**
 * File ProcessingOutputHelper.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

namespace PHPWeekly\Issue44\Output;

use React\Stream\WritableStreamInterface;

/**
 * Class ProcessingOutputHelper
 *
 * Output helper used to manage rendering current aggregate stream progress as data is received. Renders
 * multiple progress bars that track individual stream progress independently.
 *
 * Helper also used to manage CLI screen size and render maximum processing output dynamically based on the
 * number of lines available for rendering.
 *
 * @package PHPWeekly\Issue44
 */
class ProcessingOutputHelper
{
    const AVG_MAX_SIZE_INCREASE = 1.3;
    const MAX_FRAME_PER_SEC = 24;

    /**
     * @var ProgressBar[]
     */
    private $progressBars = [];

    /**
     * @var int
     */
    private $lines = 0;

    /**
     * @var int
     */
    private $last;

    /**
     * Register a new stream with the output helper to be rendered
     *
     * Bind event listeners to handle receiving stream data and completion events
     *
     * @param WritableStreamInterface $stream
     * @param int $sequence
     * @return ProcessingOutputHelper|$this
     */
    public function register(WritableStreamInterface $stream, int $sequence) : ProcessingOutputHelper
    {
        $stream->on('data', function(string $data) use ($sequence) {
            $this->onData($data, $sequence);
        });

        $stream->on('drain', function() use ($sequence) {
            $this->onComplete($sequence);
        });

        return $this;
    }

    /**
     * Handle receiving data from a sequence stream
     *
     * - Creates new stream progress bar for initial data event
     * - Recalculate max progress bar steps from previous stream if available
     * - Advance progress bar steps and trigger re-render
     *
     * @param string $data
     * @param int $sequence
     * @return void
     */
    public function onData(string $data, int $sequence)
    {
        if (!array_key_exists($sequence, $this->progressBars)) {
            $this->progressBars[$sequence] = new ProgressBar(sprintf('Sequence %s', $sequence + 1));
        }

        $progressBar = $this->progressBars[$sequence];

        if ($previous = $this->getPrev($sequence)) {
            $progressBar->setMaxSteps(floor($previous->getMaxSteps() * self::AVG_MAX_SIZE_INCREASE));
        }

        $progressBar->advance(strlen($data));
        $this->render();
    }

    /**
     * Handle stream complete event
     *
     * Complete the progress bar and re-render
     *
     * @param int $sequence
     * @return void
     */
    public function onComplete(int $sequence)
    {
        $progressBar = $this->progressBars[$sequence];
        $progressBar->complete();

        $this->recalculateMaxSteps($sequence);
        $this->render(true);
    }

    /**
     * Recalculate/estimate the remaining steps for all remaining progress
     * bars based off of the current bars total estimated steps
     *
     * @param int $sequence
     * @return void
     */
    private function recalculateMaxSteps(int $sequence)
    {
        $current = $this->progressBars[$sequence];

        while ($next = $this->getNext($sequence)) {
            $next->setMaxSteps(ceil($current->getMaxSteps() * self::AVG_MAX_SIZE_INCREASE));
            $current = $next;
            $sequence++;
        }
    }

    /**
     * Return the next sequence's progress bar
     *
     * @param int $sequence
     * @return null|ProgressBar
     */
    private function getNext(int $sequence)
    {
        return array_key_exists($sequence + 1, $this->progressBars) ? $this->progressBars[$sequence + 1] : null;
    }

    /**
     * Return the previous sequence's progress bar
     *
     * @param int $sequence
     * @return null|ProgressBar
     */
    private function getPrev(int $sequence)
    {
        return array_key_exists($sequence - 1, $this->progressBars) ? $this->progressBars[$sequence - 1] : null;
    }

    /**
     * Render all possible progress bars
     *
     * @param bool $force
     * @return void
     */
    public function render(bool $force = false)
    {
        $rows = exec('tput lines') - 10;
        $time = microtime(true);
        $fps = 1 / ($time - $this->last);

        if (!$force && $fps > self::MAX_FRAME_PER_SEC) {
            return;
        }

        $this->clear();

        ob_start();

        /** @var ProgressBar $progressBar */
        foreach (array_slice($this->progressBars, -$rows) as $progressBar) {
            $this->lines += $progressBar->render();
        }

        echo ob_get_clean();

        $this->last = $time;
    }

    /**
     * Clear current output
     *
     * @reutrn void
     */
    public function clear()
    {
        if (!$this->lines) {
            return;
        }

        echo sprintf("\033[%sA", $this->lines);

        $this->lines = 0;
    }
}
