uva-stalker ![Telegram logo](http://i.imgur.com/vvekNMU.png) ![UVa logo](http://i.imgur.com/uyCdP6k.jpg)
===================

[![License](http://img.shields.io/:license-mit-blue.svg)](http://doge.mit-license.org) [![GitHub release](https://img.shields.io/github/release/david-perez/uva-stalker.svg)](https://github.com/david-perez/uva-stalker/releases/tag/v1.0)

**uva-stalker** is a Telgram bot to stalk submissions from [UVa Online Judge](https://uva.onlinejudge.org) users.

You simply tell it which users you want to follow, and it will send you notifications every time a user makes a new submission.

An instance of the bot is already running and can be interacted with by chatting to [UVaStalkerBot](https://telegram.me/UVaStalkerBot), or you can clone this repo and set up the bot in your own server.

![UVaStalker screenshot 1](http://i.imgur.com/9M4fYOM.png)  ![UVaStalker screenshot 2](http://i.imgur.com/XMXjuOZ.png)

----------

Installation
-------------

Follow these instructions to set up the bot in a development environment and test that it works correctly. You can then deploy it to a server.

#### Creating your own Telegram bot

Chat with [BotFather](https://telegram.me/botfather) to generate an authorization token for your new bot. Head over to the [Telegram documentation](https://core.telegram.org/bots) for detailed instructions if you're new to creating bots.

#### Local installation

uva-stalker uses the [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) library in a [Laravel](https://laravel.com/) environment using a (tested in Postgres) database. A suitably configured development environment has already been set up using Docker containers thanks to [laradock](https://github.com/laradock/laradock).

After cloning the repo, you may configure the environement by typing in

```
$ cd laradock-5.0.3
$ cp env-example .env
```

And changing the environment variables contained in the `.env` file inside the `laradock-*` folder. For example, using a Postgres database, configure these variables

```
POSTGRES_DB=default
POSTGRES_USER=default
POSTGRES_PASSWORD=default
```

as needed. You should also fill in your Docker Host IP.

```
# Replace with your Docker Host IP (will be appended to /etc/hosts)
DOCKER_HOST_IP=10.0.75.1
```

Start up the environment and find out the `container id` of the `workspace` instance using `docker ps`.

```
$ docker-compose up -d nginx postgres
```

Install the project dependencies using `docker exec -t <container_id> composer install`. 

Configure the database credentials in the project folder in a `.env` file, as well as your `APP_KEY` (or use `php artisan key:generate` to fill it in). You must also fill in the variable `TELEGRAM_BOT_TOKEN` with your authorization token. You may then install the migrations and execute them to build the database tables.

```
$ cd ..
$ cp .env.example .env
$ nano .env
... (edit values)
$ docker exec -t <container_id> php artisan migrate:install
$ docker exec -t <container_id> php artisan migrate
```

Finally, run `daemon.php` to listen for messages sent to your bot. This script uses [long-polling](https://en.wikipedia.org/wiki/Push_technology#Long_polling) to request updates every second from the [Telegram Bot Api](https://core.telegram.org/bots/api) using the [getUpdates](https://core.telegram.org/bots/api#getupdates) method. It will forward the payloads to the Laravel application, which will process them under the `/api/<authorization_token` route, where `<authorization_token>` is the authorization token which BotFather provided you when you created your bot.

```
$ docker exec -t <container_id> php daemon.php
Starting bot with config {"id":356029581,"first_name":"UVaStalker","username":"UVaStalkerBot"}

Start polling Telegram api (will poll for updates every 1000 ms)

Poll took 0 ms (slept 1000000 µs)
Poll took 0 ms (slept 1000000 µs)
...
```

Any incoming updates will be printed to the console.

#### Deployment to a server

If the bot works normally using long polling, you may want to deploy it to a server and set a webhook so that incoming updates are pushed to you. By default, the Laravel application is listening for updates on `/api/<authorization_token`, so you should set up the webhook at

```
https://your-domain-name.com/api/<authorization_token>
```

Note that you need a domain name with a valid SSL certificate in order to set up the webhook.

If you have a domain name but you don't have a valid SSL certificate, a very easy way to get one is from [Let's Encrypt](https://letsencrypt.org/), and install it on a [Caddy](https://caddyserver.com/) web server. Laradock makes this a cinch, since Caddy is included with it. To set it up, edit the Caddyfile inside the `laradock-5.0.3/caddy` folder. Replace the `0.0.0.0:80` in the first line with your domain name

```
https://your-domain-name.com
root /var/www/public
...
```

and uncomment `#tls self_signed` at the bottom of the file, replacing `self_signed` with the email you registered your domain with.

```
# Change the first list to listen on port 443 when enabling TLS
tls youremail@gmail.com
...
```

All set up! You may now launch Caddy and verify that you can reach your web server via `https://your-domain-name.com`.

```
$ cd ..
$ docker-compose up caddy
Starting laradock503_applications_1
laradock503_workspace_1 is up-to-date
laradock503_php-fpm_1 is up-to-date
laradock503_caddy_1 is up-to-date
Attaching to laradock503_caddy_1
caddy_1               | Activating privacy features... done.
caddy_1               | https://your-domain-name.com
caddy_1               | http://your-domain-name.com
```

If all is good, launch the containers in detached mode.

```
$ docker-compose down
$ docker-compose up -d caddy postgres
```

All that's left to do is set up your webhook. You may do so using the [`setWebhook()`](https://core.telegram.org/bots/api#setwebhook) method of the Bot Api. Telegram has a [fantastic guide on webhooks](https://core.telegram.org/bots/webhooks) for detailed instructions.

