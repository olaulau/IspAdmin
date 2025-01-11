# IspAdmin
IspConfig additionnal admin features  
  
- websites check  
	- list vhost
		- show related aliases & subdomains
	- modules
		- WhoIs (nameservers correspond to config, for IspConfig DNS feature use)  
		- DNS (resolved address correspond to webstite's server's address configured)  
		- SSL (certificate is still valid, comes from let's encrypt)  
		- HTTP (website's home page respond with a valid status)  
		- PHP (version choosen is still supported)  
  
![websites](doc/websites.png)

- E-mails
	- mailbox bulk creation
	
- DNS
	- bulk edit
		- list all DNS entries, with filtering options
		- multi-entry select
		- edit name / data of selected entries
  
## requirements
- PHP 8.x (8.3 compatible)
	- php-curl
	- php-intl
  
## installation
- create an IspConfig remote user in the panel : "System > Remote users"  
```
git clone https://github.com/olaulau/IspAdmin
cd IspAdmin
composer install

cd conf
cp tech.dist.ini tech.ini
vim tech.ini ## fill in values
cd ..

crontab -e
	php index.php ssl_auto_renew
```
