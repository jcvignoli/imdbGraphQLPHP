<?php

#############################################################################
# PHP GraphQL API                                             (c) Tboothman #
# written by Tom Boothman                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information through GraphQL
 * @author Tom Boothman
 * @author Ed (duck7000)
 * @copyright (c) 2002-2023 by Tom Boothman
 */
class GraphQL
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * GraphQL constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct($cache, $logger, $config)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function query($query, $qn = null, $variables = array())
    {
        $key = "gql.$qn." . ($variables ? json_encode($variables) : '') . md5($query) . ".json";
        $fromCache = $this->cache->get($key);
        if ($fromCache != null) {
            return json_decode($fromCache);
        }
        // strip spaces from query due to hosters request limit
        $fullQuery = implode("\n", array_map('trim', explode("\n", $query)));
        $result = $this->doRequest($fullQuery, $qn, $variables);
        $this->cache->set($key, json_encode($result));
        return $result;
    }

    /**
     * @param string $query
     * @param string|null $queryName
     * @param array $variables
     * @return \stdClass
     */
    private function doRequest($query, $queryName = null, $variables = array())
    {
        $request = new Request('https://api.graphql.imdb.com/', $this->config);
        $request->addHeaderLine("Content-Type", "application/json");
        $request->addHeaderLine("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36");
        $request->addHeaderLine("x-imdb-client-name", "imdb-web-next-localized");

        if ($this->config->useLocalization === true) {
            $request->addHeaderLine("x-imdb-user-country", !empty($this->config->country) ? $this->config->country : "US");
            $request->addHeaderLine("X-Imdb-User-Language", !empty($this->config->language) ? $this->config->language : "US");
        }
        $payload = json_encode(
            array(
                'operationName' => $queryName,
                'query' => $query,
                'variables' => (object) $variables // apparently $variables needs to object
            )
        );
        $this->logger->info("[GraphQL] Requesting {$queryName}");
        $request->post($payload);
        if (200 == $request->getStatus()) {
            $responseObj = json_decode($request->getResponseBody());

            // Ensure response contains expected data property
            if (isset($responseObj->data)) {
                return $responseObj->data;
            }

            $this->logger->error('[GraphQL] GraphQL Error or Missing Data for ' . $queryName . ' Response: ' . $request->getResponseBody());
            return new \stdClass();
        } else {
            $this->logger->error('[GraphQL] Failed to retrieve query ' . $queryName . ' Response headers: ' . implode(' ', $request->getLastResponseHeaders()) . ' Response body: ' . $request->getResponseBody());
            if ($this->config->throwHttpExceptions) {
                // Some classes don't use imdbId like Chart, Trailers, Calendar and KeywordSearch
                $imdbErrorID = !isset($variables['id']) ? 'n/a' : $variables['id'];
                $this->logger->error("Failed to retrieve query [{$queryName}] , IMDb id [{$imdbErrorID}]");
            }
            return new \stdClass();
        }
    }
}
