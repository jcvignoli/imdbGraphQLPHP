<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Trailers;

$trailers = new Trailers();

$recent = $trailers->recentVideo();
$trending = $trailers->trendingVideo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Trailers Test</title>
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

  <h2 class="text-center">Trailers Test Suite</h2>

  <h3>Trailers::recentVideo()</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($recent); ?></td></tr></tbody>
  </table>

  <h3>Trailers::trendingVideo()</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($trending); ?></td></tr></tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
