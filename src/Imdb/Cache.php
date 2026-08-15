<?php

#############################################################################
# imdbphp6                                                (c) Ed (duck7000) #
# written by Ed                                                             #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################
declare(strict_types=1);

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * File caching
 * Caches files to disk in cacheDir optionally gzipping if cacheUseZip
 */
class Cache implements CacheInterface
{
    /**
     * Cache constructor.
     * @param Config $config
     * @param LoggerInterface $logger
     * @throws Exception
     */
    public function __construct(protected Config $config, protected LoggerInterface $logger)
    {

        if (($this->config->cacheUse || $this->config->cacheStore) && !is_dir($this->config->cacheDir)) {
            @mkdir($this->config->cacheDir, 0755, true);
            @mkdir($this->config->photoroot, 0755, true);
            if (!is_dir($this->config->cacheDir)) {
                $this->logger->critical("[Cache] Configured cache directory [{$this->config->cacheDir}] does not exist!");
                throw new Exception("[Cache] Configured cache directory [{$this->config->cacheDir}] does not exist!");
            }
        }
        if ($this->config->cacheStore && !is_writable($this->config->cacheDir)) {
            $this->logger->critical("[Cache] Configured cache directory [{$this->config->cacheDir}] lacks write permission!");
            throw new Exception("[Cache] Configured cache directory [{$this->config->cacheDir}] lacks write permission!");
        }

        // @TODO add a limit on how frequently a purge can occur
        $this->purge();
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        if (!$this->config->cacheUse) {
            return $default;
        }

        $cleanKey = $this->sanitiseKey($key);
        $fname = $this->config->cacheDir . '/' . $cleanKey;
        if (!file_exists($fname)) {
            $this->logger->debug("[Cache] Cache miss for [$key]");
            return $default;
        }

        $this->logger->debug("[Cache] Cache hit for [$key]");
        if ($this->config->cacheUseZip) {
            $content = file_get_contents('compress.zlib://' . $fname); // This can read uncompressed files too
            if (!$content) {
                return $default;
            }
            if ($this->config->cacheConvertZip) {
                @$fp = fopen($fname, "r");
                $zipchk = fread($fp, 2);
                fclose($fp);
                if (!($zipchk[0] === chr(31) && $zipchk[1] === chr(139))) { //checking for zip header
                    /* converting on access */
                    file_put_contents('compress.zlib://' . $fname, $content);
                }
            }
            return $content;
        } else { // no zip
            return file_get_contents($fname);
        }
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        if (!$this->config->cacheStore) {
            return false;
        }

        $cleanKey = $this->sanitiseKey($key);
        $fname = $this->config->cacheDir . '/' . $cleanKey;
        $this->logger->debug("[Cache] Writing key [$key] to [$fname]");
        if ($this->config->cacheUseZip) {
            $fp = gzopen($fname, "w");
            gzputs($fp, $value);
            gzclose($fp);
        } else { // no zip
            file_put_contents($fname, $value);
        }

        return true;
    }

    /**
     * This method looks for files older than the cache_expire set in the
     * \Imdb\Config and removes them
     */
    public function purge(): void
    {
        if (!$this->config->cacheStore || $this->config->cacheExpire === 0) {
            return;
        }

        $cacheDir = $this->config->cacheDir;
        $this->logger->debug("[Cache] Purging old cache entries");

        $thisdir = dir($cacheDir);
        $now = time();
        while ($file = $thisdir->read()) {
            if ($file !== "." && $file !== ".." && $file !== ".placeholder") {
                $fname = $cacheDir . '/' . $file;
                if (is_dir($fname)) {
                    continue;
                }
                $mod = filemtime($fname);
                if ($mod && ($now - $mod > $this->config->cacheExpire)) {
                    unlink($fname);
                }
            }
        }
        $thisdir->close();
    }

    /**
     * Replace characters the OS won't like using with the filesystem
     */
    protected function sanitiseKey(string $key): string
    {
        return str_replace(array('/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'), '.', $key);
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @param iterable<mixed, mixed> $keys
     * @param mixed $default Default value to return for keys that do not exist.
     * @return iterable<string, mixed>
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $default;
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @return bool
     */
    public function clear(): bool
    {
        return false;
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @return bool
     */
    public function delete($key): bool
    {
        return false;
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @param iterable<mixed, mixed> $keys
     * @return bool
     */
    public function deleteMultiple($keys): bool
    {
        return false;
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return false;
    }

    /**
     * Some empty functions so we match the interface. These will never be used
     * @param iterable<mixed, mixed> $values
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item.
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }
}
