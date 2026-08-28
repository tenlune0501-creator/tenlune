// Tenlune AI 견적 LLM 보강 엔드포인트 (v2 — pricing v1 payload 형태에 맞춤)
// wp-admin > WPCode > Snippets > Add New 에서 코드 타입 "PHP Snippet"으로 붙여넣으세요.
// (여는 <?php 태그는 넣지 마세요 — WPCode가 자동으로 감쌉니다.)
//
// 이 엔드포인트는 가격/기간 숫자를 절대 만들어내지 않습니다. 규칙 기반 계산기(JS)가
// 이미 계산한 숫자를 그대로 받아서, 그 숫자를 "왜 이 구성인지 / 뭐가 공유돼서 줄었는지"
// 설명하는 문장만 LLM에게 만들게 합니다. API 키가 없거나 호출이 실패해도 이 엔드포인트는
// {"explanation": null}을 반환할 뿐이고, 규칙 기반 계산기 자체는 이 엔드포인트 없이도
// 완전히 동작합니다.

add_action('rest_api_init', function () {
    register_rest_route('tenlune/v1', '/ai-quote-explain', array(
        'methods'             => 'POST',
        'callback'            => 'tenlune_ai_quote_explain',
        'permission_callback' => '__return_true',
    ));
});

function tenlune_ai_quote_explain(WP_REST_Request $request) {
    $api_key = get_option('tenlune_ai_quote_api_key');
    if (empty($api_key)) {
        return new WP_REST_Response(array('explanation' => null), 200);
    }

    $body = $request->get_json_params();
    if (empty($body) || empty($body['service'])) {
        return new WP_REST_Response(array('explanation' => null), 200);
    }

    $service          = sanitize_text_field($body['service']);
    $features         = isset($body['features']) && is_array($body['features'])
        ? array_map('sanitize_text_field', $body['features']) : array();
    $consult_features = isset($body['consultFeatures']) && is_array($body['consultFeatures'])
        ? array_map('sanitize_text_field', $body['consultFeatures']) : array();
    $bundle           = isset($body['bundle']) ? sanitize_text_field($body['bundle']) : null;
    $low              = isset($body['low']) ? intval($body['low']) : null;
    $high             = isset($body['high']) ? intval($body['high']) : null;
    $days             = isset($body['days']) ? intval($body['days']) : null;
    $budget           = isset($body['budget']) ? sanitize_text_field($body['budget']) : null;

    if ($low === null || $high === null) {
        return new WP_REST_Response(array('explanation' => null), 200);
    }

    $facts = array(
        '선택한 서비스: ' . $service,
        '선택한 기능: ' . (count($features) ? implode(', ', $features) : '없음'),
        '상담 후 산정 항목: ' . (count($consult_features) ? implode(', ', $consult_features) : '없음'),
        '공유 최적화 적용: ' . ($bundle ? $bundle : '해당 없음'),
        '확정된 예상 견적 범위: ' . number_format($low) . '원 ~ ' . number_format($high) . '원',
        '확정된 예상 기간: ' . $days . '영업일',
        '고객이 입력한 예산: ' . ($budget ? $budget : '미정'),
    );

    $prompt = "당신은 1인 웹 개발 스튜디오 Tenlune의 견적 설명 도우미입니다.\n"
        . "아래는 규칙 기반 계산기가 이미 확정한 사실입니다. 이 숫자들을 절대 바꾸거나 새로 "
        . "지어내지 마세요 — 있는 그대로 자연스러운 한국어 설명문으로 풀어 쓰는 것이 유일한 역할입니다.\n\n"
        . implode("\n", $facts) . "\n\n"
        . "다음만 2~4문장으로 작성하세요: (1) 고객의 요청을 쉬운 말로 요약, (2) 왜 이 구성이 "
        . "적합한지, (3) 공유 최적화가 적용됐다면 무엇이 공유돼서 비용이 줄었는지, (4) 예산과 "
        . "견적이 안 맞으면 현실적인 대안 제안. 가격/기간 숫자는 위에 주어진 값만 그대로 "
        . "인용하고 새 숫자를 계산하거나 추측하지 마세요.";

    $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
        'timeout' => 12,
        'headers' => array(
            'Content-Type'      => 'application/json',
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
        ),
        'body' => wp_json_encode(array(
            'model'      => 'claude-sonnet-5',
            'max_tokens' => 400,
            'messages'   => array(
                array('role' => 'user', 'content' => $prompt),
            ),
        )),
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_REST_Response(array('explanation' => null), 200);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $text = isset($data['content'][0]['text']) ? trim($data['content'][0]['text']) : null;

    return new WP_REST_Response(array('explanation' => $text), 200);
}
