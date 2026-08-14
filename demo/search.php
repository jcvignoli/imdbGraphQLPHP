<?php
#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
# ------------------------------------------------------------------------- #
# Search for $name and display results                                      #
#############################################################################

require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

$searchtype = $_GET["searchtype"] ?? 'tt';

# If MID has been explicitly given, we don't need to search:
if (!empty($_GET["mid"]) && preg_match('/^(tt|nm|)([0-9]+)$/', $_GET["mid"], $matches)) {
  $target = !empty($matches[1]) ? $matches[1] : $searchtype;
  switch($target) {
    case "nm" : header("Location: person.php?mid=" . $matches[2]); break;
    default   : header("Location: movie.php?mid=" . $matches[2]); break;
  }
  return;
}

# If we have no MID and no NAME, go back to search page
if (empty($_GET["name"])) {
  header("Location: index.html");
  return;
}

# Still here? Then we need to search:
if ($searchtype === 'nm') {
  $headname = "Person";
  $search = new \Imdb\NameSearch();
  $results = $search->search($_GET["name"]);
} else {
  $headname = "Movie";
  $search = new \Imdb\TitleSearch();
  if ($searchtype === "episode") {
    $results = $search->search($_GET["name"], [\Imdb\TitleSearch::TV_EPISODE]);
  } else {
    $results = $search->search($_GET["name"]);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Performing search for "<?php echo esc($_GET["name"]); ?>" - IMDbPHP</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <h2 class="text-center">Search results for <span><?php echo esc($_GET["name"]); ?></span>:</h2>
    <table class="table">
      <tr><th><?php echo $headname; ?> Details</th><th>IMDb</th></tr>
      <?php foreach ($results as $res):
        if ($searchtype === 'nm'):
          if (is_array($res)) {
            $imdbid = $res['imdbid'] ?? $res['id'] ?? '';
            $name   = $res['name'] ?? '';
            $url    = $res['main_url'] ?? ('https://www.imdb.com/name/nm' . sprintf('%07d', $imdbid));
            $role   = $res['role'] ?? '';
            $mid    = $res['mid'] ?? '';
            $movie  = $res['moviename'] ?? '';
            $year   = $res['year'] ?? '';
            $hint   = !empty($movie) ? " (" . ($role ? "$role in " : "") . "<a href='movie.php?mid=$mid'>$movie</a>" . ($year ? " ($year)" : "") . ")" : '';
          } else {
            $imdbid  = $res->imdbid();
            $name    = $res->name();
            $url     = $res->main_url();
            $details = $res->getSearchDetails();
            $hint    = '';
            if (!empty($details)) {
              $hint = " (" . ($details["role"] ?? '') . " in <a href='movie.php?mid=" . ($details["mid"] ?? '') . "'>" . ($details["moviename"] ?? '') . "</a> (" . ($details["year"] ?? '') . "))";
            }
          }
          ?>
          <tr>
            <td><a href="person.php?mid=<?php echo $imdbid; ?>"><?php echo $name; ?></a><?php echo $hint; ?></td>
            <td><a href="<?php echo $url; ?>">IMDb</a></td>
          </tr>
        <?php else:
          if (is_array($res)) {
            $imdbid = $res['imdbid'] ?? $res['id'] ?? '';
            $title  = $res['title'] ?? '';
            $year   = $res['year'] ?? '';
            $type   = $res['movietype'] ?? $res['type'] ?? '';
            $url    = $res['main_url'] ?? ('https://www.imdb.com/title/tt' . sprintf('%07d', $imdbid));
          } else {
            $imdbid = $res->imdbid();
            $title  = $res->title();
            $year   = $res->year();
            $type   = $res->movietype();
            $url    = $res->main_url();
          }
          ?>
          <tr>
            <td><a href="movie.php?mid=<?php echo $imdbid; ?>"><?php echo $title; ?> (<?php echo $year; ?>) (<?php echo $type; ?>)</a></td>
            <td><a href="<?php echo $url; ?>">IMDb</a></td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </table>
    <p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>
