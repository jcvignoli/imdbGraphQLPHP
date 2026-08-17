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
# Show what we have in the Cache                                            #
#############################################################################

require __DIR__ . "/../vendor/autoload.php";
require "inc.php";

use Imdb\Title;
use Imdb\Name;
use Imdb\Config;

$config = new Config();
$config->language = 'en-US,en';
$config->cacheUse = true;
$config->cacheStore = true;

$results = [];
$seenIds = [];

if (is_dir($config->cacheDir)) {
    // Traverse cache directory recursively
    $dirIterator = new RecursiveDirectoryIterator($config->cacheDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator);

    foreach ($iterator as $fileInfo) {
        $filename = $fileInfo->getFilename();

        // Match Title IDs (tt followed by digits)
        if (preg_match('~tt(\d+)~i', $filename, $match)) {
            $id = $match[1];
            if (!isset($seenIds['tt_' . $id])) {
                $seenIds['tt_' . $id] = true;
                $results[] = new Title($id);
            }
        // Match Person IDs (nm followed by digits)
        } elseif (preg_match('~nm(\d+)~i', $filename, $match)) {
            $id = $match[1];
            if (!isset($seenIds['nm_' . $id])) {
                $seenIds['nm_' . $id] = true;
                $results[] = new Name($id);
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>IMDbPHP Cache Contents</title>
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
      <a href="company.php">Company</a> |
      <a href="cache.php">Cache</a>
    </div>

    <?php if (empty($results)) : ?>
      <h2 class="text-center">Nothing in cache</h2>
    <?php else : ?>
      <h2 class="text-center">Cache Contents</h2>
      <table class="table">
        <tr>
          <th>Name / Title</th>
          <th>Type</th>
          <th>View Demo</th>
        </tr>
        <?php foreach ($results as $res) : ?>
            <?php if ($res instanceof Title) : ?>
            <tr>
              <td><?php echo esc($res->title()) ?></td>
              <td><?php echo esc($res->movietype()) ?></td>
              <td class="text-center">
                <a href="movie.php?mid=<?php echo esc($res->imdbid()) ?>">View Title Demo</a>
              </td>
            </tr>
            <?php elseif ($res instanceof Name) : ?>
            <tr>
              <td><?php echo esc($res->name()) ?></td>
              <td>Person</td>
              <td class="text-center">
                <a href="person.php?mid=<?php echo esc($res->imdbid()) ?>">View Person Demo</a>
              </td>
            </tr>
            <?php endif; ?>
        <?php endforeach ?>
      </table>
    <?php endif ?>
    <p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>

