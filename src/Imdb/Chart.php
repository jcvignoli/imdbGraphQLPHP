<?php

#############################################################################
# imdbGraphQLPHP Chart                       https://www.imdb.com/chart     #
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
 * Obtains information about chart lists as seen on IMDb
 * https://www.imdb.com/chart
 * @Note thumbnail width and height are set in config, one setting for all methods!
 * @author Ed (github user: duck7000)
 */
class Chart extends MdbBase
{
    protected Image $imageFunctions;
    protected int $newImageWidth;
    protected int $newImageHeight;

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
     * Get top250 titles lists as seen on IMDb https://www.imdb.com/chart
     *
     * @parameter $listType This defines different kind of lists like top250 Movie or TV
     * possible values for $listType:
     *   BOTTOM_100
     *       Overall IMDb Bottom 100 Feature List
     *   TOP_250
     *       Overall IMDb Top 250 Feature List
     *   TOP_250_ENGLISH
     *       Top 250 English Feature List
     *   TOP_250_TV
     *       Overall IMDb Top 250 TV List
     *
     * @return list<array{
     *     title: string,
     *     imdbid: string,
     *     year: int|null,
     *     rank: int,
     *     rating: float,
     *     votes: int,
     *     runtimeSeconds: int|null,
     *     runtimeText: string|null,
     *     imgUrl: string|null
     * }>
     */
    public function top250Title(string $listType = "TOP_250"): array
    {
        $top250TitleResults = array();
        $query = <<<EOF
query Top250Title {
  titleChartRankings(
    first: 250
    input: {rankingsChartType: $listType}
  ) {
    edges {
      node{
        item {
          id
          titleText {
            text
          }
          releaseYear {
            year
          }
          ratingsSummary {
            topRanking {
              rank
            }
            aggregateRating
            voteCount
          }
          primaryImage {
            url
            width
            height
          }
          runtime {
            seconds
            displayableProperty {
              value {
                plainText
              }
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Top250Title");
        if (!isset($data->titleChartRankings)) {
            return $top250TitleResults;
        }
        if (
            isset($data->titleChartRankings->edges) &&
            is_array($data->titleChartRankings->edges) &&
            count($data->titleChartRankings->edges) > 0
        ) {
            foreach ($data->titleChartRankings->edges as $edge) {
                $thumbUrl = null;
                if (!empty($edge->node->item->primaryImage->url)) {
                    $fullImageWidth = $edge->node->item->primaryImage->width;
                    $fullImageHeight = $edge->node->item->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                $top250TitleResults[] = array(
                    'title' => $edge->node->item->titleText->text,
                    'imdbid' => isset($edge->node->item->id) ?
                                    str_replace('tt', '', $edge->node->item->id) : null,
                    'year' => $edge->node->item->releaseYear->year,
                    'rank' => $edge->node->item->ratingsSummary->topRanking->rank,
                    'rating' => $edge->node->item->ratingsSummary->aggregateRating,
                    'votes' => $edge->node->item->ratingsSummary->voteCount,
                    'runtimeSeconds' => $edge->node->item->runtime->seconds,
                    'runtimeText' => $edge->node->item->runtime->displayableProperty->value->plainText,
                    'imgUrl' => $thumbUrl
                );
            }
        }
        return $top250TitleResults;
    }

    /**
     * Get top250 Names lists (Not seen on IMDb afaik)
     * @return list<array{
     *     name: string,
     *     imdbid: string,
     *     rank: int,
     *     credits: list<string>,
     *     knownFor: array{
     *         id: string|null,
     *         title: string|null,
     *         year: int|null
     *     }|null,
     *     imgUrl: string|null
     * }>
     */
    public function top250Name(): array
    {
        $top250NameResults = array();
        $query = <<<EOF
query Top250Name {
  nameChartRankings(
  first: 250
  input: {rankingsChartType: INDIA_STAR_METER}
  ) {
    edges {
      node {
        rank
        item {
          nameText {
            text
          }
          id
          creditSummary {
            categories {
              category {
                text
              }
            }
          }
          knownFor(first: 1) {
            edges {
              node {
                title {
                  id
                  titleText {
                    text
                  }
                  releaseYear {
                    year
                  }
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
EOF;
        $data = $this->graphql->query($query, "Top250Name");
        if (!isset($data->nameChartRankings)) {
            return $top250NameResults;
        }
        if (
            isset($data->nameChartRankings->edges) &&
            is_array($data->nameChartRankings->edges) &&
            count($data->nameChartRankings->edges) > 0
        ) {
            foreach ($data->nameChartRankings->edges as $edge) {
                $thumbUrl = null;
                $credits = array();
                $knownFor = array();
                if (!empty($edge->node->item->primaryImage->url)) {
                    $fullImageWidth = $edge->node->item->primaryImage->width;
                    $fullImageHeight = $edge->node->item->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                if (!empty($edge->node->item->knownFor->edges)) {
                    $knownFor = array(
                        'id' => isset($edge->node->item->knownFor->edges[0]->node->title->id) ?
                                    str_replace('tt', '', $edge->node->item->knownFor->edges[0]->node->title->id) : null,
                        'title' => $edge->node->item->knownFor->edges[0]->node->title->titleText->text,
                        'year' => $edge->node->item->knownFor->edges[0]->node->title->releaseYear->year
                    );
                }
                if (!empty($edge->node->item->creditSummary->categories)) {
                    foreach ($edge->node->item->creditSummary->categories as $item) {
                        if (!empty($item->category->text)) {
                            $credits[] = $item->category->text;
                        }
                    }
                }
                $top250NameResults[] = array(
                    'name' => $edge->node->item->nameText->text,
                    'imdbid' => isset($edge->node->item->id) ?
                                    str_replace('nm', '', $edge->node->item->id) : null,
                    'rank' => $edge->node->rank,
                    'credits' => $credits,
                    'knownFor' => $knownFor,
                    'imgUrl' => $thumbUrl
                );
            }
        }
        return $top250NameResults;
    }

    /**
     * Get most popular Names lists as seen on https://imdb.com/chart/starmeter
     * @return list<array{
     *     name: string,
     *     imdbid: string,
     *     rank: int,
     *     credits: list<string>,
     *     knownFor: array{
     *         id: string|null,
     *         title: string|null,
     *         year: int|null
     *     }|null,
     *     imgUrl: string|null
     * }>
     */
    public function mostPopularName()
    {
        $mostPopularNameResults = array();
        $query = <<<EOF
query MostPopularName {
  chartNames(
    first: 100
    chart: {chartType: MOST_POPULAR_NAMES}
    sort: {sortBy: POPULARITY, sortOrder: ASC}
  ) {
    edges {
      node {
        id
        nameText {
          text
        }
        creditCategories {
          category {
            text
          }
        }
        knownFor(first: 1) {
          edges {
            node {
              title {
                id
                titleText {
                  text
                }
                releaseYear{
                  year
                }
              }
            }
          }
        }
        primaryImage {
          url
          width
          height
        }
        meterRanking {
          currentRank
          rankChange {
            difference
            changeDirection
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "MostPopularName");
        if (!isset($data->chartNames)) {
            return $mostPopularNameResults;
        }
        if (
            isset($data->chartNames->edges) &&
            is_array($data->chartNames->edges) &&
            count($data->chartNames->edges) > 0
        ) {
            foreach ($data->chartNames->edges as $edge) {
                $thumbUrl = null;
                $credits = array();
                $knownFor = array();
                if (!empty($edge->node->primaryImage->url)) {
                    $fullImageWidth = $edge->node->primaryImage->width;
                    $fullImageHeight = $edge->node->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                if (!empty($edge->node->knownFor->edges)) {
                    $knownFor = array(
                        'id' => isset($edge->node->knownFor->edges[0]->node->title->id) ?
                                    str_replace('tt', '', $edge->node->knownFor->edges[0]->node->title->id) : null,
                        'title' => $edge->node->knownFor->edges[0]->node->title->titleText->text,
                        'year' => $edge->node->knownFor->edges[0]->node->title->releaseYear->year
                    );
                }
                if (!empty($edge->node->creditCategories)) {
                    foreach ($edge->node->creditCategories as $item) {
                        if (!empty($item->category->text)) {
                            $credits[] = $item->category->text;
                        }
                    }
                }
                $mostPopularNameResults[] = array(
                    'name' => $edge->node->nameText->text,
                    'imdbid' => isset($edge->node->id) ?
                                    str_replace('nm', '', $edge->node->id) : null,
                    'rank' => $edge->node->rank,
                    'credits' => $credits,
                    'knownFor' => $knownFor,
                    'imgUrl' => $thumbUrl
                );
            }
        }
        return $mostPopularNameResults;
    }

    /**
     * Get most popular Titles lists as seen on https://imdb.com/chart/moviemeter
     * @parameter string $genreId This filters the results on a genreId like "Horror"
     * GenreIDs: Action, Adult, Adventure, Animation, Biography, Comedy, Crime,
     *           Documentary, Drama, Family, Fantasy, Film-Noir, Game-Show,
     *           History, Horror, Music, Musical, Mystery, News, Reality-TV,
     *           Romance, Sci-Fi, Short, Sport, Talk-Show, Thriller, War, Western
     *
     * @parameter string|null $listType This defines different kind of lists like Movie or TV
     * possible values for $listType:
     *   LOWEST_RATED_MOVIES
     *       Lowest Rated IMDb Bottom List
     *   MOST_POPULAR_MOVIES
     *       Most Popular IMDb Movies List
     *   MOST_POPULAR_TV_SHOWS
     *       Most Popular IMDb TV List
     *   TOP_RATED_MOVIES
     *       Top Rated IMDb Movies List
     *   TOP_RATED_ENGLISH_MOVIES
     *       Top Rated English IMDb Movies List
     *   TOP_RATED_TV_SHOWS
     *       Top Rated IMDb TV List
     *
     * @return list<array{
     *     title: string,
     *     imdbid: string,
     *     year: int|null,
     *     runtimeSeconds: int|null,
     *     runtimeText: string|null,
     *     rank: int,
     *     genre: list<string>,
     *     rating: float|null,
     *     votes: int,
     *     imgUrl: string|null
     * }>
     */
    public function mostPopularTitle(string $listType = "MOST_POPULAR_MOVIES", ?string $genreId = null): array
    {
        $mostPopularTitleResults = array();
        $filter = '';
        if (!empty($genreId)) {
            $filter = 'genreConstraint:{allGenreIds:["' . $genreId . '"]}';
        }

        $query = <<<EOF
query MostPopularTitle {
  chartTitles(
    first: 9999
    chart: {chartType: $listType}
    sort: {sortBy: RANKING, sortOrder: ASC}
    filter:{explicitContentConstraint:{explicitContentFilter:INCLUDE_ADULT}$filter}
  ) {
    edges {
      currentRank
      node {
        id
        titleGenres {
          genres {
            genre {
              text
            }
          }
        }
        titleText {
          text
        }
        releaseYear {
          year
        }
        runtime {
          seconds
          displayableProperty {
            value {
              plainText
            }
          }
        }
        ratingsSummary {
          aggregateRating
          voteCount
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
        $data = $this->graphql->query($query, "MostPopularTitle");
        if (!isset($data->chartTitles)) {
            return $mostPopularTitleResults;
        }
        if (
            isset($data->chartTitles->edges) &&
            is_array($data->chartTitles->edges) &&
            count($data->chartTitles->edges) > 0
        ) {
            foreach ($data->chartTitles->edges as $edge) {
                $thumbUrl = null;
                if (!empty($edge->node->primaryImage->url)) {
                    $fullImageWidth = $edge->node->primaryImage->width;
                    $fullImageHeight = $edge->node->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                $genres = array();
                if (!empty($edge->node->titleGenres->genres)) {
                    foreach ($edge->node->titleGenres->genres as $genre) {
                        if (!empty($genre->genre->text)) {
                            $genres[] = $genre->genre->text;
                        }
                    }
                }
                $mostPopularTitleResults[] = array(
                    'title' => $edge->node->titleText->text,
                    'imdbid' => isset($edge->node->id) ?
                                    str_replace('tt', '', $edge->node->id) : null,
                    'year' => $edge->node->releaseYear->year,
                    'runtimeSeconds' => $edge->node->runtime->seconds,
                    'runtimeText' => $edge->node->runtime->displayableProperty->value->plainText,
                    'rank' => $edge->currentRank,
                    'genre' => $genres,
                    'rating' => $edge->node->ratingsSummary->aggregateRating,
                    'votes' => $edge->node->ratingsSummary->voteCount,
                    'imgUrl' => $thumbUrl
                );
            }
        }
        return $mostPopularTitleResults;
    }

    /**
     * Get topBoxWeekend list as seen on https://www.imdb.com/chart/boxoffice/
     * max 10 results! more is not possible
     * Thumbnail is set in config for the whole class, default 140x207
     * @return array{
     *     weekendStartDate: string|null,
     *     weekendEndDate: string|null,
     *     titles: list<array{
     *         title: string,
     *         id: string|null,
     *         rating: float|null,
     *         votes: int|null,
     *         LifetimeGrossAmount: int|null,
     *         LifetimeGrossCurrency: string|null,
     *         weekendGrossAmount: int|null,
     *         weekendGrossCurrency: string|null,
     *         weeksReleased: int|null,
     *         imgUrl: string|null
     *     }>
     * }
     */
    public function topBoxOffice(): array
    {
        $boxOfficeResults = array(
            'weekendStartDate' => null,
            'weekendEndDate' => null,
            'titles' => array()
        );
        $results = array();
        $query = <<<EOF
query BoxOffice{
  boxOfficeWeekendChart(limit: 10) {
    entries {
      title {
        id
        titleText {
          text
        }
        releaseDate {
          day
          month
          year
        }
        ratingsSummary {
          aggregateRating
          voteCount
        }
        primaryImage {
          url
          width
          height
        }
        lifetimeGross(boxOfficeArea: DOMESTIC) {
          total {
            amount
            currency
          }
        }
      }
      weekendGross {
        total {
          amount
          currency
        }
      }
    }
    weekendEndDate
    weekendStartDate
  }
}
EOF;
        $data = $this->graphql->query($query, "BoxOffice");
        if (!isset($data->boxOfficeWeekendChart)) {
            return $boxOfficeResults;
        }
        if (
            isset($data->boxOfficeWeekendChart->edges) &&
            is_array($data->boxOfficeWeekendChart->edges) &&
            count($data->boxOfficeWeekendChart->edges) > 0
        ) {
            foreach ($data->boxOfficeWeekendChart->entries as $edge) {
                $thumbUrl = null;
                if (!empty($edge->title->primaryImage->url)) {
                    $fullImageWidth = $edge->title->primaryImage->width;
                    $fullImageHeight = $edge->title->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->title->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                $weeks = null;
                if (!empty($edge->title->releaseDate->day) && !empty($edge->title->releaseDate->month) && !empty($edge->title->releaseDate->year)) {
                    $startDate = $edge->title->releaseDate->month . '/' .
                                $edge->title->releaseDate->day . '/' .
                                $edge->title->releaseDate->year;
                    $weeks = $this->datediffInWeeks($startDate, date('m/d/Y'));
                }
                $results[] = array(
                    'title' => $edge->title->titleText->text,
                    'id' => isset($edge->title->id) ?
                                str_replace('tt', '', $edge->title->id) : null,
                    'rating' => $edge->title->ratingsSummary->aggregateRating,
                    'votes' => $edge->title->ratingsSummary->voteCount,
                    'LifetimeGrossAmount' => $edge->title->lifetimeGross->total->amount,
                    'LifetimeGrossCurrency' => $edge->title->lifetimeGross->total->currency,
                    'weekendGrossAmount' => $edge->weekendGross->total->amount,
                    'weekendGrossCurrency' => $edge->weekendGross->total->currency,
                    'weeksReleased' => $weeks,
                    'imgUrl' => $thumbUrl
                );
            }
        }
        $boxOfficeResults = array(
            'weekendStartDate' => $data->boxOfficeWeekendChart->weekendStartDate,
            'weekendEndDate' => $data->boxOfficeWeekendChart->weekendEndDate,
            'titles' => $results
        );
        return $boxOfficeResults;
    }

    #========================================================[ Helper functions]===

    /**
     * Get amount of weeks between input date and current date
     * @param string $startDate like '1/2/2013' (month/day/year)
     * @param string $endDate current date! like '1/2/2013' (month/day/year)
     * @return int number of weeks
     */
    public function datediffInWeeks(string $startDate, string $endDate): int
    {
        if ($startDate > $endDate) {
            return $this->datediffInWeeks($endDate, $startDate);
        }
        $first = \DateTime::createFromFormat('m/d/Y', $startDate);
        $second = \DateTime::createFromFormat('m/d/Y', $endDate);
        return (int) ceil($first->diff($second)->days / 7);
    }
}
