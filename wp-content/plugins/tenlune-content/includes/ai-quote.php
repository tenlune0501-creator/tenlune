<?php
/**
 * AI 견적 설명 보강 — 규칙 기반 계산 결과를 LLM 이 자연어로 설명.
 * (Phase 3 — 기본 provider Groq · 기본 모델 qwen/qwen3.8-27b · Qwen 계열 모델 fallback)
 *
 * 역할 분담이 이 파일의 핵심입니다.
 *  - 가격·기간 숫자는 프런트엔드 규칙 기반 계산기(WPCode snippet 21, quote-tool-v2.js)가
 *    유일하게 정합니다. 이 엔드포인트는 그 숫자를 "사실"로만 받아 설명 문장을 만들 뿐,
 *    숫자를 새로 계산하거나 바꾸지 않습니다(프롬프트로 강제). 기본 모델이 후속 Qwen
 *    모델로 fallback 되어도 이 프롬프트/제약은 완전히 동일하게 적용됩니다.
 *  - 선택된 provider 의 키가 없거나 호출이 실패/타임아웃/레이트리밋이면 항상
 *    { "explanation": null } 을 200 으로 반환합니다. 규칙 기반 계산기는 이 엔드포인트
 *    없이도, 실패해도 완전히 동작합니다(quote-tool-v2.js 의 .catch / 조건부 렌더).
 *
 * 4층 분리:
 *  - tenlune_ai_quote_build_messages()   — provider 무관. system/user 프롬프트(설명 생성).
 *  - tenlune_ai_quote_providers() / _active_provider() / _provider_config()
 *                                        — provider 레지스트리·선택·설정(데이터). 여기만
 *                                          바꾸면 provider 전환.
 *  - tenlune_ai_quote_chat_attempt()     — OpenAI 호환 POST {base_url}/chat/completions 통신
 *                                          1회 호출(파싱·에러 분류 포함).
 *  - tenlune_ai_quote_chat()             — 위 함수를 오케스트레이션. 기본 모델 1회 →
 *                                          "모델 자체를 더 이상 쓸 수 없음"으로 확인된
 *                                          경우에만 동일 Qwen 계열 후속 모델로 1회 재시도
 *                                          (아래 "Qwen 모델 fallback" 참고).
 *                                          openrouter·groq 둘 다 동일 스키마라 provider 별
 *                                          분기 없음(base_url·model·headers·extra_body·키만
 *                                          설정에서 옴). 비호환 provider 는 이 두 함수만 재작성.
 *
 *  reasoning 모델 대응: openrouter provider 는 extra_body 로 `reasoning:{enabled:false}` 를
 *  보냅니다(qwen3.6-27b 는 default_enabled=true 라 안 끄면 max_tokens 를 <think> 로 소진).
 *  파서는 혹시 섞여 오는 `<think>…</think>` 를 제거합니다. Groq 는 extra_body 가 비어 무변경.
 *
 * Provider 선택:
 *  - 기본값 = 'groq' (tenlune_ai_quote_active_provider() 안의 한 줄). 기본 모델은
 *    provider 레지스트리의 'qwen/qwen3.8-27b' (Groq 확인값, tool calling·131K ctx).
 *  - 전환: wp-config.php 에 `define('TENLUNE_AI_QUOTE_PROVIDER', 'openrouter');` 또는
 *    필터 `tenlune/ai_quote_provider` 한 곳. 그리고 해당 provider 의 키 상수만 설정.
 *  - 프런트엔드 / REST 라우트 / 규칙 기반 견적 / 프롬프트는 전환 시에도 무변경.
 *
 * Qwen 모델 fallback (`tenlune_ai_quote_chat()` 안, class-material-manager 프로젝트의
 * Groq/Qwen fallback(`viewer/lib/tutor/providers/groq-fallback.ts`)에서 실제 검증된 것과
 * 같은 원칙을 이 계산기 구조에 맞게 최소 구현):
 *  - 정상 상황: 활성 provider 의 기본 모델을 1회 호출. `/models` 조회조차 하지 않음.
 *  - 그 호출이 404 + `model_not_found` 류(모델이 없음/deprecated/removed/unavailable)로
 *    "명백히" 확인되고, `TENLUNE_AI_QUOTE_MODEL` 로 모델을 사용자가 직접 override 하지
 *    않았을 때만 `{base_url}/models` 를 조회해 같은 규모(size)·같은 이상 버전의 Qwen
 *    계열 후속 모델을 고르고, 이번 요청 한정으로 딱 1회 재시도합니다.
 *  - 401/403(인증)·429(rate limit)·5xx·network/timeout/malformed 응답은 절대 fallback
 *    대상이 아닙니다. 후속 모델을 못 찾거나 그 재시도도 실패하면 더 재시도하지 않고
 *    (무한 retry 금지) 항상 `{ "explanation": null }` 로 귀결되어 규칙 기반 견적은
 *    그대로 유지됩니다.
 *  - fallback 후보 조회 결과는 5분 transient 캐시(성공/실패 모두) — 기본 모델이 죽어
 *    있는 동안 매 요청마다 `/models` 를 다시 부르지 않습니다.
 *  - 실제 사용된 모델(기본/fallback)은 고객 화면에 노출되지 않고, `WP_DEBUG` 활성 시
 *    서버 로그로만 확인 가능합니다.
 *
 * REST : POST /wp-json/tenlune/v1/ai-quote-explain
 *   body { service, features[], consultFeatures[], bundle|null, low, high, days, budget }
 *   →   200 { explanation: string|null }
 *
 *   입력 방어:
 *    - 동일 출처(Origin/Referer) 확인 — 아니면 403.
 *    - IP 레이트리밋 — 식별자는 REMOTE_ADDR (XFF 는 신뢰 프록시 확인 시에만, 필터로 opt-in).
 *    - 화이트리스트 — service/features/consultFeatures/budget/bundle 은 quote-tool-v2.js
 *      의 PRICING 라벨 집합에 있는 값만 통과. 임의 프롬프트 문자열 차단.
 *
 * API 키 : 선택된 provider 의 키 상수 우선 → 같은 provider 의 키 옵션 → 필터
 *          `tenlune/ai_quote_api_key`.  groq → TENLUNE_GROQ_API_KEY,
 *          openrouter → TENLUNE_OPENROUTER_API_KEY.  선택 안 된 provider 의 키는 읽지
 *          않습니다. 서버에서 Authorization 헤더로만 쓰이고 프런트엔드로는 절대 나가지
 *          않습니다(fallback 모델 조회의 `/models` 호출도 같은 키만 사용).
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 문자열 길이 자르기 (mbstring 있으면 그걸로).
 *
 * @param string $text 원본.
 * @param int    $len  최대 길이.
 * @return string
 */
function tenlune_ai_quote_clip( $text, $len ) {
	$text = (string) $text;
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $len );
	}
	return substr( $text, 0, $len );
}

/**
 * REST 라우트 등록.
 */
function tenlune_ai_quote_register_route() {
	register_rest_route(
		'tenlune/v1',
		'/ai-quote-explain',
		array(
			'methods'             => 'POST',
			'callback'            => 'tenlune_ai_quote_explain',
			'permission_callback' => 'tenlune_ai_quote_permission',
		)
	);
}
add_action( 'rest_api_init', 'tenlune_ai_quote_register_route' );

/**
 * 동일 출처에서 온 요청인지 확인.
 *
 * 견적 도구는 공개 페이지(/services/)에서 익명으로 호출하므로 로그인·nonce 를 요구하지
 * 않습니다. 대신 Origin(있으면 우선) 또는 Referer 의 호스트가 이 사이트와 같을 때만
 * 허용해 다른 사이트에서 브라우저로 이 엔드포인트를 때리는 것을 막습니다. 두 헤더가
 * 모두 없으면(curl 등 서버 대 서버) 통과시키되 레이트리밋의 보호를 받습니다.
 *
 * @return true|WP_Error
 */
function tenlune_ai_quote_permission() {
	$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

	foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $server_key ) {
		if ( empty( $_SERVER[ $server_key ] ) ) {
			continue;
		}
		$value = wp_unslash( $_SERVER[ $server_key ] );
		$host  = strtolower( (string) wp_parse_url( $value, PHP_URL_HOST ) );

		if ( '' !== $host && $host === $site_host ) {
			return true;
		}

		// 헤더는 있는데 호스트가 다르면 교차 출처 → 거부.
		return new WP_Error(
			'tenlune_ai_quote_forbidden',
			__( 'Cross-origin request rejected.', 'tenlune-content' ),
			array( 'status' => 403 )
		);
	}

	// Origin/Referer 둘 다 없음 → 통과(레이트리밋 적용).
	return true;
}

/**
 * REST 콜백. 실패해도 항상 200 { explanation: string|null }.
 *
 * @param WP_REST_Request $request 요청.
 * @return WP_REST_Response
 */
function tenlune_ai_quote_explain( WP_REST_Request $request ) {
	$null = new WP_REST_Response( array( 'explanation' => null ), 200 );

	// 1) 레이트리밋 — IP 당 시간당 N 회.
	if ( ! tenlune_ai_quote_rate_ok() ) {
		return $null;
	}

	// 2) 키 없으면 외부 호출 자체를 하지 않음.
	$api_key = tenlune_ai_quote_api_key();
	if ( '' === $api_key ) {
		return $null;
	}

	// 3) 입력 검증 — 규칙 기반 계산기가 보낸 형태만 받음.
	$payload = tenlune_ai_quote_sanitize( $request->get_json_params() );
	if ( null === $payload ) {
		return $null;
	}

	// 4) provider 무관 프롬프트.
	$messages = tenlune_ai_quote_build_messages( $payload );

	// 5) provider 호출(현재 Groq, 기본 모델 qwen/qwen3.8-27b). 모델 unavailable 확인 시
	// 동일 Qwen 계열 후속 모델로 1회 재시도(tenlune_ai_quote_chat() 내부). 실패 시 null.
	$result = tenlune_ai_quote_chat( $messages['system'], $messages['user'], $api_key );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $result['used_fallback_model'] ) ) {
		// 고객 화면에는 노출하지 않음 — 서버 로그로만 fallback 발생 여부/모델 확인.
		error_log( '[tenlune-ai-quote] fallback model used: ' . $result['used_fallback_model'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	if ( ! empty( $result['error'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[tenlune-ai-quote] ' . $result['error'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return $null;
	}

	$text = isset( $result['text'] ) ? trim( (string) $result['text'] ) : '';

	return new WP_REST_Response(
		array( 'explanation' => ( '' !== $text ) ? $text : null ),
		200
	);
}

/**
 * 레이트리밋용 클라이언트 식별자.
 *
 * 기본은 `REMOTE_ADDR` 만 씁니다. Cafe24 앞단(openresty) 이 신뢰 가능한 프록시라는
 * 확증이 없는 상태에서 `X-Forwarded-For` 를 믿으면, 클라이언트가 이 헤더를 임의로
 * 붙여 IP 별 버킷을 무한히 바꿔 레이트리밋을 우회할 수 있습니다.
 *
 * 신뢰 프록시가 확인되면 필터 `tenlune/ai_quote_trust_forwarded_for` 를 true 로 만들면
 * 그때만 XFF 맨 앞 값을 씁니다(그래도 유효한 IP 형식일 때만).
 *
 * @return string 유효 IP, 없으면 '0.0.0.0'.
 */
function tenlune_ai_quote_client_ip() {
	$remote = ! empty( $_SERVER['REMOTE_ADDR'] )
		? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] )
		: '';

	$trust_xff = (bool) apply_filters( 'tenlune/ai_quote_trust_forwarded_for', false );

	if ( $trust_xff && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		$first = trim( $parts[0] );
		if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
			return $first;
		}
	}

	return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '0.0.0.0';
}

/**
 * IP 기반 레이트리밋. 기본 시간당 15회. 필터 `tenlune/ai_quote_rate_limit` (0 이하면 무제한).
 *
 * @return bool 허용이면 true.
 */
function tenlune_ai_quote_rate_ok() {
	$limit = (int) apply_filters( 'tenlune/ai_quote_rate_limit', 15 );
	if ( $limit <= 0 ) {
		return true;
	}

	$key   = 'tl_aiq_rl_' . md5( tenlune_ai_quote_client_ip() );
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return false;
	}

	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * API 키 해석 — 현재 선택된 provider 것만.
 *
 * 순서: provider 의 키 상수(TENLUNE_OPENROUTER_API_KEY / TENLUNE_GROQ_API_KEY) →
 * 같은 provider 의 키 옵션 → 필터 `tenlune/ai_quote_api_key`. 선택 안 된 provider 의
 * 키는 아예 읽지 않습니다.
 *
 * @return string 비어 있으면 ''.
 */
function tenlune_ai_quote_api_key() {
	$cfg = tenlune_ai_quote_provider_config();
	$key = '';

	if ( ! empty( $cfg['key_constant'] ) && defined( $cfg['key_constant'] ) ) {
		$val = constant( $cfg['key_constant'] );
		if ( is_string( $val ) ) {
			$key = $val;
		}
	}

	if ( '' === $key && ! empty( $cfg['key_option'] ) ) {
		$opt = get_option( $cfg['key_option'] );
		if ( is_string( $opt ) ) {
			$key = $opt;
		}
	}

	$key = (string) apply_filters( 'tenlune/ai_quote_api_key', $key, $cfg['id'] );
	return trim( $key );
}

/**
 * 허용 값 화이트리스트.
 *
 * ⚠️ 이 목록은 quote-tool-v2.js (WPCode snippet 21) 의 `PRICING` 라벨을 그대로 미러링한
 *    것입니다. `PRICING` 이 유일한 소스이고, 여기는 공개 REST 로 들어오는 자유 텍스트를
 *    "규칙 계산기가 실제로 낼 수 있는 값" 으로만 좁히는 거울입니다. 계산 로직은 복제하지
 *    않습니다(숫자는 별도로 범위 검증). `PRICING` 라벨을 바꾸면 이 목록이나 필터
 *    `tenlune/ai_quote_whitelist` 도 함께 갱신하세요. (불일치 시 엔드포인트는 조용히
 *    {explanation:null} 을 반환할 뿐, 규칙 기반 견적은 계속 정상 동작합니다.)
 *
 * @return array{services:string[],features:string[],budgets:string[],bundles:string[]}
 */
function tenlune_ai_quote_whitelist() {
	$wl = array(
		'services' => array(
			'한 페이지로 서비스·상품을 소개하고 싶어요',
			'회사·가게·브랜드 홈페이지가 필요해요',
			'예약·신청·회원 기능 등이 있는 서비스를 만들고 싶어요',
			'이미 있는 사이트를 고치고 싶어요',
			'반복 업무를 자동화하고 싶어요',
		),
		'features' => array(
			'예약이나 신청을 받고 싶어요',
			'회원가입하고 로그인해서 쓰는 서비스예요',
			'신청 내역이나 등록된 정보를 직접 확인·관리하고 싶어요',
			'외국어로도 보여주고 싶어요',
			'현재 사용 중인 다른 서비스와 연결하고 싶어요',
			'검색엔진 초기 등록 지원',
		),
		'budgets'  => array(
			'아직 미정',
			'50만원 이하',
			'50만원 ~ 100만원',
			'100만원 이상',
		),
		'bundles'  => array(
			'예약·신청 + 회원 로그인 + 운영자 관리 화면',
		),
	);

	$wl = apply_filters( 'tenlune/ai_quote_whitelist', $wl );

	// 필터가 형태를 깨도 엔드포인트가 죽지 않도록 최소 보정.
	foreach ( array( 'services', 'features', 'budgets', 'bundles' ) as $k ) {
		if ( empty( $wl[ $k ] ) || ! is_array( $wl[ $k ] ) ) {
			$wl[ $k ] = array();
		}
	}
	return $wl;
}

/**
 * 입력 검증·정리.
 *
 * 두 단계:
 *  1) 형식 — 타입, 길이, 숫자 범위(low ≤ high 등).
 *  2) 화이트리스트 — service / features / consultFeatures / budget / bundle 값이
 *     `PRICING` 라벨 집합 안에 있는지. 하나라도 벗어나면 전체를 거부(null).
 *     → 공개 REST 로 임의 프롬프트 문자열을 넣을 수 없습니다.
 *
 * @param mixed $body JSON 파라미터.
 * @return array|null  형식·화이트리스트 중 하나라도 안 맞으면 null.
 */
function tenlune_ai_quote_sanitize( $body ) {
	if ( ! is_array( $body ) || empty( $body['service'] ) ) {
		return null;
	}

	$wl = tenlune_ai_quote_whitelist();

	$one = static function ( $v ) {
		return tenlune_ai_quote_clip( sanitize_text_field( (string) $v ), 120 );
	};
	$many = static function ( $v ) use ( $one ) {
		if ( ! is_array( $v ) ) {
			return array();
		}
		return array_values( array_filter( array_map( $one, array_slice( $v, 0, 10 ) ) ) );
	};
	$all_in = static function ( array $values, array $allowed ) {
		foreach ( $values as $v ) {
			if ( ! in_array( $v, $allowed, true ) ) {
				return false;
			}
		}
		return true;
	};

	// 1) 숫자 범위.
	$low  = isset( $body['low'] ) ? (int) $body['low'] : -1;
	$high = isset( $body['high'] ) ? (int) $body['high'] : -1;
	$days = isset( $body['days'] ) ? (int) $body['days'] : -1;

	if ( $low < 0 || $high < 0 || $low > $high || $high > 100000000 ) {
		return null;
	}
	if ( $days < 1 || $days > 400 ) {
		return null;
	}

	// 2) 자유 텍스트 필드 → 화이트리스트.
	$service = $one( $body['service'] );
	if ( ! in_array( $service, $wl['services'], true ) ) {
		return null;
	}

	$features = isset( $body['features'] ) ? $many( $body['features'] ) : array();
	$consult  = isset( $body['consultFeatures'] ) ? $many( $body['consultFeatures'] ) : array();
	if ( ! $all_in( $features, $wl['features'] ) || ! $all_in( $consult, $wl['features'] ) ) {
		return null;
	}

	$budget = empty( $body['budget'] )
		? '아직 미정'
		: tenlune_ai_quote_clip( sanitize_text_field( (string) $body['budget'] ), 60 );
	if ( ! in_array( $budget, $wl['budgets'], true ) ) {
		return null;
	}

	$bundle = empty( $body['bundle'] ) ? null : $one( $body['bundle'] );
	if ( null !== $bundle && ! in_array( $bundle, $wl['bundles'], true ) ) {
		return null;
	}

	return array(
		'service'         => $service,
		'features'        => $features,
		'consultFeatures' => $consult,
		'bundle'          => $bundle,
		'low'             => $low,
		'high'            => $high,
		'days'            => $days,
		'budget'          => $budget,
	);
}

/**
 * provider 무관 프롬프트 구성 (system / user).
 *
 * 숫자(가격·기간)를 바꾸거나 새로 만들지 말라는 지시가 프롬프트의 핵심입니다.
 *
 * @param array $p tenlune_ai_quote_sanitize() 결과.
 * @return array{system:string,user:string}
 */
function tenlune_ai_quote_build_messages( array $p ) {
	// 계산기는 이제 단일 예상가격을 냅니다(low === high). 범위 표현을 만들지 않도록
	// 사실도 단일 금액으로 전달하고, low 와 high 가 다를 때만(장래 대비) 범위로 씁니다.
	$price_fact = ( (int) $p['low'] === (int) $p['high'] )
		? '계산기가 확정한 단일 예상 견적: ' . number_format( $p['low'] ) . '원'
		: '계산기가 확정한 예상 견적 범위: ' . number_format( $p['low'] ) . '원 ~ ' . number_format( $p['high'] ) . '원';

	$facts = array(
		'선택한 서비스: ' . $p['service'],
		'선택한 기능: ' . ( $p['features'] ? implode( ', ', $p['features'] ) : '없음' ),
		'상담 후 산정 항목: ' . ( $p['consultFeatures'] ? implode( ', ', $p['consultFeatures'] ) : '없음' ),
		'공유 최적화 적용: ' . ( $p['bundle'] ? $p['bundle'] : '해당 없음' ),
		$price_fact,
		'확정된 예상 기간: ' . $p['days'] . '영업일',
		'고객이 입력한 예산: ' . $p['budget'],
	);

	$system = '당신은 1인 웹 개발 스튜디오 Tenlune 의 견적 설명 도우미입니다. '
		. '아래 사실은 규칙 기반 계산기가 이미 확정한 값입니다. 이 숫자(가격·기간)를 절대 '
		. '바꾸거나 새로 계산하거나 추측하지 마세요 — 있는 그대로 자연스러운 한국어 설명문으로 '
		. '풀어 쓰는 것이 유일한 역할입니다. 확정 견적이 아니라 예상 금액임을 전제로 씁니다.';

	$user = implode( "\n", $facts ) . "\n\n"
		. '위 내용을 바탕으로 2~4문장으로만 작성하세요: '
		. '(1) 고객의 요청을 쉬운 말로 요약, '
		. '(2) 왜 이 구성이 적합한지, '
		. '(3) 공유 최적화가 적용됐다면 무엇이 공유되어 중복 구현이 줄었는지, '
		. '(4) 예산과 예상 견적이 안 맞으면 현실적인 대안 제안. '
		. '가격·기간 숫자는 위에 주어진 값만 그대로 인용하고 새 숫자를 만들지 마세요. '
		. '예상 견적은 계산기가 확정한 하나의 금액입니다. "약 얼마~얼마", "얼마에서 얼마 사이" '
		. '같은 범위·구간 표현을 만들지 말고 그 단일 금액만 그대로 쓰세요. '
		. '"검색엔진 초기 등록 지원"이 선택 기능에 있으면 "Google·Naver 검색엔진 초기 등록을 '
		. '지원한다"는 사실만 짧게 언급하고, 검색 순위·상위 노출·색인(크롤링) 완료 시점을 '
		. '보장하거나 약속하는 표현은 절대 쓰지 마세요. '
		. '불릿·제목 없이 이어지는 문단으로 씁니다.';

	return array(
		'system' => (string) apply_filters( 'tenlune/ai_quote_system_prompt', $system, $p ),
		'user'   => (string) apply_filters( 'tenlune/ai_quote_user_prompt', $user, $p ),
	);
}

/**
 * Provider 레지스트리.
 *
 * provider 별로 base_url / model / 추가 headers / 요청 body 추가 필드(extra_body) /
 * 키 소스(상수·옵션 이름)를 한 곳에서 관리합니다. 둘 다 OpenAI 호환 /chat/completions
 * 라 통신 코드는 공용입니다. 필터 `tenlune/ai_quote_providers` 로 항목을 추가·수정.
 *
 *   ⚠️ 모델 ID 는 라이브 확인값입니다:
 *     - groq       'qwen/qwen3.8-27b'  → Groq 모델 목록에 존재 (2026-09 확인, preview,
 *                                        tool calling 지원, 131K ctx). 이 provider 가
 *                                        기본값이며, 이 모델이 unavailable 로 확인되면
 *                                        위 "Qwen 모델 fallback" 이 동작합니다.
 *     - openrouter 'qwen/qwen3.6-27b'  → OpenRouter /models 에 존재("Qwen: Qwen3.6 27B",
 *                                        262K ctx, 유료 소액, 2026-08-29 확인). :free Qwen
 *                                        없음. 현재는 대체 provider(기본 아님) — 다시
 *                                        기본으로 쓰려면 모델을 다시 확인하세요.
 *
 *   OpenRouter `extra_body.reasoning.enabled = false` (2026-08-29 추가):
 *     qwen3.6-27b 는 OpenRouter 메타에서 reasoning `default_enabled: true` / `mandatory:
 *     false`. reasoning 을 끄지 않으면 짧은 max_tokens 를 <think> 로 다 써버려 최종 답변
 *     (content)이 빈 문자열로 옴(라이브 로그: finish_reason=length, output 정확히 320).
 *     OpenRouter 공식 unified 파라미터 `reasoning: { enabled: false }` 로 비활성.
 *     provider 별 설정이라 Groq body 에는 아무 것도 추가되지 않습니다.
 *
 * @return array
 */
function tenlune_ai_quote_providers() {
	$providers = array(
		'openrouter' => array(
			'base_url'     => 'https://openrouter.ai/api/v1',
			'model'        => 'qwen/qwen3.6-27b',
			'key_constant' => 'TENLUNE_OPENROUTER_API_KEY',
			'key_option'   => 'tenlune_openrouter_api_key',
			'headers'      => array(
				'HTTP-Referer' => 'https://tenlune.com',
				'X-Title'      => 'Tenlune',
			),
			'extra_body'   => array(
				'reasoning' => array( 'enabled' => false ),
			),
		),
		'groq'       => array(
			'base_url'     => 'https://api.groq.com/openai/v1',
			'model'        => 'qwen/qwen3.8-27b',
			'key_constant' => 'TENLUNE_GROQ_API_KEY',
			'key_option'   => 'tenlune_groq_api_key',
			'headers'      => array(),
			'extra_body'   => array(),
		),
	);

	$providers = apply_filters( 'tenlune/ai_quote_providers', $providers );
	return is_array( $providers ) ? $providers : array();
}

/**
 * 현재 활성 provider 식별값.
 *
 * 기본 'groq'. 전환은 상수 TENLUNE_AI_QUOTE_PROVIDER 또는 필터
 * `tenlune/ai_quote_provider`. 레지스트리에 없는 값이면 'groq' 로 되돌립니다.
 *
 * @return string
 */
function tenlune_ai_quote_active_provider() {
	$id = 'groq';

	if ( defined( 'TENLUNE_AI_QUOTE_PROVIDER' ) && is_string( TENLUNE_AI_QUOTE_PROVIDER ) && '' !== TENLUNE_AI_QUOTE_PROVIDER ) {
		$id = TENLUNE_AI_QUOTE_PROVIDER;
	}

	$id        = (string) apply_filters( 'tenlune/ai_quote_provider', $id );
	$providers = tenlune_ai_quote_providers();

	return isset( $providers[ $id ] ) ? $id : 'groq';
}

/**
 * 활성 provider 의 완성된 설정 (공통 파라미터 병합 + 모델 override).
 *
 * 필터 `tenlune/ai_quote_provider_config` 로 최종값을 다시 조정할 수 있습니다.
 *
 * @return array id·base_url·model·headers·extra_body·timeout·max_tokens·temperature·key_constant·key_option
 */
function tenlune_ai_quote_provider_config() {
	$id        = tenlune_ai_quote_active_provider();
	$providers = tenlune_ai_quote_providers();
	$cfg       = isset( $providers[ $id ] ) ? $providers[ $id ] : array();

	$cfg = wp_parse_args(
		$cfg,
		array(
			'base_url'     => '',
			'model'        => '',
			'headers'      => array(),
			'extra_body'   => array(),
			'key_constant' => '',
			'key_option'   => '',
			'timeout'      => 12,
			// reasoning 을 끄면 출력이 곧 최종 답변이라 320 이면 충분하지만,
			// 4문장짜리 답이 마지막에 잘리지 않도록 최소한만 여유를 둡니다.
			'max_tokens'   => 400,
			'temperature'  => 0.3,
		)
	);
	$cfg['id'] = $id;

	if ( ! is_array( $cfg['headers'] ) ) {
		$cfg['headers'] = array();
	}
	if ( ! is_array( $cfg['extra_body'] ) ) {
		$cfg['extra_body'] = array();
	}

	// provider 무관 모델 강제 override (무료 모델 지정 등). 사용자가 모델을 직접
	// 고정한 것이므로, 이 경우엔 Qwen 모델 fallback 을 하지 않습니다(그 의도를 존중).
	$cfg['model_overridden'] = false;
	if ( defined( 'TENLUNE_AI_QUOTE_MODEL' ) && is_string( TENLUNE_AI_QUOTE_MODEL ) && '' !== TENLUNE_AI_QUOTE_MODEL ) {
		$cfg['model']            = TENLUNE_AI_QUOTE_MODEL;
		$cfg['model_overridden'] = true;
	}

	$cfg = apply_filters( 'tenlune/ai_quote_provider_config', $cfg );
	return is_array( $cfg ) ? $cfg : array();
}

/**
 * LLM 통신 1회 시도 — OpenAI 호환 chat/completions.
 *
 * openrouter · groq 둘 다 이 스키마이므로 provider 별 분기가 없습니다. base_url ·
 * 추가 headers · extra_body · 키는 전부 tenlune_ai_quote_provider_config() 에서 옵니다.
 * 모델만 인자로 받는 이유는 tenlune_ai_quote_chat() 이 기본 모델 → fallback 모델
 * 순으로 이 함수를 최대 2회(재시도 1회) 호출하기 때문입니다. OpenAI 비호환 provider 로
 * 갈 때만 이 함수를 재작성하면 됩니다.
 *
 * @param array  $cfg     tenlune_ai_quote_provider_config() 결과.
 * @param string $model   이번 시도에 실제로 보낼 모델 id.
 * @param string $system  system 메시지.
 * @param string $user    user 메시지.
 * @param string $api_key API 키.
 * @return array{text:?string,error:?string,status:int,code:?string,message:?string}
 */
function tenlune_ai_quote_chat_attempt( array $cfg, $model, $system, $user, $api_key ) {
	$url = rtrim( (string) $cfg['base_url'], '/' ) . '/chat/completions';

	$headers = array_merge(
		array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		$cfg['headers']
	);

	// 표준 필드가 항상 이기도록 extra_body 를 먼저 깔고 병합합니다.
	$body = array_merge(
		$cfg['extra_body'],
		array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $system,
				),
				array(
					'role'    => 'user',
					'content' => $user,
				),
			),
			'max_tokens'  => (int) $cfg['max_tokens'],
			'temperature' => (float) $cfg['temperature'],
			'stream'      => false,
		)
	);

	$response = wp_remote_post(
		$url,
		array(
			'timeout' => (float) $cfg['timeout'],
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'text'    => null,
			'error'   => 'transport: ' . $response->get_error_message(),
			'status'  => 0,
			'code'    => null,
			'message' => null,
		);
	}

	$http_status = (int) wp_remote_retrieve_response_code( $response );
	$raw         = (string) wp_remote_retrieve_body( $response );

	if ( 200 !== $http_status ) {
		// OpenAI 호환 오류 바디: { "error": { "code", "message" } } — model unavailable
		// 판별(tenlune_ai_quote_is_model_unavailable_error())에 필요한 만큼만 꺼냅니다.
		$decoded    = json_decode( $raw, true );
		$error_code = ( is_array( $decoded ) && isset( $decoded['error']['code'] ) ) ? (string) $decoded['error']['code'] : null;
		$error_msg  = ( is_array( $decoded ) && isset( $decoded['error']['message'] ) ) ? (string) $decoded['error']['message'] : null;

		return array(
			'text'    => null,
			'error'   => 'http ' . $http_status . ': ' . tenlune_ai_quote_clip( $raw, 200 ),
			'status'  => $http_status,
			'code'    => $error_code,
			'message' => $error_msg,
		);
	}

	$data = json_decode( $raw, true );

	// OpenAI 호환 스키마: choices[0].message.content
	$text = '';
	if ( is_array( $data ) && isset( $data['choices'][0]['message']['content'] ) ) {
		$text = (string) $data['choices'][0]['message']['content'];
	}

	// 방어: reasoning 을 껐는데도 <think> 를 content 에 섞어 보내는 provider 대비.
	if ( false !== stripos( $text, '<think>' ) ) {
		$text = (string) preg_replace( '#<think>.*?</think>#is', '', $text );
		// 닫는 태그 없이 잘린 경우: <think> 이후 전부 버림(→ 대개 빈 문자열 → 아래에서 null).
		$text = (string) preg_replace( '#<think>.*$#is', '', $text );
	}

	if ( '' === trim( (string) $text ) ) {
		return array(
			'text'    => null,
			'error'   => 'empty completion',
			'status'  => 200,
			'code'    => null,
			'message' => 'empty completion',
		);
	}

	return array(
		'text'    => $text,
		'error'   => null,
		'status'  => 200,
		'code'    => null,
		'message' => null,
	);
}

/**
 * "qwen/qwen<version>-<size>b(-suffix)?" 형태만 이해합니다(예: "qwen/qwen3.8-27b").
 * 그 외 이름 규칙은 안전하게 포기(null)합니다 — 아무 모델이나 후속으로 골라 답변
 * 품질을 예측 불가능하게 만들지 않기 위함입니다.
 *
 * @param string $id 모델 id.
 * @return array{raw:string,version:float,sizeB:int}|null
 */
function tenlune_ai_quote_parse_qwen_model_id( $id ) {
	$id = trim( (string) $id );
	if ( ! preg_match( '#^qwen/qwen(\d+(?:\.\d+)?)[a-z]*-(\d+)b(?:[-_][a-z0-9]+)*$#i', $id, $m ) ) {
		return null;
	}
	return array(
		'raw'     => $id,
		'version' => (float) $m[1],
		'sizeB'   => (int) $m[2],
	);
}

/**
 * 이 용도(Chat Completions, 견적 설명 문단)와 명백히 맞지 않는 특수 목적 모델 토큰.
 *
 * @return string[]
 */
function tenlune_ai_quote_blocked_model_tokens() {
	return array(
		'vl', 'vision', 'embed', 'embedding', 'rerank', 'reranker', 'guard', 'safety',
		'audio', 'whisper', 'speech', 'tts', 'stt', 'moderation', 'omni', 'coder', 'math',
	);
}

/**
 * "qwen/" 네임스페이스이고 embedding/vision/audio 등 특수 목적 모델이 아닌지.
 *
 * @param string $id 모델 id.
 * @return bool
 */
function tenlune_ai_quote_is_qwen_chat_candidate( $id ) {
	$id = strtolower( (string) $id );
	if ( 0 !== strpos( $id, 'qwen/' ) ) {
		return false;
	}
	$tokens  = (array) preg_split( '/[^a-z0-9]+/', $id );
	$blocked = tenlune_ai_quote_blocked_model_tokens();
	foreach ( $tokens as $t ) {
		if ( in_array( $t, $blocked, true ) ) {
			return false;
		}
	}
	return true;
}

/**
 * 현재(=unavailable 로 확인된) 모델과 같은 규모(size)의 Qwen 계열 중, 과거 버전으로
 * downgrade 하지 않는 가장 가까운 후속 모델을 고릅니다. 확신할 수 없으면 null 을
 * 반환합니다(안전 실패 우선).
 *
 * @param array  $models           provider `/models` 응답의 `data` 배열. 각 항목은
 *                                  `id`(필수)·`active`(선택, false 면 제외)를 가진 배열.
 * @param string $current_model_id 지금 unavailable 로 확인된 모델 id.
 * @return string|null
 */
function tenlune_ai_quote_pick_fallback_model( array $models, $current_model_id ) {
	$current = tenlune_ai_quote_parse_qwen_model_id( $current_model_id );
	if ( null === $current ) {
		return null;
	}

	$candidates = array();
	foreach ( $models as $m ) {
		if ( ! is_array( $m ) || empty( $m['id'] ) ) {
			continue;
		}
		if ( isset( $m['active'] ) && false === $m['active'] ) {
			continue;
		}

		$id = (string) $m['id'];
		if ( $id === $current_model_id || ! tenlune_ai_quote_is_qwen_chat_candidate( $id ) ) {
			continue;
		}

		$parsed = tenlune_ai_quote_parse_qwen_model_id( $id );
		if ( null === $parsed || $parsed['sizeB'] !== $current['sizeB'] || $parsed['version'] < $current['version'] ) {
			continue;
		}

		$candidates[] = $parsed;
	}

	if ( empty( $candidates ) ) {
		return null;
	}

	usort(
		$candidates,
		static function ( $a, $b ) {
			if ( $a['version'] === $b['version'] ) {
				return strcmp( $a['raw'], $b['raw'] );
			}
			return ( $a['version'] < $b['version'] ) ? -1 : 1;
		}
	);

	return $candidates[0]['raw'];
}

/**
 * 활성 provider 의 `/models` 목록을 1회 조회합니다. 실패하면 빈 배열(=fallback 후보 없음
 * → 규칙 기반 견적만 유지, 아래 tenlune_ai_quote_chat() 참고).
 *
 * @param array  $cfg     provider 설정(base_url 포함).
 * @param string $api_key API 키(같은 provider 의 키만 씁니다).
 * @return array
 */
function tenlune_ai_quote_fetch_model_ids( array $cfg, $api_key ) {
	$url = rtrim( (string) $cfg['base_url'], '/' ) . '/models';

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 8,
			'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	return ( is_array( $data ) && ! empty( $data['data'] ) && is_array( $data['data'] ) ) ? $data['data'] : array();
}

/**
 * fallback 후보 조회 — 5분 transient 캐시를 먼저 보고, 없으면 `/models` 를 1회 조회해
 * 고른 뒤 캐싱합니다. 못 찾았어도(null) 캐싱합니다 — 기본 모델이 죽어 있는 동안 매
 * 요청마다 `/models` 를 다시 부르지 않기 위해서입니다.
 *
 * @param array  $cfg              provider 설정.
 * @param string $api_key          API 키.
 * @param string $current_model_id 지금 unavailable 인 모델.
 * @return string|null
 */
function tenlune_ai_quote_resolve_fallback_model( array $cfg, $api_key, $current_model_id ) {
	$cache_key = 'tl_aiq_fb_' . md5( $cfg['id'] . '|' . $current_model_id );
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return ( '' === $cached ) ? null : $cached;
	}

	$models = tenlune_ai_quote_fetch_model_ids( $cfg, $api_key );
	$picked = tenlune_ai_quote_pick_fallback_model( $models, $current_model_id );

	set_transient( $cache_key, ( null === $picked ) ? '' : $picked, 5 * MINUTE_IN_SECONDS );
	return $picked;
}

/**
 * "이 모델 자체를 더 이상 쓸 수 없음" 만 fallback 대상으로 좁힙니다. 401/403(인증)·
 * 429(rate limit)·5xx(서버)·network/timeout/malformed request 는 이 함수에 오면 안
 * 됩니다(호출하는 쪽이 먼저 상태코드로 걸러냅니다) — 판별 기준은 404 +
 * `model_not_found` 류(does not exist / not found / deprecated / removed / unavailable).
 *
 * @param int         $status  HTTP 상태 코드.
 * @param string|null $code    OpenAI 호환 `error.code`.
 * @param string|null $message OpenAI 호환 `error.message`.
 * @return bool
 */
function tenlune_ai_quote_is_model_unavailable_error( $status, $code, $message ) {
	if ( 404 !== (int) $status ) {
		return false;
	}
	if ( 'model_not_found' === $code ) {
		return true;
	}
	return (bool) preg_match(
		'#does not exist|model[^.]*not found|no longer (available|supported)|has been (decommissioned|deprecated|removed)|model unavailable|access unavailable#i',
		(string) $message
	);
}

/**
 * LLM 통신 오케스트레이션 — 기본 모델 1회 → (모델 unavailable 로 명백히 확인될 때만)
 * 동일 Qwen 계열 후속 모델로 1회 재시도.
 *
 * openrouter · groq 둘 다 OpenAI 호환 스키마라 provider 별 분기가 없습니다. 실제
 * HTTP 통신·파싱은 tenlune_ai_quote_chat_attempt() 가 맡고, 이 함수는 "언제 재시도할지"
 * 흐름만 담당합니다(무한 retry 없음 — 최대 2회: 기본 1 + fallback 1).
 *
 * @param string $system  system 메시지.
 * @param string $user    user 메시지.
 * @param string $api_key API 키.
 * @return array{text:?string,error:?string,used_fallback_model:?string}
 */
function tenlune_ai_quote_chat( $system, $user, $api_key ) {
	$cfg = tenlune_ai_quote_provider_config();

	$first = tenlune_ai_quote_chat_attempt( $cfg, $cfg['model'], $system, $user, $api_key );
	if ( null !== $first['text'] ) {
		return array(
			'text'                => $first['text'],
			'error'               => null,
			'used_fallback_model' => null,
		);
	}

	$allow_fallback = empty( $cfg['model_overridden'] );

	if ( $allow_fallback && tenlune_ai_quote_is_model_unavailable_error( $first['status'], $first['code'], $first['message'] ) ) {
		$fallback_model = tenlune_ai_quote_resolve_fallback_model( $cfg, $api_key, $cfg['model'] );

		if ( null !== $fallback_model && $fallback_model !== $cfg['model'] ) {
			$retry = tenlune_ai_quote_chat_attempt( $cfg, $fallback_model, $system, $user, $api_key );

			if ( null !== $retry['text'] ) {
				return array(
					'text'                => $retry['text'],
					'error'               => null,
					'used_fallback_model' => $fallback_model,
				);
			}

			return array(
				'text'                => null,
				'error'               => 'fallback(' . $fallback_model . ') ' . $retry['error'],
				'used_fallback_model' => $fallback_model,
			);
		}
	}

	return array(
		'text'                => null,
		'error'               => $first['error'],
		'used_fallback_model' => null,
	);
}

/**
 * 견적 도구가 실제로 있는 페이지인지.
 *
 * quote-tool-v2.js 는 window.tlAiQuoteEndpoint 가 있을 때만 LLM 을 호출합니다. 도구가
 * 없는 페이지에까지 이 변수를 뿌리지 않도록, 본문에 마운트 div(id="tl-quote-tool")가
 * 있을 때만 출력합니다. 필터 `tenlune/ai_quote_enabled` 로 강제 on/off 가능.
 *
 * @return bool
 */
function tenlune_ai_quote_tool_present() {
	$post    = get_post();
	$present = ( $post instanceof WP_Post )
		&& false !== strpos( (string) $post->post_content, 'id="tl-quote-tool"' );

	return (bool) apply_filters( 'tenlune/ai_quote_enabled', $present, $post );
}

/**
 * 견적 도구 페이지에서만 window.tlAiQuoteEndpoint 를 세팅.
 *
 * 값이 없으면(도구 없는 페이지, 혹은 이 파일 배포 전) 프런트엔드는 지금처럼 규칙 기반
 * 결과만 표시합니다 — 이 한 줄이 LLM 보강의 on 스위치입니다.
 */
function tenlune_ai_quote_print_endpoint_var() {
	if ( is_admin() || is_feed() || ! tenlune_ai_quote_tool_present() ) {
		return;
	}

	printf(
		"<script>window.tlAiQuoteEndpoint=%s;</script>\n",
		wp_json_encode( esc_url_raw( rest_url( 'tenlune/v1/ai-quote-explain' ) ) )
	);
}
add_action( 'wp_head', 'tenlune_ai_quote_print_endpoint_var', 20 );
