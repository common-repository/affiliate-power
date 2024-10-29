=== Affiliate Power - Sales Tracking for Affiliate Marketers ===
Contributors: JonasBreuer
Donate link: https://www.affiliatepowerplugin.com
Tags: affiliate marketing, tracking, sales, financeads, awin
Requires at least: 4.6
Tested up to: 6.3.2
Requires PHP: 5.6
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html


Affiliate Power imports your sales of various affiliate networks. Thanks to the additional tracking of posts, referer, URL-Parameters and devices, you'll finally know what really pays.



== Description ==

Affiliate Marketing should be easy and effective!

As an affiliate you may know this: To get an overview over your income, you have to login into x different networks which all have different backends and statistics.

To compare the statistics, you have to export everything, paste it into Excel, convert it etc. You really have better things to do with your time.

That’s what I thought as well and that’s why I made the WordPress Plugin Affiliate Power.

Affiliate Power imports your sales of various affiliate networks. That gives you up to date income reviews and statistics right in your WordPress backend. The basic version is absolutely free. The [premium version](https://www.affiliatepowerplugin.com/premium/) actually tracks which post, referer, campaign, and device has brought the sale. You'll finally know what really pays.


**Features**

Features, which only exist in the premium version are *emphasized*.

* Supported Networks: adcell, awin, belboon, commission junction, financeads, digistore24, tradedoubler
* Filter your import per website
* Overview over all sales
* Automatic daily infomail on new or changed sales
* Export all Sales as Excel-CSV
* *Track income per Posts, Pages, Referer, Keywords URL-Parameters like utm_campaign, and device (mobile or desktop)*
* Detailed statistics over any period with income per partner, networks, days, weeks, months, *posts, landing pages, referer, URL-parameters, and devices*



== Installation ==

As usual.

1. Upload the directory `affiliate-power` in `/wp-content/plugins/` (or install the plugin over the plugin manager of WordPress)
2. Activate the plugin over the plugin manager of WordPress
3. The plugin creats it's own submenu `Affiliate Power`. In the menu item `Settings` you can enter your affiliate network data
4. After you have entered your affiliate network data, you can download your old sales on the page `Leads/Sales` by clicking on the `Refresh transactions` button.
5. The plugin will download your sales autoamtically once a day.


== Frequently Asked Questions ==

**Is my Data safe?**

Yes, your income data is stored only in your WordPress database. 

**Are additional networks planned?**

The APIs of affiliate networks are constantly changing and it’s a lot of work to keep them up to date. For this reason, I will only add new networks that reached a significant market share.

**Whats about the Amazon Partnernet?**

Unfortunately, Amazon offers no real API for downloading sales and is blocking automated logins, so it is no longer supported by Affiliate Power.

**Does the plug in work with link cloakers like Pretty Link?**

Pretty Link is officially compatible with Affiliate Power. As for other link cloakers, depending on their inner functionality, it’s possible that only the features of the basic version are working. If you want to use Affiliate Power Premium with a different link cloaker, send me a message and I’ll let you if it’s compatible.

**Can I use the plugin with its own subId tracking?**

The basic version of the plugin doesn’t use any subIds and can be used without further consideration. The premium version uses subIds to determine the additional metrics and can’t be used with your own subId tracking. However, in the vast majority of cases the data from Affiliate Power is much more valuable than the data from existing subIds. 

**Is the sales data on the plugin up to date?**

The plugin automatically downloads the sales once a day. You can, however, manually download the latest sales figures at any time. This data is the live data from the networks.


== Screenshots ==

1. Overview over your Sales
2. Statistics
3. Detailed Statistics
4. Income per Post (Premium-Version)


