<article>
	<section class="actions">
		<ul>
			<li><a href="#" class="add-reading"><i class="material-symbols-rounded mr12 create-reading">add</i>hinzufügen</a></li>
		</ul>
	</section>
	<section id="add-reading" style="display: none;">
	<?php
		$form = \Webapp\Core\Form::getInstance();
		$form->render(
			children: [
				$form->label(
					title: 'Kommentar',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Text,
							name: 'reading[comment]',
						)
					]
				),
				$form->label(
					title: 'Datum',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Text,
							name: 'reading[date_of_reading]',
						)
					]
				),
				$form->label(
					title: 'Typ',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Text,
							name: 'reading[kind_of_meter]',
						)
					]
				),
				$form->label(
					title: 'Zählerstand',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Text,
							name: 'reading[meter_count]',
						)
					]
				),
				$form->label(
					title: 'Zählernummer',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Text,
							name: 'reading[meter_id]',
						)
					]
				),
				$form->label(
					title: 'Austausch',
					titleAfterChildren: true,
					children: [
						$form->input(
							type: \Webapp\Core\FormInputType::Checkbox,
							name: 'reading[substitute]',
						)
					]
				),
				'<div class="text-right">',
				$form->button(
					type: \Webapp\Core\FormButtonType::Submit,
					name: 'update-reading',
					value: '<i class="material-symbols-rounded">edit</i>',
				),
				"</div>"
			]
		)
		?>
	</section>
	<section id="readings">
		<div class="grid col-1 col-2-tp col-3-tl">
			
		</div>
	</section>
</article>