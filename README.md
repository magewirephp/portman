# Poortman
Poortman is a command-line utility that simplifies porting PHP libraries between frameworks.

### Contents

- [Installing](#electric_plug-installing)
- [Configure](#gear-configure)
  - [Options](#options)
  - [Filtering files](#filtering-files)
  - [Environment file](#environment-file)
- [Usage](#tada-usage)
  - [Build](#build)
  - [Watch](#watch)
- [Contributing](#pencil2-contributing)
- [Code of Conduct](#book-code-of-conduct)
- [Made possible by using](#bulb-made-possible-by-using)
  - [Nikic PHP-Parser](#nikic-php-parser)
  - [Laravel Zero](#laravel-zero)

## :electric_plug: Installing
```shell
composer require --dev magewirephp/poortman
```

You can run it from your bin directory:
```shell
vendor/bin/poortman
```

## :gear: Configure
Rector works with `poortman.config.php` config file. You can create it manually, or let Poortman create it for you:
```shell
vendor/bin/poortman init
```

### Options
```php
return [
    'directories' => [
        'source' => [
            'foo' => [
                'glob'   => '**/*.php', // only php files
                'ignore' => [
                    'DontIncludeMe/**/*' // but nothing from 'DontIncludeMe'
                ]
            ],
            'bar' // the whole directory
        ], // directories where the original source code lives
        'augmentation' => ['baz'], // directories with the code that overwrites the original classes
        'additional' => [], // directories with extra code that is just additional code to copy to the dist
        'output' => 'dist' // directories with extra code that is just additional code to copy to the dist
    ],
    "rename-namespaces"        => [], // a ['From/Namespace' => 'To/Namespace'] array, to rename the original namespaces to new ones
    "rename-classes"           => [], // a ['FromClassName' => 'ToClassName'] array, to rename specific class-names
    "add-declare-strict"       => false, // add declare(strict_types=1); to the top of every file
    "file-doc-block"           => null, // a string containing the new docblock for all files
    "run-rector"               => false, // should run Rector after build/watch?
    "run-php-cs-fixer"         => false, // should run PHP-CS-Fixer after build/watch?
];
```

### Filtering files
If you want to be more precise into what files or folders to include from the directories you can use the 'ant-like glob' functionality provided by '[webmozarts/glob](https://github.com/webmozarts/glob)':
```php
return [
    'directories' => [
        'source' => [
            'foo' => [
                'glob'   => '**/*.php', // only php files
                'ignore' => [
                    'DontIncludeMe/**/*' // but nothing from 'DontIncludeMe'
                    '!DontIncludeMe/PleaseAddMe.php' // Except for the 'PleaseAddMe' file in 'DontIncludeMe'
                ]
            ],
            'bar' // the whole directory
        ], // directories where the original source code lives
        ...
    ],
    ...
];
```
Here you can see a global 'glob' definition(`'glob'   => '**/*.php'`) that will only include php files from the `foo` source directory.

But the `ignore` array specifies globs for files to ignore.
The ignore globs will stack. Globs starting with a `!` will negate the ignored files from previous ignore rules.
So given the configuration above and this folder structure:
```text
foo
  ClassFoo.php
  Namespace
    ClassBar.php
  DontIncludeMe
    PleaseAddMe.php
    ClassBaz.php
    ClassRemoved.php
```
The files `DontIncludeMe\ClassBaz.php` and `DontIncludeMe\ClassRemoved.php` will be ignored.

### Environment file
If you want to store the configuration in a different location you can set the `POORTMAN_CONFIG_FILE` environment variable.

## :tada: Usage
Just use Poortman and check its commands:
```shell
vendor/bin/poortman
```

### Build
The `build` command will merge all code into the output-directory.
```shell
vendor/bin/poortman build
```

### Watch
The `watch` command will watch for changes in any of the source/augmentation/addition directories and run the build process for the changed files.

To use `watch` you will need to install [chokidar-cli](https://www.npmjs.com/package/chokidar-cli) into the project folder
```shell
npm install chokidar-cli
```
Or globally
```shell
npm install -g chokidar-cli
```

Using the `watch` command:
```shell
vendor/bin/poortman watch
```

## :pencil2: Contributing
Thank you for considering contributing to Poortman! Please read the [contribution guide](https://github.com/magewirephp/poortman/blob/main/CONTRIBUTING.md) to know how to behave, install and use Poortman for contributors.

## :book: Code of Conduct
In order to ensure that the Poortman is welcoming to all, please review and abide by the [Code of Conduct](https://github.com/magewirephp/poortman/blob/main/CODE_OF_CONDUCT.md).

## :bulb: Made possible by using

### Nikic PHP-Parser
[PHP-Parser](https://github.com/nikic/PHP-Parser) is used to parse source and augmentation php classes, to merge them afterwards.
Created by [Nikita Popov](https://github.com/nikic)

### Laravel Zero
[Laravel Zero](https://laravel-zero.com/) is the command line utility base.
Created by [Nuno Maduro](https://github.com/nunomaduro) and [Owen Voke](https://github.com/owenvoke).