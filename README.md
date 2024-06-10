# PHP Template Inheritance Engine

This project is a simple PHP template inheritance engine. It demonstrates the usage of output buffers and `debug_backtrace` to create a template inheritance engine in pure PHP.

## Minimum Requirements

- PHP 8.3 or higher

Please ensure that you have the correct version of PHP installed on your system before running this project.

## Project Structure

- `TemplateInheritance.php`: This is the main class that handles the template inheritance.
- `ViewSupport.php`: This is the helper class that handles providing template inheritance to templates.
- `Block.php`: This class represents a block in a template.
- `index.php`: This is the entry point of the application.
- `templates/`: This directory contains the template files.

## Running the Project Locally

You can run this project locally using PHP's built-in server. Navigate to the project directory in your terminal and run the following command:

```bash
php -S localhost:7000