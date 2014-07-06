<?php

    defined('IN_MYBB') or die('Nope');

    $lang->load('style_templates');

    $sid = (int) $mybb->input['sid'];

    if ( $mybb->input['action'] == 'get_template' && $mybb->input['sid'] && $mybb->input['title'] ) {
        $query    = $db->simple_select("templates", "*", "title='" . $db->escape_string((string) $mybb->input['title']) . "' AND (sid='-2' OR sid='{$sid}')", array('order_by' => 'sid', 'order_dir' => 'DESC', 'limit' => 1));
        $template = $db->fetch_array($query);

        die(isset($template['title']) ? json_encode(array('sid' => $sid, 'tid' => (int) $template['tid'], 'title' => $template['title'], 'template' => $template['template'])) : 'error');
    }


    if ( $mybb->input['action'] == 'edit' && $mybb->input['sid'] ) {

        $page->extra_header .= '
        <link type="text/css" href="./styles/default/template.css" rel="stylesheet" id="cp-lang-style" />
        <script src="http://cdn.jsdelivr.net/jquery/1.11.1/jquery.min.js"></script><script src="http://cdn.jsdelivr.net/ace/1.1.3/min/ace.js"></script>
        <script src="http://cdn.jsdelivr.net/ace/1.1.3/min/ext-language_tools.js"></script>
        <script>var sid = ' . $sid . ', my_post_key = "' . $mybb->post_code . '";</script>
        <script src="jscripts/template.js"></script>';

        $template_sets = array(-1 => 'Global Templates');

        $data  = '';
        $query = $db->simple_select("templatesets", "sid,title", '', array('order_by' => 'title', 'order_dir' => 'ASC'));

        while ( $template_set = $db->fetch_array($query) )
            $template_sets[$template_set['sid']] = $template_set['title'];

        $page->output_header("Editing {$template_sets[$sid]}");

        $query = $db->simple_select("templatesets", "sid, title");

        while ( $set = $db->fetch_array($query) ) {
            $template_sets[$set['sid']] = $set['title'];
        }

        $template_groups = array('ungrouped' => array('title' => 'Ungrouped Templates'));

        $query = $db->simple_select("templategroups", "*");

        while ( $templategroup = $db->fetch_array($query) )
            $template_groups[$templategroup['prefix']] = array('title' => $lang->parse($templategroup['title']) . " " . $lang->templates,
                                                               'gid'   => $templategroup['gid']);

        $templates_group = array();

        $query = $db->query('SELECT tid, title FROM ' . TABLE_PREFIX . 'templates WHERE ' . ($sid == -1 ? 'sid=-1' : 'sid = "' . $sid . '" OR sid = -2') . ' GROUP BY title ORDER BY version DESC');

        while ( $template = $db->fetch_array($query) ) {
            $exploded = explode("_", $template['title'], 2);

            if ( isset($template_groups[$exploded[0]]) )
                $template['group'] = $exploded[0];
            else
                $template['group'] = 'ungrouped';

            $templates_group[$template['group']][$template['title']] = $template['tid'];
        }

        ksort($templates_group);

        foreach ( $templates_group as $group => $templates ) {
            $data .= '<li class="template_li"><span>' . $template_groups[$group]['title'] . '</span>';
            if ( !empty($templates) ) {
                $data .= '<ul class="template_parent">';

                ksort($templates);

                foreach ( $templates as $template => $tid )
                    $data .= '<li class="template_item template" template="' . $template . '">' . $template . '</li>';

                $data .= '</ul>';
            }

            $data .= '</li>';
        }

        # Lazy as I hate the template system
        echo '
        <div class="saved">Saved the template!</div>
        <div class="template_editor">

        <div class="left_pane">
            <input class="search" type="text" spellcheck="false" autocomplete="off" placeholder="Search">
            <input class="save" type="submit" value="Save">
            <ul class="template_list">
                ' . $data . '
            </ul>
        </div>

        <div class="right_pane">
            <div class="main_1">
                <ul id="main_tabs" class="main_tabs"></ul>
            </div>
            <div id="aceEditor" class="main_editor"></div>
        </div>';

    } else {
        $page->output_header('Template Sets');

        $themes = array();

        $query = $db->simple_select("themes", "name,tid,properties", "tid != '1'");

        while ( $theme = $db->fetch_array($query) ) {
            $tbits = unserialize($theme['properties']);
            $themes[$tbits['templateset']][$theme['tid']] = htmlspecialchars_uni($theme['name']);
        }

        $template_sets              = array();
        $template_sets[-1]['title'] = 'Template Sets';
        $template_sets[-1]['sid']   = -1;

        $query = $db->simple_select("templatesets", "*", "", array('order_by' => 'title', 'order_dir' => 'ASC'));

        while ( $template_set = $db->fetch_array($query) )
            $template_sets[$template_set['sid']] = $template_set;

        $table = new Table;
        $table->construct_header('Template Set');
        $table->construct_header('Controls', array("class" => "align_center", "width" => 150));

        foreach ( $template_sets as $set ) {
            if ( $set['sid'] == -1 ) {
                $table->construct_cell("<strong><a href=\"index.php?module=style-editor&amp;sid=-1&amp;action=edit\">Global Templates</a></strong><br /><small>Used by all templates</small>");
                $table->construct_cell("<a href=\"index.php?module=style-editor&amp;sid=-1&amp;action=edit\">Edit</a>", array("class" => "align_center"));
                $table->construct_row();
                continue;
            }

            if ( $themes[$set['sid']] )
                $used_by_note = "Used by: " . implode(', ', $themes[$set['sid']]);
            else
                $used_by_note = "Not used by any theme";

            $actions = "<a href=\"index.php?module=style-editor&amp;sid={$set['sid']}&amp;action=edit\">Edit</a>";

            $table->construct_cell("<strong><a href=\"index.php?module=style-editor&amp;sid={$set['sid']}&amp;action=edit\">{$set['title']}</a></strong><br /><small>{$used_by_note}</small>");
            $table->construct_cell($actions, array("class" => "align_center"));
            $table->construct_row();
        }

        $table->output('Template sets');

    }

    $page->output_footer();
