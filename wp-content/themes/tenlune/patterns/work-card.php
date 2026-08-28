<?php
/**
 * Title: 제작 사례 — 카드 (아카이브용)
 * Slug: tenlune/work-card
 * Categories: tenlune
 * Description: /work/ 아카이브의 2열 그리드 카드. 썸네일 + 제목 + 한 줄 문제 정의 + 유형 · 역할 표기.
 * Keywords: work, 카드, 아카이브
 * Inserter: false
 *
 * Home 의 전시형(tenlune/work-item)과 같은 토큰을 쓰되 밀도만 다릅니다.
 * 아카이브에서까지 전시형을 쓰면 세 번째 사례부터 페이지가 끝없이 길어집니다.
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"tagName":"article","className":"tl-work-card","layout":{"type":"default"}} -->
<article class="wp-block-group tl-work-card">
	<!-- wp:group {"className":"tl-work-fig","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-work-fig">
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","scale":"cover"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:post-title {"level":3,"isLink":true,"className":"tl-card-t"} /-->
	<!-- wp:tenlune/case-problem /-->

	<!-- wp:group {"className":"tl-tagrow","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group tl-tagrow">
		<!-- wp:post-terms {"term":"case_type"} /-->
	</div>
	<!-- /wp:group -->
</article>
<!-- /wp:group -->
