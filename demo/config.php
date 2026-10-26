<?php
require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cacheUse = isset($_POST['cacheUse']) && ($_POST['cacheUse'] === '1' || $_POST['cacheUse'] === 'true' || $_POST['cacheUse'] === 'on');
    $cacheStore = isset($_POST['cacheStore']) && ($_POST['cacheStore'] === '1' || $_POST['cacheStore'] === 'true' || $_POST['cacheStore'] === 'on');
    $language = $_POST['language'] ?? 'en-US';
    $apiUrl = trim($_POST['apiUrl'] ?? '');
    if ($apiUrl === '') {
        $apiUrl = 'https://api.graphql.imdb.com/';
    }
    $logger = $_POST['logger'] ?? 'null';

    $configData = [
        'cacheUse' => $cacheUse,
        'cacheStore' => $cacheStore,
        'language' => $language,
        'apiUrl' => $apiUrl,
        'logger' => $logger,
    ];

    $filePath = getDemoConfigFile();
    $bytes = file_put_contents($filePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($bytes !== false) {
        $message = 'Configuration saved to <code>config-temp.json</code> successfully.';
    } else {
        $message = 'Error: Failed to save configuration to <code>config-temp.json</code>.';
        $messageType = 'error';
    }
}

$currentData = getDemoConfigData();

$cacheUseVal = isset($currentData['cacheUse']) ? (bool)$currentData['cacheUse'] : false;
$cacheStoreVal = isset($currentData['cacheStore']) ? (bool)$currentData['cacheStore'] : false;
$languageVal = $currentData['language'] ?? 'en-US';
$apiUrlVal = $currentData['apiUrl'] ?? 'https://api.graphql.imdb.com/';
$loggerVal = $currentData['logger'] ?? 'null';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>IMDbPHP - Configuration Setup</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>
  <div class="nav-bar">
    <a href="index.html">Home</a> |
    <a href="search.php">Search</a> |
    <a href="calendar.php">Calendar</a> |
    <a href="chart.php">Charts</a> |
    <a href="trailers.php">Trailers</a> |
    <a href="news.php">News</a> |
    <a href="company.php">Company</a> |
    <a href="cache.php">Cache</a> |
    <a href="config.php">Config</a>
  </div>

  <h2 class="text-center">Demo Configuration Setup</h2>

  <?php if (!empty($message)) : ?>
    <div class="alert alert-<?php echo esc($messageType); ?>">
        <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="config.php" class="mb-10">
    <table style="max-width: 800px;">
      <tr><th colspan="2">Demo Config Options (Saved to demo/config-temp.json)</th></tr>
      <tr>
        <td class="text-right pl-10 pr-10" style="width: 35%;"><strong>$cacheUse:</strong></td>
        <td>
          <label>
            <input type="checkbox" name="cacheUse" value="1" <?php echo $cacheUseVal ? 'checked' : ''; ?>>
            Enable Cache Use
          </label>
        </td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10"><strong>$cacheStore:</strong></td>
        <td>
          <label>
            <input type="checkbox" name="cacheStore" value="1" <?php echo $cacheStoreVal ? 'checked' : ''; ?>>
            Enable Cache Store
          </label>
        </td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10"><strong>$language:</strong></td>
        <td>
          <select name="language">
            <option value="en-US" <?php echo $languageVal === 'en-US' ? 'selected' : ''; ?>>en-US</option>
            <option value="de-DE" <?php echo $languageVal === 'de-DE' ? 'selected' : ''; ?>>de-DE</option>
            <option value="fr-FR" <?php echo $languageVal === 'fr-FR' ? 'selected' : ''; ?>>fr-FR</option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10"><strong>$apiUrl:</strong></td>
        <td>
          <input type="text" name="apiUrl" value="<?php echo esc($apiUrlVal); ?>" size="50">
        </td>
      </tr>
      <tr>
        <td class="text-right pl-10 pr-10"><strong>$logger:</strong></td>
        <td>
          <select name="logger">
            <option value="null" <?php echo ($loggerVal === 'null' || $loggerVal === null) ? 'selected' : ''; ?>>null</option>
            <option value="new \Imdb\Logger()" <?php echo ($loggerVal === 'new \Imdb\Logger()' || $loggerVal === 'logger') ? 'selected' : ''; ?>>new \Imdb\Logger()</option>
          </select>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="text-center p-10">
          <input type="submit" value="Save Configuration">
        </td>
      </tr>
    </table>
  </form>

  <p class="text-center"><a href="index.html">Go back to home</a></p>
</body>
</html>
