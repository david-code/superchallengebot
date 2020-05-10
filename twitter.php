<?php
namespace SCBot\Twitter;

require_once 'oauth/twitteroauth.php';

class Twitter
{
    protected $twit;

    public function __construct($twit)
    {
        $this->twit = $twit;
    }

    public static function fromCreds($consumer_key, $consumer_secret_key,
                                     $oauth_key, $oauth_secret_key)
    {
        return new Twitter(new TwitterOAuth($consumer_key, $consumer_secret_key,
                                            $oauth_key, $oauth_secret_key));
    }

    public function getUnprocessedTweets($lastid)
    {
        $args = Array();
        if ($lastreadid)
            $args['since_id'] = $lastreadid;
        $tweets = $this->twit->get('statuses/mentions_timeline', $args);

        uasort($tweets, 'cmpTweetId');
        return $tweets;
    }

    public function updateTwitterFollowing()
    {

    }

    public function updateTwitterUsers()
    {

    }
}

?>
