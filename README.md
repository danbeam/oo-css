OO-CSS
======

### An object-oriented CSS parser to slightly simplify the lives of web developers everywhere.

What does this do?
------------------

Have you ever been making a website (particularly a large or complex one) and been frustrated by spaghetti CSS?  For instance, we have:

    /* everything in this div has red text! */
    div.class {
        color: red;
    }

And a couple hundred lines later:

    /* except links */
    div.class a:active, div.class a:link, div.class a:visited {
        color: black;
    }

And in a different stylesheet:

    /* and :hover links are different too! */
    div.class a:hover {
        color: blue;
    }

What if we could make CSS more object-oriented in order to group our rules and selectors together in a logical way that saves us time and redundant error?  For example, if we were given a source document of:

    /* class named class is a great name! */
    div.class {

        /* everything in this div has red text! */
        color: red;

        /* except links */
        a:active, a:link, a:visited {
            color: black;
        }

        /* and :hover links are different too! */
        a:hover {
            color: blue;
        }
    }

With which we automatically re-arrange to:

    div.class {
        color: red;
    }
    div.class a:active, div.class a:link, div.class a:visited {
        color: black;
    }
    div.class a:hover {
        color: blue;
    }

Well, look no more!  This is what OO-CSS does for you with 1 simple PHP script!


How do I use this?
------------------

### Setting up environment

Currently, this project is written in object-oriented PHP5.  Until anyone cares enough for me to port it, it'll probably stay that way.

So, first you'll need some kind of environment that can run PHP5.  If you're using a Debian-based Linux system, you'll need to do something like -

    sudo apt-get install php5-cli

Or for Redhat based systems, use -

    sudo yum install php-cli

I'm too lazy to look up how to do it using Gentoo or Puppy or Archlinux - send me a patch, haha.

On Windows, I'd recommend either [XAMPP](http://www.apachefriends.org/en/xampp.html) or [WAMP server](http://wampserver.com/en).  I don't really use Windows much any more, so I can't tell you if it works or not, but PHP in general has pretty good cross-platform compatibility (though my tests don't, as they use `exec` in an assumed bash environment).

**NOTE:** You've just installed the PHP command line "runtime".  If you want the full capability to serve stuff from a web server like Apache, you can try these alternate commands to install more stuff (instead of just php-cli)

    sudo apt-get install php

or

    sudo yum install php

### Including as a class

If you simply `require`, `include`, `require_once`, or `include_once` the /oo_css.php file, you will have access to the OO_CSS_Parser global, which contains methods to:

* parse
* warn  (write message to stderr if WARN constant is truthy)
* debug (write message to stdout if DEBUG constant is truthy)

This can be done by creating an instance of OO_CSS_Parser, like so:

    $oo_css = new OO_CSS_Parser();

and then giving it a list of files to parse:

    $oo_css->parse('some/files/to/go/to.css');

### Using PHP on the command line

And lastly, for those CLI junkies like myself, you can run OO_CSS_Parser from the command-line!  This assumes you are passing this script filename arguments to be transformed.  This means you can easily integrate this into automated builds (along with minification or static gzipping or whatever strikes your fancy) as well as combine your CSS files together (if you put them in the correct order you'll get a big ball of CSS back).  This could (and should) be combined with the YUI Compressor by Julien Lecomte to deliver the fastest possible websites for your users, :).

Here's how you get it:

    git clone git://github.com/danbeam/oo-css.git && cd oo-css ; # we're now within our newly cloned repo!

And then you can run the parser with the `php` command on any number of files or globs you want to be converted:

    php oo_css.php css/src_file.oocss
    php oo_css.php css/*.oocss

Examples and tricks
-------------------

Here's an example of before:

    me@host:oo-css(master)$ echo && cat tests/new/simple_class_and_element.oocss
    
    .class {
    
        background-color: white;
    
        span {
            margin-bottom: 10px;
        }
    
    }

And now we can parse the file from OO to something browsers better understand:

    me@host:oo-css(master)$ echo && php oo_css.php tests/new/simple_class_and_element.oocss 2>/dev/null
    
    .class {
        background-color: white;
    }
    .class span {
        margin-bottom: 10px;
    }

If you have more than one file, a comment indicating the filename will be automatically output above each file, like this:

    me@host:oo-css(master)$ echo && php oo_css.php tests/new/*.oocss 2>/dev/null
    
    /* some/file.oocss */

    /* yada yada, CSS goes here */

    /* some/other/file.oocss */
    
    /* and so on */

And lastly, like I've mentioned before, you can do magical things like OO CSS -> CSS -> minified -> gzipped in one line of bash!

    me@host:oo-css(master)$ php oo_css.php some/file.oocss 2>/dev/null | yui --type css | gzip -c > ready_for_prod.css.gz && \
    echo && zcat ready_for_prod.css.gz && echo
    
    .class{background-color:white;}.class span{margin-bottom:10px;}

**NOTE:** This assumes you have the [YUI Compressor](http://yuilibrary.com/downloads/#yuicompressor) jar on your path (named yui and with executable permissions) as well as Java and gzip installed.

Formats
-------

Recently, I added formats for your CSS output for those of you that are sticklers for maintaining a code style (perhaps with exist code) or just like flexiblity.  My script must basically rebuild everything it finds from scratch.  This has the benefit of being able to generate the CSS in an organized fashion, but has the disadvantage of only using allowed formats.  So, in an attempt to not force anybody to a certain style, I've made the following formats:

**1tbs, 0tbs, default**

    .selector {
        rule: value;
    }

**allman**

    .selector
    {
        rule: value;
    }

**oneline[r]**

    .selector { rule: value; }

**minified**

    .selector{rule:value;}

**NOTE:** The minification is crude, as this script is not truly a CSS parser - more a partial lexer (it really only identify blocks and rules), so minification with the "minified" format will not sub-optimize like a smarter minifier (again, like YUI Compressor) would.  However, if you're lazy and you want a one-stop solution for smaller CSS (i.e. you weren't going to run a better compressor anyways), this isn't a horrible option.

These options can be passed as a string to the `__constructor` when creating the parser, i.e.

    $parser = new OO_CSS_Parser('stallman');

Or can be sent on the CLI with the `-s` option

    php oo_css.php -s stallman [files to be converted...]
