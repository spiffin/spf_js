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

$plugin['version'] = '0.52';
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
spf_tab_script => JavaScripts
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
#@language fr-fr
spf_javascript => JavaScript
spf_tab_script => JavaScripts
spf_js_dir => Répertoire JavaScript
spf_script_name => Nom de ce script
spf_edit_script => Vous éditez le script
spf_copy_script => Enregistrer le script sous le nom :
spf_all_scripts => Tous les scripts
spf_create_new_script => Créer un nouveau script
spf_script_created => Le script <strong>{name}</strong> a été créé.
spf_script_exists => Le script <strong>{name}</strong> existe déjà.
spf_script_name_required => Veuillez renseigner un nom pour votre script.
spf_script_updated => Le script <strong>{name}</strong> a été mis à jour.
spf_script_deleted => Le script <strong>{name}</strong> a été supprimé.
spf_cannot_delete_default_script => Le script <strong>default</strong> ne peut pas être supprimé.
#@language de-de
spf_javascript => JavaScript
spf_tab_script => JavaScripts
spf_js_dir => JavaScript-Verzeichnis
spf_script_name => Name dieses Script
spf_edit_script => Sie bearbeiten das Script
spf_copy_script => Kopiere Script als:
spf_all_scripts => Alle Scripts
spf_create_new_script => Neues Script erstellen
spf_script_created => Script <strong>{name}</strong> wurde erstellt.
spf_script_exists => Script <strong>{name}</strong> existiert bereits.
Script <strong>{name}</strong> existiert bereits.
spf_script_name_required => Bitte vergeben Sie einen Namen für Ihr Script.
spf_script_updated => Script <strong>{name}</strong> wurde aktualisiert.
spf_script_deleted => Script <strong>{name}</strong> wurde gelöscht.
spf_cannot_delete_default_script => Script <strong>default</strong> konnte nicht gelöscht werden.
#@language it-it
spf_javascript => JavaScript
spf_tab_script => JavaScripts
spf_js_dir => JavaScript directory
spf_script_name => Nome dello script
spf_edit_script => Stai modificando lo script
spf_copy_script => &#8230;o copia lo script con nome
spf_all_scripts => Tutti gli script
spf_create_new_script => Crea nuovo script
spf_script_created => Script <strong>{name}</strong> creato.
spf_script_exists => Lo script <strong>{name}</strong> esiste gi&agrave;.
spf_script_name_required => Per favore inserisci un nome per lo script
spf_script_updated => Script <strong>{name}</strong> aggiornato.
spf_script_deleted => Script <strong>{name}</strong> cancellato.
spf_cannot_delete_default_script => Lo script <strong>default</strong> non pu&ograve; essere cancellato.
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * spf_js - JavaScript management for Textpattern 4.5.x
 *
 * © 2012 Simon Finch - https://github.com/spiffin
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 0.52 -- 21 May 2019
 *
 * Many thanks to Jukka for invaluable feedback
 */

if (@txpinterface == 'admin') {

    add_privs('spf_js', '1,2');
    register_tab('presentation', 'spf_js', gTxt('spf_javascript'));
    register_callback('spf_js_event', 'spf_js');
    register_callback('spf_js_install', 'plugin_lifecycle.spf_js', 'installed');
    //register_callback('spf_js_install', 'plugin_lifecycle.spf_js', 'enabled');
    register_callback('spf_js_remove', 'plugin_lifecycle.spf_js', 'deleted');

}

/**
 * Removal function
 */

function spf_js_remove($event, $step) {
global $prefs, $step;

    if(isset($prefs['spf_js_dir'])) {

        safe_delete(
            'txp_prefs',
            "name='spf_js_dir'"
        );

        // Don't drop the table - just in case.
        //@safe_query(
        //    'DROP TABLE IF EXISTS '.safe_pfx('spf_js')
        //);

        // delete the Textpack

        safe_delete(
            'txp_lang',
            "event = 'spf_js'"
        );

    }

}

/**
 * Installer function
 */

function spf_js_install($event, $step) {
global $prefs, $step;

    // Version check
    if (txp_version >= '4.5.0') {

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
            "name='spf_js_dir',
            val='js',
            type=1,
            event='admin',
            html='text_input',
            position=35"
        );
        
    }


// If Txp version is less than 4.5.x return message.

    } else {
        return 'The plugin spf_js version 0.5 requires Textpattern 4.5.1 - you are running Textpattern ' . txp_version . ' - please delete spf_js.';
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
        'type' => '',
        'min' => ''
    ), $atts));

    $name = strtolower(sanitizeForUrl($name));

    if(!safe_row('name', 'spf_js', "name='".doSlash($name)."'")) {

         trigger_error(gTxt('404_not_found').sp.strong('"'.$name.'"'));

        return;

    }

    if (!$type) {

        return '<script src="'.txpspecialchars(hu.$prefs['spf_js_dir'].'/'.$name).'.js"></script>';

    } elseif ($type) {

        return '<script type="text/javascript" src="'.txpspecialchars(hu.$prefs['spf_js_dir'].'/'.$name).'.js"></script>';
        
    }

}

/**
 * List function
 */

function spf_js_list($name) {

    $out[] = startTable('', '', 'txp-list');

    $rs = safe_rows_start("name as jsname", ("spf_js"), "1=1");

    if ($rs) {

        while ($a = nextRow($rs)) {
            extract($a);
            $edit = ($name!=$jsname) ? eLink('spf_js', '', 'name', $jsname, $jsname) : txpspecialchars($name);
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

        $buttons = '<div class="edit-title">'.gTxt('spf_edit_script').sp.strong(txpspecialchars($name)).'</div>';
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
    graf(sLink('spf_js', 'pour', gTxt('spf_create_new_script')), ' class="action-create"').
    spf_js_list($name, $default_name).
    '</div>';

    echo
    '<h1 class="txp-heading">'.gTxt('spf_tab_script').'</h1>'.
    '<div id="'.$event.'_container" class="txp-container">'.
    startTable('', '', 'txp-columntable').
    tr(
        td(
            form(
                '<div id="main_content">'.
                $buttons.
                '<textarea id="spf_js" class="code" name="spf_js" cols="'.INPUT_LARGE.'" rows="'.INPUT_REGULAR.'" style="height: 39.25em;">'.txpspecialchars($thejs).'</textarea>'.
                '<p>'.fInput('submit','',gTxt('save'),'publish').
                eInput('spf_js').sInput('spf_js_save').
                hInput('name',$name).'</p>'
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

    $dbname = trim(preg_replace('/[<>&"\']/', '', ps('name')));
    $name = strtolower(sanitizeForUrl(ps('name')));
    $file = $prefs['path_to_site'].'/'.$prefs['spf_js_dir'].'/'.$name;

    if ($name != 'default') {

        safe_delete("spf_js", "name = '".doSlash($dbname)."'");

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
<h1>spf_js: JavaScript management</h1>

<p>Create, edit and delete scripts in Textpattern admin and export on save to external files.</p>
<p>REQUIRES: <strong>Texpattern 4.5.1</strong> and PHP 5.</p>
<p>Please read the instructions and notes below before use.</p>
<p>Latest version: <a href="https://github.com/spiffin/spf_js">spf_js GitHub repository</a>.</p>
<p>A combination of two previously-released plugins: stm_javascript by Stanislav Müller and rvm_css by Ruud van Melick. Thanks to the original authors and to Jukka (Gocom) and Stef (Bloke) for invaluable feedback.</p>
<p>Features include exporting scripts as files to a directory, optional “type” attribute <code>type=&quot;text/javascript&quot;</code> and changing the tag argument from <code>n=</code> to <code>name=</code> to bring it in line with default css syntax.</p>

<p>Re-written for Textpattern 4.5.1 and above.</p>

<p><strong>For Textpattern 4.4.1 and below use <a href="https://raw.github.com/spiffin/spf_js/master/spf_js_0.41.txt">this version</a>.</strong></p>

<br /><hr /><br />

<h2>Instructions:</h2>

<ol>
<li>Create a directory for the static JavaScript files in the root of your textpattern installation. You should make sure that <span class="caps">PHP</span> is able to write to that directory.</li>
<li>Visit the Advanced Preferences (Admin > Preferences > Advanced) and make sure the “JavaScript directory” preference contains the directory you created in step 1 (by default ‘js’). This path is relative path to the directory of your root Textpattern installation.</li>
<li>Activate this plugin.</li>
<li>Go to Presentation > JavaScript and create JavaScripts you’d like to embed within your page templates.</li>
<li>JavaScript files are stored in the database (for easy management and editing) and, on save, exported to a directory in your website where they can be referenced (as external JavaScript) with the tag below.</li>
</ol>

<br /><hr /><br />

<h2>Tags:</h2>

<p><code>&lt;txp:spf_js /&gt; (embeds the default JavaScript file)</code></p>
<p><code>&lt;txp:spf_js name=&quot;myscript&quot; /&gt; (embeds the JavaScript file named &quot;myscript&quot;)</code></p>

<h3>HTML output:</h3>
<p><code>&lt;script src=&quot;http://mysite.com/js/myscript.js&quot;&gt;&lt;/script&gt;</code></p>

<h3>"type" attribute</h3>
<p>By default the plugin outputs a script tag without the <a href="http://www.w3schools.com/html5/tag_script.asp">"type" attribute</a> (required in XHTML/HTML4 but optional in HTML5).</p>
<p>To include a "type" attribute just use the <code>type=&quot;1&quot;</code> argument:</p>
<p><code>&lt;txp:spf_js name=&quot;myscript&quot; type=&quot;1&quot; /&gt;</code></p>
<p>Outputs:</p>
<p><code>&lt;script type=&quot;text/javascript&quot; src=&quot;http://mysite.com/js/myscript.js&quot;&gt;&lt;/script&gt;</code></p>

<br /><hr /><br />

<h2>Notes:</h2>

<ol>
<li>Don’t use non-alphanumeric characters in script names (if you try to they’ll be stripped).</li>
<li>The plugin will convert your script names to lowercase.</li>
<li>The plugin will throw an error if you try to embed a non-existent script - similar to: <code>Tag error:   -&gt;  Textpattern Notice: The requested resource was not found. &quot;script_name&quot;</code>.</li>
<li>&mdash; In which case check the script exists and your embed tag for typos.</li>
</ol>

<br /><hr /><br />

<h2>stm_javascript</h2>

<p>If stm_javascript is installed and activated you will see two JavaScript tabs in Presentation - one named ‘Javascript’ (stm_javascript - with lowercase ’s’) and another ‘JavaScript’ (spf_js - uppercase ‘S’). You can copy and paste scripts from stm_javascript to spf_js - and then disable stm_javascript. It’s not advisable to run both plugins simultaneously.</p>

<br /><hr /><br />

<h2>Language support (Textpack)</h2>

<p>This plugin uses an English Textpack by default and installs French (fr-fr), German (de-de) and Italian (it-it) Textpacks.</p>
<p>To use your own language see the <a href="https://raw.github.com/spiffin/spf_js/master/spf_js_textpack.txt">spf_js_textpack</a> file on GitHub.</p>

<br /><hr /><br />

<h2>Version history</h2>

<p>0.52 - 21 May 2019</p>
<ul>
<li>Fixed issue setting prefs in 4.7.x.</li>
<li>Removed <a href="http://code.google.com/p/minify">Minify</a> support - did anyone ever use it?</li>
</ul>
<p>0.51 - November 2012</p>
<ul>
<li>Fixed issue setting prefs in 4.5.x.</li>
</ul>
<p>0.5 - November 2012</p>
<ul>
<li>Rewritten for Textpattern 4.5.x.</li>
</ul>
<p>0.41 - June 2012</p>
<ul>
<li>Italian Textpack added (thanks Marco).</li>
<li>Last version to support Textpattern 4.4.1 and below.</li>
</ul>
<p>0.4 - May 2012</p>
<ul>
<li>Added <a href="http://code.google.com/p/minify">Minify</a> support.</li>
</ul>
<p>0.3 - May 2012</p>
<ul>
<li>Fixed delete issue with script names containing dots (thanks Yiannis).</li>
</ul>
<p>0.2 - April 2012</p>
<ul>
<li>French and German Textpacks added (thanks Patrick and Uli);</li>
<li>added compatibility with the syntax-highlighting <a href="https://github.com/spiffin/spf_codemirror">spf_codemirror</a>.</li>
</ul>

<p>0.1 - April 2012</p>
<ul>
<li>first release.</li>
</ul>
# --- END PLUGIN HELP ---
-->
<?php
}
?>