# Hostinger Deployment

1. Upload all files to public_html.
2. Copy config/mail-config.example.php to config/mail-config.php on the server.
3. Replace SMTP_PASSWORD only on the server if SMTP integration is added.
4. Confirm .htaccess is uploaded.
5. Test /, /fleet/, /services/, /request-a-quote/, /contact/, /careers/, /sitemap.xml and /robots.txt.
6. If PHP mail is disabled, configure Hostinger mail routing or replace the tiny mail function with PHPMailer without adding a framework.
