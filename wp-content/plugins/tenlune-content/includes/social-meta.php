<?php
/**
 * 소셜 공유 메타 (Open Graph / Twitter Card).
 *
 * SEO 플러그인 없이, 카카오톡·문자·Slack·SNS 링크 카드에 제목·설명·대표 이미지가
 * 뜨도록 전 페이지 <head> 에 OG/Twitter 태그를 출력합니다. 콘텐츠를 서술하는
 * 메타이므로 테마가 아니라 이 플러그인에 둡니다. — C3
 *
 * 같은 $desc·$url 로 표준 <meta name="description"> 과, 코어가 채우지 않는
 * 홈·아카이브의 <link rel="canonical"> 도 함께 출력합니다. — M2 / M3
 *
 * 값 결정
 *  - title       : wp_get_document_title()  (<title> 과 동일, 별도 관리 지점 없음)
 *  - description : 단일=발췌, 홈/검색=태그라인, 그 외=문맥별 기본 문구
 *                  (og:description · twitter:description · meta[name=description] 공용)
 *  - image       : 대표이미지 → 옵션 tenlune_og_default_image → site_icon
 *  - url         : 문맥별 정규 URL (전부 https 로 강제; 도메인 하드코딩 없음)
 *                  singular 은 코어 rel_canonical(), 그 외는 이 파일이 canonical 출력
 *  - type        : 사례/글=article, 그 외=website
 *
 * 모든 값은 tenlune/og_* 필터로 재정의 가능.
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 설명 텍스트 정리 — 태그 제거, 공백 정리, 약 200자에서 말줄임.
 *
 * @param string $text 원본 텍스트.
 * @return string
 */
function tenlune_social_meta_clean_desc( $text ) {
	$text = wp_strip_all_tags( (string) $text, true );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( (string) $text );

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 200 ) {
		$text = rtrim( mb_substr( $text, 0, 197 ) ) . '…';
	}

	return $text;
}

/**
 * https 로 스킴 고정.
 *
 * C1(도메인 마이그레이션) 이후 home_url() 이 이미 https 를 반환하지만, http 로
 * 새는 경로가 있어도 카드가 mixed 로 깨지지 않도록 방어적으로 한 번 더 강제합니다.
 *
 * @param string $url URL.
 * @return string
 */
function tenlune_social_meta_https( $url ) {
	return $url ? set_url_scheme( $url, 'https' ) : $url;
}

/**
 * 대표 이미지 결정.
 *
 * 우선순위: (단일이면) 대표 이미지 → 옵션 tenlune_og_default_image → site_icon.
 * 가로 1200px 이상 크기를 우선 선택하고, 없으면 원본(full) 을 씁니다.
 *
 * @return array { url, width, height, alt } 또는 빈 배열.
 */
function tenlune_social_meta_image() {
	$candidates = array();

	if ( is_singular() ) {
		$thumb_id = get_post_thumbnail_id( get_queried_object_id() );
		if ( $thumb_id ) {
			$candidates[] = (int) $thumb_id;
		}
	}

	$opt_id = (int) get_option( 'tenlune_og_default_image' );
	if ( $opt_id ) {
		$candidates[] = $opt_id;
	}

	$icon_id = (int) get_option( 'site_icon' );
	if ( $icon_id ) {
		$candidates[] = $icon_id;
	}

	/**
	 * OG 이미지 attachment ID 후보 목록 재정의.
	 *
	 * @param int[] $candidates 우선순위 순 attachment ID 목록.
	 */
	$candidates = apply_filters( 'tenlune/og_image_id', $candidates );

	foreach ( (array) $candidates as $id ) {
		$id = (int) $id;
		if ( ! $id || 'attachment' !== get_post_type( $id ) ) {
			continue;
		}

		foreach ( array( '1536x1536', 'large', 'full' ) as $size ) {
			$src = wp_get_attachment_image_src( $id, $size );
			if ( ! $src || empty( $src[0] ) ) {
				continue;
			}
			if ( 'full' !== $size && (int) $src[1] < 1200 ) {
				continue;
			}

			return array(
				'url'    => tenlune_social_meta_https( $src[0] ),
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			);
		}
	}

	return array();
}

/**
 * <head> 에 OG / Twitter 메타를 출력합니다.
 */
function tenlune_social_meta_render() {
	if ( is_admin() || is_feed() || is_embed() || is_robots() || is_trackback() ) {
		return;
	}

	$site_name = get_bloginfo( 'name' );
	$tagline   = get_bloginfo( 'description' );

	$title = wp_get_document_title();
	$type  = 'website';
	$url   = home_url( '/' );
	$desc  = $tagline;

	if ( is_front_page() ) {
		$desc = $tagline;
		$url  = home_url( '/' );
	} elseif ( is_singular() ) {
		$obj  = get_queried_object();
		$url  = get_permalink( $obj );
		$desc = tenlune_social_meta_clean_desc( get_the_excerpt( $obj ) );
		if ( '' === $desc ) {
			$desc = $tagline;
		}
		if ( is_singular( array( 'case', 'post' ) ) ) {
			$type = 'article';
		}
	} elseif ( is_post_type_archive( 'case' ) ) {
		$url  = get_post_type_archive_link( 'case' );

		/**
		 * Work 아카이브 og:description 기본 문구.
		 *
		 * @param string $text 기본 문구.
		 */
		$archive_desc = apply_filters(
			'tenlune/og_archive_description',
			'Tenlune 이 만든 웹사이트·웹서비스·자동화 제작 사례. 무엇을 만들었는지보다 어떤 문제를 어떻게 풀었는지를 남깁니다.'
		);
		$desc = tenlune_social_meta_clean_desc( $archive_desc );
	} elseif ( is_tax( 'case_type' ) ) {
		$term = get_queried_object();
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$url = $link;
		}
		$td   = tenlune_social_meta_clean_desc( term_description( $term->term_id, 'case_type' ) );
		$desc = '' !== $td ? $td : sprintf( '%s 유형의 Tenlune 제작 사례.', $term->name );
	} elseif ( is_404() ) {
		$desc = '요청하신 페이지를 찾을 수 없습니다.';
		$url  = home_url( '/' );
	} elseif ( is_search() ) {
		$desc = $tagline;
		$url  = home_url( '/' );
	} elseif ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		$url        = $posts_page ? get_permalink( $posts_page ) : home_url( '/' );
		$desc       = $posts_page ? tenlune_social_meta_clean_desc( get_the_excerpt( $posts_page ) ) : $tagline;
		if ( '' === $desc ) {
			$desc = $tagline;
		}
	}

	$url   = tenlune_social_meta_https( $url ? $url : home_url( '/' ) );
	$title = (string) apply_filters( 'tenlune/og_title', $title );
	$desc  = (string) apply_filters( 'tenlune/og_description', $desc );
	$type  = (string) apply_filters( 'tenlune/og_type', $type );

	$image = tenlune_social_meta_image();

	$tw_card = ( ! empty( $image['width'] ) && ! empty( $image['height'] ) && $image['width'] >= $image['height'] * 1.5 )
		? 'summary_large_image'
		: 'summary';

	$tags = array(
		array( 'property', 'og:site_name', $site_name ),
		array( 'property', 'og:locale', get_locale() ),
		array( 'property', 'og:type', $type ),
		array( 'property', 'og:title', $title ),
		array( 'property', 'og:description', $desc ),
		array( 'property', 'og:url', $url ),
	);

	if ( ! empty( $image['url'] ) ) {
		$tags[] = array( 'property', 'og:image', $image['url'] );
		if ( ! empty( $image['width'] ) ) {
			$tags[] = array( 'property', 'og:image:width', (string) $image['width'] );
			$tags[] = array( 'property', 'og:image:height', (string) $image['height'] );
		}
		$tags[] = array( 'property', 'og:image:alt', '' !== $image['alt'] ? $image['alt'] : $title );
	}

	$tags[] = array( 'name', 'twitter:card', $tw_card );
	$tags[] = array( 'name', 'twitter:title', $title );
	$tags[] = array( 'name', 'twitter:description', $desc );
	if ( ! empty( $image['url'] ) ) {
		$tags[] = array( 'name', 'twitter:image', $image['url'] );
	}

	echo "\n<!-- Tenlune social meta -->\n";

	// M2 — 표준 <meta name="description">. og:description 과 같은 $desc 를 재사용한다.
	// WordPress 코어·테마가 이 태그를 출력하지 않으므로 중복이 생기지 않는다.
	if ( '' !== (string) $desc ) {
		printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $desc ) );
	}

	// M3 — canonical. 코어 rel_canonical() 은 is_singular() 만 처리하므로,
	// 홈 · Work 아카이브 · case_type 아카이브에만 여기서 출력해 중복을 피한다.
	if ( is_front_page() || is_home() || is_post_type_archive( 'case' ) || is_tax( 'case_type' ) ) {
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $url ) );
	}

	foreach ( $tags as $t ) {
		list( $attr, $key, $val ) = $t;
		if ( '' === (string) $val ) {
			continue;
		}
		$is_url = in_array( $key, array( 'og:url', 'og:image', 'twitter:image' ), true );
		printf(
			"<meta %s=\"%s\" content=\"%s\" />\n",
			esc_attr( $attr ),
			esc_attr( $key ),
			$is_url ? esc_url( $val ) : esc_attr( $val )
		);
	}
	echo "<!-- /Tenlune social meta -->\n";
}
add_action( 'wp_head', 'tenlune_social_meta_render', 5 );
