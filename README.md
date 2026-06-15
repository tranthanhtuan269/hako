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

Edit `config/site.php` or `.env`:

```
SITE_DOMAIN=thuoc360.com
SITE_URL=https://thuoc360.com
SITE_CONTACT_EMAIL=contact@thuoc360.com
SITE_PRIVACY_EMAIL=privacy@thuoc360.com
```

## License

MIT
