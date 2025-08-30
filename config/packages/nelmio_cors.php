<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config): void {
    $origins = array_filter(array_map('trim', explode(',', getenv('FRONTEND_ORIGINS') ?: '')));

    $config->extension('nelmio_cors', [
        'defaults' => [
            'allow_credentials' => true,
            'allow_headers' => ['Content-Type', 'Authorization', 'X-API-Key', 'X-Tenant-ID'],
            'allow_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'max_age' => 3600,
        ],
        'paths' => [
            '^/api/' => ['allow_origin' => $origins],
            '^/graphql' => ['allow_origin' => $origins],
        ],
    ]);
};
