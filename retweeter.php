<?php
/*
 ======================================================================
 Retweeter 1.1 - Creating Twitter Groups
 
 Scans the tweets of those followed by a given username. 
 When it finds a hashtag (#username) in those tweets, adds them to 
  a database and retweets them, prefixed by the tweeters username. 
 This enables all who follow the account to recieve the tagged
  posts of all others who follow the account.  
 
 Assumes it is being run from a crontab entry. 

*/


// Set username - which is also the hashtag retweeter will look for
$username = 'magadantraffic';
$hashtag = 'm49';

// Setup database connection
$dbserver = '';
$dbuser = '';
$dbpass = '';
$dbname = '';

// we'll need some OAuth stuff here
// register your retweeter at http://dev.twitter.com/apps/new
$consumer_key = '';  
$consumer_request = '';
  
// then click on "my token" on the resulting page and get these (make sure 
// you are logged in AS THE USERNAME you intend to use, as these keys are 
// specific to the user:  
$retweeter_oauth_token = '';
$retweeter_oauth_secret = '';

// To use the old format rather than the new retweet API, change this to true  
define('USE_OLD_FORMAT',false);   
  
// most users should not have to config beyond here
require_once('twitteroauth.php');
  
$db_handle = mysql_connect($dbserver,$dbuser,$dbpass) or die('Could not connect: ' . mysql_error());
mysql_select_db($dbname) or die('Could not select db');

// get the md5 hash from db or make it if it doesn't exist
$query = "SELECT meta_value from conf WHERE meta_key = 'hash'";
$result = mysql_query($query) or die('Could not run query on log table' . mysql_error());
  
if ($result && (mysql_num_rows($result) == 0)) { 
  $oauth_hash = md5($consumer_key.$consumer_request.$retweeter_oauth_token.$retweeter_oauth_secret);
  $query = "INSERT into conf (meta_value,meta_key) VALUES ('" .trim($oauth_hash) . "','hash');";	
  $my_result = mysql_query($query) or die('Could not update oauth hash' . mysql_error());
} else {
  $oauth_hash = mysql_result($result,0); 
  // ----- echo 'Got Oauth hash, it is ' . $oauth_hash . '<br />\n';
}
$connection = new TwitterOAuth(
                                 $consumer_key, 
                                 $consumer_request, 
                                 $retweeter_oauth_token, 
                                 $retweeter_oauth_secret
                                 );
                                 
// The twitter API address
$url = 'http://twitter.com/statuses/friends_timeline.xml';

$buffer = $connection->get($url);
  
// check for success or failure
if (empty($buffer)) { echo 'got no data'; } else {
	$responseCode = $connection->http_code;
}
			
// Log status here - disabled
// $myResponseCode = mysql_real_escape_string($responseCode,$db_handle);
// $query = "INSERT INTO log (Status) VALUES ('" . $myResponseCode . "')";
// $result = mysql_query($query) or die('Could not run query on log table' . mysql_error());
			
if ($responseCode == 200)
{
	$xml = new SimpleXMLElement($buffer);

	foreach( $xml->status as $twTweetNode)
	{
		$strTweet = $twTweetNode->text; 
		$strPostId = $twTweetNode->user->id . $twTweetNode->id;
		$strUser = $twTweetNode->user->screen_name;
        $strPlainPostId = $twTweetNode->id;
		
		//echo $strPostId . " " . $strUser . " ";

		// Since we're using Friends_timeline, need to strip out the user			
    if (strtolower($strUser) != strtolower($username))		
    {
					
			$insert = 0;
			$tweetQuery = "SELECT Id from tweet WHERE PostId = '". $strPostId ."'"; 
			
			// echo $tweetQuery;  // echo the query

			$result = mysql_query($tweetQuery) or die('Couldnt run query on tweetid' . mysql_error());			

			if (($result) && (mysql_num_rows($result) == 0)) 
			{
				// echo "this is a new tweet";
				$insert = 1;
			}
			
      // set hashtag and tweet to lower for case-insensitive comparison
			// $myHashtag = "#" . strtolower($username); 
			$myHashtag = "#" . strtolower($hashtag); 
			if ((strpos(strtolower($strTweet),$myHashtag) > -1) && $insert == 1) 
			{
			
				$myTweet = mysql_real_escape_string($strTweet,$db_handle);
 
				$myQuery = "INSERT into tweet (PostId, User, Tweet, PlainPostID) VALUES ('" .
					trim($strPostId) . "','" . trim($strUser) .
					"','" . trim($myTweet) ."','". trim($strPlainPostId)  ."');";		
	
				$result = mysql_query($myQuery) or die('Couldnt insert tweet' . mysql_error());

				// echo "inserting tweet";					
			}
		} // end if for != $username

	} // end for each user
} // end if Status Code 200
		
// Now we'll go and check the db for tweets which have not yet been retweeted

$myQuery = "SELECT PostId,User,Tweet,Tweeted,PlainPostID FROM tweet WHERE Tweeted is NULL"; 

$result = mysql_query($myQuery) or die('Could not select tweets not tweeted' . mysql_error()); 
echo "Results of Tweeted is NULL query: " . mysql_num_rows($result) . "<br />";

// date for tweeted
$mysqldate = date('Y-m-d H:i:s');

// look at each un-retweeted tweet, post it, and set Tweeted date		
while($row = mysql_fetch_array($result))
{
    $myTweet = $row['PlainPostID']; 
    $tweet_post_url = 'http://api.twitter.com/1/statuses/retweet/' . $myTweet . '.xml';	
    $buffer = $connection->post($tweet_post_url);

  // If it fails, don't mark it Tweeted, we'll get it next time
  if (empty($buffer)) { 
    echo 'got no data'; 
    $responseCode = '';
  } else {
    $responseCode = $connection->http_code;
  }
  if ($responseCode == 200) {
    echo 're-tweeted one<br />';
		$myQuery = "UPDATE tweet SET Tweeted = '" . $mysqldate . "' WHERE PostId = '" . $row['PostId'] . "'";   
		$my_retweet_result = mysql_query($myQuery) or die('Could not update tweeted date' . mysql_error());
  } else {
    echo 'Re-tweet failed, with status code ' . $responseCode . ' on ' . $myTweet . '<br/>';
        $myQuery = "DELETE FROM tweet WHERE Tweeted is NULL";
		$my_retweet_result = mysql_query($myQuery) or die('Could not delete' . mysql_error());
  }
}
?>
