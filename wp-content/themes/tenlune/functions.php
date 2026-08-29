<?php
/**
 * Tenlune — TT5 자식 블록 테마
 *
 * 하는 일은 네 가지뿐입니다.
 *  1. 자식 테마 스타일 로드
 *     (부모 Twenty Twenty-Five 의 style.css 에는 규칙이 없어 따로 부르지 않습니다.
 *      부모의 화면 구성은 theme.json 과 블록에서 오고, 자식 theme.json 이 그것을 덮습니다.)
 *  2. Google Fonts CDN 로드 (구현 사양 06번 — v1 권장안)
 *  3. theme.json으로 표현되지 않는 레이아웃 CSS 로드
 *  4. 패턴 카테고리 등록 (패턴 파일 자체는 /patterns/ 에서 자동 등록됩니다)
 *
 * CPT `case` 는 여기에 넣지 않습니다. 테마를 바꿔도 Work 콘텐츠가 남아야 하므로
 * 별도 플러그인(tenlune-content)에서 등록합니다. — 구현 사양 07번
 *
 * @package Tenlune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TENLUNE_VERSION' ) ) {
	define( 'TENLUNE_VERSION', '0.1.2' );
}

/**
 * 폰트·스타일 로드.
 *
 * 굵기는 실제로 쓰는 것만 부릅니다 — display 500/600/700/800, body 300/400/500, mono 400/500.
 * 한글 웹폰트는 라틴보다 수십 배 무겁고, 이 사이트의 병목은 서버가 아니라 전송 구간입니다.
 * 굵기를 하나 늘리는 것이 곧 체감 속도입니다.
 */
function tenlune_enqueue_assets() {
	wp_enqueue_style(
		'tenlune-fonts',
		'https://fonts.googleapis.com/css2?family=Gothic+A1:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans+KR:wght@300;400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'tenlune-style',
		get_stylesheet_uri(),
		array(),
		TENLUNE_VERSION
	);

	wp_enqueue_style(
		'tenlune-layout',
		get_stylesheet_directory_uri() . '/assets/css/tenlune.css',
		array( 'tenlune-style' ),
		TENLUNE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'tenlune_enqueue_assets' );

/**
 * 폰트 CDN 사전 연결.
 *
 * preconnect 가 없으면 DNS·TLS 왕복이 폰트 요청 앞에 통째로 붙습니다.
 */
function tenlune_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type ) {
		return $urls;
	}

	$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
	$urls[] = array(
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => 'anonymous',
	);

	return $urls;
}
add_filter( 'wp_resource_hints', 'tenlune_resource_hints', 10, 2 );

/**
 * 편집 화면에도 같은 레이아웃 CSS를 적용합니다.
 * 편집기와 프런트가 다르게 보이면 편집 자체를 믿을 수 없게 됩니다.
 */
function tenlune_editor_styles() {
	// 편집 화면에도 같은 웹폰트를 넣어야 편집기와 프런트의 줄바꿈·높이가 맞습니다.
	add_editor_style(
		array(
			'https://fonts.googleapis.com/css2?family=Gothic+A1:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans+KR:wght@300;400;500&display=swap',
			'assets/css/tenlune.css',
		)
	);
}
add_action( 'after_setup_theme', 'tenlune_editor_styles' );

/**
 * 패턴 카테고리.
 *
 * /patterns/*.php 는 블록 테마에서 자동 등록되므로 여기서는 분류만 만듭니다.
 */
function tenlune_register_pattern_categories() {
	register_block_pattern_category(
		'tenlune',
		array(
			'label'       => __( 'Tenlune', 'tenlune' ),
			'description' => __( 'Tenlune Dark 최종안에서 나온 재사용 블록.', 'tenlune' ),
		)
	);

	register_block_pattern_category(
		'tenlune-page',
		array(
			'label'       => __( 'Tenlune — 페이지', 'tenlune' ),
			'description' => __( '고정 페이지(Services·About·Contact 등) 본문 패턴.', 'tenlune' ),
		)
	);
}
add_action( 'init', 'tenlune_register_pattern_categories' );

/**
 * 발췌문 말줄임표. 기본값 " [...]" 는 이 디자인의 조판과 어울리지 않습니다.
 */
function tenlune_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'tenlune_excerpt_more' );

/**
 * <meta name="generator" content="WordPress x.y"> 를 <head> 에서 뺍니다. — M7
 * 버전 문자열은 정상이지만(현재 7.1) 굳이 노출할 이유가 없습니다. RSS 피드 쪽
 * generator 는 건드리지 않습니다(the_generator 필터는 별개).
 */
remove_action( 'wp_head', 'wp_generator' );

/**
 * Contact Form 7 스크립트를 문의 페이지에서만 로드합니다. — M9
 *
 * CF7 은 기본적으로 전 페이지에 자기 JS(swv/index.js 등)를 큐잉합니다. 이 사이트에서
 * CF7 폼은 `/contact/`(page slug `contact`) 한 곳뿐이라, 그 외 페이지에서는 내려서
 * 불필요한 전송을 없앱니다. `/contact/` 에서는 원래대로(AJAX 검증·응답) 동작합니다.
 * 다른 페이지에 `[contact-form-7]` 를 새로 넣으면 이 조건도 같이 넓혀야 합니다.
 */
add_filter(
	'wpcf7_load_js',
	static function ( $load ) {
		return is_page( 'contact' ) ? $load : false;
	}
);
