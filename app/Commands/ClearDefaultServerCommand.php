<?php

namespace App\Commands;

use App\Client\Support\ClearServerNodeVisitor;
use App\Client\Support\InsertDefaultServerNodeVisitor;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Termwind\render;

class ClearDefaultServerCommand extends Command
{
    protected $signature = 'default-server:clear';

    protected $description = 'Clear the default server to use with Expose.';

    public function handle()
    {
        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'config.php',
        ]);

        if (! file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'));
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile);
        }

        file_put_contents($configFile, $updatedConfigFile);

        render("<div class='ml-3'>✔ Cleared the Expose default server.</div>");
    }

    protected function modifyConfigurationFile(string $configFile)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $defaultServerNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_server';
        });

        if (is_null($defaultServerNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertDefaultServerNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new ClearServerNodeVisitor());

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
