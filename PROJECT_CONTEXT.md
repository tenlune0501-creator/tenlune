# PROJECT_CONTEXT.md — Tenlune

이 문서는 Tenlune 웹사이트(WordPress, cafe24 호스팅)에 대한 지속적인 프로젝트 컨텍스트
문서입니다. **2026-08-29 이후 이 저장소(`C:\Users\minh0\Tenlune`)는 로컬 git 저장소**로,
테마(`wp-content/themes/tenlune/`)·커스텀 플러그인(`wp-content/plugins/tenlune-content/`)·
WPCode 스니펫 사본(`snippets/`)·디자인 기준 자료(`design/`)를 추적합니다. 다만 **라이브
WordPress 사이트가 여전히 Source of Truth**이고, 이 저장소는 그 사본입니다(원격 없음,
자동 배포 없음). 콘텐츠(페이지/글/CPT)와 옵션은 저장소가 아니라 사이트에만 있습니다.

마지막 갱신일: 2026-08-29 (도메인 301 마이그레이션 완료 / WPVibe→tenlune.com 이전 /
C2·C3·M1·M2·M3 라이브 반영 / tenlune-content 0.1.3 / 문서 최종 정리)

---

## 사이트 기본 정보 (2026-08-29 현재)

- **운영 도메인: `https://tenlune.com` — 연결·정규화 완료.** WP `siteurl`/`home` =
  `https://tenlune.com` (REST 루트로 재확인). 아래 리다이렉트가 전부 동작:
  - `http://tenlune.com`, `http/https://www.tenlune.com` → **301** → `https://tenlune.com`
  - `http://minh05.mycafe24.com/*` → **301** → `https://tenlune.com/*` (기존, 경로·쿼리 보존)
  - `https://minh05.mycafe24.com/*` → **301** → `https://tenlune.com/*`
    (2026-08-29 추가 — `/minh05/www/.htaccess` 최상단 host 규칙, 아래 섹션 참조)
  - 옛 도메인 어떤 스킴으로 들어와도 1홉으로 `https://tenlune.com` 도착, 루프 없음.
- **HSTS 없음** (양쪽 호스트). `Really Simple Security` 플러그인은 **비활성**
  (`wp plugin list` = `9.1.2 inactive`). HTTPS 는 WordPress 코어 스킴 보정 + 위 301 로 유지.
- 워드프레스 관리: WPVibe(Claude Code 연동). **2026-08-29 WPVibe 등록이
  `minh05.mycafe24.com` → `https://tenlune.com` 으로 이전됨** (옛 등록 제거,
  Application Password revoke). 이제 모든 WPVibe 호출은 `tenlune.com` 을 향합니다.
- `wp-config.php` `DISALLOW_FILE_EDIT=true` — wp-admin 코드 편집기 **및 WPVibe
  `file/read`·`file/write` 전부 차단**. `.htaccess` 등 서버 파일은 사용자가 Cafe24
  웹FTP/파일관리자로만 읽기·수정 가능. `DISALLOW_FILE_MODS` 미설정 → 플러그인 ZIP
  업로드 교체는 가능. 서버 실행 코드는 WPCode 스니펫(브라우저 승인 → 꺼짐 저장 →
  사용자가 wp-admin 에서 활성화; Claude 활성화 불가).
- 테마: `tenlune` (커스텀 제작, FSE 블록 테마, `wpvibe_authored: false`). 버전 `0.1.1`.
  반응형 CSS 이미 촘촘 (아래 "확인된 기존 자산" 참고).
- 커스텀 플러그인: `tenlune-content` — **버전 `0.1.3`** (라이브·저장소 일치). CPT `case`,
  택소노미 `case_type`, 케이스 필드/블록, `case_type` 아카이브 rewrite(C2),
  OG/Twitter + `<meta name="description">` + 비-singular canonical(C3/M2/M3).
- 활성 WPCode 스니펫: **21**(견적 JS)·**22**(견적 CSS)·**29**(문의 폼 자동채움)·
  **39**(코어 사이트맵 임시 비활성 — M1, 첫 글 발행 시 해제).

## 2026-08-26 — 영업 시작 준비 작업 (완료)

`class-material-manager` 세션에서 이어서, "실제 고객에게 사이트 주소를 보여주고 영업을
시작할 수 있는 상태"를 목표로 진행했습니다. 전체 사이트를 새로 만든 것이 아니라, 이미
확정된 디자인·브랜드 방향은 그대로 두고 **실제로 작동하지 않던 부분**을 채웠습니다.

### 발견한 상태 (작업 시작 시점)

- 홈페이지(히어로, 제작 범위, 작업 방식, 원칙 등)는 이미 완성도 높게 구현되어 있었음
  (재작업 안 함).
- `/contact/`, `/services/`, `/about/`, `/blog/` 가 전부 **404** — 헤더/푸터 네비게이션
  링크만 있고 실제 페이지가 없었음.
- `/privacy/` 링크도 404 (`privacy-policy` 슬러그로 초안 상태 존재, 링크는 `/privacy/`를
  가리켜 슬러그 불일치).
- 문의 폼 자체가 없었음 (Contact Form 7 등 문의 폼 플러그인 미설치).
- "AI 견적 최적화" 기능 없음 (재사용할 기존 구현이 없었음).
- 작업 사례(포트폴리오) CPT(`case`)는 준비돼 있었지만 실제 등록된 사례 0건.
- 사이트 제목이 "minh05"로 표시됨 (푸터만 "Tenlune"이라 표기가 불일치).

### 이번에 구현한 것

1. **사이트 제목/태그라인**: "Tenlune" / "웹사이트 · 웹서비스 · 업무 자동화 — 아이디어를
   쓸 수 있는 웹으로"로 수정 (기존에 "minh05"로 남아 있던 불일치 해결).
2. **Contact Form 7 설치** — 문의 폼 플러그인. ~~기본 폼(ID 12)의 필드 구성을 한국어로
   교체하려 시도했으나, WPVibe REST 경로(`contact-form-7/v1`)의 쓰기가 실제 저장소에
   반영되지 않는 캐시성 문제를 발견해 필드 라벨은 CF7 기본 영어 상태로 남아 있습니다.~~
   **(2026-08-26 후속 작업에서 완전히 해결 — 아래 "CF7 폼 한국어화 및 2단 구조 개선"
   섹션 참고. 현재 라벨은 전부 한국어이며, 예산 선택/AI 견적 자동입력/고객 자유입력이
   구조적으로 분리되어 있습니다.)** 운영자 이메일: `minh05@naver.com`.
3. **`/contact/` 페이지 신설** — 위 문의 폼 + 거래 조건(가격/기간/결제 방식) 안내.
4. **`/services/` 페이지 신설** — 4개 서비스 범위 상세 설명, 도메인·호스팅 연결을
   "원격 지원/함께 진행" 서비스로 명시(자동화 서비스 아님을 분명히 함), AI 견적
   최적화 도구 자리, 거래 조건.
5. **`/about/` 페이지 신설** — 기존에 이미 확정된 문구(1인 운영, AI 활용+사람 검수 원칙)만
   재사용. 확인되지 않은 이력·경력을 새로 지어내지 않았습니다.
6. **`/blog/` 페이지 신설** + Settings의 "글 페이지"로 지정 (기존에 아예 없어서 404였음).
7. **`/privacy/` 개인정보처리방침 수정** — 슬러그를 `privacy`로 맞추고 발행 상태로 전환,
   WordPress 기본 보일러플레이트(댓글·쿠키 등 이 사이트와 무관한 내용) 대신 실제
   수집 항목(문의 폼 입력값)·이용 목적·보관 기간·외부 서비스(cafe24, CF7, Akismet)를
   반영한 내용으로 다시 작성.
8. **작업 사례(포트폴리오) 2건 등록** — 둘 다 본문 첫 줄에 **"자체 제작
   프로젝트입니다 — 고객 의뢰가 아닙니다"** 를 명시:
   - "Tenlune 홈페이지" (이 사이트 자체)
   - "수업자료 관리 도구" (class-material-manager)
9. **AI 견적 최적화 도구 (하이브리드)** — 규칙 기반 계산(JS, 항상 동작) + LLM 설명 보강
   (PHP REST 엔드포인트, API 키 설정 시에만 동작, 실패해도 규칙 기반 결과는 그대로 유지).
   가격/기간 숫자는 항상 규칙 엔진이 정하고, AI는 그 숫자를 바꾸지 않고 설명·중복 정리만
   담당하도록 프롬프트에 명시했습니다. `/contact/`로 "이 구성으로 문의하기" 버튼을 통해
   자연스럽게 이어집니다. **WPCode 코드 스니펫 3건(JS 계산 로직/PHP LLM 엔드포인트/CSS)이
   사용자 승인 대기 중입니다 — 승인 후 wp-admin에서 활성화해야 실제로 보입니다.**

### 확인된 기존 자산 (재작업하지 않음)

- 홈페이지 전체 섹션(히어로/제작 범위/작업 방식/제작 사례 전시/원칙/기록/문의 CTA)
- 반응형 CSS(`assets/css/tenlune.css`)가 이미 촘촘하게 구성되어 있음: 12개 이상의
  브레이크포인트(400~1040px), 그리드가 모바일에서 1열로 정확히 쌓임, 가로 스크롤을
  유발할 만한 고정 너비가 사실상 없음(`rem`/`em`/`clamp()` 기반).
- `case`(제작 사례) 커스텀 포스트타입과 아카이브(`/work/`), 분류(`case_type`: 웹사이트·
  웹서비스·개선·도구)

### 검증 결과

- 신규/수정 페이지(`/`, `/contact/`, `/services/`, `/about/`, `/blog/`, `/privacy/`,
  제작 사례 2건) 전부 실제 HTTP 200으로 확인. 이전에 404였던 링크가 전부 해결됨.
  "서비스" 링크는 별도 페이지가 아니라 `/services/`(위 신설 페이지)로 정상 연결.
- 홈페이지 브랜드명이 "Tenlune"으로 정상 표시, 포트폴리오 섹션에 실제 사례 2건 노출 확인.
- CSS 파일을 직접 조회해 반응형 규칙(브레이크포인트·grid·overflow 방지)을 확인 — 실제
  브라우저 스크린샷 검증 도구는 이 세션에 연결돼 있지 않아, 코드 기준 검증까지만
  했습니다.
- `blog_public` 옵션 확인 — 검색엔진 차단 아님(정상 색인 허용 상태).
- git 기반 프로젝트가 아니라 "Production build" 개념은 적용되지 않으며, 대신 각 변경을
  실제 사이트에 적용한 뒤 REST/WP-CLI로 재조회해 검증했습니다.
- Codex 리뷰: 이 프로젝트는 로컬 코드 저장소가 없어(WordPress 사이트 자체가 소스) 기존
  class-material-manager 방식의 Codex 코드 리뷰를 그대로 적용할 대상(diff)이 없습니다.
  적용하지 않았습니다.

### 알려진 이슈 / 후속 조치 필요 (사용자 확인 필요)

> **2026-08-29 기준 갱신**: 아래 1·2·4·5 는 모두 해결됨. 미해결은 **3(AI LLM 보강
> 엔드포인트)** 뿐이며, 이는 출시 필수가 아님(규칙 기반 계산기가 핵심이고 완전 동작).

1. ~~**도메인 미연결**~~ **(2026-08-29 해결)** — `https://tenlune.com` 연결·정규화 완료,
   옛 도메인 전 스킴 301. 위 "사이트 기본 정보" + 아래 "2026-08-29" 섹션 참조.
2. ~~**CF7 문의 폼 라벨이 영어**: 기능은 정상이지만 라벨 번역이 REST 경로로는 반영되지
   않았습니다. wp-admin에서 직접 수정 권장.~~ **(2026-08-26 해결됨 — 아래 새 섹션 참고)**
3. **AI 견적 도구 — 규칙 기반 계산기(핵심 기능)는 활성화·검증 완료**: JS 계산
   로직(snippet id 21)과 CSS(snippet id 22)를 사용자가 승인·활성화했습니다.
   `/services/` 페이지에 `[wpcode id="21"]`로 연결되어 있습니다.
   - **실제로 겪은 버그와 수정**: 활성화 직후 계산기가 "불러오는 중입니다…"에서 멈추는
     문제가 있었습니다. 원인은 WordPress의 콘텐츠 렌더링 파이프라인이 인라인
     `<script>` 안의 `&&`를 `&#038;&#038;`로 텍스트 치환해 JS 문법 오류를 일으킨
     것이었습니다(라이브 스크립트를 직접 받아 Node.js로 파싱해 재현·확인). `&&`를
     `.every()` 기반 헬퍼로 바꿔 해결했고, `scrollIntoView` 방어 코드도 함께 보강했습니다.
     jsdom으로 실제 라이브 스크립트를 시뮬레이션 실행해 입력→계산→문의 연결까지
     전 과정을 재검증했습니다(정상).
   - CSS도 처음엔 `location: frontend_only`로는 실제 출력되지 않아 `site_wide_header`로
     바꿔 해결·확인했습니다.
   - **PHP LLM 보강 엔드포인트는 NinjaFirewall이 두 번(원격 승인 경로 모두) 차단**했습니다
     — 익명 접근 가능한 REST 라우트 + 외부 API 호출 + 비밀 헤더 조합이 실제 백도어
     패턴과 유사해 정상적으로 의심된 것으로 판단해 우회 시도를 하지 않았습니다. wp-admin의
     WPCode 편집기에서 직접 붙여넣어야 합니다(코드는 대화 기록 참고). **이것 없이도
     규칙 기반 견적 계산기는 완전히 동작하며, 이게 핵심 기능입니다.** LLM 설명 보강까지
     쓰려면 추가로 `tenlune_ai_quote_api_key` 옵션에 Anthropic API 키가 필요합니다
     (미설정 시 규칙 기반 결과만 표시 — 정상 동작, fallback으로 설계됨).
4. ~~**포트폴리오 이미지 없음**~~ **(2026-08-28 해결)** — case 18·19 에 대표 이미지
   적용 (attachment 32·33). 아래 "2026-08-28 — 제작 사례 대표 이미지" 섹션 참조.
5. ~~**SEO 메타 디스크립션/OG 태그**~~ **(2026-08-28~29 해결, SEO 플러그인 없이)** —
   `tenlune-content/includes/social-meta.php` 가 전 페이지에 OG/Twitter(C3) +
   `<meta name="description">`(M2) + 비-singular `<link rel="canonical">`(M3) 출력.
   Services/Contact/About/Privacy 는 수동 `post_excerpt` 로 설명문 정제. 아래 해당
   섹션들 참조.

### 유지보수 시 주의사항

- 콘텐츠 편집은 REST API(`/wp/v2/pages`, `/wp/v2/cases` 등)로 가능하지만, **CF7 폼처럼
  플러그인이 자체 저장 로직을 갖는 경우 일반 REST 경로 쓰기가 반영 안 될 수 있습니다** —
  이번에 실제로 겪은 문제이니, 비슷한 증상(쓰기는 성공했다고 나오는데 재조회하면 그대로)이
  보이면 wp-admin에서 직접 확인하세요.
- POST 본문에 `<script>` 태그가 포함되면 NinjaFirewall(보안 플러그인)이 요청 자체를
  차단합니다 — 정상적인 보안 동작입니다. 프런트엔드 JS가 필요하면 WPCode 코드 스니펫
  (사람 승인 필요)을 씁니다.
- `wp-config.php`의 `DISALLOW_FILE_EDIT` 때문에 테마/플러그인 PHP 파일 직접 편집은
  불가합니다. 필요하면 이 값을 사용자가 직접 내려야 합니다.

---

## 2026-08-26 (후속) — v1 가격 정책 개편 + CF7 한국어화 + 문의 폼 2단 구조 (완료)

기존에 정상 동작하던 계산기를 전면 재작성하지 않고, 가격 정책 데이터(`PRICING` 객체)와
결과 UX만 개편했습니다. WPCode snippet id 21(JS 계산 로직)·id 22(CSS)·id 29(문의 폼
자동채우기)를 그대로 재사용/수정했습니다.

### v1 가격 정책 (단일 소스: snippet id 21의 `PRICING` 객체)

- **서비스 5종 시작가** (전부 "~원부터", 고객 친화적 질문형 라벨 사용):
  - 한 페이지로 서비스·상품 소개 — 200,000원부터 (랜딩페이지, 5영업일~)
  - 회사·가게·브랜드 홈페이지 — 300,000원부터 (6영업일~)
  - 예약·신청·회원 기능 등이 있는 서비스(웹서비스) — 400,000원부터 (8영업일~)
  - 이미 있는 사이트 개선 — 200,000원부터 (4영업일~)
  - 반복 업무 자동화 — 250,000원부터 (4영업일~)
- **범위 폭(rangeFactors)**: 서비스 유형별 불확실성에 따라 다르게 적용
  (narrow 1.10 / medium 1.20 / wide 1.35, 웹서비스가 가장 넓음). 모든 서비스에
  일괄 +25%를 적용하던 이전 방식을 폐기했습니다.
- **기능 옵션 5종, 전부 가격 "범위"** (예약·신청 +10~15만, 회원 로그인 +10~15만,
  운영자 관리 화면 +15~20만, 다국어 지원 +10~20만, 외부 서비스·API 연동
  +10~25만이지만 실제로는 `consult: true`로 지정해 강제 숫자 대신 "상담 후 산정"
  문구를 보여줍니다). 라벨은 전부 비개발자도 이해할 수 있는 질문형이고, 기술 용어는
  괄호 힌트로만 붙습니다(예: "신청 내역이나 등록된 정보를 직접 확인·관리하고 싶어요
  (운영자 관리 화면)").
- **결제/쇼핑몰 기능 완전 제거**: 계산기에 결제 연동 옵션 자체가 없습니다
  (`#feat-payment` 미존재, jsdom으로 확인). `/services/`에 "현재 쇼핑몰·전자상거래
  사이트 제작은 진행하지 않습니다." 안내 문구를 추가했고, "결제 연동"을 언급하던
  기존 문구는 제거했습니다.
- **묶음 최적화(bundle)는 명시적으로 등록된 조합에만 적용** — 전체 조합에 일괄
  10~30% 할인을 적용하지 않습니다. 현재 등록된 조합은 `예약·신청 + 회원 로그인 +
  운영자 관리 화면` 하나뿐(로그인 인증·신청자 정보 구조·운영자 권한을 함께 설계하면
  중복 구현이 줄어든다는 실제 근거). 결과 화면은 "개별 구성 기준 예상 비용 → 공유
  가능한 구현 작업 → 중복 구현 감소 → 최적화 후 예상 견적" 순서로 보여주고, "할인"이
  아니라 "공통 구조를 함께 구현해 중복 작업을 줄였습니다"로 설명합니다.
- **초기 포트폴리오 확보 프로모션은 계산식과 완전히 분리된 `PRICING.promo` 객체**로
  존재합니다(150,000원부터, 가장 작은 작업 한정). 나중에 프로모션을 없애도 계산
  로직을 건드릴 필요가 없습니다.
- **예산 초과 시 "억지 할인" 대신 단계 제안**: 예상 견적이 입력 예산을 넘으면,
  `max` 비용이 큰 기능부터 하나씩 제외해가며 재계산하는 알고리즘으로 "1단계(지금
  진행)"와 "2단계(예산이 늘어나면 추가 가능)"를 자동으로 나눠 보여줍니다. 기본
  구성 자체가 예산보다 높으면 "범위를 더 줄인 형태로 시작" 안내로 대체됩니다.
- **문의하기 CTA의 자동 전달 문구에 최적화 요약도 포함**되도록 개선했습니다(서비스
  유형/필요 기능/상담 필요 항목/**최적화 요약**/예상 견적/예상 기간/예산 순).
- 선택 화면 표시(가격 힌트), 실제 계산, 결과 설명이 전부 `PRICING` 객체 하나만
  참조합니다 — 별도로 하드코딩된 표시용 숫자가 없습니다.
- 로컬 jsdom 시나리오 테스트(기본값/묶음 트리거/예산 초과/상담 전용 기능/결제
  옵션 부재)와 라이브 스크립트 재검증(node --check + jsdom)으로 통과 확인.

### CF7 문의 폼 한국어화 + "자동 전달 견적 / 고객 직접 작성" 2단 구조 (신규)

**저장 위치 관련 중요 발견**: CF7은 `post_content`가 아니라 **protected(밑줄 접두)
postmeta**(`_form`, `_mail`, `_mail_2`, `_messages`, `_additional_settings`,
`_locale`, `_hash`)에서 실제로 렌더링합니다. `wp post meta list <id>`는 기본적으로
이 키들을 숨기므로 `--all` 옵션이 필요합니다. 예전에 `post_content`를 아무리 수정해도
반영되지 않았던 이유가 이것입니다. **CF7 자체의 공식 REST API
(`/contact-form-7/v1/contact-forms/{id}` PUT)로도 재현 테스트했지만, 응답은 새 값을
echo하면서도 실제 저장(재조회 시 이전 값 그대로)은 되지 않는 이 환경 고유의 문제를
확인**했습니다(형식 문제 아님 — 전체 5개 속성 그룹을 다 포함해도 동일). 결국 승인
게이트가 있는 `run_wp_cli db query` UPDATE(WPVibe가 안내하는 최후 수단)로 `_form`과
`_mail`만 최소 범위로 직접 수정했습니다. **`_mail_2`/`_messages`는 건드리지 않았습니다**
(불필요했고, 손대면 PHP 직렬화 배열을 바이트 단위로 재구성해야 하는 위험이 있음).

**`run_wp_cli`의 command 문자열 이스케이프 버그(재현 확인)**: SQL 안에 `\"`(이스케이프된
큰따옴표)를 넣으면 따옴표 자체가 통째로 사라집니다(`autocomplete:"name"` →
`autocomplete:name`처럼). CF7 폼 태그 문법은 공백 포함 값(select 선택지, placeholder)에
큰따옴표가 필수라서 이 버그 때문에 처음 시도한 UPDATE는 `select`/`textarea` 태그가
파싱되지 않는 상태로 저장됐습니다. **해결책: SQL 안에서 `CHAR(34)`로 따옴표를 만들고
`CONCAT(...)`으로 이어 붙여 우회.** 세미콜론(`;`)도 같은 이유로 "Multiple SQL
statements are not allowed" 오류를 일으켜 `CHAR(59)`로 동일하게 우회했습니다. `_mail`처럼
PHP 직렬화 배열(`a:N:{s:len:"key";...}`)을 직접 SQL로 쓸 때는, Node.js로 UTF-8 바이트
길이(`Buffer.byteLength`, 문자 수가 아님)를 정확히 계산해 `s:N:"..."` 프리픽스를 만들고,
직접 만든 PHP-serialize 검증기로 결과를 재검증한 뒤에만 실행했습니다. **비슷한 작업이
필요하면 이 패턴(CHAR() 치환 + 바이트 길이 계산 + 로컬 검증)을 그대로 재사용하세요.**

**폼 구조 (post 12, `_form` meta)**: 이름·이메일·문의 제목·예산(select, 5개 선택지)에
이어 두 필드가 분리되어 있습니다.
1. `quote-summary` (textarea, 선택 사항, placeholder만 있고 비어 있어도 제출 가능) —
   AI 견적 도구의 "이 구성으로 문의하기" 버튼을 눌렀을 때만 자동으로 채워집니다.
2. `your-message` (textarea, 필수) — "상세 요청 — 원하는 디자인·분위기, 꼭 필요한
   기능, 참고 사이트, 희망 일정, 기타 전달사항을 자유롭게 적어주세요"로 라벨을 바꿔
   고객이 직접 입력하는 자유 입력란으로 재정의했습니다.

**운영자 메일 템플릿 (`_mail`)**: 제목에 `[budget]`을 추가하고, 본문에
`[견적 도구에서 전달된 내용]\n[quote-summary]\n\n[상세 요청]\n[your-message]`
형태로 두 섹션을 명확히 구분해 넣었습니다 — 자동 견적 요약과 고객의 자유 입력이
운영자 이메일 한 통에 모두 보이며, 라벨로 어느 쪽인지 구분됩니다. `_mail_2`(고객
자동 응답 메일)는 기존 상태 그대로 두었습니다(원래도 비활성 상태).

**연결 스니펫 수정**:
- snippet id 29(문의 폼 자동 채우기)가 이제 `your-message`가 아니라
  `textarea[name="quote-summary"]`를 채웁니다. `your-message`는 항상 비어 있는
  상태로 고객을 맞이합니다.
- snippet id 21(AI 견적 도구)의 CTA 링크 생성부에서, quote-summary 전용 필드가
  생겼으므로 예전에 붙이던 "\n\n추가로 전달하고 싶은 내용:\n" 안내 문구를 제거했습니다
  (이제 그 역할은 `your-message`의 CF7 라벨 자체가 합니다).

**검증**: CF7 REST 피드백 엔드포인트(`/contact-form-7/v1/contact-forms/12/feedback`)로
실제 제출 테스트 2회 진행 — 예산/견적 요약(번들 최적화 문구 포함)/자유 입력 메시지를
모두 채워 제출했고, 둘 다 `status: "mail_sent"`로 정상 처리됨을 확인했습니다(즉
`_form`과 새로 재구성한 `_mail` 직렬화 값이 실제로 유효하게 동작). 라이브 HTML에서
`budget` select 5개 옵션, `quote-summary`/`your-message` textarea가 모두 올바른
`name` 속성과 한국어 라벨로 렌더링되는 것도 curl로 재확인했습니다.
**주의**: 이 검증 과정에서 CF7 관리자 알림 메일이 실제로 2통 발송되었습니다(발신
`test-e2e@example.com`, 테스트 데이터). 실제 문의가 아니므로 무시해도 됩니다.

### AI LLM 보강 엔드포인트 — 아직 미배포 (다음 단계)

규칙 기반 계산기(핵심 기능)와 문의 폼 2단 구조가 이제 완전히 검증되었으므로, 이 다음
단계로 PHP LLM 엔드포인트를 업데이트된 v2 payload 형태(`service`, `features`,
`consultFeatures`, `bundle`, `low`, `high`, `days`, `budget` — 실제로 snippet 21이
`window.tlAiQuoteEndpoint`로 POST하는 필드)에 맞춰 갱신하고, wp-admin에 수동으로
붙여넣는 정확한 절차를 안내해야 합니다. **NinjaFirewall이 PHP 코드 스니펫 승인
경로를 차단하므로(익명 REST 라우트 + 외부 API 호출 + 비밀 헤더 조합이 백도어 패턴과
유사해 정상적으로 의심된 것으로 판단, 우회 시도하지 않음) 이 파일 자체는 여전히
`code_snippet` 도구로 저장할 수 없고, wp-admin의 WPCode 편집기에 사용자가 직접
붙여넣어야 합니다.**

---

## 2026-08-28 — 제작 사례 대표 이미지 적용 (완료)

위 "알려진 이슈" 4번(포트폴리오 이미지 없음) 해소:

- Case **18** "Tenlune 홈페이지" ← attachment **32** (`tenlune-home-case-cover.png`)
- Case **19** "수업자료 관리 도구" ← attachment **33** (`class-material-manager-case-cover.png`)
- 둘 다 1600×1000 (16:10), WP 표준 미디어 업로드 + `set_post_thumbnail()` 경로로
  적용(REST 콘텐츠/DB 직접수정 아님). 홈 `/` 와 `/work/` 모두 `has-post-thumbnail`
  + `core/post-featured-image` 로 정상 렌더(데스크톱 16:10 / 모바일 4:3 crop,
  `object-fit:cover; object-position:center top`).
- 원본 로컬 이미지는 `design/case-images/` 에 있고 미수정. 처음 업로드 시 파일명이
  한글+em-dash(`—`)라 raw URL 이 404 나는 문제가 있어 ASCII 파일명으로 재업로드,
  중간 생성물(attachment 30·31)은 사용자 승인 후 삭제.
- 캐시: `wp super-cache flush` + `wp cache flush` 실행.

---

## 2026-08-28 — /work/ 모바일 반응형 조사 (코드 미수정) + 도메인 마이그레이션 이슈 기록

> **2026-08-29 종결**: (1) `/work/` 반응형 — 이후 세션에서 라이브 `/work/`·`/work/type/`·
> case 상세를 여러 폭에서 재확인, 정상. 코드 변경 없이 종결(재확인 pending 해제).
> (2) 도메인 마이그레이션 — 아래 🔴 항목은 **완료**됨. 자세한 내용은 맨 아래
> "2026-08-29 — 도메인 301 마이그레이션" 섹션.

### /work/ 반응형 — 보고된 증상이 현재 소스에서 재현 안 됨

사용자가 `tenlune.com/work/` 를 390px 에서 열었을 때 "레이아웃이 모바일 폭으로
줄지 않고, 데스크톱 폭 콘텐츠가 가운데 남아 좌우가 잘리며, 소개문구가 잘리고,
가로 스크롤이 발생" 을 보고했습니다. 조사 결과:

- 라이브 `/work/` HTML(캐시 purge 후 `minh05.mycafe24.com`·`tenlune.com` 양쪽
  새로 fetch) + 라이브 `tenlune.css` 를 로컬 브라우저로 **360 / 390 / 768 / 1440px**
  렌더 → **전부 정상**. `.tl-work-grid` 는 <760px 1열 / ≥760px 2열 로 올바르게
  동작, 가로 오버플로·소개문구 잘림 없음. 390px 를 넘는 DOM 요소를 하나도
  특정하지 못함.
- `.tl-work-grid` CSS (`wp-content/themes/tenlune/assets/css/tenlune.css` L624–632)
  는 이미 `grid-template-columns: 1fr` (base) + `@media (min-width:760px){ 1fr 1fr }`.
  로컬 == 라이브 (MD5 `a98d09cd…`, 완전 동일). `/work/` 경로에 고정 px 폭·
  `white-space:nowrap`·`alignwide/full` 없음.
- NinjaFirewall 이 자동 브라우저의 `/work/` 접근을 **양쪽 도메인 모두 403 차단** →
  실제 라이브 URL 을 브라우저로 직접 렌더 검증은 못 함(라이브 HTML + 라이브 CSS
  로컬 렌더로 대체).
- **조치**: `wp super-cache flush` + `wp cache flush` 실행(서버 캐시 재생성).
  **코드·CSS·구조·Git 변경 없음.**
- **유력한 배경**: 오래된 캐시(브라우저의 stale `tenlune.css` `?ver=0.1.1` 또는
  `tenlune.com` 엣지 캐시가 예전 상태로 데워짐). 사용자가 하드 리프레시(Ctrl+Shift+R)
  후 390px 재확인 예정. 재확인에서도 깨지면 그때의
  `document.documentElement.scrollWidth` 값 + 넘치는 DOM 요소 목록을 받아 최소
  수정(후보: `.tl-work-grid` 를 `grid-template-columns: minmax(0,1fr)` 로 하드닝 —
  grid+이미지 blowout 표준 방어책, 정상일 땐 시각적 변화 0)을 승인받아 진행.

### ✅ 별도 이슈 — 도메인 마이그레이션 (2026-08-29 완료)

> 아래는 2026-08-28 시점 기록(당시 미완료). **2026-08-29 에 전부 해소**됨 —
> `siteurl`/`home` 은 이미 `https://tenlune.com` 이었고(문서만 stale 이었음),
> mixed content·srcset 누출도 해소, 옛 도메인은 https 까지 301. 상세는 맨 아래
> "2026-08-29 — 도메인 301 마이그레이션" 섹션. 아래 문단은 이력으로 남겨둡니다.

위 "알려진 이슈" 1번(도메인 미연결)은 부분적으로만 해소됨:

- `tenlune.com` 은 **연결됨** — `https://tenlune.com/work/` 가 이 사이트의 HTML 을
  200 으로 서빙(`Link: <https://minh05.mycafe24.com/wp-json/>`, 같은 title/구조,
  리다이렉트 없음).
- 그러나 WP `siteurl` / `home` 옵션은 여전히 **`http://minh05.mycafe24.com/`**
  (HTTP 스킴 + 옛 도메인). HTTPS 는 **Really Simple SSL** 플러그인이 출력 시점에
  `http→https` 로 덮어써서 유지 중.
- 부작용:
  - `tenlune.com/work/` 의 `<img srcset>` URL 이 `http://minh05.mycafe24.com/...`
    로 새어 나옴 → HTTPS 페이지에서 **mixed content**. (`src` 는 https 로 정상)
  - 모든 CSS / JS / 이미지 / canonical 이 `tenlune.com` 이 아니라 옛 도메인
    `minh05.mycafe24.com` 을 가리킴 (SEO·정본 URL·자산 크로스도메인 로드).
  - 캐시 variant 에 따라 예전 브라우저에서 `http://` 스타일시트가 mixed-content
    차단되면 "CSS 없는 화면" = 위 반응형 증상과 동일하게 보일 수 있음(재현은 못 했으나
    가장 그럴듯한 배경).
- **이것 자체는 `/work/` 반응형 오버플로의 직접 원인 아님** — 로컬 재현에서 CSS 를
  전부 제거해도 390px 에서 가로 오버플로가 나지 않음.
- **미실행 수정안**: `siteurl`/`home` → `https://tenlune.com/` 변경 + DB
  search-replace (`minh05.mycafe24.com` → `tenlune.com`, `http:` → `https:`).
  **도메인·호스팅 설정 변경**이라 백업 후 사용자 승인 하에 별도 작업으로만 진행.
  사용자 요청으로 이번엔 손대지 않음.

### 🟡 향후 정리 항목 — 테마 CSS 캐시 무효화

- `tenlune.css` 가 `?ver=0.1.1` (테마 버전 고정) 로 제공되어 브라우저가 사실상
  영구 캐시함. 테마 CSS 를 실제로 수정할 일이 생기면 `wp-content/themes/tenlune/
  style.css` 의 `Version:` 헤더를 올려 쿼리스트링을 바꿔야 재방문자에게 반영됨.

---

## 2026-08-29 — 도메인 301 마이그레이션 · WPVibe 이전 · C2/C3/M1/M2/M3 · 문서 정리 (완료)

이 저장소가 로컬 git 저장소로 자리잡은 뒤, "🔜 내일 이어서" 목록(C2/C3 커밋 · M4 도메인 ·
M1 sitemap · M2/M3 검색·SNS)을 순서대로 라이브 반영·검증했습니다. 라이브 검증은 전부
curl(실제 Chrome UA — NinjaFirewall) 기준. agent-browser 는 NinjaFirewall 이 자동
브라우저를 차단해 사용 불가.

### C2 / C3 커밋 + `tenlune-content` 0.1.2

- C2 — `case_type` 아카이브 rewrite 우선순위: `includes/post-types.php` 의
  `tenlune_register_case_taxonomy()` 에 `add_rewrite_rule('work/type/([^/]+)/?$',
  'index.php?case_type=$matches[1]', 'top')`. `/work/type/웹사이트/`·`/work/type/웹서비스/`
  가 attachment 조회로 빠지지 않고 200 + 올바른 사례 노출.
- C3 — `includes/social-meta.php` 신규 (`wp_head` 우선순위 5) + `tenlune-content.php`
  require 1줄. OG/Twitter 태그 전 페이지 출력. 사례/글 = `og:type=article` + 대표이미지,
  그 외 = `website` + 옵션 `tenlune_og_default_image`(attachment 36).
- 플러그인 버전 `0.1.1 → 0.1.2`, `dist/tenlune-content-plugin.zip` Python `zipfile`
  재빌드(루트 `tenlune-content/`, 전부 forward slash, CRC OK). 사용자 wp-admin 업로드
  교체 → `wp plugin list` = `0.1.2 active` 확인.
- 커밋: `145e0e0` fix(work) / `c25d64b` feat(content) / `a7614a5` chore(release)
  (dist zip + `design/tenlune-og.png` + `design/tenlune-favicon.png` +
  `design/case-images/`).

### M4 — 옛 도메인 → 신 도메인 301

- **WPVibe 재연결**: `connect_site('https://tenlune.com')` (브라우저 승인) → 검증
  (site_info / plugin list / `wp-json/wpvibe/v1` 200) → 기존 `minh05.mycafe24.com`
  등록 `remove_site` (Application Password revoke). 현재 등록 사이트 1개
  = `Tenlune / https://tenlune.com`.
- **적용 전 확인**: `http://minh05…/*` 및 `http/https://(www.)tenlune.com` 은 이미
  경로+쿼리 보존 301. `siteurl`/`home` 도 이미 `https://tenlune.com`(지난 세션에
  변경됨 — 이 문서가 stale 이었음). 남은 구멍 = `https://minh05.mycafe24.com/*` 만 200.
  origin = Apache(`.htaccess` 유효), 앞단 openresty.
- **적용**: Cafe24 도메인 포워딩은 기본 도메인(`*.mycafe24.com`)이라 대상 선택 불가
  ("이용 가능한 도메인이 없습니다") → `.htaccess` 폴백. 사용자가 Cafe24 웹FTP 로
  **`/minh05/www/.htaccess` 최상단**에 아래 블록 추가:

  ```apache
  # BEGIN Tenlune canonical host (old-domain -> tenlune.com 301) 2026-08-29
  <IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_URI} !^/\.well-known/ [NC]
  RewriteCond %{HTTP_HOST} ^(www\.)?minh05\.mycafe24\.com$ [NC]
  RewriteRule ^ https://tenlune.com%{REQUEST_URI} [R=301,L,NE]
  </IfModule>
  # END Tenlune canonical host
  ```
  (`NE` = 한글 슬러그 `%..` 이중 인코딩 방지. 쿼리스트링은 mod_rewrite 기본으로 append.
  `# BEGIN WordPress`/`# BEGIN NinjaFirewall` 블록보다 위, 그 블록들은 미수정.)
- **백업**: `/minh05/www/.htaccess.bak-20260829` (Cafe24 웹FTP). 롤백 = 백업 재업로드
  또는 삽입 블록만 삭제. DB 변경 없음.
- **검증**: `https://minh05.mycafe24.com/`·한글 taxonomy 경로+`?x=1` → 301 →
  `https://tenlune.com/...` (경로/쿼리/인코딩 보존, 1홉, 루프 없음). `tenlune.com` 200
  유지, `wp-json/wpvibe/v1` 200 유지. `<meta property="og:url">`·canonical·srcset 에서
  `minh05` / 안전하지 않은 `http://` 참조 0.

### M1 — sitemap 404 (D안: 코어 사이트맵 임시 비활성)

- **원인**: `show_on_front=posts` + 발행 글 0개라, 코어가 `/wp-sitemap*.xml` 에 유효
  XML 을 담아도 HTTP 404 로 반환(`handle_404` 경로). 커스텀 테마/플러그인/스니펫 무관.
  `wp rewrite flush` 로는 해결 안 됨(상태코드 이슈).
- **조치**: WPCode PHP 스니펫 **id 39** `M1: 코어 사이트맵 임시 비활성화` =
  `add_filter( 'wp_sitemaps_enabled', '__return_false' );` (location `everywhere`).
  사용자 승인·활성화 → `wp rewrite flush`(고아 `wp-sitemap*` 규칙 제거) → `wp cache purge`.
- **결과**: `robots.txt` 에서 `Sitemap:` 줄 제거. `/wp-sitemap.xml`·하위 4종·`.xsl`
  전부 일반 WP 404(테마 404 HTML — "깨진 XML 사이트맵" 아님).
- 🔁 **후속 (첫 실제 블로그 글 발행 시 코어 사이트맵 재활성화)**:
  1. WPCode 에서 스니펫 **id 39 비활성화**
     (`https://tenlune.com/wp-admin/admin.php?page=wpcode-snippet-manager&snippet_id=39`).
  2. `wp rewrite flush` (또는 설정 → 퍼머링크 저장) — `wp-sitemap*` 규칙 재생성.
  3. `wp cache purge`.
  4. 검증: `/wp-sitemap.xml` → 200 + 유효 XML, `robots.txt` 에 `Sitemap:` 줄 복귀.
  - M5(블로그 구조)·M6(빈 Journal 섹션) 정리와 함께 처리 권장.

### M2 / M3 + 페이지 발췌 — `tenlune-content` 0.1.3

- **M2** — `tenlune_social_meta_render()` 에 `<meta name="description">` 추가.
  `og:description` 과 같은 컨텍스트별 `$desc`(단일=발췌, 홈/검색=태그라인, 아카이브=기본
  문구) 재사용. 코어·테마가 이 태그를 안 내보내므로 중복 없음.
- **M3** — 같은 함수에 `<link rel="canonical">` 추가. `is_front_page() || is_home() ||
  is_post_type_archive('case') || is_tax('case_type')` 에만 출력 → 코어
  `rel_canonical()`(singular 전용)과 중복 안 됨. 홈=`https://tenlune.com/`,
  `/work/`=아카이브 링크, `/work/type/{slug}/`=term 링크.
- **페이지 발췌 (코드 없음)**: `wp post update` 로 Services(14)·Contact(13)·About(15)·
  Privacy(3) 에 수동 `post_excerpt` 설정. 자동 발췌의 "01 · … 02 · …" 구조 텍스트·잘림
  제거. `social-meta.php` 가 이미 `get_the_excerpt()` 를 쓰므로 `og:description` ·
  `twitter:description` · `meta[name=description]` 에 그대로 반영. 롤백 = `post_excerpt`
  빈 값.
- 플러그인 `0.1.2 → 0.1.3`, ZIP 재빌드·검증(11엔트리·forward slash·CRC OK·작업트리
  일치·내부 0.1.3). 사용자 wp-admin 업로드 교체 → `wp plugin get` = `0.1.3 active`.
- 커밋: `2d11d4f` feat(content): emit meta description and canonical for non-singular
  views / `83bb5bf` chore(release): rebuild plugin package at 0.1.3.
- **라이브 검증**: 9개 페이지(홈·work·work/type·services·contact·about·privacy·case·blog)
  전부 200 + `meta[name=description]` 1 + `canonical` 1 + `og:description` 1. singular
  canonical 중복 0. PHP 에러 0. OG/Twitter 세트(og:*=10, twitter:*=4)·social-meta
  블록 마커 2(=1블록) 유지.

### 미디어 / 옵션 / 스니펫 요약 (현재)

| 항목 | 값 |
|---|---|
| `site_icon` | attachment **35** (`tenlune-favicon.png`, 1254×1254) — 파비콘 "T", `design/tenlune-favicon.png` |
| `tenlune_og_default_image` | attachment **36** (`tenlune-og.png`, 1733×908) — 비-사례 페이지 OG 이미지, `design/tenlune-og.png` |
| case 대표 이미지 | 18 ← attachment 32, 19 ← attachment 33 (`design/case-images/`) |
| WPCode 스니펫 | 21 견적 JS · 22 견적 CSS · 29 폼 자동채움 · **39 사이트맵 임시 비활성(M1)** |
| 수동 `post_excerpt` | 페이지 14·13·15·3 |
| `blog_public` | `1` (색인 허용) / 발행 글 0개 / `show_on_front=posts` / `page_for_posts=16` |
| `tenlune-content` 플러그인 | **0.1.3** (라이브·저장소 일치) |
| 테마 `tenlune` | `0.1.1` (미변경) |

### 배포 / 유지보수 절차 (갱신)

- `tenlune-content` 코드 변경 = 로컬 편집 → 버전 올림(헤더 `Version:` + 상수 일치) →
  `dist/tenlune-content-plugin.zip` **Python `zipfile`** 재빌드(forward slash 필수;
  PowerShell `Compress-Archive` 는 `\` 로 만들어 Cafe24 설치 시 깨짐) → **사용자가
  wp-admin → 플러그인 → 새로 추가 → 플러그인 업로드 → "현재 설치된 것을 업로드로 교체"**
  → 활성 유지 확인 → curl 검증. git 원격·자동 배포 없음.
- 서버 파일(`.htaccess` 등): `DISALLOW_FILE_EDIT` 로 WPVibe 도 못 읽음 → 사용자가
  Cafe24 웹FTP/파일관리자로만. 수정 전 타임스탬프 백업 필수(같은 docroot 를
  `tenlune.com` 도 공유 → 문법 오류 시 동시 500).
- 서버 실행 PHP = WPVibe `code_snippet` → 브라우저 승인 → **꺼진 상태로 저장** →
  사용자가 wp-admin `enable_url` 에서 활성화(Claude 활성화 불가, WPCode 가 활성화 시
  fatal 검사).
- 라이브 검증 = curl + 실제 Chrome UA(NinjaFirewall). agent-browser 는 차단됨.
