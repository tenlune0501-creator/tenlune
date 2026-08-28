<?php
/**
 * Title: 페이지 — Contact
 * Slug: tenlune/page-contact
 * Categories: tenlune-page
 * Description: 폼 자체가 요구사항 정리를 유도합니다. 빈 텍스트박스 하나만 놓아두면 대부분 이탈합니다.
 * Keywords: contact, 문의, 폼
 *
 * @package Tenlune
 */

?>
<!-- wp:group {"tagName":"section","className":"tl-sec","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-sec">
	<!-- wp:group {"className":"tl-contact-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-contact-grid">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:tenlune/contact-form /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tl-aside","layout":{"type":"default"}} -->
		<div class="wp-block-group tl-aside">
			<!-- wp:html -->
			<ol class="tl-flow">
				<li><span class="k">01</span><span>만들고 싶은 것을 적어 보내주세요.</span></li>
				<li><span class="k">02</span><span>2영업일 안에 가능 여부와 대략적인 범위를 회신합니다.</span></li>
				<li><span class="k">03</span><span>요구사항을 정리한 뒤 일정과 금액을 확정하고 시작합니다.</span></li>
			</ol>
			<!-- /wp:html -->

			<!-- wp:pattern {"slug":"tenlune/terms"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
