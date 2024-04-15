<section id="login">
	<div id="login-wrapper">
		<div id="login-container">
			<div id="login-user-circle">
				<i class="material-symbols-rounded s64">supervised_user_circle</i>
			</div>
			<h2>Anmelden</h2>
			<?php
			$form = \Webapp\Core\Form::getInstance();
	
			$form->render(children: [
					$form->label(
						title: 'Benutzername',
						titleAfterChildren: true,
						children: [
							$form->input(
								type: \Webapp\Core\FormInputType::Text,
								name: 'username',
								placeholder: "john.doe",
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
					"<div>",
					$form->button(
						type: \Webapp\Core\FormButtonType::Submit,
						name: 'login',
						value: '<i class="material-symbols-rounded mr12">login</i>Anmelden'
					),
					"</div>"
				],
			);
			?>
		</div>
	</div>
</section>