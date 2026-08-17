<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\News;

$news = new News();

$listType = $_GET['listType'] ?? 'MOVIE';

$newsList = $news->newsList($listType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - News Test</title>
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

  <h2 class="text-center">News Test Suite</h2>

  <form method="get" action="news.php" class="text-center mb-10">
    <label for="listType">News List Type: </label>
    <select name="listType" id="listType" onchange="this.form.submit()">
      <option value="MOVIE" <?php if ($listType === 'MOVIE') {
            echo 'selected';
                            } ?>>MOVIE</option>
      <option value="TV" <?php if ($listType === 'TV') {
            echo 'selected';
                         } ?>>TV</option>
      <option value="CELEBRITY" <?php if ($listType === 'CELEBRITY') {
            echo 'selected';
                                } ?>>CELEBRITY</option>
    </select>
    <input type="submit" value="Update">
  </form>

  <h3>News::newsList("<?php echo esc($listType); ?>")</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($newsList); ?></td></tr></tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
