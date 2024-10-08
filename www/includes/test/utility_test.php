<?php
/*
 * SimpleTest tests for the functions in utility.php
 * $Id: utility_test.php,v 1.3 2009-06-25 12:23:58 louise Exp $
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);
include_once dirname(__FILE__) . '/../../../conf/general';
include_once '../utility.php';
include_once 'simpletest/unit_tester.php';
include_once 'simpletest/reporter.php';

class UtilityTest extends UnitTestCase {
    public function testVerpEnvelopeSenderCanCreateStandardSender() {
        $sender = twfy_verp_envelope_sender('aperson@a.nother.dom');
        $expected_sender = 'twfy+aperson=a.nother.dom@' + EMAILDOMAIN;
        $this->assertEqual($sender, $expected_sender, 'verp_envelope_sender can create a sender for a standard address');
    }

}

$test = new UtilityTest();
$test->run(new TextReporter());
