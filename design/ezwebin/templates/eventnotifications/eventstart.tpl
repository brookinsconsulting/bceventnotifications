Hello {$user_first_name} {$user_last_name},

A event will soon start!

Name: {$event_name}
Url: {$event_url}
{* $event_from_date|attribute(show,1)
*}
Start Date: {attribute_view_gui attribute=$event_from_date}

{if ne( $event_to_date, '')}

End Date: {attribute_view_gui attribute=$event_to_date}


{/if}

You can disable these notifications 
by logging into the site and editing
your account properties.
