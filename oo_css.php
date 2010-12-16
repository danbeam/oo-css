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
        'id' => "default",
        'b1' => "%s {\n    ",
        'r'  => "\n    ",
        'b2' => "\n}\n",
        'm'  => ", ",
        'd'  => '/[;{},]/',
        's'  => ': ',
    );

    /**
    * @method   OO_CSS_Parser::__construct
    * @access   public
    * Instantiation and configuration
    */
    public function __construct($style = '1tbs') {
        $format =& self::$format;
        switch ($style) {
            case 'minified': case 'min': case 'minned':
                $format = array(
                    'id' => "minned",
                    'b1' => "%s{",
                    'r'  => "",
                    'b2' => "}",
                    'm'  => ",",
                    'd'  => '/[:();{},]/',
                    's'  => ':',
                );
            break;
            case 'allman':
                $format = array(
                    'id' => "allman",
                    'b1' => "%s\n{\n    ",
                    'r'  => "\n    ",
                    'b2' => "\n}\n",
                    'm'  => ", ",
                    'd'  => '/[;{},]/',
                    's'  => ': ',
                );
            break;
            case 'oneline': case 'one-line': case 'oneliner': case 'one-liner':  
                $format = array(
                    'id' => "oneline",
                    'b1' => "%s { ",
                    'r'  => " ",
                    'b2' => " }\n",
                    'm'  => ", ",
                    'd'  => '/[;{},]/',
                    's'  => ': ',
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
        echo "$msg\n";
        return $this;
    }

    /**
    * @method   OO_CSS_Parser::warn
    * @access   public
    * For warnings like files not being found or readable
    */
    public function warn ($msg = "") {
        file_put_contents('php://stderr', "$msg\n", FILE_APPEND);
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
                $new[] = $arg;
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
            if (WARN) self::warn("No files given"); exit(1);
        }

        // set up global to hold rendered content
        $results = array();

        // set up alias for convenience
        $format =& self::$format;

        // this will work with only 1 file, too
        foreach($files as $file) {

            if (is_file($file)) {

                if (is_readable($file)) {

                    // set up some primitives to help us out
                    $line_num = 0;
                    $token = $prev = $rules = $value = '';
                    $rule_stack = $block = array();
                    $in_comment = false;

                    // open a handle to our file
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
                                if (!$in_comment) {
                                    if (DEBUG) self::debug("Found start of statement list!");
                                    if (empty($rule_stack)) {
                                        $rule_stack[] = implode($format['m'], array_map('rtrim', array_map('trim', explode(',', substr($token, 0, -1)))));
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
                                        $rule_stack[] = implode($format['m'], $computed);
                                    }
                                    $token = '';
                                }
                            break;
        
                            case '}':
                                if (!$in_comment) {
                                    if (DEBUG) self::debug("Found end of statement list!");
                                    // check for one last rule without a ; (valid at the end of blocks)
                                    $rule_split = explode(':', $token);
                                    if (isset($rule_split[1])) {
                                        $rules = array_map('trim', array_map('rtrim', explode(',', $rule_split[0])));
                                        $value = rtrim(trim(substr($rule_split[1], 0, -1).';'));
                                        $recent_rule = end($rule_stack);
                                        foreach ($rules as $rule) {
                                            $block[$recent_rule][] = array('rule' => $rule, 'value' => $value);
                                        }
                                    }
                                    array_pop($rule_stack);
                                    $token = '';
                                }
                            break;
        
                            case ';':
                                if (!$in_comment) {
                                    if (DEBUG) self::debug("Found end of rule!");
                                    if (false === ($colon_pos = strpos($token, ':'))) {
                                        //self::warn(print_r($rule_split, true));
                                        if (WARN) self::warn('Syntax error around line #'.$line_num);
                                        exit(1);
                                    }
                                    $rules = array_map('trim', array_map('rtrim', explode(',', substr($token, 0, $colon_pos))));
                                    $value = rtrim(trim(substr($token, $colon_pos + 1)));
                                    $recent_rule = end($rule_stack);
                                    foreach ($rules as $rule) {
                                        $block[$recent_rule][] = array('rule' => $rule, 'value' => $value);
                                    }
                                    $token = '';
                                }
                            break;
        
                            case '*':
                                if (DEBUG) self::debug("Found normal token \"$char\"!");
                                if ('/' === $prev) {
                                    if (DEBUG) self::debug("Found start of comment!");
                                    $in_comment = true;
                                }
                            break;
        
                            case '/':
                                if (DEBUG) self::debug("Found normal token \"$char\"!");
                                if ('*' === $prev) {
                                    $in_comment = false;
                                    if (DEBUG) self::debug("Found end of comment!");
                                    if ('/**/' !== $token || '/*\*/' !== $token) {
                                        $token = '';
                                    }
                                }
                            break;
        
                            case ' ': case "\t":
                                if (!$in_comment) {
                                    if (preg_match($format['d'], $prev)) {
                                        $token = trim($token);
                                        $char = '';
                                    }
                                }
                            break;
        
                            case "\n": case "\r":
                                ++$line_num;
                                if (DEBUG) self::debug("On line #$line_num");
                                if (!$in_comment) {
                                    if (preg_match($format['d'], $prev)) {
                                        $token = rtrim($token);
                                        $char = '';
                                    }
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
                    
                    foreach ($block as $selector => $statement) {
                        $rule_map = $rules = array();
                        // eliminate duplicate rules
                        foreach ($statement as $rule) {
                            if (!isset($rule_map[$rule['rule']])) {
                                $rule_map[$rule['rule']] = array();
                            }
                            $rule_map[$rule['rule']][] = $rule['value'];
                        }
                        // alphabetize rules (why we needed to de-dupe)
                        ksort($rule_map);
                        // .reconstruct-the {inside:"of a block";}
                        foreach ($rule_map as $attr => $vals) {
                            foreach ($vals as $val) {
                                $rules[] = $attr.$format['s'].$val;
                            }
                        }
                        // if minned, take off the last ; in the {block}
                        if ('minned' === $format['id']) {
                            $rules[] = substr(array_pop($rules), 0, -1);
                        }
                        // if it's .not-empty {}
                        if (!empty($rules)) {
                            // add and format the results
                            $results[] = str_replace(
                                array('%s', '%b1', '%b2'),
                                array($selector, $format['b1'], $format['b2']),
                                $format['b1'] . implode($format['r'], $rules) . $format['b2']
                            );
                        }
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
            return implode('', $results);
        }
    }
}

// I have no idea why $argv (ini setting register_argc_argv) is different from $_SERVER['argv']
$argv_norm = isset($argv) ? array_slice($argv, 1) : array_slice($_SERVER['argv'], 2);

// if we're on the CLI, check for arguments
if ('cli' === php_sapi_name() && count($argv_norm) > 0) {
    // get options from CLI args
    $args = getopt('s:');
    // if we found a style, remove those args
    if (isset($args['s'])) {
        array_splice($argv_norm, array_search('-s', $argv_norm), 2);
        // create an instance
        $parser = new OO_CSS_Parser($args['s']);
    }
    else {
        // create an instance
        $parser = new OO_CSS_Parser();
    }
    // output warning to stderr so normal > redirection doesn't work
    //if (WARN) $parser->warn('Found arguments on CLI, parsing...');
    // parse all arguments passed in on CLI
    ob_start(); echo $parser->parse($argv_norm); ob_end_flush();
}
