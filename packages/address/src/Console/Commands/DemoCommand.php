<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Enums\ModelEnum as AddressModelEnum;
use Capell\Address\Models\Address;
use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    use HasSitesOption;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts demo address content into the selected site(s).';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-address:demo {--sites=}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteOptions = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : null);
        } else {
            $siteOptions = $this->getDemoSites();
        }

        $sites = CapellCore::getModel(ModelEnum::Site)::query()
            ->with(['language', 'languages'])
            ->whereIn('name', $siteOptions)
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', (array) $siteOptions));

            return Command::FAILURE;
        }

        $sites->each(function (Site $site): void {
            $this->newLine();
            $this->line(sprintf('Selected site: %s', $site->name));

            $meta = $site->meta ?? [];

            $address = $this->setupAddress();

            // Ensure meta is always an array and not null or a casted object
            if (! is_array($meta)) {
                $meta = (array) $meta;
            }

            $meta['address_id'] = $address->id;
            $site->meta = $meta;
            $site->save();

            $this->line('Demo address content has been successfully created for site: ' . $site->name);
        });

        $this->line('Address demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function setupCountry()
    {
        $countryModel = CapellCore::getModel(AddressModelEnum::Country);

        return $countryModel::query()->firstOrCreate(['iso2' => 'US'], [
            'name' => 'United States',
            'iso2' => 'US',
            'iso3' => 'USA',
            'language_id' => CapellCore::getModel(ModelEnum::Language)::query()->where('code', 'en')->first()->id,
        ]);
    }

    private function setupAddress(): Address
    {
        /** @var Address $address */
        $address = CapellCore::getModel(AddressModelEnum::Address)::query()->firstOrCreate([
            'line1' => '123 Main St',
            'city' => 'Anytown',
            'postal_code' => '12345',
            'country_id' => $this->setupCountry()->id,
        ], [
            'name' => 'Headquarters',
            'line2' => 'Suite 100',
            'state' => 'CA',
            'meta' => [
                'latitude' => 34.0522,
                'longitude' => -118.2437,
            ],
        ]);

        return $address;
    }
}
