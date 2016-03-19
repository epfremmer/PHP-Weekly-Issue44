#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * File server.php.
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

require_once './vendor/autoload.php';

use PHPWeekly\Issue44\Server;

if (!isset($argv[1]) || !is_numeric($argv[1])) {
    throw new \InvalidArgumentException("Missing or invalid port provided");
}

$server = new Server((int) $argv[1]);
$server->start();
