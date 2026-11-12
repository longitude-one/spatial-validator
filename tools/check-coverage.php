<?php

/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
 */

declare(strict_types=1);

$argv = [__FILE__, __DIR__.'/../build/coverage/clover.xml', '100'];
require __DIR__.'/../quality/coverage-check.php';
