<?php
/**
 * CPT `case` 와 택소노미 `case_type`.
 *
 * URL 은 /work/{slug}/ 입니다. 나중에 바꾸면 검색 순위가 초기화되므로
 * 여기 값은 v1 확정값으로 두고 함부로 건드리지 않습니다. — 설계안 04번
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 제작 사례 (CPT: case)
 */
function tenlune_register_case_post_type() {
	$labels = array(
		'name'               => __( '제작 사례', 'tenlune-content' ),
		'singular_name'      => __( '제작 사례', 'tenlune-content' ),
		'menu_name'          => __( '제작 사례', 'tenlune-content' ),
		'add_new'            => __( '새로 추가', 'tenlune-content' ),
		'add_new_item'       => __( '새 제작 사례', 'tenlune-content' ),
		'edit_item'          => __( '제작 사례 편집', 'tenlune-content' ),
		'new_item'           => __( '새 제작 사례', 'tenlune-content' ),
		'view_item'          => __( '제작 사례 보기', 'tenlune-content' ),
		'search_items'       => __( '제작 사례 검색', 'tenlune-content' ),
		'not_found'          => __( '제작 사례가 없습니다.', 'tenlune-content' ),
		'not_found_in_trash' => __( '휴지통에 제작 사례가 없습니다.', 'tenlune-content' ),
		'all_items'          => __( '모든 제작 사례', 'tenlune-content' ),
	);

	register_post_type(
		'case',
		array(
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => 'work',
			'rewrite'       => array(
				'slug'       => 'work',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'show_in_rest'  => true,
			'rest_base'     => 'cases',
			'taxonomies'    => array( 'case_type' ),
			'template'      => array(
				array( 'core/pattern', array( 'slug' => 'tenlune/case-body' ) ),
			),
		)
	);
}
add_action( 'init', 'tenlune_register_case_post_type' );

/**
 * 사례 유형 (택소노미: case_type) — 웹사이트 / 웹서비스 / 도구 / 개선
 */
function tenlune_register_case_taxonomy() {
	register_taxonomy(
		'case_type',
		array( 'case' ),
		array(
			'labels'            => array(
				'name'          => __( '사례 유형', 'tenlune-content' ),
				'singular_name' => __( '사례 유형', 'tenlune-content' ),
				'add_new_item'  => __( '사례 유형 추가', 'tenlune-content' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'work/type',
				'with_front' => false,
			),
		)
	);

	/**
	 * `case` CPT(슬러그 work)가 만드는 첨부 규칙 `work/[^/]+/([^/]+)/?$` 가
	 * `work/type/([^/]+)/?$` 보다 먼저 매칭되어 `/work/type/{slug}/` 가
	 * attachment 조회로 빠지며 404 가 되는 것을 막습니다. 동일 규칙을 'top' 으로
	 * 올려 택소노미 아카이브가 먼저 잡히게 합니다. — C2
	 */
	add_rewrite_rule(
		'work/type/([^/]+)/?$',
		'index.php?case_type=$matches[1]',
		'top'
	);
}
add_action( 'init', 'tenlune_register_case_taxonomy' );

/**
 * 최초 설치 시 기본 유형 4개를 넣어 둡니다. 이미 있으면 건드리지 않습니다.
 */
function tenlune_seed_case_types() {
	if ( get_option( 'tenlune_case_types_seeded' ) ) {
		return;
	}

	foreach ( array( '웹사이트', '웹서비스', '도구', '개선' ) as $name ) {
		if ( ! term_exists( $name, 'case_type' ) ) {
			wp_insert_term( $name, 'case_type' );
		}
	}

	update_option( 'tenlune_case_types_seeded', 1 );
}
add_action( 'init', 'tenlune_seed_case_types', 20 );
