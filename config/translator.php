<?php

declare(strict_types=1);

use Translator\Framework\LaravelConfigLoader;
use Translator\Infra\LaravelJsonTranslationRepository;

return [
    'languages' => ['id'],
    'directories' => [
        app_path(),
        resource_path('view'),
    ],
    'output' => resource_path('../lang'),
    'extensions' => ['php'],
    'functions' => ['lang', '__'],
    'container' => [
        'config_loader' => LaravelConfigLoader::class,
        'translation_repository' => LaravelJsonTranslationRepository::class,
    ],
];
