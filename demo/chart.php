<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Chart;

$chart = new Chart();

$listType = $_GET['listType'] ?? 'TOP_250';
$popularType = $_GET['popularType'] ?? 'MOST_POPULAR_MOVIES';

$top250Title = $chart->top250Title($listType);
$top250Name = $chart->top250Name();
$mostPopularName = $chart->mostPopularName();
$mostPopularTitle = $chart->mostPopularTitle($popularType);
$topBoxOffice = $chart->topBoxOffice();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Chart Test</title>
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

  <h2 class="text-center">Charts Test Suite</h2>

  <h3>Chart::top250Title("<?php echo esc($listType); ?>")</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($top250Title); ?></td></tr></tbody>
  </table>

  <h3>Chart::top250Name()</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($top250Name); ?></td></tr></tbody>
  </table>

  <h3>Chart::mostPopularName()</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($mostPopularName); ?></td></tr></tbody>
  </table>

  <h3>Chart::mostPopularTitle("<?php echo esc($popularType); ?>")</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($mostPopularTitle); ?></td></tr></tbody>
  </table>

  <h3>Chart::topBoxOffice()</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($topBoxOffice); ?></td></tr></tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
