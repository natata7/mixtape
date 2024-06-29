# Mixtape #

**Tags:** mistake, mistype, report error  
**Requires at least:** 6.0.0  
**Tested up to:** 6.4.1  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**Contributors:** natata7  
**Stable tag:** 1.2
**License URI:** <http://www.gnu.org/licenses/gpl-2.0.html>
Mixtape is lightweight plugin, that allows readers to effortlessly notify site staff about found spelling errors.

## Description ##

On Ctrl+Enter event, the plugin sends selected text along with paragraph and page URL it belongs to an email address selected in admin settings page. You can choose among administrators and editors, or specify another address.

The plugin is very lightweight. The "press Ctrl+Enter..." caption (or your text) can be configured to be automatically appended to selected post types or be inserted anywhere using a shortcode. Disabled features don't get loaded, so performance impact is minimized to the lowest notch.
Besides text, caption also can be set as image defined by URL.

Mixtape is full of hooks enabling you to modify its behavior the way you like.

You can easily customize plugin in your colors and chose one from the icons near to the message in posts.

### Support ###

Please report any bugs, errors, warnings, code problems to [Github](https://github.com/natata7/mixtape/issues)

## Installation ##

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings > Mixtape and set options.

### 1.2 ###

* Fix: sending reports on front on some cases.

### 1.1 ###

* Add: the ability to send a bug report from a mobile device.

### 1.0 ###

* Initial release.
