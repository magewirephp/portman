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
    'post-processors'=> [
        'rector' => false,
        'php-cs-fixer' => false,
    ]
];
