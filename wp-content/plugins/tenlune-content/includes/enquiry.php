<?php
/**
 * 문의 접수.
 *
 * 설계안 11번의 필드 설계를 그대로 구현합니다. 최대 장벽은 "무엇을 써야 할지
 * 모른다" 이므로 폼 자체가 요구사항 정리를 유도합니다.
 *
 * 접수분은 비공개 CPT `enquiry` 로 저장하고 wp_mail() 로 알립니다.
 * 저장을 함께 하는 이유는 메일이 사라져도 문의는 남아야 하기 때문입니다.
 *
 * 주의 — 공용 호스팅의 기본 PHP mail() 은 스팸으로 분류되거나 아예 전달되지
 * 않는 경우가 흔합니다. 공개 전에 SMTP 플러그인을 붙이고 실제로 자신에게
 * 테스트 문의를 보내 수신을 확인해야 합니다. — 설계안 11번
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 접수 보관용 비공개 CPT.
 */
function tenlune_register_enquiry_post_type() {
	register_post_type(
		'enquiry',
		array(
			'labels'          => array(
				'name'          => __( '문의', 'tenlune-content' ),
				'singular_name' => __( '문의', 'tenlune-content' ),
				'all_items'     => __( '받은 문의', 'tenlune-content' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email',
			'menu_position'   => 6,
			'capability_type' => 'post',
			'capabilities'    => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
			'has_archive'     => false,
			'rewrite'         => false,
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'tenlune_register_enquiry_post_type' );

/**
 * 폼 필드 정의. 화면과 저장이 같은 정의를 보게 해서 어긋나지 않도록 합니다.
 *
 * @return array<string, array{label:string, required:bool}>
 */
function tenlune_enquiry_fields() {
	return array(
		'name'      => array(
			'label'    => '이름',
			'required' => true,
		),
		'email'     => array(
			'label'    => '연락받을 이메일',
			'required' => true,
		),
		'kind'      => array(
			'label'    => '문의 유형',
			'required' => false,
		),
		'want'      => array(
			'label'    => '무엇을 만들고 싶으신가요',
			'required' => true,
		),
		'reference' => array(
			'label'    => '참고할 사이트나 자료',
			'required' => false,
		),
		'timing'    => array(
			'label'    => '희망 일정',
			'required' => false,
		),
		'budget'    => array(
			'label'    => '대략적인 예산 범위',
			'required' => false,
		),
	);
}

/**
 * 문의 완료 페이지 주소.
 *
 * /contact/thanks/ 페이지를 아직 만들지 않았다면 그 주소로 보내는 순간
 * 접수는 됐는데 404 가 뜹니다. 페이지가 실제로 있을 때만 그리로 보냅니다.
 *
 * @return string
 */
function tenlune_thanks_url() {
	$page = get_page_by_path( 'contact/thanks' );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}

	return add_query_arg( 'tl_sent', '1', home_url( '/contact/' ) );
}

/**
 * 문의 접수 처리.
 */
function tenlune_handle_enquiry() {
	$referer  = wp_get_referer();
	$fallback = home_url( '/contact/' );
	$back     = $referer ? $referer : $fallback;

	// 논스 — 만료·위조 요청은 여기서 끝냅니다.
	if ( ! isset( $_POST['tenlune_enquiry_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tenlune_enquiry_nonce'] ) ), 'tenlune_enquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'tl_error', 'expired', $back ) );
		exit;
	}

	// 허니팟 — 사람은 채울 수 없는 칸입니다. 조용히 성공한 척 돌려보냅니다.
	if ( ! empty( $_POST['tl_website'] ) ) {
		wp_safe_redirect( tenlune_thanks_url() );
		exit;
	}

	$values = array();

	foreach ( tenlune_enquiry_fields() as $key => $def ) {
		$raw = isset( $_POST[ 'tl_' . $key ] ) ? wp_unslash( $_POST[ 'tl_' . $key ] ) : '';

		if ( 'email' === $key ) {
			$value = sanitize_email( $raw );
		} elseif ( 'want' === $key ) {
			$value = sanitize_textarea_field( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}

		if ( $def['required'] && '' === trim( (string) $value ) ) {
			wp_safe_redirect( add_query_arg( 'tl_error', 'required', $back ) );
			exit;
		}

		$values[ $key ] = $value;
	}

	if ( ! is_email( $values['email'] ) ) {
		wp_safe_redirect( add_query_arg( 'tl_error', 'email', $back ) );
		exit;
	}

	// 개인정보 수집·이용 동의 — 법적 요구사항입니다. 없으면 접수하지 않습니다.
	if ( empty( $_POST['tl_consent'] ) ) {
		wp_safe_redirect( add_query_arg( 'tl_error', 'consent', $back ) );
		exit;
	}

	$lines = array();

	foreach ( tenlune_enquiry_fields() as $key => $def ) {
		if ( '' === trim( (string) $values[ $key ] ) ) {
			continue;
		}
		$lines[] = $def['label'] . ': ' . $values[ $key ];
	}

	$body = implode( "\n", $lines );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'enquiry',
			'post_status'  => 'private',
			/* translators: %s: 보낸 사람 이름 */
			'post_title'   => sprintf( '%s — %s', $values['name'], wp_trim_words( $values['want'], 12, '…' ) ),
			'post_content' => $body,
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		foreach ( $values as $key => $value ) {
			update_post_meta( $post_id, '_tenlune_enq_' . $key, $value );
		}
	}

	$to      = apply_filters( 'tenlune_enquiry_recipient', get_option( 'admin_email' ) );
	$subject = sprintf( '[Tenlune] 새 문의 — %s', $values['name'] );
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $values['name'] . ' <' . $values['email'] . '>',
	);

	// 실패 사유를 잡아 둡니다. wp_mail() 은 false 만 돌려주고 이유는 알려주지 않습니다.
	$GLOBALS['tenlune_mail_error'] = '';
	add_action( 'wp_mail_failed', 'tenlune_capture_mail_error' );
	$sent = wp_mail( $to, $subject, $body, $headers );
	remove_action( 'wp_mail_failed', 'tenlune_capture_mail_error' );

	if ( ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_tenlune_enq_mail_sent', $sent ? '1' : '0' );

		if ( ! $sent ) {
			update_post_meta( $post_id, '_tenlune_enq_mail_error', (string) $GLOBALS['tenlune_mail_error'] );
			update_option( 'tenlune_mail_failed_at', time(), false );
		}
	}

	// 저장도 실패하고 메일도 실패했다면 문의는 어디에도 남지 않았습니다.
	// 이 경우에만 방문자에게 알립니다 — 완료 화면을 보여주면 거짓말이 됩니다.
	if ( is_wp_error( $post_id ) && ! $sent ) {
		wp_safe_redirect( add_query_arg( 'tl_error', 'failed', $back ) );
		exit;
	}

	// 저장은 됐는데 메일만 실패한 경우에는 방문자에게 접수 완료가 맞습니다.
	// 놓치면 안 되는 쪽은 운영자이고, 그건 관리자 화면에서 알립니다.
	wp_safe_redirect( tenlune_thanks_url() );
	exit;
}

/**
 * wp_mail 실패 사유 보관.
 *
 * @param WP_Error $error 오류.
 */
function tenlune_capture_mail_error( $error ) {
	if ( is_wp_error( $error ) ) {
		$GLOBALS['tenlune_mail_error'] = $error->get_error_message();
	}
}

/**
 * 메일 전송에 실패한 문의가 있으면 관리자 화면에 알립니다.
 *
 * 문의는 저장됐지만 메일이 안 온 상태를 모르고 지나가는 것이
 * 이 기능에서 가장 위험한 실패입니다.
 */
function tenlune_mail_failure_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! get_option( 'tenlune_mail_failed_at' ) ) {
		return;
	}

	$failed = get_posts(
		array(
			'post_type'      => 'enquiry',
			'post_status'    => 'private',
			'posts_per_page' => 5,
			'meta_key'       => '_tenlune_enq_mail_sent',
			'meta_value'     => '0',
			'fields'         => 'ids',
		)
	);

	if ( empty( $failed ) ) {
		delete_option( 'tenlune_mail_failed_at' );
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p><p><a href="%s">%s</a></p></div>',
		esc_html( sprintf( '문의 %d건이 저장됐지만 알림 메일이 전송되지 않았습니다.', count( $failed ) ) ),
		esc_html( 'SMTP 설정을 확인하세요. 내용은 「받은 문의」에 그대로 남아 있습니다.' ),
		esc_url( admin_url( 'edit.php?post_type=enquiry' ) ),
		esc_html( '받은 문의 보기' )
	);
}
add_action( 'admin_notices', 'tenlune_mail_failure_notice' );

/**
 * 문의 목록에 전송 상태 열을 붙입니다.
 *
 * @param array $columns 열 정의.
 * @return array
 */
function tenlune_enquiry_columns( $columns ) {
	$columns['tenlune_mail'] = '메일';
	return $columns;
}
add_filter( 'manage_enquiry_posts_columns', 'tenlune_enquiry_columns' );

/**
 * 전송 상태 열 내용.
 *
 * @param string $column  열 이름.
 * @param int    $post_id 글 ID.
 */
function tenlune_enquiry_column_content( $column, $post_id ) {
	if ( 'tenlune_mail' !== $column ) {
		return;
	}

	$sent = get_post_meta( $post_id, '_tenlune_enq_mail_sent', true );

	if ( '1' === $sent ) {
		echo esc_html( '전송됨' );
		return;
	}

	if ( '0' === $sent ) {
		$reason = (string) get_post_meta( $post_id, '_tenlune_enq_mail_error', true );
		printf(
			'<strong style="color:#b32d2e">%s</strong>%s',
			esc_html( '전송 실패' ),
			$reason ? '<br><span style="color:#666">' . esc_html( $reason ) . '</span>' : ''
		);
		return;
	}

	echo esc_html( '—' );
}
add_action( 'manage_enquiry_posts_custom_column', 'tenlune_enquiry_column_content', 10, 2 );
add_action( 'admin_post_nopriv_tenlune_enquiry', 'tenlune_handle_enquiry' );
add_action( 'admin_post_tenlune_enquiry', 'tenlune_handle_enquiry' );

/**
 * 폼 위에 뜨는 오류 안내.
 *
 * 오류는 사과하지 않고, 무엇이 잘못됐고 어떻게 고치는지만 말합니다.
 *
 * @return string
 */
function tenlune_enquiry_notice() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- 화면 표시용 읽기 전용 파라미터입니다.
	if ( ! empty( $_GET['tl_sent'] ) ) {
		return sprintf(
			'<div class="tl-formnotice" role="status"><span class="l">%s</span><p>%s</p></div>',
			esc_html( '접수됨' ),
			esc_html( '문의가 접수되었습니다. 2영업일 안에 가능 여부와 대략적인 범위를 적어 회신합니다.' )
		);
	}

	$code = isset( $_GET['tl_error'] ) ? sanitize_key( wp_unslash( $_GET['tl_error'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' === $code ) {
		return '';
	}

	$messages = array(
		'required' => '이름, 이메일, 만들고 싶은 것은 채워야 보낼 수 있습니다.',
		'email'    => '이메일 주소 형식이 맞지 않습니다. 회신을 받을 주소를 다시 확인해 주세요.',
		'consent'  => '개인정보 수집·이용에 동의해야 문의를 접수할 수 있습니다.',
		'expired'  => '입력 시간이 만료되어 보내지 못했습니다. 내용을 다시 확인하고 한 번 더 보내주세요.',
		'failed'   => '서버 문제로 접수되지 않았습니다. 잠시 뒤 다시 보내주시거나, 같은 내용을 메일로 보내주세요.',
	);

	$message = isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['expired'];

	return sprintf(
		'<div class="tl-formerror" role="alert"><span class="l">전송되지 않음</span><p>%s</p></div>',
		esc_html( $message )
	);
}

/**
 * 문의 폼 렌더링.
 *
 * 정적 HTML 로 둘 수 없습니다 — 논스는 그릴 때마다 새로 만들어야 합니다.
 *
 * @return string
 */
function tenlune_render_contact_form() {
	// 논스는 요청마다 새로 발급되어야 합니다. 이 페이지가 통째로 캐시되면
	// 오래된 논스가 나가고, 방문자는 이유 없이 "만료" 를 보게 됩니다.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	$out  = tenlune_enquiry_notice();
	$out .= sprintf(
		'<form class="tl-form" method="post" action="%s">',
		esc_url( admin_url( 'admin-post.php' ) )
	);
	$out .= '<input type="hidden" name="action" value="tenlune_enquiry">';
	$out .= wp_nonce_field( 'tenlune_enquiry', 'tenlune_enquiry_nonce', true, false );

	// 허니팟. 화면에서도 보조기기에서도 노출되지 않습니다.
	$out .= '<div class="tl-hp" aria-hidden="true">
		<label for="tl_website">이 칸은 비워 두세요</label>
		<input type="text" id="tl_website" name="tl_website" tabindex="-1" autocomplete="off">
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_name">이름 <span class="req">필수</span></label>
		<input type="text" id="tl_name" name="tl_name" required autocomplete="name">
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_email">연락받을 이메일 <span class="req">필수</span></label>
		<input type="email" id="tl_email" name="tl_email" required autocomplete="email" inputmode="email">
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_kind">문의 유형</label>
		<select id="tl_kind" name="tl_kind">
			<option value="">— 고르지 않아도 됩니다 —</option>
			<option value="새 사이트">새 사이트</option>
			<option value="기존 사이트 개선">기존 사이트 개선</option>
			<option value="웹서비스·도구">웹서비스 · 도구</option>
			<option value="기타 상담">기타 상담</option>
		</select>
		<span class="hint">고르는 동안 무엇을 원하는지가 스스로 정리됩니다.</span>
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_want">무엇을 만들고 싶으신가요 <span class="req">필수</span></label>
		<textarea id="tl_want" name="tl_want" required placeholder="예) 공방을 운영합니다. 지금은 인스타그램 DM으로만 예약을 받는데 놓치는 일이 많습니다. 날짜와 시간을 골라 예약하고, 예약 내역을 제가 한눈에 볼 수 있으면 좋겠습니다."></textarea>
		<span class="hint">정리된 기획서가 아니어도 됩니다. 지금 무엇이 불편한지만 적어주셔도 충분합니다.</span>
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_reference">참고할 사이트나 자료</label>
		<input type="url" id="tl_reference" name="tl_reference" inputmode="url" placeholder="https://">
		<span class="hint">말로 설명하기 어려운 것은 링크가 빠릅니다.</span>
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_timing">희망 일정</label>
		<select id="tl_timing" name="tl_timing">
			<option value="미정">미정</option>
			<option value="급함">급함</option>
			<option value="1개월 내">1개월 내</option>
		</select>
	</div>';

	$out .= '<div class="tl-field">
		<label for="tl_budget">대략적인 예산 범위</label>
		<select id="tl_budget" name="tl_budget">
			<option value="미정">미정 — 상의하고 정하고 싶습니다</option>
			<option value="50만원 미만">50만원 미만</option>
			<option value="50~150만원">50~150만원</option>
			<option value="150~300만원">150~300만원</option>
			<option value="300만원 이상">300만원 이상</option>
		</select>
		<span class="hint">범위를 알려주시면 가능한 방법을 그 안에서 찾아 제안합니다.</span>
	</div>';

	$out .= sprintf(
		'<div class="tl-field">
			<label class="tl-consent" for="tl_consent">
				<input type="checkbox" id="tl_consent" name="tl_consent" value="1" required>
				<span>문의 회신을 위해 이름과 이메일을 수집·이용하는 데 동의합니다. 보관 기간과 파기 방법은 <a href="%s">개인정보처리방침</a>에 있습니다. <span class="req">필수</span></span>
			</label>
		</div>',
		esc_url( home_url( '/privacy/' ) )
	);

	$out .= '<div class="tl-field">
		<button type="submit" class="tl-btn tl-btn--solid">문의 보내기 <span aria-hidden="true">→</span></button>
	</div>';

	$out .= '</form>';

	return $out;
}

/**
 * 숏코드 — 폼 플러그인으로 갈아탈 때도 자리를 그대로 쓸 수 있게 열어 둡니다.
 */
function tenlune_contact_form_shortcode() {
	return tenlune_render_contact_form();
}
add_shortcode( 'tenlune_contact_form', 'tenlune_contact_form_shortcode' );
