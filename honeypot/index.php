<?php
define('HONEYPOT_SALT', 'your_secret_salt_here'); // Important: put your own secret salt here
define('HONEYPOT_MAX_AGE', 60); // How many minutes old can a token be before it expires?

/**
 * Returns a honeypot token based on the current factors:
 * - user's IP address
 * - current date and time
 * - HONEYPOT_SALT
 */
function honeypot_token(){
  $time = time();
  return $time . '-' . honeypot_sign($_SERVER['REMOTE_ADDR'], $time);
}

/**
 * Returns the HMAC signature for a given IP address and timestamp.
 */
function honeypot_sign($ip, $time){
  return hash_hmac('sha256', $ip . '|' . $time, HONEYPOT_SALT);
}

/**
 * Checks that the specified token is valid (for the current user's IP) and not expired.
 * Returns true if it is, false otherwise.
 */
function honeypot_valid($token){
  if(!is_string($token) || !preg_match('/^(\d{1,12})-([0-9a-f]{64})$/', $token, $m)) return false;
  $time = (int) $m[1];
  $age = time() - $time;
  // Reject tokens from the near future (in case running on multiple servers with clock drift)
  // or older than the maximum age:
  if($age < -5 || $age > HONEYPOT_MAX_AGE * 60) return false;
  return hash_equals(honeypot_sign($_SERVER['REMOTE_ADDR'], $time), $m[2]);
}

/**
 * Applies the banhammer to the current user. Override this with your own rules.
 */
function honeypot_hit(){
  // In this sample implementation, we merely log the misbehaving IP to a temporary file:
  $ip = $_SERVER['REMOTE_ADDR'];
  $logfile = __DIR__ . '/honeypot.log';
  $entry = date('Y-m-d H:i:s') . " - $ip\n";
  file_put_contents($logfile, $entry, FILE_APPEND);
  // Other things you might consider doing instead -
  // 1. Add the IP to Fail2Ban's blocklist?
  // 2. Append the IP to a file, e.g. a .htaccess file for Apache or a blocklist for Caddy-WAF?
  // 3. Share it with abuseipdb.com?
}

// If the page is requested directly, check the query string for a valid token and apply the ban
if(1 === count(get_included_files())) {
  if(honeypot_valid($_SERVER['QUERY_STRING'] ?? '')) {
    // The honeypot was hit: ban the user's IP -
    honeypot_hit();
    header('HTTP/1.1 403 Banned');
    die();
  } else {
    // Generate a new link to check if they're abusive:
    ?>
      <p>
        If you are a human, <a href="/">go back to the main website</a> to avoid getting banned.
      </p>
      <p>
        Only you are a misbehaving robot, <a href="index.php?<?php echo honeypot_token(); ?>" aria-hidden>visit this special page just for you</a>.
      </p>
    <?php
  }
}

// Nothing else to do: this file gets loaded as a dependency!
