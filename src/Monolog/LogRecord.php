<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog;

use ArrayAccess;

/**
 * Monolog log record
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @template-implements \ArrayAccess<'message'|'level'|'context'|'level_name'|'channel'|'datetime'|'extra', int|string|\DateTimeImmutable|array<mixed>>
 * @phpstan-import-type Level from Logger
 * @phpstan-import-type LevelName from Logger
 */
class LogRecord implements \ArrayAccess
{
    private const MODIFIABLE_FIELDS = [
        'extra' => true,
        'formatted' => true,
    ];

    /** @var 'DEBUG'|'INFO'|'NOTICE'|'WARNING'|'ERROR'|'CRITICAL'|'ALERT'|'EMERGENCY' */
    public readonly string $levelName; // TODO enum?

    public function __construct(
        public readonly \DateTimeImmutable $datetime,
        public readonly string $channel,
        /** @var Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY */
        public readonly int $level, // TODO enum?
        public readonly string $message,
        /** @var array<mixed> */
        public readonly array $context = [],
        /** @var array<mixed> */
        public array $extra = [],
        public mixed $formatted = null,
    ) {
        $this->levelName = Logger::getLevelName($level);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === 'extra') {
            if (!is_array($value)) {
                throw new \InvalidArgumentException('extra must be an array');
            }

            $this->extra = $value;
            return;
        }

        if ($offset === 'formatted') {
            $this->formatted = $value;
            return;
        }

        throw new \LogicException('Unsupported operation: setting '.$offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        if ($offset === 'level_name') {
            return true;
        }

        return isset($this->{$offset});
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Unsupported operation');
    }

    public function &offsetGet(mixed $offset): mixed
    {
        if ($offset === 'level_name') {
            $offset = 'levelName';
        }

        if (isset(self::MODIFIABLE_FIELDS[$offset])) {
            return $this->{$offset};
        }

        // avoid returning readonly props by ref as this is illegal
        $copy = $this->{$offset};

        return $copy;
    }

    /**
     * @phpstan-return array{message: string, context: mixed[], level: Level, level_name: LevelName, channel: string, datetime: \DateTimeImmutable, extra: mixed[]}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context,
            'level' => $this->level,
            'level_name' => $this->levelName,
            'channel' => $this->channel,
            'datetime' => $this->datetime,
            'extra' => $this->extra,
        ];
    }

    public function with(mixed ...$args): self
    {
        foreach (['message', 'context', 'level', 'channel', 'datetime', 'extra'] as $prop) {
            $args[$prop] ??= $this->{$prop};
        }

        return new self(...$args);
    }
}
