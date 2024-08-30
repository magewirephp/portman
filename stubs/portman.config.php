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
    'transformations' => [
        'Foo\\' => [
            'rename'          => 'Baz\\',
            'file-doc-block' => '',
            'children'        => [
                'Bar' => [
                    'rename'            => 'Baz',
                    'remove-methods'    => [],
                    'remove-properties' => []
                ]
            ]
        ]
    ],
    'post-processors'=> [
        'rector' => false,
        'php-cs-fixer' => false,
    ]
];
