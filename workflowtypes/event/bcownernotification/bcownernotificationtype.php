<?php
//
// Definition of BCNotificationType class
//
// Created on: <23-Feb-2008 22:20:00 gb>
//
// Copyright (C) 1999-2008 Brookins Consulting. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 or later as published by
// the Free Software Foundation and appearing in the file LICENSE
// included in the packaging of this file.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@brookinsconsulting.com if any conditions
// of this licencing isn't clear to you.
//

/*! \file bcownernotificationtype.php
*/

/*!
  \class BCOwnerNotificationType bcownernotificationtype.php
  \brief The class BCOwnerNotificationType does provide owner notification rule creation after publish
*/

include_once( 'kernel/classes/workflowtypes/event/ezwaituntildate/ezwaituntildate.php' );

class BCOwnerNotificationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'bcownernotification';

    /*!
     Constructor
    */
    function BCOwnerNotificationType()
    {
        $this->eZWorkflowEventType( BCOwnerNotificationType::WORKFLOW_TYPE_STRING,
                                    ezi18n( 'kernel/workflow/event', "BC Create Owner Notification Rule" ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }

    function execute( $process, $event )
    {
        eZDebug::writeNotice( 'Executing notification cronjob.' );

        $notificationWorkflowINI = eZINI::instance( 'notificationworkflow.ini' );
        $parameters = $process->attribute( 'parameter_list' );
        $object = eZContentObject::fetch( $parameters['object_id'] );
        $user = eZUser::currentUser();

        if ( in_array( $object->attribute( 'class_identifier' ), $notificationWorkflowINI->variable( 'ClassSettings', 'ClassList' ) ) )
        {
            include_once( 'kernel/classes/notification/handler/ezsubtree/ezsubtreenotificationrule.php' );

            $nodeID = $object->attribute( 'main_node_id' );
            $nodeIDList = eZSubtreeNotificationRule::fetchNodesForUserID( $user->attribute( 'contentobject_id' ), false );

            if ( !in_array( $nodeID, $nodeIDList ) )
            {
                eZDebug::writeNotice( 'Added subtree notification for node ID: ' . $nodeID );
                $rule = eZSubtreeNotificationRule::create( $nodeID, $user->attribute( 'contentobject_id' ) );
                $rule->store();
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( BCOwnerNotificationType::WORKFLOW_TYPE_STRING, "BCOwnerNotificationType" );

?>