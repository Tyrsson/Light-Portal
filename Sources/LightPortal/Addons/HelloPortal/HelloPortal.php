<?php

/**
 * HelloPortal.php
 *
 * @package HelloPortal (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2021-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 05.01.22
 */

namespace Bugo\LightPortal\Addons\HelloPortal;

use Bugo\LightPortal\Addons\Plugin;

/**
 * Generated by PluginMaker
 */
class HelloPortal extends Plugin
{
	public string $type = 'other';

	private array $themes = [false, 'royal', 'nassim', 'nazanin', 'dark', 'modern', 'flattener'];

	public function init()
	{
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menuButtons#', false, __FILE__);
	}

	public function menuButtons()
	{
		if ($this->request()->isNot('admin') || empty($steps = $this->getStepData()))
			return;

		loadLanguage('Post');

		if (! empty($this->context['admin_menu_name']) && ! empty($this->context[$this->context['admin_menu_name']]) && ! empty($this->context[$this->context['admin_menu_name']]['tab_data']['title']))
			$this->context[$this->context['admin_menu_name']]['tab_data']['title'] .= '<button class="button floatnone lp_hello_portal_button" @click.prevent="runTour()" x-data>' . $this->txt['lp_hello_portal']['tour_button'] . '</button>';

		loadCSSFile('https://cdn.jsdelivr.net/npm/intro.js@4/minified/introjs.min.css', ['external' => true]);

		if (! empty($this->modSettings['lp_hello_portal_addon_theme']))
			loadCSSFile('https://cdn.jsdelivr.net/npm/intro.js@4/themes/introjs-' . $this->modSettings['lp_hello_portal_addon_theme'] . '.css', ['external' => true]);

		if ($this->context['right_to_left'])
			loadCSSFile('https://cdn.jsdelivr.net/npm/intro.js@4/minified/introjs-rtl.min.css', ['external' => true]);

		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/intro.js@4/minified/intro.min.js', ['external' => true]);

		addInlineJavaScript('
		function runTour() {
			introJs().setOptions({
				tooltipClass: "lp_addon_hello_portal",
				nextLabel: ' . JavaScriptEscape($this->txt['previous_next_forward']) . ',
				prevLabel: ' . JavaScriptEscape($this->txt['previous_next_back']) . ',
				doneLabel: ' . JavaScriptEscape($this->txt['announce_done']) . ',
				steps: [' . $steps . '],
				showProgress: ' . (empty($this->modSettings['lp_hello_portal_addon_show_progress']) ? 'false' : 'true') . ',
				showButtons: ' . (empty($this->modSettings['lp_hello_portal_addon_show_buttons']) ? 'false' : 'true') . ',
				showBullets: false,
				exitOnOverlayClick: ' . (empty($this->modSettings['lp_hello_portal_addon_exit_on_overlay_click']) ? 'false' : 'true') . ',
				keyboardNavigation: ' . (empty($this->modSettings['lp_hello_portal_addon_keyboard_navigation']) ? 'false' : 'true') . ',
				disableInteraction: ' . (empty($this->modSettings['lp_hello_portal_addon_disable_interaction']) ? 'false' : 'true') . ',
				scrollToElement: true,
				scrollTo: "tooltip"
			}).start();
		}');
	}

	public function addSettings(array &$config_vars)
	{
		$addSettings = [];
		if (! isset($this->modSettings['lp_hello_portal_addon_show_progress']))
			$addSettings['lp_hello_portal_addon_show_progress'] = 1;
		if (! isset($this->modSettings['lp_hello_portal_addon_show_buttons']))
			$addSettings['lp_hello_portal_addon_show_buttons'] = 1;
		if (! isset($this->modSettings['lp_hello_portal_addon_keyboard_navigation']))
			$addSettings['lp_hello_portal_addon_keyboard_navigation'] = 1;
		if ($addSettings)
			updateSettings($addSettings);

		$config_vars['hello_portal'][] = ['select', 'theme', array_combine($this->themes, $this->txt['lp_hello_portal']['theme_set'])];
		$config_vars['hello_portal'][] = ['check', 'show_progress'];
		$config_vars['hello_portal'][] = ['check', 'show_buttons'];
		$config_vars['hello_portal'][] = ['check', 'exit_on_overlay_click'];
		$config_vars['hello_portal'][] = ['check', 'keyboard_navigation'];
		$config_vars['hello_portal'][] = ['check', 'disable_interaction'];
	}

	public function credits(array &$links)
	{
		$links[] = [
			'title' => 'Intro.js',
			'link' => 'https://github.com/usablica/intro.js',
			'author' => 'Afshin Mehrabani',
			'license' => [
				'name' => 'GNU AGPLv3',
				'link' => 'https://github.com/usablica/intro.js/blob/master/license.md'
			]
		];
	}

	private function getStepData(): string
	{
		$steps = require_once __DIR__ . DIRECTORY_SEPARATOR . 'steps.php';

		if ($this->isCurrentArea('lp_settings', 'basic'))
			return $steps['basic_settings'];

		if ($this->isCurrentArea('lp_settings', 'extra', false))
			return $steps['extra_settings'];

		if ($this->isCurrentArea('lp_settings', 'categories', false))
			return $steps['categories'];

		if ($this->isCurrentArea('lp_settings', 'panels', false))
			return $steps['panels'];

		if ($this->isCurrentArea('lp_settings', 'misc', false))
			return $steps['misc'];

		if ($this->isCurrentArea('lp_blocks'))
			return $steps['blocks'];

		if ($this->isCurrentArea('lp_pages'))
			return $steps['pages'];

		if ($this->isCurrentArea('lp_plugins'))
			return $steps['plugins'];

		if ($this->isCurrentArea('lp_plugins', 'add', false))
			return $steps['add_plugins'];

		return '';
	}

	private function isCurrentArea(string $area, string $sa = 'main', bool $canBeEmpty = true): bool
	{
		return $this->request()->has('area') && $this->request('area') === $area &&
			($canBeEmpty ? ($this->context['current_subaction'] === $sa || empty($this->context['current_subaction'])) : $this->context['current_subaction'] === $sa);
	}
}
