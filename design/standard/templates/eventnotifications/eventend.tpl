Hello {$user_first_name} {$user_last_name},

A event has recently ended.

Name: {$event_name}
Url: {$event_url}

Start Date: {attribute_view_gui attribute=$event_from_date}

{if ne( $event_to_date, '')}

End Date: {attribute_view_gui attribute=$event_to_date}


{/if}

If you attended this event please
complete our event satisfaction survey.

{$event_survey_url}

You can disable these notifications 
by logging into the site and editing
your account properties.
