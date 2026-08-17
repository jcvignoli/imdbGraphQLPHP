<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Company;

$companyId = $_GET['companyId'] ?? '0017902'; // Default Warner Bros
$companyId = preg_replace('/[^0-9]/', '', $companyId);

$company = new Company();
$info = $company->companyInfo($companyId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Company Test</title>
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

  <h2 class="text-center">Company Info Test</h2>

  <form method="get" action="company.php" class="text-center mb-10">
    <label for="companyId">Change IMDb Company ID (co...): </label>
    <input type="text" name="companyId" id="companyId" value="<?php echo esc($companyId); ?>" size="15">
    <input type="submit" value="Load Company Info">
  </form>

  <h3>Company::companyInfo("co<?php echo esc($companyId); ?>")</h3>
  <table class="table">
    <thead><tr><th>Returned Data</th></tr></thead>
    <tbody><tr><td><?php echo renderValue($info); ?></td></tr></tbody>
  </table>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
