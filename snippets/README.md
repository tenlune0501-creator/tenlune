# Tenlune — WPCode 스니펫 / 문의 폼 라이브 연결 정보

이 폴더의 파일은 라이브 WordPress 사이트에서 실행되는 코드의 **로컬 편집 원본**입니다.
`wp-config.php`의 `DISALLOW_FILE_EDIT` + NinjaFirewall 때문에 테마/플러그인 PHP 파일
직접 편집이 막혀 있어, 서버 실행 코드는 wp-admin > WPCode 스니펫에 **수동으로 붙여넣어**
반영합니다.

## 라이브 연결

| 로컬 파일 | 라이브 연결 | 상태 |
|---|---|---|
| `quote-tool-v2.js` | WPCode snippet **id 21** — `/services/` 페이지에 `[wpcode id="21"]`로 삽입. `PRICING` 객체가 가격 정책 단일 소스 | 활성·검증 완료 |
| `ai-quote-llm-endpoint-v2.php` | WPCode PHP 스니펫 (REST 라우트 `tenlune/v1/ai-quote-explain`) | **아직 미배포.** NinjaFirewall이 원격 승인 경로를 2회 차단 → wp-admin WPCode 편집기에서 직접 붙여넣어야 함. 미배포 상태에서도 규칙 기반 견적 계산기는 정상 동작(LLM 설명 보강만 비활성) |
| CSS (이 폴더에 원본 없음) | WPCode snippet **id 22** — 위치 `site_wide_header` | 활성 |
| 문의 폼 | Contact Form 7 **form ID 12** | 활성·한국어화·2단 구조(예산 선택 / AI 견적 자동입력 / 고객 자유입력) 완료 |

추가: LLM 설명 보강을 실제로 쓰려면 `tenlune_ai_quote_api_key` 옵션에 Anthropic API 키
필요 (미설정 시 규칙 기반 결과만 표시 — fallback으로 설계됨).

## Source of Truth

**라이브 사이트가 항상 최우선 기준입니다.** 이 로컬 파일은 마지막으로 붙여넣은 버전의
사본일 뿐이며, 사이트에서 직접 수정된 내용이 있으면 그쪽이 정답입니다. 스니펫을 수정하기
전에 wp-admin의 실제 스니펫 내용과 이 파일을 먼저 비교하세요.

배경·이력은 루트의 `PROJECT_CONTEXT.md` 참고.
