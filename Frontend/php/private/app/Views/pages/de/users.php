<article>
	<section class="actions">
		<ul>
			<li>
				<a href="#" class="add-user"><i class="material-symbols-rounded mr12">add</i>hinzufügen</a>
			</li>
		</ul>
	</section>
	<section id="add-user" class="hidden">
		<h2>Neuen Benutzer erstellen</h2>
		<?php 
			$form = \Webapp\Core\Form::getInstance();
			$form->render(
				children: [
					$form->grid(
						left: 12,
						leftTabletPortrait: 6,
						leftTabletLandscape: 3,
						splitEvenly: true,
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
							$form->label(
								title: 'Passwort',
								titleAfterChildren: true,
								children: [
									$form->input(
										type: \Webapp\Core\FormInputType::Password,
										name: 'password',
										required: true,
		
									),
								],
							),
						],
					),
					'<div class="text-right">',
					$form->button(
						type: \Webapp\Core\FormButtonType::Submit,
						name: 'create-user',
						value: '<i class="material-symbols-rounded">add</i>',
					),
					'</div>',
				],
			);
		?>
	</section>
	<section>
		<h1>Benutzerverwaltung</h1>
		<div class="grid col-1 col-2-tp col-3-tl">
			<?=$this->data['usersHTML']?>
		</div>
	</section>
</article>