Thanks for trying out retweeter. 

NOTE: If you've used a version prior to 1.1, you will need to make database
changes to update your tables. 

IF AND ONLY IF YOU HAVE EXISTING DATABASE FROM VERSIONS < 1.1, RUN THIS SQL:
----------------------

CREATE TABLE `conf` (
  `meta_key` varchar(4) default NULL,
  `meta_value` varchar(32) default NULL,
  `CreatedDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ModifiedDate` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`meta_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `tweet` ADD COLUMN `PlainPostID` varchar(20) default NULL;
 
----------------------

All necessary configuration is at the top of the retweeter.php file. Set 
$username and $password to the twitter account username and pass, then
set all the DB info. 

You'll also need to register your app over at Twitter, and then set the 
appropriate fields in the config file - there are four in total (2 relate to
the app itself, 2 to the specific twitter account it will need to access). 

You'll need to setup the database directly, and create
tables named "log", "tweet", and "conf". (see SQL below). 

Retweeter depends on PHP5, since it uses SimpleXML to parse what Twitter 
returns. 

It expects to be run from the command line, on a cron job - remember that
the Twitter API is time-limited. I use a setting of every 2 minutes, which
is pretty frequent but stays within the API bounds. 

Any issues, let me know: eckman.john@gmail.com
Or contact me through: http://www.openparenthesis.org/contact/

Project home is: http://www.openparenthesis.org/code/twitter-api/

John Eckman

------

Here's the SQL for the tables:

CREATE TABLE `log` (
  `Id` int(11) NOT NULL auto_increment,
  `Status` int(11) default NULL,
  `CreatedDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

CREATE TABLE `tweet` (
  `Id` int(11) NOT NULL auto_increment,
  `PostId` varchar(20) default NULL,
  `User` varchar(100) default NULL,
  `Tweet` varchar(250) default NULL,
  `CreatedDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ModifiedDate` timestamp NOT NULL default '0000-00-00 00:00:00',
  `Tweeted` datetime default NULL,
  `PlainPostID` varchar(20) default NULL,
  PRIMARY KEY  (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

CREATE TABLE `conf` (
  `meta_key` varchar(4) default NULL,
  `meta_value` varchar(32) default NULL,
  `CreatedDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ModifiedDate` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`meta_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
