<?php
/**
 * Plugin Name:       Tenlune Content
 * Plugin URI:        https://tenlune.com/
 * Description:       Tenlune 의 콘텐츠 구조 — 커스텀 포스트 타입 case, 택소노미 case_type, 케이스 스터디 커스텀 필드(개선 로그 포함)와 렌더링 블록. 테마와 분리해 두어야 나중에 테마를 바꿔도 Work 콘텐츠가 남습니다.
 * Version:           0.1.3
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Tenlune
 * Author URI:        https://tenlune.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tenlune-content
 *
 * @package TenluneContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TENLUNE_CONTENT_VERSION', '0.1.3' );
define( 'TENLUNE_CONTENT_FILE', __FILE__ );
define( 'TENLUNE_CONTENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'TENLUNE_CONTENT_URL', plugin_dir_url( __FILE__ ) );

require_once TENLUNE_CONTENT_DIR . 'includes/post-types.php';
require_once TENLUNE_CONTENT_DIR . 'includes/fields.php';
require_once TENLUNE_CONTENT_DIR . 'includes/enquiry.php';
require_once TENLUNE_CONTENT_DIR . 'includes/blocks.php';
require_once TENLUNE_CONTENT_DIR . 'includes/social-meta.php';

/**
 * 활성화 시 퍼머링크를 한 번 다시 씁니다.
 * 이걸 빼면 /work/{slug}/ 가 404 로 뜨고, 원인 찾기가 매우 어렵습니다.
 */
function tenlune_content_activate() {
	tenlune_register_case_post_type();
	tenlune_register_case_taxonomy();
	flush_rewrite_rules();
}
register_activation_hook( TENLUNE_CONTENT_FILE, 'tenlune_content_activate' );

/**
 * 비활성화 시에도 퍼머링크를 정리합니다. 데이터는 지우지 않습니다.
 */
function tenlune_content_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( TENLUNE_CONTENT_FILE, 'tenlune_content_deactivate' );
