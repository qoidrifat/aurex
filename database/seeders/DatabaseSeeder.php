<?php

namespace Database\Seeders;

use App\Models\Analysis;
use App\Models\Recommendation;
use App\Models\StyleReport;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->admin()->pro()->create([
            'name' => 'Admin',
            'email' => 'admin@aurex.app',
        ]);

        $demo = User::factory()->create([
            'name' => 'Qoid Demo',
            'email' => 'demo@aurex.app',
        ]);

        foreach ([$admin, $demo] as $user) {
            $this->seedAnalysesFor($user);
        }
    }

    private function seedAnalysesFor(User $user): void
    {
        Analysis::factory()
            ->count(4)
            ->for($user)
            ->create()
            ->each(function (Analysis $analysis) use ($user): void {
                $image = UploadedImage::factory()->for($user)->create();
                $analysis->update(['uploaded_image_id' => $image->id]);

                $order = 0;
                foreach ((array) $analysis->hairstyles as $label) {
                    Recommendation::create([
                        'analysis_id' => $analysis->id,
                        'type' => 'hairstyle',
                        'label' => (string) $label,
                        'sort_order' => $order++,
                    ]);
                }
                foreach ((array) $analysis->colors as $label) {
                    Recommendation::create([
                        'analysis_id' => $analysis->id,
                        'type' => 'color',
                        'label' => (string) $label,
                        'sort_order' => $order++,
                    ]);
                }
                foreach ((array) $analysis->outfits as $label) {
                    Recommendation::create([
                        'analysis_id' => $analysis->id,
                        'type' => 'outfit',
                        'label' => (string) $label,
                        'sort_order' => $order++,
                    ]);
                }

                StyleReport::factory()
                    ->for($user)
                    ->for($analysis)
                    ->create();
            });
    }
}
