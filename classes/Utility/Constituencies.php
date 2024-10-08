<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Constituency Utilities
 *
 * Utility functions for dealing with constituency names
 */

class Constituencies {
    /**
     * Normalise Constituency Name
     *
     * Turn variations on a constituency name into the canonical version.
     *
     * @param $names string A constituency name to normalise.
     *
     * @return string|bool The normalised constituency name, or false if no match.
     */
    public static function normaliseConstituencyName($name) {

        $db = new \ParlDB();

        // In case we still have an &amp; lying around
        $name = str_replace("&amp;", "&", $name);

        $query = "select cons_id from constituency where name like :name and from_date <= date(now()) and date(now()) <= to_date";
        $q1 = $db->query($query, [
            ':name' => $name,
        ])->first();
        if (!$q1) {
            return false;
        }

        $query = "select name from constituency where main_name and cons_id = '" . $q1['cons_id'] . "'";
        $q2 = $db->query($query)->first();
        if (!$q2) {
            return false;
        }

        return $q2['name'];
    }

}
