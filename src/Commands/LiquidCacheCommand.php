<?php

namespace Keepsuit\LaravelLiquid\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'liquid:cache')]
class LiquidCacheCommand extends Command
{
    protected $signature = 'liquid:cache';

    protected $description = "Compile all of the application's Liquid templates";

    public function handle(Factory $view): int
    {
        $compiler = $this->laravel->make('liquid.compiler');
        $finder = $compiler->getViewFinder();

        $extensions = Collection::make($view->getExtensions())
            ->filter(fn (string $engine) => $engine === 'liquid')
            ->keys()
            ->map(fn (string $extension) => "*.{$extension}")
            ->all();

        $roots = array_map(fn (string $path) => ['', $path], $finder->getPaths());

        foreach ($finder->getHints() as $namespace => $paths) {
            foreach ($paths as $path) {
                $roots[] = ["{$namespace}::", $path];
            }
        }

        $count = 0;

        foreach ($roots as [$prefix, $root]) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (Finder::create()->in($root)->exclude('vendor')->name($extensions)->files() as $file) {
                $name = $prefix.str_replace(['/', '\\'], '.', Str::beforeLast($file->getRelativePathname(), '.'));

                $compiler->compile($finder->find($name));
                $count++;
            }
        }

        $this->components->info("{$count} Liquid templates cached successfully.");

        return self::SUCCESS;
    }
}
