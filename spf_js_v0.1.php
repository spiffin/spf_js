<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'spf_js';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.1';
$plugin['author'] = 'Simon Finch';
$plugin['author_uri'] = 'https://github.com/spiffin/spf_js';
$plugin['description'] = 'JavaScript management';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '2';

// Plugin 'textpack' - provides i18n strings to be used in conjunction with gTxt().
$plugin['textpack'] = <<< EOT
#@spf_js
spf_javascript => JavaScript
spf_js_dir => JavaScript directory
spf_script_name => Name for this script
spf_edit_script => You are editing script
spf_copy_script => &#8230;or copy script as
spf_all_scripts => All Scripts
spf_create_new_script => Create new script
spf_script_created => Script <strong>{name}</strong> created.
spf_script_exists => Script <strong>{name}</strong> already exists.
spf_script_name_required => Please provide a name for your script.
spf_script_updated => Script <strong>{name}</strong> updated.
spf_script_deleted => Script <strong>{name}</strong> deleted.
spf_cannot_delete_default_script => Script <strong>default</strong> cannot be deleted.
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * spf_js - JavaScript management for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 0.1 -- 19 April 2012
 *
 * Many thanks to Jukka for invaluable feedback
 */

if (@txpinterface == 'admin') {

    add_privs('spf_js', '1,2');
    register_tab('presentation', 'spf_js', gTxt('spf_javascript'));
    register_callback('spf_js_event', 'spf_js');
    register_callback('spf_js_install', 'plugin_lifecycle.spf_js');

}

/**
 * Installer function
 * @param string $event Admin-side event
 * @param string $step Admin-side, plugin-lifecycle step
 */

function spf_js_install($event='', $step='') {

    global $prefs;

    if($step == 'deleted') {

        safe_delete(
            'txp_prefs',
            "name='spf_js_dir'"
        );

        @safe_query(
            'DROP TABLE IF EXISTS '.safe_pfx('spf_js')
        );

        // delete the Textpack

        safe_delete(
            'txp_lang',
            "event = 'spf_js'"
        );

        return;

    }

    /* Create table, prefs and default.js when spf_js_dir isn't set */

    if(!isset($prefs['spf_js_dir'])) {

        safe_query(
            "CREATE TABLE IF NOT EXISTS ".safe_pfx('spf_js')." (
                name varchar(255) NOT NULL default '',
                js longtext NOT NULL,
                PRIMARY KEY(name)
            ) CHARSET=utf8"
        );

        if(!safe_count("spf_js", "name='default'")) {

            safe_insert("spf_js", "name='default', js='// Your default JavaScript'");

        }

        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_js_dir',
            val='js',
            type=1,
            event='admin',
            html='text_input',
            position=21"
        );
    }
}

/**
 * Event functions
 */

function spf_js_event() {

    global $event, $step;

    if ($event == 'spf_js') {
        require_privs('spf_js');

        bouncer($step,
            array(
                'spf_js_edit_raw'   => false,
                'pour'              => false,
                'spf_js_save'       => true,
                'spf_js_copy'       => true,
                'spf_js_delete'     => true,
                'spf_js_edit'       => false,
            )
        );

        switch ($step) {
            case '': spf_js_edit();                         break;
            case 'spf_js_edit_raw': spf_js_edit();          break;
            case 'pour': spf_js_edit();                     break;
            case 'spf_js_save': spf_js_save();              break;
            case 'spf_js_copy': spf_js_copy();              break;
            case 'spf_js_delete': spf_js_delete();          break;
            case 'spf_js_edit': spf_js_edit();
        }
    }
}

/**
 * Output tag
 */

function spf_js($atts) {

    global $prefs;

    extract(lAtts(array(
        'name' => 'default',
        'type' => ''
    ), $atts));

    $name = strtolower(sanitizeForUrl($name));

    if(!safe_row('name', 'spf_js', "name='".doSlash($name)."'")) {

         trigger_error(gTxt('404_not_found').sp.strong('"'.$name.'"'));

        return;

    }

    if (!$type) {

        return '<script src="'.htmlspecialchars(hu.$prefs['spf_js_dir'].'/'.$name).'.js"></script>';

    } else {

        return '<script type="text/javascript" src="'.htmlspecialchars(hu.$prefs['spf_js_dir'].'/'.$name).'.js"></script>';

    }

    // To minify output (requires Minify):
    // return '<script src="'.htmlspecialchars(hu.'min/f='.$prefs['spf_js_dir'].'/'.$name).'.js"></script>';

}

/**
 * List function
 */

function spf_js_list($name) {

    $out[] = startTable('list', 'left');

    $rs = safe_rows_start("name as jsname", ("spf_js"), "1=1");

    if ($rs) {

        while ($a = nextRow($rs)) {
            extract($a);
            $edit = ($name!=$jsname) ? eLink('spf_js', '', 'name', $jsname, $jsname) : htmlspecialchars($name);
            $delete = ($jsname!='default') ? dLink('spf_js', 'spf_js_delete', 'name', $jsname) : '';
            $out[] = tr(td($edit).td($delete));
        }

        $out[] =  endTable();

        return join('', $out);

    }
}

/**
 * Edit function
 */

function spf_js_edit($message='') {

    pagetop(gTxt('spf_javascript'),$message);
    global $step, $prefs;
    spf_js_edit_raw();

}

/**
 * Edit function (raw)
 */

function spf_js_edit_raw() {

    global $event, $step;

    $default_name = 'default';
    extract(gpsa(array('name', 'newname', 'copy', 'savenew')));

    if ($step == 'spf_js_delete' || empty($name) && $step != 'pour' && !$savenew) {

        $name = $default_name;
    }

    elseif (($copy || $savenew) && trim(preg_replace('/[<>&"\']/', '', $newname))) {

        $name = $newname;

    }

    if (empty($name)) {

        $buttons = '<div class="edit-title">'.
        gTxt('spf_script_name').': '
        .fInput('text','newname','','edit','','',20).
        hInput('savenew','savenew').
        '</div>';
        $thejs = gps('spf_js');

    } else {

        $buttons = '<div class="edit-title">'.gTxt('spf_edit_script').sp.strong(htmlspecialchars($name)).'</div>';
        $thejs = fetch("js",'spf_js','name',$name);

    }

    if (!empty($name)) {

        $copy = '<span class="copy-as"><label for="copy-js">'.gTxt('spf_copy_script').'</label>'.sp.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-js').sp.
            fInput('submit', 'copy', gTxt('copy'), 'smallerbox').'</span>';

    } else {

        $copy = '';

    }

    $right =
    '<div id="content_switcher">'.
    hed(gTxt('spf_all_scripts'),2).
    graf(sLink('spf_js', 'pour', gTxt('spf_create_new_script')), ' class="action-create smallerbox"').
    spf_js_list($name, $default_name).
    '</div>';

    echo
    '<div id="'.$event.'_container" class="txp-container txp-edit">'.
    startTable('edit').
    tr(
        td(
            form(
                '<div id="main_content">'.
                $buttons.
                '<textarea id="spf_js" class="code" name="spf_js" cols="78" rows="32">'.htmlspecialchars($thejs).'</textarea>'.br.
                fInput('submit','',gTxt('save'),'publish').
                eInput('spf_js').sInput('spf_js_save').
                hInput('name',$name)
                .$copy.
                '</div>'
            , '', '', 'post', 'edit-form', '', 'style_form')
        , '', 'column').
        tdtl(
            $right
        , ' class="column"')
    ).
    endTable().
    '</div>';
}

/**
 * Copy function
 *
 */

function spf_js_copy() {

    extract(gpsa(array('oldname', 'newname')));

    $js = doSlash(fetch('js', 'spf_js', 'name', $oldname));

    $rs = safe_insert('spf_js', "js = '$js', name = '".doSlash($newname)."'");

    spf_js_edit(
        gTxt('spf_script_created', array('{name}' => $newname))
    );
}

/**
 * Save function
 */

function spf_js_save() {

    extract(gpsa(array('name','spf_js','savenew','newname','copy')));
    $js = doSlash($spf_js);

    if ($savenew or $copy) {

        $newname = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('newname'))));

        if ($newname and safe_field('name', 'spf_js', "name = '$newname'")) {

            $message = gTxt('spf_script_exists', array('{name}' => $newname));

            if ($savenew) {

                $_POST['newname'] = '';

            }

        } elseif ($newname) {

            safe_insert('spf_js', "name = '".$newname."', js = '$js'");

            $message = gTxt('spf_script_created', array('{name}' => $newname));

            spf_js_write();

        } else {

            $message = array(gTxt('spf_script_name_required'), E_ERROR);

        }

        spf_js_edit($message);

    } else {

        safe_update('spf_js', "js = '$js'", "name = '".doSlash($name)."'");

        $message = gTxt('spf_script_updated', array('{name}' => $name));

        spf_js_write();

        spf_js_edit($message);

    }

}

/**
 * Write function
 */

function spf_js_write() {

    global $prefs;
    extract(gpsa(array('name','spf_js','savenew','newname','copy')));

    $name = (ps('copy') or ps('savenew')) ? ps('newname') : ps('name');
    $filename = strtolower(sanitizeForUrl($name));
    $file = $prefs['path_to_site'].'/'.$prefs['spf_js_dir'].'/'.$filename;

    if (empty($prefs['spf_js_dir']) or !$filename) {

        return;

    } else {

    $js_raw = fetch("js", "spf_js", 'name', $name); // Moved here to save newly-created scripts

        $handle = fopen($file.'.js', 'wb');
        fwrite($handle, $js_raw);
        fclose($handle);
        chmod($file.'.js', 0644);
    }

}

/**
 * Delete function
 */

function spf_js_delete() {

    global $prefs;

    $name = strtolower(sanitizeForUrl(ps('name')));
    $file = $prefs['path_to_site'].'/'.$prefs['spf_js_dir'].'/'.$name;

    if ($name != 'default') {

        safe_delete("spf_js", "name = '".doSlash($name)."'");

        if (!empty($prefs['spf_js_dir']) and $name) {

            @unlink($file.'.js');

        }

        spf_js_edit(

            gTxt('spf_script_deleted', array('{name}' => $name))
        );

    } else {

        echo gTxt('spf_cannot_delete_default_script').'.';

    }
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
<h1>JavaScript management</h1>

<p>Create, edit and delete scripts in Textpattern admin and export on save to external files.</p>

<p>REQUIRES: Texpattern 4.4.1 and PHP 5.</p>

<p>Please read the instructions and notes below before use.</p>

<p>Latest version: <a href="https://github.com/spiffin/spf_js">spf_js GitHub repository</a></p>

<p>A combination of two previously-released plugins:  rvm_css by Ruud van Melick and stm_javascript by Stanislav Müller. Thanks to the original authors and to Jukka (Gocom) and Stef (Bloke) for invaluable feedback.
</p>

<p>Features include exporting scripts as files to a directory, optional "type" attribute <code>type="text/javascript"</code> and changing the tag argument from <code>n=</code> to <code>name=</code> to bring it in line with default css syntax. Re-written for Textpattern 4.4.1 to mimic the Presentation > Style tab.</p>

<br />
<hr />
<br />

	<h3>Instructions:</h3>
	<ol>
		<li>Create a directory for the static JavaScript files in the root of your textpattern installation. You should make sure that <span class="caps">PHP</span> is able to write to that directory.</li>
		<li>Visit the <a href="index.php?event=prefs&amp;step=advanced_prefs">advanced preferences</a> and make sure the &#8220;JavaScript directory&#8221; preference contains the directory you created in step 1 (by default 'js'). This path is relative path to the directory of your root Textpattern installation.</li>
		<li>Activate this plugin.</li>
		<li>Go to Presentation > JavaScript and create JavaScripts you'd like to embed within your page templates.</li>
		<li>JavaScript files are stored in the database (for easy management and editing) and, on save, exported to a directory in your website where they can be referenced (as external JavaScript) with the tag below.</li>
	</ol>

<br />
<hr />
<br />

	<h3>Tags:</h3>
	<p><code>&lt;txp:spf_js /&gt; (embeds the default JavaScript file)</code></p>
	<p><code>&lt;txp:spf_js name="myscript" /&gt; (embeds the JavaScript file named &quot;myscript&quot;)</code></p>
	<br />
	<h3>HTML output:</h3>
	<p><code>&lt;script src=&quot;http://mysite.com/js/myscript.js&quot;&gt;&lt;/script&gt;</code></p>
        <br />
        <h3>"type" attribute</h3>
        <p>By default the plugin outputs a script tag without the <a href="http://www.w3schools.com/html5/tag_script.asp">"type" attribute</a> (required in XHTML/HTML4 but optional in HTML5).</p>
        <p>To include a "type" attribute just use the <code>type="1"</code> argument:</p>
	<p><code>&lt;txp:spf_js name="myscript" type="1" /&gt;</code></p>
        <p>will output:</p>
	<p><code>&lt;script type="text/javascript" src=&quot;http://mysite.com/js/myscript.js&quot;&gt;&lt;/script&gt;</code></p>
<br />
<hr />
<br />

	<h3>Notes:</h3>
        <ol>
        <li>Don't use non-alphanumeric characters in script names (if you try to they'll be stripped).</li>

        <li>The plugin will convert your script names to lowercase.</li>

        <li>The plugin will throw an error if you try to embed a non-existent script - similar to <code>Tag error:  <txp:spf_js name="missing_script" /> ->  Textpattern Notice: The requested resource was not found. "missing_script"</code> - in which case check your name attribute for typos and/or missing scripts.</li>
        </ol>

<br />
<hr />
<br />

        <h3>stm_javascript</h3>

        <p>If stm_javascript is installed and activated you will see two JavaScript tabs in Presentation - one named 'Javascript' (stm_javascript - with lowercase 's') and another 'JavaScript' (spf_js - uppercase 'S'). You can copy and paste scripts from stm_javascript to spf_js - and then disable stm_javascript. It's not advisable to run both plugins simultaneously.</p>

<br />
<hr />
<br />

        <h3>Languages (Textpack)</h3>

        <p>This plugin installs an English Textpack by default.</p>
        <p>To install a Textpack for your own language see the <a href="https://github.com/spiffin/spf_js/blob/master/spf_js_textpack.txt">instructions on GitHub</a>.</p>

<br />
<hr />
<br />

        <h3>Version history</h3>

0.1 - April 2012 - first release.
# --- END PLUGIN HELP ---
-->
<?php
}
?>