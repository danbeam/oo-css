<?php

require_once dirname(dirname(__FILE__)).'/oo_css.php';

class OO_CSS_Parser_Test extends PHPUnit_Framework_TestCase {

    public function setUp () {
        $this->testDir = dirname(__FILE__);
        $this->tests = glob($this->testDir.'/*/*.oocss');
        $this->expected = glob($this->testDir.'/*/*.css');
        $this->proxy = new OO_CSS_Parser();
        $this->croaked = false;
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

    public function testCroaksOnEmptyFileList () {
        $mock = $this->getMock('OO_CSS_Parser', array('croak', 'warn'));
        $mock->expects($this->once())
             ->method('croak')
             ->with($this->equalTo('No files given'));
        $mock->parse();
    }

    public function testWarnIfFileDoesntExist () {
        $file = microtime(true) . '.oocss';
        $mock = $this->getMock('OO_CSS_Parser', array('croak', 'warn', 'fileExists'));
        $mock->expects($this->once())
             ->method('fileExists')
             ->will($this->returnValue(false));
        $mock->expects($this->once())
             ->method('warn')
             ->with($this->equalTo($file . ' not readable'));
        $mock->parse($file);
    }

    public function testWarnIfFileNotReadable () {
        $file = microtime(true) . '.oocss';
        $mock = $this->getMock('OO_CSS_Parser', array('croak', 'warn', 'canRead', 'fileExists'));
        $mock->expects($this->once())
             ->method('fileExists')
             ->will($this->returnValue(true));
        $mock->expects($this->once())
             ->method('canRead')
             ->will($this->returnValue(false));
        $mock->expects($this->once())
             ->method('warn')
             ->with($this->equalTo($file . ' not readable'));
        $mock->parse($file);
    }

}
