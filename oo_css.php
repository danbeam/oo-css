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
    * @access   public
    * For generic debug messages that require higher verbosity
    */
    public function debug ($msg = "") {
        if (true === DEBUG) {
            echo "$msg\n";
        }
    }


    /**
    * @method   OO_CSS_Parser::warn
    * @access   public
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
                OO_CSS_Parser::flatten($arg, $new);
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
        $files = OO_CSS_Parser::flatten(func_get_args());

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

                        //OO_CSS_Parser::debug("char: $char ".($in_comment?'(comment)':''));
                        //OO_CSS_Parser::debug("prev: $prev");
                        //OO_CSS_Parser::debug("token: $token");
                        //OO_CSS_Parser::debug("stack: " . print_r($rule_stack, true));
        
                        // main loop
                        switch ($char) {
        
                            case '{':
                                OO_CSS_Parser::debug("Found start of statement list!");
                                if (empty($rule_stack)) {
                                    array_push($rule_stack, rtrim(trim(substr($token, 0, -1))));
                                }
                                else {
                                    $computed = array();
                                    $parents  = array_map('rtrim', array_map('trim', explode(',', implode(' ', $rule_stack))));
                                    $children = array_map('rtrim', array_map('trim', explode(',', substr($token, 0, -1))));
                                    foreach ($parents as $parent) {
                                        foreach ($children as $child) {
                                            $computed[] = $parent.' '.$child;
                                        }
                                    }
                                    array_push($rule_stack, implode(', ', $computed));
                                }
                                $token = '';
                            break;
        
                            case '}':
                                OO_CSS_Parser::debug("Found end of statement list!");
                                array_pop($rule_stack);
                                $token = '';
                            break;
        
                            case ';':
                                OO_CSS_Parser::debug("Found end of rule!");
                                $rules[end($rule_stack)][] = trim($token);
                                $token = '';
                            break;
        
                            case '*':
                                if ('/' === $prev) {
                                    OO_CSS_Parser::debug("Found start of comment!");
                                    $in_comment = true;
                                }
                                OO_CSS_Parser::debug("Found normal token \"$char\"!");
                            break;
        
                            case '/':
                                if ('*' === $prev) {
                                    OO_CSS_Parser::debug("Found end of comment!");
                                    $in_comment = false;
                                    if ('/**/' !== $token) {
                                        $token = '';
                                    }
                                }
                                OO_CSS_Parser::debug("Found normal token \"$char\"!");
                            break;
        
                            case ' ': case "\n": case "\r": case "\t":
                                if (preg_match('/[;{}\/]/', $prev)) {
                                    $token = rtrim(trim($token));
                                    $char = '';
                                }
                            break;
        
                            default:
                                OO_CSS_Parser::debug("Found normal token \"$char\"!");
                            break;
        
                        }
                    
                        if (isset($char{0})) {
                            $prev = $char;
                        }
                    }
        
                    $result = array();

                    if (count($files) > 1) {
                        $result[] = "/* $file */\n\n";
                    }
        
                    foreach ($rules as $selector => $styles) {
                        $result[] = $selector . " {\n    " . join ("\n    ", $styles) . "\n}\n";
                    }

                    $results[] = implode('', $result);
                }
                else {
                    OO_CSS_Parser::warn("$file not readable");
                }
            }
            else {
                OO_CSS_Parser::warn("$file not readable");
            }
        }
        if (!empty($results)) {
            return implode("\n", $results);
        }
    }
}

// if we're on the CLI, check for arguments
if ('cli' === php_sapi_name() && $argc > 1) {
    // output this to stderr so normal > redirection doesn't work
    OO_CSS_Parser::warn('Found arguments on CLI, parsing...');
    // parse all arguments passed in on CLI
    ob_start(); echo OO_CSS_Parser::parse(array_slice($argv, 1)); ob_end_flush();
}
