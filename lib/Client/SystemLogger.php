<?php

namespace LightStepBase\Client;

use Psr\Log\AbstractLogger;
use Stringable;

class SystemLogger extends AbstractLogger
{

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        error_log($message . PHP_EOL . var_export($context, true));
    }
}
