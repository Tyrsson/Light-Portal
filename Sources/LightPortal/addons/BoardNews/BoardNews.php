<?php

/**
 * BoardNews.php
 *
 * @package BoardNews (Light Portal)
 * @link https://custom.simplemachines.org/index.php?mod=4244
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @category addon
 * @version 15.12.21
 */

namespace Bugo\LightPortal\Addons\BoardNews;

use Bugo\LightPortal\Addons\Plugin;
use Bugo\LightPortal\Helper;

class BoardNews extends Plugin
{
	public string $icon = 'fas fa-newspaper';

	public function blockOptions(array &$options)
	{
		$options['board_news']['parameters'] = [
			'board_id'  => 0,
			'num_posts' => 5,
		];
	}

	public function validateBlockData(array &$parameters, string $type)
	{
		if ($type !== 'board_news')
			return;

		$parameters['board_id']  = FILTER_VALIDATE_INT;
		$parameters['num_posts'] = FILTER_VALIDATE_INT;
	}

	private function getBoardList(): array
    {
		global $modSettings, $context;

		Helper::require('Subs-MessageIndex');

		$boardListOptions = array(
			'ignore_boards'   => false,
			'use_permissions' => true,
			'not_redirection' => true,
			'excluded_boards' => empty($modSettings['recycle_board']) ? null : array((int) $modSettings['recycle_board']),
			'selected_board'  => empty($context['lp_block']['options']['parameters']['board_id']) ? false : $context['lp_block']['options']['parameters']['board_id']
		);

		return getBoardList($boardListOptions);
	}

	public function prepareBlockFields()
	{
		global $context, $txt;

		if ($context['lp_block']['type'] !== 'board_news')
			return;

		$context['posting_fields']['board_id']['label']['text'] = $txt['lp_board_news']['board_id'];
		$context['posting_fields']['board_id']['input'] = array(
			'type' => 'select',
			'attributes' => array(
				'id' => 'board_id'
			),
			'options' => array()
		);

		$board_list = $this->getBoardList();
		foreach ($board_list as $category) {
			$context['posting_fields']['board_id']['input']['options'][$category['name']] = array('options' => array());

			foreach ($category['boards'] as $board) {
				$context['posting_fields']['board_id']['input']['options'][$category['name']]['options'][$board['name']] = array(
					'value'    => $board['id'],
					'selected' => (bool) $board['selected'],
					'label'    => ($board['child_level'] > 0 ? str_repeat('==', $board['child_level'] - 1) . '=&gt;' : '') . ' ' . $board['name']
				);
			}
		}

		$context['posting_fields']['num_posts']['label']['text'] = $txt['lp_board_news']['num_posts'];
		$context['posting_fields']['num_posts']['input'] = array(
			'type' => 'number',
			'attributes' => array(
				'id'    => 'num_posts',
				'min'   => 1,
				'value' => $context['lp_block']['options']['parameters']['num_posts']
			)
		);
	}

	public function getData(array $parameters): array
	{
		$this->loadSsi();

		return ssi_boardNews($parameters['board_id'], $parameters['num_posts'], null, null, 'array');
	}

	public function prepareContent(string $type, int $block_id, int $cache_time, array $parameters)
	{
		global $user_info, $txt, $modSettings, $scripturl, $context;

		if ($type !== 'board_news')
			return;

		$board_news = Helper::cache('board_news_addon_b' . $block_id . '_u' . $user_info['id'])
			->setLifeTime($cache_time)
			->setFallback(__CLASS__, 'getData', $parameters);

		if (empty($board_news))
			return;

		foreach ($board_news as $news) {
			$news['link'] = '<a href="' . $news['href'] . '">' . Helper::getPluralText($news['replies'], $txt['lp_comments_set']) . '</a>';

			echo '
			<div class="news_item">
				<h3 class="news_header">
					', $news['icon'], '
					<a href="', $news['href'], '">', $news['subject'], '</a>
				</h3>
				<div class="news_timestamp">', $news['time'], ' ', $txt['by'], ' ', $news['poster']['link'], '</div>
				<div class="news_body" style="padding: 2ex 0">', $news['body'], '</div>
				', $news['link'], ($news['locked'] ? '' : ' | ' . $news['comment_link']), '';

			if (! empty($modSettings['enable_likes'])) {
				echo '
					<ul>';

				if (! empty($news['likes']['can_like'])) {
					echo '
						<li class="smflikebutton" id="msg_', $news['message_id'], '_likes"><a href="', $scripturl, '?action=likes;ltype=msg;sa=like;like=', $news['message_id'], ';', $context['session_var'], '=', $context['session_id'], '" class="msg_like"><span class="', ($news['likes']['you'] ? 'unlike' : 'like'), '"></span>', ($news['likes']['you'] ? $txt['unlike'] : $txt['like']), '</a></li>';
				}

				if (! empty($news['likes']['count'])) {
					$context['some_likes'] = true;
					$count = $news['likes']['count'];
					$base = 'likes_';
					if ($news['likes']['you']) {
						$base = 'you_' . $base;
						$count--;
					}
					$base .= (isset($txt[$base . $count])) ? $count : 'n';

					echo '
						<li class="like_count smalltext">', sprintf($txt[$base], $scripturl . '?action=likes;sa=view;ltype=msg;like=' . $news['message_id'] . ';' . $context['session_var'] . '=' . $context['session_id'], comma_format($count)), '</li>';
				}

				echo '
					</ul>';
			}

			echo '
			</div>';

			if (! $news['is_last'])
				echo '
			<hr>';
		}
	}
}
