<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\TitleSearch;
use Imdb\NameSearch;
use Imdb\CompanySearch;
use Imdb\KeywordSearch;
use Imdb\TitleSearchAdvanced;
use Imdb\NameSearchAdvanced;

$searchtype = $_GET["searchtype"] ?? 'movie';
$query = $_GET["query"] ?? '';

// Direct mid navigation check
if (!empty($_GET["mid"]) && preg_match('/^(tt|nm|)([0-9]+)$/', $_GET["mid"], $matches)) {
    $target = !empty($matches[1]) ? $matches[1] : ($searchtype === 'person' ? 'nm' : 'tt');
    if ($target === 'nm') {
        header("Location: person.php?mid=" . $matches[2]);
    } else {
        header("Location: movie.php?mid=" . $matches[2]);
    }
    exit;
}

$results = null;
$executedClass = '';
$executedMethod = '';

if ($query !== '' || !empty($_GET['adv_submit'])) {
    switch ($searchtype) {
        case 'movie':
            $executedClass = 'TitleSearch';
            $executedMethod = 'search';
            $s = new TitleSearch();
            $results = $s->search($query);
            break;
        case 'episode':
            $executedClass = 'TitleSearch';
            $executedMethod = 'search (TV_EPISODE)';
            $s = new TitleSearch();
            $results = $s->search($query, [TitleSearch::TV_EPISODE]);
            break;
        case 'person':
            $executedClass = 'NameSearch';
            $executedMethod = 'search';
            $s = new NameSearch();
            $results = $s->search($query);
            break;
        case 'company':
            $executedClass = 'CompanySearch';
            $executedMethod = 'searchCompany';
            $s = new CompanySearch();
            $results = $s->searchCompany($query);
            break;
        case 'keyword':
            $executedClass = 'KeywordSearch';
            $executedMethod = 'searchKeyword';
            $s = new KeywordSearch();
            $results = $s->searchKeyword($query);
            break;
        case 'title_advanced':
            $executedClass = 'TitleSearchAdvanced';
            $executedMethod = 'advancedSearch';
            $s = new TitleSearchAdvanced();
            $results = $s->advancedSearch(
                $_GET['searchTerm'] ?? '',
                $_GET['genres'] ?? '',
                $_GET['types'] ?? '',
                $_GET['creditId'] ?? '',
                $_GET['startDate'] ?? '',
                $_GET['endDate'] ?? '',
                $_GET['countryId'] ?? '',
                $_GET['languageId'] ?? '',
                $_GET['keywords'] ?? '',
                $_GET['companyId'] ?? ''
            );
            break;
        case 'person_advanced':
            $executedClass = 'NameSearchAdvanced';
            $executedMethod = 'advancedNameSearch';
            $s = new NameSearchAdvanced();
            $results = $s->advancedNameSearch(
                $_GET['searchTerm'] ?? '',
                $_GET['birthDay'] ?? '',
                $_GET['birthDateRangeStart'] ?? '',
                $_GET['birthDateRangeEnd'] ?? '',
                $_GET['deathDateRangeStart'] ?? '',
                $_GET['deathDateRangeEnd'] ?? '',
                $_GET['birthPlace'] ?? ''
            );
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Search Demo</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="nav-bar">
    <a href="index.html">Home</a> |
    <a href="search.php">Search</a> |
    <a href="calendar.php">Calendar</a> |
    <a href="chart.php">Charts</a> |
    <a href="trailers.php">Trailers</a> |
    <a href="news.php">News</a> |
    <a href="cache.php">Cache</a>
  </div>

  <h2 class="text-center">IMDb Search Suite</h2>

  <form action="search.php" method="get" class="mb-10">
    <table>
      <tr><th colspan="2">Standard Search</th></tr>
      <tr>
        <td class="text-right pl-10 pr-10" style="width: 25%;">Search Query:</td>
        <td><input type="text" name="query" value="<?php echo esc($query); ?>" size="40"></td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10">Search Type:</td>
        <td>
          <select name="searchtype">
            <option value="movie" <?php if ($searchtype === 'movie') {
                echo 'selected';
                                  } ?>>Title / Movie (TitleSearch)</option>
            <option value="episode" <?php if ($searchtype === 'episode') {
                echo 'selected';
                                    } ?>>Episode (TitleSearch)</option>
            <option value="person" <?php if ($searchtype === 'person') {
                echo 'selected';
                                   } ?>>Person (NameSearch)</option>
            <option value="company" <?php if ($searchtype === 'company') {
                echo 'selected';
                                    } ?>>Company (CompanySearch)</option>
            <option value="keyword" <?php if ($searchtype === 'keyword') {
                echo 'selected';
                                    } ?>>Keyword (KeywordSearch)</option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10">or Direct IMDb ID:</td>
        <td><input type="text" name="mid" size="20" placeholder="e.g. tt0133093 or nm0000206"></td>
      </tr>
      <tr>
        <td colspan="2" class="text-center"><input type="submit" value="Execute Search"></td>
      </tr>
    </table>
  </form>

  <form action="search.php" method="get" class="mb-10">
    <input type="hidden" name="searchtype" value="title_advanced">
    <input type="hidden" name="adv_submit" value="1">
    <table>
      <tr><th colspan="2">TitleSearchAdvanced (advancedSearch)</th></tr>
      <tr><td class="text-right pl-10 pr-10">Search Term:</td><td><input type="text" name="searchTerm" value="<?php echo esc($_GET['searchTerm'] ?? ''); ?>" size="30"></td></tr>
      <tr><td class="text-right pl-10 pr-10">Genres:</td><td><input type="text" name="genres" value="<?php echo esc($_GET['genres'] ?? ''); ?>" placeholder="e.g. action,sci-fi" size="30"></td></tr>
      <tr><td class="text-right pl-10 pr-10">Types:</td><td><input type="text" name="types" value="<?php echo esc($_GET['types'] ?? ''); ?>" placeholder="e.g. movie,tvSeries" size="30"></td></tr>
      <tr><td class="text-right pl-10 pr-10">Start Date / End Date:</td><td><input type="text" name="startDate" value="<?php echo esc($_GET['startDate'] ?? ''); ?>" placeholder="YYYY-MM-DD" size="12"> to <input type="text" name="endDate" value="<?php echo esc($_GET['endDate'] ?? ''); ?>" placeholder="YYYY-MM-DD" size="12"></td></tr>
      <tr><td colspan="2" class="text-center"><input type="submit" value="Execute Advanced Title Search"></td></tr>
    </table>
  </form>

  <form action="search.php" method="get" class="mb-10">
    <input type="hidden" name="searchtype" value="person_advanced">
    <input type="hidden" name="adv_submit" value="1">
    <table>
      <tr><th colspan="2">NameSearchAdvanced (advancedNameSearch)</th></tr>
      <tr><td class="text-right pl-10 pr-10">Search Term:</td><td><input type="text" name="searchTerm" value="<?php echo esc($_GET['searchTerm'] ?? ''); ?>" size="30"></td></tr>
      <tr><td class="text-right pl-10 pr-10">Birth Day:</td><td><input type="text" name="birthDay" value="<?php echo esc($_GET['birthDay'] ?? ''); ?>" placeholder="MM-DD" size="12"></td></tr>
      <tr><td class="text-right pl-10 pr-10">Birth Place:</td><td><input type="text" name="birthPlace" value="<?php echo esc($_GET['birthPlace'] ?? ''); ?>" size="30"></td></tr>
      <tr><td colspan="2" class="text-center"><input type="submit" value="Execute Advanced Person Search"></td></tr>
    </table>
  </form>

  <?php if ($results !== null) : ?>
    <h3>Search Results (via <code><?php echo esc($executedClass); ?>::<?php echo esc($executedMethod); ?></code>)</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Result Details</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo renderValue($results); ?></td>
        </tr>
      </tbody>
    </table>
  <?php endif; ?>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>

