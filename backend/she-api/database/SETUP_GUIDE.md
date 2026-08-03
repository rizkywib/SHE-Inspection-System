# MySQL & phpMyAdmin Setup Guide

## Problem Diagnosis

The error you're encountering indicates:
1. **Control user 'pma' doesn't exist** - phpMyAdmin's advanced features require a control user
2. **Root user authentication denied** - MySQL is rejecting the connection with "using password: NO"

## Solution

### Option 1: Using MySQL Command Line (Recommended)

1. **Open Command Prompt or PowerShell as Administrator**

2. **Navigate to MySQL bin directory** (adjust path based on your MySQL installation):
   ```cmd
   cd "C:\Program Files\MySQL\MySQL Server 8.0\bin"
   ```

3. **Login to MySQL as Administrator**:
   ```cmd
   mysql -u root -p
   ```
   If your root has no password, just press Enter when prompted.

4. **Run the setup script**:
   ```sql
   SOURCE D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\database\setup_mysql.sql;
   ```
   
   Or copy-paste the contents of `setup_mysql.sql` directly into MySQL console.

### Option 2: Using MySQL Workbench or phpMyAdmin (if accessible)

If you have another MySQL client available:

1. Connect to MySQL as root
2. Open the `setup_mysql.sql` file
3. Execute the script

## Configuration Files Created

### 1. `backend/she-api/public/phpmyadmin/config.inc.php`

This configures phpMyAdmin with:
- Connection to local MySQL at 127.0.0.1:3306
- Root user with no password (as per Laravel .env)
- phpMyAdmin storage database for advanced features
- Control user 'pma' (passwordless for development)

### 2. `backend/she-api/database/setup_mysql.sql`

Creates:
- The `she_inspection` database
- The `phpmyadmin` database for phpMyAdmin features
- 'pma' user for phpMyAdmin control
- Proper grants for root user
- phpMyAdmin configuration tables

## After Setup

1. **Restart MySQL service** (optional but recommended):
   ```cmd
   net stop MySQL80
   net start MySQL80
   ```

2. **Restart Web Server** (IIS/Apache/XAMPP/WAMP)

3. **Refresh phpMyAdmin** in your browser:
   ```
   http://eoblas10.ecogreenoleo.co.id:82/phpmyadmin
   ```

4. **Import main schema**:
   - Open phpMyAdmin
   - Select `she_inspection` database
   - Go to Import tab
   - Choose `backend/schema.sql`
   - Click Go

## Alternative: If Root Has a Password

If your MySQL root user has a password, update these files:

### Update `backend/she-api/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=she_inspection
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### Update `backend/she-api/public/phpmyadmin/config.inc.php`:
```php
$cfg['Servers'][$i]['user']          = 'root';
$cfg['Servers'][$i]['password']      = 'your_password_here';
$cfg['Servers'][$i]['AllowNoPassword'] = false;
```

## Verification

Test the connection:
```cmd
mysql -u root -p she_inspection
```

For Laravel application:
```cmd
cd backend/she-api
php artisan migrate:status
```

## Security Notes

⚠️ **For Production Environment**:

1. **Remove or restrict `AllowNoPassword`:**
   ```php
   $cfg['Servers'][$i]['AllowNoPassword'] = false;
   ```

2. **Set strong passwords:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'StrongRootPassword123!';
   CREATE USER 'pma'@'localhost' IDENTIFIED BY 'StrongPmaPassword456!';
   ```

3. **Restrict phpMyAdmin access** via Apache config
4. **Use environment variables** for sensitive credentials

## Troubleshooting

### Error: "SQLSTATE[HY000] [1045] Access denied"
- Verify username and password in `.env` file
- Check MySQL user host permissions
- Run: `SELECT user, host FROM mysql.user;`

### Error: "Unknown MySQL server host"
- Ensure MySQL service is running
- Check `DB_HOST` setting (use 127.0.0.1 instead of localhost)

### phpMyAdmin still not working
- Check Apache error logs
- Verify phpMyAdmin directory permissions
- Ensure PHP MySQL extension is loaded

## Default Credentials

After importing `schema.sql`:
- **Email**: admin@sheinspection.com
- **Password**: admin123
- **Role**: super_admin

## Support

Contact: TIM IT Ecogreen
Server: eoblas10.ecogreenoleo.co.id:82