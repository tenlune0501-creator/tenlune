<?php
/**
 * 케이스 스터디용 동적 블록.
 *
 *  tenlune/case-meta     헤더 메타 (참여 형태 · 역할 · 상태 · 기간 · 라이브 링크)
 *  tenlune/case-problem  아카이브 카드의 한 줄 문제 정의
 *  tenlune/case-toc      본문 h2 로 자동 생성하는 목차 — 10개 섹션은 모바일에서 매우 깁니다
 *  tenlune/case-log      개선 로그. 항목이 0개면 아무것도 출력하지 않습니다
 *
 * 빌드 도구 없이 PHP 등록 + 순수 JS 편집 화면으로 구성했습니다.
 * 이 사이트에 npm 빌드 파이프라인을 얹을 이유가 아직 없습니다.
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 현재 문맥의 글 ID 를 찾습니다. 쿼리 루프 안에서도 동작해야 합니다.
 *
 * @param array $block 블록 인스턴스 정보.
 * @return int
 */
function tenlune_block_post_id( $block = null ) {
	if ( is_object( $block ) && isset( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}

	return (int) get_the_ID();
}

/**
 * 헤더 메타.
 *
 * @param array    $attributes 속성.
 * @param string   $content    내용.
 * @param WP_Block $block      블록.
 * @return string
 */
function tenlune_render_case_meta( $attributes, $content, $block = null ) {
	$post_id = tenlune_block_post_id( $block );

	if ( ! $post_id ) {
		return '';
	}

	$participation = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'participation', true );
	$role          = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'role', true );
	$status        = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'status', true );
	$started_on    = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'started_on', true );
	$live_url      = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'live_url', true );
	$duration      = tenlune_duration_text( $started_on );

	$is_team    = ( '팀 프로젝트' === $participation );
	$role_label = $is_team ? '담당' : '역할';

	$rows = array();

	if ( '' !== $participation ) {
		$rows[] = array( '참여 형태', esc_html( $participation ) );
	}
	if ( '' !== $role ) {
		$rows[] = array( $role_label, esc_html( $role ) );
	}
	if ( '' !== $status ) {
		$rows[] = array( '상태', esc_html( $status ) );
	}
	if ( '' !== $duration ) {
		$rows[] = array( '기간', esc_html( $duration ) );
	}

	$types = get_the_term_list( $post_id, 'case_type', '', ' · ' );

	if ( ! is_wp_error( $types ) && ! empty( $types ) ) {
		$rows[] = array( '유형', wp_kses_post( $types ) );
	}

	if ( '' !== $live_url ) {
		$rows[] = array(
			'라이브',
			sprintf(
				'<a href="%s" rel="noopener noreferrer" target="_blank">지금 열어보기 <span aria-hidden="true">↗</span></a>',
				esc_url( $live_url )
			),
		);
	}

	if ( empty( $rows ) ) {
		return '';
	}

	$out = sprintf( '<dl %s>', get_block_wrapper_attributes( array( 'class' => 'tl-case-meta' ) ) );

	foreach ( $rows as $row ) {
		$out .= sprintf(
			'<div><dt>%s</dt><dd>%s</dd></div>',
			esc_html( $row[0] ),
			$row[1]
		);
	}

	$out .= '</dl>';

	return $out;
}

/**
 * 아카이브 카드의 한 줄 문제 정의.
 *
 * @param array    $attributes 속성.
 * @param string   $content    내용.
 * @param WP_Block $block      블록.
 * @return string
 */
function tenlune_render_case_problem( $attributes, $content, $block = null ) {
	$post_id = tenlune_block_post_id( $block );
	$line    = (string) get_post_meta( $post_id, TENLUNE_META_PREFIX . 'problem_line', true );

	if ( '' === trim( $line ) ) {
		return '';
	}

	return sprintf(
		'<p %s>%s</p>',
		get_block_wrapper_attributes( array( 'class' => 'tl-card-p' ) ),
		esc_html( $line )
	);
}

/**
 * 케이스 본문의 h2 에 붙일 앵커 이름.
 *
 * 목차와 본문이 같은 규칙을 봐야 합니다. 한쪽만 바꾸면 목차는 만들어지는데
 * 눌러도 아무 데도 가지 않는 상태가 됩니다.
 *
 * @param int $index 문서에서 몇 번째 h2 인가 (0부터).
 * @return string
 */
function tenlune_case_anchor( $index ) {
	return 'sec-' . ( (int) $index + 1 );
}

/**
 * 렌더링된 본문에서 h2 를 훑어 [앵커, 제목] 목록을 만듭니다.
 *
 * 번호는 비어 있는 h2 까지 세어 매깁니다 — 본문에 id 를 넣는 쪽과
 * 세는 방식이 같아야 두 목록이 어긋나지 않습니다.
 *
 * @param string $html 렌더링된 본문.
 * @return array<int, array{0:string,1:string}>
 */
function tenlune_case_scan_headings( $html ) {
	$items = array();

	if ( ! preg_match_all( '#<h2\b([^>]*)>(.*?)</h2>#is', $html, $matches, PREG_SET_ORDER ) ) {
		return $items;
	}

	foreach ( $matches as $i => $match ) {
		$text = trim( wp_strip_all_tags( $match[2] ) );

		if ( '' === $text ) {
			continue;
		}

		if ( preg_match( '#\bid=["\']([^"\']+)["\']#i', $match[1], $id_match ) ) {
			$anchor = $id_match[1];
		} else {
			$anchor = tenlune_case_anchor( $i );
		}

		$items[] = array( $anchor, $text );
	}

	return $items;
}

/**
 * 본문 h2 에 id 를 넣습니다. 이미 앵커가 지정된 제목은 건드리지 않습니다.
 *
 * @param string $html 렌더링된 본문.
 * @return string
 */
function tenlune_case_inject_heading_ids( $html ) {
	$index = 0;

	return (string) preg_replace_callback(
		'#<h2\b([^>]*)>#i',
		static function ( $match ) use ( &$index ) {
			$attrs = $match[1];
			$out   = $match[0];

			if ( ! preg_match( '#\bid\s*=#i', $attrs ) ) {
				$out = '<h2 id="' . esc_attr( tenlune_case_anchor( $index ) ) . '"' . $attrs . '>';
			}

			++$index;

			return $out;
		},
		$html
	);
}

/**
 * 케이스 상세 화면에서만 본문 제목에 앵커를 붙입니다.
 *
 * @param string $content 본문.
 * @return string
 */
function tenlune_case_content_anchors( $content ) {
	if ( ! is_singular( 'case' ) ) {
		return $content;
	}

	return tenlune_case_inject_heading_ids( $content );
}
add_filter( 'the_content', 'tenlune_case_content_anchors', 20 );

/**
 * 본문 h2 로 목차를 만듭니다.
 *
 * 편집한 제목을 그대로 따라가야 하므로 목록을 손으로 관리하지 않습니다.
 * 본문은 do_blocks() 로만 펼칩니다 — the_content 를 다시 돌리면 이 블록을
 * 부르는 필터와 서로를 부르는 상황이 생길 수 있습니다.
 *
 * @param array    $attributes 속성.
 * @param string   $content    내용.
 * @param WP_Block $block      블록.
 * @return string
 */
function tenlune_render_case_toc( $attributes, $content, $block = null ) {
	$post_id = tenlune_block_post_id( $block );
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$items = tenlune_case_scan_headings( do_blocks( $post->post_content ) );

	if ( count( $items ) < 3 ) {
		return '';
	}

	$out  = sprintf( '<nav %s aria-label="이 사례의 목차">', get_block_wrapper_attributes( array( 'class' => 'tl-toc' ) ) );
	$out .= '<h2>목차</h2><ol>';

	foreach ( $items as $i => $item ) {
		$out .= sprintf(
			'<li><a href="#%s"><span class="n">%02d</span><span>%s</span></a></li>',
			esc_attr( $item[0] ),
			$i + 1,
			esc_html( $item[1] )
		);
	}

	$out .= '</ol></nav>';

	return $out;
}

/**
 * 개선 로그. 항목이 0개면 섹션 자체가 렌더링되지 않습니다.
 *
 * @param array    $attributes 속성.
 * @param string   $content    내용.
 * @param WP_Block $block      블록.
 * @return string
 */
function tenlune_render_case_log( $attributes, $content, $block = null ) {
	$post_id = tenlune_block_post_id( $block );
	$rows    = get_post_meta( $post_id, TENLUNE_META_PREFIX . 'change_log', true );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return '';
	}

	$out  = sprintf( '<section %s>', get_block_wrapper_attributes( array( 'class' => 'tl-case-sec' ) ) );
	$out .= '<h2 id="sec-log"><span class="n">LOG</span><span>개선 기록</span></h2>';
	$out .= '<div class="tl-log">';

	foreach ( $rows as $row ) {
		$date   = isset( $row['date'] ) ? (string) $row['date'] : '';
		$kind   = isset( $row['kind'] ) ? (string) $row['kind'] : '';
		$issue  = isset( $row['issue'] ) ? (string) $row['issue'] : '';
		$action = isset( $row['action'] ) ? (string) $row['action'] : '';

		$out .= '<div class="tl-log-row">';
		$out .= sprintf( '<span class="tl-log-date">%s</span>', esc_html( $date ) );
		$out .= sprintf(
			'<span class="tl-log-kind" data-kind="%s">%s</span>',
			esc_attr( $kind ),
			esc_html( '즉시' === $kind ? '즉시 수정' : $kind )
		);
		$out .= '<span class="tl-log-body">' . esc_html( $issue );

		if ( '' !== $action ) {
			$out .= '<span class="fix">→ ' . esc_html( $action ) . '</span>';
		}

		$out .= '</span></div>';
	}

	$out .= '</div></section>';

	return $out;
}

/**
 * 블록 등록.
 */
function tenlune_register_blocks() {
	$common = array(
		'api_version'     => 3,
		'category'        => 'theme',
		'supports'        => array(
			'html'   => false,
			'anchor' => true,
		),
		'uses_context'    => array( 'postId', 'postType' ),
		'editor_script'   => 'tenlune-blocks',
	);

	register_block_type(
		'tenlune/case-meta',
		array_merge(
			$common,
			array(
				'title'           => __( '케이스 — 메타', 'tenlune-content' ),
				'description'     => __( '참여 형태 · 역할 · 상태 · 기간 · 유형 · 라이브 링크.', 'tenlune-content' ),
				'render_callback' => 'tenlune_render_case_meta',
			)
		)
	);

	register_block_type(
		'tenlune/case-problem',
		array_merge(
			$common,
			array(
				'title'           => __( '케이스 — 한 줄 문제 정의', 'tenlune-content' ),
				'description'     => __( '아카이브 카드에 쓰는 한 줄.', 'tenlune-content' ),
				'render_callback' => 'tenlune_render_case_problem',
			)
		)
	);

	register_block_type(
		'tenlune/case-toc',
		array_merge(
			$common,
			array(
				'title'           => __( '케이스 — 목차', 'tenlune-content' ),
				'description'     => __( '본문의 h2 로 자동 생성합니다. 제목이 3개 미만이면 나오지 않습니다.', 'tenlune-content' ),
				'render_callback' => 'tenlune_render_case_toc',
			)
		)
	);

	register_block_type(
		'tenlune/case-log',
		array_merge(
			$common,
			array(
				'title'           => __( '케이스 — 개선 기록', 'tenlune-content' ),
				'description'     => __( '「케이스 정보」의 개선 로그를 출력합니다. 항목이 0개면 아무것도 나오지 않습니다.', 'tenlune-content' ),
				'render_callback' => 'tenlune_render_case_log',
			)
		)
	);

	register_block_type(
		'tenlune/contact-form',
		array_merge(
			$common,
			array(
				'title'           => __( '문의 폼', 'tenlune-content' ),
				'description'     => __( '설계안 11번의 문의 폼. 논스가 매번 새로 발급되어야 하므로 서버에서 그립니다.', 'tenlune-content' ),
				'render_callback' => 'tenlune_render_contact_form',
			)
		)
	);
}
add_action( 'init', 'tenlune_register_blocks', 30 );

/**
 * 편집 화면 스크립트. 빌드 없이 순수 JS 로 자리표시만 그립니다.
 */
function tenlune_register_block_script() {
	wp_register_script(
		'tenlune-blocks',
		TENLUNE_CONTENT_URL . 'assets/js/blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n' ),
		TENLUNE_CONTENT_VERSION,
		true
	);
}
add_action( 'init', 'tenlune_register_block_script', 5 );
