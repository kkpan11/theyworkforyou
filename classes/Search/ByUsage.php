<?php

# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou\Search;

class ByUsage {
    public function search($searchstring) {
        $q_house = '';
        if (ctype_digit(get_http_var('house'))) {
            $q_house = get_http_var('house');
        }

        $wtt = get_http_var('wtt');
        if ($wtt) {
            $q_house = 2;
        }

        # Fetch the results
        $data = \MySociety\TheyWorkForYou\Utility\Search::searchByUsage($searchstring, $q_house);

        $data['house'] = $q_house;
        $data['search_type'] = 'person';
        return $data;
    }

}
