<?php

#############################################################################
# IMDBPHP6                             (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# written extended & maintained by Ed                                       #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Configuration class for imdbphp
 * You should override the settings in here by creating an ini file in the conf folder.
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2008 by Itzchak Rehberg and IzzySoft
 */
class Config
{
    /**
     * Set the language Imdb will use for titles, and some other localised data (e.g. tv episode air dates)
     * Any valid language code can be used here (e.g. en-US, de, pt-BR).
     * If this option is specified, a Accept-Language header with this value
     * will be included in requests to IMDb.
     * @var string
     */
    public $language = "";

    /**
     * IMDB domain to use.
     * @var string imdbsite
     */
    public $imdbsite = "www.imdb.com";

    /**
     * Where to store images retrieved from the IMDB site by the method photo_localurl().
     * This needs to be under documentroot to be able to display them on your pages.
     * @var string
     */
    public $photodir = './images/';

    /**
     * URL corresponding to photodir, i.e. the URL to the images, i.e. start at
     * your servers DOCUMENT_ROOT when specifying absolute path
     * @var string
     */
    public $photoroot = './images/';

    /**
     * Where the local IMDB images reside (look for the "showtimes/" directory)
     * This should be either a relative, an absolute, or an URL including the
     * protocol (e.g. when a different server shall deliver them)
     * @var string
     */
    public $imdb_img_url = './imgs/';

    /**
     * Enable debug mode?
     * @var boolean
     */
    public $debug = false;

    /**
     * Throw exceptions when a request to fetch some content fails?
     * @var boolean
     */
    public $throwHttpExceptions = true;

    #--------------------------------------------------=[ TWEAKING OPTIONS ]=--

    /**
     * Enable HTTP-Proxy support
     * @var bool
     */
    public $use_proxy = false;

    /**
     * Set originating IP address of a client connecting to a web server through an HTTP proxy or a load balancer.
     * Useful with language for times when Imdb uses your ip address geo-location before Accept-Language header.
     * If this option is specified, a X-Forwarded-For header with this value will be included in requests to IMDb.
     * @var string
     */
    public $ip_address = '';

    /**
     * Set hostname of HTTP-Proxy
     * @var string
     */
    public $proxy_host = null;

    /**
     * Set port on which HTTP-Proxy is listening
     * @var int
     */
    public $proxy_port = null;

    /**
     * Set username for authentication against HTTP-Proxy, if the proxy requires login.
     * Only basic authentication is supported.
     * Otherwise leave at default value
     * @var string
     */
    public $proxy_user = null;

    /**
     * Set password for authentication against HTTP-Proxy, if the proxy requires login.
     * Otherwise leave at default value
     * @var string
     */
    public $proxy_pw = '';

    /**
     * Set the default user agent (if none is detected)
     * @var string
     */
    public $default_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0';

    /**
     * Enforce the use of a special user agent
     * @var string
     */
    public $force_agent = '';

    /**
     * Constructor
     * @param string $iniFile *optional* Path to a config file containing any config overrides
     */
    public function __construct($iniFile = null)
    {

        if ($iniFile) {
            $ini_files = array($iniFile);
        } else {
            $ini_files = glob(dirname(__FILE__) . '/../../conf/*.ini');
        }

        if (is_array($ini_files)) {
            foreach ($ini_files as $file) {
                $ini = parse_ini_file($file);
                foreach ($ini as $var => $val) {
                    $this->$var = $val;
                }
            }
        }
    }
}
