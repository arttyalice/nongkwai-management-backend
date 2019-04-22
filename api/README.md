# Nongkwai Management API Service

## use docker for host apiservice
1. pull xampp image and bind your api volumn to /www on container.
```
docker run --name myXampp -p 41061:22 -p 41062:80 -d -v ~/path/to/api_root:/www tomsik68/xampp
```
1. enter http://localhost:41262/api you will see your 404 page.Btw enjoy your api
