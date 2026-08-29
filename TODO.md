# TODO — Tenlune 출시 준비

마지막 갱신: 2026-08-29 (C2/C3 커밋 + 도메인 301(M4) + sitemap 404 정상화(M1) + meta description·canonical(M2/M3)·페이지 발췌 완료)

목표: 고객에게 `https://tenlune.com` 링크를 보내고 작업 문의를 받을 수 있는 상태.

---

## ✅ 2026-08-28 완료 — 출시 전 blocker (C1–C4)

전체 사이트 실사용 QA(audit) 후 "지금 당장 고쳐야 영업 가능" 4건 라이브 반영·검증 완료.

- [x] **C1 — 홈페이지 stale 캐시 / mixed content**
  - WP Super Cache가 도메인 마이그레이션 이전 HTML 서빙 → 홈에 `minh05.mycafe24.com` 53건 + `http://` case-cover 이미지(mixed content).
  - `wp cache flush` + `wp super-cache flush` → 홈 `minh05` 0건 / mixed 0건 재검증.
- [x] **C2 — 포트폴리오 "유형" 링크 404** (`/work/type/<slug>/`)
  - 원인: `case` CPT 첨부 rewrite 규칙 `work/[^/]+/([^/]+)/?$` 가 택소노미 규칙보다 먼저 매칭 → attachment 조회 → 404.
  - `tenlune-content/includes/post-types.php` → `tenlune_register_case_taxonomy()` 에 `add_rewrite_rule('work/type/([^/]+)/?$', 'index.php?case_type=$matches[1]', 'top')` 추가.
  - 플러그인 ZIP 재빌드 → wp-admin 업로드 교체 → `wp rewrite flush` → `/work/type/웹사이트/`·`/work/type/웹서비스/` 200, 아카이브에 올바른 사례 표시.
- [x] **C4 — 파비콘이 WordPress 기본 "W"**
  - `design/tenlune-favicon.png` (1254×1254) → 미디어 업로드 (attachment **35**) → `site_icon` = 35 (REST `PUT /wp/v2/settings`).
  - `/favicon.ico` → Tenlune "T" 아이콘, `<link rel="icon">` / `apple-touch-icon` / `msapplication-TileImage` 적용.
- [x] **C3 — OG / Twitter 메타 없음** (링크 카드 미리보기 안 뜸)
  - 신규 `tenlune-content/includes/social-meta.php` (`wp_head` 우선순위 5) + `tenlune-content.php` require 1줄.
  - `design/tenlune-og.png` (1733×908) → 미디어 업로드 (attachment **36**) → 옵션 `tenlune_og_default_image` = 36.
  - 플러그인 ZIP 재빌드 → wp-admin 업로드 교체 → 캐시 flush.
  - 11개 페이지 전부 `og:*`(9종+보너스) + `twitter:*`(4종) 출력. 사례 상세는 대표이미지 + `og:type=article`, 그 외 전용 OG 이미지 + `website`. canonical 충돌 없음, 중복 없음, `minh05` 참조 0.

---

## ✅ 2026-08-29 완료 — C2/C3 커밋 · 도메인 301(M4) · sitemap(M1) · M2/M3 · 페이지 발췌

### C2 / C3 로컬 변경 커밋 (구 "내일 이어서 1")
- `tenlune-content` 플러그인 버전 `0.1.1 → 0.1.2` (헤더 `Version:` + `TENLUNE_CONTENT_VERSION` 일치).
- `dist/tenlune-content-plugin.zip` Python `zipfile` 재빌드 (루트 `tenlune-content/`, 전부 forward slash, CRC 정상, 작업트리와 바이트 일치, 내부 버전 0.1.2). → 사용자가 라이브 wp-admin 업로드 교체 완료, `wp plugin list` = `Tenlune Content 0.1.2 active` 확인.
- 커밋 3개 (main, push 안 함):
  - `145e0e0` fix(work): fix case type archive rewrite priority — `includes/post-types.php`
  - `c25d64b` feat(content): add social sharing meta tags — `includes/social-meta.php` + `tenlune-content.php` (0.1.2 포함)
  - `a7614a5` chore(release): update plugin package and design assets — `dist/…zip` + `design/tenlune-og.png` + `design/tenlune-favicon.png` + `design/case-images/`
- `PROJECT_CONTEXT.md`, `TODO.md` 문서 커밋은 이 세션 마지막 단계 (아래 "🔜 남은 작업" 참조).

### M4 — 옛 도메인 → 신 도메인 301 (구 "내일 이어서 2")
- **WPVibe 재연결**: `minh05.mycafe24.com` → `https://tenlune.com` 으로 전환. 새 연결 검증(site_info/plugin list/wp-json/wpvibe/v1 = 200) 후 기존 `minh05` 등록 `remove_site` (Application Password revoke). 현재 등록 사이트 = `Tenlune / https://tenlune.com` 1개.
- **적용 전 확인된 상태**: `http://minh05…/*` 및 `http/https://(www.)tenlune.com` 은 이미 경로+쿼리 보존 301. 남은 구멍 = `https://minh05.mycafe24.com/*` 만 200 (중복 콘텐츠). WP `siteurl`/`home` 은 이미 `https://tenlune.com` (지난 세션에 변경됨 — PROJECT_CONTEXT 기록이 stale).
- **적용**: Cafe24 도메인 포워딩은 기본 도메인이라 대상 선택 불가 → `.htaccess` 폴백. 사용자가 `/minh05/www/.htaccess` 최상단에 host 조건부 301 블록 추가 (`RewriteCond %{HTTP_HOST} ^(www\.)?minh05\.mycafe24\.com$` → `https://tenlune.com%{REQUEST_URI} [R=301,L,NE]`, `/.well-known/` 제외). 백업: `/minh05/www/.htaccess.bak-20260829`.
- **검증**: `https://minh05.mycafe24.com/` → 301 → `https://tenlune.com/`. 한글 taxonomy 경로 + `?x=1` → 경로/쿼리/인코딩 보존. 1홉, 루프 없음. `tenlune.com` 200 유지, WPVibe `wpvibe/v1` 200 유지.
- **롤백**: `.htaccess` 백업 재업로드 또는 삽입 블록 삭제. DB 변경 없음.

### M1 — sitemap 404 정상화 (구 "내일 이어서 3")
- **원인**: `show_on_front=posts` + 발행 글 0개라 코어가 `/wp-sitemap*.xml` 에 유효 XML 을 담아도 HTTP 404 로 반환 (`handle_404` 경로, `nocache_headers`). 커스텀 테마/플러그인/스니펫 무관 — 순수 코어 동작. `wp rewrite flush` 로는 해결 안 됨(상태코드 이슈).
- **조치 (D안 — 출시 우선, 임시 비활성화)**: WPCode PHP 스니펫 **id 39** `M1: 코어 사이트맵 임시 비활성화` = `add_filter( 'wp_sitemaps_enabled', '__return_false' );` (location `everywhere`). 사용자 승인·활성화 완료. → `wp rewrite flush` (고아 `wp-sitemap*` 리라이트 규칙 제거) → `wp cache purge`.
- **결과 검증**: `robots.txt` 에서 `Sitemap:` 줄 제거됨. `/wp-sitemap.xml`·하위 4종·`.xsl` 전부 일반 WP 404 (테마 404 HTML, 더 이상 "깨진 XML 사이트맵" 아님). tenlune.com 주요 9개 페이지 200, PHP 에러 0, OG 메타 유지, 옛 도메인 301 유지, WPVibe 정상.
- 🔁 **후속 (필수) — 블로그 첫 실제 글 발행 시 코어 사이트맵 재활성화**:
  1. WPCode 에서 스니펫 **id 39 비활성화** (`https://tenlune.com/wp-admin/admin.php?page=wpcode-snippet-manager&snippet_id=39`).
  2. `wp rewrite flush` 1회 (또는 설정 → 퍼머링크 저장) — `wp-sitemap*` 리라이트 규칙 재생성.
  3. `wp cache purge`.
  4. 검증: `/wp-sitemap.xml` → 200 + 유효 XML, `robots.txt` 에 `Sitemap:` 줄 복귀.
  - 이 시점은 M5(블로그 구조)·M6(빈 Journal 섹션) 정리와 함께 처리하는 것이 자연스러움.

### M2 / M3 + 페이지 발췌 — 검색·SNS 노출 마무리 (구 "남은 작업 1")
- **발췌 (코드 없음, `wp post update`)**: Services(14)·Contact(13)·About(15)·Privacy(3) 에 손으로 쓴 `post_excerpt` 설정. 자동 발췌의 "01 · … 02 · …" 구조 텍스트·잘림 제거 → 정제된 문장. 롤백 = 해당 `post_excerpt` 를 빈 값으로.
- **M2 / M3 (플러그인 코드)**: `tenlune_social_meta_render()` 한 함수에 +18줄.
  - M2 — `<meta name="description">` 를 og:description 과 같은 `$desc` 로 출력. 코어·테마가 이 태그를 안 내보내므로 중복 없음. 전 페이지 1개.
  - M3 — `<link rel="canonical">` 를 홈 · `is_post_type_archive('case')` · `is_tax('case_type')` 에만 출력. singular 은 조건에서 제외해 코어 `rel_canonical()` 과 중복 안 됨.
  - 플러그인 `0.1.2 → 0.1.3`, ZIP 재빌드(11엔트리·forward slash·CRC OK·작업트리 일치·내부 0.1.3). 사용자 wp-admin 업로드 교체 → `wp plugin get` = `Tenlune Content 0.1.3 active` 확인.
  - 커밋 2개: `2d11d4f` feat(content): emit meta description and canonical for non-singular views / `83bb5bf` chore(release): rebuild plugin package at 0.1.3.
- **라이브 검증**: 9개 페이지(홈·work·work/type·services·contact·about·privacy·case·blog) 전부 200 + `meta[name=description]` 1개 + `canonical` 1개 + `og:description` 1개. 홈 canonical `https://tenlune.com/`, `/work/` `…/work/`, `/work/type/웹사이트/` term 링크. singular canonical 중복 0 (코어 1개 유지). PHP 에러 0, OG/Twitter 세트(og:*=10, twitter:*=4) 유지, social-meta 블록 마커 2(=1블록), 옛 도메인 301·WPVibe·robots(Sitemap 줄 0)·`/wp-sitemap.xml` 404 전부 유지.

---

## 🔜 남은 작업

### ✅ 출시 필수 작업 — 전부 완료 (2026-08-29)

C1–C4 (2026-08-28) + 아래 (2026-08-29) 로 "고객에게 `https://tenlune.com` 링크를 보내고
문의를 받을 수 있는 상태" 달성. 상세는 위 **"✅ 2026-08-29 완료"** 섹션.

- [x] C2 — `case_type` 아카이브 rewrite / C3 — OG·Twitter 메타 → 라이브 + 커밋
- [x] M4 — 옛 도메인(http·https·www) → `https://tenlune.com` 301, WPVibe 재연결
- [x] M1 — 깨진 sitemap 404 정상화 (코어 사이트맵 임시 비활성, 스니펫 39)
- [x] M2 — `<meta name="description">` / M3 — 홈·아카이브 canonical → 라이브 + 커밋
- [x] Services/Contact/About/Privacy 페이지 수동 `post_excerpt`
- [x] `PROJECT_CONTEXT.md` 라이브 상태 기준 갱신 (이 세션)
- [x] `PROJECT_CONTEXT.md` + `TODO.md` 문서 커밋 (이 커밋)

### 🔁 출시 후 후속 (예정된 작업, 잊지 말 것)

- [ ] **블로그 첫 실제 글 발행 시 코어 사이트맵 재활성화**: WPCode 스니펫 **id 39**
  비활성화 → `wp rewrite flush` → `wp cache purge` → `/wp-sitemap.xml` 200 + `robots.txt`
  `Sitemap:` 줄 복귀 확인. (M5·M6 정리와 함께)

---

## 🗂 후순위 — 출시 후 개선 (기존 audit Medium / Low, 삭제하지 않음)

### Medium
- [ ] **M5 — 블로그 구조 반쪽** : `show_on_front=posts` 라 `/` 가 글 목록. `/blog/` 는 손으로 쓴 별도 정적 페이지. 사례 상세엔 "RELATED JOURNAL: 아직 이어지는 글이 없습니다". 첫 글 쓰기 전에 구조 정리 (`page_for_posts=16` 활용 여부 결정).
- [ ] **M6 — 홈 메인의 빈 "Journal / 기록" 섹션** ("아직 쓴 글이 없습니다" 노출) → 첫 글 전까지 숨기는 방안.

### Low
- [ ] **M7 — `<meta name="generator">` 노출** (`content="WordPress 7.1"`). `wp core version` = 7.1 로 값 자체는 정상 확인됨. 정보 노출 최소화 원하면 `remove_action('wp_head','wp_generator')` 정도.
- [ ] **M8 — 모바일 히어로 상단 빈 공간 과다** (홈·서비스·문의, 390px 기준 헤더~첫 콘텐츠 ~250–350px). 사용성 지장 없음, 균형만.
- [ ] **M9 — 미사용 스크립트 로드** : CF7 JS + 견적 prefill JS 가 폼 없는 페이지(홈·소개·블로그·404·개인정보)에도 로드.
- [ ] **M10 — 포트폴리오 사례 2건, 둘 다 자체 제작** ("고객 의뢰 아님" 명시). 실제 고객 사례 확보 시 추가.

### 검증만 하고 조치 안 한 항목 (참고, 문제 아님)
- `/work/feed/`, `/comments/feed/`, `xmlrpc.php?rsd` 노출 — WP 표준.
- 콘솔 에러 캡처 신뢰도 불확실 (agent-browser `console` 빈 출력) — 재확인 여지.

---

## 배포 / 검증 메모

- `tenlune-content` 플러그인 변경 = 로컬 편집 → `dist/tenlune-content-plugin.zip` 재빌드(Python `zipfile`, forward slash) → **사용자가 wp-admin → 플러그인 → 새로 추가 → 플러그인 업로드 → "현재 설치된 것을 업로드로 교체"**. git 원격·자동 배포 없음.
- `DISALLOW_FILE_EDIT=true` — wp-admin 코드 편집기 **및 WPVibe `file/read`·`file/write` 전부 차단**. `.htaccess` 등 서버 파일은 사용자가 Cafe24 웹FTP/파일관리자로만 읽기·수정 가능. `DISALLOW_FILE_MODS` 미설정이라 플러그인 ZIP 업로드 교체는 가능.
- 서버 실행 PHP = WPVibe `code_snippet` → 브라우저 승인 → 스니펫은 **꺼진 상태로 저장** → 사용자가 wp-admin `enable_url` 에서 활성화 (Claude 활성화 불가). 현재 활성 스니펫: 21(견적 JS)·22(견적 CSS)·29(폼 자동채움)·**39(사이트맵 임시 비활성 — M1)**.
- WPVibe 등록 사이트 = `https://tenlune.com` (2026-08-29 전환, 옛 `minh05` 제거됨).
- 라이브 검증 = curl (실제 Chrome UA 필수 — NinjaFirewall). agent-browser 는 NinjaFirewall 이 자동 브라우저를 차단해 대체로 타임아웃 → curl 위주.
- 이미지 등 로컬 파일을 라이브에 올릴 때 = WPVibe `request_upload` → `check_upload` → `upload_media`.
