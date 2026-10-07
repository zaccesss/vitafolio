<?php

namespace App\Console\Commands;

use App\Support\Locales;
use App\Support\TranslationKeys;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vitafolio:translations')]
#[Description('Rewrite lang/en.json from the interface strings in the code and list what each language still needs')]
class SyncTranslations extends Command
{
    public function handle(): int
    {
        $keys = TranslationKeys::all();
        $english = array_combine($keys, $keys);
        file_put_contents(lang_path('en.json'), json_encode($english, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info(count($keys).' strings written to lang/en.json.');

        $gaps = 0;
        foreach (array_keys(Locales::ALL) as $code) {
            if ($code === Locales::DEFAULT) {
                continue;
            }
            $path = lang_path($code.'.json');
            $translated = is_file($path) ? (array) json_decode((string) file_get_contents($path), true) : [];
            $missing = array_diff($keys, array_keys($translated));
            $unused = array_diff(array_keys($translated), $keys);
            $gaps += count($missing);
            $this->line(sprintf('%-6s %d missing, %d no longer used', $code, count($missing), count($unused)));
        }

        return $gaps === 0 ? self::SUCCESS : self::FAILURE;
    }
}
