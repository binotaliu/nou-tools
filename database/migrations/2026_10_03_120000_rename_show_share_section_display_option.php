<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameKey('show_share_section', 'show_backup_section');
    }

    public function down(): void
    {
        $this->renameKey('show_backup_section', 'show_share_section');
    }

    private function renameKey(string $from, string $to): void
    {
        DB::table('student_schedules')
            ->whereNotNull('display_options')
            ->where('display_options', 'like', "%\"{$from}\"%")
            ->chunkById(100, function ($rows) use ($from, $to): void {
                foreach ($rows as $row) {
                    $options = json_decode($row->display_options, true);

                    if (! is_array($options) || ! array_key_exists($from, $options)) {
                        continue;
                    }

                    // An explicit value under the new name wins over the old one.
                    $options[$to] ??= $options[$from];
                    unset($options[$from]);

                    DB::table('student_schedules')->where('id', $row->id)->update([
                        'display_options' => json_encode($options),
                    ]);
                }
            });
    }
};
