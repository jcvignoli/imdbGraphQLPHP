<?php

/**
 * imdbGraphQLPHP
 * This program is free software; you can redistribute and/or modify it
 * under the terms of the GNU General Public License (see doc/LICENSE)
 */

declare(strict_types=1);

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Obtains information about trailers as seen on https://www.imdb.com/trailers/
 * https://www.imdb.com/trailers/
 * @Note thumbnail width and height are set in config, one setting for all methods!
 */
class News extends MdbBase
{
    protected readonly Image $imageFunctions;
    protected readonly int $newImageWidth;
    protected readonly int $newImageHeight;

    /**
     * @param Config|null $config OPTIONAL override default config
     * @param LoggerInterface|null $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface|null $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->imageFunctions = new Image();
        $this->newImageWidth = 500;
        $this->newImageHeight = 281;
    }

    /**
     * Get the latest news for Movie, tv, top, celebrity or indie
     * Thumbnail size: fixed 500x281
     * max 250 items are returned, this covers about a year
     *
     * @param string $listType determines which list to return
     *                         possible values: CELEBRITY, INDIE, MOVIE, TOP, TV
     *
     * @return array<int, array{id: string|null, title: string|null, author: string|null, date: string|null, extUrl: string|null, exturlLabel: string|null, textHtml: string|null, textText: string|null, thumbnailUrl: string|null}>
     */
    public function newsList(string $listType = "MOVIE"): array
    {
        $newsListItems = array();
        $query = <<<EOF
query News{
  news(first: 250, category: $listType) {
    edges {
      node {
        articleTitle {
          plainText
        }
        byline
        date
        externalUrl
        id
        image {
          url
          width
          height
        }
        source {
          homepage {
            label
          }
        }
        text {
          plainText
          plaidHtml
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "News");
        if (!isset($data->news)) {
            return $newsListItems;
        }
        if (
            isset($data->news->edges) &&
            is_array($data->news->edges) &&
            count($data->news->edges) > 0
        ) {
            foreach ($data->news->edges as $edge) {
                $thumbUrl = null;
                if (!empty($edge->node->image->url) && isset($edge->node->image->width, $edge->node->image->height)) {
                    $fullImageWidth = $edge->node->image->width;
                    $fullImageHeight = $edge->node->image->height;
                    $img = str_replace('.jpg', '', $edge->node->image->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                $newsListItems[] = array(
                    'id' => isset($edge->node->id) ?
                                str_replace('ni', '', $edge->node->id) : null,
                    'title' => isset($edge->node->articleTitle->plainText) ?
                                    $edge->node->articleTitle->plainText : null,
                    'author' => isset($edge->node->byline) ?
                                    $edge->node->byline : null,
                    'date' => isset($edge->node->date) ?
                                    $edge->node->date : null,
                    'extUrl' => isset($edge->node->externalUrl) ?
                                    $edge->node->externalUrl : null,
                    'exturlLabel' => isset($edge->node->source->homepage->label) ?
                                        $edge->node->source->homepage->label : null,
                    'textHtml' => isset($edge->node->text->plaidHtml) ?
                                        $edge->node->text->plaidHtml : null,
                    'textText' => isset($edge->node->text->plainText) ?
                                        $edge->node->text->plainText : null,
                    'thumbnailUrl' => $thumbUrl
                );
            }
        }
        return $newsListItems;
    }
}
