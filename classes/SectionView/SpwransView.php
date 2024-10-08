<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SpwransView extends WransView {
    public $major = 8;
    protected $class = 'SPWRANSLIST';

    public function display() {
        global $PAGE;
        if ($spid = get_http_var('spid')) {
            $this->spwrans_redirect($spid);
            $PAGE->page_end();
        } else {
            return parent::display();
        }
    }

    protected function display_front() {
        global $this_page, $DATA;
        $this_page = 'spwransfront';
        $data = [];

        $args = [ 'months' => 1 ];
        $WRANSLIST = new \SPWRANSLIST();

        $wrans = [];
        $wrans['data'] = $WRANSLIST->display('recent_wrans', ['days' => 7, 'num' => 5], 'none');
        $wrans['calendar'] = $WRANSLIST->display('calendar', $args, 'none');

        $data['content'] = $wrans;

        return $data;
    }

    protected function getViewUrls() {
        $urls = [];
        $day = new \MySociety\TheyWorkForYou\Url('spwrans');
        $urls['day'] = $day;
        $urls['spwransday'] = $day;
        return $urls;
    }

    protected function getSearchSections() {
        return [
            [ 'section' => 'spwrans' ],
        ];
    }

    private function spwrans_redirect($spid) {
        global $PAGE;

        # We have a Scottish Parliament ID, need to find the date
        $SPWRANSLIST = new \SPWRANSLIST();
        $gid = $SPWRANSLIST->get_gid_from_spid($spid);
        if ($gid) {
            if (preg_match('/uk\.org\.publicwhip\/spwa\/(\d{4}-\d\d-\d\d\.(.*))/', $gid, $m)) {
                $URL = new \MySociety\TheyWorkForYou\Url('spwrans');
                $URL->reset();
                $URL->insert(['id' => $m[1]]);
                $fragment_identifier = '#g' . $m[2];
                header('Location: ' . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } elseif (preg_match('/uk\.org\.publicwhip\/spor\/(\d{4}-\d\d-\d\d\.(.*))/', $gid, $m)) {
                $URL = new \MySociety\TheyWorkForYou\Url('spdebates');
                $URL->reset();
                $URL->insert(['id' => $m[1]]);
                $fragment_identifier = '#g' . $m[2];
                header('Location: ' . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } else {
                $PAGE->error_message("Strange GID ($gid) for that Scottish Parliament ID.");
            }
        }
        $PAGE->error_message("Couldn't match that Scottish Parliament ID to a GID.");
    }

    protected function get_question_mentions_html($row_data) {
        if(count($row_data) == 0) {
            return '';
        }
        $result = '';
        $last_date = null;
        $first_difference_output = true;
        // Keep the references until after the history that's in a timeline:
        $references = [];
        foreach ($row_data as $row) {
            if(! $row["date"]) {
                // If this mention isn't associated with a date, the difference won't be interesting.
                $last_date = null;
            }
            $description = '';
            if ($last_date && ($last_date != $row["date"])) {
                // Calculate how long the gap was in days:
                $daysdiff = (int) ((strtotime($row["date"]) - strtotime($last_date)) / 86400);
                $daysstring = ($daysdiff == 1) ? "day" : "days";
                $further = "";
                if($first_difference_output) {
                    $first_difference_output = false;
                } else {
                    $further = " a further";
                }
                $description = "\n<span class=\"question-mention-gap\">After$further $daysdiff $daysstring,</span> ";
            }
            $reference = false;
            $inner = "BUG: Unknown mention type $row[type]";
            $date = format_date($row['date'], SHORTDATEFORMAT);
            $url = $row['url'];
            if (strpos($url, 'business/businessBulletin') !== false) {
                $url = str_replace('www.scottish', 'archive.scottish', $url);
            }
            switch ($row["type"]) {
                case 1:
                    $inner = "Mentioned in <a class=\"debate-speech__meta__link\" href=\"$url\">today's business on $date</a>";
                    break;
                case 2:
                    $inner = "Mentioned in <a class=\"debate-speech__meta__link\" href=\"$url\">tabled oral questions on $date</a>";
                    break;
                case 3:
                    $inner = "Mentioned in <a class=\"debate-speech__meta__link\" href=\"$url\">tabled written questions on $date</a>";
                    break;
                case 4:
                    if(preg_match('/^uk.org.publicwhip\/spq\/(.*)$/', $row['gid'], $m)) {
                        $URL = new \MySociety\TheyWorkForYou\Url("spwrans");
                        $URL->insert(['spid' => $m[1]]);
                        $relative_url = $URL->generate("none");
                        $inner = "Given a <a class=\"debate-speech__meta__link\" href=\"$relative_url\">written answer on $date</a>";
                    }
                    break;
                case 5:
                    $inner = "Given a holding answer on $date";
                    break;
                case 6:
                    if(preg_match('/^uk.org.publicwhip\/spor\/(.*)$/', $row['mentioned_gid'], $m)) {
                        $URL = new \MySociety\TheyWorkForYou\Url("spdebates");
                        $URL->insert(['id' => $m[1]]);
                        $relative_url = $URL->generate("none");
                        $inner = "<a href=\"$relative_url\">Asked in parliament on $date</a>";
                    }
                    break;
                case 7:
                    if(preg_match('/^uk.org.publicwhip\/spq\/(.*)$/', $row['mentioned_gid'], $m)) {
                        $referencing_spid = $m[1];
                        $URL = new \MySociety\TheyWorkForYou\Url("spwrans");
                        $URL->insert(['spid' => $referencing_spid]);
                        $relative_url = $URL->generate("none");
                        $inner = "Referenced in <a href=\"$relative_url\">question $referencing_spid</a>";
                        $reference = true;
                    }
                    break;
            }
            if($reference) {
                $references[] = "\n<li>$inner.";
            } else {
                $result .= "\n<li class=\"link-to-hansard\">$description$inner</span>";
                $last_date = $row["date"];
            }
        }
        foreach ($references as $reference_span) {
            $result .= $reference_span;
        }
        $result .= '';
        return $result;
    }

}
