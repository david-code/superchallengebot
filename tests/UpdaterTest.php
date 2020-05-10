<?php
use PHPUnit\Framework\TestCase;
use SCBot\Database\DatabaseQuery;
use SCBot\Update\Updater;
use SCBot\Twitter\Twitter;

require_once 'update.php';
require_once 'database.php';
require_once 'twitter.php';
require_once 'update.php';

class UpdaterTest extends TestCase {

    // mock database connection
    protected $db;
    // mock twitter API querier
    protected $twit;
    // default preferences
    protected $defaultPrefs;

    protected function setUp() : void
    {
        $this->db = $this->createMock(DatabaseQuery::class);
        $this->twit = $this->createMock(Twitter::class);
        $this->defaultsPrefs = new Preferences(new DateTime('2020-05-01'),
                                               new DateTime('2022-12-31'),
                                               50, 45, '', '', '', '');
    }

    /**
     * If there are no new tweets, we should not be processing
     * any tweets
     */
    public function testNoNewTweets()
    {

        $updater = new Updater($this->db, $this->defaultPrefs,
                               false, $this->twit);
        $this->db->expects($this->once())
             ->method('getPreference')
             ->with($this->equalTo('last_twitter_id'))
             ->willReturn('1');
        $this->twit->expects($this->once())
             ->method('getUnprocessedTweets')
             ->with($this->equalTo('1'))
             ->willReturn([]);
        $this->db->expects($this->never())
             ->method('setPreference');

        $this->assertSame($updater->updateScores(), false);
    }
}

?>
