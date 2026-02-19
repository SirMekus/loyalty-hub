# E-Commerce Loyalty System

A Laravel 12 + React 19 + TypeScript application that rewards users with achievements, badges, and wallet cashback based on their purchase history.

---

## Prerequisites

| Requirement | Minimum Version |
|-------------|-----------------|
| PHP         | 8.2+            |
| Composer    | 2.x             |
| Node.js     | 22+             |
| npm         | 10+             |

The application uses **SQLite by default** - no database server is required (but on my local environment, I used a database server o. Lol).

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
php artisan key:generate
php artisan migrate
npm install
npm run build
```

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

```
Order created
  └─► PurchaseMade event fired
        └─► PurchaseMadeListener
              └─► Checks order count against achievement thresholds
                    └─► AchievementUnlocked event fired (for each new achievement)
                          └─► AchievementUnlockedListener
                                └─► Resolves new badge tier from achievement count
                                      └─► BadgeUnlocked event fired (if badge changed)
                                            └─► BadgeUnlockedListener
                                                  └─► Credits ₦300 cashback to wallet
```

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

Every time a user's badge tier increases, **₦300** is credited to their wallet. A user who progresses through all badge tiers will receive a total of **₦1,200** in cashback.

---

## Checking a User's Progress

Retrieve a user's full loyalty status — achievements unlocked, next achievements, badge, and wallet balance — via the API:

```
GET /api/users/{userId}/achievements
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
  "next_achievements": ["Mid Tier Shopper", "High Tier Shopper", "Loyal Customer"],
  "current_badge": "BRONZE",
  "next_badge": "SILVER",
  "achievements_to_next_badge": 1,
  "total_purchases": 6,
  "wallet_balance": "₦300.00"
}
```

The loyalty dashboard at **http://localhost:8000/loyalty** lists all users and their current status.

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
| `npm run dev`                             | Start Vite dev server with HMR                   |
| `npm run build`                           | Build production frontend assets                 |

> NB: To create an entirely new user, run the command below:

```bash
php artisan db:seed --class=UserSeeder
```