# E-Commerce Loyalty System

A Laravel 12 + React 19 + TypeScript application that rewards users with achievements, badges, and cashback based on their purchase history.

---

## Prerequisites

| Requirement | Minimum Version |
|-------------|-----------------|
| PHP         | 8.2+            |
| Composer    | 2.x             |
| Node.js     | 22+             |
| npm         | 10+             |

The application uses **SQLite by default** - no database server is required (but on my local environment, I used a database server o. Lol).

You'll also need a Paystack **test** secret key for cashback disbursement (see [Cashback Disbursement](#cashback-disbursement) below) — set `PAYSTACK_SECRET_KEY` in `.env` yourself; it's intentionally left blank in `.env.example`.

---

## Running with Docker

The application is fully containerized. This is the easiest way to run it — no local PHP, Composer, or Node installation required.

`.env.docker` is gitignored (like `.env`), so it's yours to edit freely without it ever showing up as a git change. Before building, create it from the tracked template and set your own Paystack test secret key:

```bash
cp .env.docker.example .env.docker
# then edit .env.docker and set PAYSTACK_SECRET_KEY
```

The build fails with a clear message if `.env.docker` doesn't exist at all; if it exists but `PAYSTACK_SECRET_KEY` is blank, the build succeeds but cashback disbursement will fail at runtime — see [Cashback Disbursement](#cashback-disbursement).

```bash
docker compose up -d --build
```

This builds one image (PHP 8.3 + Node 22, with the frontend already compiled into it) and starts three services:

| Service | Description |
|---------|-------------|
| `app`   | Serves the app at **http://localhost:8000** via `php artisan serve`. Runs migrations automatically on first boot. |
| `queue` | Runs `php artisan queue:work`, processing the achievement/badge/cashback event chain. |
| `db`    | MySQL 8, with a persistent volume. |

Once the stack is healthy (`docker compose ps`), seed data and simulate orders the same way as a local setup, just prefixed with `docker compose exec`:

```bash
docker compose exec app php artisan db:seed
docker compose exec app php artisan app:make-order 1 6
docker compose exec app composer test
```

To stop and remove containers (add `-v` to also drop the database volume):

```bash
docker compose down
```

---

## Setup

Run the single setup command to install all dependencies, configure the environment, run migrations, and build the frontend:

```bash
composer setup
```

This executes the following steps in order:

```
composer install
cp .env.example .env
cp .env.docker.example .env.docker
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Both `.env` and `.env.docker` are only created if they don't already exist, so re-running `composer setup` never overwrites a key you've already set. The `.env.docker` copy is just a convenience for later — if you decide to try [Running with Docker](#running-with-docker), it's already there; Docker's own build doesn't run this script, so a Docker-only setup still needs its own `.env.docker` (see below).

### Using MySQL instead of SQLite

Edit `.env` after setup:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loyalty_e_commerce
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then re-run migrations:

```bash
php artisan migrate
```

---

## Running the Application

Start the Laravel server, Vite dev server, queue worker, and log viewer all at once:

```bash
composer dev
```

The application will be available at **http://localhost:8000**.

> **The queue worker is required.** All order event listeners (`PurchaseMadeListener`, `AchievementUnlockedListener`, `BadgeUnlockedListener`) implement `ShouldQueue` and are processed asynchronously. Without the queue worker running, no achievements, badge upgrades, or cashback credits will be applied after an order is created.

`composer dev` starts the queue worker automatically. If you run the server manually, start the worker in a separate terminal:

```bash
php artisan queue:listen --tries=1
```

---

## Seeding the Database

To create 5 test users and simulate 1–7 random orders per user:

```bash
php artisan db:seed
```

This fires the full order lifecycle for each seeded order. Run the queue worker before or alongside seeding so that events are processed as they are dispatched.

To reset and re-seed from scratch:

```bash
php artisan migrate:fresh --seed
```

---

## Simulating Orders

Orders are simulated via an Artisan command. This triggers the complete event chain — achievement checks, badge upgrades, and cashback credits — just as a real purchase would.

```bash
php artisan app:make-order {userId} {count=1}
```

| Argument | Description                                |
|----------|--------------------------------------------|
| `userId` | The ID of the user to simulate orders for (can be gotten from the database or you create a new user. See the end of this doc for how-to)  |
| `count`  | Number of orders to create (default: `1`)  |

**Examples:**

```bash
# Simulate 1 order for user with ID 1
php artisan app:make-order 1

# Simulate 10 orders for user with ID 3
php artisan app:make-order 3 10
```

Each order is created with a random amount between ₦1,000 and ₦50,000 and a status of `COMPLETED`.

> **The queue worker must be running** when you simulate orders, or in a separate terminal immediately after, for the achievement and cashback logic to execute.

---

## How the Loyalty System Works

### Event chain

When an order is created the following chain executes asynchronously through the queue:

1. An order is created, firing a `PurchaseMade` event.
2. `PurchaseMadeListener` picks it up and checks the user's order count against the achievement thresholds.
3. Every threshold newly crossed fires its own `AchievementUnlocked` event.
4. `AchievementUnlockedListener` handles each one, resolving the badge tier for the user's current achievement count.
5. If that's higher than their stored badge, a `BadgeUnlocked` event fires for every tier crossed (not just the highest).
6. `BadgeUnlockedListener` handles each of those, disbursing ₦300 cashback via Paystack (or your desired payment provider) per tier.

### Achievement thresholds

| Achievement          | Orders required |
|----------------------|-----------------|
| First Purchase       | 1               |
| Purchase Streak      | 5               |
| Mid Tier Shopper     | 10              |
| High Tier Shopper    | 15              |
| Loyal Customer       | 20              |

Each achievement can only be unlocked once per user.

### Badge tiers

Badges are determined by the number of achievements a user has unlocked:

| Badge    | Achievements required |
|----------|-----------------------|
| Unranked | 0                     |
| Bronze   | 1                     |
| Silver   | 2                     |
| Gold     | 3                     |
| Platinum | 5                     |

### Cashback

Every time a user's badge tier increases, **₦300** is disbursed to their bank account via Paystack (see [Cashback Disbursement](#cashback-disbursement)). A user who progresses through all badge tiers will receive a total of **₦1,200**.

The `payments` table (completed payments only) is the source of truth for how much has actually been disbursed to a user — see `User::totalDisbursed` / `totalDisbursedFormatted`.

---

## Cashback Disbursement

Cashback is a **real** Paystack transfer (`BadgeUnlockedListener` → `PaymentService` → `PaystackRepository`), not a simulation — it hits Paystack's actual API, just with a test secret key (`PAYSTACK_SECRET_KEY` in `.env`). Every user gets a bank account automatically on creation (`User::booted()`), seeded by default with Paystack's documented sandbox account (`0000000000` at Zenith Bank, `057`) — the one account guaranteed to resolve successfully in test mode, since Paystack validates account numbers against real NUBAN data even for test keys.

### Live account verification is rate-limited

Paystack allows only **3** live account-resolution calls (`/bank/resolve`) before rejecting further requests. To work around this:

- Every account successfully resolved is cached in the `resolved_bank_accounts` table (`PaystackRepository::verifyAccountNumber()` checks this table before ever calling the live API).
- To seed your own known-good account/bank pairs without spending any of the 3 live calls, add them to `database/seeders/ResolvedBankAccountSeeder.php` and run:

  ```bash
  php artisan db:seed --class=ResolvedBankAccountSeeder
  ```

  You're responsible for these being correct — nothing in the seeder is verified against Paystack. Use `php artisan bank:list` (below) to confirm a bank code first; listing banks isn't subject to the same rate limit.

### Attaching a real bank account (for manual/browser testing)

To see the full flow with your own real (or another real, resolvable test) bank account instead of the seeded default:

```bash
php artisan bank:list [search]
```

Lists Paystack-recognized banks and their codes, optionally filtered by name (e.g. `php artisan bank:list Zenith`).

```bash
php artisan bank:set-real-account {userId} {accountNumber} {bankCode}
```

Resolves the account (from cache if already known, otherwise live) and attaches it to the given user.

### Failure handling

If the Paystack transfer call throws, `PaymentService` logs the failure (reference, user, amount, error) and marks the payment `FAILED` rather than leaving it `PENDING` or silently marking it `COMPLETED`; the exception still propagates so the queued job is retried per Laravel's normal queue failure handling.

### Swapping the gateway

Nothing in `PaymentService` or `BadgeUnlockedListener` is Paystack-specific — they only depend on the `App\Interfaces\MoneyTransfer` contract. `PaystackRepository` is just the current implementation, bound in `AppServiceProvider::register()`:

```php
$this->app->bind(MoneyTransfer::class, PaystackRepository::class);
```

To use a different gateway, implement `MoneyTransfer` (`getProvider`, `prepareForTransfer`, `listBanks`, `verifyAccountNumber`, `transfer`) in a new class and change that one binding — no other application code needs to change.

`tests/Unit/BadgeUnlockedListenerTest::it_handles_disbursement_correctly` exercises the real gateway code path (not a `MoneyTransfer` mock) against faked HTTP responses, so it stays useful across a gateway swap without editing the test: it asks the currently-bound provider for its own sample responses via the optional `App\Interfaces\ProvidesGatewayFixtures` contract (`PaystackRepository::fakeHttpResponses()`). A new provider that hasn't implemented it yet just skips this one test cleanly, instead of failing against hardcoded, Paystack-specific mocks.

---

## Checking a User's Progress

Retrieve a user's full loyalty status — achievements unlocked, next achievements, badge, and total cashback disbursed — via the API:

```
GET /users/{userId}/achievements
```

**Example response:**

```json
{
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com"
  },
  "unlocked_achievements": ["First Purchase", "Purchase Streak"],
  "next_available_achievements": ["Mid Tier Shopper", "High Tier Shopper", "Loyal Customer"],
  "current_badge": "Bronze",
  "next_badge": "Silver",
  "remaining_to_unlock_next_badge": 1,
  "total_purchases": 6,
  "total_earnings": "₦ 300.00"
}
```

The loyalty dashboard at **http://localhost:8000/** lists all users and their current status.

---

## Running Tests

```bash
composer test
```

This clears the config cache, runs a PHP code style check, and then runs the full PHPUnit test suite.

---

## Available Commands

| Command                                   | Description                                      |
|-------------------------------------------|--------------------------------------------------|
| `composer setup`                          | First-time setup                                 |
| `composer dev`                            | Start all processes (server, queue, logs, Vite)  |
| `composer test`                           | Run the full test suite                          |
| `composer lint`                           | Auto-fix PHP code style                          |
| `php artisan db:seed`                     | Seed 5 users with random orders                  |
| `php artisan migrate:fresh --seed`        | Reset database and re-seed                       |
| `php artisan app:make-order {id} {count}` | Simulate orders for a user                       |
| `php artisan queue:listen --tries=1`      | Start the queue worker manually                  |
| `php artisan bank:list [search]`          | List Paystack-recognized banks and codes         |
| `php artisan bank:set-real-account {id} {accountNumber} {bankCode}` | Attach a resolved bank account to a user |
| `php artisan db:seed --class=ResolvedBankAccountSeeder` | Seed known-good account resolutions (bypasses Paystack's live rate limit) |
| `npm run dev`                             | Start Vite dev server with HMR                   |
| `npm run build`                           | Build production frontend assets                 |

> NB: To create an entirely new user, run the command below:

```bash
php artisan db:seed --class=UserSeeder
```