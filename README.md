# Prime Property Buys

Seller website and private back office for Prime Property Buys (Anthony Jaamal Jones, Valdosta / Lowndes County, Georgia; part of Vibe Virtue Solutions).

The public site asks homeowners for a cash offer. A separate, unlisted buyer page collects buy boxes. Admin is password-protected.

Stack: PHP 8 and MySQL (PDO). No build step. SQLite is available for local testing only.

## Local preview

```bash
cp config.sample.php config.php
```

For a quick local run, set `db.driver` to `sqlite`, `debug` to `true`, `mail.enabled` to `false`, and `business.url` to `http://127.0.0.1:8080`. Set `security.setup_key` to a random string of at least 12 characters (the sample value is rejected).

```bash
php -S 127.0.0.1:8080 router.php
```

Open the site, then open `/admin/setup` and create the admin login. `config.php` is not committed.

## Hostinger

1. In hPanel, create a MySQL database and user. Note the database name, user, and password.
2. Open phpMyAdmin and import `schema.sql`.
3. Upload the project files into the domain document root (`public_html` for the main domain). Do not upload `.git`, `config.php` from your laptop, or anything in `storage/*.sqlite`.
4. On the server, copy `config.sample.php` to `config.php`.
5. In `config.php`, set the MySQL driver, host, database name, user, and password. Keep the public email as `info@primepropertybuys.com`. Point `notification_email` somewhere else only if lead alerts should not land in that mailbox.
6. Set `security.setup_key` to a long random string. Generate one with `php -r "echo bin2hex(random_bytes(16));"`.
7. Leave the phone as `(TBD)`, the legal entity as `[LEGAL ENTITY]`, and the booking label as `[BOOKING TOOL]` until you have the real values. When you do, set `phone_display`, `phone_tel` (digits, like `+12295550199`), `legal_entity`, and `booking_url` (an `https://` Calendly or Cal.com link).
8. Turn on the free SSL certificate and confirm the site loads over HTTPS.
9. Visit `https://primepropertybuys.com/admin/setup` immediately and create the admin account. After that, the setup form is gone.
10. Submit a test offer and confirm it appears under Pipeline. If `mail.enabled` is true, Hostinger must allow `mail()` from `info@primepropertybuys.com`. A failed email does not drop the lead.

If login will not stay signed in on HTTPS, set `security.trust_proxy` to `true`. Leave it false otherwise.

Photo uploads accept JPG, PNG, WEBP, and HEIC up to 10 MB each, 10 photos. If a large upload fails, raise `upload_max_filesize` and `post_max_size` above 12M in the host PHP settings.

Lost the admin password: in phpMyAdmin run `DELETE FROM admin_users;`, then visit `/admin/setup` again.

## Pages

Public: `/`, `/get-offer`, `/how-it-works`, `/faq`, `/about`, `/contact`, town pages (`/valdosta`, `/hahira`, `/lake-park`, `/remerton`, `/lowndes-county`), `/privacy`, `/terms`.

Buyer list (not linked, `noindex`, disallowed in `robots.txt`, omitted from `sitemap.xml`): `/investors`.

Deal sheets created in admin use `/d/{token}` and expire. They omit the seller’s name, phone, email, and reason.

Admin: `/admin`.

## Confidentiality

Seller pages and seller emails do not mention funds or who would purchase after a contract is signed. The required disclosure appears in the footer, the terms, and the “Who closes?” answer:

> We may purchase directly or partner with or assign our purchase contract to other buyers.

Do not add a link to `/investors` in the seller navigation, footer, or emails.

## Placeholders

Phone `(TBD)`, legal entity `[LEGAL ENTITY]`, and booking tool `[BOOKING TOOL]` are visible on purpose. Replace them in `config.php` when the real values exist. Do not invent a phone number, a mailing address, review counts, or a closing history.
