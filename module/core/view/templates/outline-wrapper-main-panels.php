<response
	type="<{$system.responseType}>"
	url="<{$system.component.url}>"
	title="<{$page.title}>"
	editFormId="<{edit_form_id}>"
	panelFormId="<{panel_form_id}>"
	panelFormName="<{panel_form_name}>"
	id="<?php print $system['component']['requestId']; ?>">
	<content><?php $this->api->open('panels'); ?><?php $this->api->include($system['templates']['main']); ?><?php $this->api->close(); ?></content>
	<javascript><?php $this->api->javascript(); ?></javascript>
</response>