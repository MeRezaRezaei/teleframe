<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

interface VersionedProgram
{
    public function getVersion(): string;
    
    public function getSchemaVersion(): string;
    
    public function getState(): array;
    
    public function setState(array $state): void;
    
    public function migrateFrom(string $fromVersion): void;
    
    public function rollbackTo(string $toVersion): void;
    
    public function getStateSchema(): array;
    
    public function validateState(): bool;
}

