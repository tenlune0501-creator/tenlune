# Tenlune — WPCode 스니펫 / 문의 폼 라이브 연결 정보

이 폴더의 파일은 라이브 WordPress 사이트에서 실행되는 코드의 **로컬 사본**입니다.
`wp-config.php`의 `DISALLOW_FILE_EDIT` + NinjaFirewall 때문에 테마/플러그인 PHP 파일
직접 편집이 막혀 있어, 서버 실행 코드는 wp-admin > WPCode 스니펫에 **수동으로 붙여넣어**
반영합니다.

## 라이브 연결

| 로컬 파일 | WPCode snippet | 라이브 위치·역할 | 상태 |
|---|---|---|---|
| `quote-tool-v2.js` | **id 21** — "Tenlune AI 견적 최적화 도구" | `/services/` 페이지에 `[wpcode id="21"]`로 삽입. `PRICING` 객체가 가격 정책 단일 소스 | 활성·검증 완료 |
| `quote-tool-style.css` | **id 22** — "Tenlune AI 견적 도구 스타일" | 위치 `site_wide_header` (auto-insert) | 활성 |
| `contact-prefill.js` | **id 29** — "Tenlune 문의 폼 자동 채우기 (견적 도구 연결)" | `/contact/`에서 `?prefill=` 쿼리를 읽어 `textarea[name="quote-summary"]`를 채움 | 활성 |
| `ai-quote-llm-endpoint-v2.php` | (라이브에 대응 스니펫 **없음**) | REST 라우트 `tenlune/v1/ai-quote-explain` 예정 | **미배포.** NinjaFirewall이 원격 승인 경로를 2회 차단 → wp-admin WPCode 편집기에서 직접 붙여넣어야 함. 미배포 상태에서도 규칙 기반 견적 계산기는 정상 동작(LLM 설명 보강만 비활성) |
| — | Contact Form 7 **form ID 12** | `/contact/` 문의 폼 | 활성·한국어화·2단 구조(예산 선택 / AI 견적 자동입력 / 고객 자유입력) 완료 |

추가: LLM 설명 보강을 실제로 쓰려면 `tenlune_ai_quote_api_key` 옵션에 Anthropic API 키
필요 (미설정 시 규칙 기반 결과만 표시 — fallback으로 설계됨).

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
