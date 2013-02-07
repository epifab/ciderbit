<?php /*
<form class="dataedit" id="fileupload" action="<?php print $this->api->path('file/upload'); ?>" method="POST" enctype="multipart/form-data">
	<fieldset>
		<legend>Attachement</legend>
		<div class="de-row">
			<div class="de-cell">
				<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
				<div class="row fileupload-buttonbar">
					<div class="span7">
						<!-- The fileinput-button span is used to style the file input field as button -->
						<span class="btn btn-success fileinput-button">
							<i class="icon-plus icon-white"></i>
							<span>Add files...</span>
							<input type="file" name="files[]" multiple>
						</span>
						<button type="submit" class="btn btn-primary start">
							<i class="icon-upload icon-white"></i>
							<span>Start upload</span>
						</button>
						<button type="reset" class="btn btn-warning cancel">
							<i class="icon-ban-circle icon-white"></i>
							<span>Cancel upload</span>
						</button>
						<button type="button" class="btn btn-danger delete">
							<i class="icon-trash icon-white"></i>
							<span>Delete</span>
						</button>
						<input type="checkbox" class="toggle">
					</div>
					<!-- The global progress information -->
					<div class="span5 fileupload-progress fade">
						<!-- The global progress bar -->
						<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
							<div class="bar" style="width:0%;"></div>
						</div>
						<!-- The extended global progress information -->
						<div class="progress-extended">&nbsp;</div>
					</div>
				</div>
				<!-- The loading indicator is shown during file processing -->
				<div classs="fileupload-loading"></div>
				<!-- The table listing the files available for upload/download -->
				<table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
			</div>
		</div>
	</fieldset>
</form>
*/ ?>

<form class="dataedit" id="fileupload" action="<?php print $this->api->path('file/upload'); ?>" method="POST" enctype="multipart/form-data">
	<fieldset>
		<legend><?php print $this->api->t("Image"); ?></legend>
		<div class="de-row">
			<div class="de-label-wrapper">
				<span class="de-label"><?php $this->api->t("Image"); ?></span>
			</div>
			<div class="de-input-wrapper">
				<input type="hidden" name="system[requestId]" value="<?php print $system['component']['requestId']; ?>"/>
				<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
				<div class="row fileupload-buttonbar">
					<div class="span7">
						<!-- The fileinput-button span is used to style the file input field as button -->
						<span class="btn btn-success fileinput-button">
							<i class="icon-plus icon-white"></i>
							<span>Add file...</span>
							<input type="file" name="files[]" multiple>
						</span>
						<button type="submit" class="btn btn-primary start">
							<i class="icon-upload icon-white"></i>
							<span>Start upload</span>
						</button>
						<button type="reset" class="btn btn-warning cancel">
							<i class="icon-ban-circle icon-white"></i>
							<span>Cancel upload</span>
						</button>
					</div>
					<!-- The global progress information -->
					<div class="span5 fileupload-progress fade">
						<!-- The global progress bar -->
						<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
							<div class="bar" style="width:0%;"></div>
						</div>
						<!-- The extended global progress information -->
						<div class="progress-extended">&nbsp;</div>
					</div>
				</div>
				<!-- The loading indicator is shown during file processing -->
				<div classs="fileupload-loading"></div>
				<!-- The table listing the files available for upload/download -->
				<table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
			</div>
		</div>
	</fieldset>
</form>

<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="preview"><span class="fade"></span></td>
        <td class="name"><span>{%=file.name%}</span></td>
        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td>
        {% } else if (o.files.valid && !i) { %}
            <td>
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
            </td>
            <td class="start">{% if (!o.options.autoUpload) { %}
                <button class="btn btn-primary">
                    <i class="icon-upload icon-white"></i>
                    <span>Start</span>
                </button>
            {% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
        <td class="cancel">{% if (!i) { %}
            <button class="btn btn-warning">
                <i class="icon-ban-circle icon-white"></i>
                <span>Cancel</span>
            </button>
        {% } %}</td>
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        {% if (file.error) { %}
            <td></td>
            <td class="name"><span>{%=file.name%}</span></td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td>
        {% } else { %}
            <td class="preview">{% if (file.thumbnail_url) { %}
                <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"/></a>
            {% } %}</td>
            <td class="name">
                <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a>
            </td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            <td colspan="2"></td>
        {% } %}
        <td class="delete">
            <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %?>
                <i class="icon-trash icon-white"></i>
                <span>Delete</span>
            </button>
        </td>
    </tr>
{% } %}
</script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="http://blueimp.github.com/JavaScript-Templates/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="http://blueimp.github.com/JavaScript-Load-Image/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="http://blueimp.github.com/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js"></script>
<!-- jQuery Image Gallery -->
<script src="http://blueimp.github.com/jQuery-Image-Gallery/js/jquery.image-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="<?php print $this->api->path('js/jquery-file-upload/js/jquery.iframe-transport.js'); ?>"></script>
<!-- The basic File Upload plugin -->
<script src="<?php print $this->api->path('js/jquery-file-upload/js/jquery.fileupload.js'); ?>"></script>
<!-- The File Upload file processing plugin -->
<script src="<?php print $this->api->path('js/jquery-file-upload/js/jquery.fileupload-fp.js'); ?>"></script>
<!-- The File Upload user interface plugin -->
<script src="<?php print $this->api->path('js/jquery-file-upload/js/jquery.fileupload-ui.js'); ?>"></script>
<!-- The File Upload jQuery UI plugin -->
<script src="<?php print $this->api->path('js/jquery-file-upload/js/jquery.fileupload-jui.js'); ?>"></script>


<?php $this->api->open('deForm'); ?>
	<?php if ($node->id): ?>
	<input type="hidden" name="id" value="<?php print $node->getEdit('id'); ?>"/>
	<?php endif; ?>

	<div class="dataedit">
		<fieldset>
			<legend>
				<?php foreach ($system['langs'] as $lang): ?>
				<a href="#" id="node-lang-<?php print $lang; ?>" class="node-lang-control show-hide-class<?php if ($lang == $website->defaultLang): ?> expanded<?php endif; ?>">
					<img src="<?php print $this->api->theme_path('img/lang/40/' . $lang . '.jpg'); ?>"/>
				</a>
				<?php endforeach; ?>
			</legend>
			<?php foreach ($system['langs'] as $lang): ?>
			<div class="node-lang node-lang-<?php print $lang; ?>">
				<div class="de-row">
					<div class="de-label-wrapper">
						
					</div>
					<div class="de-input-wrapper">
						<input type="checkbox"
							class="de-input show-hide-class" 
							name="node[text_<?php print $lang; ?>.enable]"<?php if ($node->__get('text_' . $lang)->lang): ?> checked="checked"<?php endif; ?>
							id="edit-node-text_<?php print $lang; ?>-enable"/>
						<label for="edit-node-text_<?php print $lang; ?>-enable"><?php print $this->api->t("Content available for this language."); ?></label>
					</div>
				</div>
				<div id="node-lang-<?php print $lang; ?>-fields" class="edit-node-text_<?php print $lang; ?>-enable">
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-urn"><?php print $this->api->t("URN"); ?></label>
						</div>
						<div class="de-input-wrapper">
							<?php $this->api->deInput($node, 'text_' . $lang . '.urn', array('class' => 'xl')); ?>
							<div class="de-info">
								<p>
									<?php print $this->api->t("Once you choose a URN you shouldn't change it anymore."); ?><br/>
									<?php print $this->api->t("In order to get the highest rating from search engines you should choose a URN containing important keywords directly related to the content itself."); ?>
									<?php print $this->api->t("Each word should be separeted by the dash characted."); ?>
								</p>
								<p>
									<?php print $this->api->t("Please note also that two different contents, translated in @lang, must have two different URNs.", array('@lang' => $this->api->t($lang))); ?>
								</p>
							</div>
							<?php print $this->api->deError('text_' . $lang . '.urn'); ?>
						</div>
					</div>
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-description"><?php print $this->api->t('Description'); ?></label>
						</div>
						<div class="de-input-wrapper">
							<input class="de-input xxl" type="text" id="edit-node-text_<?php print $lang; ?>-description" name="node[text_<?php print $lang; ?>.description]" value="<?php print $node->__get('text_'.$lang)->getEdit('description'); ?>"/>
							<div class="de-info">
								<p>
									<?php print $this->api->t("The description is not directly shown to the user but it's used as a meta-data for search engines purposes."); ?>
								</p>
							</div>
							<?php print $this->api->de_form_error('text_' . $lang . '.description'); ?>
						</div>
					</div>
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-title"><?php print $this->api->t('Title'); ?></label>
						</div>
						<div class="de-input-wrapper">
							<input class="de-input l" type="text" id="edit-node-text_<?php print $lang; ?>-title" name="node[text_<?php print $lang; ?>.title]" value="<?php print $node->__get('text_'.$lang)->getEdit('title'); ?>"/>
							<?php print $this->api->de_form_error('text_' . $lang . '.title'); ?>
						</div>
					</div>
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-subtitle"><?php print $this->api->t('Subtitle'); ?></label>
						</div>
						<div class="de-input-wrapper">
							<input class="de-input xl" type="text" id="edit-node-text_<?php print $lang; ?>-subtitle" name="node[text_<?php print $lang; ?>.subtitle]" value="<{$node->$text->getEdit('subtitle')?>"/>
							<?php print $this->api->de_form_error('text_' . $lang . '.subtitle'); ?>
						</div>
					</div>
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-body"><?php print $this->api->t('Body'); ?></label>
						</div>
						<div class="de-input-wrapper">
							<textarea class="de-input xxl rich-text" id="edit-node-text_<?php print $lang; ?>-body" name="node[text_<?php print $lang; ?>.body]"><{$node->$text->getEdit('body')?></textarea>
							<?php print $this->api->de_form_error('text_' . $lang . '.body'); ?>
						</div>
					</div>
					<div class="de-row">
						<div class="de-label-wrapper">
							<label class="de-label" for="edit-node-text_<?php print $lang; ?>-preview"><?php print $this->api->t('Preview'); ?></label>
						</div>
						<div class="de-input-wrapper">
							<textarea class="de-input xxl rich-text" id="edit-node-text_<?php print $lang; ?>-preview" name="node[text_<?php print $lang; ?>.preview]"><{$node->$text->getEdit('preview')?></textarea>
							<?php print $this->api->de_form_error('text_' . $lang . '.preview'); ?>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</fieldset>

		<fieldset>
			<legend><?php print $this->api->t('Tags'); ?></legend>
			<div class="de-row">
				<div class="de-label-wrapper">
					<label class="de-label" for="edit-node-terms"/>
						<?php print $this->api->t('Tags'); ?>
					</label>
				</div>
				<div class="de-input-wrapper">
					<input type="text" class="de-input xl" name="node[tags]"/>
				</div>
			</div>
		</fieldset>
		<fieldset class="de-fieldset">
			<legend><?php print $this->api->t('Content access'); ?></legend>
			<div class="de-row">
				<div class="de-label-wrapper">
					<label class="de-label" for="edit-node-record_mode-users"><?php print $this->api->t('Content admininstrators'); ?></label>
				</div>
				<div class="de-input-wrapper">
					<input class="de-input xl" type="text" name="node[record_mode.users]" id="edit-node-record_mode-users" value=""/>
					<?php print $this->api->de_form_error("record_mode.users"); ?>
				</div>
			</div>
			<div class="de-row">
				<div class="de-label-wrapper">
					<label class="de-label" for="edit-node-record_mode-read_mode"><?php print $this->api->t('Read access'); ?></label>
				</div>
				<div class="de-input-wrapper">
					<select class="de-input l" id="edit-node-record_mode-read_mode" name="node[record_mode.read_mode]">
						<option value="2"<?php if ($node->record_mode->read_mode == 2): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Owner only'); ?></option>
						<option value="3"<?php if ($node->record_mode->read_mode == 3): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Content admins'); ?></option>
						<option value="4"<?php if ($node->record_mode->read_mode == 4): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Registered users'); ?></option>
						<option value="5"<?php if ($node->record_mode->read_mode == 5): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Anyone'); ?></option>
					</select>
					<?php print $this->api->de_form_error("record_mode.read_mode"); ?>
				</div>
			</div>
			<div class="de-row">
				<div class="de-label-wrapper">
					<label class="de-label" for="edit-node-record_mode-edit_mode"><?php print $this->api->t('Edit access'); ?></label>
				</div>
				<div class="de-input-wrapper">
					<select class="de-input l" id="edit-node-record_mode-edit_mode" name="node[record_mode.edit_mode]">
						<option value="1"<?php if ($node->record_mode->edit_mode == 1): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Nobody'); ?></option>
						<option value="2"<?php if ($node->record_mode->edit_mode == 2): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Owner only'); ?></option>
						<option value="3"<?php if ($node->record_mode->edit_mode == 3): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Content admins'); ?></option>
						<option value="4"<?php if ($node->record_mode->edit_mode == 4): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Registered users'); ?></option>
					</select>
					<?php print $this->api->de_form_error("record_mode.edit_mode"); ?>
				</div>
			</div>
			<div class="de-row">
				<div class="de-label-wrapper">
					<label class="de-label" for="edit-node-record_mode-delete_mode"><?php print $this->api->t('Delete access'); ?></label>
				</div>
				<div class="de-input-wrapper">
					<select class="de-input l" id="edit-node-record_mode-delete_mode" name="node[record_mode.delete_mode]">
						<option value="1"<?php if ($node->record_mode->delete_mode == 1): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Nobody'); ?></option>
						<option value="2"<?php if ($node->record_mode->delete_mode == 2): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Owner only'); ?></option>
						<option value="3"<?php if ($node->record_mode->delete_mode == 3): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Content admins'); ?></option>
						<option value="4"<?php if ($node->record_mode->delete_mode == 4): ?> selected="selected"<?php endif; ?>><?php print $this->api->t('Registered users'); ?></option>
					</select>
					<?php print $this->api->de_form_error("record_mode.delete_mode"); ?>
				</div>
			</div>
		</fieldset>
		<?php print $this->api->de_submit_control(); ?>
	</div>
<?php $this->api->close(); ?>