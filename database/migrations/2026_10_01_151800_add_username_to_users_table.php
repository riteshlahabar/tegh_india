<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('users', 'initials')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('initials', 8)->nullable()->after('role');
            });
        }

        $used = DB::table('users')->whereNotNull('username')->pluck('username')->all();

        DB::table('users')->orderBy('id')->get()->each(function (object $user) use (&$used) {
            $updates = [];

            if ($user->username === null || $user->username === '') {
                $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', (string) strstr($user->email, '@', true)));
                if ($base === '') {
                    $base = 'user'.$user->id;
                }

                $candidate = $base;
                $suffix = 2;
                while (in_array($candidate, $used, true)) {
                    $candidate = $base.$suffix;
                    $suffix++;
                }

                $used[] = $candidate;
                $updates['username'] = $candidate;
            }

            if (Schema::hasColumn('users', 'initials') && ($user->initials === null || $user->initials === '')) {
                $updates['initials'] = $this->initials($user->name);
            }

            if ($updates !== []) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        });

        $hasUnique = collect(Schema::getIndexes('users'))->contains(function (array $index) {
            return ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['username'];
        });

        if (! $hasUnique) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }

        if (Schema::hasColumn('users', 'initials')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('initials');
            });
        }
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
    }
};
