// ⚠️ 폐기됨 — 2026-08-29. 이 코드는 더 이상 사용하지 않습니다.
//
// AI 견적 LLM 보강 엔드포인트는 WPCode 스니펫이 아니라 tenlune-content 플러그인으로
// 옮겨졌습니다:  wp-content/plugins/tenlune-content/includes/ai-quote.php
//
// 바뀐 점:
//  - Provider: Anthropic → 멀티 provider (기본 OpenRouter, 설정으로 Groq 전환).
//    둘 다 OpenAI 호환 /chat/completions. 레지스트리 tenlune_ai_quote_providers().
//  - 전환: 상수 TENLUNE_AI_QUOTE_PROVIDER='groq' (또는 필터), + 해당 provider 키만.
//  - API 키: openrouter=TENLUNE_OPENROUTER_API_KEY / groq=TENLUNE_GROQ_API_KEY
//    (각각 옵션 tenlune_openrouter_api_key / tenlune_groq_api_key fallback).
//  - 통신은 공용(tenlune_ai_quote_chat) — 프롬프트 생성(build_messages)과 분리.
//  - 동일 출처 검사 + REMOTE_ADDR 레이트리밋 + 화이트리스트 + graceful fallback.
//  - 0.1.6: reasoning 모델 대응. OpenRouter 요청에만 extra_body 로
//    reasoning:{enabled:false} + max_tokens 320→400 + 파서 <think> 제거. Groq 무변경.
//  - 배포 경로: 플러그인 ZIP 업로드 (WPCode 코드 전송이 아니라서 NinjaFirewall 오탐 회피)
//
// 이 파일은 이력용으로만 남겨 둡니다. 붙여넣지 마세요.
