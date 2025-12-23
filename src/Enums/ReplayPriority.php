<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Enums;

/**
 * Priority levels for replay operations in the Forrst replay extension.
 *
 * Defines execution priority for queued replay operations. Higher priority
 * replays are processed before lower priority ones when multiple replays
 * are queued. Priority affects scheduling order but does not guarantee
 * immediate execution.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/replay
 */
enum ReplayPriority: string
{
    /**
     * High priority replay operations processed before normal and low priority.
     *
     * Used for time-sensitive or critical replays that should be processed
     * as soon as possible when the replay queue has capacity.
     */
    case High = 'high';

    /**
     * Normal priority replay operations processed in standard queue order.
     *
     * Default priority level for most replay operations. Provides balanced
     * scheduling without preferential treatment or deferral.
     */
    case Normal = 'normal';

    /**
     * Low priority replay operations processed after normal and high priority.
     *
     * Used for non-urgent replays that can be deferred when higher priority
     * work is available. Useful for batch operations or background tasks.
     */
    case Low = 'low';
}
