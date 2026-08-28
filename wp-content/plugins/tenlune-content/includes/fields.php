<?php
/**
 * 케이스 스터디 커스텀 필드.
 *
 * 구현 사양 07번의 표를 그대로 옮겼습니다.
 *
 *  participation  선택 (개인 / 팀)   메타 「참여 형태」
 *  role           텍스트             메타 「역할」 · 팀이면 「담당」
 *  status         텍스트             메타 「상태」
 *  started_on     날짜               기간 자동 계산 — 수치를 직접 쓰지 않기 위한 장치
 *  live_url       URL                케이스 상세 헤더
 *  problem_line   텍스트             아카이브 카드 한 줄
 *  change_log     반복 필드          날짜 / 문제 / 분류 / 조치
 *
 * change_log 는 지금 만들어 두지 않으면 나중에 소급 기록이 불가능한 유일한 항목입니다.
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TENLUNE_META_PREFIX = '_tenlune_';

/**
 * 단순 텍스트 계열 필드 정의.
 *
 * @return array<string, array{label:string, type:string, options?:array<string>, help?:string}>
 */
function tenlune_case_field_defs() {
	return array(
		'participation' => array(
			'label'   => '참여 형태',
			'type'    => 'select',
			'options' => array( '', '개인 프로젝트', '팀 프로젝트' ),
			'help'    => '팀이면 아래 「역할」에 담당 영역만 씁니다. 팀 전체 결과물을 제 것처럼 쓰지 않습니다.',
		),
		'role'          => array(
			'label' => '역할 · 담당',
			'type'  => 'text',
			'help'  => '예: 기획 · 설계 · 구현 · 검수 전 과정 / DB · 백엔드, 게임 로직, CI · 코드리뷰',
		),
		'status'        => array(
			'label' => '상태',
			'type'  => 'text',
			'help'  => '예: 사용하며 개선 중 / 운영 종료 / 공개 준비 중',
		),
		'started_on'    => array(
			'label' => '시작일',
			'type'  => 'date',
			'help'  => '기간은 화면에서 자동 계산됩니다. "6개월째" 같은 문구를 직접 쓰지 않기 위한 장치입니다.',
		),
		'live_url'      => array(
			'label' => '라이브 URL',
			'type'  => 'url',
			'help'  => '지금 열어볼 수 있는 주소가 있을 때만. 없으면 비워 둡니다.',
		),
		'problem_line'  => array(
			'label' => '한 줄 문제 정의',
			'type'  => 'text',
			'help'  => '아카이브 카드에 그대로 나갑니다. "무엇을 만들었나"가 아니라 "무엇이 불편했나"로 씁니다.',
		),
	);
}

/**
 * 메타 등록. REST 에 노출해 두어야 블록 편집기와 API 양쪽에서 다룰 수 있습니다.
 */
function tenlune_register_case_meta() {
	foreach ( tenlune_case_field_defs() as $key => $def ) {
		register_post_meta(
			'case',
			TENLUNE_META_PREFIX . $key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'url' === $def['type'] ? 'esc_url_raw' : 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'case',
		TENLUNE_META_PREFIX . 'change_log',
		array(
			'type'          => 'array',
			'single'        => true,
			'default'       => array(),
			'show_in_rest'  => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'date'   => array( 'type' => 'string' ),
							'kind'   => array( 'type' => 'string' ),
							'issue'  => array( 'type' => 'string' ),
							'action' => array( 'type' => 'string' ),
						),
					),
				),
			),
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'tenlune_register_case_meta' );

/**
 * 메타 상자.
 */
function tenlune_add_case_meta_box() {
	add_meta_box(
		'tenlune-case-fields',
		__( '케이스 정보', 'tenlune-content' ),
		'tenlune_render_case_meta_box',
		'case',
		'normal',
		'high',
		array( '__block_editor_compatible_meta_box' => true )
	);
}
add_action( 'add_meta_boxes', 'tenlune_add_case_meta_box' );

/**
 * 메타 상자 화면.
 *
 * @param WP_Post $post 편집 중인 글.
 */
function tenlune_render_case_meta_box( $post ) {
	wp_nonce_field( 'tenlune_save_case_meta', 'tenlune_case_nonce' );

	echo '<style>
	.tl-mb{display:grid;gap:1rem;max-width:46rem}
	.tl-mb label{display:block;font-weight:600;margin-bottom:.25rem}
	.tl-mb input[type=text],.tl-mb input[type=url],.tl-mb input[type=date],.tl-mb select{width:100%;max-width:32rem}
	.tl-mb .desc{color:#666;font-size:12px;margin:.25rem 0 0}
	.tl-log-row{display:grid;grid-template-columns:9rem 8rem 1fr 1fr 2rem;gap:.5rem;align-items:start;margin-bottom:.5rem}
	.tl-log-head{font-size:12px;color:#666;font-weight:600}
	@media (max-width:782px){.tl-log-row{grid-template-columns:1fr}.tl-log-head{display:none}}
	</style>';

	echo '<div class="tl-mb">';

	foreach ( tenlune_case_field_defs() as $key => $def ) {
		$meta_key = TENLUNE_META_PREFIX . $key;
		$value    = (string) get_post_meta( $post->ID, $meta_key, true );
		$id       = 'tl-' . $key;

		echo '<div>';
		printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $def['label'] ) );

		if ( 'select' === $def['type'] ) {
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $meta_key ) );
			foreach ( $def['options'] as $option ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $option ),
					selected( $value, $option, false ),
					esc_html( '' === $option ? '— 선택 —' : $option )
				);
			}
			echo '</select>';
		} else {
			$input_type = 'date' === $def['type'] ? 'date' : ( 'url' === $def['type'] ? 'url' : 'text' );
			printf(
				'<input type="%s" id="%s" name="%s" value="%s">',
				esc_attr( $input_type ),
				esc_attr( $id ),
				esc_attr( $meta_key ),
				esc_attr( $value )
			);
		}

		if ( ! empty( $def['help'] ) ) {
			printf( '<p class="desc">%s</p>', esc_html( $def['help'] ) );
		}
		echo '</div>';
	}

	// 개선 로그 — 반복 필드.
	$log = get_post_meta( $post->ID, TENLUNE_META_PREFIX . 'change_log', true );
	$log = is_array( $log ) ? $log : array();
	$log = array_values( $log );
	$log[] = array(
		'date'   => '',
		'kind'   => '',
		'issue'  => '',
		'action' => '',
	); // 항상 빈 줄 하나.

	echo '<div>';
	echo '<label>' . esc_html__( '개선 로그', 'tenlune-content' ) . '</label>';
	echo '<p class="desc">발견 즉시 남깁니다. 나중에 소급해서 복원할 수 없는 유일한 항목입니다. 항목이 0개면 화면에 섹션 자체가 나오지 않습니다.</p>';
	echo '<div class="tl-log-row tl-log-head"><span>날짜</span><span>분류</span><span>발견한 문제</span><span>조치</span><span></span></div>';

	foreach ( $log as $i => $row ) {
		$row = wp_parse_args(
			is_array( $row ) ? $row : array(),
			array(
				'date'   => '',
				'kind'   => '',
				'issue'  => '',
				'action' => '',
			)
		);
		printf(
			'<div class="tl-log-row">
				<input type="date" name="tenlune_log[%1$d][date]" value="%2$s">
				<select name="tenlune_log[%1$d][kind]">
					<option value=""%3$s>—</option>
					<option value="즉시"%4$s>즉시 수정</option>
					<option value="백로그"%5$s>백로그</option>
				</select>
				<input type="text" name="tenlune_log[%1$d][issue]" value="%6$s" placeholder="무엇이 문제였나">
				<input type="text" name="tenlune_log[%1$d][action]" value="%7$s" placeholder="어떻게 했나">
				<span></span>
			</div>',
			(int) $i,
			esc_attr( $row['date'] ),
			selected( $row['kind'], '', false ),
			selected( $row['kind'], '즉시', false ),
			selected( $row['kind'], '백로그', false ),
			esc_attr( $row['issue'] ),
			esc_attr( $row['action'] )
		);
	}

	echo '<p class="desc">' . esc_html__( '저장하면 빈 줄이 하나 더 생깁니다. 내용을 비우면 그 줄은 삭제됩니다.', 'tenlune-content' ) . '</p>';
	echo '</div>';

	echo '</div>';
}

/**
 * 저장.
 *
 * @param int $post_id 글 ID.
 */
function tenlune_save_case_meta( $post_id ) {
	if ( ! isset( $_POST['tenlune_case_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tenlune_case_nonce'] ) ), 'tenlune_save_case_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'case' !== get_post_type( $post_id ) ) {
		return;
	}

	foreach ( tenlune_case_field_defs() as $key => $def ) {
		$meta_key = TENLUNE_META_PREFIX . $key;

		if ( ! isset( $_POST[ $meta_key ] ) ) {
			continue;
		}

		$raw   = wp_unslash( $_POST[ $meta_key ] );
		$value = 'url' === $def['type'] ? esc_url_raw( $raw ) : sanitize_text_field( $raw );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$rows = array();

	if ( isset( $_POST['tenlune_log'] ) && is_array( $_POST['tenlune_log'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- 각 필드를 아래에서 개별 정제합니다.
		foreach ( wp_unslash( $_POST['tenlune_log'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array(
				'date'   => sanitize_text_field( $row['date'] ?? '' ),
				'kind'   => sanitize_text_field( $row['kind'] ?? '' ),
				'issue'  => sanitize_text_field( $row['issue'] ?? '' ),
				'action' => sanitize_text_field( $row['action'] ?? '' ),
			);

			// 문제와 조치가 모두 비어 있으면 빈 줄로 보고 버립니다.
			if ( '' === $clean['issue'] && '' === $clean['action'] ) {
				continue;
			}

			$rows[] = $clean;
		}
	}

	usort(
		$rows,
		static function ( $a, $b ) {
			return strcmp( (string) $b['date'], (string) $a['date'] );
		}
	);

	if ( empty( $rows ) ) {
		delete_post_meta( $post_id, TENLUNE_META_PREFIX . 'change_log' );
	} else {
		update_post_meta( $post_id, TENLUNE_META_PREFIX . 'change_log', $rows );
	}
}
add_action( 'save_post_case', 'tenlune_save_case_meta' );

/**
 * 시작일로부터 기간을 계산합니다. 수치를 손으로 쓰지 않기 위한 장치입니다.
 *
 * @param string $started_on Y-m-d.
 * @return string 예: "2026-04 시작 · 5개월째" / 값이 없으면 빈 문자열.
 */
function tenlune_duration_text( $started_on ) {
	$started_on = trim( (string) $started_on );

	if ( '' === $started_on ) {
		return '';
	}

	$start = date_create( $started_on );

	if ( ! $start ) {
		return '';
	}

	$now = current_datetime();

	if ( $start > $now ) {
		return $start->format( 'Y-m' ) . ' 시작 예정';
	}

	$diff   = $start->diff( $now );
	$months = ( $diff->y * 12 ) + $diff->m;

	if ( $months < 1 ) {
		$span = '이번 달 시작';
	} elseif ( $months < 12 ) {
		/* translators: %d: 개월 수 */
		$span = sprintf( '%d개월째', $months + 1 );
	} else {
		$years = intdiv( $months, 12 );
		$rest  = $months % 12;
		$span  = $rest > 0 ? sprintf( '%d년 %d개월째', $years, $rest ) : sprintf( '%d년째', $years );
	}

	return $start->format( 'Y-m' ) . ' 시작 · ' . $span;
}
