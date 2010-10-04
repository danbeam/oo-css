OO-CSS
======

An object-oriented CSS parser to slightly simplify the lives of web developers everywhere.

How do I use this?
------------------

Currently, this project is written in object-oriented PHP5, so first you'll need some kind of environment that can run this.

### Including as a class

If you simply `require`, `include`, `require_once`, or `include_once` the oo_css.php file, you will have access to the OO_CSS_Parser global, which contains methods:

* parse
* warn  (if WARN constant is truthy)
* debug (if DEBUG constant is truthy)

This can be done with an instance of OO_CSS_Parser, like so:

    $oo_css = new OO_CSS_Parser();
    $oo_css->parse('some/files/to/go/to.css');

### Using statically

Or alternately can be called statically (without an instance), like so:

    OO_CSS_Parser::parse('a/file.css');

### Using PHP on the command line

And lastly, for those CLI junkies like myself, you can run it from the command-line (it assumes an arguments that are gotten and are readable should at least attempt to be transformed by this class).  This means you can easily integrate this into automated builds (along with minification or static gzipping or whatever strikes you fancy).

Here's how you'd use it:

    cd to/path/where/i/cloned/oo-php
    ./oo-php css/*.css
