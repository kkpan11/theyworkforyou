<?php

include_once INCLUDESPATH . 'easyparliament/member.php';
include_once dirname(__FILE__) . '/api_getPerson.php';

function api_getMSP_front() {
    ?>
<p><big>Fetch a particular MSP.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode (optional)</dt>
<dd>Fetch the MSPs for a particular postcode.</dd>
<dt>constituency (optional)</dt>
<dd>The name of a constituency.</dd>
<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMSPs or elsewhere), this will return data for that person.</dd>

<dt>always_return (optional)</dt>
<dd>For the postcode and constituency options, sets whether to always try and
return an MSP (due to e.g. the period before an election when there are no
MSPs).</dd>

</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
  &lt;/twfy&gt;
</pre>

<?php
}

function api_getMSP_id($id) {
    return api_getPerson_id($id, HOUSE_TYPE_SCOTLAND);
}

function api_getMSP_postcode($pc) {
    api_getPerson_postcode($pc, HOUSE_TYPE_SCOTLAND);
}

function api_getMSP_constituency($constituency) {
    api_getPerson_constituency($constituency, HOUSE_TYPE_SCOTLAND);
}
