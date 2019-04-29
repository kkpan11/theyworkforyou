<?php

    $stances = array();

    foreach ($segment['votes']->positions as $key_vote) {
        if ( $key_vote['has_strong'] || $key_vote['position'] == 'has never voted on' ) {
            $stance = strip_tags($key_vote['desc'], '<b>');
            $stance = ucfirst($stance);
            $stance = preg_replace('#</?b>#i', '*', $stance);
            $stance = htmlentities(html_entity_decode($stance), ENT_COMPAT | ENT_XML1);
            $lines = explode("\n", wordwrap($stance, 84));

            $stances[] = $lines;
        }
    }

    $color_cream = '#F3F1EB';
    $color_green = '#62B356';

    $stance_lineheight = 30;
    $stance_baseline_offset = 20; // because ImageMagick doesn't support `alignment-baseline`
    $stance_padding_top = 14;
    $stance_padding_bottom = 8;

    $tspan_bold_open = '<tspan font-weight="bold">';
    $tspan_bold_close = '</tspan>';

    header("Content-type: image/svg+xml");

    echo '<?xml version="1.0" encoding="utf-8"?>';

 ?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="1000" height="500" viewBox="0 0 1000 500" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" font-family="Droid Sans, Trebuchet">

    <defs>
        <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="grad">
            <stop stop-color="<?= $color_cream ?>" stop-opacity="0" offset="0%"/>
            <stop stop-color="<?= $color_cream ?>" offset="100%"/>
        </linearGradient>
    </defs>

    <rect fill="<?= $color_cream; ?>" x="0" y="0" width="1000" height="500"></rect>

    <text font-size="40" font-weight="bold" fill="#000000">
        <tspan x="40" y="73">How <?= $full_name ?> voted</tspan>
        <tspan x="40" y="113">on <?= $segment['title'] ?></tspan>
    </text>

  <?php $stance_y = 140; ?>

  <?php foreach ($stances as $i=>$lines) { ?>
    <path d="M40,<?= $stance_y ?> L1000,<?= $stance_y ?>" stroke="#DDD8C9" stroke-width="2"></path>
    <text font-size="24" font-weight="normal" fill="#000000">
        <?php foreach ($lines as $j=>$line) { ?>
            <tspan x="40" y="<?= $stance_y + $stance_padding_top + $stance_baseline_offset + ($stance_lineheight * $j) ?>"><?php
                if (strpos($line, '*') !== False) {
                    echo preg_replace(
                        '#(?:^|[*])([^\r\n *][^*\n]*[^\r\n *])(?:$|[*])#i',
                        $tspan_bold_open . '$1' . $tspan_bold_close,
                        $line
                    );
                } else {
                    echo $line;
                }
            ?></tspan>
        <?php } ?>
    </text>
    <?php $stance_y = $stance_y + $stance_padding_top + ($stance_lineheight * count($lines)) + $stance_padding_bottom ?>
  <?php } ?>

    <rect fill="url(#grad)" x="0" y="360" width="1000" height="70"></rect>
    <rect fill="<?= $color_cream ?>" x="0" y="430" width="1000" height="70"></rect>

    <text x="960" y="476" text-anchor="end" font-size="20" fill="#6A6A6A">
      Source: theyworkforyou.com
    </text>

    <rect fill="<?= $color_green; ?>" x="40" y="440" width="270" height="60"></rect>
    <path fill="#FFFFFF" d="M66.605,480 L71.015,480 L71.015,464.16 L76.385,464.16 L76.385,460.44 L61.205,460.44 L61.205,464.16 L66.605,464.16 L66.605,480 Z M79.085,480 L83.495,480 L83.495,469.95 C84.455,469.02 85.145,468.51 86.255,468.51 C87.515,468.51 88.085,469.17 88.085,471.33 L88.085,480 L92.495,480 L92.495,470.76 C92.495,467.04 91.115,464.76 87.875,464.76 C85.865,464.76 84.395,465.81 83.285,466.8 L83.495,464.13 L83.495,458.97 L79.085,458.97 L79.085,480 Z M95.345,472.56 C95.345,477.48 98.615,480.36 102.875,480.36 C104.645,480.36 106.625,479.73 108.155,478.68 L106.685,476.01 C105.605,476.67 104.585,477 103.475,477 C101.495,477 99.995,476.01 99.605,473.76 L108.515,473.76 C108.605,473.4 108.695,472.68 108.695,471.9 C108.695,467.85 106.595,464.76 102.365,464.76 C98.795,464.76 95.345,467.73 95.345,472.56 Z M99.575,471.03 C99.875,469.05 101.075,468.12 102.455,468.12 C104.225,468.12 104.945,469.32 104.945,471.03 L99.575,471.03 Z M112.085,482.16 L111.305,485.52 C111.905,485.7 112.505,485.82 113.405,485.82 C116.915,485.82 118.505,483.9 119.915,480.15 L125.075,465.12 L120.845,465.12 L119.045,471.51 C118.685,472.92 118.355,474.33 118.025,475.74 L117.905,475.74 C117.515,474.27 117.155,472.86 116.735,471.51 L114.605,465.12 L110.165,465.12 L115.895,479.61 L115.685,480.39 C115.295,481.56 114.545,482.34 113.075,482.34 C112.745,482.34 112.355,482.22 112.085,482.16 Z M129.455,480 L134.915,480 L136.775,471.12 C137.075,469.62 137.315,468.03 137.555,466.59 L137.675,466.59 C137.885,468.03 138.155,469.62 138.455,471.12 L140.375,480 L145.925,480 L149.405,460.44 L145.175,460.44 L143.825,469.59 C143.555,471.6 143.285,473.67 143.045,475.77 L142.925,475.77 C142.535,473.67 142.145,471.57 141.755,469.59 L139.625,460.44 L135.875,460.44 L133.775,469.59 C133.385,471.63 132.995,473.7 132.635,475.77 L132.515,475.77 C132.245,473.7 131.975,471.63 131.705,469.59 L130.355,460.44 L125.825,460.44 L129.455,480 Z M150.875,472.56 C150.875,477.51 154.355,480.36 158.135,480.36 C161.885,480.36 165.365,477.51 165.365,472.56 C165.365,467.61 161.885,464.76 158.135,464.76 C154.355,464.76 150.875,467.61 150.875,472.56 Z M155.405,472.56 C155.405,469.98 156.305,468.33 158.135,468.33 C159.935,468.33 160.865,469.98 160.865,472.56 C160.865,475.14 159.935,476.79 158.135,476.79 C156.305,476.79 155.405,475.14 155.405,472.56 Z M168.395,480 L172.805,480 L172.805,471.36 C173.585,469.35 174.965,468.63 176.075,468.63 C176.705,468.63 177.155,468.72 177.695,468.87 L178.415,465.06 C177.995,464.88 177.485,464.76 176.645,464.76 C175.115,464.76 173.495,465.72 172.415,467.73 L172.295,467.73 L171.995,465.12 L168.395,465.12 L168.395,480 Z M180.335,480 L184.625,480 L184.625,476.34 L186.515,474.21 L189.875,480 L194.645,480 L189.035,471.24 L194.255,465.12 L189.455,465.12 L184.745,471.03 L184.625,471.03 L184.625,458.97 L180.335,458.97 L180.335,480 Z M197.105,480 L201.545,480 L201.545,472.41 L208.295,472.41 L208.295,468.69 L201.545,468.69 L201.545,464.16 L209.465,464.16 L209.465,460.44 L197.105,460.44 L197.105,480 Z M211.595,472.56 C211.595,477.51 215.075,480.36 218.855,480.36 C222.605,480.36 226.085,477.51 226.085,472.56 C226.085,467.61 222.605,464.76 218.855,464.76 C215.075,464.76 211.595,467.61 211.595,472.56 Z M216.125,472.56 C216.125,469.98 217.025,468.33 218.855,468.33 C220.655,468.33 221.585,469.98 221.585,472.56 C221.585,475.14 220.655,476.79 218.855,476.79 C217.025,476.79 216.125,475.14 216.125,472.56 Z M229.115,480 L233.525,480 L233.525,471.36 C234.305,469.35 235.685,468.63 236.795,468.63 C237.425,468.63 237.875,468.72 238.415,468.87 L239.135,465.06 C238.715,464.88 238.205,464.76 237.365,464.76 C235.835,464.76 234.215,465.72 233.135,467.73 L233.015,467.73 L232.715,465.12 L229.115,465.12 L229.115,480 Z M244.745,480 L249.155,480 L249.155,473.04 L255.065,460.44 L250.445,460.44 L248.705,464.94 C248.165,466.41 247.595,467.76 247.055,469.26 L246.935,469.26 C246.365,467.76 245.855,466.41 245.345,464.94 L243.575,460.44 L238.865,460.44 L244.745,473.04 L244.745,480 Z M255.905,472.56 C255.905,477.51 259.385,480.36 263.165,480.36 C266.915,480.36 270.395,477.51 270.395,472.56 C270.395,467.61 266.915,464.76 263.165,464.76 C259.385,464.76 255.905,467.61 255.905,472.56 Z M260.435,472.56 C260.435,469.98 261.335,468.33 263.165,468.33 C264.965,468.33 265.895,469.98 265.895,472.56 C265.895,475.14 264.965,476.79 263.165,476.79 C261.335,476.79 260.435,475.14 260.435,472.56 Z M273.275,474.36 C273.275,478.08 274.655,480.36 277.895,480.36 C279.935,480.36 281.315,479.43 282.575,477.93 L282.665,477.93 L282.995,480 L286.595,480 L286.595,465.12 L282.185,465.12 L282.185,474.96 C281.315,476.13 280.655,476.61 279.545,476.61 C278.255,476.61 277.685,475.92 277.685,473.79 L277.685,465.12 L273.275,465.12 L273.275,474.36 Z"></path>

</svg>
