<?php

define('DEBUG', true);

$args = count($argv) > 1 ? array_slice($argv, 1) : array();

function debug ($msg) {
    if (true === $debug) {
        echo "$msg\n";
    }
}

foreach($args as $file) {

    if (is_file($file)) {

        if (is_readable($file)) {

            $token = '';
            $in_comment = false;
            $rule_stack = array();
            $handle = fopen($file, 'r');

            while (!feof($handle)) {

                $char = fread($handle, 1);

                // debug("char: $char ".($in_comment?'(comment)':''));
                // debug("prev: $prev");
                // debug("token: $token");
                // debug("stack: " . print_r($rule_stack, true));

                switch ($char) {

                    case '{':
                        if (false === $in_comment) {
                            debug("Found start of statement list!");
                            array_push($rule_stack, rtrim(trim($token)));
                            $token = '';
                        }
                    break;

                    case '}':
                        if (false === $in_comment) {
                            debug("Found end of statement list!");
                            array_pop($rule_stack);
                        }
                    break;

                    case ';':
                        if (false === $in_comment) {
                            debug("Found end of rule!");
                            $rules[implode(" ", $rule_stack)][] = rtrim(trim($token)) . ';';
                            $token = '';
                        }
                    break;

                    case '*':
                        if ('/' === $prev) {
                            debug("Found start of comment!");
                            $in_comment = true;
                            $token = substr($token, 0, -1);
                        }
                        else if (false === $in_comment) {
                            debug("Found normal token \"$char\"!");
                            $token .= $char;
                        }
                    break;

                    case '/':
                        if ('*' === $prev) {
                            debug("Found end of comment!");
                            $in_comment = false;
                        }
                        else if (false === $in_comment) {
                            debug("Found normal token \"$char\"!");
                            $token .= $char;
                        }
                    break;

                    case ' ': case "\n": case "\r": case "\t":
                        if (preg_match('/[;{}\/]/', $prev)) {
                            $char = '';
                        }
                        else if (false === $in_comment) {
                            $token .= $char;
                        }
                    break;

                    default:
                        if (false === $in_comment) {
                            debug("Found normal token \"$char\"!");
                            $token .= $char;
                        }
                    break;

                }
            
                if (strlen($char) > 0) {
                    $prev = $char;
                }

                // echo "\n";
            }

            echo "/************* $file *************/\n\n";

            foreach ($rules as $selector => $styles) {
                echo $selector . " {\n\t" . join ("\n\t", $styles) . "\n}\n";
            }
        }
        else {
            echo "$file not readable\n";
        }
    }
    else {
        echo "$file does not exist\n";
    }
}

?>
