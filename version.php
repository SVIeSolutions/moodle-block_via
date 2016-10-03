<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Via Block - version details
 *
 * @package    contrib
 * @package   block_via
 * @copyright 1999 onwards SVIeSolutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2016042002;
$plugin->requires = 2011030300;  // Moodle version required to run it.
$plugin->component = 'block_via';  // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_STABLE; // This is considered as ready for production sites.
$plugin->release = 'v2.7-r2'; // This is our first revision for Moodle 2.7.x branch.

$plugin->dependencies = array(
    'mod_via' => 2016042000,   // The Via activity must be present.
);
