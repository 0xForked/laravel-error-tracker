<?php

namespace PollieDev\LaravelErrorTracker;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Throwable;

class ErrorHandler extends AbstractProcessingHandler implements HandlerInterface
{
    public function __construct($level = Logger::DEBUG, bool $bubble = true) {
        dd("??");
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void {
        dd($record);
    }

}
