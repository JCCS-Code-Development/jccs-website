# jccs-website
Código correspondiente a la página web principal de JCCS Services.

## Quote form setup (one time, on the server)

The Contact page form posts to `api/quote.php`, which emails each request to the office
as noreply@jccs-services.com through Microsoft Graph (same app registration as the other JCCS apps).

1. Copy `api/config.sample.php` to `/home/<cpanel-user>/secure_backups/website-config.php`
   (outside `public_html`) and fill in the `GRAPH_*` values from jccs-projects' config.
2. Set `QUOTE_TO` to a real, monitored inbox.
3. Never commit the real config file.

## Content rules

- No client logos or client names without written permission.
- No staff names, cell numbers, or personal emails; use the general inbox.
- No street address or map; list the city and service area only.
- Use only our own or licensed photos. Remove GPS data and avoid faces, room numbers, and addresses.
- No sample or invented testimonials.

## Page URLs and local preview

Each page is a folder with its own `index.html`, so URLs have no `.html`:
`/`, `/about/`, `/services/`, `/projects/`, `/contact/`. Links use root paths (`/css/...`),
so preview through a local server instead of opening files directly:
`php -S 127.0.0.1:8000` from the repo root, then open http://127.0.0.1:8000/.
