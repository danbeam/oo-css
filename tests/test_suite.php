<?php

require_once dirname(dirname(__FILE__)).'/oo_css.php';

class OO_CSS_Tests extends PHPUnit_Framework_TestCase {

    public function setUp () {
        $this->testDir = dirname(__FILE__);
        $this->tests = glob($this->testDir.'/*/*.oocss');
        $this->expected = glob($this->testDir.'/*/*.css');
        $this->proxy = new OO_CSS_Parser();
    }

    public function tearDown () {
        $this->tests = null;
        $this->expected = null;
        $this->proxy = null;
    }

    public function testExpected () {
        foreach ($this->tests as $test) {
            $this->assertTrue(
                in_array(substr($test, 0, -6) . ".css", $this->expected),
                "$test doesn't have an expected result to compare to!"
            );
        }
    }

    public function fileToDesc ($file) {
        return str_replace('_', ' ', ucfirst(basename(dirname($file))) . ' > ' . ucfirst(substr(basename($file), 0, -6)));
    }

    public function testAll () {
        foreach ($this->tests as $test) {
            $this->assertEquals(
                file_get_contents(substr($test, 0, -6).".css"),
                $this->proxy->parse($test),
                $this->fileToDesc($test)
            );
        }
    }

    public function testAllCLI () {
        foreach ($this->tests as $test) {
            // reset this to blank string every time
            $actual = '';
            // exec to emulate PHP CLI use
            exec('php ' . dirname(dirname(__FILE__)) . '/oo_css.php ' . $test . ' 2>/dev/null', $actual);
            $this->assertEquals(
                rtrim(file_get_contents(substr($test, 0, -6).".css")),
                implode("\n", $actual),
                $this->fileToDesc($test)
            );
        }
    }

    public function testMultipleFiles () {
        $files = array(
            $this->testDir.'/new/multi_to_multi.oocss',
            $this->testDir.'/new/multiple_rules.oocss',
        );
        $result = $this->proxy->parse($files);
        foreach ($files as $file) {
            $this->assertThat($result, $this->stringcontains('/* ' . $file . ' */'));
        }
    }
}
