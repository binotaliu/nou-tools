<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('study_room.announcement', <<<'MARKDOWN'
        ## 歡迎來到自習室

        這裡是大家一起專心讀書的地方，請共同維護良好的自習氣氛：

        - 保持安靜，討論請至討論區進行
        - 尊重其他自習室使用者，請勿使用不雅暱稱
        - 離開座位前記得先行離線，把位置讓給需要的同學

        祝大家讀書順利！
        MARKDOWN);

        $this->migrator->add('study_room.forbiddenNicknames', []);

        $this->migrator->add('study_room.isOpen', true);
    }
};
