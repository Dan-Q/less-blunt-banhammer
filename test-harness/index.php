<?php require_once('honeypot/index.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Less Blunt Banhammer Test - Legitimate Page</title>
</head>
<body>
  <h1>Legitimate Page</h1>
  <p>
    This is a legitimate page that regular users will see.
    But it contains a honeypot link that bad bots might hit. And if they do, their IP is logged (e.g. for permaban purposes).
  </p>
  <p>
    <a href="/honeypot/index.php?<?php echo honeypot_token(); ?>" style="display:none;" aria-hidden>Honeypot Link</a>
  </p>
</body>
</html>
