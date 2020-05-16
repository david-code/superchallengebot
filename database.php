<?php
namespace SCBot\Database;
include_once dirname(__FILE__)."/helpers.php";
//include_once dirname(__FILE__)."/config-files/configuration.php";

require_once("configuration.php");

// Do we use the testing database or the release one?
//$link = login(DB_NAME.$testing);

class DatabaseQuery {

    private $conn;

    function __construct($conn) {
        $this->conn = $conn;
    }

    public static function fromConfig($config)
    {
        $mysqli = new mysqli($config->dbHost, $config->dbUser,
                             $config->dbPassword, $config->dbName);
        if ($mysqli->connect_errno) {
            throw new Exception($mysqli->connect_error);
        }
        return new DatabaseQuery($mysqli);
    }


    public function getPreferences()
    {
        $data = $this->conn->query("SELECT * FROM Preferences");
        if (!$data) {
            throw new Exception("Failed to retrieve preferences: " . $this->conn->error());
        }

        $prefs = Array();
        while($info = $data->fetch_assoc())
            $prefs[$info['Name']] = $info['Value'];

        return new Preferences($prefs['StartDate'],
                               $prefs['EndDate'],
                               $prefs['book_pages'],
                               $prefs['film_minutes'],
                               $prefs['consumer_key'],
                               $prefs['consumer_secret_key'],
                               $prefs['oauth_token'],
                               $prefs['oauth_secret_token']);;
    }

    public function getPreference()
    {
        $data = $this->conn->query("SELECT Value FROM Preferences " .
                                   "WHERE Name = '".$name."'");
        if (!$data) {
            throw new Exception("Error retrieving preference: " . $this->conn->error());
        }

        $info = $data->fetch_assoc();
        return $info['Value'];
    }

    public function setPreference($name, $value)
    {
        $data = $this->conn->query("INSERT INTO Preferences (Name, Value)
        VALUES ('".$name."', '".$value."')
        ON DUPLICATE KEY UPDATE Value = '".$value."'");
        if (!$data) {
            throw new Exception("Error settings preferences: " . $this->conn->error());
        }
    }

    public function newUsers()
    {

    }

    public function findLanguageInString($string)
    {
        $columnnames = array("Code", "Name");
        $data = $this->conn->query(
            "SELECT ".implode(", ", $columnnames)
          . " FROM Language"
        );
        if (!$data)
        {
            throw new Exception("Error querying language: "
                              . $this->conn->error());
        }

        // check against all languages in the database
        while($info = $this->conn->fetch_assoc($data))
        {
            // check against all specified columns
            foreach($columnnames as $columnname)
            {
                $result = findHashtagInString(
                    strtolower($info[$columnname]), $string);
                if($result !== false)
                    return $info;
            }
        }

        return null;
    }

    public function insertParticipant($username, $displayname,
                                      $feedcode = "none",
                                      $feeddata = "")
    {
        // insert or update
        $data = $this->conn->query(
            "INSERT INTO Participants (UserName, DisplayName, FeedData)"
          . "VALUES ('".$this->safe($username).
            "', '".$this->safe($displayname).
            "', '".$this->safe($feeddata)."')
                ON DUPLICATE KEY UPDATE UserName=UserName");
        if (!$data)
        {
            throw new Exception("Failure inserting participant "
                              . $username . ": "
                              . $this->conn->error());
        }
    }


    public function insertEntry($username, $languagecode)
    {

        // Check for double entries
        $query = "SELECT UserName, LanguageCode FROM Entries "
               . "WHERE UserName='". $this->safe($username)
              .  "' AND LanguageCode='".safe($languagecode)."'";
        $result = $this->db->query($link, $query);

        if (!$result)
        {
            throw new Exception("Failure inserting entry " .
                                $username . "," . $languagecode
                                . "): " . $this->conn->error());
        }

        if ($result->num_rows)
        {
            return false;
        }
        else
        {
            // Insert a new entry
            $query = $this->conn->query("INSERT INTO Entries "
                                      . "(UserName, LanguageCode)"
                                      . " VALUES ('"
                                        . $this->safe($username)
                                      . "', '"
                                      . $this->safe($languagecode)
                                        . "')");
            if (!$query)
            {
                throw new Exception("Failure inserting entry " .
                                    $username . "," . $languagecode .
                                    "): " . $this->conn->error());
            }
            return true;

        }
    }


    function getAction($actionid)
    {
        $data = $this->conn->query("SELECT * FROM Actions "
                                   . " WHERE Id = '$actionid'");
        if (!$data) {
            throw new Exception("Failure getting action "
                                . $actionid . ": "
                                . $this->conn->error());
        }

        return $data->fetch_assoc();
    }

    function insertActionRecord($actionid, $actioncode, $entryid,
                                $amount, $data = "", $time = "NOW()")
    {
        if ($time != "NOW()")
        {
            $time = "'$time'";
        }
        $data = $this->conn->query("INSERT INTO Actions "
                                   . "(Id, EntryId, ActionCode, "
                                   . "Time, AmountData, TextData) "
                                   . "VALUES ('$actionid', $entryid, "
                                   . "'$actioncode', $time, "
                                   . $this->safe($amount).", '"
                                   . $this->safe($data)."')");
        if (!$data) {
            throw new Exception(
                "Error inserting action record: $actionid"
                . $this->conn->error());
        }
    }

    // increments the entry record and returns the new total
    function incrementEntryRecord($id, $fieldname, $value)
    {
        // update
        $data = $this->conn->query(
            "UPDATE Entries "
            . "SET ".$fieldname."=".$fieldname."+".$value
            . " WHERE Id=".$id);
        if (!$data)
        {
            throw new Exception(
                "Error updating entry while incrementing $id: "
                . $this->conn->error());
        }

        // return the new value
        $result = $this->db->conn(
            "SELECT ".$fieldname.
            " FROM Entries WHERE Id=".$id);
        if (!$result) {
            throw new Exception(
                "Error accessing updated entry while incrementing $id: "
                . $this->conn->error());
        }

        $info = $result->fetch_assoc();
        return $info[$fieldname];
    }

    function removeEntry($id)
    {
        // Delete entry
        $data = $this->conn->query(
            "DELETE FROM Entries WHERE Id=$id"
        );
        if (!$data)
        {
            throw new Exception(
                "Error deleting entry $id: "
                . $this->conn->error());
        }
    }

    function getUpdateNames($count = 100)
    {
        // where did we start off?
        $lastindex = $this->getPreference("last_userupdate_index");

        // return those rows
        $namesresult = $this->conn->query(
            "SELECT UserName FROM Participants LIMIT "
            .$lastindex.", ".$count);
        if (!$namesresult)
        {
            throw new Exception(
                "Error getting last user update index: "
                . $this->conn->error()
            );
        }

        $namearray = array();
        while($namerow = $this->conn->fetch_assoc($namesresult))
            $namearray[] = $namerow['UserName'];

        // how many rows, if we need to wrap
        $countresult = $this->conn->query(
            "SELECT COUNT(*) FROM Participants");

        if (!$countresult)
        {
            throw new Exception(
                "Error counting participants"
            );
        }

        $totalcount = $this->conn->fetch_array($countresult);
        $lastindex += $count;
        if($lastindex >= $totalcount[0])
            $lastindex = 0;

        // Save the last updated index (wrapping if necessary)
        $this->setPreference("last_userupdate_index", $latindex);

        return $namearray;
    }

    function updateAction($actionid, $newprefix)
    {
        // we must at least have something!
        if(!$actionid)
            return false;

        $result = $this->conn->query(
            "UPDATE Actions "
            . "SET ActionCode=CONCAT('$newprefix"."_', SUBSTR(ActionCode, 5))"
            . "WHERE id='$actionid'");

        if (!$result) {
            throw new Exception(
                "Error updating action $actionid with $newprefix: "
                . $this->conn->error());
        }

        // we should always affect 1 row
        return ($result->affected_rows($link) == 1);
    }

    function getActionEntryId($actionid) {
        $data = $this->conn->query(
            "SELECT EntryId FROM Actions "
            . "WHERE Id = '$actionid'");
        if (!$data)
        {
            throw new Exception(
                "Failing getting action entry id for $actionid: "
                . $this->conn->error());
        }

        $info = $data->fetch_array();
        return $info['EntryId'];
    }

    function getUniqueEntry($username, $languagecode = "")
    {
        // only filter by language if one is provided
        $result = $this->conn->query(
            "SELECT Id FROM Entries "
            . "WHERE UserName = '".safe($username)."' ".
            ($languagecode == "" ? "" :
             "AND LanguageCode = '".safe($languagecode)."'"));
        if (!$result)
        {
            throw new Exception(
                "Failed to get unique entry for $username "
                . $this->conn->error()
            );
        }

        // no data, or too much data
        if($this->conn->num_rows($result) < 1)
            return -1;
        else if($this->conn->num_rows($result) > 1)
            return -2;

        // otherwise, the id as promised
        $info = $result->fetch_array();
        return $info;
    }

    function updateEntryBadges($id, $longestsprint, $longeststreak, $currentstreak)
    {
        // update
        $data = $this->db->query(
            "UPDATE Entries SET LongestSprint='$longestsprint', "
            . "LongestStreak='$longeststreak', "
            . "CurrentStreak='$currentstreak' "
            . "WHERE Id=$id");
        if (!$data)
        {
            throw new Exception(
                "Error updating entry badges for $id: "
                . $this->conn->error()
            );
        }
    }

    function callStoredProcedure($procedure)
    {
        $resultset = $this->conn->multi_query("CALL ".$procedure);
        if (!$resultset)
        {
            throw new Exception(
                "Error calling stored procedure $procedure: "
                . $this->conn->error()
            );
        }
        $data = $resultset->store_result($link);

        // clear remaining sets in the resultset before returning
        while ($this->conn->more_results())
        {
            $this->conn->next_result($link);
        }
        return $data;
    }

    function updateParticipant($username, $displayname, $location,
                               $imageurl, $websiteurl, $about)
    {
       $result = $this->conn->query("UPDATE Participants
        SET DisplayName='".safe($displayname)."',
            Location='".safe($location)."',
            ImageUrl='".safe($imageurl)."',
            WebsiteUrl='".safe($websiteurl)."',
            About='".safe($about)."'
        WHERE UserName='".$username."'");

       if (!$result)
       {
           throw new Exception(
               "Error updating participant $username: "
               . $this->conn->error()
           );
       }
    }

    function safe($string)
    {
        return $this->conn->real_escape_string($link, strip_tags($string));
    }


}

?>
