<?php

/**
 * imdbGraphQLPHP
 * This program is free software; you can redistribute and/or modify it
 * under the terms of the GNU General Public License (see doc/LICENSE)
 */

declare(strict_types=1);

namespace Imdb;

use CurlHandle;
use Imdb\Config;

/**
 * The request class
 * Here we emulate a browser accessing the IMDB site. You don't need to
 * call any of its method directly - they are rather used by the IMDB classes.
 */
class Request
{
    /**
     * @var CurlHandle cURL handle instance.
     */
    private CurlHandle $ch;

    /**
     * @var string|false Raw HTML or response payload, or false if request failed or was not sent.
     */
    private string|false $page = false;

    /**
     * @var array<int, string> List of HTTP request headers to send.
     */
    private array $requestHeaders = [];

    /**
     * @var array<int, string> Raw response header lines captured during execution.
     */
    private array $responseHeaders = [];

    /**
     * @var Config Configuration object containing timeout and client settings.
     */
    private Config $config;

    /**
     * Initializes a new HTTP request targeting a specified URL.
     *
     * @param string $url Target endpoint URL.
     * @param Config $config Configuration options (e.g. timeout limits).
     */
    public function __construct(string $url, Config $config)
    {
        $this->config = $config;
        $this->ch = curl_init($url);

        curl_setopt($this->ch, CURLOPT_ACCEPT_ENCODING, "");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, [$this, "callbackCurlOptHeaderFunction"]);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->config->curloptTimeout);
    }

    /**
     * Adds a custom HTTP header line to the outbound request.
     *
     * @param string $name Header field name (e.g., 'Accept-Language').
     * @param string $value Header field value (e.g., 'en-US,en;q=0.9').
     */
    public function addHeaderLine(string $name, string $value): void
    {
        $this->requestHeaders[] = "{$name}: {$value}";
    }

    /**
     * Sends an HTTP POST request with the specified body content.
     *
     * @param string|array<mixed> $content Raw post body string or key-value payload array.
     * @return bool True if the request executed successfully, false on failure.
     */
    public function post(string|array $content): bool
    {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $content);

        return $this->sendRequest();
    }

    /**
     * Executes the HTTP request.
     *
     * @return bool True if response content was received, false on cURL execution failure.
     */
    public function sendRequest(): bool
    {
        $this->responseHeaders = [];
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);

        $result = curl_exec($this->ch);
        $this->page = is_string($result) ? $result : false;

        return $this->page !== false;
    }

    /**
     * Gets the raw response body from the last executed request.
     *
     * @return string|false Raw HTML string, or false if the request failed or hasn't run.
     */
    public function getResponseBody(): string|false
    {
        return $this->page;
    }

    /**
     * Extracts a specific header value from the response.
     *
     * Performs a case-insensitive match against response header line prefixes.
     *
     * @param string $header Header field name to look up (e.g., 'Content-Type').
     * @return string Trimmed header value, or an empty string if not present.
     */
    public function getResponseHeader(string $header): string
    {
        $prefix = strtolower($header) . ':';
        $headers = $this->getLastResponseHeaders();

        foreach ($headers as $head) {
            if (str_starts_with(strtolower($head), $prefix)) {
                $parts = explode(':', $head, 2);
                return isset($parts[1]) ? trim($parts[1]) : '';
            }
        }

        return '';
    }

    /**
     * Gets the HTTP response status code for the last request.
     *
     * @return int|null HTTP status code (e.g., 200, 404), or null if the request failed/unexecuted.
     */
    public function getStatus(): ?int
    {
        $statusCode = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($statusCode === 0) {
            return null;
        }

        return $statusCode;
    }

    /**
     * Gets all raw response header lines captured from the last request.
     *
     * @return array<int, string> Sequential list of raw header strings.
     */
    public function getLastResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * cURL callback function to collect response header lines as they arrive.
     *
     * @param CurlHandle $ch The cURL handle instance.
     * @param string $str Header line string received.
     * @return int Length of the string processed.
     */
    private function callbackCurlOptHeaderFunction(CurlHandle $ch, string $str): int
    {
        $len = strlen($str);
        if ($len > 0) {
            $this->responseHeaders[] = $str;
        }

        return $len;
    }
}
