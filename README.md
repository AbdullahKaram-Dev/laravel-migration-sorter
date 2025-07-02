# 🔄 Laravel Migration Sorter

[![GitHub stars](https://img.shields.io/github/stars/AbdullahKaram-Dev/laravel-migration-sorter?style=flat-square&logo=github)](https://github.com/AbdullahKaram-Dev/laravel-migration-sorter/stargazers)
[![Packagist Downloads](https://img.shields.io/packagist/dt/abdullahkaram-dev/laravel-migration-sorter?style=flat-square&logo=packagist)](https://packagist.org/packages/abdullahkaramdev/migration-sorter)
[![Laravel Version](https://img.shields.io/badge/Laravel-8.0%2B-red?style=flat-square&logo=laravel)](https://laravel.com)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/abdullahkaram-dev/laravel-migration-sorter.svg?style=flat-square)](https://packagist.org/packages/abdullahkaram-dev/laravel-migration-sorter)

An interactive Laravel command for sorting database migration files with drag-and-drop functionality and automatic
timestamp regeneration.

![Migration Sorter Interface](https://raw.githubusercontent.com/AbdullahKaram-Dev/laravel-migration-sorter/refs/heads/master/preview.webp)

## 🤔 Why Use This Command?

### ❌ Common Migration Problems

- **Out-of-order migrations** causing database schema conflicts 🔀
- **Foreign key constraint errors** when child tables with foreign keys are created before their parent tables 🔗❌
- **Timestamp conflicts** when multiple developers create migrations simultaneously ⏰💥
- **Dependency issues** where migrations depend on tables created in later migrations 🔄⚠️
- **Messy migration history** making it difficult to understand database evolution 📂🌪️
- **Production deployment failures** due to incorrect migration execution order 🚀💥

### ✅ Solutions This Package Provides

- **Visual migration management** - See all your migrations in one organized view 👁️📊
- **Interactive reordering** - Drag and drop migrations to the correct sequence 🖱️✨
- **Automatic timestamp fixing** - Regenerates timestamps to ensure proper execution order ⏰🔧
- **Safe operations** - Creates backups before making any changes 🛡️💾
- **Team collaboration** - Ensures consistent migration order across all environments 👥🤝
- **Time-saving** - No more manual file renaming or timestamp calculations ⚡💯

### 🌟 Real-World Scenarios

1. **After merging branches** - Sort conflicting migration timestamps from multiple feature branches 🌿🔀
2. **Before production deployment** - Ensure migrations will execute in the correct logical order 🚀✅
3. **Database refactoring** - Reorganize migrations to match your current database structure needs 🏗️🔧
4. **Team onboarding** - Help new developers understand the database evolution timeline 👨‍💻📈
5. **Migration cleanup** - Organize legacy migrations for better maintainability 🧹✨

## Features

### Interactive Terminal Interface

- **Visual file browser** with table display showing migration files
- **Real-time navigation** using arrow keys or keyboard shortcuts
- **Drag-and-drop functionality** for manual file reordering
- **Cross-platform support** (Windows, Linux, macOS)

### Multiple Sorting Options

- **Sort by Name** (A-Z or Z-A)
- **Sort by Date** (Newest first or Oldest first)
- **Sort by File Size** (Largest first or Smallest first)
- **Manual drag-and-drop** for custom ordering

### File Information Display

- Migration file names (truncated for readability)
- File sizes in human-readable format
- Last modified timestamps
- Visual indicators for selected and grabbed files

### Safe File Operations

- **Automatic backup** creation before any changes
- **Timestamp regeneration** with sequential ordering
- **Rollback capability** using backup files
- **Confirmation prompts** before destructive operations

## Installation

### Via Composer

```bash
composer require abdullahkaramdev/migration-sorter
```

### Manual Installation

1. Copy the `SortingMigration.php` command to your Laravel project
2. Register the command in your `app/Console/Kernel.php`:

```php
protected $commands = [
    \AbdullahKaramDev\MigrationSorter\Command\SortingMigration::class,
];
```

## Usage

### Basic Command

```bash
php artisan rearrange-migrations
```

### Interactive Controls

#### Universal Controls

- **Q** - Quit the application
- **R** - Reset to original order
- **ENTER** - Finish sorting and regenerate files
- **ESC** - Cancel current operation

#### Navigation Controls

**Linux/macOS:**

- **↑/↓ Arrow Keys** - Navigate up/down through files
- **SPACE** - Grab/Drop files for reordering

**Windows:**

- **W** - Move selection up
- **S** - Move selection down
- **GRAB** - Grab selected file
- **DROP** - Drop grabbed file at current position

#### Sorting Shortcuts

- **N** - Sort by name (with direction prompt)
- **D** - Sort by date (with direction prompt)
- **SIZE** - Sort by file size (with direction prompt)

### Sorting Directions

When using automatic sorting, you'll be prompted to choose:

#### Ascending Options

- **Name**: A to Z alphabetical order
- **Date**: Oldest files first
- **Size**: Smallest files first

#### Descending Options (Default)

- **Name**: Z to A reverse alphabetical
- **Date**: Newest files first
- **Size**: Largest files first

## How It Works

### File Detection

1. Scans the `database/migrations` directory
2. Identifies all `.php` migration files
3. Extracts file metadata (size, modification date)
4. Displays files in an interactive table format

### Drag-and-Drop Process

1. Navigate to desired file using arrow keys
2. Press **SPACE** (or type **GRAB**) to select file
3. Navigate to target position
4. Press **SPACE** (or type **DROP**) to place file
5. Repeat for additional reordering

### File Regeneration

1. Creates timestamped backup in `storage/app/migration_backups/`
2. Generates new sequential timestamps starting from current time
3. Renames all migration files with new timestamps
4. Preserves original migration class names and content
5. Shows progress bars for backup and regeneration processes

## File Structure

### Before Sorting

```
database/migrations/
├── 2023_01_15_120000_create_users_table.php
├── 2024_03_20_140000_create_posts_table.php
├── 2024_01_10_100000_create_categories_table.php
└── 2024_02_28_160000_add_foreign_keys.php
```

### After Sorting (by Date, Ascending)

```
database/migrations/
├── 2025_07_02_120000_create_users_table.php      # Original: 2023_01_15
├── 2025_07_02_120001_create_categories_table.php # Original: 2024_01_10
├── 2025_07_02_120002_add_foreign_keys.php        # Original: 2024_02_28
└── 2025_07_02_120003_create_posts_table.php      # Original: 2024_03_20
```

## Backup System

### Automatic Backups

- Created before any file modifications
- Stored in `storage/app/migration_backups/YYYY-MM-DD_HH-mm-ss/`
- Contains exact copies of original migration files
- Preserves original timestamps and content

### Backup Directory Structure

```
storage/app/migration_backups/
└── 2025-07-02_14-30-15/
    ├── 2023_01_15_120000_create_users_table.php
    ├── 2024_01_10_100000_create_categories_table.php
    ├── 2024_02_28_160000_add_foreign_keys.php
    └── 2024_03_20_140000_create_posts_table.php
```

## Safety Features

### Confirmation Prompts

- Asks before regenerating migration files
- Displays backup location information
- Shows progress indicators during operations

### Error Handling

- Validates migration directory existence
- Handles file permission issues
- Provides detailed error messages
- Maintains original files on failure

### Rollback Process

If you need to restore original migrations:

1. Navigate to the backup directory shown after regeneration
2. Copy files back to `database/migrations/`
3. Remove the regenerated files

## Display Information

### File Table Columns

- **#** - Sequential index number
- **Select** - Radio button indicator (● for selected, ○ for unselected)
- **Migration File Name** - Truncated filename for readability
- **Size** - Human-readable file size
- **Modified Date** - Last modification timestamp
- **Status** - Shows "GRABBED" when file is selected for moving

### Visual Indicators

- **Yellow highlighting** for currently selected file
- **Green status messages** for successful operations
- **Red error messages** for failures
- **Cyan informational text** for instructions

## Technical Requirements

### Laravel Version

- Laravel 8.0 or higher
- PHP 7.4 or higher

### Dependencies

- `illuminate/console` - For command interface
- `illuminate/support` - For collections and utilities
- `carbon/carbon` - For timestamp manipulation

### System Requirements

- Terminal with keyboard input support
- Read/write permissions for migrations directory
- Storage directory access for backups

## Troubleshooting

### Common Issues

#### "Directory not found" Error

```bash
Directory not found: /path/to/database/migrations
```

**Solution**: Ensure you're running the command from your Laravel project root

#### "No migration files found" Message

```bash
No migration files found in 'database/migrations'
Try creating some migration files first with: php artisan make:migration
```

**Solution**: Create migration files first or check directory permissions

#### Windows Key Input Issues

**Problem**: Arrow keys not working on Windows
**Solution**: Use W/S keys for navigation and type full commands

### Permission Issues

If you encounter permission errors:

1. Check file permissions: `chmod 755 database/migrations`
2. Verify storage directory access: `chmod 755 storage/app`
3. Ensure Laravel has write permissions

## Advanced Usage

### Custom Base Timestamp

The regeneration process uses the current timestamp as base. Files are given sequential timestamps (base + 0 seconds,
base + 1 second, etc.)

### Integration with Version Control

1. Run migration sorting in development environment
2. Commit the reordered migration files
3. Team members will receive properly ordered migrations
4. Backup files are automatically excluded from version control

## Contributing

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `php artisan test`
4. Follow PSR-12 coding standards

### Feature Requests

- Submit issues on GitHub with detailed descriptions
- Include use cases and expected behavior
- Provide sample migration files if relevant

## License

This package is open-sourced software licensed under the MIT license. See the [LICENSE](LICENSE) file for details.

## Support

For issues and questions:

- GitHub Issues: Report bugs and feature requests
- Documentation: Check this README for common solutions
- Laravel Community: General Laravel migration questions
