# Salvio3 POS - Pharmacy Point of Sale System

A comprehensive POS and inventory management system specifically designed for pharmacies, featuring BPOM integration, sales tracking, commission management, and profit sharing capabilities.

## Features

- User Authentication & Role Management (Admin/Sales)
- Product Management with BPOM Integration
- Sales & Transaction Processing
- Commission Calculation & Tracking
- Inventory Management
- Customer Management
- Profit Sharing for Investors
- Reporting & Analytics
- Email & WhatsApp Notifications

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- mod_rewrite enabled
- PDO PHP Extension
- JSON PHP Extension
- cURL PHP Extension

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/Salvio3.git
   ```

2. Navigate to the project directory:
   ```bash
   cd Salvio3
   ```

3. Set appropriate permissions:
   ```bash
   chmod 755 -R .
   chmod 777 -R uploads/
   ```

4. Access the installation script through your web browser:
   ```
   http://localhost/Salvio3/install.php
   ```

5. Follow the installation wizard to set up your database and initial configuration.

6. After installation, you can log in with the default admin credentials:
   - Username: admin
   - Password: admin123

   **Important**: Change these credentials immediately after first login!

## Project Structure

```
/Salvio3
├── config/             # Configuration files
├── controllers/        # Controller classes
├── core/              # Core framework classes
├── models/            # Model classes
├── views/             # View templates
├── assets/            # Public assets (CSS, JS, images)
├── uploads/           # File uploads
├── database/          # Database migrations and seeds
├── helpers/           # Helper functions
└── logs/              # Application logs
```

## Security Features

- CSRF Protection
- SQL Injection Prevention
- XSS Protection
- Password Hashing
- Session Security
- Input Validation
- Role-based Access Control

## Development Phases

1. **Phase 1**: System Foundation & Core Architecture
   - Base project structure
   - Authentication system
   - Role management

2. **Phase 2**: Master Data Management
   - Product management
   - Customer management
   - Category management

3. **Phase 2B**: BPOM Integration
   - BPOM data scraping
   - Product auto-fill
   - Data validation

4. **Phase 3**: Sales & Transactions
   - Sales processing
   - Payment handling
   - Invoice generation

5. **Phase 4**: Commission Management
   - Commission rules
   - Calculation system
   - Payment tracking

6. **Phase 5**: Profit Sharing
   - Investor management
   - Profit calculation
   - Distribution tracking

7. **Phase 6**: Reporting
   - Sales reports
   - Inventory reports
   - Commission reports
   - Profit sharing reports

8. **Phase 7**: Notifications
   - Email integration
   - WhatsApp integration
   - Alert system

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please email support@example.com or create an issue in the GitHub repository.

## Acknowledgments

- Bootstrap for the UI framework
- Font Awesome for icons
- PHPMailer for email functionality
- TCPDF for PDF generation
