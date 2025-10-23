# Twitch User Data System

## Overview

This document describes the Twitch user data ingestion and management system that has been implemented. The system allows your C# application to POST user data and stats to Laravel, while also supporting Twitch OAuth authentication for users who want to sign in to the web application.

## Database Structure

### Tables

#### `users`
Laravel's standard users table for authenticated app users.
- `id`, `name`, `email`, `password` (nullable), `remember_token`, `timestamps`
- Only contains users who have actually signed in via Twitch OAuth

#### `twitch_users`
Registry of all known Twitch users (from C# posts and OAuth sign-ins).
- `id` (PK)
- `twitch_id` (unique) - The Twitch user ID
- `user_id` (nullable FK to users.id) - Set when user signs in
- **From users.dat:**
  - `name`, `display_name`, `role`, `subscribed`, `type`, `present`, `last_active`
- **From OAuth (enriched on sign-in):**
  - `profile_image_url`, `broadcaster_type`, `description`, `twitch_created_at`, `email`
- `timestamps`

#### `twitch_user_stats`
Arbitrary stats for Twitch users.
- `id` (PK)
- `twitch_user_id` (FK to twitch_users.id)
- `name` (stat name like 'points', 'watchtime', etc.)
- `value` (text/JSON)
- `last_write` (datetime)
- `timestamps`
- UNIQUE constraint on (`twitch_user_id`, `name`)

## API Endpoints

### POST /api/twitch/users
Bulk import/update Twitch users from users.dat.

**Request Body:**
```json
{
  "users": [
    {
      "id": "112699727",
      "name": "mychoppaeats",
      "display": "mychoppaeats",
      "role": 4,
      "subscribed": true,
      "type": "twitch",
      "present": true,
      "lastActive": "2025-10-24T01:00:00.1220985+02:00"
    }
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "imported": 1,
  "updated": 0,
  "total": 1
}
```

### POST /api/twitch/stats
Bulk import/update Twitch user stats from globals.db.

**Request Body:**
```json
{
  "stats": [
    {
      "userId": "112699727",
      "platform": "twitch",
      "name": "points",
      "value": 3000,
      "lastWrite": "2025-10-21T18:30:44.8350000Z"
    },
    {
      "userId": "112699727",
      "platform": "twitch",
      "name": "watchtime",
      "value": 1314780,
      "lastWrite": "2025-07-18T23:49:25.5890000Z"
    }
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "imported": 2,
  "updated": 0,
  "total": 2
}
```

## Data Flow

### 1. C# Application Posts Users (users.dat)
- POST to `/api/twitch/users`
- Creates or updates `twitch_users` records
- Fills in basic profile information

### 2. C# Application Posts Stats (globals.db)
- POST to `/api/twitch/stats`
- Creates `twitch_users` record if it doesn't exist (with just twitch_id)
- Upserts stats into `twitch_user_stats`

### 3. User Signs In via Twitch OAuth
- User clicks "Sign in with Twitch"
- OAuth flow completes at `/auth/twitch/callback`
- System finds or creates `twitch_users` record by twitch_id
- Enriches `twitch_users` with OAuth data (avatar, description, etc.)
- Creates `users` record (or finds existing by email)
- Links `twitch_users.user_id` to `users.id`
- User is authenticated and redirected to dashboard

## Eloquent Models & Relationships

### User Model
```php
// Relationship
$user->twitchUser(); // HasOne relationship

// Helper methods
$user->stat('points');      // Get a specific stat value
$user->stat('watchtime');   // Returns the stat value or null
```

### TwitchUser Model
```php
// Relationships
$twitchUser->user();        // BelongsTo User
$twitchUser->stats();       // HasMany TwitchUserStat

// Helper methods
$twitchUser->getStat('points');                    // Get stat value
$twitchUser->setStat('points', 5000, now());      // Set/update stat
```

### TwitchUserStat Model
```php
// Relationship
$stat->twitchUser();        // BelongsTo TwitchUser
```

## Usage Examples

### In Blade Templates
```blade
{{-- Access user's Twitch display name --}}
{{ auth()->user()->twitchUser->display_name }}

{{-- Access user's stats --}}
Points: {{ auth()->user()->stat('points') }}
Watchtime: {{ auth()->user()->stat('watchtime') }}

{{-- Access avatar --}}
<img src="{{ auth()->user()->twitchUser->profile_image_url }}" />
```

### In Controllers
```php
// Get authenticated user's points
$points = auth()->user()->stat('points');

// Get all stats for a user
$stats = auth()->user()->twitchUser->stats;

// Query Twitch users with stats
$topUsers = TwitchUser::whereHas('stats', function($query) {
    $query->where('name', 'points')
          ->where('value', '>', 1000);
})->with('stats')->get();
```

### Building Leaderboards
```php
// Top users by points
$leaderboard = TwitchUser::join('twitch_user_stats', 'twitch_users.id', '=', 'twitch_user_stats.twitch_user_id')
    ->where('twitch_user_stats.name', 'points')
    ->orderByDesc('twitch_user_stats.value')
    ->select('twitch_users.*', 'twitch_user_stats.value as points')
    ->limit(10)
    ->get();
```

## Testing

Comprehensive test coverage has been implemented:

- **TwitchUserImportTest** - Tests bulk user import endpoint
- **TwitchStatsImportTest** - Tests bulk stats import endpoint
- **TwitchOAuthTest** - Tests OAuth flow, user linking, and stat access

Run tests:
```bash
ddev php artisan test
```

Run specific test suite:
```bash
ddev php artisan test --filter=TwitchUserImportTest
ddev php artisan test --filter=TwitchStatsImportTest
ddev php artisan test --filter=TwitchOAuthTest
```

## Migrations

To set up the database:

```bash
# Run migrations
ddev php artisan migrate

# Rollback if needed
ddev php artisan migrate:rollback
```

## Key Benefits

1. **Separation of Concerns**: Twitch identity data is separate from Laravel user authentication
2. **Pre-registration Stats**: Stats can be posted for users who haven't signed in yet
3. **Flexible Stats**: New stat types can be added without database migrations
4. **Efficient Queries**: Proper indexing and relationships for fast lookups
5. **Leaderboard Ready**: Easy to query and sort users by any stat
6. **OAuth Enrichment**: Profile data is automatically enriched when users sign in

## Notes

- All Twitch users exist in `twitch_users`, but only signed-in users exist in `users`
- Stats are preserved even if a user never signs in
- When a user signs in, their existing stats are automatically linked
- The system handles duplicate prevention (won't create multiple records for same Twitch ID)
- JSON values in stats are automatically encoded/decoded
- **Edge case handled**: If a user signs in via OAuth before any C# data is posted, the system creates a `twitch_users` record automatically

