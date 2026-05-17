# PHPFrame

A simple PHP micro-framework with an Eloquent-style ORM.

## Quick Start

1) Copy `.env.example` to `.env` and configure database:
```
cp .env.example .env
```

2) Start dev server:
```
php -S 0.0.0.0:8080 -t public public/index.php
```

## Directory

- Entry: `public/index.php`
- Framework: `src/`
- Views: `views/`
- Config: `config/`
- Logs: `logs/`
