<?php

namespace Keepsuit\LaravelLiquid\Support\Clockwork;

use Clockwork\Request\Timeline\Timeline;
use Keepsuit\Liquid\Profiler\Profile;
use Keepsuit\Liquid\Profiler\Profiler;
use Keepsuit\Liquid\Profiler\ProfileType;

class ProfilerClockworkDumper
{
    protected int $lastId = 1;

    public function dump(Profiler $profiler): Timeline
    {
        $timeline = new Timeline;

        foreach ($profiler->getProfiles() as $profile) {
            if (! $profile->isClosed()) {
                continue;
            }

            $this->dumpProfile($profile, $timeline);
        }

        return $timeline;
    }

    protected function dumpProfile(Profile $profile, Timeline $timeline, ?int $parentId = null): void
    {
        //        if ($profile->type !== ProfileType::Template) {
        //            foreach ($profile->getChildren() as $child) {
        //                $this->dumpProfile($child, $timeline, $parentId);
        //            }
        //            return;
        //        }

        $id = $this->lastId++;
        $name = match ($profile->type) {
            ProfileType::Tag, ProfileType::Variable => sprintf('%s (%s)', $profile->type->value, $profile->name),
            default => $profile->name,
        };

        foreach ($profile->getChildren() as $child) {
            $this->dumpProfile($child, $timeline, $id);
        }

        $timeline->event($name, [
            'name' => $id,
            'start' => $profile->getStartTime(),
            'end' => $profile->getEndTime(),
            'data' => [
                'data' => [],
                'memoryUsage' => $profile->getMemoryUsage(),
                'parent' => $parentId,
            ],
        ]);
    }
}
