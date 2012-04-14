spf\_js
=======

manage JavaScripts in Textpattern admin and export on save to external .js files
--------------------------------------------------------------------------------

[GitHub repository][]

This is a combination of two previously-released plugins:
stm\_javascript by [Stanislav Müller][] and rvm\_css by [Ruud van
Melick][] - completely re-written for Textpattern 4.4.1. Thanks to the
original authors - and to Stef (Bloke) and Jukka (Gocom) for feedback
and help.

Changes include exporting scripts as files to a directory, removing
`type="text/javascript"` from the output and changing the tag argument
from `n=` to `name=` to bring it into line with default css syntax.

  

* * * * *

  

### Instructions:

1.  This plugin is unlikely to work on Textpattern versions prior to
    4.4.1.
2.  If stm\_javascript is installed and activated - please de-activate
    (or uninstall).
3.  Create a directory for the static JavaScript files in the root of
    your textpattern installation. You should make sure that
    <span class="caps">PHP</span> is able to write to that directory.
4.  Visit the [advanced preferences][] and make sure the “JavaScript
    directory” preference contains the directory you created in step 2
    (by default ‘js’). This path is always a relative path (to the
    directory of your root textpattern installation).
5.  Activate this plugin.
6.  Go to Presentation \> JavaScript and create JavaScripts you’d like
    to embed within your page templates.
7.  JavaScript files are stored in the database (for easy management and
    editing) and, on save, exported to a directory in your website where
    they can be referenced (as external JavaScript) with the tag below.

### Tags:

`<txp:spf_js /> (embeds the default JavaScript file)`

`<txp:spf_js name="myscript" /> (embeds JavaScript file named "myscript")`

  

### HTML output:

`<script src="http://mysite.com/js/myscript.js"></script>`

  

* * * * *

  

### Notes:

1.  Don’t use non-alphanumeric characters in script names (if you try to
    they’ll be stripped).
2.  The plugin will convert your script names to lowercase.
3.  The plugin doesn’t (yet) check whether a JavaScript file exists when
    you embed it - so check the .js file is reachable by your browser -
    and, if not, check your name attribute for typos.

  

* * * * *

  

### Version history

0.1 - April 2012 - first release.

  [GitHub repository]: https://github.com/spiffin/spf_js
  [Stanislav Müller]: https://github.com/lifedraft/stm_javascript
  [Ruud van Melick]: http://vanmelick.com/
  [advanced preferences]: index.php?event=prefs&step=advanced_prefs
