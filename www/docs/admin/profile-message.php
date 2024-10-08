<?php

include_once '../../includes/easyparliament/init.php';
$this_page = 'profile_message';
$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();

print get_http_var('submit') ? submit_message() : display_message_form();

$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();

function person_drop_down() {
    global $db;
    $out = '
<div class="row">
<span class="label"><label for="form_pid">Person:</label></span>
<span class="formw"><select id="form_pid" name="pid" class="autocomplete">
';
    $query = 'SELECT house, member.person_id, title, given_name, family_name, lordofname, constituency, party
        FROM member, person_names,
        (SELECT person_id, MAX(end_date) max_date FROM person_names WHERE type="name" GROUP by person_id) md
        WHERE house>0 AND member.person_id = person_names.person_id AND person_names.type = "name"
        AND md.person_id = person_names.person_id AND md.max_date = person_names.end_date
        GROUP by person_id
        ORDER BY house, family_name, lordofname, given_name
    ';
    $q = $db->query($query);

    $houses = [1 => 'MP', 'Lord', 'MLA', 'MSP'];

    foreach ($q as $row) {
        $p_id = $row['person_id'];
        $house = $row['house'];
        $desc = member_full_name($house, $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']) .
                " " . $houses[$house];
        if ($row['party']) {
            $desc .= ' (' . $row['party'] . ')';
        }
        if ($row['constituency']) {
            $desc .= ', ' . $row['constituency'];
        }
        $out .= '<option value="' . $p_id . '">' . $desc . '</option>' . "\n";
    }

    $out .= ' </select></span> </div> ';

    return $out;
}

function submit_message() {
    global $db;

    $pid = intval(get_http_var('pid'));
    $message = get_http_var('profile_message');

    if (!$pid) {
        return display_message_form(['Please pick a person']);
    }

    $query = "INSERT INTO personinfo (person_id, data_key, data_value) VALUES
            ($pid,'profile_message',:profile_message)
        ON DUPLICATE KEY UPDATE data_value=VALUES(data_value)";
    $db->query($query, [':profile_message' => $message]);

    $person = new MySociety\TheyWorkForYou\Member([
        'person_id' => $pid]);
    $person->load_extra_info(true, true);

    return "<p><em>Profile message set for $pid</em> &mdash; check how it looks <a href=\"/mp?p=$pid\">on their page</a></p>"
        . display_message_form();
}

function display_message_form($errors = []) {
    $out = '';
    if ($errors) {
        $out .= '<ul class="error"><li>' . join('</li><li>', $errors) . '</li></ul>';
    }
    $out .= '<form method="post">';
    $out .= person_drop_down();
    $out .= <<<EOF
        <div class="row">
            <span class="label"><label for="profile_message">Profile message:</label></span>
            <span class="formw"><textarea name="profile_message" id="profile_message" rows="5" cols="50"></textarea></span>
        </div>
        <div class="row">
            <span class="label">&nbsp;</span>
            <span class="formw"><input type="submit" name="submit" value="Update"></span>
        </div>
        </form>
        EOF;
    return $out;
}
