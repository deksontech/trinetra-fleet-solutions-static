# Git Setup

Use this folder as a separate repository from the Laravel and Next.js projects:

```bash
git init
git add .
git commit -m "Initial static website for Trinetra Fleet Solutions"
git branch -M main
git remote add origin <repository-url>
git push -u origin main
```

Do not commit `config/mail-config.php`, uploaded CV files, logs, temporary files, local validation artifacts, SMTP credentials, or server-only files.
