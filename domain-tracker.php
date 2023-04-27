<?php
// Son kullanma tarihini almak için kullanılan WHOIS sunucusu adresi
$whois_server = 'whois.verisign-grs.com';

// İlgili domainlerin listesi
$domain_list = ['example.com', 'example.net', 'example.org'];

// SMTP sunucusu bilgileri
$smtp_server = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'your_username@gmail.com';
$smtp_password = 'your_password';

// E-posta gönderilecek alıcılar
$recipients = ['user1@example.com', 'user2@example.com'];

// WHOIS sunucusuna bağlanan fonksiyon
function get_expiry_date($domain) {
  global $whois_server;
  $fp = fsockopen($whois_server, 43, $errno, $errstr, 10);
  if (!$fp) {
    return false;
  }
  fputs($fp, "$domain\r\n");
  $response = '';
  while(!feof($fp)) {
    $response .= fgets($fp, 128);
  }
  fclose($fp);
  preg_match('/Registry Expiry Date: (.*?)\n/', $response, $matches);
  if (count($matches) > 1) {
    return strtotime($matches[1]);
  } else {
    return false;
  }
}

// Son kullanma tarihlerini kontrol eden ve e-posta gönderen fonksiyon
function check_expiry_dates() {
  global $domain_list, $recipients, $smtp_server, $smtp_port, $smtp_username, $smtp_password;
  foreach ($domain_list as $domain) {
    $expiry_date = get_expiry_date($domain);
    if ($expiry_date === false) {
      continue;
    }
    $expiry_datetime = new DateTime('@' . $expiry_date);
    $now_datetime = new DateTime();
    $interval = $now_datetime->diff($expiry_datetime);
    if ($interval->days < 30) {
      $subject = 'Domain bitiş tarihi yaklaşıyor: ' . $domain;
      $body = 'Domain ' . $domain . ' ' . $interval->days . ' gün içinde sona erecek.';
      $headers = 'From: ' . $smtp_username . "\r\n" .
        'Reply-To: ' . $smtp_username . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
      $smtp = new SMTP();
      $smtp->connect($smtp_server, $smtp_port);
      $smtp->login($smtp_username, $smtp_password);
      foreach ($recipients as $recipient) {
        $smtp->sendmail($recipient, $smtp_username, $subject, $body, $headers);
      }
      $smtp->quit();
    }
  }
}

// İşlemi çalıştır
check_expiry_dates();
?>
