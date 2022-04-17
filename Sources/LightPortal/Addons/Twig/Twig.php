<?php

/**
 * Twig.php
 *
 * @package Twig (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2021-2022 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 *
 * @category addon
 * @version 16.04.22
 */

namespace Bugo\LightPortal\Addons\Twig;

use Bugo\LightPortal\Addons\Plugin;

if (! defined('LP_NAME'))
	die('No direct access...');

/**
 * Generated by PluginMaker
 */
class Twig extends Plugin
{
	public string $icon = 'fab fa-symfony';
	public string $type = 'parser';

	public function init()
	{
		$this->context['lp_content_types']['twig'] = 'Twig';
	}

	public function addSettings(array &$config_vars)
	{
		$config_vars['twig'][] = ['check', 'debug_mode'];
	}

	public function parseContent(string &$content, string $type)
	{
		if ($type === 'twig')
			$content = $this->getParsedContent($content);
	}

	private function getParsedContent(string $text): string
	{
		require_once __DIR__ . '/vendor/autoload.php';

		try {
			$loader  = new \Twig\Loader\ArrayLoader(['content' => $text]);
			$twig    = new \Twig\Environment($loader, ['debug' => ! empty($this->context['lp_twig_plugin']['debug_mode'])]);
			$content = $twig->render('content', [
				'txt'         => $this->txt,
				'context'     => $this->context,
				'scripturl'   => $this->scripturl,
				'settings'    => $this->settings,
				'modSettings' => $this->modSettings,
			]);
		} catch (\Exception $e) {
			$content = $e->getMessage();
		}

		return $content;
	}

	public function credits(array &$links)
	{
		$links[] = [
			'title' => 'Twig',
			'link' => 'https://github.com/twigphp/Twig',
			'author' => 'Twig Team',
			'license' => [
				'name' => 'the BSD-3-Clause',
				'link' => 'https://github.com/twigphp/Twig/blob/3.x/LICENSE'
			]
		];
	}
}
