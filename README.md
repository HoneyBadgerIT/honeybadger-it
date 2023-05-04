# honeybadger-it
Connect your Woocommerce shop with the HoneyBadger.IT (https://honeybadger.it) platform and enjoy many features to better manage your company. Included features are custom order statuses, custom PDF atachments, email templates, product variant images, manage your suppliers, create supplier orders, create WC orders and many other features.

Description

HoneyBadger.IT is an online management system for your Woocommerce shop. This plugin is used for the communication between your site and the HoneyBadger IT platform. The communication between the parts use Oauth2 protocol for authorization and the Wordpress REST API v2 for data transfer. All communications are done over HTTPS, you would need a valid SSL certificate installed or you could use self signed certificate.

Demo Account: https://my.honeybadger.it User: demo@honeybadger.it Password: Demo123

With this plugin you can:

1. Create Custom Order Statuses, edit the current and newly created custom order statuses emails
2. Create Custom Emails to send to your customers
3. Create Custom PDF attachments to send to your customers, including PDF Invoices
4. Add image gallery to your product variations without using any third party image gallery software
5. Create sub products
6. Manage your orders
7. Create new orders
8. Split orders
9. Combine / Merge orders
10. Create Suppliers for your products and sub products
11. Associate / link products and sub products with your suppliers
12. Create Supplier orders
13. Have multiple accounts for your staff with different permissions for the system
14. Many other features

To connect your Woocommerce shop with the HoneyBadger.IT platform you need to install the plugin and set your account from the Status page, you will just need a valid email address.

HoneyBadger.IT aims to create the best environment for your online shop and company. We want to make your workflow easier and provide tools for your website and your business to be more productive. Using the HoneyBadger.IT platform will save you server resources, meaning the workload of the management part of your business is split between the platform and your server.

Installation

1. Upload the HoneyBadger.IT plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Configure the plugin
4. Log in to the platform
5. Enjoy

Frequently Asked Questions

* I d not have a valid SSL Certificate can I still use the plugin and platform?
* Yes, you would need to have at least a self signed SSL certificate and set curl_ssl_verify to no in settings.
*
* Does it work with Wordpress Multisite?
* Yes it does work with WP Multisite
*
* The WP REST API seams to do not work, what to do?
* Make sure that the API is public and not restricted by any other plugin which requires users to be logged in to be used.
*
* Does it work under Windows?
* We did not test it, it should work, let us know.
*
* I have uninstalled the plugin by accident and now I get an error message when I try to set it up, what to do?
* Login to the platform and go to the Account page as the main user, there you will find Oauth2 credentials that you need to add in the plugin tools page and continue with the setup process.
*
* What data is stored and where?
* Well, some data is saved to your server and some data is saved on the platform, be careful if you uninstall the plugin because everything stored on your server will be lost. Have a look at the features page on the honeybadger.it website to see each functionality and where data is stored