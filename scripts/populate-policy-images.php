#!/usr/bin/php -q
<?php

/*
 * Uses a CSV file to update the policies table with
 * details of the images used for the hero banners on
 * the divisions pages.
 *
 * Doesn't fetch the images but assumes they've been
 * added in www/docs/images/ as $policy_id.jpg
 *
 * Take the path to the CSV file with the details
 * Columns should be:
 * 1: policy id
 * 3: title
 * 4: description
 * 6: source: url of the original image
 * 7: image attribution - name of photographer
 * 8: license URL - url of the CC licence of photo
 *
 */

include '../www/includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$ARGV = $_SERVER['argv'];
$db = new ParlDB();

$csvfile = $ARGV[1] ?? '';

if (!$csvfile) {
    print "Need a csv file with policy details\n";
    exit(1);
}

if (!file_exists($csvfile)) {
    print "$csvfile cannot be found\n";
    exit(1);
}

$file = fopen($csvfile, 'r');

if (!$file) {
    print "failed to open $csvfile\n";
    exit(1);
}

$count = 0;
while (($policy = fgetcsv($file)) !== false) {
    if (intval($policy[0])) {
        $policy_id = $policy[0];
        $img_id = $policy[1] ? $policy[1] : $policy_id;
        $title = $policy[2];
        $description = $policy[3];
        $attribution = $policy[6];
        $licence_url = $policy[7];
        $source = $policy[5];

        $q = $db->query(
            "UPDATE policies SET
            image = :image, image_source = :image_source, image_attrib = :image_attribution,
            image_license_url = :license_url, title = :title, description = :description WHERE
            policy_id = :policy_id
            ",
            [
                ':policy_id' => $policy_id,
                ':title' => $title,
                ':description' => $description,
                ':image' => "/images/policies/" . $img_id . ".jpg",
                ':image_source' => $source,
                ':image_attribution' => $attribution,
                ':license_url' => $licence_url]
        );
        if ($q->success()) {
            $count += $q->affected_rows();
        } else {
            print "failed to update data for $policy_id\n";
        }
    }
}

// this will only give a count where the update changed something
// so if we run an update and all the values are the same then it
// won't count in this total
print "Updated details for $count policies\n";
