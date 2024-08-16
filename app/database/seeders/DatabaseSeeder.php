<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!config('app.is_production')) {
            for ($i = 1; $i <= 8; $i++) {
                $question = Question::create([
                    'content' => $i . '問目の質問です。以下の選択肢から選んでください。',
                    'sort_key' => $i,
                ]);

                foreach ([
                    ['content' => '選択肢A', 'sort_key' => 1],
                    ['content' => '選択肢B', 'sort_key' => 2],
                    ['content' => '選択肢C', 'sort_key' => 3],
                    ['content' => '選択肢D', 'sort_key' => 4],
                ] as $option) {
                    $question->options()->create($option);
                }
            }
        }
    }
}
