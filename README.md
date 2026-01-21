# Risk Management System API

A comprehensive RESTful API service for managing organizational risks, built with Laravel 8. The system allows organizations to track, assess, and manage various business risks including operational, financial, compliance, and strategic risks.

## Features

### Core Functionality
- **Risk Management**: Create, read, update, delete risks with comprehensive tracking
- **Risk Assessment**: Track risk likelihood and impact with historical assessments
- **Mitigation Actions**: Manage action plans to mitigate identified risks
- **Role-Based Access Control**: Four distinct user roles with appropriate permissions
- **Reporting & Analytics**: Comprehensive dashboards and reports
- **Real-time Notifications**: Email alerts for high-risk items

### Technical Features
- **JWT-based authentication** (tymon/jwt-auth)
  - Production-ready stateless authentication
  - Token refresh mechanism (60-minute TTL)
  - Secure password hashing
- **Laravel API Resources** for consistent response formatting
- **Role and permission-based authorization** (Spatie Laravel Permission)
  - 4 roles with granular permissions
  - API guard support
- **Input validation and sanitization**
- **Rate limiting** (60 requests/minute) and request logging
- **OpenAPI/Swagger documentation** with interactive UI
- **Automatic risk score calculation** (Likelihood × Impact)
- **Soft deletes** for data recovery
- **Database migrations and seeders** with sample data
- **Postman collection** included for API testing

## Installation

### Prerequisites
- PHP 7.4 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- MySQL client tools (optional, for database creation)

### MySQL Installation Options

**Option 1: XAMPP (Recommended for Windows)**
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache and MySQL services from XAMPP Control Panel
3. Access phpMyAdmin at http://localhost/phpmyadmin

**Option 2: MySQL Server (Direct Installation)**
1. Download MySQL Server from https://dev.mysql.com/downloads/mysql/
2. Install and configure MySQL Server
3. Start MySQL service

**Option 3: Docker (Cross-platform)**
```bash
docker run --name mysql-risk -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=risk_management -p 3306:3306 -d mysql:8.0
```

**Option 4: Local Development Stack**
- WAMP (Windows): https://www.wampserver.com/
- MAMP (macOS): https://www.mamp.info/
- LAMP (Linux): Use package manager to install Apache, MySQL, PHP

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd risk-management-laravel
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   
   **Option A: Using MySQL Command Line**
   ```sql
   mysql -u root -p
   CREATE DATABASE risk_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit
   ```
   
   **Option B: Using phpMyAdmin**
   - Open phpMyAdmin (usually at http://localhost/phpmyadmin)
   - Create a new database named `risk_management`
   - Set charset to `utf8mb4` and collation to `utf8mb4_unicode_ci`
   
   **Option C: Using Docker**
   ```bash
   docker-compose up -d
   # This will start MySQL, phpMyAdmin, and the application
   ```

4. **Environment configuration**
   Update the `.env` file with your database credentials:
   
   **For local MySQL:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=risk_management
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
   
   **For Docker setup:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=risk_management
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

5. **Automated Setup (Recommended)**
   Run the setup script that handles database creation and configuration:
   ```bash
   php setup.php
   ```
   
   **Manual Setup:**
   ```bash
   php artisan config:clear
   php artisan migrate
   php artisan db:seed
   php artisan l5-swagger:generate
   ```

5. **Generate Swagger documentation**
   ```bash
   php artisan l5-swagger:generate
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`

## Docker Deployment

For easy deployment with Docker:

1. **Start the services**
   ```bash
   docker-compose up -d
   ```

2. **Run setup inside container**
   ```bash
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan db:seed
   docker-compose exec app php artisan l5-swagger:generate
   ```

3. **Access the services**
   - API: http://localhost:8000
   - phpMyAdmin: http://localhost:8080
   - API Documentation: http://localhost:8000/api/documentation

4. **Stop the services**
   ```bash
   docker-compose down
   ```

## API Documentation

### Interactive Documentation
Access the Swagger UI documentation at: `http://localhost:8000/api/documentation`

### API Response Format
All API responses use **Laravel API Resources** for consistent formatting:
- **Standardized structure** across all endpoints
- **Conditional loading** of relationships
- **Automatic type casting** (dates, decimals, booleans)
- **Pagination metadata** included for list endpoints

### Authentication

All API endpoints (except registration and login) require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

**Token Expiration**: Tokens expire after 60 minutes. Use the refresh endpoint to get a new token without re-authenticating.

### Default Users

The system comes with pre-configured users for testing:

| Role | Email | Password | Permission Count | Description |
|------|-------|----------|------------------|-------------|
| Admin | admin@riskmanagement.com | admin123 | 21 permissions | Full system access, user management |
| Risk Manager | manager@riskmanagement.com | manager123 | 13 permissions | Manage all risks, actions, assessments |
| Risk Owner | owner@riskmanagement.com | owner123 | 9 permissions | Create and edit assigned risks |
| Auditor | auditor@riskmanagement.com | auditor123 | 5 permissions | Read-only access, export reports |

## API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Refresh JWT token
- `GET /api/v1/auth/me` - Get current user profile

### Risk Management
- `GET /api/v1/risks` - List risks with filtering and pagination
- `POST /api/v1/risks` - Create new risk
- `GET /api/v1/risks/{id}` - Get specific risk
- `PUT /api/v1/risks/{id}` - Update risk
- `DELETE /api/v1/risks/{id}` - Delete risk

### Mitigation Actions
- `GET /api/v1/mitigation-actions` - List mitigation actions
- `POST /api/v1/mitigation-actions` - Create new action
- `GET /api/v1/mitigation-actions/{id}` - Get specific action
- `PUT /api/v1/mitigation-actions/{id}` - Update action
- `DELETE /api/v1/mitigation-actions/{id}` - Delete action
- `GET /api/v1/risks/{risk}/mitigation-actions` - Get actions for specific risk

### Risk Assessments
- `GET /api/v1/risk-assessments` - List assessments
- `POST /api/v1/risks/{risk}/assessments` - Create assessment for risk
- `GET /api/v1/risks/{risk}/assessments` - Get assessments for risk
- `GET /api/v1/risk-assessments/{id}` - Get specific assessment
- `PUT /api/v1/risk-assessments/{id}` - Update assessment
- `DELETE /api/v1/risk-assessments/{id}` - Delete assessment

### Reports & Analytics
- `GET /api/v1/reports/dashboard` - Dashboard summary data
- `GET /api/v1/reports/risk-summary` - Risk summary statistics
- `GET /api/v1/reports/risk-matrix` - Risk matrix visualization data
- `GET /api/v1/reports/risks-by-category` - Risks breakdown by category
- `GET /api/v1/reports/risks-by-department` - Risks breakdown by department
- `GET /api/v1/reports/overdue-actions` - Overdue mitigation actions
- `GET /api/v1/reports/high-risk-items` - High-priority risks

## Data Models

### Risk
- **Categories**: Operational, Financial, Compliance, Strategic, Reputational
- **Statuses**: Identified, Assessed, Mitigating, Closed
- **Risk Score**: Automatically calculated (Likelihood × Impact)
- **Risk Levels**: Low (< 8), Medium (8-14), High (≥ 15)

### Mitigation Action
- **Statuses**: Planned, In Progress, Completed, Cancelled
- **Priority Levels**: 1 (Critical) to 5 (Very Low)
- **Automatic Completion**: Date set when status changes to completed

### Risk Assessment
- **Before/After Tracking**: Captures risk levels before and after mitigation
- **Improvement Calculation**: Automatic calculation of risk reduction
- **Historical Tracking**: Maintains assessment history

## Role-Based Access Control

### Admin (21 Permissions)
**Full System Access**
- **Risks**: view, create, edit, delete, manage-all-risks
- **Mitigation Actions**: view, create, edit, delete, assign
- **Assessments**: view, create, edit, delete
- **Users**: view, create, edit, delete
- **Reports**: view, export
- **System**: manage-system

### Risk Manager (13 Permissions)
**Can Manage All Organizational Risks**
- **Risks**: view, create, edit, manage-all-risks
- **Mitigation Actions**: view, create, edit, assign
- **Assessments**: view, create, edit
- **Reports**: view, export

### Risk Owner (9 Permissions)
**Can Manage Assigned Risks**
- **Risks**: view, create, edit (own risks only)
- **Mitigation Actions**: view, create, edit
- **Assessments**: view, create
- **Reports**: view

### Auditor (5 Permissions)
**Read-Only Access for Compliance**
- **Risks**: view
- **Mitigation Actions**: view
- **Assessments**: view
- **Reports**: view, export

## Security Features

### Authentication & Authorization
- JWT-based stateless authentication
- Role and permission-based access control
- Token refresh mechanism
- Account activation/deactivation

### Data Protection
- Input validation and sanitization
- SQL injection prevention (Eloquent ORM)
- XSS protection
- CSRF protection for web routes

### Rate Limiting
- API rate limiting (60 requests per minute)
- Configurable throttling
- Request logging and monitoring

## Configuration

### Environment Variables
Key configuration options in `.env`:

```env
# Application
APP_NAME="Risk Management API"
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=risk_management
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT Configuration
JWT_SECRET=<generated-secret>
JWT_TTL=60

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
```

### Risk Score Configuration
Risk scores are calculated as: `Likelihood × Impact`

- **Likelihood Scale**: 1 (Very Unlikely) to 5 (Very Likely)
- **Impact Scale**: 1 (Negligible) to 5 (Catastrophic)
- **Risk Levels**:
  - Low: 1-7
  - Medium: 8-14
  - High: 15-25

## Sample API Usage

### Login and Get Token
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager@riskmanagement.com",
    "password": "manager123"
  }'
```

### Create a Risk
```bash
curl -X POST http://localhost:8000/api/v1/risks \
  -H "Authorization: Bearer <your-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Security Risk",
    "description": "Potential security vulnerability in system",
    "category": "operational",
    "likelihood": 3,
    "impact": 4,
    "identified_date": "2026-01-20",
    "department": "IT"
  }'
```

### Get Dashboard Data
```bash
curl -X GET http://localhost:8000/api/v1/reports/dashboard \
  -H "Authorization: Bearer <your-token>"
```

## API Response Examples

### Successful Response (Risk Resource)
```json
{
  "data": {
    "id": 1,
    "title": "Data Security Vulnerability",
    "description": "Critical security issue in database access",
    "category": "operational",
    "category_label": "Operational",
    "likelihood": 4,
    "impact": 5,
    "risk_score": 20.0,
    "risk_level": "high",
    "status": "assessed",
    "status_label": "Assessed",
    "owner": {
      "id": 1,
      "name": "System Administrator",
      "email": "admin@riskmanagement.com"
    },
    "mitigation_actions": [],
    "created_at": "2026-01-21T10:25:38.000000Z",
    "updated_at": "2026-01-21T10:25:38.000000Z"
  },
  "message": "Risk retrieved successfully"
}
```

### Paginated Response (Risk Collection)
```json
{
  "data": [
    {
      "id": 1,
      "title": "Data Security Vulnerability",
      "risk_score": 20.0,
      "risk_level": "high"
      // ... other fields
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/risks?page=1",
    "last": "http://localhost:8000/api/v1/risks?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/v1/risks?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 45
  },
  "message": "Risks retrieved successfully"
}
```

### Error Response
```json
{
  "message": "This action is unauthorized.",
  "errors": {
    "permission": ["You need 'manage-all-risks' permission"]
  }
}
```

## Testing

### Postman Collection
A complete Postman collection is included for testing all API endpoints:
- **File**: `Risk_Management_API.postman_collection.json`
- **Environment**: `Risk_Management_Environment.postman_environment.json`
- **30+ API requests** covering all endpoints
- Pre-configured with example requests and responses
- Import into Postman and update the `base_url` and `token` variables

### Sample Data
The system includes pre-seeded data for testing:
- 4 users with different roles (Admin, Risk Manager, Risk Owner, Auditor)
- Sample risks across all categories
- Multiple mitigation actions per risk
- Risk assessments with before/after tracking
- Complete permission structure

## Logging

The system logs:
- All API requests and responses
- Authentication events
- High-risk notifications
- Error events

Logs are stored in `storage/logs/laravel.log`

## Performance Considerations

- Database indexes on frequently queried fields
- Pagination for large datasets
- Eager loading to prevent N+1 queries
- Caching for static data (roles, permissions)

## Future Enhancements

- Email notification system for high-risk alerts
- File attachment support for risks and actions
- Advanced reporting with charts and graphs
- Risk workflow automation
- Integration with external risk databases
- Mobile application support

## Support

For issues and questions:
1. Check the API documentation at `/api/documentation`
2. Review the logs in `storage/logs/`
3. Ensure proper authentication and permissions
4. Verify database connectivity

## License

This project is open-source software licensed under the MIT license.