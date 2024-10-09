<?php

namespace App\Commands;

use App\Client\Support\DefaultDomainNodeVisitor;
use App\Client\Support\DefaultServerNodeVisitor;
use App\Client\Support\InsertDefaultDomainNodeVisitor;
use App\Commands\SetUpExposeDefaultDomain;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Laravel\Prompts\confirm;
use function Termwind\render;

class SetDefaultDomainCommand extends Command
{

    protected $signature = 'default-domain {domain?} {--server=}';

    protected $description = 'Set or retrieve the default domain to use with Expose.';

    public function handle()
    {
        $domain = $this->argument('domain');
        $server = $this->option('server');

        if (! is_null($domain)) {

            render("<div class='ml-3'>✔ Set Expose default domain to <span class='font-bold'>$domain</span>" . ($server ? " on server <span class='font-bold'>$server</span>" : '') . ".</div>");

            $configFile = implode(DIRECTORY_SEPARATOR, [
                $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
                '.expose',
                'config.php',
            ]);

            if (! file_exists($configFile)) {
                @mkdir(dirname($configFile), 0777, true);
                $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'), $domain, $server);
            } else {
                $updatedConfigFile = $this->modifyConfigurationFile($configFile, $domain, $server);
            }

            file_put_contents($configFile, $updatedConfigFile);

            return;
        }

        if($this->option('no-interaction')) {
            $this->line(config('expose.default_domain'));
            return;
        }

        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');

        if (is_null($domain = config('expose.default_domain'))) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no default domain specified.</div>');
        } else {
            render("<div class='ml-3'>Current default domain: <span class='font-bold'>$domain</span>.</div>");
        }


        if(confirm('Would you like to set a new default domain?', false)) {
            (new SetUpExposeDefaultDomain)(config('expose.auth_token'));
        }
    }

    protected function modifyConfigurationFile(string $configFile, string $domain, ?string $server)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $defaultDomainNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_domain';
        });

        if (is_null($defaultDomainNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertDefaultDomainNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new DefaultDomainNodeVisitor($domain));

        if (! is_null($server)) {
            $nodeTraverser->addVisitor(new DefaultServerNodeVisitor($server));
        }

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
