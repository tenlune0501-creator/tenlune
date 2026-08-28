<?php
/**
 * Title: 섹션 헤드
 * Slug: tenlune/section-head
 * Categories: tenlune
 * Description: 라벨 + 제목 + 설명. 전 페이지에서 가장 많이 재사용됩니다.
 * Keywords: 섹션, 제목, 헤드
 *
 * 라벨은 라틴·숫자만 씁니다. IBM Plex Mono 에는 한글 글리프가 없어
 * 한글을 넣으면 대체 서체로 떨어지고 자간 설정이 무너집니다.
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
<div class="wp-block-group tl-sec-head">
	<!-- wp:paragraph {"className":"tl-sec-label"} -->
	<p class="tl-sec-label">Section label</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"className":"tl-sec-title"} -->
	<h2 class="wp-block-heading tl-sec-title">섹션 제목</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tl-sec-desc"} -->
	<p class="tl-sec-desc">필요할 때만 씁니다. 설명이 없어도 되는 섹션에서는 이 문단을 지웁니다.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
