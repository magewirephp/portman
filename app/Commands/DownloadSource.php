<?php

namespace App\Commands;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositorySet;
use Composer\Repository\WritableArrayRepository;
use DirectoryIterator;
use FilesystemIterator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

class DownloadSource extends Command
{
    protected $signature = 'download-source';
    protected $description = 'Download/update the source libraries';

    public function handle()
    {
        $this->info("Loading composer repository information");
        // filter sources without composer config
        $composerSources = collect(portman_config('directories.source', []))->filter(function ($source) {
            if (!isset($source['composer'])) {
                return false;
            }
            if (!is_array($source['composer']) || !isset($source['composer']['name']) || !isset($source['composer']['version'])) {
                return false;
            }
            if (!is_string($source['composer']['name']) || !is_string($source['composer']['version'])) {
                return false;
            }

            return true;
        });

        // create a repository from packagist package info
        $repoSet         = new RepositorySet();
        $versionParser   = new VersionParser();
        $composerSources->each(function ($source) use ($versionParser, $repoSet) {
            $packageName = $source['composer']['name'];
            $this->info(" - {$packageName}");
            $url         = "https://repo.packagist.org/p2/{$packageName}.json";
            $jsonData    = file_get_contents($url);
            $packageData = json_decode($jsonData, true);
            $loader      = new ArrayLoader($versionParser);
            if (isset($packageData['packages'][$packageName])) {
                $versions      = $packageData['packages'][$packageName];
                $latestVersion = reset($versions);
                $packages      = collect($versions)->transform(function ($config) use ($latestVersion, $loader) {
                    $config = [...$latestVersion, ...$config];

                    return $loader->load($config);
                });
                $repo = new WritableArrayRepository($packages->toArray());
                $repoSet->addRepository($repo);
            }
        });

        $this->info("Starting to download source code for {$composerSources->count()} packages");
        // select package version and download
        $versionSelector = new VersionSelector($repoSet);
        $composerSources->each(function ($source, $path) use ($versionSelector) {
            $packageName = $source['composer']['name'];
            $this->info("Processing {$packageName}");
            $version     = $source['composer']['version'];
            $latestVersion = $versionSelector->findBestCandidate($packageName);
            $bestVersion = $nonLockVersion = $versionSelector->findBestCandidate($packageName, $version);
            if (isset($source['composer']['version-lock']) && is_string($source['composer']['version-lock'])) {
                $version = $source['composer']['version-lock'];
                $bestVersion = $versionSelector->findBestCandidate($packageName, $version);
            }
            if(!$bestVersion){
                $this->error(" - could not find version {$version}");
               return;
            }
            $this->info(" - using {$bestVersion->getPrettyVersion()}");
            if($nonLockVersion && $nonLockVersion->getVersion()!==$bestVersion->getVersion()){
                $this->warn("   Latest non-lock version: {$nonLockVersion->getPrettyVersion()}");
            }
            if($latestVersion->getVersion()!==$bestVersion->getVersion()){
                $this->warn("   Latest package version: {$latestVersion->getPrettyVersion()}");
            }
            $this->info(" - downloading");

            // Download the latest zip file
            $temporaryDirectory = (new TemporaryDirectory())->create();
            $zipFile            = $temporaryDirectory->path('package.zip');
            $response           = Http::get($bestVersion->getDistUrl());
            file_put_contents($zipFile, $response->getBody());

            $extracted = $temporaryDirectory->path('extracted');
            $this->info(" - extracting");
            // Extract the zip file
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($extracted);
                $zip->close();
            }
            else {
                $this->error('Could not extract the zip file.');
            }

            $this->info(" - determaining base-path");
            // check for lone root directory and extract from there
            $dircount  = 0;
            $directory = null;
            foreach (new DirectoryIterator($extracted) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }
                if ($fileInfo->isDir()) {
                    $dircount++;
                    $directory = $fileInfo->getRealPath();
                }
            }
            if (!$directory || $dircount !== 1) {
                $directory = $extracted;
            }

            // add base-path from config
            if (isset($source['composer']['base-path']) && is_string($source['composer']['base-path'])) {
                $directory .= DIRECTORY_SEPARATOR . trim(trim($source['composer']['base-path']), DIRECTORY_SEPARATOR);
            }

            // remove current source directory
            $path      = trim(trim($path), DIRECTORY_SEPARATOR);
            $destinationDirectory = realpath($path);

            $this->info(" - moving source-code");
            $this->deleteDirectory($destinationDirectory);

            // rename extracted directory to $destinationDirectory
            rename($directory, $destinationDirectory);

            // cleanup
            $temporaryDirectory->delete();
        });
        $this->info("Done");
    }

    protected function deleteDirectory(string $path): bool
    {
        try {
            if (is_link($path)) {
                return unlink($path);
            }

            if (!file_exists($path)) {
                return true;
            }

            if (!is_dir($path)) {
                return unlink($path);
            }

            foreach (new FilesystemIterator($path) as $item) {
                if (!$this->deleteDirectory((string)$item)) {
                    return false;
                }
            }

            /*
             * By forcing a php garbage collection cycle using gc_collect_cycles() we can ensure
             * that the rmdir does not fail due to files still being reserved in memory.
             */
            gc_collect_cycles();

            return rmdir($path);
        }
        catch (Throwable) {
            return false;
        }
    }

}
