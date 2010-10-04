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

What if we could make CSS more object-oriented to group our rules and selector together in a logical organization way that says us the time of having to write rules over and over redundantly (like this)?

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

And we could take this and automatically re-arrange it down to 

    div.class {
        color: red;
    }
    div.class a:active, div.class a:link, div.class a:visited {
        color: black;
    }
    div.class a:hover {
        color: blue;
    }

automatically for you?  Well, look no more!

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

    sudo apt-get install php

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

And lastly, for those CLI junkies like myself, you can run it from the command-line!  This assumes you are passing this script filename arguments will be transformed.  This means you can easily integrate this into automated builds (along with minification or static gzipping or whatever strikes you fancy) and even possibly combo (as you put them in the correct order, and by default you'll get a big ball of stuff).  This could be easily combined with the YUI Compressor by Julien Lecomte to deliver the fastest possible websites for you users, :).

Here's how you'd use it:

    cd to/path/where/i/cloned/oo-css ; # move to location of script

And then you can run with the php command with a single file or glob of files you want to be converted:

    php oo_css.php css/oo_src_file.css
    php oo_css.php css/*.css

Or additionally, you can add the line

    #!/usr/bin/env php

at the top of oo_css.php, and run the command

    chmod u+x path/to/oo_css.php

adding execution permissions for your user to run that file like so:

    ./oo_css.php css/oo_src_files.css
    ./oo_css.php css/*.css