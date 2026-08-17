<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Calendar;

$calendar = new Calendar();

$region = $_GET['region'] ?? 'US';
$type = $_GET['type'] ?? 'MOVIE';
$providerId = $_GET['providerId'] ?? 'amazonprime';

$comingSoon = $calendar->comingSoon($region, $type);
$comingSoonStreaming = $calendar->comingSoonStreaming($providerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Calendar Test</title>
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

  <h2 class="text-center">Calendar Test</h2>

  <form method="get" action="calendar.php" class="mb-10">
    <table>
      <tr><th colspan="2">Coming Soon Options</th></tr>
      <tr>
        <td class="text-right pl-10 pr-10" style="width: 25%;">Region:</td>
        <td><input type="text" name="region" value="<?php echo esc($region); ?>" size="10"></td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10">Type:</td>
        <td><input type="text" name="type" value="<?php echo esc($type); ?>" size="15"> (e.g. MOVIE, TV)</td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10">Streaming Provider ID:</td>
        <td><input type="text" name="providerId" value="<?php echo esc($providerId); ?>" size="20"> (e.g. amazonprime, netflix)</td>
      </tr>
      <tr>
        <td colspan="2" class="text-center"><input type="submit" value="Update Calendar Query"></td>
      </tr>
    </table>
  </form>

  <h3>Calendar::comingSoon("<?php echo esc($region); ?>", "<?php echo esc($type); ?>")</h3>
  <table class="table">
    <thead>
      <tr><th>Returned Data</th></tr>
    </thead>
    <tbody>
      <tr><td><?php echo renderValue($comingSoon); ?></td></tr>
    </tbody>
  </table>

  <h3>Calendar::comingSoonStreaming("<?php echo esc($providerId); ?>")</h3>
  <table class="table">
    <thead>
      <tr><th>Returned Data</th></tr>
    </thead>
    <tbody>
      <tr><td><?php echo renderValue($comingSoonStreaming); ?></td></tr>
    </tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
