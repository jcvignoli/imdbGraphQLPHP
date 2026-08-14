<?php
#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

require __DIR__ . "/../vendor/autoload.php";

if (isset ($_GET["mid"]) && preg_match('/^[0-9]+$/', $_GET["mid"])) {
  $config = new \Imdb\Config();
  $config->cacheUse = true;
  $config->cacheStore = true;
  $config->language = 'en-US,en';
  $logger = null; //new \Imdb\Logger();
  $movie = new \Imdb\Title($_GET["mid"], $config, $logger );

  $imdb_host = 'www.imdb.com';
  $mTitle = $movie->title();

  # Handle year strictly (string, int, or array)
  $mYearRaw = $movie->year();
  if (is_array($mYearRaw)) {
    $mYear = implode('–', array_filter($mYearRaw));
  } else {
    $mYear = $mYearRaw ? (string)$mYearRaw : '';
  }

  $mainUrl = 'https://' . $imdb_host . '/title/tt' . sprintf('%07d', $_GET["mid"]);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($mTitle . ($mYear !== '' ? ' (' . $mYear . ')' : '')); ?> - IMDbPHP</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php # Title & year ?>
    <h2 class="text-center"><?php echo htmlspecialchars($mTitle . ($mYear !== '' ? ' (' . $mYear . ')' : '')); ?></h2>
      <?php # Photo ?>
      <div class="photo mb-10 text-center">
        <?php
          $photo_url = $movie->photoLocalurl();
          if (!empty($photo_url)) {
            echo '<img src="' . htmlspecialchars($photo_url) . '" alt="Cover">';
          } else {
            echo "No photo available";
          }
        ?>
      </div>
      <table class="table">
        <tr>
          <th colspan="2" class="move-container">
            Movie Details
            <span class="move-right pr-10">Source: [<a href="<?php echo htmlspecialchars($mainUrl); ?>">IMDb</a>]</span>
          </th>
        </tr>

        <?php
        # AKAs
        $aka = $movie->alsoknow();
        if (!empty($aka) && is_array($aka)) {
        ?>
        <tr>
          <td><b>Also known as:</b></td>
          <td>
            <table>
              <tr>
                <th>Title</th>
                <th>Language</th>
                <th>Country</th>

              </tr>
              <?php foreach ($aka as $ak) { 
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($ak["title"]); ?></td>
                  <td><?php echo htmlspecialchars($ak["language"] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($ak["country"]); ?></td>

                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php 
        } 
        ?>

        <?php 
        # Movie Type
        $mType = $movie->movietype();
        if (!empty($mType)) {
        ?>
        <tr>
          <td class="mw-120"><b>Type:</b></td>
          <td><?php echo htmlspecialchars(is_array($mType) ? implode(', ', $mType) : $mType); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Keywords
        $keywords = $movie->keyword();
        if (!empty($keywords)) {
        ?>
        <tr>
          <td><b>Keywords:</b></td>
          <td><?php echo htmlspecialchars(is_array($keywords) ? implode(', ', $keywords) : $keywords); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Episode
        $episodes = $movie->episode();
        if (!empty($episodes)) {
        ?>
        <tr>
          <td><b>Episode:</b></td>
          <td><?php echo htmlspecialchars(is_array($episodes) ? implode(', ', $episodes) : (string)$episodes); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php # Year ?>
        <?php if ($mYear !== '') { ?>
        <tr>
          <td><b>Year:</b></td>
          <td><?php echo htmlspecialchars($mYear); ?></td>
        </tr>
        <?php } ?>

        <?php
        # Runtime
        $runtime = $movie->runtime();
        if (!empty($runtime)) {
          $rTime = is_array($runtime) ? ($runtime[0]['time'] ?? $runtime[0] ?? '') : $runtime;
        ?>
        <tr>
          <td><b>Runtime:</b></td>
          <td><?php echo htmlspecialchars((string)$rTime); ?> minutes</td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # MPAA
        $mpaa = $movie->mpaa();
        if (!empty($mpaa) && is_array($mpaa)) { ?>
          <tr>
            <td><b>MPAA:</b></td>
            <td>
              <table>
                <tr>
                  <th>Country</th>
                  <th>Rating</th>
                </tr>
                <?php foreach ($mpaa as $key => $ratingVal) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($ratingVal['country'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($ratingVal['rating'] ?? ''); ?></td>
                  </tr>
                <?php } ?>
              </table>
            </td>
          </tr>
        <?php
        }
        ?>

        <?php
        # Ratings
        $ratv = $movie->rating();
        if (!empty($ratv)) {
        ?>
        <tr>
          <td ><b>Rating:</b></td>
          <td><?php echo htmlspecialchars((string)$ratv); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Votes
        $votes = $movie->votes();
        if (!empty($votes)) {
        ?>
        <tr>
          <td><b>Votes:</b></td>
          <td><?php echo htmlspecialchars((string)$votes); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Languages
        $languages = $movie->language();
        if (!empty($languages)) {
        ?>
        <tr>
          <td><b>Languages:</b></td>
          <td><?php echo htmlspecialchars(is_array($languages) ? implode(', ', $languages) : $languages); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Country
        $country = $movie->country();
        if (!empty($country)) {
        ?>
        <tr>
          <td><b>Country:</b></td>
          <td><?php echo htmlspecialchars(is_array($country) ? implode(', ', $country) : $country); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Genre
        $genres = $movie->genre();
        if (count($genres)>0) {
        ?>
        <tr>
          <td><b>Genres:</b></td>
          <td><ul><?php
          foreach( $genres as $genre ) {
           echo '<li>' . htmlspecialchars($genre ["mainGenre"]) . '</li>';
           }
           ?></ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Sub Genre
        if (count($genres)>0) {
        ?>
        <tr>
          <td><b>Sub genres:</b></td>
          <td><ul><?php
          foreach( $genres as $genre ) {
          foreach( $genre["subGenre"] as $subgenre ){
           echo '<li>' . htmlspecialchars($subgenre) . '</li>';
           }
           }
           ?></ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Colors
        $cols = $movie->color();
        if (count($cols)>0) {
        ?>
        <tr>
          <td><b>Colors:</b></td>
          <td><ul><?php
          foreach( $cols as $col ) {
           echo '<li>' . htmlspecialchars($col['type']) . '</li>';
           }
           ?></ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Sound
        $sounds = $movie->sound();
        if (count($sounds)>0) {
        ?>
        <tr>
          <td><b>Sound:</b></td>
          <td><ul><?php
          foreach( $sounds as $sound ) {
           echo '<li>' . htmlspecialchars($sound['type']) . '</li>';
           }
           ?></ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Tagline
        $tagline = $movie->tagline();
        if (!empty($tagline)) {
        ?>
        <tr>
          <td><b>Tagline:</b></td>
          <td><?php echo htmlspecialchars(is_array($tagline) ? implode(', ', $tagline) : $tagline); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Staff Section Handler
        $render_staff = function($title, $staffList) {
          if (empty($staffList) || !is_array($staffList)) return;
        ?>
        <tr>
          <td><b><?php echo htmlspecialchars($title); ?>:</b></td>
          <td>
            <table>
              <tr>
                <th class="mw-200">Name</th>
                <th class="mw-200">Role</th>
              </tr>
              <?php foreach ($staffList as $person) { 
                if (is_array($person)) {
                  $pId   = $person['imdb'] ?? $person['id'] ?? $person['mid'] ?? $person['nm'] ?? '';
                  $pName = $person['name'] ?? $person['person']['name'] ?? '';
                  $pRole = $person['role'] ?? $person['character'] ?? $person['job'] ?? '';
                } elseif (is_object($person)) {
                  $pId   = method_exists($person, 'imdbid') ? $person->imdbid() : '';
                  $pName = method_exists($person, 'name') ? $person->name() : '';
                  $pRole = method_exists($person, 'role') ? $person->role() : '';
                } else {
                  $pId   = '';
                  $pName = (string)$person;
                  $pRole = '';
                }

                $pNameStr = is_array($pName) ? implode(', ', $pName) : (string)$pName;
                $pRoleStr = is_array($pRole) ? implode(', ', $pRole) : (string)$pRole;
              ?>
                <tr>
                  <td>
                    <?php if (!empty($pId)) { ?>
                      <a href="person.php?mid=<?php echo htmlspecialchars(preg_replace('/^nm/', '', (string)$pId)); ?>"><?php echo htmlspecialchars($pNameStr); ?></a>
                    <?php } else { ?>
                      <?php echo htmlspecialchars($pNameStr); ?>
                    <?php } ?>
                  </td>
                  <td><?php echo !empty($pRoleStr) ? htmlspecialchars($pRoleStr) : '&nbsp;'; ?></td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php 
        };

        # Directors, Writers, Producers, Music, Cast
        $render_staff("Director", $movie->director());
        $render_staff("Writing By", $movie->writer());
        $render_staff("Produced By", $movie->producer());
        $render_staff("Music", $movie->composer());
        $render_staff("Cast", $movie->cast());
        ?>

        <?php
        # Plot outline
        $plotoutline = $movie->plotoutline();
        if (!empty($plotoutline)) {
        ?>
        <tr>
          <td><b>Plot Outline:</b></td>
          <td><?php echo htmlspecialchars(is_array($plotoutline) ? implode(', ', $plotoutline) : $plotoutline); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Plot
        $plot = $movie->plot();
        if (!empty($plot) && is_array($plot)) {
        ?>
        <tr>
          <td><b>Plot:</b></td>
          <td><ul>
          <?php foreach($plot as $p) { ?>
            <li><?php echo htmlspecialchars(is_array($p) ? implode(', ', $p) : $p); ?></li>
          <?php } ?>
          </ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Taglines
        $taglines = $movie->tagline();
        if (!empty($taglines) && is_array($taglines)) {
        ?>
        <tr>
          <td><b>Taglines:</b></td>
          <td><ul>
          <?php foreach($taglines as $t) { ?>
            <li><?php echo htmlspecialchars(is_array($t) ? implode(', ', $t) : $t); ?></li>
          <?php } ?>
          </ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Episodes List
        $episodes = $movie->episode();
        if (!empty($episodes) && is_array($episodes)) {
        ?>
        <tr>
          <td><b>Episodes:</b></td>
          <td>
          <?php
          foreach ($episodes as $season => $ep) {
            if (is_array($ep)) {
              foreach ($ep as $episodedata) {
                if (!is_array($episodedata)) continue;
                $epTitle  = $episodedata['title'] ?? 'Episode';
                $epImdb   = preg_replace('/^tt/', '', $episodedata['imdbid'] ?? $episodedata['id'] ?? '');
                $epSeason = $episodedata['season'] ?? $season;
                $epNum    = $episodedata['episode'] ?? '';
                $epAir    = $episodedata['airdate'] ?? '';
                $epPlot   = $episodedata['plot'] ?? '';

                echo '<b>Season ' . htmlspecialchars((string)$epSeason) . ', Episode ' . htmlspecialchars((string)$epNum) . ': ';
                if (!empty($epImdb)) {
                  echo '<a href="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '?mid=' . htmlspecialchars((string)$epImdb) . '">' . htmlspecialchars((string)$epTitle) . '</a>';
                } else {
                  echo htmlspecialchars((string)$epTitle);
                }
                echo '</b> (<b>Original Air Date: ' . htmlspecialchars((string)$epAir) . '</b>)<br>' . htmlspecialchars((string)$epPlot) . '<br/><br/>' . "\n";
              }
            }
          }
          ?>
          </td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Locations
        $locs = $movie->location();
        if (count($locs)>0) {
        ?>
        <tr>
          <td><b>Filming Locations:</b></td>
          <td><ul><?php 
          foreach( $locs as $loc ) {
          
          echo '<li>' . htmlspecialchars($loc['real']) . '</li>';
          }?></ul></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Quotes
        $quotes = $movie->quote();
        if (isset($quotes[0]) && count($quotes[0])>0) {
          $qText = $quotes[0][0];
        ?>
        <tr>
          <td><b>Movie Quotes:</b></td>
          <td><?php echo $qText; ?></td>
        </tr>
        <?php 
        } else { ?>
        <tr>
          <td><b>Movie Quotes:</b></td>
          <td>No movie quotes</td>
        </tr>
        <?php 
        }
        ?>

        <?php
        # Trailer
        $trailers = $movie->trailer(true);
        if (count($trailers)>0) {
        ?>
        <tr>
          <td><b>Trailers:</b></td>
          <td>
          <?php
            foreach($trailers as $t) { ?>
                <a href="<?php echo htmlspecialchars($t['videoUrl']); ?>"><?php echo htmlspecialchars($t['name']); ?></a><br>
            <?php
            }
          ?>
          </td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Crazy Credits
        $crazy = $movie->crazyCredit();
        if (!empty($crazy) && is_array($crazy)) {
          $cc = count($crazy);
        ?>
        <tr>
          <td><b>Crazy Credits:</b></td>
          <td>We know about <?php echo $cc; ?> <i>Crazy Credits</i>. One of them reads:<br><?php echo htmlspecialchars(is_array($crazy[0]) ? implode(', ', $crazy[0]) : $crazy[0]); ?></td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Goofs
        $goofs = $movie->goof();
        if (count($goofs)>0) {
          $gc = count($goofs);
          $firstGoof = $goofs[ array_key_first($goofs) ][0] ?? [];
          $gContent = $firstGoof["content"];
        ?>
        <tr>
          <td><b>Goofs:</b></td>
          <td>
            We know about <?php echo $gc; ?> <i>Goofs</i>. Here comes one of them:<br>
            <b><?php echo htmlspecialchars($gContent); ?></b>
          </td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Trivia
        $get_trivia = $movie->trivia();
        $trivia = $get_trivia[ array_key_first($get_trivia) ];
        if (!empty($trivia) && is_array($trivia)) {
          $tc = count($trivia);
        ?>
        <tr>
          <td><b>Trivia:</b></td>
          <td>
            There are <?php echo $tc; ?> entries in the trivia list - like these:
            <ul>
              <?php
                for ($i = 0; $i < 5; ++$i) {
                  if (empty($trivia[$i])) break;
                  $t = $trivia[$i]['content'];
                  $t = preg_replace('/https\:\/\/' . str_replace(".", "\.", $imdb_host) . '\/name\/nm(\d{7,8})/', 'person.php?mid=\\1', $t);
                  $t = preg_replace('/https\:\/\/' . str_replace(".", "\.", $imdb_host) . '\/title\/tt(\d{7,8})/', 'movie.php?mid=\\1', $t);
              ?>
              <li><?php echo $t; ?></li>
              <?php } ?>
            </ul>
          </td>
        </tr>
        <?php 
        } 
        ?>

        <?php
        # Soundtracks
        $soundtracks = $movie->soundtrack();
        if (!empty($soundtracks) && is_array($soundtracks)) {
          $sc = count($soundtracks);
        ?>
        <tr>
          <td><b>Soundtracks:</b></td>
          <td>
            There are <?php echo $sc; ?> soundtracks listed - like these:<br>
            <table>
              <tr>
                <th class="mw-200">Soundtrack</th>
                <th class="mw-200">Credits</th>
              </tr>
              <?php foreach ($soundtracks as $soundtrack) {
                $stName = $soundtrack["soundtrack"];
                $credits = $soundtrack["credits"];
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($stName); ?></td>
                  <td><?php echo implode( ', ', $credits ); ?></td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php 
        } 
        ?>
      </table>
    <p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>
<?php } ?>
