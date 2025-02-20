<?php

declare(strict_types=1);

namespace App\Portman;

use App\Portman\Configuration\ConfigurationException;
use App\Portman\Configuration\Data\Directory;
use Illuminate\Console\Command;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Webmozart\Glob\Glob;

class SourceBuilder
{
    private array $renameClasses = [];

    public function buildFile(string $path, Command $command): void
    {
        $configDirectories = portman_config_data()->directories;
        $outputDir = $configDirectories->output;

        // determine mode and file
        $modes = [
            'source'       => $configDirectories->source,
            'augmentation' => $configDirectories->augmentation,
            'addition'     => $configDirectories->additional,
        ];
        $mode  = null;
        foreach ($modes as $m => $directories) {
            foreach ($directories as $dir) {
                if (str_starts_with($path, $dir->path)) {
                    $file = str_replace($dir->path, '', $path);
                    $mode = $m;
                    break;
                }
            }
            if (!is_null($mode)) {
                break;
            }
        }

        // if mode or file is not set the file cannot be processed
        if (!isset($file) || !$mode) {
            $command->warn('Skiped: [' . $path . ']');

            return;
        }

        // copy for addition mode
        if ($mode === 'addition') {
            $buildPath = $outputDir->path . DIRECTORY_SEPARATOR . $file;
            ensure_dir($buildPath);
            copy($path, $buildPath);
            $command->line('Copied: [' . $file . ']');

            return;
        }

        // if an augmentation the source file should be present too
        $sourceFilePath = $configDirectories->findFilePathInSource($file);
        if ($mode === 'augmentation' && is_null($sourceFilePath)) {
            $command->warn('Warning: source file not found for [' . $file . ']');

            return;
        }

        // if an source the augmentation file should be present too
        $augmentationFilePath = $configDirectories->findFilePathInAugmentation($file);
        if ($mode === 'source' && is_null($augmentationFilePath)) {
            $command->warn('Warning: augmentation file not found for [' . $file . ']');

            return;
        }

        $command->info('Merging: [' . $file . ']');
        $this->mergeClass(
            $file,
            $sourceFilePath,
            $augmentationFilePath,
            $outputDir->path
        );

        $command->line('Merged, cleaning');
        if (portman_config('post-processors.rector', false)) {
            passthru('vendor/bin/rector process --no-progress-bar --no-diffs ' . realpath($outputDir->path . $file));
        }

        if (portman_config('post-processors.php-cs-fixer', false)) {
            passthru('vendor/bin/php-cs-fixer fix --quiet ' . realpath($outputDir->path . $file));
        }

        $command->info('Done: [' . $file . ']');
    }

    public function mergeClass(
        string  $file,
        string  $sourcePath,
        ?string $augmentionPaths,
        string  $outputDir
    ): void
    {
        // use the ClassMerger to combine the files
        $renamer     = app(Renamer::class);
        $classMerger = new ClassMerger($renamer);
        if ($augmentionPaths) {
            // Traverse the augmentation AST to collect the class structure in classMerger
            $astAugmetation        = $this->parseFile($augmentionPaths);
            $augmentationTraverser = new NodeTraverser();
            $augmentationTraverser->addVisitor($classMerger);
            $classMerger->startCollecting();
            $augmentationTraverser->traverse($astAugmetation);
            $this->renameClasses = array_merge($this->renameClasses, $classMerger->getClasses());
        }
        // Traverse the source AST and apply the class structure of the augmentation collected before
        $astSource       = $this->parseFile($sourcePath);
        $sourceTraverser = new NodeTraverser();
        $sourceTraverser->addVisitor($classMerger);
        $classMerger->startMerging();
        $mergedAst = $sourceTraverser->traverse($astSource);
        $className = $classMerger->getClassName();
        $mergedAst = $classMerger->finalize($mergedAst);

        // rename file if classname changed
        ['filename' => $filename, 'dirname' => $dirname] = pathinfo($file);
        if ($className && $filename !== $className) {
            $file = $dirname . $className . '.php';
        }

        // prepare the pretty printer
        $prettyPrinter = new PrettyPrinter\Standard([
            'phpVersion'       => PhpVersion::fromComponents(8, 2),
            'shortArraySyntax' => true,
        ]);
        $prettyCode    = $prettyPrinter->prettyPrintFile($mergedAst);

        // remove empty space before doc-comment
        $prettyCode = preg_replace('/^<\?php([\r?\n]+)\/\*\*/', "<?php\n/**", $prettyCode);

        // prepare the build directory and save the result
        $buildPath = $outputDir . DIRECTORY_SEPARATOR . $file;
        ensure_dir($buildPath);
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
        }
        catch (Error $error) {
            echo "Parse error: {$error->getMessage()} | file : {$file}\n";

            return null;
        }
    }

    public function build(Command $command): void
    {
        $directories = portman_config_data()->directories;
        $outputDir = $directories->output;

        // get all source file paths
        $sourcePaths = $directories->getAllFilePathsOfSource();

        // get all augmentations file paths
        $augmentionPaths = $directories->getAllFilePathsOfAugmentation();

        // get all additional file paths
        $additionalPaths = $directories->getAllFilePathsOfAdditional();

        // patch all source files with the available additions
        foreach ($sourcePaths as $file => $sourcePath) {
            $this->mergeClass(
                $file,
                $sourcePath,
                $augmentionPaths[$file] ?? null,
                $outputDir->path
            );
        }

        // warn if there is an augmentation without source
        foreach (array_diff_key($augmentionPaths, $sourcePaths) as $file => $sourcePath) {
            $command->warn('Warning: ' . $file . ' has an augmentation but no source!');
        }

        // warn if there is an augmentation without source
        foreach ($additionalPaths as $file => $sourcePath) {
            // warn if there is a source file for the addition!
            if (isset($sourcePaths[$file])) {
                $command->warn('Warning: ' . $file . ' is an addition, but should be an augmentation on source!');

                continue;
            }
            $buildPath = $outputDir->path . DIRECTORY_SEPARATOR . $file;
            ensure_dir($buildPath);
            copy($sourcePath, $buildPath);
        }

        $command->info('Build complete');
        if (portman_config('post-processors.rector', false)) {
            $command->info('Running Rector');
            passthru('vendor/bin/rector');
            $command->info('Running Rector, complete');
        }
        if (portman_config('post-processors.php-cs-fixer', false)) {
            $command->info('Running CS-Fixer');
            passthru('vendor/bin/php-cs-fixer fix');
            $command->info('Running CS-Fixer, complete');
        }
    }



}
