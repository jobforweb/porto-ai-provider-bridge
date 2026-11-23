<?php
/**
 * Админская часть плагина Porto AI Provider Bridge.
 *
 * Здесь хранятся настройки, валидация, страница настроек и ссылка
 * в списке плагинов. Логика подключения к Porto AI находится
 * в основном файле плагина и в этом файле не дублируется.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAPB_OPTION_KEY' ) ) {
	define( 'PAPB_OPTION_KEY', 'porto_ai_provider_bridge_settings' );
}

if ( ! defined( 'PAPB_OPTION_GROUP' ) ) {
	define( 'PAPB_OPTION_GROUP', 'porto_ai_provider_bridge_group' );
}

if ( ! defined( 'PAPB_PAGE_SLUG' ) ) {
	define( 'PAPB_PAGE_SLUG', 'porto-ai-provider-bridge' );
}

/**
 * Возвращает все параметры плагина со значениями по умолчанию.
 *
 * @return array
 */
function papb_get_default_settings() {
	return array(
		'model'               => 'qwen3.6:27b-coding',
		'provider_url'        => 'http://127.0.0.1:11434/v1/',
		'openai_prefix'       => 'https://api.openai.com/v1/',
		'openai_host'         => 'api.openai.com',
		'api_key_stub'        => 'sk-ollama-local',
		'php_timeout'         => 120,
		'js_timeout'          => 120000,
		'disable_thinking'    => 1,
		'force_stream_false'  => 1,
		'system_hint'         => 'CRITICAL: Do NOT use internal reasoning or thinking process. Start writing the final text IMMEDIATELY. Return ONLY pure text.',
		'max_tokens_override' => '',
		'temperature_override'=> '',
		'unset_openai_params' => 'response_format, logprobs, top_logprobs, n, seed',
		'ajax_action'         => 'porto_ai_generate',
		'admin_screens'       => 'post,page',
		'disable_ssl_verify'  => 1,
	);
}

/**
 * Возвращает сохраненные настройки с подставленными значениями по умолчанию.
 *
 * @return array
 */
function papb_get_settings() {
	$saved = get_option( PAPB_OPTION_KEY, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return array_merge( papb_get_default_settings(), $saved );
}

/**
 * Возвращает описание всех полей админки.
 *
 * Каждое поле содержит:
 * - label — название слева в таблице;
 * - type — тип поля;
 * - hint — краткое пояснение под полем;
 * - porto — соответствие в Porto;
 * - default — значение по умолчанию;
 * - note — дополнительное примечание в правой колонке.
 *
 * @return array
 */
function papb_get_fields() {
	return array(
		'model' => array(
			'label'   => __( 'Модель', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Имя модели провайдера.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_model</code> → константа <code>PORTO_AI_MODEL</code>', 'porto-ai-provider-bridge' ),
			'default' => 'qwen3.6:27b-coding',
			'note'    => __( 'Должно символ в символ совпадать с именем в <code>ollama list</code>.', 'porto-ai-provider-bridge' ),
		),
		'provider_url' => array(
			'label'   => __( 'URL провайдера', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Базовый OpenAI-совместимый адрес.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Подмена в <code>pre_http_request</code> → константа <code>PORTO_OLLAMA_URL</code>', 'porto-ai-provider-bridge' ),
			'default' => 'http://127.0.0.1:11434/v1/',
			'note'    => __( 'Обязательно с завершающим <code>/v1/</code>.', 'porto-ai-provider-bridge' ),
		),
		'openai_prefix' => array(
			'label'   => __( 'Префикс адреса OpenAI', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Что заменять в адресе запроса Porto.', 'porto-ai-provider-bridge' ),
			'porto'   => __( '<code>pre_http_request</code> → константа <code>PORTO_PAPB_OPENAI_PREFIX</code>', 'porto-ai-provider-bridge' ),
			'default' => 'https://api.openai.com/v1/',
			'note'    => __( 'Исходный префикс, который формирует тема Porto.', 'porto-ai-provider-bridge' ),
		),
		'openai_host' => array(
			'label'   => __( 'Хост запроса OpenAI', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Признак, по которому перехватывается запрос.', 'porto-ai-provider-bridge' ),
			'porto'   => __( '<code>pre_http_request</code> → константа <code>PORTO_PAPB_OPENAI_HOST</code>', 'porto-ai-provider-bridge' ),
			'default' => 'api.openai.com',
			'note'    => __( 'Другие внешние запросы WordPress не трогаются.', 'porto-ai-provider-bridge' ),
		),
		'api_key_stub' => array(
			'label'   => __( 'Заглушка API-ключа', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Подставляется вместо ключа OpenAI.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_openai_key</code> → константа <code>PORTO_OLLAMA_API_KEY_STUB</code>', 'porto-ai-provider-bridge' ),
			'default' => 'sk-ollama-local',
			'note'    => __( 'Сам провайдер ключ не требует, но Porto поле требует заполненным.', 'porto-ai-provider-bridge' ),
		),
		'php_timeout' => array(
			'label'   => __( 'Таймаут PHP, сек', 'porto-ai-provider-bridge' ),
			'type'    => 'number',
			'hint'    => __( 'Время ожидания ответа провайдера.', 'porto-ai-provider-bridge' ),
			'porto'   => __( '<code>pre_http_request</code> → <code>$args[timeout]</code> → константа <code>PORTO_OLLAMA_TIMEOUT</code>', 'porto-ai-provider-bridge' ),
			'default' => '120',
			'note'    => __( 'Дефолт Porto — 30 секунд.', 'porto-ai-provider-bridge' ),
		),
		'js_timeout' => array(
			'label'   => __( 'Таймаут браузера, мс', 'porto-ai-provider-bridge' ),
			'type'    => 'number',
			'hint'    => __( 'Таймаут AJAX в браузере.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'JS-перехват <code>$.ajax</code> → константа <code>PORTO_JS_TIMEOUT</code>', 'porto-ai-provider-bridge' ),
			'default' => '120000',
			'note'    => __( 'Дефолт Porto — 35000 мс. Должен быть не меньше PHP-таймаута × 1000.', 'porto-ai-provider-bridge' ),
		),
		'disable_thinking' => array(
			'label'   => __( 'Отключать рассуждения', 'porto-ai-provider-bridge' ),
			'type'    => 'checkbox',
			'hint'    => __( 'Передавать <code>think: false</code>.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → константа <code>PORTO_OLLAMA_DISABLE_THINKING</code>', 'porto-ai-provider-bridge' ),
			'default' => __( 'Включено', 'porto-ai-provider-bridge' ),
			'note'    => __( 'Рекомендуется для моделей семейства Qwen.', 'porto-ai-provider-bridge' ),
		),
		'force_stream_false' => array(
			'label'   => __( 'Принудительно stream: false', 'porto-ai-provider-bridge' ),
			'type'    => 'checkbox',
			'hint'    => __( 'Отключать потоковую передачу.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → константа <code>PORTO_PAPB_FORCE_STREAM</code>', 'porto-ai-provider-bridge' ),
			'default' => __( 'Включено', 'porto-ai-provider-bridge' ),
			'note'    => __( 'Оставляйте включенным: потоковый ответ ломает ожидания Porto.', 'porto-ai-provider-bridge' ),
		),
		'system_hint' => array(
			'label'   => __( 'Дополнительная инструкция', 'porto-ai-provider-bridge' ),
			'type'    => 'textarea',
			'hint'    => __( 'Добавляется к system prompt.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → <code>messages[system].content</code> → константа <code>PORTO_OLLAMA_SYSTEM_HINT</code>', 'porto-ai-provider-bridge' ),
			'default' => 'CRITICAL: Do NOT use internal reasoning or thinking process. Start writing the final text IMMEDIATELY. Return ONLY pure text.',
			'note'    => __( 'Пустое значение отключает добавление.', 'porto-ai-provider-bridge' ),
		),
		'max_tokens_override' => array(
			'label'   => __( 'Лимит токенов', 'porto-ai-provider-bridge' ),
			'type'    => 'number',
			'hint'    => __( 'Оставьте пустым, чтобы использовать Porto.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → <code>max_tokens</code> → константа <code>PORTO_OLLAMA_MAX_TOKENS_OVERRIDE</code>', 'porto-ai-provider-bridge' ),
			'default' => __( 'Не задано', 'porto-ai-provider-bridge' ) . ' — content 2048, excerpt 64, meta description 265, outline 2048',
			'note'    => __( 'Если задать число, оно применится ко всем типам контента.', 'porto-ai-provider-bridge' ),
		),
		'temperature_override' => array(
			'label'   => __( 'Температура', 'porto-ai-provider-bridge' ),
			'type'    => 'number',
			'hint'    => __( 'Диапазон 0–2. Пусто — значения Porto.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → <code>temperature</code> → константа <code>PORTO_OLLAMA_TEMPERATURE_OVERRIDE</code>', 'porto-ai-provider-bridge' ),
			'default' => __( 'Не задано', 'porto-ai-provider-bridge' ) . ' — content 0.9, excerpt 0.1, meta description 0.3',
			'note'    => __( 'Точные значения зависят от версии Porto.', 'porto-ai-provider-bridge' ),
		),
		'unset_openai_params' => array(
			'label'   => __( 'Удаляемые параметры OpenAI', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Список через запятую.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Фильтр <code>porto_ai_request_body</code> → <code>unset()</code> → константа <code>PORTO_PAPB_UNSET_PARAMS</code>', 'porto-ai-provider-bridge' ),
			'default' => 'response_format, logprobs, top_logprobs, n, seed',
			'note'    => __( 'Параметры, которые провайдер может не понять.', 'porto-ai-provider-bridge' ),
		),
		'ajax_action' => array(
			'label'   => __( 'AJAX-экшен Porto AI', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Имя действия в запросе админки.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'JS: <code>admin-ajax.php</code> → <code>action</code> → константа <code>PORTO_PAPB_AJAX_ACTION</code>', 'porto-ai-provider-bridge' ),
			'default' => 'porto_ai_generate',
			'note'    => __( 'Проверяйте после обновления темы Porto.', 'porto-ai-provider-bridge' ),
		),
		'admin_screens' => array(
			'label'   => __( 'Экраны админки', 'porto-ai-provider-bridge' ),
			'type'    => 'text',
			'hint'    => __( 'Список экранов через запятую.', 'porto-ai-provider-bridge' ),
			'porto'   => __( 'Хук <code>admin_footer</code> → <code>$screen-&gt;base</code> → константа <code>PORTO_PAPB_ADMIN_SCREENS</code>', 'porto-ai-provider-bridge' ),
			'default' => 'post,page',
			'note'    => __( 'Где подключается JS-перехватчик таймаута.', 'porto-ai-provider-bridge' ),
		),
		'disable_ssl_verify' => array(
			'label'   => __( 'Отключать проверку SSL', 'porto-ai-provider-bridge' ),
			'type'    => 'checkbox',
			'hint'    => __( 'Устанавливать <code>sslverify = false</code>.', 'porto-ai-provider-bridge' ),
			'porto'   => __( '<code>pre_http_request</code> → <code>$args[sslverify]</code> → константа <code>PORTO_OLLAMA_SSLVERIFY</code>', 'porto-ai-provider-bridge' ),
			'default' => __( 'Включено', 'porto-ai-provider-bridge' ) . ' (sslverify = false)',
			'note'    => __( 'Нужно для локальных адресов и туннелей с самоподписанным сертификатом.', 'porto-ai-provider-bridge' ),
		),
	);
}

/**
 * Добавляет страницу настроек в меню «Настройки».
 */
function papb_add_settings_page() {
	add_options_page(
		__( 'Porto AI Provider Bridge', 'porto-ai-provider-bridge' ),
		__( 'Porto AI Bridge', 'porto-ai-provider-bridge' ),
		'manage_options',
		PAPB_PAGE_SLUG,
		'papb_render_settings_page'
	);
}
add_action( 'admin_menu', 'papb_add_settings_page' );

/**
 * Регистрирует опцию плагина.
 */
function papb_register_settings() {
	register_setting(
		PAPB_OPTION_GROUP,
		PAPB_OPTION_KEY,
		array(
			'sanitize_callback' => 'papb_sanitize_settings',
			'default'           => papb_get_default_settings(),
		)
	);
}
add_action( 'admin_init', 'papb_register_settings' );

/**
 * Очищает список через запятую.
 *
 * @param string $value Исходная строка.
 * @return string
 */
function papb_normalize_list( $value ) {
	$parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
	return implode( ', ', $parts );
}

/**
 * Очистка и проверка настроек перед сохранением.
 *
 * @param array $input Введенные значения.
 * @return array
 */
function papb_sanitize_settings( $input ) {
	$defaults = papb_get_default_settings();
	$input    = is_array( $input ) ? $input : array();
	$output   = array();

	$output['model'] = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : $defaults['model'];
	if ( '' === $output['model'] ) {
		add_settings_error( PAPB_OPTION_KEY, 'papb_model', __( 'Укажите имя модели.', 'porto-ai-provider-bridge' ) );
		$output['model'] = $defaults['model'];
	}

	$provider_url = isset( $input['provider_url'] ) ? esc_url_raw( trim( $input['provider_url'] ) ) : $defaults['provider_url'];
	if ( '' === $provider_url ) {
		add_settings_error( PAPB_OPTION_KEY, 'papb_url', __( 'Укажите URL провайдера.', 'porto-ai-provider-bridge' ) );
		$provider_url = $defaults['provider_url'];
	}
	if ( '/' !== substr( $provider_url, -1 ) ) {
		$provider_url .= '/';
	}
	if ( false === strpos( $provider_url, '/v1/' ) ) {
		add_settings_error( PAPB_OPTION_KEY, 'papb_url_v1', __( 'URL провайдера должен содержать /v1/ на конце.', 'porto-ai-provider-bridge' ), 'warning' );
	}
	$output['provider_url'] = $provider_url;

	$openai_prefix = isset( $input['openai_prefix'] ) ? esc_url_raw( trim( $input['openai_prefix'] ) ) : $defaults['openai_prefix'];
	if ( '' === $openai_prefix ) {
		$openai_prefix = $defaults['openai_prefix'];
	}
	if ( '/' !== substr( $openai_prefix, -1 ) ) {
		$openai_prefix .= '/';
	}
	$output['openai_prefix'] = $openai_prefix;

	$openai_host = isset( $input['openai_host'] ) ? sanitize_text_field( $input['openai_host'] ) : $defaults['openai_host'];
	$openai_host = trim( preg_replace( '#^https?://#i', '', $openai_host ) );
	$openai_host = trim( strtok( $openai_host, '/' ) );
	$output['openai_host'] = '' !== $openai_host ? $openai_host : $defaults['openai_host'];

	$output['api_key_stub'] = isset( $input['api_key_stub'] ) ? sanitize_text_field( $input['api_key_stub'] ) : $defaults['api_key_stub'];
	if ( '' === $output['api_key_stub'] ) {
		$output['api_key_stub'] = $defaults['api_key_stub'];
	}

	$php_timeout = isset( $input['php_timeout'] ) ? absint( $input['php_timeout'] ) : $defaults['php_timeout'];
	if ( $php_timeout < 1 ) {
		add_settings_error( PAPB_OPTION_KEY, 'papb_php_timeout', __( 'Таймаут PHP должен быть не менее 1 секунды.', 'porto-ai-provider-bridge' ) );
		$php_timeout = $defaults['php_timeout'];
	}
	$output['php_timeout'] = $php_timeout;

	$js_timeout = isset( $input['js_timeout'] ) ? absint( $input['js_timeout'] ) : $defaults['js_timeout'];
	if ( $js_timeout < 1000 ) {
		add_settings_error( PAPB_OPTION_KEY, 'papb_js_timeout', __( 'Таймаут браузера должен быть не менее 1000 мс.', 'porto-ai-provider-bridge' ) );
		$js_timeout = $defaults['js_timeout'];
	}
	$output['js_timeout'] = $js_timeout;

	$output['disable_thinking']   = ! empty( $input['disable_thinking'] ) ? 1 : 0;
	$output['force_stream_false'] = ! empty( $input['force_stream_false'] ) ? 1 : 0;
	$output['disable_ssl_verify'] = ! empty( $input['disable_ssl_verify'] ) ? 1 : 0;

	$output['system_hint'] = isset( $input['system_hint'] ) ? sanitize_textarea_field( $input['system_hint'] ) : $defaults['system_hint'];

	$max_tokens = isset( $input['max_tokens_override'] ) ? trim( (string) $input['max_tokens_override'] ) : '';
	if ( '' === $max_tokens ) {
		$output['max_tokens_override'] = '';
	} else {
		$max_tokens = absint( $max_tokens );
		if ( $max_tokens < 1 ) {
			add_settings_error( PAPB_OPTION_KEY, 'papb_max_tokens', __( 'Лимит токенов должен быть не менее 1.', 'porto-ai-provider-bridge' ) );
			$output['max_tokens_override'] = '';
		} else {
			$output['max_tokens_override'] = $max_tokens;
		}
	}

	$temperature = isset( $input['temperature_override'] ) ? trim( (string) $input['temperature_override'] ) : '';
	if ( '' === $temperature ) {
		$output['temperature_override'] = '';
	} else {
		$temperature = (float) $temperature;
		if ( $temperature < 0 || $temperature > 2 ) {
			add_settings_error( PAPB_OPTION_KEY, 'papb_temperature', __( 'Температура должна быть в диапазоне от 0 до 2.', 'porto-ai-provider-bridge' ) );
			$output['temperature_override'] = '';
		} else {
			$output['temperature_override'] = (string) $temperature;
		}
	}

	$unset_params = isset( $input['unset_openai_params'] ) ? papb_normalize_list( $input['unset_openai_params'] ) : $defaults['unset_openai_params'];
	$output['unset_openai_params'] = '' !== $unset_params ? $unset_params : $defaults['unset_openai_params'];

	$ajax_action = isset( $input['ajax_action'] ) ? sanitize_key( $input['ajax_action'] ) : $defaults['ajax_action'];
	$output['ajax_action'] = '' !== $ajax_action ? $ajax_action : $defaults['ajax_action'];

	$admin_screens = isset( $input['admin_screens'] ) ? papb_normalize_list( $input['admin_screens'] ) : $defaults['admin_screens'];
	$output['admin_screens'] = '' !== $admin_screens ? $admin_screens : $defaults['admin_screens'];

	return $output;
}

/**
 * Отображает одно поле в левой колонке таблицы.
 *
 * @param string $key   Ключ настройки.
 * @param array  $field Описание поля.
 */
function papb_render_field( $key, $field ) {
	$settings = papb_get_settings();
	$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	$type     = $field['type'];

	printf( '<label for="%1$s"><strong>%2$s</strong></label><br />', esc_attr( $key ), esc_html( $field['label'] ) );

	if ( 'checkbox' === $type ) {
		printf(
			'<input type="hidden" name="%1$s[%2$s]" value="0" />',
			esc_attr( PAPB_OPTION_KEY ),
			esc_attr( $key )
		);
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s[%3$s]" value="1" %4$s /> %5$s</label>',
			esc_attr( $key ),
			esc_attr( PAPB_OPTION_KEY ),
			esc_attr( $key ),
			checked( 1, (int) $value, false ),
			esc_html__( 'Включено', 'porto-ai-provider-bridge' )
		);
	} elseif ( 'textarea' === $type ) {
		printf(
			'<textarea id="%1$s" name="%2$s[%3$s]" rows="4" class="large-text">%4$s</textarea>',
			esc_attr( $key ),
			esc_attr( PAPB_OPTION_KEY ),
			esc_attr( $key ),
			esc_textarea( $value )
		);
	} else {
		$attrs = '';
		if ( 'number' === $type ) {
			$attrs = ' min="0" step="1"';
			if ( 'max_tokens_override' === $key ) {
				$attrs = ' min="1" step="1"';
			}
			if ( 'temperature_override' === $key ) {
				$attrs = ' min="0" max="2" step="0.1"';
			}
		}
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s[%4$s]" value="%5$s" class="regular-text"%6$s />',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( PAPB_OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value ),
			$attrs
		);
	}

	if ( ! empty( $field['hint'] ) ) {
		echo '<p class="description">' . wp_kses_post( $field['hint'] ) . '</p>';
	}
}

/**
 * Отображает правую колонку с соответствием в Porto и дефолтом.
 *
 * @param array $field Описание поля.
 */
function papb_render_field_meta( $field ) {
	echo '<p><strong>' . esc_html__( 'PORTO:', 'porto-ai-provider-bridge' ) . '</strong> ' . wp_kses_post( $field['porto'] ) . '</p>';
	echo '<p><strong>' . esc_html__( 'По умолчанию:', 'porto-ai-provider-bridge' ) . '</strong> <code>' . esc_html( $field['default'] ) . '</code></p>';
	if ( ! empty( $field['note'] ) ) {
		echo '<p class="description">' . wp_kses_post( $field['note'] ) . '</p>';
	}
}

/**
 * Отображает таблицу настроек в два столбца.
 */
function papb_render_settings_table() {
	?>
	<table class="widefat striped papb-settings-table">
		<thead>
			<tr>
				<th style="width: 50%;"><?php esc_html_e( 'Настройка плагина', 'porto-ai-provider-bridge' ); ?></th>
				<th style="width: 50%;"><?php esc_html_e( 'Соответствие в PORTO и значение по умолчанию', 'porto-ai-provider-bridge' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( papb_get_fields() as $key => $field ) : ?>
				<tr>
					<td>
						<?php papb_render_field( $key, $field ); ?>
					</td>
					<td>
						<?php papb_render_field_meta( $field ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Отображает страницу настроек.
 */
function papb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p>
			<?php esc_html_e( 'Все параметры подключения AI-генератора темы Porto к внешнему провайдеру. Сейчас проверено с Ollama.', 'porto-ai-provider-bridge' ); ?>
		</p>
		<?php settings_errors( PAPB_OPTION_KEY ); ?>
		<form action="options.php" method="post">
			<?php
			settings_fields( PAPB_OPTION_GROUP );
			papb_render_settings_table();
			submit_button( __( 'Сохранить изменения', 'porto-ai-provider-bridge' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Добавляет ссылку на настройки в списке плагинов.
 *
 * @param array $links Ссылки плагина.
 * @return array
 */
function papb_add_action_links( $links ) {
	$settings_url = admin_url( 'options-general.php?page=' . PAPB_PAGE_SLUG );

	array_unshift(
		$links,
		'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Настройки', 'porto-ai-provider-bridge' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( defined( 'PAPB_PLUGIN_FILE' ) ? PAPB_PLUGIN_FILE : __FILE__ ), 'papb_add_action_links' );
