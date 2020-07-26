<?php

namespace SCBot\Update;

require_once('database.php');
require_once('preferences.php');
require_once('helpers.php');

class Updater
{
    protected $db;
    protected $prefs;
    protected $testing;
    protected $twit;

    public function __construct($db, $prefs,
                                $testing, $twit)
    {
        $this->db = $db;
        $this->prefs = $prefs;
        $this->testing = $testing;
        $this->twit = $twit;
    }

    /**
     * Run update process
     */
    public function update()
    {
        $lastupdate = $this->db->getPreference('last_update');
        if (!$testing && lastupdate > strtotime('1 minute ago'))
        {
            loginfo('update() called but not run');
            return;
        }
        // logic for new users
        $this->twit->updateTwitterFollowing();
        $newUsers = $this->twit->newUsers();
        $this->db->updateParticipants($newUsers);

        // logic for scores
        $success = $this->updateScores();

        $this->db->setPreference('last_update', time());
    }

    /**
     * Update the twitter feed with info
     */
    public function updateScores()
    {
        $lastreadid = $this->db->getPreference('last_twitter_id');
        $tweets = $this->twit->getUnprocessedTweets($lastreadid);
        if (count($tweets) === 0)
        {
            loginfo('No tweets to read!');
            return false;
        }

        foreach ($tweets as $tweet)
        {
            $lastreadid = processTweet($tweet);
        }

        $this->db->setPreference('last_twitter_id', $lastreadid);
        return true;
    }


    public function processTweet($tweet)
    {
        $contents = strtolower($tweet->text);

        if (($this->testing && !strpos($contents, "#test"))
            || (!$this->testing && strpos($contents, "#test")))
        {
            return $tweet->id_str;
        }
        loginfo("Processing tweet " . $tweet->id_str . " from user "
              . $tweet->user->screen_name. "\n" . $contents );
        $tweet->information = $this->getTweetInfo($tweet);

        $processed = false;
        $register = strpos($contents, "#register");
        if ($register)
        {
            $this->processRegistration($tweet);
            $processed = true;
        }

        $undo = strposa($contents, array('#undo', '#delete'));
        if (!$processed && $undo)
        {
            $this->processUndo($tweet);
            $processed = true;
        }

        if (!$processed && $this->findEntryId($tweet) < 0)
        {
            $this->replyEntryErrorTweet($tweet);
            $processed = true;
        }

        $giveup = strpos($contents, "#giveup");
        if (!$processed && $giveup)
        {
            $this->processGiveup($tweet);
            $processed = true;
        }

        if (!$processed && $tweet->information->contenttype)
        {
            $this->processContent($tweet);
            $processed = true;
        }

        if (!$processed)
        {
            loginfo("Error processing tweet: " . $tweet->id_str);
        }

        if (!$this->testing)
        {
            return $tweet->id_str;
        }

        return null;
    }

    public function getTweetInfo($tweet)
    {
        $contents = strtolower($tweet->text);
        $information = new stdClass();

        // first get the type
        $information->contenttype =
            (strposa($contents, $this->prefs->TAGS['book']) ? 'book' :
             (strposa($contents, $this->prefs->TAGS['film']) ? 'film' : ''));

        // then other properties
        $this->updateTweetInformation($tweet, $information);

        // done
        return $information;
    }

    public function updateTweetInformation($tweet, $information)
    {
        // information
        $contents = strtolower($tweet->text);

        // the language, if we have one
        $information->language = $this->findLanguage($tweet);

        // titles are always specified in quotes
        $information->title = findTitleInString($contents);

        // pull out numbers
        $information->amount = findAmountInString($contents, $information->contenttype);
    }

    public function processRegistration($tweet)
    {
        $language = $this->findLanguage($tweet);
        if(!$language)
        {
            // no language specified!
            // we need to have a language to sign up!
            $this->twit->replyTweet($tweet, "You can't sign up to a language challenge
            without a language! Specify one with a hashtag.");
        }
        // our variation must be correct!
        else
        {
            // add a new user!
            $this->db->insertParticipant($tweet->user->screen_name, $tweet->user->name, "twitter");
            $success = $this->db->insertEntry($tweet->user->screen_name, $language['Code']);

            // Reply to all specific registrations
            if(!$success) {
                $message = "You're already studying ".$language['Name']."!";
            } else {
                $message = "has registered for the Super Challenge in "
                          .$language['Name'].". Good luck!";
            }

            $this->twit->replyTweet($tweet, $message);
        }
    }

    function processUndo($tweet)
    {
        // the action code must not be 'del' (ie, we can't have
        // already undone this action.
        $targetaction = $this->db->getAction($tweet->in_reply_to_status_id_str);
        if(!$targetaction) {
            $this->twit->replyTweet($tweet,
                                    "Which tweet do you want to remove? "
                                    . "Reply to your own tweet that contains "
                                    . "the mistake to undo it.");
            return;
        } elseif(substr($targetaction['ActionCode'], 0, 3) == "del") {
            replyTweet($tweet, "You've already removed this tweet!");
            return;
        } elseif(substr($targetaction['ActionCode'], 0, 4) == "undo") {
            replyTweet($tweet, "You can't undo a #undo tweet!");
            return;
        }

        // update the existing action to be 'del_'
        $this->db->updateAction($tweet->in_reply_to_status_id_str, 'del');

        // if we succeeded, add a new action for the undo
        $this->db->insertActionRecord(
            $tweet->id_str, "undo",
            $this->db->getActionEntryId($tweet->in_reply_to_status_id_str),
            0, $tweet->in_reply_to_status_id_str);

        // and roll back the data
        $type = $preferences->CONTENTTYPE[
            substr($targetaction['ActionCode'], 4)
        ];
        $entrycontent = $preferences->ENTRYCONTENT[$type];
        $entryid = $targetaction['EntryId'];
        $targetactionamount = $targetaction['AmountData'];
        $this->db->incrementEntryRecord(
            $entryid, $entrycontent, -$targetactionamount);

        // tell the user what we did
        $this->twit->replyTweet(
            $tweet, "made a mistake and removed a tweet."
        );

    }

    function processEdit($tweet)
    {
        global $preferences;

        // the action code must not be 'del' or 'edt' (ie, we can't have
        // already undone this action.
        $targetaction = $this->db->getAction($tweet->in_reply_to_status_id_str);
        if(!$targetaction) {
            $this->twit->replyTweet($tweet, "Which tweet do you want to edit? Reply to your own tweet that contains the mistake to edit it.");
            return;
        } elseif(substr($targetaction['ActionCode'], 0, 3) == "del") {
            replyTweet($tweet, "You can't edit a tweet that's been undone!");
            return;
        } elseif(substr($targetaction['ActionCode'], 0, 3) == "edt") {
            replyTweet($tweet, "You've already edited this tweet! Reply to the new #edit tweet instead.");
            return;
        }

        // if there's no content type, we use the existing one and update the tweet information
        // so that stuff like the content amount, which relies on the content type, is populated
        $editaction = $targetaction;
        $editinformation = $tweet->information;
        if(!$editinformation->contenttype) {
            $actiontag = substr($targetaction['ActionCode'], 4);
            $editinformation->contenttype = $preferences->CONTENTTYPE[$actiontag];
            $this->updateTweetInformation($tweet, $editinformation);
        }

        // update things!
        $actiontaken = false;
        if($editinformation->title) {
            $editaction['TextData'] = $tweet->information->title;
            $actiontaken = true;
        }

        if($editinformation->amount) {
            $editaction['AmountData'] = $editinformation->amount;
            $actiontaken = true;
        }

        // an update tweet must actually update something
        if(!$actiontaken) {
            $this->twit->replyTweet(
                $tweet, "An edit tweet must update the "
                . "title or the amount."
            );
            return;
        }

        // update the existing action code to be edited
        $this->db->updateAction(
            $tweet->in_reply_to_status_id_str, 'edt'
        );

        // add a new action for the edit (preserving the old timestamp) and increment
        // the new total
        $this->db->insertActionRecord(
            $tweet->id_str, $editaction['ActionCode'],
            $editaction['EntryId'], $editaction['AmountData'],
            $editaction['TextData'], $editaction['Time']);
        $edittype = $this->prefs->CONTENTTYPE[substr($editaction['ActionCode'], 4)];
        $this->db->incrementEntryRecord(
            $editaction['EntryId'],
            $this->preferences->ENTRYCONTENT[$edittype],
            $editaction['AmountData']);

        // roll back the previous data
        $targettype = $this->$prefs->CONTENTTYPE[substr($targetaction['ActionCode'], 4)];
        $this->db->incrementEntryRecord(
            $targetaction['EntryId'],
            $this->prefs->ENTRYCONTENT[$targettype],
            -$targetaction['AmountData']);

        // tell the user what happened
        $this->twit->replyTweet(
            $tweet, "made a mistake and updated their tweet.");

    }

    function processGiveup($tweet)
    {
        $this->db->removeEntry($tweet->entryid);
        $languagename = $tweet->information->language['Name'];
        $this->twit->replyTweet(
            $tweet, "has stopped studying" .
            ($languagename ? " ".$languagename : "").
            " and withdrawn from the challenge. :(");
    }


    function processContent($tweet)
    {

        // get the type of content (book/movie/etc)
        $type = $tweet->information->contenttype;

        // see if we can find any other information
        $amount = $tweet->information->amount;
        $title = $tweet->information->title;

        // default our amount, if there's none specified
        if($amount == 0)
            $amount = $this->prefs->TARGETS[$type];

        // if ammount is still zero, something went wrong!
        if($amount == 0) {
            loginfo("something went wrong processing content : "
                    . $tweet->text);
            return;
        }

        // return something nice
        $replyoptions = array(
            'book' => "read $amount pages of ".($title ? $title : "a book"),
            'film' => "watched $amount minutes of ".($title ? $title : "a film"));

        $replystring = $replyoptions[$type].
                     ($tweet->information->language ?
                      " in ".$tweet->information->language['Name']
                      : "").".";

        // increment the content in the database
        $this->db->insertActionRecord($tweet->id_str, $preferences->ACTIONS[$type], $tweet->entryid, $amount, $title);
        $this->db->incrementEntryRecord($tweet->entryid, $preferences->ENTRYCONTENT[$type], $amount); // can be used to return the total amount

        // say something nice to the person
        $this->twit->replyTweet($tweet, $replystring);
    }

    // return the ID for the entry matching the tweet, and fill in the language structure if warranted
    function findEntryId($tweet)
    {
        // language is optional (but helps)
        $language = $tweet->information->language;
        $languagecode = $language ? $language['Code'] : "";


        // but we must have a single entry or we can't go further
        $entry = $this->db->getUniqueEntry(
            $tweet->user->screen_name, $languagecode
        );
        //echo "USER_SCREEN_NAME: ". $tweet->user->screen_name . " LANGCODE: ". $languagecode;
        if($entry < 0)
        {
            $tweet->error = $entry;
            return $entry;
        }

        $tweet->entryid = $entry['Id'];

        return $tweet->entryid;
    }

    function updateTwitterUsers()
    {
        // get the user data for a bunch of users
        $updateNames = $this->db->getUpdateNames(100);
        $users = $this->twit->getTwitterUsers($updateNames);
        // update each user's information
        foreach($users as $user)
        {
            $this->db->updateParticipant(
                $user->screen_name,
                $user->name,
                $user->location,
                $user->profile_image_url,
                $user->url,
                $user->description);
        }
    }

    function findLanguage($tweet)
    {
        return $this->db->findLanguageInString(
            sanifyText($tweet->text),
            $this->keywords);
    }
}

?>
