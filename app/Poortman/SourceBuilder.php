<?php
declare(strict_types=1);

namespace App\Poortman;

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

    public function buildFile(
        string $path,
        string $sourceDir,
        string $augmentionsDir,
        string $additionsDir,
        string $distDir
    ): void
    {
        // determine mode and file
        $modes = [
            'source'       => $sourceDir,
            'augmentation' => $augmentionsDir,
            'addition'     => $additionsDir
        ];
        $mode  = null;
        foreach ($modes as $m => $dir) {
            if (str_starts_with($path, $dir)) {
                $file = str_replace($dir, '', $path);
                $mode = $m;
                break;
            }
        }

        // if mode or file is not set the file cannot be processed
        if (!isset($file) || !$mode) {
            echo 'Skiped: ' . $path . "\n";

            return;
        }

        // copy for addition mode
        if ($mode === 'addition') {
            $buildPath = $distDir . DIRECTORY_SEPARATOR . $file;
            self::ensureDir($buildPath);
            copy($path, $buildPath);
            echo 'Copied: ' . $file . "\n";

            return;
        }

        // if an augmentation the source file should be present too
        if ($mode === 'augmentation' && !file_exists($sourceDir . DIRECTORY_SEPARATOR . $file)) {
            echo 'Warning: source file not found for ' . $file . "\n";

            return;
        }

        // if an source the augmentation file should be present too
        if ($mode === 'source' && !file_exists($augmentionsDir . DIRECTORY_SEPARATOR . $file)) {
            echo 'Warning: augmentation file not found for ' . $file . "\n";

            return;
        }

        echo 'Merging: ' . $file . "\n";
        $augmentationPath = $augmentionsDir . DIRECTORY_SEPARATOR . $file;
        $this->mergeClass(
            $file,
            $sourceDir . DIRECTORY_SEPARATOR . $file,
            file_exists($augmentationPath) ? $augmentationPath : null,
            $distDir
        );

        echo 'Merged, cleaning';
        passthru('vendor/bin/rector process --no-progress-bar --no-diffs ' . realpath($distDir . $file));
        passthru('vendor/bin/php-cs-fixer fix --quiet ' . realpath($distDir . $file));

        echo 'Done: ' . $file . "\n";
    }

    public static function ensureDir(string $path): string
    {
        $directory = dirname($path);
        if (!file_exists($directory)) {
            mkdir($directory, recursive: true);
        }

        return $directory;
    }

    public function mergeClass(
        string  $file,
        string  $sourcePath,
        ?string $augmentionPaths,
        string  $distDir
    ): void
    {
        // use the ClassMerger to combine the files
        $renamer  = new Renamer();
        $classMerger  = new ClassMerger($renamer);
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
            'shortArraySyntax' => true
        ]);
        $prettyCode    = $prettyPrinter->prettyPrintFile($mergedAst);

        // remove empty space before doc-comment
        $prettyCode    = preg_replace('/^<\?php([\r?\n]+)\/\*\*/', "<?php\n/**", $prettyCode);

        // prepare the build directory and save the result
        $buildPath = $distDir . DIRECTORY_SEPARATOR . $file;
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
        }
        catch (Error $error) {
            echo "Parse error: {$error->getMessage()} | file : {$file}\n";

            return null;
        }
    }

    public function build(
        string $sourceDir,
        string $augmentionsDir,
        string $additionsDir,
        string $distDir
    ): void
    {
        // get all (Livewire) source file paths
        $sourcePaths = self::getPaths($sourceDir);

        // get all (Magewire) augmentations file paths
        $augmentionPaths = self::getPaths($augmentionsDir);

        // get all (Magewire) additional file paths
        $additionalPaths = self::getPaths($additionsDir);

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
            echo "Warning: " . $file . " has an augmentation but no source!\n";
        }

        // warn if there is an augmentation without source
        foreach ($additionalPaths as $file => $sourcePath) {
            // warn if there is a source file for the addition!
            if (isset($sourcePaths[$file])) {
                echo "Warning: " . $file . " is an addition, but should be an augmentation on source!\n";
                continue;
            }
            $buildPath = $distDir . DIRECTORY_SEPARATOR . $file;
            self::ensureDir($buildPath);
            copy($sourcePath, $buildPath);
        }


        echo "Build complete, cleaning up with rector..\n";
        passthru('vendor/bin/rector');

        echo "Rector complete, cleaning up with CS-Fixer..\n";
        passthru('vendor/bin/php-cs-fixer fix');

    }

    public static function getPaths(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $classes  = [];
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isFile() && pathinfo($path)['extension'] = 'php') {
                $file           = substr($path, strlen($directory));
                $classes[$file] = $path;
            }
        }

        return $classes;
    }
}
