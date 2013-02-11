<{ciderbit_read_form}><{/ciderbit_read_form}>
<{ciderbit_read_content element="div"}>
<{if $page}>
<div class="page page1col">
	<{ciderbit_restricted_area component="EditPage" args=["id" => $page->id]}>
		<div class="page_controls">
			<{ciderbit_control component="EditPage" width=800 height=420 title="Modifica pagina" args=["id" => $page->id]}> 
			<{ciderbit_control component="EditContent" width=800 height=550 title="Aggiungi contenuto" args=["page_id" => $page->id]}>
			<{ciderbit_control component="DeletePage" confirm=true confirmTitle="La pagina verr&agrave; eliminata definitivamente" title="Elimina pagina" args=["id" => $page->id]}>
		</div>
	<{/ciderbit_restricted_area}>

	<div class="page_body">
		<div><{$page->getRead("body")}></div>
	</div>

	<!-- CONTENUTI SEZIONE LIVELLO 1 -->
	<{if count($page->contents) > 0}>
		<{include file="PageContent.tpl"}>
		<{foreach $page->contents as $content}>
			<div class="content">
				<{call name="content_display" content=$content level=0}>
			</div>
		<{/foreach}>
	<{/if}>
</div>
<{/if}>
<{/ciderbit_read_content}>