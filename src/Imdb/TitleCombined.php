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
 * A title on IMDb
 */
class TitleCombined extends MdbBase
{
    protected readonly Image $imageFunctions;
    protected readonly int $newImageWidth;
    protected readonly int $newImageHeight;
    /** @var array{title: string|null, originalTitle: string|null, imdbid: string, reDirectId: string|false, movieType: string|null, year: int|string|null, endYear: int|string|null, imgThumb: string|null, imgFull: string|null, runtime: int|float, rating: float|int, genre: list<array{mainGenre: string|null, subGenre: list<string>}>|null, plotoutline: string|null, credits: array<string, list<array{name: string|null, imdbid: string|null}>>|null}|array{} */
    protected array $main = [];

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config|null $config OPTIONAL override default config
     * @param LoggerInterface|null $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface|null $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(string $id, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
        $this->imageFunctions = new Image();
        $this->newImageWidth = $this->config->photoThumbnailWidth;
        $this->newImageHeight = $this->config->photoThumbnailHeight;
    }

    /**
     * This method will only get main values of a imdb title (inside the black top part of the imdb page)
     *
     * @return array{title: string|null, originalTitle: string|null, imdbid: string, reDirectId: string|false, movieType: string|null, year: int|string|null, endYear: int|string|null, imgThumb: string|null, imgFull: string|null, runtime: int|float, rating: float|int, genre: list<array{mainGenre: string|null, subGenre: list<string>}>|null, plotoutline: string|null, credits: array<string, list<array{name: string|null, imdbid: string|null}>>|null}|array{}
     */
    public function main(): array
    {
        $query = <<<EOF
query TitleCombinedMain(\$id: ID!) {
  title(id: \$id) {
    titleText {
      text
    }
    originalTitleText {
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
    }
    titleGenres {
      genres {
        genre {
          text
        }
        subGenres {
          keyword {
            text {
              text
            }
          }
        }
      }
    }
    plot {
      plotText {
        plainText
      }
    }
    principalCredits {
      credits(limit: 3) {
        name {
          nameText {
            text
          }
          id
        }
        category {
          text
        }
      }
    }
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "TitleCombinedMain", ["id" => "tt$this->imdbID"]);
        if (!isset($data->title)) {
            return $this->main;
        }
        $this->main = array(
            'title' => isset($data->title->titleText->text) ?
                             trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))) : null,
            'originalTitle' => isset($data->title->originalTitleText->text) ?
                                     trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))) : null,
            'imdbid' => $this->imdbID,
            'reDirectId' => isset($data->title->meta->canonicalId) ?
                                  $this->checkRedirect($data->title->meta->canonicalId) : false,
            'movieType' => isset($data->title->titleType->text) ?
                                 $data->title->titleType->text : null,
            'year' => isset($data->title->releaseYear->year) ?
                            $data->title->releaseYear->year : null,
            'endYear' => isset($data->title->releaseYear->endYear) ?
                               $data->title->releaseYear->endYear : null,
            'imgThumb' => isset($data->title->primaryImage) ?
                                $this->populatePoster($data->title->primaryImage, true) : null,
            'imgFull' => isset($data->title->primaryImage) ?
                               $this->populatePoster($data->title->primaryImage, false) : null,
            'runtime' => isset($data->title->runtime->seconds) ?
                               $data->title->runtime->seconds / 60 : 0,
            'rating' => isset($data->title->ratingsSummary->aggregateRating) ?
                              $data->title->ratingsSummary->aggregateRating : 0,
            'genre' => isset($data->title->titleGenres->genres) ?
                             $this->genre($data->title->titleGenres->genres) : null,
            'plotoutline' => isset($data->title->plot->plotText->plainText) ?
                                   $data->title->plot->plotText->plainText : null,
            'credits' => isset($data->title->principalCredits) ?
                               $this->principalCredits($data->title->principalCredits) : null
        );
        return $this->main;
    }


    #========================================================[ Helper functions ]===

    #--------------------------------------------------------------[ Photo/Poster ]---
    /**
     * Setup cover photo (thumbnail and big variant)
     * @param object{url?: string, width?: int, height?: int} $primaryImage primary image object found in main()
     * @param bool $thumb
     * @return string|null
     * @see IMDB page / (TitlePage)
     */
    private function populatePoster(object $primaryImage, bool $thumb): ?string
    {
        if (isset($primaryImage->url)) {
            $img = str_replace('.jpg', '', $primaryImage->url);
            if ($thumb === true && isset($primaryImage->width, $primaryImage->height)) {
                $fullImageWidth = $primaryImage->width;
                $fullImageHeight = $primaryImage->height;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                return $img . $parameter;
            } else {
                return $img . 'QL100_SX1000_.jpg';
            }
        }
        return null;
    }

    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @param list<\stdClass> $genreArray found genres array from main()
     * @phpstan-return list<array{
     *     mainGenre: string|null,
     *     subGenre: list<string>
     * }>|array{}
     * @see IMDB page / (TitlePage)
     */
    private function genre(array $genreArray): array
    {
        $mainGenres = [];
        if (count($genreArray) > 0) {
            foreach ($genreArray as $edge) {
                $subGenres = [];
                if (
                    isset($edge->subGenres) &&
                    is_array($edge->subGenres) &&
                    count($edge->subGenres) > 0
                ) {
                    foreach ($edge->subGenres as $subGenre) {
                        if (!empty($subGenre->keyword->text->text)) {
                            $subGenres[] = $subGenre->keyword->text->text;
                        }
                    }
                }
                $mainGenres[] = array(
                    'mainGenre' => isset($edge->genre->text) ?
                                         $edge->genre->text : null,
                    'subGenre' => $subGenres
                );
            }
        }
        return $mainGenres;
    }

    #----------------------------------------------------------------[ PrincipalCredits ]---
    /**
     * Get the PrincipalCredits for this title
     * @param list<\stdClass> $principalCredits principal credits array from main()
     * @return array<string, list<array{name: string|null, imdbid: string|null}>> creditsPrincipal[category][Director, Writer, Creator, Stars]
     */
    private function principalCredits(array $principalCredits): array
    {
        /** @var array<string, list<array{name: string|null, imdbid: string|null}>> $creditsPrincipal */
        $creditsPrincipal = [];
        if (count($principalCredits) > 0) {
            foreach ($principalCredits as $value) {
                $category = 'Unknown';
                $credits = [];
                if (!empty($value->credits[0]->category->text) && is_string($value->credits[0]->category->text)) {
                    $category = $value->credits[0]->category->text;
                    if ($category === "Actor" || $category === "Actress") {
                        $category = "Star";
                    }
                }
                if (
                    isset($value->credits) &&
                    is_array($value->credits) &&
                    count($value->credits) > 0
                ) {
                    foreach ($value->credits as $credit) {
                        $name = isset($credit->name->nameText->text) && is_string($credit->name->nameText->text)
                            ? $credit->name->nameText->text
                            : null;
                        $imdbid = isset($credit->name->id) && is_string($credit->name->id)
                            ? str_replace('nm', '', $credit->name->id)
                            : null;

                        $credits[] = array(
                            'name' => $name,
                            'imdbid' => $imdbid,
                        );
                    }
                } elseif ($category === 'Unknown') {
                    continue;
                }
                $creditsPrincipal[$category] = $credits;
            }
        }
        return $creditsPrincipal;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not
     * Sometimes it happens that imdb redirects an existing id to a new id
     * @param string $titleImdbId the returned imdbid from Graphql call
     * @return string|false $titleImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    private function checkRedirect(string $titleImdbId): string|bool
    {
        $titleImdbId = str_replace('tt', '', $titleImdbId);
        if ($titleImdbId  !== $this->imdbID) {
            // todo write to log?
            return $titleImdbId;
        } else {
            return false;
        }
    }
}
