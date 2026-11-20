<?php

namespace App\Services\Chatbot;

use App\Models\User;

/**
 * Looks up a step-by-step guide (config/guides.php) for the person who asked.
 * A guide is only given to its audience. A visitor who asks for a guide that needs
 * an account is first shown where to log in, so nobody is sent to a page they
 * cannot open.
 */
class GuideCatalog
{
    /** @return list<string> ids the model or the FAQ may use */
    public function ids(): array
    {
        return array_keys(config('guides'));
    }

    /** One line per guide, for the language model prompt. @return array<string, string> */
    public function describe(): array
    {
        return collect(config('guides'))->map(fn (array $g, string $id) => $id.' (for '.implode('/', $g['audience']).'): '.($g['steps'][0]['text'] ?? ''))->all();
    }

    /**
     * @return array{id: string, steps: list<array<string, string>>}|null
     */
    public function resolve(?string $id, ?User $user): ?array
    {
        $guide = $id ? config("guides.{$id}") : null;
        if (! $guide) {
            return null;
        }

        $audience = $this->audienceOf($user);
        $steps = $guide['steps'];

        if (! in_array($audience, $guide['audience'], true)) {
            // A visitor asking for a signed-in guide: show where to log in. Anyone else: no guide.
            if ($audience !== 'visitor') {
                return null;
            }

            $steps = [[
                'url' => '/',
                'target' => 'home-login',
                'text' => 'Log in first, then ask me again and I will show you.',
                'text_tl' => 'Mag-log in muna, tapos magtanong ulit at ipapakita ko.',
            ]];
        }

        return ['id' => $id, 'steps' => $steps];
    }

    private function audienceOf(?User $user): string
    {
        return match (true) {
            $user === null => 'visitor',
            $user->can('students.view') => $user->can('reports.view') ? 'admin' : 'staff',
            default => 'student',
        };
    }
}
