<?php

/**
 * imdbGraphQLPHP
 * This program is free software; you can redistribute and/or modify it
 * under the terms of the GNU General Public License (see doc/LICENSE)
 */

declare(strict_types=1);

namespace Imdb;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Debug logging. Echos html to the page
 * Only used when `\Imdb\Config::debug` is true
 */
class Logger extends AbstractLogger
{
    public function __construct(protected bool $enabled = true)
    {
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array<mixed> $context
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        if ($this->enabled) {
            $replace = array();
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = "<pre>" . print_r($val, true) . "</pre>";
            }

            $message = strtr((string) $message, $replace);

            switch ($level) {
                case 'emergency':
                case 'alert':
                case 'critical':
                case 'error':
                case 'warning':
                    $colour = '#ff0000';
                    break;
                default:
                    $colour = '';
                    break;
            }
            echo "<b><font color='$colour'>[$level] $message</font></b><br>\n";
        }
    }
}
