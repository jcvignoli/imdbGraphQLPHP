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
 *
 * @phpstan-type CreditsArrayDef array<array-key, array{imdb: string, name: string, jobs: list<string>, attributes: list<string>, episode: array{total: int|null, year: int|null, endYear: int|null}|null, titleFullImageUrl: string|null, titleThumbImageUrl: string|null}>
 * @phpstan-type TriviaArrayDef array<string, list<array{content: string|null, names: list<array{name: string|null, id: string|null}>, trademark: string|null, isSpoiler: bool}>>
 * @phpstan-type CompCredits array<array{name: string, id: string, country: string, attribute: string, year: int}>
 */
class Title extends MdbBase
{
    protected Image $imageFunctions;
    /** @var array<array{title: string, country: string, countryId: string, language: string|null, languageId: string, comment: array<string>|null}> */
    protected array $akas = array();
    /** @var array<array-key, array{country: string, day: int, month: int, year: int, attributes: list<mixed>}|string> */
    protected array $releaseDates = array();
    /** @var string[] */
    protected array $countries = array();
    /** @var list<array{imdb: string|null, name: string|null, alias: string|null, credited: bool, character: list<string>, comment: list<string>, thumb: string|null}> */
    protected array $creditsCast = array();
    /** @var array<string, list<array{name: string, imdbid: string}>> */
    protected array $creditsPrincipal = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsComposer = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsStunts = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsThanks = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsVisualEffects = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsSpecialEffects = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsDirector = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsProducer = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsWriter = array();
    /** @phpstan-var CreditsArrayDef */
    protected array $creditsCinematographer = array();
    /** @var string[] */
    protected array $languages = array();
    /** @var array<array-key, string> */
    protected array $keywords = array();
    protected ?string $mainPoster = null;
    protected ?string $mainPosterThumb = null;
    protected ?string $mainPlotoutline = null;
    protected ?string $mainMovietype = null;
    protected ?string $mainTitle = null;
    protected ?string $mainOriginalTitle = null;
    protected ?int $mainYear = -1;
    protected ?int $mainEndYear = -1;
    protected int $mainTop250 = 0;
    protected float $mainRating = 0.0;
    protected int $mainRatingVotes = 0;
    /** @var array{url: string|null, score: int, reviewCount: int, reviews: array<int, array{reviewer: string|null, score: int, quote: string|null, siteName: string|null, siteUrl: string|null}>}|array{} */
    protected array $mainMetacritics = array();
    /** @var array{currentRank: int, changeDirection: string, difference: int}|array{} */
    protected array $mainRank = array();
    /** @var array<int, string> */
    protected array $mainPhoto = array();
    /** @var list<array{name: string, runtime: int|null, description: string|null, titleName: string|null, titleYear: int|null, videoUrl: string|null, videoImageUrl: string|null}> */
    protected array $trailers = array();
    /** @var array<string, list<array{id: array<string>|string|null, name: string, runtime: string, description: string, titleName: string, titleYear: int, playbackUrl: string|null, imageUrl: string|null}>> */
    protected array $videos = array();
    /** @var array{award: string|null, nominations: int|null, wins: int|null}|array{} */
    protected array $mainAwards = array();
    /** @var array<string, list<array{awardYear: int|null, awardWinner: bool, awardCategory: string|null, awardName: string|null, awardNotes: string|null, awardPersons: list<array{creditId: string|null, creditName: string|null, creditNote: string|null, nameFullImageUrl: string|null, nameThumbImageUrl: string|null}>, awardOutcome: string}>|array{win: int, nom: int}> */
    protected array $awards = array();
    /** @var array<array-key, array{mainGenre: string|null, subGenre: list<string>}> */
    protected array $genres = array();
    /** @var array<array<string>|string> */
    protected array $quotes = array();
    /** @var list<array{title: string|null, imdbid: string|null, rating: int|null, img: string|null, year: int|null}> */
    protected array $recommendations = array();
    /** @var list<array{time: int|float|null, country: string|null, annotations: list<string>}> */
    protected array $runtimes = array();
    /** @var list<array{country: string|null, rating: string|null, content: string|null}> */
    protected array $mpaas = array();
    /** @var array<string, array{severity: string, severityVotedFor: int, totalSeverityVotes: int, guideItems: list<array{isSpoiler: bool, guideText: string}>}> */
    protected array $parentsGuide = array();
    /** @var list<array{plot: string, author: string}> */
    protected array $plot = array();
    /** @var array<int, array<int, list<array{imdbid: string, title: string, airdate: string|null, airdateParts: array{day: int|null, month: int|null, year: int|null}|null, plot: string|null, episode: int|string, imgUrl: string|null}>>> */
    protected array $seasonEpisodes = array();
    /** @var list<array{soundtrack: string, credits: list<string>, creditSplit: array{creditors: list<array{creditType: string|null, name: string, nameId: string|null, attribute: string|null}>, comment: list<string>}}> */
    protected array $soundtracks = array();
    /** @var string[] */
    protected array $taglines = array();
    /** @phpstan-var TriviaArrayDef */
    protected array $trivias = array();
    protected ?bool $isOngoing = null;
    /** @var array<array-key, array<array{content: string, isSpoiler: bool}>> */
    protected array $goofs = array();
    /** @var string[] */
    protected array $crazyCredits = array();
    /** @var list<array{real: string, movie: list<string>}> */
    protected array $locations = array();
    /** @phpstan-var CompCredits */
    protected array $compCreditsProd = array();
    /** @phpstan-var CompCredits */
    protected array $compCreditsDist = array();
    /** @phpstan-var CompCredits */
    protected array $compCreditsSpecial = array();
    /** @phpstan-var CompCredits */
    protected array $compCreditsOther = array();
    /** @var array<array<array{titleId: string|null, titleName: string|null, titleType: string|null, year: string|null, endYear: string|null, seriesName: string|null, description: string|null}>> */
    protected array $connections = array();
    /** @var array<array<array{label: string, url: string, language: list<string>}>> */
    protected array $externalSites = array();
    /** @var array<array-key, array{productionBudget: array{amount: int|float|null, currency: string|null}|null}> */
    protected array $productionBudget = array();
    /** @var list<array{areatype: string, amount: string|null, currency: string|null}> */
    protected array $grosses = array();
    /** @var string[] */
    protected array $alternateversions = array();
    /** @var list<array<string, list<string>|string>> */
    protected array $soundMix = array();
    /** @var list<array<string, list<string>|string>> */
    protected array $aspectRatio = array();
    /** @var list<array{cameras: string, attributes: list<string>}> */
    protected array $cameras = array();
    /** @var list<array{authorNickName: string|null, authorRating: int|null, summaryText: string|null, reviewText: string|null, submissionDate: string|null}> */
    protected array $featuredReviews = array();
    /** @var list<array{question: string|null, answer: string|null, isSpoiler: bool|null}> */
    protected array $faqs = array();
    protected ?bool $isAdult = null;
    /** @var array<string, list<array{providerId: string, providerName: string, logoUrl: string|null}>> */
    protected array $watchOption = array();
    protected ?string $status = null;
    /** @var list<array{id: string, title: string, author: string|null, date: string|null, extUrl: string|null, extHomepageUrl: string|null, extHomepageLabel: string|null, textHtml: string|null, textText: string|null, thumbnailUrl: string|null}> */
    protected array $news = array();

    #----------------------------------------------------------[ Helper for TitleSearch class ]---
    /**
     * Create an imdb object populated with id, title, year, and movie type
     * @param string $id imdb ID
     * @param string $title film title
     * @param string $orignalTitle Original film title
     * @param string|null $year
     * @param string|null $type
     * @param Config|null $config
     * @param LoggerInterface|null $logger OPTIONAL override default logger
     * @param CacheInterface|null $cache OPTIONAL override default cache
     * @return Title
     */
    public static function fromSearchResult(
        string $id,
        string $title,
        string $orignalTitle,
        ?string $year,
        ?string $type,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null
    ): Title {
        $imdb = new Title($id, $config, $logger, $cache);
        $imdb->mainTitle = $title;
        $imdb->mainYear = (int) $year;
        $imdb->mainMovietype = $type;
        return $imdb;
    }

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
    }

    #-------------------------------------------------------------[ Title ]---

    /** Get movie type
     * @return string movietype (TV Series, Movie, TV Episode, TV Special, TV Movie, TV Mini Series, Video Game, TV Short, Video)
     * @see IMDB page / (TitlePage)
     * If no movietype has been defined explicitly, it returns 'Movie' -- so this is always set.
     */
    public function movietype(): string
    {
        if (empty($this->mainMovietype)) {
            $this->titleYear();
            if (empty($this->mainMovietype)) {
                $this->mainMovietype = 'Movie';
            }
        }
        return $this->mainMovietype;
    }

    /** Get movie title
     * @return string title movie title (name)
     * @see IMDB page / (TitlePage)
     */
    public function title(): string
    {
        if (empty($this->mainTitle)) {
            $this->titleYear();
        }
        return $this->mainTitle;
    }

    /** Get movie original title
     * @phpstan-return string|null mainOriginalTitle  movie original title
     * @see IMDB page / (TitlePage)
     */
    public function originalTitle(): ?string
    {
        if (empty($this->mainOriginalTitle)) {
            $this->titleYear();
        }
        return $this->mainOriginalTitle ;
    }

    /**
     * Get year
     * @return int|null year
     * @see IMDB page / (TitlePage)
     */
    public function year(): ?int
    {
        if ($this->mainYear === -1) {
            $this->titleYear();
        }
        return $this->mainYear;
    }

    /**
     * Get end-year
     * if production spanned multiple years, usually for series
     * @return int|null endyear|null
     * @see IMDB page / (TitlePage)
     */
    public function endyear(): ?int
    {
        if ($this->mainEndYear === -1) {
            $this->titleYear();
        }
        return $this->mainEndYear;
    }

    #---------------------------------------------------------------[ Runtime ]---
    /**
     * Retrieve all runtimes and their descriptions
     * @return list<array{time: int|float|null, country: string|null, annotations: list<string>}>
     * time is the length in minutes, country optionally exists for alternate cuts, annotations is an array of comments
     */
    public function runtime(): array
    {
        if (empty($this->runtimes)) {
            $query = <<<EOF
query Runtimes(\$id: ID!) {
  title(id: \$id) {
    runtimes(first: 9999) {
      edges {
        node {
          attributes {
            text
          }
          country {
            text
          }
          seconds
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Runtimes", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->runtimes;
            }
            if (
                isset($data->title->runtimes->edges) &&
                is_array($data->title->runtimes->edges) &&
                count($data->title->runtimes->edges) > 0
            ) {
                foreach ($data->title->runtimes->edges as $edge) {
                    $attributes = array();
                    if (
                        isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    ) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $attributes[] = $attribute->text;
                            }
                        }
                    }
                    $this->runtimes[] = array(
                        'time' => isset($edge->node->seconds) ?
                                        $edge->node->seconds / 60 : null,
                        'annotations' => $attributes,
                        'country' => $edge->node->country?->text
                    );
                }
            }
        }
        return $this->runtimes;
    }

    #----------------------------------------------------------[ Movie Rating ]---
    /**
     * Get movie rating
     * @return int|float or 0
     * @see IMDB page / (TitlePage)
     */
    public function rating(): int|float
    {
        if ($this->mainRating === 0.0) {
            $query = <<<EOF
query Rating(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      aggregateRating
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Rating", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->aggregateRating)) {
                $this->mainRating = $data->title->ratingsSummary->aggregateRating;
            }
        }
        return $this->mainRating;
    }

     /**
     * Return number of votes for this movie
     * @return int
     * @see IMDB page / (TitlePage)
     */
    public function votes(): int
    {
        if ($this->mainRatingVotes === 0) {
            $query = <<<EOF
query RatingVotes(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      voteCount
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "RatingVotes", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->voteCount)) {
                $this->mainRatingVotes = $data->title->ratingsSummary->voteCount;
            }
        }
        return $this->mainRatingVotes;
    }

    /**
     * Metacritic data like score and reviews
     * @return array{url: string|null, score: int, reviewCount: int, reviews: array<int, array{reviewer: string|null, score: int, quote: string|null, siteName: string|null, siteUrl: string|null}>}|array{}
     */
    public function metacritic(): array
    {
        if (empty($this->mainMetacritics)) {
            $query = <<<EOF
query Metacritic(\$id: ID!) {
  title(id: \$id) {
    metacritic {
      url
      metascore {
        score
        reviewCount
      }
      reviews(first:9999) {
        edges {
          node {
            reviewer
            score
            site
            url
            quote {
              value
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Metacritic", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title->metacritic)) {
                return [];
            }
            $reviews = array();
            if (
                isset($data->title->metacritic->reviews->edges) &&
                is_array($data->title->metacritic->reviews->edges) &&
                count($data->title->metacritic->reviews->edges) > 0
            ) {
                foreach ($data->title->metacritic->reviews->edges as $edge) {
                    $reviews[] = array(
                        'reviewer' => $edge->node->reviewer,
                        'score' => $edge->node->score ?? 0,
                        'quote' => $edge->node->quote->value,
                        'siteName' => $edge->node->site,
                        'siteUrl' => $edge->node->url
                    );
                }
            }
            $this->mainMetacritics = array(
                    'url' => $data->title->metacritic->url,
                    'score' => $data->title->metacritic->metascore->score ?? 0,
                    'reviewCount' => $data->title->metacritic->metascore->reviewCount ?? 0,
                    'reviews' => $reviews
                );
        }
        return $this->mainMetacritics;
    }

    #----------------------------------------------------------[ Popularity ]---
    /**
     * Get movie popularity rank
     * @return array{currentRank: int, changeDirection: string, difference: int}|array{}
     * @see IMDB page / (TitlePage)
     */
    public function rank(): array
    {
        if (empty($this->mainRank)) {
            $query = <<<EOF
query Rank(\$id: ID!) {
  title(id: \$id) {
    meterRanking {
      currentRank
      rankChange {
        changeDirection
        difference
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Rank", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->meterRanking->currentRank)) {
                $this->mainRank = array(
                    'currentRank' => $data->title->meterRanking->currentRank,
                    'changeDirection' => isset($data->title->meterRanking->rankChange->changeDirection) ?
                                               $data->title->meterRanking->rankChange->changeDirection : null,
                    'difference' => isset($data->title->meterRanking->rankChange->difference) ?
                                          $data->title->meterRanking->rankChange->difference : null
                );
            }
        }
        return $this->mainRank;
    }

    #----------------------------------------------------------[ FAQ ]---
    /**
     * Get movie frequently asked questions, it includes questions with and without answer
     * @param bool $spoil include spoilers or not, isSpoiler indicates if this question is spoiler or not
     * @phpstan-return list<array{question: string|null, answer: string|null, isSpoiler: bool|null}>
     * @see IMDB page / (Faq)
     */
    public function faq(bool $spoil = false): array
    {
        if (empty($this->faqs)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
question {
  plainText
}
answer {
  plainText
}
isSpoiler
EOF;
            $data = $this->graphQlGetAll("Faq", "faqs", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $this->faqs[] = array(
                        'question' => $edge->node->question->plainText,
                        'answer' => $edge->node->answer->plainText,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->faqs;
    }

    #-------------------------------------------------------[ Recommendations ]---

    /**
     * Get recommended movies (People who liked this...also liked)
     * @return list<array{title: string|null, imdbid: string|null, rating: int|null, img: string|null, year: int|null}>
     * @see IMDB page / (TitlePage)
     */
    public function recommendation(): array
    {
        if (empty($this->recommendations)) {
            $query = <<<EOF
query Recommendations(\$id: ID!) {
  title(id: \$id) {
    moreLikeThisTitles(first: 12) {
      edges {
        node {
          id
          titleText {
            text
          }
          ratingsSummary {
            aggregateRating
          }
          primaryImage {
            url
            width
            height
          }
          releaseYear {
            year
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Recommendations", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->recommendations;
            }
            if (
                isset($data->title->moreLikeThisTitles->edges) &&
                is_array($data->title->moreLikeThisTitles->edges) &&
                count($data->title->moreLikeThisTitles->edges) > 0
            ) {
                foreach ($data->title->moreLikeThisTitles->edges as $edge) {
                    $thumb = null;
                    if (!empty($edge->node->primaryImage->url)) {
                        $fullImageWidth = $edge->node->primaryImage->width;
                        $fullImageHeight = $edge->node->primaryImage->height;
                        $newImageWidth = $this->config->recommendationThumbnailWidth;
                        $newImageHeight = $this->config->recommendationThumbnailHeight;
                        $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $thumb = $img . $parameter;
                    }
                    $this->recommendations[] = array(
                        'title' => isset($edge->node->titleText->text) ?
                                        $edge->node->titleText->text : null,
                        'imdbid' => isset($edge->node->id) ?
                                        str_replace('tt', '', $edge->node->id) : null,
                        'rating' => isset($edge->node->ratingsSummary->aggregateRating) ?
                                        $edge->node->ratingsSummary->aggregateRating : null,
                        'img' => $thumb,
                        'year' => isset($edge->node->releaseYear->year) ?
                                        $edge->node->releaseYear->year : null
                    );
                }
            }
        }
        return $this->recommendations;
    }

    #--------------------------------------------------------[ Language Stuff ]---
    /** Get all spoken languages spoken in this title
     * @return string[]
     * @see IMDB page / (TitlePage)
     */
    public function language(): array
    {
        if (empty($this->languages)) {
            $query = <<<EOF
query Languages(\$id: ID!) {
  title(id: \$id) {
    spokenLanguages {
      spokenLanguages {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Languages", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->languages;
            }
            if (
                isset($data->title->spokenLanguages->spokenLanguages) &&
                is_array($data->title->spokenLanguages->spokenLanguages) &&
                count($data->title->spokenLanguages->spokenLanguages) > 0
            ) {
                foreach ($data->title->spokenLanguages->spokenLanguages as $language) {
                    if (!empty($language->text)) {
                        $this->languages[] = $language->text;
                    }
                }
            }
        }
        return $this->languages;
    }

    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @return array<array-key, array{mainGenre: string|null, subGenre: list<string>}>
     * @see IMDB page / (TitlePage)
     */
    public function genre(): array
    {
        if (empty($this->genres)) {
            $query = <<<EOF
query Genres(\$id: ID!) {
  title(id: \$id) {
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
  }
}
EOF;
            $data = $this->graphql->query($query, "Genres", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->genres;
            }
            if (
                isset($data->title->titleGenres->genres) &&
                is_array($data->title->titleGenres->genres) &&
                count($data->title->titleGenres->genres) > 0
            ) {
                foreach ($data->title->titleGenres->genres as $edge) {
                    $subGenres = array();
                    if (
                        isset($edge->subGenres) &&
                        is_array($edge->subGenres) &&
                        count($edge->subGenres) > 0
                    ) {
                        foreach ($edge->subGenres as $subGenre) {
                            if (!empty($subGenre->keyword->text->text)) {
                                $subGenres[] = ucwords($subGenre->keyword->text->text);
                            }
                        }
                    }
                    $this->genres[] = array(
                        'mainGenre' => isset($edge->genre->text) ?
                                             $edge->genre->text : null,
                        'subGenre' => $subGenres
                    );
                }
            }
        }
        return $this->genres;
    }

    #--------------------------------------------------------[ Plot (Outline) ]---
    /** Get the main Plot outline for the movie as displayed on top of title page
     * @return string|null plotoutline
     * @see IMDB page / (TitlePage)
     */
    public function plotoutline(): ?string
    {
        if (empty($this->mainPlotoutline)) {
            $query = <<<EOF
query PlotOutline(\$id: ID!) {
  title(id: \$id) {
    plot {
      plotText {
        plainText
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "PlotOutline", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->plot->plotText->plainText)) {
                $this->mainPlotoutline = $data->title->plot->plotText->plainText;
            }
        }
        return $this->mainPlotoutline;
    }

    #--------------------------------------------------------[ Photo specific ]---
    /**
     * Get the main photo image url for thumbnail or full size
     * @param bool $thumb get the thumbnail (height: 281) or large (max 1000 pixels)
     * @return string|false|null photo (string URL if found, FALSE otherwise)
     * @see IMDB page / (TitlePage)
     */
    public function photo(bool $thumb = true): string|bool|null
    {
        if (empty($this->mainPoster)) {
            $this->populatePoster();
        }
        if (!$thumb && empty($this->mainPoster)) {
            return false;
        }
        if ($thumb && empty($this->mainPosterThumb)) {
            return false;
        }
        if ($thumb) {
            return $this->mainPosterThumb;
        }
        return $this->mainPoster;
    }

    /**
     * Save the poster/cover image to disk
     * @param string $path where to store the file
     * @param bool $thumb get the thumbnail or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return boolean success
     * @see IMDB page / (TitlePage)
     */
    public function savephoto(string $path, bool $thumb = true): bool
    {
        $photoUrl = $this->photo($thumb);
        if (!$photoUrl) {
            return false;
        }

        $req = new Request($photoUrl, $this->config);
        $req->sendRequest();
        if (
            strpos($req->getResponseHeader("Content-Type"), 'image/jpeg') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/gif') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/bmp') === 0
        ) {
            $image = $req->getResponseBody();
        } else {
            $ctype = $req->getResponseHeader("Content-Type");
            $this->debugScalar("*photoerror* at " . __FILE__ . " line " . __LINE__ . ": " . $photoUrl . ": Content Type is '$ctype'");
            if (substr($ctype, 0, 4) === 'text') {
                $this->debugScalar("Details: <PRE>" . $req->getResponseBody() . "</PRE>\n");
            }
            return false;
        }

        $fp2 = fopen($path, "w");
        if (!$fp2) {
            $this->logger->warning("Failed to open [$path] for writing  at " . __FILE__ . " line " . __LINE__ . "...<BR>");
            return false;
        }
        fputs($fp2, $image);
        return true;
    }

    /** Get the URL for the movies cover image
     * @param bool $thumb get the thumbnail (default) or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return string|bool url (string URL or FALSE if none)
     * @see IMDB page / (TitlePage)
     */
    public function photoLocalurl(bool $thumb = true): string|bool
    {
        if ($thumb) {
            $ext = "";
        } else {
            $ext = "_big";
        }
        if (!is_dir($this->config->photoroot)) {
            $this->debugScalar("<BR>***ERROR*** The configured image directory does not exist!<BR>");
            return false;
        }
        $path = $this->config->photoroot . "tt{$this->imdbid()}" . "{$ext}.jpg";
        if (file_exists($path)) {
            return $this->config->photodir . "tt{$this->imdbid()}" . "{$ext}.jpg";
        }
        if (!is_writable($this->config->photoroot)) {
            $this->debugScalar("<BR>***ERROR*** The configured image directory lacks write permission!<BR>");
            return false;
        }
        if ($this->savephoto($path, $thumb)) {
            return $this->config->photodir . "tt{$this->imdbid()}" . "{$ext}.jpg";
        }
        return false;
    }

    #-------------------------------------------------[ Country of Origin ]---
    /**
     * Get country of origin
     * @return string[]
     * @see IMDB page / (TitlePage)
     */
    public function country(): array
    {
        if (empty($this->countries)) {
            $query = <<<EOF
query Countries(\$id: ID!) {
  title(id: \$id) {
    countriesOfOrigin {
      countries {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Countries", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->countries;
            }
            if (
                isset($data->title->countriesOfOrigin->countries) &&
                is_array($data->title->countriesOfOrigin->countries) &&
                count($data->title->countriesOfOrigin->countries) > 0
            ) {
                foreach ($data->title->countriesOfOrigin->countries as $country) {
                    if (!empty($country->text)) {
                        $this->countries[] = $country->text;
                    }
                }
            }
        }
        return $this->countries;
    }

    #-------------------------------------------------[ Release dates ]---
    /**
     * Get all release dates for this title
     * @return array<array-key, array{country: string, day: int, month: int, year: int, attributes: list<mixed>}|string>
     * @see IMDB page / (TitlePage)
     */
    public function releaseDate(): array
    {
        if (empty($this->releaseDates)) {
            $query = <<<EOF
country {
  text
}
day
month
year
attributes {
  text
}
EOF;
            $data = $this->graphQlGetAll("ReleaseDates", "releaseDates", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $attributes = array();
                    if (
                        isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    ) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $attributes[] = $attribute->text;
                            }
                        }
                    }
                    $this->releaseDates[] = array(
                        'country' => isset($edge->node->country->text) ?
                                           $edge->node->country->text : null,
                        'day' => isset($edge->node->day) ?
                                       $edge->node->day : null,
                        'month' => isset($edge->node->month) ?
                                         $edge->node->month : null,
                        'year' => isset($edge->node->year) ?
                                       $edge->node->year : null,
                        'attributes' => $attributes
                    );
                }
            }
        }
        return $this->releaseDates;
    }

    #------------------------------------------------------------[ Movie AKAs ]---
    /**
     * Get movie's alternative names
     * The first item in the list will be the original title
     * @return array<array{title: string, country: string, countryId: string, language: string|null, languageId: string, comment: array<string>|null}>
     * Ordered Ascending by Country
     * @see IMDB page ReleaseInfo
     */
    public function alsoknow(): array
    {
        if (empty($this->akas)) {
            $filter = ', sort: {order: ASC by: COUNTRY}';
            $query = <<<EOF
country {
  id
  text
}
text
attributes {
  text
}
language {
  id
  text
}
EOF;
            $data = $this->graphQlGetAll("AlsoKnow", "akas", $query, $filter);
            $originalTitle = $this->originalTitle();
            if (!empty($originalTitle)) {
                $this->akas[] = array(
                    'title' => $originalTitle,
                    'country' => "(Original Title)",
                    'countryId' => null,
                    'language' => null,
                    'languageId' => null,
                    'comment' => array()
                );
            }
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $comments = array();
                    if (
                        isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    ) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->akas[] = array(
                        'title' => isset($edge->node->text) ?
                                        $edge->node->text : null,
                        'country' => isset($edge->node->country->text) ?
                                        ucwords($edge->node->country->text) : 'Unknown',
                        'countryId' => isset($edge->node->country->id) ?
                                            $edge->node->country->id : null,
                        'language' => isset($edge->node->language->text) ?
                                            ucwords($edge->node->language->text) : null,
                        'languageId' => isset($edge->node->language->id) ?
                                            $edge->node->language->id : null,
                        'comment' => $comments
                    );
                }
            }
        }
        return $this->akas;
    }

    #-------------------------------------------------------[ MPAA / PG / FSK ]---
    /**
     * Get the MPAA rating / Parental Guidance / Age rating for this title by country
     * @return list<array{country: string|null, rating: string|null, content: string|null}> comment whithout brackets
     * @see IMDB Parental Guidance page / (parentalguide)
     */
    public function mpaa(): array
    {
        if (empty($this->mpaas)) {
            $query = <<<EOF
country {
  text
}
rating
attributes {
  text
}
EOF;
            $data = $this->graphQlGetAll("Mpaa", "certificates", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $comments = array();
                    if (
                        isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    ) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->mpaas[] = array(
                        'country' => isset($edge->node->country->text) ?
                                        $edge->node->country->text : null,
                        'rating' => isset($edge->node->rating) ?
                                        $edge->node->rating : null,
                        'comment' => $comments
                    );
                }
            }
        }
        return $this->mpaas;
    }

    #-------------------------------------------------------[ ParentsGuide ]---
    /** Info for parents like Violence, Drugs. Alcohol etc
     * @param bool $spoil boolean if true spoilers are also included.
     * @return array<string, array{severity: string, severityVotedFor: int, totalSeverityVotes: int, guideItems: list<array{isSpoiler: bool, guideText: string}>}>
     */
    public function parentsGuide(bool $spoil = false): array
    {
        $filter = '';
        if ($spoil === false) {
            $filter = '(filter: {spoilers: EXCLUDE_SPOILERS})';
        }

        $query = <<<EOF
query ParentsGuide (\$id: ID!) {
  title(id: \$id) {
    parentsGuide {
      categories $filter {
        category {
          id
        }
        severity {
          text
          votedFor
        }
        totalSeverityVotes
        guideItems(first: 9999) {
          edges {
            node {
              isSpoiler
              text {
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
        $data = $this->graphql->query($query, "ParentsGuide", ["id" => "tt$this->imdbID"]);
        if (!isset($data->title)) {
            return $this->parentsGuide;
        }
        if (
            isset($data->title->parentsGuide->categories) &&
            is_array($data->title->parentsGuide->categories) &&
            count($data->title->parentsGuide->categories) > 0
        ) {
            foreach ($data->title->parentsGuide->categories as $category) {
                $guideItems = array();
                if (
                    isset($category->guideItems->edges) &&
                    is_array($category->guideItems->edges) &&
                    count($category->guideItems->edges) > 0
                ) {
                    foreach ($category->guideItems->edges as $edge) {
                        $guideItems[] = array(
                            'isSpoiler' => $edge->node->isSpoiler,
                            'guideText' => isset($edge->node->text->plainText) ?
                                                $edge->node->text->plainText : null
                        );
                    }
                }
                $this->parentsGuide[strtolower($category->category->id)] = array(
                    'severity' => isset($category->severity->text) ?
                                        $category->severity->text : null,
                    'severityVotedFor' => isset($category->severity->votedFor) ?
                                                $category->severity->votedFor : null,
                    'totalSeverityVotes' => isset($category->totalSeverityVotes) ?
                                                $category->totalSeverityVotes : null,
                    'guideItems' => $guideItems
                );
            }
        }
        return $this->parentsGuide;
    }

    #----------------------------------------------[ Position in the "Top250" ]---
    /**
     * Find the position of a movie or tv show in the top 250 ranked movies or tv shows
     * @return int position a number between 1..250 if ranked, 0 otherwise
     * @author Ed
     */
    public function top250(): int
    {
        if ($this->mainTop250 === 0) {
            $query = <<<EOF
query TopRated(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      topRanking {
        rank
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "TopRated", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->topRanking->rank)) {
                if ($data->title->ratingsSummary->topRanking->rank <= 250) {
                    $this->mainTop250 = $data->title->ratingsSummary->topRanking->rank;
                }
            }
        }
        return $this->mainTop250;
    }

    #=====================================================[ /plotsummary page ]===
    /** Get movie plots without Spoilers
     * @param bool $spoil boolean if true spoilers are also included, default: false.
     * @return list<array{plot: string, author: string}>
     * @see IMDB page /plotsummary
     */
    public function plot(bool $spoil = false): array
    {
        if (empty($this->plot)) {
            $filter = $spoil === false ? ',filter:{spoilers:EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
query Plots(\$id: ID!) {
  title(id: \$id) {
    plots(first: 9999$filter) {
      edges {
        node {
          author
          plotText {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Plots", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->plot;
            }
            if (
                isset($data->title->plots->edges) &&
                is_array($data->title->plots->edges) &&
                count($data->title->plots->edges) > 0
            ) {
                foreach ($data->title->plots->edges as $edge) {
                    if (!empty($edge->node->plotText->plainText)) {
                        $this->plot[] = array(
                            'plot' => $edge->node->plotText->plainText,
                            'author' => isset($edge->node->author) ?
                                            $edge->node->author : null
                        );
                    }
                }
            }
        }
        return $this->plot;
    }

    #========================================================[ /taglines page ]===
    /**
     * Get all available taglines for the movie
     * @return string[] taglines
     * @see IMDB page /taglines
     */
    public function tagline(): array
    {
        if (empty($this->taglines)) {
            $query = <<<EOF
query Taglines(\$id: ID!) {
  title(id: \$id) {
    taglines(first: 9999) {
      edges {
        node {
          text
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Taglines", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->taglines;
            }
            if (
                isset($data->title->taglines->edges) &&
                is_array($data->title->taglines->edges) &&
                count($data->title->taglines->edges) > 0
            ) {
                foreach ($data->title->taglines->edges as $edge) {
                    if (!empty($edge->node->text)) {
                        $this->taglines[] = $edge->node->text;
                    }
                }
            }
        }
        return $this->taglines;
    }

    #=====================================================[ /fullcredits page ]===
    #----------------------------------------------------------------[ PrincipalCredits ]---
    /**
     * Get the PrincipalCredits for this title (limited to 3 items per category)
     * @return array<string, list<array{name: string, imdbid: string}>>
     * Not all categories are always available
     */
    public function principalCredits(): array
    {
        if (empty($this->creditsPrincipal)) {
            $query = <<<EOF
query PrincipalCredits(\$id: ID!) {
  title(id: \$id) {
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
  }
}
EOF;
            $data = $this->graphql->query($query, "PrincipalCredits", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->creditsPrincipal;
            }
            if (
                isset($data->title->principalCredits) &&
                is_array($data->title->principalCredits) &&
                count($data->title->principalCredits) > 0
            ) {
                foreach ($data->title->principalCredits as $value) {
                    $category = 'Unknown';
                    $credits = array();
                    if (!empty($value->credits[0]->category->text)) {
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
                            $credits[] = array(
                                'name' => isset($credit->name->nameText->text) ?
                                                $credit->name->nameText->text : null,
                                'imdbid' => isset($credit->name->id) ?
                                                str_replace('nm', '', $credit->name->id) : null
                            );
                        }
                    } elseif ($category === 'Unknown') {
                            continue;
                    }
                    $this->creditsPrincipal[$category] = $credits;
                }
            }
        }
        return $this->creditsPrincipal;
    }

    #----------------------------------------------------------------[ Actors]---
    /**
     * Get the actors/cast members for this title
     * @return list<array{imdb: string|null, name: string|null, alias: string|null, credited: bool, character: list<string>, comment: list<string>, thumb: string|null}>
     * @see IMDB page /fullcredits
     */
    public function cast(): array
    {
        if (empty($this->creditsCast)) {
            $filter = ', filter:{categories:["cast"]}';
            $query = <<<EOF
name {
  nameText {
    text
  }
  id
  primaryImage {
    url
    width
    height
  }
}
... on Cast {
  characters {
    name
  }
  attributes {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("CreditQuery", "credits", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $castCharacters = array();
                    if (
                        isset($edge->node->characters) &&
                        is_array($edge->node->characters) &&
                        count($edge->node->characters) > 0
                    ) {
                        foreach ($edge->node->characters as $character) {
                            if (!empty($character->name)) {
                                $castCharacters[] = $character->name;
                            }
                        }
                    }
                    $comments = array();
                    $nameAlias = null;
                    $credited = true;
                    if (
                        isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    ) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                if (strpos($attribute->text, "as ") !== false) {
                                    $nameAlias = trim(ltrim($attribute->text, "as"));
                                } elseif (stripos($attribute->text, "uncredited") !== false) {
                                    $credited = false;
                                } else {
                                    $comments[] = $attribute->text;
                                }
                            }
                        }
                    }
                    $imgUrl = null;
                    if (!empty($edge->node->name->primaryImage->url)) {
                        $fullImageWidth = $edge->node->name->primaryImage->width;
                        $fullImageHeight = $edge->node->name->primaryImage->height;
                        $newImageWidth = $this->config->castThumbnailWidth;
                        $newImageHeight = $this->config->castThumbnailHeight;
                        $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $imgUrl = $img . $parameter;
                    }
                    $this->creditsCast[] = array(
                        'imdb' => isset($edge->node->name->id) ?
                                        str_replace('nm', '', $edge->node->name->id) : null,
                        'name' => isset($edge->node->name->nameText->text) ?
                                        $edge->node->name->nameText->text : null,
                        'alias' => $nameAlias,
                        'credited' => $credited,
                        'character' => $castCharacters,
                        'comment' => $comments,
                        'thumb' => $imgUrl
                    );
                }
            }
        }
        return $this->creditsCast;
    }

    #-------------------------------------------------------------[ Directors ]---
    /**
     * Get the director(s) of the movie
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function director(): array
    {
        if (!empty($this->creditsDirector)) {
            return $this->creditsDirector;
        }
        $directorData = $this->creditHelper("director");

        return $this->creditsDirector = $directorData;
    }

    #-------------------------------------------------------------[ Cinematographers ]---
    /**
     * Get the cinematographer of the title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function cinematographer(): array
    {
        if (!empty($this->creditsCinematographer)) {
            return $this->creditsCinematographer;
        }
        $cinematographerData = $this->creditHelper("cinematographer");

        return $this->creditsCinematographer = $cinematographerData;
    }

    #---------------------------------------------------------------[ Writers ]---
    /** Get the writer(s)
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function writer(): array
    {
        if (!empty($this->creditsWriter)) {
            return $this->creditsWriter;
        }
        $writerData = $this->creditHelper("writer");
        return $this->creditsWriter = $writerData;
    }

    #-------------------------------------------------------------[ Producers ]---
    /** Obtain the producer credits of this title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function producer(): array
    {
        if (!empty($this->creditsProducer)) {
            return $this->creditsProducer;
        }
        $producerData = $this->creditHelper("producer");
        return $this->creditsProducer = $producerData;
    }

    #-------------------------------------------------------------[ Composers ]---
    /** Obtain the composer(s) ("Original Music by...")
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function composer(): array
    {
        if (!empty($this->creditsComposer)) {
            return $this->creditsComposer;
        }
        $composerData = $this->creditHelper("composer");
        return $this->creditsComposer = $composerData;
    }

    #-------------------------------------------------------------[ Stunts ]---
    /** Obtain the stunts credits of this title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function stunts(): array
    {
        if (!empty($this->creditsStunts)) {
            return $this->creditsStunts;
        }
        $stuntsData = $this->creditHelper("stunts");
        return $this->creditsStunts = $stuntsData;
    }

    #-------------------------------------------------------------[ Thanks ]---
    /** Obtain thanks credits of this title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function thanks(): array
    {
        if (!empty($this->creditsThanks)) {
            return $this->creditsThanks;
        }
        $thanksData = $this->creditHelper("thanks");
        return $this->creditsThanks = $thanksData;
    }

    #-------------------------------------------------------------[ Visual Effects ]---
    /** Obtain Visual Effects credits of this title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function visualEffects(): array
    {
        if (!empty($this->creditsVisualEffects)) {
            return $this->creditsVisualEffects;
        }
        $visualEffectsData = $this->creditHelper("visual_effects");
        return $this->creditsVisualEffects = $visualEffectsData;
    }

        #-------------------------------------------------------------[ Special Effects ]---
    /** Obtain Special Effects credits of this title
     * @return array<mixed>
     * @phpstan-return CreditsArrayDef
     * @see IMDB page /fullcredits
     */
    public function specialEffects(): array
    {
        if (!empty($this->creditsSpecialEffects)) {
            return $this->creditsSpecialEffects;
        }
        $specialEffectsData = $this->creditHelper("special_effects");
        return $this->creditsSpecialEffects = $specialEffectsData;
    }

    #====================================================[ /crazycredits page ]===
    #----------------------------------------------------[ CrazyCredits Array ]---
    /**
     * Get the Crazy Credits
     * @return string[]
     * @see IMDB page /crazycredits
     */
    public function crazyCredit(): array
    {
        if (empty($this->crazyCredits)) {
            $query = <<<EOF
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("CrazyCredits", "crazyCredits", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->text->plainText)) {
                        $this->crazyCredits[] = preg_replace('/\s\s+/', ' ', $edge->node->text->plainText);
                    }
                }
            }
        }
        return $this->crazyCredits;
    }

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @see IMDB page /episodes
     * @param bool $thumb boolean true: thumbnail (cropped from center 224x126), false: large (max width 1000 pixels)
     * @param int $yearbased This gives user control if returned episodes are yearbased or season based
     * @version The outer array keys reflects the real season seasonnumber! Episodes can start at 0 (pilot episode)
     * @return array<int, array<int, list<array{imdbid: string, title: string, airdate: string|null, airdateParts: array{day: int|null, month: int|null, year: int|null}|null, plot: string|null, episode: int|string, imgUrl: string|null}>>>
     */
    public function episode(bool $thumb = true, int $yearbased = 0): array
    {
        if ($this->movietype() === "TV Series" || $this->movietype() === "TV Mini Series") {
            if (empty($this->seasonEpisodes)) {
                // Check if season or year based
                $seasonsData = $this->seasonYearCheck($yearbased);
                if ($seasonsData === false) {
                    return $this->seasonEpisodes;
                }
                if (is_array($seasonsData) && count($seasonsData) > 0) {
                    foreach ($seasonsData as $seasonsDataEdge) {
                        if (empty($seasonsDataEdge->node->text)) {
                            return $this->seasonEpisodes;
                        }
                        $seasonYear = $seasonsDataEdge->node->text;
                        $filter = $this->buildFilter($seasonYear);
                        if ($seasonYear === "Unknown") { //this is intended capitol
                            $seasonYear = -1;
                        }
                        // Get all episodes
                        $episodesData = $this->graphQlGetAllEpisodes($filter);
                        $episodes = array();
                        foreach ($episodesData as $edge) {
                            $dateParts = array(
                                'day' => isset($edge->node->releaseDate->day) ?
                                            $edge->node->releaseDate->day : null,
                                'month' => isset($edge->node->releaseDate->month) ?
                                                $edge->node->releaseDate->month : null,
                                'year' => isset($edge->node->releaseDate->year) ?
                                                $edge->node->releaseDate->year : null
                            );
                            $airDate = $this->buildDateString($dateParts);
                            $epNumber = null;
                            if (isset($edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber)) {
                                $epNumber = $edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber;
                                // Unknown episodes get a number to keep them separate.
                                if ($epNumber === "unknown") {
                                    $epNumber = -1;
                                }
                            }
                            $imgUrl = null;
                            if (!empty($edge->node->primaryImage->url)) {
                                $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                                if ($thumb === true) {
                                    $fullImageWidth = $edge->node->primaryImage->width;
                                    $fullImageHeight = $edge->node->primaryImage->height;
                                    $newImageWidth = $this->config->episodeThumbnailWidth;
                                    $newImageHeight = $this->config->episodeThumbnailHeight;
                                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                    $imgUrl = $img . $parameter;
                                } else {
                                    $imgUrl = $img . 'QL100_SX1000_.jpg';
                                }
                            }
                            $episode = array(
                                    'imdbid' => isset($edge->node->id) ?
                                                    str_replace('tt', '', $edge->node->id) : null,
                                    'title' => isset($edge->node->titleText->text) ?
                                                    $edge->node->titleText->text : null,
                                    'airdate' => $airDate,
                                    'airdateParts' => $dateParts,
                                    'plot' => isset($edge->node->plot->plotText->plainText) ?
                                                    $edge->node->plot->plotText->plainText : null,
                                    'season' => $seasonYear,
                                    'episode' => $epNumber,
                                    'imgUrl' => $imgUrl
                                );
                            $episodes[] = $episode;
                        }
                        $this->seasonEpisodes[$seasonYear] = $episodes;
                    }
                }
            }
        }
        return $this->seasonEpisodes;
    }

    #-----------------------------------------------------------[ IsOngoing TV Series ]---
     /**
     * Boolean flag if this is a still running tv series or ended
     * @return bool false if ended, true if still running or null (not a tv series)
     */
    public function isOngoing(): bool
    {
        $query = <<<EOF
query IsOngoing(\$id: ID!) {
  title(id: \$id) {
    episodes {
      isOngoing
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "IsOngoing", ["id" => "tt$this->imdbID"]);
        $this->isOngoing = $data->title->episodes->isOngoing;
        return (bool) $this->isOngoing;
    }

    #===========================================================[ /goofs page ]===
    #-----------------------------------------------------------[ Goofs Array ]---
    /** Get the goofs
     * @param bool $spoil boolean if true spoilers are also included.
     * @return array<array-key, array<array{content: string, isSpoiler: bool}>>
     * @see IMDB page /goofs
     */
    public function goof(bool $spoil = false): array
    {
        if (empty($this->goofs)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
category {
  id
}
displayableArticle {
  body {
    plainText
  }
}
isSpoiler
EOF;
            $data = $this->graphQlGetAll("Goofs", "goofs", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('_', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->goofs[$categoryId][] = array(
                        'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                        $edge->node->displayableArticle->body->plainText : null,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->goofs;
    }

    #==========================================================[ /quotes page ]===
    /** Get the quotes for a given movie
     * @return array<array<string>|string>
     * @see IMDB page /quotes
     */
    public function quote(): array
    {
        if (empty($this->quotes)) {
            $query = <<<EOF
displayableArticle {
  body {
    plaidHtml
  }
}
EOF;
            $data = $this->graphQlGetAll("Quotes", "quotes", $query);
            if (count($data) > 0) {
                foreach ($data as $key => $edge) {
                    if (!empty($edge->node->displayableArticle->body->plaidHtml)) {
                        $quoteParts = explode("<li>", $edge->node->displayableArticle->body->plaidHtml);
                        foreach ($quoteParts as $quoteItem) {
                            if (!empty(trim(strip_tags($quoteItem)))) {
                                $this->quotes[$key][] = trim(strip_tags($quoteItem));
                            }
                        }
                    }
                }
            }
        }
        return $this->quotes;
    }

    #==========================================================[ /trivia page ]===
    /**
     * Get the trivia info
     * @param bool $spoil if true spoilers are also included.
     * @phpstan-return TriviaArrayDef
     * @see IMDB page /trivia
     */
    public function trivia(bool $spoil = false): array
    {
        if (empty($this->trivias)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
category {
  id
}
displayableArticle {
  body {
    plainText
  }
}
isSpoiler
trademark {
  plainText
}
relatedNames {
  nameText {
    text
  }
  id
}
EOF;
            $data = $this->graphQlGetAll("Trivia", "trivia", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $names = array();
                    if (
                        isset($edge->node->relatedNames) &&
                        is_array($edge->node->relatedNames) &&
                        count($edge->node->relatedNames) > 0
                    ) {
                        foreach ($edge->node->relatedNames as $name) {
                            $names[] = array(
                                'name' => isset($name->nameText->text) ?
                                                $name->nameText->text : null,
                                'id' => isset($name->id) ?
                                            str_replace('nm', '', $name->id) : null
                            );
                        }
                    }
                    // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('-', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->trivias[$categoryId][] = array(
                        'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                        preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText) : null,
                        'names' => $names,
                        'trademark' => isset($edge->node->trademark->plainText) ?
                                            $edge->node->trademark->plainText : null,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->trivias;
    }

    #======================================================[ Soundtrack ]===
    /**
     * Get the soundtrack listing
     * @return list<array{soundtrack: string, credits: list<string>, creditSplit: array{creditors: list<array{creditType: string|null, name: string, nameId: string|null, attribute: string|null}>, comment: list<string>}}>
     * @see IMDB page /soundtrack
     */
    public function soundtrack(): array
    {
        if (empty($this->soundtracks)) {
            $query = <<<EOF
text
comments {
  plaidHtml
}
EOF;
            $data = $this->graphQlGetAll("Soundtrack", "soundtrack", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $title = 'Unknown';
                    if (!empty($edge->node->text)) {
                        $title = trim($edge->node->text);
                    }
                    $credits = array();
                    $creditComments = array();
                    $crediters = array();
                    if (
                        isset($edge->node->comments) &&
                        is_array($edge->node->comments) &&
                        count($edge->node->comments) > 0
                    ) {
                        foreach ($edge->node->comments as $comment) {
                            if (!empty(trim(strip_tags($comment->plaidHtml)))) {
                                $comment = $comment->plaidHtml;
                            } else {
                                continue;
                            }
                            if (stripos($comment, "arrangement with") === false) {
                                // check and replace :
                                if (($posArrangement = stripos($comment, ":")) !== false) {
                                    $comment = substr_replace($comment, " by", $posArrangement, strlen(":"));
                                }
                                if (($posBy = stripos($comment, "by")) !== false) {
                                    // split at "by"
                                    $creditRaw = substr($comment, $posBy + 2);
                                    $creditType = trim(substr($comment, 0, $posBy + 2), "[] ");
                                    // replace characters (& and) with , and explode it in array
                                    $patterns = array(
                                        '/^.*?\K&amp;(?![^>]*\/\s*a\s*>)/',
                                        '/^.*?\Kand(?![^>]*\/\s*a\s*>)/'
                                    );
                                    $creditRaw = preg_replace($patterns, ',', $creditRaw);
                                    $creditRawParts = explode(",", $creditRaw);
                                    $creditRawParts = array_values(array_filter($creditRawParts, fn($value) => (bool) $value));
                                    // loop $creditRawParts array
                                    foreach ($creditRawParts as $value) {
                                        // check if there is any text after the anchor tag
                                        $attribute = '';
                                        if (($posAttribute = strripos($value, ">")) !== false) {
                                            $valueExtention = trim(substr($value, $posAttribute + 1), ' ()[]"');
                                            if (!empty($valueExtention)) {
                                                $attribute = $valueExtention;
                                            }
                                        }
                                        // get anchor links
                                        libxml_use_internal_errors(true);
                                        $doc = new \DOMDocument();
                                        $doc->loadHTML('<?xml encoding="UTF-8">' . $value);
                                        $anchors = $doc->getElementsByTagName('a');
                                        // check if $anchors contains any <a> records
                                        if ($anchors->length > 0) {
                                            $href = $anchors->item(0)->attributes->getNamedItem('href')->nodeValue;
                                            $nameId = preg_replace('/[^0-9]+/', '', $href);
                                            $name = trim($anchors->item(0)->nodeValue);
                                        } else {
                                            // no anchor, text only, check if id is present in text form
                                            $nameId = '';
                                            $name = trim($value, "[] ");
                                            if (preg_match('/(nm?\d+)/', $value, $match)) {
                                                $nameId = preg_replace('/[^0-9]+/', '', $match[0]);
                                                $name = '';
                                            }
                                        }
                                        $crediters[] = array(
                                            'creditType' => $creditType,
                                            'name' => $name,
                                            'nameId' => $nameId,
                                            'attribute' => $attribute
                                        );
                                    }
                                } else {
                                    // no by, treat as comment in plain text
                                    $creditComments[] = trim(strip_tags($comment));
                                }
                            } else {
                                // no arrangement with, treat as comment in plain text
                                $creditComments[] = trim(strip_tags($comment));
                            }
                            // add data to $credits as plain text
                            $credits[] = trim(strip_tags($comment));
                        }
                    }
                    $this->soundtracks[] = array(
                        'soundtrack' => $title,
                        'credits' => $credits,
                        'creditSplit' => array('creditors' => $crediters, 'comment' => $creditComments)
                    );
                }
            }
        }
        return $this->soundtracks;
    }

    #=======================================================[ /locations page ]===
    /**
     * Filming locations
     * real: Real filming location, movie: location in the movie
     * @return list<array{real: string, movie: list<string>}>
     * @see IMDB page /locations
     */
    public function location(): array
    {
        if (empty($this->locations)) {
            $query = <<<EOF
displayableProperty {
  qualifiersInMarkdownList {
    plainText
  }
  value {
    plainText
  }
}
EOF;
            $data = $this->graphQlGetAll("FilmingLocations", "filmingLocations", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $movie = array();
                    if (
                        isset($edge->node->displayableProperty->qualifiersInMarkdownList) &&
                        is_array($edge->node->displayableProperty->qualifiersInMarkdownList) &&
                        count($edge->node->displayableProperty->qualifiersInMarkdownList) > 0
                    ) {
                        foreach ($edge->node->displayableProperty->qualifiersInMarkdownList as $attribute) {
                            if (!empty($attribute->plainText)) {
                                $movie[] = $attribute->plainText;
                            }
                        }
                    }
                    $this->locations[] = array(
                        'real' => isset($edge->node->displayableProperty->value->plainText) ?
                                        $edge->node->displayableProperty->value->plainText : null,
                        'movie' => $movie
                    );
                }
            }
        }
        return $this->locations;
    }

    #==================================================[ /companycredits page ]===

    #---------------------------------------------------[ Producing Companies ]---

    /** Info about Production Companies
     * @phpstan-return CompCredits
     * @see IMDB page /companycredits
     */
    public function prodCompany(): array
    {
        if (empty($this->compCreditsProd)) {
            $this->compCreditsProd = $this->companyCredits("production");
        }
        return $this->compCreditsProd;
    }

    #------------------------------------------------[ Distributing Companies ]---

    /** Info about distributors
     * @phpstan-return CompCredits
     * @see IMDB page /companycredits
     */
    public function distCompany(): array
    {
        if (empty($this->compCreditsDist)) {
            $this->compCreditsDist = $this->companyCredits("distribution");
        }
        return $this->compCreditsDist;
    }

    #---------------------------------------------[ Special Effects Companies ]---

    /** Info about Special Effects companies
     * @phpstan-return CompCredits
     * @see IMDB page /companycredits
     */
    public function specialCompany(): array
    {
        if (empty($this->compCreditsSpecial)) {
            $this->compCreditsSpecial = $this->companyCredits("specialEffects");
        }
        return $this->compCreditsSpecial;
    }

    #-------------------------------------------------------[ Other Companies ]---

    /** Info about other companies
     * @phpstan-return CompCredits
     * @see IMDB page /companycredits
     */
    public function otherCompany(): array
    {
        if (empty($this->compCreditsOther)) {
            $this->compCreditsOther = $this->companyCredits("miscellaneous");
        }
        return $this->compCreditsOther;
    }

    #-------------------------------------------------------[ Connections ]---
    /** Info about connections or references with other titles
     * @return array<array<array{titleId: string|null, titleName: string|null, titleType: string|null, year: string|null, endYear: string|null, seriesName: string|null, description: string|null}>>
     * @see IMDB page /companycredits
     */
    public function connection(): array
    {
        if (empty($this->connections)) {
            $query = <<<EOF
associatedTitle {
  id
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
  series {
    series {
      titleText {
        text
      }
    }
  }
}
category {
  id
}
description {
  plainText
}
EOF;
            $edges = $this->graphQlGetAll("Connections", "connections", $query);
            if (count($edges) > 0) {
                foreach ($edges as $edge) {
                   // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('_', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->connections[$categoryId][] = array(
                        'titleId' => isset($edge->node->associatedTitle->id) ?
                                        str_replace('tt', '', $edge->node->associatedTitle->id) : null,
                        'titleName' => $edge->node->associatedTitle->titleText?->text,
                        'titleType' => $edge->node->associatedTitle->titleType?->text,
                        'year' => $edge->node->associatedTitle->releaseYear?->year,
                        'endYear' => $edge->node->associatedTitle->releaseYear?->endYear,
                        'seriesName' => $edge->node->associatedTitle?->series?->series?->titleText?->text,
                        'description' => $edge->node->description?->plainText
                    );
                }
            }
        }
        return $this->connections;
    }

    #-------------------------------------------------------[ External sites ]---
    /** external websites with info of this title, excluding external reviews.
     * @return array<array<array{label: string, url: string, language: list<string>}>>
     * @see IMDB page /externalsites
     */
    public function extSites(): array
    {
        if (empty($this->externalSites)) {
            $query = <<<EOF
label
url
externalLinkCategory {
  id
}
externalLinkLanguages {
  text
}
EOF;
            $filter = ' filter: {excludeCategories: "review"}';
            $edges = $this->graphQlGetAll("ExternalSites", "externalLinks", $query, $filter);
            if (count($edges) > 0) {
                foreach ($edges as $edge) {
                    $language = array();
                    if (
                        isset($edge->node->externalLinkLanguages) &&
                        is_array($edge->node->externalLinkLanguages) &&
                        count($edge->node->externalLinkLanguages) > 0
                    ) {
                        foreach ($edge->node->externalLinkLanguages as $lang) {
                            if (!empty($lang->text)) {
                                $language[] = $lang->text;
                            }
                        }
                    }
                    $this->externalSites[$edge->node->externalLinkCategory->id][] = array(
                        'label' => $edge->node->label,
                        'url' => $edge->node->url,
                        'language' => $language
                    );
                }
            }
        }
        return $this->externalSites;
    }

    #========================================================[ /Box Office page ]===
    #-------------------------------------------------------[ productionBudget ]---
    /** Info about productionBudget
     * @return array<array-key, array{productionBudget: array{amount: int|float|null, currency: string|null}|null}>
     * @see IMDB page /title
     */
    public function budget(): array
    {
        if (empty($this->productionBudget)) {
            $query = <<<EOF
query ProductionBudget(\$id: ID!) {
  title(id: \$id) {
    productionBudget {
      budget {
        amount
        currency
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "ProductionBudget", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->productionBudget;
            }
            if (!empty($data->title->productionBudget->budget->amount)) {
                $this->productionBudget = array(
                    'amount' => $data->title->productionBudget->budget->amount,
                    'currency' => isset($data->title->productionBudget->budget->currency) ?
                                        $data->title->productionBudget->budget->currency : null
                );
            }
        }
        return $this->productionBudget;
    }

    #-------------------------------------------------------[ rankedLifetimeGrosses ]---
    /** Info about Grosses, ranked by amount
     * @return list<array{areatype: string, amount: string|null, currency: string|null}>
     * @see IMDB page /title
     */
    public function gross(): array
    {
        if (empty($this->grosses)) {
            $query = <<<EOF
query RankedLifetimeGrosses(\$id: ID!) {
  title(id: \$id) {
    rankedLifetimeGrosses(first: 9999) {
      edges {
        node {
          boxOfficeAreaType {
            text
          }
          total {
            amount
            currency
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "RankedLifetimeGrosses", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->grosses;
            }
            if (
                isset($data->title->rankedLifetimeGrosses->edges) &&
                is_array($data->title->rankedLifetimeGrosses->edges) &&
                count($data->title->rankedLifetimeGrosses->edges) > 0
            ) {
                foreach ($data->title->rankedLifetimeGrosses->edges as $edge) {
                    if (!empty($edge->node->boxOfficeAreaType->text)) {
                        $this->grosses[] = array(
                            'areatype' => $edge->node->boxOfficeAreaType->text,
                            'amount' => $edge->node->total->amount,
                            'currency' => $edge->node->total->currency
                        );
                    }
                }
            }
        }
        return $this->grosses;
    }

    #========================================================[ /keywords page ]===
    /**
     * Get all keywords from movie
     * @return array<array-key, string> keywords
     * @see IMDB page /keywords
     */
    public function keyword(): array
    {
        if (empty($this->keywords)) {
            $query = <<<EOF
keyword {
  text {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("Keywords", "keywords", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->keyword->text->text)) {
                        $this->keywords[] = $edge->node->keyword->text->text;
                    }
                }
            }
        }
        return $this->keywords;
    }

    #========================================================[ /Alternate versions page ]===
    /**
     * Get the Alternate Versions for a given movie
     * @return string[]
     * @see IMDB page /alternateversions
     */
    public function alternateVersion(): array
    {
        if (empty($this->alternateversions)) {
            $query = <<<EOF
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("AlternateVersions", "alternateVersions", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->text->plainText)) {
                        $this->alternateversions[] = $edge->node->text->plainText;
                    }
                }
            }
        }
        return $this->alternateversions;
    }

    #-------------------------------------------------[ Main images ]---
    /**
     * Get image URLs for (default 6) pictures from photo page
     * @param int $amount, int for how many images, max = 9999
     * @param bool $thumb
     *      true: height is always the same (set in config), width is variable!
     *      false: untouched max width 1000 pixels
     * @return list<string>
     */
    public function mainphoto(int $amount = 6, bool $thumb = true): array
    {
        if (empty($this->mainPhoto)) {
            $query = <<<EOF
query MainPhoto(\$id: ID!) {
  title(id: \$id) {
    images(first: $amount) {
      edges {
        node {
          url
          width
          height
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MainPhoto", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->mainPhoto;
            }
            if (
                isset($data->title->images->edges) &&
                is_array($data->title->images->edges) &&
                count($data->title->images->edges) > 0
            ) {
                foreach ($data->title->images->edges as $edge) {
                    if (!empty($edge->node->url)) {
                        $imgUrl = str_replace('.jpg', '', $edge->node->url);
                        if ($thumb === true) {
                            $fullImageWidth = $edge->node->width;
                            $fullImageHeight = $edge->node->height;
                            $newImageHeight = $this->config->mainphotoThumbnailHeight;
                            // calculate new width
                            $newImageWidth = $this->imageFunctions->thumbUrlNewWidth($fullImageWidth, $fullImageHeight, $newImageHeight);
                            $this->mainPhoto[] = $imgUrl . 'QL75_UX' . $newImageWidth . '_.jpg';
                        } else {
                            $this->mainPhoto[] = $imgUrl . 'QL100_UX1000_.jpg';
                        }
                    }
                }
            }
        }
        return $this->mainPhoto;
    }

    #-------------------------------------------------[ Trailer ]---
    /**
     * Get video URL's and images from videogallery page (Trailers only)
     * @param bool $customThumb Default: true
     *                          true: old style imdb custom thumb (fixed 200x150)
     *                          false: new style (fixed 500x281)
     *
     * @return list<array{name: string, runtime: int|null, description: string|null, titleName: string|null, titleYear: int|null, videoUrl: string|null, videoImageUrl: string|null}>
     */
    public function trailer(bool $customThumb = true): array
    {
        if (empty($this->trailers)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    primaryVideos(first: 9999) {
      edges {
        node {
          id
          name {
            value
          }
          thumbnail {
            url
            width
            height
          }
          runtime {
            value
          }
          contentType {
            displayName {
              value
            }
          }
          description {
            value
          }
          primaryTitle {
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
  }
}
EOF;
            $data = $this->graphql->query($query, "Video", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->trailers;
            }
            if (
                isset($data->title->primaryVideos->edges) &&
                is_array($data->title->primaryVideos->edges) &&
                count($data->title->primaryVideos->edges) > 0
            ) {
                foreach ($data->title->primaryVideos->edges as $edge) {
                    // check contentType is set and contentType == Trailer
                    if (isset($edge->node->contentType->displayName->value) && $edge->node->contentType->displayName->value !== "Trailer") {
                        continue;
                    }
                    // Embed URL
                    $embedUrl = null;
                    if (!empty($edge->node->id)) {
                        $embedUrl = "https://www.imdb.com/video/imdb/" . $edge->node->id . "/imdb/embed";
                        // Check if embed URL not == 404 or 401
                        $headers = @get_headers($embedUrl);
                        preg_match('{HTTP\/\S*\s(\d{3})}', $headers[0], $matches);
                        $statusCode = $matches[1] ?? null;
                        if (in_array($statusCode, ['404', '401'], true)) {
                            continue;
                        }
                    }
                    $titleName = isset($edge->node->primaryTitle->titleText->text) ?
                                    $edge->node->primaryTitle->titleText->text : null;
                    $runtime = isset($edge->node->runtime->value) ?
                                    $edge->node->runtime->value : null;
                    $thumbnailUrl = isset($edge->node->thumbnail->url) ?
                                        $edge->node->thumbnail->url : null;
                    $thumbUrl = '';
                    if (!empty($thumbnailUrl)) {
                        //CustomThumb
                        if ($customThumb !== false) {
                            $timeString = '';
                            if (!empty($runtime)) {
                                $timeString .= sprintf("%02d", ($runtime / 60)) . '%253A' . sprintf("%02d", $runtime % 60);
                            }
                            $title = '';
                            if (!empty($titleName)) {
                                $title = rawurlencode(rawurlencode($titleName));
                            }
                            $thumbUrl = str_replace('.jpg', '', $thumbnailUrl);
                            $thumbUrl .= '1_SP330,330,0,C,0,0,0_CR65,90,200,150_'
                                        . 'PIimdb-blackband-204-14,TopLeft,0,0_'
                                        . 'PIimdb-blackband-204-28,BottomLeft,0,1_CR0,0,200,150_'
                                        . 'PIimdb-bluebutton-big,BottomRight,-1,-1_'
                                        . 'ZATrailer,4,123,16,196,verdenab,8,255,255,255,1_'
                                        . 'ZAon%2520IMDb,4,1,14,196,verdenab,7,255,255,255,1_'
                                        . 'ZA' . $timeString . ',164,1,14,36,verdenab,7,255,255,255,1_'
                                        . 'ZA' . $title . ',4,138,14,176,arialbd,7,255,255,255,1_.jpg';
                        } else {
                            // New style thumb
                            $fullImageWidth = $edge->node->thumbnail->width;
                            $fullImageHeight = $edge->node->thumbnail->height;
                            $img = str_replace('.jpg', '', $thumbnailUrl);
                            $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
                             $thumbUrl = $img . $parameter;
                        }
                    }
                    $this->trailers[] = array(
                        'name' => $edge->node->name->value,
                        'runtime' => $runtime,
                        'description' => $edge->node->description->value,
                        'titleName' => $titleName,
                        'titleYear' => (int) $edge->node->primaryTitle->releaseYear->year,
                        'videoUrl' => $embedUrl,
                        'videoImageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->trailers;
    }

    #-------------------------------------------------[ Video ]---
    /**
     * Get all video URL's and images from videogallery page
     * @return array<string, list<array{id: array<string>|string|null, name: string, runtime: string, description: string, titleName: string, titleYear: int, playbackUrl: string|null, imageUrl: string|null}>>
     */
    public function video(): array
    {
        if (empty($this->videos)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    videoStrip(first:9999) {
      edges {
        node {
          id
          name {
            value
          }
          runtime {
            value
          }
          contentType {
            displayName {
              value
            }
          }
          description {
            value
          }
          thumbnail {
            url
            width
            height
          }
          primaryTitle {
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
  }
}
EOF;
            $data = $this->graphql->query($query, "Video", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->videos;
            }
            if (
                isset($data->title->videoStrip->edges) &&
                is_array($data->title->videoStrip->edges) &&
                count($data->title->videoStrip->edges) > 0
            ) {
                foreach ($data->title->videoStrip->edges as $edge) {
                    $thumbUrl = null;
                    $videoId = isset($edge->node->id) ?
                                    str_replace('vi', '', $edge->node->id) : null;
                    if (!empty($edge->node->thumbnail->url)) {
                        $fullImageWidth = $edge->node->thumbnail->width;
                        $fullImageHeight = $edge->node->thumbnail->height;
                        $img = str_replace('.jpg', '', $edge->node->thumbnail->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
                        $thumbUrl = $img . $parameter;
                    }
                    $this->videos[$edge->node->contentType->displayName->value][] = array(
                        'id' => $videoId,
                        'name' => $edge->node->name->value,
                        'runtime' => $edge->node->runtime->value,
                        'description' => $edge->node->description->value,
                        'titleName' => $edge->node->primaryTitle->titleText->text,
                        'titleYear' => (int) $edge->node->primaryTitle?->releaseYear?->year,
                        'playbackUrl' => !empty($videoId) ?
                                                'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                        'imageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->videos;
    }

    #-------------------------------------------------------[ Main Awards ]---
    /**
     * Get main awards (not including total wins and total nominations)
     * @return array{
     *     award: string|null,
     *     nominations: int|null,
     *     wins: int|null
     * }
     * @see IMDB page / (TitlePage)
     */
    public function mainaward(): array
    {
        if (empty($this->mainAwards)) {
            $this->mainAwards = [
                'award' => null,
                'nominations' => null,
                'wins' => null,
            ];
            $query = <<<EOF
query MainAward(\$id: ID!) {
  title(id: \$id) {
    prestigiousAwardSummary {
      award {
        text
      }
      nominations
      wins
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MainAward", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->prestigiousAwardSummary)) {
                $this->mainAwards = array(
                    'award' => $data->title->prestigiousAwardSummary->award->text,
                    'nominations' => $data->title->prestigiousAwardSummary->nominations,
                    'wins' => $data->title->prestigiousAwardSummary->wins
                );
            }
        }
        return $this->mainAwards;
    }

    #-------------------------------------------------------[ Awards ]---
    /**
     * Get all awards for a title
     * @param bool $winsOnly Default: false, set to true to only get won awards
     * @param string $event Default: "" fill eventId Example "ev0000003" to only get Oscars
     *  Possible values for $event:
     *  ev0000003 (Oscar)
     *  ev0000223 (Emmy)
     *  ev0000292 (Golden Globe)
     *  ev0000471 (National Society of Film Critics Awards, USA)
     *  ev0000004 (Academy of Science Fiction, Fantasy & Horror Films, USA)
     *  ev0000123 (BAFTA Awards)
     *  ev0000296 (Satellite Awards)
     *  ev0000453 (MTV Movie + TV Awards)
     *  ev0000511 (Online Film Critics Society Awards)
     *  ev0000786 (Empire Awards, UK)
     *  ev0001644 (DVD Exclusive Awards)
     *  ev0002704 (Online Film & Television Association)
     *  ev0002990 (Awards Circuit Community Awards)
     *  ev0003023 (Golden Schmoes Awards)
     *  ev0000133 (Critics Choice Awards)
     *  ev0000403 (London Critics Circle Film Awards)
     *  ev0000530 (People's Choice Awards, USA)
     *
     * @return array<string, list<array{awardYear: int|null, awardWinner: bool, awardCategory: string|null, awardName: string|null, awardNotes: string|null, awardPersons: list<array{creditId: string|null, creditName: string|null, creditNote: string|null, nameFullImageUrl: string|null, nameThumbImageUrl: string|null}>, awardOutcome: string}>|array{win: int, nom: int}>
     *
     * @see IMDB page / (TitlePage)
     */
    public function award(bool $winsOnly = false, string $event = ""): array
    {
        $filter = $this->awardFilter($winsOnly, $event);
        if (empty($this->awards)) {
            $query = <<<EOF
award {
  event {
    text
  }
  text
  category {
    text
  }
  eventEdition {
    year
  }
  notes {
    plainText
  }
}
isWinner
awardedEntities {
  ... on AwardedTitles {
    secondaryAwardNames {
      name {
        id
        nameText {
          text
        }
        primaryImage {
          url
          width
          height
        }
      }
      note {
        plainText
      }
    }
  }
}
EOF;
            $data = $this->graphQlGetAll("Award", "awardNominations", $query, $filter);
            $winnerCount = 0;
            $nomineeCount = 0;
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $eventName = isset($edge->node->award->event->text) ?
                                    $edge->node->award->event->text : null;
                    $awardIsWinner = $edge->node->isWinner;
                    $conclusion = $awardIsWinner === true ? "Winner" : "Nominee";
                    $awardIsWinner === true ? $winnerCount++ : $nomineeCount++;
                    //credited persons
                    $names = array();
                    if (
                        isset($edge->node->awardedEntities->secondaryAwardNames) &&
                        is_array($edge->node->awardedEntities->secondaryAwardNames) &&
                        count($edge->node->awardedEntities->secondaryAwardNames) > 0
                    ) {
                        foreach ($edge->node->awardedEntities->secondaryAwardNames as $creditor) {
                            $nameThumbImageUrl = null;
                            $nameFullImageUrl = null;
                            if (!empty($creditor->name->primaryImage->url)) {
                                $img = str_replace('.jpg', '', $creditor->name->primaryImage->url);
                                $nameFullImageUrl = $img . 'QL100_UX1000_.jpg';
                                $fullImageWidth = $creditor->name->primaryImage->width;
                                $fullImageHeight = $creditor->name->primaryImage->height;
                                $newImageWidth = 140;
                                $newImageHeight = 207;
                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $nameThumbImageUrl = $img . $parameter;
                            }
                            $names[] = array(
                                'creditId' => isset($creditor->name->id) ?
                                                    str_replace('nm', '', $creditor->name->id) : null,
                                'creditName' => isset($creditor->name->nameText->text) ?
                                                    $creditor->name->nameText->text : null,
                                'creditNote' => isset($creditor->note->plainText) ?
                                                    trim($creditor->note->plainText, " ()") : null,
                                'nameFullImageUrl' => $nameFullImageUrl,
                                'nameThumbImageUrl' => $nameThumbImageUrl
                            );
                        }
                    }

                    $this->awards[$eventName][] = array(
                        'awardYear' => isset($edge->node->award->eventEdition->year) ?
                                            $edge->node->award->eventEdition->year : null,
                        'awardWinner' => $awardIsWinner,
                        'awardCategory' => isset($edge->node->award->category->text) ?
                                                $edge->node->award->category->text : null,
                        'awardName' => isset($edge->node->award->text) ?
                                            $edge->node->award->text : null,
                        'awardNotes' => isset($edge->node->award->notes->plainText) ?
                                            $edge->node->award->notes->plainText : null,
                        'awardPersons' => $names,
                        'awardOutcome' => $conclusion
                    );
                }
            }
            if ($winnerCount > 0 || $nomineeCount > 0) {
                $this->awards['total'] = array(
                    'win' => $winnerCount,
                    'nom' => $nomineeCount
                );
            }
        }
        return $this->awards;
    }

    #----------------------------------------------------------[ Sound mix ]---
    /**
     * Get movie sound mixes
     * @return list<array<string, list<string>|string>>
     * @see IMDB page / (specifications)
     */
    public function sound(): array
    {
        if (empty($this->soundMix)) {
            return $this->techSpec("soundMixes", "text", $this->soundMix);
        }
        return $this->soundMix;
    }

    #----------------------------------------------------------[ Aspect ratio ]---
    /**
     * Get movie aspect ratio like 1.66:1 or 16:9
     * @return list<array<string, list<string>|string>>
     * @see IMDB page / (specifications)
     */
    public function aspectRatio(): array
    {
        if (empty($this->aspectRatio)) {
            return $this->techSpec("aspectRatios", "aspectRatio", $this->aspectRatio);
        }
        return $this->aspectRatio;
    }

    #----------------------------------------------------------[ Cameras ]---
    /**
     * Get cameras used in this title
     * @return list<array<string, list<string>|string>>
     * @see IMDB page / (specifications)
     */
    public function camera(): array
    {
        if (empty($this->cameras)) {
            return $this->techSpec("cameras", "camera", $this->cameras);
        }
        return $this->cameras;
    }

    #----------------------------------------------------------[ Movie Featured Reviews ]---
    /**
     * Get movie featured reviews (max 5 available)
     * @return list<array{authorNickName: string|null, authorRating: int|null, summaryText: string|null, reviewText: string|null, submissionDate: string|null}>
     * @see IMDB page / (TitlePage)
     */
    public function featuredReview(): array
    {
        if (empty($this->featuredReviews)) {
            $query = <<<EOF
query Reviews(\$id: ID!) {
  title(id: \$id) {
    featuredReviews(first: 5) {
      edges {
        node {
          summary {
            originalText
          }
          author {
            nickName
          }
          authorRating
          text {
            originalText {
              plainText
            }
          }
          submissionDate
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Reviews", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->featuredReviews;
            }
            if (
                isset($data->title->featuredReviews->edges) &&
                is_array($data->title->featuredReviews->edges) &&
                count($data->title->featuredReviews->edges) > 0
            ) {
                foreach ($data->title->featuredReviews->edges as $edge) {
                    $this->featuredReviews[] = array(
                    'authorNickName' => isset($edge->node->author->nickName) ?
                                              $edge->node->author->nickName : null,
                    'authorRating' => isset($edge->node->authorRating) ?
                                            $edge->node->authorRating : null,
                    'summaryText' => isset($edge->node->summary->originalText) ?
                                           $edge->node->summary->originalText : null,
                    'reviewText' => isset($edge->node->text->originalText->plainText) ?
                                          $edge->node->text->originalText->plainText : null,
                    'submissionDate' => isset($edge->node->submissionDate) ?
                                              $edge->node->submissionDate : null
                    );
                }
            }
        }
        return $this->featuredReviews;
    }

    #----------------------------------------------------------[ Movie isAdult ]---
    /**
     * Get adult status of a title
     * @return bool|null
     * @see IMDB page / (TitlePage)
     */
    public function isAdult(): ?bool
    {
        if (empty($this->isAdult)) {
            $query = <<<EOF
query Adult(\$id: ID!) {
  title(id: \$id) {
    isAdult
  }
}
EOF;
            $data = $this->graphql->query($query, "Adult", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->isAdult;
            }
            $this->isAdult = $data->title->isAdult;
        }
        return $this->isAdult;
    }

    #----------------------------------------------------------[ Watch Option ]---
    /**
     * watch options by category for this title
     * @Note: (DEC 2024) Only Amazon providers are returned, no others!
     * @return array<string, list<array{providerId: string, providerName: string, logoUrl: string|null}>>
     */
    public function watchOption(): array
    {
        if (empty($this->watchOption)) {
            $query = <<<EOF
query WatchOption(\$id: ID!) {
  title(id: \$id) {
    watchOptionsByCategory(limit: 250) {
      categorizedWatchOptionsList {
        categoryName {
          value
        }
        watchOptions(limit: 250) {
          provider {
            id
            name {
              value
            }
            logos {
              icon {
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
            $data = $this->graphql->query($query, "WatchOption", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->watchOption;
            }
            if (
                isset($data->title->watchOptionsByCategory->categorizedWatchOptionsList) &&
                is_array($data->title->watchOptionsByCategory->categorizedWatchOptionsList) &&
                count($data->title->watchOptionsByCategory->categorizedWatchOptionsList) > 0
            ) {
                foreach ($data->title->watchOptionsByCategory->categorizedWatchOptionsList as $item) {
                    $watchOptions = array();
                    $categoryName = strtolower(str_replace('/', '-', $item->categoryName->value));
                    if (
                        isset($item->watchOptions) &&
                        is_array($item->watchOptions) &&
                        count($item->watchOptions) > 0
                    ) {
                        foreach ($item->watchOptions as $option) {
                            $logoUrl = null;
                            if (!empty($option->provider->logos->icon->url)) {
                                $img = str_replace('.png', '', $option->provider->logos->icon->url);
                                $logoUrl = $img . 'QL100_UX250_.png';
                            }
                            $watchOptions[] = array(
                                'providerId' => isset($option->provider->id) ?
                                                    $option->provider->id : null,
                                'providerName' => isset($option->provider->name->value) ?
                                                        $option->provider->name->value : null,
                                'logoUrl' => $logoUrl
                            );
                        }
                    }
                    $this->watchOption[$categoryName] = $watchOptions;
                }
            }
        }
        return $this->watchOption;
    }

    #----------------------------------------------------------[ Production Status ]---
    /**
     * Get current production status of a title e.g. Released, In Development, Pre-Production, Complete, Production etc
     * @return string|null status
     */
    public function productionStatus(): ?string
    {
        if (empty($this->status)) {
            $query = <<<EOF
query ProductionStatus(\$id: ID!) {
  title(id: \$id) {
    productionStatus {
      currentProductionStage {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "ProductionStatus", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->status;
            }
            $this->status = $data->title->productionStatus->currentProductionStage->text;
        }
        return $this->status;
    }

    #----------------------------------------------------------[ News ]---
    /**
     * Get news items about this title, max 100 items!
     * @return list<array{id: string, title: string, author: string|null, date: string|null, extUrl: string|null, extHomepageUrl: string|null, extHomepageLabel: string|null, textHtml: string|null, textText: string|null, thumbnailUrl: string|null}>
     */
    public function news(): array
    {
        if (empty($this->news)) {
            $query = <<<EOF
query News(\$id: ID!) {
  title(id: \$id) {
    news(first: 100) {
      edges {
        node {
          id
          articleTitle {
            plainText
          }
          byline
          date
          externalUrl
          image {
            url
            width
            height
          }
          source {
            description
            homepage {
              label
              url
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
}
EOF;
            $data = $this->graphql->query($query, "News", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->news;
            }
            if (
                isset($data->title->news->edges) &&
                is_array($data->title->news->edges) &&
                count($data->title->news->edges) > 0
            ) {
                foreach ($data->title->news->edges as $edge) {
                    $thumbUrl = null;
                    if (!empty($edge->node->image->url)) {
                        $fullImageWidth = $edge->node->image->width;
                        $fullImageHeight = $edge->node->image->height;
                        $img = str_replace('.jpg', '', $edge->node->image->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
                        $thumbUrl = $img . $parameter;
                    }
                    $this->news[] = array(
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
                        'extHomepageUrl' => isset($edge->node->source->homepage->url) ?
                                                $edge->node->source->homepage->url : null,
                        'extHomepageLabel' => isset($edge->node->source->homepage->label) ?
                                                    $edge->node->source->homepage->label : null,
                        'textHtml' => isset($edge->node->text->plaidHtml) ?
                                            $edge->node->text->plaidHtml : null,
                        'textText' => isset($edge->node->text->plainText) ?
                                            $edge->node->text->plainText : null,
                        'thumbnailUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->news;
    }


    #========================================================[ Helper functions ]===
    #===============================================================================

    /**
     * Setup title and year properties
     */
    protected function titleYear(): void
    {
        $query = <<<EOF
query TitleYear(\$id: ID!) {
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
  }
}
EOF;
        $data = $this->graphql->query($query, "TitleYear", ["id" => "tt$this->imdbID"]);

        $this->mainTitle = isset($data->title->titleText->text) ?
                                 trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))) : null;
        $this->mainOriginalTitle  = isset($data->title->originalTitleText->text) ?
                                          trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))) : null;
        $this->mainMovietype = $data->title->titleType?->text;
        $this->mainYear = $data->title->releaseYear?->year;
        $this->mainEndYear = $data->title->releaseYear?->endYear;
    }

    #========================================================[ photo/poster ]===
    /**
     * Setup cover photo (thumbnail and big variant)
     * @see IMDB page / (TitlePage)
     */
    private function populatePoster(): void
    {
        $query = <<<EOF
query Poster(\$id: ID!) {
  title(id: \$id) {
    primaryImage {
      url
      width
      height
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Poster", ["id" => "tt$this->imdbID"]);
        if (!empty($data->title->primaryImage->url)) {
            $img = str_replace('.jpg', '', $data->title->primaryImage->url);
            // full image
            $this->mainPoster = $img . 'QL100_SX1000_.jpg';
            // thumb image
            if (!empty($data->title->primaryImage->width) && !empty($data->title->primaryImage->height)) {
                $fullImageWidth = $data->title->primaryImage->width;
                $fullImageHeight = $data->title->primaryImage->height;
                $newImageWidth = $this->config->photoThumbnailWidth;
                $newImageHeight = $this->config->photoThumbnailHeight;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $this->mainPosterThumb = $img . $parameter;
            }
        }
    }

    #========================================================[ CompanyCredits ]===
    /**
     * Fetch all company credits
     * @param string $category e.g. distribution, production
     * @return array<array{name: string, id: string, country: string, attribute: list<string>|string, year: int}>
     */
    protected function companyCredits(string $category): array
    {
        $filter = ', filter: { categories: ["' . $category . '"] }';
        $query = <<<EOF
company {
  id
}
displayableProperty {
  value {
    plainText
  }
}
countries(limit: 1) {
  text
}
attributes {
  text
}
yearsInvolved {
  year
}
EOF;
        $data = $this->graphQlGetAll("CompanyCredits", "companyCredits", $query, $filter);
        $results = array();
        if (count($data) > 0) {
            foreach ($data as $edge) {
                $companyAttribute = array();
                if (
                    isset($edge->node->attributes) &&
                    is_array($edge->node->attributes) &&
                    count($edge->node->attributes) > 0
                ) {
                    foreach ($edge->node->attributes as $attribute) {
                        $companyAttribute[] = $attribute->text;
                    }
                }
                $results[] = array(
                    "name" => isset($edge->node->displayableProperty->value->plainText) ?
                                    $edge->node->displayableProperty->value->plainText : null,
                    "id" => isset($edge->node->company->id) ?
                                str_replace('co', '', $edge->node->company->id) : null,
                    "country" => isset($edge->node->countries[0]->text) ?
                                    $edge->node->countries[0]->text : null,
                    "attribute" => $companyAttribute,
                    "year" => (int) $edge->node?->yearsInvolved?->year,
                );
            }
        }
        return $results;
    }

    #========================================================[ Crew Category ]===
    #---------------------------------------------------------------[ credit helper ]---
    /** helper for stunts, thanks, visualEffects, specialEffects, producer,
     *      writer, director, composer, cinematographer
     * @return list<array{imdb: string, name: string, jobs: list<string>, attributes: list<string>, episode: array{total: int|null, year: int|null, endYear: int|null}|null, titleFullImageUrl: string|null, titleThumbImageUrl: string|null}>
     * @see IMDB page /fullcredits
     */
    private function creditHelper(string $crewCategory): array
    {
        $filter = ', filter: { categories: ["' . $crewCategory . '"] }';
        $output = array();
        $query = <<<EOF
name {
  nameText {
    text
  }
  id
  primaryImage {
    url
    width
    height
  }
}
... on Crew {
  jobs {
    text
  }
  attributes {
    text
  }
  episodeCredits(first: 9999) {
    total
    yearRange {
      year
      endYear
    }
  }
}
EOF;
        $data = $this->graphQlGetAll("CreditCrew", "credits", $query, $filter);
        if (count($data) > 0) {
            foreach ($data as $edge) {
                $jobs = array();
                if (
                    isset($edge->node->jobs) &&
                    is_array($edge->node->jobs) &&
                    count($edge->node->jobs) > 0
                ) {
                    foreach ($edge->node->jobs as $value) {
                        if (!empty($value->text)) {
                            $jobs[] = $value->text;
                        }
                    }
                }
                $episodes = array();
                if (!empty($edge->node->episodeCredits)) {
                    $episodes = array(
                        'total' => isset($edge->node->episodeCredits->total) ?
                                        $edge->node->episodeCredits->total : null,
                        'year' => isset($edge->node->episodeCredits->yearRange->year) ?
                                        $edge->node->episodeCredits->yearRange->year : null,
                        'endYear' => isset($edge->node->episodeCredits->yearRange->endYear) ?
                                        $edge->node->episodeCredits->yearRange->endYear : null
                    );
                }
                $attributes = array();
                if (
                    isset($edge->node->attributes) &&
                    is_array($edge->node->attributes) &&
                    count($edge->node->attributes) > 0
                ) {
                    foreach ($edge->node->attributes as $attribute) {
                        if (!empty($attribute->text)) {
                            $attributes[] = $attribute->text;
                        }
                    }
                }
                $nameThumbImageUrl = null;
                $nameFullImageUrl = null;
                if (!empty($edge->node->name->primaryImage->url)) {
                    $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);
                    $nameFullImageUrl = $img . 'QL100_UX1000_.jpg';
                    $fullImageWidth = $edge->node->name->primaryImage->width;
                    $fullImageHeight = $edge->node->name->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $nameThumbImageUrl = $img . $parameter;
                }
                $output[] = array(
                    'imdb' => isset($edge->node->name->id) ?
                                    str_replace('nm', '', $edge->node->name->id) : null,
                    'name' => isset($edge->node->name->nameText->text) ?
                                    $edge->node->name->nameText->text : null,
                    'jobs' => $jobs,
                    'attributes' => $attributes,
                    'episode' => $episodes,
                    'titleFullImageUrl' => $nameFullImageUrl,
                    'titleThumbImageUrl' => $nameThumbImageUrl
                );
            }
        }
        return $output;
    }

    #----------------------------------------------------------[ Episode build filter]---
    /**
     * Build filter constraint for episode()
     * @param string $seasonYear node->text from season or year
     * @return string $filter
     */
    public function buildFilter(string $seasonYear): string
    {
        if (strlen($seasonYear) === 4) {
            // year based Tv Series
            $filter = 'filter:{releasedOnOrAfter:{day:1,month:1,year:' . $seasonYear . '},'
                              . 'releasedOnOrBefore:{day:31,month:12,year:' . $seasonYear . '}}';
        } else {
            // To fetch data from unknown seasons/years
            if ($seasonYear === "Unknown") { //this is intended capitol
                $SeasonUnknown = "unknown"; //this is intended not capitol
                $seasonFilter = '';
            } else {
                $seasonFilter = $seasonYear;
                $SeasonUnknown = '';
            }
            $filter = 'filter:{includeSeasons:["' . $seasonFilter . '","' . $SeasonUnknown . '"]}';
        }
        return $filter;
    }

    #========================================================[ Season Year check ]===
    /** Check if TV Series season or year based
     * @param int $yearbased 0: year based, 1: season based
     * @return array<array-key, \stdClass>|bool false
     */
    private function seasonYearCheck(int $yearbased): array|bool
    {
        $querySeasons = <<<EOF
query Seasons(\$id: ID!) {
  title(id: \$id) {
    episodes {
      displayableSeasons(first: 9999) {
        edges {
          node {
            text
          }
        }
      }
      displayableYears(first: 9999) {
        edges {
          node {
            text
          }
        }
      }
    }
  }
}
EOF;
        $seasonsData = $this->graphql->query($querySeasons, "Seasons", ["id" => "tt$this->imdbID"]);
        if (!empty($seasonsData->title->episodes)) {
            $bySeason = count($seasonsData->title->episodes->displayableSeasons->edges);
            $byYear = count($seasonsData->title->episodes->displayableYears->edges);
            if ($yearbased === 0) {
                $data = $seasonsData->title->episodes->displayableSeasons->edges;
            } else {
                $data = $seasonsData->title->episodes->displayableYears->edges;
            }
        } else {
            return false;
        }
        return $data;
    }

    #========================================================[ GraphQL Get All Episodes]===
    /**
     * Get all episodes of a title
     * @param string $filter add filter options to query
     * @return array<string, mixed>
     */
    protected function graphQlGetAllEpisodes(string $filter): array
    {
        $query = <<<EOF
query Episodes(\$id: ID!, \$after: ID) {
  title(id: \$id) {
    episodes {
      episodes(first: 9999, after: \$after $filter) {
        edges {
          node {
            id
            titleText {
              text
            }
            plot {
              plotText {
                plainText
              }
            }
            primaryImage {
              url
              width
              height
            }
            releaseDate {
              day
              month
              year
            }
            series {
              displayableEpisodeNumber {
                episodeNumber {
                  episodeNumber
                }
              }
            }
          }
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
  }
}
EOF;
        // strip spaces from query due to hosters request limit
        $fullQuery = implode("\n", array_map('trim', explode("\n", $query)));

        // Results are paginated, so loop until we've got all the data
        $endCursor = null;
        $hasNextPage = true;
        $edges = array();
        while ($hasNextPage) {
            $data = $this->graphql->query($fullQuery, "Episodes", ["id" => "tt$this->imdbID", "after" => $endCursor]);
            $edges = array_merge($edges, $data->title->episodes->episodes->edges);
            $hasNextPage = $data->title->episodes->episodes->pageInfo->hasNextPage;
            $endCursor = $data->title->episodes->episodes->pageInfo->endCursor;
        }
        return $edges;
    }

    #========================================================[ Helper Technical specifications ]===
    /**
     * Get movie tech specs
     * @param string $spec input techspec type like soundMixes or aspectRatios
     * @param string $property input type like text or soundMix
     * @param list<array<string, list<string>|string>> $target output array name
     * @return list<array<string, list<string>|string>>
     * @see IMDB page / (specifications)
     */
    protected function techSpec(string $spec, string $property, array $target): array
    {
        $query = <<<EOF
query TechSpec(\$id: ID!) {
  title(id: \$id) {
    technicalSpecifications {
      $spec {
        items {
          $property
          attributes {
            text
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "TechSpec", ["id" => "tt{$this->imdbID}"]);
        $titleVars = is_object($data->title ?? null) ? get_object_vars($data->title) : [];
        $specData = $titleVars[$spec] ?? null;
        if (!isset($specData->edges) || !is_array($specData->edges)) {
            return $target;
        }
        foreach ($specData->edges as $edge) {
            $attributes = array();
            if (isset($edge->node->attributes) && is_array($edge->node->attributes) && count($edge->node->attributes) > 0) {
                foreach ($edge->node->attributes as $attribute) {
                    if (!empty($attribute->text)) {
                        $attributes[] = $attribute->text;
                    }
                }
            }
            $nodeVars = is_object($edge->node ?? null) ? get_object_vars($edge->node) : [];
            $propObj = $nodeVars[$property] ?? null;
            $text = is_object($propObj) && isset($propObj->text) ? $propObj->text : null;
            $target[] = array('text' => $text, 'attributes' => $attributes);
        }
        return $target;
    }

    #========================================================[ GraphQL Get All]===
    /**
     * Get all edges of a field in the title type
     * @param string $queryName The cached query name
     * @param string $propertyName The field on title you want to get
     * @param string $query Graphql query that fits inside node { }
     * @param string $filter Add's extra Graphql query filters like categories
     * @return \stdClass[]
     */
    protected function graphQlGetAll(string $queryName, string $propertyName, string $query, string $filter = ''): array
    {
        $results = array();
        $after = '';
        $hasNextPage = true;
        while ($hasNextPage) {
            $gqlQuery = <<<EOF
query {$queryName}(\$id: ID!) {
  title(id: \$id) {
    {$propertyName}(first: 250$filter$after) {
      pageInfo {
        hasNextPage
        endCursor
      }
      edges {
        node {
          {$query}
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($gqlQuery, $queryName, ["id" => "tt$this->imdbID"]);
            $titleVars = is_object($data->title ?? null) ? get_object_vars($data->title) : [];
            $propData = $titleVars[$propertyName] ?? null;

            if (!isset($propData->edges) || !is_array($propData->edges)) {
                return $results;
            }
            foreach ($propData->edges as $edge) {
                $results[] = $edge;
            }
            $hasNextPage = $propData->pageInfo->hasNextPage ?? false;
            $endCursor = $propData->pageInfo->endCursor ?? '';
            if ($hasNextPage && !empty($endCursor)) {
                $after = ', after: "' . $endCursor . '"';
            } else {
                $hasNextPage = false;
            }
        }
        return $results;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * It sometimes happens that imdb redirects an existing id to a new id.
     * If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     *
     * @return string|false $titleImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    public function checkRedirect(): string|false
    {
        $query = <<<EOF
query Redirect(\$id: ID!) {
  title(id: \$id) {
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Redirect", ["id" => "tt$this->imdbID"]);
        if (
            isset($data->title->meta->canonicalId) &&
            $data->title->meta->canonicalId !== ''
        ) {
            $titleImdbId = str_replace('tt', '', $data->title->meta->canonicalId);
            if ($titleImdbId  !== $this->imdbID) {
                // todo write to log?
                return $titleImdbId;
            } else {
                return false;
            }
        }
        return false;
    }

    #----------------------------------------------------------[ Build date string for Episode() ]---
    /**
     * build date string for episode()
     * @param array<string, string> $date input date array(['day'], ['month'], ['year'])
     * @return string|null $airDate e.g. '20 jan. 2008'
     */
    private function buildDateString(array $date): ?string
    {
        $airDate = null;
        if (!empty($date['day'])) {
            $airDate .= $date['day'];
            if (!empty($date['month'])) {
                $airDate .= ' ';
            }
        }
        if (!empty($date['month'])) {
            $airDate .= date('M', mktime(0, 0, 0, (int) $date['month'], 10)) . '.';
            if (!empty($date['year'])) {
                $airDate .= ' ';
            }
        }
        if (!empty($date['year'])) {
            $airDate .= $date['year'];
        }
        return $airDate;
    }

    #----------------------------------------------------------[ Award filter helper ]---
    /**
     * Build award filter string
     * @param bool $winsOnly
     * @param string $event eventId
     * @return string $filter
     */
    public function awardFilter(bool $winsOnly, string $event): string
    {
        $filter = ', sort: {by: PRESTIGIOUS, order: DESC}';
        if (!empty($event) || $winsOnly === true) {
            $filter .= ', filter:{';
            if ($winsOnly === true) {
                $filter .= 'wins:WINS_ONLY';
                if (empty($event)) {
                    $filter .= '}';
                } else {
                    $filter .= ', events:"' . trim($event) . '"}';
                }
            } else {
                $filter .= 'events:"' . trim($event) . '"}';
            }
        }
        return $filter;
    }
}
