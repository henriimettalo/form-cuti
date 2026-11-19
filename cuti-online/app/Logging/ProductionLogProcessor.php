<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

class ProductionLogProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        // Messages and exception traces can include whole SQL statements or uploaded data.
        return $record->with(
            message: $exception instanceof Throwable ? 'Pengecualian aplikasi.' : 'Peristiwa aplikasi.',
            context: $exception instanceof Throwable ? [
                'type' => $exception::class,
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
            ] : [],
            extra: [],
        );
    }
}
