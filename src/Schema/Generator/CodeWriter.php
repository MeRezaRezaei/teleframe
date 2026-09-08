<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

/** Renders deterministic PHP files. */
final class CodeWriter
{
    public const BANNER = '// GENERATED — do not edit; run artisan teleframe:regenerate';

    /** @param list<string> $bodyLines */
    public static function phpFile(string $namespace, array $bodyLines, string $banner = self::BANNER): string
    {
        $out = "<?php\n\n" . $banner . "\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n";
        foreach ($bodyLines as $line) {
            $out .= $line . "\n";
        }
        return $out;
    }

    /** @param list<string> $upLines @param list<string> $downLines */
    public static function migrationFile(array $upLines, array $downLines, string $banner = self::BANNER): string
    {
        $out = "<?php\n\n" . $banner . "\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\DB;\nuse Illuminate\\Support\\Facades\\Schema;\n\n";
        $out .= "return new class extends Migration\n{\n";
        $out .= "    public function up(): void\n    {\n";
        foreach ($upLines as $line) {
            $out .= $line === '' ? "\n" : '        ' . $line . "\n";
        }
        $out .= "    }\n\n";
        $out .= "    public function down(): void\n    {\n";
        foreach ($downLines as $line) {
            $out .= $line === '' ? "\n" : '        ' . $line . "\n";
        }
        $out .= "    }\n};\n";
        return $out;
    }
}
