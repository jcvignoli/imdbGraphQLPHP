<?php

#############################################################################
# imdbGraphQLPHP                       (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# written extended & maintained by Ed                                       #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################
declare(strict_types=1);

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * A person on IMDb
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 * @copyright 2008 by Itzchak Rehberg and IzzySoft
 *
 * @phpstan-type RelativeDef array{ imdb: string|null, name: string|null, relType: string|null }
 * @phpstan-type PublicityDef array{ publication: string, regionId: string, title: string, date: array{ day: int, month: int, year: int }, reference: string, authors: list<string> }|array{}
* @phpstan-type DateDef array{ day: int|null, month: string|null, mon: int|null, year: int|null }
 * @phpstan-type SpouseDef array{ imdb: string|null, name: string|null, from: DateDef, to: DateDef, dateText: string|null, comment: list<string>, children: int, current: string }
* @phpstan-type SalaryDef array{ imdb: string|null, name: string|null, year: string|null, amount: string|null, currency: string|null, comment: list<string> }
 */
class Name extends MdbBase
{
    // "Name" page:
    protected Image $imageFunctions;
    protected ?string $mainPoster = null;
    protected ?string $mainPosterThumb = null;
    protected ?string $fullName = null;
    /** @phpstan-var array{ day: int|null, month: string|null, mon: int|null, year: int|null, place: string|null }|array{}|null */
    protected ?array $birthday = array();
    /** @phpstan-var array{ day: int|null, month: string|null, mon: int|null, year: int|null, place: string|null, cause: string|null, status: 'ALIVE'|'DEAD'|'PRESUMED_DEAD'|null }|array{} */
    protected array $deathday = array();
    protected ?int $age = null;
    /** @var array<array-key, string> */
    protected array $professions = array();
    /** @phpstan-var array{ currentRank: int|null, changeDirection: string|null, difference: int|null }|array{} */
    protected array $popRank = array();
    /** @var string[] */
    protected array $mainPhoto = array();
    /** @phpstan-var array<'Trailer'|'Clip', list<array{ id: string, name: string, runtime: int|null, description: string|null, titleName: string|null, titleYear: int|null, playbackUrl: string, imageUrl: string|null }>> */
    protected array $videos = array();
    /** @phpstan-var list<array{ id: string, title: string, author: string|null, date: string, extUrl: string, extHomepageUrl: string|null, extHomepageLabel: string|null, textHtml: string|null, textText: string|null, thumbnailUrl: string|null }> */
    protected array $news = array();

    // "Bio" page:
    protected ?string $birthName = null;
    /** @var string[] */
    protected array $nickName = array();
    /** @var string[] */
    protected array $akaName = array();
    /** @var array{imperial: array{feet: int, inches: float}, metric: int}|array{} */
    protected array $bodyheight = array();
    /** @phpstan-var list<SpouseDef> */
    protected array $spouses = array();
    /** @phpstan-var list<RelativeDef> */
    protected array $children = array();
    /** @phpstan-var list<RelativeDef> */
    protected array $parents = array();
    /** @phpstan-var list<RelativeDef> */
    protected array $relatives = array();
    /** @phpstan-var list<array{ desc: string, author: string }> */
    protected array $bioBio = array();
    /** @var array<string, string> */
    protected array $bioTrivia = array();
    /** @var array<string, string> */
    protected array $bioQuotes = array();
    /** @var array<string, string> */
    protected array $bioTrademark = array();
    /** @phpstan-var list<SalaryDef> */
    protected array $bioSalary = array();

    // "Publicity" page:
    /** @phpstan-var list<array{ title: string, author: list<string>, publisher: string, isbn: string|null }> */
    protected array $pubPrints = array();
    /** @phpstan-var list<array{ title: string, id: string, year: int|null, seriesTitle: string|null, seriesSeason: int|null, seriesEpisode: int|null }> */
    protected array $pubMovies = array();
    /** @phpstan-var list<array{ title: string, id: string, year: int|null }> */
    protected array $pubPortrayal = array();
    /** @phpstan-var list<PublicityDef> */
    protected array $pubArticle = array();
    /** @phpstan-var list<PublicityDef> */
    protected array $pubInterview = array();
    /** @phpstan-var list<PublicityDef> */
    protected array $pubMagazine = array();
    /** @phpstan-var list<PublicityDef> */
    protected array $pubPictorial = array();

    // "OtherWorks" page:
    /** @phpstan-var list<array{ category: string, fromDate: array{ day: int|null, month: int|null, year: int|null }|null, toDate: array{ day: int|null, month: int|null, year: int|null }|null, text: string }> */
    protected array $otherWorks = array();

    // "External Sites" page:
    /** @phpstan-var array<'official'|'video'|'photo'|'sound'|'misc', list<array{ label: string|null, url: string|null, language: list<string> }>> */
    protected array $externalSites = array();

    // "Credits" page:
    /** @phpstan-var array<string, list<array{ awardYear: int, awardWinner: bool, awardCategory: string, awardName: string, awardTitles: list<array{ titleId: string, titleName: string, titleNote: string|null, titleFullImageUrl: string|null, titleThumbImageUrl: string|null }>, awardNotes: string|null, awardOutcome: string }>|array{win: int, nom: int}> */
    protected array $awards = array();
    /** @phpstan-var list<array{ title: string, titleId: string, titleYear: int|null, titleEndYear: int|null, titleFullImageUrl: string|null, titleThumbImageUrl: string|null, titleCharacters: list<string> }> */
    protected array $creditKnownFor = array();
    /** @phpstan-var array<string, list<array{ titleId: string, titleName: string, titleType: string, year: int|null, endYear: int|null, characters: list<string>, jobs: list<string>, titleFullImageUrl: string|null, titleThumbImageUrl: string|null }>> */
    protected array $credits = array();

    #----------------------------------------------------------[ Helper for NameSearch class ]---
    /**
     * Create an person object populated with id and name
     * @param string $id name ID
     * @param string $name person name
     * @param Config|null $config
     * @param LoggerInterface|null $logger OPTIONAL override default logger
     * @param CacheInterface|null $cache OPTIONAL override default cache
     * @return Name
     */
    public static function fromSearchResult(
        string $id,
        string $name,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null
    ) {
        $person = new Name($id, $config, $logger, $cache);
        $person->fullName = $name;
        return $person;
    }

    /**
     * @param string $id IMDBID to use for data retrieval
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

    #=============================================================[ Main Page ]===

    #------------------------------------------------------------------[ Name ]---
    /** Get the name of the person
     * @return string|null name full name of the person
     * @see IMDB person page / (Main page)
     */
    public function name(): ?string
    {
        if (empty($this->fullName)) {
            $query = <<<EOF
query Name(\$id: ID!) {
  name(id: \$id) {
    nameText {
      text
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Name", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->nameText->text)) {
                $this->fullName = $data->name->nameText->text;
            }
        }
        return $this->fullName;
    }

    #--------------------------------------------------------[ Photo specific ]---
    /**
     * Get the main photo image url for thumbnail or full size
     * @param bool $thumb get the thumbnail (140x207 pixels) or large (max 1000 pixels)
     * @return string|false|null photo (string URL if found, false otherwise, but can be null)
     * @see IMDB page / (NamePage)
     */
    public function photo(bool $thumb = true): string|bool|null
    {
        if (empty($this->mainPoster)) {
            $this->populatePoster();
        }
        if ($thumb === false && empty($this->mainPoster)) {
            return false;
        }
        if ($thumb === true && empty($this->mainPosterThumb)) {
            return false;
        }
        if ($thumb === true) {
            return $this->mainPosterThumb;
        }
        return $this->mainPoster;
    }

    /**
     * Save the poster/cover image to disk
     * @param string $path where to store the file
     * @param boolean $thumb get the thumbnail or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return boolean success
     * @see IMDB page / (NamePage)
     */
    public function savephoto($path, $thumb = true)
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

    /**
     * Get the URL for the Name cover image
     * @param boolean $thumb get the thumbnail (default) or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return mixed url (string URL or FALSE if none)
     * @see IMDB page / (NamePage)
     */
    public function photoLocalurl($thumb = true)
    {
        if ($thumb) {
            $ext = "";
        } else {
            $ext = "_big";
        }
        if (!is_dir($this->config->photoroot)) {
            if (mkdir($this->config->photoroot, 0777, true) === false) {
                    $this->debugScalar('<br>***ERROR*** The configured image directory does not exist and couldn\'t be created.');
                return false;
            }
        }
        $path = $this->config->photoroot . "nm{$this->imdbid()}" . "{$ext}.jpg";
        if (file_exists($path)) {
            return $this->config->photodir . "nm{$this->imdbid()}" . "{$ext}.jpg";
        }
        if (!is_writable($this->config->photoroot)) {
            $this->debugScalar("<BR>***ERROR*** The configured image directory lacks write permission!<BR>");
            return false;
        }
        if ($this->savephoto($path, $thumb)) {
            return $this->config->photodir . "nm{$this->imdbid()}" . "{$ext}.jpg";
        }
        return false;
    }

    #==================================================================[ /bio ]===
    #------------------------------------------------------------[ Birth Name ]---
    /** Get the birth name
     * @return string|null birthname
     * @see IMDB person page /bio
     */
    public function birthname(): ?string
    {
        if (empty($this->birthName)) {
            $query = <<<EOF
query BirthName(\$id: ID!) {
  name(id: \$id) {
    birthName {
      text
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BirthName", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->birthName->text)) {
                $this->birthName = $data->name->birthName->text;
            }
        }
        return $this->birthName;
    }

    #-------------------------------------------------------------[ Nick Name ]---
    /** Get the nick name
     * @return string[] nicknames
     * @see IMDB person page /bio
     */
    public function nickname(): array
    {
        if (empty($this->nickName)) {
            $query = <<<EOF
query NickName(\$id: ID!) {
  name(id: \$id) {
    nickNames {
      text
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "NickName", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->nickName;
            }
            if (
                isset($data->name->nickNames) &&
                is_array($data->name->nickNames) &&
                count($data->name->nickNames) > 0
            ) {
                foreach ($data->name->nickNames as $nickName) {
                    if (!empty($nickName->text)) {
                        $this->nickName[] = $nickName->text;
                    }
                }
            }
        }
        return $this->nickName;
    }

    #-------------------------------------------------------------[ Alternative Names ]---
    /** Get alternative names for a person
     * @return string[] alternative names
     * @see IMDB person page /bio
     */
    public function akaName(): array
    {
        if (empty($this->akaName)) {
            $query = <<<EOF
query AkaName(\$id: ID!) {
  name(id: \$id) {
    akas(first: 9999) {
      edges {
        node {
          text
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "AkaName", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->akaName;
            }
            if (
                isset($data->name->akas->edges) &&
                is_array($data->name->akas->edges) &&
                count($data->name->akas->edges) > 0
            ) {
                foreach ($data->name->akas->edges as $edge) {
                    if (!empty($edge->node->text)) {
                        $this->akaName[] = $edge->node->text;
                    }
                }
            }
        }
        return $this->akaName;
    }

    #------------------------------------------------------------------[ Born ]---
    /** Get Birthday
     * @return array{
     *     day: int|null,
     *     month: string|null,
     *     mon: int|null,
     *     year: int|null,
     *     place: string|null
     * }|array{}|null
     * birthday [day,month,mon,year,place]
     *         where $monthName is the month name, and $monthInt the month number
     * @see IMDB person page /bio
     */
    public function born(): ?array
    {
        if (empty($this->birthday)) {
            $query = <<<EOF
query BirthDate(\$id: ID!) {
  name(id: \$id) {
    birthDate {
      dateComponents {
        day
        month
        year
      }
    }
    birthLocation {
      text
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BirthDate", ["id" => "nm$this->imdbID"]);
            $monthInt = isset($data->name->birthDate->dateComponents->month) ?
                              $data->name->birthDate->dateComponents->month : null;
            $monthName = null;
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $this->birthday = array(
                "day" => isset($data->name->birthDate->dateComponents->day) ?
                               $data->name->birthDate->dateComponents->day : null,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => isset($data->name->birthDate->dateComponents->year) ?
                                $data->name->birthDate->dateComponents->year : null,
                "place" => isset($data->name->birthLocation->text) ?
                                 $data->name->birthLocation->text : null
            );
        }
        return $this->birthday;
    }

    #------------------------------------------------------------------[ Died ]---
    /**
     * Get date of death with place and cause
     * @return array{
     *     day: int|null,
     *     month: string|null,
     *     mon: int|null,
     *     year: int|null,
     *     place: string|null,
     *     cause: string|null,
     *     status: 'ALIVE'|'DEAD'|'PRESUMED_DEAD'|null
     * }
     * @see IMDB person page /bio
     */
    public function died(): array
    {
        if (empty($this->deathday)) {
            $query = <<<EOF
query DeathDate(\$id: ID!) {
  name(id: \$id) {
    deathDate {
      dateComponents {
        day
        month
        year
      }
    }
    deathLocation {
      text
    }
    deathCause {
      text
    }
    deathStatus
  }
}
EOF;
            $data = $this->graphql->query($query, "DeathDate", ["id" => "nm$this->imdbID"]);
            $monthInt = isset($data->name->deathDate->dateComponents->month) ?
                              $data->name->deathDate->dateComponents->month : null;
            $monthName = null;
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $this->deathday = array(
                "day" => isset($data->name->deathDate->dateComponents->day) ?
                               $data->name->deathDate->dateComponents->day : null,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => isset($data->name->deathDate->dateComponents->year) ?
                                $data->name->deathDate->dateComponents->year : null,
                "place" => isset($data->name->deathLocation->text) ?
                                 $data->name->deathLocation->text : null,
                "cause" => isset($data->name->deathCause->text) ?
                                 $data->name->deathCause->text : null,
                "status" => isset($data->name->deathStatus) ?
                                  $data->name->deathStatus : null
            );
        }
        return $this->deathday;
    }

    #------------------------------------------------------------------[ Age ]---
    /** Get the age of the person
     * @return int|null age
     * @see IMDB person page / (Main page)
     */
    public function age(): ?int
    {
        if (empty($this->age)) {
            $query = <<<EOF
query Age(\$id: ID!) {
  name(id: \$id) {
    age {
      value
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Age", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->age->value)) {
                $this->age = $data->name->age->value;
            }
        }
        return $this->age;
    }

    #-----------------------------------------------------------[ Primary Professions ]---
    /** Get primary professions of this person
     * @return array<array-key, string> all professions
     * @see IMDB person page
     */
    public function profession(): array
    {
        if (empty($this->professions)) {
            $query = <<<EOF
query Professions(\$id: ID!) {
  name(id: \$id) {
    primaryProfessions {
      category {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Professions", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->professions;
            }
            if (
                isset($data->name->primaryProfessions) &&
                is_array($data->name->primaryProfessions) &&
                count($data->name->primaryProfessions) > 0
            ) {
                foreach ($data->name->primaryProfessions as $primaryProfession) {
                    if (!empty($primaryProfession->category->text)) {
                        $this->professions[] = $primaryProfession->category->text;
                    }
                }
            }
        }
        return $this->professions;
    }

    #----------------------------------------------------------[ Popularity ]---
    /**
     * Get current popularity rank of a person
     * @return array{
     *     currentRank: int|null,
     *     changeDirection: string|null,
     *     difference: int|null
     * }|array{}
     * @see IMDB page / (NamePage)
     */
    public function rank(): array
    {
        if (empty($this->popRank)) {
            $query = <<<EOF
query Rank(\$id: ID!) {
  name(id: \$id) {
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

            $data = $this->graphql->query($query, "Rank", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->meterRanking->currentRank)) {
                $this->popRank = array(
                    'currentRank' => $data->name->meterRanking->currentRank,
                    'changeDirection' => isset($data->name->meterRanking->rankChange->changeDirection) ?
                                               $data->name->meterRanking->rankChange->changeDirection : null,
                    'difference' => isset($data->name->meterRanking->rankChange->difference) ?
                                          $data->name->meterRanking->rankChange->difference : null
                );
            }
        }
        return $this->popRank;
    }

    #-----------------------------------------------------------[ Body Height ]---
    /** Get the body height
     * imperial: array[feet (int), inches (float)], metric: int (in centimeters)
     * @return array{
     *     imperial: array{feet: int, inches: float},
     *     metric: int
     * }|array{}
     * @see IMDB person page /bio
     */
    public function height(): array
    {
        if (empty($this->bodyheight)) {
            $query = <<<EOF
query BodyHeight(\$id: ID!) {
  name(id: \$id) {
    height {
      measurement {
        value
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BodyHeight", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->height->measurement->value)) {
                $value = $data->name->height->measurement->value;
                $inchesTotal = $value * 0.393701;
                $feet = intval($inchesTotal / 12);
                $inches = $inchesTotal - ($feet * 12);
                $imperial = array(
                    'feet' => $feet,
                    'inches' => $inches
                );
                $this->bodyheight = array(
                    'imperial' => $imperial,
                    'metric' => $value
                );
            }
        }
        return $this->bodyheight;
    }

    #----------------------------------------------------------------[ Spouse ]---
    /** Get spouse(s)
     * MonthName is the name, MonthInt the number of the month
     * @return array<int, array<string, mixed>>
     * @phpstan-return list<SpouseDef>
     * @see IMDB person page /bio
     */
    public function spouse(): array
    {
        if (empty($this->spouses)) {
            $query = <<<EOF
query Spouses(\$id: ID!) {
  name(id: \$id) {
    spouses {
      spouse {
        name {
          id
        }
        asMarkdown {
          plainText
        }
      }
      timeRange {
        fromDate {
          dateComponents {
            day
            month
            year
          }
        }
        toDate {
          dateComponents {
            day
            month
            year
          }
        }
        displayableProperty {
          value {
            plainText
          }
        }
      }
      attributes {
        text
      }
      current
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Spouses", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->spouses;
            }
            if (
                isset($data->name->spouses) &&
                is_array($data->name->spouses) &&
                count($data->name->spouses) > 0
            ) {
                foreach ($data->name->spouses as $spouse) {
                    // Spouse id
                    $imdbId = null;
                    if (!empty($spouse->spouse->name)) {
                        if (!empty($spouse->spouse->name->id)) {
                            $imdbId = str_replace('nm', '', $spouse->spouse->name->id);
                        }
                    }
                    // From date
                    $fromDateMonthInt = isset($spouse->timeRange->fromDate->dateComponents->month) ?
                                              $spouse->timeRange->fromDate->dateComponents->month : null;
                    $fromDateMonthName = null;
                    if (!empty($fromDateMonthInt)) {
                        $fromDateMonthName = date("F", mktime(0, 0, 0, $fromDateMonthInt, 10));
                    }
                    $fromDate = array(
                        "day" => isset($spouse->timeRange->fromDate->dateComponents->day) ?
                                       $spouse->timeRange->fromDate->dateComponents->day : null,
                        "month" => $fromDateMonthName,
                        "mon" => $fromDateMonthInt,
                        "year" => isset($spouse->timeRange->fromDate->dateComponents->year) ?
                                        $spouse->timeRange->fromDate->dateComponents->year : null
                    );
                    // To date
                    $toDateMonthInt = isset($spouse->timeRange->toDate->dateComponents->month) ?
                                            $spouse->timeRange->toDate->dateComponents->month : null;
                    $toDateMonthName = null;
                    if (!empty($toDateMonthInt)) {
                        $toDateMonthName = date("F", mktime(0, 0, 0, $toDateMonthInt, 10));
                    }
                    $toDate = array(
                        "day" => isset($spouse->timeRange->toDate->dateComponents->day) ?
                                       $spouse->timeRange->toDate->dateComponents->day : null,
                        "month" => $toDateMonthName,
                        "mon" => $toDateMonthInt,
                        "year" => isset($spouse->timeRange->toDate->dateComponents->year) ?
                                        $spouse->timeRange->toDate->dateComponents->year : null
                    );
                    // Comments and children
                    $comment = array();
                    $children = 0;
                    if (
                        isset($spouse->attributes) &&
                        is_array($spouse->attributes) &&
                        count($spouse->attributes) > 0
                    ) {
                        foreach ($spouse->attributes as $key => $attribute) {
                            if (!empty($attribute->text)) {
                                if (stripos($attribute->text, "child") !== false) {
                                    $children = (int) preg_replace('/[^0-9]/', '', $attribute->text);
                                } else {
                                    $comment[] = $attribute->text;
                                }
                            }
                        }
                    }
                    $this->spouses[] = array(
                        'imdb' => $imdbId,
                        'name' => isset($spouse->spouse->asMarkdown->plainText) ?
                                        $spouse->spouse->asMarkdown->plainText : null,
                        'from' => $fromDate,
                        'to' => $toDate,
                        'dateText' => isset($spouse->timeRange->displayableProperty->value->plainText) ?
                                            $spouse->timeRange->displayableProperty->value->plainText : null,
                        'comment' => $comment,
                        'children' => $children,
                        'current' => $spouse->current
                    );
                }
            }
        }
        return $this->spouses;
    }

    #----------------------------------------------------------------[ Children ]---
    /** Get the Children
     * @phpstan-return list<RelativeDef>
     * @see IMDB person page /bio
     */
    public function children(): array
    {
        if (empty($this->children)) {
            return $this->nameDetailsParse("CHILDREN", $this->children);
        }
        return $this->children;
    }

    #----------------------------------------------------------------[ Parents ]---
    /** Get the Parents
     * @phpstan-return list<RelativeDef>
     * @see IMDB person page /bio
     */
    public function parents(): array
    {
        if (empty($this->parents)) {
            return $this->nameDetailsParse("PARENTS", $this->parents);
        }
        return $this->parents;
    }

    #----------------------------------------------------------------[ Relatives ]---
    /** Get the relatives
     * @phpstan-return list<RelativeDef>
     * @see IMDB person page /bio
     */
    public function relatives(): array
    {
        if (empty($this->relatives)) {
            return $this->nameDetailsParse("OTHERS", $this->relatives);
        }
        return $this->relatives;
    }

    #---------------------------------------------------------------[ MiniBio ]---
    /** Get the person's mini bio
     * @phpstan-return list<array{
     *     desc: string,
     *     author: string
     * }>
     * @see IMDB person page /bio
     */
    public function bio(): array
    {
        if (empty($this->bioBio)) {
            $query = <<<EOF
query MiniBio(\$id: ID!) {
  name(id: \$id) {
    bios(first: 9999) {
      edges {
        node {
          text {
            plainText
          }
          author {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MiniBio", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->bioBio;
            }
            if (
                isset($data->name->bios->edges) &&
                is_array($data->name->bios->edges) &&
                count($data->name->bios->edges) > 0
            ) {
                foreach ($data->name->bios->edges as $edge) {
                    $this->bioBio[] = array(
                        'desc' => isset($edge->node->text->plainText) ?
                                        $edge->node->text->plainText : null,
                        'author' => isset($edge->node->author->plainText) ?
                                        $edge->node->author->plainText : null
                    );
                }
            }
        }
        return $this->bioBio;
    }

    #----------------------------------------------------------------[ Trivia ]---
    /** Get the Trivia
     * @return string[] trivias
     * @see IMDB person page /bio
     */
    public function trivia(): array
    {
        if (empty($this->bioTrivia)) {
            return $this->dataParse("trivia", $this->bioTrivia);
        }
        return $this->bioTrivia;
    }

    #----------------------------------------------------------------[ Quotes ]---
    /** Get the Personal Quotes
     * @return string[] quotes
     * @see IMDB person page /bio
     */
    public function quotes(): array
    {
        if (empty($this->bioQuotes)) {
            return $this->dataParse("quotes", $this->bioQuotes);
        }
        return $this->bioQuotes;
    }

    #------------------------------------------------------------[ Trademarks ]---
    /** Get the "trademarks" of the person
     * @return string[] trademarks
     * @see IMDB person page /bio
     */
    public function trademark(): array
    {
        if (empty($this->bioTrademark)) {
            return $this->dataParse("trademarks", $this->bioTrademark);
        }
        return $this->bioTrademark;
    }

    #----------------------------------------------------------------[ Salary ]---
    /** Get the salary list
     * @phpstan-return list<SalaryDef>
     * @see IMDB person page /bio
     */
    public function salary(): array
    {
        if (empty($this->bioSalary)) {
            $query = <<<EOF
title {
  titleText {
    text
  }
  id
  releaseYear {
    year
  }
}
amount {
  amount
  currency
}
attributes {
  text
}
EOF;
            $data = $this->graphQlGetAll("Salaries", "titleSalaries", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $comments = array();
                    if (!empty($edge->node->attributes)) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->bioSalary[] = array(
                        'imdb' => isset($edge->node->title->id) ?
                                        str_replace('tt', '', $edge->node->title->id) : null,
                        'name' => isset($edge->node->title->titleText->text) ?
                                        $edge->node->title->titleText->text : null,
                        'year' => isset($edge->node->title->releaseYear->year) ?
                                        $edge->node->title->releaseYear->year : null,
                        'amount' => isset($edge->node->amount->amount) ?
                                        $edge->node->amount->amount : null,
                        'currency' => isset($edge->node->amount->currency) ?
                                            $edge->node->amount->currency : null,
                        'comment' => $comments
                    );
                }
            }
        }
        return $this->bioSalary;
    }

    #============================================================[ /publicity ]===

    #-----------------------------------------------------------[ Print media ]---
    /** Print media about this person
     * @phpstan-return list<array{
     *     title: string,
     *     author: list<string>,
     *     publisher: string,
     *     isbn: string|null
     * }>
     * @see IMDB person page /publicity
     */
    public function pubprints(): array
    {
        if (empty($this->pubPrints)) {
            $filter = ', filter: {categories: ["namePrintBiography"]}';
            $query = <<<EOF
... on NamePrintBiography {
  title {
    text
  }
  authors {
    plainText
  }
  isbn
  publisher
}
EOF;
            $data = $this->graphQlGetAll("PubPrint", "publicityListings", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $authors = array();
                    if (!empty($edge->node->authors)) {
                        foreach ($edge->node->authors as $author) {
                            if (!empty($author->plainText)) {
                                $authors[] = $author->plainText;
                            }
                        }
                    }
                    $this->pubPrints[] = array(
                        "title" => isset($edge->node->title->text) ?
                                        $edge->node->title->text : null,
                        "author" => $authors,
                        "publisher" => isset($edge->node->publisher) ?
                                            $edge->node->publisher : null,
                        "isbn" => isset($edge->node->isbn) ?
                                        $edge->node->isbn : null
                    );
                }
            }
        }
        return $this->pubPrints;
    }

    #----------------------------------------------------[ Biographical movies ]---
    /** Biographical Movies
     * @phpstan-return list<array{
     *     title: string,
     *     id: string,
     *     year: int|null,
     *     seriesTitle: string|null,
     *     seriesSeason: int|null,
     *     seriesEpisode: int|null
     * }>
     * @see IMDB person page /publicity
     */
    public function pubmovies(): array
    {
        if (empty($this->pubMovies)) {
            $filter = ', filter: {categories: ["nameFilmBiography"]}';
            $query = <<<EOF
... on NameFilmBiography {
  title {
    titleText {
      text
    }
    id
    releaseYear {
      year
    }
    series {
      displayableEpisodeNumber {
        displayableSeason {
          text
        }
        episodeNumber {
          text
        }
      }
      series {
        titleText {
          text
        }
      }
    }
  }
}
EOF;
            $data = $this->graphQlGetAll("PubFilm", "publicityListings", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $this->pubMovies[] = array(
                        "title" => isset($edge->node->title->titleText->text) ?
                                        $edge->node->title->titleText->text : null,
                        "id" => isset($edge->node->title->id) ?
                                    str_replace('tt', '', $edge->node->title->id) : null,
                        "year" => isset($edge->node->title->releaseYear->year) ?
                                        $edge->node->title->releaseYear->year : null,
                        "seriesTitle" => isset($edge->node->title->series->series->titleText->text) ?
                                            $edge->node->title->series->series->titleText->text : null,
                        "seriesSeason" => isset($edge->node->title->series->displayableEpisodeNumber->displayableSeason->text) ?
                                                $edge->node->title->series->displayableEpisodeNumber->displayableSeason->text : null,
                        "seriesEpisode" => isset($edge->node->title->series->displayableEpisodeNumber->episodeNumber->text) ?
                                                $edge->node->title->series->displayableEpisodeNumber->episodeNumber->text : null,
                    );
                }
            }
        }
        return $this->pubMovies;
    }

    #-----------------------------------------------------------[ Portrayal]---
    /** Portrayal listings about this person
     * @phpstan-return list<array{
     *     title: string,
     *     id: string,
     *     year: int|null
     * }>
     * @see IMDB person page /publicity
     */
    public function pubportrayal(): array
    {
        if (empty($this->pubPortrayal)) {
            $filter = ', filter: {categories: ["namePortrayal"]}';
            $query = <<<EOF
... on NamePortrayal {
  title {
    titleText {
      text
    }
    id
    releaseYear {
      year
    }
  }
}
EOF;
            $data = $this->graphQlGetAll("PubPortrayal", "publicityListings", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $this->pubPortrayal[] = array(
                        'title' => isset($edge->node->title->titleText->text) ?
                                        $edge->node->title->titleText->text : null,
                        'id' => isset($edge->node->title->id) ?
                                    str_replace('tt', '', $edge->node->title->id) : null,
                        'year' => isset($edge->node->title->releaseYear->year) ?
                                        $edge->node->title->releaseYear->year : null
                    );
                }
            }
        }
        return $this->pubPortrayal;
    }

    #----------------------------------------------------------------[ Article ]---
    /** Get the Publicity Articles of this name
     * @phpstan-return list<PublicityDef>
     * @see IMDB person page /publicity
     */
    public function pubarticle(): array
    {
        if (empty($this->pubArticle)) {
            $this->pubArticle = $this->pubOtherListing("PublicityArticle");
        }
        return $this->pubArticle;
    }

    #----------------------------------------------------------------[ Interview ]---
    /** Get the Publicity Interviews of this name
     * @phpstan-return list<PublicityDef>
     * @see IMDB person page /publicity
     */
    public function pubinterview(): array
    {
        if (empty($this->pubInterview)) {
            $this->pubInterview = $this->pubOtherListing("PublicityInterview");
        }
        return $this->pubInterview;
    }

    #----------------------------------------------------------------[ Magazines ]---
    /** Get the Publicity Magazines of this name
     * @phpstan-return list<PublicityDef>
     * @see IMDB person page /publicity
     */
    public function pubmagazine(): array
    {
        if (empty($this->pubMagazine)) {
            $this->pubMagazine = $this->pubOtherListing("PublicityMagazineCover");
        }
        return $this->pubMagazine;
    }

    #----------------------------------------------------------------[ Pictorial ]---
    /** Get the Publicity Pictoryials of this name
     * @phpstan-return list<PublicityDef>
     * @see IMDB person page /publicity
     */
    public function pubpictorial(): array
    {
        if (empty($this->pubPictorial)) {
            $this->pubPictorial = $this->pubOtherListing("PublicityPictorial");
        }
        return $this->pubPictorial;
    }

    #============================================================[ /OtherWorks ]===

    /** Other works of this person
     * @phpstan-return list<array{
     *     category: string,
     *     fromDate: array{
     *         day: int|null,
     *         month: int|null,
     *         year: int|null
     *     }|null,
     *     toDate: array{
     *         day: int|null,
     *         month: int|null,
     *         year: int|null
     *     }|null,
     *     text: string
     * }>
     * @see IMDB person page /otherworks
     */
    public function otherWorks(): array
    {
        if (empty($this->otherWorks)) {
            $query = <<<EOF
category {
  text
}
fromDate
toDate
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("OtherWorks", "otherWorks", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    // From date
                    $fromDate = array(
                        "day" => isset($edge->node->fromDate->day) ?
                                    $edge->node->fromDate->day : null,
                        "month" => isset($edge->node->fromDate->month) ?
                                        $edge->node->fromDate->month : null,
                        "year" => isset($edge->node->fromDate->year) ?
                                        $edge->node->fromDate->year : null
                    );
                    // To date
                    $toDate = array(
                        "day" => isset($edge->node->toDate->day) ?
                                    $edge->node->toDate->day : null,
                        "month" => isset($edge->node->toDate->month) ?
                                        $edge->node->toDate->month : null,
                        "year" => isset($edge->node->toDate->year) ?
                                        $edge->node->toDate->year : null
                    );
                    $this->otherWorks[] = array(
                        "category" => isset($edge->node->category) ?
                                            $edge->node->category->text : null,
                        "fromDate" => $fromDate,
                        "toDate" => $toDate,
                        "text" => isset($edge->node->text->plainText) ?
                                        $edge->node->text->plainText : null
                    );
                }
            }
        }
        return $this->otherWorks;
    }

    #-------------------------------------------------------[ External sites ]---
    /**
     * external websites with info of this name, excluding external reviews.
     * @phpstan-return array<'official'|'video'|'photo'|'sound'|'misc', list<array{
     *     label: string|null,
     *     url: string|null,
     *     language: list<string>
     * }>>
     * @see IMDB page /externalsites
     */
    public function extSites(): array
    {
        $categoryIds = array(
            'official' => 'official',
            'video' => 'video',
            'photo' => 'photo',
            'sound' => 'sound',
            'misc' => 'misc'
        );
        if (empty($this->externalSites)) {
            foreach ($categoryIds as $categoryId) {
                $this->externalSites[$categoryId] = array();
            }
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
                    if (!empty($edge->node->externalLinkLanguages)) {
                        foreach ($edge->node->externalLinkLanguages as $lang) {
                            if (!empty($lang->text)) {
                                $language[] = $lang->text;
                            }
                        }
                    }
                    $this->externalSites[$categoryIds[$edge->node->externalLinkCategory->id]][] = array(
                        'label' => !empty($edge->node->label) ?
                                        $edge->node->label : null,
                        'url' => !empty($edge->node->url) ?
                                        $edge->node->url : null,
                        'language' => $language
                    );
                }
            }
        }
        return $this->externalSites;
    }

    #-------------------------------------------------[ Main images ]---
    /**
     * Get image URLs for (default 6) pictures from photo page
     * @param int $amount how many images, max = 9999
     * @param bool $thumb boolean
     *      true: height is always the same (set in config), width is variable!
     *      false: untouched max width 1000 pixels
     * @return string[] string image source
     */
    public function mainphoto(int $amount = 6, bool $thumb = true): array
    {
        if (empty($this->mainPhoto)) {
            $query = <<<EOF
query MainPhoto(\$id: ID!) {
  name(id: \$id) {
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
            $data = $this->graphql->query($query, "MainPhoto", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->mainPhoto;
            }
            if (
                isset($data->name->images->edges) &&
                is_array($data->name->images->edges) &&
                count($data->name->images->edges) > 0
            ) {
                foreach ($data->name->images->edges as $edge) {
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

    #-------------------------------------------------------[ Awards ]---
    /**
     * Get all awards for a name
     * @param bool $winsOnly Default: false, set to true to only get won awards
     * @param string $event Default: "" eventId Example " ev0000003" to only get Oscars
     *  Possible values for $event:
     *  ev0000003 (Oscar)
     *  ev0000223 (Emmy)
     *  ev0000292 (Golden Globe)
     *
     * @phpstan-return array<string, list<array{
     *     awardYear: int,
     *     awardWinner: bool,
     *     awardCategory: string,
     *     awardName: string,
     *     awardTitles: list<array{
     *         titleId: string,
     *         titleName: string,
     *         titleNote: string|null,
     *         titleFullImageUrl: string|null,
     *         titleThumbImageUrl: string|null
     *     }>,
     *     awardNotes: string|null,
     *     awardOutcome: string
     * }>|array{win: int, nom: int}>
     * @see IMDB page / (TitlePage)
     */
    public function award(bool $winsOnly = false, string $event = ""): array
    {
        if (empty($this->awards)) {
            $filter = $this->awardFilter($winsOnly, $event);
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
  ... on AwardedNames {
    secondaryAwardTitles {
      title {
        id
        titleText {
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
                    //credited titles
                    $titles = array();
                    if (
                        isset($edge->node->awardedEntities->secondaryAwardTitles) &&
                        is_array($edge->node->awardedEntities->secondaryAwardTitles) &&
                        count($edge->node->awardedEntities->secondaryAwardTitles) > 0
                    ) {
                        foreach ($edge->node->awardedEntities->secondaryAwardTitles as $title) {
                            $titleThumbImageUrl = null;
                            $titleFullImageUrl = null;
                            if (!empty($title->title->primaryImage->url)) {
                                $img = str_replace('.jpg', '', $title->title->primaryImage->url);
                                $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                                $fullImageWidth = $title->title->primaryImage->width;
                                $fullImageHeight = $title->title->primaryImage->height;
                                $newImageWidth = 140;
                                $newImageHeight = 207;
                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $titleThumbImageUrl = $img . $parameter;
                            }
                            $titles[] = array(
                                'titleId' => isset($title->title->id) ?
                                                str_replace('tt', '', $title->title->id) : null,
                                'titleName' => isset($title->title->titleText->text) ?
                                                    $title->title->titleText->text : null,
                                'titleNote' => isset($title->note->plainText) ?
                                                    trim($title->note->plainText, " ()") : null,
                                'titleFullImageUrl' => $titleFullImageUrl,
                                'titleThumbImageUrl' => $titleThumbImageUrl
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
                        'awardTitles' => $titles,
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

    #============================================================[ /creditKnownFor ]===
    /** All prestigious title credits for this person
     * @phpstan-return list<array{
     *     title: string,
     *     titleId: string,
     *     titleYear: int|null,
     *     titleEndYear: int|null,
     *     titleFullImageUrl: string|null,
     *     titleThumbImageUrl: string|null,
     *     titleCharacters: list<string>
     * }>
     * @see IMDB person page /credits
     */
    public function creditKnownFor(): array
    {
        if (empty($this->creditKnownFor)) {
            $query = <<<EOF
query KnownFor(\$id: ID!) {
  name(id: \$id) {
    knownFor(first: 9999) {
      edges {
        node{
          credit {
            title {
              id
              titleText {
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
            }
            ... on Cast {
              characters {
                name
              }
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "KnownFor", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->creditKnownFor;
            }
            if (
                isset($data->name->knownFor->edges) &&
                is_array($data->name->knownFor->edges) &&
                count($data->name->knownFor->edges) > 0
            ) {
                foreach ($data->name->knownFor->edges as $edge) {
                    $titleThumbImageUrl = null;
                    $titleFullImageUrl = null;
                    if (!empty($edge->node->credit->title->primaryImage->url)) {
                        $img = str_replace('.jpg', '', $edge->node->credit->title->primaryImage->url);
                        $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                        $fullImageWidth = $edge->node->credit->title->primaryImage->width;
                        $fullImageHeight = $edge->node->credit->title->primaryImage->height;
                        $newImageWidth = 140;
                        $newImageHeight = 207;
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $titleThumbImageUrl = $img . $parameter;
                    }
                    $characters = array();
                    if (
                        isset($edge->node->credit->characters) &&
                        is_array($edge->node->credit->characters) &&
                        count($edge->node->credit->characters) > 0
                    ) {
                        foreach ($edge->node->credit->characters as $character) {
                            if (!empty($character->name)) {
                                $characters[] = $character->name;
                            }
                        }
                    }
                    $this->creditKnownFor[] = array(
                        'title' => isset($edge->node->credit->title->titleText->text) ?
                                        $edge->node->credit->title->titleText->text : null,
                        'titleId' => isset($edge->node->credit->title->id) ?
                                        str_replace('tt', '', $edge->node->credit->title->id) : null,
                        'titleYear' => isset($edge->node->credit->title->releaseYear->year) ?
                                            $edge->node->credit->title->releaseYear->year : null,
                        'titleEndYear' => isset($edge->node->credit->title->releaseYear->endYear) ?
                                                $edge->node->credit->title->releaseYear->endYear : null,
                        'titleCharacters' => $characters,
                        'titleFullImageUrl' => $titleFullImageUrl,
                        'titleThumbImageUrl' => $titleThumbImageUrl
                    );
                }
            }
        }
        return $this->creditKnownFor;
    }

    #-------------------------------------------------------[ Credits ]---
    /** Get all credits for a person
     * @phpstan-return array<string, list<array{
     *     titleId: string,
     *     titleName: string,
     *     titleType: string,
     *     year: int|null,
     *     endYear: int|null,
     *     characters: list<string>,
     *     jobs: list<string>,
     *     titleFullImageUrl: string|null,
     *     titleThumbImageUrl: string|null
     * }>>
     * @see IMDB page /credits
     */
    public function credit(): array
    {
        if (empty($this->credits)) {
            $query = <<<EOF
category {
  id
}
title {
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
}
... on Crew {
  jobs {
    text
  }
}
EOF;
            $edges = $this->graphQlGetAll("Credits", "credits", $query);
            if (count($edges) > 0) {
                foreach ($edges as $edge) {
                    $characters = array();
                    if (
                        isset($edge->node->characters) &&
                        is_array($edge->node->characters) &&
                        count($edge->node->characters) > 0
                    ) {
                        foreach ($edge->node->characters as $character) {
                            if (!empty($character->name)) {
                                $characters[] = $character->name;
                            }
                        }
                    }
                    $jobs = array();
                    if (
                        isset($edge->node->jobs) &&
                        is_array($edge->node->jobs) &&
                        count($edge->node->jobs) > 0
                    ) {
                        foreach ($edge->node->jobs as $job) {
                            if (!empty($job->text)) {
                                $jobs[] = $job->text;
                            }
                        }
                    }
                    $titleThumbImageUrl = null;
                    $titleFullImageUrl = null;
                    if (!empty($edge->node->title->primaryImage->url)) {
                        $img = str_replace('.jpg', '', $edge->node->title->primaryImage->url);
                        $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                        $fullImageWidth = $edge->node->title->primaryImage->width;
                        $fullImageHeight = $edge->node->title->primaryImage->height;
                        $newImageWidth = 140;
                        $newImageHeight = 207;
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $titleThumbImageUrl = $img . $parameter;
                    }
                   // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('_', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->credits[$categoryId][] = array(
                        'titleId' => isset($edge->node->title->id) ?
                                        str_replace('tt', '', $edge->node->title->id) : null,
                        'titleName' => isset($edge->node->title->titleText->text) ?
                                            $edge->node->title->titleText->text : null,
                        'titleType' => isset($edge->node->title->titleType->text) ?
                                            $edge->node->title->titleType->text : null,
                        'year' => isset($edge->node->title->releaseYear->year) ?
                                        $edge->node->title->releaseYear->year : null,
                        'endYear' => isset($edge->node->title->releaseYear->endYear) ?
                                        $edge->node->title->releaseYear->endYear : null,
                        'characters' => $characters,
                        'jobs' => $jobs,
                        'titleFullImageUrl' => $titleFullImageUrl,
                        'titleThumbImageUrl' => $titleThumbImageUrl
                    );
                }
            }
        }
        return $this->credits;
    }

    #-------------------------------------------------[ Video ]---
    /**
     * Get all video URL's and images from videogallery page
     * @phpstan-return array<'Trailer'|'Clip', list<array{
     *     id: string,
     *     name: string,
     *     runtime: int|null,
     *     description: string|null,
     *     titleName: string|null,
     *     titleYear: int|null,
     *     playbackUrl: string,
     *     imageUrl: string|null
     * }>>
     */
    public function video(): array
    {
        if (empty($this->videos)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  name(id: \$id) {
    primaryVideos(first:9999) {
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
            $data = $this->graphql->query($query, "Video", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->videos;
            }
            if (
                isset($data->name->primaryVideos->edges) &&
                is_array($data->name->primaryVideos->edges) &&
                count($data->name->primaryVideos->edges) > 0
            ) {
                foreach ($data->name->primaryVideos->edges as $edge) {
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
                        'name' => isset($edge->node->name->value) ?
                                        $edge->node->name->value : null,
                        'runtime' => isset($edge->node->runtime->value) ?
                                        $edge->node->runtime->value : null,
                        'description' => isset($edge->node->description->value) ?
                                            $edge->node->description->value : null,
                        'titleName' => isset($edge->node->primaryTitle->titleText->text) ?
                                            $edge->node->primaryTitle->titleText->text : null,
                        'titleYear' => isset($edge->node->primaryTitle->releaseYear->year) ?
                                            $edge->node->primaryTitle->releaseYear->year : null,
                        'playbackUrl' => !empty($videoId) ?
                                                'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                        'imageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->videos;
    }

    #----------------------------------------------------------[ News ]---
    /**
     * Get news items about this name, max 100 items!
     * @phpstan-return list<array{
     *     id: string,
     *     title: string,
     *     author: string|null,
     *     date: string,
     *     extUrl: string,
     *     extHomepageUrl: string|null,
     *     extHomepageLabel: string|null,
     *     textHtml: string|null,
     *     textText: string|null,
     *     thumbnailUrl: string|null
     * }>
     */
    public function news(): array
    {
        if (empty($this->news)) {
            $query = <<<EOF
query News(\$id: ID!) {
  name(id: \$id) {
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
            $data = $this->graphql->query($query, "News", ["id" => "nm$this->imdbID"]);
            if (!isset($data->name)) {
                return $this->news;
            }
            if (
                isset($data->name->news->edges) &&
                is_array($data->name->news->edges) &&
                count($data->name->news->edges) > 0
            ) {
                foreach ($data->name->news->edges as $edge) {
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

    #========================================================[ photo/poster ]===
    /**
     * Setup cover photo (thumbnail and big variant)
     * @return void
     * @see IMDB page / (NamePage)
     */
    private function populatePoster(): void
    {
        $query = <<<EOF
query Poster(\$id: ID!) {
  name(id: \$id) {
    primaryImage {
      url
      width
      height
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Poster", ["id" => "nm$this->imdbID"]);
        if (!empty($data->name->primaryImage->url)) {
            $img = str_replace('.jpg', '', $data->name->primaryImage->url);

            // full image
            $this->mainPoster = $img . 'QL100_UX1000_.jpg';

            // thumb image
            if (!empty($data->name->primaryImage->width) && !empty($data->name->primaryImage->height)) {
                $fullImageWidth = $data->name->primaryImage->width;
                $fullImageHeight = $data->name->primaryImage->height;
                $newImageWidth = $this->config->namePhotoThumbnailWidth;
                $newImageHeight = $this->config->namePhotoThumbnailHeight;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $this->mainPosterThumb = $img . $parameter;
            }
        }
    }

    #-----------------------------------------[ Helper for Trivia, Quotes and Trademarks ]---
    /** Parse Trivia, Quotes and Trademarks
     * @param string $name
     * @param array<string, string> $arrayName
     * @return array<string, string>
     */
    protected function dataParse(string $name, array $arrayName): array
    {
        $query = <<<EOF
text {
  plainText
}
EOF;
        $data = $this->graphQlGetAll("Data", $name, $query);
        if (count($data) > 0) {
            foreach ($data as $edge) {
                if (!empty($edge->node->text->plainText)) {
                    $arrayName[] = $edge->node->text->plainText;
                }
            }
        }
        return $arrayName;
    }

    #-----------------------------------------[ Helper for children, parents, relatives ]---
    /** Parse children, parents, relatives
     * @param string $name
     *     possible values for $name: CHILDREN, PARENTS, OTHERS
     * @param array<string, string> $arrayName
     * @phpstan-return list<RelativeDef>
     */
    protected function nameDetailsParse(string $name, array $arrayName): array
    {
        $filter = ', filter: {relationshipTypes: ' . $name . '}';
        $query = <<<EOF
relationName {
  name {
    id
    nameText {
      text
    }
  }
  nameText
}
relationshipType {
  text
}
EOF;
        $data = $this->graphQlGetAll("Data", "relations", $query, $filter);
        if (count($data) > 0) {
            foreach ($data as $edge) {
                if (empty($edge->node->relationName->name) && empty($edge->node->relationName->nameText)) {
                    continue;
                }
                if (!empty($edge->node->relationName->name)) {
                    $id = isset($edge->node->relationName->name->id) ?
                                str_replace('nm', '', $edge->node->relationName->name->id) : null;
                    $name = isset($edge->node->relationName->name->nameText->text) ?
                                $edge->node->relationName->name->nameText->text : null;
                } else {
                    $id = null;
                    $name = isset($edge->node->relationName->nameText) ?
                                $edge->node->relationName->nameText : null;
                }
                $arrayName[] = array(
                    'imdb' => $id,
                    'name' => $name,
                    'relType' => isset($edge->node->relationshipType->text) ?
                                    $edge->node->relationshipType->text : null
                );
            }
        }
        return $arrayName;
    }

    #-----------------------------------------------------------[ Other Publicity Listings helper]---
    /** helper for Article, Interview, Magazine and Pictorial publicity listings about this person
     * @phpstan-return list<PublicityDef> listing
     * @see IMDB person page /publicity
     */
    protected function pubOtherListing(string $listingType): array
    {
        $results = array();
        $filter = ', filter: {categories: ["' . lcfirst($listingType) . '"]}';
        $query = <<<EOF
... on $listingType {
  authors {
    plainText
  }
  publication
  reference
  date
  region {
    id
  }
  title {
    text
  }
}
EOF;
        $data = $this->graphQlGetAll($listingType, "publicityListings", $query, $filter);
        if (count($data) > 0) {
            foreach ($data as $edge) {
                $date = array(
                    'day' => isset($edge->node->date->day) ?
                                $edge->node->date->day : null,
                    'month' => isset($edge->node->date->month) ?
                                    $edge->node->date->month : null,
                    'year' => isset($edge->node->date->year) ?
                                    $edge->node->date->year : null
                );
                $authors = array();
                if (
                    isset($edge->node->authors) &&
                    is_array($edge->node->authors) &&
                    count($edge->node->authors) > 0
                ) {
                    foreach ($edge->node->authors as $author) {
                        if (!empty($author->plainText)) {
                            $authors[] = $author->plainText;
                        }
                    }
                }
                $results[] = array(
                    'publication' => isset($edge->node->publication) ?
                                        $edge->node->publication : null,
                    'regionId' => isset($edge->node->region->id) ?
                                        $edge->node->region->id : null,
                    'title' => isset($edge->node->title->text) ?
                                    $edge->node->title->text : null,
                    'date' => $date,
                    'reference' => isset($edge->node->reference) ?
                                        $edge->node->reference : null,
                    'authors' => $authors
                );
            }
        }
        return $results;
    }

    #-----------------------------------------[ Helper GraphQL Paginated ]---
    /**
     * Get all edges of a field in the name type
     * @param string $queryName The cached query name
     * @param string $fieldName The field on name you want to get
     * @param string $nodeQuery Graphql query that fits inside node { }
     * @param string $filter Add's extra Graphql query filters like categories
     * @return \stdClass[]
     */
    protected function graphQlGetAll(string $queryName, string $fieldName, string $nodeQuery, string $filter = ''): array
    {
        $query = <<<EOF
query $queryName(\$id: ID!, \$after: ID) {
  name(id: \$id) {
    $fieldName(first: 9999, after: \$after$filter) {
      edges {
        node {
          $nodeQuery
        }
      }
      pageInfo {
        endCursor
        hasNextPage
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
            $data = $this->graphql->query($fullQuery, $queryName, ["id" => "nm$this->imdbID", "after" => $endCursor]);

            if (isset($data->name)) {
                $nameVars = get_object_vars($data->name);
                if (isset($nameVars[$fieldName])) {
                    /** @var \stdClass $field */
                    $field = $nameVars[$fieldName];
                    $edges = array_merge($edges, $field->edges ?? []);
                    $hasNextPage = $field->pageInfo->hasNextPage ?? false;
                    $endCursor = $field->pageInfo->endCursor ?? null;
                    continue;
                }
            }

            $hasNextPage = false;
        }
        return $edges;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * It sometimes happens that imdb redirects an existing id to a new id.
     * If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     * @info $this->imdbID The imdbid used to call this class
     * @info $nameImdbId the returned imdbid from Graphql call (in some cases this can be different)
     * @return string|false $nameImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    public function checkRedirect(): string|bool
    {
        $query = <<<EOF
query Redirect(\$id: ID!) {
  name(id: \$id) {
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Redirect", ["id" => "nm$this->imdbID"]);
        if (
            isset($data->name->meta->canonicalId) &&
            $data->name->meta->canonicalId !== ''
        ) {
            $nameImdbId = str_replace('nm', '', $data->name->meta->canonicalId);
            if ($nameImdbId  !== $this->imdbID) {
                // todo write to log?
                return $nameImdbId;
            } else {
                return false;
            }
        }
        return false;
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
