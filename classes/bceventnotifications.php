<?php
//
// Definition of BCEventNotifications class
// Created on: <10-19-2007 23:42:02 gb>
//
// COPYRIGHT NOTICE: 2001-2007 Brookins Consulting. All rights reserved.
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

/*!
 \file bceventnotifications.php
*/

/*!
 \class BCEventNotifications bceventnotifications.php
 \brief The class BCEventNotifications handles starting and ending event notifications
*/

class BCEventNotifications
{
    /*!
     Constructor
    */
    function BCEventNotifications()
    {
    }

    /*!
     Check user expire attribute for expired membership
    */
    function sendEventStartNotifications()
    {
        $ret = false;

        // Settings
        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventClassIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassIdentifier" );
        $userClassIdentifier = $ini->variable( "EventNotificationsSettings", "UserClassIdentifier" );
        $userGroupNodeID = $ini->variable( "EventNotificationsSettings", "UserGroupNodeID" );
        $eventCalendarNodeID = $ini->variable( "EventNotificationsSettings", "EventCalendarNodeID" );
        $administratorUserID = $ini->variable( "EventNotificationsSettings", "AdministratorUserID" );

        // Change Script Session User to Privilaged Role User, Admin
        $this->loginDifferentUser( $administratorUserID );

        // Fetch users in user group
        $groupNode = eZContentObjectTreeNode::fetch( $userGroupNodeID );
        $groupConditions = array( 'ClassFilterType' => 'include', 'ClassFilterArray' => array( $userClassIdentifier ) );
        $groupUsers = $groupNode->subTree( $groupConditions, $userGroupNodeID );

        foreach( $groupUsers as $user )
        {
            // Fetch subtree notification rules
            $userID = $user->attribute( 'contentobject_id' );
            $userSubtreeNotificationRules = eZSubtreeNotificationRule::fetchList( $userID, true, false, false );
            $userSubtreeNotificationRulesCount = eZSubtreeNotificationRule::fetchListCount( $userID );

            if ( $userSubtreeNotificationRulesCount != 0 )
            {
                foreach ( $userSubtreeNotificationRules as $rule )
                {
                    // Check each node in rule for event starting
                    $ruleNodeID = $rule->attribute( 'node_id' );
                    $node = eZContentObjectTreeNode::fetch( $ruleNodeID );

                    if ( $this->isEventStarting( $node ) )
                        $this->sendEventStartNotificationEmail( $user, $node );
                }
            }

        }
        return $ret;
    }

    /*!
     Check user expire attribute for expired membership
    */
    function sendEventEndNotifications()
    {
        $ret = false;

        // Settings
        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventClassIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassIdentifier" );
        $userClassIdentifier = $ini->variable( "EventNotificationsSettings", "UserClassIdentifier" );
        $userGroupNodeID = $ini->variable( "EventNotificationsSettings", "UserGroupNodeID" );
        $eventCalendarNodeID = $ini->variable( "EventNotificationsSettings", "EventCalendarNodeID" );
        $administratorUserID = $ini->variable( "EventNotificationsSettings", "AdministratorUserID" );

        // Change Script Session User to Privilaged Role User, Admin
        $this->loginDifferentUser( $administratorUserID );

        // Fetch users in user group
        $groupNode = eZContentObjectTreeNode::fetch( $userGroupNodeID );
        $groupConditions = array( 'ClassFilterType' => 'include', 'ClassFilterArray' => array( $userClassIdentifier ) );
        $groupUsers = $groupNode->subTree( $groupConditions, $userGroupNodeID );

        foreach( $groupUsers as $user )
        {
            // Fetch subtree notification rules
            $userID = $user->attribute( 'contentobject_id' );
            $userSubtreeNotificationRules = eZSubtreeNotificationRule::fetchList( $userID, true, false, false );
            $userSubtreeNotificationRulesCount = eZSubtreeNotificationRule::fetchListCount( $userID );

            if ( $userSubtreeNotificationRulesCount != 0 )
            {
                foreach ( $userSubtreeNotificationRules as $rule )
                {
                    // Check each node in rule for event ending
                    $ruleNodeID = $rule->attribute( 'node_id' );
                    $node = eZContentObjectTreeNode::fetch( $ruleNodeID );

                    if ( $this->isEventEnding( $node ) )
                        $this->sendEventEndNotificationEmail( $user, $node );
                }
            }

        }
        return $ret;
    }

    /*!
     Check event is starting
    */
    function isEventStarting( $event )
    {
        // Settings
        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventClassIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassIdentifier" );
        $eventStartDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierStartDateTime" );

        if ( $eventClassIdentifier == $event->ClassIdentifier )
        {
            // Event Start Attribute
            $dm=$event->dataMap();
            $eventStartDateAttribute=$dm[$eventStartDateTimeIdentifier];
            $eventStartDate=$eventStartDateAttribute->content();

            // Event Start DateTime Object
            $eventDateTime = new eZDateTime();
            $eventDateTime->setTimeStamp( $eventStartDate->timeStamp() );
            $eventDateTimeString = $eventDateTime->timeStamp();

            // Start time, Start of Hour
            $startOfCurrentHour = new eZDateTime();
            $startOfCurrentHour->adjustDateTime( 1, 0, 0, 0, 0, 0 );
            $startOfCurrentHour->setMinute( 0 );
            $startOfCurrentHour->setSecond( 0 );
            $startOfCurrentHourString = $startOfCurrentHour->timeStamp();

            // End time, End of Hour
            $endOfCurrentHour = new eZDateTime();
            $endOfCurrentHour->setMinute( 0 );
            $endOfCurrentHour->setSecond( 0 );
            $endOfCurrentHour->adjustDateTime( 1, 59, 59, 0, 0, 0 );
            $endOfCurrentHourString = $endOfCurrentHour->timeStamp();

            if( $eventDateTime->isGreaterThan( $startOfCurrentHour, true ) && $eventDateTimeString <= $endOfCurrentHourString )
            {
                $ret = true;
            }
            // if ($ret == true)
                // die('true');
                        
        }
        return $ret;
    }

    /*!
     Check event is ending
    */
    function isEventEnding( $event )
    {
        // Settings
        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventClassIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassIdentifier" );
        $eventEndDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierEndDateTime" );

        if( $eventClassIdentifier == $event->ClassIdentifier )
        {
            // Event Start Attribute
            $dm=$event->dataMap();
            $eventStartDateAttribute=$dm[$eventEndDateTimeIdentifier];
            $eventStartDate=$eventStartDateAttribute->content();

            // Event Start DateTime Object
            $ct = new eZDateTime();
            $ts = $ct->timeStamp();

            // Event Start DateTime Object
            $eventDateTime = new eZDateTime();
            $eventDateTime->setTimeStamp( $eventStartDate->timeStamp() );
            $eventDateTimeString = $eventDateTime->timeStamp();

            // Start time, Start of Hour
            $startOfCurrentHour = new eZDateTime();
            $startOfCurrentHour->adjustDateTime( -1, 0, 0, 0, 0, 0 );
            // $startOfCurrentHour->setMinute( 0 );
            // $startOfCurrentHour->setSecond( 0 );
            $startOfCurrentHourString = $startOfCurrentHour->timeStamp();

            // End time, End of Hour
            $endOfCurrentHour = new eZDateTime();
            $endOfCurrentHour->setMinute( 0 );
            $endOfCurrentHour->setSecond( 0 );
            $endOfCurrentHour->adjustDateTime( -1, 59, 59, 0, 0, 0 );
            $endOfCurrentHourString = $endOfCurrentHour->timeStamp();

            // Debug
            $eventName = $event->attribute('name');
            print_r( 'Name: '.  $eventName );
            print_r("\n" );

            if( $eventDateTime->isGreaterThan( $startOfCurrentHour, true ) && $eventDateTimeString <= $ts )
            {
                $ret = true;
                print_r("Notify users: TRUE\n");

                // Debug
                /*

                // Fetch current datetime object
                $cDateTime = new eZDateTime();
                $cDateTimeString = $cDateTime->timeStamp();


                print_r("\neventDateTime         : $eventDateTimeString\n\n");
                print_r("Now              : $cDateTimeString\n");
                print_r("(lte) startOfCurrentHour: $startOfCurrentHourString \n");
                print_r("(lte) endOfCurrentHour: $endOfCurrentHourString \n\n");


                 print_r("\n" );
                 print_r( 'Now: '. $cDateTimeString .' == '. $cDateTime->toString() );
                 print_r("\n");


                print_r( 'cStart: '. $startOfCurrentHourString .' == '. $startOfCurrentHour->toString() );
                print_r("\n");

                print_r( 'cEnd: '. $endOfCurrentHourString .' == '. $endOfCurrentHour->toString() );
                print_r("\n\n");

                print_r( 'Event Time: '. $eventDateTimeString .' == '. $eventDateTime->toString() );
                print_r("\n");

                print_r( 'eStart: '. $startEventDateRangeString .' == '. $startEventDateRange->toString() );
                print_r("\n");

                print_r( 'eEnd: '. $endEventDateRangeString .' == '. $endEventDateRange->toString() );
                print_r("\n\n");
                */
            }
        }
        return $ret;
    }

    /*!
     Send event start notification email
    */
    function sendEventStartNotificationEmail( $userObject, $node )
    {
        // Settings
        $sini = eZINI::instance( "site.ini" );
        $siteUrl = $sini->variable( "SiteSettings", "SiteURL" );

        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventStartDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierStartDateTime" );
        $eventEndDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierEndDateTime" );

        $eventName = $node->attribute('name');
        $eventUrl = $node->attribute('url_alias');
        $eventDataMap = $node->dataMap();

        $eventFromDateContent = $eventDataMap[$eventStartDateTimeIdentifier];
        $eventFromDate = $eventFromDateContent->content();

        $eventToDateContent = $eventDataMap[$eventEndDateTimeIdentifier];
        $eventToDate = $eventToDateContent->content();

        // Fetch Current User Session / Email
        $userID = $userObject->ContentObjectID;
        $user = eZUser::fetch( $userID );
        $userEmail = $user->Email;

        $currentUserObject = $userObject;
        $currentUserObjectDataMap = $currentUserObject->dataMap();

        // Fetch User First Name
        $attributeFirstName = $currentUserObjectDataMap['first_name'];
        $attributeLastName = $currentUserObjectDataMap['last_name'];
        $firstName = $attributeFirstName->content();
        $lastName = $attributeLastName->content();

        // Build email to user
        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();

        // Fetch site hostname
        // $tpl->setVariable( 'site_host', $siteUrl );

        // Fetch event information
        $tpl->setVariable( 'user_first_name', $firstName );
        $tpl->setVariable( 'user_last_name', $lastName );

        $tpl->setVariable( 'event_name', $eventName );
        $tpl->setVariable( 'event_url', "http://". $siteUrl."/". $eventUrl );
        $tpl->setVariable( 'event_from_date', $eventFromDateContent );
        $tpl->setVariable( 'event_to_date', $eventToDateContent );

        // Send email to user
        $subject = "Event Start Notification: " . $eventName;
        $to = $userEmail;

        $body = $tpl->fetch("design:eventnotifications/eventstart.tpl");
        $results = $this->sendNotificationEmail( $to, $subject, $body );

        return $results;
    }

    /*!
     Send event end notification email
    */
    function sendEventEndNotificationEmail( $userObject, $node )
    {
        // Settings
        $sini = eZINI::instance( "site.ini" );
        $siteUrl = $sini->variable( "SiteSettings", "SiteURL" );

        $ini = eZINI::instance( "bceventnotifications.ini" );
        $eventStartDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierStartDateTime" );
        $eventEndDateTimeIdentifier = $ini->variable( "EventNotificationsSettings", "EventClassAttributeIdentifierEndDateTime" );

        $eventName = $node->attribute('name');
        $eventUrl = $node->attribute('url_alias');
        $eventDataMap = $node->dataMap();

        $eventFromDateContent = $eventDataMap[$eventStartDateTimeIdentifier];
        $eventFromDate = $eventFromDateContent->content();

        $eventToDateContent = $eventDataMap[$eventEndDateTimeIdentifier];
        $eventToDate = $eventToDateContent->content();

        // Fetch Current User Session / Email
        $userID = $userObject->ContentObjectID;
        $user = eZUser::fetch( $userID );
        $userEmail = $user->Email;

        $currentUserObject = $userObject;
        $currentUserObjectDataMap = $currentUserObject->dataMap();

        // Fetch User First Name
        $attributeFirstName = $currentUserObjectDataMap['first_name'];
        $attributeLastName = $currentUserObjectDataMap['last_name'];
        $firstName = $attributeFirstName->content();
        $lastName = $attributeLastName->content();

        // Build email to user
        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();

        // Fetch site hostname
        // $tpl->setVariable( 'site_host', $siteUrl );

        // Fetch event information
        $tpl->setVariable( 'user_first_name', $firstName );
        $tpl->setVariable( 'user_last_name', $lastName );

        $tpl->setVariable( 'event_name', $eventName );
        $tpl->setVariable( 'event_url', "http://". $siteUrl."/". $eventUrl );
        $tpl->setVariable( 'event_from_date', $eventFromDateContent );
        $tpl->setVariable( 'event_to_date', $eventToDateContent );

        // Send email to user
        $subject = "Event End Notification: " . $eventName;
        $to = $userEmail;

        $body = $tpl->fetch("design:eventnotifications/eventend.tpl");
        $results = $this->sendNotificationEmail( $to, $subject, $body );

        return $results;
    }

    /*!
     Send Notification Email
    */
    function sendNotificationEmail( $to=false, $subject=false, $body=false )
    {
        $ret = false;
        if( $to != false && $subject != false && $body != false)
        {
            include_once( 'lib/ezutils/classes/ezmail.php' );
            include_once( 'lib/ezutils/classes/ezmailtransport.php' );

            $mail = new eZMail();
            $mail->setReceiver( $to );
            $mail->setSubject( $subject );
            $mail->setBody( $body );

            // print_r( $mail ); // die();
            $ret = eZMailTransport::send( $mail );
        }
        return $ret;
    }

    /*!
     Login a different user id (avoid ez permissions issues as anon user)
     From: eZAdmin's Class eZUserAddition::loginDifferentUser( 14 );
    */
    function loginDifferentUser( $user_id ) //, $attributes_to_export, $seperationChar )
    {
        $http =& eZHTTPTool::instance();
        $currentuser =& eZUser::currentUser();
        $user =& eZUser::fetch( $user_id );

        if ($user==null)
            return false;

        //bye old user
        $currentID = $http->sessionVariable( 'eZUserLoggedInID' );
        $http  =& eZHTTPTool::instance();
        $currentuser->logoutCurrent();

        //welcome new user
        $user->loginCurrent();
        $http->setSessionVariable( 'eZUserAdditionOldID', $user_id );

        return true;
    }

    // Variables
}

?>