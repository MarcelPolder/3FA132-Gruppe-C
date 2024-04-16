<?php
$form = \Webapp\Core\Form::getInstance();
?>
<article>
	<section class="actions">
		<ul>
			<li><a href="#" class="add-customer"><i class="material-symbols-rounded mr12">add</i>erstellen</a></li>
		</ul>
	</section>
	<?=$form->outputResponses()?>
	<section id="add-customer" class="hidden">
		<?php
			$form = \Webapp\Core\Form::getInstance();
			$form->render(
				children: [
					$form->label(
						title: 'Vorname',
						titleAfterChildren: true,
						children: [
							$form->input(
								type: \Webapp\Core\FormInputType::Text,
								name: 'firstname',
								required: true,
							),
						],
					),
					$form->label(
						title: 'Nachname',
						titleAfterChildren: true,
						children: [
							$form->input(
								type: \Webapp\Core\FormInputType::Text,
								name: 'lastname',
								required: true,
							),
						],
					),
					'<div class="text-right">',
					$form->button(
						type: \Webapp\Core\FormButtonType::Submit,
						name: 'create-customer',
						value: '<i class="material-symbols-rounded">add</i>',
					),
				],
			);
		?>
	</section>
	<section>
		<table>
			<thead>
				<tr>
					<th>Vorname</th>
					<th>Nachname</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="3">
						<div class="text-center">
							<div class="loader"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</section>
</article>