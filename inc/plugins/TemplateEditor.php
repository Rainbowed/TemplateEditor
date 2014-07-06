<?php

    defined('IN_MYBB') or die('Nope');
    defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

    function TemplateEditor_success() {
        global $mybb;

        if ( (int) $mybb->input['raw'] == 1 )
            die(json_encode(array('msg' => 'success')));
    }

    function TemplateEditor_info() {
        return array(
            'name'          => 'Template Editor',
            'author'        => 'Cake',
            'version'       => '0.4',
            'compatibility' => '16*',
            'guid'          => 'e480d11b1de4ddc53346cd43050394ec'
        );
    }

    function TemplateEditor_activate() {
        global $PL, $config;

        if ( !file_exists(PLUGINLIBRARY) ) {
            flash_message('PluginLibrary is missing, get it at <a href="http://mods.mybb.com/view/pluginlibrary">http://mods.mybb.com/view/pluginlibrary</a>.', 'error');
            admin_redirect('index.php?module=config-plugins');
        }

        $PL or require_once PLUGINLIBRARY;

        if ( $PL->version < 9 ) {
            flash_message('This plugin requires PluginLibrary 9 or newer', 'error');
            admin_redirect('index.php?module=config-plugins');
        }

        $PL->edit_core('TemplateEditor', $config['admin_dir'] . '\modules\style\templates.php',
                       array('search' => 'log_admin_action($tid, $mybb->input[\'title\'], $mybb->input[\'sid\'], $set[\'title\']);',
                             'after'  => '$plugins->run_hooks(\'template_commit_success\');'), TRUE, $d);

    }

    function TemplateEditor_deactivate() {
        global $PL, $config;

        $PL or require_once PLUGINLIBRARY;

        $PL->edit_core('TemplateEditor', $config['admin_dir'] . '\modules\style\templates.php', array(), TRUE, $d);
    }

    function TemplateEditor_handler( &$actions ) {
        $actions['editor'] = array('active' => 'editor', 'file' => 'editor.php');
        return $actions;
    }

    function TemplateEditor_permissions( &$admin_permissions ) {
        $admin_permissions['editor'] = 'Can use the template editor?';
        return $admin_permissions;
    }

    function TemplateEditor_menu( &$sub_menu ) {
        $sub_menu['40'] = array('id' => 'editor', 'title' => 'Template Editor', 'link' => 'index.php?module=style-editor');
        return $sub_menu;
    }

    $plugins->add_hook('template_commit_success', 'TemplateEditor_success');

    $plugins->add_hook('admin_style_menu', 'TemplateEditor_menu');
    $plugins->add_hook('admin_style_permissions', 'TemplateEditor_permissions');
    $plugins->add_hook('admin_style_action_handler', 'TemplateEditor_handler');
