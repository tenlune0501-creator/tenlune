# Tenlune — WPCode 스니펫 / 문의 폼 라이브 연결 정보

이 폴더의 파일은 라이브 WordPress 사이트에서 실행되는 코드의 **로컬 사본**입니다.
`wp-config.php`의 `DISALLOW_FILE_EDIT` + NinjaFirewall 때문에 테마/플러그인 PHP 파일
직접 편집이 막혀 있어, 서버 실행 코드는 wp-admin > WPCode 스니펫에 **수동으로 붙여넣어**
반영합니다.

## 라이브 연결

| 로컬 파일 | WPCode snippet | 라이브 위치·역할 | 상태 |
|---|---|---|---|
| `quote-tool-v2.js` | **id 21** — "Tenlune AI 견적 최적화 도구" | `/services/` **와** `/contact/` 두 페이지에 `[wpcode id="21"]` + `<div id="tl-quote-tool">` 로 삽입. 하나의 스니펫이 컨텍스트(services / contact)를 인식. `PRICING` 이 가격 정책 단일 소스 | 활성 — **2026-08-30 컨텍스트 인식·검색엔진 옵션·단일 예상가격 적용 완료 (라이브 MD5 일치 검증)** |
| `quote-tool-style.css` | **id 22** — "Tenlune AI 견적 도구 스타일" | 위치 `site_wide_header` (auto-insert). 전 페이지 로드 → `/contact/` 재사용에 CSS 추가 불필요 | 활성 (변경 없음) |
| `contact-prefill.js` | **id 29** — "Tenlune 문의 폼 자동 채우기 (견적 도구 연결)" | `/contact/`에서 `?prefill=` 쿼리를 읽어 `textarea[name="quote-summary"]`를 채움. snippet 21 contact 모드는 이 필드 값/`?prefill=` 존재를 보고 "다시 계산하기" 상태를 만듦 | 활성 (변경 없음) |

### snippet 21 — Services / Contact 공용 (2026-08-30)

한 스니펫이 두 컨텍스트를 처리합니다. 계산 로직·`PRICING` 은 완전히 동일하며 모드에 따라
결과 처리만 다릅니다.

- **감지**: mount `<div id="tl-quote-tool">` 에 `data-tl-quote-mode="contact"` 가 있거나
  페이지에 `textarea[name="quote-summary"]` 가 있으면 **contact 모드**, 아니면 **services 모드**(기본).
- **services 모드**(변경 없음): 결과 아래 `이 구성으로 문의하기 →` 링크(`/contact/?prefill=...`) 를 붙임.
- **contact 모드**: 이동 CTA 대신 계산 요약을 `textarea[name="quote-summary"]` 에 기록(읽기 전용) +
  그 필드를 노출. 견적 전(직접 진입, `?prefill=` 없음, 값 없음)에는 빈 quote-summary `<p>` 를
  **DOM 표시만** 숨김(CF7 `_form` 메타는 미변경). `?prefill=` 로 넘어오면 계산기를 접고
  `견적 다시 계산하기` 버튼만 노출.
- **form control name 네임스페이스**: 내부 라디오/체크박스가 `tlq-service` / `tlq-feature` /
  `tlq-budget` — CF7 의 `budget` select 등과 충돌 방지. `.value` 는 그대로 `PRICING` 옵션 id 라
  계산 결과 불변(회귀 검증 완료).
- **검색엔진 초기 등록 지원 옵션**: `PRICING.features` 에 `seo_reg` (정액 `+75,000원`, `days: 0`,
  `flat: true`). 정액 옵션은 10,000원 반올림 **이후** 더해 OFF↔ON 이 정확히 `+75,000원`.
  플러그인 `ai-quote.php` 화이트리스트·프롬프트에 이 라벨과 "검색 순위·색인 완료 시점 보장 금지"
  지침 추가(plugin 0.1.8).
- **단일 예상가격 (2026-08-30)**: 가격 표시를 "최소~최대 범위"에서 **단일 예상가격**으로 바꿈.
  `PRICING` 의 `price`(서비스) / `min`·`max`(기능) / `saveMin`·`saveMax`(번들 할인)는 전부
  **기존 min·max 범위의 정확한 중간값**(예 100k~150k→125k, 300k~360k→330k). 새 가격 임의 설계
  없음. `seo_reg`(정액 +75,000), `integration`(상담 후 산정)은 그대로. 화면·quote-summary·
  `1단계` 대안·번들 감소액 모두 단일 금액. 계산식(`computePackage`)·기간 정책 불변 → `low === high`.
  AI 는 plugin `ai-quote.php` (0.1.8) 가 단일 금액 사실 + "범위 표현 금지" 프롬프트로 설명만 담당.

### Contact 페이지(Page 13) 삽입 블록

`[contact-form-7 id="12"]` 숏코드 **바로 위**에 아래를 넣습니다(CF7 폼·메타는 미변경):

```
<!-- wp:heading -->
<h2 class="wp-block-heading">예상 견적 확인</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-contact-p"} -->
<p class="tl-contact-p">간단한 항목을 선택하면 예상 금액과 기간을 확인할 수 있습니다. 견적 없이 바로 문의하셔도 됩니다.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div id="tl-quote-tool" class="tl-quote" data-tl-quote-mode="contact"><p>견적 도구를 불러오는 중입니다…</p></div>
[wpcode id="21"]
<!-- /wp:html -->
```

### CF7 form 12 — 고객 노출 메시지 한국어화 (2026-08-30)

`_messages` 포스트메타에 영어 문자열 12개가 저장되어 CF7 에 설치된 ko_KR 번역을
덮어쓰고 있었음(고객에게 "Please fill out this field." 등 영어 노출). 폼 `<form>` 에는
이미 `novalidate` 가 있어 브라우저 기본 메시지가 아니라 **CF7 자체 메시지**가 원인.

- 변경: `_messages` 12개 키만 한국어로 교체. `필수 항목을 입력해주세요.`(`invalid_required`),
  `입력 내용을 확인한 후 다시 시도해주세요.`(`validation_error`), `문의가 정상적으로 접수되었습니다.
  감사합니다.`(`mail_sent_ok`) 등. `_messages` 에 없는 키(`invalid_email` 등)는 ko_KR 번역이
  이미 적용되어 손대지 않음.
- 반영 방법: CF7 REST `PUT /contact-form-7/v1/contact-forms/12` 는 이 사이트 쓰기 하드닝
  때문에 `wp_update_post` 가 막혀 미반영. `wp post meta update 12 _messages '<json>' --format=json --force`
  (WordPress 가 직렬화, 단일 작업, 승인 게이트)로 반영. `_form`/`_mail`/`_mail_2`·`quote-summary`
  구조는 미변경. 라이브 저장소에 CF7 export 파일은 없음 — 이 메모가 유일한 로컬 기록.

| `ai-quote-llm-endpoint-v2.php` | — (폐기, 이력용) | — | **폐기됨(2026-08-29).** LLM 보강 엔드포인트는 WPCode 스니펫이 아니라 **`tenlune-content` 플러그인 `includes/ai-quote.php`** (plugin ≥ 0.1.5) 로 이관. REST `POST /wp-json/tenlune/v1/ai-quote-explain`. 배포 = 플러그인 ZIP 업로드 |
| — | Contact Form 7 **form ID 12** | `/contact/` 문의 폼 | 활성·한국어화·2단 구조(예산 선택 / AI 견적 자동입력 / 고객 자유입력) 완료 |

추가: LLM 설명 보강은 멀티 provider (OpenAI 호환). **기본 provider = Groq**
(2026-09-15 전환, 이전엔 OpenRouter 기본), 설정으로 OpenRouter 전환 가능.

| provider | base_url | 모델(확인값) | 키 상수 (옵션 fallback) | 추가 헤더 | 요청 body 추가(`extra_body`) |
|---|---|---|---|---|---|
| `groq` (기본, 2026-09-15~) | `https://api.groq.com/openai/v1` | `qwen/qwen3.8-27b` (라이브 확인, tool calling·131K ctx) | `TENLUNE_GROQ_API_KEY` (`tenlune_groq_api_key`) | — | `{}` (없음) |
| `openrouter` (대체) | `https://openrouter.ai/api/v1` | `qwen/qwen3.6-27b` (유료·소액, :free Qwen 없음, 2026-08-29 확인 — 다시 기본으로 쓰려면 모델 재확인 필요) | `TENLUNE_OPENROUTER_API_KEY` (`tenlune_openrouter_api_key`) | `HTTP-Referer: https://tenlune.com`, `X-Title: Tenlune` | `reasoning: { enabled: false }` |

`qwen3.6-27b` 는 OpenRouter 에서 reasoning `default_enabled: true` → 안 끄면 `max_tokens` 를
`<think>` 로 다 써 최종 답변이 빈 문자열로 옴(라이브 로그: `finish_reason=length`, output
정확히 320). plugin 0.1.6 부터 OpenRouter 요청에만 공식 unified 파라미터
`reasoning:{enabled:false}` 를 붙이고(`extra_body`, provider 별), `max_tokens` 기본값을
320→400 으로 조정. 파서는 혹시 섞여 오는 `<think>…</think>` 를 제거. Groq body 는 무변경
(라이브 검증 결과 `qwen/qwen3.8-27b` 는 `finish_reason: stop`, `<think>` 없이 곧바로
최종 답변을 냄).

**Qwen 모델 fallback (2026-09-15 신규, plugin 0.1.9)**: 기본 모델이 404 +
`model_not_found` 류(deprecated/removed/not found)로 명백히 unavailable 로 확인될 때만,
활성 provider 의 `/models` 를 조회해 같은 규모(size)·이상 버전의 Qwen 계열 후속 모델로
이번 요청 한정 1회 재시도(`ai-quote.php` 의 `tenlune_ai_quote_chat()`). 401/403/429/5xx/
network/timeout 은 대상 아님, `TENLUNE_AI_QUOTE_MODEL` 로 모델을 직접 고정하면 비활성.
class-material-manager 의 Groq/Qwen fallback(`groq-fallback.ts`)과 같은 원칙.

전환: `wp-config.php` 에 `define('TENLUNE_AI_QUOTE_PROVIDER','openrouter');` +
`TENLUNE_OPENROUTER_API_KEY` (OpenRouter로 되돌리기). 모델만 바꾸려면
`define('TENLUNE_AI_QUOTE_MODEL','...');`(이 경우 Qwen fallback 비활성). 선택된 provider
의 키가 없으면 외부 호출 없이 `{explanation: null}` — 규칙 기반 결과만 표시(fallback).

## Source of Truth

**라이브 사이트가 항상 최우선 기준입니다.** 이 로컬 파일은 마지막 동기화 시점의 사본일
뿐이며, 사이트에서 직접 수정된 내용이 있으면 그쪽이 정답입니다. 스니펫을 수정하기 전에
wp-admin(또는 `wp db query`로 `wp_posts.post_content`)의 실제 내용과 이 파일을 먼저
비교하세요.

배경·이력은 루트의 `PROJECT_CONTEXT.md` 참고.

## 동기화 기록

- **2026-08-28** — 라이브 WPCode 스니펫에서 로컬로 단방향 동기화 (라이브 무수정).
  - 소스: 라이브 `wp_posts.post_content` (WPVibe `wp db query` 읽기 전용). 줄바꿈 LF,
    파일 끝 개행 없음 — 라이브 원문 그대로. 코드 개선·리팩터링 없음.
  - 동기화 후 로컬 파일 MD5가 라이브 `MD5(post_content)`와 일치함을 확인:

    | 파일 | 라이브 `post_modified` | bytes | MD5 (라이브 = 로컬) |
    |---|---|---|---|
    | `quote-tool-v2.js` | 2026-08-26 14:15:53 | 17,623 | `f4514e8128904438f5f3210bfad3b658` |
    | `quote-tool-style.css` | 2026-08-26 13:41:36 | 2,307 | `8374fc06a990aa42cac329d8f6427978` |
    | `contact-prefill.js` | 2026-08-26 14:11:29 | 514 | `9e2d948909c2bac10bc76972630d3bb0` |

  - `quote-tool-v2.js` 변경 내용(이전 로컬 사본 대비): `summaryText`에 번들 "최적화 요약"
    줄 추가, 문의 CTA `contactUrl`에서 `"\n\n추가로 전달하고 싶은 내용:\n"` 접미사 제거
    — 둘 다 `PROJECT_CONTEXT.md` 2026-08-26 후속 섹션에 라이브 반영 완료로 기록된 변경.
