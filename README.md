# ZXKAI Learning Portal

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

Import this repository into Vercel. `vercel.json` selects the Container runtime and
`Dockerfile.vercel` provides the app image, even if another framework was previously
selected for the project. See [VERCEL_DEPLOY.txt](./VERCEL_DEPLOY.txt) for details.

### Other PHP hosting

Upload all files to your PHP hosting.
