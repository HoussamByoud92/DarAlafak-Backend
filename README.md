# Laravel 10 Printing House Backend

A complete Laravel 10 backend for an e-commerce printing house website with admin dashboard functionality.

## Features

- **Book Management**: Complete CRUD operations for books with categories, authors, publishers, and series
- **Order Management**: Full order processing with WhatsApp notifications
- **User Authentication**: Registration, login, and role-based access control using Laravel Sanctum
- **Shopping Cart**: Persistent cart functionality for logged-in users
- **Content Management**: Blog posts, catalogues, and contact messages
- **Media Management**: Image uploads with automatic resizing using Spatie Media Library
- **API Resources**: RESTful API endpoints for frontend integration
- **Activity Logging**: Track all important actions in the system using Spatie Activity Log
- **Settings Management**: Configurable site settings

## Requirements

- PHP 8.1 or higher
- PostgreSQL 12 or higher
- Composer
- Laravel 10.x

## Installation

1. **Clone or download the project files**

2. **Install dependencies**
   \`\`\`bash
   composer install
   \`\`\`

3. **Environment setup**
   \`\`\`bash
   cp .env.example .env
   \`\`\`
   
   Update your `.env` file with your database credentials:
   \`\`\`env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=printing_house
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   \`\`\`

4. **Generate application key**
   \`\`\`bash
   php artisan key:generate
   \`\`\`

5. **Run database migrations**
   \`\`\`bash
   php artisan migrate
   \`\`\`

6. **Seed the database**
   \`\`\`bash
   php artisan db:seed
   \`\`\`

7. **Publish package assets**
   \`\`\`bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
   php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
   
   # Run additional migrations
   php artisan migrate
   \`\`\`

8. **Create storage link**
   \`\`\`bash
   php artisan storage:link
   \`\`\`

9. **Start the development server**
   \`\`\`bash
   php artisan serve
   \`\`\`

## Laravel 10 Specific Features Used

- **Laravel Sanctum** for API authentication
- **Eloquent Model Binding** in routes
- **Form Request Validation** classes
- **API Resource Collections** for data transformation
- **Service Classes** for business logic
- **Policy Classes** for authorization
- **Database Factories and Seeders**
- **Spatie Packages** for enhanced functionality

## API Endpoints

### Public Endpoints

- `GET /api/books` - List all books with filtering and pagination
- `GET /api/books/featured` - Get featured books
- `GET /api/books/recent` - Get recent books
- `GET /api/books/{slug}` - Get book details
- `GET /api/categories` - List all categories
- `GET /api/authors` - List all authors
- `POST /api/contact` - Submit contact message
- `POST /api/newsletter/subscribe` - Subscribe to newsletter
- `POST /api/orders` - Create new order (guest checkout)

### Authentication Endpoints

- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Protected Endpoints (Require Authentication)

- `GET /api/user` - Get current user
- `GET /api/orders` - Get user orders
- `GET /api/cart` - Get cart items
- `POST /api/cart` - Add item to cart
- `PUT /api/cart/{item}` - Update cart item
- `DELETE /api/cart/{item}` - Remove cart item

### Admin Endpoints (Require Admin Role)

- `GET /api/admin/books` - Manage books
- `GET /api/admin/orders` - Manage orders
- `GET /api/admin/categories` - Manage categories
- `GET /api/admin/authors` - Manage authors
- `GET /api/admin/messages` - Manage contact messages

## Database Schema

The database includes the following main tables:

- **users** - User accounts and authentication
- **books** - Book catalog with all details
- **categories** - Book categories
- **authors** - Author information
- **publishers** - Publisher details
- **orders** - Customer orders
- **order_items** - Order line items
- **cart_items** - Shopping cart persistence
- **contact_messages** - Contact form submissions
- **settings** - Site configuration

## Configuration

### WhatsApp Integration

To enable WhatsApp notifications for new orders, add these to your `.env`:

\`\`\`env
WHATSAPP_API_URL=your_whatsapp_api_url
WHATSAPP_API_TOKEN=your_api_token
WHATSAPP_NUMBER=your_whatsapp_number
\`\`\`

### File Storage

The system uses Laravel's storage system for file uploads. Make sure to configure your preferred storage driver in the `.env` file.

## Testing

You can test the API endpoints using tools like Postman or curl:

\`\`\`bash
# Get all books
curl http://localhost:8000/api/books

# Register a new user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123",
    "first_name": "Test",
    "last_name": "User"
  }'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
\`\`\`

## Laravel 10 Compatibility

This project is specifically built for Laravel 10 and uses:

- Laravel 10.x framework
- PHP 8.1+ compatibility
- Laravel Sanctum for API authentication
- Spatie packages compatible with Laravel 10
- Modern Eloquent features and relationships
- Laravel 10 routing and middleware patterns

## Support

For support or questions, please create an issue in the project repository or contact the development team.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
