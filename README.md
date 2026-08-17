# THUOC360 — Top Hub of US Online Coupons

**THUOC360** (`thuoc360.com`) is a Laravel 10 + MySQL website for U.S. coupon codes and discount deals.

**THUOC** = **T**op **H**ub of **US** **O**nline **C**oupons.

## Features

- Coupon & deal listings, stores, categories, search
- Static pages: About, Contact, Privacy, Terms, Cookies, Disclaimer
- Admin panel for content management

## Pages (Google / publisher ready)

| URL | Page |
|-----|------|
| `/about-us` | About THUOC360 |
| `/contact-us` | Contact form |
| `/privacy-policy` | Privacy Policy (CCPA/CPRA) |
| `/terms-of-service` | Terms of Service |
| `/cookie-policy` | Cookie Policy |
| `/disclaimer` | Disclaimer & FTC affiliate disclosure |

## Setup

## Production deploy

See [deploy/DEPLOY.md](deploy/DEPLOY.md) and [deploy/nginx.conf](deploy/nginx.conf).

```bash
php artisan migrate:fresh --seed
php artisan serve
```

## Admin

- `/login` — `admin@thuoc360.com` / `password`

## Site config

Edit `config/site.php` or `.env` for `SITE_DOMAIN`, `SITE_URL`, etc.

Contact emails and Twitter handle default from `SITE_DOMAIN` (e.g. `contact@yourdomain.com`).

Scan API and AI writing models: **Admin → Integrations** (stored in the database, not `.env`).

## License

MIT
