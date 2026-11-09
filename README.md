## Less Blunt Banhammer

### Background

I've seen a few folks recently e.g. [@Loebas](https://github.com/Loebas), [@edent](https://github.com/edent) implement aggressive honeypots to deter/stop
badly-behaved bots. The essence of this is simple:

1. Use robots.txt to ban bots from a URL.
2. Make (hidden from humans) links to that URL.
3. IP-ban anything that hits that URL.

The fundamental problem with this approach is that a GET request to a URL can easily be
forced-upon a legitimate user by an attacker, turning this otherwise-effective mechanism of
protection into a tool for denial-of-service attacks. E.g. if an attacker can work out that
hitting the path `/honeypot/index.php` will result in a ban, they can simply add some HTML code
like this to any page, email, forum post, resulting in a ban for _anybody who views that page_:

```html
<img src="//example.com/honeypot/index.php">
```

### My solution

My solution is to generate _signed links_ that encode the IP address of the person they were
delivered to. Only when triggering a honeypot link whose signature includes _your own IP address_
is the trap triggered. An attacker who takes an "armed" honeypot link and tricks somebody else
into loading it will not result in the trap being sprung.

This repository demonstrates this concept.

### Basic usage

You will need to implement your own banning code in `honeypot_hit()`. For example, you might
log to Fail2Ban or write to a blocklist read by your webserver software.

You will also need to modify `HONEYPOT_SALT` to a random string of your own choosing.

1. Universally disallow a path e.g. `/honeypot` in your `robots.txt` file.
2. Add `honeypot/index.php` into that path.
3. Add hidden links e.g. `<a aria-hidden style="display: none;" href="/honeypot/index.php">`
around your site.
4. Any badly behaved bot that follows the hidden link AND the link after is banned.

### Advanced usage

On PHP pages that you do not cache, you can inject signed links directly, liks this:

```php
<?php
// Load the same file: it contains some utility functions you'll need:
require_once('honeypot/index.php');
?>

<!-- add a (signed) poisoned link; hitting it results in an instant ban: -->
<a href="/honeypot/index.php?<?php echo honeypot_token(); ?>" style="display:none;" aria-hidden>Your text here</a>
```

### Running locally

Run `php -S 0.0.0.0:8090` locally and visit http://127.0.0.1:8090/test-harness/ to try it out.
You'll find a hidden link on that page that instantly bans you (note than banning functionality
is not implemented: you'll find your IP address in `honeypot/honeypot.log`. This demonstrates
the usage scenario described in "advanced usage".

Visit http://127.0.0.1:8090/test-harness/honeypot/index.php to see the midway stage when using
the "basic usage" as described above. You see a page and, as a human, hopefully return to the
main site. But a badly-behaved bot can click the "special" link and, once again, find itself
banned.

### Testing

Run `./test` to run a suite of automated tests.

