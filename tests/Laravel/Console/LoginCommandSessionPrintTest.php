<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use Symfony\Component\Console\Input\ArrayInput as SymfonyArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Finding H3: the login wizard printed the FULL session string to stdout
 * unconditionally, BEFORE the "save it to .env?" prompt — so an operator
 * who chose to store the credential safely still got it echoed, where
 * `| tee login.log`, a CI job or a screen share keeps it in cleartext.
 *
 * SessionData::exportString() is base64(dcId:base64(authKey):userId:delta:salt)
 * — the COMPLETE auth key, i.e. full account takeover, which is why
 * Vault\TelegramAccount treats a session string as a credential and never
 * logs one.
 *
 * The interactive parts (the confirm prompt, the real .env write) are
 * stubbed: what is under test is the OUTPUT, not the wizard's flow.
 */
final class LoginCommandSessionPrintTest extends TestCase
{
    public function test_the_session_string_is_not_printed_by_default(): void
    {
        [$command, $output, $session] = $this->harness(showSession: false);

        $command->runFinalize($session);

        $text = $output->fetch();
        self::assertStringNotContainsString(
            $session->exportString(),
            $text,
            'the full session string (the complete auth key) must never reach stdout unasked',
        );
        self::assertStringNotContainsString('Exported Session String', $text);
        self::assertStringContainsString('--show-session', $text, 'the operator is told how to get it if they really want it');
    }

    public function test_the_truncated_preview_is_still_shown(): void
    {
        [$command, $output, $session] = $this->harness(showSession: false);

        $command->runFinalize($session);

        $text = $output->fetch();
        self::assertStringContainsString('Session String', $text, 'the table still summarises the credential');
        self::assertStringContainsString(
            substr($session->exportString(), 0, 24),
            $text,
            'a truncated preview is a shape hint, not a credential',
        );
    }

    public function test_the_raw_string_prints_only_behind_the_explicit_opt_in(): void
    {
        [$command, $output, $session] = $this->harness(showSession: true);

        $command->runFinalize($session);

        $text = $output->fetch();
        self::assertStringContainsString(
            $session->exportString(),
            $text,
            '--show-session is the explicit, documented opt-in',
        );
    }

    public function test_the_command_declares_the_opt_in_flag(): void
    {
        $input = new SymfonyArrayInput([]);
        $command = new LoginCommand;
        $command->setInput($input);

        self::assertTrue(
            $command->getDefinition()->hasOption('show-session'),
            'the opt-in must be a real, discoverable flag, not a hidden env var',
        );
    }

    /**
     * @return array{0: LoginSessionProbe, 1: BufferedOutput, 2: SessionData}
     */
    private function harness(bool $showSession): array
    {
        $command = new LoginSessionProbe;
        $buffer = new BufferedOutput;
        $input = new SymfonyArrayInput($showSession ? ['--show-session' => true] : []);
        $input->bind($command->getDefinition()); // so $this->option() resolves the real flags

        $command->setLaravel($this->app);
        $command->setInput($input);
        $command->setOutput(new OutputStyle($input, $buffer));

        $session = new SessionData(dcId: 2, authKey: random_bytes(256), userId: 4242);

        return [$command, $buffer, $session];
    }
}

/**
 * Test probe: drives the protected finalizeLogin() seam with the
 * interactive .env prompt stubbed out (no filesystem writes, no TTY) so
 * only the OUTPUT contract is under test.
 */
final class LoginSessionProbe extends LoginCommand
{
    /**
     * Command::run() normally builds the $components factory; the probe
     * calls finalizeLogin() directly, so it installs one here (otherwise
     * every ->components->info() is a null call).
     */
    public function bootComponents(): void
    {
        $this->components = new Factory($this->output);
    }

    public function runFinalize(SessionData $session): int
    {
        $this->bootComponents();

        return $this->finalizeLogin($session, 'TELEGRAM_SESSION', 'User Account (MTProto)');
    }

    /** Never write a .env in a test; report the "declined" branch instead. */
    public function confirm($question, $default = false): bool
    {
        return false;
    }
}
