<?php
/**
 * StatusNet, the distributed open-source microblogging tool
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
 * @category  Plugin
 * @package   StatusNet
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

define('INSTALLDIR', realpath(dirname(__FILE__) . '/../../..'));

$shortoptions = 'n:';
$longoptions = array('nick=');

$helptext = <<<ENDOFHELP
USAGE: streamtest.php -n <username>

Attempts a User Stream connection to Twitter as the given user, dumping
data as it comes.

ENDOFHELP;

require_once INSTALLDIR.'/scripts/commandline.inc';
require_once dirname(dirname(__FILE__)) . '/jsonstreamreader.php';
require_once dirname(dirname(__FILE__)) . '/twitterstreamreader.php';

if (have_option('n')) {
    $nickname = get_option_value('n');
} else if (have_option('nick')) {
    $nickname = get_option_value('nickname');
} else {
    show_help($helptext);
    exit(0);
}

/**
 *
 * @param User $user 
 * @return TwitterOAuthClient
 */
function twitterAuthForUser(User $user)
{
    $flink = Foreign_link::getByUserID($user->id,
                                       TWITTER_SERVICE);
    if (!$flink) {
        throw new ServerException("No Twitter config for this user.");
    }

    $token = TwitterOAuthClient::unpackToken($flink->credentials);
    if (!$token) {
        throw new ServerException("No Twitter OAuth credentials for this user.");
    }

    return new TwitterOAuthClient($token->key, $token->secret);
}

function homeStreamForUser(User $user)
{
    $auth = twitterAuthForUser($user);
    return new TwitterUserStream($auth);
}

$user = User::staticGet('nickname', $nickname);
$stream = homeStreamForUser($user);
$stream->hookEvent('raw', function($data) {
    common_log(LOG_INFO, json_encode($data));
});
$stream->hookEvent('friends', function($data) {
    printf("Friend list: %s\n", implode(', ', $data));
});
$stream->hookEvent('favorite', function($data) {
    printf("%s favorited %s's notice: %s\n",
            $data['source']['screen_name'],
            $data['target']['screen_name'],
            $data['target_object']['text']);
});
$stream->hookEvent('unfavorite', function($data) {
    printf("%s unfavorited %s's notice: %s\n",
            $data['source']['screen_name'],
            $data['target']['screen_name'],
            $data['target_object']['text']);
});
$stream->hookEvent('follow', function($data) {
    printf("%s friended %s\n",
            $data['source']['screen_name'],
            $data['target']['screen_name']);
});
$stream->hookEvent('unfollow', function($data) {
    printf("%s unfriended %s\n",
            $data['source']['screen_name'],
            $data['target']['screen_name']);
});
$stream->hookEvent('delete', function($data) {
    printf("Deleted status notification: %s\n",
            $data['status']['id']);
});
$stream->hookEvent('scrub_geo', function($data) {
    printf("Req to scrub geo data for user id %s up to status ID %s\n",
            $data['user_id'],
            $data['up_to_status_id']);
});
$stream->hookEvent('status', function($data) {
    printf("Received status update from %s: %s\n",
            $data['user']['screen_name'],
            $data['text']);
});
$stream->hookEvent('direct_message', function($data) {
    printf("Direct message from %s to %s: %s\n",
            $data['sender']['screen_name'],
            $data['recipient']['screen_name'],
            $data['text']);
});

class TwitterManager extends IoManager
{
    function __construct(TwitterStreamReader $stream)
    {
        $this->stream = $stream;
    }

    function getSockets()
    {
        return $this->stream->getSockets();
    }

    function handleInput($data)
    {
        $this->stream->handleInput($data);
        return true;
    }

    function start()
    {
        $this->stream->connect();
        return true;
    }

    function finish()
    {
        $this->stream->close();
        return true;
    }

    public static function get()
    {
        throw new Exception('not a singleton');
    }
}

class TwitterStreamMaster extends IoMaster
{
    function __construct($id, $ioManager)
    {
        parent::__construct($id);
        $this->ioManager = $ioManager;
    }

    /**
     * Initialize IoManagers which are appropriate to this instance.
     */
    function initManagers()
    {
        $this->instantiate($this->ioManager);
    }
}

$master = new TwitterStreamMaster('TwitterStream', new TwitterManager($stream));
$master->init();
$master->service();
