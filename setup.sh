#!/bin/bash

# Description: This script sets up a Symfony project with PostgreSQL.

# Check if the script is run as root
if [ "$EUID" -eq 0 ]; then
  echo "Please do not run as root."
  exit 1
fi

# Check for Composer and Symfony CLI
command -v composer >/dev/null 2>&1 || { echo >&2 "Composer is not installed. Aborting."; exit 1; }
command -v symfony >/dev/null 2>&1 || { echo >&2 "Symfony CLI is not installed. Aborting."; exit 1; }

# Define project directory (current directory or specify another)
PROJECT_DIR=$(pwd)

# Create .env file if it does not exist
if [ ! -f "$PROJECT_DIR/.env" ]; then
  echo "Creating .env file..."
  cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
else
  echo ".env file already exists."
fi

# Create .env.local file if it does not exist
if [ ! -f "$PROJECT_DIR/.env.local" ]; then
  echo "Creating .env.local file..."
  touch "$PROJECT_DIR/.env.local"
else
  echo ".env.local file already exists."
fi

# Copy all content from .env to .env.local except for DATABASE_URL
grep -v '^DATABASE_URL=' "$PROJECT_DIR/.env" > "$PROJECT_DIR/.env.local"

# Prompt for database credentials with default values
read -p "DB_DRIVER (default 'postgresql'): " DB_DRIVER
DB_DRIVER=${DB_DRIVER:-postgresql}  # Set default if empty
read -p "DB_HOST (default '127.0.0.1'): " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}  # Set default if empty
read -p "DB_PORT (default '5432'): " DB_PORT
DB_PORT=${DB_PORT:-5432}  # Set default if empty
read -p "DB_NAME (default 'TRADERPOINT_CZ_DEVELOPMENT_DB_V1'): " DB_NAME
DB_NAME=${DB_NAME:-TRADERPOINT_CZ_DEVELOPMENT_DB_V1}  # Set default if empty
read -p "DB_USER (default 'postgres'): " DB_USER
DB_USER=${DB_USER:-postgres}  # Set default if empty
read -sp "DB_PASSWORD (default ''): " DB_PASSWORD
echo
DB_PASSWORD=${DB_PASSWORD:-}  # Set default if empty

# Update .env.local with database credentials
{
  echo "DATABASE_URL=\"$DB_DRIVER://$DB_USER:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_NAME\""
} >> "$PROJECT_DIR/.env.local"

# Install Composer dependencies
echo -e "\nRunning composer install..."
composer install

# Set permissions
echo -e "\nSetting permissions for var and vendor directories..."
chmod -R 775 var
chmod -R 775 vendor

# Clear Symfony cache
echo -e "\nClearing Symfony cache..."
php bin/console cache:clear

# Check if the database already exists
if pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER"; then
  echo -e "\nChecking if database '$DB_NAME' exists..."
  if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    echo "Database '$DB_NAME' already exists. Skipping creation."
  else
    # Creating the database if it does not exist
    echo -e "\nCreating database..."
    if php bin/console d:d:c; then
      echo "Database created successfully."
    else
      echo "Failed to create the database. Aborting."
      exit 1
    fi
  fi
else
  echo "Could not connect to the database server. Aborting."
  exit 1
fi

# Updating schema
echo -e "\nUpdating database schema..."
php bin/console d:s:u -f

# Install Node.js packages if using Webpack
if [ -d "node_modules" ]; then
  echo -e "\nInstalling Node.js packages..."
  npm install
fi

# Custom scripts to add
CUSTOM_SCRIPTS=$(cat <<EOF
"rector": "vendor/bin/rector p --ansi"
"rector-dry": "vendor/bin/rector p --dry-run --ansi"
"phpstan": "php -d memory_limit=256M vendor/bin/phpstan analyze"
"check-cs": "vendor/bin/ecs check --ansi"
"fix-cs": "vendor/bin/ecs check --fix --ansi"
"test": "vendor/bin/phpunit"
EOF
)

# Check if composer.json exists
if [ -f "$PROJECT_DIR/composer.json" ]; then
  echo -e "\nAdding custom scripts to composer.json..."

  # Backup the existing composer.json
  cp "$PROJECT_DIR/composer.json" "$PROJECT_DIR/composer.json.bak"

  # Check if the scripts section exists; if not, create it
  if ! grep -q '"scripts": {' "$PROJECT_DIR/composer.json"; then
    echo "Creating 'scripts' section in composer.json..."
    # Insert scripts section if it does not exist
    sed -i.bak '/}/i\
  "scripts": {' "$PROJECT_DIR/composer.json"
  fi

  # Add custom scripts if they don't already exist
  while IFS= read -r line; do
    script_name=$(echo "$line" | cut -d':' -f1 | xargs)
    if ! grep -q "\"$script_name\":" "$PROJECT_DIR/composer.json"; then
      # Append script to composer.json
      sed -i.bak "/\"scripts\": {/a\\
      $line," "$PROJECT_DIR/composer.json"
      echo "Added script: $script_name"
    else
      echo "Script already exists: $script_name"
    fi
  done <<< "$CUSTOM_SCRIPTS"

  # Remove trailing comma from the last script
  sed -i.bak '$ s/,$//' "$PROJECT_DIR/composer.json"
  echo "Custom scripts added successfully."
else
  echo "composer.json does not exist. Please create it manually."
fi

echo "run 'symfony server:start' to start the server and go to https://localhost:8000."

# Output completion message
echo -e "\nSetup completed!"