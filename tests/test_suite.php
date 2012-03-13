<?php

define('TESTING', true);

require_once dirname(dirname(__FILE__)).'/oo_css.php';

class OO_CSS_Parser_Test extends PHPUnit_Framework_TestCase {

    public function setUp () {
        $this->testDir = dirname(__FILE__);
        $this->tests = glob($this->testDir.'/*/*.oocss');
        $this->expected = glob($this->testDir.'/*/*.css');
        $this->proxy = new OO_CSS_Parser();
        $this->croaked = false;
    }

    public function isFormatTest ($file) {
        return false !== strpos($file, implode(DIRECTORY_SEPARATOR, array('', 'tests', 'format', '')));
    }

    public function testExpected () {
        foreach ($this->tests as $test) {
            if ($this->isFormatTest($test)) {
                continue;
            }
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
            if ($this->isFormatTest($test)) {
                continue;
            }
            $this->assertEquals(
                file_get_contents(substr($test, 0, -6).".css"),
                $this->proxy->parse($test),
                $this->fileToDesc($test)
            );
        }
    }

    private function execWithArgs ($args) {
        $parts = array_merge(
            array('php', dirname(dirname(__FILE__)) . '/oo_css.php'),
            $args,
            array('2>&1')
        );
        $cmd = implode(' ', $parts);
        exec($cmd, $output, $status);
        return array(
            'command' => $cmd,
            'output' => implode("\n", $output),
            'status' => $status,
        );
    }

    public function testAllCLI () {
        foreach ($this->tests as $test) {
            if ($this->isFormatTest($test)) {
                continue;
            }
            $results = $this->execWithArgs(array($test));
            // exec to emulate PHP CLI use
            $this->assertEquals(
                trim(file_get_contents(substr($test, 0, -6).".css")),
                $results['output'],
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
            $this->assertThat($result, $this->stringContains('/* ' . $file . ' */'));
        }
    }

    public function testCroaksOnEmptyFileList () {
        $mock = $this->getMock('OO_CSS_Parser', array('croak', 'warn'));
        $mock->expects($this->once())
             ->method('croak')
             ->with($this->equalTo('No files given'));
        $mock->parse();
    }

    public function testNonZeroExitStatus () {
        $results = $this->execWithArgs(array());
        $this->assertEquals(1, $results['status']);
        $this->assertEquals('No files given', $results['output']);

        $fakeOption = '-zZzZz';
        $results = $this->execWithArgs(array($fakeOption));
        $this->assertEquals(1, $results['status']);
        $this->assertEquals('Unrecognized option ' . $fakeOption, $results['output']);
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

    public function formatFile ($file, $format) {
        return substr($file, 0, -6) . ".$format.css";
    }

    public function testFormats () {
        foreach ($this->tests as $test) {
            if ($this->isFormatTest($test)) {
                foreach (array('minned', 'allman', '1tbs', 'oneline') as $format) {
                    $parser = new OO_CSS_Parser($format);
                    $this->assertEquals(
                        file_get_contents($this->formatFile($test, $format)),
                        $parser->parse($test)
                    );
                }
            }
        }
    }

    public function testDebugMessages () {
        $msg = 'blah blee blar';
        ob_start();
        $this->proxy->debug($msg);
        $this->assertEquals($msg . "\n", ob_get_clean());
    }
}
