OO-CSS
======

An object-oriented CSS parser to slightly simplify the lives of web developers everywhere.

How do I use this?
------------------

### Setting up environment

Currently, this project is written in object-oriented PHP5.  Until anyone cares enough for me to port it, it'll probably stay that way.

So, first you'll need some kind of environment that can run PHP5.  If you're using a Debian-based Linux system, you'll need to do something like -

    sudo apt-get install php5-cli

Or for Redhat based systems, use -

    sudo yum install php-cli

I'm too lazy to look up how to do it using Gentoo or Puppy or Archlinux - send me a patch, haha.

On windows, I'd recommends either XAMPP (http://www.apachefriends.org/en/xampp.html) or WAMP server (http://wampserver.com/en).  I don't really use Windows much any more, so I can't tell you if it works or not, but PHP in general has pretty good cross-platform compatibility.

**NOTE:** You've just installed the PHP command line "runtime".  If you want the full capability to serve stuff from a web server like Apache, you can try these alternate commands to install more stuff (instead of just php-cli)

    sudo apt-get php

or

    sudo yum install php

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
