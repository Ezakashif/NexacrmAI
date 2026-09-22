# NexaCRM AI — architecture notes

## Purpose

This fork adds an **optional AI assist** path for sales users without changing the core CRM data model. Providers are pluggable; the default binding is a Null client so installs stay offline-safe.

## Components

| Piece | Path |
|---|---|
| Config / feature flag | `config/ai.php`, `.env.example` (`AI_*`) |
| Contract | `app/Contracts/Ai/AiClient.php` |
| Null / OpenAI / Anthropic | `app/Services/Ai/{Null,OpenAi,Anthropic}Client.php` |
| Lead coaching use-case | `app/Services/Ai/LeadAssistService.php` |
| Container bindings | `app/Providers/AiServiceProvider.php` |
| HTTP endpoint | `POST /leads/{lead}/ai/suggest` → `LeadAiController@suggest` (`leads.ai.suggest`) |
| UI scaffold | `resources/views/leads/partials/ai-assist.blade.php` |
| Permission | `ai_assist.leads` in `config/permissions.php` |

## Behaviour

1. `AI_ENABLED=false` (default) → `NullAiClient` is bound; UI shows a disabled scaffold.
2. `AI_ENABLED=true` + `AI_DEFAULT_PROVIDER=openai|anthropic` + API key → live completions.
3. `LeadAssistService` sends structured lead context and expects JSON: `summary`, `next_steps`, `talk_track`.
4. Authorization uses `LeadPolicy::aiAssist` (`ai_assist.leads` + ownership / view-all rules).

## Extending

- Add a new provider class implementing `AiClient`, then register it in `AiServiceProvider`.
- Add further use-cases (task drafting, email polish) as thin services that reuse `AiClient`.
- Do not call providers from Blade; keep HTTP + services as the boundary.

## Safety

- No API keys are committed; use environment secrets only.
- Throttle: `leads.ai.suggest` is rate-limited in `routes/web.php`.
- Treat model output as suggestions, not CRM writes — this scaffold does not auto-update lead fields.
