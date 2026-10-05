# AI Agent Posting on Canonizer — Implementation & Architecture Plan

## Context

**Issue**: [#1385](https://github.com/the-canonizer/canonizer-3.0-api/issues/1385)

Users should be able to register AI agents that act on their behalf on Canonizer. An agent is a **real user** in the `person` table, linked to its parent (human owner) via `parent_user_id`. The agent has its own email, password, Passport token, and nickname — it authenticates and operates exactly like a regular user. Restrictions can be added later.

---

## Architecture

```
Human User (person table, id=5)
    │
    ├── Creates bot via POST /api/v3/ai-agents
    │
    └── Bot User (person table, id=200, parent_user_id=5, type='bot')
            │
            ├── Has own email: mybot@canonizer-bot.local
            ├── Has own password (auto-generated)
            ├── Has own Passport token (standard OAuth)
            ├── Has own nickname: "[BOT] MyResearchBot"
            │
            └── Full access — creates topics, camps, statements,
                support, threads, replies like any user
```

No custom middleware. No custom tokens. The bot IS a user.

---

## Phase 1: Database Migration

### Migration: `add_bot_fields_to_person_table`

Add to `person` table:

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `parent_user_id` | `integer`, nullable | NULL | FK → `person.id`. NULL = human user. Set = bot owned by that user. |
| `bot_name` | `string(100)`, nullable | NULL | Display name of the bot (e.g., "MyResearchBot") |

**Why `parent_user_id` on `person`?** It's the simplest way to link bot to owner. A human user has `parent_user_id = NULL`. A bot has `parent_user_id = owner's id`. Query all bots for a user: `WHERE parent_user_id = ?`.

**Why not a separate table?** The bot needs to authenticate via Passport, have nicknames, create content — all of which already work with the `person` table. A separate table would require duplicating all this logic.

**`type` column** — The `person` table already has a `type` column (used for 'admin'). We'll use `type = 'bot'` for bots.

---

## Phase 2: Model Changes

### Modify `User.php`
- Add `'parent_user_id'`, `'bot_name'` to `$fillable`
- Add relationship: `owner()` → `belongsTo(User::class, 'parent_user_id')`
- Add relationship: `bots()` → `hasMany(User::class, 'parent_user_id')`
- Add helper: `isBot(): bool` → `return $this->type === 'bot'`

### Modify `Nickname.php`
- Add static method `createBotNickname($userId, $botName)` — creates nickname prefixed with `[BOT]`

---

## Phase 3: Controller

### New Controller: `AiAgentController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `register(Request)` | `POST /ai-agents` | Create a bot user + nickname + Passport token |
| `list(Request)` | `GET /ai-agents` | List current user's bots |
| `show(Request, $id)` | `GET /ai-agents/{id}` | Show single bot details |
| `update(Request, $id)` | `PUT /ai-agents/{id}` | Update bot name/description |
| `destroy(Request, $id)` | `DELETE /ai-agents/{id}` | Deactivate bot (`is_active=0`) |
| `regenerateToken(Request, $id)` | `POST /ai-agents/{id}/regenerate-token` | Revoke old token, issue new one |

### `register()` flow:
1. Validate: `name` (required, max 100)
2. Check bot limit per user (default 5)
3. Create user in `person` table:
   - `email`: `{sanitized_name}-{random}@canonizer-bot.local`
   - `password`: random 32 chars, bcrypt hashed
   - `first_name`: bot name
   - `last_name`: '[BOT]'
   - `type`: 'bot'
   - `parent_user_id`: authenticated user's ID
   - `bot_name`: request name
   - `status`: 1 (active)
   - `is_active`: 1
4. Create nickname: `[BOT] {name}`
5. Generate Passport token for the bot user
6. Return bot details + plaintext token

### `regenerateToken()` flow:
1. Verify ownership (`parent_user_id` matches auth user)
2. Revoke all existing Passport tokens for the bot
3. Issue new Passport token
4. Return new token

---

## Phase 4: Routes

Add inside the `auth` middleware group in `routes/web.php`:

```php
// AI Agent Management
$router->post('ai-agents', 'AiAgentController@register');
$router->get('ai-agents', 'AiAgentController@list');
$router->get('ai-agents/{id}', 'AiAgentController@show');
$router->put('ai-agents/{id}', 'AiAgentController@update');
$router->delete('ai-agents/{id}', 'AiAgentController@destroy');
$router->post('ai-agents/{id}/regenerate-token', 'AiAgentController@regenerateToken');
```

---

## Phase 5: Validation

Add to `ValidationRules.php`:
- `getAgentRegisterValidationRules()`: name (required, string, max:100)

Add to `ValidationMessages.php`:
- `getAgentRegisterValidationMessages()`

---

## Phase 6: Tests

### Test file: `tests/AiAgentApiTest.php`
- Register bot → 201, token returned, user created with type='bot'
- Register bot → nickname created with `[BOT]` prefix
- List bots → only shows current user's bots
- Show bot → verify ownership
- Update bot name
- Deactivate bot
- Regenerate token → old token invalid, new token works
- Bot limit enforcement
- Bot can login with its own credentials
- Bot can create topic/camp/statement/thread/reply like regular user

---

## Implementation Order

```
1. Migration: add parent_user_id, bot_name to person table
2. Model: Update User.php (fillable, relationships, isBot)
3. Model: Update Nickname.php (createBotNickname)
4. Validation: Add rules/messages
5. Controller: Create AiAgentController
6. Routes: Add agent management routes
7. Tests
```

---

## File Inventory

### New Files (2)
1. `database/migrations/2026_04_22_000001_add_bot_fields_to_person_table.php`
2. `app/Http/Controllers/AiAgentController.php`

### Modified Files (4)
1. `app/Models/User.php` — Add fillable, relationships, isBot()
2. `app/Models/Nickname.php` — Add createBotNickname()
3. `routes/web.php` — Add agent routes
4. `app/Http/Request/ValidationRules.php` — Add agent rules
5. `app/Http/Request/ValidationMessages.php` — Add agent messages

---

## Verification

1. `php artisan migrate` — verify `parent_user_id` and `bot_name` columns added
2. Register bot via API → verify user created in `person` table with `type='bot'`
3. Use bot's token to create a topic → works like regular user
4. List bots → only shows owner's bots
5. Deactivate bot → can no longer authenticate
