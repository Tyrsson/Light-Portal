document.addEventListener('DOMContentLoaded', function () {
	"use strict";

	const pageComments = document.getElementById('page_comments'),
		commentForm = document.getElementById('comment_form'),
		message = document.getElementById('message');

	let current_comment, current_comment_text;

	// Increase a message height on focusing
	message.addEventListener('focus', function () {
		this.style.height = 'auto';

		if (commentForm.querySelector('.toolbar'))
			commentForm.querySelector('.toolbar').style.display = 'block';

		commentForm.comment.style.display = 'block';
	}, false);

	// Disabled/enabled a submit button on textarea changing
	message.addEventListener('keyup', function () {
		commentForm.comment.disabled = !this.value;
	}, false);

	// Post/remove comments & paste nickname to comment reply form
	pageComments.addEventListener('click', function (e) {
		for (let target = e.target; target && target !== this; target = target.parentNode) {
			if (target.matches('span.reply_button')) {
				lpLeaveReply.call(target);
				break;
			}

			if (target.matches('span.modify_button')) {
				lpModifyComment.call(target);
				break;
			}

			if (target.matches('span.update_button')) {
				lpUpdateComment.call(target);
				break;
			}

			if (target.matches('span.cancel_button')) {
				lpCancelComment.call(target);
				break;
			}

			if (target.matches('span.remove_button')) {
				lpRemoveComment.call(target);
				break;
			}

			if (target.matches('.title > span')) {
				lpPasteNickname.call(target);
				break;
			}

			if (target.matches('.toolbar > .button')) {
				lpPressButton.call(target);
				break;
			}
		}
	}, false);

	function insertTags(open, close) {
		let start = message.selectionStart,
			end = message.selectionEnd,
			text = message.value;

		message.value = text.substring(0, start) + open + text.substring(start, end) + close + text.substring(end);
		message.focus();

		let sel = open.length + end;
		message.setSelectionRange(sel, sel);

		return false;
	}

	function lpPressButton() {
		let button = this.children[0].classList[1];

		if (!button) return;

		switch (button) {
			case 'fa-bold':
				return insertTags('[b]', '[/b]');

 			case 'fa-italic':
				return insertTags('[i]', '[/i]');

			case 'fa-list-ul':
				return insertTags(`[list]\n[li]`, `[/li]\n[li][/li]\n[/list]`);

			case 'fa-list-ol':
				return insertTags(`[list type=decimal]\n[li]`, `[/li]\n[li][/li]\n[/list]`);

			case 'fa-youtube':
				return insertTags('[youtube]', '[/youtube]');

			case 'fa-image':
				return insertTags('[img]', '[/img]');

			case 'fa-link':
				return insertTags('[url]', '[/url]');

			case 'fa-code':
				return insertTags('[code]', '[/code]');

			case 'fa-quote-right':
				return insertTags('[quote]', '[/quote]');
		}
	}

	function lpLeaveReply() {
		const parentId = this.getAttribute('data-id'),
			parentLiItem = document.getElementById('comment' + parentId),
			counter = parentLiItem.getAttribute('data-counter'),
			level = parentLiItem.getAttribute('data-level'),
			start = parentLiItem.getAttribute('data-start'),
			commentator = parentLiItem.getAttribute('data-commentator');

		commentForm.parent_id.value = parentId;
		commentForm.counter.value = counter;
		commentForm.level.value = level;
		commentForm.start.value = start;
		commentForm.commentator.value = commentator;

		message.focus();
	}

	async function lpModifyComment() {
		const item = this.getAttribute('data-id'),
			comment_content = document.querySelector('#comment' + item + ' .content'),
			comment_raw_content = document.querySelector('#comment' + item + ' .raw_content'),
			modify_button = document.querySelector('#comment' + item + ' .modify_button'),
			update_button = document.querySelector('#comment' + item + ' .update_button'),
			cancel_button = document.querySelector('#comment' + item + ' .cancel_button');

		modify_button.style.display = 'none';
		update_button.style.display = 'inline-block';
		cancel_button.style.display = 'inline-block';

		current_comment = comment_content.innerHTML;
		comment_content.innerText = !current_comment_text ? comment_raw_content.innerText : current_comment_text;

		comment_content.setAttribute('contenteditable', true);
		comment_content.style.boxShadow = 'inset 2px 2px 5px rgba(154, 147, 140, 0.5), 1px 1px 5px rgba(255, 255, 255, 1)';
		comment_content.style.borderRadius = '4px';
		comment_content.style.padding = '1em';
		comment_content.focus();

		if (document.queryCommandSupported('selectAll'))
			document.execCommand('selectAll', false, null);
	}

	async function lpUpdateComment() {
		const item = this.getAttribute('data-id'),
			message = document.querySelector('#comment' + item + ' .content');

		if (!item) return;

		current_comment_text = message.innerText;

		let response = await fetch(PAGE_URL + 'sa=edit_comment', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json; charset=utf-8'
			},
			body: JSON.stringify({
				comment_id: item,
				message: message.innerHTML
			})
		});

		if (response.ok) {
			let comment = await response.json();

			lpCancelComment(this, comment)
		} else {
			console.error(response)
		}
	}

	function lpCancelComment(e, source = current_comment) {
		const item = (e ? e : this).getAttribute('data-id'),
			comment_content = document.querySelector('#comment' + item + ' .content'),
			modify_button = document.querySelector('#comment' + item + ' .modify_button'),
			update_button = document.querySelector('#comment' + item + ' .update_button'),
			cancel_button = document.querySelector('#comment' + item + ' .cancel_button');

		comment_content.innerHTML = source;
		comment_content.setAttribute('contenteditable', false);
		comment_content.style.boxShadow = 'none';
		comment_content.style.borderRadius = 0;
		comment_content.style.padding = '0 14px';

		cancel_button.style.display = 'none';
		update_button.style.display = 'none';
		modify_button.style.display = 'inline-block';
	}

	async function lpRemoveComment() {
		if (!confirm(smf_you_sure))
			return false;

		const item = this.getAttribute('data-id');

		if (item) {
			const items = [item],
				commentTree = document.querySelectorAll('li[data-id="' + item + '"] li'),
				removedItem = this.closest('li');

			commentTree.forEach(function (el) {
				items.push(el.getAttribute('data-id'))
			});

			let response = await fetch(PAGE_URL + 'sa=del_comment', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json; charset=utf-8'
				},
				body: JSON.stringify({
					items
				})
			});

			if (response.ok) {
				removedItem.remove()
			} else {
				console.error(response)
			}
		}
	}

	function lpPasteNickname() {
		const commentTextarea = message.value,
			position = message.selectionStart,
			nickname = this.innerText + ', ';

		message.value = commentTextarea.substring(0, position) + nickname + commentTextarea.substring(position);

		if (this.parentNode.parentNode.children[3]) {
			this.parentNode.parentNode.children[3].children[0].click();
		} else {
			pageComments.querySelector('span[data-id="' + this.getAttribute('data-parent') + '"]').click();
		}
	}

	// Post a comment on form submitting
	pageComments.addEventListener('submit', function (e) {
		for (let target = e.target; target && target !== this; target = target.parentNode) {
			if (target.matches('[id="comment_form"]')) {
				lpSubmitForm.call(target, e);
				break;
			}
		}
	}, false);

	async function lpSubmitForm(e) {
		e.preventDefault();

		let response = await fetch(PAGE_URL + 'sa=new_comment', {
			method: 'POST',
			body: new FormData(this)
		});

		if (response.ok) {
			let data = await response.json(),
				comment = data.comment;

			if (data.parent) {
				const liElem = document.querySelector('li[data-id="' + data.parent + '"]'),
					commentList = liElem.querySelector('ul.comment_list'),
					commentWrap = liElem.querySelector('.comment_wrapper');

				if (commentList) {
					commentList.insertAdjacentHTML('beforeend', comment);
					commentList.style.transition = 'height 3s';
				} else {
					commentWrap.insertAdjacentHTML('beforeend', '<ul class="comment_list row"></ul>');
					commentWrap.querySelector('ul.comment_list').insertAdjacentHTML('beforeend', comment);
					commentWrap.querySelector('ul.comment_list').style.transition = 'height 3s';
				}
			} else {
				const allComments = pageComments.querySelector('ul.comment_list')

				if (allComments) {
					allComments.insertAdjacentHTML('beforeend', comment);
					allComments.style.transition = 'height 3s';
				} else {
					pageComments.insertAdjacentHTML('afterbegin', '<ul class="comment_list row"></ul>');
					pageComments.querySelector('ul.comment_list').insertAdjacentHTML('beforeend', comment);
					pageComments.querySelector('ul.comment_list').style.transition = 'height 3s';
				}
			}

			message.style.height = '30px';

			commentForm.reset();

			if (commentForm.querySelector('.toolbar'))
				commentForm.querySelector('.toolbar').style.display = 'none';

			commentForm.comment.style.display = 'none';
			commentForm.parent_id.value = 0;

			if (! window.location.search) {
				if (window.location.pathname.match(/start./i) && parseInt(window.location.pathname.split('start.')[1].match(/\d+/) ?? 0) == commentForm.start.value) {
					window.location.hash = '#comment' + data.item;
				} else {
					window.location = window.origin + window.location.pathname.replace(/(start.)\d+/i, '$1' + PAGE_START) + '#comment' + data.item;
				}
			} else {
				let hasStartParam = window.location.search.match(/start./i);

				if (! hasStartParam) {
					if (! commentForm.start.value) {
						window.location.hash = '#comment' + data.item;
					} else {
						window.location.hash = '';
						window.location = window.location.origin + window.location.pathname + window.location.search + ';start=' + PAGE_START + '#comment' + data.item;
					}
				} else if (hasStartParam && parseInt(window.location.search.split('start=')[1].match(/\d+/) ?? 0) == commentForm.start.value) {
					window.location.hash = '#comment' + data.item;
				} else {
					window.location.hash = '';
					window.location = window.location.origin + window.location.pathname + window.location.search.replace(/(start.)\d+/i, '$1' + PAGE_START) + '#comment' + data.item;
				}
			}

			commentForm.start.value = PAGE_START;
		} else {
			console.error(response);
		}
	}

}, false);