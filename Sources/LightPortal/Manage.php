<?php

namespace Bugo\LightPortal;

/**
 * Manage.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2021 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 1.8
 */

trait Manage
{
	/**
	 * @see https://github.com/brianvoe/slim-select
	 *
	 * @return void
	 */
	public static function improveSelectFields()
	{
		loadCSSFile('https://cdn.jsdelivr.net/npm/slim-select@1/dist/slimselect.min.css', array('external' => true));
		//loadJavaScriptFile('https://cdn.jsdelivr.net/npm/slim-select@1/dist/slimselect.min.js', array('external' => true));
		loadJavaScriptFile('light_portal/slimselect.min.js');

		addInlineCss('
		.ss-content.ss-open {
			position: initial;
		}
		.ss-disabled {
			color: inherit !important;
		}
		.ss-main .ss-single-selected {
			height: auto;
		}
		.placeholder > div {
			margin: 0 !important;
		}');

		self::prepareIconList();
	}

	/**
	 * Prepare field array with entity options
	 *
	 * Формируем массив полей с настройками сущности
	 *
	 * @return void
	 */
	public function preparePostFields()
	{
		global $context;

		foreach ($context['posting_fields'] as $item => $data) {
			if ($item !== 'icon' && !empty($data['input']['after']))
				$context['posting_fields'][$item]['input']['after'] = '<div class="descbox alternative2 smalltext">' . $data['input']['after'] . '</div>';

			if (isset($data['input']['type']) && $data['input']['type'] == 'checkbox') {
				$data['input']['attributes']['class'] = 'checkbox';
				$data['input']['after'] = '<label class="label" for="' . $data['input']['attributes']['id'] . '"></label>' . ($context['posting_fields'][$item]['input']['after'] ?? '');
				$context['posting_fields'][$item] = $data;
			}

			if (empty($data['input']['tab']))
				$context['posting_fields'][$item]['input']['tab'] = 'tuning';
		}

		loadTemplate('LightPortal/ManageSettings');
	}

	/**
	 * @return void
	 */
	private static function prepareIconList()
	{
		global $smcFunc;

		if (Helpers::request()->has('icons') === false)
			return;

		$data = Helpers::request()->json();

		if (empty($search = $data['search']))
			return;

		$search = trim($smcFunc['strtolower']($search));

		$all_icons = Helpers::getFaIcons();
		$all_icons = array_filter($all_icons, function ($item) use ($search) {
			return strpos($item, $search) !== false;
		});

		$results = [];
		foreach ($all_icons as $icon) {
			$results[] = [
				'innerHTML' => "<i class=\"$icon\"></i>&nbsp;$icon",
				'text'      => $icon
			];
		}

		exit(json_encode($results));
	}
}
