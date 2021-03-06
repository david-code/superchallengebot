<?php

require_once('logging.php');

// strpos that takes an array of values to match against a string
// note the argument order (to match strpos)
function strposa($haystack, $needle) {
    if(!is_array($needle))
        $needle = array($needle);

    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what)) !== false)
            return $pos;
    }

    return false;
}

// returns a space-delimited string for processing by the twitterbot
function sanifyText($text) {
    // todo whitespace stripping removes commas "ostuh,otuh" and concats the words
    // fix this issue.
    return strtolower($text);
}

// finds *all* positions of one string inside another
function strpos_recursive($haystack, $needle, $offset = 0, &$results = array()) {
    $offset = strpos($haystack, $needle, $offset);
    if($offset === false) {
        return $results;
    } else {
        $results[] = $offset;
        return strpos_recursive($haystack, $needle, ($offset + 1), $results);
    }
}

function keywordExistsAtPosition($string, $position, $keywords)
{
    foreach($keywords as $keyword)
    {
        if(substr($string, $position, strlen($keyword)) == $keyword)
            return true;
    }
    return false;
}

// tag specified without hash
function findHashtagInString($hashtag, $string, $keywords, $default = false)
{
    // we find all matches against one hashtag
    $positions = null;
    strpos_recursive($string, "#".$hashtag,0,$positions);

    // and see if any are actually a tag and not just part of the
    // keywords (like "film")
    if($positions != null)
    foreach($positions as $position)
    {
        if($position !== false &&
           !keywordExistsAtPosition($string, $position, $keywords))
        {
            // return the all columns!
            return $position;
        }
    }

    return $default;
}

// Find the first item in the array that is present in the string
function findItemInString($string, $array, $default = "")
{
    // only runs on arrays
    if(!is_array($array))
        $array = array($array);

    // return the first item that matches
    foreach($array as $item)
        if(strpos($string, $item) !== false)
            return $item;

    // otherwise nothing
    return $default;
}

// find a time in a string with hours and minutes, return with minutes
function findMinutesInString($string)
{
    $minutes = findNumberInString($string, array("minutes", "minute", "mins", "min"));
    $minutes += findNumberInString($string, array("hours", "hour", "hrs", "hr")) * 60;
    return $minutes;
}


// given a keyword (or an arary of keywords), find the number that appears
// before it in the string
function findNumberInString($string, $keywords, $default = 0)
{
    // if the word "page(s)" is in the tweet
    $string = str_replace($keywords, ' xxxxx ', $string);
    $string = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $string);    // make alphanumeric
    $string = preg_replace('/\s\s+/', ' ', $string);            // strip whitespace
    $words = explode(' ', $string);
    $index = array_search('xxxxx', $words);

    // return the number beforehand, or the default
    if ($index === false || $index == 0) {
        return $default;
    } else {
        $number = $words[$index - 1];
        return is_numeric($number) ? $number : $default;
    }
    $number = ($index === false || $index == 0) ?
        $default :
        $words[$index - 1];

    return $number;
}

// find the first quote in the string
function findTitleInString($string)
{
    //forward slashes are the start and end delimeters
    $title = "";
    if(preg_match('/"([^"]+)"/', $string, $title))
        return $title[1];
    else
        return "";
}

function findAmountInString($string, $type)
{
    $amount = 0;

    if($type == 'book') {
        $amount = findNumberInString($string, array("pages", "page"));
    }
    else if($type == 'film') {
        $amount = findMinutesInString($string);
    }

    return $amount;
}

function loginfo($message) {
    global $logger;

    $logger->info($message);
}

?>
