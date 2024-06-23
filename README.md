# Poortman
Poortman is a command-line utility that simplifies porting PHP libraries between frameworks.

## Installing
```shell
composer require --dev magewirephp/poortman
```

You can run it from your bin directory:
```shell
vendor/bin/poortman
```

## Configure
Rector works with `poortman.config.php` config file. You can create it manually, or let Poortman create it for you:
```shell
vendor/bin/poortman init
```

Options:
```php
return [
    "source-directories"       => ['source'], // directories where the original source code lives
    "augmentation-directories" => ['augmentation'], // directories with the code that overwrites the orriginal classes
    "addition-directories"     => [], // directories with extra code that is just additional code to copy to the dist
    "dist-directory"           => 'dist', // the location where the combined code should go
    "rename-namespaces"        => [], // a ['From/Namespace' => 'To/Namespace'] array, to rename the orriginal namespaces to new ones
    "rename-classes"           => [], // a ['FromClassName' => 'ToClassName'] array, to rename specific class-names
    "add-declare-strict"       => false, // add declare(strict_types=1); to the top of every file
    "file-doc-block"           => null, // a string containing the new docblock for all files
    "run-rector"               => false, // should run Rector after build/watch?
    "run-php-cs-fixer"         => false, // should run PHP-CS-Fixer after build/watch?
];
```

If you want to store the configuration in a different location you can set the `POORTMAN_CONFIG_FILE` environment variable.

## Usage
Just use Poortman and check its commands:
```shell
vendor/bin/poortman
```

### Build
Will merge all code into the dist-directory.
```shell
vendor/bin/poortman build
```

### Watch
Will watch for changes in any of the source/augmentation/addition directories and run the build process for the changed files.
```shell
vendor/bin/poortman watch
```

## Contributing
Clone the repo and test the Poortman commands by running:
```shell
php poortman
```
To build the standalone app version run:
```shell
php poortman app:build
```

## Made possible by using

### Nikic PHP-Parser
[PHP-Parser](https://github.com/nikic/PHP-Parser) is used to parse source and augmentation php classes, to merge them afterwards.
Created by [Nikita Popov](https://github.com/nikic)

### Laravel Zero
[Laravel Zero](https://laravel-zero.com/) is the command line utility base.
Created by [Nuno Maduro](https://github.com/nunomaduro) and [Owen Voke](https://github.com/owenvoke).