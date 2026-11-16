# Rozgar Learning Portal v4.0

## Files
- `index.php` — Redirect to dashboard
- `dashboard.php` — Main app (auto-login with master token)
- `login.php` — Manual login page (/login)
- `admin.php` — Admin panel to view user logins
- `api.php` — Secure server-side API proxy
- `config.php` — Config (token, credentials)
- `.htaccess` — Security rules
- `data/users.json` — Login logs (auto-created)

## Admin Panel
URL: `/admin.php`
User: admin
Pass: Admin@Rozgar2024

## Deploy
### Vercel

Import this repository into Vercel. Vercel detects the root `Dockerfile` and deploys
the app with its Container runtime; the Dockerfile serves HTTP on the default port 80.
See [VERCEL_DEPLOY.txt](./VERCEL_DEPLOY.txt) for details and storage limitations.

### Other PHP hosting

Upload all files to your PHP hosting.
