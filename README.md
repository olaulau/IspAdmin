# IspAdmin
IspConfig additionnal admin features

- websites check
    - WhoIs (nameservers correspond to config, for IspConfig DNS feature use)
    - DNS (resolved address correspond to webstite's server's address configured
    - SSL (certificate is still valid, comes from let's encrypt)
    - HTTP (website's home page respond with a valid status)
    - PHP (version choosen is stilll supported)

![websites](doc/websites.png)

## prerequisite
- must create a remote user in IspConfig panel under "System > Remote users"
- requires php-intl installed before executing 'composer install'

## installation
```
git clone https://github.com/olaulau/IspAdmin
cd IspAdmin
composer install

cd conf
cp 	tech.dist.ini tech.ini
vim tech.ini ## fill in values
cd ..

crontab -e
	php index.php ssl_auto_renew
```
