<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Name;
use Imdb\Config;

$mid = $_GET['mid'] ?? '0000206'; // Default Keanu Reeves
$mid = preg_replace('/[^0-9]/', '', $mid);

$config = new Config();
$config->cacheUse = true;
$config->cacheStore = true;
$config->language = 'en-US,en';
$logger = null; //new \Imdb\Logger();

$person = new Name($mid, $config, $logger);

// List of Name methods to test with their readable names
$methodList = [
    'imdbid' => 'IMDb ID',
    'name' => 'Name',
    'photo' => 'Photo URL',
    'photoLocalurl' => 'Photo Local URL',
    'birthname' => 'Birth Name',
    'nickname' => 'Nicknames',
    'akaName' => 'AKA Names',
    'born' => 'Born Date / Place',
    'died' => 'Died Date / Place',
    'age' => 'Age',
    'profession' => 'Profession',
    'rank' => 'Rank',
    'height' => 'Height',
    'spouse' => 'Spouse',
    'children' => 'Children',
    'parents' => 'Parents',
    'relatives' => 'Relatives',
    'bio' => 'Biography',
    'trivia' => 'Trivia',
    'quotes' => 'Quotes',
    'trademark' => 'Trademarks',
    'salary' => 'Salaries',
    'pubprints' => 'Publicity Prints',
    'pubmovies' => 'Publicity Movies',
    'pubportrayal' => 'Publicity Portrayals',
    'pubarticle' => 'Publicity Articles',
    'pubinterview' => 'Publicity Interviews',
    'pubmagazine' => 'Publicity Magazines',
    'pubpictorial' => 'Publicity Pictorials',
    'otherWorks' => 'Other Works',
    'extSites' => 'External Sites',
    'mainphoto' => 'Main Photos',
    'award' => 'Awards',
    'awardFilter' => 'Awards (Wins Only)',
    'creditKnownFor' => 'Known For Credits',
    'credit' => 'All Credits',
    'video' => 'Videos',
    'news' => 'News',
    'checkRedirect' => 'Check Redirect',
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo esc($person->name() ?: 'Person'); ?> - IMDbPHP Person Test</title>
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

  <h2 class="text-center">Person Details: <span><?php echo esc($person->name()); ?> (nm<?php echo esc($mid); ?>)</span></h2>

  <form method="get" action="person.php" class="text-center mb-10">
    <label for="mid">Change IMDb Person ID (e.g. 0000206): </label>
    <input type="text" name="mid" id="mid" value="<?php echo esc($mid); ?>" size="12">
    <input type="submit" value="Load Person">
  </form>

  <?php if ($person->photo()) : ?>
    <div class="text-center photo mb-10">
      <img src="<?php echo esc($person->photo()); ?>" alt="Photo">
    </div>
  <?php endif; ?>

  <h3>Imdb\Name Methods</h3>
  <table class="table">
    <thead>
      <tr>
        <th style="width: 25%;">Method / Property</th>
        <th>Returned Data</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($methodList as $methodName => $label) : ?>
            <?php if (method_exists($person, $methodName)) : ?>
          <tr>
            <td><strong><?php echo esc($label); ?></strong><br><code>Name::<?php echo esc($methodName); ?>()</code></td>
            <td>
                <?php
                if ($methodName === 'awardFilter') {
                    $res = $person->awardFilter(true, '');
                } else {
                    $res = $person->$methodName();
                }
                echo renderValue($res);
                ?>
            </td>
          </tr>
            <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
