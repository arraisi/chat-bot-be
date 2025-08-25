# File Upload Configuration

## Current Status
- **Frontend**: Configured for 100MB file uploads
- **Backend**: Development override allows 100MB in application logic
- **PHP Server**: Limited to 2MB (requires system admin configuration)

## Frontend Changes Made
1. Updated `uploadApi.ts` to handle 100MB files
2. Increased axios timeout to 5 minutes for large uploads
3. Set max content/body length to 100MB
4. Updated default fallback limits to 100MB
5. Component now displays dynamic server limits

## Backend Changes Made
1. Added development override in `FileUploadService.php` to allow 100MB
2. Added warning message when override is active
3. Created debug endpoints to check PHP configuration

## Production Configuration Required

### For Apache Server (.htaccess)
```apache
# Already added to public/.htaccess
php_value upload_max_filesize 100M
php_value post_max_size 100M
php_value memory_limit 256M
php_value max_execution_time 300
php_value max_input_time 300
```

### For Nginx + PHP-FPM
```nginx
client_max_body_size 100M;
```

And in php.ini:
```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

### For Docker/Production Deployment
Update the Dockerfile to modify PHP configuration:
```dockerfile
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini
```

## Testing
- Files up to 100MB should now be accepted by the frontend
- Backend will show 100MB limit in development environment
- Warning message will indicate if PHP limits need adjustment

## API Endpoints for Debugging
- `GET /api/upload/limits` - Check current upload limits
- `GET /api/upload/php-info` - Check raw PHP configuration
