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
 * Title Search Advanced Class for advanced searches
 */
class TitleSearchAdvanced extends MdbBase
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
        $this->newImageWidth = $this->config->titleSearchAdvancedThumbnailWidth;
        $this->newImageHeight = $this->config->titleSearchAdvancedThumbnailHeight;
    }

    /**
     * Advanced Search IMDb on genres, titleTypes, creditId, startDate, endDate, countryId, languageId, $keywords
     *
     * @param string $searchTerm input searchTerm to search for specific titleText
     * @param string $genres if multiple genres separate by , (Horror,Action etc)
     * @param string $types if multiple types separate by , (movie,tvSeries etc)
     * @param string $creditId works only with nameID like "0001228" (without nm) (Peter Fonda)
     * @param string $startDate search from startDate til present date, iso date ("1975-01-01")
     * @param string $endDate search from endDate and earlier, iso date ("1975-01-01")
     * @param string $countryId iso 3166 country code like "US" or "US,DE" (separate by comma)
     * @param string $languageId iso 639 Language code like "en" or "en,de" (separate by comma)
     * @param string $keywords like "sex" or "sex,drugs" (separate by comma)
     * @param string $companyId like "0185428" (without co) (single companyid is supported)
     *
     * @return array{totalFoundResults: ?int, titles: array<int, array{imdbid: ?string, originalTitle: ?string, title: ?string, year: string, movietype: ?string, runtime: ?int, rating: ?float, voteCount: ?int, metacritic: ?int, plot: ?string, imgUrl: ?string}>}
     */
    public function advancedSearch(
        string $searchTerm = '',
        string $genres = '',
        string $types = '',
        string $creditId = '',
        string $startDate = '',
        string $endDate = '',
        string $countryId = '',
        string $languageId = '',
        string $keywords = '',
        string $companyId = ''
    ): array {
        $results = [
            'totalFoundResults' => null,
            'titles' => []
        ];
        $titles = [];
        $constraints = $this->buildConstraints(
            $searchTerm,
            $genres,
            $types,
            $creditId,
            $startDate,
            $endDate,
            $countryId,
            $languageId,
            $keywords,
            $companyId
        );
        if (empty($constraints)) {
            return $results;
        }

        $amount = $this->config->titleSearchAdvancedAmount;
        $sortBy = $this->config->sortBy;
        $sortOrder = $this->config->sortOrder;

        $query = <<<EOF
query advancedSearch{
  advancedTitleSearch(
    first: $amount, sort: {sortBy: $sortBy sortOrder: $sortOrder}
    constraints: $constraints
  ) {
    total
    edges {
      node{
        title {
          id
          originalTitleText {
            text
          }
          titleText {
            text
          }
          titleType {
            text
          }
          releaseYear {
            year
            endYear
          }
          primaryImage {
            url
            width
            height
          }
          runtime {
            seconds
          }
          ratingsSummary {
            aggregateRating
            voteCount
          }
          plot {
            plotText {
              plainText
            }
          }
          metacritic {
            metascore {
              score
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "advancedSearch");
        if (!isset($data->advancedTitleSearch)) {
            return $results;
        }

        if (
            isset($data->advancedTitleSearch->edges) &&
            is_array($data->advancedTitleSearch->edges) &&
            count($data->advancedTitleSearch->edges) > 0
        ) {
            foreach ($data->advancedTitleSearch->edges as $edge) {
                // Year range
                $yearRange = '';
                if (isset($edge->node->title->releaseYear->year)) {
                    $yearRange .= (string)$edge->node->title->releaseYear->year;
                    if (isset($edge->node->title->releaseYear->endYear)) {
                        $yearRange .= '-' . $edge->node->title->releaseYear->endYear;
                    }
                }

                // Image url
                $imgUrl = null;
                if (
                    !empty($edge->node->title->primaryImage->url) &&
                    is_string($edge->node->title->primaryImage->url) &&
                    isset($edge->node->title->primaryImage->width, $edge->node->title->primaryImage->height)
                ) {
                    $fullImageWidth = (int)$edge->node->title->primaryImage->width;
                    $fullImageHeight = (int)$edge->node->title->primaryImage->height;
                    $img = str_replace('.jpg', '', $edge->node->title->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $imgUrl = $img . $parameter;
                }

                $imdbid = isset($edge->node->title->id) && is_string($edge->node->title->id)
                    ? str_replace('tt', '', $edge->node->title->id)
                    : null;

                $originalTitle = isset($edge->node->title->originalTitleText->text) && is_string($edge->node->title->originalTitleText->text)
                    ? $edge->node->title->originalTitleText->text
                    : null;

                $title = isset($edge->node->title->titleText->text) && is_string($edge->node->title->titleText->text)
                    ? $edge->node->title->titleText->text
                    : null;

                $movietype = isset($edge->node->title->titleType->text) && is_string($edge->node->title->titleType->text)
                    ? $edge->node->title->titleType->text
                    : null;

                $runtime = isset($edge->node->title->runtime->seconds)
                    ? (int)$edge->node->title->runtime->seconds
                    : null;

                $rating = isset($edge->node->title->ratingsSummary->aggregateRating)
                    ? (float)$edge->node->title->ratingsSummary->aggregateRating
                    : null;

                $voteCount = isset($edge->node->title->ratingsSummary->voteCount)
                    ? (int)$edge->node->title->ratingsSummary->voteCount
                    : null;

                $metacritic = isset($edge->node->title->metacritic->metascore->score)
                    ? (int)$edge->node->title->metacritic->metascore->score
                    : null;

                $plot = isset($edge->node->title->plot->plotText->plainText) && is_string($edge->node->title->plot->plotText->plainText)
                    ? $edge->node->title->plot->plotText->plainText
                    : null;

                $titles[] = [
                    'imdbid' => $imdbid,
                    'originalTitle' => $originalTitle,
                    'title' => $title,
                    'year' => $yearRange,
                    'movietype' => $movietype,
                    'runtime' => $runtime,
                    'rating' => $rating,
                    'voteCount' => $voteCount,
                    'metacritic' => $metacritic,
                    'plot' => $plot,
                    'imgUrl' => $imgUrl,
                ];
            }
        }

        $totalFound = isset($data->advancedTitleSearch->total)
            ? (int)$data->advancedTitleSearch->total
            : null;

        return [
            'totalFoundResults' => $totalFound,
            'titles' => $titles,
        ];
    }

    #========================================================[ Helper functions]===

    /**
     * Check input parameters and build constraints
     */
    private function buildConstraints(
        ?string $searchTerm,
        ?string $genres,
        ?string $types,
        ?string $creditId,
        ?string $startDate,
        ?string $endDate,
        ?string $countryId,
        ?string $languageId,
        ?string $keywords,
        ?string $companyId
    ): string|bool {
        $constraint = '{';

        if (!empty(trim((string)$searchTerm))) {
            $constraint .= 'titleTextConstraint:{searchTerm:"' . $searchTerm . '"}';
        }

        $checkedGenres = $this->checkItems($genres);
        if ($checkedGenres !== false) {
            $constraint .= 'genreConstraint:{allGenreIds:["' . $checkedGenres . '"]}';
        }

        $checkedTypes = $this->checkItems($types);
        if ($checkedTypes !== false) {
            $constraint .= 'titleTypeConstraint:{anyTitleTypeIds:["' . $checkedTypes . '"]}';
        }

        if (!empty($creditId)) {
            $creditId = "nm$creditId";
        }
        $checkedCreditId = $this->checkItems($creditId);
        if ($checkedCreditId !== false) {
            $constraint .= 'creditedNameConstraint:{anyNameIds:["' . $checkedCreditId . '"]}';
        }

        $dateRange = $this->checkDates($startDate, $endDate);
        if ($dateRange !== false) {
            $constraint .= $dateRange;
        }

        $checkedCountryId = $this->checkItems($countryId);
        if ($checkedCountryId !== false) {
            $constraint .= 'originCountryConstraint:{anyCountries:["' . $checkedCountryId . '"]}';
        }

        $checkedLanguageId = $this->checkItems($languageId);
        if ($checkedLanguageId !== false) {
            $constraint .= 'languageConstraint:{anyLanguages:["' . $checkedLanguageId . '"]}';
        }

        $checkedKeywords = $this->checkItems($keywords);
        if ($checkedKeywords !== false) {
            $constraint .= 'keywordConstraint:{anyKeywords:["' . $checkedKeywords . '"]}';
        }

        if (!empty($companyId)) {
            $companyId = "co$companyId";
        }
        $checkedCompanyId = $this->checkItems($companyId);
        if ($checkedCompanyId !== false) {
            $constraint .= 'creditedCompanyConstraint:{anyCompanyIds:["' . $checkedCompanyId . '"]}';
        }

        if ($constraint === '{') {
            return false;
        }

        $constraint .= 'explicitContentConstraint:{explicitContentFilter:INCLUDE_ADULT}';
        $constraint .= '}';

        return $constraint;
    }

    /**
     * Check if there is at least one, possible more input items
     */
    private function checkItems(?string $items): string|bool
    {
        if (!is_string($items) || strlen($items) === 0 || empty(trim($items))) {
            return false;
        }
        $parts = array_map('trim', explode(',', $items));
        return implode('","', $parts);
    }

    /**
     * Check if provided date is valid
     */
    private function validateDate(?string $date): bool
    {
        if (!is_string($date) || strlen($date) === 0) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if input dates not empty and valid
     */
    private function checkDates(?string $startDate, ?string $endDate): string|bool
    {
        if (!empty($startDate) || !empty($endDate)) {
            $constraint = 'releaseDateConstraint:{';
            if (!empty($startDate) && !empty($endDate)) {
                if ($this->validateDate($startDate) !== false && $this->validateDate($endDate) !== false) {
                    $constraint .= 'releaseDateRange:{start:"' . $startDate . '"end:"' . $endDate . '"}}';
                } else {
                    return false;
                }
            } else {
                if (!empty($startDate) && $this->validateDate($startDate) !== false) {
                    $constraint .= 'releaseDateRange:{start:"' . $startDate . '"}}';
                } else {
                    if ($this->validateDate($endDate) !== false) {
                        $constraint .= 'releaseDateRange:{end:"' . $endDate . '"}}';
                    } else {
                        return false;
                    }
                }
            }
            return $constraint;
        } else {
            return false;
        }
    }
}
