<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Member Utilities
 *
 * Utility functions related to members
 */

class Member {
    /**
     * Find Member Image
     *
     * Return the member's image and associated information.
     *
     * @param string      $pid                The member's ID.
     * @param bool        $smallonly          Should the function only return a small sized image?
     * @param bool|string $substitute_missing Should the function substitute a placeholder if no image can be found?
     *
     * @return array Array of the member's image URL and image size.
     */

    public static function findMemberImage($pid, $smallonly = false, $substitute_missing = false) {
        $image = null;
        $sz = null;
        if (!$smallonly && is_file(BASEDIR . IMAGEPATH . 'mpsL/' . $pid . '.jpeg')) {
            $image = IMAGEPATH . 'mpsL/' . $pid . '.jpeg';
            $sz = 'L';
        } elseif (!$smallonly && is_file(BASEDIR . IMAGEPATH . 'mpsL/' . $pid . '.jpg')) {
            $image = IMAGEPATH . 'mpsL/' . $pid . '.jpg';
            $sz = 'L';
        } elseif (!$smallonly && is_file(BASEDIR . IMAGEPATH . 'mpsL/' . $pid . '.png')) {
            $image = IMAGEPATH . 'mpsL/' . $pid . '.png';
            $sz = 'L';
        } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $pid . '.jpeg')) {
            $image = IMAGEPATH . 'mps/' . $pid . '.jpeg';
            $sz = 'S';
        } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $pid . '.jpg')) {
            $image = IMAGEPATH . 'mps/' . $pid . '.jpg';
            $sz = 'S';
        } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $pid . '.png')) {
            $image = IMAGEPATH . 'mps/' . $pid . '.png';
            $sz = 'S';
        }

        //if no image, use a dummy one
        if (!$image && $substitute_missing) {
            if ($smallonly) {
                if ($substitute_missing === "lord") {
                    $image = "/images/unknownlord.png";
                } else {
                    $image = "/images/unknownperson.png";
                }
                $sz = 'S';
            } else {
                if ($substitute_missing === "lord") {
                    $image = "/images/unknownlord_large.png";
                } else {
                    $image = "/images/unknownperson_large.png";
                }
                $sz = 'L';
            }
        }
        return [$image, $sz];
    }

}
