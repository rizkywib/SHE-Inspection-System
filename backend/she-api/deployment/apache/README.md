# Apache deployment for EOBLAS10

The site on port 82 must use Laravel's `public` directory as its
`DocumentRoot`. The included virtual host assumes the repository is deployed
to:

```text
/var/www/she-inspection-system
```

If the actual deployment path is different, update both paths in
`eoblas10-she-inspection.conf`.

Run these commands on EOBLAS10 with administrator access:

```bash
# Port 82 is already listening on EOBLAS10. If this is a fresh server,
# add "Listen 82" once to /etc/apache2/ports.conf.
sudo a2enmod rewrite
sudo cp deployment/apache/eoblas10-she-inspection.conf /etc/apache2/sites-available/
sudo a2ensite eoblas10-she-inspection.conf
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
php artisan optimize:clear
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Then verify:

```bash
curl -I http://eoblas10.ecogreenoleo.co.id:82/
curl -I http://eoblas10.ecogreenoleo.co.id:82/api/auth/login
```

The first request should return `200`. The second may return `405` for a GET
request, which confirms that Laravel is handling the API route.
