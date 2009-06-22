<?php
include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
/*
<form method="post" action={"content/action"|ezurl}> 
 Subject: <input type="text" name="Subject" value="" /><br />
Author: <input type="text" name="Author" value="" /><br />
Message: <textarea name="Message"></textarea><br />
            <input type="submit" name="AddComment" value="Submit" /> 
            <input name="ContentObjectID" type="hidden" value="{$node.object.id}" /> 
</form> 
*/

function eZComments_ContentActionHandler( &$module, &$http, &$objectID )
{
    if( $http->hasPostVariable( 'AddComment' ) )
        {
            // fetch object and read node ID
            $object =& eZContentObject::fetch( $objectID );
            $nodeID = $object->attribute( 'main_node_id' );
            
            // read user variable
            $subject = $http->postVariable( 'Subject' );
            $author = $http->postVariable( 'Author' );
            $message = $http->postVariable( 'Message' );
            
            // prepare new object data
            $parentNodeID = $nodeID;
            $userID = 10;
            $class = eZContentClass::fetchByIdentifier( 'comment' );
            $parentContentObjectTreeNode = eZContentObjectTreeNode::fetch( $parentNodeID );
            $parentContentObject = $parentContentObjectTreeNode->attribute( 'object' );
            $sectionID = $parentContentObject->attribute( 'section_id' );
            
            $contentObject =& $class->instantiate( $userID, $sectionID );
            $contentObjectID = $contentObject->attribute( 'id' );
 
            $nodeAssignment =& eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                                'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                                'parent_node' => $parentContentObjectTreeNode->attribute( 'node_id' ),
                                                                'is_main' => 1 ) );
            $nodeAssignment->store();
 
            $contentObjectAttributes =& $contentObject->contentObjectAttributes();
 
            $loopLenght = count( $contentObjectAttributes );
 
            // fill up content object attributes with user data
            for( $i = 0; $i < $loopLenght; $i++ )
                {
                    switch( $contentObjectAttributes[$i]->attribute( 'contentclass_attribute_identifier' ) )
                        {
                        case 'subject':
                            $contentObjectAttributes[$i]->setAttribute( 'data_text', $subject );
                            $contentObjectAttributes[$i]->store();
                            break;
                        case 'author':
                            $contentObjectAttributes[$i]->setAttribute( 'data_text', $author );
                            $contentObjectAttributes[$i]->store();
                            break;
                        case 'message':
                            $contentObjectAttributes[$i]->setAttribute( 'data_text', $message );
                            $contentObjectAttributes[$i]->store();
                            break;
                        }
                }
 
            $contentObject->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
            $contentObject->store();
 
            // publish new comment
            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID, 'version' => 1 ) );
 
            // redirect to weblog full view
            $module->redirectTo( '/content/view/full/' . $nodeID );
        }
}
?>