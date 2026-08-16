<?php

/**
 * imdbGraphQLPHP
 * This program is free software; you can redistribute and/or modify it
 * under the terms of the GNU General Public License (see doc/LICENSE)
 */

declare(strict_types=1);

namespace Imdb;

use Imdb\Config;
use Imdb\GraphQL;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information
 * @created by 2002-2004 by Giorgos Giagas and 2004-2009 by Itzchak Rehberg and IzzySoft
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Tom Boothman
 * @author Ed
 * @author jcv
 */
class MdbBase extends Config
{
    protected CacheInterface $cache;
    protected LoggerInterface $logger;
    protected Config $config;
    protected GraphQL $graphql;

    /** @var string 7 or 8 digit identifier for this person or title */
    protected string $imdbID;
    public string $version = '3.0';

    /**
     * @param Config|null $config OPTIONAL override default config
     * @param LoggerInterface|null $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface|null $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        $this->config = $config ?? $this;
        $this->logger = $logger ?? ($this->debug ? new Logger($this->debug) : new NullLogger());
        $this->cache = $cache ?? new Cache($this->config, $this->logger);
        $this->graphql = new GraphQL($this->cache, $this->logger, $this->config);
    }

    /**
     * Retrieve the IMDB ID
     * @return string id IMDBID currently used
     */
    public function imdbid()
    {
        return $this->imdbID;
    }

    /**
     * Set and validate the IMDb ID
     * @param string $id IMDb ID
     */
    protected function setid(string $id): void
    {
        if (is_numeric($id)) {
            $this->imdbID = str_pad($id, 7, '0', STR_PAD_LEFT);
        } elseif (preg_match("/(?:nm|tt)(\d{7,8})/", $id, $matches) === 1) {
            $this->imdbID = $matches[1];
        } else {
            $this->debugScalar("<BR>setid: Invalid IMDB ID '$id'!<BR>");
        }
    }

    #---------------------------------------------------------[ Debug helpers ]---
    protected function debugScalar(string $scalar): void
    {
        $this->logger->error($scalar);
    }
}
