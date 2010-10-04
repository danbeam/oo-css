<?php

define('DEBUG', false);
define('WARN', true);

/**
* @access   public
* @author   Dan Beam <dan@danbeam.org>
* A simple CSS lexer / translator from OO CSS -> CSS
*/
class OO_CSS_Parser {

    /**
    * @method   OO_CSS_Parser::debug
    * @access   protected
    * For generic debug messages that require higher verbosity
    */
    public function debug ($msg = "") {
        if (true === DEBUG) {
            echo "$msg\n";
        }
    }


    /**
    * @method   OO_CSS_Parser::warn
    * @access   protected
    * For warnings like files not being found or readable
    */
    public function warn ($msg = "") {
        if (true === WARN) {
            file_put_contents('php://stderr', "$msg\n", FILE_APPEND);
        }
    }


    /**
    * @method   OO_CSS_Parser::flatten
    * @access   protected
    * @param    possibly multi-dimensional array
    * @return   flattened array
    * This is a helper method that flattens all the arguments given to the parse() method at the moment
    */
    protected function flatten ($args, &$new = array()) {
        // recursively iterate through args
        foreach ($args as $arg) {
            // recurse
            if (is_array($arg)) {
                $this->flatten($arg, $new);
            }
            // base case
            else {
                array_push($new, $arg);
            }
        }
        // return flattened array
        return $new;
    }


    /**
    * @method   OO_CSS_Parser::parse
    * @access   protected
    * @param    any kind of multi-dimensional array or any number of paramaters
    * @return   un-Object-Oriented CSS for use in browsers
    * Returns the output of the de-OO-ing of the OO CSS
    */
    public function parse () {

        // flatten args to allow different ways of calling thi method
        $files = $this->flatten(func_get_args());

        // set up global to hold rendered content
        $results = array();

        // if no files, what to do?
        if (empty($files)) {
            trigger_error("No files given");
        }

        // this will work with only 1 file, too
        foreach($files as $file) {

            if (is_file($file)) {

                if (is_readable($file)) {

                    // set up some pseudo-globals to help us
                    $token = '';
                    $in_comment = false;
                    $rule_stack = array();
                    $handle = fopen($file, 'r');

                    // old school way
                    while (!feof($handle)) {

                        // read a char at a time
                        $token .= ($char = fread($handle, 1));

                        //$this->debug("char: $char ".($in_comment?'(comment)':''));
                        //$this->debug("prev: $prev");
                        //$this->debug("token: $token");
                        //$this->debug("stack: " . print_r($rule_stack, true));
        
                        // main loop
                        switch ($char) {
        
                            case '{':
                                $this->debug("Found start of statement list!");
                                array_push($rule_stack, trim(substr($token, 0, -1)));
                                $token = '';
                            break;
        
                            case '}':
                                $this->debug("Found end of statement list!");
                                array_pop($rule_stack);
                                $token = '';
                            break;
        
                            case ';':
                                $this->debug("Found end of rule!");
                                $rules[implode(" ", $rule_stack)][] = trim($token);
                                $token = '';
                            break;
        
                            case '*':
                                if ('/' === $prev) {
                                    $this->debug("Found start of comment!");
                                    $in_comment = true;
                                }
                                $this->debug("Found normal token \"$char\"!");
                            break;
        
                            case '/':
                                if ('*' === $prev) {
                                    $this->debug("Found end of comment!");
                                    $in_comment = false;
                                    var_dump($token);
                                    if ('/**/' !== $token) {
                                        $token = '';
                                    }
                                }
                                $this->debug("Found normal token \"$char\"!");
                            break;
        
                            case ' ': case "\n": case "\r": case "\t":
                                if (preg_match('/[;{}\/]/', $prev)) {
                                    $token = rtrim(trim($token));
                                    $char = '';
                                }
                            break;
        
                            default:
                                $this->debug("Found normal token \"$char\"!");
                            break;
        
                        }
                    
                        if (isset($char{0})) {
                            $prev = $char;
                        }
                    }
        
                    if (count($files) > 1) {
                        $result .= "/* $file */\n\n";
                    }

                    $result = '';
        
                    foreach ($rules as $selector => $styles) {
                        $result .= $selector . " {\n    " . join ("\n    ", $styles) . "\n}\n";
                    }

                    $results[] = $result;
                }
                else {
                    $this->warn("$file not readable");
                }
            }
            else {
                $this->warn("$file not readable");
            }
        }
        if (!empty($results)) {
            return implode("\n", $results);
        }
    }
}

// if we're on the CLI, check for arguments
if ('cli' === php_sapi_name() && $argc > 1) {
    // create a parser lazily
    $parser = new OO_CSS_Parser();
    // output this to stderr so normal > redirection doesn't work
    $parser->warn('Found arguments on CLI, parsing...');
    // look through args
    for ($i = 1; $i < $argc; ++$i) {
        // if the file actually exists
        if (is_file($argv[$i])) {
            // and it's readable
            if (is_readable($argv[$i])) {
                // just output to stdout atm
                echo $parser->parse($argv[$i]);
            }
            else {
                $parser->warn($argv[$i] . " isn't readable, skipping.");
            }
        }
        else {
            $parser->warn($argv[$i] . " doesn't exist, skipping.");
        }
    }
}
