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

/**
 * Obtains information about trailers as seen on https://www.imdb.com/trailers/
 * https://www.imdb.com/trailers/
 * @Note thumbnail width and height are set in config, one setting for all methods!
 */
class Trailers extends MdbBase
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
        $this->newImageWidth = $this->config->thumbnailWidth;
        $this->newImageHeight = $this->config->thumbnailHeight;
    }

    /**
     * Get the latest trailers as seen on IMDb https://www.imdb.com/trailers/
     * @phpstan-return list<array{
     *     videoId: string|null,
     *     titleId: string|null,
     *     title: string|null,
     *     trailerCreateDate: string|null,
     *     trailerRuntime: int|null,
     *     playbackUrl: string|null,
     *     thumbnailUrl: string|null,
     *     releaseDate: string|null,
     *     contentType: string|null
     * }>
     */
    public function recentVideo(): array
    {
        $recentVideoResults = array();
        $query = <<<EOF
query RecentVideo {
  recentVideos(
    limit: 100
    queryFilter: {contentTypes: TRAILER}
  ) {
    videos {
      id
      createdDate
      primaryTitle {
        id
        titleText {
          text
        }
        releaseDate {
          displayableProperty {
            value {
              plainText
            }
          }
        }
        primaryImage {
          url
          width
          height
        }
      }
      runtime {
        value
      }
      name {
        value
      }
    }
  } 
}
EOF;
        $data = $this->graphql->query($query, "RecentVideo");
        if (!isset($data->recentVideos)) {
            return $recentVideoResults;
        }
        if (
            isset($data->recentVideos->videos) &&
            is_array($data->recentVideos->videos) &&
            count($data->recentVideos->videos) > 0
        ) {
            foreach ($data->recentVideos->videos as $edge) {
                $thumbUrl = null;
                $rawVideoId = isset($edge->id) && is_string($edge->id) ? $edge->id : null;
                $videoId = $rawVideoId !== null ? str_replace('vi', '', $rawVideoId) : null;

                if (
                    !empty($edge->primaryTitle->primaryImage->url) &&
                    isset($edge->primaryTitle->primaryImage->width, $edge->primaryTitle->primaryImage->height)
                ) {
                    $fullImageWidth = (int) $edge->primaryTitle->primaryImage->width;
                    $fullImageHeight = (int) $edge->primaryTitle->primaryImage->height;
                    $img = str_replace('.jpg', '', (string) $edge->primaryTitle->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }

                $rawTitleId = isset($edge->primaryTitle->id) && is_string($edge->primaryTitle->id) ? $edge->primaryTitle->id : null;

                $recentVideoResults[] = array(
                    'videoId' => $videoId,
                    'titleId' => $rawTitleId !== null ? str_replace('tt', '', $rawTitleId) : null,
                    'title' => isset($edge->primaryTitle->titleText->text) && is_string($edge->primaryTitle->titleText->text) ? $edge->primaryTitle->titleText->text : null,
                    'trailerCreateDate' => isset($edge->createdDate) && is_string($edge->createdDate) ? $edge->createdDate : null,
                    'trailerRuntime' => isset($edge->runtime->value) && is_numeric($edge->runtime->value) ? (int) $edge->runtime->value : null,
                    'playbackUrl' => !empty($videoId) ? 'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                    'thumbnailUrl' => $thumbUrl,
                    'releaseDate' => isset($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) && is_string($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) ? $edge->primaryTitle->releaseDate->displayableProperty->value->plainText : null,
                    'contentType' => isset($edge->name->value) && is_string($edge->name->value) ? $edge->name->value : null
                );
            }
        }
        return $recentVideoResults;
    }

    /**
     * Get trending trailers as seen on IMDb https://www.imdb.com/trailers/
     *
     * Array Structure Details:
     * - videoId: String without 'vi' prefix
     * - titleId: String without 'tt' prefix
     * - trailerCreateDate: ISO 8601 date string (e.g., "2024-11-17T13:16:18.708Z")
     * - trailerRuntime: Duration in seconds
     * - playbackUrl: Browser-only playback link
     * - thumbnailUrl: 140x207 image URL
     * - releaseDate: Date string (e.g., "December 4, 2024")
     * - contentType: Type description (e.g., "Trailer Season 1 [OV]")
     *
     * @phpstan-return list<array{
     *     videoId: string,
     *     titleId: string|null,
     *     title: string|null,
     *     trailerCreateDate: string|null,
     *     trailerRuntime: int|null,
     *     playbackUrl: string|null,
     *     thumbnailUrl: string|null,
     *     releaseDate: string|null,
     *     contentType: string|null
     * }>
     */
    public function trendingVideo(): array
    {
        $trendingVideoResults = array();
        $query = <<<EOF
query TrendingVideo {
  trendingTitles(limit: 250) {
    titles {
      id
      titleText {
        text
      }
      releaseDate {
        displayableProperty {
          value {
            plainText
          }
        }
      }
      primaryImage {
        url
        width
        height
      }
      latestTrailer {
        createdDate
        id
        runtime {
          value
        }
        name {
          value
        }
      }
      
    }
  } 
}
EOF;
        $data = $this->graphql->query($query, "TrendingVideo");
        if (!isset($data->trendingTitles)) {
            return $trendingVideoResults;
        }
        if (
            isset($data->trendingTitles->titles) &&
            is_array($data->trendingTitles->titles) &&
            count($data->trendingTitles->titles) > 0
        ) {
            foreach ($data->trendingTitles->titles as $edge) {
                $thumbUrl = null;
                $rawVideoId = isset($edge->latestTrailer->id) && is_string($edge->latestTrailer->id) ? $edge->latestTrailer->id : null;
                $videoId = $rawVideoId !== null ? str_replace('vi', '', $rawVideoId) : null;

                if ($videoId === null || $videoId === '') {
                    continue;
                }

                if (
                    !empty($edge->primaryImage->url) &&
                    isset($edge->primaryImage->width, $edge->primaryImage->height)
                ) {
                    $fullImageWidth = (int) $edge->primaryImage->width;
                    $fullImageHeight = (int) $edge->primaryImage->height;
                    $img = str_replace('.jpg', '', (string) $edge->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }

                $rawTitleId = isset($edge->id) && is_string($edge->id) ? $edge->id : null;

                $trendingVideoResults[] = array(
                    'videoId' => $videoId,
                    'titleId' => $rawTitleId !== null ? str_replace('tt', '', $rawTitleId) : null,
                    'title' => isset($edge->titleText->text) && is_string($edge->titleText->text) ? $edge->titleText->text : null,
                    'trailerCreateDate' => isset($edge->latestTrailer->createdDate) && is_string($edge->latestTrailer->createdDate) ? $edge->latestTrailer->createdDate : null,
                    'trailerRuntime' => isset($edge->latestTrailer->runtime->value) && is_numeric($edge->latestTrailer->runtime->value) ? (int) $edge->latestTrailer->runtime->value : null,
                    'playbackUrl' => 'https://www.imdb.com/video/vi' . $videoId . '/',
                    'thumbnailUrl' => $thumbUrl,
                    'releaseDate' => isset($edge->releaseDate->displayableProperty->value->plainText) && is_string($edge->releaseDate->displayableProperty->value->plainText) ? $edge->releaseDate->displayableProperty->value->plainText : null,
                    'contentType' => isset($edge->latestTrailer->name->value) && is_string($edge->latestTrailer->name->value) ? $edge->latestTrailer->name->value : null
                );
            }
        }
        return $trendingVideoResults;
    }
}
