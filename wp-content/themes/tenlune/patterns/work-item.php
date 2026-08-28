<?php
/**
 * Title: 제작 사례 — 전시 (쿼리 루프)
 * Slug: tenlune/work-item
 * Categories: tenlune
 * Description: 대표 사례를 크게 보여주는 섹션. 최신 2건을 자동으로 끌어옵니다. 사례가 늘어도 Home 은 길어지지 않습니다.
 * Keywords: work, 사례, 포트폴리오, 쿼리
 *
 * 이미지 주위에 테두리도 그림자도 프레임도 없습니다. 카드가 가려 주던 것이
 * 사라졌으므로 캡처 품질이 그대로 드러납니다. — 구현 사양 05번
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"tagName":"section","anchor":"work","className":"tl-gallery","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-gallery" id="work">
	<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-sec-head">
		<!-- wp:paragraph {"className":"tl-sec-label"} -->
		<p class="tl-sec-label">Selected work</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"tl-sec-title"} -->
		<h2 class="wp-block-heading tl-sec-title">제작 사례</h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":10,"query":{"perPage":2,"pages":0,"offset":0,"postType":"case","order":"desc","orderBy":"date","inherit":false},"className":"tl-works-query"} -->
	<div class="wp-block-query tl-works-query">
		<!-- wp:post-template {"className":"tl-works"} -->
			<!-- wp:group {"tagName":"article","className":"tl-work","layout":{"type":"default"}} -->
			<article class="wp-block-group tl-work">
				<!-- wp:group {"className":"tl-work-fig","layout":{"type":"default"}} -->
				<div class="wp-block-group tl-work-fig">
					<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","scale":"cover"} /-->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"tl-work-cap","layout":{"type":"default"}} -->
				<div class="wp-block-group tl-work-cap">
					<!-- wp:group {"layout":{"type":"default"}} -->
					<div class="wp-block-group">
						<!-- wp:post-title {"level":3,"isLink":true,"className":"tl-work-t"} /-->
						<!-- wp:post-excerpt {"moreText":"","excerptLength":45,"className":"tl-work-p"} /-->
						<!-- wp:read-more {"content":"과정 자세히 보기 →","className":"tl-work-link"} /-->
					</div>
					<!-- /wp:group -->

					<!-- wp:tenlune/case-meta /-->
				</div>
				<!-- /wp:group -->
			</article>
			<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"className":"tl-sec-desc"} -->
			<p class="tl-sec-desc">아직 공개한 사례가 없습니다. 공개할 수 있는 작업이 생기면 여기에 같은 방식으로 추가됩니다.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->

	<!-- wp:group {"className":"tl-works-note","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-works-note">
		<!-- wp:paragraph -->
		<p>개수를 늘리기보다 과정을 남깁니다. 공개할 가치가 있는 작업이 생기면 같은 방식으로 추가합니다. <a href="/work/">사례 전체 보기 →</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
