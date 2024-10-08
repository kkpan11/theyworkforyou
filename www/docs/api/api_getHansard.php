<?php

include_once INCLUDESPATH . "easyparliament/member.php";

function api_getHansard_front() {
    ?>
<p><big>Fetch all Hansard.</big></p>

<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following search terms
at present. If you wish to search multiple things (e.g. search plus person),
use the <kbd>search</kbd> parameter, and supply
<a href="/help/#searching">query arguments</a>.</p>

<dl>
<dt>search</dt>
<dd>Fetch the data that contain this term.</dd>
<dt>person</dt>
<dd>Fetch the data by a particular person ID.</dd>
<dt>order (optional, when using search or person, defaults to date)</dt>
<dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering, <kbd>p</kbd> for use by person.</dd>
<dt>page (optional, when using search or person)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional, when using search or person)</dt>
<dd>Number of results to return.</dd>
</dl>

<?php
}

function api_getHansard_search($s) {
    _api_getHansard_search([
        's' => $s,
        'pid' => get_http_var('person'),
    ]);
}
function api_getHansard_person($pid) {
    _api_getHansard_search([
        'pid' => $pid,
    ]);
}

function _api_getHansard_date($type, $d) {
    $args =  ['date' => $d];
    $LIST = _api_getListObject($type);
    $LIST->display('date', $args, 'api');
}
function _api_getHansard_year($type, $y) {
    $args = ['year' => $y];
    $LIST = _api_getListObject($type);
    $LIST->display('calendar', $args, 'api');
}
function _api_getHansard_search($array) {
    $search = isset($array['s']) ? trim($array['s']) : '';
    $pid = trim($array['pid']);
    $type = $array['type'] ?? '';
    $search = filter_user_input($search, 'strict');
    if ($pid) {
        $search .= ($search ? ' ' : '') . 'speaker:' . $pid;
    }
    if ($type) {
        $search .= " section:" . $type;
    }

    $o = get_http_var('order');
    if ($o == 'p') {
        $data = \MySociety\TheyWorkForYou\Utility\Search::searchByUsage($search);
        $out = [];
        if (!isset($data['speakers'])) {
            $data['speakers'] = [];
        }
        foreach ($data['speakers'] as $pid => $s) {
            $out[$pid] = [
                'house' => $s['house'],
                'name' => $s['name'],
                'party' => $s['party'],
                'count' => $s['count'],
                'mindate' => substr($s['pmindate'], 0, 7),
                'maxdate' => substr($s['pmaxdate'], 0, 7),
            ];
        }
        api_output($out);

        return;
    }

    global $SEARCHENGINE;
    $SEARCHENGINE = new SEARCHENGINE($search);
    if (!$SEARCHENGINE->valid) {
        api_error('Invalid search term');

        return;
    }
    #    $query_desc_short = $SEARCHENGINE->query_description_short();
    $pagenum = get_http_var('page');
    $args =  [
        's' => $search,
        'p' => $pagenum,
        'num' => get_http_var('num'),
        'pop' => 1,
        'o' => ($o == 'd' || $o == 'r') ? $o : 'd',
    ];
    $LIST = new HANSARDLIST();
    $data = $LIST->display('search', $args, 'none');
    api_output($data);
}

function _api_getHansard_gid($type, $gid) {
    $args = ['gid' => $gid];
    $LIST = _api_getListObject($type);

    try {
        return $LIST->display('gid', $args, 'api');
    } catch (RedirectException $e) {
        $url = $_SERVER['REQUEST_URI'];
        $url = str_replace($gid, $e->getMessage(), $url);
        header('Location: ' . $url);
        exit;
    }
}

function _api_getHansard_department($type, $dept) {
    $args = ['department' => $dept];
    $LIST = _api_getListObject($type);
    $LIST->display('department', $args, 'api');
}

function _api_getListObject($type) {
    eval('$list = new ' . strtoupper($type) . 'LIST;');

    return $list;
}
