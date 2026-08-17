<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Title;
use Imdb\TitleCombined;
use Imdb\Config;

$mid = $_GET['mid'] ?? '0133093'; // Default Matrix
$mid = preg_replace('/[^0-9]/', '', $mid);

$config = new Config();
$config->cacheUse = true;
$config->cacheStore = true;
$config->language = 'en-US,en';
$logger = null; //new \Imdb\Logger();

$title = new Title($mid, $config, $logger);
$titleCombined = new TitleCombined($mid, $config, $logger);

// List of Title methods to test with their readable names
$methodList = [
    'imdbid' => 'IMDb ID',
    'titleYearMovietype' => 'Title Year MovieType',
    'title' => 'Title',
    'originalTitle' => 'Original Title',
    'year' => 'Year',
    'movietype' => 'Movie Type',
    'runtime' => 'Runtime',
    'ratingVotes' => 'Rating & Votes',
    'metacritic' => 'Metacritic Score',
    'rank' => 'Rank',
    'faq' => 'FAQ',
    'recommendation' => 'Recommendations',
    'language' => 'Languages',
    'genre' => 'Genres',
    'plotoutline' => 'Plot Outline',
    'photo' => 'Photo URL',
    'photoLocalurl' => 'Photo Local URL',
    'country' => 'Countries',
    'releaseDate' => 'Release Date',
    'alsoknow' => 'Also Known As',
    'mpaa' => 'MPAA Rating',
    'parentsGuide' => 'Parents Guide',
    'top250' => 'Top 250 Rank',
    'plot' => 'Plots',
    'tagline' => 'Taglines',
    'principalCredits' => 'Principal Credits',
    'cast' => 'Cast',
    'director' => 'Directors',
    'cinematographer' => 'Cinematographers',
    'writer' => 'Writers',
    'producer' => 'Producers',
    'composer' => 'Composers',
    'stunts' => 'Stunts',
    'thanks' => 'Thanks',
    'visualEffects' => 'Visual Effects',
    'specialEffects' => 'Special Effects',
    'crazyCredit' => 'Crazy Credits',
    'episode' => 'Episodes',
    'isOngoing' => 'Is Ongoing Series',
    'goof' => 'Goofs',
    'quote' => 'Quotes',
    'trivia' => 'Trivia',
    'soundtrack' => 'Soundtracks',
    'location' => 'Filming Locations',
    'prodCompany' => 'Production Companies',
    'distCompany' => 'Distributors',
    'specialCompany' => 'Special Effects Companies',
    'otherCompany' => 'Other Companies',
    'connection' => 'Connections',
    'extSites' => 'External Sites',
    'budget' => 'Budget',
    'gross' => 'Gross',
    'interests' => 'Interests / Related',
    'keyword' => 'Keywords',
    'alternateVersion' => 'Alternate Versions',
    'mainphoto' => 'Main Photos',
    'video' => 'Videos',
    'mainaward' => 'Main Award Summary',
    'award' => 'Awards',
    'awardFilter' => 'Awards (Wins Only)',
    'sound' => 'Sound Mix',
    'color' => 'Color',
    'aspectRatio' => 'Aspect Ratio',
    'camera' => 'Camera',
    'featuredReview' => 'Featured Review',
    'isAdult' => 'Is Adult',
    'watchOption' => 'Watch Options',
    'productionStatus' => 'Production Status',
    'news' => 'News',
    'checkRedirect' => 'Check Redirect',
];

$titleCombinedData = method_exists($titleCombined, 'main') ? $titleCombined->main() : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo esc($title->title() ?: 'Movie'); ?> - IMDbPHP Title Test</title>
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

  <h2 class="text-center">Title Details: <span><?php echo esc($title->title()); ?> (tt<?php echo esc($mid); ?>)</span></h2>

  <form method="get" action="movie.php" class="text-center mb-10">
    <label for="mid">Change IMDb Title ID (e.g. 0133093): </label>
    <input type="text" name="mid" id="mid" value="<?php echo esc($mid); ?>" size="12">
    <input type="submit" value="Load Title">
  </form>

  <?php if ($title->photo()) : ?>
    <div class="text-center photo mb-10">
      <img src="<?php echo esc($title->photo()); ?>" alt="Cover Photo">
    </div>
  <?php endif; ?>

  <h3>Imdb\Title Methods</h3>
  <table class="table">
    <thead>
      <tr>
        <th style="width: 25%;">Method / Property</th>
        <th>Returned Data</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($methodList as $methodName => $label) : ?>
            <?php if (method_exists($title, $methodName)) : ?>
          <tr>
            <td><strong><?php echo esc($label); ?></strong><br><code>Title::<?php echo esc($methodName); ?>()</code></td>
            <td>
                <?php
                if ($methodName === 'awardFilter') {
                    $res = $title->awardFilter(true, '');
                } else {
                    $res = $title->$methodName();
                }
                echo renderValue($res);
                ?>
            </td>
          </tr>
            <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Imdb\TitleCombined Method</h3>
  <table class="table">
    <thead>
      <tr>
        <th style="width: 25%;">Method</th>
        <th>Returned Data</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Main Combined Data</strong><br><code>TitleCombined::main()</code></td>
        <td><?php echo renderValue($titleCombinedData); ?></td>
      </tr>
    </tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>

