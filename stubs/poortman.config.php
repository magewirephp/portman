<?php

declare(strict_types=1);

return [
    'directories' => [
        'source' => [
            'sources-directory' => [
                'glob'   => '**/*.php',
                'ignore' => [
                    'DontIncludeMe/**/*'
                ]
            ],
            'sources-directory2'
        ],
        'augmentation' => [
            'augmentation'
        ],
        'additional' => [],
        'output' => 'dist'
    ],
    'rename-namespaces' => [],
    'rename-classes' => [],
    'file-doc-block' => null,
    'run-rector' => false,
    'run-php-cs-fixer' => false,
];
