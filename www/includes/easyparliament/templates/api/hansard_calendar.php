<?php

// For displaying the main Hansard content listings (by date).

if (isset($data['years'])) {
    $DAYURL = new \MySociety\TheyWorkForYou\Url($data['info']['page']);
    $out = ['url' => $DAYURL->generate('none'), 'dates' => []];
    foreach ($data['years'] as $year => $months) {
        foreach ($months as $month => $dates) {
            foreach ($dates as $date) {
                $date = sprintf("%04d-%02d-%02d", $year, $month, $date);
                $out['dates'][] = $date;
            }
        }
    }
    api_output($out);
} else {
    api_error('Please supply a year');
}
