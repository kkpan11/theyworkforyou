<?php

function api_convertURL_front() {
    ?>
<p><big>Converts a parliament.uk Hansard URL into a TheyWorkForYou one, if possible.</big></p>

<h4>Arguments</h4>
<dl>
<dt>url (required)</dt>
<dd>The parliament.uk URL you wish to convert, e.g.
<?php	$db = new ParlDB();
    $q = $db->query('SELECT source_url FROM hansard WHERE major=1 AND hdate>"2006-07-01" ORDER BY RAND() LIMIT 1')->first();
    print $q['source_url'];
    ?></dd>
</dl>

<h4>Example Response</h4>
<pre>{
    gid : "uk.org.publicwhip/debate/2006-07-11a.1352.2",
    url : "https://www.theyworkforyou.com/debates/?id=2006-07-11a.1311.0#g1352.2"
}</pre>

<h4>Example Use</h4>
<p>This probably counts as "AJAX", though it doesn't use XMLHTTP, asynchronicity, or XML, only cross-site JavaScript... It's definitely Web 2.1, at least.</p>

<ul><li><a href="javascript:function twfy_cb(r){if (r.url)window.location=r.url;};(function(d,s){s=d.createElement('script');s.src='https://www.theyworkforyou.com/api/convertURL?callback=twfy_cb&key=Gbr9QgCDzHExFzRwPWGAiUJ5&url='+encodeURIComponent(window.location);d.getElementsByTagName('head')[0].appendChild(s);})(document)">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</li></ul>
<?php
}

/* Very similar to function in hansardlist.php, but separated */
function get_listurl($q) {
    global $hansardmajors;
    $id_data = [
        'gid' => fix_gid_from_db($q['gid']),
        'major' => $q['major'],
        'htype' => $q['htype'],
        'subsection_id' => $q['subsection_id'],
    ];
    $db = new ParlDB();
    $LISTURL = new \MySociety\TheyWorkForYou\Url($hansardmajors[$id_data['major']]['page_all']);
    $fragment = '';
    if ($id_data['htype'] == '11' || $id_data['htype'] == '10') {
        $LISTURL->insert([ 'id' => $id_data['gid'] ]);
    } else {
        $parent_epobject_id = $id_data['subsection_id'];
        $parent_gid = '';
        $r = $db->query("SELECT gid
                FROM 	hansard
                WHERE	epobject_id = :epobject_id", [
            ':epobject_id' => $parent_epobject_id,
        ])->first();
        if ($r) {
            $parent_gid = fix_gid_from_db($r['gid']);
        }
        if ($parent_gid != '') {
            $LISTURL->insert([ 'id' => $parent_gid ]);
            $fragment = '#g' . gid_to_anchor($id_data['gid']);
        }
    }
    return $LISTURL->generate('none') . $fragment;
}

function api_converturl_url_output($q) {
    $gid = $q['gid'];
    $url = get_listurl($q);
    $output = [
        'gid' => $gid,
        'url' => 'https://www.theyworkforyou.com' . $url,
    ];
    api_output($output);
}
function api_converturl_url($url) {
    $db = new ParlDB();
    $url_nohash = preg_replace('/#.*/', '', $url);
    $q = $db->query('select gid,major,htype,subsection_id from hansard where source_url = :url order by gid limit 1', [
        ':url' => $url,
    ])->first();
    if ($q) {
        return api_converturl_url_output($q);
    }

    $q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like :url order by gid limit 1', [
        ':url' => $url_nohash . '%',
    ])->first();
    if ($q) {
        return api_converturl_url_output($q);
    }

    $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);
    if ($url_bound != $url_nohash) {
        $q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like :url order by gid limit 1', [
            ':url' => $url_bound . '%',
        ])->first();
        if ($q) {
            return api_converturl_url_output($q);
        }
    }
    api_error('Sorry, URL could not be converted');
}

?>
