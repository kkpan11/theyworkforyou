<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WransView extends SectionView {
    public $major = 3;
    protected $class = 'WRANSLIST';

    protected function front_content() {
        return $this->list->display('recent_wrans', ['days' => 7, 'num' => 20], 'none');
    }

    protected function display_front() {
        global $DATA, $this_page;
        if (get_http_var('type') == 'wrans') {
            return parent::display_front();
        }

        $data = [];

        $args = [ 'months' => 1 ];
        $WRANSLIST = new \WRANSLIST();

        $wrans = [];
        $wrans['data'] = $WRANSLIST->display('recent_wrans', ['days' => 7, 'num' => 5], 'none');
        $wrans['calendar'] = $WRANSLIST->display('calendar', $args, 'none');

        $WMSLIST = new \WMSLIST();
        $wms = [];
        $wms['data'] = $WMSLIST->display('recent_wms', ['days' => 7, 'num' => 20], 'none');
        $wms['calendar'] = $WMSLIST->display('calendar', $args, 'none');
        $wms['rssurl'] = $DATA->page_metadata('wmsfront', 'rss');

        $data['wrans'] = $wrans;
        $data['wms'] = $wms;

        $data['template'] = 'section/wrans_index';
        return $data;
    }

    protected function getViewUrls() {
        $urls = [];
        $day = new \MySociety\TheyWorkForYou\Url('wrans');
        $urls['day'] = $day;
        $urls['wransday'] = $day;
        $day = new \MySociety\TheyWorkForYou\Url('wms');
        $urls['wmsday'] = $day;
        return $urls;
    }

    protected function getSearchSections() {
        $sections = [
            [ 'section' => 'wrans', 'title' => 'Written Answers' ],
        ];
        if (get_http_var('type') == '') {
            $sections[] = [ 'section' => 'wms', 'title' => 'Written Ministerial Statements' ];
        }
        return $sections;
    }

    # If we don't have "q"/"r" in the GID, we use this counter to output on any
    # speech bar the first (assuming that's the question)
    private $votelinks_so_far = 0;

    protected function generate_votes($votes, $id, $gid) {
        /*
        Returns HTML for the 'Does this answer the question?' links (wrans) in the sidebar.
        $votes = => array (
            'user'    => array ( 'yes' => '21', 'no' => '3' ),
            'anon'    => array ( 'yes' => '132', 'no' => '30' )
        )
        */

        global $this_page;

        # If there's a "q" we assume it's a question and ignore it
        if (strstr($gid, 'q')) {
            return;
        }

        $data = [];
        if ($this->votelinks_so_far > 0 || strstr($gid, 'r')) {
            $yesvotes = $votes['user']['yes'] + $votes['anon']['yes'];
            $novotes = $votes['user']['no'] + $votes['anon']['no'];

            $yesplural = $yesvotes == 1 ? 'person thinks' : 'people think';
            $noplural = $novotes == 1 ? 'person thinks' : 'people think';

            $URL = new \MySociety\TheyWorkForYou\Url($this_page);
            $returl = $URL->generate();
            $VOTEURL = new \MySociety\TheyWorkForYou\Url('epvote');
            $VOTEURL->insert(['v' => '1', 'id' => $id, 'ret' => $returl]);
            $yes_vote_url = $VOTEURL->generate();
            $VOTEURL->insert(['v' => '0']);
            $no_vote_url = $VOTEURL->generate();

            $data = [
                'yesvotes' => $yesvotes,
                'yesplural' => $yesplural,
                'yesvoteurl' => $yes_vote_url,
                'novoteurl' => $no_vote_url,
                'novotes' => $novotes,
                'noplural' => $noplural,
            ];
        }

        $this->votelinks_so_far++;
        return $data;
    }
}
