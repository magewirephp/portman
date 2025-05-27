<?php

namespace App\Updater\Strategy;

use Humbug\SelfUpdate\Exception\HttpRequestException;
use Humbug\SelfUpdate\Exception\JsonParsingException;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use LaravelZero\Framework\Components\Updater\Strategy\StrategyInterface;

class GithubReleasesStrategy extends \Humbug\SelfUpdate\Strategy\GithubStrategy implements StrategyInterface
{
    private string $remoteVersion;
    private string $remoteUrl;

    public function download(Updater $updater)
    {
        /** Switch remote request errors to HttpRequestExceptions */
        set_error_handler([$updater, 'throwHttpRequestException']);
        $result = file_get_contents($this->remoteUrl);
        restore_error_handler();
        if ($result === false) {
            throw new HttpRequestException(sprintf(
                'Request to URL failed: %s',
                $this->remoteUrl
            ));
        }

        file_put_contents($updater->getTempPharFile(), $result);
    }


    public function getCurrentRemoteVersion(Updater $updater)
    {
        /** Switch remote request errors to HttpRequestExceptions */
        set_error_handler([$updater, 'throwHttpRequestException']);
        $packageUrl = $this->getApiUrl();
        $package    = json_decode(file_get_contents($packageUrl), true);
        restore_error_handler();

        if ($package === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonParsingException(
                'Error parsing JSON package data'
                . (function_exists('json_last_error_msg') ? ': ' . json_last_error_msg() : '')
            );
        }

        $versions      = array_column($package['packages'][$this->getPackageName()], 'version');
        $versionParser = new VersionParser($versions);
        if ($this->getStability() === self::STABLE) {
            $this->remoteVersion = $versionParser->getMostRecentStable();
        }
        elseif ($this->getStability() === self::UNSTABLE) {
            $this->remoteVersion = $versionParser->getMostRecentUnstable();
        }
        else {
            $this->remoteVersion = $versionParser->getMostRecentAll();
        }

        /**
         * Setup remote URL if there's an actual version to download.
         */
        if (!empty($this->remoteVersion)) {
            $remoteVersionPackages = array_filter($package['packages'][$this->getPackageName()], function (array $package) {
                return $package['version'] === $this->remoteVersion;
            });
            $chosenVersion         = reset($remoteVersionPackages);

            $this->remoteUrl = $this->getDownloadUrl($chosenVersion);
        }

        return $this->remoteVersion;
    }

    /** @param array<mixed, mixed> $package */
    protected function getDownloadUrl(array $package)
    {
        $baseUrl = preg_replace(
            '{\.git$}',
            '',
            $package['source']['url']
        );

        return sprintf(
            '%s/releases/download/%s/%s',
            $baseUrl,
            'v' . $this->remoteVersion,
            'portman'
        );
    }
}