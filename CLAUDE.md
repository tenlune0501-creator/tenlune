# CLAUDE.md — Tenlune

## 시작 전 필수

- 작업 시작 시 항상 루트의 **`PROJECT_CONTEXT.md`를 먼저 읽는다.** 이 문서가 사이트 구조·
  가격 정책·문의 폼·알려진 이슈의 기준이다.
- 이 저장소는 **로컬 개발 소스**이고, 실제 운영 기준은 라이브 WordPress 사이트
  (현재 `minh05.mycafe24.com`, 목표 도메인 `tenlune.com`)이다.
  **라이브 사이트가 Source of Truth이며, 로컬은 그 사본이다.**

## 작업 원칙

### 1. 라이브 ↔ 로컬 차이를 먼저 확인한다
- 테마 / 플러그인 / WPCode 스니펫 / 콘텐츠를 수정하기 전에, 해당 부분의 **라이브 현재
  상태와 로컬 소스의 차이**를 먼저 확인한다 (WPVibe · REST API · wp-admin).
- 로컬이 오래됐을 수 있다. 차이가 있으면 어느 쪽이 최신인지 확인한 뒤 진행한다.
- `snippets/`의 파일은 WPCode 스니펫의 로컬 사본이다 — 연결 정보는 `snippets/README.md`.
- `wp-config.php`의 `DISALLOW_FILE_EDIT` + NinjaFirewall 때문에 서버 실행 코드는
  wp-admin의 WPCode 스니펫(사람 승인 필요)으로만 반영된다.

### 2. 디자인
- **확정된 Tenlune 디자인과 기존 구현을 우선 기준으로 삼는다.**
- 기존 디자인을 임의로 재해석하거나 새 방향을 도입하지 않는다.
- 디자인 기준 자료:
  - `design/tenlune-home-final.html` — 홈 HTML 기준안
  - `design/mockups/` — 홈 A/B 시안, 다크모드 시안
  - `design/pdf/` — 전체 · 홈 · 서비스 페이지 시안
- 반응형 CSS(`wp-content/themes/tenlune/assets/css/tenlune.css`)는 이미 촘촘하게
  구성돼 있음 (12개 이상 브레이크포인트, rem/em/clamp 기반). 재작업하지 않는다.

### 3. 코드
- 유지보수성을 우선한다. 기존 파일의 스타일 · 명명 · 구조를 따른다.
- 동일하거나 유사한 UI · 스타일 · 로직이 반복되면, 새로 하드코딩하기 전에 적절한 공통
  구조를 먼저 검토한다: 테마 `patterns/` · `parts/`, 공통 CSS 클래스 / 커스텀 프로퍼티
  (`theme.json`), 공통 PHP/JS 함수.
- 기존 공통 구조로 구현 가능한 부분을 중복 구현하지 않는다.
- 단, **과도한 추상화는 하지 않는다.** 한두 번 쓰이는 것을 미리 일반화하지 않는다.

### 4. 가격 정책
- 가격 · 기간 숫자의 단일 소스는 `snippets/quote-tool-v2.js`의 `PRICING` 객체
  (= 라이브 WPCode snippet id 21). 규칙 엔진이 숫자를 정하고 LLM은 설명만 담당한다.

## 반드시 사용자에게 확인할 것

- 기획 / 디자인 방향 변경
- 가격 정책(`PRICING`) 변경
- **라이브 사이트에 실제 반영되는 변경** — 스니펫 붙여넣기, 콘텐츠 수정, 테마/플러그인 배포
- 도메인 · 호스팅 · 보안 플러그인 설정 변경
- `PROJECT_CONTEXT.md`의 확정사항과 충돌하는 변경

## 폴더 구조

```
Tenlune/
├─ PROJECT_CONTEXT.md              진행 컨텍스트 (먼저 읽기)
├─ CLAUDE.md                       이 파일
├─ wp-content/
│  ├─ themes/tenlune/              FSE 블록 테마 (정본, 추적 대상)
│  └─ plugins/tenlune-content/     커스텀 플러그인 — CPT `case` 등 (정본, 추적 대상)
├─ snippets/                       WPCode 스니펫 로컬 원본 + README(연결 정보)
├─ design/                         디자인 기준 자료 (HTML / PNG / PDF)
├─ dist/                           원본 배포 ZIP (보존용)
└─ backups/                        cafe24 백업 복사본 (git 미추적, 원본은 OneDrive)
```
