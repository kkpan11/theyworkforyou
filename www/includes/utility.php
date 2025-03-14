<?php

/*
General utility functions v1.1 (well, it was).

*/

include_once INCLUDESPATH . '../../commonlib/phplib/email.php';
include_once INCLUDESPATH . '../../commonlib/phplib/datetime.php';
include_once INCLUDESPATH . '../../commonlib/phplib/validate.php';
use PHPMailer\PHPMailer\PHPMailer;

# Pass it a brief header word and some debug text and it'll be output.
# If TEXT is an array, call the user function, assuming it's a class.
function twfy_debug($header, $text = "") {

    // We set ?DEBUGTAG=n in the URL.
    // (DEBUGTAG is set in config.php).
    // n is a number from (currently) 1 to 4.
    // This sets what amount of debug information is shown.
    // For level '1' we show anything that is passed to this function
    // with a $header in $levels[1].
    // For level '2', anything with a $header in $levels[1] AND $levels[2].
    // Level '4' shows everything.

    $debug_level = get_http_var(DEBUGTAG);
    #$debug_level = 1;

    if ($debug_level != '') {

        // Set which level shows which types of debug info.
        $levels =  [
            1 =>  ['THEUSER', 'TIME', 'SQLERROR', 'PAGE', 'TEMPLATE', 'SEARCH', 'ALERTS', 'MP'],
            2 =>  ['SQL', 'EMAIL', 'WIKIPEDIA', 'hansardlist', 'debatelist', 'wranslist', 'whalllist'],
            3 =>  ['SQLRESULT'],
            // Higher than this: 'DATA', etc.
        ];

        // Store which headers we are allowed to show.
        $allowed_headers = [];

        if ($debug_level > count($levels)) {
            $max_level_to_show = count($levels);
        } else {
            $max_level_to_show = $debug_level;
        }

        for ($n = 1; $n <= $max_level_to_show; $n++) {
            $allowed_headers = array_merge($allowed_headers, $levels[$n]);
        }

        // If we can show this header, then, er, show it.
        if (in_array($header, $allowed_headers) || $debug_level >= 4) {
            if (is_array($text)) {
                $text = call_user_func($text);
            }
            print "<p><span style=\"color:#039;\"><strong>$header</strong></span> $text</p>\n";
        }
    }
}

function exception_handler($e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
}

function error_handler($errno, $errmsg, $filename, $linenum, $vars) {
    // Custom error-handling function.
    // Sends an email to BUGSLIST.
    global $PAGE;

    # Ignore errors we've asked to ignore
    if (error_reporting() == 0) {
        return;
    }

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    # Commented out are ones that a user function cannot handle.
    $errortype =  [
        #E_ERROR            => "Error",
        E_WARNING           => "Warning",
        #E_PARSE            => "Parsing Error",
        E_NOTICE            => "Notice",
        #E_CORE_ERROR       => "Core Error",
        #E_CORE_WARNING     => "Core Warning",
        #E_COMPILE_ERROR    => "Compile Error",
        #E_COMPILE_WARNING  => "Compile Warning",
        E_USER_ERROR        => "User Error",
        E_USER_WARNING      => "User Warning",
        E_USER_NOTICE       => "User Notice",
        E_STRICT            => "Runtime Notice",
        # 5.3 introduced E_DEPRECATED
        8192                => 'Deprecated',
    ];

    $err = '';
    if (isset($_SERVER['REQUEST_URI'])) {
        $err .= "URL:\t\thttps://" . DOMAIN . $_SERVER['REQUEST_URI'] . "\n";
    } else {
        $err .= "URL:\t\tNone - running from command line?\n";
    }
    if (isset($_SERVER['HTTP_REFERER'])) {
        $err .= "Referer:\t" . $_SERVER['HTTP_REFERER'] . "\n";
    } else {
        $err .= "Referer:\tNone\n";
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $err .= "User-Agent:\t" . $_SERVER['HTTP_USER_AGENT'] . "\n";
    } else {
        $err .= "User-Agent:\tNone\n";
    }
    $err .= "Number:\t\t$errno\n";
    $err .= "Type:\t\t" . $errortype[$errno] . "\n";
    $err .= "Message:\t$errmsg\n";
    $err .= "File:\t\t$filename\n";
    $err .= "Line:\t\t$linenum\n";
    if (count($_POST)) {
        $err .= "_POST:";
        foreach ($_POST as $k => $v) {
            $err .= "\t\t$k => $v\n";
        }
    }

    // I'm not sure this bit is actually any use!

    // set of errors for which a var trace will be saved.
    //  $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    //  if (in_array($errno, $user_errors)) {
    //      $err .= "Variables:\t" . serialize($vars) . "\n";
    //  }


    // Add the problematic line if possible.
    if (is_readable($filename)) {
        $source = file($filename);
        $err .= "\nSource:\n\n";
        // Show the line, plus prev and next, with line numbers.
        $err .= $linenum - 2 . " " . $source[$linenum - 3];
        $err .= $linenum - 1 . " " . $source[$linenum - 2];
        $err .= $linenum . " " . $source[$linenum - 1];
        $err .= $linenum + 1 . " " . $source[$linenum];
        $err .= $linenum + 2 . " " . $source[$linenum + 1];
    }


    // Will we need to exit after this error?
    $fatal_errors = [E_ERROR, E_USER_ERROR];
    if (in_array($errno, $fatal_errors)) {
        $fatal = true;
    } else {
        $fatal = false;
    }

    // Finally, display errors and stuff...

    if (DEVSITE || get_http_var(DEBUGTAG)) {
        // On a devsite we just display the problem.
        $errtxt = nl2br($err) . "\n";
        if (!strstr($errmsg, 'mysql_connect')) {
            $errtxt .= "<br><br>Backtrace:<br>" . nl2br(adodb_backtrace(false));
        }
        $message = [
            'title' => "Error",
            'text' => $errtxt,
        ];
        if (is_object($PAGE)) {
            $PAGE->error_message($message, $fatal);
        } else {
            vardump($message);
        }

    } else {
        // On live sites we display a nice message and email the problem.

        $message = [
            'title' => gettext("Sorry, an error has occurred"),
            'text' => gettext("We've been notified by email and will try to fix the problem soon!"),
        ];

        if (is_object($PAGE)) {
            $PAGE->error_message($message, $fatal);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            print "<p>Oops, sorry, an error has occurred!</p>\n";
        }
        if (!($errno & E_USER_NOTICE) && strpos($errmsg, 'pg_connect') === false && strpos($errmsg, 'mysql_connect') === false) {
            mail(BUGSLIST, "[TWFYBUG]: $errmsg", $err, "From: Bug <" . CONTACTEMAIL . ">\n" . "X-Mailer: PHP/" . phpversion());
        }
    }

    // Do we need to exit?
    if ($fatal) {
        exit(1);
    }
}

// Replacement for var_dump()
function vardump($blah) {
    print "<pre>\n";
    var_dump($blah);
    print "</pre>\n";
}

// pretty prints the backtrace, copied from http://uk.php.net/manual/en/function.debug-backtrace.php
function adodb_backtrace($print = true) {
    $s = '';
    if (PHPVERSION() >= 4.3) {

        $MAXSTRLEN = 64;

        $traceArr = debug_backtrace();
        array_shift($traceArr);
        $tabs = sizeof($traceArr) - 1;
        foreach ($traceArr as $arr) {
            for ($i = 0; $i < $tabs; $i++) {
                $s .= ' &nbsp; ';
            }
            $tabs -= 1;
            if (isset($arr['class'])) {
                $s .= $arr['class'] . '.';
            }
            $args = [];
            if (isset($arr['args'])) {
                foreach ($arr['args'] as $v) {
                    if (is_null($v)) {
                        $args[] = 'null';
                    } elseif (is_array($v)) {
                        $args[] = 'Array[' . sizeof($v) . ']';
                    } elseif (is_object($v)) {
                        $args[] = 'Object:' . get_class($v);
                    } elseif (is_bool($v)) {
                        $args[] = $v ? 'true' : 'false';
                    } else {
                        $v = (string) @$v;
                        $str = _htmlspecialchars(substr($v, 0, $MAXSTRLEN));
                        if (strlen($v) > $MAXSTRLEN) {
                            $str .= '...';
                        }
                        $args[] = $str;
                    }
                }
            }

            $s .= $arr['function'] . '(' . implode(', ', $args) . ')';
            //      $s .= sprintf("</font><font color=#808080 size=-1> # line %4d,".
            //            " file: <a href=\"file:/%s\">%s</a></font>",
            //        $arr['line'],$arr['file'],$arr['file']);
            $s .= "\n";
        }
        if ($print) {
            print $s;
        }
    }

    return $s;
}

// Returns the unixtime in microseconds.
function getmicrotime() {
    $mtime = microtime();
    $mtime = explode(" ", $mtime);
    $mtime = $mtime[1] + $mtime[0];

    return $mtime;
}

/* twfy_debug_timestamp
 * Output a timestamp since the page was started. */
$timestamp_last = $timestamp_start = getmicrotime();
function twfy_debug_timestamp($label = "") {
    global $timestamp_last, $timestamp_start;
    $t = getmicrotime();
    twfy_debug("TIME", sprintf(
        "%f msecs since start; %f msecs since last; %s",
        ($t - $timestamp_start) * 1000.0,
        ($t - $timestamp_last) * 1000.0,
        $label
    ));
    $timestamp_last = $t;
}

function format_timestamp($timestamp, $format) {
    // Pass it a MYSQL TIMESTAMP (YYYYMMDDHHMMSS) and a
    // PHP date format string (eg, "Y-m-d H:i:s")
    // and it returns a nicely formatted string according to requirements.

    // Because strtotime can't handle TIMESTAMPS.

    if (preg_match("/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/", $timestamp, $matches)) {
        [$string, $year, $month, $day, $hour, $min, $sec] = $matches;

        return strftime($format, mktime($hour, $min, $sec, $month, $day, $year));
    } else {
        return "";
    }

}


$format_date_months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$format_date_months_short = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function format_date($date, $format) {
    global $format_date_months, $format_date_months_short;
    // Pass it a date (YYYY-MM-DD) and a
    // PHP date format string (eg, "Y-m-d H:i:s")
    // and it returns a nicely formatted string according to requirements.

    if (preg_match("/^(\d\d\d\d)-(\d\d?)-(\d\d?)$/", $date, $matches)) {
        [$string, $year, $month, $day] = $matches;
        if ($year < 1902) { # gmdate fns only go back to Dec. 1901
            if ($format == SHORTDATEFORMAT) {
                return ($day + 0) . ' ' . $format_date_months_short[$month + 0] . " $year";
            } else {
                return ($day + 0) . ' ' . $format_date_months[$month + 0] . " $year";
            }
        }

        return strftime($format, mktime(0, 0, 0, $month, $day, $year));
    } else {
        return "";
    }

}


function format_time($time, $format) {
    // Pass it a time (HH:MM:SS) and a
    // PHP date format string (eg, "H:i")
    // and it returns a nicely formatted string according to requirements.

    if (preg_match("/^(\d\d):(\d\d):(\d\d)$/", $time, $matches)) {
        [$string, $hour, $min, $sec] = $matches;

        return strftime($format, mktime($hour, $min, $sec));
    } else {
        return "";
    }
}



function relative_time($datetime) {
    // Pass it a 'YYYY-MM-DD HH:MM:SS' and it will return something
    // like "Two hours ago", "Last week", etc.

    // http://maniacalrage.net/projects/relative/

    if (!preg_match("/\d\d\d\d-\d\d-\d\d \d\d\:\d\d\:\d\d/", $datetime)) {
        return '';
    }

    $in_seconds = strtotime($datetime);
    $now = time();

    $diff   =  $now - $in_seconds;
    $months =  floor($diff / 2419200);
    $diff   -= $months * 2419200;
    $weeks  =  floor($diff / 604800);
    $diff   -= $weeks * 604800;
    $days   =  floor($diff / 86400);
    $diff   -= $days * 86400;
    $hours  =  floor($diff / 3600);
    $diff   -= $hours * 3600;
    $minutes = floor($diff / 60);
    $diff   -= $minutes * 60;
    $seconds = $diff;


    if ($months > 0) {
        // Over a month old, just show the actual date.
        $date = substr($datetime, 0, 10);
        return format_date($date, LONGDATEFORMAT);

    } else {
        $relative_date = '';
        if ($weeks > 0) {
            // Weeks and days
            $relative_date .= ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '');
            $relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
        } elseif ($days > 0) {
            // days and hours
            $relative_date .= ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '');
            $relative_date .= $hours > 0 ? ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '') : '';
        } elseif ($hours > 0) {
            // hours and minutes
            $relative_date .= ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '');
            $relative_date .= $minutes > 0 ? ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '';
        } elseif ($minutes > 0) {
            // minutes only
            $relative_date .= ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } else {
            // seconds only
            $relative_date .= ($relative_date ? ', ' : '') . $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }
    }

    // Return relative date and add proper verbiage
    return $relative_date . ' ago';

}

function parse_date($date) {
    return datetime_parse_local_date($date, time(), 'en', 'gb');
}

function slugify($text) {
    $text = preg_replace('#[^\w]+#', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('#-+#', '-', $text);
    $text = strtolower($text);
    return $text;
}

/* strip_tags_tospaces TEXT
 * Return a copy of TEXT in which certain block-level HTML tags have been
 * replaced by single spaces, and other HTML tags have been removed. */
function strip_tags_tospaces($text) {
    $text = preg_replace("#\<(p|br|div|td|tr|th|table)[^>]*\>#i", " ", $text);

    return strip_tags(trim($text));
}

function trim_characters($text, $start, $length, $url_length = 60) {
    // Pass it a string, a numeric start position and a numeric length.
    // If the start position is > 0, the string will be trimmed to start at the
    // nearest word boundary after (or at) that position.
    // If the string is then longer than $length, it will be trimmed to the nearest
    // word boundary below (or at) that length.
    // If either end is trimmed, ellipses will be added.
    // The modified string is then returned - its *maximum* length is $length.
    // HTML is always stripped (must be for trimming to prevent broken tags).

    $text = strip_tags_tospaces($text);

    // Split long strings up so they don't go too long.
    // Mainly for URLs which are displayed, but aren't links when trimmed.
    $text = preg_replace('/(\S{' . $url_length . '})/', "\$1 ", $text);

    // Otherwise the word boundary matching goes odd...
    $text = preg_replace("/[\n\r]/", " ", $text);

    // Trim start.
    if ($start > 0) {
        $text = substr($text, $start);

        // Word boundary.
        if (preg_match("/.+?\b(.*)/", $text, $matches)) {
            $text = $matches[1];
            // Strip spare space at the start.
            $text = preg_replace("/^\s/", '', $text);
        }
        $text = '...' . $text;
    }

    // Trim end.
    if (strlen($text) > $length) {

        // Allow space for ellipsis.
        $text = substr($text, 0, $length - 3);

        // Word boundary.
        if (preg_match("/(.*)\s.+/", $text, $matches)) {
            $text = $matches[1];
            // Strip spare space at the end.
            $text = preg_replace("/\s$/", '', $text);
        }
        // We don't want to use the HTML entity for an ellipsis (&#8230;), because then
        // it screws up when we subsequently use htmlentities() to print the returned
        // string!
        $text .= '...';
    }

    return $text;
}

/**
 * Filters user input to remove unwanted HTML tags etc
 */
function filter_user_input($text, $filter_type) {
    // We use this to filter any major user input, especially comments.
    // Gets rid of bad HTML, basically.
    // Uses iamcal.com's lib_filter class.

    // $filter_type is the level of filtering we want:
    //      'comment' allows <b> and <i> tags.
    //      'strict' strips all tags.

    global $filter;

    $text = trim($text);

    // Replace 3 or more newlines with just two newlines.
    //$text = preg_replace("/(\n){3,}/", "\n\n", $text);

    if ($filter_type == 'strict') {
        // No tags allowed at all!
        $filter->allowed =  [];
    } else {
        // Comment.
        // Only allowing <a href>, <b>, <strong>, <i> and <em>
        $filter->allowed =  [
            'a' => ['href'],
            'strong' => [],
            'em' => [],
            'b' => [],
            'i' => [],
        ];
        // turning this on means that stray angle brackets
        // are not turned in to tags
        $filter->always_make_tags = 0;
    }

    $text = $filter->go($text);

    return $text;
}

function prepare_comment_for_display($text) {
    // Makes any URLs into HTML links.
    // Turns \n's into <br>

    // Encode HTML entities.
    // Can't do htmlentities() because it'll turn the few tags we allow into &lt;
    // Must go before the URL stuff.
    $text = htmlentities_notags($text);

    $link_length = 60;
    $text = preg_replace_callback(
        "/(?<!\"|\/)((http(s?):\/\/)|(www\.))([a-zA-Z\d_.+,;:?%~\-\/#='*$!()&[\]]+)([a-zA-Z\d_?%~\-\/#='*$!&])/",
        function ($matches) use ($link_length) {
            if (strlen($matches[0]) > $link_length) {
                return '<a href="' . $matches[0] . '" rel="nofollow">' . substr($matches[0], 0, $link_length) . "...</a>";
            } else {
                return '<a href="' . $matches[0] . '" rel="nofollow">' . $matches[0] . '</a>';
            }
        },
        $text
    );
    $text = str_replace('<a href="www', '<a href="https://www', $text);
    $text = preg_replace("/([\w\.]+)(@)([\w\.\-]+)/i", "<a href=\"mailto:$0\">$0</a>", $text);
    $text = str_replace("\n", "<br>\n", $text);

    return $text;
}

function htmlentities_notags($text) {
    // If you want to do htmlentities() on some text that has HTML tags
    // in it, then you need this function.

    $tbl = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES, 'UTF-8');

    // You could encode extra stuff...
    //$tbl["“"] = "&quot;";
    //$tbl["”"] = "&quot;";
    //$tbl["…"] = "...";
    //$tbl["—"] = "-";
    //$tbl["»"] = "&raquo;";
    //$tbl["«"] = "&laquo;";

    // lib_filter will replace unmatched < and > with entities so
    // we abuse strtr's only replace once behaviour to not double
    // encode them. May not be robust.
    // This does mean if anyone actually wants to put &gt; or &lt;
    // in a comment they can't but that's a lot less likely than
    // < or > for less than and greater than.
    $tbl['&lt;'] = "&lt;";
    $tbl['&gt;'] = "&gt;";

    // Don't want to encode these things
    unset($tbl["<"]);
    unset($tbl[">"]);
    unset($tbl["'"]);
    unset($tbl['"']);

    # strtr "will *NOT* try to replace stuff that it has already worked on."
    $text = strtr($text, $tbl);

    return $text;
}

/*
 * PHP 5.4 changes the default encoding for htmlentities and htmlspecialchars
 * to be UTF-8, not using the php.ini character encoding until PHP 5.6. So
 * we have to wrap all uses of these two functions.
 */
function _htmlentities($s) {
    return htmlentities($s, ENT_COMPAT, 'UTF-8');
}
function _htmlspecialchars($s) {
    return htmlspecialchars($s, ENT_COMPAT, 'UTF-8');
}

function get_canonical_gid($gid) {
    $db = new ParlDB();
    $might_be_redirected = true;
    while ($might_be_redirected) {
        $q = $db->query("SELECT gid_to FROM gidredirect WHERE gid_from = :gid", [':gid' => $gid])->first();
        if ($q) {
            $gid = $q['gid_to'];
        } else {
            $might_be_redirected = false;
        }
    }

    return $gid;
}


function fix_gid_from_db($gid) {
    // The gids in the database are longer than we use in the site.
    // Feed this a gid from the db and it will be returned truncated.

    // $gid will be like 'uk.org.publicwhip/debate/2003-02-28.475.3'.

    $newgid = substr($gid, strrpos($gid, '/') + 1);
    return $newgid;
}

function gid_to_anchor($gid) {
    // For trimming gids to be used as #anchors in pages.
    // Extracted here so we keep it consistent.
    // The gid should already be truncated using fix_gid_from_db(), so it
    // will be like 2003-11-20.966.0
    // This function returns 966.0

    return substr($gid, (strpos($gid, '.') + 1));
}

function preg_replacement_quote($s) {
    // This returns $s but with every $ and \ backslash-escaped.
    // This is to create a string that can be safely used in a
    // preg_replace replacement string.  This function was suggested here:
    // http://www.procata.com/blog/archives/2005/11/13/two-preg_replace-escaping-gotchas/
    return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $s);
}

function send_template_email($data, $merge, $bulk = false, $want_bounces = false, $lang = '') {
    // We should have some email templates in INCLUDESPATH/easyparliament/templates/emails/.

    // $data is like:
    // array (
    //  'template'  => 'send_confirmation',
    //  'to'        => 'phil@gyford.com',
    //  'subject'   => 'Your confirmation email'
    // );

    // $merge is like:
    // array (
    //  'FIRSTNAME' => 'Phil',
    //  'LATNAME'   => 'Gyford'
    //  etc...
    // );

    // In $data, 'template' and 'to' are mandatory. 'template' is the
    // name of the file (when it has '.txt' added to it).

    // We'll get the text of the template and replace all the $merge
    // keys with their tokens. eg, if '{FIRSTNAME}' in the template will
    // be replaced with 'Phil'.

    // Additionally, the first line of a template may start with
    // 'Subject:'. Any text immediately following that, on the same line
    // will be the subject of the email (it will also have its tokens merged).
    // But this subject can be overridden by sending including a 'subject'
    // pair in $data.

    global $PAGE;

    if (!isset($data['to']) || $data['to'] == '') {
        $PAGE->error_message("We need an email address to send to.");
        return false;
    }

    $lang = '';
    if ($lang == 'cy' || LANGUAGE == 'cy') {
        $lang = 'cy/';
    }

    $filename = INCLUDESPATH . "easyparliament/templates/emails/$lang" . $data['template'] . ".txt";
    if (!file_exists($filename) && $lang == 'cy/') {
        $filename = INCLUDESPATH . "easyparliament/templates/emails/" . $data['template'] . ".txt";
    }

    if (!file_exists($filename)) {
        $PAGE->error_message("Sorry, we could not find the email template '" . _htmlentities($data['template']) . "'.");
        return false;
    }

    // Get the text from the template.
    $handle = fopen($filename, "r");
    $emailtext = fread($handle, filesize($filename));
    fclose($handle);

    $filename = INCLUDESPATH . "easyparliament/templates/emails/$lang" . $data['template'] . ".html";
    if (!file_exists($filename) && $lang == 'cy/') {
        $filename = INCLUDESPATH . "easyparliament/templates/emails/" . $data['template'] . ".html";
    }

    if (file_exists($filename)) {
        $htmltext = file_get_contents($filename);
    } else {
        $htmltext = '';
    }

    // See if there's a default subject in the template.
    $firstline = substr($emailtext, 0, strpos($emailtext, "\n"));

    // Work out what the subject line is.
    if (preg_match("/Subject:/", $firstline)) {
        if (isset($data['subject'])) {
            $subject = trim($data['subject']);
        } else {
            $subject = trim(substr($firstline, 8));
        }

        // Either way, remove this subject line from the template.
        $emailtext = substr($emailtext, strpos($emailtext, "\n"));

    } elseif (isset($data['subject'])) {
        $subject = $data['subject'];
    } else {
        $PAGE->error_message("We don't have a subject line for the email, so it wasn't sent.");
        return false;
    }


    // Now merge all the tokens from $merge into $emailtext...
    $search = [];
    $replace = [];

    foreach ($merge as $key => $val) {
        $search[] = '/{' . $key . '}/';
        $replace[] = preg_replacement_quote($val);
    }

    $emailtext = preg_replace($search, $replace, $emailtext);
    $htmltext = preg_replace($search, $replace, $htmltext);

    if ($lang == 'cy' || LANGUAGE == 'cy') {
        if (strpos(DOMAIN, 'www') !== false) {
            $repl = "https://" . str_replace('www.', 'cy.', DOMAIN);
        } else {
            $repl = "https://cy." . DOMAIN;
        }
        $emailtext = str_replace('https://' . DOMAIN, $repl, $emailtext);
        $htmltext = str_replace('https://' . DOMAIN, $repl, $htmltext);
    }

    // Send it!
    $success = send_email($data['to'], $subject, $emailtext, $bulk, 'twfy-DO-NOT-REPLY@' . EMAILDOMAIN, $want_bounces, $htmltext);

    return $success;

}

/* verp_envelope_sender RECIPIENT
 * Construct a VERP envelope sender for an email to RECIPIENT
 */
function twfy_verp_envelope_sender($recipient) {
    $envelope_sender = verp_envelope_sender($recipient, 'twfy', EMAILDOMAIN);

    return $envelope_sender;
}

function send_email($to, $subject, $message, $bulk = false, $from = '', $want_bounces = false, $html = '') {
    // Use this rather than PHP's mail() direct, so we can make alterations
    // easily to all the emails we send out from the site.
    // eg, we might want to add a .sig to everything here...

    if (!$from) {
        $from = CONTACTEMAIL;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAutoTLS = false;
    $mail->setFrom($from, 'TheyWorkForYou');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->CharSet = 'utf-8';
    $mail->Host = OPTION_SMTP_SMARTHOST;
    $mail->Port = OPTION_SMTP_PORT;

    if ($html) {
        $mail->msgHTML($html, INCLUDESPATH . 'easyparliament/templates/emails');
        $mail->AltBody = $message;
    } else {
        $mail->Body = $message;
    }

    if ($bulk) {
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
    }

    if ($want_bounces) {
        $mail->Sender = twfy_verp_envelope_sender($to);
    }

    twfy_debug('EMAIL', "Sending email to $to with subject of '$subject'");
    try {
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


///////////////////////////////
// Cal's functions from
// http://www.iamcal.com/publish/article.php?id=13

// Call this with a key name to get a GET or POST variable.
function get_http_var($name, $default = '', $allow_array = false) {
    $val = $default;
    if (array_key_exists($name, $_POST)) {
        $val = $_POST[$name];
    }
    if (array_key_exists($name, $_GET)) {
        $val = $_GET[$name];
    }
    if (is_array($val) && !$allow_array) {
        $val = $val[0];
    }
    return $val;
}

// Call this with a key name to get a COOKIE variable.
function get_cookie_var($name, $default = '') {
    if (array_key_exists($name, $_COOKIE)) {
        return $_COOKIE[$name];
    }
    return $default;
}
///////////////////////////////

// Pass it an array of key names that should not be generated as
// hidden form variables. It then outputs hidden form variables
// based on the session_vars for this page.
function hidden_form_vars($omit = []) {
    global $DATA, $this_page;

    $session_vars = $DATA->page_metadata($this_page, "session_vars");

    foreach ($session_vars as $n => $key) {
        if (!in_array($key, $omit)) {
            print "<input type=\"hidden\" name=\"$key\" value=\"" . _htmlentities(get_http_var($key)) . "\">\n";
        }
    }
}

// Deprecated. Use hidden_form_vars, above, instead.
function hidden_vars($omit = []) {
    global $DATA;

    foreach ($args as $key => $val) {
        if (!in_array($key, $omit)) {
            print "<input type=\"hidden\" name=\"$key\" value=\"" . _htmlspecialchars($val) . "\">\n";
        }
    }
}

function make_ranking($rank) {
    $rank = $rank + 0;

    # 11th, 12th, 13th use "th" not "st", "nd", "rd"
    if (floor(($rank % 100) / 10) == 1) {
        return $rank . "th";
    }
    # 1st
    if ($rank % 10 == 1) {
        return $rank . "st";
    }
    # 2nd
    if ($rank % 10 == 2) {
        return $rank . "nd";
    }
    # 3rd
    if ($rank % 10 == 3) {
        return $rank . "rd";
    }
    # Everything else use th

    return $rank . "th";
}

function make_plural($word, $number) {
    if ($number == 1) {
        return $word;
    }
    return $word . "s";
}

# Can't have the entities in XML so replace all theones we currently have with numerical entities
# This is yucky. XXX
function entities_to_numbers($string) {
    $string = str_replace(
        ['&Ouml;', '&acirc;', '&uacute;', '&aacute;', '&iacute;', '&ocirc;', '&eacute;'],
        ['&#214;', '&#226;',  '&#250;',   '&#225;',   '&#237;',   '&#244;',  '&#233;'  ],
        $string
    );
    return $string;
}

function make_member_url($name, $const = '', $house = HOUSE_TYPE_COMMONS, $pid = null) {

    // Case for Elizabeth II
    if ($house == HOUSE_TYPE_ROYAL) {
        if (stripos($name, 'elizabeth') !== false) {
            return 'elizabeth_the_second';
        } elseif (stripos($name, 'charles') !== false) {
            return 'prince_charles';
        }
    }

    $s   = [' ', '&amp;', '&ocirc;',  '&Ouml;',  '&ouml;',   '&acirc;',  '&iacute;', '&aacute;', '&uacute;', '&eacute;', '&oacute;', '&Oacute;'];
    $s2  = [" ", "&",     "\xc3\xb4", "\xc3\96", "\xc3\xb6", "\xc3\xa5", "\xc3\xad", "\xc3\xa1", "\xc3\xba", "\xc3\xa9", "\xc3\xb3", "\xc3\x93"];
    $r   = ['_', 'and',   'o',        'o',       'o',        'a',        'i',        'a',        'u',        'e',        'o',        'o'];
    $name = preg_replace('#^the #', '', strtolower($name));

    $out = '';

    // Insert the Person ID if known.
    if ($pid !== null) {
        $out .= $pid . '/';
    }

    // Always inject the person's name
    $out .= urlencode(str_replace($s2, $r, str_replace($s, $r, $name)));

    // If there is a constituency, inject that too
    if ($const && $house == HOUSE_TYPE_COMMONS) {
        $out .= '/' . urlencode(str_replace($s2, $r, str_replace($s, $r, strtolower($const))));
    }

    return $out;
}

function member_full_name($house, $title, $given_name, $family_name, $lordofname) {

    switch ($house) {
        case HOUSE_TYPE_LORDS:
            $s = '';
            if (!$family_name) {
                $s = 'the ';
            }
            $s .= $title;
            if ($family_name) {
                $s .= ' ' . $family_name;
            }
            if ($lordofname) {
                $s .= ' of ' . $lordofname;
            }
            return $s;

        case HOUSE_TYPE_ROYAL:
            $s = "$given_name $family_name";
            return $s;

        default:
            $s = "$given_name $family_name";
            if ($title) {
                $s = $title . ' ' . $s;
            }
            return $s;
    }

}

function by_peer_name($a, $b) {
    if (!$a['family_name'] && !$b['family_name']) {
        return strcmp($a['lordofname'], $b['lordofname']);
    }
    if (!$a['family_name']) {
        return strcmp($a['lordofname'], $b['family_name']);
    }
    if (!$b['family_name']) {
        return strcmp($a['family_name'], $b['lordofname']);
    }
    if (strcmp($a['family_name'], $b['family_name'])) {
        return strcmp($a['family_name'], $b['family_name']);
    }
    return strcmp($a['lordofname'], $b['lordofname']);
}

function prettify_office($pos, $dept) {
    $lookup = [
        'Prime Minister, HM Treasury' => 'Prime Minister',
        'Secretary of State, Foreign & Commonwealth Office' => 'Foreign Secretary',
        'Secretary of State, Home Office' => 'Home Secretary',
        'Minister of State (Energy), Department of Trade and Industry'
            => 'Minister for energy, Department of Trade and Industry',
        'Minister of State (Pensions), Department for Work and Pensions'
            => 'Minister for pensions, Department for Work and Pensions',
        'Parliamentary Secretary to the Treasury, HM Treasury' => 'Chief Whip',
        'The Parliamentary Secretary to the Treasury' => 'Chief Whip',
        "Treasurer of Her Majesty's Household, HM Household" => "Deputy Chief Whip",
        "The Treasurer of Her Majesty's Household, HM Household" => "Deputy Chief Whip",
        'Comptroller, HM Household' => 'Government Whip',
        'Vice Chamberlain, HM Household' => 'Government Whip',
        "The Vice-Chamberlain of Her Majesty's Household" => 'Government Whip',
        'Lords Commissioner, HM Treasury' => 'Government Whip',
        "The Lord Commissioner of Her Majesty's Treasury" => 'Government Whip',
        'Assistant Whip, HM Treasury' => 'Assistant Whip',
        'Lords in Waiting, HM Household' => 'Government Whip',
        'Lords in Waiting (HM Household)' => 'Government Whip',
        'Baronesses in Waiting, HM Household' => 'Government Whip',
    ];
    if ($pos) { # Government post, or Chairman of Select Committee
        $pretty = $pos;
        if ($dept && $dept != 'No Department') {
            $pretty .= ", $dept";
        }
        if (array_key_exists($pretty, $lookup)) {
            $pretty = $lookup[$pretty];
        }
    } else { # Member of Select Committee
        $pretty = "Member, $dept";
    }
    return $pretty;
}

function major_summary($data, $echo = true) {
    global $hansardmajors;
    $html = '';
    $db = new ParlDB();
    $one_date = false;

    //if no printed majors passed, default to all
    if (!isset($printed_majors)) {
        $printed_majors = [1, 2, 4, 3, 5, 101, 7]; # 8
    }

    // single date?
    if (isset($data['date'])) {
        $one_date = true;
    }

    // remove empty entries, so they don't produce errors
    foreach (array_keys($hansardmajors) as $major) {
        if (array_key_exists($major, $data)) {
            if (count($data[$major]) == 0) {
                unset($data[$major]);
            }
        }
    }

    //work out the date text to be displaid
    $daytext = [];
    if (!$one_date) {
        $todaystime = gmmktime(0, 0, 0, date('m'), date('d'), date('Y'));
        foreach ($data as $major => $array) {
            if (!in_array('timestamp', $array)) {
                $daytext[$major] = "The most recent ";
            } elseif ($todaystime - $array['timestamp'] == 86400) {
                $daytext[$major] = "Yesterday&rsquo;s";
            } elseif ($todaystime - $array['timestamp'] <= (6 * 86400)) {
                $daytext[$major] = gmdate('l', $array['timestamp']) . "&rsquo;s";
            } else {
                $daytext[$major] = "The most recent ";
            }
        }
    }

    //build html
    foreach ($printed_majors as $p_major) {
        if (!array_key_exists($p_major, $data)) {
            continue;
        }

        if ($one_date) {
            $date = $data['date'];
        } else {
            $date = $data[$p_major]['hdate'];
        }
        $q = $db->query('SELECT section_id, body, gid
                FROM hansard, epobject
                WHERE hansard.epobject_id = epobject.epobject_id '
                . ($p_major == 4 ? 'AND subsection_id=0' : 'AND section_id=0') .
                ' AND hdate = "' . $date . '"
                AND major = ' . $p_major . '
                ORDER BY hpos');
        $out = '';
        $LISTURL = new \MySociety\TheyWorkForYou\Url($hansardmajors[$p_major]['page_all']);
        $current_sid = 0;
        foreach ($q as $row) {
            $gid = fix_gid_from_db($row['gid']);
            $body = $row['body'];
            $section_id = $row['section_id'];
            //if (strstr($body, 'Chair]')) continue;
            if ($p_major == 4 && !$section_id) {
                if ($current_sid++) {
                    $out .= '</ul>';
                }
                $out .= '<li>' . $body . '<ul>';
            } else {
                $LISTURL->insert([ 'id' => $gid ]);
                $out .= '<li><a href="' . $LISTURL->generate() . '">';
                $out .= $body . '</a>';
            }
        }
        if ($out) {
            $html .= _major_summary_title($p_major, $data, $LISTURL, $daytext);
            $html .= '<ul class="hansard-day">';
            $html .= $out;
            $html .= '</ul>';
        }
    }
    $html .= '</ul>';

    if ($echo) {
        print $html;
    } else {
        return $html;
    }
}

function _major_summary_title($major, $data, $LISTURL, $daytext) {
    global $hansardmajors;

    $return = '<h4>';
    if (isset($daytext[$major])) {
        $return .= $daytext[$major] . ' ';
    }

    $return .= '<a href="';
    if (isset($data[$major]['listurl'])) {
        $return .= $data[$major]['listurl'];
    } else {
        $LISTURL->reset();
        $return .= $LISTURL->generate();
    }
    $return .= '">' . $hansardmajors[$major]['title'] . '</a>';
    if (isset($daytext[$major])) {
        $return;
    }
    $return .= '</h4>';

    return $return;
}

function score_to_strongly($dmpscore) {
    $dmpdesc = "unknown about";
    if ($dmpscore > 0.95 && $dmpscore <= 1.0) {
        $dmpdesc = "consistently voted against";
    } elseif ($dmpscore > 0.85) {
        $dmpdesc = "almost always voted against";
    } elseif ($dmpscore > 0.6) {
        $dmpdesc = "generally voted against";
    } elseif ($dmpscore > 0.4) {
        $dmpdesc = "voted a mixture of for and against";
    } elseif ($dmpscore > 0.15) {
        $dmpdesc = "generally voted for";
    } elseif ($dmpscore > 0.05) {
        $dmpdesc = "almost always voted for";
    } elseif ($dmpscore >= 0.0) {
        $dmpdesc = "consistently voted for";
    } elseif ($dmpscore == -1) {
        $dmpdesc = "has never voted in a major vote about";
    }
    return $dmpdesc;
}

function valid_url($url) {
    $return = false;
    if (preg_match("/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?/i", $url)) {
        $return = true;
    }
    return $return;
}

function redirect($url, $code = 302) {
    if (defined('TESTING')) {
        print "Location: $url";
    } else {
        header("Location: $url", true, $code);
    }
    exit;
}

function cache_version($file) {
    static $version_hash = [];
    $path = BASEDIR . "/$file";
    if (is_file($path) && (!isset($version_hash[$file]) || DEVSITE)) {
        $version_hash[$file] = stat($path)[9];
        $file .= '?' . $version_hash[$file];
    }
    return WEBPATH . $file;
}
