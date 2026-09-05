<?php

namespace App\Services;

use App\Enums\LessonQuizStatus;
use App\Enums\SettingKey;
use App\Models\LessonQuizQuestion;
use App\Models\Module;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Builds a module's certification quiz by sampling the question banks of
 * its lessons. There is no stored "module quiz" — each attempt draws a
 * fresh set, so two takes of the same module differ.
 */
class ModuleQuizAssembler
{
    /**
     * Draw a balanced, randomised set of questions for one attempt.
     *
     * Balance is even coverage per lesson: the configured total is split
     * as evenly as possible across the lessons that have questions, so no
     * single lesson dominates a take. Lessons without a published quiz are
     * skipped rather than blocking the module.
     *
     * @return Collection<int, LessonQuizQuestion>
     */
    public function assemble(Module $module): Collection
    {
        $banks = $this->banksFor($module);

        if ($banks->isEmpty()) {
            return collect();
        }

        $target = (int) Setting::get(SettingKey::ModuleQuizQuestionCount);

        return $this->drawEvenly($banks, $target);
    }

    /**
     * How many questions a module could offer in total — used to tell an
     * admin whether a module is ready to certify against.
     */
    public function bankSize(Module $module): int
    {
        return $this->banksFor($module)->sum(fn (Collection $bank) => $bank->count());
    }

    /**
     * Fingerprint of everything currently eligible to be drawn, so a
     * started attempt can tell whether the module has changed underneath
     * it — a lesson added or removed, a quiz published or unpublished, or
     * questions regenerated all shift this.
     *
     * Built from question ids, so editing a question's wording in place
     * does NOT invalidate an open attempt; only the composition of the
     * bank does.
     */
    public function bankFingerprint(Module $module): string
    {
        $ids = $this->banksFor($module)
            ->flatten()
            ->pluck('id')
            ->sort()
            ->values()
            ->implode(',');

        return hash('sha256', $ids);
    }

    /**
     * Whether this module has anything to draw from at all.
     */
    public function isReady(Module $module): bool
    {
        return $this->bankSize($module) > 0;
    }

    /**
     * Each lesson's published question bank, keyed by lesson id. Lessons
     * with no published quiz, or a published quiz with no questions, are
     * omitted entirely.
     *
     * @return Collection<int, Collection<int, LessonQuizQuestion>>
     */
    private function banksFor(Module $module): Collection
    {
        return $module->lessons()
            ->with('quiz.questions')
            ->get()
            ->mapWithKeys(fn ($lesson) => [
                $lesson->id => $lesson->quiz?->status === LessonQuizStatus::Published
                    ? $lesson->quiz->questions
                    : collect(),
            ])
            ->filter(fn (Collection $bank) => $bank->isNotEmpty());
    }

    /**
     * Spread the target across banks as evenly as their sizes allow.
     *
     * Runs in passes, taking at most one question per lesson per pass, so
     * a lesson with a small bank simply drops out once exhausted and the
     * remaining slots redistribute — rather than a lesson with a large
     * bank crowding out the others.
     *
     * @param  Collection<int, Collection<int, LessonQuizQuestion>>  $banks
     * @return Collection<int, LessonQuizQuestion>
     */
    private function drawEvenly(Collection $banks, int $target): Collection
    {
        $pools = $banks->map(fn (Collection $bank) => $bank->shuffle()->values())->all();
        $drawn = collect();

        while ($drawn->count() < $target) {
            $tookAny = false;

            // Lesson order is shuffled each pass so the remainder — when
            // the target doesn't divide evenly — isn't always taken from
            // the same lessons. Shuffles the keys, not the collection:
            // Collection::shuffle() reindexes, which would lose lesson ids.
            foreach (collect(array_keys($pools))->shuffle() as $lessonId) {
                if ($drawn->count() >= $target) {
                    break;
                }

                if ($pools[$lessonId]->isEmpty()) {
                    continue;
                }

                $drawn->push($pools[$lessonId]->shift());
                $tookAny = true;
            }

            // Every bank exhausted before reaching the target — the module
            // simply offers a shorter quiz.
            if (! $tookAny) {
                break;
            }
        }

        return $drawn->shuffle()->values();
    }
}
