Nginx Configuration
===================

```nginx
server {
    listen 80 default_server;
    listen 443 ssl default_server;
    root /var/www/app.blocksedit.com/public;
    index index.php;

    error_log /var/log/nginx/app.blocksedit.com;
    access_log /var/log/nginx/app.blocksedit.com;

    ssl                 on;
    ssl_protocols       TLSv1 TLSv1.1 TLSv1.2;
    ssl_ciphers         "HIGH:!aNULL:!MD5 or HIGH:!aNULL:!MD5:!3DES";
    ssl_certificate     /etc/nginx/ssl/app.blocksedit.com.crt;
    ssl_certificate_key /etc/nginx/ssl/app.blocksedit.com.key;

    gzip on;
    gzip_min_length 1000;
    gzip_types application/x-javascript application/javascript text/css application/json application/xml text/yaml;

    add_header Access-Control-Allow-Origin *;

    location / {
            try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param APP_ENV "prod";
    }
}
```
