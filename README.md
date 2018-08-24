# mangoplus
New client for TrentBarton's Mango Card
This is a work in progress.

## Load on to your server
Lets get mangoplus running.

First, it is expected that you have server with PHP and composer installed. You also need SSH access.

Next, create a directory and install all the packages through these commands.

```
mkdir mangoplus
cd mangoplus
mkdir app
composer require --dev behat/mink
composer require behat/mink-goutte-driver

```

Okay, now that's done, upload dev.php to your mangoplus directory and index.htm to you app directory.

Visit the app page and open the console to make sure everything is running right.

Bam. Build on this as you wish.
