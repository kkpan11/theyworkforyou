<?php

namespace MySociety\TheyWorkForYou\SectionView;

class LordsView extends SectionView {
    public $major = 101;
    protected $class = 'LORDSDEBATELIST';

    protected function front_content() {
        return $this->list->display('biggest_debates', ['days' => 7, 'num' => 20], 'none');
    }
}
