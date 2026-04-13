<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\GelfHandler;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Gelf\Transport\UdpTransport;
use Gelf\Publisher;

class GraylogLoggerFactory
{
    /**
     * Create a custom Monolog instance for Graylog.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $transport = new UdpTransport(
            $config['host'] ?? '192.168.0.175',
            $config['port'] ?? 12201
        );

        $publisher = new Publisher($transport);

        $logger = new Logger('graylog');

        // Adicionar processador para campos customizados
        $logger->pushProcessor($this->getApplicationProcessor($config));

        $logger->pushHandler(
            new GelfHandler($publisher, Logger::toMonologLevel($config['level'] ?? 'debug'))
        );

        return $logger;
    }

    /**
     * Create a processor to add application-specific fields to GELF messages.
     *
     * @param  array  $config
     * @return callable
     */
    private function getApplicationProcessor(array $config)
    {
        return function (LogRecord $record) use ($config) {
            // Adiciona o identificador da aplicação
            $appIdentifier = $config['app_id'] ?? 'PROCESSOSELETIVO';

            // Adiciona campos customizados no GELF (usando _ prefix)
            $record->extra['_application'] = $appIdentifier;
            $record->extra['_facility'] = 'laravel';
            $record->extra['_environment'] = app()->environment();
            //$record->extra['_version'] = config('app.version', '1.0');

            return $record;
        };
    }
}
