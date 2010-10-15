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
    * @access   protected
    * Various formatting aspects of our output
    */
    protected static $format = array(
        'b1' => "%s {\n    ",
        'r'  => "\n    ",
        'b2' => "\n}\n",
        'm'  => ", ",
        'd'  => '/[;{},]/',
    );

    /**
    * @method   OO_CSS_Parser::__construct
    * @access   public
    * Instantiation and configuration
    */
    public function __construct($style = '1tbs') {
        $format =& self::$format;
        switch ($style) {
            case 'minified':
                $format = array(
                    'b1' => "%s{",
                    'r'  => "",
                    'b2' => "}",
                    'm'  => ",",
                    'd'  => '/[:();{},]/',
                );
            break;
            case 'allman':
                $format = array(
                    'b1' => "%s\n{\n    ",
                    'r'  => "\n    ",
                    'b2' => "\n}\n",
                    'm'  => ", ",
                    'd'  => '/[;{},]/',
                );
            break;
            case 'oneline': case 'one-line': case 'oneliner': case 'one-liner':  
                $format = array(
                    'b1' => "%s { ",
                    'r'  => " ",
                    'b2' => " }\n",
                    'm'  => ", ",
                    'd'  => '/[;{},]/',
                );
            break;
            case '0tbs': case '1tbs': default:
                // stick with default
            break;
        }
    }

    /**
    * @method   OO_CSS_Parser::debug
    * @access   public
    * For generic debug messages that require higher verbosity
    */
    public function debug ($msg = "") {
        if (true === DEBUG) {
            echo "$msg\n";
        }
        return $this;
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
        return $this;
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
                self::flatten($arg, $new);
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
        $files = self::flatten(func_get_args());

        // if no files, what to do?
        if (empty($files)) {
            trigger_error("No files given");
        }

        // set up global to hold rendered content
        $results = array();

        // set up alias for convenience
        $format =& self::$format;

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

                        //if (DEBUG) {
                        //    self::debug("char: $char ".($in_comment?'(comment)':''));
                        //    self::debug("prev: $prev");
                        //    self::debug("token: $token");
                        //    self::debug("stack: " . print_r($rule_stack, true));
                        //}
        
                        // main loop
                        switch ($char) {
        
                            case '{':
                                if (DEBUG) self::debug("Found start of statement list!");
                                if (empty($rule_stack)) {
                                    array_push($rule_stack, implode($format['m'], array_map('rtrim', array_map('trim', explode(',', substr($token, 0, -1))))));
                                }
                                else {
                                    $computed = array();
                                    $parents  = array_map('rtrim', array_map('trim', explode(',', end($rule_stack))));
                                    $children = array_map('rtrim', array_map('trim', explode(',', substr($token, 0, -1))));
                                    //var_dump(array('rule_stack' => $rule_stack, 'parents' => $parents, 'children' => $children));
                                    foreach ($parents as $parent) {
                                        foreach ($children as $child) {
                                            $computed[] = $parent.' '.$child;
                                        }
                                    }
                                    array_push($rule_stack, implode($format['m'], $computed));
                                }
                                $token = '';
                            break;
        
                            case '}':
                                if (DEBUG) self::debug("Found end of statement list!");
                                array_pop($rule_stack);
                                $token = '';
                            break;
        
                            case ';':
                                if (DEBUG) self::debug("Found end of rule!");
                                list($rules, $value) = explode(':', $token);
                                $rules = array_map('trim', array_map('rtrim', explode(',', $rules)));
                                $value = trim($value);
                                foreach ($rules as $rule) {
                                    $block[end($rule_stack)][] = $rule.': '.$value;
                                }
                                $token = '';
                            break;
        
                            case '*':
                                if ('/' === $prev) {
                                    if (DEBUG) self::debug("Found start of comment!");
                                    $in_comment = true;
                                }
                                if (DEBUG) self::debug("Found normal token \"$char\"!");
                            break;
        
                            case '/':
                                if ('*' === $prev) {
                                    if (DEBUG) self::debug("Found end of comment!");
                                    $in_comment = false;
                                    if ('/**/' !== $token) {
                                        $token = '';
                                    }
                                }
                                if (DEBUG) self::debug("Found normal token \"$char\"!");
                            break;
        
                            case ' ': case "\t":
                                if (preg_match($format['d'], $prev)) {
                                    $token = trim($token);
                                    $char = '';
                                }
                            break;
        
                            case "\n": case "\r":
                                if (preg_match($format['d'], $prev)) {
                                    $token = rtrim($token);
                                    $char = '';
                                }
                            break;

                            default:
                                if (DEBUG) self::debug("Found normal token \"$char\"!");
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
                    
                    foreach ($block as $selector => $rules) {
                        $result[] = str_replace(
                                        array('%s', '%b1', '%b2'),
                                        array($selector, $format['b1'], $format['b2']),
                                        $format['b1'].join($format['r'], $rules).$format['b2']
                                    );
                    }

                    $results[] = implode('', $result);
                }
                else {
                    if (WARN) self::warn("$file not readable");
                }
            }
            else {
                if (WARN) self::warn("$file not readable");
            }
        }
        if (!empty($results)) {
            return implode("\n", $results);
        }
    }
}

// if we're on the CLI, check for arguments
if ('cli' === php_sapi_name() && $argc > 1) {
    // get options from CLI args
    $args = getopt('s:');
    // if we found a style, remove those args
    if ($args['s']) {
        array_splice($argv, array_search('-s', $argv), 2);
    }
    // create an instance
    $parser = new OO_CSS_Parser($args['s']);
    // output warning to stderr so normal > redirection doesn't work
    $parser->warn('Found arguments on CLI, parsing...');
    // parse all arguments passed in on CLI
    ob_start(); echo $parser->parse(array_slice($argv, 1)); ob_end_flush();
}
