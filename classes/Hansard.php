<?php

namespace MySociety\TheyWorkForYou;

/**
 * Hansard
 */

class Hansard extends \HANSARDLIST {
    /**
     * Search
     *
     * Performs a search of Hansard.
     *
     * @param string $searchstring The string to initialise SEARCHENGINE with.
     * @param array  $args         An array of arguments to restrict search results.
     *
     * @return array An array of search results.
     */

    public function search($searchstring, $args) {

        if (!defined('FRONT_END_SEARCH') || !FRONT_END_SEARCH) {
            throw new \Exception('FRONT_END_SEARCH is not defined or is false.');
        }

        // throw exceptions rather than emit PAGE->error
        $args['exceptions'] = true;
        $list = new \HANSARDLIST();
        return $list->display('search', $args, 'none');

    }

}
