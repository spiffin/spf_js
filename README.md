spf_js JavaScript management for Textpattern
============================================

Create, edit and delete scripts in Textpattern admin and export on save
to external files.

**REQUIRES: Texpattern 4.4.1 (or newer) + PHP 5.**

Please read the instructions and notes below before use.

[GitHub repository][]

A combination of two previously-released plugins: stm\_javascript by
[Stanislav Müller][] and rvm\_css by [Ruud van Melick][]. Thanks to the
original authors and to Jukka (Gocom) and Stef (Bloke) for feedback and
help.

Features include exporting scripts as files to a directory, optional
“type” attribute `type="text/javascript"` and changing the tag argument
from `n=` to `name=` to bring it in line with default css syntax.
Re-written for Textpattern 4.4.1 to mimic the Presentation \> Style tab.

  

* * * * *

  

### Instructions:

1.  Create a directory for the static JavaScript files in the root of
    your textpattern installation. You should make sure that
    <span class="caps">PHP</span> is able to write to that directory.
2.  Visit the [advanced preferences][] and make sure the “JavaScript
    directory” preference contains the directory you created in step 1
    (by default ‘js’). This path is relative path to the directory of
    your root Textpattern installation.
3.  Activate this plugin.
4.  Go to Presentation \> JavaScript and create JavaScripts you’d like
    to embed within your page templates.
5.  JavaScript files are stored in the database (for easy management and
    editing) and, on save, exported to a directory in your website where
    they can be referenced (as external JavaScript) with the tag below.

  

* * * * *

  

### Tags:

`<txp:spf_js /> (embeds the default JavaScript file)`

`<txp:spf_js name="myscript" /> (embeds the JavaScript file named "myscript")`

  

### HTML output:

`<script src="http://mysite.com/js/myscript.js"></script>`

  

### “type” attribute

By default the plugin outputs a script tag without the [“type”
attribute][] (required in XHTML/HTML4 but optional in HTML5).

To include a “type” attribute just use the `type="1"` argument:

`<txp:spf_js name="myscript" type="1" />`

will output:

`<script type="text/javascript" src="http://mysite.com/js/myscript.js"></script>`

  

* * * * *

  

### Notes:

1.  Don’t use non-alphanumeric characters in script names (if you try to
    they’ll be stripped).
2.  The plugin will convert your script names to lowercase.
3.  The plugin will throw an error if you try to embed a non-existent
    script - similar to
    `Tag error:   ->  Textpattern Notice: JavaScript file not found "missing_script"`
    - in which case check your name attribute for typos and/or missing
    scripts.

  

* * * * *

  

### stm\_javascript

If stm\_javascript is installed and activated you will see two
JavaScript tabs in Presentation - one named ‘Javascript’
(stm\_javascript - with lowercase ’s’) and another ‘JavaScript’ (spf\_js
- uppercase ‘S’). You can copy and paste scripts from stm\_javascript to
spf\_js - and then disable stm\_javascript. It’s not advisable to run
both plugins simultaneously.

  

* * * * *

  

### Languages

At the beginning of the plugin code (Admin \> Plugins \> spf\_js \>
Edit) you’ll find the language function (function spf\_js\_gTxt) where
you can edit each string to your own language preferences.

  

* * * * *

  

### Version history

0.1 - April 2012 - first release.

  [GitHub repository]: https://github.com/spiffin/spf_js
  [Stanislav Müller]: https://github.com/lifedraft/stm_javascript
  [Ruud van Melick]: http://vanmelick.com/
  [advanced preferences]: index.php?event=prefs&step=advanced_prefs
  [“type” attribute]: http://www.w3schools.com/html5/tag_script.asp