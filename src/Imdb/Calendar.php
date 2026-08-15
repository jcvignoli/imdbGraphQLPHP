<?php

#############################################################################
# imdbGraphQLPHP comingSoon                                                 #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Obtains information about upcoming movie releases as seen on IMDb
 * https://www.imdb.com/calendar
 * @author Ed (github user: duck7000)
 */
class Calendar extends MdbBase
{
    protected Image $imageFunctions;
    protected int $newImageWidth;
    protected int $newImageHeight;

    /**
     * @param Config|null $config OPTIONAL override default config
     * @param LoggerInterface|null $logger OPTIONAL override default logger `\Lumiere\Vendor\Imdb\Logger` with a custom one
     * @param CacheInterface|null $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->imageFunctions = new Image();
        $this->newImageWidth = $this->config->calendarThumbnailWidth;
        $this->newImageHeight = $this->config->calendarThumbnailHeight;
    }

    /**
     * Get upcoming movie releases as seen on IMDb
     * @param string $region This defines which country's releases are returned like DE, NL, US
     * @param string $type This defines which type is returned, MOVIE, TV or TV_EPISODE
     * @param int $startDateOverride This defines the startDate override like +3 or -5 of default todays day
     * @param int $endDateOverride This defines the endDate override like +3 or -5, default + 1 year
     * @param string $filter This defines if disablePopularityFilter is set or not, set to false shows all releases,
     * true only returns populair releases so less results within the given date span
     * there seems to be a limit of 100 titles but i did get more titles so i really don't know
     *
     * @phpstan-return array<string, list<array{
     *     title: string,
     *     imdbid: string,
     *     genres: list<string>,
     *     cast: list<string>,
     *     imgUrl: string
     * }>>
     */
    public function comingSoon(string $region = "US", string $type = "MOVIE", int $startDateOverride = 0, int $endDateOverride = 0, string $filter = "true"): array
    {
        $calendar = array();
        $startDate = date("Y-m-d");
        if ($startDateOverride !== 0) {
            $startDate = date('Y-m-d', strtotime($startDateOverride . ' day', strtotime($startDate)));
        }
        $futureDate = $endDateOverride !== 0 ? gmdate('Y-m-d', strtotime('+ ' . $endDateOverride . ' days', strtotime($startDate))) : date('Y-m-d', strtotime('+1 year', strtotime($startDate)));

        $query = <<<EOF
query ComingSoon {
    comingSoon(
      first: 9999
      comingSoonType: $type
      disablePopularityFilter: $filter
      regionOverride: "$region"
      releasingOnOrAfter: "$startDate"
      releasingOnOrBefore: "$futureDate"
      sort: {sortBy: RELEASE_DATE, sortOrder: ASC}) {
    edges {
      node {
        titleText {
          text
        }
        id
        releaseDate {
          day
          month
          year
        }
        titleGenres {
          genres {
            genre {
              text
            }
          }
        }
        principalCredits(filter: {categories: "cast"}) {
          credits {
            name {
              nameText {
                text
              }
            }
          }
        }
        primaryImage {
          url
          width
          height
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "ComingSoon");
        if (!isset($data->comingSoon)) {
            return $calendar;
        }
        if (
            isset($data->comingSoon->edges) &&
            is_array($data->comingSoon->edges) &&
            count($data->comingSoon->edges) > 0
        ) {
            foreach ($data->comingSoon->edges as $edge) {
                $title = $edge->node->titleText->text;
                if ($title === null) {
                    continue;
                }
                //release date
                $dateParts = array(
                    'month' => $edge->node->releaseDate->month,
                    'day' => $edge->node->releaseDate->day,
                    'year' => $edge->node->releaseDate->year
                );
                $releaseDate = $this->buildDateString($dateParts);
                if ($releaseDate === false) {
                    continue;
                }
                // Genres
                $genres = array();
                if (!empty($edge->node->titleGenres->genres)) {
                    foreach ($edge->node->titleGenres->genres as $genre) {
                        if (!empty($genre->genre->text)) {
                            $genres[] = $genre->genre->text;
                        }
                    }
                }
                // Cast
                $cast = array();
                if (!empty($edge->node->principalCredits[0]->credits)) {
                    foreach ($edge->node->principalCredits[0]->credits as $credit) {
                        if (!empty($credit->name->nameText->text)) {
                            $cast[] = $credit->name->nameText->text;
                        }
                    }
                }
                // image url
                $imgUrl = null;
                if (!empty($edge->node->primaryImage->url)) {
                    $fullImageWidth = $edge->node->primaryImage->width;
                    $fullImageHeight = $edge->node->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $imgUrl = $img . $parameter;
                }
                $calendar[$releaseDate][] = array(
                    'title' => $title,
                    'imdbid' => isset($edge->node->id) ?
                                    str_replace('tt', '', $edge->node->id) : null,
                    'genres' => $genres,
                    'cast' => $cast,
                    'imgUrl' => $imgUrl
                );
            }
        }
        return $calendar;
    }

    /**
     * Get upcoming releases from big streaming providers for current month.
     * See https://www.imdb.com/list/ls549391228/ (Netflix)
     * @parameter string $listProviderId This is the streaming provider list id like "549391228" (without ls)
     * Possible providerIds:
     *      549391228 (Netflix)
     *      549615961 (HBO MAX)
     *      549641648 (Prime Video)
     *      549359815 (Disney+)
     *      549124072 (Hulu)
     *      549641648 (Amazon Prime)
     *      549617029 (Paramount+)
     *      544306775 (TV and Streaming Calendar)
     * @config options
     *      $streamSortBy, $streamSortOrder, $calendarThumbnailWidth, $calendarThumbnailHeight
     *
     * @return array{
     *     listId: string,
     *     listName: string,
     *     listCreateDate: string,
     *     listLastModifiedDate: string,
     *     items: list<array{
     *         id: string,
     *         title: string,
     *         type: string,
     *         year: int,
     *         description: string,
     *         runtime: int,
     *         rating: float,
     *         votes: int,
     *         metacritic: int,
     *         plot: string,
     *         thumbUrl: string,
     *         credits: array<string, list<array{
     *             nameId: string,
     *             name: string
     *         }>>
     *     }>
     * }|array{}
     */
    public function comingSoonStreaming(string $listProviderId): array
    {
        $calendarStreaming = array();
        $sortBy = $this->config->streamSortBy;
        $sortOrder = $this->config->streamSortOrder;

        $query = <<<EOF
query ComingSoonStreaming {
  list(id: "ls$listProviderId") {
    createdDate
    id
    lastModifiedDate
    name {
      originalText
    }
    items(
      first: 250
      sort: {by: $sortBy, order: $sortOrder}
    ) {
      edges {
        node {
          description {
            originalText {
              plainText
            }
          }
          item {
            ... on Title {
              id
              titleText {
                text
              }
              releaseYear {
                year
              }
              titleType {
                text
              }
              runtime {
                seconds
              }
              ratingsSummary {
                aggregateRating
                voteCount
              }
              metacritic {
                metascore {
                  score
                }
              }
              plot {
                plotText {
                  plainText
                }
              }
              principalCredits(filter: {categories: ["cast", "director"]}) {
                category {
                  text
                }
                credits(limit: 3) {
                  name {
                    id
                    nameText {
                      text
                    }
                  }
                }
              }
              primaryImage {
                url
                width
                height
              }
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "ComingSoonStreaming", ["id" => "ls$listProviderId"]);
        if (!isset($data->list)) {
            return $calendarStreaming;
        }
        if (
            isset($data->list->items->edges) &&
            is_array($data->list->items->edges) &&
            count($data->list->items->edges) > 0
        ) {
            $items = array();
            foreach ($data->list->items->edges as $edge) {
                // image url
                $imgUrl = null;
                if (!empty($edge->node->item->primaryImage->url)) {
                    $fullImageWidth = $edge->node->item->primaryImage->width;
                    $fullImageHeight = $edge->node->item->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $imgUrl = $img . $parameter;
                }

                // PrincipalCredits
                $credits = array();
                if (!empty($edge->node->item->principalCredits)) {
                    foreach ($edge->node->item->principalCredits as $principalCredit) {
                        $category = $principalCredit->category->text;
                        $temp = array();
                        foreach ($principalCredit->credits as $credit) {
                            $temp[] = array(
                                'nameId' => isset($credit->name->id) ?
                                                str_replace('nm', '', $credit->name->id) : null,
                                'name' => $credit->name->nameText->text
                            );
                        }
                        $credits[$category] = $temp;
                    }
                }

                $items[] = array(
                    'id' => isset($edge->node->item->id) ?
                                str_replace('tt', '', $edge->node->item->id) : null,
                    'title' => $edge->node->item->titleText->text,
                    'type' => $edge->node->item->titleType->text,
                    'year' => $edge->node->item->releaseYear->year,
                    'description' => $edge->node->description->originalText->plainText,
                    'runtime' => $edge->node->item->runtime->seconds,
                    'rating' => $edge->node->item->ratingsSummary->aggregateRating,
                    'votes' => $edge->node->item->ratingsSummary->voteCount,
                    'metacritic' => $edge->node->item->metacritic->metascore->score,
                    'plot' => $edge->node->item->plot->plotText->plainText,
                    'thumbUrl' => $imgUrl,
                    'credits' => $credits
                );
            }
        }
        $calendarStreaming = array(
            'listId' => isset($data->list->id) ?
                              str_replace('ls', '', $data->list->id) : null,
            'listName' => $data->list->name->originalText,
            'listCreateDate' => $data->list->createdDate,
            'listLastModifiedDate' => $data->list->lastModifiedDate,
            'items' => $items ?? array()
        );
        return $calendarStreaming;
    }

    /**
     * build date string
     * @param array<string, string> $dateParts input date
     * @return string|false false if no data
     */
    private function buildDateString(array $dateParts): string|bool
    {
        if (!empty($dateParts['month']) && !empty($dateParts['day']) && !empty($dateParts['year'])) {
            return $dateParts['month'] . '/' .
                   $dateParts['day'] . '/' .
                   $dateParts['year'];
        } else {
            return false;
        }
    }
}
