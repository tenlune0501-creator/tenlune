<?php
/**
 * Title: Hero
 * Slug: tenlune/hero
 * Categories: tenlune
 * Description: Home 전용 Hero. 한 문장 + 보조 두 줄 + CTA 2개. 다른 페이지는 축약형 헤드(tenlune/page-head)를 씁니다.
 * Keywords: hero, 홈, 상단
 * Viewport Width: 1400
 *
 * 카피는 확정된 A안(결과 중심)입니다. 첫 줄에서 업종이 판별되고,
 * 둘째 줄이 차별점을 말하고, 셋째 줄에서 AI 가 명시적으로 등장합니다.
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"align":"full","className":"tl-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tl-hero">
	<!-- wp:group {"className":"tl-hero-in","layout":{"type":"constrained"}} -->
	<div class="wp-block-group tl-hero-in">
		<!-- wp:paragraph {"className":"tl-eyebrow"} -->
		<p class="tl-eyebrow">웹사이트 · 웹서비스 · 업무 자동화</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"tl-h1"} -->
		<h1 class="wp-block-heading tl-h1">아이디어를<br>쓸 수 있는 웹으로.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"tl-lead"} -->
		<p class="tl-lead">요구사항 정리부터 배포까지, 한 사람이 끝까지 만듭니다.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"tl-lead-2"} -->
		<p class="tl-lead-2">AI를 포함한 도구로 빠르게 탐색하고 구현합니다. 무엇을 만들지 판단하고 결과를 확인해 고치는 일은 제가 합니다.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"tl-actions"} -->
		<div class="wp-block-buttons tl-actions">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/work/">제작 사례 보기 →</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"tl-btn-ghost"} -->
			<div class="wp-block-button tl-btn-ghost"><a class="wp-block-button__link wp-element-button" href="/contact/">작업 의뢰하기</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
