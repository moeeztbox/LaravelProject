---

# Deployment Checklist

This checklist should be followed when deploying the Laravel Project Management application to a production environment.

## Prerequisites

* Ensure the latest code has been reviewed and merged.
* Verify the production `.env` configuration.
* Confirm database credentials are correct.
* Ensure queue workers are configured and running.

## Deployment Steps

1. Pull the latest code from the repository.
2. Install/update Composer dependencies.
3. Run database migrations.
4. Clear and rebuild Laravel caches.
5. Restart queue workers.
6. Verify the application is running correctly.

### Commands

```bash
git pull origin main

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
```

## Post-Deployment Verification

* Verify the application loads successfully.
* Test user authentication.
* Verify project and task management functionality.
* Test file uploads.
* Confirm queued welcome emails are processed successfully.
* Check the application logs for errors.

## Notes

* Do not commit the `.env` file to the repository.
* Deploy only tested and reviewed code.
* Keep production server access limited to authorized team members.
* Always back up the database before major deployments.
