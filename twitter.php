<?php
namespace SCBot\Twitter;

require_once 'helpers.php';
require_once 'oauth/twitteroauth.php';

function cmpTweetId($a, $b)
{
    if ($a->id == $b->id) {
        return 0;
    }
    return ($a->id < $b->id) ? -1 : 1;
}

class Twitter
{
    protected $twit;

    public function __construct($twit)
    {
        $this->twit = $twit;
    }


    public static function fromCreds($consumer_key,
                                     $consumer_secret_key,
                                     $oauth_key, $oauth_secret_key)
    {
        return new Twitter(
            new TwitterOAuth($consumer_key, $consumer_secret_key,
                             $oauth_key, $oauth_secret_key));
    }


    /**
     * Create a client from preferences
     */
    public static function fromPreferences($pref)
    {
        return self::fromCreds($pref->CONSUMER_KEY, $pref->CONSUMER_SECRET_KEY,
                               $pref->OAUTH_TOKEN, $pref->OAUTH_SECRET_TOKEN);
    }

    public function getUnprocessedTweets($lastid)
    {
        $args = Array();
        if ($lastreadid)
            $args['since_id'] = $lastreadid;
        $tweets = $this->twit->get('statuses/mentions_timeline',
                                   $args);

        uasort($tweets, 'cmpTweetId');
        return $tweets;
    }

    public function updateTwitterFollowing()
    {

        // First get a list of all followers
        $args = Array();
        $args['stringify_ids'] = "true";
        $followers = $this->twit->get('followers/ids', $args);

        // the smaller numbers are newer followers - so we can just split off the first
        // 100 and use them, hopefully no more than 100 people follow us per given
        // update cycle!
        $latestfollowerids = array_slice($followers->ids, 0, 100);

        // loop up friendships (max 100 items in a query)
        $args = Array();
        $args['stringify_ids'] = "true";
        $args['user_id'] = implode(",", $latestfollowerids);
        $friendships = $this->twit->get('friendships/lookup', $args);

        foreach($friendships as $friendship)
        {
            // we should follow them in return
            if(in_array('followed_by', $friendship->connections) && // if we're followed by them
               !in_array('following', $friendship->connections) && // but not following them ourselves
               !in_array('following_requested', $friendship->connections)) // (and we've not already tried)
            {
                // TODO : you know, we probably shouldn't keep pestering people if they
                // don't want to let us follow them. But on the other hand, there's no
                // point following the language bot and not letting it follow you (ie, it
                // won't see your messages).
                loginfo("Now following ".$friendship->screen_name);
                $this->twit->post('friendships/create', array('user_id' => $friendship->id_str));
            }
        }
    }

    public function getTwitterUsers($updateNames)
    {
        $args = array();
        $args['screen_name'] = implode(", ", $updateNames);
        return $this->twit->get('users/lookup', $args);

    }

    public function replyTweet($tweet, $message)
    {
        $message = "@".$tweet->user->screen_name." ".$message;
        $replytoid = $tweet->id_str;

        loginfo("Replied to ".$replytoid." with ".$message.$testing);
        if(!$testing)
            $twit->post('statuses/update',
                        array('status' => $message,
                              'in_reply_to_status_id' => $replytoid));
    }

    function replyEntryErrorTweet($tweet)
    {
        if($tweet->error == -1)
        {
            $this->replyTweet(
                $tweet,
                "You don't seem to be studying".
                ($tweet->information->language ? " "
                 . $tweet->information->language['Name']
                 : " that language").
                ". Register first using the #register tag!");
        }
        else if($tweet->error == -2)
        {
            $this->replyTweet($tweet, "You're studying several languages.
            Specify which one you mean by using a hashtag.");
        }
        else
            loginfo("Unrecognised entry error code "
                    . $tweet->error);
    }
}

?>
