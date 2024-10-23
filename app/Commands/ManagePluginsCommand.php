<?php

namespace Expose\Client\Commands;

use Expose\Client\Logger\Plugins\PluginManager;
use Expose\Client\Commands\Concerns\RendersBanner;
use Expose\Client\Commands\Concerns\RendersOutput;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\multiselect;
use function Termwind\render;

class ManagePluginsCommand extends Command
{
    use RendersBanner, RendersOutput;

    protected $signature = 'plugins:manage {--add=}';

    protected $description = 'Activate and deactivate request plugins.';

    protected PluginManager $pluginManager;


    public function handle(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;

        $plugins = $this->pluginManager->getPlugins();

        if($this->option('add')) {
            $this->addPlugin($this->option('add'));
            return;
        }


        $this->renderBanner();

        render('<div class="ml-3">Explanation text about request plugins goes here.</div>'); // TODO:


        // Build key-based list for easier Windows support
        $activePlugins = collect($this->pluginManager->getEnabledPlugins())
            ->map(function ($pluginClass) {
                return class_basename($pluginClass);
            })
            ->toArray();


        $pluginSelectList = collect($plugins)
            ->mapWithKeys(function ($pluginClass) {
                return [str($pluginClass)->afterLast('\\')->__toString() => $pluginClass];
            })
            ->toArray();

        $pluginsToEnable = multiselect(
            'Select the plugins you want to enable:',
            $pluginSelectList,
            $activePlugins);


        $pluginsToEnable = collect($pluginsToEnable)
            ->map(function ($pluginClass) use ($pluginSelectList) {
                return $pluginSelectList[$pluginClass];
            })
            ->values()
            ->toArray();

        $this->pluginManager->modifyPluginConfiguration($pluginsToEnable);

        render("<div class='ml-3'>✔ Request plugins have been updated.</div>");
    }

    protected function addPlugin(string $class): void {

        if (!str($class)->contains('\\')) {
            $this->renderWarning("<span class='font-bold'>$class</span> is not a fully qualified class name. Please try something line <span class='font-bold'>plugins:manage --add=Expose\\\Client\\\Logger\\\Plugins\\\MyCustomPlugin</span>.");
            return;
        }

        $pluginsToEnable = collect($this->pluginManager->getEnabledPlugins())
            ->prepend($class)
            ->unique()
            ->values()
            ->toArray();

        $this->pluginManager->modifyPluginConfiguration($pluginsToEnable);
    }

}
