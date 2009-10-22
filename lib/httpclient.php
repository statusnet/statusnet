<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Utility for doing HTTP-related things
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Action
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Useful structure for HTTP responses
 *
 * We make HTTP calls in several places, and we have several different
 * ways of doing them. This class hides the specifics of what underlying
 * library (curl or PHP-HTTP or whatever) that's used.
 *
 * @category HTTP
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class HTTPResponse
{
    public $code = null;
    public $headers = array();
    public $body = null;
}

/**
 * Utility class for doing HTTP client stuff
 *
 * We make HTTP calls in several places, and we have several different
 * ways of doing them. This class hides the specifics of what underlying
 * library (curl or PHP-HTTP or whatever) that's used.
 *
 * @category HTTP
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class HTTPClient
{
    static $_client = null;

    static function start()
    {
        if (!is_null(self::$_client)) {
            return self::$_client;
        }

        $type = common_config('http', 'client');

        switch ($type) {
         case 'curl':
            self::$_client = new CurlClient();
            break;
         default:
            throw new Exception("Unknown HTTP client type '$type'");
            break;
        }

        return self::$_client;
    }

    function head($url, $headers)
    {
        throw new Exception("HEAD method unimplemented");
    }

    function get($url, $headers)
    {
        throw new Exception("GET method unimplemented");
    }

    function post($url, $headers, $body)
    {
        throw new Exception("POST method unimplemented");
    }

    function put($url, $headers, $body)
    {
        throw new Exception("PUT method unimplemented");
    }

    function delete($url, $headers)
    {
        throw new Exception("DELETE method unimplemented");
    }

    function userAgent()
    {
        return "StatusNet/".STATUSNET_VERSION." (".STATUSNET_CODENAME.")";
    }
}
