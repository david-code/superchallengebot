<?php
namespace SCBot\Preferences;

use DateTime;
use DateTimeZone;

require_once('database.php');

class Preferences
{
    // loaded from database
    public $BOOK_PAGES = 0;
    public $FILM_MINUTES = 0;

    public $CONSUMER_KEY = "";
    public $CONSUMER_SECRET_KEY = "";
    public $OAUTH_TOKEN = "";
    public $OAUTH_SECRET_TOKEN = "";

    // not loaded from database
    public $KEYWORDS = array("#film", "#listen", "#book", "#undo", "#read", "#audio", "#watch", "#listen", "#radio", "#movie");
    public $TARGET_BOOKS = 100;
    public $TARGET_FILMS = 100;

    public $STAR_VALUE = 25;
    public $EPOCH = null;

    public $START_DATE = "";
    public $END_DATE = "";


    public function __construct($startDate, $endDate, $bookPages,
                       $filmMinutes, $consumerKey,
                       $consumerSecretKey, $oauthToken,
                       $oauthSecretToken)
    {
        $this->START_DATE = $startDate;
        $this->END_DATE = $endDate;
        $this->BOOK_PAGES = $bookPages;
        $this->FILM_MINUTES = $filmMinutes;
        $this->CONSUMER_KEY = $consumerKey;
        $this->CONSUMER_SECRET_KEY = $consumerSecretKey;
        $this->OAUTH_TOKEN = $oauthToken;
        $this->OAUTH_SECRET_TOKEN = $oauthSecretToken;

        $this->EPOCH = new DateTime('2000-01-01', new DateTimeZone("UTC"));

        $this->TARGETS = array(
            'book' => $this->BOOK_PAGES,
            'film' => $this->FILM_MINUTES);

        $this->ACTIONS = array(
            'book' => 'inc_pagesread',
            'film' => 'inc_minuteswatched');

        $this->ENTRYCONTENT = array(
            'book' => 'PagesRead',
            'film' => 'MinutesWatched');

        $this->TAGS = array(
            'book' => array("#book","#read"),
            'film' => array("#film","#movie","#watch","#listen","#audio","#radio"));

        $this->CHALLENGECONTENT = array(
            'book' => 'Books',
            'film' => 'Films');

        $this->CONTENTTYPE = array(
            'pagesread' => 'book',
            'minuteswatched' => 'film');
    }

    function keywordExistsAtPosition($string, $position)
    {
        foreach($this->KEYWORDS as $keyword)
        {
            if(substr($string, $position, strlen($keyword)) == $keyword)
                return true;
        }
        return false;
    }

}

?>
