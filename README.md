# Porto AI Provider Bridge

A WordPress plugin for the **Porto** theme that switches Porto's built-in AI content generator from OpenAI to an external OpenAI-compatible provider. The plugin has been tested and works with the **Ollama** provider. It has been verified with official registered versions of the **PORTO** WordPress theme (Author: P-THEMES), **v.7.10.0 - v.7.10.2**.

**The plugin's goal** is to connect the Porto theme's built-in AI generator to free, open language models through OpenAI-compatible providers deployed on your own server. This approach eliminates spending on paid cloud AI APIs and improves privacy: prompts, drafts, and content are processed within your infrastructure rather than being sent to third-party commercial services.

The solution lets you keep using Porto's native interface (product descriptions, excerpts, meta titles/meta descriptions, outlines, etc.), but instead of the paid OpenAI API, requests are sent to a local or self-hosted server running `qwen3.6:27b-coding`.

Author: **LVI**. Website: https://jobforweb.ru/

---

## What the plugin provides

- Replaces the OpenAI model with the provider's model via Porto's native `porto_ai_model` filter.
- Substitutes a dummy API key because Porto requires a non-empty key field even when working with an external provider.
- Adapts the request body for the provider:
  - forces streaming off (`stream: false`);
  - disables the model's internal "thinking" (`think: false`);
  - removes OpenAI-specific parameters;
  - adds an instruction to the system prompt so the model returns clean text immediately.
- Redirects the HTTP request from the OpenAI address to the provider address from the plugin settings.
- Increases timeouts: PHP request — 120 seconds, browser AJAX — 120 seconds.
- Moves every used parameter to the admin page: from model and URL to the OpenAI prefix, AJAX action, and admin screens.
- Preserves Porto's native values for `max_tokens` and `temperature` when the override fields are left empty.
- Works without editing the parent `porto` theme and without moving AI code into the child theme.

---

## Requirements

- WordPress with the active **Porto** theme and active **Porto Child** child theme.
- An official registered version of the Porto theme (Author: P-THEMES), v.7.10.0 - v.7.10.2, with AI support and `porto_ai_*` filters.
- A working OpenAI-compatible provider with the endpoint:

```text
http://HOST:PORT/v1/chat/completions
```

- A model loaded on the provider server. The default configuration uses:

```text
qwen3.6:27b-coding
```

- Network connectivity from the WordPress server to the provider server.
- A filled API key field in Porto's AI settings. In fact, it can be any non-empty string, for example `sk-ollama-local`.
- PHP 7.4 or newer.

---

## Installation

1. Copy the whole plugin folder, including the nested `admin` folder, into the WordPress plugins directory:

```text
wp-content/plugins/porto-ai-provider-bridge/
```

   It should contain `porto-ai-provider-bridge.php` and `admin/papb-admin.php`.

2. If the old bridge was previously used in the child theme, remove the block starting with the following comment from `wp-content/themes/porto-child/functions.php`:

```php
// Связка Ollama + Porto
```

   Leave the standard CSS loading block of the child theme as is. This prevents duplicate `porto_ai_*` hooks.

3. Activate the **Porto AI Provider Bridge** plugin on the Plugins screen.

4. Open the theme settings:

```text
Porto → Theme Options → AI
```

   and enter any non-empty API key, for example:

```text
sk-ollama-local
```

5. Open the plugin page:

```text
Settings → Porto AI Bridge
```

   Or use the "Settings" link in the plugin list next to the plugin name.

6. Review the fields, save the changes, and test generation through Porto's standard interface: post, page, or WooCommerce product → Porto AI block → Generate.

> Important: do not edit the parent `porto` theme. The plugin uses standard hooks and does not change theme files.

---

## Admin settings

All parameters are located on the **Settings → Porto AI Bridge** page. The admin part lives in the separate file `admin/papb-admin.php`, but nothing changes for the user: it is the same settings page with a link in the plugin list.

The settings table has two columns:

- left — the input field itself;
- right — the Porto mapping and the default value.

| Field | Default value | Porto mapping |
|---|---:|---|
| Model | `qwen3.6:27b-coding` | Filter `porto_ai_model` → `PORTO_AI_MODEL` |
| Provider URL | `http://127.0.0.1:11434/v1/` | `pre_http_request` → `PORTO_OLLAMA_URL` |
| OpenAI address prefix | `https://api.openai.com/v1/` | `pre_http_request` → `PORTO_PAPB_OPENAI_PREFIX` |
| OpenAI request host | `api.openai.com` | `pre_http_request` → `PORTO_PAPB_OPENAI_HOST` |
| API key stub | `sk-ollama-local` | Filter `porto_ai_openai_key` → `PORTO_OLLAMA_API_KEY_STUB` |
| PHP timeout, sec | `120` | `pre_http_request` → `$args['timeout']` → `PORTO_OLLAMA_TIMEOUT` |
| Browser timeout, ms | `120000` | JS `$.ajax` → `PORTO_JS_TIMEOUT` |
| Disable thinking | Enabled | `porto_ai_request_body` → `think: false` → `PORTO_OLLAMA_DISABLE_THINKING` |
| Force `stream: false` | Enabled | `porto_ai_request_body` → `stream` → `PORTO_PAPB_FORCE_STREAM` |
| System prompt hint | `CRITICAL: Do NOT use internal reasoning or thinking process. Start writing the final text IMMEDIATELY. Return ONLY pure text.` | `porto_ai_request_body` → `messages[system].content` → `PORTO_OLLAMA_SYSTEM_HINT` |
| Max tokens override | Not set; Porto: content 2048, excerpt 64, meta description 265, outline 2048 | `porto_ai_request_body` → `max_tokens` → `PORTO_OLLAMA_MAX_TOKENS_OVERRIDE` |
| Temperature override | Not set; Porto: content 0.9, excerpt 0.1, meta description 0.3 | `porto_ai_request_body` → `temperature` → `PORTO_OLLAMA_TEMPERATURE_OVERRIDE` |
| OpenAI params to remove | `response_format, logprobs, top_logprobs, n, seed` | `porto_ai_request_body` → `unset()` → `PORTO_PAPB_UNSET_PARAMS` |
| Porto AI AJAX action | `porto_ai_generate` | `admin-ajax.php` → `action` → `PORTO_PAPB_AJAX_ACTION` |
| Admin screens | `post,page` | `admin_footer` → `$screen->base` → `PORTO_PAPB_ADMIN_SCREENS` |
| Disable SSL verification | Enabled (`sslverify = false`) | `pre_http_request` → `$args['sslverify']` → `PORTO_OLLAMA_SSLVERIFY` |

### Constant precedence

If a corresponding constant is already defined, for example in `wp-config.php`, it takes precedence over the admin value. This is kept for compatibility with the old configuration approach:

```php
PORTO_AI_MODEL
PORTO_OLLAMA_URL
PORTO_PAPB_OPENAI_PREFIX
PORTO_PAPB_OPENAI_HOST
PORTO_OLLAMA_TIMEOUT
PORTO_JS_TIMEOUT
PORTO_OLLAMA_DISABLE_THINKING
PORTO_PAPB_FORCE_STREAM
PORTO_OLLAMA_SYSTEM_HINT
PORTO_OLLAMA_MAX_TOKENS_OVERRIDE
PORTO_OLLAMA_TEMPERATURE_OVERRIDE
PORTO_PAPB_UNSET_PARAMS
PORTO_PAPB_AJAX_ACTION
PORTO_PAPB_ADMIN_SCREENS
PORTO_OLLAMA_API_KEY_STUB
PORTO_OLLAMA_SSLVERIFY
```

For normal use, the plugin admin page is enough — you do not need to define constants.

### Porto values preserved by default

While the "Max tokens override" and "Temperature override" fields are empty, Porto's traditional parameters remain unchanged:

| Parameter | Content type | Porto value |
|---|---|---:|
| `max_tokens` | content | 2048 |
| `max_tokens` | excerpt | 64 |
| `max_tokens` | meta description | 265 |
| `max_tokens` | outline | 2048 |
| `temperature` | content | 0.9 |
| `temperature` | excerpt | 0.1 |
| `temperature` | meta description | 0.3 |

Exact values depend on the Porto theme version; after a theme update, it is worth re-checking generation.

---

## Preparing the provider

The plugin is currently tested with the Ollama provider. Support for other OpenAI-compatible servers (llama.cpp, LM Studio, etc.) is planned in the future, but their API specifics have not been checked yet.

On the Ollama server, run:

```bash
ollama pull qwen3.6:27b-coding
```

If Ollama runs on another machine, make sure it listens on more than `127.0.0.1`. For example:

```bash
OLLAMA_HOST=0.0.0.0:11222 ollama serve
```

Then check API availability from the server where WordPress runs:

```bash
curl -sS -X POST "http://HOST:PORT/v1/chat/completions" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen3.6:27b-coding",
    "stream": false,
    "think": false,
    "messages": [
      {"role": "user", "content": "Say OK"}
    ]
  }'
```

The response should be JSON with a `choices[0].message.content` field.

---

## How the plugin works

1. The user clicks Generate in Porto's AI block.
2. Porto sends an AJAX request with the `action` set in the "Porto AI AJAX action" field.
3. The `porto_ai_model`, `porto_ai_openai_key`, and `porto_ai_request_body` filters prepare the request using the plugin settings.
4. The `pre_http_request` hook looks for the host from the "OpenAI request host" field and replaces the "OpenAI address prefix" with the provider address.
5. The provider response is returned to Porto in the format the theme expects.
6. The JS interceptor increases the AJAX timeout only on the screens from the "Admin screens" field and only for the required `action`.

Other external WordPress HTTP requests are not affected.

---

## Verification

1. The provider terminal should show `POST /v1/chat/completions` when Generate is pressed.
2. In the browser: `F12 → Network → Fetch/XHR` — the `admin-ajax.php` request with the required `action` should return status `200`.
3. The response should contain generated text inserted into Porto's field.
4. Short prompts return quickly; descriptions of about 10 sentences generate reliably on the tested production hosting.

---

## Known limitations

- Generation of up to about **10 sentences** works reliably.
- Requests for **20 or more sentences** may fail with `Request time is out`. This is an infrastructure limitation: the web server/hosting may cut off long PHP requests even if increased timeouts are set in the plugin settings.
- If long texts are needed regularly, use one of the following approaches:
  - increase `fastcgi_read_timeout`, `proxy_read_timeout`, and `max_execution_time` on the hosting;
  - generate text in parts: introduction, main body, conclusion;
  - use a faster provider model;
  - implement a background generation queue separately if needed.
- The current version does not include an asynchronous queue: the request remains synchronous in order to preserve Porto's native logic and avoid breaking the theme interface.
- Support for providers other than Ollama is not implemented yet: the plugin name reflects the development direction, but only Ollama's OpenAI-compatible API has been tested.
- Disabling the "Force `stream: false`" field may produce an SSE stream that Porto cannot parse.

---

## Troubleshooting common errors

| Symptom | What to check |
|---|---|
| `Request Failed` immediately after clicking | The "Provider URL", "OpenAI request host", and "OpenAI address prefix" fields, port, model, and endpoint availability. |
| `cURL error 28` / timeout | The "PHP timeout, sec" and "Browser timeout, ms" fields, hosting limits, model speed. |
| `No content was generated` | Whether "Disable thinking" is enabled; whether "System prompt hint" is filled; whether the model returns a non-empty `content`. |
| Text is cut off in the middle of a word | Increase the "Max tokens override" field, but keep timeouts in mind. |
| 10 sentences work, 20 do not | Hosting or browser limit; reduce the volume per request or change server settings. |
| Porto reports a key error | The API Key field in Porto AI settings must be filled with any non-empty value; check the "API key stub" field. |
| Plugin settings are not applied | Check whether a corresponding constant is defined in `wp-config.php`, and refresh the page after saving. |
| JS timeout is not applied | The "Porto AI AJAX action" and "Admin screens" fields; the current screen must be `post` or `page`. |

---

## Security

- The provider may not require authorization by default. Do not expose its port to the public internet without a firewall, VPN, or protected tunnel.
- SSL verification is disabled by default (`sslverify = false`) so the plugin works with local and tunnel addresses. Use only trusted channels. If needed, enable verification in the plugin settings.
- After updating Porto or the provider, always re-test generation: the theme may change hook names, the AJAX action, or the request format.

---

## Project structure

| File | Purpose |
|---|---|
| `porto-ai-provider-bridge.php` | Main plugin file: only the Porto AI connection logic. |
| `admin/papb-admin.php` | Admin part: settings, validation, the "Porto AI Bridge" page, and the plugin list link. |
| `README.md` | Project description in English. |
| `README-ru.md` | Project description in Russian. |
| `.gitignore` | Excludes the internal `doc/` folder. |
| `doc/` | Internal working documents and the original `functions.php`; not included in the public repository. |
