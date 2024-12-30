<?php

namespace Keepsuit\LaravelLiquid\Support\Clockwork;

use Clockwork\DataSource\DataSource;
use Clockwork\Request\Request;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Extensions\ProfilerExtension;
use Keepsuit\Liquid\Profiler\Profiler;

class LiquidDataSource extends DataSource
{
    protected Profiler $profiler;

    public function __construct(protected Environment $liquidEnvironment) {}

    public function listenToEvents(): void
    {
        $this->profiler = new Profiler;
        $this->liquidEnvironment->addExtension(new ProfilerExtension($this->profiler));
    }

    public function resolve(Request $request): Request
    {
        if ($this->profiler->getProfiles() === []) {
            return $request;
        }

        $timeline = (new ProfilerClockworkDumper)->dump($this->profiler);

        $request->viewsData = array_merge($request->viewsData, $timeline->finalize());

        return $request;
    }
}
