<?php

// Load preferences from the database, assuming they're not going to change over
// the course of our session.
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


    function __construct()
    {
        $preferences = getPreferences();
        date_default_timezone_set("utc");
        // $this->EPOCH = new DateTime("now");
        //$this->EPOCH = new DateTime::createFromFormat('d/m/Y','01/01/2017');
        $this->EPOCH = new DateTime('2000-01-01');

        $this->START_DATE = $preferences['StartDate'];
        $this->END_DATE = $preferences['EndDate'];
        $this->BOOK_PAGES = $preferences['book_pages'];
        $this->FILM_MINUTES = $preferences['film_minutes'];

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

        $this->CONSUMER_KEY = $preferences['consumer_key'];
        $this->CONSUMER_SECRET_KEY = $preferences['consumer_secret_key'];
        $this->OAUTH_TOKEN = $preferences['oauth_token'];
        $this->OAUTH_SECRET_TOKEN = $preferences['oauth_secret_token'];
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

$preferences = new Preferences();

?>
