<?php

/**
 * spf_js - JavaScript management for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 0.2 -- 26 April 2012
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
                '<textarea id="spf_js" class="code" name="spf_js" cols="78" rows="32" style="margin-top: 6px; width: 700px; height: 515px;">'.htmlspecialchars($thejs).'</textarea>'.br.
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

?>