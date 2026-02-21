# Contributing to Laravel Visual Migrator

Thank you for considering contributing to the Laravel Visual Migrator!

## Code of Conduct

Please be kind and respectful to others in all interactions.

## How Can I Contribute?

### Reporting Bugs
If you find a bug, please create an issue on GitHub with a clear description and steps to reproduce.

### Pull Requests
1. Fork the repository.
2. Create a new branch for your feature or fix.
3. Write/update tests for your changes.
4. Ensure PSR-12 coding standards are followed.
5. Submit a pull request.

### Developing Frontend Assets
If you modify the visual interface, you need to build the assets from the root of the main project:
```bash
npm run build:laravel
```
This will populate the `packages/laravel/dist` directory.

## Testing
Run the tests before submitting:
```bash
composer install
./vendor/bin/phpunit
```

## License
By contributing, you agree that your contributions will be licensed under its MIT License.
