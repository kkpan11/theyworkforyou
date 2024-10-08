<?php

include_once dirname(__FILE__) . '/api_getPerson.php';

function api_getLord_front() {
    ?>
<p><big>Fetch a particular Lord.</big></p>

<h4>Arguments</h4>
<dl>
<dt>id (optional)</dt>
<dd>If you know the person ID for the Lord you want, this will return data for that person.</dd>
</dl>

<?php
}

function api_getLord_id($id) {
    return api_getPerson_id($id, HOUSE_TYPE_LORDS);
}
