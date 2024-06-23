<?php

declare(strict_types=1);

namespace App\Poortman;

use Illuminate\Console\Command;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SourceBuilder
{
    private array $renameClasses = [];

    public function buildFile(string $path, Command $command): void
    {
        $distDir = poortman_config('dist-directory', null);
        if (! $distDir) {
            throw new ConfigurationException('No dist-directory configured');
        }
        // determine mode and file
        $modes = [
            'source' => poortman_config('source-directories', []),
            'augmentation' => poortman_config('augmentation-directories', []),
            'addition' => poortman_config('addition-directories', []),
        ];
        $mode = null;
        foreach ($modes as $m => $directories) {
            foreach ($directories as $dir) {
                if (str_starts_with($path, $dir)) {
                    $file = str_replace($dir, '', $path);
                    $mode = $m;
                    break;
                }
            }
            if (! is_null($mode)) {
                break;
            }
        }

        // if mode or file is not set the file cannot be processed
        if (! isset($file) || ! $mode) {
            $command->warn('Skiped: ['.$path.']');

            return;
        }

        // copy for addition mode
        if ($mode === 'addition') {
            $buildPath = $distDir.DIRECTORY_SEPARATOR.$file;
            self::ensureDir($buildPath);
            copy($path, $buildPath);
            $command->line('Copied: ['.$file.']');

            return;
        }

        // if an augmentation the source file should be present too
        $sourceFilePath = self::findFilePathInDirectories($file, poortman_config('source-directories', []));
        if ($mode === 'augmentation' && is_null($sourceFilePath)) {
            $command->warn('Warning: source file not found for ['.$file.']');

            return;
        }

        // if an source the augmentation file should be present too
        $augmentationFilePath = self::findFilePathInDirectories($file, poortman_config('augmentation-directories', []));
        if ($mode === 'source' && is_null($augmentationFilePath)) {
            $command->warn('Warning: augmentation file not found for ['.$file.']');

            return;
        }

        $command->info('Merging: ['.$file.']');
        $this->mergeClass(
            $file,
            $sourceFilePath,
            $augmentationFilePath,
            $distDir
        );

        $command->line('Merged, cleaning');
        passthru('vendor/bin/rector process --no-progress-bar --no-diffs '.realpath($distDir.$file));
        passthru('vendor/bin/php-cs-fixer fix --quiet '.realpath($distDir.$file));

        $command->info('Done: ['.$file.']');
    }

    public static function ensureDir(string $path): string
    {
        $directory = dirname($path);
        if (! file_exists($directory)) {
            mkdir($directory, recursive: true);
        }

        return $directory;
    }

    public static function findFilePathInDirectories(string $file, array $directories): ?string
    {
        foreach ($directories as $directory) {
            if (file_exists($directory.DIRECTORY_SEPARATOR.trim($file, DIRECTORY_SEPARATOR))) {
                return $directory.DIRECTORY_SEPARATOR.trim($file, DIRECTORY_SEPARATOR);
            }
        }

        return null;
    }

    public function mergeClass(
        string $file,
        string $sourcePath,
        ?string $augmentionPaths,
        string $distDir
    ): void {
        // use the ClassMerger to combine the files
        $renamer = new Renamer();
        $classMerger = new ClassMerger($renamer);
        if ($augmentionPaths) {
            // Traverse the augmentation AST to collect the class structure in classMerger
            $astAugmetation = $this->parseFile($augmentionPaths);
            $augmentationTraverser = new NodeTraverser();
            $augmentationTraverser->addVisitor($classMerger);
            $classMerger->startCollecting();
            $augmentationTraverser->traverse($astAugmetation);
            $this->renameClasses = array_merge($this->renameClasses, $classMerger->getClasses());
        }
        // Traverse the source AST and apply the class structure of the augmentation collected before
        $astSource = $this->parseFile($sourcePath);
        $sourceTraverser = new NodeTraverser();
        $sourceTraverser->addVisitor($classMerger);
        $classMerger->startMerging();
        $mergedAst = $sourceTraverser->traverse($astSource);
        $className = $classMerger->getClassName();
        $mergedAst = $classMerger->finalize($mergedAst);

        // rename file if classname changed
        ['filename' => $filename, 'dirname' => $dirname] = pathinfo($file);
        if ($className && $filename !== $className) {
            $file = $dirname.$className.'.php';
        }

        // prepare the pretty printer
        $prettyPrinter = new PrettyPrinter\Standard([
            'phpVersion' => PhpVersion::fromComponents(8, 2),
            'shortArraySyntax' => true,
        ]);
        $prettyCode = $prettyPrinter->prettyPrintFile($mergedAst);

        // remove empty space before doc-comment
        $prettyCode = preg_replace('/^<\?php([\r?\n]+)\/\*\*/', "<?php\n/**", $prettyCode);

        // prepare the build directory and save the result
        $buildPath = $distDir.DIRECTORY_SEPARATOR.$file;
        self::ensureDir($buildPath);
        file_put_contents(
            $buildPath,
            $prettyCode
        );
    }

    protected function parseFile(string $file): ?array
    {
        try {
            $code = file_get_contents($file);
            // Parse the code into ASTs
            $parser = (new ParserFactory())->createForNewestSupportedVersion();

            return $parser->parse($code);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()} | file : {$file}\n";

            return null;
        }
    }

    public function build(Command $command): void
    {
        $distDir = poortman_config('dist-directory', null);
        if (! $distDir) {
            throw new ConfigurationException('No dist-directory configured');
        }

        // get all source file paths
        $sourcePaths = self::getPathsFromDirectories(poortman_config('source-directories', []));

        // get all augmentations file paths
        $augmentionPaths = self::getPathsFromDirectories(poortman_config('augmentation-directories', []));

        // get all additional file paths
        $additionalPaths = self::getPathsFromDirectories(poortman_config('addition-directories', []));

        // patch all source files with the available additions
        foreach ($sourcePaths as $file => $sourcePath) {
            $this->mergeClass(
                $file,
                $sourcePath,
                $augmentionPaths[$file] ?? null,
                $distDir
            );
        }

        // warn if there is an augmentation without source
        foreach (array_diff_key($augmentionPaths, $sourcePaths) as $file => $sourcePath) {
            $command->warn('Warning: '.$file.' has an augmentation but no source!');
        }

        // warn if there is an augmentation without source
        foreach ($additionalPaths as $file => $sourcePath) {
            // warn if there is a source file for the addition!
            if (isset($sourcePaths[$file])) {
                $command->warn('Warning: '.$file.' is an addition, but should be an augmentation on source!');

                continue;
            }
            $buildPath = $distDir.DIRECTORY_SEPARATOR.$file;
            self::ensureDir($buildPath);
            copy($sourcePath, $buildPath);
        }

        $command->info('Build complete');
        if (poortman_config('run-rector', false)) {
            $command->info('Running Rector');
            passthru('vendor/bin/rector');
            $command->info('Running Rector, complete');
        }
        if (poortman_config('run-rector', false)) {
            $command->info('Running CS-Fixer');
            passthru('vendor/bin/php-cs-fixer fix');
            $command->info('Running CS-Fixer, complete');
        }
    }

    public static function getPathsFromDirectories(array $directories): array
    {
        $paths = [];
        foreach ($directories as $directory) {
            $paths = [...$paths, ...self::getPaths($directory)];
        }

        return $paths;
    }

    public static function getPaths(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $classes = [];
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isFile() && pathinfo($path)['extension'] === 'php') {
                $file = substr($path, strlen($directory));
                $classes[$file] = $path;
            }
        }

        return $classes;
    }
}
