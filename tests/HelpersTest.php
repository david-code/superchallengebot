<?php
use PHPUnit\Framework\TestCase;

require_once 'helpers.php';

class HelpersTest extends TestCase
{
    public function testMatchStringWithArrayLength1()
    {
        $pos = strposa("abcdefgsdf", ["bcd"]);
        $this->assertEquals(1, $pos);
    }

    public function testMatchStringWithArrayMultiple()
    {
        $pos = strposa("foobar", ["pok", "bar"]);
        $this->assertEquals(3, $pos);
    }

    public function testMatchStringWithArrayEmpty()
    {
        $pos = strposa("foobar", []);
        $this->assertFalse($pos);
    }

    public function testMatchStringWithArrayNoMatch()
    {
        $pos = strposa("turkey", ["chicken", "goose", "duck"]);
        $this->assertFalse($pos);
    }

    public function testMatchStringWithString()
    {
        $pos = strposa("antidisestablishmentarianism", "establish");
        $this->assertEquals(7, $pos);
    }

    public function testSanifyText()
    {
        $this->markTestSkipped("Not sure what this is supposed to do!");
    }

    public function testFindAll()
    {
        $indexes = strpos_recursive(
            'how much wood could a wood chuck chuck'
            . ' if a wood chuck could chuck wood',
            'wood'
        );
        $this->assertEquals([9, 22, 44, 67], $indexes);
    }

    public function testFindAllNoMatch()
    {
        $indexes = strpos_recursive('foobar', 'baz');
        $this->assertEquals([], $indexes);
    }

    public function testKeywordExistsAtPosition()
    {
        $exists = keywordExistsAtPosition(
            'What a lovely morning', 7, ['ravishing', 'lovely']
        );
        $this->assertTrue($exists);
    }

    public function testKeywordExistsAtPositionWrongPosition()
    {
        $exists = keywordExistsAtPosition(
            'What a lovely morning', 10, ['lovely', 'ravishing']
        );
        $this->assertFalse($exists);
    }

    public function testFindHashtagInString()
    {
        $pos = findHashtagInString('test', 'This is a #test 12345', ['test', 'practice']);
        $this->assertEquals(10, $pos);
    }

    public function testFindHashtagInStringNoResult()
    {
        $pos = findHashtagInString('test', 'This the #real deal, not a test!', ['test']);
        $this->assertFalse($pos);
    }

    public function testFindItemInStringSuccess()
    {
        $found = findItemInString('Eye of newt and toe of frog', ['frog']);
        $this->assertEquals('frog', $found);

    }

    public function testFindMinutesInStringNone()
    {
        $mins = findMinutesInString('There is no time in this string!');
        $this->assertEquals(0, $mins);
    }

    public function testFindMinutesInStringName()
    {
        $mins = findMinutesInString('I have studied for 4 hours and 26 mins');
        $this->assertEquals(266, $mins);
    }

    public function testFindTitleNotExisting()
    {
        $title = findTitleInString('我今天看过一本书和一本电影');
        $this->assertEquals("", $title);
    }

    public function testFindTitlesTwo()
    {
        $title = findTitleInString(
            'Today I read "A Tale of Two Cities" and "Oliver Twist"'
        );
        $this->assertEquals("A Tale of Two Cities", $title);
    }

    public function testfindAmountNotExisting()
    {
        $time = findAmountInString("Didn't do much today! Read no pages.",
                                   "book");
        $this->assertEquals($time, 0);
    }

    public function testFindAmountSeveral()
    {
        $time = findAmountInString(
            "I read 5 pages of my favourite book and watched " .
            "7 hours of a tv show", "book"
        );
        $this->assertEquals($time, 5);
    }

    public function testFindAmountFilm()
    {
        $time = findAmountInString(
            'I watched 2 hours of "Breaking Bad"',
            'film'
        );
        $this->assertEquals($time, 120);
    }

    public function testFindAmountNothin()
    {
        $time = findAmountInString("blassfasf", 'film');
        $this->assertEquals($time, 0);
        $time = findAmountInString('asdfasdfa', 'book');
        $this->assertEquals($time, 0);
    }
}

?>
