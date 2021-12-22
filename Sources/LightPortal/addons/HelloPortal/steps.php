<?php

global $txt, $modSettings;

return [
	'basic_settings' => '
		{
			element: document.getElementById("admin_content"),
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][0] . '",
			position: "right"
		},
		{
			element: document.getElementById("lp_frontpage_mode"),
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][1] . '"
		},' . (! empty($modSettings['lp_frontpage_mode']) && $modSettings['lp_frontpage_mode'] !== 'chosen_page' ? ('
		{
			element: document.getElementById("lp_frontpage_order_by_num_replies"),
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][2] . '"
		},') : '') . '
		{
			element: document.getElementById("setting_lp_standalone_mode").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][3] . '"
		},
		{
			element: document.getElementById("setting_lp_prohibit_php").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][4] . '"
		},
		{
			element: document.querySelector(".fa-info-circle"),
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][5] . '"
		},
		{
			element: document.querySelector(".information"),
			intro: "' . $txt['lp_hello_portal']['basic_settings_tour'][6] . '"
		}',
	'extra_settings' => '
		{
			element: document.getElementById("setting_lp_show_tags_on_page").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['extra_settings_tour'][0] . '",
			position: "right"
		},
		{
			element: document.getElementById("setting_lp_fa_source").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['extra_settings_tour'][1] . '"
		},',
	'categories' => '
		{
			element: document.querySelector(".lp_categories dd"),
			intro: "' . $txt['lp_hello_portal']['categories_tour'][0] . '"
		},
		{
			element: document.querySelector(".lp_categories dt"),
			intro: "' . $txt['lp_hello_portal']['categories_tour'][1] . '"
		},',
	'panels' => '
		{
			element: document.querySelector(".generic_list_wrapper"),
			intro: "' . $txt['lp_hello_portal']['panels_tour'][0] . '"
		},
		{
			element: document.getElementById("lp_left_panel_width[md]").parentNode.parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][1] . '"
		},
		{
			element: document.getElementById("setting_lp_swap_header_footer").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][2] . '"
		},
		{
			element: document.querySelector("label[for=lp_panel_direction_header]").parentNode.parentNode.parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][3] . '"
		},',
	'misc' => '
		{
			element: document.getElementById("setting_lp_show_debug_info_help").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][0] . '"
		},
		{
			element: document.getElementById("setting_lp_portal_action").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][1] . '"
		},
		{
			element: document.getElementById("lp_weekly_cleaning").parentNode.parentNode,
			intro: "' . $txt['lp_hello_portal']['panels_tour'][2] . '"
		}',
	'blocks' => '
		{
			element: document.getElementById("admin_content"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][0] . '",
			position: "right"
		},
		{
			element: document.querySelector("#adm_submenus + .cat_bar"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][1] . '"
		},
		{
			element: document.querySelector("tbody[data-placement=header]"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][2] . '"
		},
		{
			element: document.querySelector("td[class=status]"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][3] . '"
		},
		{
			element: document.querySelector("td[class=actions]"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][4] . '"
		},
		{
			element: document.querySelector("td[class=priority]"),
			intro: "' . $txt['lp_hello_portal']['blocks_tour'][5] . '"
		}',
	'pages' => '
		{
			element: document.getElementById("admin_content"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][0] . '",
			position: "right"
		},
		{
			element: document.querySelector("tbody tr"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][1] . '"
		},
		{
			element: document.querySelector("td.date"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][2] . '"
		},
		{
			element: document.querySelector("td.num_views"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][3] . '"
		},
		{
			element: document.querySelector("td.alias"),
			intro: "' . sprintf($txt['lp_hello_portal']['pages_tour'][4], '<strong>?' . LP_PAGE_PARAM . '=</strong>') . '"
		},
		{
			element: document.querySelector("td.status"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][5] . '"
		},
		{
			element: document.querySelector("td.actions"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][6] . '"
		},
		{
			element: document.querySelector(".additional_row input[type=search]"),
			intro: "' . $txt['lp_hello_portal']['pages_tour'][7] . '"
		}',
	'plugins' => '
		{
			element: document.getElementById("admin_content"),
			intro: "' . $txt['lp_hello_portal']['plugins_tour'][0] . '",
			position: "right"
		},
		{
			element: document.getElementById("filter"),
			intro: "' . $txt['lp_hello_portal']['plugins_tour'][1] . '"
		},
		{
			element: document.querySelector("#admin_content .windowbg"),
			intro: "' . $txt['lp_hello_portal']['plugins_tour'][2] . '",
			position: "right"
		},
		{
			element: document.querySelector(".features .lp_plugin_settings"),
			intro: "' . $txt['lp_hello_portal']['plugins_tour'][3] . '"
		},
		{
			element: document.querySelector(".features .lp_plugin_toggle"),
			intro: "' . $txt['lp_hello_portal']['plugins_tour'][4] . '"
		}',
	'add_plugins' => '
		{
			element: document.getElementById("lp_post"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][0] . '",
			position: "right"
		},
		{
			element: document.getElementById("name"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][1] . '"
		},
		{
			element: document.querySelector(".pf_type div"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][2] . '"
		},
		{
			element: document.getElementById("description_english"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][3] . '"
		},
		{
			element: document.querySelector("label[for=tab2]"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][4] . '"
		},
		{
			element: document.querySelector("label[for=tab3]"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][5] . '"
		},
		{
			element: document.querySelector("label[for=tab4]"),
			intro: "' . $txt['lp_hello_portal']['add_plugins_tour'][6] . '"
		}'
];
