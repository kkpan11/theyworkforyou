<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WmsView extends SectionView {
    public $major = 4;
    protected $class = 'WMSLIST';

    protected function front_content() {
        return $this->list->display('recent_wms', ['days' => 7, 'num' => 20], 'none');
    }

    protected function getViewUrls() {
        $urls = [];
        $day = new \MySociety\TheyWorkForYou\Url('wms');
        $urls['day'] = $day;
        $urls['wmsday'] = $day;
        return $urls;
    }
}
