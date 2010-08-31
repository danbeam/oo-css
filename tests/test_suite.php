<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)).'/oo_css.php';

class OO_CSS_Tests extends PHPUnit_Framework_TestCase {

    public function setUp () {
        $this->tests = glob(dirname(__FILE__).'/*/*.test');
        $this->expected = glob(dirname(__FILE__).'/*/*.expected');
        $this->proxy = new OO_CSS_Parser();
    }

    public function tearDown () {
        $this->tests = null;
        $this->expected = null;
        $this->proxy = null;
    }

    public function testExpected () {
        echo "\n" . str_pad(__METHOD__, 60);
        foreach ($this->tests as $test) {
            $this->assertTrue(
                in_array(substr($test, 0, -5) . ".expected", $this->expected),
                "$test doesn't have an expected result to compare to!"
            );
        }
    }

    public function testAll () {
        echo "\n" . str_pad(__METHOD__, 60);
        foreach ($this->tests as $test) {
            $this->assertEquals(
                $this->proxy->parse($test),
                file_get_contents(substr($test, 0, -5).".expected"),
                str_replace('_', ' ', ucfirst(substr(basename($test), 0, -5)))
            );
        }
    }
}

?>
