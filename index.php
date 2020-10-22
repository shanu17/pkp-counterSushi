<?php

/**
 * @defgroup plugins_generic_counterSushi
 */
 
/**
 * @file plugins/generic/counterSushi/index.php
 *
 * Copyright (c) 2019 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_counterSushi
 * @brief Wrapper for COUNTERR5 SUSHI Plugin
 *
 */

require_once('CounterSushiPlugin.inc.php');

return new CounterSushiPlugin();
