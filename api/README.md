# Nongkwai Management API Service

## use docker for host apiservice
1. pull xampp image and bind your api volumn to /www on container.
```
docker run --name myXampp -p 41061:22 -p 41062:80 -d -v ~/path/to/api_root:/www tomsik68/xampp
```

2. then in your index.php re-config your upload_url to your specific url for host static file

3. enter http://localhost:41262/www/api you will see your 404 page.Btw enjoy your api

## use local xampp
1. install xampp on your computer
2. move api directory to xampp public directory
3. use php to run composer.phar
```php composer.phar install ```
or install composer and run
```composer install ```
4. then in your index.php re-config your upload_url to your specific url for host static file
5. enter your url that match to xampp you will see your 404 page.Btw enjoy your api
