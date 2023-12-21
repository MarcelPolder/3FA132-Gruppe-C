'use client';
import { getCsrfToken, useSession } from 'next-auth/react';
import { signIn } from 'next-auth/react';

// Styles
import styles from '@/styles/views/auth/login.module.scss';
import { getServerSession } from 'next-auth';
import { redirect } from 'next/navigation';
import { useState } from 'react';

interface FormElements extends HTMLFormControlsCollection {
	csrfToken: HTMLInputElement,
	username: HTMLInputElement,
	password: HTMLInputElement,
}
interface LoginForm extends HTMLFormElement {
	readonly elements: FormElements,
}

async function getData() {
	const token = await getCsrfToken();
	if (typeof token === 'undefined') {
		throw new Error('Failed to fetch the CSRF-Token');
	}
	return token;
}
async function authenticate(event: React.FormEvent<LoginForm>) {
	event.preventDefault();
	const res = await signIn('credentials', {
		redirect: false,
		username: event.currentTarget.elements.username.value,
		password: event.currentTarget.elements.password.value,
	});
	if (typeof res !== 'undefined') {
		if (res.ok) {
			const urlParams = new URLSearchParams(window.location.search);
			const redirect = urlParams.get('callbackUrl');
			if (redirect) {
				window.location.href = redirect;
			} else {
				window.location.href = "/";
			}
		}
	}
}
export default async function login(req: Request, res: Response) {
	const csrfToken = await getData();
	return (
		<div className={styles.page}>
			<div className={styles.box}>
				<h1>Anmelden</h1>
				<form onSubmit={authenticate}>
					<input type="hidden" name="csrfToken" defaultValue={csrfToken} />
					<label htmlFor="username">
						<input type="text" name="username" required/>
						<span>Benutzername</span>
					</label>
					<label htmlFor="password">
						<input type="password" name="password" required/>
						<span>Passwort</span>
					</label>
					<button type="submit" className='btn-primary full-width'>Anmelden</button>
				</form>
				<div className={styles.claim}>
					<div className={styles.authors}>
						<span>M. Kirchermeier</span>
						<span>M. Krug</span>
						<span>M. Polder</span>
						<span>O. Fuchs</span>
					</div>
					<p>Copyright © 2023</p>
				</div>
			</div>
		</div>
	)
}
