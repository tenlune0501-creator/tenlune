<?php
/**
 * Title: 기록 — 최근 글 (쿼리 루프)
 * Slug: tenlune/journal-row
 * Categories: tenlune
 * Description: 최신 글 3건을 행으로. 카드가 아니라 행인 이유는 Home 이 블로그 목록처럼 보이면 안 되기 때문입니다.
 * Keywords: blog, 기록, 최근 글
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"tagName":"section","className":"tl-sec","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-sec">
	<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-sec-head">
		<!-- wp:paragraph {"className":"tl-sec-label"} -->
		<p class="tl-sec-label">Journal</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"tl-sec-title"} -->
		<h2 class="wp-block-heading tl-sec-title">기록</h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":20,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"className":"tl-journal-query"} -->
	<div class="wp-block-query tl-journal-query">
		<!-- wp:post-template {"className":"tl-journal"} -->
			<!-- wp:group {"className":"tl-j","layout":{"type":"default"}} -->
			<div class="wp-block-group tl-j">
				<!-- wp:post-date {"format":"Y-m-d","className":"date"} /-->
				<!-- wp:post-title {"level":3,"isLink":true,"className":"title"} /-->
				<!-- wp:post-terms {"term":"category","className":"cat"} /-->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"className":"tl-sec-desc"} -->
			<p class="tl-sec-desc">아직 쓴 글이 없습니다.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
