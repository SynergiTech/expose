<?php

namespace Expose\Client\Commands;

use Expose\Client\Logger\Plugins\PluginManager;
use LaravelZero\Framework\Commands\Command;

use function Expose\Common\banner;
use function Expose\Common\info;
use function Laravel\Prompts\table;

class ListPluginsCommand extends Command
{


    protected $signature = 'plugins';

    protected $description = 'List all active request plugins.';

    public function handle(PluginManager $pluginManager)
    {

        banner();

        info('Explanation text about request plugins goes here.'); // TODO:

        $defaultPlugins = $pluginManager->getDefaultPlugins();
        $customPlugins = $pluginManager->getCustomPlugins();

        $pluginTable = collect(array_merge($defaultPlugins, $customPlugins))
            ->map(function ($pluginClass) use ($defaultPlugins, $pluginManager) {
                return [
                    'Plugin' => $pluginClass,
                    'Type' => in_array($pluginClass, $defaultPlugins) ? 'Default' : 'Custom',
                    'Active' => $pluginManager->isEnabled($pluginClass) ? '✔' : '✘',
                ];
            })
            ->toArray();


        table(
            headers: ['Plugin', 'Type', 'Active'],
            rows: $pluginTable
        );

        info('Use the <span class="font-bold">plugins:manage</span> command to activate and deactivate request plugins.');
    }

}
