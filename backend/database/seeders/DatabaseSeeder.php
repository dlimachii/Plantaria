<?php

namespace Database\Seeders;

use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => env('PLANTARIA_ADMIN_EMAIL', 'admin@plantaria.local'),
        ]);

        $admin->fill([
            'handle' => Str::lower(env('PLANTARIA_ADMIN_HANDLE', 'plantaria_admin')),
            'display_name' => env('PLANTARIA_ADMIN_NAME', 'Plantaria Admin'),
            'password' => Hash::make(env('PLANTARIA_ADMIN_PASSWORD', 'PlantariaAdmin1')),
            'country' => 'Espana',
            'province' => 'Valencia',
            'city' => 'Valencia',
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);
        $admin->uid ??= (string) Str::uuid();
        $admin->email_verified_at = now();
        $admin->save();

        $demoUser = User::query()->firstOrNew([
            'email' => 'demo@plantaria.local',
        ]);

        $demoUser->fill([
            'handle' => 'plantaria_demo',
            'display_name' => 'Plantaria Demo',
            'password' => Hash::make('PlantariaDemo1'),
            'country' => 'Espana',
            'province' => 'Barcelona',
            'city' => 'Barcelona',
            'default_lat' => 41.3874,
            'default_lng' => 2.1686,
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
        ]);
        $demoUser->uid ??= (string) Str::uuid();
        $demoUser->email_verified_at = now();
        $demoUser->save();

        $demoRecords = [
            [
                'public_id' => 'PLANTARIADEMOBCN000001',
                'observation_public_id' => 'OBSDEMOBCN000001',
                'provisional_common_name' => 'Platanero',
                'verified_common_name' => 'Platanero de sombra',
                'verified_scientific_name' => 'Platanus x hispanica',
                'description' => 'Ejemplar urbano junto al Parc de la Ciutadella.',
                'primary_photo_path' => 'demo/platanero-ciutadella.jpg',
                'plant_condition' => PlantCondition::GOOD,
                'verification_status' => VerificationStatus::VERIFIED,
                'latitude' => 41.3887900,
                'longitude' => 2.1871200,
                'observed_at' => now()->subDays(1),
            ],
            [
                'public_id' => 'PLANTARIADEMOBCN000002',
                'observation_public_id' => 'OBSDEMOBCN000002',
                'provisional_common_name' => 'Lavanda',
                'verified_common_name' => 'Lavanda',
                'verified_scientific_name' => 'Lavandula angustifolia',
                'description' => 'Mata aromatica en zona ajardinada de Montjuic.',
                'primary_photo_path' => 'demo/lavanda-montjuic.jpg',
                'plant_condition' => PlantCondition::GOOD,
                'verification_status' => VerificationStatus::VERIFIED,
                'latitude' => 41.3635500,
                'longitude' => 2.1576600,
                'observed_at' => now()->subDays(2),
            ],
            [
                'public_id' => 'PLANTARIADEMOBCN000003',
                'observation_public_id' => 'OBSDEMOBCN000003',
                'provisional_common_name' => 'Romero',
                'verified_common_name' => 'Romero',
                'verified_scientific_name' => 'Salvia rosmarinus',
                'description' => 'Arbusto mediterraneo cerca del Park Guell.',
                'primary_photo_path' => 'demo/romero-park-guell.jpg',
                'plant_condition' => PlantCondition::REGULAR,
                'verification_status' => VerificationStatus::VERIFIED,
                'latitude' => 41.4144900,
                'longitude' => 2.1526900,
                'observed_at' => now()->subDays(3),
            ],
            [
                'public_id' => 'PLANTARIADEMOBCN000004',
                'observation_public_id' => 'OBSDEMOBCN000004',
                'provisional_common_name' => 'Bugambilia',
                'verified_common_name' => null,
                'verified_scientific_name' => null,
                'description' => 'Registro pendiente de moderacion en Gracia.',
                'primary_photo_path' => 'demo/bugambilia-gracia.jpg',
                'plant_condition' => PlantCondition::UNKNOWN,
                'verification_status' => VerificationStatus::PENDING,
                'latitude' => 41.4026900,
                'longitude' => 2.1595200,
                'observed_at' => now()->subHours(8),
            ],
        ];

        foreach ($demoRecords as $demoRecord) {
            $isVerified = $demoRecord['verification_status'] === VerificationStatus::VERIFIED;
            $record = PlantRecord::withTrashed()->firstOrNew([
                'public_id' => $demoRecord['public_id'],
            ]);

            $record->fill([
                'created_by_user_id' => $demoUser->id,
                'provisional_common_name' => $demoRecord['provisional_common_name'],
                'verified_common_name' => $demoRecord['verified_common_name'],
                'verified_scientific_name' => $demoRecord['verified_scientific_name'],
                'description' => $demoRecord['description'],
                'primary_photo_path' => $demoRecord['primary_photo_path'],
                'plant_condition' => $demoRecord['plant_condition'],
                'verification_status' => $demoRecord['verification_status'],
                'verified_by_user_id' => $isVerified ? $admin->id : null,
                'verified_at' => $isVerified ? $demoRecord['observed_at']->copy()->addHours(2) : null,
                'latitude' => $demoRecord['latitude'],
                'longitude' => $demoRecord['longitude'],
                'latest_observation_at' => $demoRecord['observed_at'],
            ]);
            $record->uid ??= (string) Str::uuid();
            $record->deleted_at = null;
            $record->save();

            $observation = Observation::query()->firstOrNew([
                'public_id' => $demoRecord['observation_public_id'],
            ]);

            $observation->fill([
                'plant_record_id' => $record->id,
                'author_user_id' => $demoUser->id,
                'photo_path' => $demoRecord['primary_photo_path'],
                'note' => $demoRecord['description'],
                'plant_condition' => $demoRecord['plant_condition'],
                'latitude' => $demoRecord['latitude'],
                'longitude' => $demoRecord['longitude'],
                'source_type' => ObservationSourceType::INITIAL,
                'observed_at' => $demoRecord['observed_at'],
            ]);
            $observation->uid ??= (string) Str::uuid();
            $observation->save();
        }
    }
}
