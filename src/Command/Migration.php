<?php

declare(strict_types=1);

namespace App\Command;

use App\Interfaces\MigrationInterface;
use App\Models\Setting;
use App\Services\DB;
use function count;
use function explode;
use function is_numeric;
use function krsort;
use function ksort;
use function scandir;
use const PHP_INT_MAX;
use const SCANDIR_SORT_NONE;

final class Migration extends Command
{
    public string $description = <<< END
├─=: php xcat Migration [版本]
│ ├─ <version>               - 迁移至指定版本（前进/退回）
│ ├─ latest                  - 迁移至最新版本
│ ├─ new                     - 导入全新数据库至最新版本
END;

    public function boot(): void
    {
        $reverse = false;
        // (min_version, max_version]
        $min_version = 0;
        $max_version = 0;

        $target = $this->argv[2] ?? 0;

        if ($target === 'new') {
            $current = 0;
        } else {
            $current = Setting::obtain('db_version');
        }

        if ($target === 'latest') {
            $min_version = $current;
            $max_version = PHP_INT_MAX;
        } elseif ($target === 'new') {
            $tables = DB::select('SHOW TABLES');
            if ($tables === []) {
                $max_version = PHP_INT_MAX;
            } else {
                echo "Database is not empty, do not use \"new\" as version.\n";
                return;
            }
        } elseif (is_numeric($target)) {
            $target = (int) $target;
            if ($target < $current) {
                $reverse = true;
                $min_version = $target;
                $max_version = $current;
            } else {
                $min_version = $current;
                $max_version = $target;
            }
        } else {
            echo "Illegal version argument.\n";
            return;
        }

        echo "Current database version {$current}.\n\n";

        $queue = [];
        $files = scandir(BASE_PATH . '/db/migrations/', SCANDIR_SORT_NONE);
        if ($files) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || ! str_ends_with($file, '.php')) {
                    continue;
                }
                $version = (int) explode('-', $file, 1)[0];
                echo "Found migration version {$version}.";
                if ($version <= $min_version || $version > $max_version) {
                    echo "...skip\n";
                    continue;
                }
                echo "\n";
                $object = require_once BASE_PATH . '/db/migrations/' . $file;
                if ($object instanceof MigrationInterface) {
                    $queue[$version] = $object;
                }
            }
        }
        echo "\n";

        if ($reverse) {
            krsort($queue);
            foreach ($queue as $version => $object) {
                echo "Reverse on {$version}\n";
                $current = $object->down();
            }
        } else {
            ksort($queue);
            foreach ($queue as $version => $object) {
                echo "Forward to {$version}\n";
                $current = $object->up();
            }
        }

        $sql = match ($target) {
            'new' => 'INSERT INTO `config` (`item`, `value`, `type`, `default`) VALUES("db_version", ?, "int", "2023020100")',
            default => 'UPDATE `config` SET `value` = ? WHERE `item` = "db_version"'
        };
        $stat = DB::getPdo()->prepare($sql);
        $stat->execute([$current]);

        $count = count($queue);
        echo "\nMigration completed. {$count} file(s) processed.\nCurrent version: {$current}\n";
    }
}
