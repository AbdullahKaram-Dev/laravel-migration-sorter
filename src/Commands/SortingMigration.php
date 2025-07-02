<?php declare(strict_types=1);

namespace AbdullahKaramDev\MigrationSorter\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Exception;

class SortingMigration extends Command
{
    protected $signature = 'rearrange-migrations';

    protected $description = 'Interactive migration file sorting with drag and drop functionality';

    private Collection $files;
    private int $selectedIndex = 0;
    private ?int $draggedIndex = null;
    private string $directory;
    private array $originalOrder = [];

    public function __construct()
    {
        parent::__construct();
        $this->directory = database_path('migrations');
        $this->files = collect();
    }

    public function handle(): int
    {
        $this->info("Scanning Directory: {$this->directory}");

        if (!$this->loadFiles()) {
            return self::FAILURE;
        }

        $this->info("Found {$this->files->count()} migration files");
        $this->storeOriginalOrder();

        $this->interactiveSorting();

        return self::SUCCESS;
    }

    private function displayFiles(): void
    {
        /** Clear screen */
        echo "\033[2J\033[H";

        $this->info("Interactive Migration File Sorting - Current Order:");
        $this->displayControls();
        $this->newLine();

        $tableData = [];
        foreach ($this->files as $index => $file) {
            $radioButton = $index === $this->selectedIndex ? "●" : "○";
            $grabbedStatus = $this->draggedIndex === $index ? "GRABBED" : "";

            /** Highlight the selected row */
            $prefix = $index === $this->selectedIndex ? "<fg=yellow;options=bold>" : "";
            $suffix = $index === $this->selectedIndex ? "</>" : "";

            $tableData[] = [
                'index' => $prefix.($index + 1).$suffix,
                'radio' => $prefix.$radioButton.$suffix,
                'filename' => $prefix.$file['display_name'].$suffix,
                'size' => $prefix.$file['display_size'].$suffix,
                'modified' => $prefix.$file['display_modified'].$suffix,
                'status' => $grabbedStatus
            ];
        }

        $this->table(['#', 'Select', 'Migration File Name', 'Size', 'Modified Date', 'Status'], $tableData);
        $this->newLine();

        // Status information
        $this->displayStatus();
    }

    private function displayControls(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->info("Controls: W=up, S=down | GRAB/DROP | FINISH=done | N=name, D=date, SIZE=size | R=reset, Q=quit");
            $this->info("Type commands and press ENTER (e.g., 'W' then ENTER for up)");
        } else {
            $this->info("Controls: ↑/↓=navigate | SPACE=grab/drop | ENTER=finish | ESC=cancel | Q=quit");
            $this->info("Sort: N=name, D=date, S=size | R=reset");
        }
    }

    private function displayStatus(): void
    {
        if ($this->draggedIndex !== null) {
            $draggedFile = $this->files[$this->draggedIndex];
            $this->line("<fg=green;options=bold>Moving: {$draggedFile['name']}</>");
            $this->line("<fg=yellow>Use navigation to move, then GRAB/DROP to place here</>");
        } else {
            $this->line("<fg=green;options=bold>Ready to sort</>");
            $this->line("<fg=cyan>Navigate with arrows, GRAB to select file, FINISH when done</>");
        }

        // Show current selection
        if (isset($this->files[$this->selectedIndex])) {
            $currentFile = $this->files[$this->selectedIndex];
            $this->line("<fg=yellow;options=bold>Current: {$currentFile['name']}</>");
        }
    }

    private function loadFiles(): bool
    {
        if (!File::exists($this->directory)) {
            $this->error("Directory not found: {$this->directory}");
            return false;
        }

        try {
            $this->files = collect(File::allFiles($this->directory))
                ->filter(function ($file) {
                    return $file->isFile() && $file->getExtension() === 'php';
                })
                ->map(function ($file) {
                    return [
                        'display_name' => Str::of($file->getFilename())->limit(60, '...')->toString(),
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'display_size' => Number::fileSize($file->getSize()),
                        'size' => $file->getSize(),
                        'display_modified' => date('Y-m-d H:i', $file->getMTime()),
                        'modified' => $file->getMTime()
                    ];
                })->values();

            return $this->files->isNotEmpty();
        } catch (Exception $e) {
            $this->error("Error loading files: ".$e->getMessage());
            return false;
        }
    }

    private function storeOriginalOrder(): void
    {
        $this->originalOrder = $this->files->toArray();
    }

    private function interactiveSorting(): void
    {
        if ($this->files->isEmpty()) {
            $this->error("No migration files found in '{$this->directory}'");
            $this->info("Try creating some migration files first with: php artisan make:migration");
            return;
        }

        $this->info("Starting Interactive File Sorting");
        $this->newLine();

        while (true) {
            $this->displayFiles();
            $key = $this->getKeyPress();

            if ($this->handleKeyPress($key)) {
                break;
            }
            usleep(50000);
        }
    }

    private function handleKeyPress(string $key): bool
    {
        switch ($key) {
            case "\033[A": // Up arrow
            case "w":
                $this->moveSelection(-1);
                break;

            case "\033[B": // Down arrow
            case "s":
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || $key === "\033[B") {
                    $this->moveSelection(1);
                } else {
                    $this->promptSortBySize();
                }
                break;

            case " ":
                $this->handleGrabDrop();
                break;

            case "\n":
                return $this->handleEnter();

            case "\033":
                return $this->handleEscape();

            case "q":
                $this->info("Goodbye!");
                return true;

            case "r":
                $this->resetOrder();
                break;

            case "size":
                $this->promptSortBySize();
                break;

            case "d":
                $this->promptSortByDate();
                break;

            case "n":
                $this->promptSortByName();
                break;

            default:
                if (strlen($key) > 1) {
                    $this->line("<fg=red>Unknown command: '{$key}'</>");
                    sleep(1);
                }
                break;
        }

        return false;
    }

    private function moveSelection(int $direction): void
    {
        $newIndex = $this->selectedIndex + $direction;

        if ($newIndex >= 0 && $newIndex < $this->files->count()) {
            $this->selectedIndex = $newIndex;
        }
    }

    private function handleGrabDrop(): void
    {
        if ($this->draggedIndex === null) {
            $this->draggedIndex = $this->selectedIndex;
            $grabbedFile = $this->files[$this->draggedIndex];
            $this->info("Grabbed: {$grabbedFile['name']}");
            sleep(1);
        } else {
            $this->moveFile($this->draggedIndex, $this->selectedIndex);
            $fromPos = $this->draggedIndex + 1;
            $toPos = $this->selectedIndex + 1;
            $this->info("File moved from position {$fromPos} to position {$toPos}!");
            $this->draggedIndex = null;
            sleep(1);
        }
    }

    private function handleEnter(): bool
    {
        if ($this->draggedIndex !== null) {
            $this->draggedIndex = null;
            $this->info("Grab cancelled");
            sleep(1);
            return false;
        } else {
            $this->info("Sorting completed!");
            $this->regenerateMigrationsWithNewOrder();
            return true;
        }
    }

    private function handleEscape(): bool
    {
        if ($this->draggedIndex !== null) {
            $this->draggedIndex = null;
            $this->info("Grab cancelled");
            sleep(1);
            return false;
        } else {
            $this->info("Sorting cancelled!");
            return true;
        }
    }

    /**
     * Prompts user for sort direction and sorts by size
     */
    private function promptSortBySize(): void
    {
        $direction = $this->getSortDirection('size');
        $this->sortBySize($direction);
    }

    /**
     * Prompts user for sort direction and sorts by date
     */
    private function promptSortByDate(): void
    {
        $direction = $this->getSortDirection('date');
        $this->sortByDate($direction);
    }

    /**
     * Prompts user for sort direction and sorts by name
     */
    private function promptSortByName(): void
    {
        $direction = $this->getSortDirection('name');
        $this->sortByName($direction);
    }

    /**
     * Gets the sort direction from user input with default as 'desc'
     */
    private function getSortDirection(string $sortType): string
    {
        echo "\033[2J\033[H"; // Clear screen

        $this->info("Sort by {$sortType}");
        $this->info("Choose sort direction:");
        $this->info("1. Descending (Default) - " . $this->getDescendingDescription($sortType));
        $this->info("2. Ascending - " . $this->getAscendingDescription($sortType));
        $this->newLine();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->line("Enter 1 for descending, 2 for ascending, or press ENTER for default (descending):");
            $input = trim(fgets(STDIN));
        } else {
            $this->line("Press 1 for descending, 2 for ascending, or ENTER for default (descending):");
            system('stty cbreak -echo');
            $input = fgetc(STDIN);
            system('stty -cbreak echo');
        }

        switch ($input) {
            case '2':
                return 'asc';
            case '1':
            case '':
            case "\n":
            default:
                return 'desc';
        }
    }

    /**
     * Get description for descending sort based on type
     */
    private function getDescendingDescription(string $sortType): string
    {
        switch ($sortType) {
            case 'size':
                return 'Largest to Smallest';
            case 'date':
                return 'Newest to Oldest';
            case 'name':
                return 'Z to A';
            default:
                return 'High to Low';
        }
    }

    /**
     * Get description for ascending sort based on type
     */
    private function getAscendingDescription(string $sortType): string
    {
        switch ($sortType) {
            case 'size':
                return 'Smallest to Largest';
            case 'date':
                return 'Oldest to Newest';
            case 'name':
                return 'A to Z';
            default:
                return 'Low to High';
        }
    }

    private function regenerateMigrationsWithNewOrder(): void
    {
        $this->newLine();
        $this->info("Regenerating migration files with new order...");

        if (!$this->confirm('Do you want to regenerate the migration files with updated timestamps?')) {
            $this->info("Migration regeneration cancelled.");
            return;
        }

        try {
            $baseTimestamp = now()->format('Y_m_d_His');
            $backupDir = storage_path('app/migration_backups/' . date('Y-m-d_H-i-s'));

            // Create backup directory
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $this->info("Creating backup of original files...");
            $progressBar = $this->output->createProgressBar($this->files->count());
            $progressBar->setFormat('Backing up files: %current%/%max% [%bar%] %percent:3s%%');

            // Backup original files
            foreach ($this->files as $file) {
                File::copy($file['path'], $backupDir . '/' . $file['name']);
                $progressBar->advance();
            }
            $progressBar->finish();

            $this->newLine();
            $this->info("Backup created at: {$backupDir}");
            $this->newLine();

            // Generate new timestamps and rename files with progress bar
            $this->info("Regenerating files with new timestamps...");
            $regenerateBar = $this->output->createProgressBar($this->files->count());
            $regenerateBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%%');

            foreach ($this->files as $index => $file) {
                $newTimestamp = $this->generateTimestamp($baseTimestamp, $index);
                $newFileName = $this->generateNewFileName($file['name'], $newTimestamp);
                $newPath = $this->directory . '/' . $newFileName;

                // Read file content
                $content = File::get($file['path']);

                // Delete old file
                File::delete($file['path']);

                // Create new file with updated timestamp
                File::put($newPath, $content);

                $regenerateBar->advance();
            }
            $regenerateBar->finish();

            $this->newLine(2);
            $this->info("Migration files successfully regenerated with new order!");
            $this->info("Backup of original files saved to: {$backupDir}");
            $this->info("{$this->files->count()} files processed successfully");

        } catch (Exception $e) {
            $this->error("Failed to regenerate migration files: " . $e->getMessage());
            $this->error("Please check the backup directory and restore manually if needed.");
        }
    }

    private function generateTimestamp(string $baseTimestamp, int $index): string
    {
        // Convert base timestamp to Carbon instance for manipulation
        $timestamp = \Carbon\Carbon::createFromFormat('Y_m_d_His', $baseTimestamp);

        // Add seconds based on index to ensure unique timestamps
        $timestamp->addSeconds($index);

        return $timestamp->format('Y_m_d_His');
    }

    private function generateNewFileName(string $originalName, string $newTimestamp): string
    {
        // Extract the migration name part (everything after the timestamp)
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $originalName, $matches)) {
            return $newTimestamp . '_' . $matches[1];
        }

        // If the pattern doesn't match, assume it's already a clean name
        return $newTimestamp . '_' . $originalName;
    }

    private function getKeyPress(): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return $this->getWindowsKeyPress();
        }

        // Unix/Linux/Mac
        system('stty cbreak -echo');
        $key = fgetc(STDIN);

        // Handle escape sequences (arrow keys)
        if ($key === "\033") {
            $key .= fgetc(STDIN);
            $key .= fgetc(STDIN);
        }

        system('stty -cbreak echo');
        return $key;
    }

    private function getWindowsKeyPress(): string
    {
        $this->newLine();
        $this->line("<fg=cyan>Enter command: w=up | s=down | grab | drop | finish | q=quit | r=reset | n/d/size=sort</>");

        $input = trim(fgets(STDIN));

        if ($input === '') {
            $this->line("<fg=yellow>Enter a command</>");
            return $this->getWindowsKeyPress();
        }

        $commandMap = [
            'w' => 'w', 'up' => 'w',
            's' => 's', 'down' => 's',
            'grab' => ' ', 'select' => ' ',
            'drop' => ' ', 'place' => ' ', 'put' => ' ',
            'finish' => "\n", 'done' => "\n", 'complete' => "\n",
            'q' => 'q', 'quit' => 'q', 'exit' => 'q',
            'r' => 'r', 'reset' => 'r',
            'n' => 'n', 'name' => 'n',
            'd' => 'd', 'date' => 'd',
            'size' => 'size',
            'esc' => "\033", 'escape' => "\033", 'cancel' => "\033"
        ];

        $command = strtolower($input);

        if (isset($commandMap[$command])) {
            return $commandMap[$command];
        }

        $this->line("<fg=red>Unknown command: '{$input}'</>");
        $this->line("<fg=yellow>Available: w, s, grab, drop, finish, q, r, n, d, size</>");
        return $this->getWindowsKeyPress();
    }

    private function moveFile(int $fromIndex, int $toIndex): void
    {
        if ($fromIndex === $toIndex) {
            return;
        }

        $filesArray = $this->files->toArray();
        $file = $filesArray[$fromIndex];

        array_splice($filesArray, $fromIndex, 1);

        array_splice($filesArray, $toIndex, 0, [$file]);

        $this->files = collect($filesArray);
        $this->selectedIndex = $toIndex;
    }

    private function resetOrder(): void
    {
        $this->files = collect($this->originalOrder);
        $this->selectedIndex = 0;
        $this->draggedIndex = null;
        $this->info("Order reset to original!");
        sleep(1);
    }

    /**
     * Sort files by size with specified direction (default: desc)
     */
    private function sortBySize(string $direction = 'desc'): void
    {
        if ($direction === 'asc') {
            $this->files = $this->files->sortBy('size')->values();
            $message = "Sorted by size (smallest first)!";
        } else {
            $this->files = $this->files->sortByDesc('size')->values();
            $message = "Sorted by size (largest first)!";
        }

        $this->selectedIndex = 0;
        $this->draggedIndex = null;
        $this->info($message);
        sleep(1);
    }

    /**
     * Sort files by date with specified direction (default: desc)
     */
    private function sortByDate(string $direction = 'desc'): void
    {
        if ($direction === 'asc') {
            $this->files = $this->files->sortBy('modified')->values();
            $message = "Sorted by date (oldest first)!";
        } else {
            $this->files = $this->files->sortByDesc('modified')->values();
            $message = "Sorted by date (newest first)!";
        }

        $this->selectedIndex = 0;
        $this->draggedIndex = null;
        $this->info($message);
        sleep(1);
    }

    /**
     * Sort files by name with specified direction (default: desc)
     */
    private function sortByName(string $direction = 'desc'): void
    {
        if ($direction === 'asc') {
            $this->files = $this->files->sortBy('name')->values();
            $message = "Sorted by name (A-Z)!";
        } else {
            $this->files = $this->files->sortByDesc('name')->values();
            $message = "Sorted by name (Z-A)!";
        }

        $this->selectedIndex = 0;
        $this->draggedIndex = null;
        $this->info($message);
        sleep(1);
    }
}