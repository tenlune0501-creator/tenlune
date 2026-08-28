<?php
/**
 * Title: 페이지 헤드 (축약형)
 * Slug: tenlune/page-head
 * Categories: tenlune, tenlune-page
 * Description: Home 이외의 페이지 상단. Hero 를 그대로 쓰지 않습니다 — Hero 는 Home 에서만 유효한 밀도입니다.
 * Keywords: 페이지, 상단, 헤드
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"className":"tl-pagehead","layout":{"type":"constrained"}} -->
<div class="wp-block-group tl-pagehead">
	<!-- wp:paragraph {"className":"tl-eyebrow"} -->
	<p class="tl-eyebrow">Page label</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"tl-h2-lg"} -->
	<h1 class="wp-block-heading tl-h2-lg">페이지 제목</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tl-lead"} -->
	<p class="tl-lead">이 페이지가 무엇을 알려주는지 한 문장으로.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
