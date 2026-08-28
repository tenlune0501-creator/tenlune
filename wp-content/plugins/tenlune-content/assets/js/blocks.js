/**
 * Tenlune 블록 — 편집 화면.
 *
 * 실제 출력은 전부 PHP(render_callback)에서 만듭니다.
 * 여기서는 편집자가 "여기에 무엇이 들어오는지" 알아볼 자리표시만 그립니다.
 * 빌드 도구를 쓰지 않으므로 JSX 없이 createElement 로 씁니다.
 *
 * registerBlockType 은 title 과 category 가 없으면 등록 자체를 거부합니다.
 * PHP 에서 이미 등록했더라도 편집기 쪽에는 다시 알려줘야 합니다.
 */
( function ( blocks, element, blockEditor ) {
	'use strict';

	if ( ! blocks || ! element || ! blockEditor ) {
		return;
	}

	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;

	var BOX = {
		border: '1px dashed #8c8f94',
		background: '#f6f7f7',
		color: '#3c434a',
		padding: '0.9rem 1.1rem',
		fontSize: '13px',
		lineHeight: '1.6',
	};

	var LABEL = {
		display: 'block',
		fontSize: '11px',
		letterSpacing: '0.12em',
		textTransform: 'uppercase',
		color: '#646970',
		marginBottom: '0.35rem',
	};

	function register( name, title, note ) {
		if ( blocks.getBlockType && blocks.getBlockType( name ) ) {
			return;
		}

		blocks.registerBlockType( name, {
			apiVersion: 3,
			title: title,
			category: 'theme',
			icon: 'editor-table',
			supports: { html: false, anchor: true },
			usesContext: [ 'postId', 'postType' ],
			edit: function () {
				return el(
					'div',
					useBlockProps( { style: BOX } ),
					el( 'span', { style: LABEL }, title ),
					el( 'span', null, note )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	register(
		'tenlune/case-meta',
		'케이스 — 메타',
		'「케이스 정보」 상자에 입력한 참여 형태 · 역할 · 상태 · 기간 · 유형 · 라이브 링크가 여기에 나옵니다.'
	);

	register(
		'tenlune/case-problem',
		'케이스 — 한 줄 문제 정의',
		'「케이스 정보」의 한 줄 문제 정의가 여기에 나옵니다.'
	);

	register(
		'tenlune/case-toc',
		'케이스 — 목차',
		'본문의 제목(h2)으로 목차를 자동 생성하고, 같은 규칙의 앵커를 본문 제목에도 붙입니다. 제목이 3개 미만이면 출력하지 않습니다.'
	);

	register(
		'tenlune/case-log',
		'케이스 — 개선 기록',
		'「케이스 정보」의 개선 로그를 표로 출력합니다. 기록이 0개면 섹션 자체가 나오지 않습니다.'
	);

	register(
		'tenlune/contact-form',
		'문의 폼',
		'이름 · 이메일 · 문의 유형 · 만들고 싶은 것 · 참고 자료 · 일정 · 예산 · 개인정보 동의. 접수분은 「받은 문의」에 저장되고 관리자 메일로 전달됩니다.'
	);
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor );
