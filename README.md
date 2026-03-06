# E.U.T Restaurant POS System

A web-based Point of Sale system for E.U.T Restaurant built with PHP MVC architecture.

## Features

- **Order Management**: Create and manage customer orders with dine-in and takeout options
- **Menu Management**: Organize menu items by categories with pricing and availability
- **Inventory Tracking**: Monitor stock levels and manage reorder points
- **User Management**: Role-based access control (Admin, Manager, Cashier)
- **Sales Reporting**: Daily sales reports and analytics
- **Kitchen View**: Order status tracking for kitchen staff
- **Receipt Printing**: Generate and print customer receipts

## System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Installation

1. **Database Setup**
   ```sql
   -- Import the provided database schema
   -- The database name should be: restaurantpos
   ```

2. **Configuration**
   - Update database credentials in `config/database.php`
   - Ensure the web server has write permissions for uploads

3. **Web Server Setup**
   - Place the files in your web root directory
   - Configure your web server to point to the project folder
   - Ensure mod_rewrite is enabled for clean URLs

## Default Login

- **Username**: admin
- **Password**: (Check the database for the hashed password or create a new admin user)

## Project Structure

```
├── config/
│   └── database.php          # Database configuration
├── controllers/
│   ├── AuthController.php     # Authentication handling
│   └── POSController.php     # POS operations
├── models/
│   ├── User.php              # User model
│   ├── Category.php          # Category model
│   ├── MenuItem.php          # Menu item model
│   ├── Order.php             # Order model
│   └── Inventory.php        # Inventory model
├── views/
│   ├── login.php             # Login page
│   ├── dashboard.php         # Dashboard
│   └── pos/
│       └── index.php         # Main POS interface
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   └── js/
│       └── pos.js           # POS JavaScript functionality
└── index.php                # Entry point
```

## Usage

### For Cashiers
1. Log in with your credentials
2. Select items from categories or use search
3. Add items to the current order
4. Set order type (dine-in/takeout)
5. Process payment and print receipt

### For Managers/Admins
1. Access dashboard for sales overview
2. Manage menu items and categories
3. Monitor inventory levels
4. Generate sales reports
5. Manage user accounts

## Database Schema

The system uses the following main tables:
- `users` - User accounts and roles
- `categories` - Menu categories
- `menuitems` - Menu items with pricing
- `orders` - Customer orders
- `orderdetails` - Order line items
- `inventory` - Stock management
- `payments` - Payment records

## Security Features

- Password hashing using PHP's password_hash()
- Session-based authentication
- SQL injection prevention with prepared statements
- Role-based access control
- Input validation and sanitization

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Support

For issues and support, please contact the system administrator.

## License

This project is proprietary software for E.U.T Restaurant.
