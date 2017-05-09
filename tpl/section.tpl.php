<!--[section.title; strconv=no;]-->

<table class="border" width="100%">
	<tr>
		<td width="20%">[section.ref_title; strconv=no;]</td>
		<td>[section.ref; strconv=no;]</td>
	</tr>
	<tr>
		<td width="20%">[section.title_title; strconv=no;]</td>
		<td>[section.title; strconv=no;]</td>
	</tr>
	<tr>
		<td width="20%">[section.fk_usergroup_title; strconv=no;]</td>
		<td>[section.fk_usergroup; strconv=no;]</td>
	</tr>
        [onshow;block=begin;when [section.plan_id] != '']
	<tr>
		<td width="20%">[section.budget_title; strconv=no;]</td>
		<td>[section.budget; strconv=no;]</td>
	</tr>
        [onshow;block=end]
        [onshow;block=begin;when [section.plan_id] != '']
	<tr>
		<td width="20%">[section.fk_section_parente_title; strconv=no;]</td>
		<td>[section.fk_section_parente; strconv=no;]</td>
	</tr>
        [onshow;block=end]        
</table>

<br />

<center>[buttons.buttons; strconv=no;]</center>
