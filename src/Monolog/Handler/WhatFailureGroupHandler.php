<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\LogRecord;
use Throwable;

/**
 * Forwards records to multiple handlers suppressing failures of each handler
 * and continuing through to give every handler a chance to succeed.
 *
 * @author Craig D'Amelio <craig@damelio.ca>
 */
class WhatFailureGroupHandler extends GroupHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(LogRecord $record): bool
    {
        if ($this->processors) {
            $record = $this->processRecord($record);
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handle($record);
            } catch (Throwable) {
                // What failure?
            }
        }

        return false === $this->bubble;
    }

    /**
     * {@inheritDoc}
     */
    public function handleBatch(array $records): void
    {
        if ($this->processors) {
            $processed = array();
            foreach ($records as $record) {
                $processed[] = $this->processRecord($record);
            }
            $records = $processed;
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handleBatch($records);
            } catch (Throwable) {
                // What failure?
            }
        }
    }
}
