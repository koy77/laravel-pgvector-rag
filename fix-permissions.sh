#!/bin/bash

# Fix Laravel storage and cache permissions for Docker
echo "Fixing Laravel permissions for Docker..."

# Set ownership to www-data
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

# Set permissions
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/

echo "Permissions fixed successfully!"
echo "Storage directory: $(ls -la storage/ | head -1)"
echo "Logs directory: $(ls -la storage/logs/ | head -1)"
