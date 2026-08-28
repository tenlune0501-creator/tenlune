<?php
/**
 * Title: 페이지 — Services
 * Slug: tenlune/page-services
 * Categories: tenlune-page
 * Description: v1 은 단일 페이지입니다. 도입 → 서비스 블록 → 의뢰인 관점 프로세스 → 하지 않는 것 → FAQ → 문의.
 * Keywords: services, 서비스, 가격
 *
 * 가격·범위는 확정 Home 디자인의 「거래 조건」과 같은 값을 씁니다.
 * 두 곳의 숫자가 어긋나면 신뢰가 먼저 깎입니다.
 *
 * @package Tenlune
 */

?>
<!-- wp:pattern {"slug":"tenlune/makes"} /-->

<!-- wp:group {"tagName":"section","className":"tl-sec","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-sec">
	<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-sec-head">
		<!-- wp:paragraph {"className":"tl-sec-label"} -->
		<p class="tl-sec-label">Terms</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"tl-sec-title"} -->
		<h2 class="wp-block-heading tl-sec-title">가격과 기간</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"tl-sec-desc"} -->
		<p class="tl-sec-desc">먼저 밝혀 두는 편이 서로 시간을 아낍니다. 정확한 금액은 요구사항을 확인한 뒤 착수 전에 확정합니다.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:pattern {"slug":"tenlune/terms"} /-->
</section>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"tenlune/process"} /-->

<!-- wp:group {"tagName":"section","className":"tl-sec","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-sec">
	<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-sec-head">
		<!-- wp:paragraph {"className":"tl-sec-label"} -->
		<p class="tl-sec-label">Out of scope</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"tl-sec-title"} -->
		<h2 class="wp-block-heading tl-sec-title">이런 건 하지 않습니다</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"tl-sec-desc"} -->
		<p class="tl-sec-desc">못 하는 것을 못 한다고 적어 두면, 할 수 있다고 적은 것도 믿을 수 있게 됩니다.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"tl-prin","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-prin">
		<!-- wp:group {"className":"tl-p","layout":{"type":"default"}} -->
		<div class="wp-block-group tl-p">
			<!-- wp:paragraph {"className":"q"} --><p class="q">대규모 트래픽을 전제로 한 시스템</p><!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"d"} --><p class="d">운영 인력이 여럿 필요한 규모는 1인 스튜디오가 맡을 자리가 아닙니다.</p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tl-p","layout":{"type":"default"}} -->
		<div class="wp-block-group tl-p">
			<!-- wp:paragraph {"className":"q"} --><p class="q">요구사항 없이 "알아서 예쁘게"</p><!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"d"} --><p class="d">무엇을 만들지 정하지 않고 시작하면 결국 두 번 만듭니다. 정리부터 함께 합니다.</p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tl-p","layout":{"type":"default"}} -->
		<div class="wp-block-group tl-p">
			<!-- wp:paragraph {"className":"q"} --><p class="q">검수 없이 빨리만 넘기는 작업</p><!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"d"} --><p class="d">직접 써보고 고치는 시간이 일정에서 빠지면 맡지 않습니다.</p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tl-sec","layout":{"type":"constrained"}} -->
<section class="wp-block-group tl-sec">
	<!-- wp:group {"className":"tl-sec-head","layout":{"type":"default"}} -->
	<div class="wp-block-group tl-sec-head">
		<!-- wp:paragraph {"className":"tl-sec-label"} -->
		<p class="tl-sec-label">FAQ</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"className":"tl-sec-title"} -->
		<h2 class="wp-block-heading tl-sec-title">자주 묻는 질문</h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:details {"summary":"AI를 쓰나요?"} -->
	<details class="wp-block-details"><summary>AI를 쓰나요?</summary>
		<!-- wp:paragraph -->
		<p>씁니다. 탐색과 구현을 빠르게 하는 데 씁니다. 다만 AI가 만든 결과를 검수 없이 납품하지 않습니다 — 무엇을 만들지 판단하고, 나온 결과를 직접 확인하고 고치는 것은 사람이 합니다. 실제로 어디에 썼고 무엇을 고쳤는지는 <a href="/work/">제작 사례</a>에 적어 둡니다.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"summary":"수정은 몇 번까지 되나요?"} -->
	<details class="wp-block-details"><summary>수정은 몇 번까지 되나요?</summary>
		<!-- wp:paragraph -->
		<p>횟수로 세지 않습니다. 처음 합의한 요구사항 안에서의 수정은 완료까지 함께 하고, 새로운 기능·화면·콘텐츠가 늘어나는 범위 확장은 별도로 상의해 진행합니다. 그래서 착수 전에 요구사항을 글로 확정합니다.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"summary":"결과물은 누구 것인가요?"} -->
	<details class="wp-block-details"><summary>결과물은 누구 것인가요?</summary>
		<!-- wp:paragraph -->
		<p>의뢰하신 분의 것입니다. 소스와 계정, 도메인·호스팅 접근 권한을 인수인계합니다. 제3자 유료 서비스나 라이선스가 들어가는 경우에는 착수 전에 어떤 것이 필요한지 알려 드립니다.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"summary":"납품 후 유지보수도 되나요?"} -->
	<details class="wp-block-details"><summary>납품 후 유지보수도 되나요?</summary>
		<!-- wp:paragraph -->
		<p>가능합니다. 다만 정기 계약을 기본으로 두지 않습니다. 필요한 시점에 필요한 범위만 상의해서 진행하는 편이 서로 부담이 적습니다.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->
</section>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"tenlune/contact"} /-->
