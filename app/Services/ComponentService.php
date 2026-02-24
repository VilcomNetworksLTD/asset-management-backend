// Inside your getStats() method
public function getStats()
{
    return [
        'total_assets'      => \App\Models\Asset::count(),
        'total_licenses'    => \App\Models\License::count(),
        'total_accessories' => \App\Models\Accessory::count(),
        'total_consumables' => \App\Models\Consumables::count(),
        'total_components'  => \App\Models\Component::count(), // Added this
        'total_users'       => \App\Models\User::count(),
    ];
}