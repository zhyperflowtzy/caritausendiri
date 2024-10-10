RewriteEngine on
RewriteCond %{HTTP_HOST} ^luzentistore.com [NC,OR]
RewriteCond %{HTTP_HOST} ^www.luzentistore.com [NC]
RewriteRule ^(.*)$ https://www.mrwebzone.com/$1 [L,R=301,NC]

# BEGIN LSCACHE
## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##
<IfModule LiteSpeed>
RewriteEngine on
CacheLookup on
RewriteRule .* - [E=Cache-Control:no-autoflush]
RewriteRule \.litespeed_conf\.dat - [F,L]

### marker ASYNC start ###
RewriteCond %{REQUEST_URI} /wp-admin/admin-ajax\.php
RewriteCond %{QUERY_STRING} action=async_litespeed
RewriteRule .* - [E=noabort:1]
### marker ASYNC end ###

### marker CACHE RESOURCE start ###
RewriteRule wp-content/.*/[^/]*(responsive|css|js|dynamic|loader|fonts)\.php - [E=cache-control:max-age=3600]
### marker CACHE RESOURCE end ###

### marker DROPQS start ###
CacheKeyModify -qs:fbclid
CacheKeyModify -qs:gclid
CacheKeyModify -qs:utm*
CacheKeyModify -qs:_ga
### marker DROPQS end ###

</IfModule>
## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##
# END LSCACHE
# BEGIN NON_LSCACHE
## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##
## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##
# END NON_LSCACHE

# BEGIN WordPress
# Las directivas (líneas) entre «BEGIN WordPress» y «END WordPress» son
# generadas dinámicamente y solo deberían ser modificadas mediante filtros de WordPress.
# Cualquier cambio en las directivas que hay entre esos marcadores serán sobrescritas.
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# END WordPress
