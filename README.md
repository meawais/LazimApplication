# Lazim Application Task Manager

1. Clone the project repository to your local machine: git clone https://github.com/meawais/LazimApplication.git
2. Navigate to the project directory: cd <project_directory>
3. composer install
4. Create a copy of the .env.example file and rename it to .env: cp .env.example .env
5. Generate an application key: php artisan key:generate
6. Run the database migrations and seeders to create the necessary tables and seed data: php artisan migrate --seed
7. Start the Laravel development server: php artisan serve
8. To test the APIs, you can use Postman to make requests to the API endpoints defined in routes/api.php.
9. In the routes/api.php file, some routes may require a Sanctum token for access, while others may be accessible without a token.
10. The TaskController is implemented with all necessary validations for tasks.
11. The signup process includes integrating a mail server to send OTP for user verification.
12. Forgot and reset password APIs are implemented, which also provide OTP for completing the desired operations.

By following these steps, you should be able to clone, set up, and run the Laravel project from the repository, along with understanding the user creation via seeder, accessible routes, API testing using Postman, Sanctum token usage, TaskController validations, and the OTP-based signup and password reset processes.
