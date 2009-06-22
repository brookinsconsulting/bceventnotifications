<?php
//
// Definition of EventStartNotification Cronjob
//
// Created on: <01-Feb-2008 00:06:00 gb>
//
// COPYRIGHT NOTICE: 2001-2008 Brookins Consulting. All rights reserved.
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0 (or later) of the GNU
//   General Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301,  USA.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html
//
// Contact licence@brookinsconsulting.com if any conditions
// of this licencing isn't clear to you.
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##

/*! \file eventstartnotification.php
    \brief The cronjob that handles user notification of events starting time through email.
*/

// include_once( 'lib/ezutils/classes/ezini.php' );
// include_once( 'extension/bceventnotifications/classes/bceventnotifications.php' );

// Main - Check all the user accounts for events starting and send notification email

$c = new BCEventNotifications();
$ret = $c->sendEventStartNotifications();
print_r( $ret );

?>