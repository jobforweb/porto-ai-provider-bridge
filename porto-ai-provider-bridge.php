<?php
/**
 * Plugin Name: Porto AI Provider Bridge
 * Plugin URI: https://jobforweb.ru/
 * Description: Подключает AI-генератор темы Porto к внешнему OpenAI-совместимому провайдеру. Проверено с провайдером Ollama.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: LVI
 * Author URI: https://jobforweb.ru/
 * Text Domain: porto-ai-provider-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAPB_PLUGIN_FILE' ) ) {
	define( 'PAPB_PLUGIN_FILE', __FILE__ );
}

// Админка, настройки и валидация вынесены в отдельную папку.
require_once plugin_dir_path( PAPB_PLUGIN_FILE ) . 'admin/papb-admin.php';

/**
 * Готовит константы для существующей логики подключения.
 *
 * Логика фильтров ниже остается прежней: меняется только источник параметров —
 * теперь это настройки плагина, а не константы в functions.php дочерней темы.
 */
$papb_settings = papb_get_settings();

if ( ! defined( 'PORTO_AI_MODEL' ) ) {
	define( 'PORTO_AI_MODEL', (string) $papb_settings['model'] );
}

if ( ! defined( 'PORTO_OLLAMA_URL' ) ) {
	define( 'PORTO_OLLAMA_URL', (string) $papb_settings['provider_url'] );
}

if ( ! defined( 'PORTO_PAPB_OPENAI_PREFIX' ) ) {
	define( 'PORTO_PAPB_OPENAI_PREFIX', (string) $papb_settings['openai_prefix'] );
}

if ( ! defined( 'PORTO_PAPB_OPENAI_HOST' ) ) {
	define( 'PORTO_PAPB_OPENAI_HOST', (string) $papb_settings['openai_host'] );
}

if ( ! defined( 'PORTO_OLLAMA_TIMEOUT' ) ) {
	define( 'PORTO_OLLAMA_TIMEOUT', absint( $papb_settings['php_timeout'] ) );
}

if ( ! defined( 'PORTO_JS_TIMEOUT' ) ) {
	define( 'PORTO_JS_TIMEOUT', absint( $papb_settings['js_timeout'] ) );
}

if ( ! defined( 'PORTO_OLLAMA_DISABLE_THINKING' ) ) {
	define( 'PORTO_OLLAMA_DISABLE_THINKING', (bool) $papb_settings['disable_thinking'] );
}

if ( ! defined( 'PORTO_PAPB_FORCE_STREAM' ) ) {
	define( 'PORTO_PAPB_FORCE_STREAM', (bool) $papb_settings['force_stream_false'] );
}

if ( ! defined( 'PORTO_OLLAMA_SYSTEM_HINT' ) ) {
	$hint = trim( (string) $papb_settings['system_hint'] );
	define( 'PORTO_OLLAMA_SYSTEM_HINT', $hint ? "\n\n" . $hint : '' );
}

if ( '' !== (string) $papb_settings['max_tokens_override'] && ! defined( 'PORTO_OLLAMA_MAX_TOKENS_OVERRIDE' ) ) {
	define( 'PORTO_OLLAMA_MAX_TOKENS_OVERRIDE', absint( $papb_settings['max_tokens_override'] ) );
}

if ( '' !== (string) $papb_settings['temperature_override'] && ! defined( 'PORTO_OLLAMA_TEMPERATURE_OVERRIDE' ) ) {
	define( 'PORTO_OLLAMA_TEMPERATURE_OVERRIDE', (float) $papb_settings['temperature_override'] );
}

if ( ! defined( 'PORTO_PAPB_UNSET_PARAMS' ) ) {
	define( 'PORTO_PAPB_UNSET_PARAMS', (string) $papb_settings['unset_openai_params'] );
}

if ( ! defined( 'PORTO_PAPB_AJAX_ACTION' ) ) {
	define( 'PORTO_PAPB_AJAX_ACTION', (string) $papb_settings['ajax_action'] );
}

if ( ! defined( 'PORTO_PAPB_ADMIN_SCREENS' ) ) {
	define( 'PORTO_PAPB_ADMIN_SCREENS', (string) $papb_settings['admin_screens'] );
}

if ( ! defined( 'PORTO_OLLAMA_API_KEY_STUB' ) ) {
	define( 'PORTO_OLLAMA_API_KEY_STUB', (string) $papb_settings['api_key_stub'] );
}

if ( ! defined( 'PORTO_OLLAMA_SSLVERIFY' ) ) {
	define( 'PORTO_OLLAMA_SSLVERIFY', ! (bool) $papb_settings['disable_ssl_verify'] );
}

/**
 * ==========================================================
 * ЛОГИКА ПОДКЛЮЧЕНИЯ К PORTO AI
 * Фильтры, тело запроса и таймауты сохранены без изменения сути.
 * ==========================================================
 */

// 1. Подменяем модель (использует настройку модели из админки плагина).
add_filter( 'porto_ai_model', function( $model ) {
	return defined( 'PORTO_AI_MODEL' ) ? PORTO_AI_MODEL : $model;
});

// 2. API-ключ (провайдер не требует ключа, но Porto проверяет его наличие).
add_filter( 'porto_ai_openai_key', function( $key ) {
	// Возвращаем заглушку из настроек, чтобы Porto считал, что ключ есть.
	return defined( 'PORTO_OLLAMA_API_KEY_STUB' ) ? PORTO_OLLAMA_API_KEY_STUB : 'sk-ollama-local';
});

// 3. Модифицируем тело запроса.
add_filter( 'porto_ai_request_body', function( $body, $prompt ) {

	// 3.1. Переопределяем max_tokens, если задана настройка.
	if ( defined( 'PORTO_OLLAMA_MAX_TOKENS_OVERRIDE' ) ) {
		$body['max_tokens'] = PORTO_OLLAMA_MAX_TOKENS_OVERRIDE;
	}
	// Иначе — оставляем дефолт Porto (он уже в $body).

	// 3.2. Переопределяем temperature, если задана настройка.
	if ( defined( 'PORTO_OLLAMA_TEMPERATURE_OVERRIDE' ) ) {
		$body['temperature'] = PORTO_OLLAMA_TEMPERATURE_OVERRIDE;
	}
	// Иначе — оставляем дефолт Porto.

	// 3.3. Отключаем "рассуждения" модели (если включено).
	if ( defined( 'PORTO_OLLAMA_DISABLE_THINKING' ) && PORTO_OLLAMA_DISABLE_THINKING ) {
		$body['think'] = false;
	}

	// 3.4. Принудительно отключаем потоковую передачу (если включено).
	if ( defined( 'PORTO_PAPB_FORCE_STREAM' ) && PORTO_PAPB_FORCE_STREAM ) {
		$body['stream'] = false;
	}

	// 3.5. Удаляем параметры OpenAI, которые провайдер может не понимать.
	$unset_params = defined( 'PORTO_PAPB_UNSET_PARAMS' ) ? PORTO_PAPB_UNSET_PARAMS : 'response_format, logprobs, top_logprobs, n, seed';
	$unset_params = array_filter( array_map( 'trim', explode( ',', $unset_params ) ) );

	foreach ( $unset_params as $param ) {
		unset( $body[ $param ] );
	}

	// 3.6. Добавляем инструкцию в system prompt (если задана).
	if ( defined( 'PORTO_OLLAMA_SYSTEM_HINT' ) && PORTO_OLLAMA_SYSTEM_HINT ) {
		if ( isset( $body['messages'] ) && is_array( $body['messages'] ) ) {
			foreach ( $body['messages'] as &$msg ) {
				if ( $msg['role'] === 'system' ) {
					$msg['content'] .= PORTO_OLLAMA_SYSTEM_HINT;
				}
			}
		}
	}

	return $body;
}, 10, 2);

// 4. Перехват URL и установка таймаута.
add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
	$openai_host = defined( 'PORTO_PAPB_OPENAI_HOST' ) && PORTO_PAPB_OPENAI_HOST
		? PORTO_PAPB_OPENAI_HOST
		: 'api.openai.com';

	if ( strpos( $url, $openai_host ) === false ) {
		return $preempt;
	}

	// Меняем URL на адрес провайдера из настроек плагина.
	$openai_prefix = defined( 'PORTO_PAPB_OPENAI_PREFIX' ) && PORTO_PAPB_OPENAI_PREFIX
		? PORTO_PAPB_OPENAI_PREFIX
		: 'https://api.openai.com/v1/';

	$provider_url = str_replace(
		$openai_prefix,
		defined( 'PORTO_OLLAMA_URL' ) ? PORTO_OLLAMA_URL : 'http://127.0.0.1:11434/v1/',
		$url
	);

	// Устанавливаем таймаут из настроек плагина.
	$args['timeout']   = defined( 'PORTO_OLLAMA_TIMEOUT' ) ? PORTO_OLLAMA_TIMEOUT : 120;
	$args['sslverify'] = defined( 'PORTO_OLLAMA_SSLVERIFY' ) ? PORTO_OLLAMA_SSLVERIFY : false;

	return wp_remote_post( $provider_url, $args );
}, 10, 3);

// 5. Увеличиваем JS таймаут в браузере.
add_action( 'admin_footer', function() {
	if ( ! function_exists( 'get_current_screen' ) ) return;
	$screen = get_current_screen();

	$screens_raw = defined( 'PORTO_PAPB_ADMIN_SCREENS' ) ? PORTO_PAPB_ADMIN_SCREENS : 'post,page';
	$screens     = array_filter( array_map( 'trim', explode( ',', $screens_raw ) ) );

	if ( ! $screen || ! in_array( $screen->base, $screens, true ) ) return;

	$js_timeout  = defined( 'PORTO_JS_TIMEOUT' ) ? PORTO_JS_TIMEOUT : 120000;
	$ajax_action = defined( 'PORTO_PAPB_AJAX_ACTION' ) ? PORTO_PAPB_AJAX_ACTION : 'porto_ai_generate';
	?>
	<script type="text/javascript">
	(function($) {
		if (typeof $ === 'undefined') return;

		var originalAjax = $.ajax;
		var ajaxAction = <?php echo wp_json_encode( $ajax_action ); ?>;

		$.ajax = function(url, options) {
			if (typeof url === 'object') {
				options = url;
			} else {
				options = options || {};
				options.url = url;
			}

			// Перехватываем только запросы Porto AI.
			if (options.data && typeof options.data === 'object' && options.data.action === ajaxAction) {
				options.timeout = <?php echo intval( $js_timeout ); ?>;
			}

			return originalAjax.call(this, options);
		};
	})(jQuery);
	</script>
	<?php
}, 999);
